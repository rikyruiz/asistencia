<!-- AGGRESSIVE MODE: Full-page blocking overlay -->
<div id="location-overlay" class="hidden fixed inset-0 bg-gray-900 bg-opacity-95 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-8 text-center">
        <div class="mb-6">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-4">
                <i class="fas fa-map-marker-alt text-red-600 text-4xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                Ubicación Requerida
            </h2>
            <p class="text-gray-600 text-lg">
                Debes habilitar tu ubicación para usar el sistema de asistencia
            </p>
        </div>

        <div class="bg-red-50 border-2 border-red-200 rounded-xl p-6 mb-6">
            <div class="flex items-center justify-center mb-4">
                <div class="animate-pulse flex items-center justify-center w-16 h-16 bg-red-600 rounded-full">
                    <i class="fas fa-exclamation text-white text-2xl"></i>
                </div>
            </div>
            <p class="text-red-800 font-semibold text-lg mb-2">
                Sin acceso a ubicación no puedes continuar
            </p>
            <p class="text-red-700 text-sm">
                El sistema intentará solicitar tu ubicación automáticamente cada 15 segundos
            </p>
        </div>

        <div class="mb-6">
            <div class="text-gray-700 mb-4">
                <p class="font-medium mb-2">Próximo intento en:</p>
                <div class="text-5xl font-bold text-navy" id="overlay-countdown">15</div>
                <p class="text-sm text-gray-500 mt-2">segundos</p>
            </div>
        </div>

        <button onclick="retryPermission()"
                class="w-full py-4 px-6 bg-green-600 hover:bg-green-700 text-white font-bold text-lg rounded-xl shadow-lg transform hover:scale-105 transition-all duration-200 mb-4">
            <i class="fas fa-location-arrow mr-2"></i>
            Habilitar Ubicación Ahora
        </button>

        <div id="overlay-instructions" class="text-left bg-gray-50 rounded-xl p-6 border border-gray-200">
            <p class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                ¿Cómo habilito la ubicación?
            </p>
            <div id="overlay-browser-instructions" class="text-sm text-gray-700">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            <?= $activeSession ? 'Registrar Salida' : 'Registrar Entrada' ?>
        </h1>
        <p class="text-gray-600 mt-1">
            Asegúrate de estar en la ubicación autorizada para registrar tu entrada
        </p>
    </div>

    <!-- Location Status Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-map-marker-alt mr-2"></i>
                Estado de Ubicación
            </h2>
            <button onclick="refreshLocation()"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-navy hover:text-white hover:bg-navy border border-navy rounded-lg transition-colors"
                    title="Solicitar ubicación nuevamente">
                <i class="fas fa-sync-alt mr-1.5"></i>
                Solicitar ubicación
            </button>
        </div>

        <!-- GPS Status -->
        <div id="gps-status" class="mb-6">
            <div class="flex items-center justify-center py-8">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-navy mx-auto mb-4"></div>
                    <p class="text-gray-600">Obteniendo tu ubicación...</p>
                    <p class="text-sm text-gray-500 mt-2">Por favor, permite el acceso a tu ubicación</p>
                </div>
            </div>
        </div>

        <!-- Location Details (hidden initially) -->
        <div id="location-details" class="hidden">
            <!-- Accuracy Indicator -->
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Precisión GPS</span>
                    <span id="accuracy-text" class="font-medium">-</span>
                </div>
                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                    <div id="accuracy-bar" class="h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>

            <!-- Location Info -->
            <div class="space-y-3">
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600 text-sm">Coordenadas</span>
                    <span id="coordinates" class="text-sm font-mono text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600 text-sm">Ubicación más cercana</span>
                    <span id="nearest-location" class="text-sm font-medium text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600 text-sm">Distancia</span>
                    <span id="distance" class="text-sm font-medium text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-gray-600 text-sm">Estado</span>
                    <span id="geofence-status" class="text-sm font-medium">-</span>
                </div>
            </div>
        </div>

        <!-- Error Message (hidden initially) -->
        <div id="location-error" class="hidden">
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-semibold text-red-800 mb-2" id="error-message">
                            Error al obtener la ubicación
                        </h3>
                        <div id="permission-instructions" class="hidden">
                            <p class="text-sm text-red-700 mb-3">
                                Has bloqueado el acceso a tu ubicación. Para usar el sistema de asistencia, necesitas habilitar los permisos de ubicación.
                            </p>
                            <div class="bg-white border border-red-200 rounded-lg p-4 mb-3">
                                <p class="text-sm font-semibold text-gray-800 mb-2">
                                    <i class="fas fa-info-circle mr-1"></i> Cómo habilitar la ubicación:
                                </p>
                                <div id="browser-instructions" class="text-sm text-gray-700 space-y-2">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                            <button onclick="retryPermission()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-redo mr-2"></i>
                                Intentar de nuevo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-map mr-2"></i>
            Mapa de Ubicación
        </h2>
        <div id="map" class="h-96 bg-gray-100 rounded-lg"></div>
    </div>

    <!-- Action Button -->
    <div class="text-center">
        <?php if ($activeSession): ?>
            <button id="clock-btn" onclick="processClockOut()" disabled
                    class="inline-flex items-center justify-center px-8 py-4 bg-red-600 text-white text-lg font-semibold rounded-lg hover:bg-red-700 transform hover:scale-105 transition-all duration-200 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                <i class="fas fa-stop-circle mr-3"></i>
                <span id="btn-text">Registrar Salida</span>
            </button>
            <p class="text-sm text-gray-500 mt-4">
                La salida se puede registrar desde cualquier ubicación
            </p>
        <?php else: ?>
            <button id="clock-btn" onclick="processClockIn()" disabled
                    class="inline-flex items-center justify-center px-8 py-4 bg-green-600 text-white text-lg font-semibold rounded-lg hover:bg-green-700 transform hover:scale-105 transition-all duration-200 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                <i class="fas fa-play-circle mr-3"></i>
                <span id="btn-text">Registrar Entrada</span>
            </button>
            <p class="text-sm text-gray-500 mt-4">
                Debes estar dentro del área autorizada para registrar entrada
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Include Geolocation Service -->
<script src="<?= url('js/geolocation.js') ?>"></script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Global variables
let geoService = null;
let currentPosition = null;
let userLocations = <?= json_encode($locations) ?>;
let isActiveSession = <?= $activeSession ? 'true' : 'false' ?>;
let map = null;
let userMarker = null;
let locationCircles = [];

