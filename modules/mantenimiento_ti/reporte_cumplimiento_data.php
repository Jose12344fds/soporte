<?php
declare(strict_types=1);

require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'ver');
header('Content-Type: application/json; charset=utf-8');

function mti_rep_fail(string $message, int $status = 500): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$year = (int)($_GET['anio'] ?? date('Y'));
if ($year < 2020 || $year > 2100) mti_rep_fail('El año solicitado no es válido.', 422);
$start = sprintf('%04d-01-01', $year);
$end = sprintf('%04d-12-31', $year);

try {
    $machineColumns = [];
    $columnResult = $db->query('SHOW COLUMNS FROM maquina');
    while ($column = $db->fetch_assoc($columnResult)) $machineColumns[strtolower((string)$column['Field'])] = (string)$column['Field'];
    $locationColumn = '';
    foreach (['ubicacion', 'sede', 'area', 'local', 'location'] as $candidate) {
        if (isset($machineColumns[$candidate])) { $locationColumn = $machineColumns[$candidate]; break; }
    }
    $locationSql = $locationColumn !== '' ? "q.`{$locationColumn}`" : "''";

    $rows = find_by_sql(
        "SELECT o.id ocurrencia_id,o.codigo ocurrencia_codigo,o.fecha_programada,o.estado ocurrencia_estado,"
        . " o.observacion ocurrencia_observacion,p.id plan_id,p.codigo plan_codigo,p.nombre plan_nombre,"
        . " p.frecuencia,p.IdMaquina,q.Host,q.Tipo_Activo,{$locationSql} ubicacion,"
        . " m.id orden_id,m.folio orden_folio,m.estado orden_estado,m.fecha_ejecucion,"
        . " m.tecnico_responsable,m.trabajo_realizado,m.diagnostico"
        . " FROM activos_mantenimiento_ocurrencias o"
        . " INNER JOIN activos_mantenimiento_planes p ON p.id=o.plan_id"
        . " INNER JOIN maquina q ON q.IdMaquina=p.IdMaquina"
        . " LEFT JOIN activos_mantenimientos m ON m.id=o.mantenimiento_id"
        . " WHERE o.fecha_programada BETWEEN '{$start}' AND '{$end}'"
        . " AND o.estado<>'CANCELADA' AND (m.id IS NULL OR m.estado<>'CANCELADO')"
        . " AND UPPER(COALESCE(p.tipo,'PREVENTIVO'))='PREVENTIVO'"
        . " ORDER BY q.Tipo_Activo,q.Host,o.fecha_programada,o.id"
    );

    $today = date('Y-m-d');
    $occurrenceByPlan = [];
    $types = [];
    $frequencies = [];
    $locations = [];
    foreach ($rows as &$row) {
        $planId = (int)$row['plan_id'];
        $occurrenceByPlan[$planId] = ($occurrenceByPlan[$planId] ?? 0) + 1;
        $row['numero_ocurrencia'] = $occurrenceByPlan[$planId];
        $completed = ($row['orden_estado'] ?? '') === 'COMPLETADO'
            || (empty($row['orden_id']) && ($row['ocurrencia_estado'] ?? '') === 'EJECUTADA');
        $row['estado_reporte'] = $completed ? 'COMPLETADO' : (($row['fecha_programada'] < $today) ? 'PENDIENTE' : 'FUTURO');
        $row['fecha_ejecucion'] = $completed ? ($row['fecha_ejecucion'] ?? '') : '';
        $row['observaciones'] = trim((string)($row['ocurrencia_observacion'] ?? ''));
        if ($row['observaciones'] === '') $row['observaciones'] = trim((string)($row['trabajo_realizado'] ?? ''));
        if ($row['observaciones'] === '') $row['observaciones'] = trim((string)($row['diagnostico'] ?? ''));
        $types[(string)($row['Tipo_Activo'] ?: 'Sin tipo')] = true;
        $frequencies[(string)($row['frecuencia'] ?: 'Sin frecuencia')] = true;
        $locations[(string)($row['ubicacion'] ?: 'Sin ubicación')] = true;
    }
    unset($row);
    $sortKeys = static function(array $values): array { $keys = array_keys($values); natcasesort($keys); return array_values($keys); };

    echo json_encode([
        'ok' => true,
        'year' => $year,
        'today' => $today,
        'location_source' => $locationColumn,
        'filters' => ['types' => $sortKeys($types), 'frequencies' => $sortKeys($frequencies), 'locations' => $sortKeys($locations)],
        'rows' => $rows,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('[MTI reporte cumplimiento] ' . $error->getMessage());
    mti_rep_fail('No se pudo preparar el reporte de cumplimiento.');
}
