<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gestión de Ubicaciones</h1>
            <p class="text-gray-600 mt-1">Administra las ubicaciones de trabajo autorizadas</p>
        </div>
        <a href="<?= url('admin/createLocation') ?>"
           class="inline-flex items-center px-4 py-2 bg-navy text-white rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>
            Nueva Ubicación
        </a>
    </div>

    <!-- Locations Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($locations as $location): ?>
        <div class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?= htmlspecialchars($location['nombre']) ?>
                        </h3>
                        <?php if ($location['codigo']): ?>
                        <p class="text-sm text-gray-500 mt-1">
                            <span class="font-mono"><?= htmlspecialchars($location['codigo']) ?></span>
                        </p>
                        <?php endif; ?>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                          <?= $location['activa'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $location['activa'] ? 'Activa' : 'Inactiva' ?>
                    </span>
                </div>
            </div>

            <!-- Details -->
            <div class="p-6 space-y-3">
                <!-- Address -->
                <?php if ($location['direccion']): ?>
                <div class="flex items-start">
                    <i class="fas fa-map-marker-alt text-gray-400 mt-1 mr-3 text-sm"></i>
                    <div class="text-sm text-gray-600">
                        <?= htmlspecialchars($location['direccion']) ?>
                        <?php if ($location['ciudad'] || $location['estado']): ?>
                            <br><?= htmlspecialchars($location['ciudad'] ?? '') ?>
                            <?= $location['ciudad'] && $location['estado'] ? ', ' : '' ?>
                            <?= htmlspecialchars($location['estado'] ?? '') ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Geofence -->
                <div class="flex items-center">
                    <i class="fas fa-circle-notch text-gray-400 mr-3 text-sm"></i>
                    <div class="text-sm text-gray-600">
                        Radio: <span class="font-medium"><?= $location['radio_metros'] ?>m</span>
                    </div>
                </div>

                <!-- Schedule -->
                <?php if ($location['horario_apertura'] && $location['horario_cierre']): ?>
                <div class="flex items-center">
                    <i class="fas fa-clock text-gray-400 mr-3 text-sm"></i>
                    <div class="text-sm text-gray-600">
                        <?= substr($location['horario_apertura'], 0, 5) ?> -
                        <?= substr($location['horario_cierre'], 0, 5) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Type -->
                <div class="flex items-center">
                    <i class="fas fa-building text-gray-400 mr-3 text-sm"></i>
                    <div class="text-sm text-gray-600">
                        <span class="capitalize"><?= $location['tipo_ubicacion'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="bg-gray-50 px-6 py-3 rounded-b-xl">
                <div class="flex justify-between text-sm">
                    <div class="text-center">
                        <p class="font-semibold text-gray-900"><?= $location['active_employees'] ?></p>
                        <p class="text-xs text-gray-500">Activos</p>
                    </div>
                    <div class="text-center">
                        <p class="font-semibold text-gray-900"><?= $location['assigned_employees'] ?></p>
                        <p class="text-xs text-gray-500">Asignados</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="<?= url('admin/editLocation/' . $location['id']) ?>"
                           class="text-blue-600 hover:text-blue-800"
                           title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="viewLocationDetails(<?= $location['id'] ?>)"
                                class="text-gray-600 hover:text-gray-800"
                                title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="deleteLocation(<?= $location['id'] ?>, '<?= htmlspecialchars($location['nombre']) ?>')"
                                class="text-red-600 hover:text-red-800"
                                title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($locations)): ?>
        <div class="col-span-full">
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <i class="fas fa-map-marked-alt text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay ubicaciones registradas</h3>
                <p class="text-gray-500 mb-6">Comienza agregando la primera ubicación de trabajo</p>
                <a href="<?= url('admin/createLocation') ?>"
                   class="inline-flex items-center px-4 py-2 bg-navy text-white rounded-lg hover:bg-primary-700">
                    <i class="fas fa-plus mr-2"></i>
                    Agregar Primera Ubicación
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Location Details Modal -->
<div id="locationModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-semibold text-gray-900" id="modalTitle">Detalles de Ubicación</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="modalContent" class="space-y-4">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewLocationDetails(id) {
    // Show modal
    document.getElementById('locationModal').classList.remove('hidden');
    document.getElementById('modalContent').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>';

    // Fetch location details
    fetch(`<?= url('admin/getLocation/') ?>${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            closeModal();
            return;
        }

        // Build content
        let content = `
            <div class="space-y-4">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Información General</h4>
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500">Nombre</dt>
                            <dd class="text-sm font-medium text-gray-900">${data.nombre}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Código</dt>
                            <dd class="text-sm font-medium text-gray-900">${data.codigo || 'N/A'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Tipo</dt>
                            <dd class="text-sm font-medium text-gray-900 capitalize">${data.tipo_ubicacion}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Estado</dt>
                            <dd class="text-sm font-medium ${data.activa ? 'text-green-600' : 'text-red-600'}">
                                ${data.activa ? 'Activa' : 'Inactiva'}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Ubicación</h4>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm text-gray-500">Dirección</dt>
                            <dd class="text-sm text-gray-900">${data.direccion || 'No especificada'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Coordenadas</dt>
                            <dd class="text-sm font-mono text-gray-900">${data.latitud}, ${data.longitud}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Radio de geofence</dt>
                            <dd class="text-sm text-gray-900">${data.radio_metros} metros</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Empleados Activos</h4>
                    ${data.active_employees && data.active_employees.length > 0 ? `
                        <div class="bg-gray-50 rounded-lg p-3 space-y-2 max-h-40 overflow-y-auto">
                            ${data.active_employees.map(emp => `
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-900">${emp.nombre} ${emp.apellidos}</span>
                                    <span class="text-xs text-gray-500">${Math.floor(emp.minutos_trabajados / 60)}h ${emp.minutos_trabajados % 60}m</span>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p class="text-sm text-gray-500">No hay empleados activos en este momento</p>'}
                </div>

                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Estadísticas del Mes</h4>
                    ${data.statistics ? `
                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-gray-500">Empleados únicos</dt>
                                <dd class="text-sm font-medium text-gray-900">${data.statistics.empleados_unicos || 0}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Total entradas</dt>
                                <dd class="text-sm font-medium text-gray-900">${data.statistics.total_entradas || 0}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Total salidas</dt>
                                <dd class="text-sm font-medium text-gray-900">${data.statistics.total_salidas || 0}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Registros fuera de área</dt>
                                <dd class="text-sm font-medium text-gray-900">${data.statistics.fuera_geofence || 0}</dd>
                            </div>
                        </dl>
                    ` : '<p class="text-sm text-gray-500">No hay estadísticas disponibles</p>'}
                </div>
            </div>
        `;

        document.getElementById('modalContent').innerHTML = content;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalContent').innerHTML = '<p class="text-red-600">Error al cargar los detalles</p>';
    });
}

function closeModal() {
    document.getElementById('locationModal').classList.add('hidden');
}

function deleteLocation(id, name) {
    if (!confirm(`¿Estás seguro de que deseas eliminar la ubicación "${name}"?`)) {
        return;
    }

    fetch(`<?= url('admin/deleteLocation/') ?>${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            <?= CSRF_TOKEN_NAME ?>: '<?= $csrf_token ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Error al eliminar la ubicación');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>