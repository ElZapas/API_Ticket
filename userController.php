<?php

require 'db.php';
require_once './utils/verificarTokenUser.php';

function obtenerTecnicos()
{
    global $pdo;

    // Obtener el token desde los headers
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Token no proporcionado']);
        return;
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $userData = verificarTokenUser($token);

    // Verificar que el token sea válido
    if (!$userData) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        return;
    }

    // Verificar si el usuario tiene el puesto "responsable"
    if ($userData['puesto'] !== PuestoUsuario::RESPONSABLE->value) {
        http_response_code(403);
        echo json_encode(['error' => 'Permiso denegado. Solo los usuarios con el puesto "responsable" pueden acceder a esta información.']);
        return;
    }

    // Consulta para obtener los usuarios técnicos
    $stmt = $pdo->prepare("SELECT id_usuario AS idUsuario, nombre_usuario AS nombreUsuario, email, fecha_creacion AS fechaCreacion FROM Usuarios WHERE puesto = ?");
    $stmt->execute(['tecnico']);

    $tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Respuesta
    if (!$tecnicos) {
        echo json_encode(['mensaje' => 'No se encontraron técnicos registrados']);
    } else {
        echo json_encode($tecnicos);
    }
}

function agregarTecnico()
{
    global $pdo;

    // Obtener el token desde los headers
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Token no proporcionado']);
        return;
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $userData = verificarTokenUser($token);

    // Verificar que el token sea válido
    if (!$userData) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        return;
    }

    // Verificar si el usuario tiene el puesto "responsable"
    if ($userData['puesto'] !== PuestoUsuario::RESPONSABLE->value) {
        http_response_code(403);
        echo json_encode(['error' => 'Permiso denegado. Solo los usuarios con el puesto "responsable" pueden agregar técnicos.']);
        return;
    }

    // Obtener los datos del nuevo técnico desde el cuerpo de la solicitud
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validar los datos requeridos
    if (!isset($inputData['nombreUsuario'], $inputData['email'], $inputData['puesto'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan datos requeridos (nombre, email, puesto).']);
        return;
    }

    // Preparar la consulta para insertar el nuevo técnico
    $stmt = $pdo->prepare("INSERT INTO Usuarios (nombre_usuario, email, puesto, fecha_creacion) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$inputData['nombreUsuario'], $inputData['email'], 'tecnico']);

    // Verificar si se insertó correctamente
    if ($stmt->rowCount() > 0) {
        echo json_encode(['mensaje' => 'Técnico agregado exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al agregar técnico']);
    }
}

function editarTecnico($id)
{
    global $pdo;

    // Obtener el token desde los headers
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Token no proporcionado']);
        return;
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $userData = verificarTokenUser($token);

    // Verificar que el token sea válido
    if (!$userData) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        return;
    }

    // Verificar si el usuario tiene el puesto "responsable"
    if ($userData['puesto'] !== PuestoUsuario::RESPONSABLE->value) {
        http_response_code(403);
        echo json_encode(['error' => 'Permiso denegado. Solo los usuarios con el puesto "responsable" pueden editar técnicos.']);
        return;
    }

    // Obtener los datos del técnico desde el cuerpo de la solicitud
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validar los datos requeridos
    if (!isset($inputData['nombreUsuario'], $inputData['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan datos requeridos (nombre, email).']);
        return;
    }

    // Preparar la consulta para actualizar el técnico
    $stmt = $pdo->prepare("UPDATE Usuarios SET nombre_usuario = ?, email = ? WHERE id_usuario = ? AND puesto = ?");
    $stmt->execute([$inputData['nombreUsuario'], $inputData['email'], $id, 'tecnico']);

    // Verificar si se actualizó correctamente
    if ($stmt->rowCount() > 0) {
        echo json_encode(['mensaje' => 'Técnico actualizado exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar técnico']);
    }
}


