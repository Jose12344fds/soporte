<?php
/**
 * API: Subir archivo de antecedentes — Actividad 1 (FH ING)
 * POST multipart/form-data: file, ot, actividad_id
 * Responde: { ok, id, nombre_original, nombre_guardado, tipo_mime, tamano }
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

    if (empty($_FILES['file'])) {
        throw new RuntimeException('No se recibió ningún archivo.');
    }

    $ot           = isset($_POST['ot'])           ? trim($_POST['ot'])           : '';
    $actividad_id = isset($_POST['actividad_id']) ? (int)$_POST['actividad_id'] : 1;

    if ($ot === '') {
        throw new RuntimeException('Parámetro ot requerido.');
    }

    $file    = $_FILES['file'];
    $errCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($errCode !== UPLOAD_ERR_OK) {
        $msgs = [
            UPLOAD_ERR_INI_SIZE   => 'Archivo demasiado grande (php.ini).',
            UPLOAD_ERR_FORM_SIZE  => 'Archivo demasiado grande (formulario).',
            UPLOAD_ERR_PARTIAL    => 'Subida parcial.',
            UPLOAD_ERR_NO_FILE    => 'No se recibió archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Sin directorio temporal.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir.',
        ];
        throw new RuntimeException($msgs[$errCode] ?? 'Error al subir (código ' . $errCode . ').');
    }

    $origName = basename($file['name']);
    $tmpName  = $file['tmp_name'];
    $size     = $file['size'];

    if ($size > 25 * 1024 * 1024) {
        throw new RuntimeException('El archivo excede el límite de 25 MB.');
    }

    $ext         = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowedExts = [
        'dwg','dxf','dws','dwt',
        'pdf',
        'jpg','jpeg','png','gif','bmp','tif','tiff','webp',
        'zip','rar','7z',
        'doc','docx','xls','xlsx','txt',
    ];
    if (!in_array($ext, $allowedExts, true)) {
        throw new RuntimeException('Tipo de archivo no permitido: .' . htmlspecialchars($ext, ENT_QUOTES));
    }

    // MIME real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($tmpName) ?: 'application/octet-stream';

    // Directorio destino
    $uploadDir = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
               . DIRECTORY_SEPARATOR . 'FH_ING'
               . DIRECTORY_SEPARATOR . 'antecedentes' . DIRECTORY_SEPARATOR;

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('No se pudo crear el directorio de destino.');
    }

    $savedName = bin2hex(random_bytes(12)) . '_' . time() . '.' . $ext;
    $dest      = $uploadDir . $savedName;

    if (!move_uploaded_file($tmpName, $dest)) {
        throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
    }

    $cu      = current_user();
    $usuario = $cu ? ($cu['name'] ?? ($cu['username'] ?? 'Usuario')) : 'Usuario';

    global $db;
    $ot_esc      = $db->escape($ot);
    $orig_esc    = $db->escape($origName);
    $saved_esc   = $db->escape($savedName);
    $mime_esc    = $db->escape($mime);
    $usuario_esc = $db->escape($usuario);
    $act_int     = (int)$actividad_id;

    $db->query(
        "INSERT INTO fh_ing_ant_archivos
            (ot, actividad_id, nombre_original, nombre_guardado, tipo_mime, tamano, usuario, created_at)
         VALUES ('$ot_esc', $act_int, '$orig_esc', '$saved_esc', '$mime_esc', $size, '$usuario_esc', NOW())"
    );
    $newId = $db->insert_id();

    ob_end_clean();
    echo json_encode([
        'ok'              => true,
        'id'              => $newId,
        'nombre_original' => $origName,
        'nombre_guardado' => $savedName,
        'tipo_mime'       => $mime,
        'tamano'          => $size,
        'usuario'         => $usuario,
        'fecha_str'       => date('d/m/Y H:i'),
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(isset($errCode) && $errCode > 0 ? 400 : 500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
