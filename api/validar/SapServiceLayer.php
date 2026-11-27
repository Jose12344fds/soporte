<?php
/**
 * SapServiceLayer.php — Integración con SAP Business One Service Layer
 * URL base: https://192.168.250.32:50000/b1s/v1/
 */
class SapServiceLayer {

    private const URL_BASE = 'https://192.168.4.33:50000/b1s/v1/';

    /**
     * Inicia sesión en SAP Service Layer y devuelve el SessionId.
     */
    public static function login(): string {
        $datos = json_encode([
            'CompanyDB' => 'SBO_TAMAING_PRODUC',
            'UserName'  => 'manager',
            'Password'  => 'tama0120'
        ]);

        $ch = curl_init(self::URL_BASE . 'Login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $datos,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($datos)
            ],
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);

        $resp = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($resp, true);
        if (!isset($json['SessionId'])) {
            throw new Exception('No se pudo iniciar sesión en SAP Service Layer.');
        }
        return $json['SessionId'];
    }

    /**
     * Actualiza ProductTrees en SAP (PATCH).
     *
     * @param string $itemCode  Código del producto (ej: CodPdre)
     * @param array  $body      Cuerpo JSON { TreeType, ProductTreeLines[] }
     * @return array ['success' => bool, 'message' => string]
     */
    public static function actualizarProductTree(string $itemCode, array $body): array {
        try {
            $token = self::login();
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $url  = self::URL_BASE . "ProductTrees('" . $itemCode . "')";
        $json = json_encode($body);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PATCH',
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
                'Cookie: B1SESSION=' . $token
            ],
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);

        $data = curl_exec($ch);

        if ($data === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'message' => 'Error cURL: ' . $err];
        }
        curl_close($ch);

        $resp = json_decode($data, true);
        if (isset($resp['error'])) {
            return ['success' => false, 'message' => 'Error SAP: ' . $resp['error']['message']['value']];
        }

        return ['success' => true, 'message' => 'Solicitud enviada con éxito.'];
    }
}
