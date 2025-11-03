<?php
/**
 * Admin Controller
 */
class AdminController extends Controller {
    private $userModel;
    private $locationModel;
    private $attendanceModel;

    public function __construct() {
        // Require admin authentication
        Middleware::requireAdmin();

        $this->userModel = $this->model('User');
        $this->locationModel = $this->model('Location');
        $this->attendanceModel = $this->model('Attendance');
    }

    /**
     * Admin Dashboard
     */
    public function dashboard() {
        // Get today's statistics
        $todayStats = $this->attendanceModel->getDailySummary(getCurrentDate());

        // Get KPI statistics
        $kpiStats = $this->attendanceModel->getKPIStats(getCurrentDate());

        // Get active sessions
        $activeSessions = $this->getActiveSessions();

        // Get weekly statistics
        $weekStats = $this->getWeeklyStatistics();

        // Get location statistics
        $locationStats = $this->getLocationStatistics();

        // Get recent activities
        $recentActivities = $this->attendanceModel->getByDateRange(
            getCurrentDate(),
            getCurrentDate(),
            null
        );

        $data = [
            'title' => 'Dashboard Administrativo',
            'todayStats' => $todayStats,
            'kpiStats' => $kpiStats,
            'activeSessions' => $activeSessions,
            'weekStats' => $weekStats,
            'locationStats' => $locationStats,
            'recentActivities' => array_slice($recentActivities, 0, 10)
        ];

        $this->viewWithLayout('admin/dashboard', $data, 'main');
    }

