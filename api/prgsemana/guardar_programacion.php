<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/* api/prgsemana/guardar_programacion.php — INSERT batch de programaciones */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasPrgSemana.php';

try {
    $raw = $_POST['datos'] ?? '';
    $datos = json_decode($raw, true);

    if (!is_array($datos) || count($datos) === 0) {
        echo json_encode(['success'=>false,'message'=>'No se recibieron datos válidos']);
        exit;
    }

    echo json_encode(ConsultasPrgSemana::guardarProgramacion($datos), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
