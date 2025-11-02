<div class="w-full max-w-md">
    <!-- Logo and Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
            <svg class="w-12 h-12 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">Sistema de Asistencia</h1>
        <p class="text-white/80">Alpe Fresh Mexico</p>
    </div>

    <!-- Login Card -->
    <div class="glass rounded-2xl shadow-2xl p-8">
        <h2 class="text-2xl font-bold text-navy mb-6 text-center">Iniciar Sesión</h2>

        <!-- Flash Messages -->
        <?php if (isset($error) && $error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <p class="text-sm"><?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($flash = getFlash('login')): ?>
        <div class="bg-<?= $flash['type'] === 'error' ? 'red' : ($flash['type'] === 'success' ? 'green' : 'yellow') ?>-100
                    border-l-4 border-<?= $flash['type'] === 'error' ? 'red' : ($flash['type'] === 'success' ? 'green' : 'yellow') ?>-500
                    text-<?= $flash['type'] === 'error' ? 'red' : ($flash['type'] === 'success' ? 'green' : 'yellow') ?>-700
                    p-4 mb-6 rounded">
            <p class="text-sm"><?= htmlspecialchars($flash['message']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="<?= url('auth/processLogin') ?>" method="POST">
            <?= csrfField() ?>

            <!-- Username Input -->
            <div class="mb-6">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre de Usuario
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
                           class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent transition-all"
                           placeholder="Tu nombre de usuario"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <!-- PIN Input -->
            <div class="mb-6">
                <label for="pin" class="block text-sm font-medium text-gray-700 mb-2">
                    PIN de Seguridad (6 dígitos)
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
                           class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent transition-all pin-input"
                           placeholder="••••••">
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between mb-6">
                <label class="flex items-center">
                    <input type="checkbox"
                           name="remember"
                           class="rounded border-gray-300 text-navy focus:ring-navy">
                    <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                </label>
                <a href="<?= url('auth/forgot-password') ?>" class="text-sm text-navy hover:text-primary-700">
                    ¿Olvidaste tu PIN?
                </a>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full bg-navy text-white py-3 px-4 rounded-lg hover:bg-primary-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                Iniciar Sesión
            </button>
        </form>

        <!-- Additional Info -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                ¿Primera vez?
                <a href="<?= url('auth/register') ?>" class="text-navy hover:text-primary-700 font-medium">
                    Registra tu cuenta aquí
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
// Auto-format PIN input as user types
document.getElementById('pin').addEventListener('input', function(e) {
    // Remove any non-digits
    this.value = this.value.replace(/[^0-9]/g, '');

    // Limit to 6 digits
    if (this.value.length > 6) {
        this.value = this.value.slice(0, 6);
    }
});

// Prevent paste of non-numeric content in PIN field
document.getElementById('pin').addEventListener('paste', function(e) {
    e.preventDefault();
    let paste = (e.clipboardData || window.clipboardData).getData('text');
    paste = paste.replace(/[^0-9]/g, '').slice(0, 6);
    this.value = paste;
});
</script>