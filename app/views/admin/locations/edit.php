<div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Editar Ubicación</h1>
                <p class="text-gray-600 mt-1">Modifica los datos de la ubicación</p>
            </div>
            <a href="<?= url('admin/locations') ?>"
               class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver
            </a>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white rounded-xl shadow p-6">
        <form action="<?= url('admin/updateLocation/' . $location['id']) ?>" method="POST" id="editLocationForm">
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
                           value="<?= htmlspecialchars($location['nombre']) ?>"
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
                           value="<?= htmlspecialchars($location['codigo']) ?>"
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
                        <option value="oficina" <?= $location['tipo_ubicacion'] === 'oficina' ? 'selected' : '' ?>>Oficina</option>
                        <option value="almacen" <?= $location['tipo_ubicacion'] === 'almacen' ? 'selected' : '' ?>>Almacén</option>
                        <option value="bodega" <?= $location['tipo_ubicacion'] === 'bodega' ? 'selected' : '' ?>>Bodega</option>
                        <option value="cooler" <?= $location['tipo_ubicacion'] === 'cooler' ? 'selected' : '' ?>>Cooler</option>
                        <option value="tienda" <?= $location['tipo_ubicacion'] === 'tienda' ? 'selected' : '' ?>>Tienda</option>
                        <option value="fabrica" <?= $location['tipo_ubicacion'] === 'fabrica' ? 'selected' : '' ?>>Fábrica</option>
                        <option value="sucursal" <?= $location['tipo_ubicacion'] === 'sucursal' ? 'selected' : '' ?>>Sucursal</option>
                        <option value="otro" <?= $location['tipo_ubicacion'] === 'otro' ? 'selected' : '' ?>>Otro</option>
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
                           value="<?= htmlspecialchars($location['direccion']) ?>"
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
                           value="<?= htmlspecialchars($location['ciudad']) ?>"
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
                           value="<?= htmlspecialchars($location['estado']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>

                <!-- Coordinates -->
                <div>
                    <label for="latitud" class="block text-sm font-medium text-gray-700 mb-2">
                        Latitud <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           id="latitud"
                           name="latitud"
                           step="any"
                           required
                           value="<?= $location['latitud'] ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                           placeholder="19.4326">
                </div>

                <div>
                    <label for="longitud" class="block text-sm font-medium text-gray-700 mb-2">
                        Longitud <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           id="longitud"
                           name="longitud"
                           step="any"
                           required
                           value="<?= $location['longitud'] ?>"
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
                           value="<?= $location['radio_metros'] ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>

                <!-- Hours -->
                <div>
                    <label for="horario_apertura" class="block text-sm font-medium text-gray-700 mb-2">
                        Hora de Apertura
                    </label>
                    <input type="time"
                           id="horario_apertura"
                           name="horario_apertura"
                           value="<?= $location['horario_apertura'] ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>

                <div>
                    <label for="horario_cierre" class="block text-sm font-medium text-gray-700 mb-2">
                        Hora de Cierre
                    </label>
                    <input type="time"
                           id="horario_cierre"
                           name="horario_cierre"
                           value="<?= $location['horario_cierre'] ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>

                <!-- Checkboxes -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox"
                               id="requiere_foto"
                               name="requiere_foto"
                               value="1"
                               <?= $location['requiere_foto'] ? 'checked' : '' ?>
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
                               <?= $location['activa'] ? 'checked' : '' ?>
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
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <!-- Assigned Users -->
    <?php if (!empty($assignedUsers)): ?>
    <div class="bg-white rounded-xl shadow p-6 mt-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-users mr-2"></i>
            Empleados Asignados (<?= count($assignedUsers) ?>)
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($assignedUsers as $user): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']) ?></p>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($user['numero_empleado']) ?></p>
                </div>
                <?php if ($user['es_principal']): ?>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Principal
                </span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Form validation before submit
document.getElementById('editLocationForm').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const latitud = document.getElementById('latitud').value;
    const longitud = document.getElementById('longitud').value;

    if (!nombre || !latitud || !longitud) {
        e.preventDefault();
        alert('Por favor completa los campos requeridos: Nombre, Latitud y Longitud');
        return false;
    }

    // Allow normal form submission
    return true;
});
</script>
