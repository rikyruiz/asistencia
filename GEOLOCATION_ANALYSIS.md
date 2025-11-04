# Geolocation System Analysis - "Solicitar Ubicación" (Request Location)

## Overview
The attendance system includes a comprehensive geolocation (GPS) functionality that enforces location-based clock-in/out procedures using browser-based geolocation APIs and server-side validation.

---

## 1. WHERE LOCATION REQUEST IS TRIGGERED

### Frontend Trigger Points

#### File: `/var/www/asistencia/app/views/empleado/clock.php` (Lines 19-24)
```html
<button onclick="refreshLocation()"
        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-navy hover:text-white hover:bg-navy border border-navy rounded-lg transition-colors"
        title="Solicitar ubicación nuevamente">
    <i class="fas fa-sync-alt mr-1.5"></i>
    Solicitar ubicación
</button>
```

**Key Trigger Function**: `refreshLocation()` (JavaScript, lines 532-558)
- Located in the same file (empleado/clock.php)
- Triggered manually by user clicking "Solicitar ubicación" button
- Also triggered automatically on page load via `initializeGeolocation()`

#### File: `/var/www/asistencia/app/views/inspector/clock.php` (Lines 19-24)
- Identical implementation for inspectors
- Shares same geolocation logic

### JavaScript Initialization (Lines 159-162)
```javascript
document.addEventListener('DOMContentLoaded', function() {
    initializeMap();
    initializeGeolocation();
});
```

### Flow Diagram
```
User visits clock.php
    ↓
DOMContentLoaded event fires
    ↓
initializeGeolocation() called
    ↓
Check browser support (navigator.geolocation)
    ↓
Request permission (navigator.permissions.query)
    ↓
If permission granted/prompt: getCurrentPosition()
    ↓
Location obtained or error shown
    ↓
User clicks "Solicitar ubicación" button (manual refresh)
    ↓
refreshLocation() function executes
```

---

## 2. GEOLOCATION PERMISSION HANDLING

### Permission Check System

#### Primary Handler: `requestPermission()` (geolocation.js, lines 33-49)
```javascript
async requestPermission() {
    if (!this.isSupported()) {
        throw new Error('Geolocalización no es soportada por este navegador');
    }
    
    try {
        if ('permissions' in navigator) {
            const permission = await navigator.permissions.query({ name: 'geolocation' });
            return permission.state; // 'granted', 'denied', or 'prompt'
        }
        return 'prompt';
    } catch (error) {
        console.error('Error checking permission:', error);
        return 'prompt';
    }
}
```

### Permission States Handled
1. **'granted'**: User previously allowed access - location obtained automatically
2. **'prompt'**: First time access - browser shows permission dialog
3. **'denied'**: User blocked access - error shown with instructions

### Browser-Specific Instructions

The system detects the user's browser and displays specific permission instructions:

**Chrome** (lines 398-411):
- Click lock icon in address bar
- Find "Location" in permissions
- Select "Allow"
- Reload page

**Safari** (lines 412-425):
- Go to Safari → Settings for this Website
- Set Location to "Allow"
- Reload page

**Firefox** (lines 426-440):
- Click lock icon in address bar
- Click arrow next to "Blocked temporarily"
- Select "Clear these permissions and ask again"
- Reload and allow

**Microsoft Edge** (lines 441-455):
- Click lock icon in address bar
- Click "Permissions for this site"
- Find "Location" and select "Allow"
- Reload page

**Mobile Devices** (lines 472-486):
- Additional system-level permission instructions
- Go to device Settings
- Find Privacy or Location
- Ensure location is enabled
- Allow access for browser

### Permission Retry Mechanism

#### Function: `retryPermission()` (lines 493-524)
```javascript
async function retryPermission() {
    // Hide error and show loading
    document.getElementById('location-error').classList.add('hidden');
    document.getElementById('gps-status').classList.remove('hidden');
    
    try {
        if ('permissions' in navigator) {
            const permission = await navigator.permissions.query({ name: 'geolocation' });
            
            if (permission.state === 'denied') {
                // Still denied, show error immediately
                setTimeout(() => {
                    showLocationError('Los permisos aún están bloqueados...', true);
                }, 500);
                return;
            }
            
            if (permission.state === 'granted') {
                // Permission was granted! Get location
                console.log('Permission granted! Getting location...');
            }
        }
        
        // Attempt to get location
        await getCurrentLocation();
    } catch (error) {
        console.error('Error retrying permission:', error);
        handleLocationError(error);
    }
}
```

