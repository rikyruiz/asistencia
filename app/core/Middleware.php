<?php
/**
 * Base Middleware Class
 */
abstract class Middleware {
    /**
     * Check if user is authenticated
     */
    public static function authenticate() {
        if (!isLoggedIn()) {
            // Save intended URL
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];

            // Redirect to login
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }

        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive >= SESSION_LIFETIME) {
                session_destroy();
                setFlash('login', 'Tu sesión ha expirado', 'warning');
                header('Location: ' . BASE_URL . 'auth/login');
                exit();
            }
        }

        // Update last activity
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Check if user has required role
     */
    public static function requireRole($roles) {
        // First check authentication
        self::authenticate();

        // Convert single role to array
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        // Check if user has one of the required roles
        if (!hasAnyRole($roles)) {
            self::unauthorized();
        }

        return true;
    }

    /**
     * Check if user is admin (admin or superadmin)
     */
    public static function requireAdmin() {
        return self::requireRole(['admin', 'superadmin']);
    }

    /**
     * Check if user is superadmin
     */
    public static function requireSuperAdmin() {
        return self::requireRole('superadmin');
    }

    /**
     * Check if user is employee
     */
    public static function requireEmployee() {
        return self::requireRole('empleado');
    }

    /**
     * Check if user is inspector
     */
    public static function requireInspector() {
        return self::requireRole('inspector');
    }

    /**
     * Check if user can view (inspector or higher)
     */
    public static function requireViewer() {
        return self::requireRole(['inspector', 'admin', 'superadmin']);
    }

    /**
     * Check if user can manage (admin or higher)
     */
    public static function requireManager() {
        return self::requireRole(['admin', 'superadmin']);
    }

    /**
     * Handle unauthorized access
     */
    private static function unauthorized() {
        // Log unauthorized access attempt
        logActivity('unauthorized_access', [
            'user_id' => getUserId(),
            'url' => $_SERVER['REQUEST_URI'],
            'role' => $_SESSION['user_role'] ?? 'none'
        ]);

        // If AJAX request, return JSON error
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        // Otherwise, redirect with error message
        setFlash('error', 'No tienes permiso para acceder a esta sección', 'error');

        // Redirect based on role
        $role = $_SESSION['user_role'] ?? '';
        switch ($role) {
            case 'empleado':
                header('Location: ' . BASE_URL . 'empleado/dashboard');
                break;
            case 'inspector':
                header('Location: ' . BASE_URL . 'inspector/dashboard');
                break;
            case 'admin':
            case 'superadmin':
                header('Location: ' . BASE_URL . 'admin/dashboard');
                break;
            default:
                header('Location: ' . BASE_URL);
        }
        exit();
    }

    /**
     * Validate CSRF token for POST requests
     */
    public static function validateCsrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrf()) {
                // Log CSRF failure
                logActivity('csrf_failure', [
                    'user_id' => getUserId(),
                    'url' => $_SERVER['REQUEST_URI']
                ]);

                // If AJAX request, return JSON error
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'CSRF token validation failed']);
                    exit();
                }

                // Otherwise, redirect with error
                setFlash('error', 'Token de seguridad inválido', 'error');
                header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL);
                exit();
            }
        }
        return true;
    }

    /**
     * Rate limiting
     */
    public static function rateLimit($key, $maxAttempts = 60, $window = 60) {
        $cacheKey = 'rate_limit_' . $key . '_' . getUserIP();
        $cacheFile = dirname(dirname(__DIR__)) . '/cache/' . md5($cacheKey) . '.tmp';

        // Get current attempts
        $attempts = 0;
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && $data['expires'] > time()) {
                $attempts = $data['attempts'];
            }
        }

        // Check if limit exceeded
        if ($attempts >= $maxAttempts) {
            // Log rate limit exceeded
            logActivity('rate_limit_exceeded', [
                'key' => $key,
                'ip' => getUserIP()
            ]);

            http_response_code(429);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Too many requests']);
            } else {
                echo 'Demasiadas solicitudes. Por favor, intenta más tarde.';
            }
            exit();
        }

        // Increment attempts
        $data = [
            'attempts' => $attempts + 1,
            'expires' => time() + $window
        ];
        file_put_contents($cacheFile, json_encode($data));

        return true;
    }

    /**
     * Check if user owns a resource
     */
    public static function checkOwnership($resourceUserId) {
        self::authenticate();

        // Admins can access everything
        if (hasAnyRole(['admin', 'superadmin'])) {
            return true;
        }

        // Check if user owns the resource
        if (getUserId() != $resourceUserId) {
            self::unauthorized();
        }

        return true;
    }

    /**
     * Sanitize all input data
     */
    public static function sanitizeInput() {
        $_GET = sanitize($_GET);
        $_POST = sanitize($_POST);
        $_REQUEST = sanitize($_REQUEST);
        return true;
    }

    /**
     * Set security headers
     */
    public static function setSecurityHeaders() {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\' https://cdn.tailwindcss.com https://fonts.googleapis.com https://fonts.gstatic.com;');
        return true;
    }
}