<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/* api/prgsemana/actualizar_horas.php — UPDATE TotHoras en programadorsem */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasPrgSemana.php';

try {
    $id    = isset($_POST['idPrograma']) ? (int)$_POST['idPrograma'] : 0;
    $horas = $_POST['totalHoras'] ?? '';

    if (!$id) {
        echo json_encode(['success'=>false,'message'=>'IdPrograma no proporcionado']);
        exit;
    }

    echo json_encode(ConsultasPrgSemana::actualizarHoras($id, $horas), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
