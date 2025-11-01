<?php
/**
 * Microsoft OAuth2 - Callback
 * Procesa la respuesta de Microsoft y autentica al usuario
 */

session_start();

require_once __DIR__ . '/../../includes/oauth_handler.php';

// Verificar parámetros requeridos
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    $_SESSION['error'] = "Respuesta inválida de Microsoft. Por favor, intenta nuevamente.";
    header('Location: /login.php');
    exit();
}

// Verificar estado CSRF
if (!isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    $_SESSION['error'] = "Estado de sesión inválido. Por favor, intenta nuevamente.";
    header('Location: /login.php');
    exit();
}

// Verificar que el proveedor sea Microsoft
if (!isset($_SESSION['oauth_provider']) || $_SESSION['oauth_provider'] !== 'microsoft') {
    $_SESSION['error'] = "Proveedor OAuth inválido.";
    header('Location: /login.php');
    exit();
}

try {
    $oauth = new OAuth2Handler('microsoft');

    // Obtener access token
    $accessToken = $oauth->getAccessToken($_GET['code']);

    // Obtener información del usuario
    $userInfo = $oauth->getUserInfo($accessToken);

    // Crear o actualizar usuario y establecer sesión
    if ($oauth->completeOAuthLogin($userInfo)) {
        // Limpiar variables OAuth de la sesión
        unset($_SESSION['oauth_state']);
        unset($_SESSION['oauth_provider']);

        // Mensaje de bienvenida
        $_SESSION['success'] = "¡Bienvenido, " . $_SESSION['user_nombre'] . "!";

        // Redirigir al dashboard
        header('Location: /dashboard.php');
        exit();
    } else {
        throw new Exception("No se pudo completar el inicio de sesión");
    }

} catch (Exception $e) {
    error_log("Error en callback OAuth Microsoft: " . $e->getMessage());

    $_SESSION['error'] = "Error al procesar la autenticación con Microsoft: " . $e->getMessage();
    header('Location: /login.php');
    exit();
}