<?php
/**
 * API: Descargar / Ver archivo histórico de Módulo 3 (FH_ING)
 * GET: ?id=N          → descarga
 * GET: ?id=N&ver=1    → abre inline (visor)
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        ob_end_clean();
        echo 'No autorizado';
        exit;
    }

    $id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $ver = isset($_GET['ver']) && $_GET['ver'] == '1';

    if ($id <= 0) {
        http_response_code(400);
        ob_end_clean();
        echo 'ID inválido.';
        exit;
    }

    global $db;

    $row = find_by_sql(
        "SELECT arch_plano, arch_original FROM fh_ing_plano_dwg_history
         WHERE id = $id AND source = 3 LIMIT 1"
    );
    if (empty($row) || empty($row[0]['arch_plano'])) {
        http_response_code(404);
        ob_end_clean();
        echo 'Archivo histórico no encontrado.';
        exit;
    }

    $savedName    = basename($row[0]['arch_plano']);
    $originalName = $row[0]['arch_original'] ?: $savedName;

    $uploadDir = rtrim(
        BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'FH_ING'
        . DIRECTORY_SEPARATOR . 'planos_mod3',
        '/\\'
    ) . DIRECTORY_SEPARATOR;

    $filePath = $uploadDir . $savedName;

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
        echo 'Archivo no encontrado en el servidor.';
        exit;
    }

    $ext = strtolower(pathinfo($savedName, PATHINFO_EXTENSION));
    $mimeMap = [
        'dwg'  => 'application/acad',
        'dxf'  => 'application/dxf',
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'dws'  => 'application/acad',
        'dwt'  => 'application/acad',
    ];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';

    ob_end_clean();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($realFile));
    if ($ver && in_array($mime, ['application/pdf', 'image/png', 'image/jpeg'], true)) {
        header('Content-Disposition: inline; filename="' . rawurlencode($originalName) . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . rawurlencode($originalName) . '"');
    }
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    readfile($realFile);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo 'Error interno.';
}
