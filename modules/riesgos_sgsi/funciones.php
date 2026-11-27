<?php

require_once __DIR__ . '/../../api/activos/_interno/compartido.php';
require_once __DIR__ . '/../../api/activos/_interno/inventario/repositorio.php';

if (!function_exists('csrf_require')) {
    function csrf_require(): void { csrf_validate_or_fail(); }
}

function sgsi_q($value): string {
    global $db;
    return "'" . $db->escape((string)$value) . "'";
}
function sgsi_nullable($value): string {
    return trim((string)$value) === '' ? 'NULL' : sgsi_q($value);
}
function sgsi_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function sgsi_user_id(): int {
    $user = current_user();
    return (int)($user['id'] ?? 0);
}
function sgsi_user_name(): string {
    $user = current_user();
    return trim((string)($user['name'] ?? $user['username'] ?? 'Sistema'));
}

function sgsi_ensure_schema(): void {
    global $db;
    static $ready = false;
    if ($ready) return;

    $db->query("CREATE TABLE IF NOT EXISTS sgsi_riesgos (
      IdRiesgo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      Codigo VARCHAR(30) NULL,
      Escenario VARCHAR(700) NOT NULL,
      Proceso VARCHAR(150) NOT NULL,
      DuenoRiesgo VARCHAR(150) NOT NULL,
      Estado VARCHAR(30) NOT NULL DEFAULT 'IDENTIFICADO',
      Tratamiento VARCHAR(20) NULL,
      FechaRevision DATE NULL,
      Observaciones TEXT NULL,
      CreadoPor INT NOT NULL DEFAULT 0,
      ActualizadoPor INT NOT NULL DEFAULT 0,
      FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (IdRiesgo), UNIQUE KEY uq_sgsi_riesgo_codigo (Codigo),
      KEY idx_sgsi_riesgo_estado (Estado), KEY idx_sgsi_riesgo_proceso (Proceso)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS sgsi_riesgo_activos (
      IdRelacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      IdRiesgo BIGINT UNSIGNED NOT NULL,
      TipoActivo VARCHAR(20) NOT NULL,
      IdActivo INT NOT NULL,
      EsPrincipal TINYINT(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (IdRelacion), UNIQUE KEY uq_sgsi_riesgo_activo (IdRiesgo,TipoActivo,IdActivo),
      KEY idx_sgsi_activo (TipoActivo,IdActivo),
      CONSTRAINT fk_sgsi_ra_riesgo FOREIGN KEY (IdRiesgo) REFERENCES sgsi_riesgos(IdRiesgo) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS sgsi_controles (
      IdControl BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      Codigo VARCHAR(40) NOT NULL,
      Descripcion VARCHAR(700) NOT NULL,
      Responsable VARCHAR(150) NOT NULL,
      Frecuencia VARCHAR(100) NULL,
      Estado VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE_VALIDACION',
      Evidencia VARCHAR(700) NULL,
      OrigenPlanId BIGINT UNSIGNED NULL,
      CreadoPor INT NOT NULL DEFAULT 0,
      FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (IdControl), UNIQUE KEY uq_sgsi_control_codigo (Codigo), KEY idx_sgsi_control_estado (Estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS sgsi_riesgo_controles (
      IdRelacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      IdRiesgo BIGINT UNSIGNED NOT NULL,
      IdControl BIGINT UNSIGNED NOT NULL,
      FechaVinculacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (IdRelacion), UNIQUE KEY uq_sgsi_riesgo_control (IdRiesgo,IdControl),
      CONSTRAINT fk_sgsi_rc_riesgo FOREIGN KEY (IdRiesgo) REFERENCES sgsi_riesgos(IdRiesgo) ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT fk_sgsi_rc_control FOREIGN KEY (IdControl) REFERENCES sgsi_controles(IdControl) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS sgsi_planes (
      IdPlan BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      IdRiesgo BIGINT UNSIGNED NOT NULL,
      Codigo VARCHAR(40) NULL,
      Accion VARCHAR(900) NOT NULL,
      Responsable VARCHAR(150) NOT NULL,
      FechaProgramada DATE NULL,
      Estado VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE',
      Evidencia VARCHAR(700) NULL,
      Aprobacion VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE',
      ControlGeneradoId BIGINT UNSIGNED NULL,
      CreadoPor INT NOT NULL DEFAULT 0,
      FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (IdPlan), UNIQUE KEY uq_sgsi_plan_codigo (Codigo), KEY idx_sgsi_plan_riesgo (IdRiesgo),
      CONSTRAINT fk_sgsi_plan_riesgo FOREIGN KEY (IdRiesgo) REFERENCES sgsi_riesgos(IdRiesgo) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS sgsi_evaluaciones (
      IdEvaluacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      IdRiesgo BIGINT UNSIGNED NOT NULL,
      Tipo VARCHAR(15) NOT NULL,
      FechaEvaluacion DATE NOT NULL,
      Probabilidad TINYINT UNSIGNED NOT NULL,
      Impacto TINYINT UNSIGNED NOT NULL,
      Puntaje TINYINT UNSIGNED NOT NULL,
      Nivel VARCHAR(20) NOT NULL,
      Evaluador VARCHAR(150) NOT NULL,
      Justificacion VARCHAR(1000) NOT NULL,
      CreadoPor INT NOT NULL DEFAULT 0,
      FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (IdEvaluacion), KEY idx_sgsi_eval_riesgo (IdRiesgo,Tipo,FechaEvaluacion),
      CONSTRAINT fk_sgsi_eval_riesgo FOREIGN KEY (IdRiesgo) REFERENCES sgsi_riesgos(IdRiesgo) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS sgsi_eficacias (
      IdEficacia BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      IdRiesgo BIGINT UNSIGNED NOT NULL,
      TipoObjetivo VARCHAR(15) NOT NULL,
      IdObjetivo BIGINT UNSIGNED NOT NULL,
      FechaVerificacion DATE NOT NULL,
      Resultado VARCHAR(20) NOT NULL,
      Responsable VARCHAR(150) NOT NULL,
      Evidencia VARCHAR(700) NOT NULL,
      Observacion VARCHAR(1000) NULL,
      CreadoPor INT NOT NULL DEFAULT 0,
      FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (IdEficacia), KEY idx_sgsi_eficacia_riesgo (IdRiesgo,FechaVerificacion),
      CONSTRAINT fk_sgsi_eficacia_riesgo FOREIGN KEY (IdRiesgo) REFERENCES sgsi_riesgos(IdRiesgo) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS sgsi_aceptaciones (
      IdAceptacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      IdRiesgo BIGINT UNSIGNED NOT NULL,
      IdEvaluacion BIGINT UNSIGNED NOT NULL,
      Decision VARCHAR(15) NOT NULL,
      Aprobador VARCHAR(150) NOT NULL,
      FechaDecision DATE NOT NULL,
      Justificacion VARCHAR(1000) NOT NULL,
      FechaRevision DATE NOT NULL,
      CreadoPor INT NOT NULL DEFAULT 0,
      FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (IdAceptacion), KEY idx_sgsi_acepta_riesgo (IdRiesgo,FechaDecision),
      CONSTRAINT fk_sgsi_acepta_riesgo FOREIGN KEY (IdRiesgo) REFERENCES sgsi_riesgos(IdRiesgo) ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT fk_sgsi_acepta_eval FOREIGN KEY (IdEvaluacion) REFERENCES sgsi_evaluaciones(IdEvaluacion) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $module = $db->fetch_assoc($db->query("SELECT id FROM modulos WHERE slug='riesgos_sgsi' LIMIT 1"));
    $nextModuleRow = $db->fetch_assoc($db->query("SELECT COALESCE(MAX(id),0)+1 AS next_id FROM modulos"));
    $nextModuleId = max(1, (int)($nextModuleRow['next_id'] ?? 1));
    if (!$module) {
        $db->query("INSERT INTO modulos (id,slug,nombre,descripcion,icono,estado,orden) VALUES ({$nextModuleId},'riesgos_sgsi','Matriz de Riesgos SGSI','Riesgos, controles, tratamientos y seguimiento ISO 27001','fas fa-shield-alt',1,48)");
    } elseif ((int)$module['id'] <= 0) {
        $db->query("UPDATE modulos SET id={$nextModuleId} WHERE slug='riesgos_sgsi' AND id=0");
    }
    $db->query("UPDATE modulos SET nombre='Matriz de Riesgos SGSI',descripcion='Riesgos, controles, tratamientos y seguimiento ISO 27001',icono='fas fa-shield-alt',estado=1 WHERE slug='riesgos_sgsi'");
    $groups = $db->query("SELECT group_level FROM user_groups WHERE group_status=1");
    while ($group = $db->fetch_assoc($groups)) {
        $level = (int)$group['group_level'];
        $manage = $level <= 2 ? 1 : 0;
        $delete = $level === 1 ? 1 : 0;
        $db->query("INSERT IGNORE INTO permisos_modulos (group_level,modulo,puede_ver,puede_crear,puede_editar,puede_eliminar) VALUES ({$level},'riesgos_sgsi',1,{$manage},{$manage},{$delete})");
    }
    $ready = true;
}

function sgsi_risk_level(int $probability, int $impact): array {
    $score = $probability * $impact;
    $matrix = [
        1 => [1 => 'NO SIGNIFICATIVO', 2 => 'NO SIGNIFICATIVO', 3 => 'NO SIGNIFICATIVO', 4 => 'TOLERADO', 5 => 'TOLERADO'],
        2 => [1 => 'NO SIGNIFICATIVO', 2 => 'TOLERADO', 3 => 'TOLERADO', 4 => 'MODERADO', 5 => 'MODERADO'],
        3 => [1 => 'NO SIGNIFICATIVO', 2 => 'TOLERADO', 3 => 'MODERADO', 4 => 'MODERADO', 5 => 'IMPORTANTE'],
        4 => [1 => 'TOLERADO', 2 => 'MODERADO', 3 => 'MODERADO', 4 => 'IMPORTANTE', 5 => 'CRITICO'],
        5 => [1 => 'TOLERADO', 2 => 'MODERADO', 3 => 'IMPORTANTE', 4 => 'CRITICO', 5 => 'CRITICO'],
    ];
    $level = $matrix[$impact][$probability] ?? 'POR EVALUAR';
    return ['score' => $score, 'level' => $level];
}

function sgsi_initial_inherent_assessment(array $risk, ?array $asset): array {
    $scenario = mb_strtolower((string)($risk['Escenario'] ?? ''), 'UTF-8');
    $criticality = mb_strtoupper(trim((string)($asset['criticality'] ?? '')), 'UTF-8');
    $impactMap = ['MUY ALTO' => 5, 'CRITICO' => 5, 'CRÍTICO' => 5, 'ALTO' => 4, 'MEDIO' => 3, 'BAJO' => 2];
    $impact = $impactMap[$criticality] ?? 3;
    $probability = 3;
    $probabilityReason = 'El escenario puede presentarse de manera posible durante la operación normal.';

    if (preg_match('/obsolesc|vencim|caduc|sin soporte|falta de actualiz|capacidad insuficiente/u', $scenario)) {
        $probability = 4;
        $probabilityReason = 'El escenario tiende a materializarse si no existe seguimiento periódico o renovación.';
    } elseif (preg_match('/incend|inund|sismo|desastre|robo físico|robo fisico/u', $scenario)) {
        $probability = 2;
        $probabilityReason = 'Es un evento poco frecuente, aunque posible y con consecuencias relevantes.';
    } elseif (preg_match('/acceso|divulg|cambio incorrect|error|falla|interrup|pérdida|perdida|corrup|malware|phishing|vulnerab|indisponib/u', $scenario)) {
        $probability = 3;
        $probabilityReason = 'La exposición tecnológica y el error humano hacen posible la ocurrencia del escenario.';
    }

    if (preg_match('/continuidad|paraliza|pérdida total|perdida total|información confidencial|informacion confidencial|base de datos|servidor|firewall/u', $scenario)) {
        $impact = max($impact, 4);
    }
    if (preg_match('/catastró|catastrof|irreversible|total de la operación|total de la operacion/u', $scenario)) {
        $impact = 5;
    }

    $result = sgsi_risk_level($probability, $impact);
    $assetLabel = $asset ? $asset['code'] . ' (' . ($criticality ?: 'sin criticidad') . ')' : 'activo sin criticidad disponible';
    $justification = 'Valoración inherente inicial. Probabilidad ' . $probability . ': ' . $probabilityReason
        . ' Impacto ' . $impact . ': se considera la criticidad de ' . $assetLabel
        . ' y la consecuencia descrita, antes de considerar controles. Resultado: ' . $result['level'] . '.';
    return ['probability' => $probability, 'impact' => $impact, 'score' => $result['score'], 'level' => $result['level'], 'justification' => $justification];
}

function sgsi_asset_catalog(): array {
    static $catalog = null;
    if ($catalog !== null) return $catalog;
    $catalog = [];
    foreach (iso_inventory_rows() as $row) {
        $key = $row['_Tipo'] . ':' . (int)$row['_Id'];
        $catalog[$key] = [
            'key' => $key, 'type' => $row['_Tipo'], 'id' => (int)$row['_Id'],
            'code' => (string)$row['IdActivo'], 'name' => (string)$row['NombreActivo'],
            'criticality' => (string)$row['Criticidad'], 'category' => (string)$row['TipoActivo'],
            'process' => (string)$row['Proceso'], 'owner' => (string)$row['Propietario'],
        ];
    }
    return $catalog;
}

function sgsi_parse_asset(string $key): ?array {
    if (!preg_match('/^([A-Z_]+):(\d+)$/', $key, $match)) return null;
    $catalog = sgsi_asset_catalog();
    return $catalog[$match[1] . ':' . (int)$match[2]] ?? null;
}

function sgsi_risk(int $id): ?array {
    global $db;
    if ($id <= 0) return null;
    return $db->fetch_assoc($db->query("SELECT * FROM sgsi_riesgos WHERE IdRiesgo={$id} LIMIT 1")) ?: null;
}

function sgsi_make_code(string $prefix, int $id): string {
    return $prefix . '-' . date('Y') . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
}

function sgsi_redirect_risk(int $id, string $tab = 'resumen'): void {
    redirect('riesgo.php?id=' . $id . '#tab-' . $tab, false);
}

function sgsi_date_or_null(string $value): string {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? sgsi_q($value) : 'NULL';
}

function sgsi_asset_options(array $selected = []): string {
    $groups = [];
    foreach (sgsi_asset_catalog() as $asset) $groups[$asset['category']][] = $asset;
    $html = '';
    foreach ($groups as $label => $assets) {
        $html .= '<optgroup label="' . sgsi_h($label) . '">';
        foreach ($assets as $asset) {
            $isSelected = in_array($asset['key'], $selected, true) ? ' selected' : '';
            $html .= '<option value="' . sgsi_h($asset['key']) . '"' . $isSelected . '>'
                . sgsi_h($asset['code'] . ' · ' . $asset['name'] . ' · ' . $asset['criticality']) . '</option>';
        }
        $html .= '</optgroup>';
    }
    return $html;
}
