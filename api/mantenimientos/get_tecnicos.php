<?php
/**
 * API: Técnicos activos — @TAMA_TECNICOS
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion_hana.php';

try {
    $pdo = getHanaPdo();

    $sql = 'SELECT T0."Code", T0."Name", T0."U_Especialidad", T0."U_Correo", T0."U_Activo"'
         . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_TECNICOS" T0'
         . " WHERE T0.\"U_Activo\" = 'Y'"
         . ' ORDER BY T0."Name"';

    $rows = $pdo->query($sql)->fetchAll();

    ob_end_clean();
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
