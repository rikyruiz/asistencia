<div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Nuevo Usuario</h1>
                <p class="text-gray-600 mt-1">Registra un nuevo usuario en el sistema</p>
            </div>
            <a href="<?= url('admin/users') ?>"
               class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver
            </a>
        </div>
    </div>

    <!-- Create Form -->
    <div class="bg-white rounded-xl shadow p-6">
        <form action="<?= url('admin/storeUser') ?>" method="POST" id="createUserForm">
            <?= csrfField() ?>

            <!-- Personal Information -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Personal</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Name -->
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre(s) <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="nombre"
                               name="nombre"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                               placeholder="Juan">
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label for="apellidos" class="block text-sm font-medium text-gray-700 mb-2">
                            Apellidos <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="apellidos"
                               name="apellidos"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                               placeholder="Pérez García">
                    </div>

                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre de Usuario <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="username"
                               name="username"
                               required
                               pattern="[a-zA-Z0-9_]{3,20}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                               placeholder="jperez">
                        <p class="text-xs text-gray-500 mt-1">3-20 caracteres, solo letras, números y guión bajo</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                               placeholder="juan.perez@alpefresh.app">
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                            Teléfono
                        </label>
                        <input type="tel"
                               id="telefono"
                               name="telefono"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                               placeholder="5551234567">
                    </div>

                    <!-- Department -->
                    <div>
                        <label for="departamento" class="block text-sm font-medium text-gray-700 mb-2">
                            Departamento
                        </label>
                        <input type="text"
                               id="departamento"
                               name="departamento"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                               placeholder="Recursos Humanos">
                    </div>
                </div>
            </div>

            <!-- Account Settings -->
            <div class="mb-6 border-t pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Configuración de Cuenta</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Role -->
                    <div>
                        <label for="rol" class="block text-sm font-medium text-gray-700 mb-2">
                            Rol <span class="text-red-500">*</span>
                        </label>
                        <select id="rol"
                                name="rol"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                            <option value="empleado" selected>Empleado</option>
                            <option value="inspector">Inspector</option>
                            <option value="admin">Administrador</option>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin'): ?>
                            <option value="superadmin">Superadmin</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Auto-assigned Employee Number Display -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Número de Empleado
                        </label>
                        <div class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i>Se asignará automáticamente
                        </div>
                        <p class="text-xs text-gray-500 mt-1">El sistema generará el siguiente número disponible</p>
                    </div>
                </div>

                <!-- Status Checkboxes -->
                <div class="mt-4 space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox"
                               id="activo"
                               name="activo"
                               value="1"
                               checked
                               class="rounded border-gray-300 text-navy focus:ring-navy">
                        <label for="activo" class="ml-2 text-sm text-gray-700">
                            Usuario activo
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox"
                               id="email_verificado"
                               name="email_verificado"
                               value="1"
                               class="rounded border-gray-300 text-navy focus:ring-navy">
                        <label for="email_verificado" class="ml-2 text-sm text-gray-700">
                            Email verificado (marcar solo si se verifica manualmente)
                        </label>
                    </div>
                </div>
            </div>

            <!-- PIN Section -->
            <div class="mb-6 border-t pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">PIN de Acceso</h2>
                <p class="text-sm text-gray-600 mb-4">El PIN es requerido para que el usuario pueda iniciar sesión</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="pin" class="block text-sm font-medium text-gray-700 mb-2">
                            PIN (6 dígitos) <span class="text-red-500">*</span>
                        </label>
                        <input type="password"
                               id="pin"
                               name="pin"
                               required
                               maxlength="6"
                               pattern="[0-9]{6}"
                               inputmode="numeric"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                               placeholder="••••••">
                    </div>

                    <div>
                        <label for="confirm_pin" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirmar PIN <span class="text-red-500">*</span>
                        </label>
                        <input type="password"
                               id="confirm_pin"
                               name="confirm_pin"
                               required
                               maxlength="6"
                               pattern="[0-9]{6}"
                               inputmode="numeric"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                               placeholder="••••••">
                    </div>
                </div>
                <div id="pin-match-indicator" class="mt-2"></div>
            </div>

            <!-- Assigned Locations -->
            <div class="mb-6 border-t pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Ubicaciones Asignadas</h2>
                <p class="text-sm text-gray-600 mb-4">Selecciona las ubicaciones donde este usuario puede registrar asistencia</p>

                <?php if (!empty($allLocations)): ?>
                <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-4">
                    <?php foreach ($allLocations as $location): ?>
                    <div class="flex items-center">
                        <input type="checkbox"
                               id="location_<?= $location['id'] ?>"
                               name="locations[]"
                               value="<?= $location['id'] ?>"
                               class="rounded border-gray-300 text-navy focus:ring-navy">
                        <label for="location_<?= $location['id'] ?>" class="ml-2 text-sm text-gray-700">
                            <?= htmlspecialchars($location['nombre']) ?>
                            <?php if ($location['codigo']): ?>
                                <span class="text-gray-500 text-xs">(<?= htmlspecialchars($location['codigo']) ?>)</span>
                            <?php endif; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        No hay ubicaciones disponibles. Crea ubicaciones primero para asignarlas a los usuarios.
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-3">
                <a href="<?= url('admin/users') ?>"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-navy text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-user-plus mr-2"></i>
                    Crear Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// PIN validation
const pinInput = document.getElementById('pin');
const confirmPinInput = document.getElementById('confirm_pin');
const indicator = document.getElementById('pin-match-indicator');

function checkPinMatch() {
    const pin = pinInput.value;
    const confirmPin = confirmPinInput.value;

    if (pin.length === 0 && confirmPin.length === 0) {
        indicator.innerHTML = '';
        return true;
    }

    if (pin.length > 0 && pin.length < 6) {
        indicator.innerHTML = '<span class="text-orange-600 text-sm"><i class="fas fa-exclamation-circle mr-1"></i>El PIN debe tener 6 dígitos</span>';
        return false;
    }

    if (confirmPin.length > 0) {
        if (pin === confirmPin && pin.length === 6) {
            indicator.innerHTML = '<span class="text-green-600 text-sm"><i class="fas fa-check-circle mr-1"></i>Los PINs coinciden</span>';
            return true;
        } else {
            indicator.innerHTML = '<span class="text-red-600 text-sm"><i class="fas fa-times-circle mr-1"></i>Los PINs no coinciden</span>';
            return false;
        }
    }

    return true;
}

pinInput.addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    checkPinMatch();
});

confirmPinInput.addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    checkPinMatch();
});

// Form validation
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    const pin = pinInput.value;
    const confirmPin = confirmPinInput.value;

    if (pin.length !== 6) {
        e.preventDefault();
        alert('El PIN debe tener exactamente 6 dígitos');
        return false;
    }

    if (pin !== confirmPin) {
        e.preventDefault();
        alert('Los PINs no coinciden');
        return false;
    }

    return true;
});
</script>
