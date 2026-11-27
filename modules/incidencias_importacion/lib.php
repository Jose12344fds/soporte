<?php
declare(strict_types=1);

function hist_schema(): void
{
    global $db;
    $db->query("CREATE TABLE IF NOT EXISTS inc_import_lotes (
        IdLote BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        CodigoLote VARCHAR(40) NOT NULL,
        NombreArchivo VARCHAR(255) NOT NULL,
        TotalTickets INT UNSIGNED NOT NULL DEFAULT 0,
        TotalActividades INT UNSIGNED NOT NULL DEFAULT 0,
        Estado VARCHAR(20) NOT NULL DEFAULT 'IMPORTADO',
        CreadoPor INT UNSIGNED NOT NULL,
        CreadoPorNombre VARCHAR(100) NOT NULL,
        FechaImportacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FechaReversion DATETIME NULL,
        RevertidoPor INT UNSIGNED NULL,
        PRIMARY KEY (IdLote), UNIQUE KEY uq_inc_import_codigo (CodigoLote),
        KEY idx_inc_import_fecha (FechaImportacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE IF NOT EXISTS inc_import_lote_tickets (
        IdLote BIGINT UNSIGNED NOT NULL,
        IdTicket BIGINT UNSIGNED NOT NULL,
        CodigoHistorico VARCHAR(80) NOT NULL,
        PRIMARY KEY (IdLote,IdTicket), UNIQUE KEY uq_inc_import_historico (CodigoHistorico),
        CONSTRAINT fk_inc_import_lote FOREIGN KEY (IdLote) REFERENCES inc_import_lotes(IdLote),
        CONSTRAINT fk_inc_import_ticket FOREIGN KEY (IdTicket) REFERENCES inc_tickets(IdTicket) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function hist_normalize(string $value): string
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    return strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '_', trim($value, '_')));
}

function hist_csv(string $path): array
{
    $handle = fopen($path, 'rb');
    if (!$handle) throw new RuntimeException('No se pudo leer el archivo CSV.');
    $sample = (string)fgets($handle);
    rewind($handle);
    $delimiter = substr_count($sample, ';') >= substr_count($sample, ',') ? ';' : ',';
    $headers = fgetcsv($handle, 0, $delimiter);
    if (!$headers) throw new RuntimeException('El archivo no contiene cabeceras.');
    $headers = array_map(static fn($v) => hist_normalize((string)$v), $headers);
    $rows = [];
    $line = 1;
    while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
        $line++;
        if (!array_filter($values, static fn($v) => trim((string)$v) !== '')) continue;
        $values = array_pad($values, count($headers), '');
        $row = array_combine($headers, array_slice($values, 0, count($headers)));
        $row['_linea'] = $line;
        $rows[] = $row;
        if (count($rows) > 5000) throw new RuntimeException('El archivo supera el limite de 5000 filas.');
    }
    fclose($handle);
    return $rows;
}

function hist_date(string $value, bool $required = false): ?string
{
    $value = trim($value);
    if ($value === '') {
        if ($required) throw new RuntimeException('La fecha es obligatoria.');
        return null;
    }
    foreach (['d/m/Y H:i:s','d/m/Y H:i','d/m/Y','Y-m-d H:i:s','Y-m-d H:i','Y-m-d'] as $format) {
        $date = DateTime::createFromFormat('!'.$format, $value);
        $errors = DateTime::getLastErrors();
        if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            if (!str_contains($format, 'H:i')) $date->setTime(0, 0);
            return $date->format('Y-m-d H:i:s');
        }
    }
    throw new RuntimeException('Fecha invalida: '.$value.'. Use dd/mm/aaaa hh:mm.');
}

