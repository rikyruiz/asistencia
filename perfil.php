<?php
/**
 * Perfil de Usuario - Sistema de Asistencia
 * Configuración personal del usuario
 */

session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /login.php');
    exit;
}

require_once 'config/database.php';

$db = db();
$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Procesar actualizaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'update_profile':
                $stmt = $db->prepare("
                    UPDATE usuarios SET
                        nombre = ?,
                        apellidos = ?,
                        telefono = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['apellidos'],
                    $_POST['telefono'],
                    $userId
                ]);
                $message = '✅ Perfil actualizado exitosamente';
                $messageType = 'success';
                break;

            case 'update_mobile_preference':
                $preference = $_POST['mobile_landing_preference'] ?? 'ask';
                if (in_array($preference, ['dashboard', 'clock', 'ask'])) {
                    $stmt = $db->prepare("UPDATE usuarios SET mobile_landing_preference = ? WHERE id = ?");
                    $stmt->execute([$preference, $userId]);
                    $message = '✅ Preferencia móvil actualizada';
                    $messageType = 'success';
                }
                break;

            case 'change_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];

                // Validar contraseña actual
                $stmt = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if (!password_verify($currentPassword, $user['password'])) {
                    $message = '❌ Contraseña actual incorrecta';
                    $messageType = 'error';
                } elseif ($newPassword !== $confirmPassword) {
                    $message = '❌ Las contraseñas nuevas no coinciden';
                    $messageType = 'error';
                } elseif (strlen($newPassword) < 6) {
                    $message = '❌ La contraseña debe tener al menos 6 caracteres';
                    $messageType = 'error';
                } else {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    $message = '✅ Contraseña cambiada exitosamente';
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        error_log("Error actualizando perfil: " . $e->getMessage());
        $message = '❌ Error al actualizar';
        $messageType = 'error';
    }
}

