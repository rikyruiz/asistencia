<?php
/**
 * Reportes de Asistencia - Sistema de Control de Asistencia
 * Monitoreo de horas trabajadas, overtime y puntualidad
 */

session_start();

// Aggressive cache prevention
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /login.php');
    exit;
}

require_once 'config/database.php';

$db = db();
$userRole = $_SESSION['user_rol'];
$userId = $_SESSION['user_id'];

// Configuración de horas laborales estándar
$STANDARD_HOURS = 8; // 8 horas diarias
$OVERTIME_THRESHOLD = 8; // Después de 8 horas es overtime
$STANDARD_ENTRY = '09:00:00'; // Hora de entrada esperada
$STANDARD_EXIT = '18:00:00'; // Hora de salida esperada
$GRACE_PERIOD = 15; // Minutos de gracia para entrada tardía

// Filtros
$filterDateStart = $_GET['date_start'] ?? date('Y-m-01'); // Inicio del mes actual
$filterDateEnd = $_GET['date_end'] ?? date('Y-m-d'); // Hoy
$filterEmployee = $_GET['employee'] ?? '';
$filterDepartment = $_GET['department'] ?? '';
$filterLocation = $_GET['location'] ?? '';

// Construir query base
$query = "
    SELECT
        a.*,
        u.nombre,
        u.apellidos,
        u.codigo_empleado,
        u.email,
        u.departamento_id,
        l.nombre as ubicacion_nombre,
        e.nombre as empresa_nombre,
        TIMESTAMPDIFF(MINUTE, a.entrada, IFNULL(a.salida, NOW())) as minutos_trabajados,
        TIME(a.entrada) as hora_entrada,
        TIME(a.salida) as hora_salida,
        DATE(a.entrada) as fecha
    FROM asistencias a
    INNER JOIN usuarios u ON a.usuario_id = u.id
    LEFT JOIN ubicaciones l ON a.ubicacion_id = l.id
    LEFT JOIN empresas e ON u.empresa_id = e.id
    WHERE DATE(a.entrada) BETWEEN ? AND ?
";

$params = [$filterDateStart, $filterDateEnd];

// Aplicar filtros adicionales
if ($userRole !== 'admin' && $userRole !== 'supervisor') {
    // Empleados solo ven sus propios registros
    $query .= " AND a.usuario_id = ?";
    $params[] = $userId;
} else {
    // Admin y supervisor pueden filtrar por empleado
    if ($filterEmployee) {
        $query .= " AND a.usuario_id = ?";
        $params[] = $filterEmployee;
    }

    if ($filterDepartment) {
        $query .= " AND u.departamento_id = ?";
        $params[] = $filterDepartment;
    }

    if ($filterLocation) {
        $query .= " AND a.ubicacion_id = ?";
        $params[] = $filterLocation;
    }
}

$query .= " ORDER BY a.entrada DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$registros = $stmt->fetchAll();

// Calcular estadísticas
$stats = [
    'total_dias' => 0,
    'total_horas' => 0,
    'overtime_horas' => 0,
    'tardanzas' => 0,
    'salidas_tempranas' => 0,
    'ausencias' => 0
];

$empleadosData = [];
$diasTrabajados = [];

