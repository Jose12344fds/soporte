<?php
/**
 * API: Descargar / visualizar archivo de antecedentes (FH ING)
 * GET: ?id=N[&inline=1]
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    if (!isset($_SESSION['user_id'])) {
        ob_end_clean();
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }

    $id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $inline = !empty($_GET['inline']);

    if ($id <= 0) {
        ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    global $db;
    $row = find_by_sql(
        "SELECT nombre_original, nombre_guardado, tipo_mime
         FROM fh_ing_ant_archivos WHERE id = $id LIMIT 1"
    );
    if (empty($row)) {
        ob_end_clean();
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Archivo no encontrado']);
        exit;
    }

    $origName  = $row[0]['nombre_original'];
    $savedName = $row[0]['nombre_guardado'];
    $mime      = $row[0]['tipo_mime'] ?: 'application/octet-stream';

    $filePath = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
              . DIRECTORY_SEPARATOR . 'FH_ING'
              . DIRECTORY_SEPARATOR . 'antecedentes'
              . DIRECTORY_SEPARATOR . basename($savedName);

    if (!file_exists($filePath)) {
        ob_end_clean();
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Archivo físico no encontrado']);
        exit;
    }

    ob_end_clean();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    $disp = $inline ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disp . '; filename="' . rawurlencode($origName) . '"');
    header('Cache-Control: private, max-age=3600');
    readfile($filePath);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Error interno']);
}
