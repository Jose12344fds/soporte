<?php
/**
 * Asegura que un plano DWG esté subido y traducido en Autodesk APS.
 * GET ?id=N → JSON { urn, status, progress, message }
 *
 * Flujo:
 *   1. Buscar archivo DWG del registro N
 *   2. Si existe .aps_urn  → usar URN guardado y verificar manifest (rápido)
 *   3. Si existe .aps_uploading (< 10 min) → responder "pending" (subida en curso)
 *   4. Si no → responder al cliente INMEDIATAMENTE con "pending",
 *              luego subir a OSS + iniciar traducción en segundo plano
 *
 * Status posibles:  pending | inprogress | success | failed
 */
error_reporting(0);
ini_set('display_errors', 0);
ignore_user_abort(true);   // continuar procesando aunque el cliente cierre la conexión
set_time_limit(600);

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
require_once dirname(dirname(__DIR__)) . '/config/aps.php';
require_once __DIR__ . '/aps_token.php';   // importa la función aps_get_token()

/* ─── Helpers ─────────────────────────────────────────────────────────────── */
/**
 * Envía la respuesta JSON al cliente y termina el script.
 */
function aps_respond($status, $urn = '', $progress = 0, $message = '') {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode(compact('status', 'urn', 'progress', 'message'));
    exit;
}

/**
 * Envía la respuesta JSON al cliente de inmediato y continúa procesando
 * en segundo plano (NO llama exit). Requiere ignore_user_abort(true).
 */
function aps_flush_response($status, $urn = '', $progress = 0, $message = '') {
    $body = json_encode(compact('status', 'urn', 'progress', 'message'));
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . strlen($body));
        header('Connection: close');
    }
    echo $body;
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        while (ob_get_level() > 0) { @ob_end_flush(); }
        @flush();
    }
}

function aps_curl($method, $url, $headers, $body = null, $rawBody = false) {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => $headers,
    ];
    if ($body !== null) {
        if ($rawBody) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        } else {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
    }
    curl_setopt_array($ch, $opts);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);
    if ($err) throw new RuntimeException('cURL: ' . $err);
    return ['code' => $httpCode, 'body' => $resp, 'json' => json_decode($resp, true)];
}

/* ─── Validar entrada ─────────────────────────────────────────────────────── */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    aps_respond('failed', '', 0, 'ID inválido');
}

if (empty(APS_CLIENT_ID) || empty(APS_CLIENT_SECRET)) {
    http_response_code(503);
    aps_respond('failed', '', 0, 'APS no configurado');
}

/* ─── Buscar archivo en BD ────────────────────────────────────────────────── */
$row = find_by_sql("SELECT arch_plano FROM pln_dtll_Ing WHERE id_planos = $id LIMIT 1");
if (empty($row) || empty($row[0]['arch_plano'])) {
    http_response_code(404);
    aps_respond('failed', '', 0, 'Plano no encontrado');
}

$savedName  = basename($row[0]['arch_plano']);
$uploadsDir = rtrim(
    BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'FH_ING' . DIRECTORY_SEPARATOR . 'planos',
    '/\\'
);

// Buscar DWG (preferido) o DXF
$ext      = strtolower(pathinfo($savedName, PATHINFO_EXTENSION));
$baseName = pathinfo($savedName, PATHINFO_FILENAME);

if ($ext === 'dwg') {
    $uploadFile = $uploadsDir . DIRECTORY_SEPARATOR . $savedName;
} else {
    // Intentar encontrar el DWG original
    $dwgPath = $uploadsDir . DIRECTORY_SEPARATOR . $baseName . '.dwg';
    $uploadFile = is_file($dwgPath) ? $dwgPath : ($uploadsDir . DIRECTORY_SEPARATOR . $savedName);
}

