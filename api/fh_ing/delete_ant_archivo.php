<?php
/**
 * API: Eliminar archivo de antecedentes (FH ING)
 * POST JSON: { id }
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
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    $id   = isset($body['id']) ? (int)$body['id'] : 0;

    if ($id <= 0) {
        throw new RuntimeException('ID inválido.');
    }

    global $db;
    $row = find_by_sql("SELECT nombre_guardado FROM fh_ing_ant_archivos WHERE id = $id LIMIT 1");
    if (empty($row)) {
        throw new RuntimeException('Archivo no encontrado.');
    }

    $savedName = $row[0]['nombre_guardado'];
    $filePath  = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
               . DIRECTORY_SEPARATOR . 'FH_ING'
               . DIRECTORY_SEPARATOR . 'antecedentes'
               . DIRECTORY_SEPARATOR . basename($savedName);

    $db->query("DELETE FROM fh_ing_ant_archivos WHERE id = $id");

    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    ob_end_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
