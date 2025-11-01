<?php
/**
 * API Simplificada de Registro de Asistencia
 * Endpoint único para marcar entrada/salida basado en geolocalización
 */

require_once '../config/config.php';

// Verificar sesión
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

$lat = $input['lat'] ?? null;
$lng = $input['lng'] ?? null;
$accuracy = $input['accuracy'] ?? null;
$location_id = $input['location_id'] ?? null;

// Validar datos
if (!$lat || !$lng) {
    jsonResponse(['success' => false, 'message' => 'Ubicación requerida'], 400);
}

if ($accuracy > MIN_GPS_ACCURACY) {
    jsonResponse([
        'success' => false,
        'message' => 'GPS muy impreciso. Precisión actual: ' . round($accuracy) . 'm'
    ], 400);
}

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];

    // Verificar que la ubicación proporcionada sea válida
    if ($location_id) {
        $stmt = $db->prepare("
            SELECT * FROM ubicaciones
            WHERE id = ? AND empresa_id = ? AND activa = 1
        ");
        $stmt->execute([$location_id, $_SESSION['empresa_id']]);
        $location = $stmt->fetch();

        if (!$location) {
            jsonResponse(['success' => false, 'message' => 'Ubicación no válida'], 400);
        }

        // Verificar distancia
        $distance = calculateDistance($lat, $lng, $location['latitud'], $location['longitud']);
        if ($distance > $location['radio_metros']) {
            jsonResponse([
                'success' => false,
                'message' => 'Fuera del rango permitido. Distancia: ' . round($distance) . 'm'
            ], 400);
        }
    }

    // Verificar si hay una sesión abierta
    $stmt = $db->prepare("
        SELECT id, hora_entrada
        FROM registros_asistencia
        WHERE usuario_id = ? AND fecha = CURDATE() AND hora_salida IS NULL
        ORDER BY hora_entrada DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $openSession = $stmt->fetch();

    if ($openSession) {
        // MARCAR SALIDA
        $horaEntrada = new DateTime($openSession['hora_entrada']);
        $horaSalida = new DateTime();
        $diff = $horaEntrada->diff($horaSalida);
        $horasTrabajadas = $diff->h + ($diff->i / 60);

        $stmt = $db->prepare("
            UPDATE registros_asistencia
            SET hora_salida = NOW(),
                lat_salida = ?,
                lon_salida = ?,
                precision_salida = ?,
                horas_trabajadas = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $lat,
            $lng,
            $accuracy,
            $horasTrabajadas,
            $openSession['id']
        ]);

        // Registrar en marcajes
        $stmt = $db->prepare("
            INSERT INTO marcajes (
                registro_id, usuario_id, tipo, hora,
                latitud, longitud, precision_metros,
                ubicacion_id, ip, metodo
            ) VALUES (?, ?, 'salida', NOW(), ?, ?, ?, ?, ?, 'gps')
        ");

        $stmt->execute([
            $openSession['id'],
            $userId,
            $lat,
            $lng,
            $accuracy,
            $location_id,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        jsonResponse([
            'success' => true,
            'action' => 'clock_out',
            'message' => 'Salida registrada exitosamente',
            'hora' => date('h:i A'),
            'horas_trabajadas' => round($horasTrabajadas, 2)
        ]);

    } else {
        // MARCAR ENTRADA
        $stmt = $db->prepare("
            INSERT INTO registros_asistencia (
                usuario_id, fecha, hora_entrada,
                lat_entrada, lon_entrada, precision_entrada,
                ubicacion_id, ip_entrada, estado
            ) VALUES (?, CURDATE(), NOW(), ?, ?, ?, ?, ?, 'presente')
        ");

        $stmt->execute([
            $userId,
            $lat,
            $lng,
            $accuracy,
            $location_id,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $registroId = $db->lastInsertId();

        // Registrar en marcajes
        $stmt = $db->prepare("
            INSERT INTO marcajes (
                registro_id, usuario_id, tipo, hora,
                latitud, longitud, precision_metros,
                ubicacion_id, ip, metodo
            ) VALUES (?, ?, 'entrada', NOW(), ?, ?, ?, ?, ?, 'gps')
        ");

        $stmt->execute([
            $registroId,
            $userId,
            $lat,
            $lng,
            $accuracy,
            $location_id,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        jsonResponse([
            'success' => true,
            'action' => 'clock_in',
            'message' => 'Entrada registrada exitosamente',
            'hora' => date('h:i A')
        ]);
    }

} catch (Exception $e) {
    error_log("Error en clock.php: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Error al procesar solicitud'
    ], 500);
}