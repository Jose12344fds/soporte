<?php
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/funciones_checklist.php';

ensure_mantenimiento_ti_schema();
csrf_require();
require_permiso('mantenimiento_ti', 'editar');

$id = (int)($_POST['mantenimiento_id'] ?? 0);
$order = $id > 0 ? $db->fetch_assoc($db->query("SELECT id,folio,fecha_programada,foto_antes,foto_despues FROM activos_mantenimientos WHERE id={$id} LIMIT 1")) : null;
if (!$order) {
    $session->msg('d', 'La orden de mantenimiento no existe.');
    redirect('index.php', false);
}

function mti_gallery_files(string $key, string $label): array {
    if (!isset($_FILES[$key])) return [];
    $source = $_FILES[$key];
    $names = is_array($source['name'] ?? null) ? $source['name'] : [$source['name'] ?? ''];
    $errors = is_array($source['error'] ?? null) ? $source['error'] : [$source['error'] ?? UPLOAD_ERR_NO_FILE];
    $sizes = is_array($source['size'] ?? null) ? $source['size'] : [$source['size'] ?? 0];
    $temps = is_array($source['tmp_name'] ?? null) ? $source['tmp_name'] : [$source['tmp_name'] ?? ''];
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $files = [];
    foreach ($names as $index => $name) {
        $error = (int)($errors[$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if ($error !== UPLOAD_ERR_OK) throw new RuntimeException("No se pudo recibir una imagen {$label}.");
        $size = (int)($sizes[$index] ?? 0);
        if ($size <= 0 || $size > 15 * 1024 * 1024) throw new RuntimeException('Cada imagen debe pesar como maximo 15 MB.');
        $tmp = (string)($temps[$index] ?? '');
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset($allowed[$mime])) throw new RuntimeException('Solo se permiten imagenes JPG, PNG o WEBP.');
        $files[] = ['tmp'=>$tmp,'name'=>basename((string)$name),'size'=>$size,'mime'=>$mime,'extension'=>$allowed[$mime]];
    }
    return $files;
}

function mti_gallery_store(array $file, array $order, string $stage): array {
    $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'mantenimiento_ti';
    $safeFolio = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$order['folio']);
    $date = (string)$order['fecha_programada'];
    $relativeDir = substr($date, 0, 4) . '/' . substr($date, 5, 2) . '/' . $safeFolio;
    $directory = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) throw new RuntimeException('No se pudo preparar la carpeta de evidencias.');
    $name = strtolower($stage) . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(5)) . '.' . $file['extension'];
    $absolute = $directory . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($file['tmp'], $absolute)) throw new RuntimeException('No se pudo guardar una de las imagenes.');
    return ['relative'=>$relativeDir.'/'.$name,'absolute'=>$absolute];
}

try {
    $groups = [
        'ANTES' => mti_gallery_files('fotos_antes', 'Antes'),
        'DESPUES' => mti_gallery_files('fotos_despues', 'Despues'),
    ];
    if (!$groups['ANTES'] && !$groups['DESPUES']) throw new RuntimeException('Seleccione al menos una imagen para agregar.');
    foreach ($groups as $stage => $files) {
        $count = $db->fetch_assoc($db->query("SELECT COUNT(*) total FROM activos_mantenimiento_fotos WHERE mantenimiento_id={$id} AND etapa='{$stage}'"));
        if ((int)$count['total'] + count($files) > 5) throw new RuntimeException("Solo puede tener 5 fotos {$stage}. Actualmente tiene " . (int)$count['total'] . '.');
    }

    $user = current_user();
    $userId = (int)($user['id'] ?? 0);
    $storedFiles = [];
    $db->query('START TRANSACTION');
    foreach ($groups as $stage => $files) {
        foreach ($files as $file) {
            $stored = mti_gallery_store($file, $order, $stage);
            $storedFiles[] = $stored['absolute'];
            $relative = $db->escape($stored['relative']);
            $original = $db->escape($file['name']);
            $mime = $db->escape($file['mime']);
            $db->query("INSERT INTO activos_mantenimiento_fotos (mantenimiento_id,etapa,ruta,nombre_original,mime,tamano_bytes,creado_por) VALUES ({$id},'{$stage}','{$relative}','{$original}','{$mime}',".(int)$file['size'].",{$userId})");
            $legacy = $stage === 'ANTES' ? 'foto_antes' : 'foto_despues';
            $db->query("UPDATE activos_mantenimientos SET {$legacy}=COALESCE(NULLIF({$legacy},''),'{$relative}'),actualizado_por={$userId} WHERE id={$id} LIMIT 1");
        }
    }
    $db->query('COMMIT');
    $session->msg('s', 'Fotografias agregadas correctamente.');
} catch (Throwable $error) {
    if (isset($storedFiles)) foreach ($storedFiles as $path) if (is_file($path)) @unlink($path);
    try { $db->query('ROLLBACK'); } catch (Throwable $ignored) {}
    $session->msg('d', $error->getMessage());
}

redirect('detalle.php?id=' . $id, false);
