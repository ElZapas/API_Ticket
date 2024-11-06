<?php
// Habilitar la visualización de errores para facilitar la depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

// Cargar variables de entorno solo si existe el archivo .env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Configura la respuesta como JSON
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}

// Requiere los archivos de conexión a la base de datos y controladores de autenticación
require 'db.php';
require 'authController.php';
require 'ticketController.php';

// Obtener la ruta solicitada y el método HTTP
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Rutas y controladores asociados
if ($uri === '/auth/register' && $method === 'POST') {
    // Llama al método de registro cuando la ruta es /auth/register y el método es POST
    register();
} elseif ($uri === '/auth/login' && $method === 'POST') {
    // Llama al método de inicio de sesión cuando la ruta es /auth/login y el método es POST
    login();
} elseif ($uri === '/me' && $method === 'GET') {
    // Llama al método de obtener datos del usuario autenticado cuando la ruta es /me y el método es GET
    obtenerDatosProtegidos();
} elseif ($uri === '/tickets' && $method === 'GET') {
    // Obtener todos los tickets
    obtenerTickets();
} elseif ($uri === '/tickets' && $method === 'POST') {
    // Agregar un nuevo ticket
    agregarTicket();
} elseif ($method === 'PUT' && preg_match('/\/tickets\/(\d+)/', $uri, $matches)) {
    // Actualizar ticket por ID
    actualizarTicket($matches[1]);
} elseif ($method === 'DELETE' && preg_match('/\/tickets\/(\d+)/', $uri, $matches)) {
    // Eliminar ticket por ID
    eliminarTicket($matches[1]);
} else {
    // Devuelve un código de error 404 si la ruta no coincide con las anteriores
    http_response_code(404);
    echo json_encode([
        'error' => 'Not Found'
    ]);
}