<?php
require 'vendor/autoload.php';

// Cargar variables de entorno solo si existe el archivo .env
if (file_exists(__DIR__ .'/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Configura la respuesta como JSON
header('Content-Type: application/json');

// Requiere los archivos de conexión a la base de datos y controladores de autenticación
require 'db.php';
require 'authController.php';

// Obtener la ruta solicitada y el método HTTP
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); //verificar que es lo que contiene eso
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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
    echo json_encode(['error' => 'Not Found',
    'JWT_SECRET_KEY' => getenv('JWT_SECRET_KEY'),
        'DB_USER' => getenv('DB_USER'),
        'DB_PASSWORD' => getenv('DB_PASSWORD'),
        'DB_HOST' => getenv('DB_HOST'),
        'DB_NAME' => getenv('DB_NAME'),
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
]); // hacer un console log con las variables de entorno
}
