<?php
session_start();
// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Asistencia</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Configuración Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            DEFAULT: '#003366',
                            light: '#004d99',
                            dark: '#002244'
                        },
                        gold: {
                            DEFAULT: '#fdb714',
                            light: '#ffc942'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <!-- Contenedor principal -->
    <div class="w-full max-w-6xl grid lg:grid-cols-2 gap-8 items-center">
        <!-- Columna izquierda - Información -->
        <div class="hidden lg:block text-white space-y-6">
            <div class="space-y-4">
                <div class="inline-block glass-gold px-4 py-2 rounded-full text-sm font-medium text-gold">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Acceso Seguro
                </div>
                <h1 class="text-5xl font-bold leading-tight">
                    Bienvenido al
                    <span class="text-gradient block">Sistema de Asistencia</span>
                </h1>
                <p class="text-lg text-white/80">
                    Gestiona tu jornada laboral de forma eficiente y segura
                </p>
            </div>

            <!-- Características -->
            <div class="space-y-4 pt-8">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-gold/20 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clock text-gold text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-1">Registro en Tiempo Real</h3>
                        <p class="text-white/70 text-sm">Marca tu entrada y salida al instante</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-gold/20 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-map-marker-alt text-gold text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-1">Verificación GPS</h3>
                        <p class="text-white/70 text-sm">Control de ubicación preciso y confiable</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-gold/20 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-chart-bar text-gold text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-1">Reportes Detallados</h3>
                        <p class="text-white/70 text-sm">Consulta tu historial y estadísticas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha - Formulario de login -->
        <div class="w-full max-w-md mx-auto">
            <div class="glass rounded-2xl p-8 md:p-10 hover-lift">
                <!-- Logo y título -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 rounded-2xl bg-gold flex items-center justify-center mx-auto mb-4 animate-pulse-glow">
                        <i class="fas fa-user-clock text-navy text-3xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-navy mb-2">Iniciar Sesión</h2>
                    <p class="text-navy/70">Ingresa tus credenciales para continuar</p>
                </div>

                <!-- Tabs de método de login -->
                <div class="flex space-x-2 mb-6 bg-navy/10 p-1 rounded-xl">
                    <button id="tab-email" class="flex-1 py-2 px-4 rounded-lg font-medium transition bg-white text-navy shadow">
                        <i class="fas fa-envelope mr-2"></i>
                        Email
                    </button>
                    <button id="tab-pin" class="flex-1 py-2 px-4 rounded-lg font-medium transition text-navy/70 hover:text-navy">
                        <i class="fas fa-hashtag mr-2"></i>
                        PIN
                    </button>
                </div>

                <!-- Formulario con Email -->
                <form id="form-email" class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-navy mb-2">
                            <i class="fas fa-envelope mr-2"></i>
                            Correo Electrónico
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="input-glass w-full px-4 py-3 rounded-xl text-navy placeholder-navy/40"
                            placeholder="tu@empresa.com"
                            required
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-navy mb-2">
                            <i class="fas fa-lock mr-2"></i>
                            Contraseña
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="input-glass w-full px-4 py-3 rounded-xl text-navy placeholder-navy/40"
                                placeholder="••••••••"
                                required
                            >
                            <button
                                type="button"
                                id="toggle-password"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-navy/50 hover:text-navy transition"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-navy/30 text-gold focus:ring-gold">
                            <span class="ml-2 text-navy/70">Recordarme</span>
                        </label>
                        <a href="forgot-password.php" class="text-gold hover:text-gold-light font-medium transition">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    <div class="text-center mt-6">
                        <span class="text-gray-600">¿No tienes cuenta?</span>
                        <a href="register.php" class="text-navy hover:text-navy-light font-medium transition ml-1">
                            Regístrate aquí
                        </a>
                    </div>

                    <button type="submit" class="btn-navy w-full py-4 rounded-xl font-semibold text-lg shadow-xl hover-lift flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Ingresar
                    </button>
                </form>

                <!-- Formulario con PIN -->
                <form id="form-pin" class="space-y-5 hidden">
                    <div>
                        <label for="employee-id" class="block text-sm font-medium text-navy mb-2">
                            <i class="fas fa-id-badge mr-2"></i>
                            ID de Empleado
                        </label>
                        <input
                            type="text"
                            id="employee-id"
                            name="employee_id"
                            class="input-glass w-full px-4 py-3 rounded-xl text-navy placeholder-navy/40"
                            placeholder="Ej: EMP001"
                            inputmode="numeric"
                            required
                        >
                    </div>

                    <div>
                        <label for="pin" class="block text-sm font-medium text-navy mb-2">
                            <i class="fas fa-key mr-2"></i>
                            PIN de Acceso
                        </label>
                        <input
                            type="password"
                            id="pin"
                            name="pin"
                            maxlength="6"
                            minlength="6"
                            class="input-glass w-full px-4 py-3 rounded-xl text-navy placeholder-navy/40 text-center text-2xl tracking-widest font-bold"
                            placeholder="••••••"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            required
                        >
                        <p class="text-xs text-navy/50 mt-2 text-center">Ingresa tu PIN de 6 dígitos</p>
                    </div>

                    <button type="submit" class="btn-gold w-full py-4 rounded-xl font-semibold text-lg shadow-xl hover-lift flex items-center justify-center">
                        <i class="fas fa-fingerprint mr-2"></i>
                        Acceder con PIN
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-navy/10"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white/80 text-navy/60">o continúa con</span>
                    </div>
                </div>

                <!-- Métodos alternativos -->
                <div class="grid grid-cols-2 gap-3">
                    <button class="flex items-center justify-center px-4 py-3 rounded-xl border border-navy/20 hover:bg-navy/5 transition glass">
                        <i class="fab fa-google text-navy text-lg"></i>
                        <span class="ml-2 text-navy font-medium">Google</span>
                    </button>
                    <button class="flex items-center justify-center px-4 py-3 rounded-xl border border-navy/20 hover:bg-navy/5 transition glass">
                        <i class="fab fa-microsoft text-navy text-lg"></i>
                        <span class="ml-2 text-navy font-medium">Microsoft</span>
                    </button>
                </div>

                <!-- Footer del formulario -->
                <div class="mt-8 text-center">
                    <p class="text-sm text-navy/70">
                        ¿Problemas para ingresar?
                        <a href="#" class="text-gold hover:text-gold-light font-medium transition ml-1">
                            Contacta soporte
                        </a>
                    </p>
                </div>
            </div>

            <!-- Volver al inicio -->
            <div class="text-center mt-6">
                <a href="index.php" class="inline-flex items-center text-white hover:text-gold transition font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver al inicio
                </a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Tabs de login
        const tabEmail = document.getElementById('tab-email');
        const tabPin = document.getElementById('tab-pin');
        const formEmail = document.getElementById('form-email');
        const formPin = document.getElementById('form-pin');

        tabEmail.addEventListener('click', () => {
            tabEmail.classList.add('bg-white', 'text-navy', 'shadow');
            tabEmail.classList.remove('text-navy/70');
            tabPin.classList.remove('bg-white', 'text-navy', 'shadow');
            tabPin.classList.add('text-navy/70');
            formEmail.classList.remove('hidden');
            formPin.classList.add('hidden');
        });

        tabPin.addEventListener('click', () => {
            tabPin.classList.add('bg-white', 'text-navy', 'shadow');
            tabPin.classList.remove('text-navy/70');
            tabEmail.classList.remove('bg-white', 'text-navy', 'shadow');
            tabEmail.classList.add('text-navy/70');
            formPin.classList.remove('hidden');
            formEmail.classList.add('hidden');
        });

        // Toggle password visibility
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.querySelector('i').classList.toggle('fa-eye');
            togglePassword.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Validación de PIN solo números
        const pinInput = document.getElementById('pin');
        pinInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });

        // Animación al cargar
        window.addEventListener('load', () => {
            document.querySelector('.glass').style.opacity = '0';
            document.querySelector('.glass').style.transform = 'translateY(20px)';
            setTimeout(() => {
                document.querySelector('.glass').style.transition = 'all 0.5s ease';
                document.querySelector('.glass').style.opacity = '1';
                document.querySelector('.glass').style.transform = 'translateY(0)';
            }, 100);
        });

        // Detectar si es móvil y activar tab PIN por defecto
        function isMobileDevice() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
                && !/iPad|tablet|playbook/i.test(navigator.userAgent);
        }

        // Si es móvil, cambiar al tab de PIN automáticamente
        if (isMobileDevice()) {
            tabPin.click();
        }

        // Función para mostrar mensajes
        function showMessage(message, type = 'error') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg glass ${
                type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
            }`;
            alertDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(alertDiv);

            setTimeout(() => {
                alertDiv.style.opacity = '0';
                alertDiv.style.transition = 'opacity 0.5s';
                setTimeout(() => alertDiv.remove(), 500);
            }, 3000);
        }

        // Login con Email
        formEmail.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = formEmail.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Validando...';
            submitBtn.disabled = true;

            const formData = {
                tipo: 'email',
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };

            try {
                const response = await fetch('/api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('¡Login exitoso! Redirigiendo...', 'success');
                    setTimeout(() => {
                        // Usar redirect del servidor (con lógica de mobile) o dashboard por defecto
                        window.location.href = result.redirect || 'dashboard.php';
                    }, 1500);
                } else {
                    showMessage(result.message || 'Error al iniciar sesión', 'error');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error de conexión. Intente nuevamente.', 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Login con PIN
        formPin.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = formPin.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Validando...';
            submitBtn.disabled = true;

            const formData = {
                tipo: 'pin',
                codigo_empleado: document.getElementById('employee-id').value,
                pin: document.getElementById('pin').value
            };

            try {
                const response = await fetch('/api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('¡Login exitoso! Redirigiendo...', 'success');
                    setTimeout(() => {
                        // Usar redirect del servidor (con lógica de mobile) o dashboard por defecto
                        window.location.href = result.redirect || 'dashboard.php';
                    }, 1500);
                } else {
                    showMessage(result.message || 'Error al iniciar sesión', 'error');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error de conexión. Intente nuevamente.', 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Mostrar mensaje si viene de logout
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('message') === 'logged_out') {
            showMessage('Sesión cerrada exitosamente', 'success');
        }
    </script>
</body>
</html>
