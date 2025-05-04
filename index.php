<?php
/**
 * This is the router, the main entry point of the StockFlow application.
 */

 require __DIR__ . '/vendor/autoload.php';

// Define BASE_URL constant if not already defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Rattrapage/');
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// At the top of index.php after session_start()
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Twig configuration
require_once __DIR__ . '/src/Config/twig.php';

// Parse the URI
if (isset($_GET['uri'])) {
    $uri = trim($_GET['uri'], '/');
} else {
    $uri = '';
}

// Split URI into segments
$segments = explode('/', $uri);
$controller = isset($segments[0]) && !empty($segments[0]) ? $segments[0] : 'home';
$action = isset($segments[1]) && !empty($segments[1]) ? $segments[1] : 'index';
$params = array_slice($segments, 2);

// After parsing URI
error_log("URI: $uri, Controller: $controller, Action: $action");

// Construct controller class name with proper namespace
$controllerName = ucfirst($controller) . 'Controller';
$controllerClass = "App\\Controller\\$controllerName";

// Ensure all controller class files are loaded
$controllerPath = __DIR__ . '/src/Controller/' . $controllerName . '.php';

// Check if the controller file exists
if (!file_exists($controllerPath)) {
    // Fallback to default controller
    $controller = 'home';
    $controllerName = 'HomeController';
    $controllerClass = "App\\Controller\\HomeController";
    $controllerPath = __DIR__ . '/src/Controller/HomeController.php';
}

try {
    // Include the base controller first
    require_once __DIR__ . '/src/Controller/Controller.php';
    
    // Include the specific controller
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
    } else {
        throw new Exception("Controller file not found: $controllerPath");
    }
    
    // Check if controller class exists
    if (class_exists($controllerClass)) {
        $controllerInstance = new $controllerClass();
        
        // Check if the action method exists
        if (method_exists($controllerInstance, $action)) {
            // Call the action with parameters
            call_user_func_array([$controllerInstance, $action], $params);
        } else {
            throw new Exception("Action '$action' not found in controller '$controllerName'");
        }
    } else {
        throw new Exception("Controller class '$controllerClass' not found");
    }
} catch (Exception $e) {
    // Log the error
    error_log($e->getMessage());
    
    // Display error message
    echo "<h1>Erreur</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
