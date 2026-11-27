<?php
$page_title = 'Control de Accesos VPN';
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_permiso('vpn', 'ver');

$currentYear = (int)date('Y');
$yearRows = find_by_sql("SELECT DISTINCT YEAR(Fecha) Anio FROM vpn WHERE Fecha IS NOT NULL ORDER BY Anio DESC");
$years = array_values(array_filter(array_map(static function (array $row): int {
    return (int)($row['Anio'] ?? 0);
}, $yearRows)));
if (!in_array($currentYear, $years, true)) array_unshift($years, $currentYear);

include_once __DIR__ . '/../../layouts/header.php';
?>
<style>
.vpn-hero{overflow:hidden;border:0;border-radius:17px;color:#fff;background:linear-gradient(118deg,#143b59,#087f8c 68%,#d79335);box-shadow:0 15px 35px rgba(18,59,76,.17)}
.vpn-kicker{font-size:10px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;opacity:.8}.vpn-hero h1{font-weight:900}.vpn-year-box{display:flex;align-items:flex-end;gap:9px;padding:10px;border:1px solid rgba(255,255,255,.25);border-radius:12px;background:rgba(255,255,255,.12)}
.vpn-year-box label{display:block;margin:0 0 3px;font-size:9px;font-weight:900;text-transform:uppercase}.vpn-year-box select{min-width:110px}.vpn-card{overflow:hidden;border:1px solid #d8e5e7;border-radius:14px;box-shadow:0 8px 24px rgba(18,59,76,.07)}
.vpn-card .card-header{border:0;background:#eff7f7;color:#163b50}.vpn-table thead th{border-color:#31566a!important;background:#163f53;color:#fff;font-size:10px;text-transform:uppercase;vertical-align:middle}.vpn-table td{font-size:11px;vertical-align:middle}.vpn-badge{padding:5px 8px;border-radius:12px;font-weight:800}
@media(max-width:767px){.vpn-hero .card-body{display:block!important}.vpn-year-box{margin-top:14px;flex-wrap:wrap}.vpn-year-box>div{flex:1}.vpn-year-box select{width:100%}}
</style>
<section class="content-header pb-1"><div class="container-fluid">
  <div class="card vpn-hero mb-3"><div class="card-body d-flex justify-content-between align-items-center">
    <div><div class="vpn-kicker">Seguridad y acceso remoto</div><h1 class="h4 mb-1"><i class="fas fa-shield-alt mr-2"></i>Registro de accesos VPN</h1><div class="small opacity-75">Consulte los accesos por año y descargue el formato T-TI-F-21 organizado por mes.</div></div>
    <div class="vpn-year-box"><div><label for="vpnAnio">Año del reporte</label><select id="vpnAnio" class="form-control form-control-sm"><?php foreach ($years as $year): ?><option value="<?= $year ?>" <?= $year === $currentYear ? 'selected' : '' ?>><?= $year ?></option><?php endforeach; ?></select></div><button type="button" id="vpnConsultar" class="btn btn-light btn-sm font-weight-bold"><i class="fas fa-search mr-1 text-info"></i>Consultar</button><button type="button" id="vpnExcel" class="btn btn-success btn-sm font-weight-bold"><i class="fas fa-file-excel mr-1"></i>Excel anual</button><?php if (tiene_permiso('vpn', 'crear')): ?><a href="agregar.php" class="btn btn-warning btn-sm font-weight-bold"><i class="fas fa-plus mr-1"></i>Nuevo acceso</a><?php endif; ?></div>
  </div></div>
</div></section>
<section class="content"><div class="container-fluid"><?= display_msg($msg) ?>
  <div class="card vpn-card"><div class="card-header d-flex align-items-center"><div><h3 class="card-title font-weight-bold"><i class="fas fa-list mr-2 text-info"></i>Accesos del año <span id="vpnYearTitle"><?= $currentYear ?></span></h3><div class="small text-muted mt-1">La fecha de solicitud determina el mes donde aparecerá cada registro.</div></div></div>
    <div class="card-body"><div class="table-responsive"><table id="tblVPN" class="table table-bordered table-striped vpn-table w-100"><thead><tr><th>#</th><th>Solicitante</th><th>Empresa</th><th>Motivo</th><th>Fecha / hora</th><th>Nivel</th><th>Tiempo</th><th>Culminado</th><th>Acciones</th></tr></thead><tbody></tbody></table></div></div>
  </div>
</div></section>
<?php
$canEdit = tiene_permiso('vpn', 'editar') ? 'true' : 'false';
$inlineJs = <<<'JS'
$(function(){
  var table=$('#tblVPN').DataTable({language:DATATABLES_SPANISH,responsive:true,processing:true,ajax:{url:'../../public/ajax/consulta6.php',dataSrc:'',data:function(d){d.anio=$('#vpnAnio').val();}},columns:[
    {data:'Id_IngVpn'},{data:'Solicitante'},{data:'Empresa'},{data:'Motivo'},{data:'dateff'},{data:'Nivel_Usu'},{data:'Tiempo'},
    {data:'Culminado',render:function(d){return d==='SI'?'<span class="badge badge-success vpn-badge">SI</span>':'<span class="badge badge-warning vpn-badge">NO</span>'; }},
    {data:'Id_IngVpn',orderable:false,render:function(d){return __CAN_EDIT__?'<a href="editar.php?id='+d+'" class="btn btn-warning btn-sm" title="Editar acceso"><i class="fas fa-edit"></i></a>':'-';}}
  ],order:[[4,'desc']]});
  function selectedYear(){return $('#vpnAnio').val();}
  $('#vpnConsultar').on('click',function(){$('#vpnYearTitle').text(selectedYear());table.ajax.reload();});
  $('#vpnAnio').on('change',function(){$('#vpnConsultar').trigger('click');});
  $('#vpnExcel').on('click',function(){window.location.href='exportar_anual.php?anio='+encodeURIComponent(selectedYear());});
});
JS;
$inlineJs = str_replace('__CAN_EDIT__', $canEdit, $inlineJs);
include_once __DIR__ . '/../../layouts/footer.php';
?>
