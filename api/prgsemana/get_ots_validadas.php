<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/* api/prgsemana/get_ots_validadas.php — OTs con Estado_OT = 2 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasPrgSemana.php';

try {
    echo json_encode(ConsultasPrgSemana::getOTsValidadas(), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
