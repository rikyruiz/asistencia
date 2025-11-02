<?php
/**
 * Front Controller
 * This is the entry point for all requests
 */

// Load configuration
require_once '../app/config/config.php';

// Load core classes
require_once '../app/core/App.php';
require_once '../app/core/Controller.php';
require_once '../app/core/Database.php';
require_once '../app/core/Model.php';

// Initialize the application
$app = new App();