### Manual Refresh with Permission Check

#### Function: `refreshLocation()` (lines 532-558)
```javascript
async function refreshLocation() {
    document.getElementById('location-error').classList.add('hidden');
    document.getElementById('location-details').classList.add('hidden');
    document.getElementById('gps-status').classList.remove('hidden');
    
    try {
        if ('permissions' in navigator) {
            const permission = await navigator.permissions.query({ name: 'geolocation' });
            
            if (permission.state === 'denied') {
                // Show instructions instead of attempting
                showLocationError('Permiso de ubicación denegado', true);
                return;
            }
        }
        
        // If permission granted or prompt, try to get location
        await getCurrentLocation();
    } catch (error) {
        console.error('Error refreshing location:', error);
        handleLocationError(error);
    }
}
```

---

## 3. LOCATION DATA FLOW (REQUEST TO STORAGE)

### Step 1: Browser Location Acquisition

#### Class: `GeolocationService` (public/js/geolocation.js, lines 54-76)
```javascript
getCurrentPosition() {
    return new Promise((resolve, reject) => {
        if (!this.isSupported()) {
            reject(new Error('Geolocalización no disponible'));
            return;
        }
        
        this.updateStatus('locating', 'Obteniendo ubicación...');
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.updatePosition(position);
                this.updateStatus('success', 'Ubicación obtenida');
                resolve(this.formatPosition(position));
            },
            (error) => {
                this.handleError(error);
                reject(error);
            },
            this.options
        );
    });
}
```

**Options Set** (lines 16-20):
```javascript
this.options = {
    enableHighAccuracy: true,  // Use GPS if available
    timeout: 30000,            // 30 seconds timeout
    maximumAge: 0              // Don't use cached positions
};
```

**Data Format After Acquisition** (lines 144-151):
```javascript
formatPosition(position) {
    return {
        lat: position.coords.latitude,
        lng: position.coords.longitude,
        accuracy: Math.round(position.coords.accuracy),
        timestamp: new Date().toISOString()
    };
}
```

### Step 2: Real-time Location Monitoring

#### Function: `startWatching()` (lines 81-111)
- Continuously monitors user position while on clock page
- Updates UI with real-time accuracy
- Triggers geofence check automatically

### Step 3: Geofence Validation (JavaScript)

#### Function: `checkGeofence()` (lines 306-353)
```javascript
function checkGeofence(position) {
    let withinGeofence = false;
    let nearestLocation = null;
    let minDistance = Infinity;
    
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
    
    // Update UI and enable/disable button
    if (withinGeofence) {
        statusElement.innerHTML = '<i class="fas fa-check-circle text-green-600 mr-1"></i> Dentro del área';
        clockBtn.disabled = false;
    } else {
        statusElement.innerHTML = '<i class="fas fa-times-circle text-red-600 mr-1"></i> Fuera del área';
        clockBtn.disabled = !isActiveSession; // Only allow clock-out from outside
    }
}
```

**Geofence Calculation Helper** (geolocation.js, lines 209-212):
```javascript
static isWithinGeofence(userLat, userLon, centerLat, centerLon, radius) {
    const distance = GeolocationService.calculateDistance(userLat, userLon, centerLat, centerLon);
    return distance <= radius;
}
```

**Distance Formula - Haversine** (geolocation.js, lines 191-204):
```javascript
static calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Earth's radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;
    
    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    
    return R * c; // Distance in meters
}
```

### Step 4: Clock-In/Out AJAX Request

