<?php
declare(strict_types=1);

/** Utilidades del importador automático de mediciones de Capacidad TI. */

function cap_log_normalize_key(string $key): string {
    $key = trim($key);
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);
        if ($converted !== false) $key = $converted;
    }
    $key = strtoupper($key);
    return trim((string)preg_replace('/[^A-Z0-9]+/', '_', $key), '_');
}

function cap_log_normalize_name(string $name): string {
    return preg_replace('/[^A-Z0-9]+/', '', cap_log_normalize_key($name)) ?? '';
}

function cap_log_number($value): ?float {
    $value = trim((string)$value);
    if ($value === '' || preg_match('/^(N\/?A|NULL|NO DISPONIBLE)$/i', $value)) return null;
    $value = preg_replace('/\s*(%|MHZ|GHZ|GB|G|MB|KB|TB|MBPS|NUCLEOS?|CORES?)\s*$/i', '', $value) ?? $value;
    $value = trim($value);
    if (str_contains($value, ',') && !str_contains($value, '.')) $value = str_replace(',', '.', $value);
    else $value = str_replace(',', '', $value);
    return is_numeric($value) ? (float)$value : null;
}

function cap_log_to_gb(string $value): ?float {
    $number = cap_log_number($value);
    if ($number === null) return null;
    if (preg_match('/\bTB\b/i', $value)) return $number * 1024;
    if (preg_match('/\bMB\b/i', $value)) return $number / 1024;
    if (preg_match('/\bKB\b/i', $value)) return $number / (1024 * 1024);
    return $number;
}

function cap_log_value(array $data, array $aliases): ?string {
    foreach ($aliases as $alias) {
        $key = cap_log_normalize_key($alias);
        if (array_key_exists($key, $data) && trim((string)$data[$key]) !== '') return trim((string)$data[$key]);
    }
    return null;
}

function cap_log_numeric(array $data, array $aliases): ?float {
    $value = cap_log_value($data, $aliases);
    return $value === null ? null : cap_log_number($value);
}

function cap_log_parse_file(string $path): array {
    if (!is_file($path) || !is_readable($path)) throw new RuntimeException('No se puede leer el archivo.');
    $content = file_get_contents($path);
    if ($content === false) throw new RuntimeException('No se pudo abrir el archivo.');
    if (str_starts_with($content, "\xFF\xFE") || str_starts_with($content, "\xFE\xFF")) {
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
    } elseif (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    $data = [];
    foreach (preg_split('/\R/u', $content) ?: [] as $lineNumber => $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) continue;

        if (str_contains($line, '|')) {
            $parts = array_map('trim', explode('|', $line));
            $key = cap_log_normalize_key((string)($parts[0] ?? ''));
            if ($key === 'COMPONENTE') continue;
            if (in_array($key, ['SERVIDOR', 'FECHA', 'IP', 'IP_SERVIDOR'], true) && isset($parts[1])) {
                $data[$key] = $parts[1];
                continue;
            }
            if (count($parts) >= 3) {
                if ($key === 'CPU') {
                    $data['CPU_TOTAL_NUCLEOS'] = (string)($parts[1] ?? '');
                    $data['CPU_PORCENTAJE'] = (string)($parts[2] ?? '');
                    continue;
                }
                if (in_array($key, ['MEMORIA_RAM', 'RAM'], true)) {
                    $total = cap_log_to_gb((string)($parts[1] ?? ''));
                    $used = cap_log_to_gb((string)($parts[2] ?? ''));
                    if ($total !== null) $data['RAM_TOTAL_GB'] = (string)$total;
                    if ($used !== null) $data['RAM_USADA_GB'] = (string)$used;
                    continue;
                }
                if (in_array($key, ['DISCO_DURO', 'DISCO'], true)) {
                    $total = cap_log_to_gb((string)($parts[1] ?? ''));
                    $used = cap_log_to_gb((string)($parts[2] ?? ''));
                    if ($total !== null) $data['DISCO_TOTAL_GB'] = (string)$total;
                    if ($used !== null) $data['DISCO_USADO_GB'] = (string)$used;
                    continue;
                }
                if (in_array($key, ['RED', 'NETWORK'], true)) {
                    $total = cap_log_number((string)($parts[1] ?? ''));
                    $used = cap_log_number((string)($parts[2] ?? ''));
                    if ($total !== null) $data['RED_TOTAL_MBPS'] = (string)$total;
                    if ($used !== null) $data['RED_USADA_MBPS'] = (string)$used;
                    continue;
                }
            }
            throw new RuntimeException('Formato no reconocido en la línea '.($lineNumber + 1).'.');
        }

        if (!preg_match('/^\s*([^:=]+?)\s*[:=]\s*(.*?)\s*$/u', $line, $match)) {
            throw new RuntimeException('Formato inválido en la línea '.($lineNumber + 1).'. Use CAMPO=VALOR.');
        }
        $key = cap_log_normalize_key($match[1]);
        if ($key !== '') $data[$key] = trim($match[2]);
    }
    if (!$data) throw new RuntimeException('El archivo no contiene campos reconocibles.');
    return $data;
}

