<?php
/**
 * Employee Controller
 */
class EmpleadoController extends Controller {
    private $userModel;
    private $attendanceModel;
    private $locationModel;

    public function __construct() {
        // Require employee authentication
        Middleware::requireEmployee();

        $this->userModel = $this->model('User');
        $this->attendanceModel = $this->model('Attendance');
        $this->locationModel = $this->model('Location');
    }

    /**
     * Employee Dashboard
     */
    public function dashboard() {
        $userId = getUserId();

        // Get user's active session if any
        $activeSession = $this->userModel->getActiveSession($userId);

        // Get user's assigned locations
        $locations = $this->userModel->getLocations($userId);

        // Get recent attendance history (last 7 days)
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $history = $this->attendanceModel->getUserHistory($userId, $startDate, null, 10);

        // Calculate week statistics
        $weekStats = $this->calculateWeekStats($history);

        $data = [
            'title' => 'Panel de Control',
            'user' => getUser(),
            'activeSession' => $activeSession,
            'locations' => $locations,
            'history' => $history,
            'weekStats' => $weekStats,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('empleado/dashboard', $data, 'main');
    }

    /**
     * Clock In/Out Page
     */
    public function clock() {
        $userId = getUserId();

        // Get user's active session
        $activeSession = $this->userModel->getActiveSession($userId);

        // Get user's assigned locations for validation
        $locations = $this->userModel->getLocations($userId);

        // Transform location data for JavaScript compatibility
        $transformedLocations = array_map(function($location) {
            return [
                'id' => $location['id'],
                'name' => $location['nombre'],
                'lat' => floatval($location['latitud']),
                'lng' => floatval($location['longitud']),
                'radius' => intval($location['radio_metros'])
            ];
        }, $locations);

        $data = [
            'title' => $activeSession ? 'Registrar Salida' : 'Registrar Entrada',
            'activeSession' => $activeSession,
            'locations' => $transformedLocations,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('empleado/clock', $data, 'main');
    }

    /**
     * Process Clock In (AJAX)
     */
    public function clockIn() {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        if (!$this->validateCsrfToken()) {
            $this->json(['error' => 'Token de seguridad inválido'], 403);
        }

        $userId = getUserId();
        $lat = floatval($this->getPost('lat'));
        $lng = floatval($this->getPost('lng'));
        $accuracy = floatval($this->getPost('accuracy'));

        // Validate coordinates
        if (!$lat || !$lng) {
            $this->json(['error' => 'Coordenadas inválidas'], 400);
        }

        // Find the location based on coordinates
        $location = $this->locationModel->findLocationByCoordinates($lat, $lng);

        if (!$location) {
            // Get nearest location for reference
            $nearest = $this->locationModel->getNearestLocation($lat, $lng);
            $this->json([
                'error' => 'No estás dentro de ninguna ubicación autorizada',
                'nearest' => $nearest ? [
                    'name' => $nearest['nombre'],
                    'distance' => $nearest['distancia']
                ] : null
            ], 400);
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
        }

        // Check if location is open - TEMPORARILY DISABLED
        // if (!$this->locationModel->isOpen($location['id'])) {
        //     $this->json(['error' => 'La ubicación está fuera del horario laboral'], 400);
        // }

        // Process clock in
        $result = $this->attendanceModel->clockIn(
            $userId,
            $location['id'],
            $lat,
            $lng,
            $accuracy,
            true, // within geofence (already validated)
            $location['distancia']
        );

        if (isset($result['error'])) {
            $errorMsg = 'Error al registrar entrada';
            if ($result['error'] === 'active_session_exists') {
                $errorMsg = 'Ya tienes una sesión activa. Debes registrar salida primero.';
            }
            $this->json(['error' => $errorMsg], 400);
        }

        $this->json([
            'success' => true,
            'message' => 'Entrada registrada correctamente',
            'location' => $location['nombre'],
            'time' => formatDateTime(getCurrentDateTime(), 'H:i'),
            'session_id' => $result['session_id']
        ]);
    }

    /**
     * Process Clock Out (AJAX)
     */
    public function clockOut() {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        if (!$this->validateCsrfToken()) {
            $this->json(['error' => 'Token de seguridad inválido'], 403);
        }

        $userId = getUserId();
        $lat = floatval($this->getPost('lat'));
        $lng = floatval($this->getPost('lng'));
        $accuracy = floatval($this->getPost('accuracy'));

        // Clock out is allowed from anywhere, but we still record the location
        $location = null;
        $withinGeofence = false;
        $distance = null;

        if ($lat && $lng) {
            $location = $this->locationModel->findLocationByCoordinates($lat, $lng);
            if ($location) {
                $withinGeofence = true;
                $distance = $location['distancia'];
            } else {
                // Get nearest for reference
                $nearest = $this->locationModel->getNearestLocation($lat, $lng);
                if ($nearest) {
                    $distance = $nearest['distancia'];
                }
            }
        }

        // Process clock out
        $result = $this->attendanceModel->clockOut(
            $userId,
            $location ? $location['id'] : null,
            $lat,
            $lng,
            $accuracy,
            $withinGeofence,
            $distance
        );

        if (isset($result['error'])) {
            $errorMsg = 'Error al registrar salida';
            if ($result['error'] === 'no_active_session') {
                $errorMsg = 'No tienes una sesión activa para cerrar.';
            }
            $this->json(['error' => $errorMsg], 400);
        }

        $this->json([
            'success' => true,
            'message' => 'Salida registrada correctamente',
            'location' => $location ? $location['nombre'] : 'Fuera de ubicación autorizada',
            'time' => formatDateTime(getCurrentDateTime(), 'H:i'),
            'duration' => $result['duration']['formatted'],
            'within_geofence' => $withinGeofence
        ]);
    }

    /**
     * Get user locations (AJAX)
     */
    public function getLocations() {
        if (!$this->isAjax()) {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        $userId = getUserId();
        $locations = $this->userModel->getLocations($userId);

        // Format for frontend
        $formatted = array_map(function($loc) {
            return [
                'id' => $loc['id'],
                'name' => $loc['nombre'],
                'lat' => floatval($loc['latitud']),
                'lng' => floatval($loc['longitud']),
                'radius' => intval($loc['radio_metros']),
                'is_primary' => $loc['es_principal'] == 1
            ];
        }, $locations);

        $this->json(['locations' => $formatted]);
    }

    /**
     * Get attendance history
     */
    public function history() {
        $userId = getUserId();

        // Get filters
        $startDate = $this->getGet('start_date', date('Y-m-01')); // Default: first day of month
        $endDate = $this->getGet('end_date', getCurrentDate());

        // Get history
        $history = $this->attendanceModel->getUserHistory($userId, $startDate, $endDate);

        // Group by date
        $groupedHistory = $this->groupHistoryByDate($history);

        // Calculate totals
        $totals = $this->calculateTotals($history);

        $data = [
            'title' => 'Historial de Asistencia',
            'history' => $groupedHistory,
            'totals' => $totals,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];

        $this->viewWithLayout('empleado/history', $data, 'main');
    }

    /**
     * Calculate week statistics
     */
    private function calculateWeekStats($history) {
        $totalHours = 0;
        $totalDays = [];
        $onTimeCount = 0;
        $lateCount = 0;

        foreach ($history as $record) {
            if ($record['tipo'] === 'entrada') {
                $date = date('Y-m-d', strtotime($record['fecha_hora']));
                if (!in_array($date, $totalDays)) {
                    $totalDays[] = $date;
                }

                // Check if on time (assuming 8 AM start)
                $entryTime = date('H:i', strtotime($record['fecha_hora']));
                if ($entryTime <= '08:15') {
                    $onTimeCount++;
                } else {
                    $lateCount++;
                }
            }

            if ($record['duracion_minutos']) {
                $totalHours += $record['duracion_minutos'] / 60;
            }
        }

        return [
            'total_hours' => round($totalHours, 1),
            'total_days' => count($totalDays),
            'on_time' => $onTimeCount,
            'late' => $lateCount,
            'attendance_rate' => count($totalDays) > 0 ? round(($onTimeCount / count($totalDays)) * 100) : 0
        ];
    }

    /**
     * Group history by date
     */
    private function groupHistoryByDate($history) {
        $grouped = [];

        foreach ($history as $record) {
            $date = date('Y-m-d', strtotime($record['fecha_hora']));
            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'day_name' => getDayNameSpanish($date),
                    'records' => []
                ];
            }
            $grouped[$date]['records'][] = $record;
        }

        return $grouped;
    }

    /**
     * Calculate totals from history
     */
    private function calculateTotals($history) {
        $totalMinutes = 0;
        $totalDays = [];
        $totalEntries = 0;
        $totalExits = 0;

        foreach ($history as $record) {
            if ($record['tipo'] === 'entrada') {
                $totalEntries++;
                $date = date('Y-m-d', strtotime($record['fecha_hora']));
                if (!in_array($date, $totalDays)) {
                    $totalDays[] = $date;
                }
            } else {
                $totalExits++;
            }

            if ($record['duracion_minutos']) {
                $totalMinutes += $record['duracion_minutos'];
            }
        }

        return [
            'hours' => floor($totalMinutes / 60),
            'minutes' => $totalMinutes % 60,
            'days' => count($totalDays),
            'entries' => $totalEntries,
            'exits' => $totalExits
        ];
    }
}