#### Clock-In Function (lines 561-611)
```javascript
async function processClockIn() {
    if (!currentPosition) {
        alert('Por favor espera a que se obtenga tu ubicación');
        return;
    }
    
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
        showSuccessMessage(data.message);
        setTimeout(() => {
            window.location.href = '<?= url("empleado/dashboard") ?>';
        }, 2000);
    }
}
```

**Clock-Out** (similar, lines 614-659):
- Allows clock-out from anywhere
- Still records location data

### Step 5: Server-Side Validation (PHP)

#### File: `/var/www/asistencia/app/controllers/EmpleadoController.php`

**Clock-In Handler** (lines 87-205):
```php
public function clockIn() {
    // Validate CSRF token
    if (!$this->validateCsrfToken()) {
        $this->json(['error' => 'Token de seguridad inválido'], 403);
        return;
    }
    
    $userId = getUserId();
    $lat = floatval($this->getPost('lat'));
    $lng = floatval($this->getPost('lng'));
    $accuracy = floatval($this->getPost('accuracy'));
    
    // Validate coordinates
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        $this->json(['error' => 'Coordenadas inválidas'], 400);
        return;
    }
    
    // Find location by coordinates (server-side geofence check)
    $location = $this->locationModel->findLocationByCoordinates($lat, $lng);
    
    if (!$location) {
        $nearest = $this->locationModel->getNearestLocation($lat, $lng);
        $this->json([
            'error' => 'No estás dentro de ninguna ubicación autorizada',
            'nearest' => $nearest
        ], 400);
        return;
    }
    
    // Check if user is assigned to this location
    $userLocations = $this->userModel->getLocations($userId);
    $isAssigned = false;
    foreach ($userLocations as $loc) {
        if ($loc['id'] == $location['id']) {
            $isAssigned = true;
            break;
        }
    }
    
    if (!$isAssigned) {
        $this->json(['error' => 'No estás asignado a esta ubicación'], 403);
        return;
    }
    
    // Process clock in
    $result = $this->attendanceModel->clockIn(
        $userId,
        $location['id'],
        $lat,
        $lng,
        $accuracy,
        true,
        $location['distancia']
    );
    
    $this->json(['success' => true, 'message' => 'Entrada registrada correctamente']);
}
```

**Clock-Out Handler** (lines 210-288):
- Allows clock-out from any location
- Still records GPS coordinates
- Records whether within/outside geofence

### Step 6: Database Storage

#### File: `/var/www/asistencia/app/models/Attendance.php`

**Clock-In Record Creation** (lines 20-80):
```php
public function clockIn($userId, $locationId, $lat, $lon, $precision = null, $withinGeofence = true, $distance = null) {
    // Check for active session
    $sql = "SELECT COUNT(*) as count FROM sesiones_trabajo WHERE usuario_id = :user_id AND estado = 'activa'";
    $result = $this->db->selectOne($sql, ['user_id' => $userId]);
    if ($result && $result['count'] > 0) {
        return ['error' => 'active_session_exists'];
    }
    
    try {
        $this->db->beginTransaction();
        
        // Insert attendance record
        $attendanceId = $this->create([
            'usuario_id' => $userId,
            'ubicacion_id' => $locationId,
            'tipo' => 'entrada',
            'fecha_hora' => getCurrentDateTime(),
            'latitud_registro' => $lat,
            'longitud_registro' => $lon,
            'precision_gps' => $precision,
            'dentro_geofence' => $withinGeofence ? 1 : 0,
            'distancia_ubicacion' => $distance,
            'metodo_registro' => 'web',
            'direccion_ip' => getUserIP(),
            'user_agent' => getUserAgent()
        ]);
        
        // Create work session
        $sessionData = [
            'usuario_id' => $userId,
            'entrada_id' => $attendanceId,
            'ubicacion_id' => $locationId,
            'fecha_inicio' => getCurrentDate(),
            'hora_entrada' => getCurrentDateTime(),
            'estado' => 'activa'
        ];
        
        $sessionId = $this->db->insert('sesiones_trabajo', $sessionData);
        
        $this->db->commit();
        
        return ['success' => true, 'attendance_id' => $attendanceId, 'session_id' => $sessionId];
    } catch (Exception $e) {
        $this->db->rollback();
        return ['error' => 'database_error', 'message' => $e->getMessage()];
    }
}
```

