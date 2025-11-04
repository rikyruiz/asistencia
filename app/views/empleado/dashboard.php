<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Welcome Section with Live Clock -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Bienvenido, <?= htmlspecialchars($user['nombre']) ?>
                </h1>
                <p class="text-gray-600 mt-1">
                    <?= getDayNameSpanish(getCurrentDate()) ?>, <?= date('d') ?> de <?= getMonthNameSpanish(date('n')) ?> de <?= date('Y') ?>
                </p>
            </div>
            <!-- Live Clock -->
            <div class="mt-4 md:mt-0">
                <div class="bg-navy text-white rounded-lg px-6 py-4 shadow-lg">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-clock text-gold text-2xl"></i>
                        <div>
                            <div class="text-3xl font-bold font-mono" id="live-time"><?= date('H:i:s') ?></div>
                            <div class="text-xs text-white/70 uppercase tracking-wide">Hora Actual</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Session Alert -->
    <?php if ($activeSession): ?>
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-clock text-green-400 text-xl"></i>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm text-green-800">
                    <strong>Sesión Activa</strong> - Entrada registrada a las <?= formatDateTime($activeSession['hora_entrada'], 'H:i') ?>
                    <?php if ($activeSession['ubicacion_nombre']): ?>
                        en <?= htmlspecialchars($activeSession['ubicacion_nombre']) ?>
                    <?php endif; ?>
                </p>
                <p class="text-xs text-green-600 mt-1">
                    Tiempo trabajado: <span id="session-timer" data-start="<?= $activeSession['hora_entrada'] ?>">calculando...</span>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Clock In/Out Button -->
    <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <div class="text-center">
            <?php if ($activeSession): ?>
                <!-- Clock Out -->
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-32 h-32 bg-red-100 rounded-full mb-4">
                        <i class="fas fa-sign-out-alt text-red-600 text-5xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Registrar Salida</h2>
                    <p class="text-gray-600 mb-6">Finaliza tu jornada laboral</p>
                </div>
                <a href="<?= url('empleado/clock') ?>" class="inline-flex items-center justify-center px-8 py-4 bg-red-600 text-white text-lg font-semibold rounded-lg hover:bg-red-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                    <i class="fas fa-stop-circle mr-3"></i>
                    Registrar Salida
                </a>
            <?php else: ?>
                <!-- Clock In -->
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-32 h-32 bg-green-100 rounded-full mb-4 pulse-animation">
                        <i class="fas fa-sign-in-alt text-green-600 text-5xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Registrar Entrada</h2>
                    <p class="text-gray-600 mb-6">Inicia tu jornada laboral</p>
                </div>
                <a href="<?= url('empleado/clock') ?>" class="inline-flex items-center justify-center px-8 py-4 bg-green-600 text-white text-lg font-semibold rounded-lg hover:bg-green-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                    <i class="fas fa-play-circle mr-3"></i>
                    Registrar Entrada
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Hours This Week -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-hourglass-half text-blue-600 text-xl"></i>
                </div>
                <span class="text-sm text-gray-500">Esta Semana</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900"><?= $weekStats['total_hours'] ?>h</h3>
            <p class="text-sm text-gray-600 mt-1">Horas trabajadas</p>
        </div>

        <!-- Days Worked -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                </div>
                <span class="text-sm text-gray-500">Esta Semana</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900"><?= $weekStats['total_days'] ?></h3>
            <p class="text-sm text-gray-600 mt-1">Días trabajados</p>
        </div>

        <!-- On Time Percentage -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
                </div>
                <span class="text-sm text-gray-500">Puntualidad</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900"><?= $weekStats['attendance_rate'] ?>%</h3>
            <p class="text-sm text-gray-600 mt-1">A tiempo</p>
        </div>

        <!-- Status -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-info-circle text-purple-600 text-xl"></i>
                </div>
                <span class="text-sm text-gray-500">Estado</span>
            </div>
            <h3 class="text-lg font-bold <?= $activeSession ? 'text-green-600' : 'text-gray-600' ?>">
                <?= $activeSession ? 'Trabajando' : 'Fuera de Turno' ?>
            </h3>
            <p class="text-sm text-gray-600 mt-1">
                <?= $activeSession ? 'Desde ' . formatDateTime($activeSession['hora_entrada'], 'H:i') : 'Sin sesión activa' ?>
            </p>
        </div>
    </div>

    <!-- Recent History -->
    <div class="bg-white rounded-xl shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-history mr-2 text-gray-500"></i>
                    Historial Reciente
                </h2>
                <a href="<?= url('empleado/history') ?>" class="text-sm text-navy hover:text-primary-700">
                    Ver todo <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fecha
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Hora
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ubicación
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Duración
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                            <p>No hay registros recientes</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($history, 0, 10) as $record): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= formatDate($record['fecha_hora']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($record['tipo'] === 'entrada'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-arrow-right mr-1"></i> Entrada
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-arrow-left mr-1"></i> Salida
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= formatDateTime($record['fecha_hora'], 'H:i') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?= htmlspecialchars($record['ubicacion_nombre'] ?? 'Sin ubicación') ?>
                                <?php if (!$record['dentro_geofence']): ?>
                                    <span class="text-xs text-orange-600 ml-1">(Fuera)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php if ($record['duracion_minutos']): ?>
                                    <?= sprintf("%02d:%02d", floor($record['duracion_minutos'] / 60), $record['duracion_minutos'] % 60) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Update live clock
function updateLiveClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    const timeElement = document.getElementById('live-time');
    if (timeElement) {
        timeElement.textContent = `${hours}:${minutes}:${seconds}`;
    }
}

// Update clock every second
updateLiveClock();
setInterval(updateLiveClock, 1000);

// Update session timer
function updateSessionTimer() {
    const timerElement = document.getElementById('session-timer');
    if (!timerElement) return;

    const startTime = new Date(timerElement.dataset.start);
    const now = new Date();
    const diff = now - startTime;

    const hours = Math.floor(diff / 3600000);
    const minutes = Math.floor((diff % 3600000) / 60000);

    timerElement.textContent = `${hours}h ${minutes}m`;
}

// Update timer every minute
if (document.getElementById('session-timer')) {
    updateSessionTimer();
    setInterval(updateSessionTimer, 60000);
}
</script>