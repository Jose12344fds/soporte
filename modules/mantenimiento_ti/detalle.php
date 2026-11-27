<?php
$page_title = 'Detalle Mantenimiento TI';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist.php');
require_once(__DIR__ . '/funciones_checklist_pantalla.php');

ensure_mantenimiento_ti_schema();
mti_cp_ensure_schema();
require_permiso('mantenimiento_ti', 'ver');
$id = (int)($_GET['id'] ?? 0);

$rows = find_by_sql(
    "SELECT m.*, q.Host, q.Tipo_Activo, q.EstadoMaquina, q.Ip,
            NULLIF(m.usuario_asignado_nombre,'') AS usuario_equipo,
            uc.name AS creado_por_nombre, ua.name AS actualizado_por_nombre
     FROM activos_mantenimientos m
     INNER JOIN maquina q ON q.IdMaquina = m.IdMaquina
     LEFT JOIN users uc ON uc.id = m.creado_por
     LEFT JOIN users ua ON ua.id = m.actualizado_por
     WHERE m.id = {$id} LIMIT 1"
);
if (!$rows) {
    $session->msg('d', 'La orden solicitada no existe.');
    redirect('index.php', false);
}
$orden = $rows[0];
$orderPhotos = ['ANTES' => [], 'DESPUES' => []];
foreach (find_by_sql("SELECT * FROM activos_mantenimiento_fotos WHERE mantenimiento_id={$id} ORDER BY etapa,id") as $photo) {
    if (isset($orderPhotos[$photo['etapa']])) $orderPhotos[$photo['etapa']][] = $photo;
}
$detalles = find_by_sql(
    "SELECT d.*, u.name AS creado_por_nombre
     FROM activos_mantenimiento_detalle d
     LEFT JOIN users u ON u.id = d.creado_por
     WHERE d.mantenimiento_id = {$id} ORDER BY d.id ASC"
);
$historial = find_by_sql(
    "SELECT h.*, u.name AS registrado_por_nombre
     FROM activos_mantenimiento_historial h
     LEFT JOIN users u ON u.id = h.registrado_por
     WHERE h.mantenimiento_id = {$id} ORDER BY h.id DESC"
);
$detailCost = 0;
foreach ($detalles as $detail) $detailCost += (float)$detail['cantidad'] * (float)$detail['costo_unitario'];
$totalCost = $detailCost;
$cleanScreenChecklist = $db->fetch_assoc($db->query(
    "SELECT id,fecha_inspeccion,responsable_nombre FROM mti_checklist_pantalla WHERE mantenimiento_id={$id} LIMIT 1"
)) ?: null;
$checklistSchemaReady = mti_chk_schema_ready();
$orderPlan = null;
$availableTemplates = $orderChecklists = $orderFindings = [];
if ($checklistSchemaReady) {
    $orderPlan = mti_chk_plan_for_order($id);
    $templateCondition = $orderPlan
        ? '(p.plan_id=' . (int)$orderPlan['plan_id'] . ' OR p.plan_id IS NULL)'
        : 'p.plan_id IS NULL';
    $availableTemplates = find_by_sql(
        "SELECT p.* FROM activos_mantenimiento_checklist_plantillas p"
        . " WHERE p.activo=1 AND {$templateCondition}"
        . " AND EXISTS (SELECT 1 FROM activos_mantenimiento_checklist_items i"
        . " WHERE i.plantilla_id=p.id AND i.activo=1)"
        . " AND NOT EXISTS (SELECT 1 FROM activos_mantenimiento_checklists c"
        . " WHERE c.mantenimiento_id={$id} AND c.plantilla_id=p.id)"
        . ($orderPlan
            ? " ORDER BY CASE WHEN p.plan_id=" . (int)$orderPlan['plan_id'] . " THEN 0 ELSE 1 END,p.id DESC"
            : " ORDER BY p.id DESC")
    );
    $orderChecklists = find_by_sql(
        "SELECT c.*,p.codigo plantilla_codigo,u.name ejecutado_por_nombre"
        . " FROM activos_mantenimiento_checklists c"
        . " INNER JOIN activos_mantenimiento_checklist_plantillas p ON p.id=c.plantilla_id"
        . " LEFT JOIN users u ON u.id=c.ejecutado_por"
        . " WHERE c.mantenimiento_id={$id} ORDER BY c.id"
    );
    foreach ($orderChecklists as &$checklist) {
        $checklist['respuestas'] = find_by_sql(
            "SELECT r.*,COUNT(DISTINCT h.id) hallazgos"
            . " FROM activos_mantenimiento_checklist_respuestas r"
            . " LEFT JOIN activos_mantenimiento_hallazgos h"
            . " ON h.checklist_respuesta_id=r.id AND h.estado<>'ANULADO'"
            . " WHERE r.checklist_id=" . (int)$checklist['id']
            . " GROUP BY r.id ORDER BY r.orden_snapshot,r.id"
        );
    }
    unset($checklist);
    $orderFindings = find_by_sql(
        "SELECT h.*,COUNT(a.id) total_acciones,"
        . " SUM(CASE WHEN a.estado IN ('PENDIENTE','EN_PROCESO') THEN 1 ELSE 0 END) acciones_pendientes"
        . " FROM activos_mantenimiento_hallazgos h"
        . " LEFT JOIN activos_mantenimiento_hallazgo_acciones a ON a.hallazgo_id=h.id"
        . " WHERE h.mantenimiento_id={$id} GROUP BY h.id ORDER BY h.id DESC"
    );
}

