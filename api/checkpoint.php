<?php
/**
 * Checkpoint API - Handle multiple check-ins/outs per day
 * Allows users to check in at different locations throughout the day
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verify authentication
Auth::requireAuth();

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$action = $input['action'] ?? null; // 'checkin', 'checkout', 'transfer'
$lat = $input['lat'] ?? null;
$lng = $input['lng'] ?? null;
$accuracy = $input['accuracy'] ?? null;
$location_id = $input['location_id'] ?? null;
$reason = $input['reason'] ?? null;

// Validate required data
if (!$action || !$lat || !$lng) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos requeridos faltantes']);
    exit;
}

// GPS accuracy check
$min_accuracy = 50; // meters
if ($accuracy && $accuracy > $min_accuracy) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'GPS muy impreciso. Precisión actual: ' . round($accuracy) . 'm. Requerido: < ' . $min_accuracy . 'm'
    ]);
    exit;
}

try {
    $db = db();
    $userId = $_SESSION['user_id'];
    $empresaId = $_SESSION['empresa_id'];
    $fecha = date('Y-m-d');

    // Verify location if provided
    if ($location_id) {
        $stmt = $db->prepare("
            SELECT * FROM ubicaciones
            WHERE id = ? AND empresa_id = ? AND activa = 1
        ");
        $stmt->execute([$location_id, $empresaId]);
        $location = $stmt->fetch();

        if (!$location) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ubicación no válida']);
            exit;
        }

        // Calculate distance
        $distance = calculateDistance($lat, $lng, $location['latitud'], $location['longitud']);
        if ($distance > $location['radio_metros']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Fuera del rango permitido. Distancia: ' . round($distance) . 'm',
                'distance' => round($distance),
                'max_distance' => $location['radio_metros']
            ]);
            exit;
        }
    }

    // Handle different actions
    switch ($action) {

        case 'checkin':
            // Auto-close any active checkpoints first (optional - allows multiple active if needed)
            // Uncomment the next 3 lines to auto-close previous checkpoints
            // $stmt = $db->prepare("CALL sp_close_active_checkpoints(?, ?, NOW())");
            // $stmt->execute([$userId, $fecha]);

            // Get next sequence number
            $stmt = $db->prepare("
                SELECT COALESCE(MAX(checkpoint_sequence), 0) + 1 as next_seq
                FROM registros_asistencia
                WHERE usuario_id = ? AND fecha = ?
            ");
            $stmt->execute([$userId, $fecha]);
            $sequence = $stmt->fetch()['next_seq'];

            // Create new checkpoint
            $stmt = $db->prepare("
                INSERT INTO registros_asistencia (
                    usuario_id, ubicacion_id, fecha, hora_entrada,
                    lat_entrada, lon_entrada, precision_entrada,
                    session_type, checkpoint_sequence, is_active,
                    estado, ip_entrada, dispositivo_entrada
                ) VALUES (?, ?, ?, NOW(), ?, ?, ?, 'checkpoint', ?, 1, 'presente', ?, ?)
            ");

            $stmt->execute([
                $userId,
                $location_id,
                $fecha,
                $lat,
                $lng,
                $accuracy,
                $sequence,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $registroId = $db->lastInsertId();

            // Record marcaje
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

            echo json_encode([
                'success' => true,
                'action' => 'checkin',
                'message' => 'Check-in registrado exitosamente',
                'checkpoint_number' => $sequence,
                'time' => date('h:i A'),
                'registro_id' => $registroId
            ]);
            break;

        case 'checkout':
            // Find active checkpoint
            $stmt = $db->prepare("
                SELECT id, hora_entrada, ubicacion_id, checkpoint_sequence
                FROM registros_asistencia
                WHERE usuario_id = ? AND fecha = ? AND is_active = 1 AND hora_salida IS NULL
                ORDER BY hora_entrada DESC
                LIMIT 1
            ");
            $stmt->execute([$userId, $fecha]);
            $activeCheckpoint = $stmt->fetch();

            if (!$activeCheckpoint) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No hay checkpoint activo para cerrar'
                ]);
                exit;
            }

            // Calculate hours worked
            $entrada = new DateTime($activeCheckpoint['hora_entrada']);
            $salida = new DateTime();
            $diff = $entrada->diff($salida);
            $horasTrabajadas = $diff->h + ($diff->i / 60) + ($diff->days * 24);

            // Close checkpoint
            $stmt = $db->prepare("
                UPDATE registros_asistencia
                SET hora_salida = NOW(),
                    lat_salida = ?,
                    lon_salida = ?,
                    precision_salida = ?,
                    horas_trabajadas = ?,
                    is_active = 0,
                    ip_salida = ?,
                    dispositivo_salida = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $lat,
                $lng,
                $accuracy,
                $horasTrabajadas,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $activeCheckpoint['id']
            ]);

            // Record marcaje
            $stmt = $db->prepare("
                INSERT INTO marcajes (
                    registro_id, usuario_id, tipo, hora,
                    latitud, longitud, precision_metros,
                    ubicacion_id, ip, metodo
                ) VALUES (?, ?, 'salida', NOW(), ?, ?, ?, ?, ?, 'gps')
            ");

            $stmt->execute([
                $activeCheckpoint['id'],
                $userId,
                $lat,
                $lng,
                $accuracy,
                $location_id,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            // Get total hours for the day
            $stmt = $db->prepare("
                SELECT SUM(horas_trabajadas) as total_horas
                FROM registros_asistencia
                WHERE usuario_id = ? AND fecha = ?
            ");
            $stmt->execute([$userId, $fecha]);
            $totalHoras = $stmt->fetch()['total_horas'] ?? 0;

            echo json_encode([
                'success' => true,
                'action' => 'checkout',
                'message' => 'Check-out registrado exitosamente',
                'checkpoint_number' => $activeCheckpoint['checkpoint_sequence'],
                'hours_worked' => round($horasTrabajadas, 2),
                'total_daily_hours' => round($totalHoras, 2),
                'time' => date('h:i A')
            ]);
            break;

        case 'transfer':
            // Quick transfer: checkout from current location and checkin to new location
            if (!$location_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Se requiere ubicación de destino']);
                exit;
            }

            // Call stored procedure
            $stmt = $db->prepare("
                CALL sp_transfer_location(?, ?, ?, ?, ?, ?, @success, @message, @new_registro_id)
            ");

            $stmt->execute([
                $userId,
                $location_id,
                $lat,
                $lng,
                $accuracy,
                $reason
            ]);

            // Get output parameters
            $result = $db->query("SELECT @success as success, @message as message, @new_registro_id as registro_id")->fetch();

            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'action' => 'transfer',
                    'message' => $result['message'],
                    'time' => date('h:i A'),
                    'registro_id' => $result['registro_id']
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }

} catch (Exception $e) {
    error_log("Error en checkpoint.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar solicitud: ' . $e->getMessage()
    ]);
}

/**
 * Calculate distance between two GPS coordinates (Haversine formula)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earthRadius * $c;
}