// Validar que el archivo esté dentro del directorio permitido
$sep      = DIRECTORY_SEPARATOR;
$dirNorm  = str_replace(['/', '\\'], $sep, $uploadsDir) . $sep;
$fileNorm = str_replace(['/', '\\'], $sep, realpath($uploadFile) ?: $uploadFile);
if (strpos($fileNorm, $dirNorm) !== 0 || !is_file($uploadFile)) {
    http_response_code(404);
    aps_respond('failed', '', 0, 'Archivo DWG no encontrado en el servidor');
}

$objectName    = $baseName . '.' . strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
$urnFile       = $uploadsDir . DIRECTORY_SEPARATOR . $baseName . '.aps_urn';
$uploadingFile = $uploadsDir . DIRECTORY_SEPARATOR . $baseName . '.aps_uploading';

/* ─── Función: calcular URN base64url ─────────────────────────────────────── */
function aps_urn($bucketKey, $objectName) {
    $raw = 'urn:adsk.objects:os.object:' . $bucketKey . '/' . $objectName;
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

$urn = aps_urn(APS_BUCKET_KEY, $objectName);

/* ─── Fast path: URN ya guardado → solo verificar manifest ──────────────── */
if (is_file($urnFile)) {
    $savedUrn = trim(file_get_contents($urnFile));
    if ($savedUrn) {
        $urn = $savedUrn;
        // Solo necesitamos token de lectura para el manifest
        try {
            $viewerTok = aps_get_token('data:read', APS_CLIENT_ID, APS_CLIENT_SECRET);
        } catch (Throwable $e) {
            aps_respond('failed', '', 0, 'Error auth APS: ' . $e->getMessage());
        }
        $bearerR = 'Authorization: Bearer ' . $viewerTok['access_token'];
        goto check_manifest;
    }
}

/* ─── Upload en curso en otro proceso → responder pending ───────────────── */
if (is_file($uploadingFile)) {
    $uploadAge = time() - (int)@file_get_contents($uploadingFile);
    if ($uploadAge >= 0 && $uploadAge < 600) {
        aps_respond('pending', '', 3, 'Subiendo archivo a la nube… (' . $uploadAge . 's)');
    }
    @unlink($uploadingFile); // obsoleto, eliminar y reintentar
}

/* ─── Obtener tokens (necesitamos escritura para subir) ─────────────────── */
try {
    $viewerTok = aps_get_token('data:read', APS_CLIENT_ID, APS_CLIENT_SECRET);
    $writeTok  = aps_get_token('data:read data:write data:create bucket:create bucket:read',
                               APS_CLIENT_ID, APS_CLIENT_SECRET);
} catch (Throwable $e) {
    http_response_code(500);
    aps_respond('failed', '', 0, 'Error auth APS: ' . $e->getMessage());
}

$bearerW = 'Authorization: Bearer ' . $writeTok['access_token'];
$bearerR = 'Authorization: Bearer ' . $viewerTok['access_token'];

/* ─── Marcar como "subiendo" y responder al cliente INMEDIATAMENTE ────────── */
@file_put_contents($uploadingFile, (string)time());
aps_flush_response('pending', '', 2, 'Subiendo archivo a la nube por primera vez…');
// A partir de aquí el cliente YA recibió la respuesta.
// El script sigue ejecutando en segundo plano (ignore_user_abort = true).

/* ─── Crear bucket si no existe ──────────────────────────────────────────── */
try {
    $res = aps_curl('POST', 'https://developer.api.autodesk.com/oss/v2/buckets',
        [$bearerW, 'Content-Type: application/json'],
        ['bucketKey' => APS_BUCKET_KEY, 'policyKey' => 'persistent', 'access' => 'full']
    );
    // 200=creado, 409=ya existe — ambos son OK
    if ($res['code'] !== 200 && $res['code'] !== 409) {
        @unlink($uploadingFile); exit;
    }
} catch (Throwable $e) {
    @unlink($uploadingFile); exit;
}

/* ─── Subir archivo a OSS vía Signed S3 URL ─────────────────────────────── */
$fileSize = filesize($uploadFile);
$fileData = @file_get_contents($uploadFile);
if ($fileData === false) { @unlink($uploadingFile); exit; }

try {
    // Paso 1: URL firmada S3
    $signedUrlEndpoint = 'https://developer.api.autodesk.com/oss/v2/buckets/'
        . urlencode(APS_BUCKET_KEY) . '/objects/' . urlencode($objectName) . '/signeds3upload?minutesExpiration=30';
    $res = aps_curl('GET', $signedUrlEndpoint, [$bearerW]);
    if ($res['code'] !== 200) { @unlink($uploadingFile); exit; }

    $signedData = $res['json'];
    $s3Url      = $signedData['urls'][0] ?? ($signedData['url'] ?? '');
    $uploadKey  = $signedData['uploadKey'] ?? '';
    if (!$s3Url) { @unlink($uploadingFile); exit; }
} catch (Throwable $e) {
    @unlink($uploadingFile); exit;
}

// Paso 2: Subir directamente a S3
$ch = curl_init($s3Url);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'PUT',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 300,
    CURLOPT_POSTFIELDS     => $fileData,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/octet-stream',
        'Content-Length: ' . $fileSize,
    ],
]);
$s3Resp = curl_exec($ch);
$s3Code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$fileData = null; // liberar memoria

