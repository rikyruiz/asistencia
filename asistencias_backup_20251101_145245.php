<?php
/**
 * Sistema de Marcaje de Asistencia
 * Control de entrada/salida con geolocalización
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

require_once 'config/database.php';

$db = db();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_rol'] ?? 'empleado';
$message = '';
$messageType = '';

// Procesar marcaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $ubicacionId = $_POST['ubicacion_id'] ?? null;
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    $notas = $_POST['notas'] ?? '';

    try {
        if ($action === 'entrada') {
            // Verificar si ya hay una entrada sin salida
            $checkStmt = $db->prepare("
                SELECT id FROM asistencias
                WHERE usuario_id = ? AND DATE(entrada) = CURDATE() AND salida IS NULL
            ");
            $checkStmt->execute([$userId]);

            if ($checkStmt->fetch()) {
                $message = 'Ya tienes una entrada registrada hoy sin salida';
                $messageType = 'warning';
            } else {
                // Registrar entrada
                $stmt = $db->prepare("
                    INSERT INTO asistencias (usuario_id, entrada, ubicacion_id, tipo, notas)
                    VALUES (?, NOW(), ?, 'entrada', ?)
                ");
                $stmt->execute([$userId, $ubicacionId, $notas]);
                $message = '✅ Entrada registrada exitosamente';
                $messageType = 'success';
            }
        } elseif ($action === 'salida') {
            // Buscar la última entrada sin salida
            $findStmt = $db->prepare("
                SELECT id, ubicacion_id FROM asistencias
                WHERE usuario_id = ? AND DATE(entrada) = CURDATE() AND salida IS NULL
                ORDER BY entrada DESC LIMIT 1
            ");
            $findStmt->execute([$userId]);
            $asistencia = $findStmt->fetch();

            if ($asistencia) {
                // Verificar si está fuera de rango
                $fueraDeRango = false;
                $mensajeAdvertencia = '';

                if ($lat && $lng && $ubicacionId) {
                    // Obtener la ubicación para verificar distancia
                    $ubicStmt = $db->prepare("
                        SELECT latitud, longitud, radio_metros, nombre
                        FROM ubicaciones
                        WHERE id = ?
                    ");
                    $ubicStmt->execute([$ubicacionId]);
                    $ubicacion = $ubicStmt->fetch();

                    if ($ubicacion) {
                        // Calcular distancia usando la fórmula de Haversine
                        $earthRadius = 6371000; // metros
                        $lat1 = deg2rad($lat);
                        $lng1 = deg2rad($lng);
                        $lat2 = deg2rad($ubicacion['latitud']);
                        $lng2 = deg2rad($ubicacion['longitud']);

                        $dLat = $lat2 - $lat1;
                        $dLng = $lng2 - $lng1;

                        $a = sin($dLat/2) * sin($dLat/2) +
                             cos($lat1) * cos($lat2) *
                             sin($dLng/2) * sin($dLng/2);
                        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                        $distancia = $earthRadius * $c;

                        if ($distancia > $ubicacion['radio_metros']) {
                            $fueraDeRango = true;
                            $mensajeAdvertencia = ' (registrada desde ubicación no autorizada: ' .
                                                round($distancia) . 'm de ' .
                                                htmlspecialchars($ubicacion['nombre']) . ')';
                        }
                    }
                }

                // Registrar salida con coordenadas y flag
                $updateStmt = $db->prepare("
                    UPDATE asistencias
                    SET salida = NOW(),
                        tipo = 'salida',
                        lat_salida = ?,
                        lon_salida = ?,
                        fuera_de_rango = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $lat,
                    $lng,
                    $fueraDeRango ? 1 : 0,
                    $asistencia['id']
                ]);

                $message = '✅ Salida registrada exitosamente' . $mensajeAdvertencia;
                $messageType = $fueraDeRango ? 'warning' : 'success';
            } else {
                $message = 'No hay entrada registrada para hoy';
                $messageType = 'error';
            }
        }
    } catch (Exception $e) {
        error_log("Error en marcaje: " . $e->getMessage());
        $message = 'Error al procesar el marcaje';
        $messageType = 'error';
    }
}

// Obtener ubicaciones configuradas
$ubicacionesStmt = $db->query("
    SELECT id, nombre, direccion, latitud, longitud, radio_metros
    FROM ubicaciones
    WHERE activa = 1
    ORDER BY nombre
");
$ubicaciones = $ubicacionesStmt->fetchAll();

// Obtener estado actual del usuario (con mejor detección)
$estadoStmt = $db->prepare("
    SELECT id, entrada, salida, ubicacion_id,
           CASE
               WHEN salida IS NULL THEN 'checked_in'
               ELSE 'completed'
           END as estado
    FROM asistencias
    WHERE usuario_id = ? AND DATE(entrada) = CURDATE()
    ORDER BY entrada DESC
    LIMIT 1
");
$estadoStmt->execute([$userId]);
$estadoActual = $estadoStmt->fetch();

// Determinar el estado: tiene entrada sin salida = debe marcar salida
$tieneEntrada = $estadoActual && is_null($estadoActual['salida']);
$estadoActualTexto = $tieneEntrada ? 'Ya marcaste entrada. Ahora puedes marcar salida.' : 'No has marcado entrada hoy.';

// Debug info (solo para development)
$debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debugMode) {
    error_log("Estado actual - Usuario ID: $userId");
    error_log("Registro encontrado: " . ($estadoActual ? 'SI' : 'NO'));
    if ($estadoActual) {
        error_log("ID: " . $estadoActual['id']);
        error_log("Entrada: " . $estadoActual['entrada']);
        error_log("Salida: " . ($estadoActual['salida'] ?? 'NULL'));
        error_log("Estado: " . $estadoActual['estado']);
    }
    error_log("Tiene entrada sin salida: " . ($tieneEntrada ? 'SI' : 'NO'));
}

// Obtener historial de hoy
$historialStmt = $db->prepare("
    SELECT a.*, u.nombre as ubicacion_nombre,
           a.lat_salida, a.lon_salida, a.fuera_de_rango
    FROM asistencias a
    LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
    WHERE a.usuario_id = ? AND DATE(a.entrada) = CURDATE()
    ORDER BY a.entrada DESC
");
$historialStmt->execute([$userId]);
$historialHoy = $historialStmt->fetchAll();

// Detectar si es móvil (más preciso)
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isMobile = preg_match('/(android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile)/i', $userAgent)
    && !preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $userAgent);

$page_title = 'Marcaje de Asistencia';
$page_subtitle = 'Control de Entrada y Salida';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="theme-color" content="#001f3f">
    <title><?php echo $page_title; ?> - Sistema de Asistencia AlpeFresh</title>

    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/images/icon-192.png">

    <!-- Common head elements (Font Awesome, Google Fonts, Styles) -->
    <?php include __DIR__ . '/includes/head-common.php'; ?>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        /* Botón de GPS fijo */
        .gps-activate-button {
            position: fixed !important;
            bottom: 100px !important;
            right: 20px !important;
            z-index: 9999 !important;
            background: linear-gradient(135deg, #001f3f 0%, #004080 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 50px !important;
            padding: 15px 30px !important;
            font-size: 16px !important;
            font-weight: bold !important;
            cursor: pointer !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3) !important;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .gps-activate-button:hover {
            transform: scale(1.1) !important;
        }

        /* Estilos Desktop */
        .desktop-header {
            display: block;
            width: 100%;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .desktop-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .desktop-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .clock-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .clock-time {
            font-size: 3rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 0.5rem;
            font-family: 'Courier New', monospace;
        }

        .clock-date {
            font-size: 1.25rem;
            color: var(--gray-600);
            margin-bottom: 2rem;
        }

        .location-status {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .location-status.in-range {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .location-status.out-range {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .location-status.checking {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
        }

        .btn-clock {
            padding: 1.5rem 3rem;
            font-size: 1.5rem;
            font-weight: 700;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-clock.entrada {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-clock.entrada:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-clock.salida {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-clock.salida:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-clock:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        .location-list {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .location-item {
            padding: 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .location-item.in-range {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .distance-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .history-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        #mapContainer {
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }

        /* Estilos móviles */
        @media (max-width: 768px) {
            .desktop-header { display: none !important; }
            #desktop-header-container { display: none !important; }
            .mobile-header { display: block !important; }
            .desktop-container { display: none !important; }
            .mobile-container { display: block !important; }
            .bottom-nav { display: flex !important; }

            .mobile-header {
                background: linear-gradient(135deg, var(--navy) 0%, #004080 100%);
                color: white;
                padding: 1rem;
                position: sticky;
                top: 0;
                z-index: 100;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            .mobile-header h1 {
                font-size: 1.25rem;
                margin: 0;
                font-weight: 600;
            }

            .mobile-header .user-info {
                font-size: 0.875rem;
                opacity: 0.9;
                margin-top: 0.25rem;
            }

            .mobile-header button:active {
                background: rgba(255,255,255,0.3) !important;
                transform: scale(0.95);
            }

            .mobile-menu nav a {
                transition: all 0.2s ease;
                border-radius: 8px;
                margin: 0.25rem 0;
            }

            .mobile-menu nav a:hover,
            .mobile-menu nav a:active {
                background: var(--gray-100) !important;
                padding-left: 1rem !important;
            }

            .mobile-menu nav a i {
                min-width: 20px;
                text-align: center;
            }

            .mobile-container {
                max-width: 100%;
                padding: 0;
                margin: 0;
                min-height: 100vh;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            }

            .clock-container {
                margin: 1rem;
                padding: 1.5rem;
                border-radius: 20px;
            }

            .clock-time {
                font-size: 2.5rem;
                letter-spacing: -1px;
            }

            .clock-date {
                font-size: 1rem;
                margin-bottom: 1rem;
            }

            .location-status {
                margin: 1rem;
                padding: 0.75rem;
                font-size: 0.9rem;
                font-weight: 500;
            }

            .btn-clock {
                width: 90%;
                max-width: 300px;
                padding: 1.25rem;
                font-size: 1.25rem;
                font-weight: 600;
                border-radius: 16px;
                margin: 1rem auto;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .btn-clock.entrada:active:not(:disabled),
            .btn-clock.salida:active:not(:disabled) {
                transform: scale(0.98);
            }

            .btn-clock:disabled {
                opacity: 0.4;
                background: var(--gray-300);
            }

            .mobile-section {
                background: white;
                margin: 1rem;
                border-radius: 16px;
                padding: 1rem;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }

            #mapContainer {
                height: 300px;
                border-radius: 12px;
            }

            .bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                display: flex;
                justify-content: space-around;
                padding: 0.5rem 0;
                z-index: 100;
            }

            .nav-item {
                flex: 1;
                text-align: center;
                padding: 0.5rem;
                color: var(--gray-600);
                text-decoration: none;
                font-size: 0.75rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.25rem;
            }

            .nav-item.active {
                color: var(--navy);
            }

            .nav-item i {
                font-size: 1.25rem;
            }

            body {
                padding-bottom: 60px;
            }
        }

        /* Desktop only styles */
        @media (min-width: 769px) {
            .desktop-header { display: block !important; }
            .mobile-header { display: none !important; }
            .mobile-container { display: none !important; }
            .bottom-nav { display: none !important; }
            .mobile-menu { display: none !important; }
            #menuOverlay { display: none !important; }

            .desktop-grid {
                display: grid;
            }

            .desktop-container {
                display: block !important;
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Botón GPS se moverá dentro del formulario -->

    <style>
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>

    <script>
        // Función global para activar UBICACIÓN (no solo GPS)
        function requestGPSLocation() {
            console.log('=== ACTIVANDO UBICACIÓN (Múltiples fuentes) ===');
            // Buscar el botón que esté visible (desktop o mobile)
            let btn = document.getElementById('gps-button-desktop');
            if (!btn || btn.offsetParent === null) {
                btn = document.getElementById('gps-button-mobile');
            }
            if (!btn) {
                btn = document.getElementById('gps-button-html');
            }
            if (!btn) return;

            btn.innerHTML = '⏳ Obteniendo ubicación...';
            btn.disabled = true;

            if (!navigator.geolocation) {
                btn.innerHTML = '❌ No soportado';
                // Intentar obtener por IP
                fetchIPLocation();
                return;
            }

            // ESTRATEGIA 1: Primero intentar con BAJA precisión (más rápido, usa WiFi/IP)
            console.log('Intentando ubicación con baja precisión (WiFi/IP)...');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    console.log('✅ Ubicación obtenida (baja precisión):', position);
                    handleLocationSuccess(position, btn);

                    // Luego intentar mejorar con alta precisión
                    setTimeout(() => {
                        console.log('Mejorando precisión...');
                        navigator.geolocation.getCurrentPosition(
                            function(betterPosition) {
                                console.log('✅ Ubicación mejorada (alta precisión):', betterPosition);
                                handleLocationSuccess(betterPosition, btn);
                            },
                            function() {
                                // Si falla alta precisión, mantener la baja
                                console.log('No se pudo mejorar precisión, manteniendo ubicación actual');
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 5000,
                                maximumAge: 0
                            }
                        );
                    }, 1000);
                },
                function(error) {
                    console.error('Error con baja precisión, intentando alta precisión...');

                    // ESTRATEGIA 2: Si falla baja precisión, intentar con ALTA (GPS)
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            console.log('✅ Ubicación obtenida (alta precisión):', position);
                            handleLocationSuccess(position, btn);
                        },
                        function(error2) {
                            console.error('❌ Error con ambas precisiones:', error2);

                            // ESTRATEGIA 3: Como último recurso, usar IP
                            if (error2.code === 1) {
                                btn.innerHTML = '📍 Permisos denegados - Usando ubicación aproximada';
                                btn.style.background = '#f59e0b';
                                fetchIPLocation();
                            } else {
                                btn.innerHTML = '❌ Error - Click para reintentar';
                                btn.style.background = '#dc2626';
                                btn.disabled = false;
                            }
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 30000 // Permitir caché de 30 segundos
                        }
                    );
                },
                {
                    enableHighAccuracy: false, // IMPORTANTE: false para usar WiFi/IP primero
                    timeout: 5000, // Timeout más corto
                    maximumAge: 60000 // Permitir caché de 1 minuto
                }
            );
        }

        // Función para manejar ubicación exitosa
        function handleLocationSuccess(position, btn) {
            const accuracy = position.coords.accuracy;
            console.log('Ubicación obtenida con precisión de:', accuracy, 'metros');

            // Actualizar UI según la precisión
            if (accuracy < 50) {
                btn.innerHTML = '✅ Ubicación PRECISA';
                btn.style.background = '#10b981';
            } else if (accuracy < 200) {
                btn.innerHTML = '✅ Ubicación BUENA';
                btn.style.background = '#22c55e';
            } else if (accuracy < 1000) {
                btn.innerHTML = '✅ Ubicación APROXIMADA';
                btn.style.background = '#f59e0b';
            } else {
                btn.innerHTML = '⚠️ Ubicación IMPRECISA';
                btn.style.background = '#f59e0b';
            }

            // Guardar coordenadas globalmente
            window.userLat = position.coords.latitude;
            window.userLng = position.coords.longitude;

            // Actualizar campos del formulario
            ['lat', 'lng', 'lat-mobile', 'lng-mobile'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = id.includes('lat') ? window.userLat : window.userLng;
            });

            // Actualizar estado de ubicación en la UI
            ['locationStatus', 'locationStatus-mobile'].forEach(id => {
                const statusEl = document.getElementById(id);
                if (statusEl) {
                    statusEl.className = 'location-status in-range';
                    statusEl.innerHTML = '<i class="fas fa-check-circle"></i><span>Ubicación activa</span>';
                }
            });

            // Actualizar mapa si existe
            if (typeof updateUserPositionOnMap === 'function') {
                updateUserPositionOnMap(window.userLat, window.userLng);
            }

            // Verificar geofences si existe
            if (typeof checkGeofences === 'function') {
                checkGeofences();
            }

            // Ocultar mensajes de ayuda
            const helpDiv = document.getElementById('locationHelp');
            if (helpDiv) helpDiv.style.display = 'none';

            // Ocultar botón después de 3 segundos
            setTimeout(function() {
                btn.style.display = 'none';
                // Iniciar tracking continuo
                if (typeof startWatchingLocation === 'function') {
                    startWatchingLocation();
                }
            }, 3000);
        }

        // Función para obtener ubicación por IP
        function fetchIPLocation() {
            console.log('Intentando ubicación por IP...');
            const btn = document.getElementById('gps-button-html');

            // Usar servicio gratuito de geolocalización por IP
            fetch('https://ipapi.co/json/')
                .then(response => response.json())
                .then(data => {
                    console.log('Ubicación por IP:', data);

                    if (data.latitude && data.longitude) {
                        btn.innerHTML = '📍 Ubicación por IP (aproximada)';
                        btn.style.background = '#f59e0b';

                        // Crear un objeto position simulado
                        const fakePosition = {
                            coords: {
                                latitude: data.latitude,
                                longitude: data.longitude,
                                accuracy: 5000, // 5km de precisión aproximada
                                altitude: null,
                                altitudeAccuracy: null,
                                heading: null,
                                speed: null
                            },
                            timestamp: Date.now()
                        };

                        // Usar la ubicación
                        window.userLat = data.latitude;
                        window.userLng = data.longitude;

                        // Actualizar campos del formulario
                        ['lat', 'lng', 'lat-mobile', 'lng-mobile'].forEach(id => {
                            const el = document.getElementById(id);
                            if (el) el.value = id.includes('lat') ? window.userLat : window.userLng;
                        });

                        // Actualizar estado de ubicación
                        ['locationStatus', 'locationStatus-mobile'].forEach(id => {
                            const statusEl = document.getElementById(id);
                            if (statusEl) {
                                statusEl.className = 'location-status checking';
                                statusEl.innerHTML = '<i class="fas fa-map-marker-alt"></i><span>Ubicación aproximada (IP)</span>';
                            }
                        });

                        // Actualizar mapa
                        if (typeof updateUserPositionOnMap === 'function') {
                            updateUserPositionOnMap(window.userLat, window.userLng);
                        }

                        // Verificar geofences
                        if (typeof checkGeofences === 'function') {
                            checkGeofences();
                        }

                        // Mostrar información
                        console.log(`Ubicación aproximada: ${data.city}, ${data.region}, ${data.country_name}`);

                        setTimeout(() => {
                            btn.style.display = 'none';
                            if (typeof startWatchingLocation === 'function') {
                                // Intentar mejorar la ubicación con watchPosition
                                startWatchingLocation();
                            }
                        }, 3000);
                    } else {
                        btn.innerHTML = '❌ No se pudo obtener ubicación';
                        btn.style.background = '#dc2626';
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error obteniendo ubicación por IP:', error);
                    btn.innerHTML = '❌ Error de red';
                    btn.style.background = '#dc2626';
                    btn.disabled = false;
                });
        }

        // Auto-ocultar si ya hay ubicación
        setTimeout(function() {
            if (typeof userLat !== 'undefined' && userLat !== null) {
                const btn = document.getElementById('gps-button-html');
                if (btn) btn.style.display = 'none';
            }
        }, 3000);
    </script>
    <!-- Desktop Header (always show on non-mobile) -->
    <div class="desktop-header" id="desktop-header-container">
        <?php include 'includes/header.php'; ?>
    </div>

    <!-- Mobile Header -->
    <div class="mobile-header" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="fas fa-clock"></i> Asistencia AlpeFresh</h1>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario'); ?>
                </div>
            </div>
            <button onclick="toggleMenu()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; padding: 0.75rem; cursor: pointer; border-radius: 8px; min-width: 50px; min-height: 50px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="mobile-menu" style="position: fixed; top: 0; right: -300px; width: 280px; height: 100vh; background: white; box-shadow: -2px 0 10px rgba(0,0,0,0.2); z-index: 1001; transition: right 0.3s ease; overflow-y: auto;">
        <div style="background: var(--navy); color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; flex: 1; font-size: 1.25rem;"><i class="fas fa-bars" style="margin-right: 0.5rem;"></i>Menú</h3>
            <button onclick="toggleMenu()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; border-radius: 8px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- User Info in Menu -->
        <div style="padding: 1rem; background: var(--gray-50); border-bottom: 2px solid var(--gray-200); display: flex; align-items: center; gap: 0.75rem;">
            <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--navy); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold;">
                <i class="fas fa-user"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; color: var(--navy); font-size: 1rem;"><?php echo htmlspecialchars(explode(' ', $_SESSION['user_nombre'] ?? 'Usuario')[0]); ?></div>
                <div style="font-size: 0.875rem; color: var(--gray-600);"><?php echo ucfirst($_SESSION['user_rol'] ?? 'empleado'); ?></div>
            </div>
        </div>

        <nav style="padding: 1rem;">
            <a href="/dashboard.php" style="display: block; padding: 0.75rem; color: var(--navy); text-decoration: none; border-bottom: 1px solid var(--gray-200);">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="/asistencias.php" style="display: block; padding: 0.75rem; color: var(--navy); text-decoration: none; border-bottom: 1px solid var(--gray-200); background: var(--gray-100);">
                <i class="fas fa-clock"></i> Marcar Asistencia
            </a>
            <?php if ($userRole === 'admin' || $userRole === 'rrhh'): ?>
            <a href="/usuarios.php" style="display: block; padding: 0.75rem; color: var(--navy); text-decoration: none; border-bottom: 1px solid var(--gray-200);">
                <i class="fas fa-users"></i> Usuarios
            </a>
            <a href="/ubicaciones.php" style="display: block; padding: 0.75rem; color: var(--navy); text-decoration: none; border-bottom: 1px solid var(--gray-200);">
                <i class="fas fa-map-marker-alt"></i> Ubicaciones
            </a>
            <a href="/empresas.php" style="display: block; padding: 0.75rem; color: var(--navy); text-decoration: none; border-bottom: 1px solid var(--gray-200);">
                <i class="fas fa-building"></i> Empresas
            </a>
            <?php endif; ?>
            <a href="/reportes.php" style="display: block; padding: 0.75rem; color: var(--navy); text-decoration: none; border-bottom: 1px solid var(--gray-200);">
                <i class="fas fa-chart-bar"></i> Reportes
            </a>
            <a href="/perfil.php" style="display: block; padding: 0.75rem; color: var(--navy); text-decoration: none; border-bottom: 1px solid var(--gray-200);">
                <i class="fas fa-user"></i> Mi Perfil
            </a>
            <?php if ($userRole === 'admin'): ?>
            <a href="/configuracion.php" style="display: block; padding: 0.75rem; color: var(--navy); text-decoration: none; border-bottom: 1px solid var(--gray-200);">
                <i class="fas fa-cog"></i> Configuración
            </a>
            <?php endif; ?>
            <hr style="margin: 1rem 0;">
            <a href="/logout.php" style="display: block; padding: 0.75rem; color: #dc2626; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </nav>
    </div>

    <!-- Menu Overlay -->
    <div id="menuOverlay" onclick="toggleMenu()" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; transition: opacity 0.3s ease;"></div>

    <!-- Desktop Container -->
    <div class="desktop-container">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem;">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Debug Panel (activar con ?debug=1) -->
        <?php if ($debugMode): ?>
        <div style="background: #1f2937; color: #10b981; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-family: monospace; font-size: 0.9rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <strong style="color: #fbbf24;"><i class="fas fa-bug"></i> DEBUG MODE</strong>
                <span style="color: #9ca3af; font-size: 0.8rem;">Usuario ID: <?php echo $userId; ?></span>
            </div>
            <hr style="border-color: #374151; margin: 0.5rem 0;">
            <div style="display: grid; grid-template-columns: 200px 1fr; gap: 0.5rem;">
                <div style="color: #9ca3af;">Registro encontrado:</div>
                <div><?php echo $estadoActual ? '<span style="color: #10b981;">✓ SÍ</span>' : '<span style="color: #ef4444;">✗ NO</span>'; ?></div>

                <?php if ($estadoActual): ?>
                <div style="color: #9ca3af;">ID Registro:</div>
                <div><?php echo $estadoActual['id']; ?></div>

                <div style="color: #9ca3af;">Hora Entrada:</div>
                <div><?php echo $estadoActual['entrada']; ?></div>

                <div style="color: #9ca3af;">Hora Salida:</div>
                <div><?php echo $estadoActual['salida'] ? $estadoActual['salida'] : '<span style="color: #fbbf24;">NULL (sin salida)</span>'; ?></div>

                <div style="color: #9ca3af;">Estado Calculado:</div>
                <div><?php echo $estadoActual['estado']; ?></div>
                <?php endif; ?>

                <div style="color: #9ca3af;">Tiene Entrada Activa:</div>
                <div><?php echo $tieneEntrada ? '<span style="color: #10b981;">✓ SÍ - Debe marcar SALIDA</span>' : '<span style="color: #fbbf24;">✗ NO - Debe marcar ENTRADA</span>'; ?></div>

                <div style="color: #9ca3af;">Botón que se muestra:</div>
                <div><strong style="color: #60a5fa;"><?php echo $tieneEntrada ? 'MARCAR SALIDA' : 'MARCAR ENTRADA'; ?></strong></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="desktop-grid">
            <!-- Panel Principal -->
            <div>
                <!-- Reloj -->
                <div class="clock-container">
                    <div class="clock-time" id="clock">00:00:00</div>
                    <div class="clock-date" id="date">--</div>

                    <!-- Estado de Asistencia del Día -->
                    <div style="padding: 1rem; margin: 1rem 0; border-radius: 8px; text-align: center; font-weight: 600; <?php echo $tieneEntrada ? 'background: #dcfce7; border: 2px solid #10b981; color: #166534;' : 'background: #fef3c7; border: 2px solid #f59e0b; color: #92400e;'; ?>">
                        <i class="fas fa-<?php echo $tieneEntrada ? 'check-circle' : 'clock'; ?>"></i>
                        <?php echo $estadoActualTexto; ?>
                        <?php if ($estadoActual): ?>
                            <br><small style="font-size: 0.85rem; opacity: 0.9;">Entrada: <?php echo date('h:i A', strtotime($estadoActual['entrada'])); ?></small>
                        <?php endif; ?>
                    </div>

                    <!-- Estado de Ubicación -->
                    <div class="location-status checking" id="locationStatus">
                        <i class="fas fa-satellite-dish pulse"></i>
                        <span>Verificando ubicación...</span>
                    </div>


                    <!-- Botón de Marcaje -->
                    <form method="POST" id="clockForm">
                        <input type="hidden" name="ubicacion_id" id="ubicacionId">
                        <input type="hidden" name="lat" id="lat">
                        <input type="hidden" name="lng" id="lng">

                        <?php if (!$tieneEntrada): ?>
                            <input type="hidden" name="action" value="entrada">
                            <button type="submit" class="btn-clock entrada" id="clockBtn" disabled>
                                <i class="fas fa-sign-in-alt"></i>
                                MARCAR ENTRADA
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="salida">
                            <button type="submit" class="btn-clock salida" id="clockBtn" disabled>
                                <i class="fas fa-sign-out-alt"></i>
                                MARCAR SALIDA
                            </button>
                        <?php endif; ?>

                        <!-- Botones de GPS y Reintentar -->
                        <div style="display: flex; gap: 1rem; margin-top: 1rem; justify-content: center;">
                            <button type="button" id="gps-button-desktop" onclick="requestGPSLocation()" style="
                                padding: 0.75rem 1.5rem;
                                background: linear-gradient(135deg, #001f3f, #004080);
                                color: white;
                                border: none;
                                border-radius: 8px;
                                font-size: 1rem;
                                font-weight: bold;
                                cursor: pointer;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                                flex: 1;
                                max-width: 200px;
                            ">
                                <i class="fas fa-location-arrow"></i> Activar GPS
                            </button>

                            <button type="button" onclick="retryLocation()" id="retryBtn" style="
                                padding: 0.75rem 1.5rem;
                                background: var(--navy);
                                color: white;
                                border: none;
                                border-radius: 8px;
                                font-size: 1rem;
                                font-weight: bold;
                                cursor: pointer;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                                flex: 1;
                                max-width: 200px;
                                display: none;
                            ">
                                <i class="fas fa-redo"></i> Reintentar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Historial del día -->
                <?php if ($historialHoy): ?>
                <div class="card">
                    <h3 style="margin-top: 0; color: var(--navy);">
                        <i class="fas fa-history"></i> Tu actividad de hoy
                    </h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Ubicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historialHoy as $registro): ?>
                            <tr>
                                <td>
                                    <?php echo date('h:i A', strtotime($registro['entrada'])); ?>
                                    <?php if ($registro['salida']): ?>
                                    - <?php echo date('h:i A', strtotime($registro['salida'])); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-success">Entrada</span>
                                    <?php if ($registro['salida']): ?>
                                    <span class="badge badge-danger">Salida</span>
                                    <?php if ($registro['fuera_de_rango']): ?>
                                    <span class="badge badge-warning" title="Salida registrada fuera del rango permitido">⚠️ Fuera de rango</span>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($registro['ubicacion_nombre'] ?? 'Sin ubicación'); ?>
                                    <?php if ($registro['fuera_de_rango'] && $registro['lat_salida']): ?>
                                    <br><small style="color: #f59e0b;">
                                        <i class="fas fa-map-marker-alt"></i>
                                        GPS: <?php echo number_format($registro['lat_salida'], 6); ?>,
                                        <?php echo number_format($registro['lon_salida'], 6); ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Panel Secundario -->
            <div>
                <!-- Ubicaciones Autorizadas (solo admin/rh) -->
                <?php if ($userRole === 'admin' || $userRole === 'rrhh'): ?>
                <div class="location-list">
                    <h3 style="margin-top: 0; color: var(--navy);">
                        <i class="fas fa-map-marker-alt"></i> Ubicaciones Autorizadas
                    </h3>
                    <?php if ($ubicaciones): ?>
                        <?php foreach ($ubicaciones as $ubicacion): ?>
                        <div class="location-item"
                             data-id="<?php echo $ubicacion['id']; ?>"
                             data-lat="<?php echo $ubicacion['latitud']; ?>"
                             data-lng="<?php echo $ubicacion['longitud']; ?>"
                             data-radio="<?php echo $ubicacion['radio_metros']; ?>">
                            <div>
                                <div style="font-weight: 600; color: var(--navy);">
                                    <?php echo htmlspecialchars($ubicacion['nombre']); ?>
                                </div>
                                <div style="font-size: 0.875rem; color: var(--gray-600);">
                                    <?php echo htmlspecialchars($ubicacion['direccion']); ?>
                                    <br>Radio: <?php echo $ubicacion['radio_metros']; ?>m
                                </div>
                            </div>
                            <div class="distance-badge">
                                <span class="distance-text">--</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                        <i class="fas fa-map" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>No hay ubicaciones configuradas</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Mapa -->
                <div class="card" style="position: relative;">
                    <h3 style="margin-top: 0; color: var(--navy);">
                        <i class="fas fa-globe"></i> Tu Ubicación
                    </h3>
                    <div id="mapContainer">
                        <!-- El mapa se cargará aquí -->
                    </div>
                    <div id="mapLoading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--navy);"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Container -->
    <div class="mobile-container" style="display: none;">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin: 1rem; padding: 1rem; border-radius: 12px; font-size: 0.9rem;">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Reloj Principal -->
        <div class="clock-container">
            <div class="clock-time" id="clock-mobile">00:00:00</div>
            <div class="clock-date" id="date-mobile">--</div>

            <!-- Estado de Asistencia del Día -->
            <div style="padding: 0.75rem; margin: 0.75rem 0; border-radius: 8px; text-align: center; font-weight: 600; font-size: 0.9rem; <?php echo $tieneEntrada ? 'background: #dcfce7; border: 2px solid #10b981; color: #166534;' : 'background: #fef3c7; border: 2px solid #f59e0b; color: #92400e;'; ?>">
                <i class="fas fa-<?php echo $tieneEntrada ? 'check-circle' : 'clock'; ?>"></i>
                <?php echo $estadoActualTexto; ?>
                <?php if ($estadoActual): ?>
                    <br><small style="font-size: 0.8rem; opacity: 0.9;">Entrada: <?php echo date('h:i A', strtotime($estadoActual['entrada'])); ?></small>
                <?php endif; ?>
            </div>

            <!-- Estado de Ubicación -->
            <div class="location-status checking" id="locationStatus-mobile">
                <i class="fas fa-satellite-dish pulse"></i>
                <span>Verificando ubicación...</span>
            </div>


            <!-- Botón de Marcaje -->
            <form method="POST" id="clockForm-mobile">
                <input type="hidden" name="ubicacion_id" id="ubicacionId-mobile">
                <input type="hidden" name="lat" id="lat-mobile">
                <input type="hidden" name="lng" id="lng-mobile">

                <?php if (!$tieneEntrada): ?>
                    <input type="hidden" name="action" value="entrada">
                    <button type="submit" class="btn-clock entrada" id="clockBtn-mobile" disabled>
                        <i class="fas fa-sign-in-alt"></i>
                        MARCAR ENTRADA
                    </button>
                <?php else: ?>
                    <input type="hidden" name="action" value="salida">
                    <button type="submit" class="btn-clock salida" id="clockBtn-mobile" disabled>
                        <i class="fas fa-sign-out-alt"></i>
                        MARCAR SALIDA
                    </button>
                <?php endif; ?>

                <!-- Botones de GPS y Reintentar para móvil -->
                <div style="display: flex; gap: 0.5rem; margin-top: 1rem; justify-content: center;">
                    <button type="button" id="gps-button-mobile" onclick="requestGPSLocation()" style="
                        padding: 0.75rem 1rem;
                        background: linear-gradient(135deg, #001f3f, #004080);
                        color: white;
                        border: none;
                        border-radius: 8px;
                        font-size: 0.9rem;
                        font-weight: bold;
                        cursor: pointer;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                        flex: 1;
                    ">
                        <i class="fas fa-location-arrow"></i> Activar GPS
                    </button>

                    <button type="button" onclick="retryLocation()" id="retryBtn-mobile" style="
                        padding: 0.75rem 1rem;
                        background: var(--navy);
                        color: white;
                        border: none;
                        border-radius: 8px;
                        font-size: 0.9rem;
                        font-weight: bold;
                        cursor: pointer;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                        flex: 1;
                        display: none;
                    ">
                        <i class="fas fa-redo"></i> Reintentar
                    </button>
                </div>
            </form>
        </div>

        <!-- Mapa -->
        <div class="mobile-section">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--navy); margin-bottom: 1rem;">
                <i class="fas fa-map-marked-alt"></i> Tu Ubicación
            </h3>
            <div id="mapContainer-mobile" style="height: 300px; border-radius: 12px; overflow: hidden;">
                <!-- El mapa se cargará aquí -->
            </div>
        </div>

        <!-- Historial de Hoy -->
        <?php if ($historialHoy): ?>
        <div class="mobile-section">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--navy); margin-bottom: 1rem;">
                <i class="fas fa-history"></i> Tu actividad de hoy
            </h3>
            <?php foreach ($historialHoy as $registro): ?>
            <div style="padding: 0.75rem; border-left: 3px solid <?php echo $registro['fuera_de_rango'] ? '#f59e0b' : 'var(--gold)'; ?>; margin-bottom: 0.75rem; background: var(--gray-50); border-radius: 0 8px 8px 0;">
                <div style="font-weight: 600; color: var(--navy); font-size: 0.95rem;">
                    <?php echo date('h:i A', strtotime($registro['entrada'])); ?>
                    <span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 500; margin-left: 0.5rem; background: #dcfce7; color: #166534;">ENTRADA</span>
                </div>
                <?php if ($registro['salida']): ?>
                <div style="font-weight: 600; color: var(--navy); font-size: 0.95rem; margin-top: 0.5rem;">
                    <?php echo date('h:i A', strtotime($registro['salida'])); ?>
                    <span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 500; margin-left: 0.5rem; background: #fee2e2; color: #991b1b;">SALIDA</span>
                    <?php if ($registro['fuera_de_rango']): ?>
                    <span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 500; margin-left: 0.5rem; background: #fef3c7; color: #92400e;" title="Salida fuera de rango">⚠️ FUERA</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($registro['ubicacion_nombre']): ?>
                <div style="font-size: 0.8rem; color: var(--gray-600); margin-top: 0.25rem;">
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($registro['ubicacion_nombre']); ?>
                </div>
                <?php endif; ?>
                <?php if ($registro['fuera_de_rango'] && $registro['lat_salida']): ?>
                <div style="font-size: 0.75rem; color: #f59e0b; margin-top: 0.25rem; padding: 0.25rem; background: #fef3c7; border-radius: 4px;">
                    <i class="fas fa-exclamation-triangle"></i> Salida fuera de rango
                    <br>GPS: <?php echo number_format($registro['lat_salida'], 6); ?>, <?php echo number_format($registro['lon_salida'], 6); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation (solo móvil) -->
    <div class="bottom-nav" style="display: none;">
        <a href="/dashboard.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="/asistencias.php" class="nav-item active">
            <i class="fas fa-clock"></i>
            <span>Marcar</span>
        </a>
        <a href="/reportes.php" class="nav-item">
            <i class="fas fa-chart-bar"></i>
            <span>Reportes</span>
        </a>
        <a href="/perfil.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    // Detectar si es móvil
    const isMobile = window.innerWidth <= 768;

    // Toggle menú hamburguesa (solo móvil)
    function toggleMenu() {
        const menu = document.getElementById('mobileMenu');
        const overlay = document.getElementById('menuOverlay');

        if (menu.style.right === '0px') {
            menu.style.right = '-300px';
            overlay.style.display = 'none';
            document.body.style.overflow = 'auto';
        } else {
            menu.style.right = '0px';
            overlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    // Reloj en tiempo real
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('es-MX', { hour12: false });
        const dateString = now.toLocaleDateString('es-MX', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Actualizar relojes desktop y móvil
        const clockDesktop = document.getElementById('clock');
        const clockMobile = document.getElementById('clock-mobile');
        const dateDesktop = document.getElementById('date');
        const dateMobile = document.getElementById('date-mobile');

        if (clockDesktop) clockDesktop.textContent = timeString;
        if (clockMobile) clockMobile.textContent = timeString;
        if (dateDesktop) dateDesktop.textContent = dateString;
        if (dateMobile) dateMobile.textContent = dateString;
    }

    updateClock();
    setInterval(updateClock, 1000);

    // Variables globales
    let userLat = null;
    let userLng = null;
    let nearestLocation = null;
    let map = null;
    let mapMobile = null;
    let userMarker = null;
    let userMarkerMobile = null;
    let locationMarkers = [];
    let locationCircles = [];
    let locationMarkersMobile = [];
    let locationCirclesMobile = [];
    let watchId = null; // Para almacenar el ID del watchPosition
    const GEOFENCE_CHECK_INTERVAL = 10000;

    // Inicializar mapa
    function initMap() {
        const mapId = isMobile ? 'mapContainer-mobile' : 'mapContainer';
        const mapElement = document.getElementById(mapId);

        if (!mapElement) return;

        const mapInstance = L.map(mapId, {
            zoomControl: !isMobile,
            attributionControl: !isMobile
        }).setView([19.4326, -99.1332], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(mapInstance);

        if (isMobile) {
            L.control.zoom({
                position: 'bottomright'
            }).addTo(mapInstance);
            mapMobile = mapInstance;
        } else {
            map = mapInstance;
        }

        // Ocultar el spinner
        const loadingEl = document.getElementById('mapLoading');
        if (loadingEl) loadingEl.style.display = 'none';

        // Agregar ubicaciones autorizadas al mapa
        <?php foreach ($ubicaciones as $ubicacion): ?>
        addLocationToMap(
            <?php echo $ubicacion['latitud']; ?>,
            <?php echo $ubicacion['longitud']; ?>,
            <?php echo $ubicacion['radio_metros']; ?>,
            "<?php echo htmlspecialchars($ubicacion['nombre']); ?>",
            mapInstance
        );
        <?php endforeach; ?>
    }

    // Agregar ubicación autorizada al mapa
    function addLocationToMap(lat, lng, radius, name, mapInstance) {
        const circle = L.circle([lat, lng], {
            color: '#10b981',
            fillColor: '#10b981',
            fillOpacity: 0.2,
            radius: radius,
            weight: 2
        }).addTo(mapInstance);

        const marker = L.marker([lat, lng], {
            icon: L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background: #10b981; color: white; padding: 4px 8px; border-radius: 5px; white-space: nowrap; font-size: 0.8rem; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        <i class="fas fa-building"></i> ${name}
                       </div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            })
        }).addTo(mapInstance);

        marker.bindPopup(`<strong>${name}</strong><br>Radio: ${radius}m`);

        if (mapInstance === map) {
            locationMarkers.push(marker);
            locationCircles.push(circle);
        } else {
            locationMarkersMobile.push(marker);
            locationCirclesMobile.push(circle);
        }
    }

    // Actualizar posición del usuario en el mapa
    function updateUserPositionOnMap(lat, lng) {
        const mapInstance = isMobile ? mapMobile : map;
        if (!mapInstance) return;

        let markerInstance = isMobile ? userMarkerMobile : userMarker;

        if (markerInstance) {
            mapInstance.removeLayer(markerInstance);
        }

        markerInstance = L.marker([lat, lng], {
            icon: L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background: #3b82f6; color: white; padding: 6px; border-radius: 50%; box-shadow: 0 3px 8px rgba(59,130,246,0.5); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border: 3px solid white;">
                        <i class="fas fa-user"></i>
                       </div>`,
                iconSize: [36, 36],
                iconAnchor: [18, 18]
            })
        }).addTo(mapInstance);

        markerInstance.bindPopup('<strong>Tu ubicación actual</strong>');

        if (isMobile) {
            userMarkerMobile = markerInstance;
        } else {
            userMarker = markerInstance;
        }

        mapInstance.setView([lat, lng], 16);

        // Ajustar vista para mostrar ubicaciones cercanas
        const circles = isMobile ? locationCirclesMobile : locationCircles;
        if (circles.length > 0) {
            const bounds = L.latLngBounds([[lat, lng]]);
            let hasNearbyLocation = false;

            circles.forEach(circle => {
                const center = circle.getLatLng();
                const dist = mapInstance.distance([lat, lng], center);
                if (dist < 1000) {
                    bounds.extend(circle.getBounds());
                    hasNearbyLocation = true;
                }
            });

            if (hasNearbyLocation) {
                mapInstance.fitBounds(bounds, { padding: [30, 30] });
            }
        }
    }

    // Calcular distancia entre dos puntos
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371e3;
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return R * c;
    }

    // Verificar ubicación
    function checkLocation() {
        console.log('Iniciando verificación de ubicación...');

        if (!navigator.geolocation) {
            console.error('Geolocation API no disponible');
            updateLocationStatus('error', 'GPS no disponible');
            showLocationHelp('Tu navegador no soporta geolocalización');
            return;
        }

        updateLocationStatus('checking', 'Obteniendo ubicación...');

        // Opciones optimizadas para móvil - sin caché para forzar nueva petición
        const geoOptions = {
            enableHighAccuracy: true,  // Usar GPS de alta precisión
            timeout: 30000,            // Aumentar timeout a 30 segundos para móvil
            maximumAge: 0              // NO usar caché - siempre pedir ubicación nueva
        };

        navigator.geolocation.getCurrentPosition(
            (position) => {
                console.log('Ubicación obtenida:', position.coords);
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;

                // Actualizar campos de formulario
                ['lat', 'lng', 'lat-mobile', 'lng-mobile'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = id.includes('lat') ? userLat : userLng;
                });

                updateUserPositionOnMap(userLat, userLng);
                checkGeofences();

                // Ocultar mensajes de ayuda si existen
                hideLocationHelp();
            },
            (error) => {
                console.error('Error obteniendo ubicación:', error);
                handleLocationError(error);

                // En desarrollo, permitir marcaje sin ubicación
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    console.log('Modo desarrollo: permitiendo marcaje sin ubicación');
                    ['clockBtn', 'clockBtn-mobile'].forEach(id => {
                        const btn = document.getElementById(id);
                        if (btn) btn.disabled = false;
                    });
                }
            },
            geoOptions
        );
    }

    // Mostrar ayuda de ubicación
    function showLocationHelp(message) {
        // Crear o actualizar mensaje de ayuda
        let helpDiv = document.getElementById('locationHelp');
        if (!helpDiv) {
            helpDiv = document.createElement('div');
            helpDiv.id = 'locationHelp';
            helpDiv.style.cssText = 'background: #fef3c7; border: 1px solid #f59e0b; padding: 1rem; margin: 1rem; border-radius: 8px; font-size: 0.9rem; white-space: pre-line;';

            // Insertar después del estado de ubicación
            const statusEl = document.getElementById(isMobile ? 'locationStatus-mobile' : 'locationStatus');
            if (statusEl && statusEl.parentNode) {
                statusEl.parentNode.insertBefore(helpDiv, statusEl.nextSibling);
            }
        }

        helpDiv.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #f59e0b; margin-right: 0.5rem;"></i>' + message;

        // Agregar botón para solicitar permisos manualmente en móvil
        if (isMobile && navigator.permissions) {
            const btnHtml = '<br><br><button onclick="requestLocationPermission()" style="background: var(--navy); color: white; padding: 0.5rem 1rem; border: none; border-radius: 6px; margin-top: 0.5rem;"><i class="fas fa-map-marker-alt"></i> Activar Ubicación</button>';
            helpDiv.innerHTML += btnHtml;
        }
    }

    // Ocultar ayuda de ubicación
    function hideLocationHelp() {
        const helpDiv = document.getElementById('locationHelp');
        if (helpDiv) {
            helpDiv.remove();
        }
    }

    // Solicitar permisos de ubicación explícitamente
    function requestLocationPermission() {
        console.log('Solicitando permisos de ubicación...');

        // Intentar obtener ubicación nuevamente
        checkLocation();
    }

    // Verificar geofences
    function checkGeofences() {
        const locations = document.querySelectorAll('.location-item');
        let inRange = false;
        let closestLocationId = null;
        let closestDistance = Infinity;

        // Verificar con elementos DOM si existen
        locations.forEach(location => {
            const id = location.dataset.id;
            const lat = parseFloat(location.dataset.lat);
            const lng = parseFloat(location.dataset.lng);
            const radio = parseFloat(location.dataset.radio);

            const distance = calculateDistance(userLat, userLng, lat, lng);
            const distanceText = location.querySelector('.distance-text');

            if (distanceText) {
                if (distance < 1000) {
                    distanceText.textContent = Math.round(distance) + 'm';
                } else {
                    distanceText.textContent = (distance / 1000).toFixed(1) + 'km';
                }
            }

            if (distance <= radio) {
                location.classList.add('in-range');
                inRange = true;
                closestLocationId = id;
            } else {
                location.classList.remove('in-range');
            }

            // Track closest location even if out of range
            if (distance < closestDistance) {
                closestDistance = distance;
                if (!inRange) {
                    closestLocationId = id;
                }
            }
        });

        // Si no hay elementos de ubicación, verificar directamente
        if (locations.length === 0) {
            <?php foreach ($ubicaciones as $index => $ubicacion): ?>
            const distance<?php echo $index; ?> = calculateDistance(
                userLat, userLng,
                <?php echo $ubicacion['latitud']; ?>,
                <?php echo $ubicacion['longitud']; ?>
            );
            if (distance<?php echo $index; ?> <= <?php echo $ubicacion['radio_metros']; ?>) {
                inRange = true;
                closestLocationId = <?php echo $ubicacion['id']; ?>;
            } else if (!closestLocationId || distance<?php echo $index; ?> < closestDistance) {
                closestDistance = distance<?php echo $index; ?>;
                closestLocationId = <?php echo $ubicacion['id']; ?>;
            }
            <?php endforeach; ?>
        }

        // Store range status globally for checkout warning
        window.isInRange = inRange;
        window.closestLocationId = closestLocationId;

        // Actualizar ubicacion_id y estado
        ['ubicacionId', 'ubicacionId-mobile'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = closestLocationId || '';
        });

        // ENABLE buttons regardless of range - checkout allowed from anywhere!
        ['clockBtn', 'clockBtn-mobile'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                // Only disable if no GPS coordinates available
                el.disabled = !userLat || !userLng;
            }
        });

        updateLocationStatus(inRange ? 'success' : 'error',
                           inRange ? '✓ En ubicación autorizada' : 'Fuera de rango - Puedes marcar salida de todos modos');
    }

    // Actualizar estado de ubicación
    function updateLocationStatus(type, message) {
        ['locationStatus', 'locationStatus-mobile'].forEach(id => {
            const statusDiv = document.getElementById(id);
            if (statusDiv) {
                statusDiv.className = 'location-status';

                if (type === 'success') {
                    statusDiv.classList.add('in-range');
                    statusDiv.innerHTML = '<i class="fas fa-check-circle"></i><span>' + message + '</span>';
                } else if (type === 'error') {
                    statusDiv.classList.add('out-range');
                    statusDiv.innerHTML = '<i class="fas fa-times-circle"></i><span>' + message + '</span>';
                } else {
                    statusDiv.classList.add('checking');
                    statusDiv.innerHTML = '<i class="fas fa-satellite-dish pulse"></i><span>' + message + '</span>';
                }
            }
        });

        // Mostrar/ocultar botón de reintentar
        ['retryBtn', 'retryBtn-mobile'].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.style.display = type === 'error' ? 'block' : 'none';
            }
        });
    }

    // Función para reintentar ubicación
    function retryLocation() {
        console.log('Reintentando obtener ubicación...');
        hideLocationHelp();
        updateLocationStatus('checking', 'Reintentando...');

        // Limpiar watch anterior si existe
        if (watchId) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }

        // Reiniciar watchPosition
        startWatchingLocation();
    }

    // Función para usar watchPosition (más confiable especialmente en móvil)
    function startWatchingLocation() {
        console.log('Iniciando tracking de ubicación...');

        if (!navigator.geolocation) {
            updateLocationStatus('error', 'GPS no disponible');
            showLocationHelp('Tu navegador no soporta geolocalización');
            return;
        }

        // Opciones optimizadas - similar a alpefresh.app/asist
        const watchOptions = {
            enableHighAccuracy: true,
            timeout: 10000,  // 10 segundos como en asist
            maximumAge: 0     // Sin caché
        };

        // Limpiar watch anterior si existe
        if (watchId) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }

        // Iniciar watchPosition directamente
        watchId = navigator.geolocation.watchPosition(
            (position) => {
                console.log('Ubicación obtenida:', position.coords);
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;

                // Actualizar campos de formulario
                ['lat', 'lng', 'lat-mobile', 'lng-mobile'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = id.includes('lat') ? userLat : userLng;
                });

                updateUserPositionOnMap(userLat, userLng);
                checkGeofences();
                hideLocationHelp();

                // Primera vez que obtenemos ubicación, actualizar estado
                updateLocationStatus('success', 'GPS activo');
            },
            (error) => {
                console.error('Error obteniendo ubicación:', error);
                handleLocationError(error);
            },
            watchOptions
        );
    }

    // Función para manejar errores de ubicación
    function handleLocationError(error) {
        console.log('Error de ubicación código:', error.code);

        switch(error.code) {
            case error.PERMISSION_DENIED:
                updateLocationStatus('error', 'Ubicación bloqueada');
                // Solo mostrar ayuda si realmente fue denegado
                showLocationHelp('Permite el acceso a la ubicación en tu navegador para marcar asistencia.');
                break;

            case error.POSITION_UNAVAILABLE:
                updateLocationStatus('error', 'GPS no disponible');
                // Seguir intentando en segundo plano
                break;

            case error.TIMEOUT:
                updateLocationStatus('checking', 'Buscando señal GPS...');
                // No mostrar error en timeout, watchPosition seguirá intentando
                break;

            default:
                updateLocationStatus('error', 'Error de ubicación');
        }
    }

    // Registrar Service Worker para PWA
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('Service Worker registrado:', registration);

                // Mostrar prompt de instalación si está disponible
                let deferredPrompt;
                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    deferredPrompt = e;

                    // Mostrar botón de instalación personalizado en móvil
                    if (isMobile) {
                        const installBtn = document.createElement('button');
                        installBtn.innerHTML = '<i class="fas fa-download"></i> Instalar App';
                        installBtn.style.cssText = 'position: fixed; bottom: 70px; left: 50%; transform: translateX(-50%); z-index: 1000; background: var(--navy); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
                        installBtn.onclick = () => {
                            deferredPrompt.prompt();
                            deferredPrompt.userChoice.then((choiceResult) => {
                                if (choiceResult.outcome === 'accepted') {
                                    console.log('App instalada');
                                    installBtn.remove();
                                }
                                deferredPrompt = null;
                            });
                        };
                        document.body.appendChild(installBtn);
                    }
                });
            })
            .catch(err => console.log('Error registrando Service Worker:', err));
    }

    // CREAR BOTÓN GPS INMEDIATAMENTE (ANTES DE DOMCONTENTLOADED)
    window.addEventListener('load', function() {
        console.log('=== CREANDO BOTÓN GPS FLOTANTE ===');

        // Crear botón flotante
        const gpsBtn = document.createElement('button');
        gpsBtn.setAttribute('id', 'floating-gps-btn');
        gpsBtn.innerHTML = '📍 ACTIVAR GPS';
        gpsBtn.style.cssText = `
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 999999;
            background: #001f3f;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: block;
            visibility: visible;
        `;

        // Agregar al body
        document.body.appendChild(gpsBtn);
        console.log('Botón GPS agregado al body');

        // Click handler
        gpsBtn.addEventListener('click', function() {
            console.log('BOTÓN GPS PRESIONADO');
            this.innerHTML = '⏳ Activando...';
            this.disabled = true;

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    console.log('GPS ACTIVADO:', pos.coords);
                    gpsBtn.innerHTML = '✅ GPS Activo';
                    gpsBtn.style.background = '#10b981';

                    // Actualizar variables globales
                    if (typeof userLat !== 'undefined') {
                        userLat = pos.coords.latitude;
                        userLng = pos.coords.longitude;

                        // Llamar funciones si existen
                        if (typeof updateUserPositionOnMap === 'function') {
                            updateUserPositionOnMap(userLat, userLng);
                        }
                        if (typeof checkGeofences === 'function') {
                            checkGeofences();
                        }
                        if (typeof startWatchingLocation === 'function') {
                            setTimeout(() => {
                                gpsBtn.style.display = 'none';
                                startWatchingLocation();
                            }, 2000);
                        }
                    }
                },
                function(err) {
                    console.error('Error GPS:', err);
                    gpsBtn.innerHTML = '❌ Error';
                    gpsBtn.style.background = '#dc2626';
                    setTimeout(() => {
                        gpsBtn.innerHTML = '🔄 Reintentar';
                        gpsBtn.disabled = false;
                        gpsBtn.style.background = '#001f3f';
                    }, 2000);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });
    });

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado, iniciando...');

        // Verificar contexto seguro (HTTPS)
        if (!window.isSecureContext) {
            console.error('Contexto no seguro - Geolocalización no disponible');
            updateLocationStatus('error', 'Conexión no segura');

            const errorMsg = 'La geolocalización requiere una conexión segura (HTTPS).\n\n';
            if (location.protocol === 'http:') {
                // Intentar redirigir a HTTPS
                const httpsUrl = location.href.replace('http://', 'https://');
                showLocationHelp(errorMsg + 'Redirigiendo a versión segura...');
                setTimeout(() => {
                    window.location.href = httpsUrl;
                }, 2000);
            } else {
                showLocationHelp(errorMsg + 'Por favor, accede a través de https://asistencia.alpefresh.app');
            }
            return;
        }

        console.log('Contexto seguro verificado:', {
            protocol: location.protocol,
            hostname: location.hostname,
            isSecureContext: window.isSecureContext
        });

        // Inicializar mapa
        try {
            initMap();
        } catch (e) {
            console.error('Error iniciando mapa:', e);
        }

        // Crear función para manejar el botón de activación
        function createLocationButton() {
            // Verificar si ya existe el botón
            if (document.getElementById('activateLocationBtn')) {
                return;
            }

            // Crear botón de activación grande y visible
            const activateBtn = document.createElement('button');
            activateBtn.id = 'activateLocationBtn';
            activateBtn.type = 'button';
            activateBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> ACTIVAR GPS';
            activateBtn.className = 'activate-location-btn';

            // Estilos del botón
            activateBtn.style.cssText = `
                display: block;
                width: 90%;
                max-width: 300px;
                margin: 1rem auto;
                padding: 1rem 2rem;
                background: linear-gradient(135deg, #001f3f 0%, #004080 100%);
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 1.2rem;
                font-weight: bold;
                cursor: pointer;
                box-shadow: 0 4px 15px rgba(0,0,0,0.3);
                transition: all 0.3s;
            `;

            // Efecto hover
            activateBtn.onmouseover = function() {
                this.style.transform = 'scale(1.05)';
            };
            activateBtn.onmouseout = function() {
                this.style.transform = 'scale(1)';
            };

            // Insertar el botón en el lugar correcto
            const targetId = isMobile ? 'locationStatus-mobile' : 'locationStatus';
            const statusEl = document.getElementById(targetId);
            if (statusEl && statusEl.parentNode) {
                statusEl.parentNode.insertBefore(activateBtn, statusEl.nextSibling);
            }

            // Función al hacer clic
            activateBtn.onclick = function(event) {
                event.preventDefault();
                event.stopPropagation();

                console.log('=== BOTÓN CLICKEADO - Solicitando ubicación ===');

                // Feedback visual inmediato
                activateBtn.disabled = true;
                activateBtn.style.opacity = '0.7';
                activateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ACTIVANDO GPS...';

                // Verificar que tenemos la API de geolocalización
                if (!navigator.geolocation) {
                    activateBtn.innerHTML = '<i class="fas fa-times"></i> GPS NO DISPONIBLE';
                    activateBtn.style.background = '#dc2626';
                    return;
                }

                // MÉTODO 1: getCurrentPosition con callback directo
                navigator.geolocation.getCurrentPosition(
                    // Success callback
                    function(position) {
                        console.log('✅ ÉXITO: Ubicación obtenida', position.coords);

                        // Actualizar UI
                        activateBtn.innerHTML = '<i class="fas fa-check"></i> GPS ACTIVADO';
                        activateBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';

                        // Guardar coordenadas
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;

                        // Actualizar formularios
                        ['lat', 'lng', 'lat-mobile', 'lng-mobile'].forEach(id => {
                            const el = document.getElementById(id);
                            if (el) el.value = id.includes('lat') ? userLat : userLng;
                        });

                        // Actualizar mapa y estado
                        updateUserPositionOnMap(userLat, userLng);
                        checkGeofences();
                        hideLocationHelp();
                        updateLocationStatus('success', 'GPS Activo');

                        // Ocultar botón después de 2 segundos
                        setTimeout(function() {
                            activateBtn.style.display = 'none';
                            // Iniciar tracking continuo
                            startWatchingLocation();
                        }, 2000);
                    },
                    // Error callback
                    function(error) {
                        console.error('❌ ERROR al obtener ubicación:', error);

                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                activateBtn.innerHTML = '<i class="fas fa-ban"></i> PERMISOS DENEGADOS';
                                activateBtn.style.background = '#dc2626';
                                showLocationHelp(
                                    'Los permisos de ubicación fueron denegados.\n\n' +
                                    'Para solucionarlo:\n' +
                                    '1. Revisa el ícono de ubicación en la barra de direcciones\n' +
                                    '2. O ve a configuración del sitio\n' +
                                    '3. Permite el acceso a ubicación\n' +
                                    '4. Recarga la página'
                                );
                                break;

                            case error.POSITION_UNAVAILABLE:
                                activateBtn.innerHTML = '<i class="fas fa-satellite-dish"></i> SIN SEÑAL';
                                activateBtn.style.background = '#f59e0b';
                                showLocationHelp('No se puede obtener la señal GPS. Intenta en un área abierta.');
                                // Re-habilitar para reintentar
                                setTimeout(() => {
                                    activateBtn.disabled = false;
                                    activateBtn.style.opacity = '1';
                                    activateBtn.innerHTML = '<i class="fas fa-redo"></i> REINTENTAR';
                                    activateBtn.style.background = 'linear-gradient(135deg, #001f3f 0%, #004080 100%)';
                                }, 3000);
                                break;

                            case error.TIMEOUT:
                                activateBtn.innerHTML = '<i class="fas fa-clock"></i> TIEMPO AGOTADO';
                                activateBtn.style.background = '#f59e0b';
                                // Reintentar automáticamente
                                setTimeout(() => {
                                    activateBtn.disabled = false;
                                    activateBtn.style.opacity = '1';
                                    activateBtn.innerHTML = '<i class="fas fa-redo"></i> REINTENTAR';
                                    activateBtn.style.background = 'linear-gradient(135deg, #001f3f 0%, #004080 100%)';
                                }, 3000);
                                break;

                            default:
                                activateBtn.innerHTML = '<i class="fas fa-exclamation"></i> ERROR';
                                activateBtn.style.background = '#dc2626';
                        }

                        // Log adicional para debugging
                        console.log('Código de error:', error.code);
                        console.log('Mensaje de error:', error.message);
                    },
                    // Opciones
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,  // 15 segundos de timeout
                        maximumAge: 0     // No usar caché
                    }
                );
            };

            return activateBtn;
        }

        // Mostrar estado inicial esperando acción del usuario
        console.log('Esperando activación de ubicación por el usuario...');
        updateLocationStatus('warning', 'Ubicación no activada');

        // El botón ya está en el HTML, no crear duplicados

        // Prevenir zoom accidental en móvil
        if (isMobile) {
            document.addEventListener('gesturestart', function(e) {
                e.preventDefault();
            });

            // Re-verificar ubicación cuando la app vuelve a estar activa
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    console.log('App activa, re-verificando ubicación...');
                    // Si hay un watchPosition activo, no necesita re-verificar
                    // El watch sigue activo en segundo plano
                    if (!watchId) {
                        startWatchingLocation();
                    }
                }
            });

            // Limpiar watchPosition cuando se descarga la página
            window.addEventListener('beforeunload', function() {
                if (watchId) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                    console.log('Watch de ubicación detenido');
                }
            });
        }

        // watchPosition ya maneja las actualizaciones continuas, no necesitamos intervalos
    });

    // Confirmar marcaje
    ['clockForm', 'clockForm-mobile'].forEach(id => {
        const form = document.getElementById(id);
        if (form) {
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('input[name="action"]').value;
                let mensaje = '';

                if (action === 'entrada') {
                    mensaje = '¿Confirmar ENTRADA?';
                } else {
                    // Check if out of range for checkout
                    if (window.isInRange === false) {
                        mensaje = '⚠️ ADVERTENCIA: Estás FUERA del rango permitido.\n\n' +
                                'Tu ubicación será registrada y quedará marcada como "salida fuera de rango".\n\n' +
                                'Se guardará un registro con tu ubicación GPS actual para verificación.\n\n' +
                                '¿Deseas continuar con la salida de todos modos?';
                    } else {
                        mensaje = '¿Confirmar SALIDA?';
                    }
                }

                if (navigator.vibrate && isMobile) {
                    navigator.vibrate(100);
                }

                if (!confirm(mensaje)) {
                    e.preventDefault();
                }
            });
        }
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>