function mtd_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function mtd_date($value) { return $value ? date('d/m/Y', strtotime($value)) : '-'; }

include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
:root { --dt-ink:#123746; --dt-teal:#137c8b; --dt-soft:#f2f7f6; }
.dt-banner { border:0; border-radius:14px; color:#fff; background:linear-gradient(115deg,var(--dt-ink),var(--dt-teal)); box-shadow:0 12px 30px rgba(18,55,70,.18); }
.dt-label { color:#71828a; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
.dt-value { color:var(--dt-ink); font-weight:700; }
.dt-panel { border:1px solid #dce7e6; border-radius:11px; background:#fff; overflow:hidden; }
.dt-panel-title { padding:12px 15px; color:var(--dt-ink); background:var(--dt-soft); border-bottom:1px solid #dce7e6; font-weight:800; }
.dt-history { position:relative; padding-left:20px; }
.dt-history:before { content:''; position:absolute; left:5px; top:5px; bottom:5px; width:2px; background:#cfe0df; }
.dt-event { position:relative; padding:0 0 15px 5px; }
.dt-event:before { content:''; position:absolute; left:-19px; top:4px; width:10px; height:10px; border-radius:50%; background:var(--dt-teal); border:2px solid #fff; box-shadow:0 0 0 2px #b9d7d5; }
.dt-total { color:var(--dt-teal); font-size:25px; font-weight:900; }
.dt-evidence-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; padding:15px; }
.dt-evidence-card { position:relative; min-height:215px; padding:10px; border:1px solid #d8e5e3; border-radius:12px; background:#f4f8f7; overflow:hidden; }
.dt-evidence-gallery { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:9px; }
.dt-evidence-card img { width:100%; height:132px; border-radius:9px; object-fit:cover; }
.dt-evidence-card button { display:block; width:100%; padding:0; border:0; background:transparent; cursor:zoom-in; }
.dt-evidence-tag { position:absolute; left:19px; top:19px; z-index:2; padding:5px 10px; border-radius:20px; color:#fff; background:rgba(18,55,70,.88); font-size:10px; font-weight:900; letter-spacing:.05em; }
.dt-evidence-empty { display:flex; height:132px; flex-direction:column; align-items:center; justify-content:center; color:#71868e; text-align:center; border:2px dashed #c3d6d3; border-radius:9px; }
.dt-evidence-empty i { margin-bottom:9px; color:#9bb4b0; font-size:30px; }
.dt-check-state{display:flex;align-items:center;gap:10px;min-width:155px}.dt-check-state input{width:22px;height:22px;cursor:pointer}.dt-check-state label{margin:0;cursor:pointer;font-weight:700;color:#a33}.dt-check-state input:checked+label{color:#21834f}.dt-check-state input:disabled+label{cursor:default}
#modalFotoDetalleTI .modal-content { border:0; border-radius:16px; overflow:hidden; background:#102e36; }
#modalFotoDetalleTI .modal-header { border:0; color:#fff; background:#123f49; }
#modalFotoDetalleTI .modal-body { padding:12px; text-align:center; }
#modalFotoDetalleTI img { max-width:100%; max-height:76vh; border-radius:10px; object-fit:contain; }
@media(max-width:767px){.dt-evidence-grid{grid-template-columns:1fr}}
</style>

<section class="content-header pb-1"><div class="container-fluid d-flex justify-content-between align-items-center">
 <h1 class="h4 mb-0"><i class="fas fa-clipboard-check mr-2 text-info"></i><?= mtd_h($orden['folio']) ?></h1>
 <div><a href="checklist_plantillas.php" class="btn btn-sm btn-outline-info"><i class="fas fa-list-check mr-1"></i>Plantillas</a> <a href="hallazgos.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-triangle-exclamation mr-1"></i>Hallazgos</a> <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Volver</a></div>
</div></section>

<section class="content"><div class="container-fluid">
 <?= display_msg($msg) ?>
 <div class="card dt-banner mb-3"><div class="card-body d-md-flex justify-content-between align-items-center">
  <div><div class="small text-uppercase font-weight-bold opacity-75"><?= mtd_h($orden['tipo']) ?> / <?= mtd_h($orden['prioridad']) ?></div><h2 class="h4 font-weight-bold mb-1"><?= mtd_h($orden['Host']) ?></h2><div><?= mtd_h($orden['Tipo_Activo']) ?> &middot; <?= mtd_h($orden['Ip'] ?: 'Sin IP') ?></div></div>
  <div class="text-md-right mt-3 mt-md-0"><div class="small text-uppercase opacity-75">Estado actual</div><div class="h5 font-weight-bold mb-0"><?= mtd_h(str_replace('_', ' ', $orden['estado'])) ?></div></div>
 </div></div>

 <div class="row">
  <div class="col-lg-8">
   <div class="dt-panel mb-3"><div class="dt-panel-title"><i class="fas fa-circle-info mr-2 text-info"></i>Informacion de la orden</div><div class="p-3">
    <div class="row mb-3"><div class="col-md-4"><div class="dt-label">Fecha programada</div><div class="dt-value"><?= mtd_date($orden['fecha_programada']) ?></div></div><div class="col-md-4"><div class="dt-label">Fecha realizada</div><div class="dt-value"><?= mtd_date($orden['fecha_ejecucion']) ?></div></div><div class="col-md-4"><div class="dt-label">Estado</div><div class="dt-value"><?= mtd_h(str_replace('_',' ',$orden['estado'])) ?></div></div></div>
    <div class="dt-label">Motivo o falla reportada</div><p><?= nl2br(mtd_h($orden['motivo'])) ?></p>
    <div class="dt-label">Diagnostico</div><p><?= $orden['diagnostico'] ? nl2br(mtd_h($orden['diagnostico'])) : '<span class="text-muted">Pendiente</span>' ?></p>
    <div class="dt-label">Trabajo realizado</div><p class="mb-0"><?= $orden['trabajo_realizado'] ? nl2br(mtd_h($orden['trabajo_realizado'])) : '<span class="text-muted">Pendiente</span>' ?></p>
   </div></div>

   <div class="dt-panel mb-3">
    <div class="dt-panel-title d-flex justify-content-between align-items-center"><span><i class="fas fa-camera-retro mr-2 text-info"></i>Antes y despues del mantenimiento</span><div><span class="badge badge-info mr-2"><?= count($orderPhotos['ANTES']) + count($orderPhotos['DESPUES']) ?>/10 fotos</span><?php if(tiene_permiso('mantenimiento_ti','editar') && (count($orderPhotos['ANTES'])<5 || count($orderPhotos['DESPUES'])<5)): ?><button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalAgregarFotos"><i class="fas fa-plus mr-1"></i>Agregar fotos</button><?php endif; ?></div></div>
    <div class="dt-evidence-grid">
     <?php foreach (['ANTES' => 'Como se encontro el equipo', 'DESPUES' => 'Como quedo el equipo'] as $photoType => $photoCaption): ?>
      <div class="dt-evidence-card">
       <span class="dt-evidence-tag"><?= mtd_h($photoType) ?> · <?= count($orderPhotos[$photoType]) ?>/5</span>
       <?php if ($orderPhotos[$photoType]): ?>
        <div class="dt-evidence-gallery"><?php foreach ($orderPhotos[$photoType] as $photo): $photoUrl='foto.php?foto='.(int)$photo['id'].'&v='.urlencode((string)$photo['created_at']); ?>
         <button type="button" class="btnFotoOrden" data-toggle="modal" data-target="#modalFotoDetalleTI" data-title="<?= mtd_h(ucfirst(strtolower($photoType)) . ' del mantenimiento') ?>" data-src="<?=mtd_h($photoUrl)?>"><img src="<?=mtd_h($photoUrl)?>" alt="<?= mtd_h($photoCaption) ?>" loading="lazy"></button>
        <?php endforeach; ?></div>
       <?php else: ?>
        <div class="dt-evidence-empty"><i class="fas fa-image"></i><strong>Foto pendiente</strong><small><?= mtd_h($photoCaption) ?></small></div>
       <?php endif; ?>
      </div>
     <?php endforeach; ?>
    </div>
   </div>

   <div class="dt-panel mb-3"><div class="dt-panel-title d-flex justify-content-between align-items-center"><span><i class="fas fa-list-check mr-2 text-info"></i>Actividades, servicios y repuestos</span><?php if(tiene_permiso('mantenimiento_ti','editar') && $orden['estado'] !== 'CANCELADO'): ?><button class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalDetalle"><i class="fas fa-plus mr-1"></i>Agregar</button><?php endif; ?></div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Tipo</th><th>Descripcion</th><th>Cantidad</th><th>Costo unit.</th><th>Resultado</th><th>Subtotal</th></tr></thead><tbody>
    <?php if(!$detalles): ?><tr><td colspan="6" class="text-center text-muted py-4">Aun no se registraron actividades o repuestos.</td></tr><?php endif; ?>
    <?php foreach($detalles as $detail): ?><tr><td><span class="badge badge-light border"><?= mtd_h($detail['tipo']) ?></span></td><td><?= mtd_h($detail['descripcion']) ?><div class="small text-muted"><?= mtd_h($detail['creado_por_nombre'] ?: '') ?></div></td><td><?= number_format((float)$detail['cantidad'],2) ?></td><td>S/ <?= number_format((float)$detail['costo_unitario'],2) ?></td><td><?= mtd_h($detail['resultado'] ?: '-') ?></td><td>S/ <?= number_format((float)$detail['cantidad']*(float)$detail['costo_unitario'],2) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
   </div>

   <div class="dt-panel mb-3" id="pantalla-limpia">
    <div class="dt-panel-title d-flex justify-content-between align-items-center"><span><i class="fas fa-shield-screen mr-2 text-info"></i>Escritorio y pantalla limpia</span><span class="badge badge-light border">T-SI-PT-14</span></div>
    <div class="p-3 d-md-flex justify-content-between align-items-center">
     <div><?php if($cleanScreenChecklist): ?><strong>Inspección registrada</strong><div class="small text-muted"><?=mtd_h(date('d/m/Y H:i',strtotime($cleanScreenChecklist['fecha_inspeccion'])))?> &middot; Responsable: <?=mtd_h($cleanScreenChecklist['responsable_nombre'])?></div><?php else: ?><strong>Checklist pendiente para esta OT</strong><div class="small text-muted">Verifique los 12 criterios de escritorio y pantalla limpia del equipo <?=mtd_h($orden['Host'])?>.</div><?php endif; ?></div>
     <div class="mt-3 mt-md-0"><?php if($cleanScreenChecklist): ?><a href="checklist_pantalla_detalle.php?id=<?=(int)$cleanScreenChecklist['id']?>" class="btn btn-info font-weight-bold"><i class="fas fa-eye mr-1"></i>Ver checklist</a><?php elseif(tiene_permiso('mantenimiento_ti','crear')): ?><a href="checklist_pantalla_form.php?mantenimiento_id=<?=$id?>" class="btn btn-success font-weight-bold"><i class="fas fa-clipboard-check mr-1"></i>Realizar checklist</a><?php endif; ?></div>
    </div>
   </div>

   <div class="dt-panel mb-3" id="checklists">
    <div class="dt-panel-title d-flex justify-content-between align-items-center"><span><i class="fas fa-clipboard-list mr-2 text-info"></i>Checklists</span><span class="badge badge-info"><?=count($orderChecklists)?></span></div>
    <?php if(!$checklistSchemaReady):?>
     <div class="alert alert-warning m-3 mb-3">Modulo pendiente de migracion. Ejecute <code>01_checklists_hallazgos.sql</code> con respaldo.</div>
    <?php else:?>
     <?php if($orderPlan):?><div class="alert alert-light border m-3 py-2"><strong>Plan:</strong> <?=mtd_h($orderPlan['codigo'].' - '.$orderPlan['nombre'])?>. Puede asignar su checklist especifico o uno general.</div><?php else:?><div class="alert alert-light border m-3 py-2">OT manual/correctiva: se ofrecen plantillas generales.</div><?php endif;?>
     <?php if($availableTemplates&&tiene_permiso('mantenimiento_ti','editar')):?><form method="post" action="asignar_checklist.php" class="p-3 border-bottom"><?=csrf_input()?><input type="hidden" name="mantenimiento_id" value="<?=$id?>"><label class="font-weight-bold">Jalar checklist maestro a esta orden</label><div class="input-group"><select name="plantilla_id" class="form-control" required><?php foreach($availableTemplates as $templateIndex=>$template): $recommended=$orderPlan&&(int)$template['plan_id']===(int)$orderPlan['plan_id'];?><option value="<?=(int)$template['id']?>" <?=$templateIndex===0?'selected':''?>><?=mtd_h(($recommended?'RECOMENDADO - ':'').$template['codigo'].' - '.$template['nombre'].' (v'.$template['version'].')')?></option><?php endforeach;?></select><div class="input-group-append"><button class="btn btn-info"><i class="fas fa-link mr-1"></i>Crear ejecucion</button></div></div><small class="form-text text-muted">El checklist predefinido para el plan aparece primero. Se copiara y quedara relacionado con la orden <?=mtd_h($orden['folio'])?>.</small></form><?php endif;?>
     <?php if(!$orderChecklists):?><div class="text-center text-muted py-4">No hay checklists asignados.</div><?php endif;?>
     <?php foreach($orderChecklists as $checklist): $locked=$checklist['estado']==='COMPLETADO';?>
      <form method="post" action="guardar_checklist.php" class="border-top"><?=csrf_input()?><input type="hidden" name="checklist_id" value="<?=(int)$checklist['id']?>">
       <div class="p-3 bg-light d-flex justify-content-between align-items-center"><div><strong>Ejecucion: <?=mtd_h($checklist['nombre_snapshot'])?></strong><div class="small text-muted"><?=mtd_h($checklist['plantilla_codigo'])?> &middot; Version <?=(int)$checklist['version_snapshot']?> &middot; Relacionada con <?=mtd_h($orden['folio'])?></div><?php if(!empty($checklist['descripcion_snapshot'])):?><div class="small"><?=mtd_h($checklist['descripcion_snapshot'])?></div><?php endif;?></div><div class="text-right"><span class="badge badge-<?=$locked?'success':'warning'?>"><?=mtd_h($checklist['estado'])?></span><?php if(!$locked):?><button type="button" class="btn btn-sm btn-outline-success d-block mt-2 ml-auto btnTodosConformes"><i class="fas fa-check-double mr-1"></i>Todos conformes</button><?php endif;?></div></div>
       <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th style="width:40%">Componente o punto de revision</th><th>Estado</th><th>Observacion/diagnostico</th><th>Hallazgo / accion</th></tr></thead><tbody>
       <?php foreach($checklist['respuestas'] as $response): $rid=(int)$response['id'];$type=$response['tipo_respuesta_snapshot'];$currentValue=$response['respuesta_valor'];if((int)$response['hallazgos']>0&&in_array($type,['SI_NO_NA','CUMPLE_NO_CUMPLE_NA'],true)){$currentValue=$type==='SI_NO_NA'?'NO':'NO_CUMPLE';}elseif(($currentValue===null||$currentValue==='')&&$checklist['estado']==='PENDIENTE'){$currentValue=$type==='SI_NO_NA'?'SI':($type==='CUMPLE_NO_CUMPLE_NA'?'CUMPLE':'');}?>
        <tr><td><?=mtd_h($response['descripcion_snapshot'])?><?=$response['obligatorio_snapshot']?'<span class="text-danger"> *</span>':''?><?=$response['requiere_evidencia_si_falla_snapshot']?'<div class="small text-warning">Evidencia requerida si falla</div>':''?></td><td>
         <?php if(in_array($type,['SI_NO_NA','CUMPLE_NO_CUMPLE_NA'],true)): $positive=$type==='SI_NO_NA'?'SI':'CUMPLE';$negative=$type==='SI_NO_NA'?'NO':'NO_CUMPLE';$checked=$currentValue===$positive;?>
          <?php if(!$locked):?><input type="hidden" name="respuesta[<?=$rid?>]" value="<?=$negative?>"><?php endif;?><div class="dt-check-state"><input type="checkbox" id="respuesta<?=$rid?>" name="respuesta[<?=$rid?>]" value="<?=$positive?>" class="respuesta-checklist" data-positive="<?=$positive?>" data-negative="<?=$negative?>" <?=$checked?'checked':''?> <?=$locked?'disabled':''?>><label for="respuesta<?=$rid?>"><span class="estado-check-text"><?=$checked?'CONFORME':'CON FALLA'?></span></label></div>
         <?php elseif($type==='NUMERICO'):?><input type="number" step="any" name="respuesta[<?=$rid?>]" value="<?=mtd_h($response['respuesta_valor'])?>" class="form-control form-control-sm" <?=$locked?'disabled':''?>>
         <?php else:?><input name="respuesta[<?=$rid?>]" value="<?=mtd_h($response['respuesta_valor'])?>" class="form-control form-control-sm" <?=$locked?'disabled':''?>><?php endif;?>
        </td><td><input name="observacion[<?=$rid?>]" value="<?=mtd_h($response['observacion'])?>" class="form-control form-control-sm" placeholder="Detalle de la revision" <?=$locked?'disabled':''?>></td><td class="text-center"><span class="badge badge-<?=$response['hallazgos']?'danger':'light'?>"><?=(int)$response['hallazgos']?> hallazgo(s)</span><?php if(!$locked&&tiene_permiso('mantenimiento_ti','editar')):?><button type="button" class="btn btn-sm btn-outline-danger btnHallazgo ml-1" data-response="<?=$rid?>" data-question="<?=mtd_h($response['descripcion_snapshot'])?>" data-toggle="modal" data-target="#modalHallazgo"><i class="fas fa-triangle-exclamation mr-1"></i>Levantar</button><?php endif;?></td></tr>
       <?php endforeach;?></tbody></table></div>
       <?php if(!$locked&&tiene_permiso('mantenimiento_ti','editar')):?><div class="p-3 text-right"><button name="modo" value="parcial" class="btn btn-outline-info"><i class="fas fa-save mr-1"></i>Guardar avance</button> <button name="modo" value="completar" class="btn btn-success"><i class="fas fa-check mr-1"></i>Completar y bloquear</button></div><?php endif;?>
      </form>
     <?php endforeach;?>
    <?php endif;?>
   </div>

   <div class="dt-panel mb-3" id="hallazgos">
    <div class="dt-panel-title d-flex justify-content-between align-items-center"><span><i class="fas fa-triangle-exclamation mr-2 text-danger"></i>Hallazgos</span><?php if($checklistSchemaReady&&tiene_permiso('mantenimiento_ti','editar')):?><button class="btn btn-sm btn-danger btnHallazgo" data-response="0" data-question="Hallazgo manual" data-toggle="modal" data-target="#modalHallazgo"><i class="fas fa-plus mr-1"></i>Registrar</button><?php endif;?></div>
    <?php if($checklistSchemaReady):?><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Codigo</th><th>Hallazgo</th><th>Criticidad</th><th>Estado</th><th>Acciones</th><th></th></tr></thead><tbody>
     <?php foreach($orderFindings as $finding):?><tr><td class="font-weight-bold"><?=mtd_h($finding['codigo'])?></td><td><?=mtd_h($finding['titulo'])?><div class="small text-muted"><?=mtd_h($finding['componente'])?></div></td><td><span class="badge badge-<?=in_array($finding['criticidad'],['ALTA','CRITICA'],true)?'danger':'warning'?>"><?=mtd_h($finding['criticidad'])?></span></td><td><?=mtd_h(str_replace('_',' ',$finding['estado']))?></td><td><?=(int)$finding['acciones_pendientes']?> pendientes / <?=(int)$finding['total_acciones']?></td><td><a href="hallazgo.php?id=<?=(int)$finding['id']?>" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a></td></tr><?php endforeach;?>
     <?php if(!$orderFindings):?><tr><td colspan="6" class="text-center text-muted py-4">No se registraron hallazgos.</td></tr><?php endif;?></tbody></table></div><?php endif;?>
   </div>
  </div>

  <div class="col-lg-4">
   <div class="dt-panel mb-3"><div class="dt-panel-title"><i class="fas fa-wallet mr-2 text-info"></i>Resumen de costos</div><div class="p-3"><div class="d-flex justify-content-between"><span>Repuestos y servicios</span><strong>S/ <?= number_format($detailCost,2) ?></strong></div><hr><div class="d-flex justify-content-between align-items-end"><span class="font-weight-bold">Total</span><span class="dt-total">S/ <?= number_format($totalCost,2) ?></span></div></div></div>
   <div class="dt-panel mb-3"><div class="dt-panel-title"><i class="fas fa-user-gear mr-2 text-info"></i>Personas vinculadas</div><div class="p-3"><div class="dt-label">Usuario del equipo al crear la OT</div><div class="dt-value mb-3"><?= mtd_h($orden['usuario_equipo'] ?: 'Sin usuario asignado') ?></div><div class="dt-label">Tecnico responsable</div><div class="dt-value"><?= mtd_h($orden['tecnico_responsable'] ?: 'Sin asignar') ?></div><div class="small text-muted mt-2">Creado por <?= mtd_h($orden['creado_por_nombre'] ?: 'Sistema') ?> el <?= mtd_h(date('d/m/Y H:i', strtotime($orden['created_at']))) ?></div></div></div>
   <div class="dt-panel"><div class="dt-panel-title"><i class="fas fa-clock-rotate-left mr-2 text-info"></i>Bitacora</div><div class="p-3 dt-history">
    <?php foreach($historial as $event): ?><div class="dt-event"><strong><?= mtd_h(str_replace('_',' ',$event['accion'])) ?></strong><div class="small"><?= mtd_h($event['comentario'] ?: '') ?></div><div class="small text-muted"><?= mtd_h($event['registrado_por_nombre'] ?: 'Sistema') ?> &middot; <?= mtd_h(date('d/m/Y H:i',strtotime($event['created_at']))) ?></div></div><?php endforeach; ?>
   </div></div>
  </div>
 </div>
</div></section>

<?php if(tiene_permiso('mantenimiento_ti','editar') && $orden['estado'] !== 'CANCELADO'): ?>
<div class="modal fade" id="modalDetalle" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="post" action="guardar_detalle.php">
 <?= csrf_input() ?><input type="hidden" name="mantenimiento_id" value="<?= $id ?>">
 <div class="modal-header bg-info"><h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i>Agregar detalle</h5><button class="close text-white" type="button" data-dismiss="modal">&times;</button></div>
 <div class="modal-body"><div class="row"><div class="col-md-4 form-group"><label>Tipo *</label><select name="tipo" class="form-control"><option>ACTIVIDAD</option><option>REPUESTO</option><option>SERVICIO</option></select></div><div class="col-md-4 form-group"><label>Cantidad *</label><input type="number" name="cantidad" class="form-control" min="0.01" step="0.01" value="1" required></div><div class="col-md-4 form-group"><label>Costo unitario (S/)</label><input type="number" name="costo_unitario" class="form-control" min="0" step="0.01" value="0"></div></div><div class="form-group"><label>Descripcion *</label><textarea name="descripcion" class="form-control" rows="2" maxlength="500" required></textarea></div><div class="form-group mb-0"><label>Resultado u observacion</label><textarea name="resultado" class="form-control" rows="2" maxlength="250"></textarea></div></div>
 <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-info font-weight-bold"><i class="fas fa-save mr-1"></i>Guardar detalle</button></div>
</form></div></div></div>
<?php endif; ?>

<?php if($checklistSchemaReady&&tiene_permiso('mantenimiento_ti','editar')):?>
<div class="modal fade" id="modalHallazgo" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="post" action="guardar_hallazgo.php" enctype="multipart/form-data">
 <?=csrf_input()?><input type="hidden" name="mantenimiento_id" value="<?=$id?>"><input type="hidden" name="checklist_respuesta_id" id="hallazgoRespuesta" value="0"><input type="hidden" name="respuesta_estado" id="hallazgoRespuestaEstado" value="">
 <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-triangle-exclamation mr-2"></i>Registrar hallazgo</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
 <div class="modal-body"><div class="alert alert-light border py-2" id="hallazgoPregunta">Hallazgo manual</div><div class="row"><div class="col-md-5 form-group"><label>Componente</label><input name="componente" class="form-control" maxlength="200"></div><div class="col-md-7 form-group"><label>Titulo *</label><input name="titulo" class="form-control" maxlength="200" required></div></div><div class="form-group"><label>Descripcion *</label><textarea name="descripcion" class="form-control" rows="3" required></textarea></div><div class="row"><div class="col-md-4 form-group"><label>Criticidad *</label><select name="criticidad" class="form-control"><option>BAJA</option><option selected>MEDIA</option><option>ALTA</option><option>CRITICA</option></select></div><div class="col-md-4 form-group pt-4"><label><input type="checkbox" name="requiere_parada"> Requiere parada</label></div><div class="col-md-4 form-group"><label>Evidencia inicial</label><input type="file" name="evidencia" class="form-control-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.mp4"></div></div><div class="form-group mb-0"><label>Medida de control inmediata</label><textarea name="accion_inmediata" class="form-control" rows="2" placeholder="Obligatoria si es critica o requiere parada"></textarea></div></div>
 <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-danger"><i class="fas fa-save mr-1"></i>Registrar hallazgo</button></div>
</form></div></div></div>
<?php endif;?>

<?php if(tiene_permiso('mantenimiento_ti','editar') && (count($orderPhotos['ANTES'])<5 || count($orderPhotos['DESPUES'])<5)): ?>
<div class="modal fade" id="modalAgregarFotos" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="post" action="guardar_fotos.php" enctype="multipart/form-data" id="formAgregarFotos">
 <?=csrf_input()?><input type="hidden" name="mantenimiento_id" value="<?=$id?>">
 <div class="modal-header bg-info text-white"><div><h5 class="modal-title font-weight-bold"><i class="fas fa-images mr-2"></i>Agregar evidencias</h5><small>Complete la galería sin reemplazar las fotos existentes.</small></div><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
 <div class="modal-body"><div class="alert alert-light border"><i class="fas fa-circle-info text-info mr-1"></i>Puede guardar hasta <strong>5 fotos Antes</strong> y <strong>5 fotos Después</strong>. Formatos JPG, PNG o WEBP; máximo 15 MB cada una.</div><div class="row">
  <div class="col-md-6"><div class="border rounded p-3 h-100"><div class="d-flex justify-content-between mb-2"><strong class="text-warning"><i class="fas fa-camera mr-1"></i>ANTES</strong><span class="badge badge-light border"><?=count($orderPhotos['ANTES'])?>/5 guardadas</span></div><label>Agregar hasta <?=5-count($orderPhotos['ANTES'])?> foto(s)</label><input type="file" name="fotos_antes[]" class="form-control-file mti-gallery-input" accept="image/jpeg,image/png,image/webp" multiple data-existing="<?=count($orderPhotos['ANTES'])?>"><div class="small text-muted mt-2 mti-selected-count">Ninguna nueva seleccionada.</div></div></div>
  <div class="col-md-6 mt-3 mt-md-0"><div class="border rounded p-3 h-100"><div class="d-flex justify-content-between mb-2"><strong class="text-success"><i class="fas fa-camera mr-1"></i>DESPUÉS</strong><span class="badge badge-light border"><?=count($orderPhotos['DESPUES'])?>/5 guardadas</span></div><label>Agregar hasta <?=5-count($orderPhotos['DESPUES'])?> foto(s)</label><input type="file" name="fotos_despues[]" class="form-control-file mti-gallery-input" accept="image/jpeg,image/png,image/webp" multiple data-existing="<?=count($orderPhotos['DESPUES'])?>"><div class="small text-muted mt-2 mti-selected-count">Ninguna nueva seleccionada.</div></div></div>
 </div></div>
 <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-success font-weight-bold"><i class="fas fa-upload mr-1"></i>Guardar fotografías</button></div>
</form></div></div></div>
<?php endif; ?>

<div class="modal fade" id="modalFotoDetalleTI" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
 <div class="modal-header"><h5 class="modal-title font-weight-bold"><i class="fas fa-image mr-2"></i><span id="fotoDetalleTitulo">Evidencia</span></h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
 <div class="modal-body"><img id="fotoDetalleImagen" alt="Evidencia ampliada"></div>
</div></div></div>

<?php
$inlineJs = <<<'JS'
$(function(){
  $('.btnFotoOrden').on('click',function(){
    $('#fotoDetalleTitulo').text(this.dataset.title || 'Evidencia');
    $('#fotoDetalleImagen').attr('src',this.dataset.src || '');
  });
  $('#modalFotoDetalleTI').on('hidden.bs.modal',function(){ $('#fotoDetalleImagen').removeAttr('src'); });
  $('.mti-gallery-input').on('change',function(){
    var files=Array.from(this.files||[]),existing=parseInt(this.dataset.existing||'0',10),remaining=5-existing;
    if(files.length>remaining){this.value='';$(this).siblings('.mti-selected-count').text('Ninguna nueva seleccionada.');Swal.fire('Límite de fotografías','Solo puede agregar '+remaining+' foto(s) en esta etapa.','warning');return;}
    $(this).siblings('.mti-selected-count').text(files.length?files.length+' foto(s) nueva(s) seleccionada(s).':'Ninguna nueva seleccionada.');
  });
  $('.btnHallazgo').on('click',function(event){
    var responseId = parseInt(this.dataset.response || '0', 10);
    var responseControl = responseId > 0 ? document.getElementById('respuesta' + responseId) : null;
    if (responseControl && responseControl.checked) {
      event.preventDefault();
      event.stopImmediatePropagation();
      Swal.fire({
        icon: 'warning',
        title: 'El componente esta Conforme',
        text: 'Marquelo como Con falla antes de levantar un hallazgo.',
        confirmButtonText: 'Entendido'
      });
      return false;
    }
    $('#hallazgoRespuesta').val(responseId);
    $('#hallazgoRespuestaEstado').val(responseControl ? responseControl.dataset.negative : '');
    $('#hallazgoPregunta').text(this.dataset.question || 'Hallazgo manual');
  });
  $('.btnTodosConformes').on('click',function(){
    $(this).closest('form').find('.respuesta-checklist').each(function(){
      this.checked = true;
      $(this).trigger('change');
    });
  });
  $('.respuesta-checklist').on('change',function(){
    $(this).next('label').find('.estado-check-text').text(this.checked ? 'CONFORME' : 'CON FALLA');
  });
});
JS;
include_once(__DIR__ . '/../../layouts/footer.php');
?>
