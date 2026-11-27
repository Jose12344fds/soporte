<?php
require_once __DIR__ . '/../hhmeta_guard.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/ConexionServiceLayer.php';

final class SapBomServiceV2 {
    public static function getProductTree(string $itemCode): array {
        $resp = ConexionServiceLayer::request('GET', "ProductTrees('" . rawurlencode($itemCode) . "')");

        if (!$resp['ok']) {
            return [
                'success' => false,
                'message' => $resp['message'],
                'httpCode' => $resp['httpCode'],
                'response' => $resp['decoded']
            ];
        }

        $tree = is_array($resp['decoded']) ? $resp['decoded'] : [];
        $lines = isset($tree['ProductTreeLines']) && is_array($tree['ProductTreeLines']) ? $tree['ProductTreeLines'] : [];
        $moLines = array_values(array_filter($lines, static function ($line) {
            $lineItemCode = (string)($line['ItemCode'] ?? '');
            return preg_match('/^MO\d{3}$/', $lineItemCode) === 1;
        }));

        return [
            'success' => true,
            'itemCode' => $itemCode,
            'tree' => $tree,
            'moLines' => $moLines,
        ];
    }

    public static function updateMoLines(string $itemCode, array $updates): array {
        $current = self::getProductTree($itemCode);
        if (empty($current['success'])) {
            return $current;
        }

        $tree = $current['tree'];
        $lines = isset($tree['ProductTreeLines']) && is_array($tree['ProductTreeLines']) ? $tree['ProductTreeLines'] : [];
        $updatesByKey = [];

        foreach ($updates as $row) {
            $rowItemCode = trim((string)($row['ItemCode'] ?? ''));
            $stageId = (int)($row['StageID'] ?? 0);
            $childNum = (int)($row['ChildNum'] ?? -1);
            if ($rowItemCode === '' || $childNum < 0) {
                continue;
            }
            $updatesByKey[$rowItemCode . '|' . $stageId . '|' . $childNum] = (float)($row['Quantity'] ?? 0);
        }

        foreach ($lines as &$line) {
            $rowItemCode = trim((string)($line['ItemCode'] ?? ''));
            if (preg_match('/^MO\d{3}$/', $rowItemCode) !== 1) {
                continue;
            }

            $stageId = (int)($line['StageID'] ?? 0);
            $childNum = (int)($line['ChildNum'] ?? -1);
            $key = $rowItemCode . '|' . $stageId . '|' . $childNum;
            if (array_key_exists($key, $updatesByKey)) {
                $line['Quantity'] = $updatesByKey[$key];
            }
        }
        unset($line);

        $body = [
            'TreeType' => $tree['TreeType'] ?? 'iProductionTree',
            'Quantity' => $tree['Quantity'] ?? 1,
            'Warehouse' => $tree['Warehouse'] ?? null,
            'ProductTreeLines' => $lines,
        ];

        $resp = ConexionServiceLayer::request('PATCH', "ProductTrees('" . rawurlencode($itemCode) . "')", $body);

        if (!$resp['ok']) {
            return [
                'success' => false,
                'message' => $resp['message'],
                'httpCode' => $resp['httpCode'],
                'response' => $resp['decoded'],
                'sentBody' => $body
            ];
        }

        return [
            'success' => true,
            'message' => 'MO actualizados correctamente.',
            'httpCode' => $resp['httpCode'],
            'response' => $resp['decoded']
        ];
    }
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $itemCode = trim((string)($_GET['itemCode'] ?? ''));
        if ($itemCode === '') {
            echo json_encode(['success' => false, 'message' => 'Parámetro itemCode requerido.']);
            exit;
        }
        echo json_encode(SapBomServiceV2::getProductTree($itemCode), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
        exit;
    }

    $itemCode = trim((string)($payload['itemCode'] ?? ''));
    $updates = $payload['updates'] ?? null;
    if ($itemCode === '' || !is_array($updates)) {
        echo json_encode(['success' => false, 'message' => 'Se requiere itemCode y updates.']);
        exit;
    }

    echo json_encode(SapBomServiceV2::updateMoLines($itemCode, $updates), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
