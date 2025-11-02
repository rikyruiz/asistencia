<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard Administrativo</h1>
        <p class="text-gray-600 mt-1">
            Resumen de actividad del <?= formatDate(getCurrentDate()) ?>
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Active Employees -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Empleados Activos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">
                        <?= $todayStats['sesiones_activas'] ?? 0 ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Trabajando ahora</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Today's Entries -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Entradas Hoy</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">
                        <?= $todayStats['total_entradas'] ?? 0 ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Total registros</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sign-in-alt text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Today's Exits -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Salidas Hoy</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">
                        <?= $todayStats['total_salidas'] ?? 0 ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Total registros</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sign-out-alt text-red-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Outside Geofence -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Fuera de Área</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">
                        <?= $todayStats['fuera_geofence'] ?? 0 ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Registros externos</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Active Sessions -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-clock mr-2 text-gray-500"></i>
                        Sesiones Activas
                    </h2>
                    <span class="text-sm text-gray-500">
                        <?= count($activeSessions) ?> empleados trabajando
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Empleado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ubicación
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Entrada
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tiempo
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($activeSessions)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-user-clock text-3xl text-gray-300 mb-2"></i>
                                <p>No hay empleados activos en este momento</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($activeSessions as $session): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($session['nombre'] . ' ' . $session['apellidos']) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($session['numero_empleado']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= htmlspecialchars($session['ubicacion'] ?? 'Sin ubicación') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= formatDateTime($session['hora_entrada'], 'H:i') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-circle text-green-400 mr-1 text-xs animate-pulse"></i>
                                        <?= $session['tiempo_trabajado'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Location Statistics -->
        <div class="bg-white rounded-xl shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-map-marker-alt mr-2 text-gray-500"></i>
                    Por Ubicación
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <?php if (empty($locationStats)): ?>
                    <p class="text-gray-500 text-center">No hay ubicaciones activas</p>
                <?php else: ?>
                    <?php foreach ($locationStats as $stat): ?>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($stat['name']) ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?= $stat['active'] ?> de <?= $stat['total'] ?> empleados
                            </p>
                        </div>
                        <div class="flex items-center">
                            <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                                <div class="bg-navy h-2 rounded-full"
                                     style="width: <?= $stat['total'] > 0 ? ($stat['active'] / $stat['total'] * 100) : 0 ?>%">
                                </div>
                            </div>
                            <span class="text-sm font-medium text-gray-700">
                                <?= $stat['total'] > 0 ? round($stat['active'] / $stat['total'] * 100) : 0 ?>%
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Weekly Chart -->
    <div class="bg-white rounded-xl shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-chart-bar mr-2 text-gray-500"></i>
                Asistencia Semanal
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-7 gap-2">
                <?php foreach ($weekStats as $day): ?>
                <div class="text-center">
                    <div class="text-xs text-gray-500 mb-2"><?= substr($day['day'], 0, 3) ?></div>
                    <div class="relative bg-gray-100 rounded" style="height: 120px;">
                        <div class="absolute bottom-0 w-full bg-navy rounded transition-all duration-300"
                             style="height: <?= $day['employees'] > 0 ? min(100, $day['employees'] * 5) : 0 ?>%">
                        </div>
                    </div>
                    <div class="text-sm font-semibold text-gray-900 mt-2"><?= $day['employees'] ?></div>
                    <div class="text-xs text-gray-500"><?= date('d/m', strtotime($day['date'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-history mr-2 text-gray-500"></i>
                    Actividad Reciente
                </h2>
                <a href="<?= url('admin/reports') ?>" class="text-sm text-navy hover:text-primary-700">
                    Ver reportes <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Hora
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Empleado
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ubicación
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($recentActivities)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No hay actividad reciente
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($recentActivities, 0, 10) as $activity): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= formatDateTime($activity['fecha_hora'], 'H:i') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($activity['nombre'] . ' ' . $activity['apellidos']) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= htmlspecialchars($activity['numero_empleado']) ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($activity['tipo'] === 'entrada'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-arrow-right mr-1"></i> Entrada
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-arrow-left mr-1"></i> Salida
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?= htmlspecialchars($activity['ubicacion'] ?? 'Sin ubicación') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($activity['dentro_geofence']): ?>
                                    <span class="text-green-600 text-sm">
                                        <i class="fas fa-check-circle"></i> Dentro
                                    </span>
                                <?php else: ?>
                                    <span class="text-orange-600 text-sm">
                                        <i class="fas fa-exclamation-circle"></i> Fuera
                                    </span>
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
// Auto-refresh dashboard every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);
</script>