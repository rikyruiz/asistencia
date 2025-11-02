<div class="w-full max-w-md">
    <!-- Logo and Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
            <svg class="w-12 h-12 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">Crear Cuenta</h1>
        <p class="text-white/80">Sistema de Asistencia - Alpe Fresh</p>
    </div>

    <!-- Registration Card -->
    <div class="glass rounded-2xl shadow-2xl p-8">
        <h2 class="text-2xl font-bold text-navy mb-6 text-center">Registro de Usuario</h2>

        <!-- Flash Messages -->
        <?php if ($flash = getFlash('register')): ?>
        <div class="bg-<?= $flash['type'] === 'error' ? 'red' : ($flash['type'] === 'success' ? 'green' : 'yellow') ?>-100
                    border-l-4 border-<?= $flash['type'] === 'error' ? 'red' : ($flash['type'] === 'success' ? 'green' : 'yellow') ?>-500
                    text-<?= $flash['type'] === 'error' ? 'red' : ($flash['type'] === 'success' ? 'green' : 'yellow') ?>-700
                    p-4 mb-6 rounded">
            <p class="text-sm"><?= htmlspecialchars($flash['message']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form action="<?= url('auth/processRegister') ?>" method="POST" id="registerForm">
            <?= csrfField() ?>

            <!-- Personal Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <!-- Nombre -->
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre(s) <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="nombre"
                           name="nombre"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent transition-all"
                           placeholder="Juan"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>

                <!-- Apellidos -->
                <div>
                    <label for="apellidos" class="block text-sm font-medium text-gray-700 mb-2">
                        Apellidos <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="apellidos"
                           name="apellidos"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent transition-all"
                           placeholder="Pérez García"
                           value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>">
                </div>
            </div>

            <!-- Employee Number -->
            <div class="mb-4">
                <label for="numero_empleado" class="block text-sm font-medium text-gray-700 mb-2">
                    Número de Empleado <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="numero_empleado"
                       name="numero_empleado"
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent transition-all"
                       placeholder="EMP001"
                       value="<?= htmlspecialchars($_POST['numero_empleado'] ?? '') ?>">
            </div>

            <!-- Username -->
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre de Usuario <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input type="text"
                           id="username"
                           name="username"
                           required
                           pattern="[a-zA-Z0-9_]{3,30}"
                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent transition-all"
                           placeholder="usuario123"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <p class="text-xs text-gray-500 mt-1">3-30 caracteres, solo letras, números y guión bajo</p>
            </div>

            <!-- Email (Optional) -->
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email <span class="text-gray-500">(Opcional - para notificaciones)</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <input type="email"
                           id="email"
                           name="email"
                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent transition-all"
                           placeholder="correo@ejemplo.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <!-- PIN -->
            <div class="mb-4">
                <label for="pin" class="block text-sm font-medium text-gray-700 mb-2">
                    PIN de Seguridad (6 dígitos) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input type="password"
                           id="pin"
                           name="pin"
                           required
                           maxlength="6"
                           pattern="[0-9]{6}"
                           inputmode="numeric"
                           class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent transition-all"
                           placeholder="••••••">
                    <button type="button"
                            onclick="togglePinVisibility('pin')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg id="pin-eye" class="h-5 w-5 text-gray-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg id="pin-eye-off" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Confirm PIN -->
            <div class="mb-6">
                <label for="confirm_pin" class="block text-sm font-medium text-gray-700 mb-2">
                    Confirmar PIN <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <input type="password"
                           id="confirm_pin"
                           name="confirm_pin"
                           required
                           maxlength="6"
                           pattern="[0-9]{6}"
                           inputmode="numeric"
                           class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent transition-all"
                           placeholder="••••••">
                    <button type="button"
                            onclick="togglePinVisibility('confirm_pin')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg id="confirm_pin-eye" class="h-5 w-5 text-gray-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg id="confirm_pin-eye-off" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        </svg>
                    </button>
                </div>
                <div id="pin-match-indicator" class="mt-2"></div>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full bg-navy text-white py-3 px-4 rounded-lg hover:bg-primary-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                Crear Cuenta
            </button>
        </form>

        <!-- Login Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                ¿Ya tienes cuenta?
                <a href="<?= url('auth/login') ?>" class="text-navy hover:text-primary-700 font-medium">
                    Inicia sesión aquí
                </a>
            </p>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-8 text-center text-white/60 text-sm">
        <p>&copy; <?= date('Y') ?> Alpe Fresh Mexico. Todos los derechos reservados.</p>
    </div>
</div>

<script>
// Toggle PIN visibility
function togglePinVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const eyeIcon = document.getElementById(fieldId + '-eye');
    const eyeOffIcon = document.getElementById(fieldId + '-eye-off');

    if (field.type === 'password') {
        field.type = 'text';
        eyeIcon.classList.remove('hidden');
        eyeOffIcon.classList.add('hidden');
    } else {
        field.type = 'password';
        eyeIcon.classList.add('hidden');
        eyeOffIcon.classList.remove('hidden');
    }
}

// Auto-format PIN inputs
['pin', 'confirm_pin'].forEach(id => {
    const field = document.getElementById(id);

    field.addEventListener('input', function(e) {
        // Remove any non-digits
        this.value = this.value.replace(/[^0-9]/g, '');

        // Limit to 6 digits
        if (this.value.length > 6) {
            this.value = this.value.slice(0, 6);
        }

        // Check PIN match
        checkPinMatch();
    });

    // Prevent paste of non-numeric content
    field.addEventListener('paste', function(e) {
        e.preventDefault();
        let paste = (e.clipboardData || window.clipboardData).getData('text');
        paste = paste.replace(/[^0-9]/g, '').slice(0, 6);
        this.value = paste;
        checkPinMatch();
    });
});

// Check if PINs match
function checkPinMatch() {
    const pin = document.getElementById('pin').value;
    const confirmPin = document.getElementById('confirm_pin').value;
    const indicator = document.getElementById('pin-match-indicator');

    if (pin.length === 0 || confirmPin.length === 0) {
        indicator.innerHTML = '';
        return;
    }

    if (pin === confirmPin && pin.length === 6) {
        indicator.innerHTML = '<span class="text-green-600 text-sm"><i class="fas fa-check-circle mr-1"></i>Los PINs coinciden</span>';
    } else if (confirmPin.length === 6) {
        indicator.innerHTML = '<span class="text-red-600 text-sm"><i class="fas fa-times-circle mr-1"></i>Los PINs no coinciden</span>';
    }
}

// Username validation
document.getElementById('username').addEventListener('input', function(e) {
    // Remove invalid characters
    this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');

    // Limit length
    if (this.value.length > 30) {
        this.value = this.value.slice(0, 30);
    }
});

// Form validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const pin = document.getElementById('pin').value;
    const confirmPin = document.getElementById('confirm_pin').value;

    if (pin !== confirmPin) {
        e.preventDefault();
        alert('Los PINs no coinciden. Por favor verifica.');
        return false;
    }

    if (pin.length !== 6) {
        e.preventDefault();
        alert('El PIN debe ser de exactamente 6 dígitos.');
        return false;
    }
});
</script>