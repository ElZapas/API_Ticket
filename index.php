<?php

use utils\ApiResource;
use utils\HttpResponses;
use utils\Request;

require 'vendor/autoload.php';

spl_autoload_register(function ($class) {
    $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class);

    $file = __DIR__ . DIRECTORY_SEPARATOR . $class_path . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        HttpResponses::Internal_Error("Archivo no encontrado: $file");
    }
});

function debug($message = "")
{
    ["file" => $f, "line" => $l] = debug_backtrace()[0];
    echo "\nstoped in: $f \nline: $l \nmessage: " . print_r($message);
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
require_once __DIR__ . "/Router.php";

$pathController = @ROUTERS[Request::$URI_ARR[0]] ?? false;

if (!$pathController) {
    HttpResponses::Not_Found("Recurso no encontrado");
}

$controller = require_once $pathController;
if ($controller instanceof ApiResource) {
    $controller->process();
} else {
    HttpResponses::Internal_Error("Error al procesar la solicitud");
}