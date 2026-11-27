<?php
$page_title = 'Mantenimiento TI';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/funciones_checklist.php');

ensure_mantenimiento_ti_schema();
ensure_users_tipo_registro();
require_permiso('mantenimiento_ti', 'ver');
$checklistReady = mti_chk_schema_ready();
$correctiveFinding = null;
$findingId = tiene_permiso('mantenimiento_ti', 'crear') ? (int)($_GET['hallazgo'] ?? 0) : 0;
if ($findingId > 0 && mti_chk_schema_ready()) {
    $correctiveFinding = $db->fetch_assoc($db->query(
        "SELECT h.id,h.codigo,h.titulo,h.descripcion,h.criticidad,h.mantenimiento_id,m.IdMaquina"
        . " FROM activos_mantenimiento_hallazgos h"
        . " INNER JOIN activos_mantenimientos m ON m.id=h.mantenimiento_id"
        . " WHERE h.id={$findingId} AND h.estado NOT IN ('CERRADO','ANULADO') LIMIT 1"
    )) ?: null;
}
$preselectedMachine = $correctiveFinding
    ? (int)$correctiveFinding['IdMaquina']
    : (tiene_permiso('mantenimiento_ti', 'crear') ? (int)($_GET['equipo'] ?? 0) : 0);

$extraCss = [
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    'https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css',
];

function mt_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$equipos = find_by_sql(
    "SELECT q.IdMaquina,q.Host,q.Tipo_Activo,q.EstadoMaquina,u.name AS usuario_asignado"
    . " FROM maquina q"
    . " LEFT JOIN asig_maquina a ON a.IdAsMaq=(SELECT MAX(a2.IdAsMaq) FROM asig_maquina a2"
    . " WHERE a2.IdMaquina=q.IdMaquina AND a2.Estado=1)"
    . " LEFT JOIN users u ON u.id=a.id ORDER BY q.Host ASC"
);
$personalTi = find_by_sql("SELECT id,name,username FROM users WHERE status=1 AND tipo_registro='USUARIO' AND es_personal_ti=1 ORDER BY name");
$ordenes = find_by_sql(
    "SELECT m.*, q.Host, q.Tipo_Activo, q.EstadoMaquina,
            NULLIF(m.usuario_asignado_nombre,'') AS usuario_asignado,
            oc.id AS ocurrencia_id, oc.codigo AS ocurrencia_codigo,
            COALESCE(d.total_detalle, 0) AS total_detalle,
            COALESCE(d.items, 0) AS items
     FROM activos_mantenimientos m
     INNER JOIN maquina q ON q.IdMaquina = m.IdMaquina
     LEFT JOIN activos_mantenimiento_ocurrencias oc ON oc.mantenimiento_id = m.id
     LEFT JOIN (
        SELECT mantenimiento_id, COUNT(*) AS items, SUM(cantidad * costo_unitario) AS total_detalle
        FROM activos_mantenimiento_detalle GROUP BY mantenimiento_id
     ) d ON d.mantenimiento_id = m.id
     ORDER BY m.fecha_programada DESC, m.id DESC"
);
$photoMap = [];
foreach (find_by_sql("SELECT id,mantenimiento_id,etapa,created_at FROM activos_mantenimiento_fotos ORDER BY id") as $photo) {
    $orderId = (int)$photo['mantenimiento_id'];
    $photoMap[$orderId][$photo['etapa']][] = [
        'id' => (int)$photo['id'],
        'url' => 'foto.php?foto=' . (int)$photo['id'] . '&v=' . rawurlencode((string)$photo['created_at']),
    ];
}

$ocurrenciasDisponibles = find_by_sql(
    "SELECT o.id,o.codigo,o.fecha_programada,o.estado,o.mantenimiento_id,"
    . " p.IdMaquina,p.codigo AS plan_codigo,p.nombre AS plan_nombre,p.tipo,p.prioridad,p.instruccion,"
    . " q.Host FROM activos_mantenimiento_ocurrencias o"
    . " INNER JOIN activos_mantenimiento_planes p ON p.id=o.plan_id"
    . " INNER JOIN maquina q ON q.IdMaquina=p.IdMaquina"
    . " WHERE (o.mantenimiento_id IS NULL AND o.estado='PENDIENTE') OR o.mantenimiento_id IS NOT NULL"
    . " ORDER BY o.fecha_programada,o.id"
);

