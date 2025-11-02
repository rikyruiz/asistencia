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
            $this->redirect('admin/locations/create');
        }

        // Check if code already exists
        if (!empty($data['codigo']) && $this->locationModel->findByCode($data['codigo'])) {
            $this->setFlash('error', 'El código de ubicación ya existe', 'error');
            $this->redirect('admin/locations/create');
        }

        // Create location
        $locationId = $this->locationModel->create($data);

        if ($locationId) {
            logActivity('location_created', ['location_id' => $locationId, 'name' => $data['nombre']]);
            $this->setFlash('success', 'Ubicación creada correctamente', 'success');
            $this->redirect('admin/locations');
        } else {
            $this->setFlash('error', 'Error al crear la ubicación', 'error');
            $this->redirect('admin/locations/create');
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
                $this->redirect('admin/locations/edit/' . $id);
            }
        }

        // Update location
        if ($this->locationModel->update($id, $data)) {
            logActivity('location_updated', ['location_id' => $id, 'name' => $data['nombre']]);
            $this->setFlash('success', 'Ubicación actualizada correctamente', 'success');
            $this->redirect('admin/locations');
        } else {
            $this->setFlash('error', 'Error al actualizar la ubicación', 'error');
            $this->redirect('admin/locations/edit/' . $id);
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
        $locations = $this->locationModel->getActiveLocations();

        $data = [
            'title' => 'Nuevo Usuario',
            'locations' => $locations,
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

        // Validate input
        $data = [
            'email' => sanitize($this->getPost('email')),
            'nombre' => sanitize($this->getPost('nombre')),
            'apellidos' => sanitize($this->getPost('apellidos')),
            'pin' => sanitize($this->getPost('pin')),
            'rol' => sanitize($this->getPost('rol', 'empleado')),
            'departamento' => sanitize($this->getPost('departamento')),
            'numero_empleado' => sanitize($this->getPost('numero_empleado')),
            'telefono' => sanitize($this->getPost('telefono')),
            'direccion' => sanitize($this->getPost('direccion')),
            'fecha_ingreso' => sanitize($this->getPost('fecha_ingreso')),
            'activo' => $this->getPost('activo') ? 1 : 0,
            'email_verificado' => 1, // Admin creates verified users
            'creado_por' => getUserId()
        ];

        // Validate required fields
        if (empty($data['email']) || empty($data['nombre']) || empty($data['apellidos']) || empty($data['pin'])) {
            $this->setFlash('error', 'Por favor completa todos los campos requeridos', 'error');
            $this->redirect('admin/users/create');
        }

        // Validate email
        if (!isValidEmail($data['email'])) {
            $this->setFlash('error', 'Email inválido', 'error');
            $this->redirect('admin/users/create');
        }

        // Check if email already exists
        if ($this->userModel->findByEmail($data['email'])) {
            $this->setFlash('error', 'El email ya está registrado', 'error');
            $this->redirect('admin/users/create');
        }

        // Validate PIN
        if (!isValidPin($data['pin'])) {
            $this->setFlash('error', 'El PIN debe ser de 6 dígitos', 'error');
            $this->redirect('admin/users/create');
        }

        // Hash PIN
        $data['pin'] = hashPin($data['pin']);

        // Create user
        $userId = $this->userModel->create($data);

        if ($userId) {
            // Assign locations
            $locations = $this->getPost('locations', []);
            $primaryLocation = $this->getPost('primary_location');

            foreach ($locations as $locationId) {
                $isPrimary = ($locationId == $primaryLocation);
                $this->userModel->assignLocation($userId, $locationId, $isPrimary);
            }

            logActivity('user_created', ['user_id' => $userId, 'email' => $data['email']]);
            $this->setFlash('success', 'Usuario creado correctamente', 'success');
            $this->redirect('admin/users');
        } else {
            $this->setFlash('error', 'Error al crear el usuario', 'error');
            $this->redirect('admin/users/create');
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
}