<?php

namespace utils;

class HttpResponses
{
    static function OK($data = null, $message = null)
    {
        self::send(HttpCode::OK, "OK", $data, $message);
    }

    static function Not_Found($data = null, $message = null)
    {
        self::send(HttpCode::Not_Found, "No Found", $data, $message);
    }

    static function Bad_Request($data = null, $message = null)
    {
        self::send(HttpCode::Bad_Request, "error", $data, $message);
    }

    static function Unauthorized($data = null, $message = null)
    {
        self::send(HttpCode::Unauthorized, 'error', $data, $message);
    }

    static function Method_Not_Allowed($data = null, $message = null)
    {
        self::send(HttpCode::Method_Not_Allowed, "Method Not Allowed", $data, $message);
    }

    static function Internal_Error($data = null, $message = null)
    {
        self::send(HttpCode::Internal_Error, "error", $data, $message);
    }

    static function Forbidden($data = null, $message = null)
    {
        self::send(HttpCode::Forbidden, "error", $data, $message);
    }

    static function send(
        int $code,
        $status = "",
        $data = null,
        $message = null
    ) {
        http_response_code($code);
        //OPEN API STANDARD
        // SendAnswer::JSON([
        //     "status" => $status,
        //     "data" => !$_ENV["TEST"] ? null : $data,
        //     "message" => !$_ENV["TEST"] ? null : $message
        // ]);
        SendAnswer::JSON($data);
        exit;
    }
}

enum HttpCode: int
{
    //exitoso (get,post,put,delete)
    const OK = 200;
    //creacion exitosa (post)
    const Created = 201;
    //solicitud en proceso, pero aceptada
    const Accepted = 202;
    //solicitud exitosa, pero no hay contenido
    const No_Content = 204;
    //solicitud erronea
    const Bad_Request = 400;
    //falta de credenciales
    const Unauthorized = 401;
    //falta de permisos para obtener el recurso
    const Forbidden = 403;
    //recurso no encontrado
    const Not_Found = 404;
    //metodo no permitido para este recurso
    const Method_Not_Allowed = 405;
    //encabezado Accept no obedecido
    const Not_Acceptable = 406;
    //error interno del server
    const Internal_Error = 500;
    //servidor actualmente no dispible
    const Service_Unavalible = 503;
}
