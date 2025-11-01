<?php
/**
 * Checkpoint System - End-to-End Workflow Test
 * Simulates a complete day with multiple checkpoints
 */

require_once __DIR__ . '/config/database.php';

$db = db();

echo "============================================================\n";
echo "  CHECKPOINT SYSTEM - WORKFLOW SIMULATION TEST\n";
echo "============================================================\n\n";

// Use a test user
$testUserId = 5; // Adrian (empleado)
$testEmpresaId = 1;

// Get user info
$userStmt = $db->prepare("SELECT nombre, apellidos, email FROM usuarios WHERE id = ?");
$userStmt->execute([$testUserId]);
$user = $userStmt->fetch();

if (!$user) {
    die("❌ Usuario de prueba no encontrado\n");
}

echo "👤 Usuario de prueba: {$user['nombre']} {$user['apellidos']} ({$user['email']})\n";
echo "📅 Fecha: " . date('Y-m-d') . "\n\n";

// Get available locations
$locStmt = $db->prepare("SELECT id, nombre, latitud, longitud, radio_metros FROM ubicaciones WHERE activa = 1 ORDER BY id LIMIT 3");
$locStmt->execute();
$locations = $locStmt->fetchAll();

echo "📍 Ubicaciones disponibles:\n";
foreach ($locations as $loc) {
    echo "   - ID {$loc['id']}: {$loc['nombre']} (Radio: {$loc['radio_metros']}m)\n";
}
echo "\n";

// Clean up any existing checkpoints for today
echo "🧹 Limpiando checkpoints de prueba anteriores...\n";
$db->prepare("DELETE FROM registros_asistencia WHERE usuario_id = ? AND fecha = CURDATE() AND session_type = 'checkpoint'")->execute([$testUserId]);
$db->prepare("DELETE FROM location_transfers WHERE usuario_id = ? AND DATE(transfer_time) = CURDATE()")->execute([$testUserId]);
echo "✓ Limpieza completada\n\n";

// Helper function to simulate GPS near location
function getGPSNearLocation($location, $offsetMeters = 10) {
    // Add small offset to simulate being near the location
    $earthRadius = 6371000;
    $dLat = $offsetMeters / $earthRadius;
    $dLon = $offsetMeters / ($earthRadius * cos(deg2rad($location['latitud'])));

    return [
        'lat' => $location['latitud'] + rad2deg($dLat),
        'lng' => $location['longitud'] + rad2deg($dLon),
        'accuracy' => 15.5
    ];
}

echo "============================================================\n";
echo "  SIMULACIÓN DE DÍA COMPLETO\n";
echo "============================================================\n\n";

$checkpoints = [];

// CHECKPOINT 1: Check-in at first location (9:00 AM)
echo "⏰ 09:00 AM - Check-in en {$locations[0]['nombre']}\n";
echo "-----------------------------------------------------------\n";

$gps1 = getGPSNearLocation($locations[0]);
$time1 = date('Y-m-d 09:00:00');

