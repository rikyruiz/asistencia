<?php
/**
 * Panel de Administración - Usuarios Pendientes
 * Sistema de Asistencia AlpeFresh
 */

session_start();

// Verificar autenticación y rol de admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /login.php');
    exit;
}

if ($_SESSION['user_rol'] !== 'admin') {
    header('Location: /dashboard.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/email_service.php';

$page_title = 'Usuarios Pendientes';
$page_subtitle = 'Gestión de Solicitudes de Registro';

$db = db();
$message = '';
$messageType = '';

// Procesar aprobación/rechazo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? 0;
    $action = $_POST['action'] ?? '';

    if ($userId && in_array($action, ['approve', 'reject'])) {
        try {
            // Obtener información del usuario
            $userStmt = $db->prepare("
                SELECT email, nombre, apellidos
                FROM usuarios
                WHERE id = ? AND estado_aprobacion = 'pendiente'
            ");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();

            if ($user) {
                if ($action === 'approve') {
                    // Aprobar usuario
                    $updateStmt = $db->prepare("
                        UPDATE usuarios
                        SET estado_aprobacion = 'aprobado',
                            activo = 1
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$userId]);

                    // Enviar email de aprobación
                    $emailService = new EmailService();
                    $emailService->sendAccountApprovedEmail(
                        $user['email'],
                        $user['nombre'] . ' ' . $user['apellidos']
                    );

                    $message = 'Usuario aprobado exitosamente. Se ha enviado una notificación por email.';
                    $messageType = 'success';
                } else {
                    // Rechazar usuario (eliminar registro)
                    $deleteStmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
                    $deleteStmt->execute([$userId]);

                    $message = 'Solicitud de registro rechazada y eliminada.';
                    $messageType = 'info';
                }
            }
        } catch (Exception $e) {
            error_log("Error procesando usuario: " . $e->getMessage());
            $message = 'Error al procesar la solicitud.';
            $messageType = 'error';
        }
    }
}

// Obtener usuarios pendientes
$pendingStmt = $db->query("
    SELECT u.*, e.nombre as empresa_nombre
    FROM usuarios u
    LEFT JOIN empresas e ON u.empresa_id = e.id
    WHERE u.estado_aprobacion = 'pendiente'
    ORDER BY u.fecha_registro DESC
");
$pendingUsers = $pendingStmt->fetchAll();

// Obtener estadísticas
$stats = [];
$statsStmt = $db->query("
    SELECT
        COUNT(CASE WHEN estado_aprobacion = 'pendiente' THEN 1 END) as pendientes,
        COUNT(CASE WHEN estado_aprobacion = 'aprobado' THEN 1 END) as aprobados,
        COUNT(CASE WHEN estado_aprobacion = 'rechazado' THEN 1 END) as rechazados,
        COUNT(CASE WHEN DATE(fecha_registro) = CURDATE() THEN 1 END) as hoy
    FROM usuarios
");
$stats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Asistencia AlpeFresh</title>

    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico">

    <!-- Estilos del marketplace -->
    <!-- Common head elements (Font Awesome, Google Fonts, Styles) -->
    <?php include '../includes/head-common.php'; ?>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container" style="max-width: 1400px; margin: 2rem auto; padding: 0 1rem;">
        <!-- Page Header -->
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; font-weight: 700; color: var(--navy); margin-bottom: 0.5rem;">
                <i class="fas fa-user-clock" style="margin-right: 0.5rem;"></i>
                Usuarios Pendientes
            </h1>
            <p style="color: var(--gray-600); font-size: 1rem;">
                Gestión de solicitudes de registro y aprobación de nuevos usuarios
            </p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 1.5rem;">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-4" style="gap: 1.5rem; margin-bottom: 2rem;">
            <div class="stat-card" style="border-left-color: var(--gold);">
                <div class="stat-value" style="color: #f59e0b;">
                    <?php echo $stats['pendientes']; ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-clock" style="margin-right: 0.25rem;"></i>
                    Pendientes
                </div>
            </div>

            <div class="stat-card" style="border-left-color: #10b981;">
                <div class="stat-value" style="color: #10b981;">
                    <?php echo $stats['aprobados']; ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-check-circle" style="margin-right: 0.25rem;"></i>
                    Aprobados
                </div>
            </div>

            <div class="stat-card" style="border-left-color: #3b82f6;">
                <div class="stat-value" style="color: #3b82f6;">
                    <?php echo $stats['hoy']; ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-calendar-day" style="margin-right: 0.25rem;"></i>
                    Registros Hoy
                </div>
            </div>

            <div class="stat-card" style="border-left-color: var(--navy);">
                <div class="stat-value">
                    <?php echo $stats['aprobados'] + $stats['pendientes']; ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-users" style="margin-right: 0.25rem;"></i>
                    Total Usuarios
                </div>
            </div>
        </div>

        <?php if (empty($pendingUsers)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <i class="fas fa-user-check" style="font-size: 4rem; color: var(--gray-300); margin-bottom: 1rem; display: block;"></i>
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">
                No hay usuarios pendientes
            </h3>
            <p style="color: var(--gray-500);">
                Todas las solicitudes han sido procesadas
            </p>
        </div>
        <?php else: ?>
        <!-- Tabla de usuarios pendientes -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Empresa</th>
                            <th>Fecha Registro</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingUsers as $user): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-500"></i>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($user['codigo_empleado']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>"
                                       class="text-blue-600 hover:underline">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['telefono'] ?: 'No proporcionado'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['empresa_nombre'] ?: 'Principal'); ?>
                                </td>
                                <td>
                                    <div>
                                        <?php
                                        $fecha = new DateTime($user['fecha_registro']);
                                        echo $fecha->format('d/m/Y');
                                        ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo $fecha->format('H:i'); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex gap-2 justify-center">
                                        <form method="POST" action="" style="display: inline-block;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit"
                                                    class="btn"
                                                    style="background-color: #10b981; color: white; padding: 0.375rem 0.75rem; font-size: 0.875rem;"
                                                    onclick="return confirm('¿Aprobar este usuario?')">
                                                <i class="fas fa-check"></i>
                                                Aprobar
                                            </button>
                                        </form>

                                        <form method="POST" action="" style="display: inline-block;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit"
                                                    class="btn"
                                                    style="background-color: #ef4444; color: white; padding: 0.375rem 0.75rem; font-size: 0.875rem;"
                                                    onclick="return confirm('¿Rechazar y eliminar este registro?')">
                                                <i class="fas fa-times"></i>
                                                Rechazar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Usuarios Aprobados Recientemente -->
    <?php
    $recentStmt = $db->query("
        SELECT u.*, e.nombre as empresa_nombre
        FROM usuarios u
        LEFT JOIN empresas e ON u.empresa_id = e.id
        WHERE u.estado_aprobacion = 'aprobado'
          AND u.fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY u.fecha_registro DESC
        LIMIT 10
    ");
    $recentUsers = $recentStmt->fetchAll();
    ?>

        <?php if (!empty($recentUsers)): ?>
        <div style="margin-top: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-800); margin-bottom: 1rem;">
                <i class="fas fa-history" style="margin-right: 0.5rem; color: var(--navy);"></i>
                Aprobados Recientemente (Últimos 7 días)
            </h2>
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Empresa</th>
                                <th>Fecha Registro</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td>
                                        <div class="font-semibold">
                                            <?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($user['empresa_nombre'] ?: 'Principal'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $fecha = new DateTime($user['fecha_registro']);
                                        echo $fecha->format('d/m/Y H:i');
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i>
                                            Activo
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>