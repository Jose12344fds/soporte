<?php
/**
 * API: Descargar / Abrir archivo DWG de Detalle de Planos (FH_ING)
 * GET: ?id=N          → descarga (Content-Disposition: attachment)
 * GET: ?id=N&ver=1    → abre inline; el SO usa el programa registrado para .dwg (p.ej. TBM)
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    $id  = isset($_GET['id'])  ? (int)$_GET['id']  : 0;
    $ver = isset($_GET['ver']) && $_GET['ver'] == '1';

    if ($id <= 0) {
        http_response_code(400);
        ob_end_clean();
        echo 'ID inválido.';
        exit;
    }

    $row = find_by_sql("SELECT arch_plano, arch_original FROM pln_dtll_Ing WHERE id_planos = $id LIMIT 1");
    if (empty($row) || empty($row[0]['arch_plano'])) {
        http_response_code(404);
        ob_end_clean();
        echo 'Archivo no encontrado.';
        exit;
    }

    $savedName    = basename($row[0]['arch_plano']);
    $originalName = $row[0]['arch_original'] ?: $savedName;
    $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $filePath = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
              . DIRECTORY_SEPARATOR . 'FH_ING'
              . DIRECTORY_SEPARATOR . 'planos'
              . DIRECTORY_SEPARATOR . $savedName;

    if (!is_file($filePath)) {
        http_response_code(404);
        ob_end_clean();
        echo 'Archivo no existe en el servidor.';
        exit;
    }

    // MIME types por extensión
    $mimeMap = [
        'dwg' => 'application/acad',
        'dxf' => 'application/dxf',
        'pdf' => 'application/pdf',
        'dws' => 'application/acad',
        'dwt' => 'application/acad',
    ];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';

    ob_end_clean();

    if ($ver) {
        // Modo VER: el navegador/SO abre con el programa asociado (TBM, AutoCAD, etc.)
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($originalName) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
    } else {
        // Modo DESCARGAR
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . addslashes($originalName) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
    }

    readfile($filePath);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
