<?php
/**
 * API: Resumen completo de un proyecto OT (todos los módulos)
 * GET ?ot=OTC-XXXXX
 * Responde: {
 *   ot, proyecto, estado, fecha_inicio,
 *   mod1: { total_dias, items:[{nombre,dias,fecha_inicio,fecha_fin}] },
 *   mod2: { total_dias, fecha_inicio, items:[{cod_plano,desc_plano,dias,fecha_inicio,fecha_fin}] },
 *   mod3: { total_dias, fecha_inicio, items:[{cod_plano,desc_mod3,dias}] }
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
    if ($ot === '') {
        throw new RuntimeException('Parámetro ot requerido.');
    }
    $ot_esc = $db->escape($ot);

    // ── Cabecera OT ───────────────────────────────────────────────────────────
    $cab = find_by_sql("SELECT * FROM fh_ing_planos_cab WHERE ot = '$ot_esc' LIMIT 1");
    $estado      = isset($cab[0]['estado'])      ? (int)$cab[0]['estado']      : -1;
    $fecha_inicio= isset($cab[0]['fecha_inicio']) ? $cab[0]['fecha_inicio']     : null;
    $proyecto    = '';

    $ot_det = find_by_sql("
        SELECT proyecto FROM fh_ing_ot_detalle
        WHERE ot = '$ot_esc'
        ORDER BY id DESC LIMIT 1
    ");
    if (!empty($ot_det[0]['proyecto'])) {
        $proyecto = $ot_det[0]['proyecto'];
    }

    // ── Módulo 1: actividades ─────────────────────────────────────────────────
    $m1_rows=[]; $m2_rows=[]; $m1_total=0; $m2_total=0; $fecha_inicio_mod2=null;
    $filas = find_by_sql("SELECT p.*, a.nombre AS actividad_nombre, a.orden AS actividad_orden
        FROM pln_dtll_Ing p INNER JOIN fh_ing_actividades a ON a.id=p.actividad_id
        WHERE p.ot='$ot_esc' AND a.activo=1 ORDER BY a.orden, a.id, p.orden, p.id_planos");
    $grupos=[];
    foreach ($filas as $r) {
        $aid=(int)$r['actividad_id'];
        if (!isset($grupos[$aid])) $grupos[$aid]=['actividad_id'=>$aid,'nombre'=>$r['actividad_nombre'],'planos'=>[]];
        $grupos[$aid]['planos'][]=$r;
    }
    $cronograma=fh_schedule_activities(array_values($grupos), $fecha_inicio ?: date('Y-m-d'));
    foreach ($cronograma as $a) {
        $m1_rows[]=['nombre'=>$a['nombre'],'dias'=>$a['total_dias'],'horas'=>$a['total_horas'],'fecha_inicio'=>$a['fecha_inicio'],'fecha_fin'=>$a['fecha_fin']];
        $m1_total+=$a['total_dias'];
        foreach ($a['planos'] as $p) {
            $p['dias']=(float)$p['dias']; $p['actividad_nombre']=$a['nombre'];
            $m2_rows[]=$p; $m2_total+=$p['dias'];
            if (!$fecha_inicio_mod2 && $p['fecha_inicio']) $fecha_inicio_mod2=$p['fecha_inicio'];
        }
    }

    $m3_rows = [];
    $m3_total = 0;
    $fecha_inicio_mod3 = null;

    // Calcular fecha_inicio_mod3 = última fecha_fin de Mod2 + 1 día
    $ultimo_m2 = find_by_sql("
        SELECT fecha_fin FROM pln_dtll_Ing
        WHERE ot = '$ot_esc' AND fecha_fin IS NOT NULL
        ORDER BY fecha_fin DESC LIMIT 1
    ");
    if (!empty($ultimo_m2[0]['fecha_fin'])) {
        $dt = new DateTime($ultimo_m2[0]['fecha_fin']);
        $dt->modify('+1 day');
        $fecha_inicio_mod3 = fh_work_date($dt->format('Y-m-d'),0);
    }

    $det3 = find_by_sql("
        SELECT p.cod_plano, m.desc_mod3, m.dias
        FROM pln_mod3_Ing m
        INNER JOIN pln_dtll_Ing p ON p.id_planos = m.id_planos
        WHERE m.ot='$ot_esc' AND m.id_mod3=(SELECT MAX(v.id_mod3) FROM pln_mod3_Ing v WHERE v.id_planos=m.id_planos)
        ORDER BY p.orden ASC, p.id_planos ASC
    ");
    foreach ((array)$det3 as $r) {
        $m3_rows[] = [
            'cod_plano' => $r['cod_plano'],
            'desc_mod3' => $r['desc_mod3'],
            'dias'      => (float)$r['dias'],
        ];
        $m3_total += (float)$r['dias'];
    }

    ob_end_clean();
    echo json_encode([
        'success'   => true,
        'ot'        => $ot,
        'proyecto'  => $proyecto,
        'estado'    => $estado,
        'fecha_inicio' => $fecha_inicio,
        'mod1' => [
            'total_dias'   => $m1_total,
            'fecha_inicio' => !empty($m1_rows) ? $m1_rows[0]['fecha_inicio'] : null,
            'items'        => $m1_rows,
        ],
        'mod2' => [
            'total_dias'   => $m2_total,
            'fecha_inicio' => $fecha_inicio_mod2,
            'items'        => $m2_rows,
        ],
        'mod3' => [
            'total_dias'   => $m3_total,
            'fecha_inicio' => $fecha_inicio_mod3,
            'items'        => $m3_rows,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
