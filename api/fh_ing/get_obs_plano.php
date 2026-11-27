<?php
/**
 * API: Obtener observaciones de un plano (FH ING Módulo 2)
 * GET: ?id_planos=N → JSON { success, obs: [{id, texto, usuario, created_at}] }
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

    $id_planos = isset($_GET['id_planos']) ? (int)$_GET['id_planos'] : 0;
    if ($id_planos <= 0) {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'id_planos inválido']);
        exit;
    }

    require_once __DIR__ . '/obs_modulo.php';
    fh_obs_modulo_schema();
    $modulo=((int)($_GET['modulo']??1)===2)?2:1;
    global $db;
    $rows = find_by_sql(
        "SELECT id, texto, usuario, DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS fecha
         FROM fh_ing_plano_obs
         WHERE modulo=$modulo AND id_planos = " . $id_planos . "
         ORDER BY created_at ASC"
    );

    $obs = array_map(function($r) {
        $obs_id = (int)$r['id'];
        $imgs   = find_by_sql(
            "SELECT id, nombre_original
             FROM fh_ing_obs_imagenes
             WHERE id_obs = $obs_id
             ORDER BY created_at ASC"
        );
        return [
            'id'       => $obs_id,
            'texto'    => $r['texto'],
            'usuario'  => $r['usuario'],
            'fecha'    => $r['fecha'],
            'imagenes' => is_array($imgs) ? array_map(function($im) {
                return ['id' => (int)$im['id'], 'nombre' => $im['nombre_original']];
            }, $imgs) : [],
        ];
    }, $rows ?: []);

    ob_end_clean();
    echo json_encode(['success' => true, 'obs' => $obs]);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno']);
}
