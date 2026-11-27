<?php
/**
 * API: Guardar observación para un plano (FH ING Módulo 2)
 * POST JSON: { id_planos, ot, cod_plano, texto }
 * Responde: { success, obs: {id, texto, usuario, fecha} }
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'JSON inválido']);
        exit;
    }

    $id_planos = isset($body['id_planos']) ? (int)$body['id_planos'] : 0;
    $ot        = isset($body['ot'])        ? trim($body['ot'])        : '';
    $cod_plano = isset($body['cod_plano']) ? trim($body['cod_plano']) : '';
    $texto     = isset($body['texto'])     ? trim($body['texto'])     : '';

    if ($id_planos <= 0) {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'id_planos inválido']);
        exit;
    }
    if ($texto === '') {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'El texto no puede estar vacío']);
        exit;
    }
    if (mb_strlen($texto) > 2000) {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'El texto excede 2000 caracteres']);
        exit;
    }

    // Obtener nombre del usuario actual
    $cu      = current_user();
    $usuario = $cu ? ($cu['name'] ?? ($cu['username'] ?? 'Usuario')) : 'Usuario';

    require_once __DIR__ . '/obs_modulo.php';
    fh_obs_modulo_schema();
    $modulo=((int)($body['modulo']??1)===2)?2:1;
    global $db;
    $id_planos_esc = (int)$id_planos;
    $ot_esc        = $db->escape($ot);
    $cod_esc       = $db->escape($cod_plano);
    $texto_esc     = $db->escape($texto);
    $usuario_esc   = $db->escape($usuario);

    $db->query(
        "INSERT INTO fh_ing_plano_obs (id_planos, ot, cod_plano, texto, usuario, modulo, created_at)
         VALUES ($id_planos_esc, '$ot_esc', '$cod_esc', '$texto_esc', '$usuario_esc', $modulo, NOW())"
    );
    $newId = $db->insert_id();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'obs' => [
            'id'      => $newId,
            'texto'   => $texto,
            'usuario' => $usuario,
            'fecha'   => date('d/m/Y H:i'),
        ],
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno']);
}
