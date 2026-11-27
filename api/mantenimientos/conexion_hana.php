<?php
/**
 * Conexión SAP HANA vía ODBC
 * Módulo Mantenimientos — TAMA
 */
function getHanaPdo(): PDO {
    $dsn = 'odbc:Driver={HDBODBC};ServerNode=192.168.4.33:30015;Database=SBO_TAMAING_PRODUC;CHAR_AS_UTF8=1';
    return new PDO($dsn, 'B1ADMIN', 'Au$tr4l14', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
    ]);
}
