<?php
/**
 * Retorna un token de acceso APS para el Viewer JavaScript.
 * GET ? { access_token, expires_in }
 *
 * Scope: data:read - solo lectura, seguro de exponer al frontend.
 * El token se cachea en un archivo temporal para reducir llamadas a Autodesk.
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
require_once dirname(dirname(__DIR__)) . '/config/aps.php';

/* --- Funcion reutilizable (disponible tambien cuando aps_ensure.php require este archivo) --- */
if (!function_exists('aps_get_token')) {
    function aps_get_token($scopes, $clientId, $clientSecret) {
        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR
                   . 'aps_tok_' . md5($scopes . $clientId) . '.json';

        if (is_file($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (!empty($cached['access_token']) && $cached['expires_at'] > time() + 120) {
                return $cached;
            }
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL no esta disponible en PHP');
        }

        $ch = curl_init('https://developer.api.autodesk.com/authentication/v2/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'scope'      => $scopes,
            ]),
        ]);

        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) throw new RuntimeException('cURL error: ' . $curlErr);
        if ($httpCode !== 200) {
            throw new RuntimeException('APS auth HTTP ' . $httpCode . ': ' . substr($resp, 0, 300));
        }

        $data = json_decode($resp, true);
        if (empty($data['access_token'])) {
            throw new RuntimeException('APS auth: respuesta sin token');
        }

        $result = [
            'access_token' => $data['access_token'],
            'expires_in'   => (int)($data['expires_in'] ?? 3600),
            'expires_at'   => time() + (int)($data['expires_in'] ?? 3600),
        ];

        @file_put_contents($cacheFile, json_encode($result));
        return $result;
    }
}

/* --- Responder al browser (solo cuando se llama directamente via HTTP) --- */
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'aps_token.php') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');

    if (empty(APS_CLIENT_ID) || empty(APS_CLIENT_SECRET)) {
        http_response_code(503);
        echo json_encode(['error' => 'APS no configurado']);
        exit;
    }

    try {
        $tok = aps_get_token('data:read', APS_CLIENT_ID, APS_CLIENT_SECRET);
        echo json_encode([
            'access_token' => $tok['access_token'],
            'expires_in'   => $tok['expires_in'],
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}