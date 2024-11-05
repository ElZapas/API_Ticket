<?php

require 'db.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Obtiene la clave secreta desde las variables de entorno de Railway
$key = $_ENV['JWT_SECRET_KEY'];

// Función para verificar el token JWT y obtener los datos del usuario
function verificarTokenTicket($jwt)
{
    global $key;
    try {
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
        return (array) $decoded->user;
    } catch (Exception $e) {
        return null;
    }
}

// Función para obtener los tickets del usuario autenticado
function obtenerTickets()
{
    global $pdo;

    // Obtener el token desde los headers
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Token no proporcionado']);
        return;
    }

    // Extraer el token del header 'Authorization'
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $userData = verificarTokenTicket($token);

    if (!$userData) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        return;
    }

    // Generar la consulta SQL para obtener los tickets
    $query = "SELECT * FROM tickets";
    if ($userData['puesto'] === 'tecnico') {
        $query .= " WHERE tecnico_asignado_id = ?";
    }
    $query .= " LIMIT 20";

    $stmt = $pdo->prepare($query);
    if ($userData['puesto'] === 'tecnico') {
        $stmt->execute([$userData['idUsuario']]);
    } else {
        $stmt->execute();
    }

    $tickets = $stmt->fetchAll();

    // Verificar si hay tickets y devolver la respuesta
    if (!$tickets) {
        echo json_encode(['mensaje' => 'No se encontraron tickets registrados']);
    } else {
        echo json_encode($tickets);
    }
}

function agregarTicket()
{
    global $pdo;

    // Leer y decodificar JSON del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar datos requeridos
    if (!isset($data['id_cliente'], $data['id_usuario'], $data['descripcion'], $data['prioridad'], $data['canalRecepcion'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Todos los campos son obligatorios']);
        return;
    }

    // Preparar y ejecutar la consulta de inserción
    $stmt = $pdo->prepare('INSERT INTO Tickets (id_cliente, id_usuario, descripcion, estado, prioridad, canal_recepcion, fecha_resolucion) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $estado = 'Abierto';
    $fechaResolucion = ($estado === 'Resuelto') ? date("Y-m-d H:i:s") : null;

    $stmt->execute([$data['id_cliente'], $data['id_usuario'], $data['descripcion'], $estado, $data['prioridad'], $data['canalRecepcion'], $fechaResolucion]);

    // Verificar si la inserción fue exitosa
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => 'Ticket creado exitosamente', 'id_ticket' => $pdo->lastInsertId()]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el ticket']);
    }
}

function actualizarTicket($id)
{
    global $pdo;

    // Leer y decodificar JSON del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar datos requeridos
    if (!isset($data['descripcion'], $data['estado'], $data['prioridad'], $data['canalRecepcion'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Todos los campos son obligatorios']);
        return;
    }

    // Preparar y ejecutar la consulta de actualización
    $stmt = $pdo->prepare('UPDATE Tickets SET descripcion = ?, estado = ?, prioridad = ?, canal_recepcion = ?, fecha_resolucion = ? WHERE id_ticket = ?');
    $fechaResolucion = ($data['estado'] === 'Resuelto') ? date("Y-m-d H:i:s") : null;
    $stmt->execute([$data['descripcion'], $data['estado'], $data['prioridad'], $data['canalRecepcion'], $fechaResolucion, $id]);

    // Verificar si la actualización fue exitosa
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => 'Ticket actualizado exitosamente']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket no encontrado o no se realizaron cambios']);
    }
}

// Función para eliminar un ticket
function eliminarTicket($id)
{
    global $pdo;

    // Preparar y ejecutar la consulta de eliminación
    $stmt = $pdo->prepare('DELETE FROM Tickets WHERE id_ticket = ?');
    $stmt->execute([$id]);

    // Verificar si la eliminación fue exitosa
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => 'Ticket eliminado exitosamente']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket no encontrado']);
    }
}