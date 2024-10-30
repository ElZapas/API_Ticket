<?php

// Exportamos el archivo de conexión a la base de datos, permitiéndonos interactuar con las tablas de usuarios.
require 'db.php';

// Importamos las librerías necesarias para trabajar con JSON Web Tokens (JWT) en PHP.
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Definimos una clave secreta para firmar y verificar tokens JWT.
// Esta clave es crucial para la seguridad del sistema y debe mantenerse segura.
// En producción, se recomienda almacenarla en una variable de entorno en lugar de en el código.
// Obtiene la clave secreta desde las variables de entorno de Railway
$key = $_ENV['JWT_SECRET_KEY'];

// Función para registrar un nuevo usuario en la base de datos.
function register() {
    global $pdo; // Accedemos al objeto PDO para interactuar con la base de datos.

    // Leemos el cuerpo de la solicitud, que contiene los datos enviados por el cliente en formato JSON.
    $data = json_decode(file_get_contents('php://input'), true);

    // Verificamos si los datos necesarios ('name', 'email' y 'password') están presentes en la solicitud.
    if (!isset($data['name'], $data['email'], $data['password'])) {
        // Si falta alguno de los datos obligatorios, enviamos un código de error 400 (Bad Request).
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos obligatorios']);
        return;
    }

    // Verificamos si ya existe un usuario registrado con el mismo correo electrónico en la base de datos.
    $stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE email = ?');
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        // Si el correo ya está registrado, enviamos un código de error 409 (Conflict).
        http_response_code(409);
        echo json_encode(['error' => 'El email ya está registrado']);
        return;
    }

    // Si el email es único, encriptamos la contraseña del usuario usando `password_hash` para mayor seguridad.
    // Esto evita almacenar contraseñas en texto plano en la base de datos.
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insertamos el nuevo usuario en la base de datos con su nombre, email, puesto, fecha de creación y contraseña encriptada.
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre_usuario, email, puesto, fecha_creacion, password) VALUES (?, ?, "empleado", NOW(), ?)');
    $stmt->execute([$data['name'], $data['email'], $hashedPassword]);

    // Obtenemos el ID del usuario recién creado, que nos devuelve PDO después de la inserción.
    $userId = $pdo->lastInsertId();

    // Recuperamos los datos del usuario recién creado para devolverlos en la respuesta, sin incluir la contraseña.
    $stmt = $pdo->prepare('SELECT id_usuario AS id, nombre_usuario AS name, email, fecha_creacion AS createdAt FROM usuarios WHERE id_usuario = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Enviamos un código 201 (Created) y devolvemos los datos del usuario en formato JSON como confirmación del registro exitoso.
    http_response_code(201);
    echo json_encode($user);
}

// Función para realizar el login y generar un token de sesión JWT.
function login() {
    error_log("Login function called");
    
    global $pdo, $key; // Accedemos al objeto PDO y a la clave secreta para JWT.

    // Decodificamos el cuerpo de la solicitud para obtener los datos enviados por el cliente.
    $data = json_decode(file_get_contents('php://input'), true);

    // Verificamos que los campos de 'email' y 'password' estén presentes en los datos.
    if (!isset($data['email'], $data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos obligatorios']);
        return;
    }

    // Buscamos al usuario en la base de datos usando el email proporcionado.
    $stmt = $pdo->prepare('SELECT id_usuario, nombre_usuario, email, password FROM usuarios WHERE email = ?');
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();

    // Verificamos si el usuario existe y si la contraseña proporcionada coincide con la almacenada.
    if (!$user || !password_verify($data['password'], $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales incorrectas']);
        return;
    }

    // Configuramos el tiempo de creación y expiración del token.
    $issuedAt = time(); // Hora de creación del token en tiempo Unix.
    $expirationTime = $data['rememberMe'] ? $issuedAt + (60 * 60 * 24 * 14) : $issuedAt + (60 * 60); // 2 semanas o 1 hora.

    // Creamos el payload del token, que contiene la información del usuario y las configuraciones de tiempo.
    $payload = [
        'iat' => $issuedAt,                // 'iat' es la hora de creación (issued at).
        'exp' => $expirationTime,          // 'exp' es la hora de expiración (expiration time).
        'data' => [                        // Datos del usuario que queremos incluir en el token.
            'id' => $user['id_usuario'],   // ID único del usuario.
            'name' => $user['nombre_usuario'], // Nombre del usuario.
            'email' => $user['email']      // Correo electrónico del usuario.
        ]
    ];

    // Generamos el token JWT usando el payload y la clave secreta con el algoritmo HS256.
    $jwt = JWT::encode($payload, $key, 'HS256');

    // Devolvemos el token y la información básica del usuario como respuesta JSON.
    echo json_encode([
        'token' => $jwt,
        'user' => [
            'id' => $user['id_usuario'],
            'name' => $user['nombre_usuario'],
            'email' => $user['email']
        ]
    ]);
}

// Función para verificar la validez del token JWT recibido en el header de la solicitud.
function verificarToken($jwt) {
    global $key; // Incluimos la clave secreta para JWT.

    try {
        // Decodificamos el token usando la clave y el algoritmo HS256 para obtener los datos originales.
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
        return (array) $decoded->data; // Devolvemos los datos del usuario si el token es válido.
    } catch (Exception $e) {
        // Si el token es inválido o ha expirado, devolvemos null para indicar un error de autenticación.
        return null;
    }
}

// Función para obtener datos de usuario protegido por autenticación JWT
function obtenerDatosProtegidos() {
    global $pdo; // Accedemos al objeto PDO para interactuar con la base de datos.

    // Obtenemos todos los headers de la solicitud, donde esperamos encontrar el token de autenticación.
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        // Si no se proporciona el token, devolvemos un código 401 (Unauthorized).
        http_response_code(401);
        echo json_encode(['error' => 'Token no proporcionado']);
        return;
    }

    // Extraemos el token del header 'Authorization', eliminando el prefijo 'Bearer '.
    $token = str_replace('Bearer ', '', $headers['Authorization']);

    // Verificamos el token decodificándolo y obteniendo los datos del usuario.
    $userData = verificarToken($token);
    if (!$userData) {
        // Si el token es inválido o ha expirado, devolvemos un código 401.
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        return;
    }

    // Realizamos una consulta SQL para obtener los datos del usuario en la base de datos usando su ID.
    $stmt = $pdo->prepare('SELECT id_usuario AS id, nombre_usuario AS name, email, fecha_creacion AS createdAt FROM usuarios WHERE id_usuario = ?');
    $stmt->execute([$userData['id']]);
    $user = $stmt->fetch();

    // Si no encontramos al usuario asociado, enviamos un error 404 (Not Found).
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado']);
        return;
    }

    // Si todo está bien, devolvemos los datos del usuario en formato JSON.
    echo json_encode($user);
}
