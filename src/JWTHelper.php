<?php

namespace src;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use utils\HttpResponses;
use Utils\Request;

class JWTHelper
{
    static $TOKEN = null;

    private function __construct() {}

    static final function redecode(
        string $token = null,
        string $key = null
    ): array|null {

        if (self::$TOKEN === null) {
            try {
                self::$TOKEN = (array) JWT::decode(
                    $token ?? Request::$TOKEN,
                    new Key(
                        $key ?? $_ENV["JWT_SECRET_KEY"],
                        'HS256'
                    )
                );
            } catch (\Firebase\JWT\ExpiredException $e) {
                HttpResponses::Unauthorized("token ha expirado");
            } catch (\Exception $e) {
                HttpResponses::Unauthorized("token invalido");
            }
        }
        return self::$TOKEN;
    }

    static final function tokenIsNull(bool $critic = false): bool
    {
        $isNull = is_null(self::$TOKEN);
        if ($isNull && $critic)
            HttpResponses::Unauthorized(
                "Token no proporcionado"
            );
        return $isNull;
    }

    static final function getUser()
    {
        self::tokenIsNull(true);
        if (!isset(self::$TOKEN["user"]))
            HttpResponses::Unauthorized('Token inválido o expirado');
        return self::$TOKEN["user"];
    }
};

JWTHelper::redecode();
