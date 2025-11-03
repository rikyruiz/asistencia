<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Reportes de Asistencia</h1>
        <p class="text-gray-600 mt-1">Genera y consulta reportes del sistema</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h2>
        <form method="GET" action="<?= url('admin/reports') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Date Range -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha Inicio
                </label>
                <input type="date"
                       id="start_date"
                       name="start_date"
                       value="<?= htmlspecialchars($startDate) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
            </div>

            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha Fin
                </label>
                <input type="date"
                       id="end_date"
                       name="end_date"
                       value="<?= htmlspecialchars($endDate) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
            </div>

            <!-- User Filter -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Usuario
                </label>
                <select id="user_id" name="user_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                    <option value="">Todos los usuarios</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= $filters['user_id'] == $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Location Filter -->
            <div>
                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Ubicación
                </label>
                <select id="location_id" name="location_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                    <option value="">Todas las ubicaciones</option>
                    <?php foreach ($locations as $location): ?>
                    <option value="<?= $location['id'] ?>" <?= $filters['location_id'] == $location['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($location['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit Buttons -->
            <div class="md:col-span-4 flex justify-end space-x-3">
                <a href="<?= url('admin/reports') ?>"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Limpiar Filtros
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-navy text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Generar Reporte
                </button>
            </div>
        </form>
    </div>

    <!-- Report Types -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <!-- Attendance Report -->
        <div class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow cursor-pointer"
             onclick="generateReport('attendance')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Reporte de Asistencia</h3>
                <p class="text-sm text-gray-600 mb-4">Listado detallado de entradas y salidas por empleado</p>
                <button onclick="generateReport('attendance')"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-file-download mr-2"></i>Generar
                </button>
            </div>
        </div>

        <!-- Summary Report -->
        <div class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow cursor-pointer"
             onclick="generateReport('summary')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-bar text-green-600 text-xl"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Reporte Resumen</h3>
                <p class="text-sm text-gray-600 mb-4">Totales de horas trabajadas por empleado</p>
                <button onclick="generateReport('summary')"
                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-file-download mr-2"></i>Generar
                </button>
            </div>
        </div>

        <!-- Location Report -->
        <div class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow cursor-pointer"
             onclick="generateReport('location')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-purple-600 text-xl"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Reporte por Ubicación</h3>
                <p class="text-sm text-gray-600 mb-4">Estadísticas de asistencia por ubicación</p>
                <button onclick="generateReport('location')"
                        class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-file-download mr-2"></i>Generar
                </button>
            </div>
        </div>

        <!-- Incomplete Sessions Report (Missing Clock-Out) -->
        <div class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow cursor-pointer border-2 border-orange-200"
             onclick="generateReport('incomplete')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-orange-600 text-xl"></i>
                    </div>
                    <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full">KPI</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Salidas Faltantes</h3>
                <p class="text-sm text-gray-600 mb-4">Empleados que olvidaron registrar salida (sesiones incompletas)</p>
                <button onclick="generateReport('incomplete')"
                        class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    <i class="fas fa-file-download mr-2"></i>Generar
                </button>
            </div>
        </div>

        <!-- Geofence Violations Report -->
        <div class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow cursor-pointer border-2 border-red-200"
             onclick="generateReport('violations')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-map-marked-alt text-red-600 text-xl"></i>
                    </div>
                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">KPI</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Violaciones de Geovalla</h3>
                <p class="text-sm text-gray-600 mb-4">Salidas registradas fuera de ubicaciones autorizadas</p>
                <button onclick="generateReport('violations')"
                        class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-file-download mr-2"></i>Generar
                </button>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-download mr-2 text-navy"></i>Opciones de Exportación
        </h2>
        <p class="text-sm text-gray-600 mb-4">
            Selecciona un tipo de reporte y haz clic en Generar. Los reportes estarán disponibles en los siguientes formatos:
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                <i class="fas fa-file-pdf text-red-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-medium text-gray-900">PDF</p>
                    <p class="text-xs text-gray-500">Formato de impresión</p>
                </div>
            </div>
            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                <i class="fas fa-file-excel text-green-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-medium text-gray-900">Excel</p>
                    <p class="text-xs text-gray-500">Formato editable</p>
                </div>
            </div>
            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                <i class="fas fa-file-csv text-blue-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-medium text-gray-900">CSV</p>
                    <p class="text-xs text-gray-500">Datos sin formato</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Format Modal -->
<div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-download mr-2 text-navy"></i>
                    Exportar Reporte
                </h3>
                <button onclick="closeExportModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">
                Selecciona el formato en el que deseas exportar el reporte:
            </p>
            <div class="space-y-3">
                <!-- PDF -->
                <button onclick="exportReport('pdf')"
                        class="w-full flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-red-500 hover:bg-red-50 transition-colors group">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-red-200">
                        <i class="fas fa-file-pdf text-red-600 text-2xl"></i>
                    </div>
                    <div class="flex-1 text-left">
                        <p class="font-semibold text-gray-900">Exportar como PDF</p>
                        <p class="text-xs text-gray-500">Formato de impresión profesional</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400 group-hover:text-red-600"></i>
                </button>

                <!-- Excel -->
                <button onclick="exportReport('excel')"
                        class="w-full flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors group">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-green-200">
                        <i class="fas fa-file-excel text-green-600 text-2xl"></i>
                    </div>
                    <div class="flex-1 text-left">
                        <p class="font-semibold text-gray-900">Exportar como Excel</p>
                        <p class="text-xs text-gray-500">Formato editable con fórmulas</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400 group-hover:text-green-600"></i>
                </button>

                <!-- CSV -->
                <button onclick="exportReport('csv')"
                        class="w-full flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors group">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-blue-200">
                        <i class="fas fa-file-csv text-blue-600 text-2xl"></i>
                    </div>
                    <div class="flex-1 text-left">
                        <p class="font-semibold text-gray-900">Exportar como CSV</p>
                        <p class="text-xs text-gray-500">Datos sin formato para análisis</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400 group-hover:text-blue-600"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportType = null;

function generateReport(type) {
    currentReportType = type;
    document.getElementById('exportModal').classList.remove('hidden');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.add('hidden');
    currentReportType = null;
}

function exportReport(format) {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const userId = document.getElementById('user_id').value;
    const locationId = document.getElementById('location_id').value;

    if (!startDate || !endDate) {
        alert('Por favor selecciona las fechas de inicio y fin');
        return;
    }

    // Build query string
    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate,
        type: currentReportType,
        format: format
    });

    if (userId) params.append('user_id', userId);
    if (locationId) params.append('location_id', locationId);

    // Redirect to generate report
    window.location.href = `<?= url('admin/generateReport') ?>?${params.toString()}`;

    // Close modal
    closeExportModal();
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeExportModal();
    }
});

// Close modal when clicking outside
document.getElementById('exportModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeExportModal();
    }
});
</script>