if ($s3Code !== 200) { @unlink($uploadingFile); exit; }

try {
    // Paso 3: Completar upload
    $res = aps_curl('POST',
        'https://developer.api.autodesk.com/oss/v2/buckets/'
            . urlencode(APS_BUCKET_KEY) . '/objects/' . urlencode($objectName) . '/signeds3upload',
        [$bearerW, 'Content-Type: application/json'],
        ['uploadKey' => $uploadKey]
    );
    if ($res['code'] !== 200) { @unlink($uploadingFile); exit; }
} catch (Throwable $e) {
    @unlink($uploadingFile); exit;
}

// Guardar URN y limpiar flag de subida
@file_put_contents($urnFile, $urn);
@unlink($uploadingFile);

/* ─── Iniciar traducción ─────────────────────────────────────────────────── */
try {
    aps_curl('POST', 'https://developer.api.autodesk.com/modelderivative/v2/designdata/job',
        [$bearerW, 'Content-Type: application/json', 'x-ads-force: false'],
        [
            'input'  => ['urn' => $urn],
            'output' => ['formats' => [['type' => 'svf2', 'views' => ['2d', '3d']]]],
        ]
    );
    // Si falla, el URN ya está guardado; el siguiente poll iniciará la traducción vía reintento.
} catch (Throwable $e) { /* ignorar */ }
exit; // background process done

/* ─── Verificar estado del manifest ─────────────────────────────────────── */
check_manifest:
$manifestUrl = 'https://developer.api.autodesk.com/modelderivative/v2/designdata/'
             . urlencode($urn) . '/manifest';
$res = aps_curl('GET', $manifestUrl, [$bearerR]);

if ($res['code'] === 404) {
    // Aún no hay manifest — traducción recién iniciada
    aps_respond('pending', $urn, 0, 'Traducción iniciada, espera un momento…');
}

if ($res['code'] !== 200) {
    aps_respond('failed', $urn, 0, 'Error manifest: HTTP ' . $res['code']);
}

$manifest = $res['json'];
$status   = $manifest['status'] ?? 'pending';
$progress = $manifest['progress'] ?? '0%';

// Extraer número de progreso
preg_match('/(\d+)/', $progress, $m);
$progressNum = isset($m[1]) ? (int)$m[1] : 0;

switch ($status) {
    case 'success':
        aps_respond('success', $urn, 100, 'Listo');
    case 'inprogress':
        aps_respond('inprogress', $urn, $progressNum, 'Traduciendo: ' . $progress);
    case 'failed':
    case 'timeout':
        // Limpiar URN para permitir reintento
        @unlink($urnFile);
        $errMsg = $manifest['derivatives'][0]['messages'][0]['message'] ?? 'Error en traducción';
        aps_respond('failed', $urn, 0, $errMsg);
    default:
        aps_respond('pending', $urn, $progressNum, 'En cola…');
}
