<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/* api/prgsemana/get_items.php — Subconsulta items por IdPadre */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasPrgSemana.php';

try {
    $idPadre = isset($_POST['idPadre']) ? (int)$_POST['idPadre'] : 0;
    $tipo    = isset($_POST['tipoConsulta']) ? (int)$_POST['tipoConsulta'] : 1;

    if (!$idPadre) {
        echo json_encode([]);
        exit;
    }

    echo json_encode(ConsultasPrgSemana::getItems($idPadre, $tipo), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
