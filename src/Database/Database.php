<?php

namespace src\Database;

use utils\HttpResponses;
use Utils\Request;

class Database
{
    private static \PDO|null $instance = null;

    public static function connection(): \PDO
    {
        if (self::$instance === null) {
            try {

                [
                    'DB_HOST' => $host,
                    'DB_NAME' => $db,
                    'DB_USER' => $user,
                    'DB_PASSWORD' => $pass,
                    'DB_PORT' => $port,
                    
                ] = $_ENV;

                $dsn = "mysql:host=$host;port=$port;dbname=$db;";


                $options = [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new \PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                // HttpResponses::Internal_Error("wasa");
                throw new \PDOException($e->getMessage(), (int)$e->getCode());
            }
        }
        return self::$instance;
    }

    private function __construct() {}

    // public function __clone() {}

    // public function __wakeup() {}
}