    /**
     * Location Management
     */
    public function locations() {
        $locations = $this->locationModel->all('nombre', 'ASC');

        // Get statistics for each location
        foreach ($locations as &$location) {
            $location['active_employees'] = count($this->locationModel->getActiveEmployees($location['id']));
            $location['assigned_employees'] = count($this->locationModel->getUsers($location['id']));
        }

        $data = [
            'title' => 'Gestión de Ubicaciones',
            'locations' => $locations,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('admin/locations/index', $data, 'main');
    }

    /**
     * Create Location Form
     */
    public function createLocation() {
        $data = [
            'title' => 'Nueva Ubicación',
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('admin/locations/create', $data, 'main');
    }

    /**
     * Store Location
     */
    public function storeLocation() {
        if (!$this->isPost() || !$this->validateCsrfToken()) {
            $this->setFlash('error', 'Token de seguridad inválido', 'error');
            $this->redirect('admin/locations');
        }

        // Validate input
        $data = [
            'nombre' => sanitize($this->getPost('nombre')),
            'codigo' => sanitize($this->getPost('codigo')),
            'direccion' => sanitize($this->getPost('direccion')),
            'ciudad' => sanitize($this->getPost('ciudad')),
            'estado' => sanitize($this->getPost('estado')),
            'codigo_postal' => sanitize($this->getPost('codigo_postal')),
            'latitud' => floatval($this->getPost('latitud')),
            'longitud' => floatval($this->getPost('longitud')),
            'radio_metros' => intval($this->getPost('radio_metros', 100)),
            'tipo_ubicacion' => sanitize($this->getPost('tipo_ubicacion', 'oficina')),
            'horario_apertura' => sanitize($this->getPost('horario_apertura')),
            'horario_cierre' => sanitize($this->getPost('horario_cierre')),
            'dias_laborales' => implode(',', $this->getPost('dias_laborales', [])),
            'requiere_foto' => $this->getPost('requiere_foto') ? 1 : 0,
            'activa' => $this->getPost('activa') ? 1 : 0,
            'creado_por' => getUserId()
        ];

        // Validate required fields
        if (empty($data['nombre']) || empty($data['latitud']) || empty($data['longitud'])) {
            $this->setFlash('error', 'Por favor completa todos los campos requeridos', 'error');
            $this->redirect('admin/createLocation');
        }

        // Check if code already exists
        if (!empty($data['codigo']) && $this->locationModel->findByCode($data['codigo'])) {
            $this->setFlash('error', 'El código de ubicación ya existe', 'error');
            $this->redirect('admin/createLocation');
        }

        // Create location
        $locationId = $this->locationModel->create($data);

        if ($locationId) {
            logActivity('location_created', ['location_id' => $locationId, 'name' => $data['nombre']]);
            $this->setFlash('success', 'Ubicación creada correctamente', 'success');
            $this->redirect('admin/locations');
        } else {
            $this->setFlash('error', 'Error al crear la ubicación', 'error');
            $this->redirect('admin/createLocation');
        }
    }

    /**
     * Edit Location Form
     */
    public function editLocation($id) {
        $location = $this->locationModel->find($id);

        if (!$location) {
            $this->setFlash('error', 'Ubicación no encontrada', 'error');
            $this->redirect('admin/locations');
        }

        // Get assigned users
        $assignedUsers = $this->locationModel->getUsers($id);

        $data = [
            'title' => 'Editar Ubicación',
            'location' => $location,
            'assignedUsers' => $assignedUsers,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('admin/locations/edit', $data, 'main');
    }

    /**
     * Update Location
     */
    public function updateLocation($id) {
        if (!$this->isPost() || !$this->validateCsrfToken()) {
            $this->setFlash('error', 'Token de seguridad inválido', 'error');
            $this->redirect('admin/locations');
        }

        $location = $this->locationModel->find($id);
        if (!$location) {
            $this->setFlash('error', 'Ubicación no encontrada', 'error');
            $this->redirect('admin/locations');
        }

        // Prepare update data
        $data = [
            'nombre' => sanitize($this->getPost('nombre')),
            'codigo' => sanitize($this->getPost('codigo')),
            'direccion' => sanitize($this->getPost('direccion')),
            'ciudad' => sanitize($this->getPost('ciudad')),
            'estado' => sanitize($this->getPost('estado')),
            'codigo_postal' => sanitize($this->getPost('codigo_postal')),
            'latitud' => floatval($this->getPost('latitud')),
            'longitud' => floatval($this->getPost('longitud')),
            'radio_metros' => intval($this->getPost('radio_metros', 100)),
            'tipo_ubicacion' => sanitize($this->getPost('tipo_ubicacion', 'oficina')),
            'horario_apertura' => sanitize($this->getPost('horario_apertura')),
            'horario_cierre' => sanitize($this->getPost('horario_cierre')),
            'dias_laborales' => implode(',', $this->getPost('dias_laborales', [])),
            'requiere_foto' => $this->getPost('requiere_foto') ? 1 : 0,
            'activa' => $this->getPost('activa') ? 1 : 0
        ];

        // Check if code is unique
        if (!empty($data['codigo'])) {
            $existing = $this->locationModel->findByCode($data['codigo']);
            if ($existing && $existing['id'] != $id) {
                $this->setFlash('error', 'El código de ubicación ya existe', 'error');
                $this->redirect('admin/editLocation/' . $id);
            }
        }

        // Update location
        if ($this->locationModel->update($id, $data)) {
            logActivity('location_updated', ['location_id' => $id, 'name' => $data['nombre']]);
            $this->setFlash('success', 'Ubicación actualizada correctamente', 'success');
            $this->redirect('admin/locations');
        } else {
            $this->setFlash('error', 'Error al actualizar la ubicación', 'error');
            $this->redirect('admin/editLocation/' . $id);
        }
    }

    /**
     * Delete Location
     */
    public function deleteLocation($id) {
        if (!$this->isPost() || !$this->validateCsrfToken()) {
            $this->json(['error' => 'Token de seguridad inválido'], 403);
        }

        $location = $this->locationModel->find($id);
        if (!$location) {
            $this->json(['error' => 'Ubicación no encontrada'], 404);
        }

        // Check if location has active sessions
        $activeEmployees = $this->locationModel->getActiveEmployees($id);
        if (count($activeEmployees) > 0) {
            $this->json(['error' => 'No se puede eliminar una ubicación con empleados activos'], 400);
        }

        // Soft delete by deactivating
        if ($this->locationModel->update($id, ['activa' => 0])) {
            logActivity('location_deleted', ['location_id' => $id, 'name' => $location['nombre']]);
            $this->json(['success' => true, 'message' => 'Ubicación desactivada correctamente']);
        } else {
            $this->json(['error' => 'Error al eliminar la ubicación'], 500);
        }
    }

    /**
     * Get location details (AJAX)
     */
    public function getLocation($id) {
        if (!$this->isAjax()) {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        $location = $this->locationModel->find($id);
        if (!$location) {
            $this->json(['error' => 'Ubicación no encontrada'], 404);
        }

        // Get additional data
        $location['active_employees'] = $this->locationModel->getActiveEmployees($id);
        $location['assigned_users'] = $this->locationModel->getUsers($id);
        $location['statistics'] = $this->locationModel->getStatistics($id);

        $this->json($location);
    }

    /**
     * User Management
     */
    public function users() {
        // Get filters
        $filters = [
            'role' => $this->getGet('role'),
            'status' => $this->getGet('status'),
            'location' => $this->getGet('location')
        ];

        // Build conditions
        $conditions = [];
        if ($filters['role']) {
            $conditions['rol'] = $filters['role'];
        }
        if ($filters['status'] !== null && $filters['status'] !== '') {
            $conditions['activo'] = $filters['status'];
        }

        // Get users
        $users = $this->userModel->where($conditions, 'nombre', 'ASC');

        // Get locations for filter
        $locations = $this->locationModel->getActiveLocations();

        $data = [
            'title' => 'Gestión de Usuarios',
            'users' => $users,
            'locations' => $locations,
            'filters' => $filters,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('admin/users/index', $data, 'main');
    }

    /**
     * Create User Form
     */
    public function createUser() {
        $allLocations = $this->locationModel->getActiveLocations();

        $data = [
            'title' => 'Nuevo Usuario',
            'allLocations' => $allLocations,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('admin/users/create', $data, 'main');
    }

    /**
     * Store User
     */
    public function storeUser() {
        if (!$this->isPost() || !$this->validateCsrfToken()) {
            $this->setFlash('error', 'Token de seguridad inválido', 'error');
            $this->redirect('admin/users');
        }

        // Generate employee number
        $numeroEmpleado = $this->userModel->generateEmployeeNumber();

        // Validate input
        $data = [
            'username' => sanitize($this->getPost('username')),
            'email' => sanitize($this->getPost('email')),
            'nombre' => sanitize($this->getPost('nombre')),
            'apellidos' => sanitize($this->getPost('apellidos')),
            'pin' => sanitize($this->getPost('pin')),
            'rol' => sanitize($this->getPost('rol', 'empleado')),
            'departamento' => sanitize($this->getPost('departamento')),
            'numero_empleado' => $numeroEmpleado,
            'telefono' => sanitize($this->getPost('telefono')),
            'activo' => $this->getPost('activo') ? 1 : 0,
            'email_verificado' => $this->getPost('email_verificado') ? 1 : 0,
            'creado_por' => getUserId()
        ];

        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['nombre']) || empty($data['apellidos']) || empty($data['pin'])) {
            $this->setFlash('error', 'Por favor completa todos los campos requeridos', 'error');
            $this->redirect('admin/createUser');
        }

        // Validate email
        if (!isValidEmail($data['email'])) {
            $this->setFlash('error', 'Email inválido', 'error');
            $this->redirect('admin/createUser');
        }

        // Check if username already exists
        if ($this->userModel->findByUsername($data['username'])) {
            $this->setFlash('error', 'El nombre de usuario ya está en uso', 'error');
            $this->redirect('admin/createUser');
        }

        // Check if email already exists
        if ($this->userModel->findByEmail($data['email'])) {
            $this->setFlash('error', 'El email ya está registrado', 'error');
            $this->redirect('admin/createUser');
        }

        // Validate PIN
        if (!isValidPin($data['pin'])) {
            $this->setFlash('error', 'El PIN debe ser de 6 dígitos', 'error');
            $this->redirect('admin/createUser');
        }

        // Hash PIN
        $data['pin'] = hashPin($data['pin']);

        // Create user
        $userId = $this->userModel->create($data);

        if ($userId) {
            // Assign locations
            $locations = $this->getPost('locations', []);

            foreach ($locations as $locationId) {
                $this->userModel->assignLocation($userId, $locationId, 0);
            }

            logActivity('user_created', ['user_id' => $userId, 'username' => $data['username'], 'employee_number' => $numeroEmpleado]);
            $this->setFlash('success', 'Usuario creado correctamente con número de empleado: ' . $numeroEmpleado, 'success');
            $this->redirect('admin/users');
        } else {
            $this->setFlash('error', 'Error al crear el usuario', 'error');
            $this->redirect('admin/createUser');
        }
    }

    /**
     * Edit User Form
     */
    public function editUser($id) {
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->setFlash('error', 'Usuario no encontrado', 'error');
            $this->redirect('admin/users');
        }

        // Get user's locations
        $userLocations = $this->userModel->getLocations($id);
        $assignedLocationIds = array_column($userLocations, 'id');

        // Get all locations
        $allLocations = $this->locationModel->getActiveLocations();

        $data = [
            'title' => 'Editar Usuario',
            'user' => $user,
            'userLocations' => $userLocations,
            'assignedLocationIds' => $assignedLocationIds,
            'allLocations' => $allLocations,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('admin/users/edit', $data, 'main');
    }

    /**
     * Update User
     */
    public function updateUser($id) {
        if (!$this->isPost() || !$this->validateCsrfToken()) {
            $this->setFlash('error', 'Token de seguridad inválido', 'error');
            $this->redirect('admin/users');
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->setFlash('error', 'Usuario no encontrado', 'error');
            $this->redirect('admin/users');
        }

        // Prepare update data
        $data = [
            'username' => sanitize($this->getPost('username')),
            'email' => sanitize($this->getPost('email')),
            'nombre' => sanitize($this->getPost('nombre')),
            'apellidos' => sanitize($this->getPost('apellidos')),
            'rol' => sanitize($this->getPost('rol')),
            'departamento' => sanitize($this->getPost('departamento')),
            'telefono' => sanitize($this->getPost('telefono')),
            'activo' => $this->getPost('activo') ? 1 : 0,
            'email_verificado' => $this->getPost('email_verificado') ? 1 : 0
        ];

        // Update PIN if provided
        $newPin = sanitize($this->getPost('new_pin'));
        $confirmPin = sanitize($this->getPost('confirm_pin'));

        if (!empty($newPin) || !empty($confirmPin)) {
            if ($newPin !== $confirmPin) {
                $this->setFlash('error', 'Los PINs no coinciden', 'error');
                $this->redirect('admin/editUser/' . $id);
            }
            if (!isValidPin($newPin)) {
                $this->setFlash('error', 'El PIN debe ser de 6 dígitos', 'error');
                $this->redirect('admin/editUser/' . $id);
            }
            $data['pin'] = hashPin($newPin);
        }

        // Check if username is unique
        if ($data['username'] !== $user['username']) {
            $existingUser = $this->userModel->findByUsername($data['username']);
            if ($existingUser && $existingUser['id'] != $id) {
                $this->setFlash('error', 'El nombre de usuario ya está en uso', 'error');
                $this->redirect('admin/editUser/' . $id);
            }
        }

        // Check if email is unique
        if ($data['email'] !== $user['email']) {
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $id) {
                $this->setFlash('error', 'El email ya está registrado', 'error');
                $this->redirect('admin/editUser/' . $id);
            }
        }

        // Update user
        if ($this->userModel->update($id, $data)) {
            // Update location assignments
            $locations = $this->getPost('locations', []);

            // Get current locations
            $currentLocations = $this->userModel->getLocations($id);
            $currentLocationIds = array_column($currentLocations, 'id');

            // Remove unassigned locations
            foreach ($currentLocationIds as $locationId) {
                if (!in_array($locationId, $locations)) {
                    $this->userModel->removeLocation($id, $locationId);
                }
            }

            // Add new locations (default is_principal to 0 since we don't have that in the form)
            foreach ($locations as $locationId) {
                if (!in_array($locationId, $currentLocationIds)) {
                    $this->userModel->assignLocation($id, $locationId, 0);
                }
            }

            logActivity('user_updated', ['user_id' => $id, 'username' => $data['username']]);
            $this->setFlash('success', 'Usuario actualizado correctamente', 'success');
            $this->redirect('admin/users');
        } else {
            $this->setFlash('error', 'Error al actualizar el usuario', 'error');
            $this->redirect('admin/editUser/' . $id);
        }
    }

    /**
     * Delete/Deactivate User
     */
    public function deleteUser($id) {
        if (!$this->isPost() || !$this->validateCsrfToken()) {
            $this->json(['error' => 'Token de seguridad inválido'], 403);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Don't allow deleting self
        if ($id == getUserId()) {
            $this->json(['error' => 'No puedes eliminar tu propia cuenta'], 400);
        }

        // Check if user has active session
        if ($this->userModel->hasActiveSession($id)) {
            $this->json(['error' => 'No se puede eliminar un usuario con sesión activa'], 400);
        }

        // Soft delete by deactivating
        if ($this->userModel->update($id, ['activo' => 0])) {
            logActivity('user_deleted', ['user_id' => $id, 'email' => $user['email']]);
            $this->json(['success' => true, 'message' => 'Usuario desactivado correctamente']);
        } else {
            $this->json(['error' => 'Error al eliminar el usuario'], 500);
        }
    }

    /**
     * Get active sessions for dashboard
     */
    private function getActiveSessions() {
        $sql = "SELECT st.*, u.nombre, u.apellidos, u.numero_empleado, l.nombre as ubicacion
                FROM sesiones_trabajo st
                JOIN usuarios u ON st.usuario_id = u.id
                LEFT JOIN ubicaciones l ON st.ubicacion_id = l.id
                WHERE st.estado = 'activa'
                ORDER BY st.hora_entrada DESC";

        $sessions = $this->attendanceModel->raw($sql);

        // Calculate working time
        foreach ($sessions as &$session) {
            $start = strtotime($session['hora_entrada']);
            $diff = time() - $start;
            $session['tiempo_trabajado'] = sprintf("%02d:%02d", floor($diff / 3600), floor(($diff % 3600) / 60));
        }

        return $sessions;
    }

    /**
     * Get weekly statistics
     */
    private function getWeeklyStatistics() {
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = getCurrentDate();

        $stats = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $summary = $this->attendanceModel->getDailySummary($currentDate);
            $stats[] = [
                'date' => $currentDate,
                'day' => getDayNameSpanish($currentDate),
                'entries' => $summary['total_entradas'],
                'exits' => $summary['total_salidas'],
                'employees' => $summary['empleados_total']
            ];
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        return $stats;
    }

    /**
     * Get location statistics
     */
    private function getLocationStatistics() {
        $locations = $this->locationModel->getActiveLocations();
        $stats = [];

        foreach ($locations as $location) {
            $activeEmployees = $this->locationModel->getActiveEmployees($location['id']);
            $stats[] = [
                'name' => $location['nombre'],
                'active' => count($activeEmployees),
                'total' => count($this->locationModel->getUsers($location['id']))
            ];
        }

        return $stats;
    }

    /**
     * Reports Dashboard
     */
    public function reports() {
        // Get date range from request or default to current month
        $startDate = $this->getGet('start_date', date('Y-m-01'));
        $endDate = $this->getGet('end_date', date('Y-m-d'));

        // Get filters
        $filters = [
            'user_id' => $this->getGet('user_id'),
            'location_id' => $this->getGet('location_id'),
            'tipo' => $this->getGet('tipo')
        ];

        // Get all users for filter
        $users = $this->userModel->all('nombre', 'ASC');

        // Get all locations for filter
        $locations = $this->locationModel->getActiveLocations();

        $data = [
            'title' => 'Reportes',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $filters,
            'users' => $users,
            'locations' => $locations,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('admin/reports/index', $data, 'main');
    }

    /**
     * Generate and export report
     */
    public function generateReport() {
        $type = $this->getGet('type'); // attendance, summary, location, incomplete, violations
        $format = $this->getGet('format', 'pdf'); // pdf, excel, csv
        $startDate = $this->getGet('start_date', date('Y-m-01'));
        $endDate = $this->getGet('end_date', date('Y-m-d'));
        $userId = $this->getGet('user_id');
        $locationId = $this->getGet('location_id');

        if (!in_array($type, ['attendance', 'summary', 'location', 'incomplete', 'violations'])) {
            $this->setFlash('error', 'Tipo de reporte inválido', 'error');
            $this->redirect('admin/reports');
        }

        if (!in_array($format, ['pdf', 'excel', 'csv'])) {
            $this->setFlash('error', 'Formato de exportación inválido', 'error');
            $this->redirect('admin/reports');
        }

        // Load ReportExporter
        require_once __DIR__ . '/../helpers/ReportExporter.php';

        try {
            switch ($type) {
                case 'attendance':
                    $this->exportAttendanceReport($startDate, $endDate, $userId, $locationId, $format);
                    break;
                case 'summary':
                    $this->exportSummaryReport($startDate, $endDate, $userId, $locationId, $format);
                    break;
                case 'location':
                    $this->exportLocationReport($startDate, $endDate, $locationId, $format);
                    break;
                case 'incomplete':
                    $this->exportIncompleteSessionsReport($startDate, $endDate, $userId, $locationId, $format);
                    break;
                case 'violations':
                    $this->exportGeofenceViolationsReport($startDate, $endDate, $userId, $locationId, $format);
                    break;
            }
        } catch (Exception $e) {
            logError('Report generation failed: ' . $e->getMessage());
            $this->setFlash('error', 'Error al generar el reporte: ' . $e->getMessage(), 'error');
            $this->redirect('admin/reports');
        }
    }

    /**
     * Export attendance report
     */
    private function exportAttendanceReport($startDate, $endDate, $userId, $locationId, $format) {
        // Get attendance data
        $data = $this->attendanceModel->getByDateRange($startDate, $endDate, $locationId);

        // Filter by user if specified
        if ($userId) {
            $data = array_filter($data, function($record) use ($userId) {
                return $record['usuario_id'] == $userId;
            });
        }

        // Prepare filters info for report
        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'user_name' => null,
            'location_name' => null
        ];

        if ($userId) {
            $user = $this->userModel->find($userId);
            $filters['user_name'] = $user ? $user['nombre'] . ' ' . $user['apellidos'] : null;
        }

        if ($locationId) {
            $location = $this->locationModel->find($locationId);
            $filters['location_name'] = $location ? $location['nombre'] : null;
        }

        // Add duration to records
        $sessions = [];
        $sql = "SELECT entrada_id, salida_id, duracion_minutos FROM sesiones_trabajo WHERE estado = 'completada'";
        $completedSessions = $this->attendanceModel->raw($sql);

        foreach ($completedSessions as $session) {
            $sessions[$session['entrada_id']] = $session['duracion_minutos'];
            $sessions[$session['salida_id']] = $session['duracion_minutos'];
        }

        foreach ($data as &$record) {
            $record['duracion_minutos'] = $sessions[$record['id']] ?? null;
        }

        ReportExporter::exportAttendanceReport($data, $filters, $format);
    }

    /**
     * Export summary report
     */
    private function exportSummaryReport($startDate, $endDate, $userId, $locationId, $format) {
        // Build query to get summary data
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $sql = "SELECT
                    u.id as usuario_id,
                    u.nombre,
                    u.apellidos,
                    u.numero_empleado,
                    COUNT(DISTINCT DATE(st.hora_entrada)) as dias_trabajados,
                    COALESCE(SUM(st.duracion_minutos), 0) as total_minutos
                FROM usuarios u
                LEFT JOIN sesiones_trabajo st ON u.id = st.usuario_id
                    AND st.estado = 'completada'
                    AND DATE(st.hora_entrada) BETWEEN :start_date AND :end_date";

        $conditions = [];

        if ($userId) {
            $conditions[] = "u.id = :user_id";
            $params['user_id'] = $userId;
        }

        if ($locationId) {
            $conditions[] = "st.ubicacion_id = :location_id";
            $params['location_id'] = $locationId;
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(' AND ', $conditions);
        }

        $sql .= " GROUP BY u.id, u.nombre, u.apellidos, u.numero_empleado
                  HAVING dias_trabajados > 0
                  ORDER BY u.nombre, u.apellidos";

        $data = $this->attendanceModel->raw($sql, $params);

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        ReportExporter::exportSummaryReport($data, $filters, $format);
    }

    /**
     * Export location report
     */
    private function exportLocationReport($startDate, $endDate, $locationId, $format) {
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $sql = "SELECT
                    l.id as ubicacion_id,
                    l.nombre as ubicacion_nombre,
                    COUNT(CASE WHEN ra.tipo = 'entrada' THEN 1 END) as total_entradas,
                    COUNT(CASE WHEN ra.tipo = 'salida' THEN 1 END) as total_salidas,
                    COUNT(DISTINCT ra.usuario_id) as empleados_unicos
                FROM ubicaciones l
                LEFT JOIN registros_asistencia ra ON l.id = ra.ubicacion_id
                    AND ra.fecha_local BETWEEN :start_date AND :end_date
                WHERE l.activa = 1";

        if ($locationId) {
            $sql .= " AND l.id = :location_id";
            $params['location_id'] = $locationId;
        }

        $sql .= " GROUP BY l.id, l.nombre
                  ORDER BY l.nombre";

        $data = $this->attendanceModel->raw($sql, $params);

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        ReportExporter::exportLocationReport($data, $filters, $format);
    }

    /**
     * Export incomplete sessions report (missing clock-outs)
     */
    private function exportIncompleteSessionsReport($startDate, $endDate, $userId, $locationId, $format) {
        // Get incomplete sessions data
        $data = $this->attendanceModel->getIncompleteSessions($startDate, $endDate, $userId, $locationId);

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'user_id' => $userId,
            'location_id' => $locationId
        ];

        ReportExporter::exportIncompleteSessionsReport($data, $filters, $format);
    }

    /**
     * Export geofence violations report
     */
    private function exportGeofenceViolationsReport($startDate, $endDate, $userId, $locationId, $format) {
        // Get geofence violations data
        $data = $this->attendanceModel->getGeofenceViolations($startDate, $endDate, $userId, $locationId);

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'user_id' => $userId,
            'location_id' => $locationId
        ];

        ReportExporter::exportGeofenceViolationsReport($data, $filters, $format);
    }
}