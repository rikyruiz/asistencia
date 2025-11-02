<?php
/**
 * Home Controller
 */
class HomeController extends Controller {

    public function index() {
        // Redirect to login if not logged in
        if (!isLoggedIn()) {
            $this->redirect('auth/login');
        }

        // Redirect based on role
        $role = $_SESSION['user_role'] ?? '';

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
                $this->redirect('auth/login');
        }
    }

    /**
     * Test page to verify MVC is working
     */
    public function test() {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Test - Sistema de Asistencia</title>
        </head>
        <body>
            <h1>Sistema MVC Funcionando!</h1>
            <p>La estructura MVC está configurada correctamente.</p>
            <ul>
                <li>PHP Version: " . phpversion() . "</li>
                <li>Timezone: " . date_default_timezone_get() . "</li>
                <li>Current Time: " . date('Y-m-d H:i:s') . "</li>
            </ul>
        </body>
        </html>";
    }
}