<?php
// Main API Router for Matin Food v1
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle CORS Pre-flight Options Request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../db.php";

// Parse endpoint (from .htaccess)
$request = isset($_GET['request']) ? explode('/', rtrim($_GET['request'], '/')) : [];
$resource = isset($request[0]) ? $request[0] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Route logic
switch ($resource) {
    case 'products':
        require_once "controllers/ProductController.php";
        $controller = new ProductController($conn, $pdo);
        $controller->handleRequest($method, $request);
        break;

    case 'orders':
        require_once "controllers/OrderController.php";
        $controller = new OrderController($conn, $pdo);
        $controller->handleRequest($method, $request);
        break;

    case 'ai':
        require_once "controllers/AiController.php";
        $controller = new AiController($conn, $pdo);
        $controller->handleRequest($method, $request);
        break;

    case 'health':
        echo json_encode([
            "status" => "online", 
            "version" => "1.0.0", 
            "api" => "Matin Food REST API",
            "timestamp" => date('Y-m-d H:i:s')
        ]);
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found: $resource"]);
        break;
}
?>
