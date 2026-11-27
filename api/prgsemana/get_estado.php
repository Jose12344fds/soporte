<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/* api/prgsemana/get_estado.php — Estado programado de un proceso */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasPrgSemana.php';

try {
    $idPadreOT = isset($_POST['idPadreOT']) ? (int)$_POST['idPadreOT'] : 0;
    $codPdre   = $_POST['codPdre'] ?? '';
    $proceso   = $_POST['proceso'] ?? '';

    if (!$idPadreOT || !$codPdre || !$proceso) {
        echo json_encode(['RESULT'=>0]);
        exit;
    }

    $result = ConsultasPrgSemana::getEstadoProgramado($idPadreOT, $codPdre, $proceso);
    echo json_encode(['RESULT'=>$result], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['RESULT'=>0]);
}
