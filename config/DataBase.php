<?php

class DataBase
{
    private static ?PDO $conn = null;

    public function getConnection()
    {
        if (self::$conn !== null) {
            return self::$conn;
        }

        $host    = env('DB_HOST', 'localhost:3306');
        $db_name = env('DB_NAME', 'test');
        $db_user = env('DB_USER', 'root');
        $db_pass = env('DB_PASS', '');

        try {
            self::$conn = new PDO(
                "mysql:dbname={$db_name};host={$host};charset=utf8mb4",
                $db_user,
                $db_pass,
                [
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // En producción NUNCA volcar el mensaje al cliente.
            error_log("DB connection error: " . $e->getMessage());
            if (env('APP_ENV', 'production') === 'development') {
                echo "Error en la conexion: " . $e->getMessage();
            } else {
                echo "Error de conexión a la base de datos.";
            }
            return null;
        }
        return self::$conn;
    }
}