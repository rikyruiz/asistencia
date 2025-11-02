<?php
/**
 * User Model
 */
class User extends Model {
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $fillable = [
        'email', 'nombre', 'apellidos', 'pin', 'rol',
        'departamento', 'numero_empleado', 'activo', 'email_verificado',
        'intentos_login', 'bloqueado_hasta', 'ultimo_login',
        'token_verificacion', 'token_recuperacion', 'token_expiracion',
        'foto_perfil', 'telefono', 'direccion', 'fecha_ingreso', 'creado_por'
    ];
    protected $hidden = ['pin', 'token_verificacion', 'token_recuperacion'];

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        return $this->findBy('email', $email);
    }

    /**
     * Find user by username
     */
    public function findByUsername($username) {
        return $this->findBy('username', $username);
    }

    /**
     * Find user by employee number
     */
    public function findByEmployeeNumber($number) {
        return $this->findBy('numero_empleado', $number);
    }

    /**
     * Generate next employee number
     */
    public function generateEmployeeNumber() {
        $sql = "SELECT numero_empleado FROM usuarios
                WHERE numero_empleado REGEXP '^EMP[0-9]+$'
                ORDER BY CAST(SUBSTRING(numero_empleado, 4) AS UNSIGNED) DESC
                LIMIT 1";

        $result = $this->db->selectOne($sql);

        if ($result && isset($result['numero_empleado'])) {
            // Extract the number part and increment
            $lastNumber = intval(substr($result['numero_empleado'], 3));
            $nextNumber = $lastNumber + 1;
        } else {
            // Start from 1 if no employees exist
            $nextNumber = 1;
        }

        return 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Authenticate user with username and PIN
     */
    public function authenticate($username, $pin) {
        $user = $this->findByUsername($username);

        if (!$user) {
            return false;
        }

        // Check if account is locked
        if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
            return ['error' => 'account_locked', 'until' => $user['bloqueado_hasta']];
        }

        // Verify PIN
        if (!verifyPin($pin, $user['pin'])) {
            // Increment failed attempts
            $this->incrementFailedAttempts($user['id']);
            return false;
        }

        // Reset failed attempts and update last login
        $this->update($user['id'], [
            'intentos_login' => 0,
            'bloqueado_hasta' => null,
            'ultimo_login' => getCurrentDateTime()
        ]);

        return $user;
    }

    /**
     * Increment failed login attempts
     */
    private function incrementFailedAttempts($userId) {
        $user = $this->find($userId);
        $attempts = $user['intentos_login'] + 1;

        $updateData = ['intentos_login' => $attempts];

        // Lock account if max attempts reached
        if ($attempts >= PIN_MAX_ATTEMPTS) {
            $updateData['bloqueado_hasta'] = date('Y-m-d H:i:s', time() + PIN_LOCKOUT_TIME);
            $updateData['intentos_login'] = 0; // Reset counter
        }

        $this->update($userId, $updateData);
    }

    /**
     * Get user's locations
     */
    public function getLocations($userId) {
        $sql = "SELECT u.*, ul.es_principal
                FROM ubicaciones u
                JOIN usuarios_ubicaciones ul ON u.id = ul.ubicacion_id
                WHERE ul.usuario_id = :user_id AND u.activa = 1
                ORDER BY ul.es_principal DESC, u.nombre";

        return $this->db->select($sql, ['user_id' => $userId]);
    }

    /**
     * Assign location to user
     */
    public function assignLocation($userId, $locationId, $isPrimary = false) {
        // If setting as primary, remove other primary locations
        if ($isPrimary) {
            $sql = "UPDATE usuarios_ubicaciones SET es_principal = 0 WHERE usuario_id = :user_id";
            $this->db->query($sql, ['user_id' => $userId]);
        }

        // Check if already assigned
        $sql = "SELECT id FROM usuarios_ubicaciones WHERE usuario_id = :user_id AND ubicacion_id = :location_id";
        $existing = $this->db->selectOne($sql, ['user_id' => $userId, 'location_id' => $locationId]);

        if ($existing) {
            // Update existing
            $sql = "UPDATE usuarios_ubicaciones SET es_principal = :is_primary WHERE id = :id";
            $this->db->query($sql, ['is_primary' => $isPrimary ? 1 : 0, 'id' => $existing['id']]);
        } else {
            // Insert new
            $this->db->insert('usuarios_ubicaciones', [
                'usuario_id' => $userId,
                'ubicacion_id' => $locationId,
                'es_principal' => $isPrimary ? 1 : 0
            ]);
        }
    }

    /**
     * Remove location from user
     */
    public function removeLocation($userId, $locationId) {
        $sql = "DELETE FROM usuarios_ubicaciones WHERE usuario_id = :user_id AND ubicacion_id = :location_id";
        return $this->db->query($sql, ['user_id' => $userId, 'location_id' => $locationId]);
    }

    /**
     * Get active employees
     */
    public function getActiveEmployees() {
        return $this->where(['activo' => 1, 'rol' => 'empleado'], 'nombre', 'ASC');
    }

    /**
     * Get users by role
     */
    public function getUsersByRole($role) {
        return $this->where(['rol' => $role, 'activo' => 1], 'nombre', 'ASC');
    }

    /**
     * Check if user has active session
     */
    public function hasActiveSession($userId) {
        $sql = "SELECT COUNT(*) as count FROM sesiones_trabajo
                WHERE usuario_id = :user_id AND estado = 'activa'";
        $result = $this->db->selectOne($sql, ['user_id' => $userId]);
        return $result && $result['count'] > 0;
    }

    /**
     * Get user's active session
     */
    public function getActiveSession($userId) {
        $sql = "SELECT st.*, u.nombre as ubicacion_nombre
                FROM sesiones_trabajo st
                LEFT JOIN ubicaciones u ON st.ubicacion_id = u.id
                WHERE st.usuario_id = :user_id AND st.estado = 'activa'
                LIMIT 1";
        return $this->db->selectOne($sql, ['user_id' => $userId]);
    }

    /**
     * Generate email verification token
     */
    public function generateVerificationToken($userId) {
        $token = generateToken();
        $expiration = date('Y-m-d H:i:s', time() + 86400); // 24 hours

        $this->update($userId, [
            'token_verificacion' => $token,
            'token_expiracion' => $expiration
        ]);

        return $token;
    }

    /**
     * Verify email with token
     */
    public function verifyEmail($token) {
        $sql = "SELECT * FROM usuarios WHERE token_verificacion = :token
                AND token_expiracion > NOW() LIMIT 1";
        $user = $this->db->selectOne($sql, ['token' => $token]);

        if (!$user) return false;

        return $this->update($user['id'], [
            'email_verificado' => 1,
            'token_verificacion' => null,
            'token_expiracion' => null
        ]);
    }

    /**
     * Generate password reset token
     */
    public function generateResetToken($email) {
        $user = $this->findByEmail($email);
        if (!$user) return false;

        $token = generateToken();
        $expiration = date('Y-m-d H:i:s', time() + 86400); // 24 hours

        $this->update($user['id'], [
            'token_recuperacion' => $token,
            'token_expiracion' => $expiration
        ]);

        return $token;
    }

    /**
     * Verify reset token
     */
    public function verifyResetToken($token) {
        $sql = "SELECT * FROM usuarios WHERE token_recuperacion = :token
                AND token_expiracion > NOW() LIMIT 1";
        return $this->db->selectOne($sql, ['token' => $token]);
    }

    /**
     * Reset PIN
     */
    public function resetPin($token, $newPin) {
        $user = $this->verifyResetToken($token);
        if (!$user) return false;

        return $this->update($user['id'], [
            'pin' => hashPin($newPin),
            'token_recuperacion' => null,
            'token_expiracion' => null
        ]);
    }
}