<?php
/**
 * ConexionLocal.php — Conexión MySQL local para módulo ProgramarSemana
 * Base de datos: tama en localhost
 */
class ConexionLocal {
    private static $instance = null;

    public static function conectar() {
        if (self::$instance === null) {
            $host = 'localhost';
            $db   = 'tama';
            $user = 'root';
            $pass = '';

            $opciones = [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            ];

            try {
                self::$instance = new PDO("mysql:host={$host};dbname={$db}", $user, $pass, $opciones);
            } catch (PDOException $e1) {
                try {
                    self::$instance = new PDO("mysql:host={$host};dbname={$db}", $user, '', $opciones);
                } catch (PDOException $e2) {
                    try {
                        self::$instance = new PDO("mysql:host=127.0.0.1;dbname={$db}", $user, '', $opciones);
                    } catch (PDOException $e3) {
                        throw new PDOException(
                            "No se pudo conectar a MySQL.\n" .
                            "Intento 1 (localhost+pass): " . $e1->getMessage() . "\n" .
                            "Intento 2 (localhost): " . $e2->getMessage() . "\n" .
                            "Intento 3 (127.0.0.1): " . $e3->getMessage()
                        );
                    }
                }
            }
        }
        return self::$instance;
    }
}
