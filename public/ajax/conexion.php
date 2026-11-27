<?php
class Conexion
{
    public static function Conectar()
    {
        require_once dirname(__DIR__, 2) . '/config/database.php';
        try {
            return database_connection([PDO::ATTR_EMULATE_PREPARES => true]);
        } catch (Exception $e) {
            die('El error de Conexion es: ' . $e->getMessage());
        }
    }
}
