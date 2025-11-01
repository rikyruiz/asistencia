<?php
/**
 * OAuth2 Handler
 * Maneja la autenticación con Google y Microsoft
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

class OAuth2Handler {
    private $config;
    private $provider;
    private $db;

    public function __construct($provider) {
        $this->config = include __DIR__ . '/../config/oauth.php';
        $this->provider = $provider;
        $this->db = db();

        if (!isset($this->config[$provider])) {
            throw new Exception("Proveedor OAuth no configurado: $provider");
        }
    }

    /**
     * Genera la URL de autorización OAuth
     */
    public function getAuthorizationUrl() {
        $config = $this->config[$this->provider];

        // Generar state para prevenir CSRF
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_provider'] = $this->provider;

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => $config['scopes'],
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'select_account'
        ];

        // Microsoft requiere response_mode
        if ($this->provider === 'microsoft') {
            $params['response_mode'] = 'query';
        }

        return $config['auth_url'] . '?' . http_build_query($params);
    }

    /**
     * Intercambia el código de autorización por un access token
     */
    public function getAccessToken($code) {
        $config = $this->config[$this->provider];

        $params = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($config['token_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Error obteniendo access token: $response");
        }

        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            throw new Exception("No se recibió access token");
        }

        return $data['access_token'];
    }

    /**
     * Obtiene la información del usuario usando el access token
     */
    public function getUserInfo($accessToken) {
        $config = $this->config[$this->provider];

        $ch = curl_init($config['user_info_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Error obteniendo información del usuario: $response");
        }

        $data = json_decode($response, true);

        // Normalizar datos según el proveedor
        return $this->normalizeUserData($data);
    }

    /**
     * Normaliza los datos del usuario según el proveedor
     */
    private function normalizeUserData($data) {
        $normalized = [];

        switch ($this->provider) {
            case 'google':
                $normalized = [
                    'email' => $data['email'] ?? '',
                    'name' => $data['name'] ?? '',
                    'given_name' => $data['given_name'] ?? '',
                    'family_name' => $data['family_name'] ?? '',
                    'picture' => $data['picture'] ?? '',
                    'provider_id' => $data['sub'] ?? ''
                ];
                break;

            case 'microsoft':
                $normalized = [
                    'email' => $data['mail'] ?? $data['userPrincipalName'] ?? '',
                    'name' => $data['displayName'] ?? '',
                    'given_name' => $data['givenName'] ?? '',
                    'family_name' => $data['surname'] ?? '',
                    'picture' => null, // Microsoft no proporciona foto directamente
                    'provider_id' => $data['id'] ?? ''
                ];
                break;
        }

        $normalized['provider'] = $this->provider;
        return $normalized;
    }

    /**
     * Crea o actualiza el usuario en la base de datos
     */
    public function findOrCreateUser($userData) {
        try {
            // Buscar usuario existente por email
            $stmt = $this->db->prepare("
                SELECT id, empresa_id, email, nombre, apellidos, rol, activo
                FROM usuarios
                WHERE email = ?
            ");
            $stmt->execute([$userData['email']]);
            $user = $stmt->fetch();

            if ($user) {
                // Usuario existe - actualizar última conexión y foto si cambió
                if (!empty($userData['picture'])) {
                    $updateStmt = $this->db->prepare("
                        UPDATE usuarios
                        SET ultimo_acceso = NOW(),
                            foto_url = ?,
                            oauth_provider = ?,
                            oauth_provider_id = ?
                        WHERE id = ?
                    ");
                    $updateStmt->execute([
                        $userData['picture'],
                        $userData['provider'],
                        $userData['provider_id'],
                        $user['id']
                    ]);
                } else {
                    $updateStmt = $this->db->prepare("
                        UPDATE usuarios
                        SET ultimo_acceso = NOW(),
                            oauth_provider = ?,
                            oauth_provider_id = ?
                        WHERE id = ?
                    ");
                    $updateStmt->execute([
                        $userData['provider'],
                        $userData['provider_id'],
                        $user['id']
                    ]);
                }

                return $user;
            } else {
                // Crear nuevo usuario
                // Primero, obtener empresa_id por defecto o crear una
                $empresaStmt = $this->db->query("SELECT id FROM empresas WHERE activa = 1 LIMIT 1");
                $empresa = $empresaStmt->fetch();

                if (!$empresa) {
                    // Crear empresa por defecto
                    $createEmpresaStmt = $this->db->prepare("
                        INSERT INTO empresas (nombre, rfc, activa)
                        VALUES ('Empresa Principal', 'XAXX010101000', 1)
                    ");
                    $createEmpresaStmt->execute();
                    $empresa_id = $this->db->lastInsertId();
                } else {
                    $empresa_id = $empresa['id'];
                }

                // Generar código de empleado único
                $codigo_empleado = 'EMP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                // Insertar nuevo usuario
                $insertStmt = $this->db->prepare("
                    INSERT INTO usuarios (
                        empresa_id, codigo_empleado, email, password,
                        nombre, apellidos, rol, foto_url, activo,
                        oauth_provider, oauth_provider_id, ultimo_acceso
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                    )
                ");

                $insertStmt->execute([
                    $empresa_id,
                    $codigo_empleado,
                    $userData['email'],
                    password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), // Password aleatorio
                    $userData['given_name'],
                    $userData['family_name'],
                    'empleado', // Rol por defecto
                    $userData['picture'],
                    1, // Activo
                    $userData['provider'],
                    $userData['provider_id']
                ]);

                // Obtener el usuario recién creado
                $newUserId = $this->db->lastInsertId();
                $stmt = $this->db->prepare("
                    SELECT id, empresa_id, email, nombre, apellidos, rol, activo, foto_url
                    FROM usuarios
                    WHERE id = ?
                ");
                $stmt->execute([$newUserId]);
                return $stmt->fetch();
            }
        } catch (Exception $e) {
            error_log("Error en findOrCreateUser: " . $e->getMessage());
            throw new Exception("Error procesando usuario OAuth: " . $e->getMessage());
        }
    }

    /**
     * Completa el proceso de login OAuth
     */
    public function completeOAuthLogin($userData) {
        $user = $this->findOrCreateUser($userData);

        if (!$user || !$user['activo']) {
            throw new Exception("Usuario no autorizado o inactivo");
        }

        // Establecer sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['empresa_id'] = $user['empresa_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nombre'] = $user['nombre'] . ' ' . $user['apellidos'];
        $_SESSION['user_rol'] = $user['rol'];
        $_SESSION['user_foto'] = $user['foto_url'] ?? null;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['oauth_provider'] = $this->provider;

        return true;
    }
}