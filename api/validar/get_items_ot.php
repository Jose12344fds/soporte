<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * get_items_ot.php — Items hijos de una OT padre (para modal Exportar)
 * POST: idPadre
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasValidar.php';

try {
    $idPadre = isset($_POST['idPadre']) ? (int)$_POST['idPadre'] : 0;
    if (!$idPadre) {
        echo json_encode([]);
        exit;
    }
    echo json_encode(ConsultasValidar::getItemsOT($idPadre), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