function hist_find_user(string $value): ?array
{
    global $db;
    $value = trim($value);
    if ($value === '') return null;
    $safe = $db->escape($value);
    $rows = find_by_sql("SELECT id,name,username,correo,area,status,es_personal_ti FROM users
        WHERE id=".(ctype_digit($value) ? (int)$value : 0)." OR LOWER(username)=LOWER('{$safe}')
           OR LOWER(correo)=LOWER('{$safe}') OR LOWER(name)=LOWER('{$safe}') LIMIT 2");
    return count($rows) === 1 ? $rows[0] : null;
}

function hist_validate(array $tickets, array $activities): array
{
    global $db;
    $errors = [];
    $validTypes = ['INCIDENTE','SOLICITUD','EVENTO'];
    $validChannels = ['PORTAL','CORREO','TELEFONO','PRESENCIAL','MONITOREO'];
    $validStates = ['NUEVO','EN_PROGRESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO','RESUELTO','CERRADO','CANCELADO'];
    $codes = [];
    $normalized = [];
    foreach ($tickets as $row) {
        $line = (int)$row['_linea'];
        try {
            $code = trim((string)($row['codigo_historico'] ?? ''));
            if ($code === '') throw new RuntimeException('Falta codigo_historico.');
            if (mb_strlen($code) > 80) throw new RuntimeException('codigo_historico no puede superar 80 caracteres.');
            if (isset($codes[$code])) throw new RuntimeException('Codigo historico duplicado en el archivo.');
            $codes[$code] = true;
            if (find_by_sql("SELECT CodigoHistorico FROM inc_import_lote_tickets WHERE CodigoHistorico='".$db->escape($code)."' LIMIT 1")) throw new RuntimeException('El codigo historico ya fue importado.');
            $requester = hist_find_user((string)($row['solicitante'] ?? ''));
            if (!$requester) throw new RuntimeException('Solicitante inexistente o ambiguo. Use ID, usuario o correo exacto.');
            $assignee = hist_find_user((string)($row['responsable'] ?? ''));
            if (trim((string)($row['responsable'] ?? '')) !== '' && (!$assignee || (int)$assignee['es_personal_ti'] !== 1)) throw new RuntimeException('Responsable no es personal TI valido.');
            $categoryCode = strtoupper(trim((string)($row['categoria_codigo'] ?? '')));
            $category = find_by_sql("SELECT IdCategoria FROM inc_categorias WHERE Codigo='".$db->escape($categoryCode)."' LIMIT 1");
            if (!$category) throw new RuntimeException('Categoria inexistente: '.$categoryCode.'.');
            $type = strtoupper(trim((string)($row['tipo'] ?? 'INCIDENTE')));
            $channel = strtoupper(trim((string)($row['canal'] ?? 'CORREO')));
            $state = strtoupper(trim((string)($row['estado'] ?? 'CERRADO')));
            if (!in_array($type, $validTypes, true)) throw new RuntimeException('Tipo no valido.');
            if (!in_array($channel, $validChannels, true)) throw new RuntimeException('Canal no valido.');
            if (!in_array($state, $validStates, true)) throw new RuntimeException('Estado no valido.');
            $impact = (int)($row['impacto'] ?? 2); $urgency = (int)($row['urgencia'] ?? 2);
            if ($impact < 1 || $impact > 4 || $urgency < 1 || $urgency > 4) throw new RuntimeException('Impacto y urgencia deben estar entre 1 y 4.');
            $created = hist_date((string)($row['fecha_creacion'] ?? ''), true);
            $first = hist_date((string)($row['fecha_primera_respuesta'] ?? ''));
            $resolved = hist_date((string)($row['fecha_resolucion'] ?? ''));
            $closed = hist_date((string)($row['fecha_cierre'] ?? ''));
            foreach ([$first,$resolved,$closed] as $date) if ($date && $date < $created) throw new RuntimeException('Una fecha de atención es anterior a la creación.');
            if ($closed && $resolved && $closed < $resolved) throw new RuntimeException('La fecha de cierre es anterior a la resolución.');
            if (in_array($state, ['RESUELTO','CERRADO'], true) && trim((string)($row['solucion'] ?? '')) === '') throw new RuntimeException('Los tickets resueltos/cerrados requieren solución.');
            $title = trim((string)($row['titulo'] ?? '')); $description = trim((string)($row['descripcion'] ?? ''));
            if (mb_strlen($title) < 5 || mb_strlen($title) > 180) throw new RuntimeException('El título debe tener entre 5 y 180 caracteres.');
            if (mb_strlen($description) < 10) throw new RuntimeException('La descripción debe tener al menos 10 caracteres.');
            $priority = inc_priority($impact, $urgency); $sla = inc_sla_for_priority($priority);
            if (!$sla) throw new RuntimeException('No existe SLA activo para '.$priority.'.');
            $normalized[$code] = compact('code','requester','assignee','category','type','channel','state','impact','urgency','created','first','resolved','closed','title','description','priority','sla') + ['source'=>$row];
        } catch (Throwable $e) { $errors[] = 'Tickets, línea '.$line.': '.$e->getMessage(); }
    }
    $normalizedActivities = [];
    foreach ($activities as $row) {
        $line = (int)$row['_linea'];
        try {
            $code = trim((string)($row['codigo_historico'] ?? ''));
            if (!isset($codes[$code])) throw new RuntimeException('El codigo_historico no existe en Tickets.');
            $date = hist_date((string)($row['fecha'] ?? ''), true);
            $comment = trim((string)($row['comentario'] ?? ''));
            if ($comment === '') throw new RuntimeException('El comentario es obligatorio.');
            $author = hist_find_user((string)($row['autor'] ?? ''));
            if (!$author) throw new RuntimeException('Autor inexistente o ambiguo.');
            $normalizedActivities[] = ['code'=>$code,'date'=>$date,'comment'=>$comment,'author'=>$author,
                'type'=>strtoupper(trim((string)($row['tipo_actividad'] ?? 'COMENTARIO'))),
                'public'=>strtoupper(trim((string)($row['es_publico'] ?? 'SI'))) !== 'NO'];
        } catch (Throwable $e) { $errors[] = 'Actividad, línea '.$line.': '.$e->getMessage(); }
    }
    return ['errors'=>$errors,'tickets'=>$normalized,'activities'=>$normalizedActivities];
}

function hist_sql(?string $value): string
{
    global $db;
    return $value === null || $value === '' ? 'NULL' : "'".$db->escape($value)."'";
}
