<?php
/**
 * APS Ensure — Módulo 3 (archivos en uploads/FH_ING/planos_mod3/)
 * GET ?file=savedname → JSON { urn, status, progress, message }
 */
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(120);

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
require_once dirname(dirname(__DIR__)) . '/config/aps.php';
require_once __DIR__ . '/aps_token.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function aps3_respond($status, $urn = '', $progress = 0, $message = '') {
    echo json_encode(compact('status', 'urn', 'progress', 'message'));
    exit;
}

function aps3_curl($method, $url, $headers, $body = null, $rawBody = false) {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => $headers,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = $rawBody ? $body : json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);
    if ($err) throw new RuntimeException('cURL: ' . $err);
    return ['code' => $httpCode, 'body' => $resp, 'json' => json_decode($resp, true)];
}

function aps3_urn($bucketKey, $objectName) {
    $raw = 'urn:adsk.objects:os.object:' . $bucketKey . '/' . $objectName;
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

/* ─── Validar entrada ─────────────────────────────────────────────────── */
$file = isset($_GET['file']) ? basename($_GET['file']) : '';
if (!$file || !preg_match('/^[a-z0-9_\-\.]+$/i', $file)) {
    http_response_code(400);
    aps3_respond('failed', '', 0, 'Nombre de archivo inválido');
}

if (empty(APS_CLIENT_ID) || empty(APS_CLIENT_SECRET)) {
    http_response_code(503);
    aps3_respond('failed', '', 0, 'APS no configurado');
}

$uploadsDir = rtrim(
    BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'FH_ING'
    . DIRECTORY_SEPARATOR . 'planos_mod3',
    '/\\'
);

$ext      = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$baseName = pathinfo($file, PATHINFO_FILENAME);

$uploadFile = $uploadsDir . DIRECTORY_SEPARATOR . $file;

// Path traversal guard
$realDir  = realpath($uploadsDir);
$realFile = realpath($uploadFile);
if (!$realDir || !$realFile || strpos($realFile, $realDir) !== 0 || !is_file($realFile)) {
    http_response_code(404);
    aps3_respond('failed', '', 0, 'Archivo no encontrado en el servidor');
}

$objectName = $baseName . '.' . $ext;
$urnFile    = $uploadsDir . DIRECTORY_SEPARATOR . $baseName . '.aps_urn';

/* ─── Tokens ─────────────────────────────────────────────────────────── */
try {
    $viewerTok = aps_get_token('data:read', APS_CLIENT_ID, APS_CLIENT_SECRET);
    $writeTok  = aps_get_token('data:read data:write data:create bucket:create bucket:read',
                               APS_CLIENT_ID, APS_CLIENT_SECRET);
} catch (Throwable $e) {
    http_response_code(500);
    aps3_respond('failed', '', 0, 'Error auth APS: ' . $e->getMessage());
}

$bearerW = 'Authorization: Bearer ' . $writeTok['access_token'];
$bearerR = 'Authorization: Bearer ' . $viewerTok['access_token'];

$urn = aps3_urn(APS_BUCKET_KEY, $objectName);

/* ─── Si ya hay URN guardado ─────────────────────────────────────────── */
if (is_file($urnFile)) {
    $savedUrn = trim(file_get_contents($urnFile));
    if ($savedUrn) {
        $urn = $savedUrn;
        goto check_manifest;
    }
}

/* ─── Crear bucket si no existe ──────────────────────────────────────── */
$res = aps3_curl('POST', 'https://developer.api.autodesk.com/oss/v2/buckets',
    [$bearerW, 'Content-Type: application/json'],
    ['bucketKey' => APS_BUCKET_KEY, 'policyKey' => 'persistent', 'access' => 'full']
);
if ($res['code'] !== 200 && $res['code'] !== 409) {
    http_response_code(500);
    aps3_respond('failed', '', 0, 'Error creando bucket: HTTP ' . $res['code']);
}

/* ─── Obtener URL firmada S3 ──────────────────────────────────────────── */
$signedUrlEndpoint = 'https://developer.api.autodesk.com/oss/v2/buckets/'
    . urlencode(APS_BUCKET_KEY) . '/objects/' . urlencode($objectName) . '/signeds3upload?minutesExpiration=30';
$res = aps3_curl('GET', $signedUrlEndpoint, [$bearerW]);
if ($res['code'] !== 200) {
    http_response_code(500);
    aps3_respond('failed', '', 0, 'Error obteniendo URL firmada: HTTP ' . $res['code']);
}
$s3Url    = $res['json']['urls'][0] ?? ($res['json']['url'] ?? '');
$uploadKey = $res['json']['uploadKey'] ?? '';
if (!$s3Url) {
    http_response_code(500);
    aps3_respond('failed', '', 0, 'URL firmada vacía');
}

/* ─── Subir a S3 ──────────────────────────────────────────────────────── */
$fileData = file_get_contents($realFile);
$fileSize = filesize($realFile);
$ch = curl_init($s3Url);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'PUT',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 300,
    CURLOPT_POSTFIELDS     => $fileData,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/octet-stream', 'Content-Length: ' . $fileSize],
]);
$s3Code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_exec($ch); $s3Code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
if ($s3Code !== 200) {
    http_response_code(500);
    aps3_respond('failed', '', 0, 'Error subiendo a S3: HTTP ' . $s3Code);
}

