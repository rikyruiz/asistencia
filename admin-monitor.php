<?php
/**
 * Monitor de Sesiones y Períodos de Trabajo
 * Panel administrativo para ver logins y horas trabajadas
 */

session_start();

// Verificar autenticación y rol de admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /login.php');
    exit;
}

if ($_SESSION['user_rol'] !== 'admin') {
    header('Location: /dashboard.php');
    exit;
}

require_once 'config/database.php';

$page_title = 'Monitor de Sesiones';
$page_subtitle = 'Control de logins y períodos de trabajo';

$db = db();

// Filtros
$filterDateStart = $_GET['date_start'] ?? date('Y-m-01');
$filterDateEnd = $_GET['date_end'] ?? date('Y-m-d');
$filterEmployee = $_GET['employee'] ?? '';
$activeTab = $_GET['tab'] ?? 'summary';

// 1. OBTENER RESUMEN POR EMPLEADO
function getEmployeeSummary($db, $dateStart, $dateEnd, $employeeId = null) {
    $query = "
        SELECT
            u.id,
            u.codigo_empleado,
            u.nombre,
            u.apellidos,
            u.email,
            u.rol,
            u.activo,
            u.ultimo_acceso,
            COUNT(DISTINCT s.id) as total_logins,
            MAX(s.created_at) as ultimo_login,
            COUNT(DISTINCT ra.fecha) as dias_trabajados,
            SUM(TIMESTAMPDIFF(MINUTE, ra.hora_entrada, IFNULL(ra.hora_salida, NOW())) / 60) as total_horas,
            MAX(ra.fecha) as ultima_asistencia
        FROM usuarios u
        LEFT JOIN sesiones s ON u.id = s.usuario_id
        LEFT JOIN registros_asistencia ra ON u.id = ra.usuario_id
            AND ra.fecha BETWEEN ? AND ?
        WHERE u.empresa_id = (SELECT empresa_id FROM usuarios WHERE id = ? LIMIT 1)
    ";

    $params = [$dateStart, $dateEnd, $_SESSION['user_id']];

    if ($employeeId) {
        $query .= " AND u.id = ?";
        $params[] = $employeeId;
    }

    $query .= " GROUP BY u.id ORDER BY total_horas DESC, u.apellidos, u.nombre";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// 2. OBTENER SESIONES DE LOGIN
function getLoginSessions($db, $dateStart, $dateEnd, $employeeId = null, $limit = 100) {
    $query = "
        SELECT
            s.id,
            s.usuario_id,
            u.nombre,
            u.apellidos,
            u.codigo_empleado,
            s.created_at as login_time,
            s.ultima_actividad,
            s.expira_en,
            s.activa,
            s.ip,
            s.dispositivo_id,
            TIMESTAMPDIFF(MINUTE, s.created_at, IFNULL(s.ultima_actividad, NOW())) as duracion_minutos
        FROM sesiones s
        JOIN usuarios u ON s.usuario_id = u.id
        WHERE DATE(s.created_at) BETWEEN ? AND ?
    ";

    $params = [$dateStart, $dateEnd];

    if ($employeeId) {
        $query .= " AND s.usuario_id = ?";
        $params[] = $employeeId;
    }

    $query .= " ORDER BY s.created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// 3. OBTENER PERÍODOS DE TRABAJO
function getWorkPeriods($db, $dateStart, $dateEnd, $employeeId = null, $limit = 100) {
    $query = "
        SELECT
            ra.id,
            ra.usuario_id,
            u.nombre,
            u.apellidos,
            u.codigo_empleado,
            ra.fecha,
            ra.hora_entrada,
            ra.hora_salida,
            ub.nombre as ubicacion,
            TIMESTAMPDIFF(MINUTE, ra.hora_entrada, IFNULL(ra.hora_salida, NOW())) / 60 as horas_trabajadas,
            ra.lat_entrada,
            ra.lon_entrada,
            ra.lat_salida,
            ra.lon_salida,
            ra.estado
        FROM registros_asistencia ra
        JOIN usuarios u ON ra.usuario_id = u.id
        LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
        WHERE ra.fecha BETWEEN ? AND ?
    ";

    $params = [$dateStart, $dateEnd];

    if ($employeeId) {
        $query .= " AND ra.usuario_id = ?";
        $params[] = $employeeId;
    }

    $query .= " ORDER BY ra.fecha DESC, ra.hora_entrada DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// 4. OBTENER MARCAJES DETALLADOS
function getDetailedClockEvents($db, $dateStart, $dateEnd, $employeeId = null, $limit = 100) {
    $query = "
        SELECT
            m.id,
            m.usuario_id,
            u.nombre,
            u.apellidos,
            u.codigo_empleado,
            m.tipo,
            m.hora,
            m.metodo,
            m.validado,
            ub.nombre as ubicacion,
            m.ip,
            m.latitud,
            m.longitud
        FROM marcajes m
        JOIN usuarios u ON m.usuario_id = u.id
        LEFT JOIN ubicaciones ub ON m.ubicacion_id = ub.id
        WHERE DATE(m.hora) BETWEEN ? AND ?
    ";

    $params = [$dateStart, $dateEnd];

    if ($employeeId) {
        $query .= " AND m.usuario_id = ?";
        $params[] = $employeeId;
    }

    $query .= " ORDER BY m.hora DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// 5. DETECTAR DISCREPANCIAS EN ENTRADAS Y SALIDAS
function getDiscrepancies($db, $dateStart, $dateEnd, $employeeId = null) {
    $discrepancies = [];

    // Query para obtener todos los registros de asistencia en el rango
    $query = "
        SELECT
            ra.id,
            ra.usuario_id,
            u.nombre,
            u.apellidos,
            u.codigo_empleado,
            ra.fecha,
            ra.hora_entrada,
            ra.hora_salida,
            ub.nombre as ubicacion,
            TIMESTAMPDIFF(MINUTE, ra.hora_entrada, IFNULL(ra.hora_salida, NOW())) / 60 as horas_trabajadas
        FROM registros_asistencia ra
        JOIN usuarios u ON ra.usuario_id = u.id
        LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
        WHERE ra.fecha BETWEEN ? AND ?
    ";

    $params = [$dateStart, $dateEnd];

    if ($employeeId) {
        $query .= " AND ra.usuario_id = ?";
        $params[] = $employeeId;
    }

    $query .= " ORDER BY ra.usuario_id, ra.fecha, ra.hora_entrada";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    foreach ($records as $record) {
        $issues = [];
        $severity = 'info';

        // 1. Verificar si falta la salida
        if (empty($record['hora_salida'])) {
            $issues[] = 'Falta registrar salida';
            $severity = 'warning';

            // Verificar si es de hace más de 24 horas
            $entryTime = strtotime($record['hora_entrada']);
            $now = time();
            $hoursSince = ($now - $entryTime) / 3600;

            if ($hoursSince > 24) {
                $issues[] = sprintf('Entrada sin salida desde hace %.1f horas', $hoursSince);
                $severity = 'critical';
            }
        }

        // 2. Verificar horas trabajadas inusuales
        if (!empty($record['hora_salida'])) {
            $horasTrabajadas = $record['horas_trabajadas'];

            // Menos de 1 hora
            if ($horasTrabajadas < 1) {
                $issues[] = sprintf('Sesión muy corta (%.2f horas)', $horasTrabajadas);
                $severity = ($severity === 'critical') ? 'critical' : 'warning';
            }

            // Más de 10 horas (límite configurado)
            if ($horasTrabajadas > 10) {
                $issues[] = sprintf('Sesión excede el límite de 10 horas (%.2f horas registradas)', $horasTrabajadas);
                $severity = 'critical';
            }
        }

        // 3. Verificar múltiples entradas en el mismo día
        $countQuery = "
            SELECT COUNT(*) as count
            FROM registros_asistencia
            WHERE usuario_id = ? AND fecha = ?
        ";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute([$record['usuario_id'], $record['fecha']]);
        $countResult = $countStmt->fetch();

        if ($countResult['count'] > 1) {
            $issues[] = sprintf('Múltiples registros en el mismo día (%d registros)', $countResult['count']);
            $severity = ($severity === 'critical') ? 'critical' : 'warning';
        }

        // 4. Verificar marcajes asociados
        $marcajesQuery = "
            SELECT COUNT(*) as entradas,
                   SUM(CASE WHEN tipo = 'salida' THEN 1 ELSE 0 END) as salidas
            FROM marcajes
            WHERE usuario_id = ? AND DATE(hora) = ?
        ";
        $marcajesStmt = $db->prepare($marcajesQuery);
        $marcajesStmt->execute([$record['usuario_id'], $record['fecha']]);
        $marcajesResult = $marcajesStmt->fetch();

        $entradas = $marcajesResult['entradas'] ?? 0;
        $salidas = $marcajesResult['salidas'] ?? 0;

        if ($entradas > $salidas && empty($record['hora_salida'])) {
            $issues[] = sprintf('Desbalance de marcajes: %d entradas, %d salidas', $entradas, $salidas);
            $severity = ($severity === 'critical') ? 'critical' : 'warning';
        }

        // Solo agregar si hay problemas detectados
        if (count($issues) > 0) {
            $discrepancies[] = [
                'id' => $record['id'],
                'usuario_id' => $record['usuario_id'],
                'nombre' => $record['nombre'],
                'apellidos' => $record['apellidos'],
                'codigo_empleado' => $record['codigo_empleado'],
                'fecha' => $record['fecha'],
                'hora_entrada' => $record['hora_entrada'],
                'hora_salida' => $record['hora_salida'],
                'ubicacion' => $record['ubicacion'],
                'horas_trabajadas' => $record['horas_trabajadas'],
                'issues' => $issues,
                'severity' => $severity
            ];
        }
    }

    return $discrepancies;
}

// Obtener lista de empleados para el filtro
$employeesStmt = $db->prepare("
    SELECT id, codigo_empleado, nombre, apellidos
    FROM usuarios
    WHERE empresa_id = (SELECT empresa_id FROM usuarios WHERE id = ? LIMIT 1)
    ORDER BY apellidos, nombre
");
$employeesStmt->execute([$_SESSION['user_id']]);
$employees = $employeesStmt->fetchAll();

// Obtener datos según la pestaña activa
$summary = getEmployeeSummary($db, $filterDateStart, $filterDateEnd, $filterEmployee);
$sessions = ($activeTab === 'sessions') ? getLoginSessions($db, $filterDateStart, $filterDateEnd, $filterEmployee) : [];
$workPeriods = ($activeTab === 'periods') ? getWorkPeriods($db, $filterDateStart, $filterDateEnd, $filterEmployee) : [];
$clockEvents = ($activeTab === 'events') ? getDetailedClockEvents($db, $filterDateStart, $filterDateEnd, $filterEmployee) : [];
$discrepancies = ($activeTab === 'discrepancies') ? getDiscrepancies($db, $filterDateStart, $filterDateEnd, $filterEmployee) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Asistencia AlpeFresh</title>

    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Estilos del marketplace -->
    <?php include 'includes/styles.php'; ?>

    <style>
        .monitor-container {
            max-width: 1600px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .filters-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: var(--gray-700);
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 0.95rem;
            width: 100%;
        }

        .btn-filter {
            background: var(--green-600);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-filter:hover {
            background: var(--green-700);
            transform: translateY(-1px);
        }

        .btn-reset {
            background: var(--gray-500);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-reset:hover {
            background: var(--gray-600);
            transform: translateY(-1px);
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--gray-200);
            overflow-x: auto;
        }

        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray-600);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab:hover {
            color: var(--navy);
        }

        .tab.active {
            color: var(--navy);
            border-bottom-color: var(--gold);
        }

        .data-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .data-card h3 {
            margin: 0 0 15px 0;
            color: var(--navy);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-card h3 i {
            color: var(--gold);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th {
            background: var(--gray-50);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-700);
        }

        tr:hover {
            background: var(--gray-50);
        }

        .badge-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .gps-coords {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray-500);
        }

        .no-data i {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }

            .tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            table {
                font-size: 0.8rem;
            }

            th, td {
                padding: 8px;
            }

            .monitor-container {
                padding: 0 10px;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <div class="container">
        <div class="monitor-container">
            <!-- Filtros -->
            <div class="filters-card">
                <form method="GET" action="">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($activeTab); ?>">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label for="date_start"><i class="fas fa-calendar"></i> Fecha Inicio</label>
                            <input type="date" id="date_start" name="date_start" value="<?php echo htmlspecialchars($filterDateStart); ?>" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="date_end"><i class="fas fa-calendar"></i> Fecha Fin</label>
                            <input type="date" id="date_end" name="date_end" value="<?php echo htmlspecialchars($filterDateEnd); ?>" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="employee"><i class="fas fa-user"></i> Empleado</label>
                            <select id="employee" name="employee" class="form-control">
                                <option value="">Todos los empleados</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo $filterEmployee == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['codigo_empleado'] . ' - ' . $emp['nombre'] . ' ' . $emp['apellidos']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>

                        <div class="form-group">
                            <a href="?tab=<?php echo htmlspecialchars($activeTab); ?>" class="btn-reset">
                                <i class="fas fa-redo"></i> Reiniciar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Pestañas -->
            <div class="tabs">
                <a href="?tab=summary&date_start=<?php echo urlencode($filterDateStart); ?>&date_end=<?php echo urlencode($filterDateEnd); ?>&employee=<?php echo urlencode($filterEmployee); ?>" class="tab <?php echo $activeTab === 'summary' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> Resumen
                </a>
                <a href="?tab=discrepancies&date_start=<?php echo urlencode($filterDateStart); ?>&date_end=<?php echo urlencode($filterDateEnd); ?>&employee=<?php echo urlencode($filterEmployee); ?>" class="tab <?php echo $activeTab === 'discrepancies' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i> Discrepancias
                </a>
                <a href="?tab=sessions&date_start=<?php echo urlencode($filterDateStart); ?>&date_end=<?php echo urlencode($filterDateEnd); ?>&employee=<?php echo urlencode($filterEmployee); ?>" class="tab <?php echo $activeTab === 'sessions' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-in-alt"></i> Sesiones
                </a>
                <a href="?tab=periods&date_start=<?php echo urlencode($filterDateStart); ?>&date_end=<?php echo urlencode($filterDateEnd); ?>&employee=<?php echo urlencode($filterEmployee); ?>" class="tab <?php echo $activeTab === 'periods' ? 'active' : ''; ?>">
                    <i class="fas fa-business-time"></i> Períodos
                </a>
                <a href="?tab=events&date_start=<?php echo urlencode($filterDateStart); ?>&date_end=<?php echo urlencode($filterDateEnd); ?>&employee=<?php echo urlencode($filterEmployee); ?>" class="tab <?php echo $activeTab === 'events' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Marcajes
                </a>
            </div>

            <!-- Contenido de las pestañas -->
            <?php if ($activeTab === 'summary'): ?>
                <!-- RESUMEN POR EMPLEADO -->
                <div class="data-card">
                    <h3><i class="fas fa-chart-pie"></i> Resumen por Empleado</h3>
                    <p style="color: var(--gray-600); margin-bottom: 20px;">
                        Período: <?php echo date('d/m/Y', strtotime($filterDateStart)); ?> - <?php echo date('d/m/Y', strtotime($filterDateEnd)); ?>
                    </p>

                    <?php if (count($summary) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Código</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Total Logins</th>
                                        <th>Último Login</th>
                                        <th>Días Trabajados</th>
                                        <th>Total Horas</th>
                                        <th>Última Asistencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($summary as $emp): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellidos']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($emp['codigo_empleado'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php
                                                $roleBadge = [
                                                    'admin' => 'badge-danger',
                                                    'supervisor' => 'badge-warning',
                                                    'empleado' => 'badge-info'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $roleBadge[$emp['rol']] ?? 'badge-secondary'; ?>">
                                                    <?php echo strtoupper($emp['rol']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $emp['activo'] ? 'badge-success' : 'badge-danger'; ?>">
                                                    <?php echo $emp['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $emp['total_logins'] ?? 0; ?></td>
                                            <td>
                                                <?php
                                                echo $emp['ultimo_login']
                                                    ? date('d/m/Y H:i', strtotime($emp['ultimo_login']))
                                                    : '<span style="color: var(--gray-400);">Nunca</span>';
                                                ?>
                                            </td>
                                            <td><strong><?php echo $emp['dias_trabajados'] ?? 0; ?></strong></td>
                                            <td><strong><?php echo number_format($emp['total_horas'] ?? 0, 2); ?> hrs</strong></td>
                                            <td>
                                                <?php
                                                echo $emp['ultima_asistencia']
                                                    ? date('d/m/Y', strtotime($emp['ultima_asistencia']))
                                                    : '<span style="color: var(--gray-400);">Nunca</span>';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>No hay datos disponibles para el período seleccionado</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab === 'sessions'): ?>
                <!-- SESIONES DE LOGIN -->
                <div class="data-card">
                    <h3><i class="fas fa-sign-in-alt"></i> Sesiones de Login</h3>
                    <p style="color: var(--gray-600); margin-bottom: 20px;">
                        Mostrando últimas 100 sesiones del período seleccionado
                    </p>

                    <?php if (count($sessions) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Código</th>
                                        <th>Login</th>
                                        <th>Última Actividad</th>
                                        <th>Duración</th>
                                        <th>Estado</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sessions as $session): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($session['nombre'] . ' ' . $session['apellidos']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($session['codigo_empleado'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($session['login_time'])); ?></td>
                                            <td>
                                                <?php
                                                echo $session['ultima_actividad']
                                                    ? date('d/m/Y H:i', strtotime($session['ultima_actividad']))
                                                    : '<span style="color: var(--gray-400);">N/A</span>';
                                                ?>
                                            </td>
                                            <td><?php echo $session['duracion_minutos']; ?> min</td>
                                            <td>
                                                <span class="badge <?php echo $session['activa'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                    <?php echo $session['activa'] ? 'Activa' : 'Inactiva'; ?>
                                                </span>
                                            </td>
                                            <td class="gps-coords"><?php echo htmlspecialchars($session['ip'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>No hay sesiones para el período seleccionado</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab === 'periods'): ?>
                <!-- PERÍODOS DE TRABAJO -->
                <div class="data-card">
                    <h3><i class="fas fa-business-time"></i> Períodos de Trabajo</h3>
                    <p style="color: var(--gray-600); margin-bottom: 20px;">
                        Mostrando últimos 100 registros del período seleccionado
                    </p>

                    <?php if (count($workPeriods) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Código</th>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Horas</th>
                                        <th>Ubicación</th>
                                        <th>GPS Entrada</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($workPeriods as $period): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($period['nombre'] . ' ' . $period['apellidos']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($period['codigo_empleado'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($period['fecha'])); ?></td>
                                            <td><?php echo $period['hora_entrada'] ? date('H:i', strtotime($period['hora_entrada'])) : 'N/A'; ?></td>
                                            <td>
                                                <?php
                                                if ($period['hora_salida']) {
                                                    echo date('H:i', strtotime($period['hora_salida']));
                                                } else {
                                                    echo '<span class="badge badge-warning">Abierto</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><strong><?php echo number_format($period['horas_trabajadas'], 2); ?> hrs</strong></td>
                                            <td><?php echo htmlspecialchars($period['ubicacion'] ?? 'N/A'); ?></td>
                                            <td class="gps-coords">
                                                <?php
                                                echo $period['lat_entrada']
                                                    ? number_format($period['lat_entrada'], 6) . ', ' . number_format($period['lon_entrada'], 6)
                                                    : 'N/A';
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusBadge = [
                                                    'presente' => 'badge-success',
                                                    'ausente' => 'badge-danger',
                                                    'tardanza' => 'badge-warning',
                                                    'permiso' => 'badge-info',
                                                    'vacaciones' => 'badge-info',
                                                    'incapacidad' => 'badge-warning'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $statusBadge[$period['estado']] ?? 'badge-secondary'; ?>">
                                                    <?php echo ucfirst($period['estado']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>No hay períodos de trabajo para el rango seleccionado</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab === 'events'): ?>
                <!-- MARCAJES DETALLADOS -->
                <div class="data-card">
                    <h3><i class="fas fa-clipboard-list"></i> Marcajes Detallados</h3>
                    <p style="color: var(--gray-600); margin-bottom: 20px;">
                        Mostrando últimos 100 marcajes del período seleccionado
                    </p>

                    <?php if (count($clockEvents) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Código</th>
                                        <th>Tipo</th>
                                        <th>Fecha/Hora</th>
                                        <th>Ubicación</th>
                                        <th>GPS</th>
                                        <th>Método</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clockEvents as $event): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($event['nombre'] . ' ' . $event['apellidos']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($event['codigo_empleado'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php
                                                $typeIcons = [
                                                    'entrada' => '<i class="fas fa-sign-in-alt" style="color: var(--green-600);"></i>',
                                                    'salida' => '<i class="fas fa-sign-out-alt" style="color: var(--red-600);"></i>',
                                                    'inicio_comida' => '<i class="fas fa-utensils" style="color: #ff9800;"></i>',
                                                    'fin_comida' => '<i class="fas fa-utensils" style="color: var(--green-600);"></i>',
                                                ];
                                                echo $typeIcons[$event['tipo']] ?? '';
                                                echo ' ' . ucfirst(str_replace('_', ' ', $event['tipo']));
                                                ?>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($event['hora'])); ?></td>
                                            <td><?php echo htmlspecialchars($event['ubicacion'] ?? 'N/A'); ?></td>
                                            <td class="gps-coords">
                                                <?php
                                                echo $event['latitud']
                                                    ? number_format($event['latitud'], 6) . ', ' . number_format($event['longitud'], 6)
                                                    : 'N/A';
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $methodIcons = [
                                                    'gps' => '<i class="fas fa-map-marker-alt"></i> GPS',
                                                    'manual' => '<i class="fas fa-hand-pointer"></i> Manual',
                                                    'qr' => '<i class="fas fa-qrcode"></i> QR',
                                                    'facial' => '<i class="fas fa-user-circle"></i> Facial'
                                                ];
                                                echo $methodIcons[$event['metodo']] ?? $event['metodo'];
                                                ?>
                                            </td>
                                            <td class="gps-coords"><?php echo htmlspecialchars($event['ip'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>No hay marcajes para el período seleccionado</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab === 'discrepancies'): ?>
                <!-- DISCREPANCIAS DE ENTRADAS Y SALIDAS -->
                <div class="data-card">
                    <h3><i class="fas fa-exclamation-triangle"></i> Discrepancias Detectadas</h3>
                    <p style="color: var(--gray-600); margin-bottom: 20px;">
                        Análisis de registros con problemas o inconsistencias (<?php echo count($discrepancies); ?> encontrados)
                    </p>

                    <?php if (count($discrepancies) > 0): ?>
                        <!-- Resumen de severidad -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                            <?php
                            $criticalCount = 0;
                            $warningCount = 0;
                            $infoCount = 0;
                            foreach ($discrepancies as $d) {
                                if ($d['severity'] === 'critical') $criticalCount++;
                                if ($d['severity'] === 'warning') $warningCount++;
                                if ($d['severity'] === 'info') $infoCount++;
                            }
                            ?>
                            <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 15px; border-radius: 8px;">
                                <div style="font-size: 2rem; font-weight: bold;"><?php echo $criticalCount; ?></div>
                                <div style="font-size: 0.9rem; opacity: 0.9;">Críticos</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 15px; border-radius: 8px;">
                                <div style="font-size: 2rem; font-weight: bold;"><?php echo $warningCount; ?></div>
                                <div style="font-size: 0.9rem; opacity: 0.9;">Advertencias</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 15px; border-radius: 8px;">
                                <div style="font-size: 2rem; font-weight: bold;"><?php echo $infoCount; ?></div>
                                <div style="font-size: 0.9rem; opacity: 0.9;">Informativos</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Severidad</th>
                                        <th>Empleado</th>
                                        <th>Código</th>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Horas</th>
                                        <th>Ubicación</th>
                                        <th>Problemas Detectados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($discrepancies as $disc): ?>
                                        <tr style="<?php echo $disc['severity'] === 'critical' ? 'background-color: #fef2f2;' : ($disc['severity'] === 'warning' ? 'background-color: #fffbeb;' : ''); ?>">
                                            <td>
                                                <?php
                                                $severityBadges = [
                                                    'critical' => '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Crítico</span>',
                                                    'warning' => '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Advertencia</span>',
                                                    'info' => '<span class="badge badge-info"><i class="fas fa-info-circle"></i> Info</span>'
                                                ];
                                                echo $severityBadges[$disc['severity']] ?? '';
                                                ?>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($disc['nombre'] . ' ' . $disc['apellidos']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($disc['codigo_empleado'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($disc['fecha'])); ?></td>
                                            <td>
                                                <?php
                                                echo $disc['hora_entrada']
                                                    ? date('H:i', strtotime($disc['hora_entrada']))
                                                    : '<span style="color: var(--gray-400);">N/A</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if ($disc['hora_salida']) {
                                                    echo date('H:i', strtotime($disc['hora_salida']));
                                                } else {
                                                    echo '<span class="badge badge-danger">Sin registrar</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if ($disc['hora_salida']) {
                                                    $horas = $disc['horas_trabajadas'];
                                                    $color = 'var(--gray-700)';
                                                    if ($horas < 1) $color = '#f59e0b';
                                                    if ($horas > 12) $color = '#ef4444';
                                                    echo '<strong style="color: ' . $color . ';">' . number_format($horas, 2) . ' hrs</strong>';
                                                } else {
                                                    echo '<span style="color: var(--gray-400);">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($disc['ubicacion'] ?? 'N/A'); ?></td>
                                            <td>
                                                <ul style="margin: 0; padding-left: 20px; font-size: 0.85rem;">
                                                    <?php foreach ($disc['issues'] as $issue): ?>
                                                        <li style="color: var(--gray-700); margin-bottom: 4px;">
                                                            <?php echo htmlspecialchars($issue); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-check-circle" style="color: var(--green-500);"></i>
                            <p>¡Excelente! No se detectaron discrepancias en el período seleccionado</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script src="/assets/js/dropdown.js"></script>
</body>
</html>
