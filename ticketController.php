<?php

require 'db.php';
require_once 'enums/ticketCanalRecepcion.php';
require_once 'enums/ticketEstados.php';
require_once 'enums/ticketPrioridad.php';
require_once './utils/verificarTokenUser.php';

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
    $userData = verificarTokenUser($token);

    if (!$userData) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        return;
    }

    // Generar la consulta SQL para obtener los tickets
    $query = "SELECT id_ticket AS idTicket, id_cliente AS idCliente, id_usuario AS idUsuario, descripcion, fecha_recepcion AS fechaRecepcion, estado, prioridad, canal_recepcion AS canalRecepcion, fecha_resolucion AS fechaResolucion FROM tickets";
    if ($userData['puesto'] === 'tecnico') {
        $query .= " WHERE id_usuario = ?";
    }
    $query .= " LIMIT 20";

    $stmt = $pdo->prepare($query);
    if ($userData['puesto'] === 'tecnico') {
        $stmt->execute([$userData['idUsuario']]);
    } else {
        $stmt->execute();
    }

    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        // Si no se proporciona el token, devolvemos un código 401 (Unauthorized).
        http_response_code(401);
        echo json_encode(['error' => 'Token no proporcionado']);
        return;
    }

    // Extraemos el token del header 'Authorization', eliminando el prefijo 'Bearer '.
    $token = str_replace('Bearer ', '', $headers['Authorization']);

    $userData = verificarTokenUser($token);
    if (!$userData) {
        // Si el token es inválido o ha expirado, devolvemos un código 401.
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        return;
    }

    // Leer y decodificar JSON del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar datos requeridos
    if (!isset($data['idCliente'], $userData['idUsuario'], $data['descripcion'], $data['prioridad'], $data['canalRecepcion'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Todos los campos son obligatorios']);
        return;
    }

    // Preparar y ejecutar la consulta de inserción
    $stmt = $pdo->prepare('INSERT INTO Tickets (id_cliente, id_usuario, descripcion, estado, prioridad, canal_recepcion, fecha_resolucion) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $estado = TicketEstados::ABIERTO->value;
    $fechaResolucion = ($estado === TicketEstados::RESUELTO->value) ? date("Y-m-d H:i:s") : null;

    $stmt->execute([$data['idCliente'], $userData['idUsuario'], $data['descripcion'], $estado, $data['prioridad'], $data['canalRecepcion'], $fechaResolucion]);

    // Verificar si la inserción fue exitosa
    if ($stmt->rowCount() > 0) {
        $idTicket = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT id_ticket as idTicket, id_cliente as idCliente, id_usuario as idUsuario, descripcion, fecha_recepcion as fechaRecepcion, estado, prioridad, canal_recepcion as canalRecepcion, fecha_resolucion as fechaResolucion FROM Tickets WHERE id_ticket = ?");
        $stmt->execute([$idTicket]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => 'Ticket creado exitosamente', 'ticket' => $ticket]);
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