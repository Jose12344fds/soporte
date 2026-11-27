<?php 
class Conexion{	  
    public static function Conectar() {
        if (!defined('servidor'))  define('servidor', 'localhost');
        if (!defined('nombre_bd')) define('nombre_bd', 'tama');
        if (!defined('usuario'))   define('usuario', 'root');
        if (!defined('password'))  define('password', '');

        $opciones = array(
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        try {
            $conexion = new PDO("mysql:host=" . servidor . ";dbname=" . nombre_bd, usuario, password, $opciones);
            return $conexion;
        } catch (Exception $e) {
            // NO usar die() — retornar JSON válido para APIs
            if (php_sapi_name() !== 'cli' && !headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['success' => false, 'message' => 'Error de conexión BD: ' . $e->getMessage()]);
            exit;
        }
    }
}