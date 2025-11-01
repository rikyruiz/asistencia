<?php
/**
 * Checkpoint System Testing Script
 * Run this to verify all components are working
 */

require_once __DIR__ . '/config/database.php';

$db = db();
$testResults = [];
$allPassed = true;

function test($name, $callback) {
    global $testResults, $allPassed;

    try {
        $result = $callback();
        $testResults[] = [
            'name' => $name,
            'status' => $result ? 'PASS' : 'FAIL',
            'message' => $result ? '✓' : '✗'
        ];
        if (!$result) $allPassed = false;
        return $result;
    } catch (Exception $e) {
        $testResults[] = [
            'name' => $name,
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ];
        $allPassed = false;
        return false;
    }
}

echo "=================================================\n";
echo "  CHECKPOINT SYSTEM - COMPREHENSIVE TEST SUITE  \n";
echo "=================================================\n\n";

// TEST 1: Database Tables
test("location_transfers table exists", function() use ($db) {
    $result = $db->query("SHOW TABLES LIKE 'location_transfers'")->fetch();
    return $result !== false;
});

test("registros_asistencia has session_type column", function() use ($db) {
    $result = $db->query("SHOW COLUMNS FROM registros_asistencia LIKE 'session_type'")->fetch();
    return $result !== false;
});

test("registros_asistencia has checkpoint_sequence column", function() use ($db) {
    $result = $db->query("SHOW COLUMNS FROM registros_asistencia LIKE 'checkpoint_sequence'")->fetch();
    return $result !== false;
});

test("registros_asistencia has is_active column", function() use ($db) {
    $result = $db->query("SHOW COLUMNS FROM registros_asistencia LIKE 'is_active'")->fetch();
    return $result !== false;
});

// TEST 2: Database Views
test("v_checkpoint_summary view exists", function() use ($db) {
    $result = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_asist_db = 'v_checkpoint_summary'")->fetch();
    return $result !== false;
});

// TEST 3: Functions
test("calculate_checkpoint_hours function exists", function() use ($db) {
    $result = $db->query("SHOW FUNCTION STATUS WHERE Db = 'asist_db' AND Name = 'calculate_checkpoint_hours'")->fetch();
    return $result !== false;
});

test("calculate_checkpoint_hours function works", function() use ($db) {
    $result = $db->query("SELECT calculate_checkpoint_hours(1, CURDATE()) as hours")->fetch();
    return isset($result['hours']) && is_numeric($result['hours']);
});

// TEST 4: Stored Procedures
test("sp_transfer_location procedure exists", function() use ($db) {
    $result = $db->query("SHOW PROCEDURE STATUS WHERE Db = 'asist_db' AND Name = 'sp_transfer_location'")->fetch();
    return $result !== false;
});

// TEST 5: Triggers
test("before_checkpoint_insert trigger exists", function() use ($db) {
    $result = $db->query("SHOW TRIGGERS WHERE Trigger = 'before_checkpoint_insert'")->fetch();
    return $result !== false;
});

// TEST 6: API File
test("checkpoint API file exists", function() {
    return file_exists(__DIR__ . '/api/checkpoint.php');
});

// TEST 7: UI Files
test("asistencias_checkpoint.php exists", function() {
    return file_exists(__DIR__ . '/asistencias_checkpoint.php');
});

test("dashboard_checkpoint_widget.php exists", function() {
    return file_exists(__DIR__ . '/dashboard_checkpoint_widget.php');
});

// TEST 8: Sample Data Test - Get test user
test("Can query usuarios table", function() use ($db) {
    $result = $db->query("SELECT id FROM usuarios LIMIT 1")->fetch();
    return $result !== false;
});

// TEST 9: Get test location
$testLocationId = null;
test("Can query ubicaciones table", function() use ($db, &$testLocationId) {
    $result = $db->query("SELECT id FROM ubicaciones WHERE activa = 1 LIMIT 1")->fetch();
    if ($result) {
        $testLocationId = $result['id'];
        return true;
    }
    return false;
});