// Obtener datos del usuario
$stmt = $db->prepare("
    SELECT u.*, e.nombre as empresa_nombre
    FROM usuarios u
    LEFT JOIN empresas e ON u.empresa_id = e.id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Obtener estadísticas del usuario
$statsStmt = $db->prepare("
    SELECT
        COUNT(*) as total_asistencias,
        COUNT(CASE WHEN MONTH(entrada) = MONTH(CURDATE()) THEN 1 END) as asistencias_mes
    FROM asistencias
    WHERE usuario_id = ?
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

$page_title = 'Mi Perfil';
$page_subtitle = 'Configuración personal';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Asistencia</title>

    <link rel="icon" href="/favicon.ico">
    <?php include 'includes/styles.php'; ?>

    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--navy) 0%, #004080 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--navy);
        }

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .preference-option {
            padding: 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .preference-option:hover {
            border-color: var(--navy);
            background: var(--gray-50);
        }

        .preference-option input[type="radio"]:checked + label {
            font-weight: 600;
            color: var(--navy);
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container" style="max-width: 1000px; margin: 2rem auto; padding: 0 1rem;">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 1.5rem;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellidos'], 0, 1)); ?>
            </div>
            <div style="flex: 1;">
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?>
                </h1>
                <div style="opacity: 0.9; font-size: 1rem;">
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                </div>
                <div style="opacity: 0.9; font-size: 1rem; margin-top: 0.25rem;">
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($user['empresa_nombre'] ?: 'Sin empresa'); ?> •
                    <i class="fas fa-user-tag"></i> <?php echo ucfirst($user['rol']); ?>
                </div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; font-weight: 700;"><?php echo $stats['asistencias_mes']; ?></div>
                <div style="opacity: 0.9; font-size: 0.875rem;">Asistencias este mes</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid" style="grid-template-columns: 1fr; gap: 2rem;">
            <!-- Información Personal -->
            <div class="section-card">
                <h2 style="color: var(--navy); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-user"></i> Información Personal
                </h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control"
                                   value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                        </div>
                        <div>
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="apellidos" class="form-control"
                                   value="<?php echo htmlspecialchars($user['apellidos']); ?>" required>
                        </div>
                    </div>
                    <div style="margin-top: 1rem;">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control"
                               value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>">
                    </div>
                    <div style="margin-top: 1rem;">
                        <label class="form-label">Código de Empleado</label>
                        <input type="text" class="form-control"
                               value="<?php echo htmlspecialchars($user['codigo_empleado']); ?>" disabled>
                        <small style="color: var(--gray-500);">El código de empleado no puede ser modificado</small>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </form>
            </div>

            <!-- Preferencias Móviles -->
            <div class="section-card">
                <h2 style="color: var(--navy); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-mobile-alt"></i> Preferencias Móviles
                </h2>
                <p style="color: var(--gray-600); font-size: 0.95rem; margin-bottom: 1.5rem;">
                    Configura a dónde quieres ir después de iniciar sesión desde tu móvil
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="update_mobile_preference">

                    <label class="preference-option">
                        <input type="radio" name="mobile_landing_preference" value="dashboard"
                               <?php echo ($user['mobile_landing_preference'] ?? 'ask') === 'dashboard' ? 'checked' : ''; ?>
                               style="margin-right: 0.5rem;">
                        <label style="cursor: pointer;">
                            <i class="fas fa-th-large" style="color: var(--navy);"></i>
                            <strong>Dashboard</strong> - Ver estadísticas y resumen general
                        </label>
                    </label>

                    <label class="preference-option">
                        <input type="radio" name="mobile_landing_preference" value="clock"
                               <?php echo ($user['mobile_landing_preference'] ?? 'ask') === 'clock' ? 'checked' : ''; ?>
                               style="margin-right: 0.5rem;">
                        <label style="cursor: pointer;">
                            <i class="fas fa-fingerprint" style="color: var(--navy);"></i>
                            <strong>Marcar Asistencia</strong> - Ir directo a entrada/salida
                        </label>
                    </label>

                    <label class="preference-option">
                        <input type="radio" name="mobile_landing_preference" value="ask"
                               <?php echo ($user['mobile_landing_preference'] ?? 'ask') === 'ask' ? 'checked' : ''; ?>
                               style="margin-right: 0.5rem;">
                        <label style="cursor: pointer;">
                            <i class="fas fa-question-circle" style="color: var(--navy);"></i>
                            <strong>Preguntarme cada vez</strong> - Mostrar opciones al iniciar sesión
                        </label>
                    </label>

                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Guardar Preferencia
                    </button>
                </form>

                <div style="margin-top: 1rem; padding: 0.75rem; background: var(--gray-50); border-radius: 8px; font-size: 0.875rem; color: var(--gray-600);">
                    <i class="fas fa-info-circle"></i>
                    Esta configuración solo aplica cuando inicias sesión desde dispositivos móviles. En escritorio siempre irás al dashboard.
                </div>
            </div>

            <!-- Cambiar Contraseña -->
            <div class="section-card">
                <h2 style="color: var(--navy); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-key"></i> Cambiar Contraseña
                </h2>
                <form method="POST" id="passwordForm">
                    <input type="hidden" name="action" value="change_password">
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Contraseña Actual</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="new_password" class="form-control"
                               minlength="6" id="newPassword" required>
                        <small style="color: var(--gray-500);">Mínimo 6 caracteres</small>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" name="confirm_password" class="form-control"
                               minlength="6" id="confirmPassword" required>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-lock"></i> Cambiar Contraseña
                    </button>
                </form>
            </div>

            <!-- Estadísticas -->
            <div class="section-card">
                <h2 style="color: var(--navy); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-chart-line"></i> Mis Estadísticas
                </h2>
                <div class="grid" style="grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div style="text-align: center; padding: 1rem; background: var(--gray-50); border-radius: 8px;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--navy);">
                            <?php echo $stats['total_asistencias']; ?>
                        </div>
                        <div style="color: var(--gray-600); font-size: 0.875rem;">Total Asistencias</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: var(--gray-50); border-radius: 8px;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--green-600);">
                            <?php echo $stats['asistencias_mes']; ?>
                        </div>
                        <div style="color: var(--gray-600); font-size: 0.875rem;">Este Mes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validar que las contraseñas coincidan
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas nuevas no coinciden');
            }
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