function cap_log_filename_data(string $filename): array {
    if (preg_match('/^(.+?)_(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})(?:_((?:\d{1,3}\.){3}\d{1,3}))?\.txt$/i', $filename, $match)) {
        $date = sprintf('%04d-%02d-%02d %02d:%02d:%02d', (int)$match[2], (int)$match[3], (int)$match[4], (int)$match[5], (int)$match[6], (int)$match[7]);
        $name = trim($match[1]);
        $ip = trim((string)($match[8] ?? ''));
    } elseif (preg_match('/^(.+)-(\d{2})-(\d{2})-(\d{4})_(\d{2})-(\d{2})\.txt$/i', $filename, $match)) {
        $date = sprintf('%04d-%02d-%02d %02d:%02d:00', (int)$match[4], (int)$match[3], (int)$match[2], (int)$match[5], (int)$match[6]);
        $name = trim($match[1]);
        $ip = '';
    } else {
        throw new RuntimeException('Nombre inválido. Use SERVIDOR_aaaammdd_HHMMSS_IP.txt.');
    }
    $dateObject = DateTime::createFromFormat('!Y-m-d H:i:s', $date);
    $errors = DateTime::getLastErrors();
    if (!$dateObject || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) throw new RuntimeException('La fecha u hora del nombre del archivo no es válida.');
    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) throw new RuntimeException('La IP incluida en el nombre del archivo no es válida.');
    return ['vm' => $name, 'date' => $date, 'ip' => $ip];
}

function cap_log_measurement_date(array $data, string $fallback): string {
    $value = cap_log_value($data, ['FECHA', 'FECHA_HORA', 'FECHA_MEDICION']);
    if ($value === null) return $fallback;
    $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd-m-Y H:i:s', 'd-m-Y H:i'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat('!'.$format, $value);
        $errors = DateTime::getLastErrors();
        if ($date && ($errors === false || (!$errors['warning_count'] && !$errors['error_count']))) return $date->format('Y-m-d H:i:s');
    }
    throw new RuntimeException('El campo FECHA no tiene un formato válido.');
}

function cap_log_add_metric(array &$metrics, string $resource, ?float $capacity, ?float $used, ?float $percent): void {
    if ($capacity === null || $capacity <= 0) throw new RuntimeException("Falta la capacidad total válida para {$resource}.");
    if ($used === null && $percent !== null) $used = $capacity * $percent / 100;
    if ($percent === null && $used !== null) $percent = ($used / $capacity) * 100;
    if ($used === null || $used < 0 || $percent === null || $percent < 0) throw new RuntimeException("Falta el consumo o porcentaje válido para {$resource}.");
    $metrics[] = ['resource' => $resource, 'capacity' => round($capacity, 2), 'used' => round($used, 2), 'percent' => round(min(9999.99, $percent), 2)];
}

