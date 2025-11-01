<?php
// Aggressive cache prevention
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar autenticación
require_once __DIR__ . '/includes/auth.php';
Auth::requireAuth();

// Obtener datos del usuario actual
$currentUser = Auth::getCurrentUser();

// Obtener datos de la base de datos
require_once __DIR__ . '/config/database.php';
$db = db();

// Obtener estadísticas del usuario
try {
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_marcajes_hoy
        FROM asistencias
        WHERE usuario_id = ? AND DATE(entrada) = CURDATE()
    ");
    $stmt->execute([$currentUser['id']]);
    $marcajes_hoy = $stmt->fetch()['total_marcajes_hoy'];
} catch (Exception $e) {
    $marcajes_hoy = 0;
}

// Obtener últimos registros
try {
    if ($currentUser['rol'] == 'admin' || $currentUser['rol'] == 'supervisor') {
        // Admin ve todos los registros
        $stmt = $db->prepare("
            SELECT a.*, u.nombre, u.apellidos, ub.nombre as ubicacion_nombre
            FROM asistencias a
            JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN ubicaciones ub ON a.ubicacion_id = ub.id
            ORDER BY a.entrada DESC
            LIMIT 15
        ");
        $stmt->execute();
    } else {
        // Usuario normal solo ve sus registros
        $stmt = $db->prepare("
            SELECT a.*, u.nombre, u.apellidos, ub.nombre as ubicacion_nombre
            FROM asistencias a
            JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN ubicaciones ub ON a.ubicacion_id = ub.id
            WHERE a.usuario_id = ?
            ORDER BY a.entrada DESC
            LIMIT 15
        ");
        $stmt->execute([$currentUser['id']]);
    }
    $ultimos_registros = $stmt->fetchAll();
} catch (Exception $e) {
    $ultimos_registros = [];
}

// Obtener datos para el gráfico semanal
try {
    $stmt = $db->prepare("
        SELECT
            DAYOFWEEK(entrada) as dia_semana,
            DATE(entrada) as fecha,
            SUM(TIMESTAMPDIFF(HOUR, entrada, IFNULL(salida, NOW()))) as horas_trabajadas
        FROM asistencias
        WHERE usuario_id = ?
        AND entrada >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(entrada)
        ORDER BY fecha
    ");
    $stmt->execute([$currentUser['id']]);
    $datos_semanales = $stmt->fetchAll();

    // Preparar array para el gráfico (7 días de la semana)
    $horas_semana = [0, 0, 0, 0, 0, 0, 0];
    foreach ($datos_semanales as $dia) {
        // MySQL DAYOFWEEK: 1=Domingo, 2=Lunes, etc.
        // Convertir a 0=Lunes, 1=Martes, etc.
        $indice = ($dia['dia_semana'] + 5) % 7;
        $horas_semana[$indice] = round($dia['horas_trabajadas'], 1);
    }
} catch (Exception $e) {
    $horas_semana = [0, 0, 0, 0, 0, 0, 0];
}

// Obtener entrada de hoy si existe
try {
    $stmt = $db->prepare("
        SELECT entrada, salida
        FROM asistencias
        WHERE usuario_id = ? AND DATE(entrada) = CURDATE()
        ORDER BY entrada DESC
        LIMIT 1
    ");
    $stmt->execute([$currentUser['id']]);
    $registro_hoy = $stmt->fetch();
} catch (Exception $e) {
    $registro_hoy = null;
}

// Calcular horas trabajadas hoy
$horas_trabajadas_hoy = 0;
if ($registro_hoy && $registro_hoy['entrada']) {
    $entrada = new DateTime($registro_hoy['entrada']);
    $salida = $registro_hoy['salida'] ? new DateTime($registro_hoy['salida']) : new DateTime();
    $intervalo = $entrada->diff($salida);
    $horas_trabajadas_hoy = $intervalo->h + ($intervalo->i / 60);
}

// Calcular porcentaje de asistencia del mes
try {
    $stmt = $db->prepare("
        SELECT
            COUNT(DISTINCT DATE(entrada)) as dias_asistidos,
            DAY(LAST_DAY(CURDATE())) as dias_mes
        FROM asistencias
        WHERE usuario_id = ?
        AND MONTH(entrada) = MONTH(CURDATE())
        AND YEAR(entrada) = YEAR(CURDATE())
    ");
    $stmt->execute([$currentUser['id']]);
    $asistencia = $stmt->fetch();

    // Días laborables aproximados (excluyendo fines de semana)
    $dias_laborables = ceil($asistencia['dias_mes'] * 5 / 7);
    $porcentaje_asistencia = $dias_laborables > 0 ?
        round(($asistencia['dias_asistidos'] / $dias_laborables) * 100, 1) : 0;
} catch (Exception $e) {
    $porcentaje_asistencia = 0;
}

// Obtener ubicaciones activas
try {
    $stmt = $db->prepare("
        SELECT * FROM ubicaciones
        WHERE empresa_id = ? AND activa = 1
        ORDER BY nombre
    ");
    $stmt->execute([$currentUser['empresa_id']]);
    $ubicaciones = $stmt->fetchAll();
} catch (Exception $e) {
    $ubicaciones = [];
}

// Configuración de página
$page_title = 'Dashboard de Asistencia';
$page_subtitle = 'Panel de Control Principal';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Asistencia</title>

    <!-- Common head elements (Font Awesome, Google Fonts, Styles) -->
    <?php include __DIR__ . '/includes/head-common.php'; ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Dashboard específico */
        .dashboard-header {
            background: linear-gradient(135deg, var(--navy) 0%, #004080 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .welcome-text {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .datetime-display {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .datetime-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-action-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .quick-action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            border-color: var(--gold);
        }

        .quick-action-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--gold) 0%, #ffc942 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: var(--navy);
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            height: 400px;
            position: relative;
        }

        .location-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            border-left: 4px solid var(--green-600);
        }

        .location-card.inactive {
            border-left-color: var(--gray-400);
        }

        .clock-display {
            font-size: 3rem;
            font-weight: 700;
            color: var(--navy);
            text-align: center;
            margin: 2rem 0;
        }

        .pulse-button {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(253, 183, 20, 0.4);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(253, 183, 20, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(253, 183, 20, 0);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="welcome-text">
                ¡Bienvenido, <?php echo htmlspecialchars(explode(' ', $currentUser['nombre'])[0]); ?>! 👋
            </div>
            <div class="datetime-display">
                <div class="datetime-item">
                    <i class="fas fa-calendar"></i>
                    <span id="current-date"></span>
                </div>
                <div class="datetime-item">
                    <i class="fas fa-clock"></i>
                    <span id="current-time"></span>
                </div>
                <div class="datetime-item">
                    <i class="fas fa-briefcase"></i>
                    <span><?php echo ucfirst($currentUser['rol']); ?></span>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-value"><?php echo $marcajes_hoy; ?></div>
                <div class="stat-label">Marcajes Hoy</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 12% vs ayer
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php
                    $horas = floor($horas_trabajadas_hoy);
                    $minutos = round(($horas_trabajadas_hoy - $horas) * 60);
                    echo $horas . 'h ' . $minutos . 'm';
                    ?>
                </div>
                <div class="stat-label">Horas Trabajadas Hoy</div>
                <div class="stat-change <?php echo $horas_trabajadas_hoy >= 8 ? 'positive' : ''; ?>">
                    <i class="fas fa-<?php echo $horas_trabajadas_hoy >= 8 ? 'check-circle' : 'clock'; ?>"></i>
                    <?php echo $horas_trabajadas_hoy >= 8 ? 'Completo' : 'En progreso'; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $porcentaje_asistencia; ?>%</div>
                <div class="stat-label">Asistencia del Mes</div>
                <div class="stat-change <?php echo $porcentaje_asistencia >= 95 ? 'positive' : ($porcentaje_asistencia >= 80 ? '' : 'negative'); ?>">
                    <i class="fas fa-<?php echo $porcentaje_asistencia >= 95 ? 'trophy' : 'chart-line'; ?>"></i>
                    <?php
                    if ($porcentaje_asistencia >= 95) echo 'Excelente';
                    elseif ($porcentaje_asistencia >= 80) echo 'Buena';
                    else echo 'Mejorar';
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($ubicaciones); ?></div>
                <div class="stat-label">Ubicaciones Activas</div>
                <div class="stat-change">
                    <i class="fas fa-map-marker-alt"></i> Disponibles
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 style="margin-bottom: 1rem; color: var(--navy);">
            <i class="fas fa-bolt" style="color: var(--gold);"></i> Acciones Rápidas
        </h2>
        <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
            <div class="quick-action-card" onclick="window.location.href='/asistencias.php'">
                <div class="quick-action-icon">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <h3>Marcar Entrada</h3>
                <p style="color: var(--gray-600); font-size: 0.875rem;">Registrar llegada</p>
            </div>
            <div class="quick-action-card" onclick="window.location.href='/asistencias.php'">
                <div class="quick-action-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3>Marcar Salida</h3>
                <p style="color: var(--gray-600); font-size: 0.875rem;">Registrar salida</p>
            </div>
            <div class="quick-action-card" onclick="verReporte()">
                <div class="quick-action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Ver Reportes</h3>
                <p style="color: var(--gray-600); font-size: 0.875rem;">Estadísticas</p>
            </div>
            <div class="quick-action-card" onclick="solicitarPermiso()">
                <div class="quick-action-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3>Solicitar Permiso</h3>
                <p style="color: var(--gray-600); font-size: 0.875rem;">Permisos y vacaciones</p>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-3" style="gap: 2rem;">
            <!-- Reloj y Marcaje -->
            <div class="card">
                <h3 style="margin-bottom: 1rem; color: var(--navy);">
                    <i class="fas fa-clock" style="color: var(--gold);"></i> Reloj de Marcaje
                </h3>
                <div class="clock-display" id="clock-display">
                    00:00:00
                </div>
                <a href="/asistencias.php" class="btn btn-accent pulse-button" style="width: 100%; font-size: 1.125rem; padding: 1rem; text-decoration: none; display: inline-block;">
                    <i class="fas fa-fingerprint"></i>
                    Marcar Asistencia
                </a>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--gray-200);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: var(--gray-600);">Entrada:</span>
                        <span style="font-weight: 600;">
                            <?php echo $registro_hoy && $registro_hoy['entrada'] ? date('h:i A', strtotime($registro_hoy['entrada'])) : '--:--'; ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: var(--gray-600);">Salida:</span>
                        <span style="font-weight: 600;">
                            <?php echo $registro_hoy && $registro_hoy['salida'] ? date('h:i A', strtotime($registro_hoy['salida'])) : '--:--'; ?>
                        </span>
                    </div>
                    <?php if ($registro_hoy && $registro_hoy['entrada'] && !$registro_hoy['salida']): ?>
                        <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--gray-100);">
                            <span style="color: var(--gray-600);">Tiempo trabajado:</span>
                            <span style="font-weight: 600; color: var(--green-600);" id="tiempo-trabajado">
                                Calculando...
                            </span>
                        </div>
                        <script>
                            // Calcular tiempo trabajado en tiempo real
                            function calcularTiempoTrabajado() {
                                const entrada = new Date('<?php echo $registro_hoy['entrada']; ?>');
                                const ahora = new Date();
                                const diff = ahora - entrada;
                                const horas = Math.floor(diff / 3600000);
                                const minutos = Math.floor((diff % 3600000) / 60000);
                                document.getElementById('tiempo-trabajado').textContent = `${horas}h ${minutos}m`;
                            }
                            calcularTiempoTrabajado();
                            setInterval(calcularTiempoTrabajado, 60000); // Actualizar cada minuto
                        </script>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gráfico de Asistencia -->
            <div class="chart-container">
                <h3 style="margin-bottom: 1rem; color: var(--navy);">
                    <i class="fas fa-chart-bar" style="color: var(--gold);"></i> Asistencia Semanal
                </h3>
                <canvas id="attendanceChart"></canvas>
            </div>

            <!-- Últimos Registros -->
            <div class="card">
                <h3 style="margin-bottom: 1rem; color: var(--navy);">
                    <i class="fas fa-history" style="color: var(--gold);"></i> Últimos Registros
                </h3>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php if (count($ultimos_registros) > 0): ?>
                        <?php foreach ($ultimos_registros as $registro): ?>
                            <div style="padding: 0.75rem; border-bottom: 1px solid var(--gray-100);">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: var(--navy); font-size: 0.95rem;">
                                            <?php echo htmlspecialchars($registro['nombre'] . ' ' . $registro['apellidos']); ?>
                                        </div>
                                        <div style="display: flex; gap: 0.75rem; margin-top: 0.25rem;">
                                            <?php if ($registro['entrada']): ?>
                                                <span style="font-size: 0.8rem; color: #059669;">
                                                    <i class="fas fa-sign-in-alt"></i>
                                                    <?php echo date('h:i A', strtotime($registro['entrada'])); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($registro['salida']): ?>
                                                <span style="font-size: 0.8rem; color: #dc2626;">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                    <?php echo date('h:i A', strtotime($registro['salida'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="font-size: 0.8rem; color: #f59e0b;">
                                                    <i class="fas fa-clock"></i> En curso
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($registro['ubicacion_nombre']): ?>
                                            <div style="font-size: 0.75rem; color: var(--gray-500); margin-top: 0.25rem;">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($registro['ubicacion_nombre']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 0.75rem; color: var(--gray-500);">
                                            <?php echo date('d/m', strtotime($registro['entrada'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                            <p>No hay registros disponibles</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ubicaciones -->
        <h2 style="margin: 2rem 0 1rem; color: var(--navy);">
            <i class="fas fa-map-marked-alt" style="color: var(--gold);"></i> Ubicaciones Autorizadas
        </h2>
        <div class="grid grid-cols-4">
            <?php foreach ($ubicaciones as $ubicacion): ?>
                <div class="location-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                        <div>
                            <h4 style="font-weight: 600; color: var(--navy);">
                                <?php echo htmlspecialchars($ubicacion['nombre']); ?>
                            </h4>
                            <p style="font-size: 0.875rem; color: var(--gray-600);">
                                <?php echo htmlspecialchars($ubicacion['direccion'] ?? 'Sin dirección'); ?>
                            </p>
                        </div>
                        <span class="badge badge-success">Activa</span>
                    </div>
                    <div style="display: flex; gap: 1rem; font-size: 0.875rem; color: var(--gray-600);">
                        <span>
                            <i class="fas fa-circle-notch"></i> <?php echo $ubicacion['radio_metros']; ?>m
                        </span>
                        <span>
                            <i class="fas fa-building"></i> <?php echo ucfirst($ubicacion['tipo']); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Reloj en tiempo real
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock-display').textContent = `${hours}:${minutes}:${seconds}`;
        }

        // Fecha y hora actual
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('es-ES', options);
            document.getElementById('current-time').textContent = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        }

        // Gráfico de asistencia con datos reales
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Horas Trabajadas',
                    data: <?php echo json_encode($horas_semana); ?>,
                    backgroundColor: 'rgba(253, 183, 20, 0.8)',
                    borderColor: 'rgba(253, 183, 20, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 12,
                        ticks: {
                            callback: function(value) {
                                return value + 'h';
                            }
                        }
                    }
                }
            }
        });

        // Funciones de acción
        function marcarAsistencia() {
            alert('Función de marcaje en desarrollo');
        }

        function marcarSalida() {
            alert('Función de salida en desarrollo');
        }

        function verReporte() {
            window.location.href = '/reportes.php';
        }

        function solicitarPermiso() {
            alert('Función de permisos en desarrollo');
        }

        // Inicializar
        setInterval(updateClock, 1000);
        setInterval(updateDateTime, 60000);
        updateClock();
        updateDateTime();
    </script>
</body>
</html>