try {
    $stmt = $db->prepare("
        INSERT INTO registros_asistencia (
            usuario_id, ubicacion_id, fecha, hora_entrada,
            lat_entrada, lon_entrada, precision_entrada,
            session_type, checkpoint_sequence, is_active, estado
        ) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, 'checkpoint', 1, 1, 'presente')
    ");

    $stmt->execute([
        $testUserId,
        $locations[0]['id'],
        $time1,
        $gps1['lat'],
        $gps1['lng'],
        $gps1['accuracy']
    ]);

    $checkpoints[1] = [
        'id' => $db->lastInsertId(),
        'location' => $locations[0]['nombre'],
        'checkin' => $time1
    ];

    echo "✅ Checkpoint #1 creado (ID: {$checkpoints[1]['id']})\n";
    echo "   📍 Ubicación: {$locations[0]['nombre']}\n";
    echo "   🌐 GPS: {$gps1['lat']}, {$gps1['lng']}\n";
    echo "   📊 Precisión: {$gps1['accuracy']}m\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Wait simulation (2.5 hours)
sleep(1);

// CHECKPOINT 1: Check-out (11:30 AM)
echo "⏰ 11:30 AM - Check-out de {$locations[0]['nombre']}\n";
echo "-----------------------------------------------------------\n";

$time2 = date('Y-m-d 11:30:00');
$hoursWorked1 = 2.5;

try {
    $stmt = $db->prepare("
        UPDATE registros_asistencia
        SET hora_salida = ?,
            lat_salida = ?,
            lon_salida = ?,
            precision_salida = ?,
            horas_trabajadas = ?,
            is_active = 0
        WHERE id = ?
    ");

    $stmt->execute([
        $time2,
        $gps1['lat'],
        $gps1['lng'],
        $gps1['accuracy'],
        $hoursWorked1,
        $checkpoints[1]['id']
    ]);

    echo "✅ Checkpoint #1 cerrado\n";
    echo "   ⏱️  Horas trabajadas: {$hoursWorked1}h\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// CHECKPOINT 2: Check-in at second location (12:00 PM)
if (isset($locations[1])) {
    echo "⏰ 12:00 PM - Check-in en {$locations[1]['nombre']}\n";
    echo "-----------------------------------------------------------\n";

    $gps2 = getGPSNearLocation($locations[1]);
    $time3 = date('Y-m-d 12:00:00');

    try {
        $stmt = $db->prepare("
            INSERT INTO registros_asistencia (
                usuario_id, ubicacion_id, fecha, hora_entrada,
                lat_entrada, lon_entrada, precision_entrada,
                session_type, checkpoint_sequence, is_active, estado
            ) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, 'checkpoint', 2, 1, 'presente')
        ");

        $stmt->execute([
            $testUserId,
            $locations[1]['id'],
            $time3,
            $gps2['lat'],
            $gps2['lng'],
            $gps2['accuracy']
        ]);

        $checkpoints[2] = [
            'id' => $db->lastInsertId(),
            'location' => $locations[1]['nombre'],
            'checkin' => $time3
        ];

        echo "✅ Checkpoint #2 creado (ID: {$checkpoints[2]['id']})\n";
        echo "   📍 Ubicación: {$locations[1]['nombre']}\n\n";

    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
}

// CHECKPOINT 2 to 3: Transfer (2:00 PM)
if (isset($locations[2]) && isset($checkpoints[2])) {
    echo "⏰ 02:00 PM - TRANSFERENCIA: {$locations[1]['nombre']} → {$locations[2]['nombre']}\n";
    echo "-----------------------------------------------------------\n";

    $time4 = date('Y-m-d 14:00:00');
    $hoursWorked2 = 2.0;
    $gps3 = getGPSNearLocation($locations[2]);

    try {
        // Close checkpoint 2
        $stmt = $db->prepare("
            UPDATE registros_asistencia
            SET hora_salida = ?,
                lat_salida = ?,
                lon_salida = ?,
                horas_trabajadas = ?,
                is_active = 0
            WHERE id = ?
        ");

        $stmt->execute([
            $time4,
            $gps2['lat'],
            $gps2['lng'],
            $hoursWorked2,
            $checkpoints[2]['id']
        ]);

        // Create checkpoint 3
        $stmt = $db->prepare("
            INSERT INTO registros_asistencia (
                usuario_id, ubicacion_id, fecha, hora_entrada,
                lat_entrada, lon_entrada, precision_entrada,
                session_type, checkpoint_sequence, is_active, estado
            ) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, 'checkpoint', 3, 1, 'presente')
        ");

        $stmt->execute([
            $testUserId,
            $locations[2]['id'],
            $time4,
            $gps3['lat'],
            $gps3['lng'],
            $gps3['accuracy']
        ]);

        $checkpoints[3] = [
            'id' => $db->lastInsertId(),
            'location' => $locations[2]['nombre'],
            'checkin' => $time4
        ];

        // Record transfer
        $stmt = $db->prepare("
            INSERT INTO location_transfers (
                usuario_id, from_ubicacion_id, to_ubicacion_id,
                from_registro_id, to_registro_id, transfer_time,
                lat, lon, precision_metros, transfer_reason
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $testUserId,
            $locations[1]['id'],
            $locations[2]['id'],
            $checkpoints[2]['id'],
            $checkpoints[3]['id'],
            $time4,
            $gps3['lat'],
            $gps3['lng'],
            $gps3['accuracy'],
            'Reunión con cliente'
        ]);

        echo "✅ Transferencia completada\n";
        echo "   ⏱️  Checkpoint #2 cerrado: {$hoursWorked2}h trabajadas\n";
        echo "   ✅ Checkpoint #3 creado (ID: {$checkpoints[3]['id']})\n";
        echo "   📝 Motivo: Reunión con cliente\n\n";

    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
}

// CHECKPOINT 3: Check-out (6:00 PM)
if (isset($checkpoints[3])) {
    echo "⏰ 06:00 PM - Check-out de {$locations[2]['nombre']}\n";
    echo "-----------------------------------------------------------\n";

    $time5 = date('Y-m-d 18:00:00');
    $hoursWorked3 = 4.0;

    try {
        $stmt = $db->prepare("
            UPDATE registros_asistencia
            SET hora_salida = ?,
                lat_salida = ?,
                lon_salida = ?,
                horas_trabajadas = ?,
                is_active = 0
            WHERE id = ?
        ");

        $stmt->execute([
            $time5,
            $gps3['lat'],
            $gps3['lng'],
            $hoursWorked3,
            $checkpoints[3]['id']
        ]);

        echo "✅ Checkpoint #3 cerrado\n";
        echo "   ⏱️  Horas trabajadas: {$hoursWorked3}h\n\n";

    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
}

// Summary
echo "\n";
echo "============================================================\n";
echo "  RESUMEN DEL DÍA\n";
echo "============================================================\n\n";

// Get summary from view
$summaryStmt = $db->prepare("
    SELECT * FROM v_checkpoint_summary
    WHERE usuario_id = ? AND fecha = CURDATE()
");
$summaryStmt->execute([$testUserId]);
$summary = $summaryStmt->fetch();

if ($summary) {
    echo "👤 Usuario: {$summary['nombre']} {$summary['apellidos']}\n";
    echo "📅 Fecha: {$summary['fecha']}\n";
    echo "📊 Total Checkpoints: {$summary['total_checkpoints']}\n";
    echo "⏱️  Total Horas: " . number_format($summary['total_hours_worked'], 2) . "h\n";
    echo "🗺️  Ruta del día:\n";
    echo "   {$summary['checkpoint_route']}\n\n";
} else {
    echo "ℹ️  No se encontró resumen en la vista\n\n";
}

// Test calculate function
$totalHours = $db->prepare("SELECT calculate_checkpoint_hours(?, CURDATE()) as total");
$totalHours->execute([$testUserId]);
$calcResult = $totalHours->fetch();

echo "🔧 Función calculate_checkpoint_hours(): " . number_format($calcResult['total'], 2) . "h\n\n";

// Get all checkpoints
echo "============================================================\n";
echo "  DETALLE DE CHECKPOINTS\n";
echo "============================================================\n\n";

$detailStmt = $db->prepare("
    SELECT
        ra.checkpoint_sequence as num,
        ub.nombre as ubicacion,
        TIME(ra.hora_entrada) as entrada,
        TIME(ra.hora_salida) as salida,
        ra.horas_trabajadas as horas,
        ra.is_active as activo
    FROM registros_asistencia ra
    LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
    WHERE ra.usuario_id = ?
      AND ra.fecha = CURDATE()
      AND ra.session_type = 'checkpoint'
    ORDER BY ra.checkpoint_sequence
");
$detailStmt->execute([$testUserId]);
$details = $detailStmt->fetchAll();

foreach ($details as $d) {
    $status = $d['activo'] ? '🟢 ACTIVO' : '✅ COMPLETADO';
    echo "Checkpoint #{$d['num']}: {$d['ubicacion']} {$status}\n";
    echo "  Entrada:  {$d['entrada']}\n";
    echo "  Salida:   " . ($d['salida'] ?: '--:--:--') . "\n";
    echo "  Horas:    " . ($d['horas'] ? number_format($d['horas'], 2) . 'h' : 'N/A') . "\n\n";
}

// Get transfers
echo "============================================================\n";
echo "  TRANSFERENCIAS REGISTRADAS\n";
echo "============================================================\n\n";

$transferStmt = $db->prepare("
    SELECT
        TIME(lt.transfer_time) as hora,
        ub_from.nombre as desde,
        ub_to.nombre as hacia,
        lt.transfer_reason as motivo
    FROM location_transfers lt
    LEFT JOIN ubicaciones ub_from ON lt.from_ubicacion_id = ub_from.id
    JOIN ubicaciones ub_to ON lt.to_ubicacion_id = ub_to.id
    WHERE lt.usuario_id = ?
      AND DATE(lt.transfer_time) = CURDATE()
    ORDER BY lt.transfer_time
");
$transferStmt->execute([$testUserId]);
$transfers = $transferStmt->fetchAll();

if (count($transfers) > 0) {
    foreach ($transfers as $t) {
        echo "🔄 {$t['hora']}: {$t['desde']} → {$t['hacia']}\n";
        if ($t['motivo']) {
            echo "   💬 Motivo: {$t['motivo']}\n";
        }
        echo "\n";
    }
} else {
    echo "ℹ️  No hay transferencias registradas\n\n";
}

echo "============================================================\n";
echo "  ✅ PRUEBA COMPLETADA EXITOSAMENTE\n";
echo "============================================================\n\n";

echo "📝 Datos de prueba creados. Puedes:\n";
echo "   1. Ver los checkpoints en la interfaz web:\n";
echo "      https://asistencia.alpefresh.app/asistencias_checkpoint.php\n\n";
echo "   2. Consultar en la base de datos:\n";
echo "      SELECT * FROM v_checkpoint_summary WHERE fecha = CURDATE();\n\n";
echo "   3. Limpiar datos de prueba:\n";
echo "      DELETE FROM registros_asistencia WHERE usuario_id = {$testUserId} AND fecha = CURDATE();\n\n";
?>