// AGGRESSIVE MODE: Auto-retry variables
let retryInterval = null;
let countdownInterval = null;
let retrySeconds = 15;
let locationGranted = false;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeMap();
    initializeGeolocation();
    // Show overlay initially until location is granted
    showLocationOverlay();
    // Start auto-retry mechanism
    startAutoRetry();
});

// Initialize map
function initializeMap() {
    // Default center (will be updated when we get user location)
    const defaultLat = userLocations.length > 0 ? userLocations[0].lat : 20.6597;
    const defaultLng = userLocations.length > 0 ? userLocations[0].lng : -103.3496;

    // Create map
    map = L.map('map').setView([defaultLat, defaultLng], 15);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // Add location circles for authorized locations
    userLocations.forEach(location => {
        // Add circle for geofence
        const circle = L.circle([location.lat, location.lng], {
            color: '#003366',
            fillColor: '#003366',
            fillOpacity: 0.2,
            radius: location.radius
        }).addTo(map);

        // Add marker for location
        L.marker([location.lat, location.lng])
            .bindPopup(`<strong>${location.name}</strong><br>Radio: ${location.radius}m`)
            .addTo(map);

        locationCircles.push(circle);
    });
}

// Update map with user position
function updateMap(position) {
    if (!map) return;

    // Remove old user marker if exists
    if (userMarker) {
        map.removeLayer(userMarker);
    }

    // Add new user marker
    const userIcon = L.divIcon({
        className: 'custom-user-marker',
        html: '<div style="background-color: #3b82f6; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
        iconSize: [20, 20],
        iconAnchor: [10, 10]
    });

    userMarker = L.marker([position.lat, position.lng], { icon: userIcon })
        .bindPopup('Tu ubicación actual')
        .addTo(map);

    // Center map on user location
    map.setView([position.lat, position.lng], 17);

    // Add accuracy circle
    L.circle([position.lat, position.lng], {
        color: '#3b82f6',
        fillColor: '#3b82f6',
        fillOpacity: 0.1,
        radius: position.accuracy
    }).addTo(map);
}

// AGGRESSIVE MODE: Show location overlay
function showLocationOverlay() {
    const overlay = document.getElementById('location-overlay');
    overlay.classList.remove('hidden');

    // Populate browser instructions in overlay
    populateOverlayInstructions();

    // Disable page scrolling
    document.body.style.overflow = 'hidden';
}

