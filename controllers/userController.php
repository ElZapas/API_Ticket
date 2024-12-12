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
            verification: fn() => isset(Request::$URI_ARR[1]),
            resources: [
                new ApiResource(
                    method: "DELETE",
                    resources: fn() => deshabilitarTecnico(),
                ),
                new ApiResource(
                    method: "PUT",
                    resources: fn() => actualizarTecnico(),
                ),
            ]
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
    $query = $pdo->prepare(
        "SELECT 
        id_usuario AS idUsuario, 
        nombre_usuario AS nombreUsuario, 
        email, 
        fecha_creacion AS fechaCreacion 
            FROM usuarios 
            WHERE activo = true
            AND puesto = ?"
    );
    $query->execute([PuestoUsuario::TECNICO->value]);

    $tecnicos = $query->fetchAll();

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
    $db = Database::connection();
    $userData = JWTHelper::getUser();
    if ($userData->puesto !== PuestoUsuario::RESPONSABLE->value)
        HttpResponses::Unauthorized("Recurso autorizado solo para tecnicos");

    $idTecnico = (int)Request::$URI_ARR[1];
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

function actualizarTecnico()
{
    // ruta : users/{id del tecnico a eliminar}
    // requiere token dentro del header
    // metodo : PUT
    // body:{
    //  nombreUsuario,
    //  email
    //}
    $userData = JWTHelper::getUser();

    // Verificar que el usuario sea "Responsable"
    if ($userData->puesto !== PuestoUsuario::RESPONSABLE->value)
        HttpResponses::Forbidden(
            ['error' => 'Permiso denegado. Solo los usuarios con puesto "responsable" pueden actualizar tecnicos.']
        );

    $data = Request::$POST;

    if (!isset($data['nombreUsuario'], $data['email']))
        HttpResponses::Bad_Request(['error' => 'Faltan campos']);

    $idTecnico = (int)Request::$URI_ARR[1];
    $pdo = Database::connection();
    $query = $pdo->prepare(
        "UPDATE usuarios
            SET 
            nombre_usuario = ?,
            email = ?
                WHERE id_usuario = ?
                AND puesto != 'responsable'
                AND activo = true
                    LIMIT 1
                "
    );

    $query->execute([
        $data['nombreUsuario'],
        $data['email'],
        $idTecnico,
    ]);

    if ($query->rowCount() > 0) {
        HttpResponses::OK(['success' => 'Usuario actualizado exitosamente']);
    } else {
        HttpResponses::Bad_Request(
            ['error' => 'Usuario no encontrado o no se realizaron cambios']
        );
    }
}

function actualizarPassword()
{
    // ruta : users/{id del tecnico a eliminar}
    // requiere token dentro del header
    // metodo : PUT
    // body:{
    //  nombreUsuario,
    //  email
    //}
    $userData = JWTHelper::getUser();

    // Verificar que el usuario sea "Responsable"
    if ($userData->puesto !== PuestoUsuario::RESPONSABLE->value)
        HttpResponses::Forbidden(
            ['error' => 'Permiso denegado. Solo los usuarios con puesto "responsable" pueden actualizar tecnicos.']
        );

    $data = Request::$POST;

    if (!isset($data['password'], $data['email']))
        HttpResponses::Bad_Request(['error' => 'Faltan campos']);

    $idTecnico = (int)Request::$URI_ARR[1];
    $pdo = Database::connection();
    $query = $pdo->prepare(
        "UPDATE usuarios
            SET 
            nombre_usuario = ?,
            email = ?
                WHERE id_usuario = ?
                AND puesto != 'responsable'
                AND activo = true
                    LIMIT 1
                "
    );

    $query->execute([
        $data['nombreUsuario'],
        $data['email'],
        $idTecnico,
    ]);

    if ($query->rowCount() > 0) {
        HttpResponses::OK(['success' => 'Usuario actualizado exitosamente']);
    } else {
        HttpResponses::Bad_Request(
            ['error' => 'Usuario no encontrado o no se realizaron cambios']
        );
    }
}
