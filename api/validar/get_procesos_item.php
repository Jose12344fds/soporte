<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * get_procesos_item.php — Desglose de procesos por item (16 UNIONs)
 * POST: idPadre, codPdre, idHijOT (opcional)
 */
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasValidar.php';

try {
    $idPadre = isset($_POST['idPadre']) ? (int)$_POST['idPadre'] : 0;
    $codPdre = isset($_POST['codPdre']) ? trim($_POST['codPdre']) : '';
    $idHijOT = isset($_POST['idHijOT']) && $_POST['idHijOT'] !== '' ? (int)$_POST['idHijOT'] : null;

    if (!$idPadre || $codPdre === '') {
        echo json_encode([]);
        exit;
    }
    echo json_encode(ConsultasValidar::getProcesosItem($idPadre, $codPdre, $idHijOT), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