// AGGRESSIVE MODE: Hide location overlay
function hideLocationOverlay() {
    const overlay = document.getElementById('location-overlay');
    overlay.classList.add('hidden');

    // Re-enable page scrolling
    document.body.style.overflow = 'auto';

    // Stop auto-retry
    stopAutoRetry();
}

// AGGRESSIVE MODE: Populate overlay with browser instructions
function populateOverlayInstructions() {
    const instructionsContainer = document.getElementById('overlay-browser-instructions');
    const userAgent = navigator.userAgent;
    let instructions = '';

    // Detect browser
    if (userAgent.includes('Chrome') && !userAgent.includes('Edg')) {
        instructions = `
            <div class="flex items-start space-x-2">
                <i class="fab fa-chrome text-lg mt-0.5"></i>
                <div>
                    <p class="font-medium mb-1">Google Chrome:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Haz clic en el <strong>ícono de candado</strong> en la barra de direcciones</li>
                        <li>Busca <strong>"Ubicación"</strong> en los permisos</li>
                        <li>Selecciona <strong>"Permitir"</strong></li>
                        <li>Recarga la página (F5)</li>
                    </ol>
                </div>
            </div>
        `;
    } else if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) {
        instructions = `
            <div class="flex items-start space-x-2">
                <i class="fab fa-safari text-lg mt-0.5"></i>
                <div>
                    <p class="font-medium mb-1">Safari:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Ve a <strong>Safari</strong> → <strong>Configuración para este sitio web</strong></li>
                        <li>En <strong>"Ubicación"</strong> selecciona <strong>"Permitir"</strong></li>
                        <li>Recarga la página</li>
                    </ol>
                </div>
            </div>
        `;
    } else if (userAgent.includes('Firefox')) {
        instructions = `
            <div class="flex items-start space-x-2">
                <i class="fab fa-firefox text-lg mt-0.5"></i>
                <div>
                    <p class="font-medium mb-1">Firefox:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Haz clic en el <strong>ícono de candado</strong> en la barra de direcciones</li>
                        <li>Haz clic en la <strong>flecha</strong> junto a "Bloqueado temporalmente"</li>
                        <li>Selecciona <strong>"Limpiar estos permisos y volver a preguntar"</strong></li>
                        <li>Recarga la página y permite el acceso</li>
                    </ol>
                </div>
            </div>
        `;
    } else if (userAgent.includes('Edg')) {
        instructions = `
            <div class="flex items-start space-x-2">
                <i class="fab fa-edge text-lg mt-0.5"></i>
                <div>
                    <p class="font-medium mb-1">Microsoft Edge:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Haz clic en el <strong>ícono de candado</strong> en la barra de direcciones</li>
                        <li>Haz clic en <strong>"Permisos para este sitio"</strong></li>
                        <li>Busca <strong>"Ubicación"</strong> y selecciona <strong>"Permitir"</strong></li>
                        <li>Recarga la página (F5)</li>
                    </ol>
                </div>
            </div>
        `;
    } else {
        instructions = `
            <div>
                <p class="font-medium mb-1">Pasos generales:</p>
                <ol class="list-decimal list-inside space-y-1 text-xs">
                    <li>Busca el ícono de <strong>candado</strong> en la barra de direcciones</li>
                    <li>Haz clic en él y busca <strong>"Ubicación"</strong></li>
                    <li>Cambia el permiso a <strong>"Permitir"</strong></li>
                    <li>Recarga esta página</li>
                </ol>
            </div>
        `;
    }

    // Add mobile-specific instructions
    if (/Android|iPhone|iPad|iPod/i.test(userAgent)) {
        instructions += `
            <div class="mt-3 pt-3 border-t border-gray-200">
                <p class="font-medium mb-1 flex items-center">
                    <i class="fas fa-mobile-alt mr-1"></i> Dispositivo móvil:
                </p>
                <ol class="list-decimal list-inside space-y-1 text-xs">
                    <li>Ve a <strong>Configuración</strong> del dispositivo</li>
                    <li>Busca <strong>"Privacidad"</strong> o <strong>"Ubicación"</strong></li>
                    <li>Asegúrate de que la ubicación esté <strong>activada</strong></li>
                    <li>Permite el acceso para tu navegador</li>
                </ol>
            </div>
        `;
    }

    instructionsContainer.innerHTML = instructions;
}

