<?php
/**
 * Locations Controller (Admin namespace)
 * Handles admin/locations/* routes
 */
class LocationsController extends Controller {
    private $locationModel;
    private $userModel;

    public function __construct() {
        // Require admin authentication
        Middleware::requireAdmin();

        $this->locationModel = $this->model('Location');
        $this->userModel = $this->model('User');
    }

    /**
     * List all locations (redirects to admin controller)
     */
    public function index() {
        $this->redirect('admin/locations');
    }

    /**
     * Show create form (redirects to admin controller)
     */
    public function create() {
        $this->redirect('admin/createLocation');
    }

    /**
     * Edit location
     */
    public function edit($id) {
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
     * Update location
     */
    public function update($id = null) {
        if (!$id) {
            $this->json(['error' => 'ID de ubicación no especificado'], 400);
        }

        if (!$this->isPost()) {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        if (!$this->validateCsrfToken()) {
            $this->json(['error' => 'Token de seguridad inválido'], 403);
        }

        $location = $this->locationModel->find($id);
        if (!$location) {
            $this->json(['error' => 'Ubicación no encontrada'], 404);
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
            'activa' => $this->getPost('activa') ? 1 : 0
        ];

        // Validate required fields
        if (empty($data['nombre']) || empty($data['latitud']) || empty($data['longitud'])) {
            $this->json(['error' => 'Por favor completa todos los campos requeridos'], 400);
        }

        // Check if code already exists (except current location)
        if (!empty($data['codigo'])) {
            $existing = $this->locationModel->findByCode($data['codigo']);
            if ($existing && $existing['id'] != $id) {
                $this->json(['error' => 'El código de ubicación ya existe'], 400);
            }
        }

        // Update location
        if ($this->locationModel->update($id, $data)) {
            logActivity('location_updated', ['location_id' => $id, 'name' => $data['nombre']]);
            $this->json(['success' => true, 'message' => 'Ubicación actualizada correctamente']);
        } else {
            $this->json(['error' => 'Error al actualizar la ubicación'], 500);
        }
    }

    /**
     * Delete location
     */
    public function delete($id) {
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
            $this->json(['error' => 'No se puede eliminar una ubicación con sesiones activas'], 400);
        }

        // Soft delete (deactivate)
        if ($this->locationModel->update($id, ['activa' => 0])) {
            logActivity('location_deleted', ['location_id' => $id, 'name' => $location['nombre']]);
            $this->json(['success' => true, 'message' => 'Ubicación desactivada correctamente']);
        } else {
            $this->json(['error' => 'Error al desactivar la ubicación'], 500);
        }
    }

    /**
     * Get location details (AJAX)
     */
    public function get($id) {
        if (!$this->isAjax()) {
            $this->redirect('admin/locations');
        }

        $location = $this->locationModel->find($id);
        if (!$location) {
            $this->json(['error' => 'Ubicación no encontrada'], 404);
        }

        // Get active employees
        $activeEmployees = $this->locationModel->getActiveEmployees($id);

        // Get statistics for this month
        $statistics = $this->locationModel->getMonthlyStatistics($id);

        $this->json([
            'id' => $location['id'],
            'nombre' => $location['nombre'],
            'codigo' => $location['codigo'],
            'direccion' => $location['direccion'],
            'ciudad' => $location['ciudad'],
            'estado' => $location['estado'],
            'latitud' => $location['latitud'],
            'longitud' => $location['longitud'],
            'radio_metros' => $location['radio_metros'],
            'tipo_ubicacion' => $location['tipo_ubicacion'],
            'activa' => $location['activa'],
            'active_employees' => $activeEmployees,
            'statistics' => $statistics
        ]);
    }
}
