<?php
/**
 * API: Obtener actividades con planos anidados — Vista combinada FH_ING
 * GET ?ot=OTC-XXXX
 *
 * Responde:
 * {
 *   ot, fecha_inicio, estado,
 *   actividades: [
 *     { actividad_id, nombre, orden, total_dias, fecha_inicio, fecha_fin,
 *       planos: [{ id_planos, cod_plano, desc_plano, arch_plano, arch_original, dias, fecha_inicio, fecha_fin }] }
 *   ]
 * }
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once __DIR__ . '/calendario.php';

    $ot = isset($_GET['ot']) ? trim($_GET['ot']) : '';
    if ($ot === '') throw new RuntimeException('Parámetro ot requerido.');

    $ot_esc = $db->escape($ot);

    // ── Cabecera del cronograma ──────────────────────────────────────
    $cab = find_by_sql("SELECT id, fecha_inicio, estado FROM fh_ing_planos_cab WHERE ot = '$ot_esc' LIMIT 1");
    $fecha_inicio = !empty($cab[0]['fecha_inicio']) ? $cab[0]['fecha_inicio'] : date('Y-m-d');
    $estado       = isset($cab[0]['estado']) ? (int)$cab[0]['estado'] : 0;

    // ── Catálogo de actividades ──────────────────────────────────────
    $acts = find_by_sql("SELECT id, nombre, orden FROM fh_ing_actividades WHERE activo = 1 ORDER BY orden ASC");

    // ── Planos de esta OT (todos, con actividad_id) ──────────────────
    $planos_all = find_by_sql("
        SELECT id_planos, actividad_id, cod_plano, desc_plano,
               arch_plano, arch_original, dias, fecha_inicio, fecha_fin, orden
        FROM pln_dtll_Ing
        WHERE ot = '$ot_esc'
        ORDER BY actividad_id ASC, orden ASC, id_planos ASC
    ");

    // Agrupar planos por actividad_id
    $planos_by_act = [];
    foreach (is_array($planos_all) ? $planos_all : [] as $p) {
        $aid = (int)$p['actividad_id'];
        $planos_by_act[$aid][] = [
            'id_planos'    => (int)$p['id_planos'],
            'cod_plano'    => $p['cod_plano'],
            'desc_plano'   => $p['desc_plano'],
            'arch_plano'   => $p['arch_plano'],
            'arch_original'=> $p['arch_original'],
            'dias'         => (float)$p['dias'],
            'fecha_inicio' => $p['fecha_inicio'],
            'fecha_fin'    => $p['fecha_fin'],
        ];
    }

    // ── Fechas calculadas por actividad (fh_ing_planos_det) ──────────
    $det_by_act = [];
    if (!empty($cab[0]['id'])) {
        $cab_id = (int)$cab[0]['id'];
        $det_rows = find_by_sql("
            SELECT actividad_id, dias, fecha_inicio, fecha_fin
            FROM fh_ing_planos_det
            WHERE cab_id = $cab_id
        ");
        foreach (is_array($det_rows) ? $det_rows : [] as $d) {
            $det_by_act[(int)$d['actividad_id']] = $d;
        }
    }

    // ── Construir respuesta ──────────────────────────────────────────
    $actividades = [];
    foreach (is_array($acts) ? $acts : [] as $act) {
        $aid    = (int)$act['id'];
        $planos = $planos_by_act[$aid] ?? [];
        $total  = 0;
        foreach ($planos as $p) $total += (float)$p['dias'];
        $det    = $det_by_act[$aid] ?? null;

        $actividades[] = [
            'actividad_id'  => $aid,
            'nombre'        => $act['nombre'],
            'orden'         => (int)$act['orden'],
            'total_dias'    => (float)$total,
            'fecha_inicio'  => $det ? $det['fecha_inicio'] : null,
            'fecha_fin'     => $det ? $det['fecha_fin']    : null,
            'planos'        => array_values($planos),
        ];
    }

    $actividades = fh_schedule_activities($actividades, $fecha_inicio);
    ob_end_clean();
    echo json_encode([
        'ot'           => $ot,
        'fecha_inicio' => $fecha_inicio,
        'estado'       => $estado,
        'actividades'  => $actividades,
    ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
