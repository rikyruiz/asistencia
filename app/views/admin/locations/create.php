<div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Nueva Ubicación</h1>
                <p class="text-gray-600 mt-1">Registra una nueva ubicación de trabajo</p>
            </div>
            <a href="<?= url('admin/locations') ?>"
               class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver
            </a>
        </div>
    </div>

    <!-- Create Form -->
    <div class="bg-white rounded-xl shadow p-6">
        <form action="<?= url('admin/storeLocation') ?>" method="POST" id="createLocationForm">
            <?= csrfField() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="md:col-span-2">
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre de la Ubicación <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="nombre"
                           name="nombre"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                           placeholder="Ej: Oficina Principal">
                </div>

                <!-- Code -->
                <div>
                    <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">
                        Código
                    </label>
                    <input type="text"
                           id="codigo"
                           name="codigo"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                           placeholder="Ej: OF-001">
                </div>

                <!-- Type -->
                <div>
                    <label for="tipo_ubicacion" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Ubicación
                    </label>
                    <select id="tipo_ubicacion"
                            name="tipo_ubicacion"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        <option value="oficina" selected>Oficina</option>
                        <option value="almacen">Almacén</option>
                        <option value="bodega">Bodega</option>
                        <option value="cooler">Cooler</option>
                        <option value="tienda">Tienda</option>
                        <option value="fabrica">Fábrica</option>
                        <option value="sucursal">Sucursal</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="direccion" class="block text-sm font-medium text-gray-700 mb-2">
                        Dirección
                    </label>
                    <input type="text"
                           id="direccion"
                           name="direccion"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                           placeholder="Calle y número">
                </div>

                <!-- City -->
                <div>
                    <label for="ciudad" class="block text-sm font-medium text-gray-700 mb-2">
                        Ciudad
                    </label>
                    <input type="text"
                           id="ciudad"
                           name="ciudad"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>

                <!-- State -->
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                        Estado
                    </label>
                    <input type="text"
                           id="estado"
                           name="estado"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>

                <!-- Coordinates Section -->
                <div class="md:col-span-2">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <h3 class="text-sm font-medium text-blue-900 mb-2">
                            <i class="fas fa-map-marker-alt mr-2"></i>Obtener Coordenadas
                        </h3>
                        <p class="text-xs text-blue-700 mb-3">
                            Haz clic en el botón para usar tu ubicación actual o introduce las coordenadas manualmente
                        </p>
                        <button type="button"
                                onclick="getCurrentLocation()"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-crosshairs mr-2"></i>
                            Usar Mi Ubicación Actual
                        </button>
                        <span id="location-status" class="ml-3 text-sm"></span>
                    </div>
                </div>

                <!-- Latitude -->
                <div>
                    <label for="latitud" class="block text-sm font-medium text-gray-700 mb-2">
                        Latitud <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           id="latitud"
                           name="latitud"
                           step="any"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                           placeholder="19.4326">
                </div>

                <!-- Longitude -->
                <div>
                    <label for="longitud" class="block text-sm font-medium text-gray-700 mb-2">
                        Longitud <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           id="longitud"
                           name="longitud"
                           step="any"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                           placeholder="-99.1332">
                </div>

                <!-- Radius -->
                <div>
                    <label for="radio_metros" class="block text-sm font-medium text-gray-700 mb-2">
                        Radio de Geofence (metros)
                    </label>
                    <input type="number"
                           id="radio_metros"
                           name="radio_metros"
                           min="50"
                           max="500"
                           value="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Rango permitido: 50-500 metros</p>
                </div>

                <!-- Schedule -->
                <div>
                    <label for="horario_apertura" class="block text-sm font-medium text-gray-700 mb-2">
                        Hora de Apertura
                    </label>
                    <input type="time"
                           id="horario_apertura"
                           name="horario_apertura"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>

                <div>
                    <label for="horario_cierre" class="block text-sm font-medium text-gray-700 mb-2">
                        Hora de Cierre
                    </label>
                    <input type="time"
                           id="horario_cierre"
                           name="horario_cierre"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>

                <!-- Checkboxes -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox"
                               id="requiere_foto"
                               name="requiere_foto"
                               value="1"
                               class="rounded border-gray-300 text-navy focus:ring-navy">
                        <label for="requiere_foto" class="ml-2 text-sm text-gray-700">
                            Requiere foto al registrar entrada/salida
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox"
                               id="activa"
                               name="activa"
                               value="1"
                               checked
                               class="rounded border-gray-300 text-navy focus:ring-navy">
                        <label for="activa" class="ml-2 text-sm text-gray-700">
                            Ubicación activa
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="<?= url('admin/locations') ?>"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-navy text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Crear Ubicación
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Get current location
function getCurrentLocation() {
    const statusEl = document.getElementById('location-status');
    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin text-blue-600"></i> <span class="text-blue-600">Obteniendo ubicación...</span>';

    if (!navigator.geolocation) {
        statusEl.innerHTML = '<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>Geolocalización no soportada</span>';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById('latitud').value = position.coords.latitude.toFixed(6);
            document.getElementById('longitud').value = position.coords.longitude.toFixed(6);
            statusEl.innerHTML = '<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Ubicación obtenida correctamente</span>';

            setTimeout(() => {
                statusEl.innerHTML = '';
            }, 3000);
        },
        function(error) {
            let errorMsg = 'Error al obtener ubicación';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg = 'Permiso de ubicación denegado';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg = 'Ubicación no disponible';
                    break;
                case error.TIMEOUT:
                    errorMsg = 'Tiempo de espera agotado';
                    break;
            }
            statusEl.innerHTML = `<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>${errorMsg}</span>`;
        }
    );
}

// Form validation
document.getElementById('createLocationForm').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const latitud = document.getElementById('latitud').value;
    const longitud = document.getElementById('longitud').value;

    if (!nombre || !latitud || !longitud) {
        e.preventDefault();
        alert('Por favor completa los campos requeridos: Nombre, Latitud y Longitud');
        return false;
    }

    return true;
});
</script>
