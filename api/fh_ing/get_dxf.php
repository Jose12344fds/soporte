<?php
/**
 * API: Servir el archivo DXF raw para el viewer JavaScript (three-dxf).
 * GET: ?id=N  → devuelve text/plain con el contenido del DXF
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { _jsonError(400, 'ID inválido'); }

    $row = find_by_sql("SELECT arch_plano FROM pln_dtll_Ing WHERE id_planos = $id LIMIT 1");
    if (empty($row) || empty($row[0]['arch_plano'])) {
        _jsonError(404, 'No encontrado');
    }

    $savedName  = basename($row[0]['arch_plano']);
    $extSaved   = strtolower(pathinfo($savedName, PATHINFO_EXTENSION));
    $uploadsDir = rtrim(BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
                      . DIRECTORY_SEPARATOR . 'FH_ING'
                      . DIRECTORY_SEPARATOR . 'planos', '/\\');

    // Resolver ruta al DXF
    if ($extSaved === 'dxf') {
        $dxfPath = $uploadsDir . DIRECTORY_SEPARATOR . $savedName;
    } else {
        $dxfName = pathinfo($savedName, PATHINFO_FILENAME) . '.dxf';
        $dxfPath = $uploadsDir . DIRECTORY_SEPARATOR . $dxfName;
    }

    // Validar que está dentro del directorio permitido
    $sep    = DIRECTORY_SEPARATOR;
    $dirNorm = str_replace(['/', '\\'], $sep, $uploadsDir) . $sep;
    $fileNorm = str_replace(['/', '\\'], $sep, $dxfPath);
    if (strpos($fileNorm, $dirNorm) !== 0) { _jsonError(403, 'Acceso denegado'); }

    if (!is_file($dxfPath)) { _jsonError(404, 'DXF no encontrado'); }

    ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: private, max-age=86400');
    header('Access-Control-Allow-Origin: *');
    readfile($dxfPath);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}
