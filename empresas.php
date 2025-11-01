<?php
/**
 * Gestión de Empresas - Sistema de Asistencia
 * CRUD completo para administración de empresas
 */

session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /login.php');
    exit;
}

// Solo admin puede gestionar empresas
if ($_SESSION['user_rol'] !== 'admin') {
    header('Location: /dashboard.php');
    exit;
}

require_once 'config/database.php';

$db = db();
$message = '';
$messageType = '';

// Procesar acciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create':
            case 'update':
                $id = $_POST['id'] ?? null;
                $nombre = trim($_POST['nombre'] ?? '');
                $rfc = trim($_POST['rfc'] ?? '');
                $razon_social = trim($_POST['razon_social'] ?? '');
                $direccion = trim($_POST['direccion'] ?? '');
                $ciudad = trim($_POST['ciudad'] ?? '');
                $estado = trim($_POST['estado'] ?? '');
                $codigo_postal = trim($_POST['codigo_postal'] ?? '');
                $telefono = trim($_POST['telefono'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $contacto_nombre = trim($_POST['contacto_nombre'] ?? '');
                $contacto_telefono = trim($_POST['contacto_telefono'] ?? '');
                $contacto_email = trim($_POST['contacto_email'] ?? '');
                $configuracion = json_encode([
                    'horario_entrada' => $_POST['horario_entrada'] ?? '09:00',
                    'horario_salida' => $_POST['horario_salida'] ?? '18:00',
                    'tolerancia_minutos' => $_POST['tolerancia_minutos'] ?? 15,
                    'horas_semana' => $_POST['horas_semana'] ?? 40
                ]);

                if (empty($nombre)) {
                    throw new Exception('El nombre de la empresa es requerido');
                }

                if ($action === 'create') {
                    // Verificar si el RFC ya existe
                    if (!empty($rfc)) {
                        $checkStmt = $db->prepare("SELECT id FROM empresas WHERE rfc = ?");
                        $checkStmt->execute([$rfc]);
                        if ($checkStmt->fetch()) {
                            throw new Exception('Ya existe una empresa con ese RFC');
                        }
                    }

                    $stmt = $db->prepare("
                        INSERT INTO empresas (
                            nombre, rfc, razon_social, direccion, ciudad, estado,
                            codigo_postal, telefono, email, contacto_nombre,
                            contacto_telefono, contacto_email, configuracion
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $nombre, $rfc, $razon_social, $direccion, $ciudad, $estado,
                        $codigo_postal, $telefono, $email, $contacto_nombre,
                        $contacto_telefono, $contacto_email, $configuracion
                    ]);
                    $message = '✅ Empresa creada exitosamente';
                } else {
                    $stmt = $db->prepare("
                        UPDATE empresas SET
                            nombre = ?, rfc = ?, razon_social = ?, direccion = ?,
                            ciudad = ?, estado = ?, codigo_postal = ?, telefono = ?,
                            email = ?, contacto_nombre = ?, contacto_telefono = ?,
                            contacto_email = ?, configuracion = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $nombre, $rfc, $razon_social, $direccion, $ciudad, $estado,
                        $codigo_postal, $telefono, $email, $contacto_nombre,
                        $contacto_telefono, $contacto_email, $configuracion, $id
                    ]);
                    $message = '✅ Empresa actualizada exitosamente';
                }
                $messageType = 'success';
                break;

            case 'toggle':
                $id = $_POST['id'] ?? 0;
                $stmt = $db->prepare("UPDATE empresas SET activa = NOT activa WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Estado actualizado';
                $messageType = 'success';
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;

                // Verificar si tiene usuarios o ubicaciones asociadas
                $checkUsersStmt = $db->prepare("SELECT COUNT(*) as count FROM usuarios WHERE empresa_id = ?");
                $checkUsersStmt->execute([$id]);
                $usersCount = $checkUsersStmt->fetch()['count'];

                $checkLocsStmt = $db->prepare("SELECT COUNT(*) as count FROM ubicaciones WHERE empresa_id = ?");
                $checkLocsStmt->execute([$id]);
                $locsCount = $checkLocsStmt->fetch()['count'];

                if ($usersCount > 0 || $locsCount > 0) {
                    throw new Exception("No se puede eliminar: tiene $usersCount usuarios y $locsCount ubicaciones asociadas");
                }

                $stmt = $db->prepare("DELETE FROM empresas WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Empresa eliminada';
                $messageType = 'info';
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener todas las empresas con estadísticas
$empresasStmt = $db->query("
    SELECT e.*,
           (SELECT COUNT(*) FROM usuarios WHERE empresa_id = e.id) as total_usuarios,
           (SELECT COUNT(*) FROM usuarios WHERE empresa_id = e.id AND activo = 1) as usuarios_activos,
           (SELECT COUNT(*) FROM ubicaciones WHERE empresa_id = e.id) as total_ubicaciones,
           (SELECT COUNT(*) FROM departamentos WHERE empresa_id = e.id) as total_departamentos
    FROM empresas e
    ORDER BY e.nombre
");
$empresas = $empresasStmt->fetchAll();

// Estadísticas generales
$statsStmt = $db->query("
    SELECT
        COUNT(*) as total_empresas,
        COUNT(CASE WHEN activa = 1 THEN 1 END) as empresas_activas,
        (SELECT COUNT(*) FROM usuarios) as total_usuarios_sistema,
        (SELECT COUNT(*) FROM ubicaciones) as total_ubicaciones_sistema
    FROM empresas
");
$stats = $statsStmt->fetch();

$page_title = 'Gestión de Empresas';
$page_subtitle = 'Administración de empresas del sistema';
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
        .company-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .company-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            border-top: 4px solid var(--navy);
        }

        .company-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .company-card.inactive {
            opacity: 0.7;
            border-top-color: var(--gray-400);
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .company-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 0.25rem;
        }

        .company-rfc {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .company-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1.5rem 0;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 8px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--navy);
        }

        .stat-text {
            font-size: 0.75rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .company-details {
            margin-bottom: 1.5rem;
        }

        .detail-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            font-size: 0.875rem;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-icon {
            color: var(--gray-400);
            width: 20px;
        }

        .company-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--navy) 0%, #004080 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            background: var(--gray-50);
            border-radius: 0 0 12px 12px;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gold);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row.three-cols {
            grid-template-columns: repeat(3, 1fr);
        }

        .search-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .search-row {
            display: flex;
            gap: 1rem;
            align-items: end;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            background: white;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .company-grid {
                grid-template-columns: 1fr;
            }

            .form-row,
            .form-row.three-cols {
                grid-template-columns: 1fr;
            }

            .search-row {
                flex-direction: column;
                align-items: stretch;
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
                    <i class="fas fa-building"></i> <?php echo $page_title; ?>
                </h1>
                <p style="color: var(--gray-600);"><?php echo $page_subtitle; ?></p>
            </div>
            <button onclick="openModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Empresa
            </button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 1.5rem;">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Búsqueda y Filtros -->
        <div class="search-container">
            <div class="search-row">
                <input type="text"
                       id="searchInput"
                       class="search-input"
                       placeholder="Buscar por nombre, RFC, dirección o contacto...">
                <select id="filterStatus" class="filter-select">
                    <option value="all">Todas las Empresas</option>
                    <option value="active">Solo Activas</option>
                    <option value="inactive">Solo Inactivas</option>
                    <option value="with-users">Con Usuarios</option>
                    <option value="empty">Sin Usuarios</option>
                </select>
                <button onclick="clearFilters()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </button>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_empresas']; ?></div>
                <div class="stat-label">Total Empresas</div>
            </div>
            <div class="stat-card" style="border-left-color: #10b981;">
                <div class="stat-value" style="color: #10b981;">
                    <?php echo $stats['empresas_activas']; ?>
                </div>
                <div class="stat-label">Activas</div>
            </div>
            <div class="stat-card" style="border-left-color: #3b82f6;">
                <div class="stat-value" style="color: #3b82f6;">
                    <?php echo $stats['total_usuarios_sistema']; ?>
                </div>
                <div class="stat-label">Usuarios Totales</div>
            </div>
            <div class="stat-card" style="border-left-color: var(--gold);">
                <div class="stat-value" style="color: var(--gold);">
                    <?php echo $stats['total_ubicaciones_sistema']; ?>
                </div>
                <div class="stat-label">Ubicaciones</div>
            </div>
        </div>

        <!-- Grid de Empresas -->
        <div class="company-grid">
            <?php foreach ($empresas as $empresa): ?>
            <?php
                $config = json_decode($empresa['configuracion'] ?: '{}', true);
            ?>
            <div class="company-card <?php echo !$empresa['activa'] ? 'inactive' : ''; ?>"
                 data-name="<?php echo strtolower(htmlspecialchars($empresa['nombre'])); ?>"
                 data-rfc="<?php echo strtolower(htmlspecialchars($empresa['rfc'] ?? '')); ?>"
                 data-address="<?php echo strtolower(htmlspecialchars($empresa['direccion'] ?? '')); ?>"
                 data-contact="<?php echo strtolower(htmlspecialchars($empresa['contacto_nombre'] ?? '')); ?>"
                 data-active="<?php echo $empresa['activa'] ? '1' : '0'; ?>"
                 data-users="<?php echo $empresa['total_usuarios']; ?>">
                <div class="company-header">
                    <div>
                        <div class="company-name">
                            <?php echo htmlspecialchars($empresa['nombre']); ?>
                        </div>
                        <?php if ($empresa['rfc']): ?>
                        <div class="company-rfc">
                            RFC: <?php echo htmlspecialchars($empresa['rfc']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <span class="badge <?php echo $empresa['activa'] ? 'badge-success' : 'badge-danger'; ?>">
                        <?php echo $empresa['activa'] ? 'Activa' : 'Inactiva'; ?>
                    </span>
                </div>

                <div class="company-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $empresa['total_usuarios']; ?></div>
                        <div class="stat-text">Usuarios</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $empresa['total_ubicaciones']; ?></div>
                        <div class="stat-text">Ubicaciones</div>
                    </div>
                </div>

                <div class="company-details">
                    <?php if ($empresa['direccion']): ?>
                    <div class="detail-row">
                        <i class="fas fa-map-marker-alt detail-icon"></i>
                        <span><?php echo htmlspecialchars($empresa['direccion']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($empresa['telefono']): ?>
                    <div class="detail-row">
                        <i class="fas fa-phone detail-icon"></i>
                        <span><?php echo htmlspecialchars($empresa['telefono']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($empresa['email']): ?>
                    <div class="detail-row">
                        <i class="fas fa-envelope detail-icon"></i>
                        <span><?php echo htmlspecialchars($empresa['email']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($config['horario_entrada']) && isset($config['horario_salida'])): ?>
                    <div class="detail-row">
                        <i class="fas fa-clock detail-icon"></i>
                        <span>Horario: <?php echo $config['horario_entrada']; ?> - <?php echo $config['horario_salida']; ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="company-actions">
                    <button onclick='editCompany(<?php echo json_encode($empresa); ?>)'
                            class="btn btn-secondary btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </button>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?php echo $empresa['id']; ?>">
                        <button type="submit"
                                class="btn btn-sm <?php echo $empresa['activa'] ? 'btn-secondary' : 'btn-accent'; ?>">
                            <i class="fas fa-power-off"></i>
                            <?php echo $empresa['activa'] ? 'Desactivar' : 'Activar'; ?>
                        </button>
                    </form>

                    <?php if ($empresa['total_usuarios'] == 0 && $empresa['total_ubicaciones'] == 0): ?>
                    <form method="POST" style="display: inline;"
                          onsubmit="return confirm('¿Eliminar esta empresa permanentemente?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $empresa['id']; ?>">
                        <button type="submit" class="btn btn-sm" style="background: #ef4444; color: white;">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($empresas)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <i class="fas fa-building" style="font-size: 4rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--gray-600); margin-bottom: 0.5rem;">No hay empresas registradas</h3>
            <p style="color: var(--gray-500);">Haz clic en "Nueva Empresa" para agregar la primera</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Crear/Editar -->
    <div id="companyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle" style="margin: 0;">
                    <i class="fas fa-building"></i> Nueva Empresa
                </h2>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: white;">
                    &times;
                </button>
            </div>

            <form method="POST" id="companyForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="companyId">

                <div class="modal-body">
                    <!-- Información Básica -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-info-circle"></i> Información Básica
                        </h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nombre de la Empresa *</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">RFC</label>
                                <input type="text" name="rfc" id="rfc" class="form-control"
                                       pattern="[A-Z]{3,4}[0-9]{6}[A-Z0-9]{3}"
                                       placeholder="ABC123456DEF">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Razón Social</label>
                            <input type="text" name="razon_social" id="razon_social" class="form-control">
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-map-marker-alt"></i> Dirección
                        </h3>
                        <div class="form-group">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" id="direccion" class="form-control">
                        </div>

                        <div class="form-row three-cols">
                            <div class="form-group">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" id="ciudad" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Estado</label>
                                <input type="text" name="estado" id="estado" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Código Postal</label>
                                <input type="text" name="codigo_postal" id="codigo_postal" class="form-control"
                                       pattern="[0-9]{5}">
                            </div>
                        </div>
                    </div>

                    <!-- Contacto -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-address-card"></i> Información de Contacto
                        </h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" name="telefono" id="telefono" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control">
                            </div>
                        </div>

                        <div class="form-row three-cols">
                            <div class="form-group">
                                <label class="form-label">Nombre del Contacto</label>
                                <input type="text" name="contacto_nombre" id="contacto_nombre" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Teléfono del Contacto</label>
                                <input type="tel" name="contacto_telefono" id="contacto_telefono" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email del Contacto</label>
                                <input type="email" name="contacto_email" id="contacto_email" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Configuración -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-cog"></i> Configuración de Horarios
                        </h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Hora de Entrada</label>
                                <input type="time" name="horario_entrada" id="horario_entrada"
                                       class="form-control" value="09:00">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Hora de Salida</label>
                                <input type="time" name="horario_salida" id="horario_salida"
                                       class="form-control" value="18:00">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Tolerancia (minutos)</label>
                                <input type="number" name="tolerancia_minutos" id="tolerancia_minutos"
                                       class="form-control" value="15" min="0" max="60">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Horas por Semana</label>
                                <input type="number" name="horas_semana" id="horas_semana"
                                       class="form-control" value="40" min="1" max="60">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal() {
        document.getElementById('companyModal').classList.add('active');
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-building"></i> Nueva Empresa';
        document.getElementById('formAction').value = 'create';
        document.getElementById('companyForm').reset();
    }

    function editCompany(company) {
        document.getElementById('companyModal').classList.add('active');
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-building"></i> Editar Empresa';
        document.getElementById('formAction').value = 'update';

        // Llenar el formulario
        document.getElementById('companyId').value = company.id;
        document.getElementById('nombre').value = company.nombre || '';
        document.getElementById('rfc').value = company.rfc || '';
        document.getElementById('razon_social').value = company.razon_social || '';
        document.getElementById('direccion').value = company.direccion || '';
        document.getElementById('ciudad').value = company.ciudad || '';
        document.getElementById('estado').value = company.estado || '';
        document.getElementById('codigo_postal').value = company.codigo_postal || '';
        document.getElementById('telefono').value = company.telefono || '';
        document.getElementById('email').value = company.email || '';
        document.getElementById('contacto_nombre').value = company.contacto_nombre || '';
        document.getElementById('contacto_telefono').value = company.contacto_telefono || '';
        document.getElementById('contacto_email').value = company.contacto_email || '';

        // Configuración
        const config = company.configuracion ? JSON.parse(company.configuracion) : {};
        document.getElementById('horario_entrada').value = config.horario_entrada || '09:00';
        document.getElementById('horario_salida').value = config.horario_salida || '18:00';
        document.getElementById('tolerancia_minutos').value = config.tolerancia_minutos || 15;
        document.getElementById('horas_semana').value = config.horas_semana || 40;
    }

    function closeModal() {
        document.getElementById('companyModal').classList.remove('active');
    }

    // Cerrar modal con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // Validación de RFC en tiempo real
    document.getElementById('rfc')?.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Validación de formulario mejorada
    document.getElementById('companyForm').addEventListener('submit', function(e) {
        const nombre = document.getElementById('nombre').value.trim();
        const rfc = document.getElementById('rfc').value.trim();
        const email = document.getElementById('email').value.trim();
        const contactoEmail = document.getElementById('contacto_email').value.trim();

        // Validar nombre
        if (nombre.length < 2) {
            e.preventDefault();
            alert('El nombre de la empresa debe tener al menos 2 caracteres');
            return false;
        }

        // Validar RFC si se proporciona
        if (rfc && !/^[A-Z]{3,4}[0-9]{6}[A-Z0-9]{3}$/.test(rfc)) {
            e.preventDefault();
            alert('El RFC no tiene un formato válido. Debe ser: 3-4 letras + 6 números + 3 caracteres');
            return false;
        }

        // Validar emails si se proporcionan
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            e.preventDefault();
            alert('El email de la empresa no es válido');
            return false;
        }

        if (contactoEmail && !emailRegex.test(contactoEmail)) {
            e.preventDefault();
            alert('El email del contacto no es válido');
            return false;
        }

        // Validar horarios
        const horarioEntrada = document.getElementById('horario_entrada').value;
        const horarioSalida = document.getElementById('horario_salida').value;

        if (horarioEntrada && horarioSalida && horarioEntrada >= horarioSalida) {
            e.preventDefault();
            alert('La hora de salida debe ser posterior a la hora de entrada');
            return false;
        }
    });

    // Auto-formato de teléfonos
    ['telefono', 'contacto_telefono'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 10) {
                    value = value.slice(0, 10);
                }
                if (value.length >= 6) {
                    value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6);
                } else if (value.length >= 3) {
                    value = value.slice(0, 3) + '-' + value.slice(3);
                }
                this.value = value;
            });
        }
    });

    // Confirmación para acciones críticas
    document.querySelectorAll('form').forEach(form => {
        const action = form.querySelector('input[name="action"]')?.value;
        if (action === 'delete') {
            form.addEventListener('submit', function(e) {
                if (!confirm('¿Está seguro de eliminar esta empresa? Esta acción no se puede deshacer.')) {
                    e.preventDefault();
                }
            });
        }
        if (action === 'toggle') {
            form.addEventListener('submit', function(e) {
                const button = form.querySelector('button[type="submit"]');
                const isActivating = button.textContent.includes('Activar');
                if (!confirm(`¿Está seguro de ${isActivating ? 'activar' : 'desactivar'} esta empresa?`)) {
                    e.preventDefault();
                }
            });
        }
    });

    // Funcionalidad de búsqueda y filtros
    function filterCompanies() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const filterStatus = document.getElementById('filterStatus').value;
        const cards = document.querySelectorAll('.company-card');
        let visibleCount = 0;

        cards.forEach(card => {
            let showCard = true;

            // Filtro por búsqueda
            if (searchTerm) {
                const name = card.dataset.name || '';
                const rfc = card.dataset.rfc || '';
                const address = card.dataset.address || '';
                const contact = card.dataset.contact || '';

                showCard = name.includes(searchTerm) ||
                          rfc.includes(searchTerm) ||
                          address.includes(searchTerm) ||
                          contact.includes(searchTerm);
            }

            // Filtro por estado
            if (showCard && filterStatus !== 'all') {
                const isActive = card.dataset.active === '1';
                const userCount = parseInt(card.dataset.users || 0);

                switch(filterStatus) {
                    case 'active':
                        showCard = isActive;
                        break;
                    case 'inactive':
                        showCard = !isActive;
                        break;
                    case 'with-users':
                        showCard = userCount > 0;
                        break;
                    case 'empty':
                        showCard = userCount === 0;
                        break;
                }
            }

            card.style.display = showCard ? 'block' : 'none';
            if (showCard) visibleCount++;
        });

        // Mostrar mensaje si no hay resultados
        const grid = document.querySelector('.company-grid');
        let noResultsMsg = document.getElementById('noResultsMessage');

        if (visibleCount === 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.id = 'noResultsMessage';
                noResultsMsg.className = 'card';
                noResultsMsg.style.cssText = 'grid-column: 1/-1; text-align: center; padding: 3rem;';
                noResultsMsg.innerHTML = `
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--gray-600); margin-bottom: 0.5rem;">No se encontraron empresas</h3>
                    <p style="color: var(--gray-500);">Intenta con otros términos de búsqueda o filtros</p>
                `;
                grid.appendChild(noResultsMsg);
            }
        } else if (noResultsMsg) {
            noResultsMsg.remove();
        }
    }

    // Event listeners para búsqueda y filtros
    document.getElementById('searchInput').addEventListener('input', filterCompanies);
    document.getElementById('filterStatus').addEventListener('change', filterCompanies);

    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterStatus').value = 'all';
        filterCompanies();
    }

    // Mostrar contador de resultados
    document.getElementById('searchInput').addEventListener('input', function() {
        const cards = document.querySelectorAll('.company-card');
        let visibleCount = 0;
        cards.forEach(card => {
            if (card.style.display !== 'none') visibleCount++;
        });

        // Actualizar contador si existe
        let counter = document.getElementById('resultCounter');
        if (!counter && this.value) {
            counter = document.createElement('div');
            counter.id = 'resultCounter';
            counter.style.cssText = 'color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem;';
            this.parentElement.appendChild(counter);
        }

        if (counter) {
            if (this.value) {
                counter.textContent = `Mostrando ${visibleCount} de ${cards.length} empresas`;
            } else {
                counter.remove();
            }
        }
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>