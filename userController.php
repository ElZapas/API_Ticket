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
