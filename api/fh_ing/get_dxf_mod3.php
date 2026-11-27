<?php
/**
 * API: Servir DXF raw de Módulo 3 para el viewer three-dxf.
 * GET: ?file=savedname  → devuelve text/plain con el contenido DXF
 * Si el archivo es .dwg intenta buscar un .dxf hermano con mismo nombre base.
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

function _jsonError(int $code, string $msg): void {
    ob_end_clean();
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    $file = isset($_GET['file']) ? basename($_GET['file']) : '';
    if (!$file) { _jsonError(400, 'Nombre de archivo inválido.'); }

    if (!preg_match('/^[a-z0-9_\-\.]+$/i', $file)) {
        _jsonError(400, 'Nombre de archivo no permitido.');
    }

    $uploadDir = rtrim(BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
               . DIRECTORY_SEPARATOR . 'FH_ING'
               . DIRECTORY_SEPARATOR . 'planos_mod3', '/\\') . DIRECTORY_SEPARATOR;

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($ext === 'dxf') {
        $dxfPath = $uploadDir . $file;
    } else {
        // Buscar .dxf con mismo nombre base
        $baseName = pathinfo($file, PATHINFO_FILENAME);
        $dxfPath  = $uploadDir . $baseName . '.dxf';
    }

    // Path traversal guard
    $realDir = realpath($uploadDir);
    $realDxf = realpath($dxfPath);
    if (!$realDir || !$realDxf || strpos($realDxf, $realDir) !== 0) {
        _jsonError(404, 'DXF no encontrado.');
    }

    if (!is_file($realDxf)) {
        _jsonError(404, 'DXF no encontrado para este archivo.');
    }

    ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: private, max-age=86400');
    readfile($realDxf);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}
