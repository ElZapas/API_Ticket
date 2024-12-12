<?php

use src\Database\Database;
use src\JWTHelper;
use utils\HttpResponses;
use enums\PuestoUsuario;
use utils\ApiResource;
use Utils\Request;

return new ApiResource(
    resources: [
        new ApiResource(
            method: "GET",
            resources: fn() => obtenerTecnicos(),
        ),
        new ApiResource(
            method: "DELETE",
            verification: fn() => isset(Request::$URI_ARR[1]),
            resources: fn() => deshabilitarTecnico(),
        )
    ]
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
            AND puesto = ?"
    );
    $stmt->execute([PuestoUsuario::TECNICO->value]);

    $tecnicos = $stmt->fetchAll();

    HttpResponses::OK(
        $tecnicos ? $tecnicos :
            ['mensaje' => 'No se encontraron técnicos registrados']
    );
}
function deshabilitarTecnico()
{
    // ruta : users/{id del tecnico a eliminar}
    // requiere token dentro del header
    // metodo : DELETE
    $userData = JWTHelper::getUser();
    if ($userData->puesto !== PuestoUsuario::RESPONSABLE->value)
        HttpResponses::Unauthorized("Recurso autorizado solo para tecnicos");

    $idTecnico = (int)Request::$URI_ARR[1];
    $db = Database::connection();
    $query = $db->prepare(
        "UPDATE usuarios 
            SET activo = false
                WHERE id_usuario = ? 
                AND puesto = 'tecnico'
                LIMIT 1
                ",
    );
    // $query = $db->prepare(
    //     "SELECT * FROM usuarios 
    //             WHERE id_usuario = ?
    //     ",
    // );
    $query->execute([$idTecnico]);
    $query->rowCount() == 1 ?
        HttpResponses::OK("Tecnico Deshabilitado") :
        HttpResponses::Bad_Request("Tecnico no encontrado o ya esta deshabilitado");
}
