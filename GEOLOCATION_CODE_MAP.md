# Geolocation Code Map - Quick Reference

## Critical Code Locations

### Frontend - User Interface & Triggering

#### "Solicitar ubicación" Button & Flow
- **Location**: `/var/www/asistencia/app/views/empleado/clock.php` lines 19-24
- **Trigger Function**: `refreshLocation()` at lines 532-558
- **Auto-trigger**: Page load handler at lines 159-162

#### Employee Clock Page
- **File**: `/var/www/asistencia/app/views/empleado/clock.php`
- **Key Functions**:
  - Line 159-162: DOM load event → calls `initializeGeolocation()`
  - Line 232-261: `initializeGeolocation()` - Sets up geolocation service
  - Line 264-271: `getCurrentLocation()` - Gets position once
  - Line 532-558: `refreshLocation()` - Manual refresh with permission check
  - Line 493-524: `retryPermission()` - Retry when denied then fixed
  - Line 306-353: `checkGeofence()` - Real-time geofence validation
  - Line 561-611: `processClockIn()` - Send location to server
  - Line 614-659: `processClockOut()` - Send clock-out data

#### Inspector Clock Page (Identical)
- **File**: `/var/www/asistencia/app/views/inspector/clock.php`
- **Lines**: 19-24 (button), 159-162 (init), etc. - exact same structure

---

### Frontend - Geolocation Service Library

#### Core Geolocation Service Class
- **File**: `/var/www/asistencia/public/js/geolocation.js`
- **Key Methods**:
  - Line 6-21: Constructor - sets up options (timeout: 30s, high accuracy)
  - Line 26-28: `isSupported()` - Check navigator.geolocation exists
  - Line 33-49: `requestPermission()` - Query permission state
  - Line 54-76: `getCurrentPosition()` - Get location once with Promise
  - Line 81-111: `startWatching()` - Continuous monitoring
  - Line 116-122: `stopWatching()` - Stop monitoring
  - Line 127-139: `updatePosition()` - Parse position coords
  - Line 144-151: `formatPosition()` - Format for API
  - Line 156-177: `handleError()` - Categorize geolocation errors
  - Line 191-204: `calculateDistance()` - Haversine formula
  - Line 209-212: `isWithinGeofence()` - Distance check
  - Line 217-223: `getAccuracyLevel()` - 5-level accuracy rating

---

### Backend - Request Handlers

#### Employee Controller Clock Methods
- **File**: `/var/www/asistencia/app/controllers/EmpleadoController.php`

**Clock-In Handler**
- Lines 87-205: `clockIn()` method
  - Line 94: CSRF token validation
  - Line 100-103: Extract lat/lng/accuracy from POST
  - Line 106-115: Validate coordinates exist and are valid ranges
  - Line 129: Call `findLocationByCoordinates()` - server-side geofence check
  - Line 131-142: If no location, reject clock-in
  - Line 144-152: Check if user assigned to location
  - Line 154-157: If not assigned, reject clock-in
  - Line 166-174: Call `clockIn()` model with all parameters
  - Line 176-188: Handle errors

**Clock-Out Handler**
- Lines 210-288: `clockOut()` method
  - Line 224-226: Extract lat/lng/accuracy
  - Line 228-245: Optional location lookup (allows from anywhere)
  - Line 248-256: Call `clockOut()` model
  - Line 258-279: Return success with duration

#### Inspector Controller Clock Methods
- **File**: `/var/www/asistencia/app/controllers/InspectorController.php`
- Lines 110-211: `clockIn()` method (slightly different, requires location_id selection)
- Lines 216-304: `clockOut()` method (similar to employee)

---

### Backend - Data Models

#### Attendance Model
- **File**: `/var/www/asistencia/app/models/Attendance.php`

**Clock-In Database Operation**
- Lines 20-80: `clockIn()` method
  - Line 22-26: Check for active session (prevent double-entry)
  - Line 32-45: Create `registros_asistencia` record with GPS data
  - Line 48-55: Create `sesiones_trabajo` work session record
  - Line 59: Transaction commit
  - Line 62-67: Log activity

