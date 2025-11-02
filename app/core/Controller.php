<?php
/**
 * Base Controller Class
 */
abstract class Controller {
    protected $data = [];

    /**
     * Load a model
     */
    protected function model($model) {
        require_once '../app/models/' . $model . '.php';
        return new $model();
    }

    /**
     * Load a view with data
     */
    protected function view($view, $data = []) {
        $this->data = $data;

        // Make data available to view
        extract($data);

        // Check if view exists
        $viewFile = '../app/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("View does not exist: " . $view);
        }
    }

    /**
     * Load view with layout
     */
    protected function viewWithLayout($view, $data = [], $layout = 'main') {
        $this->data = $data;

        // Make data available to view
        extract($data);

        // Capture view content
        ob_start();
        $viewFile = '../app/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
            $content = ob_get_clean();
        } else {
            ob_end_clean();
            die("View does not exist: " . $view);
        }

        // Load layout with content
        $layoutFile = '../app/views/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content; // Fallback to content only
        }
    }

    /**
     * Redirect to another URL
     */
    protected function redirect($url) {
        header('Location: ' . BASE_URL . $url);
        exit();
    }

    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Check if request is POST
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Get POST data
     */
    protected function getPost($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * Get GET data
     */
    protected function getGet($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * Check CSRF token
     */
    protected function validateCsrfToken() {
        if (!isset($_POST[CSRF_TOKEN_NAME]) || !isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $_POST[CSRF_TOKEN_NAME]);
    }

    /**
     * Generate CSRF token
     */
    protected function generateCsrfToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Set flash message
     */
    protected function setFlash($key, $message, $type = 'info') {
        $_SESSION['flash'][$key] = [
            'message' => $message,
            'type' => $type
        ];
    }

    /**
     * Get flash message
     */
    protected function getFlash($key) {
        if (isset($_SESSION['flash'][$key])) {
            $flash = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $flash;
        }
        return null;
    }
}