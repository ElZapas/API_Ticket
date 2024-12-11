<?php

use src\Database\Database;
use src\JWTHelper;
use utils\HttpResponses;
use enums\PuestoUsuario;
use utils\ApiResource;
use Utils\Request;

return new ApiResource(
    "GET",
    null,
    fn() => obtenerTecnicos(),
);

function obtenerTecnicos()
{
    $pdo = Database::connection();

    $userData = JWTHelper::getUser();

    // Verificar si el usuario tiene el puesto "responsable"
    if ($userData->puesto !== PuestoUsuario::RESPONSABLE->value) {
        HttpResponses::Forbidden(
            ['error' => 'Permiso denegado. Solo los usuarios con el puesto "responsable" pueden acceder a esta información.']
        );
    }

    // Consulta para obtener los usuarios técnicos
    $stmt = $pdo->prepare(
        "SELECT 
        id_usuario AS idUsuario, 
        nombre_usuario AS nombreUsuario, 
        email, 
        fecha_creacion AS fechaCreacion 
            FROM usuarios 
            WHERE activo = true
                WHERE puesto = ?"
    );
    $stmt->execute([PuestoUsuario::TECNICO->value]);

    $tecnicos = $stmt->fetchAll();

    HttpResponses::OK(
        $tecnicos ? $tecnicos :
            ['mensaje' => 'No se encontraron técnicos registrados']
    );
}
