<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
// Requiere los archivos de conexión a la base de datos y controladores de autenticación
require 'db.php';
require 'authController.php';
echo $_ENV["DB_USER"] ?? 'Sin usuario';

// Configura la respuesta como JSON
header('Content-Type: application/json');

// Obtener la ruta solicitada y el método HTTP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Rutas y controladores asociados
if ($uri == '/auth/register' && $method == 'POST') {
    // Llama al método de registro cuando la ruta es /auth/register y el método es POST
    register();
} elseif ($uri == '/auth/login' && $method == 'POST') {
    // Llama al método de inicio de sesión cuando la ruta es /auth/login y el método es POST
    login();
} elseif ($uri == '/me' && $method == 'GET') {
    // Llama al método de obtener datos del usuario autenticado cuando la ruta es /me y el método es GET
    obtenerDatosProtegidos();
} else {
    // Devuelve un código de error 404 si la ruta no coincide con las anteriores
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}

