<?php
/**
 * Cerrar Sesión
 */

require_once __DIR__ . '/includes/auth.php';

Auth::logout();

// Redirigir al login con mensaje
header('Location: /login.php?message=logged_out');
exit;