$stats = ['TOTAL' => 0, 'PROGRAMADO' => 0, 'EN_PROCESO' => 0, 'COMPLETADO' => 0, 'VENCIDO' => 0];
$today = date('Y-m-d');
foreach ($ordenes as $row) {
    $stats['TOTAL']++;
    if (isset($stats[$row['estado']])) $stats[$row['estado']]++;
    if ($row['estado'] === 'PROGRAMADO' && $row['fecha_programada'] < $today) $stats['VENCIDO']++;
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
:root { --mti-ink:#123746; --mti-blue:#137c8b; --mti-orange:#e78b35; --mti-paper:#f4f8f7; }
.mti-hero { border:0; border-radius:14px; overflow:hidden; color:#fff; background:linear-gradient(120deg,var(--mti-ink),#176c78 68%,#dd8434); box-shadow:0 14px 35px rgba(18,55,70,.18); }
.mti-hero .card-body { padding:22px 26px; }
.mti-kicker { font-size:11px; letter-spacing:.13em; text-transform:uppercase; opacity:.75; font-weight:800; }
.mti-stat { height:100%; padding:15px 16px; border:1px solid #dbe7e7; border-radius:11px; background:#fff; box-shadow:0 5px 15px rgba(18,55,70,.06); }
.mti-stat strong { display:block; color:var(--mti-ink); font-size:25px; line-height:1; }
.mti-stat span { color:#68808a; font-size:11px; font-weight:800; text-transform:uppercase; }
.mti-filter { padding:13px 15px; border-radius:10px; background:var(--mti-paper); border:1px solid #dce8e7; }
.mti-filter label { color:#5c747e; font-size:10px; font-weight:800; text-transform:uppercase; }
.mti-table thead th { border:0; background:var(--mti-ink); color:#fff; font-size:11px; text-transform:uppercase; }
.mti-table td { vertical-align:middle; }
.mti-folio { color:var(--mti-blue); font-weight:800; }
.mti-status { display:inline-block; min-width:92px; padding:5px 8px; border-radius:20px; text-align:center; font-size:10px; font-weight:800; }
.mti-status.PROGRAMADO { background:#e8f2ff; color:#245d9b; }
.mti-status.EN_PROCESO { background:#fff3d8; color:#94610a; }
.mti-status.COMPLETADO { background:#e1f5ea; color:#24734b; }
.mti-status.CANCELADO { background:#edf0f2; color:#64737a; }
#modalOrdenTI .modal-dialog { width:calc(100% - 32px); max-width:1240px; }
#modalOrdenTI .modal-content { border:0; border-radius:18px; overflow:hidden; box-shadow:0 28px 70px rgba(10,42,52,.28); }
#modalOrdenTI .modal-header { align-items:center; padding:20px 26px; border:0; color:#fff; background:linear-gradient(120deg,#0f3e4a,var(--mti-blue)); }
#modalOrdenTI .modal-header-icon { display:flex; width:42px; height:42px; align-items:center; justify-content:center; margin-right:13px; border-radius:11px; background:rgba(255,255,255,.14); }
#modalOrdenTI .modal-header .close { margin:-8px -4px -8px auto; padding:12px; font-size:28px; opacity:.8; }
#modalOrdenTI .modal-body { max-height:calc(100vh - 190px); overflow-y:auto; padding:24px 26px; background:linear-gradient(145deg,#f8fbfa,#eef5f4); }
#modalOrdenTI .form-card { height:100%; padding:22px; border:1px solid #d7e5e3; border-radius:14px; background:#fff; box-shadow:0 5px 16px rgba(18,55,70,.06); }
#modalOrdenTI .form-section-title { display:flex; align-items:center; gap:8px; margin:0 0 18px; padding-bottom:12px; border-bottom:1px solid #e4edec; color:var(--mti-ink); font-size:12px; font-weight:900; text-transform:uppercase; letter-spacing:.055em; }
#modalOrdenTI .form-section-title i { color:var(--mti-blue); }
#modalOrdenTI .form-group { margin-bottom:18px; }
#modalOrdenTI label { margin-bottom:7px; color:#49636d; font-size:10px; font-weight:850; text-transform:uppercase; letter-spacing:.035em; }
#modalOrdenTI .form-control { min-height:42px; border-color:#cfdddc; border-radius:9px; }
#modalOrdenTI textarea.form-control { min-height:112px; padding:12px 14px; line-height:1.45; resize:vertical; }
#modalOrdenTI #ordenMotivo { min-height:132px; }
#modalOrdenTI .form-text,#modalOrdenTI small.text-muted { display:block; margin-top:7px; line-height:1.35; }
#modalOrdenTI .assigned-user-box { display:flex; align-items:center; gap:11px; margin-top:10px; padding:11px 13px; border:1px solid #d5e5e2; border-radius:10px; background:#f1f8f6; }
#modalOrdenTI .assigned-user-box i { color:var(--mti-blue); font-size:18px; }
#modalOrdenTI .assigned-user-box small { display:block; margin:0; color:#6b7f87; font-size:9px; font-weight:800; text-transform:uppercase; }
#modalOrdenTI .assigned-user-box strong { color:var(--mti-ink); font-size:13px; }
#modalOrdenTI .modal-footer { padding:16px 26px; border-top:1px solid #dce7e6; background:#fff; }
#modalOrdenTI .modal-footer .btn { min-width:120px; padding:9px 18px; border-radius:9px; }
#modalOrdenTI .select2-container { width:100% !important; }
#modalOrdenTI .select2-container .select2-selection--single { height:42px; border-color:#cfdddc; border-radius:9px; }
#modalOrdenTI .select2-container .select2-selection--single .select2-selection__rendered { line-height:40px; padding-left:13px; }
#modalOrdenTI .select2-container .select2-selection--single .select2-selection__arrow { height:40px; }
.mti-evidence-section { margin-top:18px; padding:18px; border:1px solid #cfe0df; border-radius:14px; background:#fff; }
.mti-evidence-heading { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
.mti-evidence-heading strong { color:var(--mti-ink); font-size:13px; text-transform:uppercase; letter-spacing:.04em; }
.mti-photo-card { position:relative; min-height:205px; padding:13px; border:1px solid #d7e4e3; border-radius:13px; background:#f5f9f8; overflow:hidden; }
.mti-photo-card.is-ready { background:#eaf4f1; }
.mti-photo-list { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:8px; min-height:105px; }
.mti-photo-thumb { position:relative; height:100px; padding:0; border:0; border-radius:9px; overflow:hidden; background:#dfeae8; cursor:zoom-in; }
.mti-photo-thumb img { width:100%; height:100%; object-fit:cover; }
.mti-photo-thumb.is-new:after { content:'NUEVA'; position:absolute; right:5px; top:5px; padding:2px 5px; border-radius:8px; color:#fff; background:#168b6b; font-size:8px; font-weight:900; }
.mti-photo-empty { display:flex; height:100px; flex-direction:column; align-items:center; justify-content:center; color:#71868e; text-align:center; border:2px dashed #bfd4d1; border-radius:9px; }
.mti-photo-empty i { margin-bottom:8px; color:var(--mti-blue); font-size:28px; }
.mti-photo-actions { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:9px; }
.mti-photo-actions strong { color:var(--mti-ink); font-size:12px; }
.mti-photo-actions .btn { border-radius:8px; }
.mti-photo-count { color:#59727b; font-size:11px; font-weight:800; }
#modalFotoTI { z-index:1080; }
#modalFotoTI .modal-dialog { max-width:1050px; }
#modalFotoTI .modal-content { border:0; border-radius:16px; overflow:hidden; background:#102e36; box-shadow:0 25px 70px rgba(0,0,0,.4); }
#modalFotoTI .modal-header { border:0; color:#fff; background:#123f49; }
#modalFotoTI .modal-body { padding:12px; text-align:center; }
#modalFotoTI img { max-width:100%; max-height:76vh; border-radius:10px; object-fit:contain; }
@media (max-width:767px) { .mti-hero .card-body { padding:18px; } .mti-actions { margin-top:14px; display:flex; flex-wrap:wrap; gap:6px; } #modalOrdenTI .modal-dialog{width:calc(100% - 14px);margin:7px auto} #modalOrdenTI .modal-body{padding:16px;max-height:calc(100vh - 150px)} #modalOrdenTI .modal-header,#modalOrdenTI .modal-footer{padding:15px 17px} #modalOrdenTI .form-card{padding:17px} }
</style>

<section class="content-header pb-1">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0"><i class="fas fa-screwdriver-wrench mr-2 text-info"></i>Mantenimiento TI</h1>
      <ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li><li class="breadcrumb-item active">Mantenimiento TI</li></ol>
    </div>
  </div>
</section>

<section class="content">
<div class="container-fluid">
  <?= display_msg($msg) ?>
  <div class="card mti-hero mb-3">
    <div class="card-body d-md-flex justify-content-between align-items-center">
      <div>
        <div class="mti-kicker">Gestion de equipos de computo / MySQL</div>
        <h2 class="h4 font-weight-bold mb-1">Ordenes de mantenimiento</h2>
        <div class="small opacity-75">Programa, ejecuta y conserva el historial tecnico de cada activo.</div>
      </div>
      <div class="mti-actions">
        <a href="indicadores.php" class="btn btn-outline-light font-weight-bold"><i class="fas fa-chart-line mr-1"></i>Indicadores</a>
        <a href="planes.php" class="btn btn-outline-light font-weight-bold"><i class="fas fa-layer-group mr-1"></i>Planes</a>
        <a href="ocurrencias.php" class="btn btn-outline-light font-weight-bold"><i class="fas fa-calendar-alt mr-1"></i>Programacion</a>
        <a href="checklist_plantillas.php" class="btn btn-outline-light font-weight-bold"><i class="fas fa-list-check mr-1"></i>Checklists</a>
        <a href="checklist_pantalla.php" class="btn btn-outline-light font-weight-bold"><i class="fas fa-shield-screen mr-1"></i>Pantalla limpia</a>
        <a href="hallazgos.php" class="btn btn-outline-light font-weight-bold"><i class="fas fa-triangle-exclamation mr-1"></i>Hallazgos</a>
        <a href="exportar.php" class="btn btn-success font-weight-bold" id="btnReporteExcel"><i class="fas fa-file-excel mr-1"></i>Reporte Excel</a>
        <?php if (tiene_permiso('mantenimiento_ti', 'crear')): ?>
        <button class="btn btn-light font-weight-bold" id="btnNuevaOrden"><i class="fas fa-plus-circle mr-1 text-warning"></i>Nueva orden</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-6 col-lg mb-2"><div class="mti-stat"><strong><?= $stats['TOTAL'] ?></strong><span>Total</span></div></div>
    <div class="col-6 col-lg mb-2"><div class="mti-stat"><strong><?= $stats['PROGRAMADO'] ?></strong><span>Programados</span></div></div>
    <div class="col-6 col-lg mb-2"><div class="mti-stat"><strong><?= $stats['EN_PROCESO'] ?></strong><span>En proceso</span></div></div>
    <div class="col-6 col-lg mb-2"><div class="mti-stat"><strong><?= $stats['COMPLETADO'] ?></strong><span>Completados</span></div></div>
    <div class="col-6 col-lg mb-2"><div class="mti-stat"><strong class="text-danger"><?= $stats['VENCIDO'] ?></strong><span>Vencidos</span></div></div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <div class="mti-filter row align-items-end mb-3">
        <div class="col-md-3 mb-2 mb-md-0"><label>Buscar</label><input id="fBuscar" class="form-control form-control-sm" placeholder="Folio, equipo, tecnico..."></div>
        <div class="col-md-3 mb-2 mb-md-0"><label>Estado</label><select id="fEstado" class="form-control form-control-sm"><option value="">Todos</option><option>PROGRAMADO</option><option>EN_PROCESO</option><option>COMPLETADO</option><option>CANCELADO</option></select></div>
        <div class="col-md-3 mb-2 mb-md-0"><label>Tipo</label><select id="fTipo" class="form-control form-control-sm"><option value="">Todos</option><option>PREVENTIVO</option><option>CORRECTIVO</option><option>DIAGNOSTICO</option><option>MEJORA</option></select></div>
        <div class="col-md-3"><label>Prioridad</label><select id="fPrioridad" class="form-control form-control-sm"><option value="">Todas</option><option>BAJA</option><option>MEDIA</option><option>ALTA</option><option>CRITICA</option></select></div>
      </div>
      <div class="table-responsive">
        <table id="tblMantenimientoTI" class="table table-hover table-bordered mti-table w-100">
          <thead><tr><th>Folio</th><th>Equipo</th><th>Tipo</th><th>Prioridad</th><th>Programado</th><th>Estado</th><th>Tecnico</th><th>Repuestos/Servicios</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($ordenes as $orden):
            $record = [
              'id'=>(int)$orden['id'], 'IdMaquina'=>(int)$orden['IdMaquina'], 'folio'=>$orden['folio'],
              'tipo'=>$orden['tipo'], 'prioridad'=>$orden['prioridad'], 'estado'=>$orden['estado'],
              'fecha_programada'=>$orden['fecha_programada'], 'fecha_ejecucion'=>$orden['fecha_ejecucion'],
              'motivo'=>$orden['motivo'], 'diagnostico'=>$orden['diagnostico'],
              'trabajo_realizado'=>$orden['trabajo_realizado'], 'tecnico_responsable'=>$orden['tecnico_responsable'],
              'fotos_antes'=>$photoMap[(int)$orden['id']]['ANTES'] ?? [],
              'fotos_despues'=>$photoMap[(int)$orden['id']]['DESPUES'] ?? [],
              'usuario_asignado_nombre'=>$orden['usuario_asignado_nombre'] ?? $orden['usuario_asignado'] ?? '',
              'ocurrencia_id'=>(int)($orden['ocurrencia_id'] ?? 0),
              'ocurrencia_codigo'=>$orden['ocurrencia_codigo'] ?? ''
            ];
          ?>
            <tr>
              <td><span class="mti-folio"><?= mt_h($orden['folio']) ?></span><?= !empty($orden['ocurrencia_codigo']) ? '<div class="small text-success"><i class="fas fa-link mr-1"></i>'.mt_h($orden['ocurrencia_codigo']).'</div>' : '<div class="small text-muted">Sin programacion</div>' ?></td>
              <td><strong><?= mt_h($orden['Host']) ?></strong><div class="small text-muted"><?= mt_h($orden['usuario_asignado'] ?: 'Sin usuario asignado') ?></div></td>
              <td><?= mt_h($orden['tipo']) ?></td>
              <td><?= mt_h($orden['prioridad']) ?></td>
              <td data-order="<?= mt_h($orden['fecha_programada']) ?>"><?= mt_h(date('d/m/Y', strtotime($orden['fecha_programada']))) ?></td>
              <td><span class="mti-status <?= mt_h($orden['estado']) ?>"><?= mt_h(str_replace('_', ' ', $orden['estado'])) ?></span></td>
              <td><?= mt_h($orden['tecnico_responsable'] ?: '-') ?></td>
              <td class="text-right">S/ <?= number_format((float)$orden['total_detalle'], 2) ?></td>
              <td class="text-center text-nowrap">
                <a href="detalle.php?id=<?= (int)$orden['id'] ?>" class="btn btn-sm btn-info" title="Ver seguimiento"><i class="fas fa-eye"></i></a>
                <?php if ($checklistReady && tiene_permiso('mantenimiento_ti', 'editar')): ?>
                <form method="post" action="preparar_checklist.php" class="d-inline"><?= csrf_input() ?><input type="hidden" name="mantenimiento_id" value="<?= (int)$orden['id'] ?>"><button type="submit" class="btn btn-sm btn-success" title="Abrir checklist predefinido"><i class="fas fa-list-check"></i></button></form>
                <?php endif; ?>
                <?php if (tiene_permiso('mantenimiento_ti', 'editar')): ?>
                <button type="button" class="btn btn-sm btn-warning btnEditarOrden" data-record="<?= mt_h(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>" title="Editar"><i class="fas fa-edit"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</section>

<div class="modal fade" id="modalOrdenTI" tabindex="-1">
 <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
  <form method="post" action="guardar.php" id="formOrdenTI" enctype="multipart/form-data">
   <?= csrf_input() ?><input type="hidden" name="id" id="ordenId" value="0"><input type="hidden" name="hallazgo_id" id="ordenHallazgoId" value="<?= $correctiveFinding ? (int)$correctiveFinding['id'] : 0 ?>"><input type="hidden" id="preselectedMachine" value="<?= $preselectedMachine ?>">
   <div class="modal-header"><div class="modal-header-icon"><i class="fas fa-clipboard-check"></i></div><div><div class="mti-kicker">Mantenimiento de activo</div><h5 class="modal-title font-weight-bold" id="ordenTitulo">Nueva orden</h5></div><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
   <div class="modal-body"><div class="row">
    <div class="col-lg-5"><div class="form-card h-100">
      <div class="form-section-title"><i class="fas fa-sliders-h"></i>Datos generales</div>
      <div class="form-group"><label>Equipo *</label><select name="IdMaquina" id="ordenEquipo" class="form-control" required><option value="">Buscar equipo...</option><?php foreach($equipos as $equipo): ?><option value="<?= (int)$equipo['IdMaquina'] ?>" data-assigned-user="<?= mt_h($equipo['usuario_asignado'] ?? '') ?>"><?= mt_h($equipo['Host'].' / '.$equipo['Tipo_Activo'].' / '.$equipo['EstadoMaquina']) ?></option><?php endforeach; ?></select><div class="assigned-user-box"><i class="fas fa-user-tag"></i><div><small>Usuario asignado al equipo</small><strong id="ordenUsuarioEquipo">Selecciona un equipo</strong></div></div></div>
      <div class="form-group"><label>Programacion asociada <span class="text-muted">(opcional)</span></label><select name="ocurrencia_id" id="ordenOcurrencia" class="form-control"><option value="">Orden sin programacion</option></select><small id="ordenOcurrenciaHelp" class="form-text text-muted">Selecciona primero un equipo para listar sus ocurrencias pendientes.</small></div>
      <div class="row"><div class="col-6 form-group"><label>Tipo *</label><select name="tipo" id="ordenTipo" class="form-control" required><option>PREVENTIVO</option><option>CORRECTIVO</option><option>DIAGNOSTICO</option><option>MEJORA</option></select></div><div class="col-6 form-group"><label>Prioridad *</label><select name="prioridad" id="ordenPrioridad" class="form-control"><option>BAJA</option><option selected>MEDIA</option><option>ALTA</option><option>CRITICA</option></select></div></div>
      <div class="row"><div class="col-6 form-group"><label>Fecha programada *</label><input type="date" name="fecha_programada" id="ordenFecha" class="form-control" required><small class="text-muted">Proviene de la ocurrencia seleccionada.</small></div><div class="col-6 form-group"><label>Fecha realizada</label><input type="date" name="fecha_ejecucion" id="ordenFechaEjecucion" class="form-control"><small class="text-muted">Dia real en que se ejecuto la OT.</small></div></div>
      <div class="form-group"><label>Estado *</label><select name="estado" id="ordenEstado" class="form-control"><option>PROGRAMADO</option><option>EN_PROCESO</option><option>COMPLETADO</option><option>CANCELADO</option></select></div>
      <div class="form-group mb-0"><label>Responsable TI</label><select name="tecnico_responsable" id="ordenTecnico" class="form-control"><option value="">Sin asignar</option><?php foreach($personalTi as $persona):?><option value="<?=mt_h($persona['name'])?>"><?=mt_h($persona['name'].' / '.$persona['username'])?></option><?php endforeach;?></select><small class="text-muted">Solo aparecen usuarios activos marcados como Personal de TI.</small></div>
    </div></div>
    <div class="col-lg-7 mt-3 mt-lg-0">
     <div class="form-card h-100">
      <div class="form-section-title"><i class="fas fa-file-medical-alt"></i>Diagnostico y trabajo</div>
      <div class="form-group"><label>Motivo o falla reportada *</label><textarea name="motivo" id="ordenMotivo" class="form-control" rows="4" required maxlength="2000" placeholder="Describe el problema reportado o el mantenimiento programado..."></textarea></div>
      <div class="row"><div class="col-md-6 form-group mb-md-0"><label>Diagnostico</label><textarea name="diagnostico" id="ordenDiagnostico" class="form-control" rows="4" maxlength="3000" placeholder="Hallazgos de la revision..."></textarea></div><div class="col-md-6 form-group mb-0"><label>Trabajo realizado</label><textarea name="trabajo_realizado" id="ordenTrabajo" class="form-control" rows="4" maxlength="3000" placeholder="Actividades ejecutadas..."></textarea></div></div>
     </div>
    </div>
   </div>
   <div class="mti-evidence-section">
    <div class="mti-evidence-heading"><strong><i class="fas fa-camera-retro mr-2 text-info"></i>Evidencia antes y despues</strong><span class="small text-muted">Hasta 5 fotos por etapa · JPG, PNG o WEBP · 15 MB c/u</span></div>
    <div class="row">
     <div class="col-md-6 mb-3 mb-md-0">
      <div class="mti-photo-card" id="cardFotoAntes">
       <div class="mti-photo-list" id="listaFotoAntes"></div>
       <div class="mti-photo-empty" id="emptyFotoAntes"><i class="fas fa-camera"></i><strong>Antes del mantenimiento</strong><small>Como se encontro el equipo</small></div>
       <div class="mti-photo-actions"><strong><i class="fas fa-circle-dot mr-1 text-warning"></i>ANTES <span class="mti-photo-count" id="countFotoAntes">0/5</span></strong><label for="ordenFotoAntes" class="btn btn-sm btn-outline-info mb-0"><i class="fas fa-images mr-1"></i><span>Agregar fotos</span></label></div>
       <input type="file" name="fotos_antes[]" id="ordenFotoAntes" class="d-none" accept="image/jpeg,image/png,image/webp" multiple>
      </div>
     </div>
     <div class="col-md-6">
      <div class="mti-photo-card" id="cardFotoDespues">
       <div class="mti-photo-list" id="listaFotoDespues"></div>
       <div class="mti-photo-empty" id="emptyFotoDespues"><i class="fas fa-camera"></i><strong>Despues del mantenimiento</strong><small>Como quedo el equipo</small></div>
       <div class="mti-photo-actions"><strong><i class="fas fa-circle-check mr-1 text-success"></i>DESPUES <span class="mti-photo-count" id="countFotoDespues">0/5</span></strong><label for="ordenFotoDespues" class="btn btn-sm btn-outline-info mb-0"><i class="fas fa-images mr-1"></i><span>Agregar fotos</span></label></div>
       <input type="file" name="fotos_despues[]" id="ordenFotoDespues" class="d-none" accept="image/jpeg,image/png,image/webp" multiple>
      </div>
     </div>
    </div>
   </div>
   </div>
   <div class="modal-footer"><button type="button" class="btn btn-success mr-auto" id="btnChecklistOrden" disabled><i class="fas fa-list-check mr-1"></i>Checklist predefinido</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-info font-weight-bold"><i class="fas fa-save mr-1"></i>Guardar orden</button></div>
  </form>
 </div></div>
</div>

<form method="post" action="preparar_checklist.php" id="formPrepararChecklist" class="d-none"><?= csrf_input() ?><input type="hidden" name="mantenimiento_id" id="checklistOrdenId" value="0"></form>

<div class="modal fade" id="modalFotoTI" tabindex="-1">
 <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title font-weight-bold" id="modalFotoTitulo"><i class="fas fa-image mr-2"></i>Evidencia</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
  <div class="modal-body"><img id="modalFotoImagen" alt="Evidencia ampliada"></div>
 </div></div>
</div>

<script type="application/json" id="mtiOccurrenceData"><?= json_encode($ocurrenciasDisponibles, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script type="application/json" id="mtiCorrectiveData"><?= json_encode($correctiveFinding ? [
  'id' => (int)$correctiveFinding['id'],
  'codigo' => (string)$correctiveFinding['codigo'],
  'criticidad' => in_array($correctiveFinding['criticidad'], ['ALTA', 'CRITICA'], true) ? $correctiveFinding['criticidad'] : 'MEDIA',
  'motivo' => 'Correctivo por ' . $correctiveFinding['codigo'] . ': ' . $correctiveFinding['titulo'] . "\n" . $correctiveFinding['descripcion'],
] : null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php
$inlineJs = <<<'JS'
$(function() {
  var table = $('#tblMantenimientoTI').DataTable({
    responsive:true, pageLength:15, lengthMenu:[[15,30,50,-1],[15,30,50,'Todos']],
    order:[[4,'desc']], language:DATATABLES_SPANISH,
    dom:'rt<"row mt-3"<"col-md-5"i><"col-md-7"p>>'
  });
  $('#fBuscar').on('input', function(){ table.search(this.value).draw(); });
  $('#fEstado').on('change', function(){ table.column(5).search(this.value ? '^'+this.value.replace('_',' ')+'$' : '', true, false).draw(); });
  $('#fTipo').on('change', function(){ table.column(2).search(this.value ? '^'+this.value+'$' : '', true, false).draw(); });
  $('#fPrioridad').on('change', function(){ table.column(3).search(this.value ? '^'+this.value+'$' : '', true, false).draw(); });
  $('#btnReporteExcel').on('click', function(event) {
    event.preventDefault();
    var params = new URLSearchParams();
    var search = String($('#fBuscar').val() || '').trim();
    if (search) params.set('q', search);
    if ($('#fEstado').val()) params.set('estado', $('#fEstado').val());
    if ($('#fTipo').val()) params.set('tipo', $('#fTipo').val());
    if ($('#fPrioridad').val()) params.set('prioridad', $('#fPrioridad').val());
    window.location.href = 'exportar.php' + (params.toString() ? '?' + params.toString() : '');
  });

  var equipo = $('#ordenEquipo').select2({dropdownParent:$('#modalOrdenTI'), theme:'bootstrap4', width:'100%', placeholder:'Buscar equipo...'});
  var ocurrencias = JSON.parse(document.getElementById('mtiOccurrenceData').textContent || '[]');
  var correctiveData = JSON.parse(document.getElementById('mtiCorrectiveData').textContent || 'null');
  var localPhotoUrls = {antes:[],despues:[]};
  var storedPhotos = {antes:[],despues:[]};
  function photoElements(kind) {
    var suffix = kind === 'antes' ? 'Antes' : 'Despues';
    return {
      input: document.getElementById('ordenFoto' + suffix),
      list: document.getElementById('listaFoto' + suffix),
      empty: document.getElementById('emptyFoto' + suffix),
      card: document.getElementById('cardFoto' + suffix),
      count: document.getElementById('countFoto' + suffix)
    };
  }
  function renderPhotos(kind) {
    var elements = photoElements(kind);
    var photos = storedPhotos[kind].map(function(photo){return {src:photo.url,isNew:false};})
      .concat(localPhotoUrls[kind].map(function(src){return {src:src,isNew:true};}));
    elements.list.innerHTML = photos.map(function(photo,index){
      return '<button type="button" class="mti-photo-thumb btnViewGallery '+(photo.isNew?'is-new':'')+'" data-src="'+photo.src+'" data-title="'+(kind==='antes'?'Antes':'Despues')+' · foto '+(index+1)+'"><img src="'+photo.src+'" alt="Evidencia"></button>';
    }).join('');
    elements.empty.style.display = photos.length ? 'none' : 'flex';
    elements.list.style.display = photos.length ? 'grid' : 'none';
    elements.card.classList.toggle('is-ready', photos.length > 0);
    elements.count.textContent = photos.length + '/5';
    $(elements.card).find('label').toggleClass('disabled', photos.length >= 5);
  }
  function clearPhotos() {
    ['antes','despues'].forEach(function(kind) {
      localPhotoUrls[kind].forEach(function(url){URL.revokeObjectURL(url);});
      localPhotoUrls[kind] = [];
      storedPhotos[kind] = [];
      var elements = photoElements(kind);
      elements.input.value = '';
      renderPhotos(kind);
    });
  }
  function loadStoredPhotos(record) {
    ['antes','despues'].forEach(function(kind) {
      storedPhotos[kind] = Array.isArray(record['fotos_' + kind]) ? record['fotos_' + kind] : [];
      renderPhotos(kind);
    });
  }
  $('#ordenFotoAntes,#ordenFotoDespues').on('change', function() {
    var kind = this.id === 'ordenFotoAntes' ? 'antes' : 'despues';
    var files = Array.from(this.files || []);
    var invalid = files.some(function(file){return !['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > 15 * 1024 * 1024;});
    if (invalid) {
      this.value = '';
      Swal.fire('Fotos no validas','Use imagenes JPG, PNG o WEBP de hasta 15 MB cada una.','warning');
      return;
    }
    if (storedPhotos[kind].length + files.length > 5) {
      this.value = '';
      Swal.fire('Limite de fotografias','Puede completar como maximo 5 fotos '+(kind==='antes'?'Antes':'Despues')+'. Ya existen '+storedPhotos[kind].length+'.','warning');
      return;
    }
    localPhotoUrls[kind].forEach(function(url){URL.revokeObjectURL(url);});
    localPhotoUrls[kind] = files.map(function(file){return URL.createObjectURL(file);});
    renderPhotos(kind);
  });
  $(document).on('click', '.btnViewGallery', function() {
    if (!this.dataset.src) return;
    $('#modalFotoTitulo').html('<i class="fas fa-image mr-2"></i>' + (this.dataset.title || 'Evidencia'));
    $('#modalFotoImagen').attr('src', this.dataset.src);
    $('#modalFotoTI').modal('show');
  });
  $('#modalFotoTI').on('hidden.bs.modal', function() {
    $('#modalFotoImagen').removeAttr('src');
    if ($('#modalOrdenTI').hasClass('show')) document.body.classList.add('modal-open');
  });
  function occurrenceOptions(machineId, selectedId, orderId) {
    var select = document.getElementById('ordenOcurrencia');
    var rows = ocurrencias.filter(function(row) {
      return String(row.IdMaquina) === String(machineId)
        && (!row.mantenimiento_id || String(row.mantenimiento_id) === String(orderId));
    });
    select.innerHTML = '<option value="">Orden sin programacion</option>' + rows.map(function(row) {
      var label = row.codigo + ' / ' + row.plan_codigo + ' - ' + row.plan_nombre + ' / ' + row.fecha_programada;
      return '<option value="' + row.id + '">' + $('<div>').text(label).html() + '</option>';
    }).join('');
    select.value = selectedId ? String(selectedId) : '';
    select.disabled = !!selectedId;
    var selectedRow = rows.find(function(row){return String(row.id)===String(selectedId);});
    document.getElementById('ordenOcurrenciaHelp').textContent = selectedId
      ? 'Vinculada a ' + (selectedRow ? selectedRow.codigo : 'la programacion seleccionada') + '. La asociacion queda protegida para conservar trazabilidad.'
      : (rows.length ? rows.length + ' ocurrencia(s) pendiente(s) disponibles para este equipo.' : 'Este equipo no tiene ocurrencias pendientes disponibles.');
  }
  function localDate(){ var d=new Date(); return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }
  function resetForm(){
    clearPhotos(); document.getElementById('formOrdenTI').reset(); $('#ordenId,#ordenHallazgoId').val('0'); equipo.val('').trigger('change');
    occurrenceOptions('',0,0); $('#ordenUsuarioEquipo').text('Selecciona un equipo'); $('#ordenFecha').val(localDate()).prop('readonly',false); $('#ordenFechaEjecucion').val(''); $('#ordenTitulo').text('Nueva orden'); $('#ordenEquipo').prop('disabled',false); $('#btnChecklistOrden').prop('disabled',true);
  }
  equipo.on('change', function(){
    occurrenceOptions(this.value, $('#ordenOcurrencia').val(), $('#ordenId').val());
    var option=this.options[this.selectedIndex];
    $('#ordenUsuarioEquipo').text(option && option.dataset.assignedUser ? option.dataset.assignedUser : (this.value ? 'Sin usuario asignado' : 'Selecciona un equipo'));
  });
  $('#ordenOcurrencia').on('change', function(){
    $('#ordenFecha').prop('readonly', !!this.value);
    if (!this.value || $('#ordenId').val() !== '0') return;
    var row = ocurrencias.find(function(item){return String(item.id) === String($('#ordenOcurrencia').val());});
    if (!row) return;
    $('#ordenTipo').val(row.tipo || 'PREVENTIVO'); $('#ordenPrioridad').val(row.prioridad || 'MEDIA');
    $('#ordenFecha').val(row.fecha_programada || localDate()); $('#ordenMotivo').val(row.instruccion || row.plan_nombre || '');
  });
  $('#btnNuevaOrden').on('click', function(){ resetForm(); $('#modalOrdenTI').modal('show'); });
  var preselected=$('#preselectedMachine').val();
  if (preselected && preselected !== '0') {
    resetForm(); equipo.val(preselected).trigger('change');
    if (correctiveData) {
      $('#ordenHallazgoId').val(correctiveData.id);
      $('#ordenTipo').val('CORRECTIVO');
      $('#ordenPrioridad').val(correctiveData.criticidad);
      $('#ordenMotivo').val(correctiveData.motivo);
      $('#ordenTitulo').text('Nueva OT correctiva - ' + correctiveData.codigo);
    }
    $('#modalOrdenTI').modal('show');
  }
  $(document).on('click', '.btnEditarOrden', function(event){
    event.preventDefault();
    event.stopPropagation();
    var r=$(this).attr('data-record');
    try { r=JSON.parse(r); } catch(error) {
      Swal.fire('No se pudo editar','Los datos de esta orden no tienen un formato valido. Recargue la pagina e intente nuevamente.','error');
      return;
    }
    if(!r || !r.id || !r.IdMaquina) {
      Swal.fire('No se pudo editar','La orden no contiene un identificador o equipo valido.','error');
      return;
    }
    resetForm(); $('#ordenId').val(r.id); equipo.val(String(r.IdMaquina)).trigger('change');
    $('#ordenTipo').val(r.tipo); $('#ordenPrioridad').val(r.prioridad); $('#ordenEstado').val(r.estado);
    $('#ordenFecha').val(r.fecha_programada).prop('readonly', !!r.ocurrencia_id); $('#ordenFechaEjecucion').val(r.fecha_ejecucion || '');
    $('#ordenTecnico').val(r.tecnico_responsable || ''); $('#ordenMotivo').val(r.motivo || ''); $('#ordenDiagnostico').val(r.diagnostico || ''); $('#ordenTrabajo').val(r.trabajo_realizado || '');
    $('#ordenUsuarioEquipo').text(r.usuario_asignado_nombre || 'Sin usuario asignado al crear la OT');
    occurrenceOptions(r.IdMaquina, r.ocurrencia_id || 0, r.id);
    loadStoredPhotos(r);
    $('#ordenTitulo').text('Editar '+r.folio); $('#ordenEquipo').prop('disabled',true); $('#btnChecklistOrden').prop('disabled',false); $('#modalOrdenTI').modal('show');
  });
  $('#btnChecklistOrden').on('click', function(){
    var orderId = Number($('#ordenId').val() || 0);
    if (!orderId) return;
    $('#checklistOrdenId').val(orderId);
    document.getElementById('formPrepararChecklist').submit();
  });
  $('#formOrdenTI').on('submit', function(){ $('#ordenEquipo,#ordenOcurrencia').prop('disabled',false); });
  $('#ordenEstado').on('change', function(){if(this.value==='COMPLETADO'&&!$('#ordenFechaEjecucion').val())$('#ordenFechaEjecucion').val(localDate());});
  $('#ordenFechaEjecucion').on('change', function(){
    if(this.value && $('#ordenEstado').val()!=='CANCELADO') $('#ordenEstado').val('COMPLETADO');
    else if(!this.value && $('#ordenEstado').val()==='COMPLETADO') $('#ordenEstado').val('PROGRAMADO');
  });
});
JS;
include_once(__DIR__ . '/../../layouts/footer.php');
?>
