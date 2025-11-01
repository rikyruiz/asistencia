<?php
/**
 * Restablecer Contraseña - Sistema de Asistencia
 */

session_start();

require_once 'config/database.php';

$error = '';
$success = '';
$validToken = false;
$token = $_GET['token'] ?? '';

// Verificar token
if ($token) {
    try {
        $db = db();

        $stmt = $db->prepare("
            SELECT id, nombre, apellidos, email
            FROM usuarios
            WHERE reset_token = ?
              AND reset_token_expires > NOW()
              AND activo = 1
              AND estado_aprobacion = 'aprobado'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $validToken = true;

            // Procesar cambio de contraseña
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                if (empty($password)) {
                    $error = 'La contraseña es requerida';
                } elseif (strlen($password) < 6) {
                    $error = 'La contraseña debe tener al menos 6 caracteres';
                } elseif ($password !== $confirm_password) {
                    $error = 'Las contraseñas no coinciden';
                } else {
                    // Actualizar contraseña y limpiar token
                    $updateStmt = $db->prepare("
                        UPDATE usuarios
                        SET password = ?,
                            reset_token = NULL,
                            reset_token_expires = NULL
                        WHERE id = ?
                    ");
                    $updateStmt->execute([
                        password_hash($password, PASSWORD_DEFAULT),
                        $user['id']
                    ]);

                    $success = 'Tu contraseña ha sido restablecida exitosamente. Ahora puedes iniciar sesión.';

                    // Invalidar token para evitar reuso
                    $validToken = false;
                }
            }
        } else {
            $error = 'El enlace de recuperación es inválido o ha expirado.';
        }
    } catch (Exception $e) {
        error_log("Error en reset password: " . $e->getMessage());
        $error = 'Error al procesar la solicitud.';
    }
} else {
    $error = 'Token de recuperación no proporcionado.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Sistema de Asistencia AlpeFresh</title>

    <!-- Favicon -->
    <link rel="icon" href="https://alpefresh.app/favicon.ico">

    <!-- Estilos del marketplace -->
    <?php include 'includes/styles.php'; ?>

    <style>
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .reset-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
        }

        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 150px;
            margin-bottom: 1.5rem;
        }

        .reset-title {
            color: var(--navy);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .reset-subtitle {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .password-requirements {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: var(--navy);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <img src="https://alpefresh.app/assets/images/logo.png" alt="AlpeFresh" class="logo">
                <h1 class="reset-title">Nueva Contraseña</h1>
                <?php if ($validToken && isset($user)): ?>
                    <p class="reset-subtitle">
                        Crea una nueva contraseña para tu cuenta
                    </p>
                <?php endif; ?>
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

                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        Ir al Inicio de Sesión
                    </a>
                </div>
            <?php elseif ($validToken): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="password">
                            Nueva Contraseña
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                            autofocus
                        >
                        <div class="password-requirements">
                            Mínimo 6 caracteres
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            Confirmar Contraseña
                        </label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                        <i class="fas fa-key"></i>
                        Restablecer Contraseña
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!$success): ?>
                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i>
                        Volver al inicio de sesión
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

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

        <?php if ($success): ?>
        // Redirigir al login después de mostrar el mensaje
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>