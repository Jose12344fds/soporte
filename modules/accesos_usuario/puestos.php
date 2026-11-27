<?php
$page_title = 'Matriz de accesos por puesto';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones.php');
require_permiso('accesos_usuario', 'ver');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = max(0, (int)($_POST['id'] ?? 0));
    require_permiso('accesos_usuario', $id > 0 ? 'editar' : 'crear');
    csrf_validate_or_fail();
    if (($_POST['action'] ?? '') === 'deactivate') {
        if ($id <= 0) {
            $session->msg('d', 'El puesto indicado no es valido.');
            redirect('puestos.php', false);
        }
        $db->query("UPDATE `f08_puestos` SET `activo`=0,`updated_at`=NOW() WHERE `id`={$id} LIMIT 1");
        $session->msg('s', 'El puesto fue retirado del catalogo activo. Sus fichas historicas se conservaron.');
        redirect('puestos.php', false);
    }
    if (($_POST['action'] ?? '') === 'delete') {
        if ($id <= 0) {
            $session->msg('d', 'El puesto indicado no es valido.');
            redirect('puestos.php', false);
        }
        $positionToDelete = $db->fetch_assoc($db->query("SELECT `id`,`nombre`,`nombre_clave` FROM `f08_puestos` WHERE `id`={$id} LIMIT 1"));
        if (!$positionToDelete) {
            $session->msg('d', 'El puesto ya no existe.');
            redirect('puestos.php', false);
        }
        $operator = current_user();
        $operatorId = (int)($operator['id'] ?? 0);
        $db->query('START TRANSACTION');
        $db->query("INSERT INTO `f08_puestos_excluidos` (`nombre_clave`,`nombre`,`eliminado_por`,`eliminado_at`)
          VALUES (" . f08_q($positionToDelete['nombre_clave']) . ',' . f08_q($positionToDelete['nombre']) . ",{$operatorId},NOW())
          ON DUPLICATE KEY UPDATE `nombre`=VALUES(`nombre`),`eliminado_por`=VALUES(`eliminado_por`),`eliminado_at`=NOW()");
        $db->query("UPDATE `usuariosf08` SET `puesto_id`=NULL WHERE `puesto_id`={$id}");
        $db->query("DELETE FROM `f08_puestos` WHERE `id`={$id} LIMIT 1");
        $db->query('COMMIT');
        $session->msg('s', 'El puesto fue eliminado de la matriz. Las fichas F08 relacionadas conservaron todos sus datos.');
        redirect('puestos.php', false);
    }
    $name = f08_position_name(mb_substr(trim((string)($_POST['nombre'] ?? '')), 0, 180));
    $key = f08_position_key($name);
    if ($name === '' || $key === '') {
        $session->msg('d', 'Indique el nombre del puesto de trabajo.');
        redirect('puestos.php' . ($id ? '?id=' . $id : '?new=1'), false);
    }
    $duplicate = $db->fetch_assoc($db->query("SELECT `id` FROM `f08_puestos` WHERE `nombre_clave`=" . f08_q($key) . " AND `id`<>{$id} LIMIT 1"));
    if ($duplicate) {
        $session->msg('d', 'Ya existe un puesto equivalente. Los numeros finales se agrupan en una sola plantilla.');
        redirect('puestos.php?id=' . (int)$duplicate['id'], false);
    }
    $allowedLevel = ['SIN_ACCESO','Nivel_I','Nivel_II','Nivel_III'];
    $yesNo = static function($value): string { return strtoupper((string)$value) === 'SI' ? 'SI' : 'NO'; };
    $level = static function($value) use ($allowedLevel): string { return in_array($value, $allowedLevel, true) ? $value : 'SIN_ACCESO'; };
    $operator = current_user();
    $values = [
        'nombre' => $name,
        'nombre_clave' => $key,
        'area' => mb_substr(trim((string)($_POST['area'] ?? '')), 0, 100),
        'descripcion' => mb_substr(trim((string)($_POST['descripcion'] ?? '')), 0, 500),
        'CarpetasCompartidasM' => mb_substr(trim((string)($_POST['CarpetasCompartidasM'] ?? '')), 0, 10000),
        'CarpetasCompartidasV' => mb_substr(trim((string)($_POST['CarpetasCompartidasV'] ?? '')), 0, 10000),
        'corrcelu_f8' => $yesNo($_POST['corrcelu_f8'] ?? 'NO'),
        'ausb_f8' => $yesNo($_POST['ausb_f8'] ?? 'NO'),
        'ninavega_f8' => $level((string)($_POST['ninavega_f8'] ?? '')),
        'nivcorr_f8' => $level((string)($_POST['nivcorr_f8'] ?? '')),
        'corrpuen_f8' => mb_substr(trim((string)($_POST['corrpuen_f8'] ?? '')), 0, 100),
        'softwarebasi_f8' => $yesNo($_POST['softwarebasi_f8'] ?? 'NO'),
        'sap_f8' => $yesNo($_POST['sap_f8'] ?? 'NO'),
        'saplicen_f8' => mb_substr(trim((string)($_POST['saplicen_f8'] ?? '')), 0, 50),
        'requerimient_f8' => $yesNo($_POST['requerimient_f8'] ?? 'NO'),
        'requeutili_f8' => mb_substr(trim((string)($_POST['requeutili_f8'] ?? '')), 0, 50),
        'orbis_f8' => $yesNo($_POST['orbis_f8'] ?? 'NO'),
        'utilidadorb_f8' => mb_substr(trim((string)($_POST['utilidadorb_f8'] ?? '')), 0, 50),
        'apptam_f8' => $yesNo($_POST['apptam_f8'] ?? 'NO'),
        'utilidapptam_f8' => mb_substr(trim((string)($_POST['utilidapptam_f8'] ?? '')), 0, 50),
        'configurado' => '1',
        'activo' => (int)($_POST['activo'] ?? 1) === 1 ? '1' : '0',
        'updated_by' => (string)(int)($operator['id'] ?? 0),
    ];
    $sets = [];
    foreach ($values as $column => $value) $sets[] = "`{$column}`=" . (in_array($column, ['configurado','activo','updated_by'], true) ? $value : f08_q($value));
    if ($id > 0) {
        $db->query("UPDATE `f08_puestos` SET " . implode(',', $sets) . ",`updated_at`=NOW() WHERE `id`={$id} LIMIT 1");
    } else {
        $columns = array_keys($values);
        $sqlValues = [];
        foreach ($values as $column => $value) $sqlValues[] = in_array($column, ['configurado','activo','updated_by'], true) ? $value : f08_q($value);
        $columns[] = 'created_by'; $sqlValues[] = (string)(int)($operator['id'] ?? 0);
        $db->query("INSERT INTO `f08_puestos` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $sqlValues) . ")");
        $id = (int)$db->insert_id();
    }
    $session->msg('s', 'Plantilla del puesto guardada correctamente.');
    redirect('puestos.php?id=' . $id, false);
}