// AGGRESSIVE MODE: Start countdown timer
function startCountdown() {
    let seconds = retrySeconds;
    const countdownEl = document.getElementById('overlay-countdown');

    // Clear any existing countdown
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    countdownInterval = setInterval(() => {
        seconds--;
        countdownEl.textContent = seconds;

        if (seconds <= 0) {
            clearInterval(countdownInterval);
            countdownEl.textContent = retrySeconds;
        }
    }, 1000);
}

// AGGRESSIVE MODE: Start auto-retry mechanism
function startAutoRetry() {
    // Clear any existing interval
    if (retryInterval) {
        clearInterval(retryInterval);
    }

    // Start countdown
    startCountdown();

    // Set up auto-retry every 15 seconds
    retryInterval = setInterval(() => {
        if (!locationGranted) {
            console.log('Auto-retrying location request...');
            refreshLocation();
            startCountdown(); // Restart countdown
        }
    }, retrySeconds * 1000);
}

// AGGRESSIVE MODE: Stop auto-retry mechanism
function stopAutoRetry() {
    if (retryInterval) {
        clearInterval(retryInterval);
        retryInterval = null;
    }
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
}

// Initialize geolocation
async function initializeGeolocation() {
    geoService = new GeolocationService();

    if (!geoService.isSupported()) {
        showLocationError('Tu navegador no soporta geolocalización', false);
        return;
    }

    try {
        // AGGRESSIVE MODE: Always attempt to get location regardless of permission state
        // This will summon the browser's "Allow" prompt
        getCurrentLocation();

        // Start watching position
        geoService.startWatching({
            onUpdate: handleLocationUpdate,
            onError: handleLocationError,
            onStatusChange: handleStatusChange
        });
    } catch (error) {
        showLocationError('Error al inicializar geolocalización: ' + error.message);
    }
}

// Get current location
async function getCurrentLocation() {
    try {
        const position = await geoService.getCurrentPosition();
        handleLocationUpdate(position);
    } catch (error) {
        handleLocationError(error);
    }
}

// Handle location update
function handleLocationUpdate(position) {
    currentPosition = position;

    // AGGRESSIVE MODE: Location was granted successfully!
    if (!locationGranted) {
        locationGranted = true;
        hideLocationOverlay();
        console.log('Location granted! Hiding overlay and stopping auto-retry.');
    }

    // Update UI
    document.getElementById('gps-status').classList.add('hidden');
    document.getElementById('location-details').classList.remove('hidden');
    document.getElementById('location-error').classList.add('hidden');

    // Update coordinates
    document.getElementById('coordinates').textContent =
        `${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`;

    // Update accuracy
    const accuracyLevel = GeolocationService.getAccuracyLevel(position.accuracy);
    document.getElementById('accuracy-text').textContent =
        `${position.accuracy}m (${accuracyLevel.text})`;

    const accuracyBar = document.getElementById('accuracy-bar');
    const accuracyPercent = Math.max(0, Math.min(100, 100 - (position.accuracy / 100 * 100)));
    accuracyBar.style.width = accuracyPercent + '%';
    accuracyBar.className = `h-2 rounded-full transition-all duration-300 bg-${accuracyLevel.color}-500`;

    // Check geofence
    checkGeofence(position);

    // Update map if available
    if (map) {
        updateMap(position);
    }
}

// Check if user is within geofence
function checkGeofence(position) {
    let withinGeofence = false;
    let nearestLocation = null;
    let minDistance = Infinity;

    // Check each assigned location
    userLocations.forEach(location => {
        const distance = GeolocationService.calculateDistance(
            position.lat, position.lng,
            location.lat, location.lng
        );

        if (distance < minDistance) {
            minDistance = distance;
            nearestLocation = location;
        }

        if (GeolocationService.isWithinGeofence(
            position.lat, position.lng,
            location.lat, location.lng,
            location.radius
        )) {
            withinGeofence = true;
        }
    });

    // Update UI
    if (nearestLocation) {
        document.getElementById('nearest-location').textContent = nearestLocation.name;
        document.getElementById('distance').textContent =
            GeolocationService.formatDistance(minDistance);
    }

    const statusElement = document.getElementById('geofence-status');
    const clockBtn = document.getElementById('clock-btn');

    if (withinGeofence) {
        statusElement.innerHTML = '<i class="fas fa-check-circle text-green-600 mr-1"></i> Dentro del área';
        statusElement.className = 'text-sm font-medium text-green-600';
        clockBtn.disabled = false;
    } else {
        statusElement.innerHTML = '<i class="fas fa-times-circle text-red-600 mr-1"></i> Fuera del área';
        statusElement.className = 'text-sm font-medium text-red-600';

        // For clock out, always enable. For clock in, disable if outside
        clockBtn.disabled = !isActiveSession;
    }
}

