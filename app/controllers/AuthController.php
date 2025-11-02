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

        $email = sanitize($this->getPost('email'));
        $pin = sanitize($this->getPost('pin'));
        $remember = $this->getPost('remember');

        // Validate inputs
        if (empty($email) || empty($pin)) {
            $this->setFlash('login', 'Por favor ingresa tu email y PIN', 'error');
            $this->redirect('auth/login');
        }

        if (!isValidEmail($email)) {
            $this->setFlash('login', 'Email inválido', 'error');
            $this->redirect('auth/login');
        }

        if (!isValidPin($pin)) {
            $this->setFlash('login', 'PIN debe ser de 6 dígitos', 'error');
            $this->redirect('auth/login');
        }

        // Authenticate user
        $result = $this->userModel->authenticate($email, $pin);

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