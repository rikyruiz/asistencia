<?php
/**
 * Página de Registro - Sistema de Asistencia
 * Los usuarios registrados quedan pendientes de aprobación
 */

session_start();

// Determinar si es un admin agregando usuario o un registro público
$isAdminAdding = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && $_SESSION['user_rol'] === 'admin';
$isPublicRegistration = !isset($_SESSION['logged_in']) || !$_SESSION['logged_in'];

// Si está logueado pero NO es admin, redirigir al dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && $_SESSION['user_rol'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/email_service.php';

$error = '';
$success = '';

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');

    // Validaciones
    if (empty($nombre) || empty($apellidos) || empty($email) || empty($password)) {
        $error = 'Todos los campos obligatorios deben ser completados';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            $db = db();

            // Verificar si el email ya existe
            $checkStmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $checkStmt->execute([$email]);

            if ($checkStmt->fetch()) {
                $error = 'Este correo electrónico ya está registrado';
            } else {
                // Obtener empresa por defecto
                $empresaStmt = $db->query("SELECT id FROM empresas WHERE activa = 1 LIMIT 1");
                $empresa = $empresaStmt->fetch();

                if (!$empresa) {
                    // Crear empresa por defecto
                    $createEmpresaStmt = $db->prepare("
                        INSERT INTO empresas (nombre, rfc, activa)
                        VALUES ('Empresa Principal', 'XAXX010101000', 1)
                    ");
                    $createEmpresaStmt->execute();
                    $empresa_id = $db->lastInsertId();
                } else {
                    $empresa_id = $empresa['id'];
                }

                // Generar código de empleado único
                $codigo_empleado = 'EMP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                // Determinar estado según si es admin o registro público
                if ($isAdminAdding) {
                    // Admin creando usuario: auto-aprobar y activar
                    $insertStmt = $db->prepare("
                        INSERT INTO usuarios (
                            empresa_id, codigo_empleado, email, password,
                            nombre, apellidos, telefono, rol, activo,
                            estado_aprobacion, fecha_registro, aprobado_por, fecha_aprobacion
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, 'empleado', 1, 'aprobado', NOW(), ?, NOW()
                        )
                    ");

                    $insertStmt->execute([
                        $empresa_id,
                        $codigo_empleado,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $nombre,
                        $apellidos,
                        $telefono,
                        $_SESSION['user_id']
                    ]);

                    // Enviar email de bienvenida (cuenta ya activa)
                    $emailService = new EmailService();
                    $emailService->sendWelcomeEmail($email, $nombre . ' ' . $apellidos, false);

                    $success = 'Usuario creado exitosamente. El usuario ya está activo y puede iniciar sesión.';
                } else {
                    // Registro público: pendiente de aprobación
                    $insertStmt = $db->prepare("
                        INSERT INTO usuarios (
                            empresa_id, codigo_empleado, email, password,
                            nombre, apellidos, telefono, rol, activo,
                            estado_aprobacion, fecha_registro
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, 'empleado', 0, 'pendiente', NOW()
                        )
                    ");

                    $insertStmt->execute([
                        $empresa_id,
                        $codigo_empleado,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $nombre,
                        $apellidos,
                        $telefono
                    ]);

                    // Enviar email de confirmación al usuario
                    $emailService = new EmailService();
                    $emailService->sendWelcomeEmail($email, $nombre . ' ' . $apellidos, true);

                    // Notificar a los administradores
                    $adminStmt = $db->query("
                        SELECT email, nombre, apellidos
                        FROM usuarios
                        WHERE rol = 'admin' AND activo = 1
                    ");

                    while ($admin = $adminStmt->fetch()) {
                        $emailService->sendNewRegistrationNotification(
                            $admin['email'],
                            $nombre . ' ' . $apellidos,
                            $email
                        );
                    }

                    $success = 'Registro exitoso. Tu cuenta está pendiente de aprobación. Te notificaremos por email cuando sea aprobada.';
                }

                // Limpiar campos del formulario
                $nombre = $apellidos = $email = $telefono = '';
            }
        } catch (Exception $e) {
            error_log("Error en registro: " . $e->getMessage());
            $error = 'Error al procesar el registro. Por favor, intenta nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Asistencia AlpeFresh</title>

    <!-- Favicon -->
    <link rel="icon" href="https://alpefresh.app/favicon.ico">

    <!-- Estilos del marketplace -->
    <?php include 'includes/styles.php'; ?>

    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .register-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 150px;
            margin-bottom: 1.5rem;
        }

        .register-title {
            color: var(--navy);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .register-subtitle {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .password-requirements {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }

        .divider {
            margin: 1.5rem 0;
            text-align: center;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gray-200);
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            color: var(--gray-500);
            font-size: 0.875rem;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .login-link a {
            color: var(--navy);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .register-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php if ($isAdminAdding): ?>
        <?php
        $page_title = 'Agregar Usuario';
        $page_subtitle = 'Crear nuevo usuario en el sistema';
        require_once 'includes/header.php';
        ?>
        <div class="container">
    <?php endif; ?>

    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <img src="https://alpefresh.app/assets/images/logo.png" alt="AlpeFresh" class="logo">
                <h1 class="register-title"><?php echo $isAdminAdding ? 'Agregar Nuevo Usuario' : 'Crear Cuenta'; ?></h1>
                <p class="register-subtitle"><?php echo $isAdminAdding ? 'Crear nuevo usuario desde panel de administración' : 'Sistema de Asistencia'; ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="nombre">
                            Nombre *
                        </label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            class="form-control"
                            value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="apellidos">
                            Apellidos *
                        </label>
                        <input
                            type="text"
                            id="apellidos"
                            name="apellidos"
                            class="form-control"
                            value="<?php echo htmlspecialchars($apellidos ?? ''); ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group full-width">
                    <label class="form-label" for="email">
                        Correo Electrónico *
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group full-width">
                    <label class="form-label" for="telefono">
                        Teléfono
                    </label>
                    <input
                        type="tel"
                        id="telefono"
                        name="telefono"
                        class="form-control"
                        value="<?php echo htmlspecialchars($telefono ?? ''); ?>"
                        placeholder="Opcional"
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password">
                            Contraseña *
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                        >
                        <div class="password-requirements">
                            Mínimo 6 caracteres
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            Confirmar Contraseña *
                        </label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            required
                        >
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-user-plus"></i>
                        Registrarse
                    </button>
                </div>

                <div class="alert alert-info" style="margin-top: 1rem; font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i>
                    Tu cuenta quedará pendiente de aprobación por un administrador.
                </div>
            </form>
            <?php endif; ?>

            <div class="divider">
                <span>o</span>
            </div>

            <div class="login-link">
                <?php if ($isAdminAdding): ?>
                    <a href="usuarios.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Usuarios
                    </a>
                <?php else: ?>
                    ¿Ya tienes cuenta?
                    <a href="login.php">Inicia sesión aquí</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($isAdminAdding): ?>
        </div> <!-- Close container -->
        <?php require_once 'includes/footer.php'; ?>
    <?php endif; ?>

    <script>
        // Validación de contraseña en tiempo real
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;

            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });

        // Mostrar mensaje de éxito por más tiempo
        <?php if ($success): ?>
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>