// Handle location error
function handleLocationError(error) {
    let message = 'Error al obtener ubicación';
    let isPermissionError = false;

    if (error.code === 1) {
        message = 'Permiso de ubicación denegado';
        isPermissionError = true;
    } else if (error.code === 2) {
        message = 'Información de ubicación no disponible. Verifica tu GPS.';
    } else if (error.code === 3) {
        message = 'Tiempo de espera agotado al obtener la ubicación.';
    }

    showLocationError(message, isPermissionError);
}

// Show location error
function showLocationError(message, showInstructions = false) {
    document.getElementById('gps-status').classList.add('hidden');
    document.getElementById('location-details').classList.add('hidden');
    document.getElementById('location-error').classList.remove('hidden');
    document.getElementById('error-message').textContent = message;
    document.getElementById('clock-btn').disabled = true;

    // Show permission instructions if it's a permission error
    const instructionsEl = document.getElementById('permission-instructions');
    if (showInstructions) {
        instructionsEl.classList.remove('hidden');
        showBrowserInstructions();
    } else {
        instructionsEl.classList.add('hidden');
    }
}

// Detect browser and show specific instructions
function showBrowserInstructions() {
    const instructionsContainer = document.getElementById('browser-instructions');
    const userAgent = navigator.userAgent;
    let instructions = '';

    // Detect browser
    if (userAgent.includes('Chrome') && !userAgent.includes('Edg')) {
        instructions = `
            <div class="flex items-start space-x-2">
                <i class="fab fa-chrome text-lg mt-0.5"></i>
                <div>
                    <p class="font-medium mb-1">Google Chrome:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Haz clic en el <strong>ícono de candado</strong> o <strong>ícono de información</strong> en la barra de direcciones</li>
                        <li>Busca <strong>"Ubicación"</strong> en los permisos</li>
                        <li>Selecciona <strong>"Permitir"</strong></li>
                        <li>Recarga la página (F5)</li>
                    </ol>
                </div>
            </div>
        `;
    } else if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) {
        instructions = `
            <div class="flex items-start space-x-2">
                <i class="fab fa-safari text-lg mt-0.5"></i>
                <div>
                    <p class="font-medium mb-1">Safari:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Ve a <strong>Safari</strong> → <strong>Configuración para este sitio web</strong></li>
                        <li>En <strong>"Ubicación"</strong> selecciona <strong>"Permitir"</strong></li>
                        <li>Recarga la página</li>
                    </ol>
                </div>
            </div>
        `;
    } else if (userAgent.includes('Firefox')) {
        instructions = `
            <div class="flex items-start space-x-2">
                <i class="fab fa-firefox text-lg mt-0.5"></i>
                <div>
                    <p class="font-medium mb-1">Firefox:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Haz clic en el <strong>ícono de candado</strong> en la barra de direcciones</li>
                        <li>Haz clic en la <strong>flecha</strong> junto a "Bloqueado temporalmente"</li>
                        <li>Selecciona <strong>"Limpiar estos permisos y volver a preguntar"</strong></li>
                        <li>Recarga la página y permite el acceso</li>
                    </ol>
                </div>
            </div>
        `;
    } else if (userAgent.includes('Edg')) {
        instructions = `
            <div class="flex items-start space-x-2">
                <i class="fab fa-edge text-lg mt-0.5"></i>
                <div>
                    <p class="font-medium mb-1">Microsoft Edge:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Haz clic en el <strong>ícono de candado</strong> en la barra de direcciones</li>
                        <li>Haz clic en <strong>"Permisos para este sitio"</strong></li>
                        <li>Busca <strong>"Ubicación"</strong> y selecciona <strong>"Permitir"</strong></li>
                        <li>Recarga la página (F5)</li>
                    </ol>
                </div>
            </div>
        `;
    } else {
        // Generic instructions
        instructions = `
            <div>
                <p class="font-medium mb-1">Pasos generales:</p>
                <ol class="list-decimal list-inside space-y-1 text-xs">
                    <li>Busca el ícono de <strong>candado</strong> o <strong>información</strong> en la barra de direcciones</li>
                    <li>Haz clic en él y busca la configuración de <strong>"Ubicación"</strong></li>
                    <li>Cambia el permiso a <strong>"Permitir"</strong></li>
                    <li>Recarga esta página</li>
                </ol>
            </div>
        `;
    }

    // Add mobile-specific instructions
    if (/Android|iPhone|iPad|iPod/i.test(userAgent)) {
        instructions += `
            <div class="mt-3 pt-3 border-t border-gray-200">
                <p class="font-medium mb-1 flex items-center">
                    <i class="fas fa-mobile-alt mr-1"></i> Dispositivo móvil:
                </p>
                <p class="text-xs mb-1">Verifica también los permisos del sistema:</p>
                <ol class="list-decimal list-inside space-y-1 text-xs">
                    <li>Ve a <strong>Configuración</strong> del dispositivo</li>
                    <li>Busca <strong>"Privacidad"</strong> o <strong>"Ubicación"</strong></li>
                    <li>Asegúrate de que la ubicación esté <strong>activada</strong></li>
                    <li>Permite el acceso para tu navegador</li>
                </ol>
            </div>
        `;
    }

    instructionsContainer.innerHTML = instructions;
}

