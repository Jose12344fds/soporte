<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * get_items_validar.php — Items con SUT según tipo de proceso
 * POST: idPadre, tipoConsulta (1-6)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasValidar.php';

try {
    $idPadre = isset($_POST['idPadre']) ? (int)$_POST['idPadre'] : 0;
    $tipo    = isset($_POST['tipoConsulta']) ? (int)$_POST['tipoConsulta'] : 1;

    if (!$idPadre) {
        echo json_encode([]);
        exit;
    }
    echo json_encode(ConsultasValidar::getItemsValidar($idPadre, $tipo), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
