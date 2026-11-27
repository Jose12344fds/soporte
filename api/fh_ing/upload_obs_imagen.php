<?php
/**
 * API: Subir imagen referencial a una anotación de plano (FH ING)
 * POST multipart/form-data: image (file), id_obs (int)
 * Responde: { ok, id, nombre_original, nombre_guardado }
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
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $id_obs = isset($_POST['id_obs']) ? (int)$_POST['id_obs'] : 0;
    if ($id_obs <= 0) {
        throw new RuntimeException('id_obs inválido.');
    }

    if (empty($_FILES['image'])) {
        throw new RuntimeException('No se recibió imagen.');
    }

    $file    = $_FILES['image'];
    $errCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($errCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al recibir la imagen (código ' . $errCode . ').');
    }

    $origName = basename($file['name']);
    $tmpName  = $file['tmp_name'];
    $size     = $file['size'];

    if ($size > 10 * 1024 * 1024) {
        throw new RuntimeException('La imagen excede el límite de 10 MB.');
    }

    // Verificar que sea imagen real
    $imageInfo = @getimagesize($tmpName);
    if (!$imageInfo) {
        throw new RuntimeException('El archivo no es una imagen válida.');
    }
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
    if (!in_array($imageInfo['mime'], $allowedMimes, true)) {
        throw new RuntimeException('Tipo de imagen no permitido.');
    }

    $ext         = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg','jpeg','png','gif','webp','bmp'];
    if (!in_array($ext, $allowedExts, true)) {
        $ext = 'jpg';
    }

    $uploadDir = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
               . DIRECTORY_SEPARATOR . 'FH_ING'
               . DIRECTORY_SEPARATOR . 'obs_imgs' . DIRECTORY_SEPARATOR;

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('No se pudo crear el directorio.');
    }

    $savedName = bin2hex(random_bytes(12)) . '_' . time() . '.' . $ext;
    $dest      = $uploadDir . $savedName;

    if (!move_uploaded_file($tmpName, $dest)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    global $db;
    $orig_esc  = $db->escape($origName);
    $saved_esc = $db->escape($savedName);

    $db->query(
        "INSERT INTO fh_ing_obs_imagenes (id_obs, nombre_original, nombre_guardado, created_at)
         VALUES ($id_obs, '$orig_esc', '$saved_esc', NOW())"
    );
    $newId = $db->insert_id();

    ob_end_clean();
    echo json_encode([
        'ok'              => true,
        'id'              => $newId,
        'nombre_original' => $origName,
        'nombre_guardado' => $savedName,
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
