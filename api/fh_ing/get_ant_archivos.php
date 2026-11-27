<?php
/**
 * API: Listar archivos de antecedentes (FH ING — Actividad 1)
 * GET: ?ot=OT-XXXX[&actividad_id=1]
 * Responde: { success, archivos: [...] }
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    $ot           = isset($_GET['ot'])           ? trim($_GET['ot'])           : '';
    $actividad_id = isset($_GET['actividad_id']) ? (int)$_GET['actividad_id'] : 1;

    if ($ot === '') {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Parámetro ot requerido']);
        exit;
    }

    global $db;
    $ot_esc  = $db->escape($ot);
    $act_int = (int)$actividad_id;

    $rows = find_by_sql(
        "SELECT id, ot, actividad_id, nombre_original, nombre_guardado,
                tipo_mime, tamano, descripcion, usuario,
                DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS fecha_str
         FROM fh_ing_ant_archivos
         WHERE ot = '$ot_esc' AND actividad_id = $act_int
         ORDER BY created_at ASC"
    );

    ob_end_clean();
    echo json_encode([
        'success'  => true,
        'archivos' => is_array($rows) ? $rows : [],
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno']);
}
