<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/../../Reportes/_xlsx_corporativo.php';

sgsi_ensure_schema();
require_permiso('riesgos_sgsi', 'ver');

$onlyId = max(0, (int)($_GET['id'] ?? 0));
$where = $onlyId > 0 ? ' WHERE r.IdRiesgo=' . $onlyId : '';
$risks = find_by_sql("SELECT r.* FROM sgsi_riesgos r{$where} ORDER BY r.Codigo,r.IdRiesgo");
$assets = sgsi_asset_catalog();

function sgsi_export_lines(array $rows, callable $formatter): string {
    $lines = [];
    foreach ($rows as $row) $lines[] = $formatter($row);
    return implode("\n", array_filter($lines, static fn($line) => trim((string)$line) !== ''));
}

function sgsi_latest_evaluation(int $riskId, string $type): ?array {
    global $db;
    $typeSql = sgsi_q($type);
    return $db->fetch_assoc($db->query(
        "SELECT * FROM sgsi_evaluaciones WHERE IdRiesgo={$riskId} AND Tipo={$typeSql} " .
        "ORDER BY FechaEvaluacion DESC,IdEvaluacion DESC LIMIT 1"
    )) ?: null;
}

$rows = [];
foreach ($risks as $risk) {
    $riskId = (int)$risk['IdRiesgo'];
    $relations = find_by_sql("SELECT * FROM sgsi_riesgo_activos WHERE IdRiesgo={$riskId} ORDER BY EsPrincipal DESC,IdRelacion");
    $linkedAssets = [];
    $principal = null;
    foreach ($relations as $relation) {
        $key = $relation['TipoActivo'] . ':' . (int)$relation['IdActivo'];
        if (!isset($assets[$key])) continue;
        $asset = $assets[$key];
        $asset['principal'] = (int)$relation['EsPrincipal'] === 1;
        $linkedAssets[] = $asset;
        if ($asset['principal']) $principal = $asset;
    }

    $controls = find_by_sql(
        "SELECT c.* FROM sgsi_riesgo_controles rc INNER JOIN sgsi_controles c ON c.IdControl=rc.IdControl " .
        "WHERE rc.IdRiesgo={$riskId} ORDER BY c.Codigo"
    );
    $plans = find_by_sql("SELECT * FROM sgsi_planes WHERE IdRiesgo={$riskId} ORDER BY Codigo,IdPlan");
    $efficacies = find_by_sql("SELECT * FROM sgsi_eficacias WHERE IdRiesgo={$riskId} ORDER BY FechaVerificacion,IdEficacia");
    $inherent = sgsi_latest_evaluation($riskId, 'INHERENTE');
    $residual = sgsi_latest_evaluation($riskId, 'RESIDUAL');
    $acceptance = $residual ? $db->fetch_assoc($db->query(
        'SELECT * FROM sgsi_aceptaciones WHERE IdRiesgo=' . $riskId . ' AND IdEvaluacion=' . (int)$residual['IdEvaluacion'] .
        ' ORDER BY FechaDecision DESC,IdAceptacion DESC LIMIT 1'
    )) : null;

    $controlText = sgsi_export_lines($controls, static function (array $control): string {
        $parts = [$control['Codigo'] . ': ' . $control['Descripcion']];
        $parts[] = 'Resp.: ' . $control['Responsable'];
        if (trim((string)$control['Frecuencia']) !== '') $parts[] = 'Frec.: ' . $control['Frecuencia'];
        $parts[] = 'Estado: ' . str_replace('_', ' ', $control['Estado']);
        if (trim((string)$control['Evidencia']) !== '') $parts[] = 'Evid.: ' . $control['Evidencia'];
        return implode(' | ', $parts);
    });
    $planText = sgsi_export_lines($plans, static function (array $plan): string {
        $parts = [$plan['Codigo'] . ': ' . $plan['Accion'], 'Resp.: ' . $plan['Responsable']];
        if ($plan['FechaProgramada']) $parts[] = 'Fecha: ' . $plan['FechaProgramada'];
        $parts[] = 'Estado: ' . str_replace('_', ' ', $plan['Estado']);
        $parts[] = 'Aprob.: ' . $plan['Aprobacion'];
        if (trim((string)$plan['Evidencia']) !== '') $parts[] = 'Evid.: ' . $plan['Evidencia'];
        return implode(' | ', $parts);
    });
    $efficacyText = sgsi_export_lines($efficacies, static function (array $efficacy): string {
        $parts = [$efficacy['FechaVerificacion'] . ' · ' . $efficacy['TipoObjetivo'] . ' #' . $efficacy['IdObjetivo']];
        $parts[] = $efficacy['Resultado'];
        $parts[] = 'Resp.: ' . $efficacy['Responsable'];
        $parts[] = 'Evid.: ' . $efficacy['Evidencia'];
        if (trim((string)$efficacy['Observacion']) !== '') $parts[] = $efficacy['Observacion'];
        return implode(' | ', $parts);
    });

    $rows[] = [
        'risk_id' => $risk['Codigo'],
        'asset_ids' => sgsi_export_lines($linkedAssets, static fn(array $asset): string => $asset['code'] . ($asset['principal'] ? ' (principal)' : '')),
        'asset_names' => sgsi_export_lines($linkedAssets, static fn(array $asset): string => $asset['name']),
        'asset_criticality' => sgsi_export_lines($linkedAssets, static fn(array $asset): string => $asset['code'] . ': ' . $asset['criticality']),
        'asset_type' => $principal['category'] ?? '',
        'process' => $risk['Proceso'],
        'scenario' => $risk['Escenario'],
        'owner' => $risk['DuenoRiesgo'],
        'status' => str_replace('_', ' ', $risk['Estado']),
        'inh_date' => $inherent['FechaEvaluacion'] ?? '',
        'inh_probability' => $inherent['Probabilidad'] ?? '',
        'inh_impact' => $inherent['Impacto'] ?? '',
        'inh_score' => $inherent['Puntaje'] ?? '',
        'inh_level' => $inherent['Nivel'] ?? 'PENDIENTE',
        'inh_evaluator' => $inherent['Evaluador'] ?? '',
        'inh_reason' => $inherent['Justificacion'] ?? '',
        'controls' => $controlText,
        'treatment' => $risk['Tratamiento'] ?: 'PENDIENTE',
        'plans' => $planText,
        'res_date' => $residual['FechaEvaluacion'] ?? '',
        'res_probability' => $residual['Probabilidad'] ?? '',
        'res_impact' => $residual['Impacto'] ?? '',
        'res_score' => $residual['Puntaje'] ?? '',
        'res_level' => $residual['Nivel'] ?? 'PENDIENTE',
        'res_evaluator' => $residual['Evaluador'] ?? '',
        'res_reason' => $residual['Justificacion'] ?? '',
        'acceptance' => $acceptance ? implode(' | ', [
            $acceptance['Decision'], $acceptance['Aprobador'], $acceptance['FechaDecision'],
            $acceptance['Justificacion'], 'Revisión: ' . $acceptance['FechaRevision'],
        ]) : '',
        'efficacy' => $efficacyText,
        'review_date' => $risk['FechaRevision'] ?? '',
        'observations' => $risk['Observaciones'] ?? '',
    ];
}

