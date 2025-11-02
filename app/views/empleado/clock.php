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
            <button onclick="refreshLocation()" class="text-navy hover:text-primary-700">
                <i class="fas fa-sync-alt"></i> Actualizar
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
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800" id="error-message">
                            Error al obtener la ubicación
                        </p>
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
        <div id="map" class="h-96 bg-gray-100 rounded-lg flex items-center justify-center">
            <p class="text-gray-500">El mapa se cargará aquí</p>
        </div>
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

<!-- Include Geolocation Service -->
<script src="<?= asset('js/geolocation.js') ?>"></script>

<script>
// Global variables
let geoService = null;
let currentPosition = null;
let userLocations = <?= json_encode($locations) ?>;
let isActiveSession = <?= $activeSession ? 'true' : 'false' ?>;
let map = null;
let userMarker = null;
let locationCircles = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeGeolocation();
    // Load map if needed (you can use Google Maps, Leaflet, etc.)
    // initializeMap();
});

// Initialize geolocation
async function initializeGeolocation() {
    geoService = new GeolocationService();

    if (!geoService.isSupported()) {
        showLocationError('Tu navegador no soporta geolocalización');
        return;
    }

    try {
        const permission = await geoService.requestPermission();
        if (permission === 'denied') {
            showLocationError('Permiso de ubicación denegado. Por favor, habilita la ubicación en tu navegador.');
            return;
        }

        // Get current position
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

    if (error.code === 1) {
        message = 'Permiso de ubicación denegado. Por favor, habilita la ubicación en tu navegador.';
    } else if (error.code === 2) {
        message = 'Información de ubicación no disponible. Verifica tu GPS.';
    } else if (error.code === 3) {
        message = 'Tiempo de espera agotado al obtener la ubicación.';
    }

    showLocationError(message);
}

// Show location error
function showLocationError(message) {
    document.getElementById('gps-status').classList.add('hidden');
    document.getElementById('location-details').classList.add('hidden');
    document.getElementById('location-error').classList.remove('hidden');
    document.getElementById('error-message').textContent = message;
    document.getElementById('clock-btn').disabled = true;
}

// Handle status change
function handleStatusChange(status) {
    console.log('Location status:', status);
}

// Refresh location
function refreshLocation() {
    document.getElementById('gps-status').classList.remove('hidden');
    document.getElementById('location-details').classList.add('hidden');
    getCurrentLocation();
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
        const response = await fetch('<?= url("empleado/clockIn") ?>', {
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
                window.location.href = '<?= url("empleado/dashboard") ?>';
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
        const response = await fetch('<?= url("empleado/clockOut") ?>', {
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
                window.location.href = '<?= url("empleado/dashboard") ?>';
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