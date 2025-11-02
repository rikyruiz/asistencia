<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Historial de Asistencia</h1>
        <p class="text-gray-600 mt-1">Consulta tu registro completo de entradas y salidas</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <form method="GET" action="<?= url('empleado/history') ?>" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha Inicio
                </label>
                <input type="date"
                       id="start_date"
                       name="start_date"
                       value="<?= htmlspecialchars($filters['start_date']) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
            </div>

            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha Fin
                </label>
                <input type="date"
                       id="end_date"
                       name="end_date"
                       value="<?= htmlspecialchars($filters['end_date']) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
            </div>

            <div class="flex items-end">
                <button type="submit"
                        class="w-full bg-navy text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Hours -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Horas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">
                        <?= $totals['hours'] ?>h <?= $totals['minutes'] ?>m
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Days -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Días Trabajados</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">
                        <?= $totals['days'] ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Entries -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Entradas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">
                        <?= $totals['entries'] ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sign-in-alt text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Exits -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Salidas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">
                        <?= $totals['exits'] ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sign-out-alt text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- History Timeline -->
    <div class="bg-white rounded-xl shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-history mr-2 text-gray-500"></i>
                Registro Detallado
            </h2>
        </div>

        <div class="p-6">
            <?php if (empty($history)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay registros</h3>
                    <p class="text-gray-500">No se encontraron registros de asistencia en el período seleccionado</p>
                </div>
            <?php else: ?>
                <!-- Timeline -->
                <div class="space-y-8">
                    <?php foreach ($history as $dayData): ?>
                        <div class="relative">
                            <!-- Date Header -->
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0 w-24">
                                    <div class="bg-navy text-white px-3 py-2 rounded-lg text-center">
                                        <div class="text-2xl font-bold">
                                            <?= date('d', strtotime($dayData['date'])) ?>
                                        </div>
                                        <div class="text-xs uppercase">
                                            <?= date('M', strtotime($dayData['date'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <?= $dayData['day_name'] ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        <?= formatDate($dayData['date']) ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Records for this day -->
                            <div class="ml-24 pl-8 border-l-2 border-gray-200 space-y-4">
                                <?php foreach ($dayData['records'] as $record): ?>
                                    <div class="relative">
                                        <!-- Timeline dot -->
                                        <div class="absolute -left-[37px] w-4 h-4 rounded-full <?= $record['tipo'] === 'entrada' ? 'bg-green-500' : 'bg-red-500' ?>"></div>

                                        <!-- Record Card -->
                                        <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center mb-2">
                                                        <?php if ($record['tipo'] === 'entrada'): ?>
                                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                                <i class="fas fa-arrow-right mr-2"></i>Entrada
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                                <i class="fas fa-arrow-left mr-2"></i>Salida
                                                            </span>
                                                        <?php endif; ?>

                                                        <span class="ml-3 text-lg font-semibold text-gray-900">
                                                            <?= formatDateTime($record['fecha_hora'], 'H:i') ?>
                                                        </span>
                                                    </div>

                                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                                        <!-- Location -->
                                                        <div class="flex items-center text-gray-600">
                                                            <i class="fas fa-map-marker-alt w-5 text-gray-400"></i>
                                                            <span class="ml-2">
                                                                <?= htmlspecialchars($record['ubicacion'] ?? 'Sin ubicación') ?>
                                                            </span>
                                                        </div>

                                                        <!-- Geofence Status -->
                                                        <div class="flex items-center">
                                                            <?php if ($record['dentro_geofence']): ?>
                                                                <i class="fas fa-check-circle w-5 text-green-500"></i>
                                                                <span class="ml-2 text-green-700">Dentro del área</span>
                                                            <?php else: ?>
                                                                <i class="fas fa-exclamation-circle w-5 text-orange-500"></i>
                                                                <span class="ml-2 text-orange-700">Fuera del área</span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <!-- Distance -->
                                                        <?php if ($record['distancia_metros']): ?>
                                                        <div class="flex items-center text-gray-600">
                                                            <i class="fas fa-ruler w-5 text-gray-400"></i>
                                                            <span class="ml-2">
                                                                <?= round($record['distancia_metros']) ?> metros
                                                            </span>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Duration (for exits) -->
                                                    <?php if ($record['tipo'] === 'salida' && $record['duracion_minutos']): ?>
                                                        <div class="mt-3 flex items-center">
                                                            <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                                                <i class="fas fa-hourglass-half mr-2"></i>
                                                                Duración:
                                                                <?php
                                                                    $hours = floor($record['duracion_minutos'] / 60);
                                                                    $mins = $record['duracion_minutos'] % 60;
                                                                    echo $hours > 0 ? "{$hours}h " : '';
                                                                    echo "{$mins}m";
                                                                ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Coordinates button -->
                                                <?php if ($record['latitud'] && $record['longitud']): ?>
                                                <div class="flex-shrink-0 ml-4">
                                                    <a href="https://www.google.com/maps?q=<?= $record['latitud'] ?>,<?= $record['longitud'] ?>"
                                                       target="_blank"
                                                       class="inline-flex items-center px-3 py-1 text-sm text-navy hover:text-primary-700 border border-navy hover:border-primary-700 rounded-lg transition-colors"
                                                       title="Ver en mapa">
                                                        <i class="fas fa-map-marked-alt mr-1"></i>
                                                        Mapa
                                                    </a>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Export Options (Future Enhancement) -->
    <div class="mt-6 flex justify-end">
        <button onclick="window.print()"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
            <i class="fas fa-print mr-2"></i>
            Imprimir
        </button>
    </div>
</div>

<style>
@media print {
    .sidebar, nav, button, .no-print {
        display: none !important;
    }

    .container {
        max-width: 100% !important;
    }
}
</style>
