<?php
/**
 * NEW Header - Clean rebuild with proper admin navigation
 * Roles: admin, supervisor, empleado
 */

// Ultra-aggressive cache prevention
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0, post-check=0, pre-check=0");
header("Pragma: no-cache");
header("Expires: Mon, 01 Jan 1990 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// Get current page and user role
$current_page = basename($_SERVER['PHP_SELF']);
$userRole = $_SESSION['user_rol'] ?? 'guest';
$userName = $_SESSION['user_nombre'] ?? 'Usuario';
$userPhoto = $_SESSION['user_foto'] ?? '/assets/images/avatar.png';
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];

// Define navigation structure clearly for each role
$navigationStructure = [
    'empleado' => [
        'Inicio' => ['url' => '/dashboard.php', 'icon' => 'fas fa-home'],
        'Asistencias' => ['url' => '/asistencias.php', 'icon' => 'fas fa-clock']
    ],
    'supervisor' => [
        'Inicio' => ['url' => '/dashboard.php', 'icon' => 'fas fa-home'],
        'Asistencias' => ['url' => '/asistencias.php', 'icon' => 'fas fa-clock'],
        'Reportes' => ['url' => '/reportes.php', 'icon' => 'fas fa-chart-bar'],
        'Ubicaciones' => ['url' => '/ubicaciones.php', 'icon' => 'fas fa-map-marked-alt']
    ],
    'admin' => [
        'Inicio' => ['url' => '/dashboard.php', 'icon' => 'fas fa-home'],
        'Asistencias' => ['url' => '/asistencias.php', 'icon' => 'fas fa-clock'],
        'Reportes' => ['url' => '/reportes.php', 'icon' => 'fas fa-chart-bar'],
        'Ubicaciones' => ['url' => '/ubicaciones.php', 'icon' => 'fas fa-map-marked-alt']
    ]
];

// Admin-only dropdown items
$adminDropdownItems = [
    'Monitor de Sesiones' => ['url' => '/admin-monitor.php', 'icon' => 'fas fa-chart-line'],
    'Usuarios Pendientes' => ['url' => '/admin/pending-users.php', 'icon' => 'fas fa-user-clock'],
    'Usuarios' => ['url' => '/usuarios.php', 'icon' => 'fas fa-users'],
    'Empresas' => ['url' => '/empresas.php', 'icon' => 'fas fa-building'],
    'Configuración' => ['url' => '/configuracion.php', 'icon' => 'fas fa-tools']
];

// Profile dropdown items (for all logged users)
$profileDropdownItems = [
    'Mi Perfil' => ['url' => '/perfil.php', 'icon' => 'fas fa-user'],
    'Cambiar Contraseña' => ['url' => '/cambiar-password.php', 'icon' => 'fas fa-key']
];

// Get navigation for current role
$currentNavigation = $navigationStructure[$userRole] ?? [];
?>

<!-- Navigation -->
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="/index.php" class="logo">ASISTENCIA</a>
            <div class="nav-title">
                <h1><?php echo $page_title ?? 'Sistema de Control de Asistencia'; ?></h1>
                <p><?php echo $page_subtitle ?? 'Gestión Inteligente de Personal'; ?></p>
            </div>
        </div>

        <?php if ($isLoggedIn): ?>
        <div class="nav-links">
            <!-- Main Navigation Items -->
            <?php foreach ($currentNavigation as $label => $item): ?>
                <a href="<?php echo $item['url']; ?>" <?php echo $current_page === basename($item['url']) ? 'class="active"' : ''; ?>>
                    <i class="<?php echo $item['icon']; ?>"></i> <?php echo $label; ?>
                </a>
            <?php endforeach; ?>

            <!-- Admin Dropdown (Only for admin role) -->
            <?php if ($userRole === 'admin'): ?>
            <div class="admin-dropdown">
                <button class="admin-dropdown-btn" onclick="toggleAdminDropdown(event)">
                    <i class="fas fa-cog"></i> Admin ▼
                </button>
                <div class="admin-dropdown-content" id="adminDropdownContent">
                    <?php foreach ($adminDropdownItems as $label => $item): ?>
                        <a href="<?php echo $item['url']; ?>" <?php echo $current_page === basename($item['url']) ? 'class="active"' : ''; ?>>
                            <i class="<?php echo $item['icon']; ?>"></i> <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- User/Profile Dropdown (For all logged users) -->
            <div class="user-dropdown">
                <button class="user-dropdown-btn" onclick="toggleUserDropdown(event)">
                    <img src="<?php echo $userPhoto; ?>" alt="Avatar" class="avatar">
                    <?php echo explode(' ', $userName)[0]; ?> ▼
                </button>
                <div class="user-dropdown-content" id="userDropdownContent">
                    <?php foreach ($profileDropdownItems as $label => $item): ?>
                        <a href="<?php echo $item['url']; ?>">
                            <i class="<?php echo $item['icon']; ?>"></i> <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                    <hr>
                    <a href="/logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Not Logged In -->
        <div class="nav-links">
            <a href="/login.php" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </a>
        </div>
        <?php endif; ?>

        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">☰</button>
    </div>
