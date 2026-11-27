<?php
declare(strict_types=1);

require_once __DIR__.'/bootstrap.php';

function evt_log_source_from_name(string $value): string
{
    $value = strtoupper(trim($value));
    if (preg_match('/(?:^|[_\-])(WINDOWS?|WINDOS|WIN|WS\d*)(?:$|[_\-])/i', $value) || preg_match('/^WS\d+/i', $value)) return 'WINDOWS';
    if (preg_match('/(?:^|[_\-])(SUSE|LINUX)(?:$|[_\-])/i', $value)) return 'SUSE';
    if (str_contains($value, 'HANA')) return 'SAP_HANA';
    if (str_contains($value, 'SAP')) return 'SAP';
    return 'OTRO';
}

function evt_log_filename(string $filename): array
{
    /* Admite el formato histórico EVENTOS_FUENTE_... y HOST_... en JSON/TXT. */
    if (!preg_match('/^(.+?)_(\d{8})_(\d{6})(?:_((?:\d{1,3}\.){3}\d{1,3}))?\.(json|txt)$/i', $filename, $match)) {
        throw new RuntimeException('Nombre inválido. Use HOST_aaaammdd_HHMMSS_IP.txt o EVENTOS_FUENTE_aaaammdd_HHMMSS_IP.json.');
    }
    $prefix = trim((string)$match[1]);
    $ip = trim((string)($match[4] ?? ''));
    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        throw new RuntimeException('La IP del archivo no es válida.');
    }
    $date = DateTimeImmutable::createFromFormat('!Ymd His', $match[2].' '.$match[3]);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if ($date === false || ($dateErrors !== false && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0))) {
        throw new RuntimeException('La fecha u hora incluida en el nombre no es válida.');
    }
    $isLegacy = str_starts_with(strtoupper($prefix), 'EVENTOS_');
    $nameValue = $isLegacy ? trim(substr($prefix, 8)) : $prefix;
    if ($nameValue === '') throw new RuntimeException('El nombre debe indicar la fuente o el host.');
    return [
        'source' => $isLegacy ? strtoupper($nameValue) : evt_log_source_from_name($nameValue),
        'host' => $isLegacy ? '' : $nameValue,
        'date' => $date->format('Y-m-d H:i:s'),
        'ip' => $ip,
        'extension' => strtolower((string)$match[5]),
    ];
}

function evt_log_content(string $path): string
{
    if (!is_file($path) || !is_readable($path)) throw new RuntimeException('No se puede leer el archivo.');
    $content = file_get_contents($path);
    if ($content === false) throw new RuntimeException('No se pudo abrir el archivo.');
    if (str_starts_with($content, "\xFF\xFE") || str_starts_with($content, "\xFE\xFF")) $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
    elseif (!mb_check_encoding($content, 'UTF-8')) $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    return trim((string)preg_replace('/^\xEF\xBB\xBF/', '', $content));
}

function evt_log_text_records(string $content): array
{
    $records = [];
    $record = [];
    $messageLines = [];
    $flush = static function () use (&$records, &$record, &$messageLines): void {
        if ($messageLines) {
            $extra = trim(implode(PHP_EOL, $messageLines));
            if ($extra !== '') {
                $current = trim((string)($record['Mensaje'] ?? $record['Message'] ?? ''));
                $record['Mensaje'] = trim($current.($current !== '' ? PHP_EOL : '').$extra);
            }
        }
        if ($record) $records[] = $record;
        $record = [];
        $messageLines = [];
    };

    foreach (preg_split('/\R/u', $content) ?: [] as $lineNumber => $line) {
        $line = trim($line);
        if ($line === '' || preg_match('/^-{3,}$/', $line)) {
            $flush();
            continue;
        }
        if (str_starts_with($line, '#')) continue;
        $separator = strpos($line, '=');
        $pipe = strpos($line, '|');
        if ($separator === false || ($pipe !== false && $pipe < $separator)) $separator = $pipe;
        if ($separator === false) {
            if (!$record) throw new RuntimeException('Formato TXT inválido en la línea '.($lineNumber + 1).'. Use CLAVE=valor o CLAVE|valor.');
            $messageLines[] = $line;
            continue;
        }
        $key = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));
        if ($key === '') throw new RuntimeException('La clave está vacía en la línea '.($lineNumber + 1).'.');
        if (array_key_exists($key, $record) && $record[$key] !== '') $record[$key] .= PHP_EOL.$value;
        else $record[$key] = $value;
    }
    $flush();
    if (!$records) throw new RuntimeException('El archivo TXT no contiene registros reconocibles.');
    return $records;
}

