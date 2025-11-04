<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Sistema de Asistencia' ?> - Alpe Fresh</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#003366',
                        gold: '#fdb714',
                        primary: {
                            50: '#e6f0ff',
                            100: '#cce0ff',
                            200: '#99c2ff',
                            300: '#66a3ff',
                            400: '#3385ff',
                            500: '#0066ff',
                            600: '#0052cc',
                            700: '#003d99',
                            800: '#003366',
                            900: '#001a33',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Clock animation */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-navy shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Left side -->
                <div class="flex items-center">
                    <!-- Logo -->
                    <a href="<?= url('') ?>" class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gold rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-navy text-xl"></i>
                        </div>
                        <span class="text-white font-semibold text-lg hidden sm:block">
                            Sistema de Asistencia
                        </span>
                    </a>

                    <!-- Main Navigation (Desktop) -->
                    <div class="hidden md:flex items-center space-x-1 ml-10">
                        <?php if (hasRole('empleado')): ?>
                            <a href="<?= url('empleado/dashboard') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-home mr-2"></i>Inicio
                            </a>
                            <a href="<?= url('empleado/clock') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-clock mr-2"></i>Registrar
                            </a>
                            <a href="<?= url('empleado/history') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-history mr-2"></i>Historial
                            </a>
                        <?php endif; ?>

                        <?php if (hasRole('inspector')): ?>
                            <a href="<?= url('inspector/dashboard') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-home mr-2"></i>Inicio
                            </a>
                            <a href="<?= url('inspector/clock') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-clock mr-2"></i>Registrar
                            </a>
                            <a href="<?= url('admin/users') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-users mr-2"></i>Usuarios
                            </a>
                            <a href="<?= url('admin/locations') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-map-marker-alt mr-2"></i>Ubicaciones
                            </a>
                            <a href="<?= url('admin/reports') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-file-alt mr-2"></i>Reportes
                            </a>
                        <?php endif; ?>

                        <?php if (hasAnyRole(['admin', 'superadmin'])): ?>
                            <a href="<?= url('admin/dashboard') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-chart-line mr-2"></i>Dashboard
                            </a>
                            <a href="<?= url('admin/users') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-users mr-2"></i>Usuarios
                            </a>
                            <a href="<?= url('admin/locations') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-map-marker-alt mr-2"></i>Ubicaciones
                            </a>
                            <a href="<?= url('admin/reports') ?>" class="text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-file-alt mr-2"></i>Reportes
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right side -->
                <div class="flex items-center space-x-4">
                    <!-- User dropdown -->
                    <div class="relative group">
                        <button class="flex items-center space-x-3 text-white hover:text-gold transition-colors">
                            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            <span class="hidden sm:block text-sm">
                                <?= htmlspecialchars($_SESSION['user_nombre'] ?? '') ?>
                            </span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>

                        <!-- Dropdown menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <div class="py-1">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-xs text-gray-500">Rol</p>
                                    <p class="text-sm font-medium capitalize"><?= $_SESSION['user_role'] ?? '' ?></p>
                                </div>
                                <a href="<?= url('profile') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user-cog mr-2"></i>Mi Perfil
                                </a>
                                <a href="<?= url('auth/logout') ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <button class="md:hidden text-white" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="hidden md:hidden bg-navy/95 border-t border-white/10">
            <div class="px-4 py-3 space-y-1">
                <?php if (hasRole('empleado')): ?>
                    <a href="<?= url('empleado/dashboard') ?>" class="block text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-home mr-2"></i>Inicio
                    </a>
                    <a href="<?= url('empleado/clock') ?>" class="block text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-clock mr-2"></i>Registrar
                    </a>
                    <a href="<?= url('empleado/history') ?>" class="block text-white/80 hover:text-white hover:bg-white/10 px-3 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-history mr-2"></i>Historial
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if ($flash = getFlash('success')): ?>
    <div class="fixed top-20 right-4 z-50 animate-slide-in">
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-3"></i>
                <p><?= htmlspecialchars($flash['message']) ?></p>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($flash = getFlash('error')): ?>
    <div class="fixed top-20 right-4 z-50 animate-slide-in">
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <p><?= htmlspecialchars($flash['message']) ?></p>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="min-h-screen">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
                <div class="text-gray-500 text-sm">
                    &copy; <?= date('Y') ?> Alpe Fresh Mexico. Todos los derechos reservados.
                </div>
                <div class="flex items-center space-x-4 text-sm text-gray-500">
                    <span>Versión 1.0.0</span>
                    <span>|</span>
                    <a href="#" class="hover:text-navy">Soporte</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Global JavaScript -->
    <script>
        // Toggle mobile menu
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Auto-hide flash messages
        setTimeout(() => {
            const flashMessages = document.querySelectorAll('[class*="animate-slide-in"]');
            flashMessages.forEach(msg => {
                msg.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => msg.remove(), 300);
            });
        }, 5000);

        // Check session periodically
        setInterval(() => {
            fetch('<?= url("auth/checkSession") ?>')
                .then(response => response.json())
                .then(data => {
                    if (!data.valid) {
                        window.location.href = '<?= url("auth/login") ?>';
                    }
                });
        }, 60000); // Check every minute
    </script>

    <!-- Animation styles -->
    <style>
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</body>
</html>