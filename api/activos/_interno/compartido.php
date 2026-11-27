<?php
/**
 * Utilidades compartidas de los módulos ISO 27001:
 * ubicaciones.php, informacion.php, copias.php, inventario_iso.php y aplicaciones.php
 * Copiar junto a index.php (modules/activos/).
 */
if (defined('ISO_COMMON_LOADED')) return;
define('ISO_COMMON_LOADED', true);
require_once __DIR__ . '/fechas.php';

const ISO_CLASIFICACIONES = ['PUBLICO', 'INTERNO', 'CONFIDENCIAL', 'RESTRINGIDO'];
const ISO_TIPOS_ACTIVO = [
    'MAQUINA'     => 'Hardware',
    'APLICACION'  => 'Aplicaciones',
    'SERVICIO'    => 'Servicios TI',
    'INFORMACION' => 'Activos de información',
    'UBICACION'   => 'Ubicaciones físicas',
    'COPIA'       => 'Copias de seguridad',
    'ROL'         => 'Personal / Roles clave',
];

/* ---------- helpers básicos ---------- */
function iso_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function iso_p(string $k): string { return trim((string)($_POST[$k] ?? '')); }
function iso_flag(string $k): int { return !empty($_POST[$k]) ? 1 : 0; }
function iso_sql_str(string $v): string { global $db; return $v === '' ? 'NULL' : "'" . $db->escape($v) . "'"; }
function iso_sql_date(string $v): string {
    $date = activos_fecha_normalizar($v);
    return $date === null ? 'NULL' : "'" . $date . "'";
}
function iso_sql_int(string $v): string { return (ctype_digit($v) && (int)$v > 0) ? (string)(int)$v : 'NULL'; }
function iso_sql_num(string $v): string { return is_numeric($v) && (float)$v >= 0 ? (string)(float)$v : 'NULL'; }

function iso_user(): string {
    if (function_exists('current_user')) {
        $u = current_user();
        if (is_array($u)) return (string)($u['name'] ?? $u['username'] ?? 'sistema');
    }
    return 'sistema';
}

