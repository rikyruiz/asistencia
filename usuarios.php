<?php
/**
 * Gestión de Usuarios - Sistema de Asistencia
 * Panel para administrar todos los usuarios del sistema
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

require_once 'config/database.php';
require_once 'includes/email_service.php';

$page_title = 'Gestión de Usuarios';
$page_subtitle = 'Administración de usuarios del sistema';

$db = db();
$message = '';
$messageType = '';

// Procesar acciones (activar/desactivar, cambiar rol, eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? 0;

    if ($userId && $userId != $_SESSION['user_id']) { // No permitir auto-modificación
        try {
            switch ($action) {
                case 'toggle_active':
                    $stmt = $db->prepare("UPDATE usuarios SET activo = NOT activo WHERE id = ?");
                    $stmt->execute([$userId]);
                    $message = 'Estado del usuario actualizado';
                    $messageType = 'success';
                    break;

                case 'change_role':
                    $newRole = $_POST['new_role'] ?? 'empleado';
                    if (in_array($newRole, ['admin', 'supervisor', 'empleado'])) {
                        $stmt = $db->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
                        $stmt->execute([$newRole, $userId]);
                        $message = 'Rol actualizado exitosamente';
                        $messageType = 'success';
                    }
                    break;

                case 'delete':
                    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
                    $stmt->execute([$userId]);
                    $message = 'Usuario eliminado';
                    $messageType = 'info';
                    break;

                case 'reset_password':
                    // Generar nueva contraseña temporal
                    $tempPassword = 'Temp' . rand(1000, 9999) . '!';
                    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

                    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);

                    // Obtener email del usuario
                    $userStmt = $db->prepare("SELECT email, nombre FROM usuarios WHERE id = ?");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch();

                    $message = "Contraseña restablecida. Nueva contraseña temporal: $tempPassword";
                    $messageType = 'success';
                    break;

                case 'set_pin':
                case 'reset_pin':
                    // Generar nuevo PIN de 6 dígitos
                    $newPin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $hashedPin = password_hash($newPin, PASSWORD_DEFAULT);

                    $stmt = $db->prepare("UPDATE usuarios SET pin = ?, pin_intentos = 0, pin_bloqueado_hasta = NULL WHERE id = ?");
                    $stmt->execute([$hashedPin, $userId]);

                    // Obtener info del usuario
                    $userStmt = $db->prepare("SELECT codigo_empleado, nombre FROM usuarios WHERE id = ?");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch();

                    $message = "PIN " . ($action === 'set_pin' ? 'generado' : 'restablecido') . " exitosamente. Nuevo PIN: <strong>$newPin</strong> (Código empleado: {$user['codigo_empleado']})";
                    $messageType = 'success';
                    break;

                case 'edit_user':
                    // Obtener datos del formulario
                    $nombre = trim($_POST['nombre'] ?? '');
                    $apellidos = trim($_POST['apellidos'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $telefono = trim($_POST['telefono'] ?? '');
                    $codigoEmpleado = trim($_POST['codigo_empleado'] ?? '');
                    $empresaId = $_POST['empresa_id'] ?? null;
                    $departamentoId = $_POST['departamento_id'] ?? null;

                    // Validar campos requeridos
                    if (empty($nombre) || empty($apellidos) || empty($email) || empty($codigoEmpleado)) {
                        $message = 'Nombre, apellidos, email y código de empleado son requeridos';
                        $messageType = 'error';
                        break;
                    }

                    // Validar formato de email
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $message = 'Email no válido';
                        $messageType = 'error';
                        break;
                    }

                    // Verificar que el email no esté en uso por otro usuario
                    $checkEmailStmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                    $checkEmailStmt->execute([$email, $userId]);
                    if ($checkEmailStmt->fetch()) {
                        $message = 'El email ya está en uso por otro usuario';
                        $messageType = 'error';
                        break;
                    }

                    // Verificar que el código de empleado no esté en uso por otro usuario
                    $checkCodigoStmt = $db->prepare("SELECT id FROM usuarios WHERE codigo_empleado = ? AND id != ?");
                    $checkCodigoStmt->execute([$codigoEmpleado, $userId]);
                    if ($checkCodigoStmt->fetch()) {
                        $message = 'El código de empleado ya está en uso';
                        $messageType = 'error';
                        break;
                    }

                    // Actualizar usuario
                    $stmt = $db->prepare("
                        UPDATE usuarios
                        SET nombre = ?, apellidos = ?, email = ?, telefono = ?,
                            codigo_empleado = ?, empresa_id = ?, departamento_id = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $nombre, $apellidos, $email, $telefono,
                        $codigoEmpleado, $empresaId, $departamentoId, $userId
                    ]);

                    $message = 'Usuario actualizado exitosamente';
                    $messageType = 'success';
                    break;
            }
        } catch (Exception $e) {
            error_log("Error en gestión de usuarios: " . $e->getMessage());
            $message = 'Error al procesar la solicitud';
            $messageType = 'error';
        }
    } else if ($action === 'add_user') {
        // Agregar nuevo usuario (no requiere user_id)
        try {
            $nombre = trim($_POST['nombre'] ?? '');
            $apellidos = trim($_POST['apellidos'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $codigoEmpleado = trim($_POST['codigo_empleado'] ?? '');
            $empresaId = $_POST['empresa_id'] ?? null;
            $departamentoId = $_POST['departamento_id'] ?? null;
            $rol = $_POST['rol'] ?? 'empleado';
            $pin = trim($_POST['pin'] ?? '');
            $password = $_POST['password'] ?? '';

            // Validaciones
            if (empty($nombre) || empty($apellidos) || empty($email) || empty($codigoEmpleado)) {
                $message = 'Nombre, apellidos, email y código de empleado son requeridos';
                $messageType = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Email no válido';
                $messageType = 'error';
            } elseif (!empty($pin) && !preg_match('/^\d{6}$/', $pin)) {
                $message = 'El PIN debe ser exactamente 6 dígitos numéricos';
                $messageType = 'error';
            } elseif (empty($password) || strlen($password) < 6) {
                $message = 'La contraseña debe tener al menos 6 caracteres';
                $messageType = 'error';
            } else {
                // Verificar que el email no exista
                $checkEmailStmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
                $checkEmailStmt->execute([$email]);
                if ($checkEmailStmt->fetch()) {
                    $message = 'El email ya está en uso';
                    $messageType = 'error';
                } else {
                    // Verificar que el código de empleado no exista
                    $checkCodigoStmt = $db->prepare("SELECT id FROM usuarios WHERE codigo_empleado = ?");
                    $checkCodigoStmt->execute([$codigoEmpleado]);
                    if ($checkCodigoStmt->fetch()) {
                        $message = 'El código de empleado ya está en uso';
                        $messageType = 'error';
                    } else {
                        // Si no se proporcionó empresa, usar la del admin
                        if (!$empresaId) {
                            $adminEmpresaStmt = $db->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
                            $adminEmpresaStmt->execute([$_SESSION['user_id']]);
                            $adminData = $adminEmpresaStmt->fetch();
                            $empresaId = $adminData['empresa_id'];
                        }

                        // Insertar usuario
                        $insertStmt = $db->prepare("
                            INSERT INTO usuarios (
                                empresa_id, departamento_id, codigo_empleado, email, password,
                                pin, nombre, apellidos, telefono, rol, activo,
                                estado_aprobacion, fecha_registro, aprobado_por, fecha_aprobacion
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'aprobado', NOW(), ?, NOW()
                            )
                        ");

                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $hashedPin = !empty($pin) ? password_hash($pin, PASSWORD_DEFAULT) : null;

                        $insertStmt->execute([
                            $empresaId,
                            $departamentoId,
                            $codigoEmpleado,
                            $email,
                            $hashedPassword,
                            $hashedPin,
                            $nombre,
                            $apellidos,
                            $telefono,
                            $rol,
                            $_SESSION['user_id']
                        ]);

                        if (!empty($pin)) {
                            $message = "Usuario creado exitosamente. PIN: <strong>$pin</strong> (Código: $codigoEmpleado)";
                        } else {
                            $message = "Usuario creado exitosamente. Código: $codigoEmpleado";
                        }
                        $messageType = 'success';
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error al agregar usuario: " . $e->getMessage());
            $message = 'Error al crear el usuario';
            $messageType = 'error';
        }
    }
}

// Filtros
$searchTerm = $_GET['search'] ?? '';
$filterRole = $_GET['role'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterEmpresa = $_GET['empresa'] ?? '';

// Construir query con filtros
$query = "
    SELECT u.*, e.nombre as empresa_nombre,
           (SELECT COUNT(*) FROM asistencias WHERE usuario_id = u.id) as total_asistencias,
           CASE WHEN u.pin IS NOT NULL THEN 1 ELSE 0 END as has_pin
    FROM usuarios u
    LEFT JOIN empresas e ON u.empresa_id = e.id
    WHERE 1=1
";

$params = [];

if ($searchTerm) {
    $query .= " AND (u.nombre LIKE ? OR u.apellidos LIKE ? OR u.email LIKE ? OR u.codigo_empleado LIKE ?)";
    $searchWildcard = "%$searchTerm%";
    $params = array_merge($params, [$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard]);
}

if ($filterRole) {
    $query .= " AND u.rol = ?";
    $params[] = $filterRole;
}

if ($filterStatus !== '') {
    $query .= " AND u.activo = ?";
    $params[] = $filterStatus;
}

if ($filterEmpresa) {
    $query .= " AND u.empresa_id = ?";
    $params[] = $filterEmpresa;
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Obtener empresas para el filtro
$empresasStmt = $db->query("SELECT id, nombre FROM empresas WHERE activa = 1 ORDER BY nombre");
$empresas = $empresasStmt->fetchAll();

// Obtener departamentos para el modal
$departamentosStmt = $db->query("SELECT id, nombre, empresa_id FROM departamentos ORDER BY nombre");
$departamentos = $departamentosStmt->fetchAll();

// Estadísticas
$statsStmt = $db->query("
    SELECT
        COUNT(*) as total,
        COUNT(CASE WHEN activo = 1 THEN 1 END) as activos,
        COUNT(CASE WHEN rol = 'admin' THEN 1 END) as admins,
        COUNT(CASE WHEN rol = 'supervisor' THEN 1 END) as supervisores,
        COUNT(CASE WHEN rol = 'empleado' THEN 1 END) as empleados,
        COUNT(CASE WHEN pin IS NOT NULL THEN 1 END) as con_pin
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

    <!-- Common head elements (Font Awesome, Google Fonts, Styles) -->
    <?php include __DIR__ . '/includes/head-common.php'; ?>

    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background-color: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--gray-600);
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .role-admin {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .role-supervisor {
            background-color: #fef3c7;
            color: #92400e;
        }

        .role-empleado {
            background-color: #e5e7eb;
            color: #374151;
        }

        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 480px) {
            .grid {
                grid-template-columns: 1fr !important;
            }
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-icon {
            padding: 0.375rem 0.625rem;
            font-size: 0.875rem;
            border-radius: 6px;
            border: 1px solid var(--gray-300);
            background: white;
            color: var(--gray-600);
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-icon:hover {
            background: var(--gray-50);
            border-color: var(--navy);
            color: var(--navy);
        }

        .btn-icon.danger {
            border-color: #fca5a5;
            color: #dc2626;
            background: #fef2f2;
        }

        .btn-icon.danger:hover {
            background: #fee2e2;
            border-color: #ef4444;
            color: #b91c1c;
        }

        .btn-icon.success {
            border-color: #86efac;
            color: #16a34a;
            background: #f0fdf4;
        }

        .btn-icon.success:hover {
            background: #dcfce7;
            border-color: #22c55e;
            color: #15803d;
        }

        .btn-icon.warning {
            border-color: #fcd34d;
            color: #d97706;
            background: #fef3c7;
        }

        .btn-icon.warning:hover {
            background: #fed7aa;
            border-color: #f59e0b;
            color: #c2410c;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container" style="max-width: 1400px; margin: 2rem auto; padding: 0 1rem;">
        <!-- Page Header -->
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; font-weight: 700; color: var(--navy); margin-bottom: 0.5rem;">
                <i class="fas fa-users" style="margin-right: 0.5rem;"></i>
                Gestión de Usuarios
            </h1>
            <p style="color: var(--gray-600); font-size: 1rem;">
                Administra todos los usuarios del sistema
            </p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 1.5rem;">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="grid" style="grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Usuarios</div>
            </div>
            <div class="stat-card" style="border-left-color: #10b981;">
                <div class="stat-value" style="color: #10b981;"><?php echo $stats['activos']; ?></div>
                <div class="stat-label">Activos</div>
            </div>
            <div class="stat-card" style="border-left-color: #8b5cf6;">
                <div class="stat-value" style="color: #8b5cf6;"><?php echo $stats['con_pin']; ?></div>
                <div class="stat-label">Con PIN</div>
            </div>
            <div class="stat-card" style="border-left-color: #3b82f6;">
                <div class="stat-value" style="color: #3b82f6;"><?php echo $stats['admins']; ?></div>
                <div class="stat-label">Administradores</div>
            </div>
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <div class="stat-value" style="color: #f59e0b;"><?php echo $stats['supervisores']; ?></div>
                <div class="stat-label">Supervisores</div>
            </div>
            <div class="stat-card" style="border-left-color: #6b7280;">
                <div class="stat-value" style="color: #6b7280;"><?php echo $stats['empleados']; ?></div>
                <div class="stat-label">Empleados</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div>
                        <label class="form-label">Buscar</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Nombre, email, código..."
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div>
                        <label class="form-label">Rol</label>
                        <select name="role" class="form-control">
                            <option value="">Todos</option>
                            <option value="admin" <?php echo $filterRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="supervisor" <?php echo $filterRole === 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            <option value="empleado" <?php echo $filterRole === 'empleado' ? 'selected' : ''; ?>>Empleado</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-control">
                            <option value="">Todos</option>
                            <option value="1" <?php echo $filterStatus === '1' ? 'selected' : ''; ?>>Activos</option>
                            <option value="0" <?php echo $filterStatus === '0' ? 'selected' : ''; ?>>Inactivos</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Empresa</label>
                        <select name="empresa" class="form-control">
                            <option value="">Todas</option>
                            <?php foreach ($empresas as $empresa): ?>
                            <option value="<?php echo $empresa['id']; ?>"
                                    <?php echo $filterEmpresa == $empresa['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($empresa['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="usuarios.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Empresa</th>
                            <th>Rol</th>
                            <th style="text-align: center;">PIN</th>
                            <th>Asistencias</th>
                            <th>Estado</th>
                            <th>Último Acceso</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <?php if ($user['foto_url']): ?>
                                        <img src="<?php echo htmlspecialchars($user['foto_url']); ?>"
                                             alt="Avatar" class="user-avatar">
                                    <?php else: ?>
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellidos'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 600; color: var(--gray-900);">
                                            <?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--gray-500);">
                                            <?php echo htmlspecialchars($user['codigo_empleado']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>"
                                   style="color: var(--blue-600); text-decoration: none;">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($user['empresa_nombre'] ?: 'Sin empresa'); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['rol']; ?>">
                                    <?php echo ucfirst($user['rol']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($user['has_pin']): ?>
                                    <span class="badge badge-success" title="PIN configurado">
                                        <i class="fas fa-check-circle"></i> Sí
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning" title="Sin PIN">
                                        <i class="fas fa-times-circle"></i> No
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight: 600;"><?php echo $user['total_asistencias']; ?></span>
                            </td>
                            <td>
                                <?php if ($user['activo']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['ultimo_acceso']): ?>
                                    <?php
                                    $ultimo = new DateTime($user['ultimo_acceso']);
                                    echo $ultimo->format('d/m/Y H:i');
                                    ?>
                                <?php else: ?>
                                    <span style="color: var(--gray-400);">Nunca</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons" style="justify-content: center;">
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <!-- Edit User -->
                                        <button type="button"
                                                class="btn-icon"
                                                onclick='openEditModal(<?php echo json_encode($user); ?>)'
                                                title="Editar información del usuario">
                                            <i class="fas fa-edit"></i>
                                            Editar
                                        </button>

                                        <!-- Toggle Active Status -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <button type="submit"
                                                    class="btn-icon <?php echo $user['activo'] ? 'success' : 'warning'; ?>"
                                                    title="<?php echo $user['activo'] ? 'Desactivar usuario' : 'Activar usuario'; ?>">
                                                <i class="fas fa-power-off"></i>
                                                <?php echo $user['activo'] ? 'ON' : 'OFF'; ?>
                                            </button>
                                        </form>

                                        <!-- Change Role Dropdown -->
                                        <select onchange="if(this.value) changeRole(<?php echo $user['id']; ?>, this.value)"
                                                class="btn-icon" title="Cambiar rol"
                                                style="padding-right: 1.5rem; min-width: 90px;">
                                            <option value="">Rol...</option>
                                            <option value="admin">→ Admin</option>
                                            <option value="supervisor">→ Supervisor</option>
                                            <option value="empleado">→ Empleado</option>
                                        </select>

                                        <!-- Reset Password -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="reset_password">
                                            <button type="submit" class="btn-icon warning"
                                                    title="Generar nueva contraseña temporal"
                                                    onclick="return confirm('¿Generar nueva contraseña temporal para este usuario?')">
                                                <i class="fas fa-key"></i>
                                                Reset
                                            </button>
                                        </form>

                                        <!-- Set/Reset PIN -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="<?php echo $user['has_pin'] ? 'reset_pin' : 'set_pin'; ?>">
                                            <button type="submit" class="btn-icon <?php echo $user['has_pin'] ? 'warning' : 'success'; ?>"
                                                    title="<?php echo $user['has_pin'] ? 'Generar nuevo PIN' : 'Configurar PIN'; ?>"
                                                    onclick="return confirm('<?php echo $user['has_pin'] ? '¿Generar nuevo PIN de 6 dígitos?' : '¿Configurar PIN de 6 dígitos?'; ?>')">
                                                <i class="fas fa-hashtag"></i>
                                                <?php echo $user['has_pin'] ? 'Reset PIN' : 'Set PIN'; ?>
                                            </button>
                                        </form>

                                        <!-- Delete User -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn-icon danger"
                                                    title="Eliminar usuario permanentemente"
                                                    onclick="return confirm('⚠️ ¿Eliminar este usuario permanentemente?\n\nEsta acción no se puede deshacer.')">
                                                <i class="fas fa-trash-alt"></i>
                                                Eliminar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400); font-size: 0.875rem;">Tu cuenta</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add New User Button -->
        <div style="margin-top: 2rem; text-align: center;">
            <button type="button" onclick="openAddModal()" class="btn btn-accent">
                <i class="fas fa-user-plus"></i>
                Agregar Nuevo Usuario
            </button>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" style="
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        justify-content: center;
        align-items: center;
        overflow-y: auto;
        padding: 1rem;
    ">
        <div style="
            background: white;
            border-radius: 16px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        ">
            <!-- Modal Header -->
            <div style="
                padding: 1.5rem;
                border-bottom: 1px solid var(--gray-200);
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: linear-gradient(135deg, var(--green-600) 0%, var(--green-700) 100%);
                color: white;
                border-radius: 16px 16px 0 0;
            ">
                <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0;">
                    <i class="fas fa-user-plus" style="margin-right: 0.5rem;"></i>
                    Agregar Nuevo Usuario
                </h3>
                <button type="button" onclick="closeAddModal()" style="
                    background: rgba(255, 255, 255, 0.2);
                    border: none;
                    color: white;
                    width: 32px;
                    height: 32px;
                    border-radius: 8px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s;
                " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
                   onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form method="POST" id="addUserForm" style="padding: 1.5rem;">
                <input type="hidden" name="action" value="add_user">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <!-- Nombre -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-user" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Nombre *
                        </label>
                        <input type="text" name="nombre" required
                               class="form-control" placeholder="Ej: Juan"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                    </div>

                    <!-- Apellidos -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-user" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Apellidos *
                        </label>
                        <input type="text" name="apellidos" required
                               class="form-control" placeholder="Ej: Pérez García"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                    </div>
                </div>

                <!-- Email -->
                <div style="margin-top: 1rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                        <i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--gold);"></i>
                        Email *
                    </label>
                    <input type="email" name="email" required
                           class="form-control" placeholder="usuario@ejemplo.com"
                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                    <!-- Código Empleado -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-id-card" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Código Empleado *
                        </label>
                        <input type="text" name="codigo_empleado" required
                               class="form-control" placeholder="Ej: EMP0001"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-phone" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Teléfono
                        </label>
                        <input type="tel" name="telefono"
                               class="form-control" placeholder="Ej: 5551234567"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                    <!-- Empresa -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-building" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Empresa
                        </label>
                        <select name="empresa_id" class="form-control"
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                            <option value="">Seleccionar empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo $empresa['id']; ?>"><?php echo htmlspecialchars($empresa['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Departamento -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-sitemap" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Departamento
                        </label>
                        <select name="departamento_id" class="form-control"
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                            <option value="">Seleccionar departamento</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Rol -->
                <div style="margin-top: 1rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                        <i class="fas fa-user-tag" style="margin-right: 0.5rem; color: var(--gold);"></i>
                        Rol *
                    </label>
                    <select name="rol" required class="form-control"
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                        <option value="empleado">Empleado</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                    <!-- Contraseña -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-lock" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Contraseña *
                        </label>
                        <input type="password" name="password" required minlength="6"
                               class="form-control" placeholder="Mínimo 6 caracteres"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                    </div>

                    <!-- PIN -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-key" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            PIN (6 dígitos)
                        </label>
                        <input type="text" name="pin" pattern="[0-9]{6}" maxlength="6"
                               class="form-control" placeholder="Ej: 123456"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                        <small style="color: var(--gray-500); font-size: 0.75rem;">Solo números, exactamente 6 dígitos</small>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div style="
                    margin-top: 1.5rem;
                    padding-top: 1.5rem;
                    border-top: 1px solid var(--gray-200);
                    display: flex;
                    justify-content: flex-end;
                    gap: 0.75rem;
                ">
                    <button type="button" onclick="closeAddModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-accent">
                        <i class="fas fa-save"></i> Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" style="
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        justify-content: center;
        align-items: center;
        overflow-y: auto;
        padding: 1rem;
    ">
        <div style="
            background: white;
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        ">
            <!-- Modal Header -->
            <div style="
                padding: 1.5rem;
                border-bottom: 1px solid var(--gray-200);
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: linear-gradient(135deg, var(--navy) 0%, #004080 100%);
                color: white;
                border-radius: 16px 16px 0 0;
            ">
                <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0;">
                    <i class="fas fa-user-edit" style="margin-right: 0.5rem;"></i>
                    Editar Usuario
                </h3>
                <button type="button" onclick="closeEditModal()" style="
                    background: rgba(255, 255, 255, 0.2);
                    border: none;
                    color: white;
                    width: 32px;
                    height: 32px;
                    border-radius: 8px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s;
                " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
                   onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form method="POST" id="editUserForm" style="padding: 1.5rem;">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <!-- Nombre -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-user" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Nombre *
                        </label>
                        <input type="text" name="nombre" id="edit_nombre" required
                               class="form-control" placeholder="Ej: Juan"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                    </div>

                    <!-- Apellidos -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-user" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Apellidos *
                        </label>
                        <input type="text" name="apellidos" id="edit_apellidos" required
                               class="form-control" placeholder="Ej: Pérez García"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                    </div>
                </div>

                <!-- Email -->
                <div style="margin-top: 1rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                        <i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--gold);"></i>
                        Email *
                    </label>
                    <input type="email" name="email" id="edit_email" required
                           class="form-control" placeholder="usuario@empresa.com"
                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                    <!-- Teléfono -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-phone" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Teléfono
                        </label>
                        <input type="tel" name="telefono" id="edit_telefono"
                               class="form-control" placeholder="Ej: 999-999-9999"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                    </div>

                    <!-- Código Empleado -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-id-badge" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Código Empleado *
                        </label>
                        <input type="text" name="codigo_empleado" id="edit_codigo_empleado" required
                               class="form-control" placeholder="Ej: EMP001"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                    <!-- Empresa -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-building" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Empresa
                        </label>
                        <select name="empresa_id" id="edit_empresa_id" class="form-control"
                                onchange="filterDepartamentos(this.value)"
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                            <option value="">Seleccionar empresa...</option>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo $empresa['id']; ?>">
                                    <?php echo htmlspecialchars($empresa['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Departamento -->
                    <div>
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--navy);">
                            <i class="fas fa-sitemap" style="margin-right: 0.5rem; color: var(--gold);"></i>
                            Departamento
                        </label>
                        <select name="departamento_id" id="edit_departamento_id" class="form-control"
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                            <option value="">Seleccionar departamento...</option>
                            <?php foreach ($departamentos as $depto): ?>
                                <option value="<?php echo $depto['id']; ?>"
                                        data-empresa="<?php echo $depto['empresa_id']; ?>">
                                    <?php echo htmlspecialchars($depto['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div style="
                    margin-top: 1.5rem;
                    padding-top: 1.5rem;
                    border-top: 1px solid var(--gray-200);
                    display: flex;
                    justify-content: flex-end;
                    gap: 0.75rem;
                ">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function changeRole(userId, newRole) {
        if (newRole && confirm(`¿Cambiar el rol a ${newRole}?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="action" value="change_role">
                <input type="hidden" name="new_role" value="${newRole}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function openAddModal() {
        // Clear form fields
        document.getElementById('addUserForm').reset();
        // Show modal
        document.getElementById('addUserModal').style.display = 'flex';
    }

    function closeAddModal() {
        document.getElementById('addUserModal').style.display = 'none';
    }

    function openEditModal(user) {
        // Populate form fields
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_nombre').value = user.nombre || '';
        document.getElementById('edit_apellidos').value = user.apellidos || '';
        document.getElementById('edit_email').value = user.email || '';
        document.getElementById('edit_telefono').value = user.telefono || '';
        document.getElementById('edit_codigo_empleado').value = user.codigo_empleado || '';
        document.getElementById('edit_empresa_id').value = user.empresa_id || '';
        document.getElementById('edit_departamento_id').value = user.departamento_id || '';

        // Filter departamentos based on selected empresa
        if (user.empresa_id) {
            filterDepartamentos(user.empresa_id);
            // Set departamento after filtering
            setTimeout(() => {
                document.getElementById('edit_departamento_id').value = user.departamento_id || '';
            }, 50);
        }

        // Show modal
        document.getElementById('editUserModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editUserModal').style.display = 'none';
    }

    function filterDepartamentos(empresaId) {
        const deptoSelect = document.getElementById('edit_departamento_id');
        const options = deptoSelect.querySelectorAll('option');

        options.forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
                return;
            }

            const empresaData = option.getAttribute('data-empresa');
            if (!empresaId || empresaData === empresaId) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });

        // Reset selection if current option is now hidden
        const currentOption = deptoSelect.options[deptoSelect.selectedIndex];
        if (currentOption && currentOption.style.display === 'none') {
            deptoSelect.value = '';
        }
    }

    // Close modal when clicking outside
    document.getElementById('editUserModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
        }
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>