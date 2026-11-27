<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * update_celda.php — Edición inline de celda en othija
 * POST: IdHijOT, fieldName, newValue
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasValidar.php';

try {
    $idHijOT = isset($_POST['IdHijOT'])   ? (int)$_POST['IdHijOT']      : 0;
    $campo   = isset($_POST['fieldName'])  ? trim($_POST['fieldName'])   : '';
    $valor   = isset($_POST['newValue'])   ? $_POST['newValue']          : '';

    if (!$idHijOT || $campo === '') {
        echo json_encode(['success' => false, 'message' => 'Parámetros insuficientes.']);
        exit;
    }
    echo json_encode(ConsultasValidar::updateCelda($idHijOT, $campo, $valor));
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
