<?php
declare(strict_types=1);

require_once __DIR__.'/../capacidad_ti/bootstrap.php';
require_once __DIR__.'/../incidencias/bootstrap.php';

if (!function_exists('evt_h')) {
    function evt_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('evt_user')) {
    function evt_user(): array
    {
        $user = function_exists('current_user') ? current_user() : null;
        return is_array($user) ? $user : [];
    }
}

if (!function_exists('evt_redirect')) {
    function evt_redirect(string $message, string $type = 's', string $url = 'index.php'): void
    {
        global $session;
        if (isset($session)) $session->msg($type, $message);
        redirect($url, false);
    }
}

if (!function_exists('evt_normalize')) {
    function evt_normalize(string $value): string
    {
        $value = trim($value);
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) $value = $converted;
        }
        return strtoupper(trim((string)preg_replace('/[^A-Z0-9]+/', '_', $value), '_'));
    }
}

if (!function_exists('evt_severity_label')) {
    function evt_severity_label(string $severity): string
    {
        return [
            'CRITICO' => 'Crítico',
            'ERROR' => 'Error',
            'ADVERTENCIA' => 'Advertencia',
            'INFORMATIVO' => 'Informativo',
        ][strtoupper($severity)] ?? $severity;
    }
}

if (!function_exists('evt_status_label')) {
    function evt_status_label(string $status): string
    {
        return [
            'NUEVO' => 'Nuevo',
            'EN_REVISION' => 'En revisión',
            'REVISADO' => 'Revisado',
            'DESCARTADO' => 'Descartado',
            'VINCULADO' => 'Con incidencia',
        ][strtoupper($status)] ?? $status;
    }
}

