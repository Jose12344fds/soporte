<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion_hana.php';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';

function ato_out(array $data, int $status = 200): void {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function ato_date($value, bool $required = false): ?string {
    $value = trim((string)$value);
    if ($value === '') {
        if ($required) ato_out(['error' => 'La fecha de inicio es obligatoria.'], 400);
        return null;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        ato_out(['error' => 'El formato de las fechas no es valido.'], 400);
    }
    return $value;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ato_out(['error' => 'Metodo no permitido.'], 405);
}
if (!$session->isUserLoggedIn(false)) {
    ato_out(['error' => 'No autorizado.'], 401);
}
if (!tiene_permiso('mantenimientos', 'editar')) {
    ato_out(['error' => 'No tiene permiso para editar ordenes de trabajo.'], 403);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    $docentry = (int)($input['docentry'] ?? 0);
    $tecnico = strtoupper(trim((string)($input['tecnico'] ?? '')));
    $fechaInicio = ato_date($input['fecha_inicio'] ?? '', true);
    $fechaFin = ato_date($input['fecha_fin'] ?? '', false);
    if ($docentry <= 0 || $tecnico === '') {
        ato_out(['error' => 'La OT y el tecnico son obligatorios.'], 400);
    }
    if ($fechaFin !== null && $fechaFin < $fechaInicio) {
        ato_out(['error' => 'La fecha de fin no puede ser anterior a la fecha de inicio.'], 400);
    }

    $pdo = getHanaPdo();
    $techStmt = $pdo->prepare(
        'SELECT "Code","Name" FROM "SBO_TAMAING_PRODUC"."@TAMA_TECNICOS"'
        . ' WHERE "Code" = ? AND "U_Activo" = \'Y\''
    );
    $techStmt->execute([$tecnico]);
    $tech = $techStmt->fetch(PDO::FETCH_ASSOC);
    if (!$tech) {
        ato_out(['error' => 'El tecnico seleccionado no existe o esta inactivo.'], 409);
    }

    $otStmt = $pdo->prepare(
        'SELECT "DocEntry","DocNum","Status","Canceled" FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO"'
        . ' WHERE "DocEntry" = ?'
    );
    $otStmt->execute([$docentry]);
    $ot = $otStmt->fetch(PDO::FETCH_ASSOC);
    if (!$ot) {
        ato_out(['error' => 'La orden de trabajo no existe.'], 404);
    }
    if (($ot['Status'] ?? '') === 'C') {
        ato_out(['error' => 'La orden de trabajo esta cerrada y no puede reasignarse.'], 409);
    }
    if (($ot['Canceled'] ?? 'N') === 'Y') {
        ato_out(['error' => 'La orden de trabajo esta anulada y no puede modificarse.'], 409);
    }

    $update = $pdo->prepare(
        'UPDATE "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO"'
        . ' SET "U_Tecnico" = ?, "U_FechaInicio" = ?, "U_FechaFin" = ?, "UpdateDate" = ?, "UpdateTime" = ?'
        . ' WHERE "DocEntry" = ? AND "Status" <> \'C\''
    );
    $update->execute([$tecnico, $fechaInicio, $fechaFin, date('Y-m-d'), (int)date('Hi'), $docentry]);
    $verify = $pdo->prepare(
        'SELECT "U_Tecnico",SUBSTRING(TO_VARCHAR("U_FechaInicio"),1,10) AS "FechaInicio",'
        . ' SUBSTRING(TO_VARCHAR("U_FechaFin"),1,10) AS "FechaFin"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO"'
        . ' WHERE "DocEntry" = ? AND "Status" <> \'C\''
    );
    $verify->execute([$docentry]);
    $saved = $verify->fetch(PDO::FETCH_ASSOC);
    $savedStart = substr(trim((string)($saved['FechaInicio'] ?? '')), 0, 10);
    $savedEnd = substr(trim((string)($saved['FechaFin'] ?? '')), 0, 10);
    if (!$saved
        || strtoupper(trim((string)($saved['U_Tecnico'] ?? ''))) !== $tecnico
        || $savedStart !== $fechaInicio
        || $savedEnd !== ($fechaFin ?? '')
    ) {
        ato_out(['error' => 'La OT cambio durante el proceso. Actualice el listado.'], 409);
    }

    ato_out([
        'success' => true,
        'docentry' => $docentry,
        'docnum' => $ot['DocNum'] ?? null,
        'tecnico' => $tecnico,
        'tecnico_nombre' => $tech['Name'] ?? $tecnico,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
    ]);
} catch (Throwable $e) {
    error_log('Error asignando tecnico OT: ' . $e->getMessage());
    ato_out(['error' => 'No se pudo actualizar el responsable en SAP.'], 500);
}
