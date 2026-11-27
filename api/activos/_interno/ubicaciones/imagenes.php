<?php

function ubi_img_q($value): string {
    global $db;
    return "'" . $db->escape((string)$value) . "'";
}

function ubi_img_ensure_schema(): void {
    global $db;
    static $ready = false;
    if ($ready) return;
    $db->query("CREATE TABLE IF NOT EXISTS ubicaciones_fisicas_imagenes (
        IdImagen BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        IdUbicacion INT NOT NULL,
        Ruta VARCHAR(500) NOT NULL,
        NombreOriginal VARCHAR(255) NOT NULL,
        Mime VARCHAR(50) NOT NULL,
        TamanioBytes INT UNSIGNED NOT NULL DEFAULT 0,
        CreadoPor INT NOT NULL DEFAULT 0,
        FechaRegistro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (IdImagen),
        KEY idx_ubi_img_ubicacion (IdUbicacion, IdImagen)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready = true;
}

function ubi_img_storage_root(): string {
    return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'activos' . DIRECTORY_SEPARATOR . 'ubicaciones';
}

function ubi_img_rows(int $locationId): array {
    if ($locationId <= 0) return [];
    return find_by_sql(
        "SELECT IdImagen,IdUbicacion,NombreOriginal,Mime,TamanioBytes,FechaRegistro "
        . "FROM ubicaciones_fisicas_imagenes WHERE IdUbicacion={$locationId} ORDER BY IdImagen"
    ) ?: [];
}

function ubi_img_map(): array {
    $map = [];
    foreach (find_by_sql("SELECT IdImagen,IdUbicacion,NombreOriginal,Mime,TamanioBytes,FechaRegistro FROM ubicaciones_fisicas_imagenes ORDER BY IdImagen") ?: [] as $row) {
        $map[(int)$row['IdUbicacion']][] = $row;
    }
    return $map;
}

function ubi_img_normalize_files(array $files): array {
    if (!isset($files['name']) || !is_array($files['name'])) return [];
    $out = [];
    foreach ($files['name'] as $index => $name) {
        $error = (int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        $out[] = [
            'name' => $name,
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $error,
            'size' => (int)($files['size'][$index] ?? 0),
        ];
    }
    return $out;
}

function ubi_img_validate_files(array $files): void {
    foreach ($files as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Un archivo no pudo cargarse correctamente.');
        }
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
        $isImage = in_array($mime, ['image/jpeg','image/png','image/webp'], true);
        $isPdf = $mime === 'application/pdf';
        $maxBytes = $isPdf ? 20 * 1024 * 1024 : 8 * 1024 * 1024;
        if (($file['size'] ?? 0) <= 0 || (int)$file['size'] > $maxBytes) {
            throw new RuntimeException($isPdf ? 'Cada plano PDF debe pesar como máximo 20 MB.' : 'Cada imagen debe pesar como máximo 8 MB.');
        }
        if ((!$isImage && !$isPdf) || ($isImage && @getimagesize((string)$file['tmp_name']) === false)) {
            throw new RuntimeException('Solo se permiten imágenes JPG, PNG, WEBP o planos PDF válidos.');
        }
        if ($isPdf) {
            $handle = @fopen((string)$file['tmp_name'], 'rb');
            $signature = $handle ? (string)fread($handle, 5) : '';
            if ($handle) fclose($handle);
            if ($signature !== '%PDF-') throw new RuntimeException('El archivo seleccionado no es un PDF válido.');
        }
    }
}

function ubi_img_store_file(array $file, int $locationId): array {
    $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Formato de evidencia no permitido.');
    $relativeDir = (string)$locationId;
    $directory = ubi_img_storage_root() . DIRECTORY_SEPARATOR . $relativeDir;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo preparar la carpeta segura de imágenes.');
    }
    $name = bin2hex(random_bytes(18)) . '.' . $extensions[$mime];
    $absolute = $directory . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file((string)$file['tmp_name'], $absolute)) {
        throw new RuntimeException('No se pudo guardar una evidencia de la ubicación.');
    }
    return [
        'ruta' => $relativeDir . '/' . $name,
        'absolute' => $absolute,
        'original' => basename((string)$file['name']),
        'mime' => $mime,
        'size' => (int)$file['size'],
    ];
}

function ubi_img_safe_path(string $relative): ?string {
    $root = ubi_img_storage_root();
    $rootReal = realpath($root);
    $fileReal = realpath($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative));
    if (!$rootReal || !$fileReal
        || !str_starts_with(strtolower($fileReal), strtolower($rootReal . DIRECTORY_SEPARATOR))
        || !is_file($fileReal)) return null;
    return $fileReal;
}

function ubi_img_sync(int $locationId, array $files, array $removeIds, int $userId): int {
    global $db;
    $existing = find_by_sql("SELECT IdImagen,Ruta FROM ubicaciones_fisicas_imagenes WHERE IdUbicacion={$locationId}") ?: [];
    $existingById = [];
    foreach ($existing as $row) $existingById[(int)$row['IdImagen']] = $row;
    $remove = [];
    foreach ($removeIds as $rawId) {
        $imageId = (int)$rawId;
        if ($imageId > 0 && isset($existingById[$imageId])) $remove[$imageId] = $existingById[$imageId];
    }
    $remaining = count($existingById) - count($remove);
    if ($remaining + count($files) > 4) {
        throw new RuntimeException('Cada ubicación permite como máximo 4 imágenes. Retire una antes de agregar otra.');
    }

    $newPaths = [];
    $db->query('START TRANSACTION');
    try {
        if ($remove) {
            $db->query("DELETE FROM ubicaciones_fisicas_imagenes WHERE IdUbicacion={$locationId} AND IdImagen IN (" . implode(',', array_keys($remove)) . ")");
        }
        foreach ($files as $file) {
            $stored = ubi_img_store_file($file, $locationId);
            $newPaths[] = $stored['absolute'];
            $db->query(
                "INSERT INTO ubicaciones_fisicas_imagenes (IdUbicacion,Ruta,NombreOriginal,Mime,TamanioBytes,CreadoPor) VALUES ("
                . $locationId . ',' . ubi_img_q($stored['ruta']) . ',' . ubi_img_q($stored['original']) . ','
                . ubi_img_q($stored['mime']) . ',' . (int)$stored['size'] . ',' . $userId . ')'
            );
        }
        $db->query('COMMIT');
    } catch (Throwable $error) {
        $db->query('ROLLBACK');
        foreach ($newPaths as $path) if (is_file($path)) @unlink($path);
        throw $error;
    }
    foreach ($remove as $row) {
        $path = ubi_img_safe_path((string)$row['Ruta']);
        if ($path) @unlink($path);
    }
    return $remaining + count($files);
}