// Retry permission request
async function retryPermission() {
    // Hide error and show loading
    document.getElementById('location-error').classList.add('hidden');
    document.getElementById('gps-status').classList.remove('hidden');

    try {
        // AGGRESSIVE MODE: Always attempt to get location
        // Force the browser to show the permission prompt again
        await getCurrentLocation();

    } catch (error) {
        console.error('Error retrying permission:', error);
        handleLocationError(error);
    }
}

// Handle status change
function handleStatusChange(status) {
    console.log('Location status:', status);
}

// Refresh location
async function refreshLocation() {
    // Hide any existing errors or details
    document.getElementById('location-error').classList.add('hidden');
    document.getElementById('location-details').classList.add('hidden');
    document.getElementById('gps-status').classList.remove('hidden');

    try {
        // AGGRESSIVE MODE: Always attempt to get location
        // This will trigger the browser's permission dialog regardless of state
        await getCurrentLocation();

    } catch (error) {
        console.error('Error refreshing location:', error);
        handleLocationError(error);
    }
}

// Process Clock In
async function processClockIn() {
    if (!currentPosition) {
        alert('Por favor espera a que se obtenga tu ubicación');
        return;
    }

    const btn = document.getElementById('clock-btn');
    const btnText = document.getElementById('btn-text');

    // Disable button and show loading
    btn.disabled = true;
    btnText.textContent = 'Procesando...';

    try {
        const response = await fetch('<?= url("inspector/clockIn") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                lat: currentPosition.lat,
                lng: currentPosition.lng,
                accuracy: currentPosition.accuracy,
                <?= CSRF_TOKEN_NAME ?>: '<?= $csrf_token ?>'
            })
        });

        const data = await response.json();

        if (data.success) {
            // Show success message
            showSuccessMessage(data.message);

            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = '<?= url("inspector/dashboard") ?>';
            }, 2000);
        } else {
            // Show error
            alert(data.error || 'Error al registrar entrada');
            btn.disabled = false;
            btnText.textContent = 'Registrar Entrada';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
        btn.disabled = false;
        btnText.textContent = 'Registrar Entrada';
    }
}

// Process Clock Out
async function processClockOut() {
    const btn = document.getElementById('clock-btn');
    const btnText = document.getElementById('btn-text');

    // Disable button and show loading
    btn.disabled = true;
    btnText.textContent = 'Procesando...';

    try {
        const response = await fetch('<?= url("inspector/clockOut") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                lat: currentPosition ? currentPosition.lat : 0,
                lng: currentPosition ? currentPosition.lng : 0,
                accuracy: currentPosition ? currentPosition.accuracy : 0,
                <?= CSRF_TOKEN_NAME ?>: '<?= $csrf_token ?>'
            })
        });

        const data = await response.json();

        if (data.success) {
            // Show success message
            showSuccessMessage(data.message + ' - Duración: ' + data.duration);

            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = '<?= url("inspector/dashboard") ?>';
            }, 2000);
        } else {
            // Show error
            alert(data.error || 'Error al registrar salida');
            btn.disabled = false;
            btnText.textContent = 'Registrar Salida';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
        btn.disabled = false;
        btnText.textContent = 'Registrar Salida';
    }
}

// Show success message
function showSuccessMessage(message) {
    // Create toast notification
    const toast = document.createElement('div');
    toast.className = 'fixed top-20 right-4 z-50 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-lg';
    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <p>${message}</p>
        </div>
    `;
    document.body.appendChild(toast);

    // Auto remove after 5 seconds
    setTimeout(() => toast.remove(), 5000);
}
</script>