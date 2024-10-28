<?php
// Configuración de la conexión a la base de datos
$host = 'localhost'; // Dirección del servidor de base de datos
$db = 'mi_base_de_datos'; // Nombre de la base de datos
$user = 'usuario'; // Usuario de la base de datos
$pass = 'contraseña'; // Contraseña de la base de datos
$charset = 'utf8mb4'; // Conjunto de caracteres para asegurar el soporte de caracteres especiales

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

