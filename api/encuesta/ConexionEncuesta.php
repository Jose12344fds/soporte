<?php
/**
 * ConexionEncuesta.php — Conexión PDO para módulo Encuestas
 * Base de datos: tama en localhost
 */
class ConexionEncuesta {
    private static $instance = null;

    public static function conectar(): PDO {
        if (self::$instance === null) {
            $host = 'localhost';
            $db   = 'tama';
            $user = 'root';
            $pass = '';

            $opciones = [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ];

            self::$instance = new PDO(
                "mysql:host={$host};dbname={$db};charset=utf8mb4",
                $user,
                $pass,
                $opciones
            );
        }
        return self::$instance;
    }
}
