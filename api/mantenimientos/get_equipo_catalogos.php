<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion_hana.php';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';

function out_cat(array $data, int $status = 200): void {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function cat_table(string $tipo): ?string {
    $tipo = strtolower(trim($tipo));
    if ($tipo === 'proceso' || $tipo === 'procesos') return '@TAMA_PROCESO';
    if ($tipo === 'registro' || $tipo === 'registros') return '@TAMA_REGISTROS';
    return null;
}

function is_registro_tipo(string $tipo): bool {
    $tipo = strtolower(trim($tipo));
    return $tipo === 'registro' || $tipo === 'registros';
}

try {
    if (!$session->isUserLoggedIn(false)) {
        out_cat(['success' => false, 'error' => 'No autorizado'], 401);
    }
    $tipo = $_GET['tipo'] ?? 'proceso';
    $table = cat_table($tipo);
    if (!$table) {
        out_cat(['success' => false, 'error' => 'Catalogo no valido'], 400);
    }
    $pdo = getHanaPdo();
    $isRegistro = is_registro_tipo($tipo);
    $select = $isRegistro ? '"Code", "Name", "U_NomRegisList"' : '"Code", "Name", NULL AS "U_NomRegisList"';
    $rows = $pdo->query(
        'SELECT ' . $select . ' FROM "SBO_TAMAING_PRODUC"."' . $table . '" ORDER BY LPAD(COALESCE("Code", \'\'), 12, \'0\'), "Name"'
    )->fetchAll();
    $data = array_map(function($r) {
        return [
            'Code' => trim((string)($r['Code'] ?? '')),
            'Name' => trim((string)($r['Name'] ?? '')),
            'U_NomRegisList' => trim((string)($r['U_NomRegisList'] ?? '')),
        ];
    }, $rows);
    out_cat(['success' => true, 'data' => $data, 'total' => count($data)]);
} catch (Throwable $e) {
    out_cat(['success' => false, 'error' => $e->getMessage(), 'line' => $e->getLine()], 500);
}
