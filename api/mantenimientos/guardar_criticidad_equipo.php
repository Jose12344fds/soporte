<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion_hana.php';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';

function criticidad_out(array $data, int $status = 200): void {
    while (ob_get_level()) ob_end_clean();
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function criticidad_column_exists(PDO $pdo, string $column): bool {
    try {
        $pdo->query(
            'SELECT "' . str_replace('"', '""', $column) . '"'
            . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" LIMIT 1'
        )->fetch();
        return true;
    } catch (Throwable $error) {
        return false;
    }
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    criticidad_out(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}
if (!$session->isUserLoggedIn(false)) {
    criticidad_out(['success' => false, 'error' => 'No autorizado.'], 401);
}
if (!tiene_permiso('mantenimientos', 'editar')) {
    criticidad_out(['success' => false, 'error' => 'No tiene permiso para evaluar equipos.'], 403);
}

try {
    $input = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($input)) $input = $_POST;
    $code = trim((string)($input['code'] ?? ''));
    if ($code === '' || mb_strlen($code) > 50) {
        criticidad_out(['success' => false, 'error' => 'Codigo SAP del equipo no valido.'], 400);
    }

    $factorMap = [
        'F1_EVA' => 'U_F1_EVA',
        'R_EVA' => 'U_R_EVA',
        'SHA_EVA' => 'U_SHA_EVA',
        'IC_EVA' => 'U_IC_EVA',
        'IP_EVA' => 'U_IP_EVA',
        'BM_EVA' => 'U_BM_EVA',
        'CM_EVA' => 'U_CM_EVA',
    ];
    $requiredColumns = array_merge(array_values($factorMap), ['U_RIESGO_EVA', 'U_Nivel_Criterio']);
    $pdo = getHanaPdo();
    $missing = [];
    foreach ($requiredColumns as $column) {
        if (!criticidad_column_exists($pdo, $column)) $missing[] = $column;
    }
    if ($missing) {
        criticidad_out([
            'success' => false,
            'error' => 'Falta crear campos de evaluacion en @TAMA_EQUIPOS_TM.',
            'campos_faltantes' => $missing,
        ], 409);
    }

    $scores = [];
    foreach ($factorMap as $inputName => $column) {
        $raw = $input[$inputName] ?? null;
        if (filter_var($raw, FILTER_VALIDATE_INT) === false) {
            criticidad_out(['success' => false, 'error' => "Complete el factor {$inputName} con un valor del 1 al 5."], 400);
        }
        $score = (int)$raw;
        if ($score < 1 || $score > 5) {
            criticidad_out(['success' => false, 'error' => "El factor {$inputName} debe estar entre 1 y 5."], 400);
        }
        $scores[$inputName] = $score;
    }

    $frequencyRelevanceAverage = ($scores['F1_EVA'] + $scores['R_EVA']) / 2;
    $impactAverage = ($scores['SHA_EVA'] + $scores['IC_EVA'] + $scores['IP_EVA'] + $scores['BM_EVA'] + $scores['CM_EVA']) / 5;
    $risk = round(($frequencyRelevanceAverage * $impactAverage / 25) * 100, 2);
    $level = $risk > 36.99 ? 'ALTO' : ($risk >= 16.99 ? 'MEDIO' : 'BAJO');

    $codeSql = "'" . str_replace("'", "''", $code) . "'";
    $exists = $pdo->query(
        'SELECT COUNT(*) AS "CNT" FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" WHERE "Code" = ' . $codeSql
    )->fetch();
    if ((int)($exists['CNT'] ?? 0) !== 1) {
        criticidad_out(['success' => false, 'error' => 'El equipo indicado no existe en SAP.'], 404);
    }

    $sets = [];
    foreach ($factorMap as $inputName => $column) $sets[] = '"' . $column . '" = ' . $scores[$inputName];
    $sets[] = '"U_RIESGO_EVA" = ' . number_format($risk, 2, '.', '');
    $sets[] = '"U_Nivel_Criterio" = \'' . $level . '\'';
    $pdo->exec(
        'UPDATE "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" SET ' . implode(', ', $sets)
        . ' WHERE "Code" = ' . $codeSql
    );

    criticidad_out([
        'success' => true,
        'message' => 'Evaluacion de criticidad guardada en SAP.',
        'code' => $code,
        'puntajes' => $scores,
        'riesgo' => $risk,
        'nivel' => $level,
    ]);
} catch (Throwable $error) {
    criticidad_out(['success' => false, 'error' => 'No se pudo guardar la evaluacion de criticidad.'], 500);
}
