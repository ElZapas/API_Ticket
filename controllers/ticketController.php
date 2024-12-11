<?php

use enums\PuestoUsuario;
use enums\TicketEstados;
use src\Database\Database;
use src\JWTHelper;
use utils\ApiResource;
use utils\HttpCode;
use utils\HttpResponses;
use utils\Request;

return new ApiResource(
    resources: [
        new ApiResource(
            "GET",
            null,
            fn() => obtenerTickets(),
        ),
        new ApiResource(
            "POST",
            null,
            fn() => agregarTicket(),
        ),
        new ApiResource(
            null,
            fn() => isset(Request::$URI_ARR[1]),
            [
                new ApiResource(
                    "PUT",
                    null,
                    fn() => actualizarTicket(),
                ),
                new ApiResource(
                    "DELETE",
                    null,
                    fn() => eliminarTicket(),
                ),
            ]
        ),
    ]
);

// Función para obtener los tickets del usuario autenticado
function obtenerTickets()
{
    $get = Request::$GET;
    $pdo = Database::connection();

    $userData = JWTHelper::getUser();


    $estado = $get['estado'] ?? null;
    $prioridad = $get['prioridad'] ?? null;
    $tecnico = $get['tecnico'] ?? null;

    $query =
        "SELECT 
    t.id_ticket AS idTicket,
    c.nombre_cliente AS nombreCliente,
    u.nombre_usuario AS nombreUsuario,
    t.descripcion,
    t.fecha_recepcion AS fechaRecepcion,
    t.estado,
    t.prioridad,
    t.canal_recepcion AS canalRecepcion,
    t.fecha_resolucion AS fechaResolucion
        FROM tickets t
        JOIN usuarios u ON t.id_usuario = u.id_usuario
        JOIN clientes c ON t.id_cliente = c.id_cliente
    ";

    $conditions = [];
    $values = [];

    // Filtro por técnico
    if (!empty($tecnico)) {
        $conditions[] = "u.nombre_usuario = ?";
        $values[] = $tecnico;
    }

    // Filtro por estado
    if (!empty($estado)) {
        $conditions[] = "t.estado = ?";
        $values[] = $estado;
    }

    // Filtro por prioridad
    if (!empty($prioridad)) {
        $conditions[] = "t.prioridad = ?";
        $values[] = $prioridad;
    }

    // Filtro por usuario técnico (si aplica)
    if ($userData->puesto === PuestoUsuario::TECNICO->value) {
        $conditions[] = "t.id_usuario = ?";
        $values[] = $userData['id_usuario'];
    }

    // Construcción de la consulta con condiciones
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " LIMIT 20";
    // HttpResponses::OK($query);

    $stmt = $pdo->prepare($query);

    $stmt->execute($values);

    $tickets = $stmt->fetchAll();
    HttpResponses::OK(
        $tickets ? $tickets : ['mensaje' =>  'No se encontraron tickets registrados']
    );
}
function agregarTicket()
{
    $pdo = Database::connection();

    $userData = JWTHelper::getUser();


    // Verificar que el usuario sea "Responsable"
    if ($userData->puesto !== PuestoUsuario::RESPONSABLE->value) {
        HttpResponses::Forbidden('Permiso denegado. Solo los usuarios con puesto "Responsable" pueden agregar tickets.');
    }

    $data = Request::$POST;

    if (!isset(
        $data['nombreCliente'],
        $data['nombreUsuario'],
        $data['descripcion'],
        $data['prioridad'],
        $data['canalRecepcion']
    ))
        HttpResponses::Bad_Request('Todos los campos son obligatorios');

    // Obtener el id_cliente a partir del nombreCliente
    $stmt = $pdo->prepare(
        "SELECT id_cliente
            FROM clientes 
                WHERE nombre_cliente = ?"
    );
    $stmt->execute([$data['nombreCliente']]);
    $idCliente = $stmt->fetchColumn();

    if (!$idCliente)
        HttpResponses::Bad_Request('Cliente no encontrado');

    // Obtener el id_usuario a partir del nombreUsuario
    $stmt = $pdo->prepare(
        "SELECT id_usuario
            FROM usuarios
                WHERE nombre_usuario = ?"
    );
    $stmt->execute([$data['nombreUsuario']]);
    $idUsuarioAsignado = $stmt->fetchColumn();

    if (!$idUsuarioAsignado)
        HttpResponses::Bad_Request('Usuario técnico no encontrado');

    // Registrar el ticket
    $stmt = $pdo->prepare(
        'INSERT INTO tickets (
        id_cliente, 
        id_usuario, 
        descripcion, 
        estado, 
        prioridad, 
        canal_recepcion, 
        fecha_resolucion
        ) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $estado = TicketEstados::ABIERTO->value;
    $fechaResolucion = ($estado === TicketEstados::RESUELTO->value) ? date("Y-m-d H:i:s") : null;

    $stmt->execute([$idCliente, $idUsuarioAsignado, $data['descripcion'], $estado, $data['prioridad'], $data['canalRecepcion'], $fechaResolucion]);

    if ($stmt->rowCount() > 0) {
        $idTicket = $pdo->lastInsertId();

        // Obtener el ticket recién creado con nombres de usuario y cliente
        $stmt = $pdo->prepare(
            "SELECT 
                t.id_ticket AS idTicket,
                c.nombre_cliente AS nombreCliente,
                u.nombre_usuario AS nombreUsuario,
                t.descripcion,
                t.fecha_recepcion AS fechaRecepcion,
                t.estado,
                t.prioridad,
                t.canal_recepcion AS canalRecepcion,
                t.fecha_resolucion AS fechaResolucion
            FROM tickets t
                JOIN usuarios u ON t.id_usuario = u.id_usuario
                JOIN clientes c ON t.id_cliente = c.id_cliente
                    WHERE t.id_ticket = ?
        "
        );
        $stmt->execute([$idTicket]);
        $ticket = $stmt->fetch();
        HttpResponses::send(
            code: HttpCode::Created,
            data: [
                'success' => 'Ticket creado exitosamente',
                'ticket' => $ticket
            ]
        );
    } else {
        HttpResponses::Internal_Error('Error al crear el ticket');
    }
}

