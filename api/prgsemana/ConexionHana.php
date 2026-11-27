<?php

class Conexion1 {
    public static function Conectar1() {
        return getHanaConnection();
    }
}

function getHanaConnection() {
    $username = "B1ADMIN";
    $password = 'Au$tr4l14';
    $host = "192.168.4.33";
    $port = "30015";
    $database = "SBO_TAMAING_PRODUC";
    $dsn = "odbc:Driver={HDBODBC};ServerNode=$host:$port;Database=$database";
    try {
        $conexion = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $conexion;
    } catch (PDOException $e) {
        throw new Exception("Error de conexión a SAP HANA: " . $e->getMessage());
    }
}
?>