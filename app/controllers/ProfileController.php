<?php
/**
 * Profile Controller
 */
class ProfileController extends Controller {
    private $userModel;

    public function __construct() {
        // Require authentication
        Middleware::authenticate();
        $this->userModel = $this->model('User');
    }

    /**
     * View Profile
     */
    public function index() {
        $userId = getUserId();
        $user = $this->userModel->find($userId);

        if (!$user) {
            $this->setFlash('error', 'Usuario no encontrado', 'error');
            $this->redirect('');
        }

        // Get user locations
        $locations = $this->userModel->getLocations($userId);

        $data = [
            'title' => 'Mi Perfil',
            'user' => $user,
            'locations' => $locations,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('profile/index', $data, 'main');
    }

    /**
     * Update Profile
     */
    public function update() {
        if (!$this->isPost() || !$this->validateCsrfToken()) {
            $this->setFlash('error', 'Token de seguridad inválido', 'error');
            $this->redirect('profile');
        }

        $userId = getUserId();
        $user = $this->userModel->find($userId);

        if (!$user) {
            $this->setFlash('error', 'Usuario no encontrado', 'error');
            $this->redirect('');
        }

        // Collect data
        $data = [
            'nombre' => $this->getPost('nombre'),
            'apellidos' => $this->getPost('apellidos'),
            'telefono' => $this->getPost('telefono'),
            'departamento' => $this->getPost('departamento')
        ];

        // Validate
        $errors = [];

        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es requerido';
        }

        if (empty($data['apellidos'])) {
            $errors[] = 'Los apellidos son requeridos';
        }

        if (!empty($errors)) {
            $this->setFlash('error', implode(', ', $errors), 'error');
            $this->redirect('profile');
        }

        // Update user
        if ($this->userModel->update($userId, $data)) {
            // Update session
            $_SESSION['user_nombre'] = $data['nombre'];

            logActivity('profile_updated', ['user_id' => $userId]);
            $this->setFlash('success', 'Perfil actualizado correctamente', 'success');
        } else {
            $this->setFlash('error', 'Error al actualizar el perfil', 'error');
        }

        $this->redirect('profile');
    }

    /**
     * Change PIN
     */
    public function changePin() {
        if (!$this->isPost() || !$this->validateCsrfToken()) {
            $this->json(['error' => 'Token de seguridad inválido'], 403);
        }

        $userId = getUserId();
        $currentPin = $this->getPost('current_pin');
        $newPin = $this->getPost('new_pin');
        $confirmPin = $this->getPost('confirm_pin');

        // Validate
        if (empty($currentPin) || empty($newPin) || empty($confirmPin)) {
            $this->json(['error' => 'Todos los campos son requeridos'], 400);
        }

        if (strlen($newPin) !== 6 || !is_numeric($newPin)) {
            $this->json(['error' => 'El PIN debe tener exactamente 6 dígitos'], 400);
        }

        if ($newPin !== $confirmPin) {
            $this->json(['error' => 'Los PINs no coinciden'], 400);
        }

        // Verify current PIN
        $user = $this->userModel->find($userId);
        if (!password_verify($currentPin, $user['pin'])) {
            $this->json(['error' => 'El PIN actual es incorrecto'], 400);
        }

        // Update PIN
        $hashedPin = hashPin($newPin);
        if ($this->userModel->update($userId, ['pin' => $hashedPin])) {
            logActivity('pin_changed', ['user_id' => $userId]);
            $this->json(['success' => true, 'message' => 'PIN actualizado correctamente']);
        } else {
            $this->json(['error' => 'Error al actualizar el PIN'], 500);
        }
    }
}
