<?php
/**
 * Configuración General del Sistema
 */

session_start();

// Zona horaria de México
date_default_timezone_set('America/Mexico_City');

// Configuración de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Constantes del sistema
define('SITE_NAME', 'Sistema de Asistencia');
define('SITE_URL', 'https://asistencia.alpefresh.app');

// Radio por defecto para geovalla (metros)
define('DEFAULT_RADIUS', 100);
define('MIN_GPS_ACCURACY', 50); // Precisión mínima aceptable en metros

// Incluir base de datos
require_once __DIR__ . '/database.php';

// Funciones auxiliares
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // metros

    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLon = deg2rad($lon2 - $lon1);

    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}