<?php

// Compatibilidad interna del modulo con el helper CSRF oficial del proyecto.
if (!function_exists('csrf_require')) {
    function csrf_require(): void {
        csrf_validate_or_fail();
    }
}

function mti_chk_q($value): string {
    global $db;
    return "'" . $db->escape((string)$value) . "'";
}

function mti_chk_nullable($value): string {
    return $value === null || trim((string)$value) === '' ? 'NULL' : mti_chk_q($value);
}

function mti_chk_schema_tables(): array {
    return [
        'activos_mantenimiento_checklist_plantillas',
        'activos_mantenimiento_checklist_items',
        'activos_mantenimiento_checklists',
        'activos_mantenimiento_checklist_respuestas',
        'activos_mantenimiento_hallazgos',
        'activos_mantenimiento_hallazgo_acciones',
        'activos_mantenimiento_hallazgo_evidencias',
        'activos_mantenimiento_hallazgo_historial',
    ];
}

function mti_chk_schema_missing(): array {
    global $db;
    static $missing = null;
    if ($missing !== null) return $missing;
    $tables = mti_chk_schema_tables();
    $quoted = implode(',', array_map('mti_chk_q', $tables));
    $result = $db->query(
        "SELECT LOWER(table_name) AS nombre_tabla FROM information_schema.tables"
        . " WHERE table_schema=DATABASE() AND table_name IN ({$quoted})"
    );
    $found = [];
    while ($row = $db->fetch_assoc($result)) {
        $name = $row['nombre_tabla'] ?? $row['NOMBRE_TABLA'] ?? '';
        if ($name !== '') $found[] = strtolower((string)$name);
    }
    return $missing = array_values(array_diff($tables, $found));
}

function mti_chk_schema_ready(): bool {
    return mti_chk_schema_missing() === [];
}

function mti_chk_require_schema(): void {
    global $session;
    if (mti_chk_schema_ready()) return;
    $missing = implode(', ', mti_chk_schema_missing());
    $session->msg('d', 'La migracion de checklists esta incompleta. Falta: ' . $missing . '.');
    redirect('index.php', false);
}

function mti_chk_order(int $id): ?array {
    global $db;
    $result = $db->query(
        "SELECT m.*,q.Host,q.Tipo_Activo FROM activos_mantenimientos m"
        . " INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina WHERE m.id={$id} LIMIT 1"
    );
    return $db->fetch_assoc($result) ?: null;
}

function mti_chk_plan_for_order(int $maintenanceId): ?array {
    global $db;
    $result = $db->query(
        "SELECT o.id ocurrencia_id,o.plan_id,p.codigo,p.nombre"
        . " FROM activos_mantenimiento_ocurrencias o"
        . " INNER JOIN activos_mantenimiento_planes p ON p.id=o.plan_id"
        . " WHERE o.mantenimiento_id={$maintenanceId} LIMIT 1"
    );
    return $db->fetch_assoc($result) ?: null;
}

function mti_chk_template_used(int $templateId): bool {
    global $db;
    $row = $db->fetch_assoc($db->query(
        "SELECT COUNT(*) total FROM activos_mantenimiento_checklists WHERE plantilla_id={$templateId}"
    ));
    return (int)($row['total'] ?? 0) > 0;
}

function mti_chk_value_valid(string $type, ?string $value): bool {
    if ($value === null || $value === '') return true;
    if ($type === 'SI_NO_NA') return in_array($value, ['SI','NO','NA'], true);
    if ($type === 'CUMPLE_NO_CUMPLE_NA') return in_array($value, ['CUMPLE','NO_CUMPLE','NA'], true);
    if ($type === 'NUMERICO') return preg_match('/^-?\d+(?:[.,]\d+)?$/', $value) === 1;
    return $type === 'TEXTO';
}

function mti_chk_is_failure(string $type, ?string $value): bool {
    return ($type === 'SI_NO_NA' && $value === 'NO')
        || ($type === 'CUMPLE_NO_CUMPLE_NA' && $value === 'NO_CUMPLE');
}

function mti_chk_hallazgo_history(
    int $findingId,
    string $event,
    ?string $oldState,
    ?string $newState,
    string $comment,
    int $userId,
    ?int $actionId = null
): void {
    global $db;
    $actionSql = $actionId ? (string)$actionId : 'NULL';
    $db->query(
        "INSERT INTO activos_mantenimiento_hallazgo_historial"
        . " (hallazgo_id,accion_id,evento,estado_anterior,estado_nuevo,comentario,registrado_por)"
        . " VALUES ({$findingId},{$actionSql}," . mti_chk_q($event) . ','
        . mti_chk_nullable($oldState) . ',' . mti_chk_nullable($newState) . ','
        . mti_chk_nullable($comment) . ',' . $userId . ')'
    );
}

function mti_chk_evidence_root(): string {
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'mantenimiento_ti' . DIRECTORY_SEPARATOR . 'hallazgos';
}

function mti_chk_store_upload(array $file, int $findingId, string $stage): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Seleccione un archivo valido.');
    }
    if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
        throw new RuntimeException('El archivo supera el limite de 10 MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => ['jpg','FOTO'],
        'image/png' => ['png','FOTO'],
        'image/webp' => ['webp','FOTO'],
        'application/pdf' => ['pdf','DOCUMENTO'],
        'video/mp4' => ['mp4','VIDEO'],
    ];
    if (!isset($allowed[$mime])) throw new RuntimeException('Formato no permitido.');
    [$extension, $type] = $allowed[$mime];
    $relativeDir = $findingId . '/' . strtolower($stage);
    $directory = mti_chk_evidence_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo crear la carpeta de evidencias.');
    }
    $name = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $name)) {
        throw new RuntimeException('No se pudo guardar la evidencia.');
    }
    return [
        'relative' => $relativeDir . '/' . $name,
        'original' => basename((string)$file['name']),
        'mime' => $mime,
        'type' => $type,
    ];
}
