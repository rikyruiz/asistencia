<?php
/**
 * Sistema de Marcaje de Asistencia con Checkpoint System
 * Permite múltiples check-ins/outs por día en diferentes ubicaciones
 */

session_start();

// Cache prevention
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /login.php');
    exit;
}

require_once 'config/database.php';

$db = db();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_rol'] ?? 'empleado';
$empresaId = $_SESSION['empresa_id'];

// Get user info
$userStmt = $db->prepare("SELECT nombre, apellidos FROM usuarios WHERE id = ?");
$userStmt->execute([$userId]);
$currentUser = $userStmt->fetch();

// Check if checkpoint system is enabled
$checkpointEnabled = true; // Can be read from configuracion_sistema

// Get active checkpoint for today
$activeCheckpoint = null;
$todayCheckpoints = [];

$stmt = $db->prepare("
    SELECT
        ra.*,
        ub.nombre as ubicacion_nombre,
        ub.latitud,
        ub.longitud,
        ub.radio_metros
    FROM registros_asistencia ra
    LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
    WHERE ra.usuario_id = ? AND ra.fecha = CURDATE()
    ORDER BY ra.hora_entrada ASC
");
$stmt->execute([$userId]);
$todayCheckpoints = $stmt->fetchAll();

// Find active checkpoint
foreach ($todayCheckpoints as $cp) {
    if ($cp['is_active'] == 1 && $cp['hora_salida'] === null) {
        $activeCheckpoint = $cp;
        break;
    }
}

// Get available locations
$locStmt = $db->prepare("
    SELECT * FROM ubicaciones
    WHERE empresa_id = ? AND activa = 1
    ORDER BY nombre
");
$locStmt->execute([$empresaId]);
$locations = $locStmt->fetchAll();

// Calculate total hours today
$totalHoursToday = 0;
foreach ($todayCheckpoints as $cp) {
    if ($cp['horas_trabajadas']) {
        $totalHoursToday += $cp['horas_trabajadas'];
    }
}

// If there's an active checkpoint, calculate current hours
if ($activeCheckpoint && $activeCheckpoint['hora_entrada']) {
    $entrada = new DateTime($activeCheckpoint['hora_entrada']);
    $ahora = new DateTime();
    $diff = $entrada->diff($ahora);
    $horasActuales = $diff->h + ($diff->i / 60) + ($diff->days * 24);
}

$page_title = 'Control de Asistencia';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Asistencia</title>

    <?php include __DIR__ . '/includes/head-common.php'; ?>

    <style>
        .checkpoint-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            border-left: 4px solid var(--gold);
        }

        .checkpoint-card.active {
            border-left-color: var(--green-600);
            background: linear-gradient(135deg, #f0fdf4 0%, white 100%);
        }

        .checkpoint-card.completed {
            border-left-color: var(--gray-400);
            opacity: 0.8;
        }

        .checkpoint-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .checkpoint-timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--gray-200);
        }

        .checkpoint-item {
            position: relative;
            margin-bottom: 2rem;
        }

        .checkpoint-dot {
            position: absolute;
            left: -1.65rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--gold);
        }

        .checkpoint-dot.active {
            border-color: var(--green-600);
            background: var(--green-600);
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
            animation: pulse 2s infinite;
        }

        .checkpoint-dot.completed {
            border-color: var(--gray-400);
            background: var(--gray-400);
        }

        .location-card {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .location-card:hover {
            border-color: var(--gold);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(253, 183, 20, 0.2);
        }

        .location-card.selected {
            border-color: var(--gold);
            background: linear-gradient(135deg, #fffbeb 0%, white 100%);
        }

        .action-button {
            width: 100%;
            padding: 1.25rem;
            font-size: 1.125rem;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-checkin {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
        }

        .btn-checkin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(5, 150, 105, 0.3);
        }

        .btn-checkout {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(220, 38, 38, 0.3);
        }

        .btn-transfer {
            background: linear-gradient(135deg, var(--gold) 0%, #ffc942 100%);
            color: var(--navy);
        }

        .btn-transfer:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(253, 183, 20, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--navy);
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .gps-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .gps-status.good {
            background: #f0fdf4;
            color: #059669;
        }

        .gps-status.poor {
            background: #fef3c7;
            color: #f59e0b;
        }

        .gps-status.bad {
            background: #fef2f2;
            color: #dc2626;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container" style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">

        <!-- Header -->
        <div class="dashboard-header" style="background: linear-gradient(135deg, var(--navy) 0%, #004080 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
            <h1 style="font-size: 1.75rem; margin-bottom: 0.5rem;">
                <i class="fas fa-fingerprint"></i> Control de Asistencia
            </h1>
            <p style="opacity: 0.9;">
                <?php echo htmlspecialchars($currentUser['nombre'] . ' ' . $currentUser['apellidos']); ?>
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?php echo count($todayCheckpoints); ?></div>
                <div class="stat-label">Checkpoints Hoy</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">
                    <?php
                    $hours = floor($totalHoursToday);
                    $minutes = round(($totalHoursToday - $hours) * 60);
                    echo $hours . 'h ' . $minutes . 'm';
                    ?>
                </div>
                <div class="stat-label">Horas Trabajadas</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">
                    <?php echo $activeCheckpoint ? '⏱️ Activo' : '✅ Libre'; ?>
                </div>
                <div class="stat-label">Estado Actual</div>
            </div>
        </div>

        <!-- GPS Status -->
        <div id="gps-status" class="gps-status" style="display: none;">
            <i class="fas fa-circle-notch fa-spin"></i>
            <span id="gps-message">Obteniendo ubicación GPS...</span>
        </div>

        <!-- Main Grid -->
        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">

            <!-- Left Column: Actions -->
            <div>
                <div class="card">
                    <h3 style="margin-bottom: 1rem; color: var(--navy);">
                        <i class="fas fa-map-marker-alt" style="color: var(--gold);"></i>
                        Selecciona Ubicación
                    </h3>

                    <div id="locations-container" style="display: grid; gap: 0.75rem;">
                        <?php foreach ($locations as $loc): ?>
                        <div class="location-card" data-location-id="<?php echo $loc['id']; ?>"
                             data-lat="<?php echo $loc['latitud']; ?>"
                             data-lng="<?php echo $loc['longitud']; ?>"
                             data-radius="<?php echo $loc['radio_metros']; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <div style="font-weight: 600; color: var(--navy); margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($loc['nombre']); ?>
                                    </div>
                                    <div style="font-size: 0.875rem; color: var(--gray-600);">
                                        <?php echo htmlspecialchars($loc['direccion'] ?? 'Sin dirección'); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--gray-500); margin-top: 0.25rem;">
                                        <i class="fas fa-circle-notch"></i> Radio: <?php echo $loc['radio_metros']; ?>m
                                    </div>
                                </div>
                                <div class="badge badge-success" style="font-size: 0.75rem;">
                                    <?php echo ucfirst($loc['tipo']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
                        <?php if ($activeCheckpoint): ?>
                            <!-- Active Checkpoint Actions -->
                            <button class="action-button btn-checkout" id="btn-checkout">
                                <i class="fas fa-sign-out-alt"></i>
                                Hacer Check-Out
                            </button>

                            <button class="action-button btn-transfer" id="btn-transfer" style="margin-top: 0.75rem;">
                                <i class="fas fa-exchange-alt"></i>
                                Transferir a Otra Ubicación
                            </button>
                        <?php else: ?>
                            <!-- No Active Checkpoint -->
                            <button class="action-button btn-checkin" id="btn-checkin">
                                <i class="fas fa-fingerprint"></i>
                                Hacer Check-In
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Timeline -->
            <div>
                <div class="card">
                    <h3 style="margin-bottom: 1rem; color: var(--navy);">
                        <i class="fas fa-history" style="color: var(--gold);"></i>
                        Checkpoints de Hoy
                    </h3>

                    <?php if (count($todayCheckpoints) > 0): ?>
                        <div class="checkpoint-timeline">
                            <?php foreach ($todayCheckpoints as $index => $cp): ?>
                            <div class="checkpoint-item">
                                <div class="checkpoint-dot <?php echo $cp['is_active'] ? 'active' : 'completed'; ?>"></div>

                                <div class="checkpoint-card <?php echo $cp['is_active'] ? 'active' : 'completed'; ?>">
                                    <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 0.5rem;">
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; color: var(--navy); margin-bottom: 0.25rem;">
                                                Checkpoint #<?php echo $cp['checkpoint_sequence'] ?? ($index + 1); ?>
                                            </div>
                                            <div style="font-size: 0.875rem; color: var(--gray-600);">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($cp['ubicacion_nombre'] ?? 'Sin ubicación'); ?>
                                            </div>
                                        </div>
                                        <?php if ($cp['is_active']): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-clock"></i> Activo
                                        </span>
                                        <?php endif; ?>
                                    </div>

                                    <div style="display: flex; gap: 1rem; font-size: 0.875rem; margin-top: 0.75rem;">
                                        <div>
                                            <i class="fas fa-sign-in-alt" style="color: #059669;"></i>
                                            <?php echo $cp['hora_entrada'] ? date('h:i A', strtotime($cp['hora_entrada'])) : '--:--'; ?>
                                        </div>
                                        <?php if ($cp['hora_salida']): ?>
                                        <div>
                                            <i class="fas fa-sign-out-alt" style="color: #dc2626;"></i>
                                            <?php echo date('h:i A', strtotime($cp['hora_salida'])); ?>
                                        </div>
                                        <div style="margin-left: auto; font-weight: 600; color: var(--navy);">
                                            <?php
                                            $h = floor($cp['horas_trabajadas']);
                                            $m = round(($cp['horas_trabajadas'] - $h) * 60);
                                            echo $h . 'h ' . $m . 'm';
                                            ?>
                                        </div>
                                        <?php elseif ($cp['is_active']): ?>
                                        <div style="margin-left: auto; color: var(--green-600); font-weight: 600;">
                                            <i class="fas fa-spinner fa-pulse"></i> En curso
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 3rem 1rem; color: var(--gray-500);">
                            <i class="fas fa-calendar-check" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p>No hay checkpoints registrados hoy</p>
                            <p style="font-size: 0.875rem;">Selecciona una ubicación y haz check-in para comenzar</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Modal -->
    <div id="transfer-modal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 1rem; color: var(--navy);">
                <i class="fas fa-exchange-alt" style="color: var(--gold);"></i>
                Transferir Ubicación
            </h3>
            <p style="margin-bottom: 1.5rem; color: var(--gray-600);">
                Selecciona la nueva ubicación a la que te transferirás. Tu checkpoint actual se cerrará automáticamente.
            </p>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                    Motivo de transferencia (opcional)
                </label>
                <input type="text" id="transfer-reason" class="form-control" placeholder="Ej: Reunión con cliente, cambio de proyecto..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button class="btn btn-secondary" id="btn-cancel-transfer" style="flex: 1;">
                    Cancelar
                </button>
                <button class="btn btn-accent" id="btn-confirm-transfer" style="flex: 1;">
                    <i class="fas fa-check"></i> Confirmar Transferencia
                </button>
            </div>
        </div>
    </div>

    <script>
        let selectedLocationId = null;
        let selectedLocationData = null;
        let currentPosition = null;
        let gpsAccuracy = null;

        // Location selection
        document.querySelectorAll('.location-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.location-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedLocationId = this.dataset.locationId;
                selectedLocationData = {
                    id: this.dataset.locationId,
                    lat: parseFloat(this.dataset.lat),
                    lng: parseFloat(this.dataset.lng),
                    radius: parseInt(this.dataset.radius)
                };
            });
        });

        // Get GPS position
        function getGPSPosition() {
            return new Promise((resolve, reject) => {
                const statusEl = document.getElementById('gps-status');
                const messageEl = document.getElementById('gps-message');

                statusEl.style.display = 'flex';
                statusEl.className = 'gps-status poor';
                messageEl.textContent = 'Obteniendo ubicación GPS...';

                if (!navigator.geolocation) {
                    statusEl.className = 'gps-status bad';
                    messageEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> GPS no disponible en este dispositivo';
                    reject('GPS not available');
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        currentPosition = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        gpsAccuracy = position.coords.accuracy;

                        if (gpsAccuracy <= 20) {
                            statusEl.className = 'gps-status good';
                            messageEl.innerHTML = '<i class="fas fa-check-circle"></i> GPS excelente (±' + Math.round(gpsAccuracy) + 'm)';
                        } else if (gpsAccuracy <= 50) {
                            statusEl.className = 'gps-status good';
                            messageEl.innerHTML = '<i class="fas fa-check-circle"></i> GPS bueno (±' + Math.round(gpsAccuracy) + 'm)';
                        } else {
                            statusEl.className = 'gps-status poor';
                            messageEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> GPS poco preciso (±' + Math.round(gpsAccuracy) + 'm)';
                        }

                        resolve(position);
                    },
                    (error) => {
                        statusEl.className = 'gps-status bad';
                        messageEl.innerHTML = '<i class="fas fa-times-circle"></i> Error obteniendo GPS: ' + error.message;
                        reject(error);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            });
        }

        // Check-in button
        const btnCheckin = document.getElementById('btn-checkin');
        if (btnCheckin) {
            btnCheckin.addEventListener('click', async function() {
                if (!selectedLocationId) {
                    alert('Por favor selecciona una ubicación');
                    return;
                }

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Procesando...';

                try {
                    await getGPSPosition();

                    const response = await fetch('/asistencia/api/checkpoint.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'checkin',
                            location_id: selectedLocationId,
                            lat: currentPosition.lat,
                            lng: currentPosition.lng,
                            accuracy: gpsAccuracy
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('✅ ' + data.message);
                        window.location.reload();
                    } else {
                        alert('❌ ' + data.message);
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-fingerprint"></i> Hacer Check-In';
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-fingerprint"></i> Hacer Check-In';
                }
            });
        }

        // Check-out button
        const btnCheckout = document.getElementById('btn-checkout');
        if (btnCheckout) {
            btnCheckout.addEventListener('click', async function() {
                if (!confirm('¿Confirmas que deseas hacer check-out?')) {
                    return;
                }

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Procesando...';

                try {
                    await getGPSPosition();

                    const response = await fetch('/asistencia/api/checkpoint.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'checkout',
                            location_id: selectedLocationId,
                            lat: currentPosition.lat,
                            lng: currentPosition.lng,
                            accuracy: gpsAccuracy
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('✅ ' + data.message + '\nHoras trabajadas: ' + data.hours_worked + 'h\nTotal del día: ' + data.total_daily_hours + 'h');
                        window.location.reload();
                    } else {
                        alert('❌ ' + data.message);
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-sign-out-alt"></i> Hacer Check-Out';
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-sign-out-alt"></i> Hacer Check-Out';
                }
            });
        }

        // Transfer button
        const btnTransfer = document.getElementById('btn-transfer');
        const transferModal = document.getElementById('transfer-modal');
        const btnCancelTransfer = document.getElementById('btn-cancel-transfer');
        const btnConfirmTransfer = document.getElementById('btn-confirm-transfer');

        if (btnTransfer) {
            btnTransfer.addEventListener('click', function() {
                if (!selectedLocationId) {
                    alert('Por favor selecciona una ubicación de destino');
                    return;
                }
                transferModal.classList.add('active');
            });
        }

        if (btnCancelTransfer) {
            btnCancelTransfer.addEventListener('click', function() {
                transferModal.classList.remove('active');
            });
        }

        if (btnConfirmTransfer) {
            btnConfirmTransfer.addEventListener('click', async function() {
                if (!selectedLocationId) {
                    alert('Por favor selecciona una ubicación');
                    return;
                }

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Transfiriendo...';

                try {
                    await getGPSPosition();

                    const reason = document.getElementById('transfer-reason').value;

                    const response = await fetch('/asistencia/api/checkpoint.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'transfer',
                            location_id: selectedLocationId,
                            lat: currentPosition.lat,
                            lng: currentPosition.lng,
                            accuracy: gpsAccuracy,
                            reason: reason
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('✅ ' + data.message);
                        window.location.reload();
                    } else {
                        alert('❌ ' + data.message);
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-check"></i> Confirmar Transferencia';
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-check"></i> Confirmar Transferencia';
                }
            });
        }

        // Close modal on outside click
        transferModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    </script>
</body>
</html>
