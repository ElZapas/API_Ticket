<?php

// Importamos las librerías necesarias para trabajar con JSON Web Tokens (JWT) en PHP.
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Variable de entorno KEY
$key = $_ENV['JWT_SECRET_KEY'];

function verificarTokenUser($jwt)
{
    global $key; // Incluimos la clave secreta para JWT.

    try {
        // Decodificamos el token usando la clave y el algoritmo HS256 para obtener los datos originales.
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
        return (array) $decoded->user; // Devolvemos los datos del usuario si el token es válido.
    } catch (Exception $e) {
        // Si el token es inválido o ha expirado, devolvemos null para indicar un error de autenticación.
        return null;
    }
}