if (!function_exists('evt_schema')) {
    function evt_schema(): void
    {
        global $db;
        static $ready = false;
        if ($ready) return;

        inc_schema();
        capacidad_ti_schema();

        $queries = [
            "CREATE TABLE IF NOT EXISTS evt_reglas (
                IdRegla INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Nombre VARCHAR(120) NOT NULL,
                Fuente VARCHAR(30) NOT NULL,
                CodigoEvento VARCHAR(80) NULL,
                PatronTexto VARCHAR(250) NULL,
                Categoria VARCHAR(40) NOT NULL DEFAULT 'SISTEMA',
                Severidad VARCHAR(20) NOT NULL DEFAULT 'ADVERTENCIA',
                RequiereIncidencia TINYINT(1) NOT NULL DEFAULT 0,
                Activa TINYINT(1) NOT NULL DEFAULT 1,
                Orden SMALLINT UNSIGNED NOT NULL DEFAULT 100,
                Descripcion VARCHAR(500) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (IdRegla),
                UNIQUE KEY uq_evt_regla_nombre (Nombre),
                KEY idx_evt_regla_busqueda (Activa,Fuente,CodigoEvento,Orden)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS evt_eventos (
                IdEvento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdVM INT UNSIGNED NULL,
                IdMaquina INT NULL,
                IdAplicacion INT UNSIGNED NULL,
                IdIncidencia BIGINT UNSIGNED NULL,
                Fuente VARCHAR(30) NOT NULL,
                Proveedor VARCHAR(100) NULL,
                Canal VARCHAR(30) NOT NULL DEFAULT 'LOG',
                CodigoEvento VARCHAR(80) NULL,
                Categoria VARCHAR(40) NOT NULL DEFAULT 'SISTEMA',
                Severidad VARCHAR(20) NOT NULL DEFAULT 'INFORMATIVO',
                FechaEvento DATETIME NOT NULL,
                Titulo VARCHAR(200) NOT NULL,
                Mensaje MEDIUMTEXT NOT NULL,
                DatosJson LONGTEXT NULL,
                IPOrigen VARCHAR(45) NULL,
                HostOrigen VARCHAR(120) NULL,
                ClaveAgrupacion CHAR(64) NOT NULL,
                HashEvento CHAR(64) NOT NULL,
                RequiereIncidencia TINYINT(1) NOT NULL DEFAULT 0,
                Estado VARCHAR(30) NOT NULL DEFAULT 'NUEVO',
                ArchivoOrigen VARCHAR(255) NULL,
                RegistradoPor INT UNSIGNED NOT NULL DEFAULT 0,
                RevisadoPor INT UNSIGNED NULL,
                ComentarioRevision VARCHAR(1000) NULL,
                FechaRegistro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FechaRevision DATETIME NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (IdEvento),
                UNIQUE KEY uq_evt_evento_hash (HashEvento),
                KEY idx_evt_fecha (FechaEvento),
                KEY idx_evt_estado_severidad (Estado,Severidad),
                KEY idx_evt_vm_fecha (IdVM,FechaEvento),
                KEY idx_evt_incidencia (IdIncidencia),
                KEY idx_evt_grupo (ClaveAgrupacion),
                CONSTRAINT fk_evt_vm FOREIGN KEY (IdVM) REFERENCES maquina_virtual (IdVM) ON DELETE SET NULL,
                CONSTRAINT fk_evt_aplicacion FOREIGN KEY (IdAplicacion) REFERENCES aplicacion (IdAplicacion) ON DELETE SET NULL,
                CONSTRAINT fk_evt_incidencia FOREIGN KEY (IdIncidencia) REFERENCES inc_tickets (IdTicket) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS evt_importaciones (
                IdImportacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                ArchivoOriginal VARCHAR(255) NOT NULL,
                HashArchivo CHAR(64) NOT NULL,
                Fuente VARCHAR(30) NULL,
                IPOrigen VARCHAR(45) NULL,
                CantidadLeida INT UNSIGNED NOT NULL DEFAULT 0,
                CantidadInsertada INT UNSIGNED NOT NULL DEFAULT 0,
                CantidadDuplicada INT UNSIGNED NOT NULL DEFAULT 0,
                Estado VARCHAR(20) NOT NULL DEFAULT 'PROCESADO',
                Mensaje VARCHAR(1000) NULL,
                FechaProceso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (IdImportacion),
                UNIQUE KEY uq_evt_importacion_hash (HashArchivo),
                KEY idx_evt_importacion_fecha (FechaProceso)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
        foreach ($queries as $query) $db->query($query);

        $rules = [
            ['Windows: apagado inesperado', 'WINDOWS', '6008', '', 'REINICIO', 'CRITICO', 1, 10, 'El cierre anterior del sistema fue inesperado.'],
            ['Windows: Kernel-Power', 'WINDOWS', '41', '', 'REINICIO', 'CRITICO', 1, 10, 'Reinicio o pérdida de energía sin apagado correcto.'],
            ['Windows: reinicio solicitado', 'WINDOWS', '1074', '', 'REINICIO', 'INFORMATIVO', 0, 20, 'Reinicio iniciado por un usuario, proceso o actualización.'],
            ['Windows: servicio detenido', 'WINDOWS', '7031', '', 'SERVICIO', 'ERROR', 1, 30, 'Un servicio terminó de forma inesperada.'],
            ['SUSE: kernel panic', 'SUSE', '', 'kernel panic', 'SISTEMA', 'CRITICO', 1, 10, 'Fallo crítico del kernel.'],
            ['SUSE: memoria agotada', 'SUSE', '', 'out of memory', 'MEMORIA', 'ERROR', 1, 20, 'El sistema activó el mecanismo OOM.'],
            ['SUSE: sistema de archivos solo lectura', 'SUSE', '', 'read-only file system', 'DISCO', 'CRITICO', 1, 20, 'Posible falla o corrupción del sistema de archivos.'],
            ['SUSE: servicio fallido', 'SUSE', '', 'failed', 'SERVICIO', 'ERROR', 0, 100, 'Servicio o unidad systemd con fallo.'],
            ['SAP HANA: servicio detenido', 'SAP_HANA', '', 'stopped', 'APLICACION', 'CRITICO', 1, 10, 'Servicio de SAP HANA detenido.'],
        ];
        foreach ($rules as $rule) {
            $escaped = array_map([$db, 'escape'], [$rule[0],$rule[1],$rule[2],$rule[3],$rule[4],$rule[5],$rule[8]]);
            $db->query("INSERT IGNORE INTO evt_reglas (Nombre,Fuente,CodigoEvento,PatronTexto,Categoria,Severidad,RequiereIncidencia,Orden,Descripcion)
                VALUES ('{$escaped[0]}','{$escaped[1]}',".($rule[2] !== '' ? "'{$escaped[2]}'" : 'NULL').",".($rule[3] !== '' ? "'{$escaped[3]}'" : 'NULL').",'{$escaped[4]}','{$escaped[5]}',".(int)$rule[6].",".(int)$rule[7].",'{$escaped[6]}')");
        }

        $tables = $db->query("SHOW TABLES LIKE 'modulos'");
        if ($db->num_rows($tables)) {
            $db->query("INSERT INTO modulos (slug,nombre,descripcion,icono,estado,orden)
                SELECT 'eventos_ti','Gestión de Eventos TI','Logs, eventos de servidores y vínculo con incidencias','fas fa-wave-square',1,44
                FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM modulos WHERE slug='eventos_ti')");
            $db->query("UPDATE modulos SET nombre='Gestión de Eventos TI',descripcion='Logs, eventos de servidores y vínculo con incidencias',icono='fas fa-wave-square',estado=1,orden=44 WHERE slug='eventos_ti'");
        }

        $permTables = $db->query("SHOW TABLES LIKE 'permisos_modulos'");
        $groupTables = $db->query("SHOW TABLES LIKE 'user_groups'");
        if ($db->num_rows($permTables) && $db->num_rows($groupTables)) {
            $groups = $db->query("SELECT group_level FROM user_groups WHERE group_status=1");
            while ($group = $db->fetch_assoc($groups)) {
                $level = (int)$group['group_level'];
                $allowed = $level <= 2 ? 1 : 0;
                $delete = $level === 1 ? 1 : 0;
                $db->query("INSERT INTO permisos_modulos (group_level,modulo,puede_ver,puede_crear,puede_editar,puede_eliminar)
                    SELECT {$level},'eventos_ti',{$allowed},{$allowed},{$allowed},{$delete}
                    FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permisos_modulos WHERE group_level={$level} AND modulo='eventos_ti')");
                $db->query("UPDATE permisos_modulos SET puede_ver={$allowed},puede_crear={$allowed},puede_editar={$allowed},puede_eliminar={$delete}
                    WHERE group_level={$level} AND modulo='eventos_ti'");
            }
        }
        $ready = true;
    }
}

if (!function_exists('evt_find_rule')) {
    function evt_find_rule(string $source, string $code, string $text): ?array
    {
        global $db;
        $sourceSql = $db->escape(strtoupper($source));
        $rules = find_by_sql("SELECT * FROM evt_reglas WHERE Activa=1 AND (Fuente='{$sourceSql}' OR Fuente='TODOS') ORDER BY Orden,IdRegla");
        foreach ($rules as $rule) {
            $ruleCode = trim((string)$rule['CodigoEvento']);
            $pattern = trim((string)$rule['PatronTexto']);
            if ($ruleCode !== '' && strcasecmp($ruleCode, $code) !== 0) continue;
            if ($pattern !== '' && mb_stripos($text, $pattern) === false) continue;
            if ($ruleCode === '' && $pattern === '') continue;
            return $rule;
        }
        return null;
    }
}

if (!function_exists('evt_find_infrastructure')) {
    function evt_find_infrastructure(string $ip, string $host): array
    {
        global $db;
        $result = ['IdVM' => null, 'IdMaquina' => null, 'NombreVM' => null, 'Host' => null];
        if ($ip !== '') {
            $ipSql = $db->escape($ip);
            $rows = find_by_sql("SELECT v.IdVM,v.IdMaquina,v.NombreVM,m.Host FROM maquina_virtual v INNER JOIN maquina m ON m.IdMaquina=v.IdMaquina WHERE v.IP_VM='{$ipSql}' LIMIT 2");
            if (count($rows) === 1) return $rows[0];
            $machines = find_by_sql("SELECT IdMaquina,Host FROM maquina WHERE Ip='{$ipSql}' LIMIT 2");
            if (count($machines) === 1) return ['IdVM'=>null,'IdMaquina'=>$machines[0]['IdMaquina'],'NombreVM'=>null,'Host'=>$machines[0]['Host']];
        }
        $normalized = str_replace('_', '', evt_normalize($host));
        if ($normalized !== '') {
            $rows = find_by_sql("SELECT v.IdVM,v.IdMaquina,v.NombreVM,m.Host FROM maquina_virtual v INNER JOIN maquina m ON m.IdMaquina=v.IdMaquina");
            foreach ($rows as $row) {
                if (str_replace('_', '', evt_normalize((string)$row['NombreVM'])) === $normalized) return $row;
            }
        }
        return $result;
    }
}

if (!function_exists('evt_event_text_key')) {
    function evt_event_text_key(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }
}

if (!function_exists('evt_event_fingerprint')) {
    function evt_event_fingerprint(string $source, string $host, string $ip, string $code, string $date, string $severity, string $title, string $message): string
    {
        return hash('sha256', implode('|', [
            strtoupper(trim($source)),
            evt_event_text_key($host),
            strtolower(trim($ip)),
            strtoupper(trim($code)),
            $date,
            strtoupper(trim($severity)),
            evt_event_text_key($title),
            evt_event_text_key($message),
        ]));
    }
}
if (!function_exists('evt_insert_event')) {
    function evt_insert_event(array $event, string $file = '', int $registeredBy = 0): array
    {
        global $db;
        $source = strtoupper(trim((string)($event['Fuente'] ?? 'OTRO')));
        $code = trim((string)($event['CodigoEvento'] ?? ''));
        $title = trim((string)($event['Titulo'] ?? 'Evento del sistema'));
        $message = trim((string)($event['Mensaje'] ?? $title));
        $date = trim((string)($event['FechaEvento'] ?? date('Y-m-d H:i:s')));
        $timestamp = strtotime($date);
        if ($timestamp === false) throw new RuntimeException('La fecha del evento no es válida.');
        $date = date('Y-m-d H:i:s', $timestamp);
        $ip = trim((string)($event['IPOrigen'] ?? ''));
        $host = trim((string)($event['HostOrigen'] ?? ''));
        $infra = evt_find_infrastructure($ip, $host);
        $idVm = (int)($event['IdVM'] ?? $infra['IdVM'] ?? 0);
        $idMachine = (int)($event['IdMaquina'] ?? $infra['IdMaquina'] ?? 0);
        $idApplication = (int)($event['IdAplicacion'] ?? 0);

        $rule = evt_find_rule($source, $code, $title.' '.$message);
        $severity = strtoupper(trim((string)($rule['Severidad'] ?? ($event['Severidad'] ?? 'INFORMATIVO'))));
        if (!in_array($severity, ['INFORMATIVO','ADVERTENCIA','ERROR','CRITICO'], true)) $severity = 'INFORMATIVO';
        $category = strtoupper(trim((string)($rule['Categoria'] ?? ($event['Categoria'] ?? 'SISTEMA'))));
        $requires = $rule !== null ? (int)$rule['RequiereIncidencia'] : (isset($event['RequiereIncidencia']) ? (int)(bool)$event['RequiereIncidencia'] : ($severity === 'CRITICO' ? 1 : 0));
        $provider = trim((string)($event['Proveedor'] ?? ''));
        $channel = strtoupper(trim((string)($event['Canal'] ?? 'LOG')));
        $correlation = $category === 'REINICIO' ? 'REINICIO' : ($code !== '' ? $code : evt_normalize($title));
        $groupKey = hash('sha256', implode('|', [$source,$idVm,$idMachine,$idApplication,$category,$correlation]));
        $eventHash = evt_event_fingerprint($source, $host, $ip, $code, $date, $severity, $title, $message);
        $eventHashSql = $db->escape($eventHash);
        $existing = find_by_sql("SELECT IdEvento FROM evt_eventos WHERE HashEvento='{$eventHashSql}' LIMIT 1");
        if ($existing) return ['inserted'=>false,'id'=>(int)$existing[0]['IdEvento']];

        // Compatibilidad con eventos importados antes de usar la huella normalizada.
        // Compara el mismo instante, origen, severidad y código, pero tolera diferencias de espacios y saltos de línea.
        $candidateWhere = "Fuente='".$db->escape($source)."' AND FechaEvento='".$db->escape($date)."' AND Severidad='".$db->escape($severity)."'".
            " AND COALESCE(IPOrigen,'')='".$db->escape($ip)."' AND COALESCE(CodigoEvento,'')='".$db->escape($code)."'";
        $candidates = find_by_sql("SELECT IdEvento,Titulo,Mensaje FROM evt_eventos WHERE {$candidateWhere} ORDER BY IdEvento DESC LIMIT 25");
        $titleKey = evt_event_text_key($title);
        $messageKey = evt_event_text_key($message);
        foreach ($candidates as $candidate) {
            if (evt_event_text_key((string)$candidate['Titulo']) === $titleKey && evt_event_text_key((string)$candidate['Mensaje']) === $messageKey) {
                return ['inserted'=>false,'id'=>(int)$candidate['IdEvento']];
            }
        }

        $values = array_map([$db, 'escape'], [$source,$provider,$channel,$code,$category,$severity,$date,$title,$message,$ip,$host,$groupKey,$eventHash,$file]);
        $json = json_encode($event, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
        $jsonSql = $json !== false ? "'".$db->escape($json)."'" : 'NULL';
        $db->query("INSERT IGNORE INTO evt_eventos
            (IdVM,IdMaquina,IdAplicacion,Fuente,Proveedor,Canal,CodigoEvento,Categoria,Severidad,FechaEvento,Titulo,Mensaje,DatosJson,IPOrigen,HostOrigen,ClaveAgrupacion,HashEvento,RequiereIncidencia,ArchivoOrigen,RegistradoPor)
            VALUES (".($idVm ?: 'NULL').",".($idMachine ?: 'NULL').",".($idApplication ?: 'NULL').",'{$values[0]}','{$values[1]}','{$values[2]}','{$values[3]}','{$values[4]}','{$values[5]}','{$values[6]}','{$values[7]}','{$values[8]}',{$jsonSql},'{$values[9]}','{$values[10]}','{$values[11]}','{$values[12]}',{$requires},'{$values[13]}',{$registeredBy})");
        $insertedId = (int)$db->insert_id();
        if ($insertedId <= 0) {
            $duplicate = find_by_sql("SELECT IdEvento FROM evt_eventos WHERE HashEvento='{$eventHashSql}' LIMIT 1");
            return ['inserted'=>false,'id'=>(int)($duplicate[0]['IdEvento'] ?? 0)];
        }
        return ['inserted'=>true,'id'=>$insertedId];
    }
}
