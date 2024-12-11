<?php

namespace utils;

class Request
{
    static public string $METHOD;
    static public string $URI;
    static public array $URI_ARR;
    static public $POST;
    static public $GET;
    static public $HEADERS;
    static public $TOKEN;
    public static function reload(): void
    {
        self::$METHOD = $_SERVER["REQUEST_METHOD"];
        self::$URI = $_SERVER["PATH_INFO"] ?? $_SERVER["REDIRECT_URL"] ?? "/";
        self::$URI_ARR = array_values(
            array_filter(
                explode("/", self::$URI),
                fn($v) => $v !== ""
            )
        );
        if (function_exists("apache_request_headers")) {
            self::$HEADERS = apache_request_headers();
        } else {
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $nombreCabecera = str_replace('_', '-', substr($key, 5));
                    $nombreCabecera = ucwords(strtolower($nombreCabecera), '-');
                    $cabeceras[$nombreCabecera] = $value;
                }
            }
            self::$HEADERS = $cabeceras;
        }
        if (isset(self::$HEADERS['Authorization']))
            self::$TOKEN = str_replace(
                'Bearer ',
                '',
                self::$HEADERS['Authorization']
            );
        self::$POST = sizeof($_POST) == 0 ?
            json_decode(file_get_contents("php://input"), true) : $_POST;
        self::$GET = $_GET;
    }
    private function __construct() {}
};

Request::reload();
