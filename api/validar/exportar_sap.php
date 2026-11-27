<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * exportar_sap.php — Enviar datos a SAP Service Layer (PATCH ProductTrees)
 * POST: var_json (JSON string), ord_dato (ItemCode)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/SapServiceLayer.php';

try {
    $varJson  = isset($_POST['var_json']) ? $_POST['var_json'] : null;
    $ordDato  = isset($_POST['ord_dato']) ? trim($_POST['ord_dato']) : '';

    if (!$varJson || $ordDato === '') {
        echo json_encode(['success' => false, 'message' => 'Datos insuficientes.']);
        exit;
    }

    $body = json_decode($varJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'JSON inválido: ' . json_last_error_msg()]);
        exit;
    }

    echo json_encode(SapServiceLayer::actualizarProductTree($ordDato, $body));
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
