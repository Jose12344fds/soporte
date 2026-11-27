<?php

function mti_ensure_auto_increment(): void {
    global $db;
    static $ready = false;
    if ($ready) return;
    $tables = [
        'activos_mantenimiento_planes',
        'activos_mantenimiento_ocurrencias',
        'activos_mantenimiento_ocurrencia_historial',
        'activos_mantenimientos',
        'activos_mantenimiento_detalle',
        'activos_mantenimiento_historial',
    ];
    $db->query('SET FOREIGN_KEY_CHECKS=0');
    foreach ($tables as $table) {
        $zero = $db->fetch_assoc($db->query("SELECT id FROM {$table} WHERE id=0 LIMIT 1"));
        if (!$zero) continue;
        $maxRow = $db->fetch_assoc($db->query("SELECT COALESCE(MAX(id),0) AS max_id FROM {$table}"));
        $newId = max(1, (int)($maxRow['max_id'] ?? 0) + 1);
        $db->query("UPDATE {$table} SET id={$newId} WHERE id=0 LIMIT 1");
        if ($table === 'activos_mantenimiento_planes') {
            $db->query("UPDATE activos_mantenimiento_ocurrencias SET plan_id={$newId} WHERE plan_id=0");
            $db->query("UPDATE activos_mantenimiento_planes SET codigo='MTIP-" . str_pad((string)$newId, 6, '0', STR_PAD_LEFT) . "' WHERE id={$newId}");
        } elseif ($table === 'activos_mantenimiento_ocurrencias') {
            $db->query("UPDATE activos_mantenimiento_ocurrencia_historial SET ocurrencia_id={$newId} WHERE ocurrencia_id=0");
            $db->query("UPDATE activos_mantenimiento_ocurrencias SET codigo='MTIO-" . str_pad((string)$newId, 7, '0', STR_PAD_LEFT) . "' WHERE id={$newId}");
        } elseif ($table === 'activos_mantenimientos') {
            $db->query("UPDATE activos_mantenimiento_ocurrencias SET mantenimiento_id={$newId} WHERE mantenimiento_id=0");
            $db->query("UPDATE activos_mantenimiento_detalle SET mantenimiento_id={$newId} WHERE mantenimiento_id=0");
            $db->query("UPDATE activos_mantenimiento_historial SET mantenimiento_id={$newId} WHERE mantenimiento_id=0");
            $db->query("UPDATE activos_mantenimientos SET folio='MTI-" . date('Y') . '-' . str_pad((string)$newId, 6, '0', STR_PAD_LEFT) . "' WHERE id={$newId}");
        }
    }
    foreach ($tables as $table) {
        $column = $db->fetch_assoc($db->query("SHOW COLUMNS FROM {$table} WHERE Field='id'"));
        if ($column && stripos((string)($column['Extra'] ?? ''), 'auto_increment') === false) {
            $db->query("ALTER TABLE {$table} MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        }
    }
    $db->query('SET FOREIGN_KEY_CHECKS=1');
    // Recupera ocurrencias cuyo proceso se interrumpio antes de crear la OT.
    $db->query("UPDATE activos_mantenimiento_ocurrencias SET estado='PENDIENTE'"
        . " WHERE estado='GENERANDO_OT' AND mantenimiento_id IS NULL");
    $ready = true;
}

mti_ensure_auto_increment();

function mti_ensure_first_scheduled_date(): void {
    global $db;
    static $ready = false;
    if ($ready) return;

    $column = $db->fetch_assoc(
        $db->query("SHOW COLUMNS FROM activos_mantenimiento_planes WHERE Field='primera_fecha_programada'")
    );
    if (!$column) {
        $db->query(
            "ALTER TABLE activos_mantenimiento_planes"
            . " ADD COLUMN primera_fecha_programada DATE NULL AFTER fecha_inicio"
        );
    }

    // Compatibilidad: los planes creados antes de este cambio conservan su calendario actual.
    $db->query(
        "UPDATE activos_mantenimiento_planes"
        . " SET primera_fecha_programada=fecha_inicio"
        . " WHERE primera_fecha_programada IS NULL"
    );
    $column = $db->fetch_assoc(
        $db->query("SHOW COLUMNS FROM activos_mantenimiento_planes WHERE Field='primera_fecha_programada'")
    );
    if ($column && strtoupper((string)($column['Null'] ?? 'YES')) === 'YES') {
        $db->query(
            "ALTER TABLE activos_mantenimiento_planes"
            . " MODIFY primera_fecha_programada DATE NOT NULL"
        );
    }
    $ready = true;
}

mti_ensure_first_scheduled_date();

function mti_plan_q($value): string {
    global $db;
    return "'" . $db->escape((string)$value) . "'";
}

function mti_plan_nullable($value): string {
    return $value === null || trim((string)$value) === '' ? 'NULL' : mti_plan_q($value);
}

function mti_plan_find(int $id): ?array {
    global $db;
    $result = $db->query("SELECT * FROM activos_mantenimiento_planes WHERE id={$id} LIMIT 1");
    $row = $db->fetch_assoc($result);
    return $row ?: null;
}

function mti_plan_next_date(DateTimeImmutable $date, int $interval, string $unit): DateTimeImmutable {
    $interval = max(1, $interval);
    $units = ['DIA' => 'D', 'SEMANA' => 'W', 'MES' => 'M', 'ANIO' => 'Y'];
    $suffix = $units[$unit] ?? 'M';
    return $date->add(new DateInterval('P' . $interval . $suffix));
}

function mti_generate_occurrences(array $plan, ?string $until = null): int {
    global $db;
    $planId = (int)$plan['id'];
    $interval = max(1, (int)$plan['intervalo']);
    $unit = strtoupper((string)$plan['unidad_intervalo']);
    $anchor = !empty($plan['primera_fecha_programada'])
        ? $plan['primera_fecha_programada']
        : $plan['fecha_inicio'];
    $start = new DateTimeImmutable((string)$anchor);

    $lastResult = $db->query(
        "SELECT MAX(fecha_original) AS ultima FROM activos_mantenimiento_ocurrencias WHERE plan_id={$planId}"
    );
    $lastRow = $db->fetch_assoc($lastResult);
    if (!empty($lastRow['ultima'])) {
        $start = mti_plan_next_date(new DateTimeImmutable($lastRow['ultima']), $interval, $unit);
    }

    if ($until) {
        $end = new DateTimeImmutable($until);
    } elseif (!empty($plan['fecha_fin'])) {
        $end = new DateTimeImmutable($plan['fecha_fin']);
    } else {
        // Sin fecha final se genera un horizonte cerrado de 12 meses desde
        // la primera fecha pendiente, sin arrastrar anos hasta la fecha actual.
        $end = $start->add(new DateInterval('P12M'))->sub(new DateInterval('P1D'));
    }

    $created = 0;
    $user = current_user();
    $userId = (int)($user['id'] ?? 0);
    for ($date = $start; $date <= $end; $date = mti_plan_next_date($date, $interval, $unit)) {
        $value = $date->format('Y-m-d');
        $db->query(
            "INSERT IGNORE INTO activos_mantenimiento_ocurrencias"
            . " (plan_id,fecha_original,fecha_programada,estado,observacion,tecnico_responsable,creado_por,actualizado_por)"
            . " VALUES ({$planId}," . mti_plan_q($value) . ',' . mti_plan_q($value)
            . ",'PENDIENTE',NULL,NULL,{$userId},{$userId})"
        );
        $occurrenceId = (int)$db->insert_id();
        if ($occurrenceId <= 0) continue;
        $code = 'MTIO-' . str_pad((string)$occurrenceId, 7, '0', STR_PAD_LEFT);
        $db->query(
            "UPDATE activos_mantenimiento_ocurrencias SET codigo=" . mti_plan_q($code)
            . " WHERE id={$occurrenceId} LIMIT 1"
        );
        $db->query(
            "INSERT INTO activos_mantenimiento_ocurrencia_historial"
            . " (ocurrencia_id,accion,fecha_nueva,estado_nuevo,comentario,registrado_por)"
            . " VALUES ({$occurrenceId},'GENERACION'," . mti_plan_q($value)
            . ",'PENDIENTE','Ocurrencia generada desde el plan',{$userId})"
        );
        $created++;
    }
    if ($created > 0) {
        $lastGenerated = $date->sub(new DateInterval('P' . $interval . (['DIA'=>'D','SEMANA'=>'W','MES'=>'M','ANIO'=>'Y'][$unit] ?? 'M')))->format('Y-m-d');
        $db->query(
            "UPDATE activos_mantenimiento_planes SET ultima_fecha_generada=" . mti_plan_q($lastGenerated)
            . " WHERE id={$planId} LIMIT 1"
        );
    }
    return $created;
}
