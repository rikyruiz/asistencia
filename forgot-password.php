<?php
/**
 * Recuperación de Contraseña - Sistema de Asistencia
 */

session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/email_service.php';

$error = '';
$success = '';

// Procesar solicitud de recuperación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Por favor ingresa tu correo electrónico';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido';
    } else {
        try {
            $db = db();

            // Buscar usuario
            $stmt = $db->prepare("
                SELECT id, nombre, apellidos, estado_aprobacion, activo
                FROM usuarios
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                // No revelar si el email existe o no por seguridad
                $success = 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.';
            } elseif ($user['estado_aprobacion'] === 'pendiente') {
                $error = 'Tu cuenta está pendiente de aprobación. No puedes restablecer la contraseña hasta que sea aprobada.';
            } elseif (!$user['activo']) {
                $error = 'Tu cuenta está desactivada. Contacta al administrador.';
            } else {
                // Generar token único
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Actualizar token en la base de datos
                $updateStmt = $db->prepare("
                    UPDATE usuarios
                    SET reset_token = ?,
                        reset_token_expires = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$token, $expires, $user['id']]);

                // Enviar email
                $emailService = new EmailService();
                $fullName = $user['nombre'] . ' ' . $user['apellidos'];

                if ($emailService->sendPasswordResetEmail($email, $fullName, $token)) {
                    $success = 'Se han enviado las instrucciones para restablecer tu contraseña a tu correo electrónico.';
                } else {
                    $error = 'Error al enviar el correo. Por favor, intenta nuevamente.';
                }
            }
        } catch (Exception $e) {
            error_log("Error en recuperación de contraseña: " . $e->getMessage());
            $error = 'Error al procesar la solicitud. Por favor, intenta nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Sistema de Asistencia AlpeFresh</title>

    <!-- Favicon -->
    <link rel="icon" href="https://alpefresh.app/favicon.ico">

    <!-- Estilos del marketplace -->
    <?php include 'includes/styles.php'; ?>

    <style>
        .forgot-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .forgot-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 150px;
            margin-bottom: 1.5rem;
        }

        .forgot-title {
            color: var(--navy);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .forgot-subtitle {
            color: var(--gray-600);
            font-size: 0.875rem;
            line-height: 1.5;
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

        .info-box {
            background: #f0f9ff;
            border: 1px solid #0284c7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-box p {
            color: #0c4a6e;
            font-size: 0.875rem;
            margin: 0;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-header">
                <img src="https://alpefresh.app/assets/images/logo.png" alt="AlpeFresh" class="logo">
                <h1 class="forgot-title">Recuperar Contraseña</h1>
                <p class="forgot-subtitle">
                    Ingresa tu correo electrónico y te enviaremos instrucciones para restablecer tu contraseña
                </p>
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
            <?php else: ?>
                <div class="info-box">
                    <p>
                        <i class="fas fa-info-circle"></i>
                        El enlace de recuperación expirará en 1 hora por seguridad.
                    </p>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="email">
                            Correo Electrónico
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="tu@email.com"
                            required
                            autofocus
                        >
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                        <i class="fas fa-paper-plane"></i>
                        Enviar Instrucciones
                    </button>
                </form>
            <?php endif; ?>

            <div class="back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i>
                    Volver al inicio de sesión
                </a>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
    <script>
        // Redirigir al login después de mostrar el mensaje
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 5000);
    </script>
    <?php endif; ?>
</body>
</html>