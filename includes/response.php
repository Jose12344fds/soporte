<?php

/** Respuesta JSON comun que conserva los campos de las APIs existentes. */
function api_json_response(array $payload, int $status = 200): void
{
    $payload = array_merge(['success' => $status >= 200 && $status < 300], $payload);
    try {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );
    } catch (JsonException $error) {
        error_log('No se pudo serializar la respuesta JSON: ' . $error->getMessage());
        $status = 500;
        $json = '{"success":false,"error":"No se pudo generar la respuesta."}';
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo $json;
    exit;
}