/* ─── Completar upload ────────────────────────────────────────────────── */
$completeUrl = 'https://developer.api.autodesk.com/oss/v2/buckets/'
    . urlencode(APS_BUCKET_KEY) . '/objects/' . urlencode($objectName) . '/signeds3upload';
$res = aps3_curl('POST', $completeUrl, [$bearerW, 'Content-Type: application/json'], ['uploadKey' => $uploadKey]);
if ($res['code'] !== 200) {
    http_response_code(500);
    aps3_respond('failed', '', 0, 'Error completando upload: HTTP ' . $res['code']);
}
@file_put_contents($urnFile, $urn);

/* ─── Iniciar traducción ──────────────────────────────────────────────── */
$res = aps3_curl('POST', 'https://developer.api.autodesk.com/modelderivative/v2/designdata/job',
    [$bearerW, 'Content-Type: application/json', 'x-ads-force: false'],
    ['input' => ['urn' => $urn], 'output' => ['formats' => [['type' => 'svf2', 'views' => ['2d', '3d']]]]]
);
if ($res['code'] !== 200 && $res['code'] !== 201) {
    @unlink($urnFile);
    http_response_code(500);
    aps3_respond('failed', $urn, 0, 'Error iniciando traducción: HTTP ' . $res['code']);
}

/* ─── Verificar manifest ──────────────────────────────────────────────── */
check_manifest:
$manifestUrl = 'https://developer.api.autodesk.com/modelderivative/v2/designdata/'
             . urlencode($urn) . '/manifest';
$res = aps3_curl('GET', $manifestUrl, [$bearerR]);

if ($res['code'] === 404) {
    aps3_respond('pending', $urn, 0, 'Traducción iniciada, espera un momento…');
}
if ($res['code'] !== 200) {
    aps3_respond('failed', $urn, 0, 'Error manifest: HTTP ' . $res['code']);
}

$manifest = $res['json'];
$status   = $manifest['status'] ?? 'pending';
$progress = $manifest['progress'] ?? '0%';
preg_match('/(\d+)/', $progress, $m);
$progressNum = isset($m[1]) ? (int)$m[1] : 0;

switch ($status) {
    case 'success':   aps3_respond('success', $urn, 100, 'Listo');
    case 'inprogress': aps3_respond('inprogress', $urn, $progressNum, 'Traduciendo: ' . $progress);
    case 'failed':
    case 'timeout':
        @unlink($urnFile);
        $errMsg = $manifest['derivatives'][0]['messages'][0]['message'] ?? 'Error en traducción';
        aps3_respond('failed', $urn, 0, $errMsg);
    default:
        aps3_respond('pending', $urn, $progressNum, 'En cola…');
}
