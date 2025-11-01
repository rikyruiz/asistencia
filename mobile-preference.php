<?php
/**
 * Mobile Landing Preference Selection
 * One-time setup for mobile users to choose their preferred landing page
 */

session_start();

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /login.php');
    exit;
}

require_once 'config/database.php';
$db = db();
$userId = $_SESSION['user_id'];

// Procesar selección
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preference = $_POST['preference'] ?? 'dashboard';

    if (in_array($preference, ['dashboard', 'clock', 'ask'])) {
        try {
            $stmt = $db->prepare("UPDATE usuarios SET mobile_landing_preference = ? WHERE id = ?");
            $stmt->execute([$preference, $userId]);

            // Redirigir según la preferencia seleccionada
            if ($preference === 'clock') {
                header('Location: /asistencias.php');
            } else {
                header('Location: /dashboard.php');
            }
            exit;
        } catch (Exception $e) {
            error_log("Error guardando preferencia mobile: " . $e->getMessage());
        }
    }
}

// Obtener nombre del usuario
$stmt = $db->prepare("SELECT nombre FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preferencias Móviles - Sistema de Asistencia</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

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

    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .preference-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid transparent;
            text-align: center;
        }

        .preference-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            border-color: #fdb714;
        }

        .preference-card input[type="radio"] {
            display: none;
        }

        .preference-card input[type="radio"]:checked + .card-content {
            border-color: #003366;
        }

        .preference-card input[type="radio"]:checked + .card-content .icon-box {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
        }

        .icon-box {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            border-radius: 20px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            transition: all 0.3s;
        }
    </style>
</head>
<body>
    <div style="max-width: 600px; width: 100%;">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 2rem; color: white;">
            <div style="width: 80px; height: 80px; margin: 0 auto 1rem; border-radius: 20px; background: linear-gradient(135deg, #003366, #004080); display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.5rem;">
                Bienvenido, <?php echo htmlspecialchars($user['nombre']); ?>! 👋
            </h1>
            <p style="font-size: 1rem; opacity: 0.9;">
                Configura tu experiencia móvil
            </p>
        </div>

        <!-- Main Card -->
        <div class="glass" style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 2rem; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);">
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.25rem; font-weight: 600; color: #003366; margin-bottom: 0.5rem;">
                    ¿A dónde te gustaría ir después de iniciar sesión desde tu móvil?
                </h2>
                <p style="color: #6b7280; font-size: 0.95rem;">
                    Puedes cambiar esto más tarde en Configuración
                </p>
            </div>

            <form method="POST" id="preferenceForm">
                <div style="display: grid; gap: 1rem; margin-bottom: 2rem;">
                    <!-- Opción 1: Dashboard -->
                    <label class="preference-card">
                        <input type="radio" name="preference" value="dashboard" required>
                        <div class="card-content">
                            <div class="icon-box">
                                <i class="fas fa-th-large"></i>
                            </div>
                            <h3 style="font-size: 1.125rem; font-weight: 600; color: #003366; margin-bottom: 0.5rem;">
                                Dashboard
                            </h3>
                            <p style="color: #6b7280; font-size: 0.875rem;">
                                Ver estadísticas, gráficos y resumen general
                            </p>
                        </div>
                    </label>

                    <!-- Opción 2: Marcar Asistencia -->
                    <label class="preference-card">
                        <input type="radio" name="preference" value="clock" required>
                        <div class="card-content">
                            <div class="icon-box">
                                <i class="fas fa-fingerprint"></i>
                            </div>
                            <h3 style="font-size: 1.125rem; font-weight: 600; color: #003366; margin-bottom: 0.5rem;">
                                Marcar Asistencia
                            </h3>
                            <p style="color: #6b7280; font-size: 0.875rem;">
                                Ir directo a entrada/salida (recomendado)
                            </p>
                        </div>
                    </label>

                    <!-- Opción 3: Preguntar cada vez -->
                    <label class="preference-card">
                        <input type="radio" name="preference" value="ask" required>
                        <div class="card-content">
                            <div class="icon-box">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <h3 style="font-size: 1.125rem; font-weight: 600; color: #003366; margin-bottom: 0.5rem;">
                                Preguntarme cada vez
                            </h3>
                            <p style="color: #6b7280; font-size: 0.875rem;">
                                Mostrar menú de opciones al iniciar sesión
                            </p>
                        </div>
                    </label>
                </div>

                <button type="submit" class="btn-navy" style="width: 100%; padding: 1rem; font-size: 1.125rem; font-weight: 600; border-radius: 12px;">
                    <i class="fas fa-check-circle"></i>
                    Continuar
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="/dashboard.php" style="color: #6b7280; text-decoration: none; font-size: 0.875rem;">
                    Omitir configuración
                </a>
            </div>
        </div>

        <!-- Info Footer -->
        <div style="text-align: center; margin-top: 1.5rem; color: white; opacity: 0.8; font-size: 0.875rem;">
            <i class="fas fa-info-circle"></i>
            Esta configuración solo aplica cuando inicias sesión desde dispositivos móviles
        </div>
    </div>

    <script>
        // Auto-select card on click
        document.querySelectorAll('.preference-card').forEach(card => {
            card.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;

                // Visual feedback
                document.querySelectorAll('.preference-card').forEach(c => {
                    c.style.borderColor = 'transparent';
                });
                this.style.borderColor = '#003366';
            });
        });

        // Auto-submit on selection (optional - can remove if you prefer manual submit)
        document.querySelectorAll('input[name="preference"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Wait a bit for visual feedback, then submit
                setTimeout(() => {
                    document.getElementById('preferenceForm').submit();
                }, 300);
            });
        });
    </script>
</body>
</html>
