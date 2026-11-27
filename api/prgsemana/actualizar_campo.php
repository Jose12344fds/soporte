<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/* api/prgsemana/actualizar_campo.php — UPDATE campo individual en programadorsem */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasPrgSemana.php';

try {
    $id    = isset($_POST['idPrograma']) ? (int)$_POST['idPrograma'] : 0;
    $field = $_POST['field'] ?? '';
    $val   = $_POST['newValue'] ?? '';

    if (!$id || !$field) {
        echo json_encode(['success'=>false,'message'=>'Parámetros incompletos']);
        exit;
    }

    echo json_encode(ConsultasPrgSemana::actualizarCampo($id, $field, $val, $_POST['totalHoras'] ?? null), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
