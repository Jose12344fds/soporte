<?php
/**
 * API: Subir archivo para Módulo 3 (FH_ING)
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

    if ($size > 50 * 1024 * 1024) {
        throw new RuntimeException('El archivo excede el límite de 50 MB.');
    }

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowedExt = ['dwg', 'dxf', 'pdf', 'dws', 'dwt', 'png', 'jpg', 'jpeg'];
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Tipo de archivo no permitido. Se aceptan: ' . implode(', ', $allowedExt));
    }

    $uploadDir = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
               . DIRECTORY_SEPARATOR . 'FH_ING'
               . DIRECTORY_SEPARATOR . 'planos_mod3' . DIRECTORY_SEPARATOR;

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('No se pudo crear el directorio de destino.');
        }
    }

    $savedName = bin2hex(random_bytes(12)) . '_' . time() . '.' . $ext;
    $dest      = $uploadDir . $savedName;

    if (!move_uploaded_file($tmpName, $dest)) {
        throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
    }

    ob_end_clean();
    echo json_encode([
        'ok'            => true,
        'saved_name'    => $savedName,
        'original_name' => $origName,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
