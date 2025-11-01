<?php
/**
 * Script para crear usuario de prueba
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = db();

    // Verificar si existe la empresa 1
    $stmt = $db->query("SELECT id FROM empresas LIMIT 1");
    $empresa = $stmt->fetch();

    if (!$empresa) {
        // Crear empresa de prueba
        $db->exec("INSERT INTO empresas (nombre, rfc, direccion, telefono, email, activa)
                   VALUES ('AlpeFresh', 'AFP000000XXX', 'Dirección Principal', '555-0000', 'info@alpefresh.com', 1)");
        $empresa_id = $db->lastInsertId();
    } else {
        $empresa_id = $empresa['id'];
    }

    // Hash para password y PIN
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $pin = password_hash('123456', PASSWORD_DEFAULT);

    // Insertar o actualizar usuario
    $stmt = $db->prepare("
        INSERT INTO usuarios (
            empresa_id, codigo_empleado, email, password, pin,
            nombre, apellidos, rol, activo
        ) VALUES (
            ?, 'EMP001', 'admin@alpefresh.com', ?, ?,
            'Admin', 'Sistema', 'admin', 1
        )
        ON DUPLICATE KEY UPDATE
            password = VALUES(password),
            pin = VALUES(pin),
            activo = 1
    ");

    $stmt->execute([$empresa_id, $password, $pin]);

    echo "✓ Usuario de prueba creado/actualizado exitosamente\n";
    echo "  Email: admin@alpefresh.com\n";
    echo "  Password: admin123\n";
    echo "  Código: EMP001\n";
    echo "  PIN: 123456\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}