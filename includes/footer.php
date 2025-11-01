<?php
/**
 * Footer compartido para el sistema de asistencia
 * Basado en el estilo del marketplace
 */
?>

<footer style="margin-top: 4rem; padding: 2rem 0; background-color: var(--gray-50); border-top: 1px solid var(--gray-200);">
    <div class="container" style="max-width: 1400px; margin: 0 auto; padding: 0 1rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <!-- Company Info -->
            <div>
                <h4 style="color: var(--navy); font-weight: 600; margin-bottom: 1rem;">
                    <i class="fas fa-clock" style="margin-right: 0.5rem;"></i>
                    Sistema de Asistencia
                </h4>
                <p style="color: var(--gray-600); font-size: 0.875rem; line-height: 1.5;">
                    Gestión inteligente de personal y control de asistencias para AlpeFresh.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 style="color: var(--navy); font-weight: 600; margin-bottom: 1rem;">Enlaces Rápidos</h4>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 0.5rem;">
                        <a href="/dashboard.php" style="color: var(--gray-600); text-decoration: none; font-size: 0.875rem;">
                            <i class="fas fa-home" style="width: 20px;"></i> Dashboard
                        </a>
                    </li>
                    <li style="margin-bottom: 0.5rem;">
                        <a href="/asistencias.php" style="color: var(--gray-600); text-decoration: none; font-size: 0.875rem;">
                            <i class="fas fa-clock" style="width: 20px;"></i> Asistencias
                        </a>
                    </li>
                    <li style="margin-bottom: 0.5rem;">
                        <a href="/reportes.php" style="color: var(--gray-600); text-decoration: none; font-size: 0.875rem;">
                            <i class="fas fa-chart-bar" style="width: 20px;"></i> Reportes
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <h4 style="color: var(--navy); font-weight: 600; margin-bottom: 1rem;">Soporte</h4>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 0.5rem;">
                        <a href="/ayuda.php" style="color: var(--gray-600); text-decoration: none; font-size: 0.875rem;">
                            <i class="fas fa-question-circle" style="width: 20px;"></i> Centro de Ayuda
                        </a>
                    </li>
                    <li style="margin-bottom: 0.5rem;">
                        <a href="mailto:soporte@alpefresh.app" style="color: var(--gray-600); text-decoration: none; font-size: 0.875rem;">
                            <i class="fas fa-envelope" style="width: 20px;"></i> Contacto
                        </a>
                    </li>
                    <li style="margin-bottom: 0.5rem;">
                        <span style="color: var(--gray-600); font-size: 0.875rem;">
                            <i class="fas fa-phone" style="width: 20px;"></i> +52 (555) 123-4567
                        </span>
                    </li>
                </ul>
            </div>

            <!-- System Info -->
            <div>
                <h4 style="color: var(--navy); font-weight: 600; margin-bottom: 1rem;">Información</h4>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 0.5rem; color: var(--gray-600); font-size: 0.875rem;">
                        <i class="fas fa-server" style="width: 20px;"></i>
                        Versión 1.0.0
                    </li>
                    <li style="margin-bottom: 0.5rem; color: var(--gray-600); font-size: 0.875rem;">
                        <i class="fas fa-clock" style="width: 20px;"></i>
                        <span id="current-time"><?php echo date('H:i:s'); ?></span>
                    </li>
                    <li style="margin-bottom: 0.5rem; color: var(--gray-600); font-size: 0.875rem;">
                        <i class="fas fa-calendar" style="width: 20px;"></i>
                        <?php echo date('d/m/Y'); ?>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Copyright -->
        <div style="padding-top: 2rem; border-top: 1px solid var(--gray-200); text-align: center;">
            <p style="color: var(--gray-500); font-size: 0.875rem; margin-bottom: 0.5rem;">
                © <?php echo date('Y'); ?> AlpeFresh. Todos los derechos reservados.
            </p>
            <p style="color: var(--gray-400); font-size: 0.75rem;">
                Desarrollado con <i class="fas fa-heart" style="color: var(--gold);"></i> por el equipo de AlpeFresh
            </p>
        </div>
    </div>
</footer>

<script>
// Update footer time every second
setInterval(function() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('es-MX', { hour12: false });
    document.getElementById('current-time').textContent = timeString;
}, 1000);
</script>