function cap_log_normalize_network_counter(array &$metrics, int $vmId, string $measurementDate): void {
    global $db;
    foreach ($metrics as &$metric) {
        if ($metric['resource'] !== 'RED' || $metric['used'] <= $metric['capacity']) continue;

        $counterMb = (float)$metric['used'];
        $dateSql = $db->escape($measurementDate);
        $previousRows = find_by_sql("SELECT FechaEvaluacion,Observacion
            FROM historial_capacidad
            WHERE IdVM={$vmId} AND Recurso='RED' AND FechaEvaluacion<'{$dateSql}'
              AND Observacion LIKE '%ContadorRedMB=%'
            ORDER BY FechaEvaluacion DESC,IdHistorial DESC LIMIT 1");

        $usedMbps = 0.0;
        $reason = 'lectura base del contador acumulado';
        if ($previousRows && preg_match('/ContadorRedMB=([0-9]+(?:\.[0-9]+)?)/', (string)$previousRows[0]['Observacion'], $match)) {
            $previousCounter = (float)$match[1];
            $seconds = strtotime($measurementDate) - strtotime((string)$previousRows[0]['FechaEvaluacion']);
            if ($seconds > 0 && $counterMb >= $previousCounter) {
                $usedMbps = (($counterMb - $previousCounter) * 8) / $seconds;
                $reason = 'promedio calculado por diferencia entre contadores';
            } else {
                $reason = 'reinicio o lectura no secuencial del contador';
            }
        }

        $metric['used'] = round($usedMbps, 2);
        $metric['percent'] = round(($usedMbps / (float)$metric['capacity']) * 100, 2);
        $metric['network_counter_mb'] = round($counterMb, 2);
        $metric['network_note'] = $reason;
    }
    unset($metric);
}

function cap_log_build_metrics(array $data, array $vm): array {
    $metrics = [];
    $cpuPercent = cap_log_numeric($data, ['CPU_PORCENTAJE', 'CPU_USO_PORCENTAJE', 'CPU_PERCENT', 'CPU_USO']);
    $cpuUsed = cap_log_numeric($data, ['CPU_USADO_MHZ', 'CPU_CONSUMO_MHZ']);
    if ($cpuPercent !== null || $cpuUsed !== null) {
        $cpuCapacity = cap_log_numeric($data, ['CPU_TOTAL_MHZ', 'CPU_CAPACIDAD_MHZ']);
        if ($cpuCapacity === null) $cpuCapacity = (float)$vm['CPUAsignada'] * (float)$vm['FrecuenciaCPUMHz'];
        cap_log_add_metric($metrics, 'CPU', $cpuCapacity, $cpuUsed, $cpuPercent);
    }

    $ramPercent = cap_log_numeric($data, ['RAM_PORCENTAJE', 'RAM_USO_PORCENTAJE', 'RAM_PERCENT']);
    $ramUsed = cap_log_numeric($data, ['RAM_USADA_GB', 'RAM_USADO_GB', 'RAM_CONSUMO_GB']);
    if ($ramPercent !== null || $ramUsed !== null) {
        $ramCapacity = cap_log_numeric($data, ['RAM_TOTAL_GB', 'RAM_CAPACIDAD_GB']) ?? (float)$vm['RAMAsignada'];
        cap_log_add_metric($metrics, 'RAM', $ramCapacity, $ramUsed, $ramPercent);
    }

    $diskPercent = cap_log_numeric($data, ['DISCO_PORCENTAJE', 'DISCO_USO_PORCENTAJE', 'DISCO_PERCENT']);
    $diskUsed = cap_log_numeric($data, ['DISCO_USADO_GB', 'DISCO_USADA_GB', 'DISCO_CONSUMO_GB']);
    if ($diskPercent !== null || $diskUsed !== null) {
        $diskCapacity = cap_log_numeric($data, ['DISCO_TOTAL_GB', 'DISCO_CAPACIDAD_GB']) ?? (float)$vm['DiscoAsignado'];
        cap_log_add_metric($metrics, 'DISCO', $diskCapacity, $diskUsed, $diskPercent);
    }

    $networkPercent = cap_log_numeric($data, ['RED_PORCENTAJE', 'RED_USO_PORCENTAJE', 'RED_PERCENT']);
    $networkUsed = cap_log_numeric($data, ['RED_USADA_MBPS', 'RED_USADO_MBPS', 'RED_CONSUMO_MBPS']);
    if ($networkPercent !== null || $networkUsed !== null) {
        $networkCapacity = cap_log_numeric($data, ['RED_TOTAL_MBPS', 'RED_CAPACIDAD_MBPS']);
        cap_log_add_metric($metrics, 'RED', $networkCapacity, $networkUsed, $networkPercent);
    }
    if (!$metrics) throw new RuntimeException('No se encontraron métricas de CPU, RAM, DISCO o RED.');
    return $metrics;
}

function cap_log_schema(): void {
    global $db;
    $db->query("CREATE TABLE IF NOT EXISTS importacion_log_capacidad (
        IdImportacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ArchivoOriginal VARCHAR(255) NOT NULL,
        HashArchivo CHAR(64) NOT NULL,
        IdVM INT UNSIGNED NOT NULL,
        NombreVMArchivo VARCHAR(120) NOT NULL,
        FechaMedicion DATETIME NOT NULL,
        CantidadMediciones SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        Estado VARCHAR(20) NOT NULL DEFAULT 'PROCESADO',
        Mensaje VARCHAR(500) NULL,
        FechaProceso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (IdImportacion),
        UNIQUE KEY uq_importacion_log_hash (HashArchivo),
        KEY idx_importacion_log_archivo (ArchivoOriginal),
        KEY idx_importacion_log_vm_fecha (IdVM,FechaMedicion),
        CONSTRAINT fk_importacion_log_vm FOREIGN KEY (IdVM) REFERENCES maquina_virtual (IdVM) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE IF NOT EXISTS fuente_log_capacidad (
        IdFuente INT UNSIGNED NOT NULL AUTO_INCREMENT,
        IdVM INT UNSIGNED NOT NULL,
        NombreFuente VARCHAR(120) NOT NULL,
        NombreNormalizado VARCHAR(120) NOT NULL,
        IPFuente VARCHAR(45) NULL,
        UltimaLectura DATETIME NULL,
        Activa TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (IdFuente),
        UNIQUE KEY uq_fuente_log_nombre (NombreNormalizado),
        KEY idx_fuente_log_vm (IdVM),
        KEY idx_fuente_log_ip (IPFuente),
        CONSTRAINT fk_fuente_log_vm FOREIGN KEY (IdVM) REFERENCES maquina_virtual (IdVM) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function cap_log_save_source(string $name, string $ip, int $vmId): void {
    global $db;
    $nameSql = $db->escape(trim($name));
    $normalizedSql = $db->escape(cap_log_normalize_name($name));
    $ipSql = $db->escape($ip);
    $db->query("INSERT INTO fuente_log_capacidad (IdVM,NombreFuente,NombreNormalizado,IPFuente,UltimaLectura,Activa)
        VALUES ({$vmId},'{$nameSql}','{$normalizedSql}',".($ip !== '' ? "'{$ipSql}'" : 'NULL').",NOW(),1)
        ON DUPLICATE KEY UPDATE IdVM=VALUES(IdVM),NombreFuente=VALUES(NombreFuente),IPFuente=COALESCE(VALUES(IPFuente),IPFuente),UltimaLectura=NOW(),Activa=1");
}

function cap_log_find_vm(string $name, string $ip = ''): array {
    global $db;
    $rows = find_by_sql("SELECT v.IdVM,v.NombreVM,v.CPUAsignada,v.RAMAsignada,v.DiscoAsignado,v.IP_VM,
        m.Host,COALESCE(h.FrecuenciaCPUMHz,0) FrecuenciaCPUMHz,
        COALESCE(c.UmbralAdvertencia,75) UmbralAdvertencia,COALESCE(c.UmbralCritico,90) UmbralCritico
        FROM maquina_virtual v
        INNER JOIN maquina m ON m.IdMaquina=v.IdMaquina
        LEFT JOIN hipervisor h ON h.IdHipervisor=v.IdHipervisor
        LEFT JOIN configuracion_capacidad_vm c ON c.IdVM=v.IdVM AND c.Activa=1
        ORDER BY c.IdConfiguracion DESC,v.IdVM");

    if ($ip !== '') {
        $matches = array_values(array_filter($rows, static fn(array $row): bool => trim((string)$row['IP_VM']) === $ip));
        if (count($matches) === 1) {
            cap_log_save_source($name, $ip, (int)$matches[0]['IdVM']);
            return $matches[0];
        }
        if (count($matches) > 1) throw new RuntimeException("La IP {$ip} coincide con varias VM.");
    }

    $normalized = cap_log_normalize_name($name);
    $normalizedSql = $db->escape($normalized);
    $sources = find_by_sql("SELECT IdVM FROM fuente_log_capacidad WHERE NombreNormalizado='{$normalizedSql}' AND Activa=1 LIMIT 2");
    if (count($sources) === 1) {
        $sourceId = (int)$sources[0]['IdVM'];
        $matches = array_values(array_filter($rows, static fn(array $row): bool => (int)$row['IdVM'] === $sourceId));
        if (count($matches) === 1) {
            cap_log_save_source($name, $ip, $sourceId);
            return $matches[0];
        }
    }

    $matches = array_values(array_filter($rows, static fn(array $row): bool => cap_log_normalize_name((string)$row['NombreVM']) === $normalized));
    if (count($matches) !== 1) {
        $suffix = $ip !== '' ? " (IP {$ip})" : '';
        throw new RuntimeException(count($matches) > 1 ? "El nombre {$name} coincide con varias VM." : "No se encontró una VM para {$name}{$suffix}.");
    }
    cap_log_save_source($name, $ip, (int)$matches[0]['IdVM']);
    return $matches[0];
}

function cap_log_sql_number(float $number): string {
    return number_format($number, 2, '.', '');
}

function cap_log_import_file(string $path): array {
    global $db;
    $filename = basename($path);
    $fileInfo = cap_log_filename_data($filename);
    $data = cap_log_parse_file($path);
    $vmName = cap_log_value($data, ['VM', 'NOMBRE_VM', 'SERVIDOR_VIRTUAL', 'SERVIDOR']) ?? $fileInfo['vm'];
    $sourceIp = cap_log_value($data, ['IP', 'IP_SERVIDOR']) ?? (string)($fileInfo['ip'] ?? '');
    $date = cap_log_measurement_date($data, $fileInfo['date']);
    $hash = hash_file('sha256', $path);
    if ($hash === false) throw new RuntimeException('No se pudo calcular la firma del archivo.');

    cap_log_schema();
    $hashSql = $db->escape($hash);
    $duplicates = find_by_sql("SELECT IdImportacion,CantidadMediciones FROM importacion_log_capacidad WHERE HashArchivo='{$hashSql}' LIMIT 1");
    if ($duplicates) return ['duplicate' => true, 'measurements' => (int)$duplicates[0]['CantidadMediciones'], 'vm' => $vmName, 'date' => $date, 'source' => $vmName, 'ip' => $sourceIp];

    $vm = cap_log_find_vm($vmName, $sourceIp);
    $metrics = cap_log_build_metrics($data, $vm);
    cap_log_normalize_network_counter($metrics, (int)$vm['IdVM'], $date);
    $fileSql = $db->escape($filename);
    $vmNameSql = $db->escape($vmName);
    $dateSql = $db->escape($date);
    $note = trim((string)(cap_log_value($data, ['OBSERVACION', 'COMENTARIO']) ?? ''));

    $db->query('START TRANSACTION');
    try {
        $db->query("INSERT INTO importacion_log_capacidad (ArchivoOriginal,HashArchivo,IdVM,NombreVMArchivo,FechaMedicion,CantidadMediciones,Estado)
            VALUES ('{$fileSql}','{$hashSql}',".(int)$vm['IdVM'].",'{$vmNameSql}','{$dateSql}',0,'PROCESANDO')");
        $importId = (int)$db->insert_id();
        $warning = (float)$vm['UmbralAdvertencia'];
        $critical = (float)$vm['UmbralCritico'];
        foreach ($metrics as $metric) {
            $resource = $db->escape($metric['resource']);
            $capacity = cap_log_sql_number($metric['capacity']);
            $used = cap_log_sql_number($metric['used']);
            $percent = cap_log_sql_number($metric['percent']);
            $state = $metric['percent'] >= $critical ? 'CRITICO' : ($metric['percent'] >= $warning ? 'ADVERTENCIA' : 'NORMAL');
            $threshold = $state === 'CRITICO' ? $critical : $warning;
            $observation = 'Importación automática #'.$importId.' desde '.$filename;
            if (isset($metric['network_counter_mb'])) {
                $observation .= '. ContadorRedMB='.number_format((float)$metric['network_counter_mb'], 2, '.', '').'; '.$metric['network_note'];
            }
            if ($note !== '') $observation .= '. '.$note;
            $observationSql = $db->escape($observation);
            $db->query("INSERT INTO historial_capacidad (IdVM,FechaEvaluacion,Recurso,CapacidadTotal,ConsumoActual,PorcentajeUso,UmbralAlerta,Estado,RegistradoPor,Observacion)
                VALUES (".(int)$vm['IdVM'].",'{$dateSql}','{$resource}',{$capacity},{$used},{$percent},".cap_log_sql_number($threshold).",'{$state}',0,'{$observationSql}')");
            $historyId = (int)$db->insert_id();
            if ($state !== 'NORMAL') {
                $level = $state === 'CRITICO' ? 'ROJO' : 'AMARILLO';
                $description = $db->escape("{$metric['resource']} al {$percent}% detectado en {$filename} (umbral ".cap_log_sql_number($threshold)."%).");
                $openAlerts = find_by_sql("SELECT IdAlerta FROM alerta_capacidad WHERE IdVM=".(int)$vm['IdVM']." AND Recurso='{$resource}' AND EstadoAlerta='ABIERTA' ORDER BY IdAlerta DESC LIMIT 1");
                if ($openAlerts) {
                    $alertId = (int)$openAlerts[0]['IdAlerta'];
                    $db->query("UPDATE alerta_capacidad SET IdHistorial={$historyId},PorcentajeDetectado={$percent},NivelAlerta='{$level}',Descripcion='{$description}' WHERE IdAlerta={$alertId}");
                } else {
                    $db->query("INSERT INTO alerta_capacidad (IdVM,IdHistorial,Recurso,PorcentajeDetectado,NivelAlerta,Descripcion)
                        VALUES (".(int)$vm['IdVM'].",{$historyId},'{$resource}',{$percent},'{$level}','{$description}')");
                }
            }
        }
        $message = $db->escape(count($metrics).' mediciones importadas correctamente.');
        $db->query("UPDATE importacion_log_capacidad SET CantidadMediciones=".count($metrics).",Estado='PROCESADO',Mensaje='{$message}' WHERE IdImportacion={$importId}");
        $db->query('COMMIT');
    } catch (Throwable $error) {
        $db->query('ROLLBACK');
        throw $error;
    }
    cap_log_save_source($vmName, $sourceIp, (int)$vm['IdVM']);
    return ['duplicate' => false, 'measurements' => count($metrics), 'vm' => $vm['NombreVM'], 'date' => $date, 'source' => $vmName, 'ip' => $sourceIp];
}

function cap_log_read_name(string $path): string {
    $directory = dirname($path);
    $base = pathinfo($path, PATHINFO_FILENAME);
    return $directory.DIRECTORY_SEPARATOR.$base.'-LEIDO.TXT';
}
