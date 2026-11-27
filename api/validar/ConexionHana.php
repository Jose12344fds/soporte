<?php
/**
 * ConexionHana.php — Conexión SAP HANA para módulo Validar
 * Host: 192.168.250.32:30015  |  DB: SBO_TAMAING_PRODUC
 */
class ConexionHana {
    private static $instance = null;

    public static function conectar() {
        if (self::$instance === null) {
            $dsn = 'odbc:Driver={HDBODBC};ServerNode=192.168.4.33:30015;Database=SBO_TAMAING_PRODUC';
            try {
                self::$instance = new PDO($dsn, 'B1ADMIN', 'Au$tr4l14', [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES    => false
                ]);
            } catch (PDOException $e) {
                throw new Exception('Error de conexión SAP HANA: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
