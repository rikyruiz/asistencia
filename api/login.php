<?php
/**
 * API de Login
 * Procesa las solicitudes de autenticación
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Si no es JSON, intentar con POST normal
    $input = $_POST;
}

$tipo = $input['tipo'] ?? '';

if ($tipo === 'email') {
    // Login con email y contraseña
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email y contraseña son requeridos']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        exit;
    }

    $result = Auth::loginWithEmail($email, $password);

    // Si login exitoso, agregar redirect según dispositivo (solo móvil)
    if ($result['success']) {
        $result['redirect'] = Auth::getMobileRedirect();
    }

    echo json_encode($result);

} elseif ($tipo === 'pin') {
    // Login con código de empleado y PIN
    $codigo_empleado = trim($input['codigo_empleado'] ?? '');
    $pin = $input['pin'] ?? '';

    if (empty($codigo_empleado) || empty($pin)) {
        echo json_encode(['success' => false, 'message' => 'Código de empleado y PIN son requeridos']);
        exit;
    }

    if (!preg_match('/^\d{6}$/', $pin)) {
        echo json_encode(['success' => false, 'message' => 'El PIN debe ser de 6 dígitos']);
        exit;
    }

    $result = Auth::loginWithPin($codigo_empleado, $pin);

    // Si login exitoso, agregar redirect según dispositivo (solo móvil)
    if ($result['success']) {
        $result['redirect'] = Auth::getMobileRedirect();
    }

    echo json_encode($result);

} else {
    echo json_encode(['success' => false, 'message' => 'Tipo de login no válido']);
}