<?php
/**
 * Microsoft OAuth2 - Inicio del flujo de autenticación
 * Sistema de Asistencia - AlpeFresh
 */

session_start();

require_once __DIR__ . '/../../includes/oauth_handler.php';

try {
    $oauth = new OAuth2Handler('microsoft');
    $authUrl = $oauth->getAuthorizationUrl();

    // Redirigir al usuario a Microsoft para autenticación
    header('Location: ' . $authUrl);
    exit();

} catch (Exception $e) {
    error_log("Error iniciando OAuth Microsoft: " . $e->getMessage());

    // Redirigir con mensaje de error
    $_SESSION['error'] = "No se pudo iniciar el proceso de autenticación con Microsoft. Por favor, intenta nuevamente.";
    header('Location: /login.php');
    exit();
}