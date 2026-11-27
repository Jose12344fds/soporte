<?php

final class ConexionServiceLayer {
    private const URL_BASE = 'https://192.168.4.33:50000/b1s/v2/';
    private const COMPANY_DB = 'SBO_TAMAING_PRODUC';
    private const USERNAME = 'manager';
    private const PASSWORD = 'tama0120';

    public static function getBaseUrl(): string {
        return self::URL_BASE;
    }

    public static function login(): string {
        $payload = json_encode([
            'CompanyDB' => self::COMPANY_DB,
            'UserName' => self::USERNAME,
            'Password' => self::PASSWORD,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init(self::URL_BASE . 'Login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ],
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);

        $resp = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new Exception('Error cURL login: ' . $curlErr);
        }

        $decoded = json_decode($resp, true);
        if (!isset($decoded['SessionId'])) {
            throw new Exception('No se pudo iniciar sesión en SAP Service Layer v2.');
        }

        return $decoded['SessionId'];
    }

    public static function request(string $method, string $resource, ?array $body = null): array {
        $token = self::login();
        $headers = [
            'Content-Type: application/json',
            'Cookie: B1SESSION=' . $token
        ];

        $opts = [
            CURLOPT_URL => self::URL_BASE . ltrim($resource, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ];

        if ($body !== null) {
            $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $opts[CURLOPT_POSTFIELDS] = $json;
            $opts[CURLOPT_HTTPHEADER][] = 'Content-Length: ' . strlen($json);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) {
            return [
                'ok' => false,
                'message' => 'Error cURL: ' . $curlErr,
                'httpCode' => $httpCode,
                'decoded' => null
            ];
        }

        $decoded = json_decode($resp, true);
        if (isset($decoded['error'])) {
            return [
                'ok' => false,
                'message' => $decoded['error']['message']['value'] ?? 'Error SAP',
                'httpCode' => $httpCode,
                'decoded' => $decoded
            ];
        }

        return [
            'ok' => true,
            'message' => 'OK',
            'httpCode' => $httpCode,
            'decoded' => $decoded
        ];
    }
}
