<?php
// Configuración de la conexión a la base de datos usando getenv para obtener los valores de las variables de entorno
$host = $_ENV['DB_HOST']; // Dirección del servidor de base de datos
$db = $_ENV['DB_NAME']; // Nombre de la base de datos
$user = $_ENV['DB_USER']; // Usuario de la base de datos
$pass = $_ENV['DB_PASSWORD']; // Contraseña de la base de datos
$charset = 'utf8mb4'; // Conjunto de caracteres

// Definición del DSN (Data Source Name) para la conexión
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opciones de PDO para controlar el comportamiento de la conexión
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Habilita las excepciones en caso de error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Devuelve los resultados como un array asociativo
    PDO::ATTR_EMULATE_PREPARES   => false, // Desactiva la emulación de consultas preparadas
];

try {
    // Intenta crear una nueva conexión PDO con los datos proporcionados
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Lanza una excepción si falla la conexión
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

