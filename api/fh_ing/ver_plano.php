<?php
/**
 * API: Servir previsualización SVG de un plano DXF/DWG.
 * GET: ?id=N  → devuelve image/svg+xml
 *
 * Estrategia (en orden de preferencia):
 *   1. Servir el .svg pre-generado (creado por src/helpers/generar_svgs.py)
 *   2. Generar SVG en tiempo real via proc_open() con array (bypass cmd.exe /
 *      posible bypass de ESET HIPS que bloquea PHP→cmd.exe→python)
 *   3. Error 503 con instrucción para generar el SVG manualmente
 *
 * Para generar los SVG de los DXF existentes ejecuta desde CLI:
 *   C:\Python313\python.exe src\helpers\generar_svgs.py
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(360);   // 6 min máximo para DXF grandes (~100 MB)

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    // --- Validar ID ---
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        _jsonError(400, 'ID inválido');
    }

    // --- Obtener registro de BD ---
    $row = find_by_sql("SELECT arch_plano, arch_original FROM pln_dtll_Ing WHERE id_planos = $id LIMIT 1");
    if (empty($row) || empty($row[0]['arch_plano'])) {
        _jsonError(404, 'Archivo no encontrado en la base de datos');
    }

    $savedName    = basename($row[0]['arch_plano']);
    $originalName = $row[0]['arch_original'] ?: $savedName;
    $extSaved     = strtolower(pathinfo($savedName, PATHINFO_EXTENSION));

    $uploadsDir = rtrim(
        BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
                  . DIRECTORY_SEPARATOR . 'FH_ING'
                  . DIRECTORY_SEPARATOR . 'planos',
        '/\\'
    );

    // ── Resolver DXF a renderizar ─────────────────────────────────────────
    // No se usa realpath() — puede fallar en contextos de servicio Windows.
    $renderFile = null;

    if ($extSaved === 'dxf') {
        $candidate = $uploadsDir . DIRECTORY_SEPARATOR . $savedName;
        if (is_file($candidate)) {
            $renderFile = $candidate;
        }
    } elseif (in_array($extSaved, ['dwg', 'dws', 'dwt'], true)) {
        $dxfSibling = pathinfo($savedName, PATHINFO_FILENAME) . '.dxf';
        $candidate  = $uploadsDir . DIRECTORY_SEPARATOR . basename($dxfSibling);
        if (is_file($candidate)) {
            $renderFile = $candidate;
        } else {
            // Verificar si hay un .pending (DWG subido pero aún en cola de conversión)
            $pendingFile = $uploadsDir . DIRECTORY_SEPARATOR
                         . pathinfo($savedName, PATHINFO_FILENAME) . '.pending';
            if (is_file($pendingFile)) {
                _jsonError(202, 'pending');
            }
            _jsonError(404,
                'No se encontró el DXF para este DWG. ' .
                'Ejecuta: C:\\Python313\\python.exe src\\helpers\\convertir_existentes.py'
            );
        }
    } else {
        _jsonError(415, 'Formato no soportado para previsualización: .' . $extSaved);
    }

    if ($renderFile === null) {
        _jsonError(404, 'Archivo DXF no encontrado en el servidor');
    }

    // ── Seguridad: renderFile debe estar dentro de uploads/FH_ING/planos/ ──
    $sep         = DIRECTORY_SEPARATOR;
    $uploadsNorm = str_replace(['/', '\\'], $sep, $uploadsDir) . $sep;
    $renderNorm  = str_replace(['/', '\\'], $sep, $renderFile);
    if (strpos($renderNorm, $uploadsNorm) !== 0) {
        _jsonError(403, 'Acceso denegado');
    }

    // ── ESTRATEGIA 1: Servir SVG pre-generado (fast path) ──────────────────
    // Generado manualmente con: C:\Python313\python.exe src\helpers\generar_svgs.py
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

    // ── SVG no generado aún: encolar conversión y retornar 202 ─────────────
    // Apache no puede ejecutar Python directamente (ESET HIPS bloqueado).
    // Se escribe un .pending para que el watcher del cliente lo procese.
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

// ════════════════════════════════════════════════════════════════════════════
// Helpers
// ════════════════════════════════════════════════════════════════════════════

/** Termina la ejecución con una respuesta JSON de error. */
function _jsonError(int $code, string $message, array $extra = []): void
{
    ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['error' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Localiza el ejecutable Python 3 usando is_file() directamente.
 * open_basedir no está activo y las rutas C:\PythonXXX\ son locales al servidor.
 * No se usa exec() para evitar que ESET bloquee el proceso.
 */
function _findPython(): ?string
{
    $candidates = [
        'C:\\Python313\\python.exe',
        'C:\\python313\\python.exe',
        'C:\\Python312\\python.exe',
        'C:\\Python311\\python.exe',
        'C:\\Python310\\python.exe',
    ];
    foreach ($candidates as $p) {
        if (is_file($p)) return $p;
    }
    return null;
}
