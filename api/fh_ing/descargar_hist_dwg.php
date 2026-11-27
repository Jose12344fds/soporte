<?php
/**
 * API: Descargar / ver archivo histórico DWG (FH ING)
 * GET: ?id=N[&ver=1]
 *   id  = fh_ing_plano_dwg_history.id
 *   ver = 1  → inline (para CAD con plugin)
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    if (!isset($_SESSION['user_id'])) {
        ob_end_clean(); http_response_code(401);
        header('Content-Type: text/plain'); echo 'No autorizado.'; exit;
    }

    $id  = isset($_GET['id'])  ? (int)$_GET['id']  : 0;
    $ver = isset($_GET['ver']) && $_GET['ver'] == '1';

    if ($id <= 0) {
        ob_end_clean(); http_response_code(400);
        header('Content-Type: text/plain'); echo 'ID inválido.'; exit;
    }

    global $db;
    $row = find_by_sql(
        "SELECT arch_plano, arch_original FROM fh_ing_plano_dwg_history WHERE id = $id LIMIT 1"
    );
    if (empty($row) || empty($row[0]['arch_plano'])) {
        ob_end_clean(); http_response_code(404);
        header('Content-Type: text/plain'); echo 'Archivo histórico no encontrado.'; exit;
    }

    $savedName = basename($row[0]['arch_plano']);
    $origName  = $row[0]['arch_original'] ?: $savedName;

    $filePath = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
              . DIRECTORY_SEPARATOR . 'FH_ING'
              . DIRECTORY_SEPARATOR . 'planos'
              . DIRECTORY_SEPARATOR . $savedName;

    if (!is_file($filePath)) {
        ob_end_clean(); http_response_code(404);
        header('Content-Type: text/plain'); echo 'Archivo físico no encontrado.'; exit;
    }

    $ext  = strtolower(pathinfo($savedName, PATHINFO_EXTENSION));
    $mime_map = [
        'dwg'  => 'application/acad',
        'dxf'  => 'application/dxf',
        'dws'  => 'application/acad',
        'dwt'  => 'application/acad',
        'pdf'  => 'application/pdf',
    ];
    $mime = $mime_map[$ext] ?? 'application/octet-stream';
    $disp = $ver ? 'inline' : 'attachment';

    ob_end_clean();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: ' . $disp . '; filename="' . rawurlencode($origName) . '"');
    header('Cache-Control: private, max-age=3600');
    readfile($filePath);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Error interno.';
}
