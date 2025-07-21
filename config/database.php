<?php
namespace App\Config;

use PDO;

class Database
{
    public static function getConnection(): PDO
    {
        $host = 'postgres';
        $db   = 'auth_db';
        $user = 'user';
        $pass = 'password';

        $dsn = "pgsql:host=$host;port=5432;dbname=$db;user=$user;password=$pass";

        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
}
