<?php
session_start();

// Aggressive cache prevention
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: text/html; charset=UTF-8');

require_once 'config/database.php';

// Get user from database
$db = db();
$stmt = $db->prepare("SELECT id, email, nombre, apellidos, rol, empresa_id, activo FROM usuarios WHERE email = ?");
$stmt->execute(['ricardo.ruiz.o@gmail.com']);
$dbUser = $stmt->fetch();

// Get session data
$sessionUser = [
    'id' => $_SESSION['user_id'] ?? 'NOT SET',
    'email' => $_SESSION['user_email'] ?? 'NOT SET',
    'nombre' => $_SESSION['user_nombre'] ?? 'NOT SET',
    'rol' => $_SESSION['user_rol'] ?? 'NOT SET'
];

// Define what the header shows (from header.php)
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

$adminDropdownItems = [
    'Monitor de Sesiones' => '/admin-monitor.php',
    'Usuarios Pendientes' => '/admin/pending-users.php',
    'Usuarios' => '/usuarios.php',
    'Empresas' => '/empresas.php',
    'Configuración' => '/configuracion.php'
];

$profileDropdownItems = [
    'Mi Perfil' => '/perfil.php',
    'Cambiar Contraseña' => '/cambiar-password.php'
];

// Determine what SHOULD be shown
$shouldShowNavigation = $navigationStructure[$dbUser['rol']] ?? [];
$shouldShowAdminDropdown = ($dbUser['rol'] === 'admin');

