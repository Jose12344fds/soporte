<?php
/**
 * API: Ver imagen referencial de anotación (inline)
 * GET: ?id=N
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
        exit;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        ob_end_clean();
        http_response_code(400);
        exit;
    }

    global $db;
    $row = find_by_sql(
        "SELECT nombre_original, nombre_guardado
         FROM fh_ing_obs_imagenes WHERE id = $id LIMIT 1"
    );
    if (empty($row)) {
        ob_end_clean();
        http_response_code(404);
        exit;
    }

    $origName  = $row[0]['nombre_original'];
    $savedName = $row[0]['nombre_guardado'];

    $filePath = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
              . DIRECTORY_SEPARATOR . 'FH_ING'
              . DIRECTORY_SEPARATOR . 'obs_imgs'
              . DIRECTORY_SEPARATOR . basename($savedName);

    if (!file_exists($filePath)) {
        ob_end_clean();
        http_response_code(404);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($filePath) ?: 'image/jpeg';

    ob_end_clean();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="' . rawurlencode($origName) . '"');
    header('Cache-Control: private, max-age=3600');
    readfile($filePath);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    exit;
}
