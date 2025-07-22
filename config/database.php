<?php
namespace App\Config;

use PDO;

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = 'postgres';
            $db   = 'auth_db';
            $user = 'user';
            $pass = 'password';

            $dsn = "pgsql:host=$host;port=5432;dbname=$db;user=$user;password=$pass";
            self::$instance = new PDO($dsn);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return self::$instance;
    }
}