/**
 * Tolera el formato emitido por algunos exportadores: objetos JSON consecutivos
 * y mensajes de Windows con comillas internas sin escapar.
 */
function evt_log_loose_json_records(string $content): array
{
    if (!preg_match_all('/\{(.*?)\}(?=\s*(?:\{|$))/su', $content, $blocks)) return [];
    $records = [];
    foreach ($blocks[1] as $block) {
        $row = [];
        foreach (preg_split('/\R/u', (string)$block) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !preg_match('/^"([^"]+)"\s*:\s*(.*?)(?:,)?$/u', $line, $field)) continue;
            $key = trim((string)$field[1]);
            $raw = trim((string)$field[2]);
            if (str_ends_with($raw, ',')) $raw = rtrim(substr($raw, 0, -1));
            if (strlen($raw) >= 2 && $raw[0] === '"' && substr($raw, -1) === '"') $raw = substr($raw, 1, -1);
            $row[$key] = str_replace(['\\"','\\r','\\n','\\t','\\\\'], ['"',"\r","\n","\t",'\\'], $raw);
        }
        if ($row) $records[] = $row;
    }
    return $records;
}
function evt_log_json_records(string $path): array
{
    $content = evt_log_content($path);
    if ($content === '') return [];

    $decoded = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($decoded['Events']) && is_array($decoded['Events'])) return $decoded['Events'];
        if (isset($decoded['eventos']) && is_array($decoded['eventos'])) return $decoded['eventos'];
        if (is_array($decoded) && array_is_list($decoded)) return $decoded;
        if (is_array($decoded)) return [$decoded];
    }

    $records = [];
    $jsonLines = true;
    foreach (preg_split('/\R/u', $content) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $row = json_decode($line, true);
        if (!is_array($row)) {
            $jsonLines = false;
            break;
        }
        $records[] = $row;
    }
    if ($jsonLines && $records) return $records;
    $looseRecords = evt_log_loose_json_records($content);
    if ($looseRecords) return $looseRecords;
    return evt_log_text_records($content);
}

function evt_log_first(array $row, array $keys, $default = '')
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') return $row[$key];
    }
    $normalized = [];
    foreach ($row as $key => $value) $normalized[mb_strtolower(trim((string)$key), 'UTF-8')] = $value;
    foreach ($keys as $key) {
        $lookup = mb_strtolower(trim((string)$key), 'UTF-8');
        if (array_key_exists($lookup, $normalized) && $normalized[$lookup] !== null && $normalized[$lookup] !== '') return $normalized[$lookup];
    }
    return $default;
}

function evt_log_date($value, string $fallback): string
{
    $value = trim((string)$value);
    if ($value === '') return $fallback;
    if (preg_match('/^\/Date\((\d{10,13})/', $value, $match)) {
        $stamp = (int)$match[1];
        if (strlen($match[1]) === 13) $stamp = intdiv($stamp, 1000);
        return date('Y-m-d H:i:s', $stamp);
    }
    if (ctype_digit($value) && strlen($value) >= 13) {
        $stamp = (int)$value;
        if (strlen($value) >= 16) $stamp = intdiv($stamp, 1000000);
        elseif (strlen($value) === 13) $stamp = intdiv($stamp, 1000);
        return date('Y-m-d H:i:s', $stamp);
    }
    $stamp = strtotime($value);
    return $stamp === false ? $fallback : date('Y-m-d H:i:s', $stamp);
}

