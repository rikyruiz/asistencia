<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Asistencia - Inicio</title>

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
</head>
<body class="min-h-screen">
    <!-- Fondo con gradiente y patrón -->
    <div class="fixed inset-0 gradient-navy-radial pattern-dots -z-10"></div>

    <!-- Navegación -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gold flex items-center justify-center">
                        <i class="fas fa-clock text-navy text-xl"></i>
                    </div>
                    <span class="text-xl font-bold text-navy">SistemaAsist</span>
                </div>

                <!-- Menú desktop -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#inicio" class="text-navy hover:text-gold transition font-medium">Inicio</a>
                    <a href="#caracteristicas" class="text-navy hover:text-gold transition font-medium">Características</a>
                    <a href="#estadisticas" class="text-navy hover:text-gold transition font-medium">Estadísticas</a>
                    <a href="login.php" class="btn-navy inline-flex items-center px-6 py-2 rounded-xl font-medium shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Ingresar
                    </a>
                </div>

                <!-- Botón menú móvil -->
                <button class="md:hidden text-navy" id="mobile-menu-button">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>

        <!-- Menú móvil -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-4 pt-2 pb-4 space-y-2 glass-dark">
                <a href="#inicio" class="block px-4 py-2 text-white hover:bg-white/10 rounded-xl transition">Inicio</a>
                <a href="#caracteristicas" class="block px-4 py-2 text-white hover:bg-white/10 rounded-xl transition">Características</a>
                <a href="#estadisticas" class="block px-4 py-2 text-white hover:bg-white/10 rounded-xl transition">Estadísticas</a>
                <a href="login.php" class="block px-4 py-2 btn-gold text-center rounded-xl font-medium">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Ingresar
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="inicio" class="pt-32 pb-20">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!-- Contenido izquierdo -->
                <div class="text-white space-y-6">
                    <div class="inline-block glass-gold px-4 py-2 rounded-full text-sm font-medium text-gold mb-4">
                        <i class="fas fa-star mr-2"></i>
                        Sistema Profesional de Asistencia
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">
                        Control Total de
                        <span class="text-gradient block">Asistencia</span>
                    </h1>

                    <p class="text-lg text-white/80 leading-relaxed">
                        Gestiona la asistencia de tu equipo de manera eficiente y moderna.
                        Registros en tiempo real, reportes detallados y análisis completo.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <a href="login.php" class="btn-gold inline-flex items-center justify-center px-8 py-4 rounded-xl font-semibold shadow-xl hover-lift">
                            <i class="fas fa-rocket mr-2"></i>
                            Comenzar Ahora
                        </a>
                        <a href="#caracteristicas" class="glass inline-flex items-center justify-center px-8 py-4 rounded-xl font-semibold hover-lift">
                            <i class="fas fa-play-circle mr-2 text-navy"></i>
                            <span class="text-navy">Ver Demo</span>
                        </a>
                    </div>

                    <!-- Métricas rápidas -->
                    <div class="grid grid-cols-3 gap-4 pt-8">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gold">99.9%</div>
                            <div class="text-sm text-white/70">Disponibilidad</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gold">500+</div>
                            <div class="text-sm text-white/70">Empresas</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gold">24/7</div>
                            <div class="text-sm text-white/70">Soporte</div>
                        </div>
                    </div>
                </div>

                <!-- Imagen/Ilustración derecha -->
                <div class="relative">
                    <div class="glass rounded-2xl p-8 hover-lift animate-float">
                        <div class="space-y-4">
                            <!-- Simulación de interfaz -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 rounded-xl bg-gold flex items-center justify-center">
                                        <i class="fas fa-user text-navy text-xl"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-navy">Juan Pérez</div>
                                        <div class="text-sm text-navy/60">Desarrollador</div>
                                    </div>
                                </div>
                                <div class="badge-success px-4 py-2 rounded-xl font-medium text-sm">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Activo
                                </div>
                            </div>

                            <!-- Gráfico simulado -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-navy/70">Entrada</span>
                                    <span class="font-semibold text-navy">08:00 AM</span>
                                </div>
                                <div class="h-2 bg-navy/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-navy to-gold w-4/5"></div>
                                </div>

                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-sm text-navy/70">Salida</span>
                                    <span class="font-semibold text-navy">17:00 PM</span>
                                </div>
                                <div class="h-2 bg-navy/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-navy to-gold w-full"></div>
                                </div>
                            </div>

                            <!-- Estadísticas -->
                            <div class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t border-navy/10">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-navy">9h</div>
                                    <div class="text-xs text-navy/60">Horas trabajadas</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gold">100%</div>
                                    <div class="text-xs text-navy/60">Asistencia</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Elementos decorativos -->
                    <div class="absolute -top-6 -right-6 w-24 h-24 bg-gold/20 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-6 -left-6 w-32 h-32 bg-navy/20 rounded-full blur-2xl"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Características -->
    <section id="caracteristicas" class="py-20">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Encabezado -->
            <div class="text-center mb-16">
                <div class="inline-block glass px-6 py-2 rounded-full text-sm font-medium text-navy mb-4">
                    <i class="fas fa-sparkles mr-2 text-gold"></i>
                    Características Principales
                </div>
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    Todo lo que necesitas
                </h2>
                <p class="text-lg text-white/80 max-w-2xl mx-auto">
                    Sistema completo con todas las herramientas necesarias para gestionar la asistencia de tu equipo
                </p>
            </div>

            <!-- Grid de características -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Característica 1 -->
                <div class="glass rounded-2xl p-6 hover-lift stat-card">
                    <div class="w-14 h-14 rounded-xl bg-gold/20 flex items-center justify-center mb-4">
                        <i class="fas fa-clock text-gold text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-navy mb-2">Registro en Tiempo Real</h3>
                    <p class="text-navy/70">
                        Registra entradas y salidas al instante con sincronización automática en la nube.
                    </p>
                </div>

                <!-- Característica 2 -->
                <div class="glass rounded-2xl p-6 hover-lift stat-card">
                    <div class="w-14 h-14 rounded-xl bg-gold/20 flex items-center justify-center mb-4">
                        <i class="fas fa-map-marker-alt text-gold text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-navy mb-2">Geolocalización GPS</h3>
                    <p class="text-navy/70">
                        Control de ubicación preciso para verificar asistencias desde lugares autorizados.
                    </p>
                </div>

                <!-- Característica 3 -->
                <div class="glass rounded-2xl p-6 hover-lift stat-card">
                    <div class="w-14 h-14 rounded-xl bg-gold/20 flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-gold text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-navy mb-2">Reportes Avanzados</h3>
                    <p class="text-navy/70">
                        Genera reportes detallados con gráficos y estadísticas para análisis completo.
                    </p>
                </div>

                <!-- Característica 4 -->
                <div class="glass rounded-2xl p-6 hover-lift stat-card">
                    <div class="w-14 h-14 rounded-xl bg-gold/20 flex items-center justify-center mb-4">
                        <i class="fas fa-mobile-alt text-gold text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-navy mb-2">100% Responsive</h3>
                    <p class="text-navy/70">
                        Accede desde cualquier dispositivo: computadora, tablet o smartphone.
                    </p>
                </div>

                <!-- Característica 5 -->
                <div class="glass rounded-2xl p-6 hover-lift stat-card">
                    <div class="w-14 h-14 rounded-xl bg-gold/20 flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-gold text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-navy mb-2">Seguridad Avanzada</h3>
                    <p class="text-navy/70">
                        Encriptación de datos y múltiples niveles de autenticación para máxima seguridad.
                    </p>
                </div>

                <!-- Característica 6 -->
                <div class="glass rounded-2xl p-6 hover-lift stat-card">
                    <div class="w-14 h-14 rounded-xl bg-gold/20 flex items-center justify-center mb-4">
                        <i class="fas fa-bell text-gold text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-navy mb-2">Notificaciones Push</h3>
                    <p class="text-navy/70">
                        Recibe alertas instantáneas sobre eventos importantes y recordatorios.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Estadísticas en Tiempo Real -->
    <section id="estadisticas" class="py-20">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass-dark rounded-2xl p-8 md:p-12">
                <div class="text-center mb-12">
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        Estadísticas en Tiempo Real
                    </h2>
                    <p class="text-lg text-white/80">
                        Monitorea el estado actual de tu equipo
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Estadística 1 -->
                    <div class="glass rounded-2xl p-6 text-center hover-lift">
                        <div class="w-16 h-16 rounded-full bg-gold/20 flex items-center justify-center mx-auto mb-4 animate-pulse-glow">
                            <i class="fas fa-users text-gold text-3xl"></i>
                        </div>
                        <div class="text-4xl font-bold text-navy mb-2" data-count="156">0</div>
                        <div class="text-navy/70 font-medium">Empleados Activos</div>
                        <div class="mt-3 flex items-center justify-center text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <span>12% vs ayer</span>
                        </div>
                    </div>

                    <!-- Estadística 2 -->
                    <div class="glass rounded-2xl p-6 text-center hover-lift">
                        <div class="w-16 h-16 rounded-full bg-gold/20 flex items-center justify-center mx-auto mb-4 animate-pulse-glow">
                            <i class="fas fa-check-circle text-gold text-3xl"></i>
                        </div>
                        <div class="text-4xl font-bold text-navy mb-2" data-count="142">0</div>
                        <div class="text-navy/70 font-medium">Asistencias Hoy</div>
                        <div class="mt-3 flex items-center justify-center text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <span>5% vs ayer</span>
                        </div>
                    </div>

                    <!-- Estadística 3 -->
                    <div class="glass rounded-2xl p-6 text-center hover-lift">
                        <div class="w-16 h-16 rounded-full bg-gold/20 flex items-center justify-center mx-auto mb-4 animate-pulse-glow">
                            <i class="fas fa-clock text-gold text-3xl"></i>
                        </div>
                        <div class="text-4xl font-bold text-navy mb-2" data-count="8">0</div>
                        <div class="text-navy/70 font-medium">Horas Promedio</div>
                        <div class="mt-3 flex items-center justify-center text-sm text-navy/50">
                            <i class="fas fa-minus mr-1"></i>
                            <span>Sin cambios</span>
                        </div>
                    </div>

                    <!-- Estadística 4 -->
                    <div class="glass rounded-2xl p-6 text-center hover-lift">
                        <div class="w-16 h-16 rounded-full bg-gold/20 flex items-center justify-center mx-auto mb-4 animate-pulse-glow">
                            <i class="fas fa-percentage text-gold text-3xl"></i>
                        </div>
                        <div class="text-4xl font-bold text-navy mb-2" data-count="96">0</div>
                        <div class="text-navy/70 font-medium">Tasa de Asistencia</div>
                        <div class="mt-3 flex items-center justify-center text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <span>3% vs ayer</span>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de actividad -->
                <div class="mt-12 glass rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-navy mb-6">Actividad de la Semana</h3>
                    <div class="flex items-end justify-between h-48 gap-4">
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-navy/20 rounded-t-xl relative overflow-hidden" style="height: 65%">
                                <div class="absolute bottom-0 w-full bg-gradient-to-t from-navy to-gold rounded-t-xl h-full"></div>
                            </div>
                            <span class="text-sm text-navy/70 mt-2 font-medium">Lun</span>
                        </div>
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-navy/20 rounded-t-xl relative overflow-hidden" style="height: 80%">
                                <div class="absolute bottom-0 w-full bg-gradient-to-t from-navy to-gold rounded-t-xl h-full"></div>
                            </div>
                            <span class="text-sm text-navy/70 mt-2 font-medium">Mar</span>
                        </div>
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-navy/20 rounded-t-xl relative overflow-hidden" style="height: 90%">
                                <div class="absolute bottom-0 w-full bg-gradient-to-t from-navy to-gold rounded-t-xl h-full"></div>
                            </div>
                            <span class="text-sm text-navy/70 mt-2 font-medium">Mié</span>
                        </div>
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-navy/20 rounded-t-xl relative overflow-hidden" style="height: 75%">
                                <div class="absolute bottom-0 w-full bg-gradient-to-t from-navy to-gold rounded-t-xl h-full"></div>
                            </div>
                            <span class="text-sm text-navy/70 mt-2 font-medium">Jue</span>
                        </div>
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-navy/20 rounded-t-xl relative overflow-hidden" style="height: 100%">
                                <div class="absolute bottom-0 w-full bg-gradient-to-t from-navy to-gold rounded-t-xl h-full"></div>
                            </div>
                            <span class="text-sm text-navy/70 mt-2 font-medium">Vie</span>
                        </div>
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-navy/20 rounded-t-xl relative overflow-hidden" style="height: 45%">
                                <div class="absolute bottom-0 w-full bg-gradient-to-t from-navy to-gold rounded-t-xl h-full"></div>
                            </div>
                            <span class="text-sm text-navy/70 mt-2 font-medium">Sáb</span>
                        </div>
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-navy/20 rounded-t-xl relative overflow-hidden" style="height: 30%">
                                <div class="absolute bottom-0 w-full bg-gradient-to-t from-navy to-gold rounded-t-xl h-full"></div>
                            </div>
                            <span class="text-sm text-navy/70 mt-2 font-medium">Dom</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 mt-20">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass rounded-2xl p-8">
                <div class="grid md:grid-cols-4 gap-8">
                    <!-- Columna 1 -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-gold flex items-center justify-center">
                                <i class="fas fa-clock text-navy text-xl"></i>
                            </div>
                            <span class="text-xl font-bold text-navy">SistemaAsist</span>
                        </div>
                        <p class="text-navy/70 text-sm">
                            La solución moderna para gestionar la asistencia de tu equipo de manera eficiente.
                        </p>
                        <div class="flex space-x-3">
                            <a href="#" class="w-10 h-10 rounded-xl bg-navy/10 flex items-center justify-center hover:bg-gold transition">
                                <i class="fab fa-facebook-f text-navy"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-xl bg-navy/10 flex items-center justify-center hover:bg-gold transition">
                                <i class="fab fa-twitter text-navy"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-xl bg-navy/10 flex items-center justify-center hover:bg-gold transition">
                                <i class="fab fa-linkedin-in text-navy"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-xl bg-navy/10 flex items-center justify-center hover:bg-gold transition">
                                <i class="fab fa-instagram text-navy"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Columna 2 -->
                    <div>
                        <h4 class="font-bold text-navy mb-4">Producto</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Características</a></li>
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Precios</a></li>
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Integraciones</a></li>
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Actualizaciones</a></li>
                        </ul>
                    </div>

                    <!-- Columna 3 -->
                    <div>
                        <h4 class="font-bold text-navy mb-4">Empresa</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Sobre Nosotros</a></li>
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Blog</a></li>
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Carreras</a></li>
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Contacto</a></li>
                        </ul>
                    </div>

                    <!-- Columna 4 -->
                    <div>
                        <h4 class="font-bold text-navy mb-4">Soporte</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Centro de Ayuda</a></li>
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Documentación</a></li>
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">API</a></li>
                            <li><a href="#" class="text-navy/70 hover:text-gold transition">Estado del Sistema</a></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-navy/10 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                    <p class="text-sm text-navy/70">
                        &copy; 2025 SistemaAsist. Todos los derechos reservados.
                    </p>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="#" class="text-sm text-navy/70 hover:text-gold transition">Privacidad</a>
                        <a href="#" class="text-sm text-navy/70 hover:text-gold transition">Términos</a>
                        <a href="#" class="text-sm text-navy/70 hover:text-gold transition">Cookies</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Menú móvil
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });

        // Scroll suave
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    document.getElementById('mobile-menu').classList.add('hidden');
                }
            });
        });

        // Animación de contadores
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current);
                }
            }, 30);
        }

        // Observador de intersección para animar contadores
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.getAttribute('data-count'));
                    animateCounter(counter, target);
                    observer.unobserve(counter);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('[data-count]').forEach(counter => {
            observer.observe(counter);
        });

        // Cambiar opacidad del nav al hacer scroll
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.style.background = 'rgba(255, 255, 255, 0.95)';
            } else {
                nav.style.background = 'rgba(255, 255, 255, 0.7)';
            }
        });
    </script>
</body>
</html>