// Determine what IS being shown (based on session)
$currentlyShowingNavigation = $navigationStructure[$sessionUser['rol']] ?? [];
$currentlyShowingAdminDropdown = ($sessionUser['rol'] === 'admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Verification - ricardo.ruiz.o@gmail.com</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #003366;
            color: white;
        }
        .match { color: #16a34a; font-weight: bold; }
        .mismatch { color: #dc2626; font-weight: bold; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
        .success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 20px 0; }
        .menu-item { padding: 8px; background: #f9f9f9; margin: 5px 0; border-radius: 4px; }
        .should-show { border-left: 4px solid #10b981; }
        .should-not-show { border-left: 4px solid #dc2626; }
    </style>
</head>
<body>
    <h1>🔍 Menu Verification for ricardo.ruiz.o@gmail.com</h1>

    <div class="box">
        <h2>Database Record:</h2>
        <table>
            <tr>
                <th>Field</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>ID</td>
                <td><?php echo $dbUser['id']; ?></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><?php echo htmlspecialchars($dbUser['email']); ?></td>
            </tr>
            <tr>
                <td>Nombre</td>
                <td><?php echo htmlspecialchars($dbUser['nombre'] . ' ' . $dbUser['apellidos']); ?></td>
            </tr>
            <tr>
                <td><strong>ROL (Database)</strong></td>
                <td><strong style="color: #003366; font-size: 18px;"><?php echo strtoupper($dbUser['rol']); ?></strong></td>
            </tr>
            <tr>
                <td>Empresa ID</td>
                <td><?php echo $dbUser['empresa_id']; ?></td>
            </tr>
            <tr>
                <td>Activo</td>
                <td><?php echo $dbUser['activo'] ? '✅ Yes' : '❌ No'; ?></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Current Session Data:</h2>
        <table>
            <tr>
                <th>Variable</th>
                <th>Value</th>
                <th>Match</th>
            </tr>
            <tr>
                <td>user_id</td>
                <td><?php echo $sessionUser['id']; ?></td>
                <td class="<?php echo ($sessionUser['id'] == $dbUser['id']) ? 'match' : 'mismatch'; ?>">
                    <?php echo ($sessionUser['id'] == $dbUser['id']) ? '✅ MATCH' : '❌ MISMATCH'; ?>
                </td>
            </tr>
            <tr>
                <td>user_nombre</td>
                <td><?php echo htmlspecialchars($sessionUser['nombre']); ?></td>
                <td class="<?php echo ($sessionUser['nombre'] == ($dbUser['nombre'] . ' ' . $dbUser['apellidos'])) ? 'match' : 'mismatch'; ?>">
                    <?php echo ($sessionUser['nombre'] == ($dbUser['nombre'] . ' ' . $dbUser['apellidos'])) ? '✅ MATCH' : '❌ MISMATCH'; ?>
                </td>
            </tr>
            <tr>
                <td><strong>user_rol</strong></td>
                <td><strong style="color: #dc2626; font-size: 18px;"><?php echo strtoupper($sessionUser['rol']); ?></strong></td>
                <td class="<?php echo ($sessionUser['rol'] == $dbUser['rol']) ? 'match' : 'mismatch'; ?>">
                    <?php echo ($sessionUser['rol'] == $dbUser['rol']) ? '✅ MATCH' : '❌ MISMATCH'; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>What This User SHOULD See (Based on DB Role: <?php echo $dbUser['rol']; ?>):</h2>

        <h3>Main Navigation:</h3>
        <?php foreach ($shouldShowNavigation as $label => $item): ?>
            <div class="menu-item should-show">
                <i class="<?php echo $item['icon']; ?>"></i> <strong><?php echo $label; ?></strong> → <?php echo $item['url']; ?>
            </div>
        <?php endforeach; ?>

        <h3>Admin Dropdown:</h3>
        <?php if ($shouldShowAdminDropdown): ?>
            <?php foreach ($adminDropdownItems as $label => $url): ?>
                <div class="menu-item should-show">
                    <i class="fas fa-cog"></i> <strong><?php echo $label; ?></strong> → <?php echo $url; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="menu-item should-not-show">
                ❌ Admin dropdown should NOT be visible
            </div>
        <?php endif; ?>

        <h3>Profile Dropdown (Always visible for logged users):</h3>
        <?php foreach ($profileDropdownItems as $label => $url): ?>
            <div class="menu-item should-show">
                <i class="fas fa-user"></i> <strong><?php echo $label; ?></strong> → <?php echo $url; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="box">
        <h2>What Is CURRENTLY Being Shown (Based on Session Role: <?php echo $sessionUser['rol']; ?>):</h2>

        <h3>Main Navigation:</h3>
        <?php if (!empty($currentlyShowingNavigation)): ?>
            <?php foreach ($currentlyShowingNavigation as $label => $item): ?>
                <div class="menu-item <?php echo isset($shouldShowNavigation[$label]) ? 'should-show' : 'should-not-show'; ?>">
                    <i class="<?php echo $item['icon']; ?>"></i> <strong><?php echo $label; ?></strong> → <?php echo $item['url']; ?>
                    <?php if (!isset($shouldShowNavigation[$label])): ?>
                        <strong style="color: #dc2626;">← SHOULD NOT BE VISIBLE!</strong>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No navigation items (user not logged in or invalid role)</p>
        <?php endif; ?>

        <h3>Admin Dropdown:</h3>
        <?php if ($currentlyShowingAdminDropdown): ?>
            <div class="menu-item <?php echo $shouldShowAdminDropdown ? 'should-show' : 'should-not-show'; ?>">
                <i class="fas fa-cog"></i> <strong>Admin Dropdown</strong>
                <?php if (!$shouldShowAdminDropdown): ?>
                    <strong style="color: #dc2626;">← SHOULD NOT BE VISIBLE!</strong>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="menu-item should-show">
                ✅ Admin dropdown is NOT being shown (correct for <?php echo $dbUser['rol']; ?>)
            </div>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>📊 Diagnosis:</h2>

        <?php if ($sessionUser['rol'] == $dbUser['rol']): ?>
            <div class="success">
                <h3>✅ SESSION IS CORRECT!</h3>
                <p>Session role matches database role: <strong><?php echo $dbUser['rol']; ?></strong></p>
                <p>The header should be showing the correct menu items.</p>
                <p><strong>If you see wrong items, it's browser cache!</strong></p>
                <ol>
                    <li>Press Ctrl+Shift+R to hard refresh</li>
                    <li>Or use Incognito mode</li>
                    <li>Or clear browser cache completely</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="warning">
                <h3>⚠️ SESSION MISMATCH!</h3>
                <p>Database says role: <strong><?php echo $dbUser['rol']; ?></strong></p>
                <p>Session says role: <strong><?php echo $sessionUser['rol']; ?></strong></p>
                <p><strong>Solution: Logout and login again!</strong></p>
                <a href="/logout.php" style="display: inline-block; padding: 10px 20px; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; margin-top: 10px;">
                    🚪 Logout Now
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px;">
        <a href="/dashboard.php" style="color: #003366;">← Back to Dashboard</a>
    </div>
</body>
</html>