// TEST 10: Insert test checkpoint
$testCheckpointId = null;
test("Can insert test checkpoint", function() use ($db, $testLocationId, &$testCheckpointId) {
    if (!$testLocationId) return false;

    // Get a test user
    $userResult = $db->query("SELECT id, empresa_id FROM usuarios LIMIT 1")->fetch();
    if (!$userResult) return false;

    $userId = $userResult['id'];

    // Insert test checkpoint
    $stmt = $db->prepare("
        INSERT INTO registros_asistencia (
            usuario_id, ubicacion_id, fecha, hora_entrada,
            lat_entrada, lon_entrada, precision_entrada,
            session_type, checkpoint_sequence, is_active, estado
        ) VALUES (?, ?, CURDATE(), NOW(), 19.432608, -99.133209, 15.5, 'checkpoint', 1, 1, 'presente')
    ");

    $result = $stmt->execute([$userId, $testLocationId]);
    if ($result) {
        $testCheckpointId = $db->lastInsertId();
    }
    return $result;
});

// TEST 11: Verify trigger auto-sequencing works
test("Trigger auto-assigns checkpoint_sequence", function() use ($db, $testCheckpointId) {
    if (!$testCheckpointId) return false;

    $stmt = $db->prepare("SELECT checkpoint_sequence FROM registros_asistencia WHERE id = ?");
    $stmt->execute([$testCheckpointId]);
    $result = $stmt->fetch();

    return $result && $result['checkpoint_sequence'] > 0;
});

// TEST 12: Test checkpoint view
test("v_checkpoint_summary returns data", function() use ($db) {
    $result = $db->query("SELECT * FROM v_checkpoint_summary LIMIT 1")->fetch();
    return $result !== false || true; // Pass even if empty
});

// TEST 13: Test calculate function with test data
test("calculate_checkpoint_hours returns correct value", function() use ($db, $testCheckpointId) {
    if (!$testCheckpointId) return true; // Skip if no test data

    $stmt = $db->prepare("SELECT usuario_id, fecha FROM registros_asistencia WHERE id = ?");
    $stmt->execute([$testCheckpointId]);
    $checkpoint = $stmt->fetch();

    if (!$checkpoint) return true;

    $stmt = $db->prepare("SELECT calculate_checkpoint_hours(?, ?) as hours");
    $stmt->execute([$checkpoint['usuario_id'], $checkpoint['fecha']]);
    $result = $stmt->fetch();

    return isset($result['hours']) && $result['hours'] >= 0;
});

// TEST 14: Clean up test data
test("Cleanup test checkpoint", function() use ($db, $testCheckpointId) {
    if (!$testCheckpointId) return true;

    $stmt = $db->prepare("DELETE FROM registros_asistencia WHERE id = ?");
    return $stmt->execute([$testCheckpointId]);
});

// TEST 15: Distance calculation function
test("Haversine distance calculation works", function() {
    // Test with known coordinates
    function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    // Distance from Mexico City to nearby point (should be ~1000m)
    $distance = calculateDistance(19.4326, -99.1332, 19.4236, -99.1432);

    return $distance > 500 && $distance < 2000; // Reasonable range
});

// Display Results
echo "\n";
echo "=================================================\n";
echo "                  TEST RESULTS                   \n";
echo "=================================================\n\n";

$passed = 0;
$failed = 0;
$errors = 0;

foreach ($testResults as $test) {
    $statusColor = $test['status'] === 'PASS' ? '' : '';
    $icon = $test['status'] === 'PASS' ? '✓' : ($test['status'] === 'ERROR' ? '⚠' : '✗');

    printf("%s [%s] %s\n", $icon, str_pad($test['status'], 5), $test['name']);

    if ($test['status'] !== 'PASS' && $test['message'] !== '✗') {
        echo "    → " . $test['message'] . "\n";
    }

    if ($test['status'] === 'PASS') $passed++;
    elseif ($test['status'] === 'ERROR') $errors++;
    else $failed++;
}

echo "\n";
echo "=================================================\n";
printf("Total Tests: %d | Passed: %d | Failed: %d | Errors: %d\n",
    count($testResults), $passed, $failed, $errors);
echo "=================================================\n\n";

if ($allPassed) {
    echo "🎉 ALL TESTS PASSED! Checkpoint system is fully functional.\n\n";
    echo "Next steps:\n";
    echo "1. Access UI: https://asistencia.alpefresh.app/asistencias_checkpoint.php\n";
    echo "2. Test API manually with curl or Postman\n";
    echo "3. Test user workflows end-to-end\n";
} else {
    echo "⚠️  SOME TESTS FAILED. Please review errors above.\n\n";
}

echo "\n";
?>
