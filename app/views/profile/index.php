<div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Mi Perfil</h1>
        <p class="text-gray-600 mt-1">Administra tu información personal y configuración</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Information Card -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-user mr-2 text-navy"></i>
                        Información Personal
                    </h2>
                </div>
                <form action="<?= url('profile/update') ?>" method="POST" class="p-6">
                    <?= csrfField() ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="nombre"
                                   name="nombre"
                                   required
                                   value="<?= htmlspecialchars($user['nombre']) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
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
                                   value="<?= htmlspecialchars($user['apellidos']) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        </div>

                        <!-- Email (read-only) -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email
                            </label>
                            <input type="email"
                                   id="email"
                                   value="<?= htmlspecialchars($user['email']) ?>"
                                   disabled
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                            <p class="text-xs text-gray-500 mt-1">El email no puede ser modificado</p>
                        </div>

                        <!-- Employee Number (read-only) -->
                        <div>
                            <label for="numero_empleado" class="block text-sm font-medium text-gray-700 mb-2">
                                Número de Empleado
                            </label>
                            <input type="text"
                                   id="numero_empleado"
                                   value="<?= htmlspecialchars($user['numero_empleado']) ?>"
                                   disabled
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                                Teléfono
                            </label>
                            <input type="tel"
                                   id="telefono"
                                   name="telefono"
                                   value="<?= htmlspecialchars($user['telefono'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                                   placeholder="555-123-4567">
                        </div>

                        <!-- Department -->
                        <div>
                            <label for="departamento" class="block text-sm font-medium text-gray-700 mb-2">
                                Departamento
                            </label>
                            <input type="text"
                                   id="departamento"
                                   name="departamento"
                                   value="<?= htmlspecialchars($user['departamento'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                                   placeholder="Ej: Ventas">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                                class="px-6 py-2 bg-navy text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>

            <!-- Assigned Locations -->
            <?php if (!empty($locations)): ?>
            <div class="bg-white rounded-xl shadow mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-map-marker-alt mr-2 text-navy"></i>
                        Ubicaciones Asignadas
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($locations as $location): ?>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 bg-navy rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-building text-white"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($location['nombre']) ?></p>
                                <p class="text-sm text-gray-500"><?= ucfirst($location['tipo_ubicacion']) ?></p>
                            </div>
                            <?php if ($location['es_principal']): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Principal
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Account Info -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-info-circle mr-2 text-navy"></i>
                    Información de Cuenta
                </h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Rol</p>
                        <p class="text-sm font-medium capitalize"><?= htmlspecialchars($user['rol']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Estado</p>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $user['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= $user['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Miembro desde</p>
                        <p class="text-sm"><?= formatDate($user['creado_en']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Change PIN -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-key mr-2 text-navy"></i>
                    Cambiar PIN
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    Tu PIN es usado para el inicio de sesión rápido
                </p>
                <button onclick="openChangePinModal()"
                        class="w-full px-4 py-2 bg-navy text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-lock mr-2"></i>
                    Cambiar PIN
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Change PIN Modal -->
<div id="changePinModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-key mr-2 text-navy"></i>
                    Cambiar PIN
                </h3>
                <button onclick="closePinModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <form id="changePinForm" class="p-6">
            <?= csrfField() ?>

            <div class="space-y-4">
                <!-- Current PIN -->
                <div>
                    <label for="current_pin" class="block text-sm font-medium text-gray-700 mb-2">
                        PIN Actual
                    </label>
                    <input type="password"
                           id="current_pin"
                           name="current_pin"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent text-center text-2xl tracking-widest"
                           placeholder="••••••">
                </div>

                <!-- New PIN -->
                <div>
                    <label for="new_pin" class="block text-sm font-medium text-gray-700 mb-2">
                        Nuevo PIN (6 dígitos)
                    </label>
                    <input type="password"
                           id="new_pin"
                           name="new_pin"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent text-center text-2xl tracking-widest"
                           placeholder="••••••">
                </div>

                <!-- Confirm PIN -->
                <div>
                    <label for="confirm_pin" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirmar Nuevo PIN
                    </label>
                    <input type="password"
                           id="confirm_pin"
                           name="confirm_pin"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent text-center text-2xl tracking-widest"
                           placeholder="••••••">
                </div>

                <div id="pin-error" class="hidden p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600"></div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button"
                        onclick="closePinModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-navy text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-check mr-2"></i>
                    Actualizar PIN
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openChangePinModal() {
    document.getElementById('changePinModal').classList.remove('hidden');
    document.getElementById('current_pin').focus();
}

function closePinModal() {
    document.getElementById('changePinModal').classList.add('hidden');
    document.getElementById('changePinForm').reset();
    document.getElementById('pin-error').classList.add('hidden');
}

// Handle PIN change form submission
document.getElementById('changePinForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const currentPin = document.getElementById('current_pin').value;
    const newPin = document.getElementById('new_pin').value;
    const confirmPin = document.getElementById('confirm_pin').value;
    const errorDiv = document.getElementById('pin-error');

    // Client-side validation
    if (newPin.length !== 6 || !/^\d+$/.test(newPin)) {
        errorDiv.textContent = 'El PIN debe tener exactamente 6 dígitos';
        errorDiv.classList.remove('hidden');
        return;
    }

    if (newPin !== confirmPin) {
        errorDiv.textContent = 'Los PINs no coinciden';
        errorDiv.classList.remove('hidden');
        return;
    }

    errorDiv.classList.add('hidden');

    // Submit via AJAX
    const formData = new FormData(this);

    fetch('<?= url("profile/changePin") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closePinModal();
            alert('PIN actualizado correctamente');
        } else {
            errorDiv.textContent = data.error || 'Error al actualizar el PIN';
            errorDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        errorDiv.textContent = 'Error de conexión';
        errorDiv.classList.remove('hidden');
    });
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePinModal();
    }
});

// Only allow numbers in PIN fields
document.querySelectorAll('input[type="password"][pattern="[0-9]{6}"]').forEach(input => {
    input.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
</script>