$positions = f08_get_positions(false);
$selectedId = max(0, (int)($_GET['id'] ?? 0));
$isNew = isset($_GET['new']);
$selected = null;
foreach ($positions as $position) if ((int)$position['id'] === $selectedId) $selected = $position;
if (!$selected && !$isNew && $positions) $selected = $positions[0];
$catalog = f08_samba_catalog();
$sambaLabels = f08_samba_labels();
$blank = array_fill_keys(array_merge(['id','nombre','area','descripcion','activo','configurado'], f08_template_fields()), '');
$blank['activo'] = 1; $blank['softwarebasi_f8'] = 'SI';
$selected = $selected ?: $blank;
$writeCustom = f08_samba_custom_value((string)$selected['CarpetasCompartidasM'], $catalog);
$readCustom = f08_samba_custom_value((string)$selected['CarpetasCompartidasV'], $catalog);
$editorFolders = [];
foreach ($catalog as $group => $folders) {
    foreach ($folders as $folder) {
        $permission = f08_samba_has_folder((string)$selected['CarpetasCompartidasM'], $folder)
            ? 'write' : (f08_samba_has_folder((string)$selected['CarpetasCompartidasV'], $folder) ? 'read' : 'none');
        $editorFolders[] = ['code' => $folder, 'group' => $group, 'permission' => $permission];
    }
}
usort($editorFolders, static function(array $left, array $right): int {
    $priority = ['write' => 0, 'read' => 1, 'none' => 2];
    $permissionOrder = $priority[$left['permission']] <=> $priority[$right['permission']];
    return $permissionOrder !== 0 ? $permissionOrder : strnatcasecmp($left['code'], $right['code']);
});
$assignedEditorFolders = array_values(array_filter($editorFolders, static function(array $folder): bool {
    return $folder['permission'] !== 'none';
}));
$folderCount = static function(string $value): int { return count(array_filter(preg_split('/\s*[,;\n]+\s*/', trim($value)) ?: [])); };
$extraCss = ['https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap'];
$extraJs = ['https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js'];
$exportRows = [];
foreach ($positions as $position) {
    $row = [
        'Puesto' => (string)$position['nombre'], 'Area' => (string)$position['area'],
        'Descripcion' => (string)$position['descripcion'], 'Colaboradores' => (int)$position['colaboradores'],
        'Colaboradores activos' => (int)$position['colaboradores_activos'],
        'Colaboradores inactivos' => (int)$position['colaboradores_inactivos'],
        'Estado' => (int)$position['activo'] === 1 ? 'ACTIVO' : 'INACTIVO',
        'Configuracion' => (int)$position['configurado'] === 1 ? 'CONFIGURADA' : 'PENDIENTE',
    ];
    foreach ($catalog as $folders) {
        foreach ($folders as $folder) {
            $row['Samba ' . $folder . ' - ' . ($sambaLabels[$folder] ?? $folder)] = f08_samba_has_folder((string)$position['CarpetasCompartidasM'], $folder)
                ? 'MODIFICAR' : (f08_samba_has_folder((string)$position['CarpetasCompartidasV'], $folder) ? 'LECTURA' : '');
        }
    }
    $row['Otras carpetas - modificar'] = f08_samba_custom_value((string)$position['CarpetasCompartidasM'], $catalog);
    $row['Otras carpetas - lectura'] = f08_samba_custom_value((string)$position['CarpetasCompartidasV'], $catalog);
    $row['Correo en celular'] = f08_yes($position['corrcelu_f8']) ? 'SI' : 'NO';
    $row['Acceso USB'] = f08_yes($position['ausb_f8']) ? 'SI' : 'NO';
    $row['Nivel navegacion'] = (string)$position['ninavega_f8'];
    $row['Nivel correo'] = (string)$position['nivcorr_f8'];
    $row['Correo puente'] = (string)$position['corrpuen_f8'];
    $row['Software basico'] = f08_yes($position['softwarebasi_f8']) ? 'SI' : 'NO';
    foreach ([['SAP','sap_f8','saplicen_f8'],['RCO','requerimient_f8','requeutili_f8'],['Orbis','orbis_f8','utilidadorb_f8'],['App TAMA','apptam_f8','utilidapptam_f8']] as $app) {
        $row[$app[0] . ' - acceso'] = f08_yes($position[$app[1]]) ? 'SI' : 'NO';
        $row[$app[0] . ' - perfil'] = (string)$position[$app[2]];
    }
    $exportRows[] = $row;
}
$exportRowsJson = json_encode($exportRows, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$positionUserCounts = [];
foreach ($positions as $position) {
    $positionUserCounts[(int)$position['id']] = [
        'total' => (int)$position['colaboradores'],
        'active' => (int)$position['colaboradores_activos'],
        'inactive' => (int)$position['colaboradores_inactivos'],
    ];
}
$positionUserCountsJson = json_encode($positionUserCounts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
:root{--pm-navy:#123746;--pm-teal:#087f8c;--pm-green:#25a77d;--pm-gold:#efa83b;--pm-line:#dbe7e9}.pm-page{font-family:'Manrope',sans-serif;color:#173b49}.pm-hero{border:0;border-radius:18px;background:linear-gradient(120deg,#102f43,#087f8c 68%,#29b389);color:#fff;box-shadow:0 15px 35px rgba(18,55,70,.17)}.pm-hero .card-body{padding:21px 25px}.pm-kicker{font-size:9px;letter-spacing:.15em;text-transform:uppercase;font-weight:800;color:#c6f2e8}.pm-hero h1{font-size:23px;font-weight:800;margin:3px 0}.pm-hero p{font-size:11px;color:#d9eeee;margin:0}.pm-layout{display:grid;grid-template-columns:340px minmax(0,1fr);gap:15px;align-items:start}.pm-panel{border:0;border-radius:15px;box-shadow:0 8px 25px rgba(18,55,70,.08);overflow:hidden}.pm-panel .card-header{padding:14px 16px;background:#fff;border-bottom:1px solid var(--pm-line)}.pm-panel h3{font-size:13px;font-weight:800;margin:0}.pm-list{max-height:720px;overflow:auto}.pm-position{display:flex;align-items:center;gap:8px;padding:11px 12px;border-bottom:1px solid #e8eff0;background:#fff}.pm-position.active{background:#eef9f7}.pm-position-main{min-width:0;flex:1;color:#244a57}.pm-position-main:hover{color:#087f8c;text-decoration:none}.pm-position strong{display:block;font-size:11px}.pm-position small{display:block;color:#7c9199;font-size:9px}.pm-position-actions{display:flex;gap:4px}.pm-position-actions .btn{width:29px;height:29px;padding:4px;border-radius:7px}.pm-meta{display:flex;gap:5px;margin-top:6px}.pm-pill{padding:3px 6px;border-radius:9px;background:#e8eff1;font-size:8px;font-weight:800}.pm-pill.ready{background:#dff5ea;color:#177451}.pm-editor .card-body{padding:18px}.pm-section{padding:15px;border:1px solid var(--pm-line);border-radius:12px;background:#fbfdfd;margin-bottom:13px}.pm-section-title{display:flex;align-items:center;gap:9px;margin-bottom:13px;color:#0b7480;font-size:11px;font-weight:800;text-transform:uppercase}.pm-section-title i{width:29px;height:29px;display:grid;place-items:center;border-radius:8px;background:#e2f3f2}.pm-page label{font-size:9px;text-transform:uppercase;font-weight:800;color:#58717a}.pm-page .form-control{border-color:#cadadd;border-radius:8px;font-size:11px;min-height:37px}.pm-folder-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:7px}.pm-folder{display:grid;grid-template-columns:1fr 105px;gap:6px;align-items:center;padding:7px 9px;border:1px solid #dce7e9;border-radius:8px;background:#fff}.pm-folder strong{font-size:10px}.pm-folder select{height:29px;min-height:29px;padding:2px 5px;font-size:9px}.pm-controls,.pm-apps{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.pm-control,.pm-app{padding:10px;border:1px solid #dce7e9;border-radius:9px;background:#fff}.pm-app{border-top:3px solid #1e9f84}.pm-app strong{display:block;font-size:10px;margin-bottom:8px}.pm-save{position:sticky;bottom:0;display:flex;justify-content:space-between;align-items:center;padding:13px 17px;background:#fff;border-top:1px solid var(--pm-line);box-shadow:0 -7px 18px rgba(18,55,70,.07)}.pm-empty{padding:30px 18px;text-align:center;color:#789098}.pm-tip{padding:10px 12px;border-radius:9px;background:#fff6df;color:#79591e;font-size:9px}.pm-search{position:relative}.pm-search i{position:absolute;left:11px;top:11px;color:#799098}.pm-search input{padding-left:31px}@media(max-width:1100px){.pm-layout{grid-template-columns:280px 1fr}.pm-folder-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:850px){.pm-layout{grid-template-columns:1fr}.pm-list{max-height:300px}.pm-controls,.pm-apps{grid-template-columns:repeat(2,1fr)}}@media(max-width:575px){.pm-folder-grid,.pm-controls,.pm-apps{grid-template-columns:1fr}}
.pm-folder{grid-template-columns:minmax(0,1fr) 105px;padding:9px;gap:8px}.pm-folder strong{display:block;color:#173b49}.pm-folder small{display:block;margin-top:2px;line-height:1.3;color:#789098;font-size:8px}
.pm-layout{grid-template-columns:390px minmax(0,1fr)}.pm-position{padding:12px}.pm-position.active{border-left:4px solid #16a085}.pm-meta{flex-wrap:wrap}.pm-assigned{display:flex;gap:4px;flex-wrap:wrap;margin-top:7px}.pm-assigned-label{width:100%;font-size:8px;font-weight:800;color:#607b84;text-transform:uppercase}.pm-folder-chip{padding:3px 6px;border-radius:8px;background:#e4f3ff;color:#23699c;font-size:8px;font-weight:800}.pm-folder-chip.write{background:#dff5ea;color:#177451}.pm-folder-chip.more{background:#f0edf9;color:#66528d}.pm-no-folders{font-size:8px;color:#a17927}.pm-folder{transition:.15s}.pm-folder[data-permission="read"]{border-color:#93bee4;background:#f0f7ff;box-shadow:inset 3px 0 #3989d0}.pm-folder[data-permission="write"]{border-color:#8fd1ba;background:#eefaf5;box-shadow:inset 3px 0 #1d966b}.pm-folder[data-permission="read"] select{border-color:#78add9;color:#226da8;font-weight:800}.pm-folder[data-permission="write"] select{border-color:#72c3a5;color:#167653;font-weight:800}@media(max-width:1100px){.pm-layout{grid-template-columns:330px 1fr}}@media(max-width:850px){.pm-layout{grid-template-columns:1fr}}
.pm-current-summary{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:11px 13px;margin-bottom:12px;border:1px solid #b7dfd1;border-radius:10px;background:#effaf6}.pm-current-summary>strong{font-size:10px;color:#166b52}.pm-current-summary .pm-folder-chip{font-size:9px}.pm-current-summary-empty{color:#8b742e;font-size:9px}
.pm-position.active{position:relative;background:#e8f8f4;border-left:4px solid #0c9a79;box-shadow:inset 0 0 0 1px rgba(12,154,121,.08)}.pm-selected-mark{display:inline-flex;align-items:center;gap:3px;padding:3px 6px;border-radius:9px;background:#087f8c;color:#fff;font-size:8px;font-weight:800}.pm-position:not(.active) .pm-selected-mark{display:none}.pm-search-clear{position:absolute;right:7px;top:6px;width:26px;height:26px;padding:0;border:0;border-radius:7px;background:transparent;color:#81959c}.pm-search-clear:hover{background:#e8f1f2;color:#087f8c}.pm-search input{padding-right:35px}
</style>
<section class="content pm-page"><div class="container-fluid pt-3 pb-4">
 <?=display_msg($msg)?>
 <div class="card pm-hero mb-3"><div class="card-body d-md-flex justify-content-between align-items-center"><div><div class="pm-kicker">Gobierno de identidades</div><h1><i class="fas fa-sitemap mr-2"></i>Matriz de accesos por puesto</h1><p>Defina una sola vez los accesos esperados para cada funci&oacute;n y apl&iacute;quelos luego a las fichas F08.</p></div><div class="mt-3 mt-md-0"><a href="index.php" class="btn btn-outline-light mr-1"><i class="fas fa-arrow-left mr-1"></i>Fichas F08</a><button type="button" id="pmExportExcel" class="btn btn-light text-success mr-1"><i class="fas fa-file-excel mr-1"></i>Exportar matriz</button><?php if(tiene_permiso('accesos_usuario','crear')):?><a href="puestos.php?new=1" class="btn btn-warning font-weight-bold"><i class="fas fa-plus mr-1"></i>Nuevo puesto</a><?php endif;?></div></div></div>
 <div class="pm-layout">
  <div class="card pm-panel"><div class="card-header"><h3><i class="fas fa-briefcase mr-2 text-info"></i>Cat&aacute;logo de puestos</h3><div class="pm-search mt-2"><i class="fas fa-search"></i><input id="pmSearch" class="form-control" placeholder="Buscar puesto o &aacute;rea..." autocomplete="off"><button type="button" id="pmSearchClear" class="pm-search-clear" title="Limpiar b&uacute;squeda"><i class="fas fa-times"></i></button></div></div><div class="pm-list" id="pmList">
   <?php foreach($positions as $position):$active=(int)$position['activo']===1;$isSelected=(int)$selected['id']===(int)$position['id']&&!$isNew;$assigned=[];foreach($catalog as $folders){foreach($folders as $folder){if(f08_samba_has_folder((string)$position['CarpetasCompartidasM'],$folder))$assigned[]=['code'=>$folder,'mode'=>'write'];elseif(f08_samba_has_folder((string)$position['CarpetasCompartidasV'],$folder))$assigned[]=['code'=>$folder,'mode'=>'read'];}}?><div class="pm-position <?=$isSelected?'active':''?>" data-position-id="<?=(int)$position['id']?>" data-search="<?=f08_h(mb_strtolower($position['nombre'].' '.$position['area']))?>"><a class="pm-position-main pm-select" href="puestos.php?id=<?=(int)$position['id']?>"><strong><?=f08_h($position['nombre'])?></strong><small><?=f08_h($position['area']?:'Sin area definida')?></small><div class="pm-meta"><span class="pm-selected-mark"><i class="fas fa-check"></i>Seleccionado</span><span class="pm-pill <?=((int)$position['configurado']===1)?'ready':''?>"><?=((int)$position['configurado']===1)?'Plantilla lista':'Por configurar'?></span><span class="pm-pill"><?=(int)$position['colaboradores']?> colaborador(es)</span><?php if(!$active):?><span class="pm-pill">Inactivo</span><?php endif;?></div><div class="pm-assigned"><span class="pm-assigned-label"><i class="fas fa-folder-open mr-1"></i>Carpetas asignadas (<?=count($assigned)?>)</span><?php if($assigned):foreach(array_slice($assigned,0,4) as $folderInfo):?><span class="pm-folder-chip <?=$folderInfo['mode']?>" title="<?=f08_h($sambaLabels[$folderInfo['code']]??'')?>"><?=f08_h($folderInfo['code'])?> &middot; <?=$folderInfo['mode']==='write'?'Modificar':'Lectura'?></span><?php endforeach;if(count($assigned)>4):?><span class="pm-folder-chip more">+<?=count($assigned)-4?> m&aacute;s</span><?php endif;else:?><span class="pm-no-folders">Sin carpetas del cat&aacute;logo asignadas</span><?php endif;?></div></a><div class="pm-position-actions"><a href="puestos.php?id=<?=(int)$position['id']?>" class="btn btn-outline-warning pmEdit" title="Editar puesto"><i class="fas fa-pen"></i></a><?php if(tiene_permiso('accesos_usuario','editar')):?><?php if($active):?><button type="button" class="btn btn-outline-secondary pm-deactivate" data-id="<?=(int)$position['id']?>" data-name="<?=f08_h($position['nombre'])?>" title="Desactivar temporalmente"><i class="fas fa-pause"></i></button><?php endif;?><button type="button" class="btn btn-outline-danger pm-delete" data-id="<?=(int)$position['id']?>" data-name="<?=f08_h($position['nombre'])?>" data-linked="<?=(int)$position['colaboradores']?>" title="Eliminar definitivamente"><i class="fas fa-trash"></i></button><?php endif;?></div></div><?php endforeach;?>
   <?php if(!$positions):?><div class="pm-empty">No hay cargos registrados. Puede crear el primer puesto manualmente.</div><?php endif;?>
  </div></div>
  <form method="post" class="card pm-panel pm-editor" id="pmForm"><?=csrf_input()?><input type="hidden" name="id" value="<?=(int)$selected['id']?>"><input type="hidden" name="CarpetasCompartidasM" id="pmWrite"><input type="hidden" name="CarpetasCompartidasV" id="pmRead">
   <div class="card-header d-flex justify-content-between align-items-center"><div><h3><?=((int)$selected['id']>0)?'Editar plantilla':'Crear puesto de trabajo'?></h3><small class="text-muted">No almacena contrase&ntilde;as ni nombres de cuenta personales.</small></div><?php if((int)$selected['configurado']===1):?><span class="badge badge-success p-2">Configurada</span><?php endif;?></div>
   <div class="card-body">
    <div class="pm-section"><div class="pm-section-title"><i class="fas fa-id-badge"></i>Identidad del puesto</div><div class="row"><div class="col-lg-7 form-group"><label>Nombre del puesto *</label><input name="nombre" class="form-control" maxlength="180" required value="<?=f08_h($selected['nombre'])?>" placeholder="Ej.: Asistente de Tesorer&iacute;a, Facturaci&oacute;n y Cobranza"></div><div class="col-lg-5 form-group"><label>&Aacute;rea</label><input name="area" class="form-control" maxlength="100" value="<?=f08_h($selected['area'])?>"></div><div class="col-md-9 form-group mb-md-0"><label>Descripci&oacute;n / alcance</label><input name="descripcion" class="form-control" maxlength="500" value="<?=f08_h($selected['descripcion'])?>" placeholder="Responsabilidad general o criterio de asignaci&oacute;n"></div><div class="col-md-3 form-group mb-0"><label>Estado</label><select name="activo" class="form-control"><option value="1" <?=(int)$selected['activo']===1?'selected':''?>>Activo</option><option value="0" <?=(int)$selected['activo']===0?'selected':''?>>Inactivo</option></select></div></div></div>
    <div class="pm-section"><div class="pm-section-title"><i class="fas fa-folder-open"></i>Carpetas compartidas Samba</div><div class="pm-tip mb-3"><i class="fas fa-info-circle mr-1"></i>Las carpetas asignadas aparecen primero. Seleccione <b>Lectura</b> o <b>Modificar</b>; una carpeta solo puede tener un nivel por puesto.</div><div class="pm-current-summary"><strong><i class="fas fa-check-circle mr-1"></i>Asignadas actualmente (<?=count($assignedEditorFolders)?>)</strong><?php if($assignedEditorFolders):foreach($assignedEditorFolders as $folderInfo):?><span class="pm-folder-chip <?=$folderInfo['permission']?>"><?=f08_h($folderInfo['code'])?> - <?=f08_h($sambaLabels[$folderInfo['code']]??'')?> (<?=$folderInfo['permission']==='write'?'Modificar':'Lectura'?>)</span><?php endforeach;else:?><span class="pm-current-summary-empty">Este puesto no tiene carpetas del cat&aacute;logo seleccionadas.</span><?php endif;?></div><div class="pm-folder-grid">
     <?php foreach($editorFolders as $folderInfo):$folder=$folderInfo['code'];$group=$folderInfo['group'];$permission=$folderInfo['permission'];?><div class="pm-folder" data-code="<?=f08_h($folder)?>" data-permission="<?=$permission?>"><div><strong><i class="fas fa-folder text-warning mr-1"></i><?=f08_h($folder)?></strong><small><?=f08_h($sambaLabels[$folder]??'Carpeta Samba')?></small></div><select class="form-control pm-permission"><option value="none" <?=$permission==='none'?'selected':''?>>Sin acceso</option><option value="read" <?=$permission==='read'?'selected':''?>>Solo lectura</option><option value="write" <?=$permission==='write'?'selected':''?>>Modificar</option></select></div><?php endforeach;?>
    </div><div class="row mt-3"><div class="col-md-6 form-group mb-md-0"><label>Otras rutas con modificaci&oacute;n</label><input id="pmCustomWrite" class="form-control" value="<?=f08_h($writeCustom)?>" placeholder="Separar por comas"></div><div class="col-md-6 form-group mb-0"><label>Otras rutas de lectura</label><input id="pmCustomRead" class="form-control" value="<?=f08_h($readCustom)?>" placeholder="Separar por comas"></div></div></div>
    <div class="pm-section"><div class="pm-section-title"><i class="fas fa-shield-alt"></i>Controles generales</div><div class="pm-controls">
     <?php $binary=[['corrcelu_f8','Correo en celular'],['ausb_f8','Acceso USB'],['softwarebasi_f8','Software b&aacute;sico']];foreach($binary as $item):?><div class="pm-control"><label><?=$item[1]?></label><select name="<?=$item[0]?>" class="form-control"><option value="NO" <?=!f08_yes($selected[$item[0]])?'selected':''?>>No</option><option value="SI" <?=f08_yes($selected[$item[0]])?'selected':''?>>S&iacute;</option></select></div><?php endforeach;?><div class="pm-control"><label>Nivel de navegaci&oacute;n</label><select name="ninavega_f8" class="form-control"><?php foreach(['SIN_ACCESO','Nivel_I','Nivel_II','Nivel_III'] as $option):?><option value="<?=$option?>" <?=$selected['ninavega_f8']===$option?'selected':''?>><?=str_replace('_',' ',$option)?></option><?php endforeach;?></select></div><div class="pm-control"><label>Nivel de correo</label><select name="nivcorr_f8" class="form-control"><?php foreach(['SIN_ACCESO','Nivel_I','Nivel_II','Nivel_III'] as $option):?><option value="<?=$option?>" <?=$selected['nivcorr_f8']===$option?'selected':''?>><?=str_replace('_',' ',$option)?></option><?php endforeach;?></select></div><div class="pm-control"><label>Correo puente</label><input name="corrpuen_f8" class="form-control" maxlength="100" value="<?=f08_h($selected['corrpuen_f8'])?>"></div>
    </div></div>
    <div class="pm-section mb-0"><div class="pm-section-title"><i class="fas fa-th-large"></i>Aplicaciones y perfiles esperados</div><div class="pm-apps">
     <?php $apps=[['SAP Business One','sap_f8','saplicen_f8'],['Requerimientos RCO','requerimient_f8','requeutili_f8'],['Orbis','orbis_f8','utilidadorb_f8'],['App TAMA','apptam_f8','utilidapptam_f8']];foreach($apps as $app):?><div class="pm-app"><strong><?=$app[0]?></strong><div class="row"><div class="col-4"><label>Acceso</label><select name="<?=$app[1]?>" class="form-control"><option value="NO" <?=!f08_yes($selected[$app[1]])?'selected':''?>>No</option><option value="SI" <?=f08_yes($selected[$app[1]])?'selected':''?>>S&iacute;</option></select></div><div class="col-8"><label>Perfil / licencia</label><input name="<?=$app[2]?>" class="form-control" maxlength="50" value="<?=f08_h($selected[$app[2]])?>" placeholder="Perfil requerido"></div></div></div><?php endforeach;?>
    </div></div>
   </div>
   <div class="pm-save"><span class="small text-muted"><i class="fas fa-history mr-1"></i>La ficha del usuario conservar&aacute; una copia de esta configuraci&oacute;n.</span><?php if(((int)$selected['id']>0&&tiene_permiso('accesos_usuario','editar'))||((int)$selected['id']===0&&tiene_permiso('accesos_usuario','crear'))):?><button class="btn btn-warning font-weight-bold"><i class="fas fa-save mr-1"></i>Guardar plantilla</button><?php endif;?></div>
  </form>
 </div>
</div></section>
<?php
$inlineJs = 'var pmExportRows=' . $exportRowsJson . ';' . PHP_EOL;
$inlineJs .= 'var pmPositionUserCounts=' . $positionUserCountsJson . ';' . PHP_EOL;
$inlineJs .= <<<'JS'
$(function(){
 var stateKey='f08-position-matrix-state';
 function readState(){try{return JSON.parse(sessionStorage.getItem(stateKey)||'{}')}catch(e){return{}}}
 function saveState(extra){var state=readState();state.search=$('#pmSearch').val()||'';state.listScroll=$('#pmList').scrollTop()||0;state.pageScroll=$(window).scrollTop()||0;if(extra)Object.keys(extra).forEach(function(key){state[key]=extra[key]});sessionStorage.setItem(stateKey,JSON.stringify(state))}
 function filterPositions(){var q=$.trim($('#pmSearch').val()).toLowerCase();$('.pm-position').each(function(){$(this).toggle(!q||String($(this).data('search')).indexOf(q)!==-1)})}
 function split(value){var seen={};return String(value||'').split(/[,;\n]+/).map(function(v){return v.trim()}).filter(function(v){var key=v.toLowerCase();if(!v||seen[key])return false;seen[key]=true;return true})}
 function refreshAssignedSummary(){var assigned=[];$('.pm-folder').each(function(){var row=$(this),permission=row.find('.pm-permission').val();if(permission!=='none')assigned.push({code:String(row.data('code')),name:row.find('small').text(),permission:permission})});var box=$('.pm-current-summary').empty();box.append($('<strong><i class="fas fa-check-circle mr-1"></i>Asignadas actualmente ('+assigned.length+')</strong>'));if(!assigned.length){box.append($('<span class="pm-current-summary-empty">Este puesto no tiene carpetas del catalogo seleccionadas.</span>'));return}assigned.forEach(function(item){box.append($('<span class="pm-folder-chip"></span>').addClass(item.permission).text(item.code+' - '+item.name+' ('+(item.permission==='write'?'Modificar':'Lectura')+')'))})}
 function syncFolders(){var write=[],read=[];$('.pm-folder').each(function(){var code=String($(this).data('code')),permission=$(this).find('.pm-permission').val();if(permission==='write')write.push(code);if(permission==='read')read.push(code)});write=write.concat(split($('#pmCustomWrite').val()));var writeKeys={};write.forEach(function(v){writeKeys[v.toLowerCase()]=true});read=read.concat(split($('#pmCustomRead').val())).filter(function(v){return !writeKeys[v.toLowerCase()]});$('#pmWrite').val(write.join(', '));$('#pmRead').val(read.join(', '));refreshAssignedSummary()}
 $('.pm-permission').on('change',function(){$(this).closest('.pm-folder').attr('data-permission',this.value)});$('.pm-permission,#pmCustomWrite,#pmCustomRead').on('change input',syncFolders);syncFolders();
 var savedState=readState();if(savedState.search)$('#pmSearch').val(savedState.search);filterPositions();$('#pmSearch').on('input',function(){filterPositions();saveState()});$('#pmSearchClear').on('click',function(){$('#pmSearch').val('');filterPositions();saveState();$('#pmSearch').trigger('focus')});
 $(document).on('click','.pm-select,.pmEdit',function(){var id=Number($(this).closest('.pm-position').data('position-id')||0);saveState({selectedId:id})});
 var activePosition=$('.pm-position.active');setTimeout(function(){if(savedState.listScroll!=null)$('#pmList').scrollTop(Number(savedState.listScroll)||0);if(activePosition.length&&!activePosition.is(':visible')){$('#pmSearch').val('');filterPositions()}if(activePosition.length){var list=$('#pmList'),top=activePosition.position().top,bottom=top+activePosition.outerHeight();if(top<0||bottom>list.innerHeight())list.scrollTop(list.scrollTop()+top-list.innerHeight()/3)}if(savedState.restorePage)$(window).scrollTop(Number(savedState.pageScroll)||0);saveState({restorePage:false})},40);
 $('#pmExportExcel').on('click',function(){if(typeof XLSX==='undefined'){Swal.fire('Exportacion no disponible','No se pudo cargar el componente de Excel.','error');return}if(!pmExportRows.length){Swal.fire('Sin datos','No existen puestos para exportar.','info');return}var ws=XLSX.utils.json_to_sheet(pmExportRows),headers=Object.keys(pmExportRows[0]);ws['!autofilter']={ref:XLSX.utils.encode_range({r:0,c:0},{r:pmExportRows.length,c:headers.length-1})};ws['!freeze']={xSplit:2,ySplit:1};ws['!cols']=headers.map(function(header){if(header==='Puesto'||header==='Descripcion')return{wch:38};if(header.indexOf('Samba ')===0)return{wch:13};return{wch:19}});var wb=XLSX.utils.book_new();XLSX.utils.book_append_sheet(wb,ws,'Matriz por puestos');XLSX.writeFile(wb,'Matriz_Accesos_por_Puesto_'+new Date().toISOString().slice(0,10)+'.xlsx')});
 function submitPositionAction(id,action){var form=$('<form method="post"></form>').appendTo('body');form.append($('<input type="hidden" name="id">').val(id));form.append($('<input type="hidden" name="action">').val(action));var csrf=$('#pmForm input[name="_csrf"]').first();if(csrf.length)form.append($('<input type="hidden" name="_csrf">').val(csrf.val()));form.trigger('submit')}
 function safeName(value){return String(value).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]})}
 $(document).on('click','.pm-deactivate',function(){var id=$(this).data('id'),name=$(this).data('name');Swal.fire({title:'Desactivar temporalmente?',html:'<b>'+safeName(name)+'</b><br>Dejara de aparecer en las nuevas fichas, pero podra reactivarlo cuando lo necesite.',icon:'question',showCancelButton:true,confirmButtonText:'Si, desactivar',cancelButtonText:'Cancelar',confirmButtonColor:'#657981'}).then(function(result){if(result.isConfirmed)submitPositionAction(id,'deactivate')})});
 $(document).on('click','.pm-delete',function(){var id=Number($(this).data('id')),name=$(this).data('name'),counts=pmPositionUserCounts[id]||{total:0,active:0,inactive:0};var detail=counts.total?'<div class="alert alert-warning mt-3 mb-0 text-left"><div class="font-weight-bold mb-2">Este cargo coincide con '+counts.total+' colaborador(es):</div><div><i class="fas fa-user-check text-success mr-2"></i><b>'+counts.active+'</b> activo(s)</div><div><i class="fas fa-user-slash text-secondary mr-2"></i><b>'+counts.inactive+'</b> inactivo(s)</div><div class="mt-2 small">Sus fichas F08 conservaran todos sus permisos historicos.</div></div>':'<div class="alert alert-light border mt-3 mb-0">No hay colaboradores asociados con este cargo.</div>';Swal.fire({title:'Eliminar definitivamente?',html:'<b>'+safeName(name)+'</b><br><br>Esta fila desaparecera de la matriz y no se volvera a crear automaticamente.'+detail,icon:'warning',showCancelButton:true,confirmButtonText:'Si, eliminar fila',cancelButtonText:'Cancelar',confirmButtonColor:'#c03946',focusCancel:true}).then(function(result){if(result.isConfirmed)submitPositionAction(id,'delete')})});
 $('#pmForm').on('submit',function(){syncFolders();saveState({selectedId:Number($(this).find('[name=id]').val()||0),restorePage:true});$(this).find('button[type=submit]').prop('disabled',true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...')});
});
JS;
include_once(__DIR__ . '/../../layouts/footer.php');
?>
