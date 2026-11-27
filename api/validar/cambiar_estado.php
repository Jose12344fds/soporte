<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * cambiar_estado.php — Cambiar Estado_OT → 2 (Validado)
 * POST: IdHijOT (IdPadre de la OT)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasValidar.php';

try {
    $idPadre = isset($_POST['IdHijOT']) ? (int)$_POST['IdHijOT'] : 0;
    if (!$idPadre) {
        echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
        exit;
    }
    echo json_encode(ConsultasValidar::cambiarEstado($idPadre));
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
