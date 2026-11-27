<?php
$page_title = 'Checklists maestros TI';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist.php');
ensure_mantenimiento_ti_schema();
require_permiso('mantenimiento_ti', 'ver');
$schemaReady = mti_chk_schema_ready();
$templates = $plans = $items = $compatibleOrders = [];
$selected = null;
$selectedId = (int)($_GET['plantilla'] ?? 0);
if ($schemaReady) {
    $plans = find_by_sql("SELECT id,codigo,nombre FROM activos_mantenimiento_planes ORDER BY nombre");
    $templates = find_by_sql(
        "SELECT p.*,mp.codigo plan_codigo,mp.nombre plan_nombre,"
        . " COUNT(DISTINCT i.id) total_items,COUNT(DISTINCT c.id) ejecuciones"
        . " FROM activos_mantenimiento_checklist_plantillas p"
        . " LEFT JOIN activos_mantenimiento_planes mp ON mp.id=p.plan_id"
        . " LEFT JOIN activos_mantenimiento_checklist_items i ON i.plantilla_id=p.id"
        . " LEFT JOIN activos_mantenimiento_checklists c ON c.plantilla_id=p.id"
        . " GROUP BY p.id ORDER BY p.activo DESC,p.id DESC"
    );
    if ($selectedId > 0) {
        foreach ($templates as $templateRow) {
            if ((int)$templateRow['id'] === $selectedId) {
                $selected = $templateRow;
                break;
            }
        }
        $items = find_by_sql(
            "SELECT * FROM activos_mantenimiento_checklist_items"
            . " WHERE plantilla_id={$selectedId} ORDER BY orden,id"
        );
        if ($selected) {
            $planFilter = !empty($selected['plan_id'])
                ? ' AND o.plan_id=' . (int)$selected['plan_id']
                : '';
            $compatibleOrders = find_by_sql(
                "SELECT m.id,m.folio,m.fecha_programada,m.estado,q.Host,q.Tipo_Activo,"
                . " o.plan_id,c.id checklist_id,c.estado checklist_estado"
                . " FROM activos_mantenimientos m"
                . " INNER JOIN maquina q ON q.IdMaquina=m.IdMaquina"
                . " LEFT JOIN activos_mantenimiento_ocurrencias o ON o.mantenimiento_id=m.id"
                . " LEFT JOIN activos_mantenimiento_checklists c"
                . " ON c.mantenimiento_id=m.id AND c.plantilla_id={$selectedId}"
                . " WHERE m.estado<>'CANCELADO'{$planFilter}"
                . " ORDER BY (c.id IS NULL) DESC,m.fecha_programada DESC,m.id DESC LIMIT 200"
            );
        }
    }
}
function mtpchk_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
.chk-hero{border:0;border-radius:14px;color:#fff;background:linear-gradient(120deg,#173f4f,#16858d)}
.chk-card{border:1px solid #dce8e8;border-radius:12px;overflow:hidden}
.chk-code{font-weight:900;color:#116b73}.chk-type{font-size:11px;letter-spacing:.03em}
.chk-flow{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:18px}.chk-step{padding:12px 14px;border:1px solid rgba(255,255,255,.25);border-radius:10px;background:rgba(255,255,255,.09)}.chk-step strong{display:block;margin-bottom:3px}.chk-step span{font-size:12px;opacity:.85}@media(max-width:767px){.chk-flow{grid-template-columns:1fr}}
</style>
<section class="content-header pb-1"><div class="container-fluid d-flex justify-content-between align-items-center">
 <h1 class="h4 mb-0"><i class="fas fa-list-check mr-2 text-info"></i>Checklists maestros</h1>
 <div><a href="hallazgos.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-triangle-exclamation mr-1"></i>Hallazgos</a> <a href="index.php" class="btn btn-sm btn-outline-secondary">Volver</a></div>
</div></section>
<section class="content"><div class="container-fluid"><?=display_msg($msg)?>
<?php if(!$schemaReady):?>
 <div class="alert alert-warning"><strong>Migracion incompleta.</strong> Faltan las tablas: <code><?= mtpchk_h(implode(', ', mti_chk_schema_missing())) ?></code>. Verifique que <code>01_checklists_hallazgos.sql</code> se ejecuto completo en la misma base de datos de esta aplicacion.</div>
<?php else:?>
 <div class="card chk-hero mb-3"><div class="card-body"><div class="small text-uppercase font-weight-bold">Mantenimiento TI</div><h2 class="h4 font-weight-bold mb-1">Checklist padre reutilizable</h2><div>Defina una sola vez los componentes que deben revisarse. Luego jale este checklist a cada orden de mantenimiento.</div><div class="chk-flow"><div class="chk-step"><strong>1. Crear checklist maestro</strong><span>Ejemplo: revision preventiva de PC.</span></div><div class="chk-step"><strong>2. Agregar componentes</strong><span>Disco, fuente, ventiladores, procesador y otros.</span></div><div class="chk-step"><strong>3. Asignar a una orden</strong><span>Se crea una ejecucion relacionada al mantenimiento, sin modificar el padre.</span></div></div></div></div>
 <div class="row">
  <div class="col-lg-7">
   <div class="card chk-card"><div class="card-header bg-white d-flex justify-content-between"><strong>Checklists maestros registrados</strong><input id="buscarPlantilla" class="form-control form-control-sm w-50" placeholder="Buscar..."></div>
    <div class="table-responsive"><table class="table table-hover mb-0" id="tblPlantillas"><thead><tr><th>Checklist padre</th><th>Aplicacion</th><th>Componentes</th><th>Ordenes usadas</th><th>Estado</th><th></th></tr></thead><tbody>
    <?php foreach($templates as $template):?><tr>
     <td><span class="chk-code"><?=mtpchk_h($template['codigo'])?></span><div class="font-weight-bold"><?=mtpchk_h($template['nombre'])?></div><small>Version <?=(int)$template['version']?></small></td>
     <td><?=!empty($template['plan_id'])?mtpchk_h($template['plan_codigo'].' - '.$template['plan_nombre']):'<span class="badge badge-secondary">GENERAL</span>'?></td>
     <td class="text-center"><?=(int)$template['total_items']?></td><td class="text-center"><?=(int)$template['ejecuciones']?></td>
     <td><span class="badge badge-<?=$template['activo']?'success':'secondary'?>"><?=$template['activo']?'ACTIVA':'INACTIVA'?></span></td>
     <td class="text-nowrap"><a class="btn btn-sm btn-info" href="?plantilla=<?=(int)$template['id']?>"><i class="fas fa-eye"></i></a>
      <?php if(tiene_permiso('mantenimiento_ti','editar')):?><form class="d-inline" method="post" action="guardar_checklist_plantilla.php"><?=csrf_input()?><input type="hidden" name="accion" value="toggle_plantilla"><input type="hidden" name="plantilla_id" value="<?=(int)$template['id']?>"><button class="btn btn-sm btn-outline-secondary"><i class="fas fa-power-off"></i></button></form><?php endif;?>
     </td></tr><?php endforeach;?>
    </tbody></table></div>
   </div>
  </div>
  <div class="col-lg-5">
   <?php if(tiene_permiso('mantenimiento_ti','editar')):?>
   <div class="card chk-card mb-3"><div class="card-header bg-info font-weight-bold">Nuevo checklist maestro</div><form method="post" action="guardar_checklist_plantilla.php"><div class="card-body">
    <?=csrf_input()?><input type="hidden" name="accion" value="crear_plantilla">
    <div class="form-group"><label>Nombre *</label><input name="nombre" class="form-control" maxlength="200" required></div>
    <div class="form-group"><label>Donde se podra utilizar</label><select name="plan_id" class="form-control"><option value="">GENERAL - disponible para cualquier mantenimiento</option><?php foreach($plans as $plan):?><option value="<?=(int)$plan['id']?>"><?=mtpchk_h('Solo '.$plan['codigo'].' - '.$plan['nombre'])?></option><?php endforeach;?></select><small class="form-text text-muted">Use GENERAL si desea jalarlo manualmente en distintas ordenes.</small></div>
    <div class="form-group mb-0"><label>Descripcion</label><textarea name="descripcion" class="form-control" rows="2"></textarea></div>
   </div><div class="card-footer text-right"><button class="btn btn-info font-weight-bold"><i class="fas fa-save mr-1"></i>Crear checklist padre</button></div></form></div>
   <?php endif;?>
  </div>
 </div>
 <?php if($selectedId>0&&$selected):?>
 <div class="card chk-card mt-3"><div class="card-header bg-dark text-white"><strong><?=mtpchk_h($selected['codigo'].' - '.$selected['nombre'])?></strong><?php if((int)$selected['ejecuciones']>0):?><span class="badge badge-info ml-2">En uso: puede agregar nuevos puntos</span><?php endif;?></div>
  <div class="card-body border-bottom">
   <div class="d-flex justify-content-between align-items-center mb-2"><div><h3 class="h6 font-weight-bold mb-1">Asignar este checklist padre a un mantenimiento</h3><div class="small text-muted"><?=empty($selected['plan_id'])?'Checklist general: disponible para cualquier orden activa.':'Checklist predefinido para el plan '.$selected['plan_codigo'].'. Solo se muestran sus ordenes.'?></div></div><span class="badge badge-info"><?=count($compatibleOrders)?> ordenes</span></div>
   <div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Orden</th><th>Equipo</th><th>Programada</th><th>Estado</th><th>Checklist</th><th></th></tr></thead><tbody>
   <?php foreach($compatibleOrders as $maintenance):?><tr><td class="font-weight-bold"><?=mtpchk_h($maintenance['folio'])?></td><td><?=mtpchk_h($maintenance['Host'])?><div class="small text-muted"><?=mtpchk_h($maintenance['Tipo_Activo'])?></div></td><td><?=date('d/m/Y',strtotime($maintenance['fecha_programada']))?></td><td><?=mtpchk_h(str_replace('_',' ',$maintenance['estado']))?></td><td><?php if($maintenance['checklist_id']):?><span class="badge badge-success"><?=mtpchk_h($maintenance['checklist_estado'])?></span><?php else:?><span class="badge badge-light border">SIN ASIGNAR</span><?php endif;?></td><td class="text-right"><?php if($maintenance['checklist_id']):?><a href="detalle.php?id=<?=(int)$maintenance['id']?>#checklists" class="btn btn-sm btn-outline-info"><i class="fas fa-eye mr-1"></i>Abrir</a><?php elseif(tiene_permiso('mantenimiento_ti','editar')):?><form method="post" action="asignar_checklist.php" class="d-inline"><?=csrf_input()?><input type="hidden" name="mantenimiento_id" value="<?=(int)$maintenance['id']?>"><input type="hidden" name="plantilla_id" value="<?=$selectedId?>"><button class="btn btn-sm btn-info"><i class="fas fa-link mr-1"></i>Asignar</button></form><?php endif;?></td></tr><?php endforeach;?>
   <?php if(!$compatibleOrders):?><tr><td colspan="6" class="text-center text-muted py-3">No existen ordenes compatibles con este checklist.</td></tr><?php endif;?></tbody></table></div>
  </div>
  <?php if(tiene_permiso('mantenimiento_ti','editar')&&(int)$selected['ejecuciones']===0):?><form method="post" action="guardar_checklist_plantilla.php" class="p-3 border-bottom bg-light"><?=csrf_input()?><input type="hidden" name="accion" value="actualizar_plantilla"><input type="hidden" name="plantilla_id" value="<?=$selectedId?>"><div class="row"><div class="col-md-4 form-group mb-md-0"><label>Nombre</label><input name="nombre" class="form-control" value="<?=mtpchk_h($selected['nombre'])?>" required></div><div class="col-md-4 form-group mb-md-0"><label>Plan</label><select name="plan_id" class="form-control"><option value="">General</option><?php foreach($plans as $plan):?><option value="<?=(int)$plan['id']?>" <?=(int)$selected['plan_id']===(int)$plan['id']?'selected':''?>><?=mtpchk_h($plan['codigo'].' - '.$plan['nombre'])?></option><?php endforeach;?></select></div><div class="col-md-4 form-group mb-0"><label>Descripcion</label><input name="descripcion" class="form-control" value="<?=mtpchk_h($selected['descripcion'])?>"></div></div><button class="btn btn-sm btn-info mt-2">Actualizar datos</button></form><?php endif;?>
  <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Orden</th><th>Componente o punto de revision</th><th>Evaluacion</th><th>Obligatorio</th><th>Evidencia al fallar</th><th>Estado</th><th></th></tr></thead><tbody>
  <?php foreach($items as $item):?><tr><td><?=(int)$item['orden']?></td><td><?=mtpchk_h($item['descripcion'])?></td><td><span class="badge badge-light border chk-type"><?=mtpchk_h($item['tipo_respuesta'])?></span></td><td><?=$item['obligatorio']?'SI':'NO'?></td><td><?=$item['requiere_evidencia_si_falla']?'SI':'NO'?></td><td><?=$item['activo']?'ACTIVA':'INACTIVA'?></td><td class="text-nowrap"><?php if(tiene_permiso('mantenimiento_ti','editar')&&(int)$selected['ejecuciones']===0):?><button type="button" class="btn btn-sm btn-outline-info btnEditarPregunta" data-id="<?=(int)$item['id']?>" data-description="<?=mtpchk_h($item['descripcion'])?>" data-type="<?=mtpchk_h($item['tipo_respuesta'])?>" data-order="<?=(int)$item['orden']?>" data-required="<?=(int)$item['obligatorio']?>" data-evidence="<?=(int)$item['requiere_evidencia_si_falla']?>" data-toggle="modal" data-target="#modalEditarPregunta"><i class="fas fa-pen"></i></button> <form class="d-inline" method="post" action="guardar_checklist_plantilla.php"><?=csrf_input()?><input type="hidden" name="accion" value="toggle_item"><input type="hidden" name="plantilla_id" value="<?=$selectedId?>"><input type="hidden" name="item_id" value="<?=(int)$item['id']?>"><button class="btn btn-sm btn-outline-secondary"><i class="fas fa-power-off"></i></button></form><?php endif;?></td></tr><?php endforeach;?>
  <?php if(!$items):?><tr><td colspan="7" class="text-center text-muted py-4">Agregue los componentes o puntos que se revisaran en cada mantenimiento.</td></tr><?php endif;?></tbody></table></div>
  <?php if(tiene_permiso('mantenimiento_ti','editar')):?><form method="post" action="guardar_checklist_plantilla.php"><div class="card-body bg-light"><?=csrf_input()?><input type="hidden" name="accion" value="agregar_item"><input type="hidden" name="plantilla_id" value="<?=$selectedId?>"><div class="row"><div class="col-md-5 form-group"><label>Componente o punto de revision *</label><input name="descripcion" class="form-control" maxlength="500" placeholder="Ej. Disco duro en buen estado" required></div><div class="col-md-3 form-group"><label>Forma de evaluar *</label><select name="tipo_respuesta" class="form-control"><option>SI_NO_NA</option><option selected>CUMPLE_NO_CUMPLE_NA</option><option>NUMERICO</option><option>TEXTO</option></select></div><div class="col-md-1 form-group"><label>Orden</label><input type="number" name="orden" class="form-control" min="0" value="<?=count($items)+1?>"></div><div class="col-md-3 pt-4"><label class="mr-3"><input type="checkbox" name="obligatorio" checked> Obligatorio</label><label><input type="checkbox" name="requiere_evidencia"> Evidencia si falla</label></div></div><?php if((int)$selected['ejecuciones']>0):?><div class="alert alert-info py-2 small"><i class="fas fa-info-circle mr-1"></i>El nuevo punto se agregará también a las ejecuciones pendientes o en proceso. Los checklists completados no se modificarán.</div><?php endif;?><button class="btn btn-success"><i class="fas fa-plus mr-1"></i>Agregar componente</button></div></form><?php endif;?>
 </div>
 <?php endif;?>
<?php endif;?>
</div></section>
<?php if($selectedId>0&&isset($selected)&&$selected&&tiene_permiso('mantenimiento_ti','editar')&&(int)$selected['ejecuciones']===0):?><div class="modal fade" id="modalEditarPregunta" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="post" action="guardar_checklist_plantilla.php"><?=csrf_input()?><input type="hidden" name="accion" value="actualizar_item"><input type="hidden" name="plantilla_id" value="<?=$selectedId?>"><input type="hidden" name="item_id" id="editItemId"><div class="modal-header bg-info"><h5 class="modal-title">Editar pregunta</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><div class="form-group"><label>Pregunta</label><input name="descripcion" id="editItemDescription" class="form-control" required></div><div class="row"><div class="col-md-5 form-group"><label>Tipo</label><select name="tipo_respuesta" id="editItemType" class="form-control"><option>SI_NO_NA</option><option>CUMPLE_NO_CUMPLE_NA</option><option>NUMERICO</option><option>TEXTO</option></select></div><div class="col-md-2 form-group"><label>Orden</label><input type="number" name="orden" id="editItemOrder" class="form-control"></div><div class="col-md-5 pt-4"><label class="mr-3"><input type="checkbox" name="obligatorio" id="editItemRequired"> Obligatoria</label><label><input type="checkbox" name="requiere_evidencia" id="editItemEvidence"> Evidencia al fallar</label></div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-info">Guardar cambios</button></div></form></div></div></div><?php endif;?>
<?php $inlineJs=<<<'JS'
$('#buscarPlantilla').on('input',function(){var q=this.value.toLowerCase();$('#tblPlantillas tbody tr').each(function(){this.style.display=this.innerText.toLowerCase().includes(q)?'':'none'})});
$('.btnEditarPregunta').on('click',function(){$('#editItemId').val(this.dataset.id);$('#editItemDescription').val(this.dataset.description);$('#editItemType').val(this.dataset.type);$('#editItemOrder').val(this.dataset.order);$('#editItemRequired').prop('checked',this.dataset.required==='1');$('#editItemEvidence').prop('checked',this.dataset.evidence==='1')});
JS;include_once(__DIR__.'/../../layouts/footer.php');?>