**Clock-Out Database Operation**
- Lines 85-159: `clockOut()` method
  - Line 87-92: Get active session
  - Line 98-111: Create exit `registros_asistencia` record
  - Line 114: Calculate duration
  - Line 125-134: Update session record with duration/end time
  - Line 136: Transaction commit

#### Location Model
- **File**: `/var/www/asistencia/app/models/Location.php`

**Geofence Checking**
- Lines 45-57: `findLocationByCoordinates($lat, $lon)`
  - Line 46: Get all active locations
  - Line 49: Call `isWithinGeofence()` helper for each
  - Line 50-51: Calculate distance
  - Returns matching location or null

- Lines 62-77: `getNearestLocation($lat, $lon)`
  - Iterates all active locations
  - Returns location with smallest distance

---

### Backend - Helper Functions

#### Helper Functions File
- **File**: `/var/www/asistencia/app/helpers/functions.php`

**Distance Calculation - Haversine Formula**
- Lines 92-107: `calculateDistance($lat1, $lon1, $lat2, $lon2)`
  - Line 93: Earth radius constant (6,371,000 meters)
  - Lines 95-104: Haversine formula implementation
  - Line 106: Return distance in meters

**Geofence Check with Tolerance**
- Lines 112-115: `isWithinGeofence($userLat, $userLon, $centerLat, $centerLon, $radius)`
  - Line 113: Call calculateDistance()
  - Line 114: Return true if distance <= (radius + GEOFENCE_TOLERANCE)
  - Note: GEOFENCE_TOLERANCE = 10 meters (configurable)

---

### Database Schema

#### Main Tables
- **File**: `/var/www/asistencia/database/schema.sql`

**registros_asistencia (Attendance Records)**
- Lines 94-124
- Columns storing GPS:
  - `latitud_registro` DECIMAL(10,8)
  - `longitud_registro` DECIMAL(11,8)
  - `precision_gps` DECIMAL(6,2)
  - `dentro_geofence` BOOLEAN
  - `distancia_ubicacion` INT (meters)

**ubicaciones (Locations/Geofences)**
- Lines 50-73
- Columns defining geofences:
  - `latitud` DECIMAL(10,8)
  - `longitud` DECIMAL(11,8)
  - `radio_metros` INT (geofence radius)

**sesiones_trabajo (Work Sessions)**
- Lines 129-156
- Tracks clock-in/out pairs with duration

**configuracion_sistema (Configuration)**
- Lines 161-174
- Key config at lines 225-228:
  - `geofence_tolerance`: 10 meters (line 225)
  - `default_radius`: 100 meters (line 226)

---

## Data Flow Summary

```
USER VISITS CLOCK.PHP
     ↓
JavaScript DOMContentLoaded fires
     ↓
initializeGeolocation() called
     ↓
Check navigator.geolocation support
     ↓
requestPermission() queries permission state
     ↓
IF granted: getCurrentPosition() → browser gives coordinates
IF prompt: browser shows permission dialog
IF denied: showLocationError() with browser-specific instructions
     ↓
formatPosition() returns {lat, lng, accuracy, timestamp}
     ↓
startWatching() begins continuous monitoring
     ↓
handleLocationUpdate() called on each new position
     ↓
checkGeofence() validates against all assigned locations
     ↓
IF within geofence: Enable clock-in button
IF outside geofence: Disable clock-in button (enable clock-out)
     ↓
USER CLICKS "Registrar Entrada"
     ↓
processClockIn() sends AJAX POST with coordinates
     ↓
EmpleadoController::clockIn() receives request
     ↓
Server-side validations:
  - CSRF token check
  - Coordinate range validation
  - findLocationByCoordinates() - geofence check
  - Check user assigned to location
     ↓
Attendance::clockIn() creates two database records:
  - registros_asistencia (attendance record with GPS)
  - sesiones_trabajo (work session, marked 'activa')
     ↓
Success response sent to browser
     ↓
showSuccessMessage() + redirect to dashboard
```

---

## Permission Handling Flow