function evt_log_severity(array $row, string $source): string
{
    $label = strtoupper(trim((string)evt_log_first($row, ['Severidad','Severity','LevelDisplayName','LEVEL'], '')));
    if (str_contains($label, 'CRIT') || str_contains($label, 'EMERG') || str_contains($label, 'ALERT')) return 'CRITICO';
    if (str_contains($label, 'ERROR') || str_contains($label, 'ERR')) return 'ERROR';
    if (str_contains($label, 'WARN') || str_contains($label, 'ADVERT')) return 'ADVERTENCIA';
    $priority = evt_log_first($row, ['PRIORITY','Priority'], null);
    if ($priority !== null && is_numeric($priority)) {
        $priority = (int)$priority;
        if ($priority <= 2) return 'CRITICO';
        if ($priority === 3) return 'ERROR';
        if ($priority === 4) return 'ADVERTENCIA';
    }
    $level = evt_log_first($row, ['Level'], null);
    if ($source === 'WINDOWS' && is_numeric($level)) {
        $level = (int)$level;
        if ($level === 1) return 'CRITICO';
        if ($level === 2) return 'ERROR';
        if ($level === 3) return 'ADVERTENCIA';
    }
    return 'INFORMATIVO';
}

function evt_log_to_event(array $row, array $meta): array
{
    $source = strtoupper(trim((string)evt_log_first($row, ['Fuente','Source','SistemaOperativo','SO'], $meta['source'])));
    $message = trim((string)evt_log_first($row, ['Mensaje','Message','MESSAGE','Description','Descripcion','Detalle'], ''));
    $code = trim((string)evt_log_first($row, ['CodigoEvento','EventId','EventID','Id','ID','Codigo','SYSLOG_IDENTIFIER'], ''));
    $provider = trim((string)evt_log_first($row, ['Proveedor','ProviderName','Provider','Origen','_SYSTEMD_UNIT','SYSLOG_IDENTIFIER'], ''));
    $host = trim((string)evt_log_first($row, ['HostOrigen','MachineName','Computer','_HOSTNAME','Hostname','Host','Equipo','Servidor'], $meta['host'] ?? ''));
    $contentIp = trim((string)evt_log_first($row, ['IPOrigen','IP','IpAddress','DireccionIP','Direccion_IP'], ''));
    $ip = filter_var($contentIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? $contentIp : trim((string)$meta['ip']);
    $rawDate = evt_log_first($row, ['FechaEvento','TimeCreated','Timestamp','__REALTIME_TIMESTAMP','Fecha','FechaHora','DateTime'], $meta['date']);
    $date = evt_log_date($rawDate, $meta['date']);
    $title = trim((string)evt_log_first($row, ['Titulo','Title','Asunto'], ''));
    if ($title === '') {
        $prefix = trim($provider.($code !== '' ? ' #'.$code : ''));
        $summary = preg_replace('/\s+/u', ' ', $message) ?? $message;
        $title = $prefix !== '' ? $prefix : mb_substr($summary, 0, 180);
    }
    if ($message === '') $message = $title;
    return [
        'Fuente' => $source,
        'Proveedor' => $provider,
        'Canal' => 'LOG',
        'CodigoEvento' => $code,
        'Severidad' => evt_log_severity($row, $source),
        'FechaEvento' => $date,
        'Titulo' => mb_substr($title, 0, 200),
        'Mensaje' => $message,
        'IPOrigen' => $ip,
        'HostOrigen' => $host,
    ];
}

function evt_log_import(string $path): array
{
    global $db;
    $filename = basename($path);
    $meta = evt_log_filename($filename);
    $hash = hash_file('sha256', $path);
    if ($hash === false) throw new RuntimeException('No se pudo calcular la firma del archivo.');
    evt_schema();
    $hashSql = $db->escape($hash);
    $existing = find_by_sql("SELECT * FROM evt_importaciones WHERE HashArchivo='{$hashSql}' LIMIT 1");
    if ($existing) return ['duplicateFile'=>true,'read'=>(int)$existing[0]['CantidadLeida'],'inserted'=>(int)$existing[0]['CantidadInsertada'],'duplicates'=>(int)$existing[0]['CantidadDuplicada']];

    $records = evt_log_json_records($path);
    $inserted = 0;
    $duplicates = 0;
    $db->query('START TRANSACTION');
    try {
        foreach ($records as $row) {
            if (!is_array($row)) continue;
            $result = evt_insert_event(evt_log_to_event($row, $meta), $filename, 0);
            if ($result['inserted']) $inserted++; else $duplicates++;
        }
        $fileSql = $db->escape($filename);
        $sourceSql = $db->escape($meta['source']);
        $ipSql = $db->escape($meta['ip']);
        $messageSql = $db->escape("{$inserted} evento(s) nuevo(s) y {$duplicates} duplicado(s).");
        $db->query("INSERT INTO evt_importaciones (ArchivoOriginal,HashArchivo,Fuente,IPOrigen,CantidadLeida,CantidadInsertada,CantidadDuplicada,Estado,Mensaje)
            VALUES ('{$fileSql}','{$hashSql}','{$sourceSql}','{$ipSql}',".count($records).",{$inserted},{$duplicates},'PROCESADO','{$messageSql}')");
        $db->query('COMMIT');
    } catch (Throwable $error) {
        $db->query('ROLLBACK');
        throw $error;
    }
    return ['duplicateFile'=>false,'read'=>count($records),'inserted'=>$inserted,'duplicates'=>$duplicates];
}

function evt_log_read_name(string $path): string
{
    $extension = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($extension, ['json', 'txt'], true)) $extension = 'txt';
    return dirname($path).DIRECTORY_SEPARATOR.pathinfo($path, PATHINFO_FILENAME).'-LEIDO.'.strtoupper($extension);
}

/** Procesa archivos pendientes con bloqueo compartido entre web, Python y tarea programada. */
function evt_import_pending_folder(string $folder, int $limit = 50): array
{
    $summary = ['processed'=>0,'inserted'=>0,'duplicates'=>0,'failed'=>0,'errors'=>[]];
    if (!is_dir($folder) && !mkdir($folder, 0775, true) && !is_dir($folder)) {
        $summary['failed'] = 1; $summary['errors'][] = 'No se pudo preparar Log_Eventos.'; return $summary;
    }
    $lock = fopen($folder.DIRECTORY_SEPARATOR.'.importador.lock', 'c+');
    if ($lock === false || !flock($lock, LOCK_EX|LOCK_NB)) return $summary;
    try {
        $files = [];
        foreach (new DirectoryIterator($folder) as $entry) {
            if (!$entry->isFile()) continue;
            $extension = strtolower($entry->getExtension());
            if (in_array($extension, ['json','txt'], true) && !preg_match('/-LEIDO\.(?:JSON|TXT)$/i', $entry->getFilename())) $files[] = $entry->getPathname();
        }
        natcasesort($files);
        foreach (array_slice(array_values($files), 0, max(1, $limit)) as $path) {
            try {
                $result = evt_log_import($path);
                $target = evt_log_read_name($path);
                if (file_exists($target)) throw new RuntimeException('Ya existe '.basename($target).'.');
                if (!rename($path, $target)) throw new RuntimeException('Se importo, pero no se pudo agregar -LEIDO.');
                $summary['processed']++; $summary['inserted'] += $result['inserted']; $summary['duplicates'] += $result['duplicates'];
            } catch (Throwable $error) {
                $summary['failed']++; $summary['errors'][] = basename($path).' | '.$error->getMessage();
                $line = '['.date('Y-m-d H:i:s').'] '.basename($path).' | '.$error->getMessage().PHP_EOL;
                file_put_contents($folder.DIRECTORY_SEPARATOR.'importador_errores.log', $line, FILE_APPEND|LOCK_EX);
            }
        }
    } finally {
        flock($lock, LOCK_UN); fclose($lock);
    }
    return $summary;
}