function actualizarTicket()
{
    $idTicket = Request::$URI_ARR[1];
    $pdo = Database::connection();
    $userData = JWTHelper::getUser();

    // Verificar que el usuario sea "Responsable"
    if ($userData->puesto !== PuestoUsuario::RESPONSABLE->value)
        HttpResponses::Forbidden(
            ['error' => 'Permiso denegado. Solo los usuarios con puesto "responsable" pueden actualizar tickets.']
        );

    $data = Request::$POST;

    if (!isset(
        $data['nombreCliente'],
        $data['nombreUsuario'],
        $data['descripcion'],
        $data['prioridad'],
        $data['canalRecepcion']
    ))
        HttpResponses::Bad_Request(['error' => 'Todos los campos son obligatorios']);


    // Obtener el id_cliente a partir del nombreCliente
    $stmt = $pdo->prepare(
        "SELECT id_cliente 
            FROM clientes 
                WHERE nombre_cliente = ?"
    );
    $stmt->execute([$data['nombreCliente']]);
    $idCliente = $stmt->fetchColumn();

    if (!$idCliente)
        HttpResponses::Bad_Request(['error' => 'Cliente no encontrado']);


    // Obtener el id_usuario a partir del nombreUsuario
    $stmt = $pdo->prepare(
        "SELECT id_usuario 
            FROM usuarios 
                WHERE nombre_usuario = ?"
    );
    $stmt->execute([$data['nombreUsuario']]);
    $idUsuarioAsignado = $stmt->fetchColumn();

    if (!$idUsuarioAsignado) {
        http_response_code(400);
        echo json_encode(['error' => 'Usuario técnico no encontrado']);
        return;
    }

    $stmt = $pdo->prepare(
        'UPDATE 
        tickets 
            SET 
            id_cliente = ?, 
            id_usuario = ?, 
            descripcion = ?, 
            estado = ?, 
            prioridad = ?, 
            canal_recepcion = ?, 
            fecha_resolucion = ? 
                WHERE id_ticket = ?'
    );
    $fechaResolucion = ($data['estado'] === TicketEstados::RESUELTO->value) ? date("Y-m-d H:i:s") : null;
    $estado = $data['estado'] ?? 'Abierto';
    $stmt->execute(
        [
            $idCliente,
            $idUsuarioAsignado,
            $data['descripcion'],
            $estado,
            $data['prioridad'],
            $data['canalRecepcion'],
            $fechaResolucion,
            $idTicket
        ]
    );

    if ($stmt->rowCount() > 0) {
        HttpResponses::OK(['success' => 'Ticket actualizado exitosamente']);
    } else {
        HttpResponses::Forbidden(['error' => 'Ticket no encontrado o no se realizaron cambios']);
    }
}

