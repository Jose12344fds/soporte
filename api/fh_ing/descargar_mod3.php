<?php
/**
 * API: Descargar / Abrir archivo de Módulo 3 (FH_ING)
 * GET: ?file=savedname          → descarga
 * GET: ?file=savedname&ver=1    → abre inline
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    $file = isset($_GET['file']) ? basename($_GET['file']) : '';
    $ver  = isset($_GET['ver']) && $_GET['ver'] == '1';

    if (!$file) {
        http_response_code(400);
        ob_end_clean();
        echo 'Nombre de archivo inválido.';
        exit;
    }

    // Validar solo caracteres seguros (hex + timestamp + extensión)
    if (!preg_match('/^[a-z0-9_\-\.]+$/i', $file)) {
        http_response_code(400);
        ob_end_clean();
        echo 'Nombre de archivo no permitido.';
        exit;
    }

    $uploadDir = rtrim(BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
               . DIRECTORY_SEPARATOR . 'FH_ING'
               . DIRECTORY_SEPARATOR . 'planos_mod3', '/\\') . DIRECTORY_SEPARATOR;

    $filePath = $uploadDir . $file;

    // Path traversal guard
    $realDir  = realpath($uploadDir);
    $realFile = realpath($filePath);
    if (!$realDir || !$realFile || strpos($realFile, $realDir) !== 0) {
        http_response_code(403);
        ob_end_clean();
        echo 'Acceso denegado.';
        exit;
    }

    if (!is_file($realFile)) {
        http_response_code(404);
        ob_end_clean();
        echo 'Archivo no encontrado.';
        exit;
    }

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    $mimeMap = [
        'dwg'  => 'application/acad',
        'dxf'  => 'application/dxf',
        'pdf'  => 'application/pdf',
        'dws'  => 'application/acad',
        'dwt'  => 'application/acad',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';

    ob_end_clean();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($realFile));
    if ($ver) {
        header('Content-Disposition: inline; filename="' . addslashes($file) . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . addslashes($file) . '"');
    }
    header('Cache-Control: private, max-age=3600');
    readfile($realFile);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo 'Error interno.';
}
