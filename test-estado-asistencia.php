<?php
/**
 * Test de Estado de Asistencia
 * Diagnóstico para verificar detección de check-in/check-out
 */

session_start();
require_once 'config/database.php';

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    die("Por favor, inicia sesión primero: <a href='/login.php'>Login</a>");
}

$db = db();
$userId = $_SESSION['user_id'];

// Obtener información del usuario
$userStmt = $db->prepare("SELECT nombre, email, codigo_empleado FROM usuarios WHERE id = ?");
$userStmt->execute([$userId]);
$usuario = $userStmt->fetch();

// Obtener estado actual (misma lógica que asistencias.php)
$estadoStmt = $db->prepare("
    SELECT id, entrada, salida, ubicacion_id,
           CASE
               WHEN salida IS NULL THEN 'checked_in'
               ELSE 'completed'
           END as estado
    FROM asistencias
    WHERE usuario_id = ? AND DATE(entrada) = CURDATE()
    ORDER BY entrada DESC
    LIMIT 1
");
$estadoStmt->execute([$userId]);
$estadoActual = $estadoStmt->fetch();

$tieneEntrada = $estadoActual && is_null($estadoActual['salida']);

// Obtener todas las asistencias de hoy
$historialStmt = $db->prepare("
    SELECT id, entrada, salida,
           CASE
               WHEN salida IS NULL THEN 'SIN SALIDA'
               ELSE 'COMPLETO'
           END as estado_texto
    FROM asistencias
    WHERE usuario_id = ? AND DATE(entrada) = CURDATE()
    ORDER BY entrada DESC
");
$historialStmt->execute([$userId]);
$historialHoy = $historialStmt->fetchAll();

// Obtener estadísticas generales
$statsStmt = $db->prepare("
    SELECT
        COUNT(*) as total_registros,
        SUM(CASE WHEN salida IS NULL THEN 1 ELSE 0 END) as sin_salida,
        SUM(CASE WHEN salida IS NOT NULL THEN 1 ELSE 0 END) as completos
    FROM asistencias
    WHERE usuario_id = ? AND DATE(entrada) = CURDATE()
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Estado Asistencia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 2rem;
        }

        h1 {
            color: #1f2937;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-checked-in {
            background: #dcfce7;
            color: #166534;
            border: 2px solid #10b981;
        }

        .status-no-entry {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid #f59e0b;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 1rem;
            margin: 1rem 0;
        }

        .info-label {
            font-weight: 600;
            color: #6b7280;
        }

        .info-value {
            color: #1f2937;
        }

        .highlight {
            background: #fef3c7;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            color: #92400e;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f9fafb;
            font-weight: 600;
            color: #1f2937;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .action-buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>
                <i class="fas fa-vial"></i>
                Test de Estado de Asistencia
            </h1>
            <p style="color: #6b7280; margin-bottom: 2rem;">
                Diagnóstico para verificar la detección automática de check-in/check-out
            </p>

            <h2 style="color: #1f2937; font-size: 1.3rem; margin-bottom: 1rem;">
                <i class="fas fa-user"></i> Información del Usuario
            </h2>
            <div class="info-grid">
                <div class="info-label">ID:</div>
                <div class="info-value"><?php echo $userId; ?></div>

                <div class="info-label">Nombre:</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['nombre']); ?></div>

                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></div>

                <div class="info-label">Código:</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['codigo_empleado']); ?></div>
            </div>
        </div>

        <div class="card">
            <h2 style="color: #1f2937; font-size: 1.3rem; margin-bottom: 1rem;">
                <i class="fas fa-clipboard-check"></i> Estado Actual
            </h2>

            <div class="info-grid">
                <div class="info-label">Fecha de hoy:</div>
                <div class="info-value"><?php echo date('Y-m-d'); ?></div>

                <div class="info-label">Registro encontrado:</div>
                <div class="info-value">
                    <?php if ($estadoActual): ?>
                        <span class="highlight success">✓ SÍ (ID: <?php echo $estadoActual['id']; ?>)</span>
                    <?php else: ?>
                        <span class="highlight">✗ NO</span>
                    <?php endif; ?>
                </div>

                <?php if ($estadoActual): ?>
                <div class="info-label">Hora de entrada:</div>
                <div class="info-value"><?php echo $estadoActual['entrada']; ?></div>

                <div class="info-label">Hora de salida:</div>
                <div class="info-value">
                    <?php if ($estadoActual['salida']): ?>
                        <?php echo $estadoActual['salida']; ?>
                    <?php else: ?>
                        <span class="highlight">NULL (sin salida)</span>
                    <?php endif; ?>
                </div>

                <div class="info-label">Estado calculado:</div>
                <div class="info-value">
                    <span class="highlight <?php echo $estadoActual['estado'] === 'checked_in' ? 'success' : ''; ?>">
                        <?php echo $estadoActual['estado']; ?>
                    </span>
                </div>
                <?php endif; ?>

                <div class="info-label">Tiene entrada activa:</div>
                <div class="info-value">
                    <?php if ($tieneEntrada): ?>
                        <span class="highlight success">✓ SÍ</span>
                    <?php else: ?>
                        <span class="highlight">✗ NO</span>
                    <?php endif; ?>
                </div>

                <div class="info-label">Botón a mostrar:</div>
                <div class="info-value">
                    <strong style="color: #667eea; font-size: 1.1rem;">
                        <?php echo $tieneEntrada ? 'MARCAR SALIDA' : 'MARCAR ENTRADA'; ?>
                    </strong>
                </div>

                <div class="info-label">Estado visual:</div>
                <div class="info-value">
                    <span class="status-badge <?php echo $tieneEntrada ? 'status-checked-in' : 'status-no-entry'; ?>">
                        <i class="fas fa-<?php echo $tieneEntrada ? 'check-circle' : 'clock'; ?>"></i>
                        <?php echo $tieneEntrada ? 'Ya marcaste entrada. Ahora puedes marcar salida.' : 'No has marcado entrada hoy.'; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 style="color: #1f2937; font-size: 1.3rem; margin-bottom: 1rem;">
                <i class="fas fa-chart-bar"></i> Estadísticas de Hoy
            </h2>

            <div class="info-grid">
                <div class="info-label">Total de registros:</div>
                <div class="info-value"><?php echo $stats['total_registros']; ?></div>

                <div class="info-label">Sin salida:</div>
                <div class="info-value"><?php echo $stats['sin_salida']; ?></div>

                <div class="info-label">Completos:</div>
                <div class="info-value"><?php echo $stats['completos']; ?></div>
            </div>

            <?php if ($historialHoy): ?>
            <h3 style="color: #1f2937; margin-top: 2rem; margin-bottom: 1rem;">
                Todos los registros de hoy:
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historialHoy as $registro): ?>
                    <tr>
                        <td><?php echo $registro['id']; ?></td>
                        <td><?php echo date('H:i:s', strtotime($registro['entrada'])); ?></td>
                        <td>
                            <?php if ($registro['salida']): ?>
                                <?php echo date('H:i:s', strtotime($registro['salida'])); ?>
                            <?php else: ?>
                                <span style="color: #f59e0b;">NULL</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $registro['salida'] ? 'badge-success' : 'badge-warning'; ?>">
                                <?php echo $registro['estado_texto']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="/asistencias.php" class="btn">
                <i class="fas fa-clock"></i> Ir a Asistencias
            </a>
            <a href="/asistencias.php?debug=1" class="btn" style="background: #f59e0b;">
                <i class="fas fa-bug"></i> Asistencias (Debug Mode)
            </a>
            <a href="/dashboard.php" class="btn" style="background: #10b981;">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>
</body>
</html>
