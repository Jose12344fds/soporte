<?php
declare(strict_types=1);

require_once __DIR__ . '/../hhmeta_guard.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$action = trim((string)($_GET['action'] ?? ''));
$baseUrl = rtrim(SAP_API_BASE_URL, '/');
$ot = '';

if ($action === 'selector') {
    $target = $baseUrl . '/api/sap/proyectos-pcp/selector';
} elseif ($action === 'items') {
    $ot = trim((string)($_GET['ot'] ?? ''));
    if ($ot === '' || strlen($ot) > 30) {
        http_response_code(400);
        echo json_encode(['error' => 'La OT es obligatoria o no es valida.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $target = $baseUrl . '/api/sap/proyectos-pcp/items?ot=' . rawurlencode($ot);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Operacion SAP no encontrada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$curl = curl_init($target);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);
$body = curl_exec($curl);
$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

if ($body === false || $status === 0 || $status === 502 || $status === 503 || $status === 504) {
    // Contingencia temporal mientras SAP.API se instala localmente como servicio de Windows.
    // La API siempre es la fuente principal; esta ruta evita interrumpir HH_META durante el despliegue.
    try {
        require_once __DIR__ . '/ConsultasHana.php';
        $consultas = new Consultas();
        $result = $action === 'selector'
            ? $consultas->obtenerProyectos4()
            : $consultas->obtenerItemsPorOT($ot);

        header('X-SAP-Source: legacy-fallback');
        error_log(sprintf(
            'HH_META: SAP.API no disponible (HTTP %d, %s). Se uso contingencia HANA para %s.',
            $status,
            $curlError !== '' ? $curlError : 'sin detalle',
            $action
        ));
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    } catch (Throwable $fallbackError) {
        error_log('HH_META: fallo SAP.API y tambien la contingencia HANA: ' . $fallbackError->getMessage());
        http_response_code(503);
        echo json_encode([
            'error' => 'No fue posible consultar los proyectos SAP en este momento.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

header('X-SAP-Source: sap-api');
http_response_code($status);
echo $body;
