<?php
$page_title = 'Matriz de Riesgos SGSI';
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/funciones.php';

sgsi_ensure_schema();
require_permiso('riesgos_sgsi', 'ver');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'evaluar_inherentes_pendientes') {
    csrf_require();
    require_permiso('riesgos_sgsi', 'editar');
    $catalog = sgsi_asset_catalog();
    $pendingRisks = find_by_sql("SELECT r.IdRiesgo,r.Codigo,r.Escenario,a.TipoActivo,a.IdActivo
        FROM sgsi_riesgos r
        LEFT JOIN sgsi_riesgo_activos a ON a.IdRiesgo=r.IdRiesgo AND a.EsPrincipal=1
        WHERE NOT EXISTS (SELECT 1 FROM sgsi_evaluaciones e WHERE e.IdRiesgo=r.IdRiesgo AND e.Tipo='INHERENTE')
        ORDER BY r.IdRiesgo");
    $created = 0;
    $backup = 'bsgsi_' . date('Ymd_His') . '_evaluaciones';
    try {
        $db->query("CREATE TABLE `{$backup}` LIKE sgsi_evaluaciones");
        $db->query("INSERT INTO `{$backup}` SELECT * FROM sgsi_evaluaciones");
        $db->query('START TRANSACTION');
        foreach ($pendingRisks as $pendingRisk) {
            $assetKey = (string)$pendingRisk['TipoActivo'] . ':' . (int)$pendingRisk['IdActivo'];
            $assessment = sgsi_initial_inherent_assessment($pendingRisk, $catalog[$assetKey] ?? null);
            $riskId = (int)$pendingRisk['IdRiesgo'];
            $db->query("INSERT INTO sgsi_evaluaciones
                (IdRiesgo,Tipo,FechaEvaluacion,Probabilidad,Impacto,Puntaje,Nivel,Evaluador,Justificacion,CreadoPor)
                VALUES ({$riskId},'INHERENTE',CURDATE(),{$assessment['probability']},{$assessment['impact']},{$assessment['score']},"
                . sgsi_q($assessment['level']) . ',' . sgsi_q(sgsi_user_name()) . ',' . sgsi_q($assessment['justification']) . ',' . sgsi_user_id() . ')');
            $created++;
        }
        $db->query('COMMIT');
        $session->msg('s', "Se evaluaron {$created} riesgo(s) pendientes. Se respetaron las evaluaciones existentes. Respaldo: {$backup}.");
    } catch (Throwable $error) {
        try { $db->query('ROLLBACK'); } catch (Throwable $ignored) {}
        $session->msg('d', 'No se pudo completar la evaluación: ' . $error->getMessage());
    }
    redirect('index.php', false);
}
if (false && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'normalizar_ti') {
    csrf_require();
    require_permiso('riesgos_sgsi', 'editar');
    $backup = 'bsgsi_' . date('Ymd_His') . '_riesgos_owner';
    $db->query("CREATE TABLE `{$backup}` LIKE sgsi_riesgos");
    $db->query("INSERT INTO `{$backup}` SELECT * FROM sgsi_riesgos");
    $db->query("UPDATE sgsi_riesgos SET Proceso='TI',DuenoRiesgo='Jose Sovero'");
    $updated = $db->affected_rows();
    $session->msg('s', "Se asigno Proceso TI y dueño Jose Sovero a {$updated} riesgo(s). Respaldo: {$backup}.");
    redirect('index.php', false);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear_riesgo_a57') {
    csrf_require();
    require_permiso('riesgos_sgsi', 'crear');
    $existingRisk = $db->fetch_assoc($db->query("SELECT IdRiesgo FROM sgsi_riesgos WHERE Codigo='R-A57-001' LIMIT 1"));
    $riskId = (int)($existingRisk['IdRiesgo'] ?? 0);
    $catalog = sgsi_asset_catalog();
    $byCode = [];
    foreach ($catalog as $asset) $byCode[strtoupper(trim((string)$asset['code']))] = $asset;
    $userId = sgsi_user_id();
    $scenario = 'Identificacion y analisis tardio de amenazas externas por ausencia de una revision formal de fuentes confiables, permitiendo la explotacion de vulnerabilidades aplicables y afectando la confidencialidad, integridad o disponibilidad de los activos de informacion de TI.';
    $notes = 'ISO/IEC 27001:2022 Anexo A, control 5.7. Alcance: servidores, firewall, comunicaciones, computadoras, SAP Business One, bases de datos, aplicaciones web, respaldos y personal con acceso a informacion.';
    try {
        $backup = 'bsgsi_' . date('Ymd_His') . '_riesgos_a57';
        $db->query("CREATE TABLE `{$backup}` LIKE sgsi_riesgos");
        $db->query("INSERT INTO `{$backup}` SELECT * FROM sgsi_riesgos");
        $db->query('START TRANSACTION');
        if ($riskId > 0) {
            $db->query("UPDATE sgsi_riesgos SET Escenario=".sgsi_q($scenario).",Proceso='TI',DuenoRiesgo='Jose Sovero',Estado='EN_TRATAMIENTO',Tratamiento='REDUCIR',Observaciones=".sgsi_q($notes)." WHERE IdRiesgo={$riskId}");
        } else {
            $db->query("INSERT INTO sgsi_riesgos (Codigo,Escenario,Proceso,DuenoRiesgo,Estado,Tratamiento,Observaciones,CreadoPor,ActualizadoPor) VALUES ('R-A57-001',".sgsi_q($scenario).",'TI','Jose Sovero','EN_TRATAMIENTO','REDUCIR',".sgsi_q($notes).",{$userId},{$userId})");
            $riskId = (int)$db->insert_id();
        }
        $db->query("DELETE FROM sgsi_riesgo_activos WHERE IdRiesgo={$riskId}");
        if (isset($byCode['PER-TI-001'])) {
            $principal = $byCode['PER-TI-001'];
            $db->query("INSERT INTO sgsi_riesgo_activos (IdRiesgo,TipoActivo,IdActivo,EsPrincipal) VALUES ({$riskId},".sgsi_q($principal['type']).','.(int)$principal['id'].',1)');
        }
        foreach (['FIREWALL-01','SERV-001','BKP-HANA','APP-007'] as $assetCode) {
            if (!isset($byCode[$assetCode])) continue;
            $asset = $byCode[$assetCode];
            $db->query("INSERT IGNORE INTO sgsi_riesgo_activos (IdRiesgo,TipoActivo,IdActivo,EsPrincipal) VALUES ({$riskId},".sgsi_q($asset['type']).','.(int)$asset['id'].',0)');
        }
        $control = $db->fetch_assoc($db->query("SELECT IdControl FROM sgsi_controles WHERE Codigo='CTRL-A57-01' LIMIT 1"));
        if ($control) {
            $controlId = (int)$control['IdControl'];
        } else {
            $controlDescription = 'Revisar, registrar y analizar informacion de amenazas proveniente de Fortinet PSIRT, CISA KEV, Microsoft MSRC, INCIBE-CERT y NIST NVD.';
            $evidence = 'Boletin consultado, version instalada, analisis de aplicabilidad, mitigacion y validacion de cierre.';
            $db->query("INSERT INTO sgsi_controles (Codigo,Descripcion,Responsable,Frecuencia,Estado,Evidencia,CreadoPor) VALUES ('CTRL-A57-01',".sgsi_q($controlDescription).",'Jose Sovero','Mensual y ante alertas criticas','PROPUESTO',".sgsi_q($evidence).",{$userId})");
            $controlId = (int)$db->insert_id();
        }
        $db->query("INSERT IGNORE INTO sgsi_riesgo_controles (IdRiesgo,IdControl) VALUES ({$riskId},{$controlId})");
        $planAction = 'Implementar y documentar la revision mensual de inteligencia de amenazas, el registro de alertas aplicables, su tratamiento, evidencias y validacion de cierre.';
        $planEvidence = 'Formato de revision mensual, capturas de boletines, analisis de aplicabilidad y evidencias de mitigacion.';
        if (!$db->fetch_assoc($db->query("SELECT IdPlan FROM sgsi_planes WHERE Codigo='PLAN-A57-01' LIMIT 1"))) {
            $db->query("INSERT INTO sgsi_planes (IdRiesgo,Codigo,Accion,Responsable,Estado,Evidencia,Aprobacion,CreadoPor) VALUES ({$riskId},'PLAN-A57-01',".sgsi_q($planAction).",'Jose Sovero','PENDIENTE',".sgsi_q($planEvidence).",'PENDIENTE',{$userId})");
        }
        if (!$db->fetch_assoc($db->query("SELECT IdEvaluacion FROM sgsi_evaluaciones WHERE IdRiesgo={$riskId} AND Tipo='INHERENTE' LIMIT 1"))) {
            $level = sgsi_risk_level(3,4);
            $justification = 'Probabilidad posible e impacto mayor: una amenaza no detectada puede explotar vulnerabilidades en infraestructura o aplicaciones criticas.';
            $db->query("INSERT INTO sgsi_evaluaciones (IdRiesgo,Tipo,FechaEvaluacion,Probabilidad,Impacto,Puntaje,Nivel,Evaluador,Justificacion,CreadoPor) VALUES ({$riskId},'INHERENTE',CURDATE(),3,4,".(int)$level['score'].','.sgsi_q($level['level']).",'Jose Sovero',".sgsi_q($justification).",{$userId})");
        }
        if (!$db->fetch_assoc($db->query("SELECT IdEvaluacion FROM sgsi_evaluaciones WHERE IdRiesgo={$riskId} AND Tipo='RESIDUAL' LIMIT 1"))) {
            $residual = sgsi_risk_level(2,3);
            $residualJustification = 'Evaluacion residual objetivo P2 x I3, condicionada a implementar y verificar el control A.5.7. Debe reevaluarse con evidencias antes de aceptarse.';
            $db->query("INSERT INTO sgsi_evaluaciones (IdRiesgo,Tipo,FechaEvaluacion,Probabilidad,Impacto,Puntaje,Nivel,Evaluador,Justificacion,CreadoPor) VALUES ({$riskId},'RESIDUAL',CURDATE(),2,3,".(int)$residual['score'].','.sgsi_q($residual['level']).",'Jose Sovero',".sgsi_q($residualJustification).",{$userId})");
        }
        $db->query('COMMIT');
        $session->msg('s', 'Matriz normalizada a TI / Jose Sovero. Riesgo A.5.7 completado con evaluación inherente y residual objetivo.');
        redirect('riesgo.php?id=' . $riskId, false);
    } catch (Throwable $error) {
        try { $db->query('ROLLBACK'); } catch (Throwable $ignored) {}
        $session->msg('d', 'No se pudo crear el riesgo A.5.7: ' . $error->getMessage());
        redirect('index.php', false);
    }
}
$assets = sgsi_asset_catalog();
$rows = find_by_sql(
    "SELECT r.*,a.TipoActivo,a.IdActivo,"
    . "(SELECT e.Puntaje FROM sgsi_evaluaciones e WHERE e.IdRiesgo=r.IdRiesgo AND e.Tipo='INHERENTE' ORDER BY e.FechaEvaluacion DESC,e.IdEvaluacion DESC LIMIT 1) InherentePuntaje,"
    . "(SELECT e.Nivel FROM sgsi_evaluaciones e WHERE e.IdRiesgo=r.IdRiesgo AND e.Tipo='INHERENTE' ORDER BY e.FechaEvaluacion DESC,e.IdEvaluacion DESC LIMIT 1) InherenteNivel,"
    . "(SELECT e.Puntaje FROM sgsi_evaluaciones e WHERE e.IdRiesgo=r.IdRiesgo AND e.Tipo='RESIDUAL' ORDER BY e.FechaEvaluacion DESC,e.IdEvaluacion DESC LIMIT 1) ResidualPuntaje,"
    . "(SELECT e.Nivel FROM sgsi_evaluaciones e WHERE e.IdRiesgo=r.IdRiesgo AND e.Tipo='RESIDUAL' ORDER BY e.FechaEvaluacion DESC,e.IdEvaluacion DESC LIMIT 1) ResidualNivel,"
    . "(SELECT COUNT(*) FROM sgsi_riesgo_controles rc WHERE rc.IdRiesgo=r.IdRiesgo) TotalControles,"
    . "(SELECT COUNT(*) FROM sgsi_planes p WHERE p.IdRiesgo=r.IdRiesgo AND p.Estado NOT IN ('COMPLETADO','CANCELADO')) PlanesPendientes "
    . "FROM sgsi_riesgos r LEFT JOIN sgsi_riesgo_activos a ON a.IdRiesgo=r.IdRiesgo AND a.EsPrincipal=1 "
    . "ORDER BY r.IdRiesgo DESC"
);
$stats = ['TOTAL'=>count($rows),'ALTOS'=>0,'SIN_RESIDUAL'=>0,'PLANES'=>0];
foreach ($rows as &$row) {
    $key = (string)$row['TipoActivo'] . ':' . (int)$row['IdActivo'];
    $row['Activo'] = $assets[$key] ?? null;
    if (in_array($row['ResidualNivel'] ?: $row['InherenteNivel'], ['IMPORTANTE','ALTO','CRITICO'], true)) $stats['ALTOS']++;
    if (!$row['ResidualNivel']) $stats['SIN_RESIDUAL']++;
    $stats['PLANES'] += (int)$row['PlanesPendientes'];
}
unset($row);
function sgsi_level_badge($level, $score = null): string {
    if (!$level) return '<span class="badge badge-secondary">SIN EVALUAR</span>';
    $class = $level === 'CRITICO' ? 'danger' : (in_array($level, ['IMPORTANTE','ALTO'], true) ? 'warning' : ($level === 'MODERADO' ? 'info' : 'success'));
    return '<span class="badge badge-' . $class . '">' . sgsi_h($level) . ($score !== null ? ' · ' . (int)$score : '') . '</span>';
}
include_once __DIR__ . '/../../layouts/header.php';
?>
<style>
:root{--risk-ink:#173a4b;--risk-teal:#087f8c;--risk-gold:#d69a27;--risk-soft:#f1f7f7}.risk-hero{border:0;border-radius:17px;color:#fff;background:linear-gradient(118deg,#122f43,#087f8c 70%,#d79a27);box-shadow:0 15px 34px rgba(18,47,67,.2)}.risk-kicker{font-size:10px;font-weight:900;letter-spacing:.14em;text-transform:uppercase;opacity:.75}.risk-stat{height:100%;padding:15px 17px;border:1px solid #d8e6e6;border-radius:13px;background:#fff;box-shadow:0 6px 16px rgba(18,55,70,.06)}.risk-stat strong{display:block;color:var(--risk-ink);font-size:26px;line-height:1.1}.risk-stat span{color:#71868e;font-size:10px;font-weight:800;text-transform:uppercase}.risk-panel{border:0;border-radius:15px;overflow:hidden;box-shadow:0 8px 22px rgba(18,55,70,.07)}.risk-panel thead th{border:0;background:var(--risk-ink);color:#fff;font-size:10px;text-transform:uppercase;white-space:nowrap}.risk-panel td{vertical-align:middle;font-size:12px}.risk-code{color:var(--risk-teal);font-weight:900}.risk-asset{max-width:260px}.risk-asset strong{display:block;color:#294e5c}.risk-search{position:relative}.risk-search i{position:absolute;left:12px;top:11px;color:#8aa0a7}.risk-search input{padding-left:34px;border-radius:18px}
</style>
<section class="content-header pb-1"><div class="container-fluid d-flex justify-content-between align-items-center"><h1 class="h4 mb-0"><i class="fas fa-shield-alt mr-2 text-info"></i>Matriz de Riesgos SGSI</h1><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li><li class="breadcrumb-item active">Riesgos SGSI</li></ol></div></section>
<section class="content"><div class="container-fluid"><?=display_msg($msg)?>
 <div class="card risk-hero mb-3"><div class="card-body d-md-flex justify-content-between align-items-center"><div><div class="risk-kicker">ISO/IEC 27001 · Gestión del riesgo</div><h2 class="h4 font-weight-bold mb-1">Matriz completa SGSI</h2><div class="small">Identifique escenarios, evalúe el riesgo, gestione controles y conserve la aprobación residual.</div></div><div class="mt-3 mt-md-0"><a href="exportar.php" class="btn btn-success font-weight-bold"><i class="fas fa-file-excel mr-1"></i>Exportar matriz</a> <?php if(tiene_permiso('riesgos_sgsi','crear')):?><a href="riesgo.php" class="btn btn-light font-weight-bold"><i class="fas fa-plus-circle mr-1 text-warning"></i>Nuevo riesgo</a><?php endif;?></div></div></div>
 <?php if(tiene_permiso('riesgos_sgsi','editar')):?><div class="alert alert-warning border d-md-flex justify-content-between align-items-center"><div><strong><i class="fas fa-calculator mr-1"></i>Evaluación inherente pendiente</strong><div class="small">Calcula probabilidad e impacto individualmente según escenario y criticidad del activo. No reemplaza evaluaciones existentes ni genera residual sin controles validados.</div></div><form method="post" class="mt-2 mt-md-0"><?=csrf_input()?><input type="hidden" name="action" value="evaluar_inherentes_pendientes"><button class="btn btn-warning font-weight-bold" onclick="return confirm('¿Evaluar todos los riesgos que aún no tienen valoración inherente? Se respetarán las evaluaciones existentes.')"><i class="fas fa-check-double mr-1"></i>Evaluar riesgos pendientes</button></form></div><?php endif;?>
 <?php if(tiene_permiso('riesgos_sgsi','crear')):?><div class="alert alert-light border d-md-flex justify-content-between align-items-center"><div><strong><i class="fas fa-shield-virus text-info mr-1"></i>Inteligencia de Amenazas A.5.7</strong><div class="small text-muted">Crea o completa únicamente este riesgo, sin modificar los demás registros de la matriz.</div></div><form method="post" class="mt-2 mt-md-0"><?=csrf_input()?><input type="hidden" name="action" value="crear_riesgo_a57"><button class="btn btn-info font-weight-bold" onclick="return confirm('Se creará o completará únicamente el riesgo A.5.7 con su control, plan y evaluaciones. ¿Continuar?')"><i class="fas fa-shield-virus mr-1"></i>Completar riesgo A.5.7</button></form></div><?php endif;?>
 <div class="row mb-3"><div class="col-6 col-lg-3 mb-2"><div class="risk-stat"><strong><?=$stats['TOTAL']?></strong><span>Riesgos registrados</span></div></div><div class="col-6 col-lg-3 mb-2"><div class="risk-stat"><strong class="text-danger"><?=$stats['ALTOS']?></strong><span>Altos o críticos</span></div></div><div class="col-6 col-lg-3 mb-2"><div class="risk-stat"><strong class="text-warning"><?=$stats['SIN_RESIDUAL']?></strong><span>Sin evaluación residual</span></div></div><div class="col-6 col-lg-3 mb-2"><div class="risk-stat"><strong class="text-info"><?=$stats['PLANES']?></strong><span>Planes pendientes</span></div></div></div>
 <div class="card risk-panel"><div class="card-header bg-white"><div class="row align-items-center"><div class="col-md-7"><strong>Registro de riesgos</strong><div class="small text-muted">Una fila por escenario; un activo puede tener varios riesgos.</div></div><div class="col-md-5 risk-search"><i class="fas fa-search"></i><input id="riskSearch" class="form-control form-control-sm" placeholder="Buscar código, activo, proceso, escenario o dueño"></div></div></div><div class="table-responsive"><table class="table table-hover mb-0" id="tblRiesgos"><thead><tr><th>Riesgo</th><th>Activo principal</th><th>Proceso / dueño</th><th>Inherente</th><th>Tratamiento</th><th>Residual</th><th>Controles</th><th>Planes</th><th>Estado</th><th></th></tr></thead><tbody>
 <?php foreach($rows as $row):$asset=$row['Activo'];?><tr><td><span class="risk-code"><?=sgsi_h($row['Codigo'])?></span><div><?=sgsi_h($row['Escenario'])?></div></td><td class="risk-asset"><?php if($asset):?><strong><?=sgsi_h($asset['code'].' · '.$asset['name'])?></strong><span class="badge badge-light"><?=sgsi_h($asset['criticality'])?></span><?php else:?><span class="text-danger">Activo no disponible</span><?php endif;?></td><td><strong><?=sgsi_h($row['Proceso'])?></strong><div class="text-muted"><?=sgsi_h($row['DuenoRiesgo'])?></div></td><td><?=sgsi_level_badge($row['InherenteNivel'],$row['InherentePuntaje'])?></td><td><?=sgsi_h($row['Tratamiento']?:'-')?></td><td><?=sgsi_level_badge($row['ResidualNivel'],$row['ResidualPuntaje'])?></td><td><span class="badge badge-light"><?=(int)$row['TotalControles']?></span></td><td><span class="badge badge-<?=$row['PlanesPendientes']?'warning':'light'?>"><?=(int)$row['PlanesPendientes']?></span></td><td><?=sgsi_h(str_replace('_',' ',$row['Estado']))?></td><td><a href="riesgo.php?id=<?=(int)$row['IdRiesgo']?>" class="btn btn-sm btn-info" title="Abrir ficha"><i class="fas fa-arrow-right"></i></a></td></tr><?php endforeach;?>
 </tbody></table></div></div>
</div></section>
<?php
$inlineJs=<<<'JS'
$(function(){var t=$('#tblRiesgos').DataTable({pageLength:15,order:[[0,'desc']],language:DATATABLES_SPANISH,autoWidth:false,dom:'rt<"d-flex justify-content-between align-items-center flex-wrap p-3"lip>'});$('#riskSearch').on('input',function(){t.search(this.value).draw()});});
JS;
include_once __DIR__ . '/../../layouts/footer.php';
?>
