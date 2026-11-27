<?php

if (!function_exists('csrf_require')) {
    function csrf_require(): void {
        csrf_validate_or_fail();
    }
}

function mti_cp_q($value): string {
    global $db;
    return "'" . $db->escape((string)$value) . "'";
}

function mti_cp_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mti_cp_criteria(): array {
    return [
        1 => 'La PC se bloquea cuando el usuario se ausenta de su puesto de trabajo.',
        2 => 'La PC requiere credenciales para acceder a la sesión bloqueada.',
        3 => 'No existen contraseñas visibles o almacenadas en archivos accesibles del escritorio.',
        4 => 'El escritorio de Windows no contiene archivos con información sensible o confidencial.',
        5 => 'Los archivos digitales se encuentran organizados en las ubicaciones autorizadas.',
        6 => 'La información altamente confidencial se almacena únicamente en repositorios seguros autorizados.',
        7 => 'No se utiliza la carpeta puente 5.20 para almacenar información altamente confidencial.',
        8 => 'No se mantiene información sensible abierta innecesariamente en pantalla.',
        9 => 'El usuario cierra los documentos sensibles antes de abandonar su PC.',
        10 => 'No se encuentran conectados dispositivos USB o medios de almacenamiento no autorizados.',
        11 => 'El usuario cierra su sesión al finalizar sus actividades.',
        12 => 'La PC se apaga al finalizar la jornada laboral.',
    ];
}

