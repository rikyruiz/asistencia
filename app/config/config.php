<?php
/**
 * Configuration file for the Attendance System
 */

// Environment
define('ENVIRONMENT', 'production'); // 'development' or 'production'

// Base URLs
define('BASE_URL', 'https://asistencia.alpefresh.app/');
define('SITE_NAME', 'Sistema de Control de Asistencia - Alpe Fresh');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'asistencia_db');
define('DB_USER', 'ricruiz');
define('DB_PASS', 'Ruor7708028L8+');
define('DB_CHARSET', 'utf8mb4');

// Email Configuration
define('MAIL_HOST', 'smtp.hostinger.com');
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_USERNAME', 'notificaciones@alpefresh.app');
define('MAIL_PASSWORD', 'Alpe25879*');
define('MAIL_FROM_ADDRESS', 'notificaciones@alpefresh.app');
define('MAIL_FROM_NAME', 'Sistema de Asistencia - Alpe Fresh');

// Security
define('SESSION_NAME', 'asistencia_session');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SECURE_COOKIES', true); // Set to true in production with HTTPS
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds

// PIN Configuration
define('PIN_LENGTH', 6);
define('PIN_MAX_ATTEMPTS', 5);
define('PIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// Geolocation Configuration
define('GEOFENCE_TOLERANCE', 10); // meters
define('DEFAULT_RADIUS', 100); // meters
define('MAX_RADIUS', 500); // meters
define('MIN_RADIUS', 50); // meters

// File Upload Configuration
define('UPLOAD_PATH', dirname(dirname(__DIR__)) . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Timezone
date_default_timezone_set('America/Mexico_City');

// Error Reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Autoload function
spl_autoload_register(function ($class) {
    $paths = [
        dirname(__DIR__) . '/core/',
        dirname(__DIR__) . '/models/',
        dirname(__DIR__) . '/controllers/',
        dirname(__DIR__) . '/helpers/'
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load helper functions
require_once dirname(__DIR__) . '/helpers/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}