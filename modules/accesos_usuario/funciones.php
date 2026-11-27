<?php

function f08_column_exists(string $table, string $column): bool
{
    global $db;
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = $db->escape($column);
    $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $db->num_rows($result) > 0;
}

function f08_index_exists(string $table, string $index): bool
{
    global $db;
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $index = $db->escape($index);
    $result = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name='{$index}'");
    return $db->num_rows($result) > 0;
}

function ensure_accesos_usuario_schema(): void
{
    global $db;
    static $ready = false;
    if ($ready) return;

    $db->query("CREATE TABLE IF NOT EXISTS `usuariosf08` (
      `ID_Usuariosf08` INT NOT NULL AUTO_INCREMENT,
      `user_id` INT NULL,
      `puesto_id` INT NULL,
      `maquina_id` INT NULL,
      `sap_account_id` INT NULL,
      `orbis_account_id` INT NULL,
      `FechaRegistro` DATE NOT NULL,
      `TipoRegis` VARCHAR(100) NOT NULL,
      `NomApe` VARCHAR(150) NOT NULL,
      `Cargo` VARCHAR(150) NOT NULL DEFAULT '',
      `UsuarioTamadom` VARCHAR(50) NOT NULL DEFAULT '',
      `NumPC` VARCHAR(50) NOT NULL DEFAULT '',
      `Correo` VARCHAR(100) NOT NULL DEFAULT '',
      `FechaLabores` DATE NULL,
      `Celular_f8` VARCHAR(20) NOT NULL DEFAULT '',
      `CarpetasCompartidasM` TEXT NULL,
      `CarpetasCompartidasV` TEXT NULL,
      `corrcelu_f8` VARCHAR(100) NOT NULL DEFAULT 'NO',
      `ausb_f8` VARCHAR(20) NOT NULL DEFAULT 'NO',
      `ninavega_f8` VARCHAR(50) NOT NULL DEFAULT 'SIN_ACCESO',
      `nivcorr_f8` VARCHAR(50) NOT NULL DEFAULT 'SIN_ACCESO',
      `corrpuen_f8` VARCHAR(100) NOT NULL DEFAULT '',
      `softwarebasi_f8` VARCHAR(20) NOT NULL DEFAULT 'NO',
      `sap_f8` VARCHAR(20) NOT NULL DEFAULT 'NO',
      `saplicen_f8` VARCHAR(50) NOT NULL DEFAULT '',
      `ususap_f8` VARCHAR(30) NOT NULL DEFAULT '',
      `requerimient_f8` VARCHAR(30) NOT NULL DEFAULT 'NO',
      `requeutili_f8` VARCHAR(30) NOT NULL DEFAULT '',
      `usureque_f8` VARCHAR(30) NOT NULL DEFAULT '',
      `orbis_f8` VARCHAR(20) NOT NULL DEFAULT 'NO',
      `utilidadorb_f8` VARCHAR(50) NOT NULL DEFAULT '',
      `usuorb_f8` VARCHAR(30) NOT NULL DEFAULT '',
      `apptam_f8` VARCHAR(20) NOT NULL DEFAULT 'NO',
      `utilidapptam_f8` VARCHAR(30) NOT NULL DEFAULT '',
      `usuapptam_f8` VARCHAR(30) NOT NULL DEFAULT '',
      `Observaciones` VARCHAR(255) NOT NULL DEFAULT '',
      `estado_f8` TINYINT(1) NOT NULL DEFAULT 1,
      `created_by` INT NOT NULL DEFAULT 0,
      `updated_by` INT NOT NULL DEFAULT 0,
      `created_at` DATETIME NULL,
      `updated_at` DATETIME NULL,
      PRIMARY KEY (`ID_Usuariosf08`),
      KEY `idx_f08_user` (`user_id`, `estado_f8`),
      KEY `idx_f08_machine` (`maquina_id`),
      KEY `idx_f08_sap_account` (`sap_account_id`),
      KEY `idx_f08_orbis_account` (`orbis_account_id`),
      KEY `idx_f08_samba` (`UsuarioTamadom`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $columns = [
        'user_id' => "INT NULL AFTER `ID_Usuariosf08`",
        'puesto_id' => "INT NULL AFTER `user_id`",
        'maquina_id' => "INT NULL AFTER `puesto_id`",
        'sap_account_id' => "INT NULL AFTER `maquina_id`",
        'orbis_account_id' => "INT NULL AFTER `sap_account_id`",
        'created_by' => "INT NOT NULL DEFAULT 0 AFTER `estado_f8`",
        'updated_by' => "INT NOT NULL DEFAULT 0 AFTER `created_by`",
        'created_at' => "DATETIME NULL AFTER `updated_by`",
        'updated_at' => "DATETIME NULL AFTER `created_at`",
    ];
    foreach ($columns as $name => $definition) {
        if (!f08_column_exists('usuariosf08', $name)) {
            $db->query("ALTER TABLE `usuariosf08` ADD COLUMN `{$name}` {$definition}");
        }
    }

    $phoneColumn = $db->fetch_assoc($db->query("SHOW COLUMNS FROM `usuariosf08` LIKE 'Celular_f8'"));
    if ($phoneColumn && stripos((string)($phoneColumn['Type'] ?? ''), 'varchar') === false) {
        $db->query("ALTER TABLE `usuariosf08` MODIFY `Celular_f8` VARCHAR(20) NOT NULL DEFAULT ''");
    }
    $workDateColumn = $db->fetch_assoc($db->query("SHOW COLUMNS FROM `usuariosf08` LIKE 'FechaLabores'"));
    if ($workDateColumn && strtoupper((string)($workDateColumn['Null'] ?? 'NO')) === 'NO') {
        $db->query("ALTER TABLE `usuariosf08` MODIFY `FechaLabores` DATE NULL");
    }
    $orbisUtilityColumn = $db->fetch_assoc($db->query("SHOW COLUMNS FROM `usuariosf08` LIKE 'utilidadorb_f8'"));
    if ($orbisUtilityColumn && preg_match('/varchar\((\d+)\)/i', (string)($orbisUtilityColumn['Type'] ?? ''), $lengthMatch) && (int)$lengthMatch[1] < 50) {
        $db->query("ALTER TABLE `usuariosf08` MODIFY `utilidadorb_f8` VARCHAR(50) NOT NULL DEFAULT ''");
    }
    foreach (['CarpetasCompartidasM', 'CarpetasCompartidasV'] as $folderColumn) {
        $column = $db->fetch_assoc($db->query("SHOW COLUMNS FROM `usuariosf08` LIKE '{$folderColumn}'"));
        if ($column && stripos((string)($column['Type'] ?? ''), 'text') === false) {
            $db->query("ALTER TABLE `usuariosf08` MODIFY `{$folderColumn}` TEXT NULL");
        }
    }

    $idResult = $db->query("SHOW COLUMNS FROM `usuariosf08` LIKE 'ID_Usuariosf08'");
    $idColumn = $db->fetch_assoc($idResult);
    if ($idColumn && stripos((string)($idColumn['Extra'] ?? ''), 'auto_increment') === false) {
        $zeroResult = $db->query("SELECT COUNT(*) AS total FROM `usuariosf08` WHERE `ID_Usuariosf08`=0");
        $zero = $db->fetch_assoc($zeroResult);
        if ((int)($zero['total'] ?? 0) > 0) {
            $max = $db->fetch_assoc($db->query("SELECT COALESCE(MAX(`ID_Usuariosf08`),0)+1 AS next_id FROM `usuariosf08`"));
            $next = max(1, (int)($max['next_id'] ?? 1));
            $db->query("UPDATE `usuariosf08` SET `ID_Usuariosf08`={$next} WHERE `ID_Usuariosf08`=0 LIMIT 1");
        }
        $idIndex = $db->query("SHOW INDEX FROM `usuariosf08` WHERE `Column_name`='ID_Usuariosf08'");
        if ($db->num_rows($idIndex) === 0) {
            $db->query("ALTER TABLE `usuariosf08` ADD UNIQUE INDEX `uq_f08_id` (`ID_Usuariosf08`)");
        }
        $db->query("ALTER TABLE `usuariosf08` MODIFY `ID_Usuariosf08` INT NOT NULL AUTO_INCREMENT");
    }

    $indexes = [
        'idx_f08_user' => '`user_id`,`estado_f8`',
        'idx_f08_puesto' => '`puesto_id`',
        'idx_f08_machine' => '`maquina_id`',
        'idx_f08_sap_account' => '`sap_account_id`',
        'idx_f08_orbis_account' => '`orbis_account_id`',
        'idx_f08_samba' => '`UsuarioTamadom`',
    ];
    foreach ($indexes as $name => $fields) {
        if (!f08_index_exists('usuariosf08', $name)) {
            $db->query("ALTER TABLE `usuariosf08` ADD INDEX `{$name}` ({$fields})");
        }
    }

    $db->query("CREATE TABLE IF NOT EXISTS `usuariosf08_historial` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `registro_id` INT NOT NULL,
      `accion` VARCHAR(30) NOT NULL,
      `detalle` VARCHAR(500) NULL,
      `estado_anterior` TINYINT(1) NULL,
      `estado_nuevo` TINYINT(1) NULL,
      `registrado_por` INT NOT NULL DEFAULT 0,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_f08_historial_registro` (`registro_id`,`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $db->query("CREATE TABLE IF NOT EXISTS `usuariosf08_correos` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `registro_id` INT NOT NULL,
      `asunto` VARCHAR(180) NOT NULL DEFAULT '',
      `descripcion` VARCHAR(500) NOT NULL DEFAULT '',
      `nombre_original` VARCHAR(255) NOT NULL,
      `nombre_archivo` VARCHAR(120) NOT NULL,
      `ruta_relativa` VARCHAR(500) NOT NULL,
      `mime_type` VARCHAR(100) NOT NULL DEFAULT 'application/octet-stream',
      `extension` VARCHAR(10) NOT NULL,
      `tamano_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
      `estado` VARCHAR(15) NOT NULL DEFAULT 'VALIDO',
      `cargado_por` INT NOT NULL DEFAULT 0,
      `fecha_carga` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `actualizado_por` INT NOT NULL DEFAULT 0,
      `fecha_actualizacion` DATETIME NULL,
      PRIMARY KEY (`id`),
      KEY `idx_f08_correo_registro` (`registro_id`,`fecha_carga`),
      KEY `idx_f08_correo_estado` (`estado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $db->query("CREATE TABLE IF NOT EXISTS `f08_puestos` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `nombre` VARCHAR(180) NOT NULL,
      `nombre_clave` VARCHAR(180) NOT NULL,
      `area` VARCHAR(100) NOT NULL DEFAULT '',
      `descripcion` VARCHAR(500) NOT NULL DEFAULT '',
      `CarpetasCompartidasM` TEXT NULL,
      `CarpetasCompartidasV` TEXT NULL,
      `corrcelu_f8` VARCHAR(10) NOT NULL DEFAULT 'NO',
      `ausb_f8` VARCHAR(10) NOT NULL DEFAULT 'NO',
      `ninavega_f8` VARCHAR(50) NOT NULL DEFAULT 'SIN_ACCESO',
      `nivcorr_f8` VARCHAR(50) NOT NULL DEFAULT 'SIN_ACCESO',
      `corrpuen_f8` VARCHAR(100) NOT NULL DEFAULT '',
      `softwarebasi_f8` VARCHAR(10) NOT NULL DEFAULT 'SI',
      `sap_f8` VARCHAR(10) NOT NULL DEFAULT 'NO',
      `saplicen_f8` VARCHAR(50) NOT NULL DEFAULT '',
      `requerimient_f8` VARCHAR(10) NOT NULL DEFAULT 'NO',
      `requeutili_f8` VARCHAR(50) NOT NULL DEFAULT '',
      `orbis_f8` VARCHAR(10) NOT NULL DEFAULT 'NO',
      `utilidadorb_f8` VARCHAR(50) NOT NULL DEFAULT '',
      `apptam_f8` VARCHAR(10) NOT NULL DEFAULT 'NO',
      `utilidapptam_f8` VARCHAR(50) NOT NULL DEFAULT '',
      `configurado` TINYINT(1) NOT NULL DEFAULT 0,
      `activo` TINYINT(1) NOT NULL DEFAULT 1,
      `created_by` INT NOT NULL DEFAULT 0,
      `updated_by` INT NOT NULL DEFAULT 0,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_f08_puesto_clave` (`nombre_clave`),
      KEY `idx_f08_puesto_activo` (`activo`,`nombre`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $db->query("CREATE TABLE IF NOT EXISTS `f08_puestos_excluidos` (
      `nombre_clave` VARCHAR(180) NOT NULL,
      `nombre` VARCHAR(180) NOT NULL DEFAULT '',
      `eliminado_por` INT NOT NULL DEFAULT 0,
      `eliminado_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`nombre_clave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    f08_sync_positions();

    // Vincula el historico por las claves antiguas, sin modificar sus textos originales.
    $db->query("UPDATE `usuariosf08` f
      INNER JOIN `users` u ON
        CONVERT(TRIM(LOWER(u.`UserHomeTama`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci=
        CONVERT(TRIM(LOWER(f.`UsuarioTamadom`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci
      SET f.`user_id`=u.`id`
      WHERE f.`user_id` IS NULL AND TRIM(f.`UsuarioTamadom`)<>'' AND TRIM(u.`UserHomeTama`)<>''");
    $db->query("UPDATE `usuariosf08` f
      INNER JOIN `users` u ON
        CONVERT(TRIM(LOWER(u.`name`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci=
        CONVERT(TRIM(LOWER(f.`NomApe`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci
      SET f.`user_id`=u.`id`
      WHERE f.`user_id` IS NULL AND TRIM(f.`NomApe`)<>''");
    $db->query("UPDATE `usuariosf08` f
      INNER JOIN `maquina` m ON
        CONVERT(TRIM(LOWER(m.`Host`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci=
        CONVERT(TRIM(LOWER(f.`NumPC`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci
      SET f.`maquina_id`=m.`IdMaquina`
      WHERE f.`maquina_id` IS NULL AND TRIM(f.`NumPC`)<>''");
    $db->query("UPDATE `usuariosf08` f
      INNER JOIN (
        SELECT a.`id` AS user_id,MAX(a.`IdAsMaq`) AS assignment_id
        FROM `asig_maquina` a WHERE a.`Estado`=1 GROUP BY a.`id`
      ) current_assignment ON current_assignment.`user_id`=f.`user_id`
      INNER JOIN `asig_maquina` a ON a.`IdAsMaq`=current_assignment.`assignment_id`
      SET f.`maquina_id`=a.`IdMaquina`
      WHERE f.`maquina_id` IS NULL AND f.`user_id` IS NOT NULL");

    // Relaciona la ficha F08 con la cuenta maestra de Licencias SAP.
    // `usuariossap.UserId` es la clave comun con el maestro de colaboradores.
    $sapTable = $db->query("SHOW TABLES LIKE 'usuariossap'");
    if ($db->num_rows($sapTable) > 0) {
        if (!f08_column_exists('usuariossap', 'UserId')) {
            $db->query("ALTER TABLE `usuariossap` ADD COLUMN `UserId` INT NULL AFTER `IdUsuSap`");
        }
        if (!f08_index_exists('usuariossap', 'idx_usuariossap_user')) {
            $db->query("ALTER TABLE `usuariossap` ADD INDEX `idx_usuariossap_user` (`UserId`)");
        }
        $db->query("UPDATE `usuariossap` s
          INNER JOIN `users` u ON
            CONVERT(TRIM(LOWER(u.`name`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci=
            CONVERT(TRIM(LOWER(s.`NomUsuario`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci
          SET s.`UserId`=u.`id`
          WHERE s.`UserId` IS NULL AND TRIM(s.`NomUsuario`)<>'' AND UPPER(TRIM(s.`NomUsuario`))<>'LIBRE'");
        $db->query("UPDATE `usuariosf08` f
          INNER JOIN `usuariossap` s ON s.`UserId`=f.`user_id` AND s.`Estado`=1
          SET f.`sap_account_id`=s.`IdUsuSap`
          WHERE f.`sap_account_id` IS NULL AND UPPER(TRIM(f.`sap_f8`)) IN ('SI','S','1','YES')
            AND (TRIM(f.`ususap_f8`)='' OR
              CONVERT(TRIM(LOWER(f.`ususap_f8`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci=
              CONVERT(TRIM(LOWER(s.`UsuarioSap`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci)");
    }

    // Identificador estable del colaborador usado por Licencias Orbis y F08.
    $orbisTable = $db->query("SHOW TABLES LIKE 'usuariosorbis'");
    if ($db->num_rows($orbisTable) > 0) {
        $orbisIdColumn = $db->fetch_assoc($db->query("SHOW COLUMNS FROM `usuariosorbis` LIKE 'IdUsuOrbis'"));
        if ($orbisIdColumn && stripos((string)($orbisIdColumn['Extra'] ?? ''), 'auto_increment') === false) {
            $zeroAccount = $db->fetch_assoc($db->query("SELECT `IdUsuOrbis` FROM `usuariosorbis` WHERE `IdUsuOrbis`=0 LIMIT 1"));
            if ($zeroAccount) {
                $nextAccount = $db->fetch_assoc($db->query("SELECT COALESCE(MAX(`IdUsuOrbis`),0)+1 AS next_id FROM `usuariosorbis`"));
                $nextAccountId = max(1, (int)($nextAccount['next_id'] ?? 1));
                $db->query("UPDATE `usuariosorbis` SET `IdUsuOrbis`={$nextAccountId} WHERE `IdUsuOrbis`=0 LIMIT 1");
                $db->query("UPDATE `usuariosf08` SET `orbis_account_id`={$nextAccountId} WHERE `orbis_account_id`=0");
            }
            $orbisIdIndex = $db->query("SHOW INDEX FROM `usuariosorbis` WHERE `Column_name`='IdUsuOrbis'");
            if ($db->num_rows($orbisIdIndex) === 0) {
                $db->query("ALTER TABLE `usuariosorbis` ADD PRIMARY KEY (`IdUsuOrbis`)");
            }
            $db->query("ALTER TABLE `usuariosorbis` MODIFY `IdUsuOrbis` INT NOT NULL AUTO_INCREMENT");
        }
        if (!f08_column_exists('usuariosorbis', 'UserId')) {
            $db->query("ALTER TABLE `usuariosorbis` ADD COLUMN `UserId` INT NULL AFTER `IdUsuOrbis`");
        }
        if (!f08_index_exists('usuariosorbis', 'idx_usuariosorbis_user')) {
            $db->query("ALTER TABLE `usuariosorbis` ADD INDEX `idx_usuariosorbis_user` (`UserId`)");
        }
        $db->query("UPDATE `usuariosorbis` o
          INNER JOIN `users` u ON
            CONVERT(TRIM(LOWER(u.`name`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci=
            CONVERT(TRIM(LOWER(o.`NomUsuario`)) USING utf8mb4) COLLATE utf8mb4_unicode_ci
          SET o.`UserId`=u.`id`
          WHERE o.`UserId` IS NULL AND TRIM(o.`NomUsuario`)<>''");
    }

    $db->query("INSERT INTO `modulos` (`slug`,`nombre`,`descripcion`,`icono`,`estado`,`orden`)
      SELECT 'accesos_usuario','Accesos de Usuario','Registro T-TI-F-08: Samba, equipo y aplicaciones','fas fa-user-shield',1,34
      FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `modulos` WHERE `slug`='accesos_usuario')");
    $groups = $db->query("SELECT `group_level` FROM `user_groups` WHERE `group_status`=1");
    while ($group = $db->fetch_assoc($groups)) {
        $level = (int)$group['group_level'];
        $create = $level <= 2 ? 1 : 0;
        $edit = $level <= 2 ? 1 : 0;
        $db->query("INSERT IGNORE INTO `permisos_modulos`
          (`group_level`,`modulo`,`puede_ver`,`puede_crear`,`puede_editar`,`puede_eliminar`)
          VALUES ({$level},'accesos_usuario',1,{$create},{$edit},0)");
    }

    $ready = true;
}

function f08_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function f08_q($value): string
{
    global $db;
    return "'" . $db->escape((string)$value) . "'";
}

function f08_get_record(int $id): ?array
{
    global $db;
    $result = $db->query("SELECT f.*,u.`area`,u.`subarea`,m.`Tipo_Activo`,m.`EstadoMaquina`
      FROM `usuariosf08` f
      LEFT JOIN `users` u ON u.`id`=f.`user_id`
      LEFT JOIN `maquina` m ON m.`IdMaquina`=f.`maquina_id`
      WHERE f.`ID_Usuariosf08`={$id} LIMIT 1");
    $row = $db->fetch_assoc($result);
    return $row ?: null;
}

function f08_yes($value): bool
{
    return in_array(strtoupper(trim((string)$value)), ['SI', 'S', '1', 'YES'], true);
}

function f08_position_name(string $position): string
{
    $position = trim((string)preg_replace('/\s+/u', ' ', $position));
    return trim((string)preg_replace('/\s+\d+\s*$/u', '', $position));
}

function f08_position_key(string $position): string
{
    return mb_strtolower(f08_position_name($position), 'UTF-8');
}

function f08_template_fields(): array
{
    return [
        'CarpetasCompartidasM','CarpetasCompartidasV','corrcelu_f8','ausb_f8',
        'ninavega_f8','nivcorr_f8','corrpuen_f8','softwarebasi_f8',
        'sap_f8','saplicen_f8','requerimient_f8','requeutili_f8',
        'orbis_f8','utilidadorb_f8','apptam_f8','utilidapptam_f8',
    ];
}

function f08_sync_positions(): void
{
    global $db;
    static $synced = false;
    if ($synced) return;
    $synced = true;

    $existingTemplates = [];
    $history = $db->query("SELECT * FROM `usuariosf08` WHERE TRIM(`Cargo`)<>'' ORDER BY `estado_f8` DESC,`FechaRegistro` DESC,`ID_Usuariosf08` DESC");
    while ($row = $db->fetch_assoc($history)) {
        $key = f08_position_key((string)$row['Cargo']);
        if ($key !== '' && !isset($existingTemplates[$key])) $existingTemplates[$key] = $row;
    }

    $candidates = [];
    $users = $db->query("SELECT `cargo`,`area` FROM `users` WHERE TRIM(COALESCE(`cargo`,''))<>'' ORDER BY `cargo`");
    while ($user = $db->fetch_assoc($users)) $candidates[] = $user;
    foreach ($existingTemplates as $source) {
        $candidates[] = ['cargo' => (string)$source['Cargo'], 'area' => 'Historial F08'];
    }
    $seen = [];
    foreach ($candidates as $user) {
        $name = f08_position_name((string)$user['cargo']);
        $key = f08_position_key($name);
        if ($key === '' || isset($seen[$key])) continue;
        $seen[$key] = true;
        $excluded = $db->fetch_assoc($db->query("SELECT `nombre_clave` FROM `f08_puestos_excluidos` WHERE `nombre_clave`=" . f08_q($key) . " LIMIT 1"));
        if ($excluded) continue;
        $exists = $db->fetch_assoc($db->query("SELECT `id` FROM `f08_puestos` WHERE `nombre_clave`=" . f08_q($key) . " LIMIT 1"));
        if ($exists) continue;

        $source = $existingTemplates[$key] ?? null;
        $columns = ['nombre','nombre_clave','area','configurado'];
        $values = [f08_q($name),f08_q($key),f08_q((string)($user['area'] ?? '')),$source ? '1' : '0'];
        if ($source) {
            foreach (f08_template_fields() as $field) {
                $columns[] = $field;
                $values[] = f08_q((string)($source[$field] ?? ''));
            }
        }
        $quoted = array_map(static function($column){ return "`{$column}`"; }, $columns);
        $db->query("INSERT INTO `f08_puestos` (" . implode(',', $quoted) . ") VALUES (" . implode(',', $values) . ")");
    }

    $positionMap = [];
    $positionRows = $db->query("SELECT `id`,`nombre_clave` FROM `f08_puestos`");
    while ($position = $db->fetch_assoc($positionRows)) $positionMap[(string)$position['nombre_clave']] = (int)$position['id'];
    $unlinked = $db->query("SELECT `ID_Usuariosf08`,`Cargo` FROM `usuariosf08` WHERE `puesto_id` IS NULL AND TRIM(`Cargo`)<>''");
    while ($record = $db->fetch_assoc($unlinked)) {
        $positionId = $positionMap[f08_position_key((string)$record['Cargo'])] ?? 0;
        if ($positionId > 0) {
            $recordId = (int)$record['ID_Usuariosf08'];
            $db->query("UPDATE `usuariosf08` SET `puesto_id`={$positionId} WHERE `ID_Usuariosf08`={$recordId} LIMIT 1");
        }
    }
}

function f08_get_positions(bool $onlyActive = true): array
{
    $where = $onlyActive ? 'WHERE `activo`=1' : '';
    $positions = find_by_sql("SELECT * FROM `f08_puestos` {$where} ORDER BY `activo` DESC,`nombre`");
    $counts = [];
    $linkedUsers = find_by_sql("SELECT f.`puesto_id`,f.`user_id`,u.`status`,u.`estado`
      FROM `usuariosf08` f
      INNER JOIN `users` u ON u.`id`=f.`user_id`
      WHERE f.`puesto_id` IS NOT NULL AND f.`puesto_id`>0 AND f.`estado_f8`=1
      GROUP BY f.`puesto_id`,f.`user_id`,u.`status`,u.`estado`");
    foreach ($linkedUsers as $user) {
        $positionId = (int)$user['puesto_id'];
        if (!isset($counts[$positionId])) $counts[$positionId] = ['total' => 0, 'activos' => 0, 'inactivos' => 0];
        $counts[$positionId]['total']++;
        if ((int)$user['status'] === 1 && (int)$user['estado'] === 1) $counts[$positionId]['activos']++;
        else $counts[$positionId]['inactivos']++;
    }
    foreach ($positions as &$position) {
        $positionCounts = $counts[(int)$position['id']] ?? ['total' => 0, 'activos' => 0, 'inactivos' => 0];
        $position['colaboradores'] = $positionCounts['total'];
        $position['colaboradores_activos'] = $positionCounts['activos'];
        $position['colaboradores_inactivos'] = $positionCounts['inactivos'];
    }
    unset($position);
    return $positions;
}

function f08_samba_catalog(): array
{
    return [
        '1' => ['1.10','1.11','1.12','1.13','1.14','1.20','1.21','1.22','1.30','1.31','1.32','1.33','1.40','1.42','1.43'],
        '2' => ['2.10','2.11','2.12','2.13','2.14','2.20','2.30'],
        '3' => ['3.10','3.11','3.12','3.20','3.21','3.30','3.31','3.40','3.41','3.50','3.60','3.70','3.80'],
        '4' => ['4.05','4.10','4.11','4.20','4.30','4.31','4.32','4.40','4.50','4.51','4.60','4.70','4.80','4.90'],
        '5' => ['5.10','5.20','5.21','5.30','5.40','5.50','5.59'],
        'especial' => ['Vault'],
    ];
}

function f08_samba_labels(): array
{
    return [
        '1.10'=>'Gerencia General','1.11'=>'SAP ERP','1.12'=>'Clientes 2026','1.13'=>'Gestión de Personas en Proceso','1.14'=>'Videos',
        '1.20'=>'Gestión de Personas','1.21'=>'Seguridad, Salud y Medio Ambiente','1.22'=>'Seguridad, Salud y Medio Ambiente Sup.',
        '1.30'=>'Gerencia de Desarrollo','1.31'=>'Roca Backup abril 2025 / Roca ISO','1.32'=>'Roca ISO 2022','1.33'=>'Roca ISO Backup 2024',
        '1.40'=>'Sistemas TI','1.42'=>'Rad','1.43'=>'Bienestar Social',
        '2.10'=>'Gerencia de Administración y Finanzas','2.11'=>'Contabilidad General','2.12'=>'Tesorería, Facturación y Cobranzas',
        '2.13'=>'Contabilidad de Costos','2.14'=>'ADCompartido','2.20'=>'Almacén','2.30'=>'Varios G.P. y Administración',
        '3.10'=>'Gerencia de Operaciones','3.11'=>'Asistente de G.O. 1','3.12'=>'Asistente de G.O. 2',
        '3.20'=>'Planeamiento y Control de la Producción','3.21'=>'Desarrollo PCP','3.30'=>'Producción Local 1','3.31'=>'Producción Local 2',
        '3.40'=>'Aseguramiento y Control de la Calidad','3.41'=>'Documentos de Calidad en Proceso','3.50'=>'Logística',
        '3.60'=>'Acabados','3.70'=>'Mantenimiento','3.80'=>'Archivo Operaciones Varios',
        '4.05'=>'Gerencia Comercial','4.10'=>'Ventas','4.11'=>'Hardox Comercial','4.20'=>'Presupuestos',
        '4.30'=>'Ventas y Presupuestos Actuales','4.31'=>'Compartido Presupuesto','4.32'=>'Presupuesto Internet','4.40'=>'Proyectos',
        '4.50'=>'Ingeniería','4.51'=>'Hardox Ingeniería','4.60'=>'Ingeniería Nuevos','4.70'=>'Proyectos Especiales de Ingeniería',
        '4.80'=>'Proyectos en Obra','4.90'=>'Biblioteca Investigación Comercial',
        '5.10'=>'Internet','5.20'=>'Varios','5.21'=>'Vigilancia','5.30'=>'Servicios Terceros','5.40'=>'ProgramC-CUT',
        '5.50'=>'Biblioteca Virtual','5.59'=>'Biblioteca Virtual 2','Vault'=>'Bóveda documental Vault',
    ];
}

function f08_samba_has_folder(string $stored, string $folder): bool
{
    if (trim($stored) === '') return false;
    $escaped = preg_quote($folder, '/');
    $pattern = preg_match('/^[0-9.]+$/', $folder)
        ? '/(?<![0-9.])' . $escaped . '(?![0-9.])/i'
        : '/(?<![a-z0-9_])' . $escaped . '(?![a-z0-9_])/i';
    return preg_match($pattern, $stored) === 1;
}

function f08_samba_custom_value(string $stored, array $catalog): string
{
    $remaining = trim($stored);
    foreach ($catalog as $folders) {
        foreach ($folders as $folder) {
            $escaped = preg_quote($folder, '/');
            $pattern = preg_match('/^[0-9.]+$/', $folder)
                ? '/(?<![0-9.])' . $escaped . '(?![0-9.])/i'
                : '/(?<![a-z0-9_])' . $escaped . '(?![a-z0-9_])/i';
            $remaining = (string)preg_replace($pattern, '', $remaining);
        }
    }
    $remaining = trim($remaining, " \t\n\r\0\x0B,;-|()[]");
    if ($remaining === '' || preg_match('/[a-z0-9\\\\\/]/i', $remaining) !== 1) return '';
    return preg_replace('/\s{2,}/', ' ', $remaining) ?? $remaining;
}

function f08_date_display($value): string
{
    $value = trim((string)$value);
    if ($value === '' || $value === '0000-00-00') return '-';
    $time = strtotime($value);
    return $time ? date('d/m/Y', $time) : $value;
}

function f08_audit(int $recordId, string $action, string $detail, ?int $oldStatus, ?int $newStatus): void
{
    global $db;
    $user = current_user();
    $userId = (int)($user['id'] ?? 0);
    $oldSql = $oldStatus === null ? 'NULL' : (string)$oldStatus;
    $newSql = $newStatus === null ? 'NULL' : (string)$newStatus;
    $detail = mb_substr(trim($detail), 0, 500);
    $db->query("INSERT INTO `usuariosf08_historial`
      (`registro_id`,`accion`,`detalle`,`estado_anterior`,`estado_nuevo`,`registrado_por`)
      VALUES ({$recordId}," . f08_q($action) . ',' . f08_q($detail) . ",{$oldSql},{$newSql},{$userId})");
}

ensure_accesos_usuario_schema();
