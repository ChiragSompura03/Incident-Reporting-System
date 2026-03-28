<?php

require_once __DIR__ . '/config.php';

class Database
{

    private static ?PDO $instance = null;
    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (APP_ENV === 'development') {
                    die(json_encode([
                        'status'  => 'error',
                        'message' => 'DB Connection Failed: ' . $e->getMessage()
                    ]));
                } else {
                    die(json_encode([
                        'status'  => 'error',
                        'message' => 'Database connection error. Please try later.'
                    ]));
                }
            }
        }
        return self::$instance;
    }

    private function __clone() {}
}

function db(): PDO
{
    return Database::getInstance();
}
