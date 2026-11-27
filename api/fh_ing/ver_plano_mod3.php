<?php
/**
 * API: Previsualizar plano SVG — Módulo 3
 * GET: ?file=savedname  → image/svg+xml  (o JSON error)
 *
 * Estrategia:
 *  1. Si existe <basename>.svg pre-generado → servir inmediatamente.
 *  2. Si no existe → crear .pending y devolver 202 { error:'pending' }
 *     para que el cliente reintente cuando el script Python lo convierta.
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    if (!isset($_SESSION['user_id'])) {
        _jsonError(401, 'No autorizado');
    }

    $file = isset($_GET['file']) ? basename(trim($_GET['file'])) : '';
    if (!$file || !preg_match('/^[a-z0-9_\-\.]+$/i', $file)) {
        _jsonError(400, 'Nombre de archivo inválido.');
    }

    $uploadsDir = rtrim(
        BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
                  . DIRECTORY_SEPARATOR . 'FH_ING'
                  . DIRECTORY_SEPARATOR . 'planos_mod3',
        '/\\'
    );

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // Resolver archivo DXF a renderizar
    if ($ext === 'dxf') {
        $renderFile = $uploadsDir . DIRECTORY_SEPARATOR . $file;
    } elseif (in_array($ext, ['dwg', 'dws', 'dwt'], true)) {
        $dxfSibling = pathinfo($file, PATHINFO_FILENAME) . '.dxf';
        $candidate  = $uploadsDir . DIRECTORY_SEPARATOR . basename($dxfSibling);
        if (is_file($candidate)) {
            $renderFile = $candidate;
        } else {
            $pendingFile = $uploadsDir . DIRECTORY_SEPARATOR
                         . pathinfo($file, PATHINFO_FILENAME) . '.pending';
            if (is_file($pendingFile)) {
                _jsonError(202, 'pending');
            }
            _jsonError(404,
                'No se encontró el DXF para este DWG. ' .
                'Ejecuta el script de conversión en el servidor.'
            );
        }
    } else {
        _jsonError(415, 'Formato no soportado para previsualización: .' . $ext);
    }

    if (!is_file($renderFile)) {
        _jsonError(404, 'Archivo no encontrado en el servidor.');
    }

    // Seguridad: renderFile debe estar dentro de uploads/FH_ING/planos_mod3/
    $sep         = DIRECTORY_SEPARATOR;
    $uploadsNorm = str_replace(['/', '\\'], $sep, $uploadsDir) . $sep;
    $renderNorm  = str_replace(['/', '\\'], $sep, $renderFile);
    if (strpos($renderNorm, $uploadsNorm) !== 0) {
        _jsonError(403, 'Acceso denegado');
    }

    // ── ESTRATEGIA 1: Servir SVG pre-generado ────────────────────────────
    $svgPath = $uploadsDir . DIRECTORY_SEPARATOR
             . pathinfo(basename($renderFile), PATHINFO_FILENAME) . '.svg';

    if (is_file($svgPath) && filesize($svgPath) > 100) {
        ob_end_clean();
        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        readfile($svgPath);
        exit;
    }

    // ── SVG no generado aún: crear .pending y devolver 202 ───────────────
    $pendingFile = $uploadsDir . DIRECTORY_SEPARATOR
                 . pathinfo(basename($renderFile), PATHINFO_FILENAME) . '.pending';
    if (!is_file($pendingFile)) {
        @file_put_contents($pendingFile, $renderFile);
    }
    _jsonError(202, 'pending');

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Error interno del servidor']);
}

function _jsonError(int $code, string $message): void
{
    ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