foreach ($registros as $registro) {
    $empleadoId = $registro['usuario_id'];
    $fecha = $registro['fecha'];
    $minutosTrabajados = $registro['minutos_trabajados'];
    $horasTrabajadas = $minutosTrabajados / 60;

    // Agrupar por empleado
    if (!isset($empleadosData[$empleadoId])) {
        $empleadosData[$empleadoId] = [
            'nombre' => $registro['nombre'] . ' ' . $registro['apellidos'],
            'codigo' => $registro['codigo_empleado'],
            'dias' => [],
            'total_horas' => 0,
            'overtime' => 0,
            'tardanzas' => 0,
            'salidas_tempranas' => 0
        ];
    }

    // Agrupar por día
    if (!isset($empleadosData[$empleadoId]['dias'][$fecha])) {
        $empleadosData[$empleadoId]['dias'][$fecha] = [
            'entradas' => [],
            'salidas' => [],
            'horas' => 0
        ];
    }

    // Registrar entrada/salida
    $empleadosData[$empleadoId]['dias'][$fecha]['entradas'][] = $registro['hora_entrada'];
    if ($registro['hora_salida']) {
        $empleadosData[$empleadoId]['dias'][$fecha]['salidas'][] = $registro['hora_salida'];
    }
    $empleadosData[$empleadoId]['dias'][$fecha]['horas'] += $horasTrabajadas;

    // Calcular estadísticas
    $empleadosData[$empleadoId]['total_horas'] += $horasTrabajadas;

    // Verificar tardanza (primera entrada del día)
    if (count($empleadosData[$empleadoId]['dias'][$fecha]['entradas']) == 1) {
        $entradaTime = strtotime($registro['hora_entrada']);
        $expectedTime = strtotime($STANDARD_ENTRY);
        $gracePeriodTime = $expectedTime + ($GRACE_PERIOD * 60);

        if ($entradaTime > $gracePeriodTime) {
            $empleadosData[$empleadoId]['tardanzas']++;
            $stats['tardanzas']++;
        }
    }

    // Verificar salida temprana
    if ($registro['hora_salida']) {
        $salidaTime = strtotime($registro['hora_salida']);
        $expectedExitTime = strtotime($STANDARD_EXIT);

        if ($salidaTime < $expectedExitTime) {
            $empleadosData[$empleadoId]['salidas_tempranas']++;
            $stats['salidas_tempranas']++;
        }
    }

    // Calcular overtime del día
    if ($empleadosData[$empleadoId]['dias'][$fecha]['horas'] > $OVERTIME_THRESHOLD) {
        $overtime = $empleadosData[$empleadoId]['dias'][$fecha]['horas'] - $OVERTIME_THRESHOLD;
        $empleadosData[$empleadoId]['overtime'] += $overtime;
        $stats['overtime_horas'] += $overtime;
    }

    $stats['total_horas'] += $horasTrabajadas;
}

// Contar días únicos trabajados
foreach ($empleadosData as $empleado) {
    $stats['total_dias'] += count($empleado['dias']);
}