</nav>

<!-- Mobile Menu Overlay -->
<div class="mobile-nav-overlay" id="mobileOverlay" onclick="closeMobileMenu()"></div>

<!-- Mobile Menu -->
<div class="mobile-nav-menu" id="mobileMenu">
    <div class="mobile-nav-header">
        <span>Menú</span>
        <button class="mobile-nav-close" onclick="closeMobileMenu()">×</button>
    </div>

    <?php if ($isLoggedIn): ?>
    <div class="mobile-user-info">
        <img src="<?php echo $userPhoto; ?>" alt="Avatar" class="mobile-avatar">
        <div>
            <div class="mobile-user-name"><?php echo $userName; ?></div>
            <div class="mobile-user-role"><?php echo ucfirst($userRole); ?></div>
        </div>
    </div>

    <div class="mobile-nav-links">
        <!-- Mobile Navigation Items -->
        <?php foreach ($currentNavigation as $label => $item): ?>
            <a href="<?php echo $item['url']; ?>">
                <i class="<?php echo $item['icon']; ?>"></i> <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Mobile Admin Section (Only for admin) -->
    <?php if ($userRole === 'admin'): ?>
    <div class="mobile-admin-section">
        <div class="mobile-admin-header">Administración</div>
        <div class="mobile-admin-links">
            <?php foreach ($adminDropdownItems as $label => $item): ?>
                <a href="<?php echo $item['url']; ?>">
                    <i class="<?php echo $item['icon']; ?>"></i> <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mobile Footer with Profile Links -->
    <div class="mobile-nav-footer">
        <?php foreach ($profileDropdownItems as $label => $item): ?>
            <a href="<?php echo $item['url']; ?>" class="mobile-footer-link">
                <i class="<?php echo $item['icon']; ?>"></i> <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
        <a href="/logout.php" class="mobile-footer-link logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </div>
    <?php else: ?>
    <!-- Mobile Not Logged In -->
    <div class="mobile-nav-links">
        <a href="/index.php">
            <i class="fas fa-home"></i> Inicio
        </a>
        <a href="/login.php">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
// Toggle functions
function toggleAdminDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('adminDropdownContent');
    const userDropdown = document.getElementById('userDropdownContent');
    userDropdown.style.display = 'none'; // Close user dropdown
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function toggleUserDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('userDropdownContent');
    const adminDropdown = document.getElementById('adminDropdownContent');
    if (adminDropdown) adminDropdown.style.display = 'none'; // Close admin dropdown if exists
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileOverlay');
    menu.classList.add('active');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileOverlay');
    menu.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.admin-dropdown') && !e.target.closest('.user-dropdown')) {
        const adminDropdown = document.getElementById('adminDropdownContent');
        const userDropdown = document.getElementById('userDropdownContent');
        if (adminDropdown) adminDropdown.style.display = 'none';
        if (userDropdown) userDropdown.style.display = 'none';
    }
});
</script>

<!-- Enhanced Dropdown Script if exists -->
<?php if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/js/dropdown.js')): ?>
<script src="/assets/js/dropdown.js"></script>
<?php endif; ?>

<!-- DEBUG INFO (Remove in production) -->
<?php if (isset($_GET['debug'])): ?>
<div style="position: fixed; bottom: 0; right: 0; background: #000; color: #fff; padding: 10px; font-size: 12px; z-index: 9999;">
    Generated: <?php echo date('Y-m-d H:i:s'); ?><br>
    Role: <?php echo $userRole; ?><br>
    User ID: <?php echo $_SESSION['user_id'] ?? 'N/A'; ?><br>
    Name: <?php echo $userName; ?>
</div>
<?php endif; ?>