<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out_cat_save(['success' => false, 'error' => 'Metodo no permitido'], 405);
}

require_once __DIR__ . '/conexion_hana.php';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';

function out_cat_save(array $data, int $status = 200): void {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function vcat($v): string {
    return trim((string)($v ?? ''));
}

function qcat($v): string {
    return "'" . str_replace("'", "''", vcat($v)) . "'";
}

function cat_table_save(string $tipo): ?string {
    $tipo = strtolower(trim($tipo));
    if ($tipo === 'proceso' || $tipo === 'procesos') return '@TAMA_PROCESO';
    if ($tipo === 'registro' || $tipo === 'registros') return '@TAMA_REGISTROS';
    return null;
}

function is_registro_save(string $tipo): bool {
    $tipo = strtolower(trim($tipo));
    return $tipo === 'registro' || $tipo === 'registros';
}

try {
    if (!$session->isUserLoggedIn(false)) {
        out_cat_save(['success' => false, 'error' => 'No autorizado'], 401);
    }
    if (!tiene_permiso('mantenimientos', 'crear') && !tiene_permiso('mantenimientos', 'editar')) {
        out_cat_save(['success' => false, 'error' => 'No tiene permiso para registrar catalogos.'], 403);
    }

    $raw = file_get_contents('php://input');
    $input = !empty($raw) ? (json_decode($raw, true) ?? $_POST) : $_POST;
    $tipo = vcat($input['tipo'] ?? '');
    $action = strtolower(vcat($input['action'] ?? $input['accion'] ?? 'create'));
    if ($action === 'actualizar') $action = 'update';
    if ($action === 'crear') $action = 'create';
    $table = cat_table_save($tipo);
    if (!$table) {
        out_cat_save(['success' => false, 'error' => 'Catalogo no valido'], 400);
    }

    $code = vcat($input['code'] ?? $input['Code'] ?? '');
    $name = vcat($input['name'] ?? $input['Name'] ?? '');
    $nomRegisList = vcat($input['nom_regis_list'] ?? $input['U_NomRegisList'] ?? '');
    if ($code === '' || $name === '') {
        out_cat_save(['success' => false, 'error' => 'Complete Code y Name.'], 400);
    }
    if (!preg_match('/^\d+$/', $code)) {
        out_cat_save(['success' => false, 'error' => 'Code debe ser entero.'], 400);
    }

    $pdo = getHanaPdo();
    $isRegistro = is_registro_save($tipo);

    if ($action === 'update') {
        if (!tiene_permiso('mantenimientos', 'editar')) {
            out_cat_save(['success' => false, 'error' => 'No tiene permiso para editar catalogos.'], 403);
        }
        $exists = $pdo->query(
            'SELECT COUNT(*) AS "CNT" FROM "SBO_TAMAING_PRODUC"."' . $table . '" WHERE "Code" = ' . qcat($code)
        )->fetch();
        if ((int)($exists['CNT'] ?? array_values($exists)[0] ?? 0) <= 0) {
            out_cat_save(['success' => false, 'error' => 'El Code no existe.'], 404);
        }
        $pdo->exec(
            'UPDATE "SBO_TAMAING_PRODUC"."' . $table . '"'
            . ' SET "Name" = ' . qcat($name)
            . ($isRegistro ? ', "U_NomRegisList" = ' . qcat($nomRegisList) : '')
            . ', "UpdateDate" = CURRENT_DATE, "UpdateTime" = ' . (int)date('Hi')
            . ' WHERE "Code" = ' . qcat($code)
        );
        out_cat_save(['success' => true, 'action' => 'updated', 'Code' => $code, 'Name' => $name, 'U_NomRegisList' => $nomRegisList]);
    }

    $exists = $pdo->query(
        'SELECT COUNT(*) AS "CNT" FROM "SBO_TAMAING_PRODUC"."' . $table . '" WHERE "Code" = ' . qcat($code)
    )->fetch();
    if ((int)($exists['CNT'] ?? array_values($exists)[0] ?? 0) > 0) {
        out_cat_save(['success' => false, 'error' => 'El Code ya existe.'], 409);
    }

    $doc = $pdo->query(
        'SELECT COALESCE(MAX("DocEntry"), 0) + 1 AS "NXT" FROM "SBO_TAMAING_PRODUC"."' . $table . '"'
    )->fetch();
    $docEntry = (int)($doc['NXT'] ?? array_values($doc)[0] ?? 1);

    $fields = '"Code","Name","DocEntry","Canceled","Object","LogInst","UserSign","Transfered","CreateDate","CreateTime","UpdateDate","UpdateTime","DataSource"';
    $values = qcat($code) . ',' . qcat($name) . ',' . $docEntry . ',\'N\',' . qcat(substr($table, 1)) . ',1,1,\'N\',CURRENT_DATE,' . (int)date('Hi') . ',CURRENT_DATE,' . (int)date('Hi') . ',\'I\'';
    if ($isRegistro) {
        $fields .= ',"U_NomRegisList"';
        $values .= ',' . qcat($nomRegisList);
    }

    $pdo->exec(
        'INSERT INTO "SBO_TAMAING_PRODUC"."' . $table . '" (' . $fields . ') VALUES (' . $values . ')'
    );

    out_cat_save(['success' => true, 'Code' => $code, 'Name' => $name, 'DocEntry' => $docEntry, 'U_NomRegisList' => $nomRegisList]);
} catch (Throwable $e) {
    out_cat_save(['success' => false, 'error' => $e->getMessage(), 'line' => $e->getLine()], 500);
}
