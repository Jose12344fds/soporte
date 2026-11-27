<?php
/**
 * API: Obtener filas del Módulo 2 (pln_dtll_Ing) con datos del Módulo 3 (pln_mod3_Ing)
 * GET ?ot=OTC-XXXXXX
 * Responde: { success, ot, rows: [{id_planos, cod_plano, desc_mod3, arch_plano, arch_original, dias, id_mod3}] }
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once __DIR__ . '/modulo2_helpers.php';

    $ot = isset($_GET['ot']) ? trim($_GET['ot']) : '';
    if ($ot === '') {
        throw new RuntimeException('El parámetro ot es requerido.');
    }

    $ot_esc = $db->escape($ot);

    $fecha_inicio_mod3 = fh_modulo2_inicio($ot);

    // Filas del Módulo 2 para esta OT, LEFT JOIN con Módulo 3
    $rows = find_by_sql("
        SELECT
            d.id_planos,
            d.cod_plano,
            COALESCE(m.id_mod3, 0)       AS id_mod3,
            COALESCE(m.desc_mod3, '')     AS desc_mod3,
            COALESCE(m.arch_plano, '')    AS arch_plano,
            COALESCE(m.arch_original, '') AS arch_original,
            COALESCE(m.dias, 0) / 8       AS dias
        FROM pln_dtll_Ing d
        INNER JOIN fh_ing_actividades a ON a.id=d.actividad_id
        LEFT JOIN pln_mod3_Ing m ON m.id_planos=d.id_planos AND m.id_mod3=(SELECT MAX(v.id_mod3) FROM pln_mod3_Ing v WHERE v.id_planos=m.id_planos)
        WHERE d.ot = '$ot_esc'
        ORDER BY a.orden ASC, d.orden ASC, d.id_planos ASC
    ");

    $rows=fh_modulo2_fechas(is_array($rows)?$rows:[], $fecha_inicio_mod3);
    ob_end_clean();
    echo json_encode([
        'success'            => true,
        'ot'                 => $ot,
        'fecha_inicio_mod3'  => $fecha_inicio_mod3,
        'unidad' => 'dias',
        'rows'               => is_array($rows) ? $rows : [],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
