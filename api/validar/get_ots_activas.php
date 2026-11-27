<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * get_ots_activas.php — Devuelve OTs con Estado_OT = 1
 * Uso: POST/GET → JSON array
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasValidar.php';

try {
    echo json_encode(ConsultasValidar::getOTsActivas(), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
