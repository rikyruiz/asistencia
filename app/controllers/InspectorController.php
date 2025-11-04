<?php
/**
 * Inspector Controller
 * Inspectors have read-only access to view reports, locations, and users
 */
class InspectorController extends Controller {
    private $userModel;
    private $attendanceModel;
    private $locationModel;

    public function __construct() {
        // Require inspector role or higher
        Middleware::requireViewer();

        $this->userModel = $this->model('User');
        $this->attendanceModel = $this->model('Attendance');
        $this->locationModel = $this->model('Location');
    }

    /**
     * Inspector Dashboard
     * Shows read-only view of system stats and recent activity
     */
    public function dashboard() {
        $userId = getUserId();

        // Get system statistics
        $stats = [
            'total_employees' => $this->userModel->count(['rol' => 'empleado', 'activo' => 1]),
            'active_sessions' => $this->attendanceModel->getActiveSessionsCount(),
            'total_locations' => $this->locationModel->count(['activa' => 1]),
            'today_registrations' => $this->attendanceModel->getTodayCount()
        ];

        // Get recent attendance records (last 20)
        $recentAttendance = $this->attendanceModel->getRecentWithDetails(20);

        // Get active sessions
        $activeSessions = $this->attendanceModel->getActiveSessions();

        $data = [
            'title' => 'Dashboard Inspector',
            'stats' => $stats,
            'recent_attendance' => $recentAttendance,
            'active_sessions' => $activeSessions,
            'user_role' => $_SESSION['user_role']
        ];

        $this->viewWithLayout('inspector/dashboard', $data, 'main');
    }

    /**
     * View all reports (read-only)
     */
    public function reports() {
        $this->redirect('admin/reports');
    }

    /**
     * View locations (read-only)
     */
    public function locations() {
        $this->redirect('admin/locations');
    }

    /**
     * View users (read-only)
     */
    public function users() {
        $this->redirect('admin/users');
    }

    /**
     * Clock In/Out Page
     * Allows inspectors to register their own attendance
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

        $this->viewWithLayout('inspector/clock', $data, 'main');
    }

    /**
     * Process Clock In (AJAX)
     */
    public function clockIn() {
        try {
            if (!$this->isPost() || !$this->isAjax()) {
                $this->json(['error' => 'Método no permitido'], 405);
                return;
            }

            if (!$this->validateCsrfToken()) {
                $this->json(['error' => 'Token de seguridad inválido'], 403);
                return;
            }

            $userId = getUserId();

            // Get location and GPS data
            $locationId = $this->getPost('location_id');
            $lat = $this->getPost('lat');
            $lng = $this->getPost('lng');
            $precision = $this->getPost('accuracy');

            // Validate required fields
            if (!$locationId || $lat === null || $lat === '' || $lng === null || $lng === '') {
                logError('Inspector clock in missing data', [
                    'user_id' => $userId,
                    'location_id' => $locationId,
                    'lat' => $lat,
                    'lng' => $lng,
                    'post_data' => $_POST
                ]);
                $this->json(['error' => 'Faltan datos requeridos'], 400);
                return;
            }

            // Convert to float
            $lat = floatval($lat);
            $lng = floatval($lng);
            $precision = floatval($precision);

            // Get location details
            $location = $this->locationModel->find($locationId);
            if (!$location) {
                $this->json(['error' => 'Ubicación no encontrada'], 404);
                return;
            }

            // Calculate distance and verify geofence
            $distance = calculateDistance(
                $lat,
                $lng,
                $location['latitud'],
                $location['longitud']
            );

            $withinGeofence = isWithinGeofence(
                $lat,
                $lng,
                $location['latitud'],
                $location['longitud'],
                $location['radio_metros']
            );

            // Register clock in
            $result = $this->attendanceModel->clockIn(
                $userId,
                $locationId,
                $lat,
                $lng,
                $precision,
                $withinGeofence,
                round($distance, 2)
            );

            if (isset($result['error'])) {
                $errorMessages = [
                    'active_session_exists' => 'Ya tienes una sesión activa. Debes registrar salida primero.',
                    'outside_operating_hours' => 'Fuera del horario de operación de esta ubicación'
                ];
                $message = $errorMessages[$result['error']] ?? 'Error al registrar entrada';
                $this->json(['error' => $message], 400);
                return;
            }

            logActivity('clock_in_inspector', [
                'user_id' => $userId,
                'location_id' => $locationId,
                'within_geofence' => $withinGeofence,
                'distance' => round($distance, 2)
            ]);

            $this->json([
                'success' => true,
                'message' => 'Entrada registrada correctamente',
                'session_id' => $result,
                'within_geofence' => $withinGeofence,
                'distance' => round($distance, 2)
            ]);

        } catch (Exception $e) {
            logError('clock_in_error', ['error' => $e->getMessage(), 'user_id' => getUserId()]);
            $this->json(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process Clock Out (AJAX)
     */
    public function clockOut() {
        try {
            if (!$this->isPost() || !$this->isAjax()) {
                $this->json(['error' => 'Método no permitido'], 405);
                return;
            }

            if (!$this->validateCsrfToken()) {
                $this->json(['error' => 'Token de seguridad inválido'], 403);
                return;
            }

            $userId = getUserId();

            // Get GPS data (optional for clock out)
            $lat = $this->getPost('lat');
            $lng = $this->getPost('lng');
            $precision = $this->getPost('accuracy');

            // Get active session
            $activeSession = $this->userModel->getActiveSession($userId);
            if (!$activeSession) {
                $this->json(['error' => 'No tienes una sesión activa'], 400);
                return;
            }

            // For clock out, we allow it from anywhere but still record the location
            $withinGeofence = false;
            $distance = null;
            $locationId = $activeSession['ubicacion_id'];

            if ($lat && $lng && $locationId) {
                $location = $this->locationModel->find($locationId);
                if ($location) {
                    $distance = calculateDistance(
                        $lat,
                        $lng,
                        $location['latitud'],
                        $location['longitud']
                    );

                    $withinGeofence = isWithinGeofence(
                        $lat,
                        $lng,
                        $location['latitud'],
                        $location['longitud'],
                        $location['radio_metros']
                    );
                }
            }

            // Register clock out
            $result = $this->attendanceModel->clockOut(
                $userId,
                $lat,
                $lng,
                $precision,
                $withinGeofence,
                $distance ? round($distance, 2) : null
            );

            if (isset($result['error'])) {
                $errorMessages = [
                    'no_active_session' => 'No tienes una sesión activa'
                ];
                $message = $errorMessages[$result['error']] ?? 'Error al registrar salida';
                $this->json(['error' => $message], 400);
                return;
            }

            logActivity('clock_out_inspector', [
                'user_id' => $userId,
                'session_id' => $activeSession['id'],
                'within_geofence' => $withinGeofence,
                'distance' => $distance ? round($distance, 2) : null
            ]);

            $this->json([
                'success' => true,
                'message' => 'Salida registrada correctamente',
                'duration' => $result['duration'] ?? null,
                'within_geofence' => $withinGeofence
            ]);

        } catch (Exception $e) {
            logError('clock_out_error', ['error' => $e->getMessage(), 'user_id' => getUserId()]);
            $this->json(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()], 500);
        }
    }

    /**
     * View attendance history
     */
    public function history() {
        $this->redirect('empleado/history');
    }
}
