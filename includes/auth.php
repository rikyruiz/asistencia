<?php
/**
 * Sistema de Autenticación
 * Maneja login con email/contraseña y PIN de empleado
 */

session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    /**
     * Login con email y contraseña
     */
    public static function loginWithEmail($email, $password) {
        try {
            $db = db();
            $stmt = $db->prepare("
                SELECT id, empresa_id, departamento_id, codigo_empleado, email, password,
                       nombre, apellidos, telefono, rol, foto_url, activo, estado_aprobacion
                FROM usuarios
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Verificar estado de aprobación
                if (isset($user['estado_aprobacion']) && $user['estado_aprobacion'] === 'pendiente') {
                    return ['success' => false, 'message' => 'Tu cuenta está pendiente de aprobación'];
                }

                // Verificar si está activo
                if (!$user['activo']) {
                    return ['success' => false, 'message' => 'Tu cuenta está desactivada'];
                }

                // Verificar contraseña
                if (!password_verify($password, $user['password'])) {
                    return ['success' => false, 'message' => 'Credenciales incorrectas'];
                }
                // Actualizar último acceso
                $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                // Guardar en sesión
                self::setUserSession($user);
                return ['success' => true, 'user' => $user];
            }

            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        } catch (Exception $e) {
            error_log("Error en login con email: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en el sistema'];
        }
    }

    /**
     * Login con código de empleado y PIN
     */
    public static function loginWithPin($codigo_empleado, $pin) {
        try {
            $db = db();

            // Verificar si el usuario está bloqueado
            $stmt = $db->prepare("
                SELECT id, pin_bloqueado_hasta
                FROM usuarios
                WHERE codigo_empleado = ? AND activo = 1
            ");
            $stmt->execute([$codigo_empleado]);
            $check = $stmt->fetch();

            if ($check && $check['pin_bloqueado_hasta']) {
                $bloqueado_hasta = new DateTime($check['pin_bloqueado_hasta']);
                $ahora = new DateTime();

                if ($bloqueado_hasta > $ahora) {
                    $minutos = $bloqueado_hasta->diff($ahora)->i;
                    return [
                        'success' => false,
                        'message' => "PIN bloqueado. Intenta en {$minutos} minutos."
                    ];
                }
            }

            // Intentar login
            $stmt = $db->prepare("
                SELECT id, empresa_id, departamento_id, codigo_empleado, email, pin,
                       nombre, apellidos, telefono, rol, foto_url, activo, pin_intentos
                FROM usuarios
                WHERE codigo_empleado = ? AND activo = 1
            ");
            $stmt->execute([$codigo_empleado]);
            $user = $stmt->fetch();

            if ($user && password_verify($pin, $user['pin'])) {
                // Resetear intentos
                $updateStmt = $db->prepare("
                    UPDATE usuarios
                    SET ultimo_acceso = NOW(),
                        pin_intentos = 0,
                        pin_bloqueado_hasta = NULL
                    WHERE id = ?
                ");
                $updateStmt->execute([$user['id']]);

                // Guardar en sesión
                self::setUserSession($user);
                return ['success' => true, 'user' => $user];
            } else if ($user) {
                // Incrementar intentos fallidos
                $intentos = ($user['pin_intentos'] ?? 0) + 1;
                $bloqueado_hasta = null;

                if ($intentos >= 3) {
                    $bloqueado_hasta = (new DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s');
                }

                $updateStmt = $db->prepare("
                    UPDATE usuarios
                    SET pin_intentos = ?,
                        pin_bloqueado_hasta = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$intentos, $bloqueado_hasta, $user['id']]);

                $intentos_restantes = 3 - $intentos;
                if ($intentos_restantes > 0) {
                    return [
                        'success' => false,
                        'message' => "PIN incorrecto. {$intentos_restantes} intentos restantes."
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => "PIN bloqueado por 15 minutos debido a múltiples intentos fallidos."
                    ];
                }
            }

            return ['success' => false, 'message' => 'Código de empleado no encontrado'];
        } catch (Exception $e) {
            error_log("Error en login con PIN: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en el sistema'];
        }
    }

    /**
     * Establecer sesión de usuario
     */
    private static function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['empresa_id'] = $user['empresa_id'];
        $_SESSION['departamento_id'] = $user['departamento_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nombre'] = $user['nombre'] . ' ' . $user['apellidos'];
        $_SESSION['user_rol'] = $user['rol'];
        $_SESSION['user_foto'] = $user['foto_url'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Verificar rol del usuario
     */
    public static function hasRole($roles) {
        if (!self::isLoggedIn()) {
            return false;
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        return in_array($_SESSION['user_rol'], $roles);
    }

    /**
     * Obtener datos del usuario actual
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'empresa_id' => $_SESSION['empresa_id'],
            'departamento_id' => $_SESSION['departamento_id'],
            'email' => $_SESSION['user_email'],
            'nombre' => $_SESSION['user_nombre'],
            'rol' => $_SESSION['user_rol'],
            'foto' => $_SESSION['user_foto']
        ];
    }

    /**
     * Cerrar sesión
     */
    public static function logout() {
        session_destroy();
        session_start();
    }

    /**
     * Redirigir si no está autenticado
     */
    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * Redirigir si no tiene el rol requerido
     */
    public static function requireRole($roles) {
        self::requireAuth();

        if (!self::hasRole($roles)) {
            header('Location: /dashboard.php?error=unauthorized');
            exit;
        }
    }

    /**
     * Obtener URL de redirección según dispositivo y preferencias de usuario
     * Solo aplica para móviles - Desktop siempre va a dashboard
     */
    public static function getMobileRedirect() {
        // Detectar si es móvil
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isMobile = preg_match('/(android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile)/i', $userAgent)
            && !preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $userAgent);

        // Desktop siempre va a dashboard
        if (!$isMobile) {
            return 'dashboard.php';
        }

        // Obtener preferencia del usuario
        try {
            $db = db();
            $stmt = $db->prepare("SELECT mobile_landing_preference FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            $preference = $user['mobile_landing_preference'] ?? 'clock';

            switch ($preference) {
                case 'dashboard':
                    return 'dashboard.php';
                case 'clock':
                    return 'asistencias.php';
                case 'ask':
                    return 'mobile-preference.php';
                default:
                    return 'asistencias.php'; // Default to clock for mobile
            }
        } catch (Exception $e) {
            error_log("Error obteniendo preferencia mobile: " . $e->getMessage());
            return 'asistencias.php'; // Mobile goes to clock by default on error
        }
    }
}