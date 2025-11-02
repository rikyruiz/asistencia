<?php
/**
 * Authentication Controller
 */
class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        $this->userModel = $this->model('User');
    }

    /**
     * Show login form
     */
    public function login() {
        // Redirect if already logged in
        if (isLoggedIn()) {
            $this->redirect('');
        }

        $data = [
            'title' => 'Iniciar Sesión',
            'csrf_token' => $this->generateCsrfToken(),
            'error' => null
        ];

        // Check for flash messages
        if ($flash = $this->getFlash('login')) {
            $data['error'] = $flash['message'];
        }

        $this->viewWithLayout('auth/login', $data, 'auth');
    }

    /**
     * Process login
     */
    public function processLogin() {
        if (!$this->isPost()) {
            $this->redirect('auth/login');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('login', 'Token de seguridad inválido', 'error');
            $this->redirect('auth/login');
        }

        $username = sanitize($this->getPost('username'));
        $pin = sanitize($this->getPost('pin'));
        $remember = $this->getPost('remember');

        // Validate inputs
        if (empty($username) || empty($pin)) {
            $this->setFlash('login', 'Por favor ingresa tu usuario y PIN', 'error');
            $this->redirect('auth/login');
        }

        if (!isValidPin($pin)) {
            $this->setFlash('login', 'PIN debe ser de 6 dígitos', 'error');
            $this->redirect('auth/login');
        }

        // Authenticate user
        $result = $this->userModel->authenticate($username, $pin);

        if ($result === false) {
            $this->setFlash('login', 'Email o PIN incorrectos', 'error');
            logActivity('failed_login', ['email' => $email]);
            $this->redirect('auth/login');
        }

        if (is_array($result) && isset($result['error'])) {
            if ($result['error'] === 'account_locked') {
                $until = formatDateTime($result['until'], 'H:i');
                $this->setFlash('login', "Cuenta bloqueada hasta las $until por múltiples intentos fallidos", 'error');
            } else {
                $this->setFlash('login', 'Error al iniciar sesión', 'error');
            }
            $this->redirect('auth/login');
        }

        // Check if account is active
        if (!$result['activo']) {
            $this->setFlash('login', 'Tu cuenta está desactivada', 'error');
            $this->redirect('auth/login');
        }

        // Check if email is verified
        if (!$result['email_verificado']) {
            $this->setFlash('login', 'Por favor verifica tu email primero', 'warning');
            $this->redirect('auth/login');
        }

        // Create session
        $this->createUserSession($result);

        // Handle remember me
        if ($remember) {
            $this->setRememberCookie($result['id']);
        }

        // Log successful login
        logActivity('successful_login', ['user_id' => $result['id']]);

        // Redirect based on role
        $this->redirectByRole($result['rol']);
    }

    /**
     * Create user session
     */
    private function createUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_apellidos'] = $user['apellidos'];
        $_SESSION['user_role'] = $user['rol'];
        $_SESSION['logged_in_time'] = time();
        $_SESSION['last_activity'] = time();
    }

    /**
     * Set remember me cookie
     */
    private function setRememberCookie($userId) {
        $token = generateToken();
        $expires = time() + (30 * 24 * 3600); // 30 days

        // Store token in database (you might want to add a remember_tokens table)
        // For now, we'll just set a cookie
        setcookie('remember_token', $token, $expires, '/', '', SECURE_COOKIES, true);
        setcookie('remember_user', $userId, $expires, '/', '', SECURE_COOKIES, true);
    }

    /**
     * Redirect based on role
     */
    private function redirectByRole($role) {
        switch ($role) {
            case 'superadmin':
            case 'admin':
                $this->redirect('admin/dashboard');
                break;
            case 'inspector':
                $this->redirect('inspector/dashboard');
                break;
            case 'empleado':
                $this->redirect('empleado/dashboard');
                break;
            default:
                $this->redirect('');
        }
    }

    /**
     * Logout
     */
    public function logout() {
        // Log logout activity
        if (isLoggedIn()) {
            logActivity('logout', ['user_id' => getUserId()]);
        }

        // Destroy session
        session_destroy();

        // Remove remember cookies
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_user', '', time() - 3600, '/');

        $this->setFlash('login', 'Has cerrado sesión correctamente', 'success');
        $this->redirect('auth/login');
    }

    /**
     * Show registration form
     */
    public function register() {
        // Redirect if already logged in
        if (isLoggedIn()) {
            $this->redirect('');
        }

        $data = [
            'title' => 'Registrar Cuenta',
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('auth/register', $data, 'auth');
    }

    /**
     * Process registration
     */
    public function processRegister() {
        if (!$this->isPost()) {
            $this->redirect('auth/register');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('register', 'Token de seguridad inválido', 'error');
            $this->redirect('auth/register');
        }

        // Get form data
        $username = sanitize($this->getPost('username'));
        $pin = $this->getPost('pin');
        $confirmPin = $this->getPost('confirm_pin');
        $nombre = sanitize($this->getPost('nombre'));
        $apellidos = sanitize($this->getPost('apellidos'));
        $email = sanitize($this->getPost('email'));

        // Validate inputs
        if (empty($username) || empty($pin) || empty($confirmPin) || empty($nombre) || empty($apellidos) || empty($email)) {
            $this->setFlash('register', 'Por favor completa todos los campos requeridos', 'error');
            $this->redirect('auth/register');
        }

        // Validate username format (alphanumeric, underscore, min 3 chars)
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $this->setFlash('register', 'El nombre de usuario debe tener entre 3 y 30 caracteres alfanuméricos', 'error');
            $this->redirect('auth/register');
        }

        // Check if username already exists
        if ($this->userModel->findByUsername($username)) {
            $this->setFlash('register', 'Este nombre de usuario ya está en uso', 'error');
            $this->redirect('auth/register');
        }

        // Validate email
        if (!isValidEmail($email)) {
            $this->setFlash('register', 'El email proporcionado no es válido', 'error');
            $this->redirect('auth/register');
        }

        // Check if email already exists
        if ($this->userModel->findByEmail($email)) {
            $this->setFlash('register', 'Este email ya está registrado', 'error');
            $this->redirect('auth/register');
        }

        // Validate PIN
        if (!isValidPin($pin)) {
            $this->setFlash('register', 'El PIN debe ser de 6 dígitos', 'error');
            $this->redirect('auth/register');
        }

        // Confirm PIN match
        if ($pin !== $confirmPin) {
            $this->setFlash('register', 'Los PINs no coinciden', 'error');
            $this->redirect('auth/register');
        }

        // Generate employee number automatically
        $numeroEmpleado = $this->userModel->generateEmployeeNumber();

        // Create user account
        $userData = [
            'username' => $username,
            'pin' => hashPin($pin),
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'email' => $email,
            'numero_empleado' => $numeroEmpleado,
            'rol' => 'empleado', // Default role
            'activo' => 1,
            'email_verificado' => 0, // Require email verification
            'creado_en' => getCurrentDateTime()
        ];

        $userId = $this->userModel->create($userData);

        if ($userId) {
            // TODO: Send verification email
            $this->setFlash('login', "Cuenta creada exitosamente con número de empleado $numeroEmpleado. Por favor verifica tu email antes de iniciar sesión.", 'success');

            logActivity('user_registered', ['user_id' => $userId, 'username' => $username, 'numero_empleado' => $numeroEmpleado]);
            $this->redirect('auth/login');
        } else {
            $this->setFlash('register', 'Error al crear la cuenta. Por favor intenta de nuevo.', 'error');
            $this->redirect('auth/register');
        }
    }

    /**
     * Show forgot password form
     */
    public function forgotPassword() {
        $data = [
            'title' => 'Recuperar PIN',
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('auth/forgot-password', $data, 'auth');
    }

    /**
     * Process forgot password
     */
    public function processForgotPassword() {
        if (!$this->isPost()) {
            $this->redirect('auth/forgot-password');
        }

        if (!$this->validateCsrfToken()) {
            $this->setFlash('forgot', 'Token de seguridad inválido', 'error');
            $this->redirect('auth/forgot-password');
        }

        $email = sanitize($this->getPost('email'));

        if (!isValidEmail($email)) {
            $this->setFlash('forgot', 'Email inválido', 'error');
            $this->redirect('auth/forgot-password');
        }

        // Generate reset token
        $token = $this->userModel->generateResetToken($email);

        if ($token) {
            // TODO: Send email with reset link
            // For now, show the token (remove in production)
            $resetUrl = url('auth/reset-password/' . $token);
            $this->setFlash('forgot', 'Se ha enviado un enlace de recuperación a tu email', 'success');

            logActivity('password_reset_requested', ['email' => $email]);
        } else {
            // Don't reveal if email exists or not
            $this->setFlash('forgot', 'Si el email existe, recibirás instrucciones para recuperar tu PIN', 'info');
        }

        $this->redirect('auth/forgot-password');
    }

    /**
     * Show reset password form
     */
    public function resetPassword($token = null) {
        if (!$token) {
            $this->setFlash('login', 'Token inválido', 'error');
            $this->redirect('auth/login');
        }

        // Verify token
        $user = $this->userModel->verifyResetToken($token);
        if (!$user) {
            $this->setFlash('login', 'Token inválido o expirado', 'error');
            $this->redirect('auth/login');
        }

        $data = [
            'title' => 'Restablecer PIN',
            'token' => $token,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->viewWithLayout('auth/reset-password', $data, 'auth');
    }

    /**
     * Process reset password
     */
    public function processResetPassword() {
        if (!$this->isPost()) {
            $this->redirect('auth/login');
        }

        if (!$this->validateCsrfToken()) {
            $this->setFlash('login', 'Token de seguridad inválido', 'error');
            $this->redirect('auth/login');
        }

        $token = $this->getPost('token');
        $pin = $this->getPost('pin');
        $confirmPin = $this->getPost('confirm_pin');

        // Validate inputs
        if (!isValidPin($pin)) {
            $this->setFlash('reset', 'El PIN debe ser de 6 dígitos', 'error');
            $this->redirect('auth/reset-password/' . $token);
        }

        if ($pin !== $confirmPin) {
            $this->setFlash('reset', 'Los PINs no coinciden', 'error');
            $this->redirect('auth/reset-password/' . $token);
        }

        // Reset PIN
        if ($this->userModel->resetPin($token, $pin)) {
            $this->setFlash('login', 'Tu PIN ha sido restablecido correctamente', 'success');
            logActivity('password_reset_completed', ['token' => substr($token, 0, 10) . '...']);
            $this->redirect('auth/login');
        } else {
            $this->setFlash('login', 'Error al restablecer el PIN', 'error');
            $this->redirect('auth/login');
        }
    }

    /**
     * Check session timeout (AJAX)
     */
    public function checkSession() {
        if (!$this->isAjax()) {
            $this->redirect('');
        }

        if (!isLoggedIn()) {
            $this->json(['valid' => false]);
        }

        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive >= SESSION_LIFETIME) {
                session_destroy();
                $this->json(['valid' => false, 'reason' => 'timeout']);
            }
        }

        // Update last activity
        $_SESSION['last_activity'] = time();

        $this->json(['valid' => true]);
    }
}