<?php
/**
 * Gestión de Ubicaciones - Sistema de Asistencia
 * CRUD completo con integración de mapas
 */

session_start();

// Aggressive cache prevention
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /login.php');
    exit;
}

// Solo admin y supervisor pueden gestionar ubicaciones
if (!in_array($_SESSION['user_rol'], ['admin', 'supervisor'])) {
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
                $direccion = trim($_POST['direccion'] ?? '');
                $latitud = floatval($_POST['latitud'] ?? 0);
                $longitud = floatval($_POST['longitud'] ?? 0);
                $radio = intval($_POST['radio_metros'] ?? 100);
                $tipo = $_POST['tipo'] ?? 'sucursal';
                $empresa_id = $_POST['empresa_id'] ?? 1;

                // Debug: Log de coordenadas recibidas
                error_log("Ubicaciones - Acción: $action, Lat: $latitud, Lng: $longitud");

                if (empty($nombre) || empty($direccion)) {
                    throw new Exception('Nombre y dirección son requeridos');
                }

                // Validar coordenadas
                if ($latitud == 0 || $longitud == 0) {
                    throw new Exception('Debe seleccionar una ubicación en el mapa');
                }

                if ($action === 'create') {
                    $stmt = $db->prepare("
                        INSERT INTO ubicaciones (empresa_id, nombre, direccion, latitud, longitud, radio_metros, tipo)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$empresa_id, $nombre, $direccion, $latitud, $longitud, $radio, $tipo]);
                    $message = '✅ Ubicación creada exitosamente';
                } else {
                    $stmt = $db->prepare("
                        UPDATE ubicaciones
                        SET nombre = ?, direccion = ?, latitud = ?, longitud = ?,
                            radio_metros = ?, tipo = ?, empresa_id = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$nombre, $direccion, $latitud, $longitud, $radio, $tipo, $empresa_id, $id]);
                    $message = '✅ Ubicación actualizada exitosamente';
                }
                $messageType = 'success';
                break;

            case 'toggle':
                $id = $_POST['id'] ?? 0;
                $stmt = $db->prepare("UPDATE ubicaciones SET activa = NOT activa WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Estado actualizado';
                $messageType = 'success';
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                // Verificar si tiene asistencias asociadas
                $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM asistencias WHERE ubicacion_id = ?");
                $checkStmt->execute([$id]);
                $result = $checkStmt->fetch();

                if ($result['count'] > 0) {
                    throw new Exception('No se puede eliminar: hay asistencias registradas en esta ubicación');
                }

                $stmt = $db->prepare("DELETE FROM ubicaciones WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Ubicación eliminada';
                $messageType = 'info';
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener todas las ubicaciones
$ubicacionesStmt = $db->query("
    SELECT u.*, e.nombre as empresa_nombre,
           (SELECT COUNT(*) FROM asistencias WHERE ubicacion_id = u.id) as total_asistencias
    FROM ubicaciones u
    LEFT JOIN empresas e ON u.empresa_id = e.id
    ORDER BY u.nombre
");
$ubicaciones = $ubicacionesStmt->fetchAll();

// Obtener empresas para el formulario
$empresasStmt = $db->query("SELECT id, nombre FROM empresas WHERE activa = 1 ORDER BY nombre");
$empresas = $empresasStmt->fetchAll();

$page_title = 'Gestión de Ubicaciones';
$page_subtitle = 'Administración de ubicaciones con geofencing';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Asistencia AlpeFresh</title>

    <link rel="icon" href="/favicon.ico">

    <!-- Common head elements (Font Awesome, Google Fonts, Styles) -->
    <?php include __DIR__ . '/includes/head-common.php'; ?>

    <!-- Leaflet CSS for OpenStreetMap -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        .location-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .location-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .location-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .location-map {
            height: 200px;
            width: 100%;
            background: var(--gray-100);
        }

        .location-info {
            padding: 1.5rem;
        }

        .location-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .location-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 0.25rem;
        }

        .location-type {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .type-oficina_principal {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-sucursal {
            background: #dcfce7;
            color: #166534;
        }

        .type-cliente {
            background: #fef3c7;
            color: #92400e;
        }

        .type-obra {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .location-details {
            margin: 1rem 0;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .location-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .location-actions {
            display: flex;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }

        .btn-small {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
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
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
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
        }

        #mapPicker {
            height: 400px;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .coordinates-display {
            background: var(--gray-50);
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .add-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--navy);
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 100;
        }

        .add-button:hover {
            transform: scale(1.1);
            background: #ffc942;
        }

        @media (max-width: 768px) {
            .location-grid {
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
                    <i class="fas fa-map-marked-alt"></i> <?php echo $page_title; ?>
                </h1>
                <p style="color: var(--gray-600);"><?php echo $page_subtitle; ?></p>
            </div>
            <div>
                <button onclick="openModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Ubicación
                </button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 1.5rem;">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($ubicaciones); ?></div>
                <div class="stat-label">Total Ubicaciones</div>
            </div>
            <div class="stat-card" style="border-left-color: #10b981;">
                <div class="stat-value" style="color: #10b981;">
                    <?php echo count(array_filter($ubicaciones, fn($u) => $u['activa'])); ?>
                </div>
                <div class="stat-label">Activas</div>
            </div>
            <div class="stat-card" style="border-left-color: #3b82f6;">
                <div class="stat-value" style="color: #3b82f6;">
                    <?php echo array_sum(array_column($ubicaciones, 'total_asistencias')); ?>
                </div>
                <div class="stat-label">Asistencias Totales</div>
            </div>
            <div class="stat-card" style="border-left-color: var(--gold);">
                <div class="stat-value" style="color: var(--gold);">
                    <?php echo count(array_unique(array_column($ubicaciones, 'empresa_id'))); ?>
                </div>
                <div class="stat-label">Empresas</div>
            </div>
        </div>

        <!-- Grid de Ubicaciones -->
        <div class="location-grid">
            <?php foreach ($ubicaciones as $ubicacion): ?>
            <div class="location-card">
                <!-- Mapa -->
                <div id="map-<?php echo $ubicacion['id']; ?>" class="location-map"></div>

                <!-- Información -->
                <div class="location-info">
                    <div class="location-header">
                        <div>
                            <div class="location-title">
                                <?php echo htmlspecialchars($ubicacion['nombre']); ?>
                            </div>
                            <span class="location-type type-<?php echo $ubicacion['tipo']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $ubicacion['tipo'])); ?>
                            </span>
                        </div>
                        <span class="status-badge <?php echo $ubicacion['activa'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $ubicacion['activa'] ? 'Activa' : 'Inactiva'; ?>
                        </span>
                    </div>

                    <div class="location-details">
                        <div class="location-detail">
                            <i class="fas fa-map-marker-alt" style="color: var(--gray-400);"></i>
                            <?php echo htmlspecialchars($ubicacion['direccion']); ?>
                        </div>
                        <div class="location-detail">
                            <i class="fas fa-building" style="color: var(--gray-400);"></i>
                            <?php echo htmlspecialchars($ubicacion['empresa_nombre'] ?: 'Sin empresa'); ?>
                        </div>
                        <div class="location-detail">
                            <i class="fas fa-broadcast-tower" style="color: var(--gray-400);"></i>
                            Radio: <?php echo $ubicacion['radio_metros']; ?> metros
                        </div>
                        <div class="location-detail">
                            <i class="fas fa-fingerprint" style="color: var(--gray-400);"></i>
                            <?php echo $ubicacion['total_asistencias']; ?> asistencias registradas
                        </div>
                    </div>

                    <div class="location-actions">
                        <button onclick="editLocation(<?php echo htmlspecialchars(json_encode($ubicacion)); ?>)"
                                class="btn btn-small btn-secondary">
                            <i class="fas fa-edit"></i> Editar
                        </button>

                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $ubicacion['id']; ?>">
                            <button type="submit"
                                    class="btn btn-small <?php echo $ubicacion['activa'] ? 'btn-secondary' : 'btn-accent'; ?>">
                                <i class="fas fa-power-off"></i>
                                <?php echo $ubicacion['activa'] ? 'Desactivar' : 'Activar'; ?>
                            </button>
                        </form>

                        <a href="https://www.openstreetmap.org/?mlat=<?php echo $ubicacion['latitud']; ?>&mlon=<?php echo $ubicacion['longitud']; ?>&zoom=16"
                           target="_blank"
                           class="btn btn-small btn-primary">
                            <i class="fas fa-external-link-alt"></i> Ver Mapa
                        </a>

                        <?php if ($ubicacion['total_asistencias'] == 0): ?>
                        <form method="POST" style="display: inline;"
                              onsubmit="return confirm('¿Eliminar esta ubicación?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $ubicacion['id']; ?>">
                            <button type="submit" class="btn btn-small"
                                    style="background: #ef4444; color: white;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($ubicaciones)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <i class="fas fa-map-marked-alt" style="font-size: 4rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--gray-600); margin-bottom: 0.5rem;">No hay ubicaciones configuradas</h3>
            <p style="color: var(--gray-500);">Haz clic en "Nueva Ubicación" para agregar la primera</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Crear/Editar -->
    <div id="locationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Ubicación</h2>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">
                    &times;
                </button>
            </div>

            <form method="POST" id="locationForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="locationId">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dirección *</label>
                        <input type="text" name="direccion" id="direccion" class="form-control" required>
                    </div>

                    <div class="grid grid-cols-2" style="gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Empresa</label>
                            <select name="empresa_id" id="empresa_id" class="form-control">
                                <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo $empresa['id']; ?>">
                                    <?php echo htmlspecialchars($empresa['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" id="tipo" class="form-control">
                                <option value="oficina_principal">Oficina Principal</option>
                                <option value="sucursal">Sucursal</option>
                                <option value="cliente">Cliente</option>
                                <option value="obra">Obra</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Radio de Geofencing (metros)</label>
                        <input type="number" name="radio_metros" id="radio_metros"
                               class="form-control" value="100" min="10" max="1000">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ubicación en el Mapa</label>
                        <div id="mapPicker"></div>
                        <div class="coordinates-display">
                            <i class="fas fa-map-pin"></i>
                            Latitud: <span id="latDisplay">0</span>,
                            Longitud: <span id="lngDisplay">0</span>
                        </div>
                        <input type="hidden" name="latitud" id="latitud" value="19.4326">
                        <input type="hidden" name="longitud" id="longitud" value="-99.1332">
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Haz clic en el mapa para seleccionar la ubicación exacta
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    let mapPicker;
    let marker;
    let locationMaps = {};

    // Inicializar mapas pequeños para cada ubicación
    <?php foreach ($ubicaciones as $ubicacion): ?>
    (function() {
        const map = L.map('map-<?php echo $ubicacion['id']; ?>', {
            center: [<?php echo $ubicacion['latitud']; ?>, <?php echo $ubicacion['longitud']; ?>],
            zoom: 15,
            scrollWheelZoom: false,
            zoomControl: false
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        const marker = L.marker([<?php echo $ubicacion['latitud']; ?>, <?php echo $ubicacion['longitud']; ?>]).addTo(map);

        // Círculo de geofencing
        L.circle([<?php echo $ubicacion['latitud']; ?>, <?php echo $ubicacion['longitud']; ?>], {
            color: '#fdb714',
            fillColor: '#fdb714',
            fillOpacity: 0.2,
            radius: <?php echo $ubicacion['radio_metros']; ?>
        }).addTo(map);

        locationMaps[<?php echo $ubicacion['id']; ?>] = map;
    })();
    <?php endforeach; ?>

    function openModal() {
        document.getElementById('locationModal').classList.add('active');
        document.getElementById('modalTitle').textContent = 'Nueva Ubicación';
        document.getElementById('formAction').value = 'create';
        document.getElementById('locationForm').reset();

        // Inicializar mapa del modal
        setTimeout(() => {
            if (!mapPicker) {
                mapPicker = L.map('mapPicker').setView([19.4326, -99.1332], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(mapPicker);

                mapPicker.on('click', function(e) {
                    placeMarker(e.latlng.lat, e.latlng.lng);
                });
            }
            mapPicker.invalidateSize();
        }, 100);
    }

    function editLocation(location) {
        console.log('Editando ubicación:', location);
        document.getElementById('locationModal').classList.add('active');
        document.getElementById('modalTitle').textContent = 'Editar Ubicación';
        document.getElementById('formAction').value = 'update';

        // Llenar formulario
        document.getElementById('locationId').value = location.id;
        document.getElementById('nombre').value = location.nombre;
        document.getElementById('direccion').value = location.direccion || '';
        document.getElementById('empresa_id').value = location.empresa_id || '';
        document.getElementById('tipo').value = location.tipo || 'oficina_principal';
        document.getElementById('radio_metros').value = location.radio_metros || 100;

        // Asegurar que las coordenadas se establecen correctamente
        const lat = parseFloat(location.latitud);
        const lng = parseFloat(location.longitud);

        document.getElementById('latitud').value = lat;
        document.getElementById('longitud').value = lng;
        document.getElementById('latDisplay').textContent = lat.toFixed(7);
        document.getElementById('lngDisplay').textContent = lng.toFixed(7);

        console.log('Coordenadas establecidas:', lat, lng);

        // Actualizar mapa
        setTimeout(() => {
            if (!mapPicker) {
                mapPicker = L.map('mapPicker').setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(mapPicker);

                mapPicker.on('click', function(e) {
                    console.log('Click en mapa:', e.latlng);
                    placeMarker(e.latlng.lat, e.latlng.lng);
                });
            } else {
                mapPicker.setView([lat, lng], 15);
            }

            // Colocar marcador en las coordenadas existentes
            placeMarker(lat, lng);
            mapPicker.invalidateSize();
        }, 100);
    }

    function placeMarker(lat, lng) {
        console.log('Colocando marcador en:', lat, lng);

        // Asegurar que lat y lng son números válidos
        lat = parseFloat(lat);
        lng = parseFloat(lng);

        if (isNaN(lat) || isNaN(lng)) {
            console.error('Coordenadas inválidas:', lat, lng);
            return;
        }

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(mapPicker);
        }

        // Actualizar todos los campos de coordenadas
        document.getElementById('latitud').value = lat.toFixed(7);
        document.getElementById('longitud').value = lng.toFixed(7);
        document.getElementById('latDisplay').textContent = lat.toFixed(7);
        document.getElementById('lngDisplay').textContent = lng.toFixed(7);

        console.log('Valores establecidos en campos ocultos:',
            document.getElementById('latitud').value,
            document.getElementById('longitud').value);
    }

    function closeModal() {
        document.getElementById('locationModal').classList.remove('active');
    }

    // Cerrar modal con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // Validar formulario antes de enviar
    document.getElementById('locationForm').addEventListener('submit', function(e) {
        const lat = document.getElementById('latitud').value;
        const lng = document.getElementById('longitud').value;

        console.log('Enviando formulario con coordenadas:', lat, lng);

        // Verificar que las coordenadas estén presentes
        if (!lat || !lng || lat == 0 || lng == 0) {
            e.preventDefault();
            alert('Por favor, selecciona una ubicación en el mapa haciendo clic sobre él.');
            return false;
        }

        // Verificar que las coordenadas sean válidas
        const latNum = parseFloat(lat);
        const lngNum = parseFloat(lng);

        if (isNaN(latNum) || isNaN(lngNum)) {
            e.preventDefault();
            alert('Las coordenadas no son válidas. Por favor, selecciona una ubicación en el mapa.');
            return false;
        }

        // Asegurar que los valores están en los campos antes de enviar
        document.getElementById('latitud').value = latNum;
        document.getElementById('longitud').value = lngNum;

        console.log('Formulario validado, enviando...');
        return true;
    });

    // Búsqueda de dirección usando Nominatim (OpenStreetMap)
    document.getElementById('direccion').addEventListener('blur', function() {
        const address = this.value;
        if (address.length > 10) {
            console.log('Buscando dirección:', address);
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`)
                .then(response => response.json())
                .then(data => {
                    if (data && data[0]) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        console.log('Dirección encontrada:', lat, lng);
                        placeMarker(lat, lng);
                        mapPicker.setView([lat, lng], 15);
                    } else {
                        console.log('No se encontraron resultados para:', address);
                    }
                })
                .catch(error => console.error('Error geocoding:', error));
        }
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>