```
FIRST VISIT TO CLOCK.PHP
     ↓
requestPermission() checks state
     ↓
IF state = 'prompt': Browser shows permission dialog
   - User clicks "Allow" → permission granted
   - User clicks "Block" → permission denied
   - User dismisses → state unchanged (prompt)
     ↓
IF state = 'granted': getCurrentPosition() gets coords immediately
     ↓
IF state = 'denied': showLocationError() + show instructions
   - Browser-specific instructions displayed
   - "Intentar de nuevo" button shown
     ↓
USER FIXES PERMISSION IN BROWSER SETTINGS
     ↓
USER CLICKS "Intentar de nuevo"
     ↓
retryPermission() checks permission state again
     ↓
IF now 'granted': getCurrentLocation() fetches coords
IF still 'denied': Show error again with instructions
```

---

## Error Handling

### Geolocation Error Codes (handleError in geolocation.js, lines 160-172)

1. **error.PERMISSION_DENIED (code 1)**
   - User blocked access
   - Shows browser-specific instructions
   - Shows "Intentar de nuevo" button

2. **error.POSITION_UNAVAILABLE (code 2)**
   - GPS not working / no signal
   - Message: "Verifica tu GPS"
   - Still allows user to retry

3. **error.TIMEOUT (code 3)**
   - GPS didn't respond in 30 seconds
   - Message: "Tiempo de espera agotado"

### Browser-Specific Instructions (showBrowserInstructions, lines 391-489)

**Chrome** (lines 398-411): Lock icon → Location → Allow → Reload
**Safari** (lines 412-425): Settings for this Website → Location → Allow
**Firefox** (lines 426-440): Lock icon → Clear permissions → Reload
**Edge** (lines 441-455): Lock icon → Permissions → Location → Allow → Reload
**Mobile** (lines 472-486): Device Settings → Privacy/Location → Enable

---

## Configuration Points

### Geofence Tolerance
- **Default**: 10 meters
- **Location**: Database `configuracion_sistema` table, key `geofence_tolerance`
- **Used in**: `isWithinGeofence()` function at line 114 of functions.php
- **Purpose**: Adds buffer to account for GPS accuracy variance

### Geolocation Timeout
- **Default**: 30 seconds
- **Location**: `geolocation.js` line 18
- **Purpose**: Maximum wait for GPS signal before error

### Accuracy Requirement
- **No hard minimum**: System shows accuracy level but allows clock-in anyway
- **Display levels**: Excellent (<10m), Good (<30m), Fair (<50m), Poor (<100m), Very Poor (>100m)

### Clock-Out Flexibility
- **Location requirement**: NOT required (can clock-out from anywhere)
- **Still recorded**: GPS coordinates and geofence status captured
- **Reason**: Employees may work in field or away from office

---

## Key Dependencies

- **Browser**: navigator.geolocation API (HTTPS required, modern browsers)
- **JavaScript**: ES6+ async/await, Fetch API, DOM manipulation
- **PHP**: PDO database driver, helpers (calculateDistance, isWithinGeofence)
- **Database**: MySQL/MariaDB with transaction support
- **Map Library**: Leaflet 1.9.4 (https://unpkg.com/leaflet@1.9.4)

---

## Testing Endpoints

### Manual Testing

1. **Clock-In Request** (POST)
   - URL: `{BASE_URL}/empleado/clockIn`
   - Headers: `Content-Type: application/x-www-form-urlencoded`, `X-Requested-With: XMLHttpRequest`
   - Body: `lat=20.123456&lng=-99.654321&accuracy=15&{CSRF_TOKEN_NAME}={token}`
   - Expected: `{"success": true, "message": "Entrada registrada correctamente"}`

2. **Clock-Out Request** (POST)
   - URL: `{BASE_URL}/empleado/clockOut`
   - Headers: Same as above
   - Body: `lat=20.123456&lng=-99.654321&accuracy=15&{CSRF_TOKEN_NAME}={token}`
   - Expected: `{"success": true, "message": "Salida registrada correctamente", "duration": "08:30"}`

3. **Mock Geolocation** (Browser Console)
   - Test permission states by mocking `navigator.permissions.query()`
   - Test coordinates by modifying `currentPosition` variable