### Step 7: Database Schema

#### Table: `registros_asistencia` (Database Schema, lines 94-124)
```sql
CREATE TABLE registros_asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ubicacion_id INT,
    tipo ENUM('entrada', 'salida') NOT NULL,
    fecha_hora DATETIME NOT NULL,
    fecha_local DATE GENERATED ALWAYS AS (DATE(CONVERT_TZ(fecha_hora, '+00:00', '-06:00'))) STORED,
    latitud_registro DECIMAL(10, 8),
    longitud_registro DECIMAL(11, 8),
    precision_gps DECIMAL(6, 2),
    dentro_geofence BOOLEAN DEFAULT TRUE,
    distancia_ubicacion INT,  -- Distance in meters
    metodo_registro ENUM('web', 'app', 'manual', 'sistema') DEFAULT 'web',
    direccion_ip VARCHAR(45),
    user_agent TEXT,
    dispositivo_id VARCHAR(100),
    foto_registro VARCHAR(255),
    notas TEXT,
    editado BOOLEAN DEFAULT FALSE,
    editado_por INT,
    editado_en DATETIME,
    razon_edicion TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id),
    INDEX idx_usuario_fecha (usuario_id, fecha_hora),
    INDEX idx_dentro_geofence (dentro_geofence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table: `ubicaciones` (Locations, Database Schema, lines 50-73)
```sql
CREATE TABLE ubicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(50) UNIQUE,
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    radio_metros INT DEFAULT 100,
    tipo_ubicacion ENUM('oficina', 'campo', 'almacen', 'otro'),
    horario_apertura TIME,
    horario_cierre TIME,
    dias_laborales VARCHAR(20) DEFAULT '1,2,3,4,5',
    activa BOOLEAN DEFAULT TRUE,
    ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table: `sesiones_trabajo` (Work Sessions, Database Schema, lines 129-156)
```sql
CREATE TABLE sesiones_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    entrada_id INT NOT NULL,
    salida_id INT,
    ubicacion_id INT,
    fecha_inicio DATE NOT NULL,
    hora_entrada DATETIME NOT NULL,
    hora_salida DATETIME,
    duracion_minutos INT,
    estado ENUM('activa', 'completada', 'anormal', 'editada'),
    ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. RETRY AND ENFORCEMENT MECHANISMS

### Permission Enforcement - Browser Level

1. **First Visit**: Browser shows permission prompt
   - User can Allow, Block, or Dismiss
   - If Blocked → system shows error with instructions

2. **Subsequent Visits**: 
   - If previously Allowed: automatic location fetch
   - If previously Blocked: error shown immediately

### Permission Enforcement - UI Level

#### Retry Button (lines 95-98 in empleado/clock.php)
```html
<button onclick="retryPermission()" class="...">
    <i class="fas fa-redo mr-2"></i>
    Intentar de nuevo
</button>
```

**Scenario**: User denied permission, browser shows error with instructions, user fixes permission and clicks "Intentar de nuevo"

#### Refresh Button (lines 19-24)
```html
<button onclick="refreshLocation()" class="...">
    <i class="fas fa-sync-alt mr-1.5"></i>
    Solicitar ubicación
</button>
```

**Scenario**: Location data stale or inaccurate, user manually refreshes

### Clock-In Button Enforcement

#### Client-Side Gate (lines 305-353)
```javascript
function checkGeofence(position) {
    // ... geofence calculation ...
    
    if (withinGeofence) {
        statusElement.innerHTML = '<i class="fas fa-check-circle text-green-600 mr-1"></i> Dentro del área';
        statusElement.className = 'text-sm font-medium text-green-600';
        clockBtn.disabled = false;  // ENABLE button
    } else {
        statusElement.innerHTML = '<i class="fas fa-times-circle text-red-600 mr-1"></i> Fuera del área';
        statusElement.className = 'text-sm font-medium text-red-600';
        clockBtn.disabled = !isActiveSession;  // DISABLE for clock-in, ENABLE for clock-out
    }
}
```

**Key Behaviors**:
- Clock-In: Button DISABLED if outside geofence
- Clock-Out: Button ENABLED from anywhere
- No location data: Button always DISABLED

#### Server-Side Gate (EmpleadoController.php, lines 129-142)
```php
// Find location by coordinates
$location = $this->locationModel->findLocationByCoordinates($lat, $lng);

