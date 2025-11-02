<?php
/**
 * Global Helper Functions
 */

/**
 * Sanitize input data
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate PIN (6 digits)
 */
function isValidPin($pin) {
    return preg_match('/^[0-9]{' . PIN_LENGTH . '}$/', $pin);
}

/**
 * Hash PIN securely
 */
function hashPin($pin) {
    return password_hash($pin, PASSWORD_DEFAULT);
}

/**
 * Verify PIN
 */
function verifyPin($pin, $hash) {
    return password_verify($pin, $hash);
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Format date to Spanish
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format datetime to Spanish
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    $timestamp = strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Get current datetime in Mexico City timezone
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * Get current date in Mexico City timezone
 */
function getCurrentDate() {
    return date('Y-m-d');
}

/**
 * Calculate distance between two coordinates (Haversine formula)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters

    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLatRad = deg2rad($lat2 - $lat1);
    $deltaLonRad = deg2rad($lon2 - $lon1);

    $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLonRad / 2) * sin($deltaLonRad / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c; // Distance in meters
}

/**
 * Check if coordinates are within geofence
 */
function isWithinGeofence($userLat, $userLon, $centerLat, $centerLon, $radius) {
    $distance = calculateDistance($userLat, $userLon, $centerLat, $centerLon);
    return $distance <= ($radius + GEOFENCE_TOLERANCE);
}

/**
 * Get user IP address
 */
function getUserIP() {
    $ip = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * Get user agent
 */
function getUserAgent() {
    return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
}

/**
 * Create URL
 */
function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Asset URL
 */
function asset($path) {
    return BASE_URL . 'public/' . ltrim($path, '/');
}

/**
 * Redirect
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . url($url), true, $statusCode);
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has role
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Check if user has any of the roles
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) return false;
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
}

/**
 * Get logged user ID
 */
function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get logged user data
 */
function getUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'nombre' => $_SESSION['user_nombre'] ?? null,
        'apellidos' => $_SESSION['user_apellidos'] ?? null,
        'rol' => $_SESSION['user_role'] ?? null
    ];
}

/**
 * Generate CSRF token
 */
function csrf() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * CSRF token field
 */
function csrfField() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf() . '">';
}

/**
 * Verify CSRF token
 */
function verifyCsrf() {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $_POST[CSRF_TOKEN_NAME]);
}

/**
 * Set flash message
 */
function setFlash($key, $message, $type = 'info') {
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get flash message
 */
function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

/**
 * Has flash message
 */
function hasFlash($key) {
    return isset($_SESSION['flash'][$key]);
}

/**
 * Debug function
 */
function dd($data, $die = true) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    if ($die) die();
}

/**
 * Log error
 */
function logError($message, $context = []) {
    $logFile = dirname(dirname(__DIR__)) . '/logs/error_' . date('Y-m-d') . '.log';
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }
    $logMessage .= PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Log activity
 */
function logActivity($action, $details = []) {
    $logFile = dirname(dirname(__DIR__)) . '/logs/activity_' . date('Y-m-d') . '.log';
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => getUserId(),
        'action' => $action,
        'ip' => getUserIP(),
        'details' => $details
    ];
    $logMessage = json_encode($logData) . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Format time difference
 */
function timeDifference($start, $end) {
    $startTime = strtotime($start);
    $endTime = strtotime($end);
    $diff = $endTime - $startTime;

    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);

    return sprintf("%02d:%02d", $hours, $minutes);
}

/**
 * Get day name in Spanish
 */
function getDayNameSpanish($date) {
    $days = [
        'Sunday' => 'Domingo',
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado'
    ];

    $dayName = date('l', strtotime($date));
    return $days[$dayName] ?? $dayName;
}

/**
 * Get month name in Spanish
 */
function getMonthNameSpanish($month) {
    $months = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
        4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $months[(int)$month] ?? $month;
}