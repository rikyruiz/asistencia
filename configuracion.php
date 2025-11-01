<?php
/**
 * Configuración del Sistema - Sistema de Asistencia
 * Panel de configuración general del sistema
 */

session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /login.php');
    exit;
}

// Solo admin puede acceder a configuración
if ($_SESSION['user_rol'] !== 'admin') {
    header('Location: /dashboard.php');
    exit;
}

require_once 'config/database.php';

$db = db();
$message = '';
$messageType = '';

// Obtener o crear configuración
$configStmt = $db->query("SELECT * FROM configuracion_sistema WHERE id = 1");
$config = $configStmt->fetch();

if (!$config) {
    // Crear configuración por defecto
    $db->exec("
        INSERT INTO configuracion_sistema (
            id, nombre_sistema, timezone, formato_fecha, formato_hora,
            radio_geofence, intentos_login, sesion_duracion,
            email_notificaciones, notificar_entrada, notificar_salida,
            notificar_ausencia, hora_cierre_jornada, dias_laborales,
            permitir_registro, requiere_aprobacion, permitir_pin,
            backup_automatico, dias_retener_logs
        ) VALUES (
            1, 'Sistema de Asistencia AlpeFresh', 'America/Mexico_City', 'd/m/Y', 'H:i',
            100, 3, 480,
            1, 1, 1,
            1, '23:59', 'lun,mar,mie,jue,vie',
            1, 1, 1,
            1, 30
        )
    ");

    $configStmt = $db->query("SELECT * FROM configuracion_sistema WHERE id = 1");
    $config = $configStmt->fetch();
}

// Procesar actualizaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';

    try {
        switch ($section) {
            case 'general':
                $stmt = $db->prepare("
                    UPDATE configuracion_sistema SET
                        nombre_sistema = ?,
                        timezone = ?,
                        formato_fecha = ?,
                        formato_hora = ?
                    WHERE id = 1
                ");
                $stmt->execute([
                    $_POST['nombre_sistema'],
                    $_POST['timezone'],
                    $_POST['formato_fecha'],
                    $_POST['formato_hora']
                ]);
                $message = '✅ Configuración general actualizada';
                break;

            case 'asistencia':
                $dias_laborales = implode(',', $_POST['dias_laborales'] ?? []);
                $stmt = $db->prepare("
                    UPDATE configuracion_sistema SET
                        radio_geofence = ?,
                        hora_cierre_jornada = ?,
                        dias_laborales = ?,
                        permitir_pin = ?
                    WHERE id = 1
                ");
                $stmt->execute([
                    $_POST['radio_geofence'],
                    $_POST['hora_cierre_jornada'],
                    $dias_laborales,
                    isset($_POST['permitir_pin']) ? 1 : 0
                ]);
                $message = '✅ Configuración de asistencia actualizada';
                break;

            case 'seguridad':
                $stmt = $db->prepare("
                    UPDATE configuracion_sistema SET
                        intentos_login = ?,
                        sesion_duracion = ?,
                        permitir_registro = ?,
                        requiere_aprobacion = ?
                    WHERE id = 1
                ");
                $stmt->execute([
                    $_POST['intentos_login'],
                    $_POST['sesion_duracion'],
                    isset($_POST['permitir_registro']) ? 1 : 0,
                    isset($_POST['requiere_aprobacion']) ? 1 : 0
                ]);
                $message = '✅ Configuración de seguridad actualizada';
                break;

            case 'notificaciones':
                $stmt = $db->prepare("
                    UPDATE configuracion_sistema SET
                        email_notificaciones = ?,
                        notificar_entrada = ?,
                        notificar_salida = ?,
                        notificar_ausencia = ?
                    WHERE id = 1
                ");
                $stmt->execute([
                    isset($_POST['email_notificaciones']) ? 1 : 0,
                    isset($_POST['notificar_entrada']) ? 1 : 0,
                    isset($_POST['notificar_salida']) ? 1 : 0,
                    isset($_POST['notificar_ausencia']) ? 1 : 0
                ]);
                $message = '✅ Configuración de notificaciones actualizada';
                break;

            case 'mantenimiento':
                $stmt = $db->prepare("
                    UPDATE configuracion_sistema SET
                        backup_automatico = ?,
                        dias_retener_logs = ?
                    WHERE id = 1
                ");
                $stmt->execute([
                    isset($_POST['backup_automatico']) ? 1 : 0,
                    $_POST['dias_retener_logs']
                ]);
                $message = '✅ Configuración de mantenimiento actualizada';
                break;
        }

        $messageType = 'success';

        // Recargar configuración
        $configStmt = $db->query("SELECT * FROM configuracion_sistema WHERE id = 1");
        $config = $configStmt->fetch();

    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener estadísticas del sistema
$statsStmt = $db->query("
    SELECT
        (SELECT COUNT(*) FROM usuarios) as total_usuarios,
        (SELECT COUNT(*) FROM usuarios WHERE activo = 1) as usuarios_activos,
        (SELECT COUNT(*) FROM empresas) as total_empresas,
        (SELECT COUNT(*) FROM ubicaciones) as total_ubicaciones,
        (SELECT COUNT(*) FROM asistencias WHERE DATE(fecha_hora) = CURDATE()) as asistencias_hoy,
        (SELECT COUNT(*) FROM usuarios WHERE estado = 'pendiente') as usuarios_pendientes
");
$stats = $statsStmt->fetch();

$dias_laborales = explode(',', $config['dias_laborales'] ?? 'lun,mar,mie,jue,vie');

$page_title = 'Configuración del Sistema';
$page_subtitle = 'Ajustes generales y parámetros del sistema';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Asistencia AlpeFresh</title>

    <link rel="icon" href="/favicon.ico">
    <?php include 'includes/styles.php'; ?>

    <style>
        .config-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .config-sidebar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .config-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .config-menu-item {
            margin-bottom: 0.5rem;
        }

        .config-menu-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            color: var(--gray-700);
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .config-menu-link:hover {
            background: var(--gray-50);
            color: var(--navy);
        }

        .config-menu-link.active {
            background: linear-gradient(135deg, var(--navy) 0%, #004080 100%);
            color: white;
        }

        .config-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .config-section {
            display: none;
            padding: 2rem;
        }

        .config-section.active {
            display: block;
        }

        .section-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        .config-form {
            max-width: 800px;
        }

        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--gray-50);
            border-radius: 8px;
        }

        .form-section-title {
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 6px;
            border: 1px solid var(--gray-300);
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-item label {
            cursor: pointer;
            user-select: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-mini {
            background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid var(--gold);
        }

        .stat-mini-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--navy);
        }

        .stat-mini-label {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .info-box {
            background: #e0f2fe;
            border-left: 4px solid #0284c7;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .info-box i {
            color: #0284c7;
            margin-right: 0.5rem;
        }

        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .warning-box i {
            color: #f59e0b;
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .config-container {
                grid-template-columns: 1fr;
            }

            .config-sidebar {
                position: static;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container" style="max-width: 1400px; margin: 2rem auto; padding: 0 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 2rem; font-weight: 700; color: var(--navy); margin-bottom: 0.5rem;">
                    <i class="fas fa-cog"></i> <?php echo $page_title; ?>
                </h1>
                <p style="color: var(--gray-600);"><?php echo $page_subtitle; ?></p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 1.5rem;">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="config-container">
            <!-- Sidebar -->
            <div class="config-sidebar">
                <ul class="config-menu">
                    <li class="config-menu-item">
                        <a href="#general" class="config-menu-link active" onclick="showSection('general')">
                            <i class="fas fa-sliders-h"></i>
                            General
                        </a>
                    </li>
                    <li class="config-menu-item">
                        <a href="#asistencia" class="config-menu-link" onclick="showSection('asistencia')">
                            <i class="fas fa-clock"></i>
                            Asistencia
                        </a>
                    </li>
                    <li class="config-menu-item">
                        <a href="#seguridad" class="config-menu-link" onclick="showSection('seguridad')">
                            <i class="fas fa-shield-alt"></i>
                            Seguridad
                        </a>
                    </li>
                    <li class="config-menu-item">
                        <a href="#notificaciones" class="config-menu-link" onclick="showSection('notificaciones')">
                            <i class="fas fa-bell"></i>
                            Notificaciones
                        </a>
                    </li>
                    <li class="config-menu-item">
                        <a href="#mantenimiento" class="config-menu-link" onclick="showSection('mantenimiento')">
                            <i class="fas fa-wrench"></i>
                            Mantenimiento
                        </a>
                    </li>
                    <li class="config-menu-item">
                        <a href="#info" class="config-menu-link" onclick="showSection('info')">
                            <i class="fas fa-info-circle"></i>
                            Información
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Content -->
            <div class="config-content">
                <!-- General -->
                <div id="general" class="config-section active">
                    <div class="section-header">
                        <h2 class="section-title">Configuración General</h2>
                        <p class="section-subtitle">Ajustes básicos del sistema</p>
                    </div>

                    <form method="POST" class="config-form">
                        <input type="hidden" name="section" value="general">

                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-building"></i> Información del Sistema
                            </h3>

                            <div class="form-group">
                                <label class="form-label">Nombre del Sistema</label>
                                <input type="text" name="nombre_sistema" class="form-control"
                                       value="<?php echo htmlspecialchars($config['nombre_sistema']); ?>" required>
                                <small class="form-text">Este nombre aparecerá en el encabezado y correos</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Zona Horaria</label>
                                    <select name="timezone" class="form-control">
                                        <option value="America/Mexico_City" <?php echo $config['timezone'] == 'America/Mexico_City' ? 'selected' : ''; ?>>
                                            Ciudad de México (GMT-6)
                                        </option>
                                        <option value="America/Cancun" <?php echo $config['timezone'] == 'America/Cancun' ? 'selected' : ''; ?>>
                                            Cancún (GMT-5)
                                        </option>
                                        <option value="America/Tijuana" <?php echo $config['timezone'] == 'America/Tijuana' ? 'selected' : ''; ?>>
                                            Tijuana (GMT-8)
                                        </option>
                                        <option value="America/Hermosillo" <?php echo $config['timezone'] == 'America/Hermosillo' ? 'selected' : ''; ?>>
                                            Hermosillo (GMT-7)
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Formato de Fecha</label>
                                    <select name="formato_fecha" class="form-control">
                                        <option value="d/m/Y" <?php echo $config['formato_fecha'] == 'd/m/Y' ? 'selected' : ''; ?>>
                                            DD/MM/AAAA (31/12/2025)
                                        </option>
                                        <option value="Y-m-d" <?php echo $config['formato_fecha'] == 'Y-m-d' ? 'selected' : ''; ?>>
                                            AAAA-MM-DD (2025-12-31)
                                        </option>
                                        <option value="m/d/Y" <?php echo $config['formato_fecha'] == 'm/d/Y' ? 'selected' : ''; ?>>
                                            MM/DD/AAAA (12/31/2025)
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Formato de Hora</label>
                                    <select name="formato_hora" class="form-control">
                                        <option value="H:i" <?php echo $config['formato_hora'] == 'H:i' ? 'selected' : ''; ?>>
                                            24 horas (23:59)
                                        </option>
                                        <option value="h:i A" <?php echo $config['formato_hora'] == 'h:i A' ? 'selected' : ''; ?>>
                                            12 horas (11:59 PM)
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Asistencia -->
                <div id="asistencia" class="config-section">
                    <div class="section-header">
                        <h2 class="section-title">Configuración de Asistencia</h2>
                        <p class="section-subtitle">Parámetros para el control de asistencia</p>
                    </div>

                    <form method="POST" class="config-form">
                        <input type="hidden" name="section" value="asistencia">

                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-map-marker-alt"></i> Geolocalización
                            </h3>

                            <div class="form-group">
                                <label class="form-label">Radio de Geofence (metros)</label>
                                <input type="number" name="radio_geofence" class="form-control"
                                       value="<?php echo $config['radio_geofence']; ?>" min="50" max="1000" required>
                                <small class="form-text">Distancia máxima para permitir marcaje de asistencia</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-calendar-alt"></i> Horarios y Días
                            </h3>

                            <div class="form-group">
                                <label class="form-label">Hora de Cierre de Jornada</label>
                                <input type="time" name="hora_cierre_jornada" class="form-control"
                                       value="<?php echo $config['hora_cierre_jornada']; ?>" required>
                                <small class="form-text">Hora límite para cerrar automáticamente las asistencias abiertas</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Días Laborales</label>
                                <div class="checkbox-group">
                                    <?php
                                    $dias = [
                                        'lun' => 'Lunes',
                                        'mar' => 'Martes',
                                        'mie' => 'Miércoles',
                                        'jue' => 'Jueves',
                                        'vie' => 'Viernes',
                                        'sab' => 'Sábado',
                                        'dom' => 'Domingo'
                                    ];
                                    foreach ($dias as $key => $nombre):
                                    ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="dias_laborales[]"
                                               id="dia_<?php echo $key; ?>"
                                               value="<?php echo $key; ?>"
                                               <?php echo in_array($key, $dias_laborales) ? 'checked' : ''; ?>>
                                        <label for="dia_<?php echo $key; ?>"><?php echo $nombre; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="permitir_pin" id="permitir_pin" value="1"
                                           <?php echo $config['permitir_pin'] ? 'checked' : ''; ?>>
                                    <label for="permitir_pin">
                                        Permitir autenticación con PIN de 6 dígitos
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Seguridad -->
                <div id="seguridad" class="config-section">
                    <div class="section-header">
                        <h2 class="section-title">Configuración de Seguridad</h2>
                        <p class="section-subtitle">Parámetros de acceso y autenticación</p>
                    </div>

                    <form method="POST" class="config-form">
                        <input type="hidden" name="section" value="seguridad">

                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-lock"></i> Autenticación
                            </h3>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Intentos de Login Máximos</label>
                                    <input type="number" name="intentos_login" class="form-control"
                                           value="<?php echo $config['intentos_login']; ?>" min="1" max="10" required>
                                    <small class="form-text">Número de intentos antes de bloquear la cuenta</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Duración de Sesión (minutos)</label>
                                    <input type="number" name="sesion_duracion" class="form-control"
                                           value="<?php echo $config['sesion_duracion']; ?>" min="30" max="1440" required>
                                    <small class="form-text">Tiempo de inactividad antes de cerrar sesión</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-user-plus"></i> Registro de Usuarios
                            </h3>

                            <div class="checkbox-item" style="margin-bottom: 1rem;">
                                <input type="checkbox" name="permitir_registro" id="permitir_registro" value="1"
                                       <?php echo $config['permitir_registro'] ? 'checked' : ''; ?>>
                                <label for="permitir_registro">
                                    Permitir auto-registro de nuevos usuarios
                                </label>
                            </div>

                            <div class="checkbox-item">
                                <input type="checkbox" name="requiere_aprobacion" id="requiere_aprobacion" value="1"
                                       <?php echo $config['requiere_aprobacion'] ? 'checked' : ''; ?>>
                                <label for="requiere_aprobacion">
                                    Requerir aprobación de administrador para nuevos registros
                                </label>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Notificaciones -->
                <div id="notificaciones" class="config-section">
                    <div class="section-header">
                        <h2 class="section-title">Configuración de Notificaciones</h2>
                        <p class="section-subtitle">Alertas y notificaciones por correo electrónico</p>
                    </div>

                    <form method="POST" class="config-form">
                        <input type="hidden" name="section" value="notificaciones">

                        <div class="info-box">
                            <i class="fas fa-info-circle"></i>
                            Las notificaciones se envían desde: <strong>notificaciones@alpefresh.app</strong>
                        </div>

                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-envelope"></i> Notificaciones por Email
                            </h3>

                            <div class="checkbox-item" style="margin-bottom: 1.5rem;">
                                <input type="checkbox" name="email_notificaciones" id="email_notificaciones" value="1"
                                       <?php echo $config['email_notificaciones'] ? 'checked' : ''; ?>>
                                <label for="email_notificaciones">
                                    <strong>Activar sistema de notificaciones por email</strong>
                                </label>
                            </div>

                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="notificar_entrada" id="notificar_entrada" value="1"
                                           <?php echo $config['notificar_entrada'] ? 'checked' : ''; ?>>
                                    <label for="notificar_entrada">
                                        Notificar marcaje de entrada
                                    </label>
                                </div>

                                <div class="checkbox-item">
                                    <input type="checkbox" name="notificar_salida" id="notificar_salida" value="1"
                                           <?php echo $config['notificar_salida'] ? 'checked' : ''; ?>>
                                    <label for="notificar_salida">
                                        Notificar marcaje de salida
                                    </label>
                                </div>

                                <div class="checkbox-item">
                                    <input type="checkbox" name="notificar_ausencia" id="notificar_ausencia" value="1"
                                           <?php echo $config['notificar_ausencia'] ? 'checked' : ''; ?>>
                                    <label for="notificar_ausencia">
                                        Notificar ausencias
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Mantenimiento -->
                <div id="mantenimiento" class="config-section">
                    <div class="section-header">
                        <h2 class="section-title">Configuración de Mantenimiento</h2>
                        <p class="section-subtitle">Respaldos y limpieza del sistema</p>
                    </div>

                    <form method="POST" class="config-form">
                        <input type="hidden" name="section" value="mantenimiento">

                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-database"></i> Respaldos
                            </h3>

                            <div class="checkbox-item" style="margin-bottom: 1rem;">
                                <input type="checkbox" name="backup_automatico" id="backup_automatico" value="1"
                                       <?php echo $config['backup_automatico'] ? 'checked' : ''; ?>>
                                <label for="backup_automatico">
                                    Realizar respaldo automático diario de la base de datos
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Días para retener logs del sistema</label>
                                <input type="number" name="dias_retener_logs" class="form-control"
                                       value="<?php echo $config['dias_retener_logs']; ?>" min="7" max="365" required>
                                <small class="form-text">Los logs más antiguos se eliminarán automáticamente</small>
                            </div>
                        </div>

                        <div class="warning-box">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Acciones de Mantenimiento:</strong>
                            <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                                <button type="button" onclick="if(confirm('¿Limpiar todos los logs del sistema?')) limpiarLogs();"
                                        class="btn btn-secondary btn-sm">
                                    <i class="fas fa-broom"></i> Limpiar Logs
                                </button>
                                <button type="button" onclick="if(confirm('¿Crear respaldo manual ahora?')) crearRespaldo();"
                                        class="btn btn-secondary btn-sm">
                                    <i class="fas fa-download"></i> Respaldo Manual
                                </button>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Información -->
                <div id="info" class="config-section">
                    <div class="section-header">
                        <h2 class="section-title">Información del Sistema</h2>
                        <p class="section-subtitle">Estadísticas y estado actual</p>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-mini">
                            <div class="stat-mini-value"><?php echo $stats['total_usuarios']; ?></div>
                            <div class="stat-mini-label">Usuarios Totales</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-value"><?php echo $stats['usuarios_activos']; ?></div>
                            <div class="stat-mini-label">Usuarios Activos</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-value"><?php echo $stats['total_empresas']; ?></div>
                            <div class="stat-mini-label">Empresas</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-value"><?php echo $stats['total_ubicaciones']; ?></div>
                            <div class="stat-mini-label">Ubicaciones</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-value"><?php echo $stats['asistencias_hoy']; ?></div>
                            <div class="stat-mini-label">Asistencias Hoy</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-value"><?php echo $stats['usuarios_pendientes']; ?></div>
                            <div class="stat-mini-label">Pendientes Aprobación</div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-server"></i> Información del Servidor
                        </h3>

                        <table class="table">
                            <tbody>
                                <tr>
                                    <td><strong>PHP Version:</strong></td>
                                    <td><?php echo phpversion(); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>MySQL Version:</strong></td>
                                    <td><?php echo $db->query("SELECT VERSION()")->fetchColumn(); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Zona Horaria:</strong></td>
                                    <td><?php echo $config['timezone']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha del Servidor:</strong></td>
                                    <td><?php echo date($config['formato_fecha'] . ' ' . $config['formato_hora']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>URL del Sistema:</strong></td>
                                    <td>https://asistencia.alpefresh.app</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-history"></i> Actividad Reciente
                        </h3>

                        <?php
                        $activityStmt = $db->query("
                            SELECT 'login' as tipo, CONCAT(nombre, ' ', apellido) as descripcion, ultimo_login as fecha
                            FROM usuarios
                            WHERE ultimo_login IS NOT NULL
                            ORDER BY ultimo_login DESC
                            LIMIT 5
                        ");
                        $activities = $activityStmt->fetchAll();
                        ?>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Último Acceso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($activity['descripcion']); ?></td>
                                    <td><?php echo $activity['fecha']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showSection(sectionId) {
        // Ocultar todas las secciones
        document.querySelectorAll('.config-section').forEach(section => {
            section.classList.remove('active');
        });

        // Desactivar todos los enlaces del menú
        document.querySelectorAll('.config-menu-link').forEach(link => {
            link.classList.remove('active');
        });

        // Mostrar la sección seleccionada
        document.getElementById(sectionId).classList.add('active');

        // Activar el enlace del menú correspondiente
        document.querySelector(`a[href="#${sectionId}"]`).classList.add('active');
    }

    function limpiarLogs() {
        // Aquí iría la lógica para limpiar logs vía AJAX
        alert('Función en desarrollo: Limpiar logs del sistema');
    }

    function crearRespaldo() {
        // Aquí iría la lógica para crear respaldo vía AJAX
        alert('Función en desarrollo: Crear respaldo manual');
    }

    // Manejar navegación con hash
    if (window.location.hash) {
        const section = window.location.hash.substring(1);
        showSection(section);
    }

    // Deshabilitar opciones de notificación si el sistema está desactivado
    document.getElementById('email_notificaciones')?.addEventListener('change', function() {
        const checkboxes = ['notificar_entrada', 'notificar_salida', 'notificar_ausencia'];
        checkboxes.forEach(id => {
            const checkbox = document.getElementById(id);
            if (checkbox) {
                checkbox.disabled = !this.checked;
                if (!this.checked) checkbox.checked = false;
            }
        });
    });

    // Validación de al menos un día laboral seleccionado
    document.querySelector('form[action*="asistencia"]')?.addEventListener('submit', function(e) {
        const dias = document.querySelectorAll('input[name="dias_laborales[]"]:checked');
        if (dias.length === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos un día laboral');
        }
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>