$columns = [
    ['key'=>'risk_id','label'=>'ID DEL RIESGO','width'=>17],
    ['key'=>'asset_ids','label'=>'ID(S) DE ACTIVO','width'=>23],
    ['key'=>'asset_names','label'=>'ACTIVO(S) VINCULADO(S)','width'=>38],
    ['key'=>'asset_criticality','label'=>'CRITICIDAD DEL ACTIVO','width'=>23],
    ['key'=>'asset_type','label'=>'TIPO DE ACTIVO','width'=>20],
    ['key'=>'process','label'=>'PROCESO','width'=>22],
    ['key'=>'scenario','label'=>'ESCENARIO DE RIESGO','width'=>46],
    ['key'=>'owner','label'=>'DUEÑO DEL RIESGO','width'=>24],
    ['key'=>'status','label'=>'ESTADO','width'=>18],
    ['key'=>'inh_date','label'=>'FECHA','width'=>13],
    ['key'=>'inh_probability','label'=>'PROBABILIDAD (1-5)','width'=>15,'type'=>'number'],
    ['key'=>'inh_impact','label'=>'IMPACTO (1-5)','width'=>14,'type'=>'number'],
    ['key'=>'inh_score','label'=>'PUNTAJE','width'=>11,'type'=>'number'],
    ['key'=>'inh_level','label'=>'NIVEL','width'=>14],
    ['key'=>'inh_evaluator','label'=>'EVALUADOR','width'=>23],
    ['key'=>'inh_reason','label'=>'JUSTIFICACIÓN','width'=>40],
    ['key'=>'controls','label'=>'CONTROLES EXISTENTES Y EVIDENCIA','width'=>48],
    ['key'=>'treatment','label'=>'DECISIÓN DE TRATAMIENTO','width'=>21],
    ['key'=>'plans','label'=>'PLANES DE TRATAMIENTO','width'=>48],
    ['key'=>'res_date','label'=>'FECHA','width'=>13],
    ['key'=>'res_probability','label'=>'PROBABILIDAD (1-5)','width'=>15,'type'=>'number'],
    ['key'=>'res_impact','label'=>'IMPACTO (1-5)','width'=>14,'type'=>'number'],
    ['key'=>'res_score','label'=>'PUNTAJE','width'=>11,'type'=>'number'],
    ['key'=>'res_level','label'=>'NIVEL','width'=>14],
    ['key'=>'res_evaluator','label'=>'EVALUADOR','width'=>23],
    ['key'=>'res_reason','label'=>'JUSTIFICACIÓN','width'=>40],
    ['key'=>'acceptance','label'=>'ACEPTACIÓN / RECHAZO','width'=>45],
    ['key'=>'efficacy','label'=>'VERIFICACIÓN DE EFICACIA Y EVIDENCIA','width'=>48],
    ['key'=>'review_date','label'=>'PRÓXIMA REVISIÓN','width'=>16],
    ['key'=>'observations','label'=>'OBSERVACIONES','width'=>42],
];

try {
    tx_export([[
        'name' => 'Matriz completa SGSI',
        'title' => 'MATRIZ COMPLETA DE RIESGOS DEL SGSI',
        'code' => 'T-GI-F-03',
        'version' => '01',
        'date' => date('d/m/Y'),
        'elaborado' => 'SGSI',
        'revisado' => 'TI',
        'aprobado' => 'GG',
        'layoutCount' => count($columns),
        'columns' => $columns,
        'groups' => [
            ['start'=>1,'end'=>9,'label'=>'IDENTIFICACIÓN'],
            ['start'=>10,'end'=>17,'label'=>'EVALUACIÓN INHERENTE'],
            ['start'=>18,'end'=>19,'label'=>'TRATAMIENTO'],
            ['start'=>20,'end'=>27,'label'=>'EVALUACIÓN RESIDUAL'],
            ['start'=>28,'end'=>30,'label'=>'SEGUIMIENTO'],
        ],
        'rows' => $rows,
        'rowHeight' => 78,
    ]], 'Matriz_Completa_SGSI_' . date('Y-m-d_His') . '.xlsx', dirname(__DIR__, 2) . '/assets/img/logo.png');
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'No se pudo generar la matriz SGSI: ' . sgsi_h($exception->getMessage());
}