// Obtener lista de empleados para el filtro
$empleadosStmt = $db->query("
    SELECT id, nombre, apellidos, codigo_empleado
    FROM usuarios
    WHERE activo = 1
    ORDER BY nombre, apellidos
");
$empleadosList = $empleadosStmt->fetchAll();

// Obtener ubicaciones para el filtro
$ubicacionesStmt = $db->query("
    SELECT id, nombre
    FROM ubicaciones
    WHERE activa = 1
    ORDER BY nombre
");
$ubicacionesList = $ubicacionesStmt->fetchAll();

$page_title = 'Reportes de Asistencia';
$page_subtitle = 'Monitoreo de Horas y Overtime';

// Manejar exportación
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_asistencia_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, "\xEF\xBB\xBF"); // BOM para UTF-8

    // Headers
    fputcsv($output, [
        'Fecha',
        'Empleado',
        'Código',
        'Entrada',
        'Salida',
        'Horas Trabajadas',
        'Overtime',
        'Ubicación',
        'Estado'
    ]);

    // Data
    foreach ($registros as $registro) {
        $horasTrabajadas = round($registro['minutos_trabajados'] / 60, 2);
        $overtime = max(0, $horasTrabajadas - $OVERTIME_THRESHOLD);

        $estado = 'Normal';
        if (!$registro['hora_salida']) {
            $estado = 'Sin salida';
        } elseif (strtotime($registro['hora_entrada']) > strtotime($STANDARD_ENTRY) + ($GRACE_PERIOD * 60)) {
            $estado = 'Tardanza';
        }

        fputcsv($output, [
            $registro['fecha'],
            $registro['nombre'] . ' ' . $registro['apellidos'],
            $registro['codigo_empleado'],
            $registro['hora_entrada'],
            $registro['hora_salida'] ?: 'N/A',
            $horasTrabajadas,
            $overtime,
            $registro['ubicacion_nombre'] ?: 'N/A',
            $estado
        ]);
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Asistencia AlpeFresh</title>

    <link rel="icon" href="/favicon.ico">

    <!-- Common head elements (Font Awesome, Google Fonts, Styles) -->
    <?php include __DIR__ . '/includes/head-common.php'; ?>

    <style>
        .report-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--gold);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--navy);
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .employee-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .employee-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .time-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
        }

        .time-badge.normal {
            background: #dcfce7;
            color: #166534;
        }

        .time-badge.overtime {
            background: #fef3c7;
            color: #92400e;
        }

        .time-badge.late {
            background: #fee2e2;
            color: #991b1b;
        }

        .attendance-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .attendance-table th {
            background: var(--gray-50);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .attendance-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .attendance-table tbody tr:hover {
            background: var(--gray-50);
        }

        .export-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .progress-bar {
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--gold);
            border-radius: 4px;
            transition: width 0.3s;
        }

        .summary-section {
            background: linear-gradient(135deg, var(--navy) 0%, #004080 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .summary-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 2rem;
        }

        .summary-stat {
            text-align: center;
        }

        .summary-stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gold);
        }

        .summary-stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container" style="max-width: 1400px; margin: 2rem auto; padding: 0 1rem;">
        <!-- Filtros -->
        <div class="report-filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div>
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="date_start" class="form-control"
                               value="<?php echo $filterDateStart; ?>">
                    </div>
                    <div>
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" name="date_end" class="form-control"
                               value="<?php echo $filterDateEnd; ?>">
                    </div>

                    <?php if ($userRole === 'admin' || $userRole === 'supervisor'): ?>
                    <div>
                        <label class="form-label">Empleado</label>
                        <select name="employee" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($empleadosList as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"
                                    <?php echo $filterEmployee == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellidos']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Ubicación</label>
                        <select name="location" class="form-control">
                            <option value="">Todas</option>
                            <?php foreach ($ubicacionesList as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>"
                                    <?php echo $filterLocation == $loc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Botones de Exportación -->
        <div class="export-buttons">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>"
               class="btn btn-accent">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>

        <!-- Resumen General -->
        <div class="summary-section">
            <h2 class="summary-title">
                <i class="fas fa-chart-pie"></i> Resumen del Período
            </h2>
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="summary-stat-value">
                        <?php echo number_format($stats['total_horas'], 1); ?>
                    </div>
                    <div class="summary-stat-label">Horas Totales</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-value">
                        <?php echo number_format($stats['overtime_horas'], 1); ?>
                    </div>
                    <div class="summary-stat-label">Horas Extra</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-value">
                        <?php echo $stats['tardanzas']; ?>
                    </div>
                    <div class="summary-stat-label">Tardanzas</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-value">
                        <?php echo $stats['salidas_tempranas']; ?>
                    </div>
                    <div class="summary-stat-label">Salidas Tempranas</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-value">
                        <?php echo count($empleadosData); ?>
                    </div>
                    <div class="summary-stat-label">Empleados</div>
                </div>
            </div>
        </div>

        <!-- Estadísticas por Empleado -->
        <?php if ($userRole === 'admin' || $userRole === 'supervisor'): ?>
        <h2 style="margin-bottom: 1rem; color: var(--navy);">
            <i class="fas fa-users"></i> Resumen por Empleado
        </h2>
        <div class="stats-grid">
            <?php foreach ($empleadosData as $empId => $empData): ?>
            <?php
                $expectedHours = count($empData['dias']) * $STANDARD_HOURS;
                $percentage = $expectedHours > 0 ? ($empData['total_horas'] / $expectedHours) * 100 : 0;
            ?>
            <div class="employee-card">
                <div class="employee-header">
                    <div>
                        <strong><?php echo htmlspecialchars($empData['nombre']); ?></strong>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">
                            <?php echo htmlspecialchars($empData['codigo']); ?>
                        </div>
                    </div>
                    <?php if ($empData['overtime'] > 0): ?>
                    <span class="time-badge overtime">
                        +<?php echo number_format($empData['overtime'], 1); ?>h OT
                    </span>
                    <?php endif; ?>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Horas Trabajadas</div>
                        <div style="font-size: 1.25rem; font-weight: 600; color: var(--navy);">
                            <?php echo number_format($empData['total_horas'], 1); ?>h
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Días</div>
                        <div style="font-size: 1.25rem; font-weight: 600; color: var(--navy);">
                            <?php echo count($empData['dias']); ?>
                        </div>
                    </div>
                </div>

                <?php if ($empData['tardanzas'] > 0): ?>
                <div style="font-size: 0.875rem; color: var(--red-600); margin-bottom: 0.5rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $empData['tardanzas']; ?> tardanza<?php echo $empData['tardanzas'] > 1 ? 's' : ''; ?>
                </div>
                <?php endif; ?>

                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min(100, $percentage); ?>%"></div>
                </div>
                <div style="font-size: 0.75rem; color: var(--gray-600); margin-top: 0.25rem;">
                    <?php echo number_format($percentage, 1); ?>% del tiempo esperado
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Tabla Detallada -->
        <h2 style="margin-bottom: 1rem; color: var(--navy);">
            <i class="fas fa-table"></i> Registros Detallados
        </h2>
        <div style="overflow-x: auto;">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Empleado</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Tiempo Trabajado</th>
                        <th>Overtime</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                    <?php
                        $horasTrabajadas = round($registro['minutos_trabajados'] / 60, 2);
                        $overtime = max(0, $horasTrabajadas - $OVERTIME_THRESHOLD);

                        // Determinar estado
                        $estado = 'normal';
                        $estadoTexto = 'Normal';

                        if (!$registro['hora_salida']) {
                            $estado = 'late';
                            $estadoTexto = 'Sin salida';
                        } elseif (strtotime($registro['hora_entrada']) > strtotime($STANDARD_ENTRY) + ($GRACE_PERIOD * 60)) {
                            $estado = 'late';
                            $estadoTexto = 'Tardanza';
                        } elseif ($overtime > 0) {
                            $estado = 'overtime';
                            $estadoTexto = 'Overtime';
                        }
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo date('d/m/Y', strtotime($registro['fecha'])); ?></strong>
                            <div style="font-size: 0.75rem; color: var(--gray-500);">
                                <?php
                                $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                echo $dias[date('w', strtotime($registro['fecha']))];
                                ?>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($registro['nombre'] . ' ' . $registro['apellidos']); ?></strong>
                            <div style="font-size: 0.75rem; color: var(--gray-500);">
                                <?php echo htmlspecialchars($registro['codigo_empleado']); ?>
                            </div>
                        </td>
                        <td>
                            <i class="fas fa-sign-in-alt" style="color: var(--green-500); margin-right: 0.25rem;"></i>
                            <?php echo $registro['hora_entrada']; ?>
                        </td>
                        <td>
                            <?php if ($registro['hora_salida']): ?>
                                <i class="fas fa-sign-out-alt" style="color: var(--red-500); margin-right: 0.25rem;"></i>
                                <?php echo $registro['hora_salida']; ?>
                            <?php else: ?>
                                <span style="color: var(--gray-400);">--:--</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo floor($horasTrabajadas); ?>h <?php echo round(($horasTrabajadas - floor($horasTrabajadas)) * 60); ?>m</strong>
                        </td>
                        <td>
                            <?php if ($overtime > 0): ?>
                                <span class="time-badge overtime">
                                    +<?php echo number_format($overtime, 1); ?>h
                                </span>
                            <?php else: ?>
                                <span style="color: var(--gray-400);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($registro['ubicacion_nombre']): ?>
                                <i class="fas fa-map-marker-alt" style="color: var(--gray-500); margin-right: 0.25rem;"></i>
                                <?php echo htmlspecialchars($registro['ubicacion_nombre']); ?>
                            <?php else: ?>
                                <span style="color: var(--gray-400);">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="time-badge <?php echo $estado; ?>">
                                <?php echo $estadoTexto; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p>No hay registros para el período seleccionado</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    // Auto-refresh cada 5 minutos
    setTimeout(() => {
        location.reload();
    }, 5 * 60 * 1000);
    </script>
</body>
</html>