function mti_cp_ensure_schema(): void {
    global $db;
    static $ready = false;
    if ($ready) return;

    $db->query("CREATE TABLE IF NOT EXISTS mti_checklist_pantalla (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        mantenimiento_id BIGINT UNSIGNED NOT NULL,
        fecha_inspeccion DATETIME NOT NULL,
        usuario_inspeccionado VARCHAR(150) NOT NULL,
        area VARCHAR(100) NOT NULL,
        equipo_nombre VARCHAR(150) NOT NULL,
        responsable_id INT NOT NULL DEFAULT 0,
        responsable_nombre VARCHAR(150) NOT NULL,
        creado_por INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_mti_cp_orden (mantenimiento_id),
        KEY idx_mti_cp_fecha (fecha_inspeccion),
        CONSTRAINT fk_mti_cp_orden FOREIGN KEY (mantenimiento_id)
          REFERENCES activos_mantenimientos (id) ON DELETE RESTRICT ON UPDATE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS mti_checklist_pantalla_respuestas (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        checklist_id BIGINT UNSIGNED NOT NULL,
        numero TINYINT UNSIGNED NOT NULL,
        criterio_snapshot VARCHAR(500) NOT NULL,
        estado VARCHAR(20) NOT NULL,
        observacion TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_mti_cp_respuesta (checklist_id, numero),
        CONSTRAINT fk_mti_cp_respuesta FOREIGN KEY (checklist_id)
          REFERENCES mti_checklist_pantalla (id) ON DELETE CASCADE ON UPDATE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS mti_checklist_pantalla_fotos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        checklist_id BIGINT UNSIGNED NOT NULL,
        respuesta_id BIGINT UNSIGNED NULL,
        ruta VARCHAR(500) NOT NULL,
        nombre_original VARCHAR(255) NOT NULL,
        mime VARCHAR(50) NOT NULL,
        tamano_bytes INT UNSIGNED NOT NULL DEFAULT 0,
        creado_por INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_mti_cp_foto_checklist (checklist_id),
        KEY idx_mti_cp_foto_respuesta (respuesta_id),
        CONSTRAINT fk_mti_cp_foto_checklist FOREIGN KEY (checklist_id)
          REFERENCES mti_checklist_pantalla (id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_mti_cp_foto_respuesta FOREIGN KEY (respuesta_id)
          REFERENCES mti_checklist_pantalla_respuestas (id) ON DELETE CASCADE ON UPDATE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $checklistColumn = $db->query("SHOW COLUMNS FROM mti_checklist_pantalla_fotos LIKE 'checklist_id'");
    if ($db->num_rows($checklistColumn) === 0) {
        $db->query("ALTER TABLE mti_checklist_pantalla_fotos ADD COLUMN checklist_id BIGINT UNSIGNED NULL AFTER id");
    }
    $responseColumn = $db->fetch_assoc($db->query("SHOW COLUMNS FROM mti_checklist_pantalla_fotos LIKE 'respuesta_id'"));
    if (($responseColumn['Null'] ?? '') !== 'YES') {
        $db->query("ALTER TABLE mti_checklist_pantalla_fotos MODIFY COLUMN respuesta_id BIGINT UNSIGNED NULL");
    }
    $unlinked = $db->fetch_assoc($db->query("SELECT COUNT(*) total FROM mti_checklist_pantalla_fotos WHERE checklist_id IS NULL"));
    if ((int)($unlinked['total'] ?? 0) > 0) {
        $db->query(
            "UPDATE mti_checklist_pantalla_fotos f "
            . "INNER JOIN mti_checklist_pantalla_respuestas r ON r.id=f.respuesta_id "
            . "SET f.checklist_id=r.checklist_id WHERE f.checklist_id IS NULL"
        );
    }
    $checklistIndex = $db->query("SHOW INDEX FROM mti_checklist_pantalla_fotos WHERE Key_name='idx_mti_cp_foto_checklist'");
    if ($db->num_rows($checklistIndex) === 0) {
        $db->query("ALTER TABLE mti_checklist_pantalla_fotos ADD KEY idx_mti_cp_foto_checklist (checklist_id)");
    }
    $foreignKey = $db->fetch_assoc($db->query(
        "SELECT COUNT(*) total FROM information_schema.REFERENTIAL_CONSTRAINTS "
        . "WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='mti_checklist_pantalla_fotos' "
        . "AND CONSTRAINT_NAME='fk_mti_cp_foto_checklist'"
    ));
    if ((int)($foreignKey['total'] ?? 0) === 0) {
        $db->query("ALTER TABLE mti_checklist_pantalla_fotos ADD CONSTRAINT fk_mti_cp_foto_checklist "
            . "FOREIGN KEY (checklist_id) REFERENCES mti_checklist_pantalla (id) ON DELETE CASCADE ON UPDATE CASCADE");
    }

    $ready = true;
}

function mti_cp_order(int $id): ?array {
    global $db;
    $result = $db->query(
        "SELECT m.id,m.folio,m.IdMaquina,m.usuario_asignado_id,m.usuario_asignado_nombre,"
        . "q.Host,q.Tipo_Activo,u.name AS usuario_actual,u.area AS area_usuario "
        . "FROM activos_mantenimientos m "
        . "INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina "
        . "LEFT JOIN users u ON u.id=m.usuario_asignado_id "
        . "WHERE m.id={$id} LIMIT 1"
    );
    return $db->fetch_assoc($result) ?: null;
}

function mti_cp_storage_root(): string {
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'mantenimiento_ti' . DIRECTORY_SEPARATOR . 'checklist_pantalla';
}

function mti_cp_store_photo(array $file, int $checklistId, int $number): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Una fotografía no pudo cargarse correctamente.');
    }
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 8 * 1024 * 1024) {
        throw new RuntimeException('Cada fotografía debe pesar como máximo 8 MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file((string)$file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime]) || @getimagesize((string)$file['tmp_name']) === false) {
        throw new RuntimeException('Solo se permiten imágenes JPG, PNG o WEBP válidas.');
    }
    $folder = $number > 0 ? 'criterio-' . $number : 'general';
    $relativeDir = date('Y/m') . '/' . $checklistId . '/' . $folder;
    $directory = mti_cp_storage_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo preparar la carpeta segura de fotografías.');
    }
    $name = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
    if (!move_uploaded_file((string)$file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $name)) {
        throw new RuntimeException('No se pudo guardar una fotografía.');
    }
    return [
        'ruta' => $relativeDir . '/' . $name,
        'nombre_original' => basename((string)$file['name']),
        'mime' => $mime,
        'tamano' => $size,
    ];
}

function mti_cp_normalize_files(array $files): array {
    if (!isset($files['name']) || !is_array($files['name'])) return [];
    $normalized = [];
    foreach ($files['name'] as $index => $name) {
        $error = (int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        $normalized[] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $error,
            'size' => $files['size'][$index] ?? 0,
        ];
    }
    return $normalized;
}