function eliminarTicket()
{

    $pdo = Database::connection();

    $userData = JWTHelper::getUser();
    $idTicket = Request::$URI_ARR[1];
    // Verificar que el usuario sea "Responsable"
    if ($userData->puesto !== PuestoUsuario::RESPONSABLE->value)
        HttpResponses::Forbidden(['error' => 'Permiso denegado. Solo los usuarios con puesto "Responsable" pueden eliminar tickets.']);


    $stmt = $pdo->prepare(
        'DELETE FROM tickets 
            WHERE id_ticket = ?'
    );
    $stmt->execute([$idTicket]);

    if ($stmt->rowCount() > 0) {
        HttpResponses::OK(['success' => 'Ticket eliminado exitosamente']);
    } else {
        HttpResponses::Not_Found(['error' => 'Ticket no encontrado']);
    }
}


// Filtrar tickets por estado (solo acepta "Abierto" o "Cerrado")
/* function filtrarTicketsPorEstado($estado)
{
    global $pdo;

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Token no proporcionado']);
        return;
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $userData = verificarTokenUser($token);

    if (!$userData) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        return;
    }

    // Validar que el estado es permitido
    if (!in_array($estado, [TicketEstados::ABIERTO->value, TicketEstados::CERRADO->value])) {
        http_response_code(400);
        echo json_encode(['error' => 'Estado no permitido. Solo se aceptan "Abierto" o "Cerrado".']);
        return;
    }

    $query = "
        SELECT 
            t.id_ticket AS idTicket,
            c.nombre_cliente AS nombreCliente,
            u.nombre_usuario AS nombreUsuario,
            t.descripcion,
            t.fecha_recepcion AS fechaRecepcion,
            t.estado,
            t.prioridad,
            t.canal_recepcion AS canalRecepcion,
            t.fecha_resolucion AS fechaResolucion
        FROM Tickets t
        JOIN Usuarios u ON t.id_usuario = u.id_usuario
        JOIN Clientes c ON t.id_cliente = c.id_cliente
        WHERE t.estado = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$estado]);

    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$tickets) {
        echo json_encode(['mensaje' => 'No se encontraron tickets con el estado especificado']);
    } else {
        echo json_encode($tickets);
    }
}

// Filtrar tickets por prioridad (acepta todas las prioridades)
function filtrarTicketsPorPrioridad($prioridad)
{
    global $pdo;

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Token no proporcionado']);
        return;
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $userData = verificarTokenUser($token);

    if (!$userData) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        return;
    }

    // Validar que la prioridad es válida
    $prioridadesValidas = array_column(TicketPrioridad::cases(), 'value');
    if (!in_array($prioridad, $prioridadesValidas)) {
        http_response_code(400);
        echo json_encode(['error' => 'Prioridad no válida']);
        return;
    }

    $query = "
        SELECT 
            t.id_ticket AS idTicket,
            c.nombre_cliente AS nombreCliente,
            u.nombre_usuario AS nombreUsuario,
            t.descripcion,
            t.fecha_recepcion AS fechaRecepcion,
            t.estado,
            t.prioridad,
            t.canal_recepcion AS canalRecepcion,
            t.fecha_resolucion AS fechaResolucion
        FROM Tickets t
        JOIN Usuarios u ON t.id_usuario = u.id_usuario
        JOIN Clientes c ON t.id_cliente = c.id_cliente
        WHERE t.prioridad = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$prioridad]);

    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$tickets) {
        echo json_encode(['mensaje' => 'No se encontraron tickets con la prioridad especificada']);
    } else {
        echo json_encode($tickets);
    }
} */