/* ---------- esquema ---------- */
function iso_missing_tables(array $tables): array {
    global $db;
    $missing = [];
    foreach ($tables as $t) {
        $r = find_by_sql("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $db->escape($t) . "' LIMIT 1");
        if (!$r) $missing[] = $t;
    }
    return $missing;
}
function iso_schema_alert(array $missing): string {
    return '<section class="content pt-3"><div class="container-fluid"><div class="alert alert-warning">'
        . '<h5><i class="fas fa-database mr-2"></i>Falta ejecutar la migración</h5>'
        . 'No existen estas tablas/vistas: <strong>' . iso_h(implode(', ', $missing)) . '</strong>.<br>'
        . 'Ejecute <code>migracion_iso27001.sql</code> (primero en una copia de prueba) y recargue la página.'
        . '</div></div></section>';
}

function iso_ensure_roles_schema(): void {
    global $db;
    static $ready = false;
    if ($ready) return;
    $db->query("CREATE TABLE IF NOT EXISTS activos_roles_clave (
      IdRol INT UNSIGNED NOT NULL AUTO_INCREMENT,
      CodigoRol VARCHAR(30) NOT NULL,
      NombreRol VARCHAR(120) NOT NULL,
      Descripcion TEXT NOT NULL,
      Proceso VARCHAR(120) NOT NULL,
      Propietario VARCHAR(120) NOT NULL,
      TitularActual VARCHAR(120) NULL,
      Suplente VARCHAR(120) NULL,
      DatosPersonales TINYINT(1) NOT NULL DEFAULT 0,
      RequisitoLegal VARCHAR(500) NULL,
      UbicacionLogica VARCHAR(200) NULL,
      Confidencialidad TINYINT UNSIGNED NULL,
      Integridad TINYINT UNSIGNED NULL,
      Disponibilidad TINYINT UNSIGNED NULL,
      Criticidad VARCHAR(20) NULL,
      Clasificacion VARCHAR(20) NULL,
      EstadoRol VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
      Observaciones TEXT NULL,
      CreadoPor VARCHAR(120) NULL,
      FechaRegistro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (IdRol),
      UNIQUE KEY uq_activos_roles_codigo (CodigoRol),
      KEY idx_activos_roles_estado (EstadoRol),
      KEY idx_activos_roles_proceso (Proceso)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready = true;
}

/* ---------- guardado genérico ---------- */
/** $cols = [columna => literal SQL ya escapado]. Devuelve el Id guardado o 0 si falla. */
function iso_upsert(string $table, string $pk, int $id, array $cols): int {
    global $db;
    try {
        if ($id > 0) {
            $set = [];
            foreach ($cols as $k => $v) $set[] = "`{$k}`={$v}";
            if (!$db->query("UPDATE `{$table}` SET " . implode(',', $set) . " WHERE `{$pk}`={$id}")) return 0;
            return $id;
        }
        $sql = "INSERT INTO `{$table}` (`" . implode('`,`', array_keys($cols)) . "`) VALUES (" . implode(',', $cols) . ")";
        if (!$db->query($sql)) return 0;
        $r = find_by_sql("SELECT LAST_INSERT_ID() AS id");
        return (int)($r[0]['id'] ?? 0);
    } catch (\Throwable $e) {
        error_log('[iso_upsert] ' . $table . ': ' . $e->getMessage());
        return 0;
    }
}

/* ---------- evaluación C-I-D ---------- */
function iso_criticidad(int $c, int $i, int $d): string {
    return [1 => 'BAJO', 2 => 'MEDIO', 3 => 'ALTO', 4 => 'MUY ALTO'][max($c, $i, $d)] ?? 'SIN EVALUAR';
}

/** Lee y valida la sección CIA del POST. Agrega mensajes a $errors. */
function iso_read_cia(array &$errors): array {
    $c = (int)($_POST['Confidencialidad'] ?? 0);
    $i = (int)($_POST['Integridad'] ?? 0);
    $d = (int)($_POST['Disponibilidad'] ?? 0);
    foreach ([$c, $i, $d] as $s) {
        if ($s < 1 || $s > 4) { $errors[] = 'Confidencialidad, integridad y disponibilidad deben valorarse del 1 al 4.'; break; }
    }
    $cl = strtoupper(iso_p('Clasificacion'));
    if (!in_array($cl, ISO_CLASIFICACIONES, true)) $errors[] = 'Seleccione una clasificación válida.';
    $just = iso_p('JustificacionCIA');
    if (mb_strlen($just, 'UTF-8') < 5) $errors[] = 'Escriba la justificación de la valoración (el auditor la va a pedir).';
    $prox = iso_p('ProximaRevision');
    if ($prox !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $prox)) $errors[] = 'La fecha de próxima revisión no es válida.';
    return ['C' => $c, 'I' => $i, 'D' => $d, 'crit' => iso_criticidad($c, $i, $d), 'clasif' => $cl, 'just' => $just, 'prox' => $prox];
}

/** Columnas CIA listas para iso_upsert. */
function iso_cia_cols(array $cia): array {
    global $db;
    return [
        'Confidencialidad' => (string)$cia['C'], 'Integridad' => (string)$cia['I'], 'Disponibilidad' => (string)$cia['D'],
        'Criticidad' => "'" . $db->escape($cia['crit']) . "'", 'Clasificacion' => "'" . $db->escape($cia['clasif']) . "'",
    ];
}

/** Guarda en el historial solo si cambió la valoración o la justificación. */
function iso_save_eval(string $tipo, int $id, array $cia): void {
    global $db;
    if ($id <= 0) return;
    $t = $db->escape($tipo);
    $last = find_by_sql("SELECT Confidencialidad,Integridad,Disponibilidad,Clasificacion,Justificacion FROM evaluaciones_cia WHERE TipoActivo='{$t}' AND IdActivo={$id} ORDER BY IdEvaluacion DESC LIMIT 1");
    if ($last) {
        $l = $last[0];
        if ((int)$l['Confidencialidad'] === $cia['C'] && (int)$l['Integridad'] === $cia['I'] && (int)$l['Disponibilidad'] === $cia['D']
            && $l['Clasificacion'] === $cia['clasif'] && (string)$l['Justificacion'] === $cia['just']) {
            // Sin cambios: solo se actualiza la fecha de próxima revisión de la última evaluación.
            $db->query("UPDATE evaluaciones_cia SET ProximaRevision=" . iso_sql_date($cia['prox']) . " WHERE TipoActivo='{$t}' AND IdActivo={$id} ORDER BY IdEvaluacion DESC LIMIT 1");
            return;
        }
    }
    $db->query("INSERT INTO evaluaciones_cia (TipoActivo,IdActivo,Confidencialidad,Integridad,Disponibilidad,Criticidad,Clasificacion,Justificacion,EvaluadoPor,ProximaRevision) VALUES ('{$t}',{$id},{$cia['C']},{$cia['I']},{$cia['D']},'" . $db->escape($cia['crit']) . "','" . $db->escape($cia['clasif']) . "'," . iso_sql_str($cia['just']) . ",'" . $db->escape(iso_user()) . "'," . iso_sql_date($cia['prox']) . ")");
}

/** Subconsultas para traer justificación / fechas de la última evaluación. */
function iso_eval_cols(string $tipo, string $idExpr): string {
    $b = "FROM evaluaciones_cia e WHERE e.TipoActivo='{$tipo}' AND e.IdActivo={$idExpr} ORDER BY e.IdEvaluacion DESC LIMIT 1";
    return "(SELECT e.Justificacion {$b}) AS JustificacionCIA, (SELECT e.ProximaRevision {$b}) AS ProximaRevision, (SELECT e.FechaEvaluacion {$b}) AS UltimaEvaluacion";
}

/* ---------- relaciones entre activos ---------- */
function iso_asset_catalog(): array {
    static $c = null;
    if ($c !== null) return $c;

    $q = [
        'MAQUINA'     => "SELECT IdMaquina id, CONCAT(Host,' · ',Tipo_Activo) label FROM maquina ORDER BY Host",
        'APLICACION'  => "SELECT IdAplicacionActivo id, CONCAT(CodigoAplicacion,' · ',NombreAplicacion) label FROM activos_aplicaciones ORDER BY NombreAplicacion",
        'SERVICIO'    => "SELECT IdServicio id, CONCAT(CodigoServicio,' · ',NombreServicio) label FROM servicios_ti ORDER BY NombreServicio",
        'INFORMACION' => "SELECT IdActivoInformacion id, CONCAT(CodigoActivo,' · ',NombreActivo) label FROM activos_informacion ORDER BY NombreActivo",
        'UBICACION'   => "SELECT IdUbicacion id, CONCAT(CodigoUbicacion,' · ',NombreUbicacion) label FROM ubicaciones_fisicas ORDER BY NombreUbicacion",
        'COPIA'       => "SELECT IdCopia id, CONCAT(CodigoCopia,' · ',NombreCopia) label FROM copias_seguridad ORDER BY NombreCopia",
        'ROL'         => "SELECT IdRol id, CONCAT(CodigoRol,' · ',NombreRol) label FROM activos_roles_clave ORDER BY NombreRol",
    ];
    $c = [];
    foreach ($q as $tipo => $sql) {
        $c[$tipo] = [];
        foreach ((find_by_sql($sql) ?: []) as $r) $c[$tipo][(int)$r['id']] = (string)$r['label'];
    }
    return $c;
}

/** Lista de casillas agrupada por tipo, con buscador. Los valores son "TIPO:ID". */
function iso_checklist(string $name, array $types): string {
    $cat = iso_asset_catalog();
    ob_start(); ?>
    <input type="search" class="form-control form-control-sm iso-checklist-filter mb-1" placeholder="Filtrar...">
    <div class="iso-checklist-box">
    <?php foreach ($types as $t): if (empty($cat[$t])) continue; ?>
        <div class="iso-checklist-group"><?= iso_h(ISO_TIPOS_ACTIVO[$t] ?? $t) ?></div>
        <?php foreach ($cat[$t] as $id => $label): ?>
        <label class="iso-check"><input type="checkbox" name="<?= iso_h($name) ?>[]" value="<?= iso_h($t . ':' . $id) ?>"> <?= iso_h($label) ?></label>
        <?php endforeach; ?>
    <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
}

/** Mapa idOrigen => ["TIPO:ID", ...] para un tipo de origen y relación. */
function iso_rel_map(string $tipoOrigen, string $tipoRel): array {
    global $db;
    $rows = find_by_sql("SELECT IdOrigen,TipoDestino,IdDestino FROM activos_relaciones WHERE TipoOrigen='" . $db->escape($tipoOrigen) . "' AND TipoRelacion='" . $db->escape($tipoRel) . "'");
    $map = [];
    foreach (($rows ?: []) as $r) $map[(int)$r['IdOrigen']][] = $r['TipoDestino'] . ':' . (int)$r['IdDestino'];
    return $map;
}

/** Reemplaza las relaciones de un activo. $values = ["TIPO:ID", ...]. */
function iso_rel_sync(string $tipoOrigen, int $idOrigen, string $tipoRel, array $values, array $allowed): void {
    global $db;
    if ($idOrigen <= 0) return;
    $o = $db->escape($tipoOrigen); $r = $db->escape($tipoRel);
    $db->query("DELETE FROM activos_relaciones WHERE TipoOrigen='{$o}' AND IdOrigen={$idOrigen} AND TipoRelacion='{$r}'");
    $seen = [];
    foreach ($values as $v) {
        if (!is_string($v) || !preg_match('/^([A-Z]+):(\d+)$/', $v, $m)) continue;
        if (!in_array($m[1], $allowed, true) || isset($seen[$v])) continue;
        $seen[$v] = true;
        $db->query("INSERT IGNORE INTO activos_relaciones (TipoOrigen,IdOrigen,TipoDestino,IdDestino,TipoRelacion) VALUES ('{$o}',{$idOrigen},'" . $db->escape($m[1]) . "'," . (int)$m[2] . ",'{$r}')");
    }
}

/** Etiquetas cortas de relaciones, "A, B +3 más". */
function iso_rel_labels(array $values, int $max = 3): string {
    $cat = iso_asset_catalog();
    $out = [];
    foreach ($values as $v) {
        [$t, $id] = explode(':', $v);
        $out[] = $cat[$t][(int)$id] ?? $v;
    }
    if (!$out) return '<span class="text-muted">Ninguno</span>';
    $shown = array_slice($out, 0, $max);
    $s = iso_h(implode(', ', $shown));
    if (count($out) > $max) $s .= ' <span class="badge badge-light" title="' . iso_h(implode(' | ', $out)) . '">+' . (count($out) - $max) . '</span>';
    return $s;
}

/* ---------- presentación ---------- */
function iso_crit_badge($crit): string {
    $crit = (string)$crit;
    if ($crit === '' || $crit === 'SIN EVALUAR') return '<span class="badge badge-secondary">SIN EVALUAR</span>';
    $cls = $crit === 'MUY ALTO' ? 'danger' : ($crit === 'ALTO' ? 'warning' : ($crit === 'MEDIO' ? 'info' : 'success'));
    return '<span class="badge badge-' . $cls . '">' . iso_h($crit) . '</span>';
}
function iso_cia_chips(array $r): string {
    if (empty($r['Confidencialidad'])) return '<span class="text-muted small">Sin evaluar</span>';
    return '<span class="iso-score" title="Confidencialidad">C' . (int)$r['Confidencialidad'] . '</span>'
         . '<span class="iso-score" title="Integridad">I' . (int)$r['Integridad'] . '</span>'
         . '<span class="iso-score" title="Disponibilidad">D' . (int)$r['Disponibilidad'] . '</span>';
}
function iso_review_cell($date): string {
    $date = activos_fecha_normalizar($date);
    if (!$date) return '<span class="text-muted">-</span>';
    $late = $date < date('Y-m-d');
    return '<span class="' . ($late ? 'text-danger font-weight-bold' : '') . '">' . iso_h($date) . ($late ? ' <i class="fas fa-exclamation-circle" title="Revisión vencida"></i>' : '') . '</span>';
}
function iso_bool_icon($v): string {
    return $v === null || $v === '' ? '<span class="text-muted">-</span>' : ((int)$v ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>');
}

function iso_styles(): string {
    return <<<'CSS'
<style>
:root{--iso-navy:#123746;--iso-teal:#078591;--iso-bg:#f3f7f8;--iso-line:#d9e6e7}
.iso-stat{height:100%;padding:15px 17px;border:1px solid var(--iso-line);border-radius:13px;background:#fff;box-shadow:0 6px 16px rgba(18,55,70,.06)}
.iso-stat strong{display:block;color:var(--iso-navy);font-size:25px}.iso-stat span{color:#70848b;font-size:10px;font-weight:800;text-transform:uppercase}
.iso-actionbar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;padding:14px 18px;background:#fff;border:1px solid var(--iso-line);border-radius:13px;box-shadow:0 6px 16px rgba(18,55,70,.06)}
.iso-search{position:relative;flex:1 1 280px;max-width:420px}.iso-search i{position:absolute;left:12px;top:11px;color:#91a2a8}.iso-search input{height:36px;padding-left:33px;border:1px solid #cddedd;border-radius:9px}
.iso-tabs{padding:7px;border:1px solid var(--iso-line);border-radius:13px;background:#fff}.iso-tabs .nav-link{display:flex;align-items:center;gap:7px;padding:9px 15px;border-radius:8px;color:#526d75;font-size:11px;font-weight:800}.iso-tabs .nav-link.active{color:#fff;background:var(--iso-teal)}
.iso-panel{border:0;border-radius:15px;box-shadow:0 8px 22px rgba(18,55,70,.07);overflow:hidden}
.iso-table thead th{border:0;background:var(--iso-navy);color:#fff;font-size:10px;text-transform:uppercase;white-space:nowrap}.iso-table td{vertical-align:middle;font-size:12.5px}
.iso-name{display:block;color:var(--iso-teal);font-size:13px;font-weight:800}
.iso-score{display:inline-grid;width:26px;height:26px;margin-right:3px;place-items:center;border-radius:7px;color:#315761;background:#eaf2f2;font-weight:800;font-size:11px}
.iso-modal .modal-content{border:0;border-radius:17px;overflow:hidden;box-shadow:0 25px 70px rgba(9,42,52,.3)}
.iso-modal .modal-header{border:0;color:#fff;background:linear-gradient(120deg,var(--iso-navy),var(--iso-teal))}
.iso-modal .modal-body{padding:22px;background:var(--iso-bg)}
.iso-section{padding:16px;margin-bottom:13px;border:1px solid var(--iso-line);border-radius:12px;background:#fff}
.iso-section-title{margin-bottom:13px;color:var(--iso-navy);font-size:11px;font-weight:800;text-transform:uppercase}
.iso-modal label{color:#536c74;font-size:9.5px;font-weight:800;text-transform:uppercase}
.iso-modal .form-control{min-height:39px;border-color:#cbdcdb;border-radius:8px}
.iso-cia{padding:12px;border:1px solid #dce8e7;border-radius:10px;background:#f8fbfb}.iso-cia small{display:block;min-height:32px;color:#74878e}
.iso-crit-box{padding:12px;border-radius:10px;color:#315d64;background:#e7f5f3;text-align:center}.iso-crit-box strong{display:block;color:var(--iso-teal);font-size:18px}
.iso-checklist-box{max-height:230px;overflow:auto;padding:8px 10px;border:1px solid #cbdcdb;border-radius:8px;background:#fff}
.iso-checklist-group{margin:6px 0 3px;color:var(--iso-navy);font-size:10px;font-weight:800;text-transform:uppercase}
.iso-check{display:block;margin:0;padding:1px 0;color:#2c4650;font-size:12px;font-weight:400;text-transform:none}.iso-check input{margin-right:6px}
.iso-guide td,.iso-guide th{padding:4px 8px;font-size:11px;vertical-align:top}
@media(max-width:767px){.iso-modal .modal-body{padding:13px}}
</style>
CSS;
}

/** Sección CIA reutilizable para todos los formularios. */
function iso_cia_form(): string {
    ob_start(); ?>
    <div class="iso-section"><div class="iso-section-title"><i class="fas fa-shield-alt mr-1 text-info"></i>Valoración de seguridad (C-I-D)</div>
    <div class="row">
        <div class="col-md-3 mb-2"><div class="iso-cia"><label>Confidencialidad *</label><small>Impacto si lo ve quien no debe.</small>
            <select name="Confidencialidad" class="form-control iso-score-select" required><option value="">Evaluar...</option><option value="1">1 - Bajo</option><option value="2">2 - Medio</option><option value="3">3 - Alto</option><option value="4">4 - Muy alto</option></select></div></div>
        <div class="col-md-3 mb-2"><div class="iso-cia"><label>Integridad *</label><small>Impacto si se altera o corrompe.</small>
            <select name="Integridad" class="form-control iso-score-select" required><option value="">Evaluar...</option><option value="1">1 - Bajo</option><option value="2">2 - Medio</option><option value="3">3 - Alto</option><option value="4">4 - Muy alto</option></select></div></div>
        <div class="col-md-3 mb-2"><div class="iso-cia"><label>Disponibilidad *</label><small>Impacto si deja de estar disponible.</small>
            <select name="Disponibilidad" class="form-control iso-score-select" required><option value="">Evaluar...</option><option value="1">1 - Bajo</option><option value="2">2 - Medio</option><option value="3">3 - Alto</option><option value="4">4 - Muy alto</option></select></div></div>
        <div class="col-md-3 mb-2"><div class="iso-crit-box"><small>Criticidad automática</small><strong class="iso-crit">SIN EVALUAR</strong><span>Se toma el mayor valor C-I-D</span></div></div>
    </div>
    <div class="row">
        <div class="col-md-3 form-group"><label>Clasificación *</label>
            <select name="Clasificacion" class="form-control" required><option value="">Seleccionar...</option><option value="PUBLICO">Público</option><option value="INTERNO">Interno</option><option value="CONFIDENCIAL">Confidencial</option><option value="RESTRINGIDO">Restringido</option></select></div>
        <div class="col-md-6 form-group"><label>Justificación de la valoración *</label><input name="JustificacionCIA" maxlength="500" class="form-control" required placeholder="Ej. Contiene datos de clientes; si se cae se detiene la facturación."></div>
        <div class="col-md-3 form-group"><label>Próxima revisión</label><input type="date" name="ProximaRevision" class="form-control"></div>
    </div>
    <details class="mt-1"><summary class="small text-info" style="cursor:pointer">Guía de valoración (1 a 4)</summary>
    <table class="table table-sm iso-guide mt-2 mb-0"><thead><tr><th></th><th>Confidencialidad</th><th>Integridad</th><th>Disponibilidad (tolerancia a caída)</th></tr></thead><tbody>
    <tr><th>1 Bajo</th><td>Pública; divulgarla no genera daño.</td><td>Alterarla no tiene consecuencia o se detecta de inmediato.</td><td>Puede estar caído más de 1 semana.</td></tr>
    <tr><th>2 Medio</th><td>Uso interno; su fuga causa molestia o daño menor.</td><td>Se corrige con poco esfuerzo; retrabajo menor.</td><td>Tolera hasta ~3 días sin afectar la operación.</td></tr>
    <tr><th>3 Alto</th><td>Datos de clientes/personales, contratos, costos; daño económico o legal.</td><td>Un error afecta decisiones, facturación o reportes y es difícil de revertir.</td><td>Tolera como máximo ~1 día (8 h laborales).</td></tr>
    <tr><th>4 Muy alto</th><td>Secretos, credenciales, información estratégica; daño grave o sanción.</td><td>Alteración compromete contabilidad, seguridad o cumplimiento; irreversible.</td><td>Menos de ~4 horas; la parada detiene la empresa.</td></tr>
    </tbody></table></details>
    </div>
    <?php return ob_get_clean();
}

/** JS común: criticidad en vivo, llenado de formularios, CSRF y filtro de listas. */
function iso_js(): string {
    return <<<'JS'
$(function(){
  window.isoCritText=function(scope){var m=0;$(scope).find('.iso-score-select').each(function(){m=Math.max(m,parseInt(this.value,10)||0)});$(scope).find('.iso-crit').text(({1:'BAJO',2:'MEDIO',3:'ALTO',4:'MUY ALTO'})[m]||'SIN EVALUAR')};
  window.isoCsrf=function(form){var t=document.querySelector('meta[name="csrf-token"]');if(t)$(form).find('input[name="_csrf"]').val(t.getAttribute('content')||'')};
  window.isoChecks=function(form,name,list){$(form).find('input[name="'+name+'[]"]').each(function(){this.checked=(list||[]).indexOf(this.value)>-1})};
  window.isoNew=function(form){form.reset();isoCsrf(form);isoCritText(form);var r=$(form).data('review');if(r&&form.elements.ProximaRevision)form.elements.ProximaRevision.value=r;$(form).find('.iso-checklist-box input').prop('checked',false)};
  window.isoFill=function(form,d){form.reset();isoCsrf(form);Object.keys(d).forEach(function(k){var el=form.elements.namedItem(k);if(!el||d[k]===null||/^\d+$/.test(k)||k==='_csrf'||k==='action')return;if(el.type==='checkbox'){el.checked=String(d[k])==='1'}else if(el.tagName){$(el).val(d[k])}});isoCritText(form)};
  $(document).on('change','.iso-score-select',function(){isoCritText($(this).closest('form'))});
  $(document).on('input','.iso-checklist-filter',function(){var q=this.value.toLowerCase();$(this).next('.iso-checklist-box').find('label').each(function(){$(this).toggle(this.textContent.toLowerCase().indexOf(q)>-1)})});
  window.addEventListener('pageshow',function(){$('form').each(function(){isoCsrf(this)})});
});
JS;
}