if (!$location) {
    $nearest = $this->locationModel->getNearestLocation($lat, $lng);
    $this->json([
        'error' => 'No estás dentro de ninguna ubicación autorizada',
        'nearest' => $nearest
    ], 400);
    return;
}

// Check if user is assigned to this location
$userLocations = $this->userModel->getLocations($userId);
$isAssigned = false;
foreach ($userLocations as $loc) {
    if ($loc['id'] == $location['id']) {
        $isAssigned = true;
        break;
    }
}

if (!$isAssigned) {
    $this->json(['error' => 'No estás asignado a esta ubicación'], 403);
    return;
}
```

### Timeout Handling

#### Geolocation Options (geolocation.js, lines 16-20)
```javascript
this.options = {
    enableHighAccuracy: true,
    timeout: 30000,      // 30 seconds - if no position after 30s, error
    maximumAge: 0        // Don't use cached positions
};
```

**Error Handling** (lines 156-177):
```javascript
handleError(error) {
    let message = 'Error desconocido';
    let code = 'UNKNOWN';
    
    switch (error.code) {
        case error.PERMISSION_DENIED:
            message = 'Permiso de ubicación denegado...';
            code = 'PERMISSION_DENIED';
            break;
        case error.POSITION_UNAVAILABLE:
            message = 'Información de ubicación no disponible...';
            code = 'POSITION_UNAVAILABLE';
            break;
        case error.TIMEOUT:
            message = 'Tiempo de espera agotado al obtener la ubicación.';
            code = 'TIMEOUT';
            break;
    }
    
    this.updateStatus('error', message, code);
}
```

### Continuous Monitoring (Geofence Watch)

#### Active Position Watching (lines 81-111)
```javascript
startWatching(callbacks = {}) {
    if (!this.isSupported()) {
        throw new Error('Geolocalización no disponible');
    }
    
    this.callbacks = { ...this.callbacks, ...callbacks };
    
    if (this.watchId !== null) {
        this.stopWatching();
    }
    
    this.updateStatus('watching', 'Monitoreando ubicación...');
    
    this.watchId = navigator.geolocation.watchPosition(
        (position) => {
            this.updatePosition(position);
            if (this.callbacks.onUpdate) {
                this.callbacks.onUpdate(this.formatPosition(position));
            }
        },
        (error) => {
            this.handleError(error);
            if (this.callbacks.onError) {
                this.callbacks.onError(error);
            }
        },
        this.options
    );
}
```

**Usage** (clock.php, lines 253-257):
```javascript
geoService.startWatching({
    onUpdate: handleLocationUpdate,
    onError: handleLocationError,
    onStatusChange: handleStatusChange
});
```

This means:
- While on clock page, location is continuously monitored
- As user moves, geofence check updates automatically
- Button enable/disable state changes in real-time
- Accuracy indicator updates live

### Geofence Tolerance Setting

#### Configuration (Database schema.sql, line 225)
```sql
INSERT INTO configuracion_sistema VALUES
('geofence_tolerance', '10', 'integer', 'Tolerancia de geofence en metros', 'geolocalización')
```

#### Usage in PHP Helper (functions.php, lines 112-115)
```php
function isWithinGeofence($userLat, $userLon, $centerLat, $centerLon, $radius) {
    $distance = calculateDistance($userLat, $userLon, $centerLat, $centerLon);
    return $distance <= ($radius + GEOFENCE_TOLERANCE);  // 10m buffer
}
```

This adds a 10-meter buffer to the geofence radius for GPS accuracy tolerance.

### Multi-Location Support

Users can be assigned to multiple locations. System checks ALL assigned locations:

#### Clock.php Location Loop (lines 312-330)
```javascript
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
```

---

## 5. ACCURACY DISPLAY

### Live Accuracy Indicator

```javascript
const accuracyLevel = GeolocationService.getAccuracyLevel(position.accuracy);
document.getElementById('accuracy-text').textContent =
    `${position.accuracy}m (${accuracyLevel.text})`;

