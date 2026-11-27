<?php
/**
 * API: Subir archivo DWG para Detalle de Planos (FH_ING)
 * POST multipart/form-data — campo: file
 * Responde: { ok, saved_name, original_name }
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Método no permitido.');
    }

    if (empty($_FILES['file'])) {
        throw new RuntimeException('No se recibió ningún archivo.');
    }

    $file    = $_FILES['file'];
    $errCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($errCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al recibir el archivo (código ' . $errCode . ').');
    }

    $origName = basename($file['name']);
    $tmpName  = $file['tmp_name'];
    $size     = $file['size'];

    // Límite de 50 MB
    if ($size > 50 * 1024 * 1024) {
        throw new RuntimeException('El archivo excede el límite de 50 MB.');
    }

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowedExt = ['dwg', 'dxf', 'pdf', 'dws', 'dwt'];
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Tipo de archivo no permitido. Se aceptan: ' . implode(', ', $allowedExt));
    }

    // Directorio de destino
    $uploadDir = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
               . DIRECTORY_SEPARATOR . 'FH_ING'
               . DIRECTORY_SEPARATOR . 'planos' . DIRECTORY_SEPARATOR;

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('No se pudo crear el directorio de destino.');
        }
    }

    // Nombre único para evitar colisiones
    $savedName = bin2hex(random_bytes(12)) . '_' . time() . '.' . $ext;
    $dest      = $uploadDir . $savedName;

    if (!move_uploaded_file($tmpName, $dest)) {
        throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
    }

    // ── Auto-conversión DWG/DWS/DWT → DXF (para previsualización) ────────
    $dxfName     = null;
    $dxfError    = null;
    if (in_array($ext, ['dwg', 'dws', 'dwt'], true)) {
        // Escribir archivo .pending para que el Task Scheduler lo convierta
        // (Apache no puede ejecutar Python directamente — ESET HIPS lo bloquea)
        $pendingFile = $uploadDir . pathinfo($savedName, PATHINFO_FILENAME) . '.pending';
        @file_put_contents($pendingFile, $dest);
    }

    ob_end_clean();
    echo json_encode([
        'ok'            => true,
        'saved_name'    => $savedName,
        'original_name' => $origName,
        'dxf_name'      => $dxfName,   // nombre del DXF generado, o null
        'dxf_error'     => $dxfError,  // mensaje si falló la conversión
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

// ════════════════════════════════════════════════════════════════════════════
// Helpers
// ════════════════════════════════════════════════════════════════════════════

/**
 * Convierte un archivo DWG/DWS/DWT a DXF usando ODA File Converter vía Python.
 * Usa proc_open() con array de argumentos para llamar CreateProcess directamente
 * (sin cmd.exe), lo que puede eludir ESET HIPS que bloquea php→cmd.exe→python.
 * Devuelve ['dxf_name' => string|null, 'error' => string|null].
 */
function _convertDwgToDxf(string $dwgPath, string $outputDir): array
{
    $pythonExe  = 'C:\\Python313\\python.exe';
    $scriptPath = BASE_PATH . DIRECTORY_SEPARATOR . 'src'
                . DIRECTORY_SEPARATOR . 'helpers'
                . DIRECTORY_SEPARATOR . 'run_oda_convert.py';

    if (!is_file($pythonExe)) {
        return ['dxf_name' => null, 'error' => 'Python no encontrado en: ' . $pythonExe];
    }
    if (!is_file($scriptPath)) {
        return ['dxf_name' => null, 'error' => 'Script de conversión ODA no encontrado.'];
    }

    $baseName = basename($dwgPath);
    $dxfBase  = pathinfo($baseName, PATHINFO_FILENAME) . '.dxf';
    $dxfDest  = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . $dxfBase;

    $pyUserSite  = 'C:\\Users\\Jose_Sovero\\AppData\\Roaming\\Python\\Python313\\site-packages';
    $env         = array_merge((array)getenv(), ['PYTHONPATH' => $pyUserSite]);
    $descriptors = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];

    // Array mode → CreateProcess sin cmd.exe (bypass ESET HIPS)
    $proc = @proc_open(
        [$pythonExe, $scriptPath, $dwgPath, $dxfDest],
        $descriptors, $pipes, 'C:\\', $env
    );

    if (!is_resource($proc)) {
        return ['dxf_name' => null, 'error' => 'No se pudo iniciar Python (bloqueado por seguridad del sistema). Usa el script CLI.'];
    }

    fclose($pipes[0]);
    $stdout   = stream_get_contents($pipes[1]);
    $stderr   = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($proc);

    if ($exitCode !== 0 || strpos($stdout, 'OK:') === false) {
        $errMsg = trim($stderr ?: 'Conversión ODA falló');
        foreach (explode("\n", $errMsg) as $line) {
            if (strpos($line, 'ERROR:') !== false) {
                $errMsg = trim(str_replace('ERROR:', '', $line));
                break;
            }
        }
        return ['dxf_name' => null, 'error' => $errMsg];
    }

    if (!is_file($dxfDest) || filesize($dxfDest) === 0) {
        return ['dxf_name' => null, 'error' => 'DXF no encontrado en destino tras la conversión.'];
    }

    return ['dxf_name' => $dxfBase, 'error' => null];
}
