<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * get_recursos_sap.php — Recursos SAP HANA de un item (OWOR→OITT→ITT1→ORSC)
 * POST: ItemCod, proy
 */
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasHana.php';

try {
    $itemCod  = isset($_POST['ItemCod']) ? trim($_POST['ItemCod']) : '';
    $proyecto = isset($_POST['proy'])    ? trim($_POST['proy'])    : '';

    if ($itemCod === '' || $proyecto === '') {
        echo json_encode([]);
        exit;
    }
    echo json_encode(ConsultasHana::getRecursosSAP($itemCod, $proyecto), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