const accuracyBar = document.getElementById('accuracy-bar');
const accuracyPercent = Math.max(0, Math.min(100, 100 - (position.accuracy / 100 * 100)));
accuracyBar.style.width = accuracyPercent + '%';
```

**Accuracy Levels** (geolocation.js, lines 217-223):
```javascript
static getAccuracyLevel(accuracy) {
    if (accuracy <= 10) return { level: 'excellent', text: 'Excelente', color: 'green' };
    if (accuracy <= 30) return { level: 'good', text: 'Buena', color: 'blue' };
    if (accuracy <= 50) return { level: 'fair', text: 'Aceptable', color: 'yellow' };
    if (accuracy <= 100) return { level: 'poor', text: 'Baja', color: 'orange' };
    return { level: 'very-poor', text: 'Muy Baja', color: 'red' };
}
```

---

## 6. KEY FILES SUMMARY

| File | Purpose |
|------|---------|
| `/var/www/asistencia/public/js/geolocation.js` | Core GeolocationService class handling all browser GPS |
| `/var/www/asistencia/app/views/empleado/clock.php` | Employee clock page with UI and frontend logic |
| `/var/www/asistencia/app/views/inspector/clock.php` | Inspector clock page (nearly identical) |
| `/var/www/asistencia/app/controllers/EmpleadoController.php` | Backend clock-in/out handlers with validation |
| `/var/www/asistencia/app/controllers/InspectorController.php` | Inspector clock handlers |
| `/var/www/asistencia/app/models/Attendance.php` | Database operations for attendance |
| `/var/www/asistencia/app/models/Location.php` | Geofence calculations and location queries |
| `/var/www/asistencia/app/helpers/functions.php` | Helper functions: calculateDistance, isWithinGeofence |
| `/var/www/asistencia/database/schema.sql` | Database schema and configuration |

---

## 7. SECURITY CONSIDERATIONS

1. **CSRF Protection**: All AJAX requests validate CSRF token
2. **Server-Side Validation**: Location coordinates re-validated on server
3. **User Assignment Check**: User must be assigned to location before clock-in allowed
4. **Coordinate Validation**: lat (-90 to 90), lng (-180 to 180) ranges checked
5. **IP Logging**: User IP and user agent recorded with every clock
6. **Multiple Fallbacks**: If geolocation fails, error shown with clear instructions

---

## 8. LIMITATIONS & NOTES

1. **Location Permission Persistence**: 
   - Once blocked by user, requires manual browser settings change to re-enable
   - Not possible to re-prompt programmatically in most browsers

2. **GPS Accuracy**:
   - Depends on device GPS hardware and environment
   - Indoors: typically 5-50m accuracy
   - Outdoors: typically 5-10m accuracy

3. **Work Schedule Validation** (Currently Disabled):
   - Code at line 159-163 of EmpleadoController.php shows location hours check is disabled
   - Comment: "TEMPORARILY DISABLED"

4. **Clock-Out Freedom**:
   - Unlike clock-in, clock-out is allowed from ANY location
   - Location is still recorded but not required to match geofence

5. **Geofence Tolerance**:
   - Set to 10 meters buffer in configuration
   - Can be adjusted in `configuracion_sistema` table

---

## Summary

The "Solicitar Ubicación" (Request Location) system is a comprehensive, browser-based geolocation implementation that:

- **Triggers** automatically on page load and manually via button
- **Handles permissions** gracefully with browser-specific instructions
- **Validates** locations on both client (for UX) and server (for security)
- **Enforces** geofence for clock-in but allows clock-out from anywhere
- **Retries** automatically via "Intentar de nuevo" button if permissions denied
- **Monitors** continuously while user is on clock page
- **Records** GPS coordinates, accuracy, and geofence status for all records
- **Tolerates** 10m GPS accuracy variance via configurable tolerance

