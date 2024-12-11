<?php

use Firebase\JWT\JWT;
use src\Database\Database;
use src\JWTHelper;
use utils\ApiResource;
use utils\HttpCode;
use utils\HttpResponses;
use utils\Request;
use enums\PuestoUsuario;

return new ApiResource(
    verification: fn() => isset(Request::$URI_ARR[1]),
    resources: [
        new ApiResource(
            method: "POST",
            resources: [
                new ApiResource(
                    verification: fn() => Request::$URI_ARR[1] === "login",
                    resources: function () {
                        error_log("Login function called");

                        $data = Request::$POST;

                        if (!isset(
                            $data['email'],
                            $data['password'],
                            $data['rememberMe']
                        )) {
                            HttpResponses::Bad_Request('Faltan campos obligatorios');
                        }

                        $db = Database::connection();
                        $query = $db->prepare(
                            'SELECT 
                                id_usuario, nombre_usuario, email, puesto, password 
                                    FROM 
                                    usuarios WHERE email = ?'
                        );
                        $query->execute([$data['email']]);
                        $user = $query->fetch();

                        // Verificamos si el usuario existe y si la contraseña proporcionada coincide con la almacenada.
                        // if (!$user || !password_verify($data['password'], $user['password'])) {
                        //     http_response_code(401);
                        //     echo json_encode(['error' => 'Credenciales incorrectas']);
                        //     return;
                        // }
                        if (!$user || !password_verify($data['password'], $user['password'])) {
                            HttpResponses::Unauthorized(data: 'Credenciales incorrectas');
                        }

                        // Configuramos el tiempo de creación y expiración del token.
                        $issuedAt = time(); // Hora de creación del token en tiempo Unix.
                        $expirationTime = $data['rememberMe'] ? $issuedAt + (60 * 60 * 24 * 14) : $issuedAt + (60 * 60); // 2 semanas o 1 hora.

                        // Creamos el payload del token, que contiene la información del usuario y las configuraciones de tiempo.
                        $payload = [
                            //hora de creación
                            'iat' => $issuedAt,
                            //la hora de expiración
                            'exp' => $expirationTime,
                            // Datos del USUARIO
                            'user' => [
                                'idUsuario' => $user['id_usuario'],
                                'nombreUsuario' => $user['nombre_usuario'],
                                'email' => $user['email'],
                                'puesto' => $user['puesto']
                            ]
                        ];
                        // HttpResponses::OK(JWTHelper::$TOKEN);
                        // Generamos el token JWT usando el payload y la clave secreta con el algoritmo HS256.
                        $jwt = JWT::encode($payload, $_ENV["JWT_SECRET_KEY"], 'HS256');

                        HttpResponses::OK([
                            'token' => $jwt,
                            'user' => [
                                'idUsuario' => $user['id_usuario'],
                                'nombreUsuario' => $user['nombre_usuario'],
                                'email' => $user['email'],
                                'puesto' => $user['puesto'],
                            ]
                        ]);
                    },
                ),
                new ApiResource(
                    verification: fn() => Request::$URI_ARR[1] === "register",
                    resources: function () {
                        $db = Database::connection();

                        $data = Request::$POST;

                        if (!isset(
                            $data['nombreUsuario'],
                            $data['email'],
                            $data['password'],
                            $data['puesto']
                        )) {
                            HttpResponses::Bad_Request('Faltan campos obligatorios');
                        }

                        // Validamos que el valor de 'puesto' sea "responsable" o "tecnico".
                        if (!in_array($data['puesto'], [PuestoUsuario::RESPONSABLE->value, PuestoUsuario::TECNICO->value])) {
                            HttpResponses::Bad_Request('El campo puesto debe ser "' . PuestoUsuario::RESPONSABLE->value . '" o "' . PuestoUsuario::TECNICO->value . '"');
                        }

                        // Verificamos si ya existe un usuario registrado con el mismo correo electrónico en la base de datos.
                        $query = $db->prepare(
                            'SELECT id_usuario 
                                FROM usuarios 
                                    WHERE email = ?'
                        );
                        $query->execute([$data['email']]);
                        if ($query->fetch()) {
                            // Si el correo ya está registrado, enviamos un código de error 409 (Conflict).
                            HttpResponses::send(
                                code: 409,
                                message: 'El email ya está registrado'
                            );
                            return;
                        }

                        // Si el email es único, encriptamos la contraseña del usuario usando `password_hash` para mayor seguridad.
                        // Esto evita almacenar contraseñas en texto plano en la base de datos.
                        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                        // Insertamos el nuevo usuario en la base de datos con su nombre, email, puesto, fecha de creación y contraseña encriptada.
                        $query = $db->prepare(
                            'INSERT INTO usuarios (
                            nombre_usuario, 
                            email, 
                            puesto, 
                            fecha_creacion, 
                            password
                            ) VALUES (?, ?, ?, NOW(), ?)'
                        );
                        $query->execute([
                            $data['nombreUsuario'],
                            $data['email'],
                            $data['puesto'],
                            $hashedPassword
                        ]);

                        // Obtenemos el ID del usuario recién creado, que nos devuelve PDO después de la inserción.
                        $userId = $db->lastInsertId();

                        // Recuperamos los datos del usuario recién creado para devolverlos en la respuesta, sin incluir la contraseña.
                        $query = $db->prepare(
                            'SELECT 
                            id_usuario AS idUsuario, 
                            nombre_usuario AS nombreUsuario, 
                            email, 
                            puesto, 
                            fecha_creacion AS fechaCreacion 
                                FROM usuarios 
                                    WHERE id_usuario = ?'
                        );
                        $query->execute([$userId]);
                        $user = $query->fetch();

                        HttpResponses::send(
                            code: HttpCode::Created,
                            data: $user
                        );
                    }
                )
            ]
        ),
        new ApiResource(
            method: "GET",
            verification: fn() => Request::$URI_ARR[1] === "me",
            resources: function () {
                $db = Database::connection();

                $userData = JWTHelper::getUser();

                $query = $db->prepare(
                    'SELECT 
                        id_usuario AS idUsuario, 
                        nombre_usuario AS nombreUsuario, 
                        email, 
                        puesto, 
                        fecha_creacion AS fechaCreacion 
                            FROM usuarios 
                                WHERE id_usuario = ?'
                );
                $query->execute([$userData->idUsuario]);
                $user = $query->fetch();

                if (!$user) HttpResponses::Not_Found('Usuario no encontrado');

                HttpResponses::OK($user);
            }
        ),
    ],
);
