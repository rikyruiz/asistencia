<?php
/**
 * Checkpoint Widget for Dashboard
 * Displays today's checkpoint summary and timeline
 * Include this in dashboard.php
 */

// Get today's checkpoints
$checkpointStmt = $db->prepare("
    SELECT
        ra.*,
        ub.nombre as ubicacion_nombre,
        ub.tipo as ubicacion_tipo
    FROM registros_asistencia ra
    LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
    WHERE ra.usuario_id = ? AND ra.fecha = CURDATE() AND ra.session_type = 'checkpoint'
    ORDER BY ra.checkpoint_sequence ASC
");
$checkpointStmt->execute([$currentUser['id']]);
$checkpoints = $checkpointStmt->fetchAll();

// Calculate total checkpoint hours
$totalCheckpointHours = 0;
$activeCheckpoint = null;
foreach ($checkpoints as $cp) {
    if ($cp['horas_trabajadas']) {
        $totalCheckpointHours += $cp['horas_trabajadas'];
    }
    if ($cp['is_active'] == 1 && !$cp['hora_salida']) {
        $activeCheckpoint = $cp;
    }
}

// Get location transfers today
$transferStmt = $db->prepare("
    SELECT
        lt.*,
        ub_from.nombre as from_location,
        ub_to.nombre as to_location
    FROM location_transfers lt
    LEFT JOIN ubicaciones ub_from ON lt.from_ubicacion_id = ub_from.id
    JOIN ubicaciones ub_to ON lt.to_ubicacion_id = ub_to.id
    WHERE lt.usuario_id = ? AND DATE(lt.transfer_time) = CURDATE()
    ORDER BY lt.transfer_time ASC
");
$transferStmt->execute([$currentUser['id']]);
$transfers = $transferStmt->fetchAll();
?>

<!-- Checkpoint Summary Widget -->
<div class="card" style="margin-top: 2rem;">
    <h3 style="margin-bottom: 1rem; color: var(--navy); display: flex; align-items: center; justify-content: space-between;">
        <span>
            <i class="fas fa-route" style="color: var(--gold);"></i> Checkpoints de Hoy
        </span>
        <?php if (count($checkpoints) > 0): ?>
        <a href="/asistencias_checkpoint.php" class="btn btn-sm" style="font-size: 0.875rem;">
            <i class="fas fa-fingerprint"></i> Ir a Control
        </a>
        <?php endif; ?>
    </h3>

    <?php if (count($checkpoints) > 0): ?>

        <!-- Summary Stats -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 12px;">
            <div style="text-align: center;">
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--navy);">
                    <?php echo count($checkpoints); ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--gray-600);">Checkpoints</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--navy);">
                    <?php
                    $h = floor($totalCheckpointHours);
                    $m = round(($totalCheckpointHours - $h) * 60);
                    echo $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
                    ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--gray-600);">Horas Totales</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--navy);">
                    <?php echo count($transfers); ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--gray-600);">Transferencias</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.5rem; font-weight: 700; color: <?php echo $activeCheckpoint ? 'var(--green-600)' : 'var(--gray-400)'; ?>;">
                    <?php echo $activeCheckpoint ? '⏱️' : '✓'; ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--gray-600);">
                    <?php echo $activeCheckpoint ? 'Activo' : 'Completo'; ?>
                </div>
            </div>
        </div>

        <!-- Checkpoint Timeline -->
        <div style="position: relative; padding-left: 2.5rem;">
            <!-- Timeline Line -->
            <div style="position: absolute; left: 0.75rem; top: 0; bottom: 0; width: 2px; background: linear-gradient(180deg, var(--gold) 0%, var(--gray-300) 100%);"></div>

            <?php foreach ($checkpoints as $index => $cp): ?>
            <div style="position: relative; margin-bottom: <?php echo $index < count($checkpoints) - 1 ? '1.5rem' : '0'; ?>;">
                <!-- Timeline Dot -->
                <div style="position: absolute; left: -1.8rem; top: 0.35rem; width: 0.875rem; height: 0.875rem; border-radius: 50%; background: white; border: 3px solid <?php echo $cp['is_active'] ? 'var(--green-600)' : 'var(--gold)'; ?>; <?php echo $cp['is_active'] ? 'box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);' : ''; ?>"></div>

                <!-- Checkpoint Card -->
                <div style="background: white; border: 1px solid var(--gray-200); border-left: 3px solid <?php echo $cp['is_active'] ? 'var(--green-600)' : 'var(--gold)'; ?>; border-radius: 8px; padding: 0.875rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: var(--navy); font-size: 0.9rem; margin-bottom: 0.25rem;">
                                #<?php echo $cp['checkpoint_sequence']; ?> - <?php echo htmlspecialchars($cp['ubicacion_nombre'] ?? 'Sin ubicación'); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--gray-600);">
                                <i class="fas fa-building" style="width: 14px;"></i>
                                <?php echo ucfirst($cp['ubicacion_tipo'] ?? 'oficina'); ?>
                            </div>
                        </div>
                        <?php if ($cp['is_active']): ?>
                        <span style="background: #dcfce7; color: #15803d; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600;">
                            <i class="fas fa-circle" style="font-size: 0.5rem; animation: pulse 2s infinite;"></i> EN CURSO
                        </span>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: 1.25rem; font-size: 0.8rem; color: var(--gray-700);">
                        <div>
                            <i class="fas fa-sign-in-alt" style="color: #059669; width: 14px;"></i>
                            <strong><?php echo $cp['hora_entrada'] ? date('H:i', strtotime($cp['hora_entrada'])) : '--:--'; ?></strong>
                        </div>

                        <?php if ($cp['hora_salida']): ?>
                        <div>
                            <i class="fas fa-sign-out-alt" style="color: #dc2626; width: 14px;"></i>
                            <strong><?php echo date('H:i', strtotime($cp['hora_salida'])); ?></strong>
                        </div>
                        <div style="margin-left: auto; font-weight: 700; color: var(--navy);">
                            <?php
                            $h = floor($cp['horas_trabajadas']);
                            $m = round(($cp['horas_trabajadas'] - $h) * 60);
                            echo $h . 'h ' . $m . 'm';
                            ?>
                        </div>
                        <?php elseif ($cp['is_active']): ?>
                        <div style="margin-left: auto; color: var(--green-600); font-weight: 600;">
                            <i class="fas fa-hourglass-half fa-spin"></i>
                            <span id="active-checkpoint-time-<?php echo $cp['id']; ?>">Calculando...</span>
                        </div>
                        <script>
                            // Real-time calculation for active checkpoint
                            (function() {
                                const startTime = new Date('<?php echo $cp['hora_entrada']; ?>');
                                const displayEl = document.getElementById('active-checkpoint-time-<?php echo $cp['id']; ?>');

                                function updateTime() {
                                    const now = new Date();
                                    const diff = now - startTime;
                                    const hours = Math.floor(diff / 3600000);
                                    const minutes = Math.floor((diff % 3600000) / 60000);
                                    displayEl.textContent = hours + 'h ' + minutes + 'm';
                                }

                                updateTime();
                                setInterval(updateTime, 60000); // Update every minute
                            })();
                        </script>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Transfers Summary -->
        <?php if (count($transfers) > 0): ?>
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
            <h4 style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.75rem;">
                <i class="fas fa-exchange-alt"></i> Transferencias Realizadas
            </h4>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach ($transfers as $transfer): ?>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; padding: 0.5rem; background: #fef3c7; border-radius: 6px;">
                    <i class="fas fa-arrow-right" style="color: var(--gold);"></i>
                    <span style="color: var(--gray-700);">
                        <?php echo date('H:i', strtotime($transfer['transfer_time'])); ?>
                    </span>
                    <span style="color: var(--navy); font-weight: 600;">
                        <?php echo htmlspecialchars($transfer['from_location'] ?? 'Inicio'); ?>
                    </span>
                    <i class="fas fa-long-arrow-alt-right" style="color: var(--gray-400);"></i>
                    <span style="color: var(--navy); font-weight: 600;">
                        <?php echo htmlspecialchars($transfer['to_location']); ?>
                    </span>
                    <?php if ($transfer['transfer_reason']): ?>
                    <span style="margin-left: auto; color: var(--gray-500); font-style: italic; font-size: 0.75rem;">
                        "<?php echo htmlspecialchars($transfer['transfer_reason']); ?>"
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- No Checkpoints Today -->
        <div style="text-align: center; padding: 2rem 1rem; color: var(--gray-500);">
            <i class="fas fa-calendar-day" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.3;"></i>
            <p style="margin-bottom: 0.5rem;">No hay checkpoints registrados hoy</p>
            <a href="/asistencias_checkpoint.php" class="btn btn-accent" style="margin-top: 1rem; text-decoration: none; display: inline-block;">
                <i class="fas fa-fingerprint"></i> Hacer Check-In
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
