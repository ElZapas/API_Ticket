<?php

use utils\ApiResource;
use utils\HttpResponses;
use utils\Request;

require 'vendor/autoload.php';

spl_autoload_register(function ($class) {
    $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class);

    $file =  $_SERVER["DOCUMENT_ROOT"] . $class_path . '.php';
    // if (file_exists($file)) require_once $file;
    // echo json_encode($file);
    require_once $file;
});

function debug($message = "")
{
    ["file" => $f, "line" => $l] = debug_backtrace()[0];
    echo "\nstoped in: $f \nline: $l \nmessage: " . print_r($message);
    // phpinfo();
    exit;
}

// Habilitar la visualización de errores para facilitar la depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cargar variables de entorno solo si existe el archivo .env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    // if (isset($_ENV["TEST"]))
    //     $_ENV["TEST"] = filter_var($_ENV["TEST"], FILTER_VALIDATE_BOOLEAN);
}

// Configura la respuesta como JSON
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');

if (Request::$METHOD == "OPTIONS") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}
// Rutas y controladores asociados
// HttpResponses::OK(getallheaders());

require_once $_SERVER["DOCUMENT_ROOT"] . "/Router.php";

$pathController = @ROUTERS[Request::$URI_ARR[0]] ?? false;

if (!$pathController)
    // HttpResponses::Bad_Request($_SERVER);
    HttpResponses::Not_Found("Recurso no encontrado");

$controller = require_once $pathController;
if ($controller instanceof ApiResource) {
    $controller->process();
}

// if ($uri === '/users' && $method === 'GET') {
//     // Obtener la lista de técnicos
//     obtenerTecnicos();
//     /* } elseif ($uri === '/tickets/estado' && $method === 'GET') {
//     $estado = $_GET['estado'] ?? null;
//     if (!$estado) {
//         http_response_code(400);
//         echo json_encode(['error' => 'Debe especificar el parámetro "estado"']);
//     } else {
//         filtrarTicketsPorEstado($estado);
//     }
// } elseif ($uri === '/tickets/prioridad' && $method === 'GET') {
//     $prioridad = $_GET['prioridad'] ?? null;
//     if (!$prioridad) {
//         http_response_code(400);
//         echo json_encode(['error' => 'Debe especificar el parámetro "prioridad"']);
//     } else {
//         filtrarTicketsPorPrioridad($prioridad);
//     } */
// }