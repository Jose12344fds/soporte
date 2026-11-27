<?php
$page_title = 'Programar Semana v2';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_permiso('hh-meta', 'ver');

/* ──── Datos de semanas para el select dinámico ──── */
$id = date('Y-m-d');
$all_problema = find_all_EXT($id);
$activo1 = remove_junk($all_problema['semana']);

$all_semana_rows = find_by_sql(
    "SELECT YEAR(fecha) AS anio, semana FROM dias_del_anio
     WHERE YEAR(fecha) IN (" . date('Y') . ", " . (date('Y') - 1) . ")
     GROUP BY YEAR(fecha), semana
     ORDER BY YEAR(fecha), semana"
);
$all_semana = [];
if ($all_semana_rows && is_array($all_semana_rows)) {
    foreach ($all_semana_rows as $r) {
        $y = $r['anio'];
        if (!isset($all_semana[$y])) $all_semana[$y] = [];
        if (!in_array($r['semana'], $all_semana[$y])) $all_semana[$y][] = $r['semana'];
    }
}
$all_semana_json = json_encode($all_semana);
$all_anio        = find_allq('dias_del_anio');
$all_anio_json   = json_encode($all_anio);

include_once dirname(dirname(__DIR__)) . '/layouts/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="../../assets/css/programarsemana1.css?v=20261021_float2">

<!-- ════════════════════════════════════════════════
     HTML — Programar Semana v2
     ════════════════════════════════════════════════ -->
<div class="container-fluid" style="padding: 0 16px;">

  <!-- HERO -->
  <div class="ps-hero">
    <div class="ps-hero-left">
      <div class="ps-hero-icon"><i class="fa fa-calendar-check-o"></i></div>
      <div>
        <h4><i class="fa fa-calendar"></i> Programar Semana</h4>
        <p><i class="fa fa-mouse-pointer"></i> Clic en OT &rarr; ver items &nbsp;&bull;&nbsp; <i class="fa fa-hand-o-right"></i> Clic derecho en celda MO &rarr; programar &nbsp;&bull;&nbsp; <i class="fa fa-pencil"></i> Doble clic para editar</p>
      </div>
    </div>
    <div class="ps-hero-right">
      <span class="ps-hero-badge" id="badgeCount"><i class="fa fa-spinner fa-spin"></i> Cargando...</span>
    </div>
  </div>

  <!-- ═══ TABLA OTs VALIDADAS ═══ -->
  <div class="ps-card">
    <div class="ps-card-header primary">
      <span><i class="fa fa-list"></i> OTs Validadas <span class="ps-header-count" id="otCount"></span></span>
      <div class="ps-search-wrap">
        <i class="fa fa-search ps-search-icon"></i>
        <input type="text" id="otSearch" class="ps-search-input" placeholder="Buscar OT, Proyecto, Descripción..." autocomplete="off">
        <button type="button" class="ps-search-clear" id="otSearchClear" title="Limpiar">&times;</button>
      </div>
      <small class="ps-header-hint">Clic en fila &rarr; ver items</small>
    </div>
    <div class="ps-card-body" style="padding:0;">
      <div class="table-responsive">
        <table id="tblOTs" class="table table-striped table-bordered" style="width:100%">
          <thead>
            <tr>
              <th style="width:6%">ID</th>
              <th style="width:12%">Proyecto</th>
              <th style="width:28%">Descripción</th>
              <th style="width:9%">Correo</th>
              <th style="width:9%">Estado</th>
              <th style="width:9%">Cerrar OT</th>
              <th style="width:12%">Cerrar Semanas</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ═══ SUBCONSULTA ITEMS ═══ -->
  <div class="ps-card" id="cardSub">
    <div class="ps-card-header info">
      <span><i class="fa fa-th"></i> Detalle de Ítems</span>
      <div style="display:flex;align-items:center;gap:10px;">
        <button type="button" class="btn-toggle-pe" id="btnTogglePE">
          <span class="icon">+</span> Proc. Especiales
        </button>
        <small style="opacity:.7">Clic derecho sobre celda MO &rarr; programar</small>
      </div>
    </div>
    <div class="ps-card-body">
      <div id="subHint" class="text-center text-muted" style="padding:30px;">
        <i class="fa fa-hand-pointer-o fa-2x"></i><br>
        Selecciona una OT arriba para ver sus ítems
      </div>
      <div id="subWrapper" style="display:none; overflow-x:auto; width:100%;">
        <table id="tblSub" class="table table-striped table-bordered meta-table">
          <thead>
            <tr>
              <th class="th-group th-hab" colspan="1"></th>
              <th class="th-group th-hab" colspan="1"></th>
              <th class="th-group th-hab" colspan="1"></th>
              <th class="th-group th-hab" colspan="1"></th>
              <th class="th-group th-hab" colspan="1"></th>
              <th class="th-group th-hab" colspan="1"></th>
              <th class="th-group th-hab" colspan="1"></th>
              <th class="th-group th-hab" colspan="4">Habilitado Meta</th>
              <th class="th-group th-fab" colspan="11">Fabricación HH Meta</th>
              <th class="th-group th-acab" colspan="4">Acabados HH Meta</th>
              <th class="th-group th-pe col-pe-header" colspan="4" style="display:none">Procesos Especiales</th>
              <th class="th-group th-tot" colspan="1">Total</th>
            </tr>
            <tr>
              <th class="th-neutral">N°</th>
              <th class="th-neutral">Tipo</th>
              <th class="th-neutral">Cantd</th>
              <th class="th-neutral" style="width:120px">NPdre</th>
              <th class="th-neutral">HH.Ppto</th>
              <th class="th-neutral">Peso Ppto</th>
              <th class="th-neutral">Peso Sold.</th>
              <!-- Habilitado -->
              <th class="vtext th-hab">Pantógrafo Grande</th>
              <th class="vtext th-hab">Pantógrafo Chico</th>
              <th class="vtext th-hab">Habilitado Perfiles</th>
              <th class="vtext th-hab">Limpieza Mecánica</th>
              <!-- Fabricación -->
              <th class="vtext th-fab">Rolado</th>
              <th class="vtext th-fab">Torno Grande</th>
              <th class="vtext th-fab">Torno Chico</th>
              <th class="vtext th-fab">CNC Grande</th>
              <th class="vtext th-fab">CNC Chico</th>
              <th class="vtext th-fab">Armado</th>
              <th class="vtext th-fab">Soldeo</th>
              <th class="vtext th-fab">Plantillado</th>
              <th class="vtext th-fab">Taladro</th>
              <th class="vtext th-fab">Limpieza Mec.</th>
              <th class="vtext th-fab">Ensamble</th>
              <!-- Acabados -->
              <th class="vtext th-acab">Granallado</th>
              <th class="vtext th-acab">Pintura</th>
              <th class="vtext th-acab">Decapado Pasivado</th>
              <th class="vtext th-acab">Embalaje</th>
              <!-- Especiales -->
              <th class="vtext th-pe col-pe-header">Tratam. Térmico</th>
              <th class="vtext th-pe col-pe-header">Mecanizado</th>
              <th class="vtext th-pe col-pe-header">Revestimiento</th>
              <th class="vtext th-pe col-pe-header">Varios</th>
              <!-- Total -->
              <th class="vtext th-tot">HH. Meta</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Botón Reporte -->
  <div class="ps-report-area">
    <button id="btnReporte" class="btn-report" type="button">
      <i class="fa fa-bar-chart"></i> Reporte de Procesos No Cerrados
    </button>
  </div>

</div><!-- /container -->

<div style="clear: both; height: 80px;"></div>

<style>
#modalReporte { padding: 5vh 3vw; overflow: hidden; backdrop-filter: blur(4px); }
#modalReporte .ps-modal-report__dialog { width: 100%; max-width: 1600px; height: 90vh; min-height: 0; overflow: hidden; border-radius: 16px; }
#modalReporte .ps-modal-report__header { flex-shrink: 0; padding: 18px 24px; background: linear-gradient(110deg,#193b52,#28536a); }
#modalReporte .ps-modal-report__header h3 { font-size: 19px; font-weight: 600; }
#modalReporte .ps-modal-report__close { width: 36px; height: 36px; border-radius: 9px; background: #ffffff18; }
#modalReporte .ps-modal-report__frame { width: 100%; min-height: 0; flex: 1 1 0; background: #f1f5f9; }
</style>
<!-- ═══ MODAL REPORTE (iframe) ═══ -->
<div id="modalReporte" class="ps-modal-report">
  <div class="ps-modal-report__dialog">
    <div class="ps-modal-report__header">
      <h3><i class="fa fa-bar-chart"></i> Reporte de Procesos No Cerrados</h3>
      <button type="button" class="ps-modal-report__close" id="closeReporte">&times;</button>
    </div>
    <iframe id="iframeReporte" class="ps-modal-report__frame" src=""></iframe>
  </div>
</div>

<!-- ═══ FLOATING PANEL como Bootstrap Modal ═══ -->
<div class="modal fade" id="floatModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" id="floatModalDialog" role="document">
    <div class="modal-content ps-floating">
      <div class="ps-floating__title" id="floatTitle">
        <span class="ps-floating__title-text" id="floatTitleText">Programación</span>
        <div class="ps-floating__btns">
          <button type="button" class="ps-floating__btn ps-floating__btn-max" id="floatMax" title="Maximizar">
            <i class="fa fa-expand"></i>
          </button>
          <button type="button" class="ps-floating__btn ps-floating__btn-close" id="floatClose" data-dismiss="modal" title="Cerrar">
            <i class="fa fa-times"></i>
          </button>
        </div>
      </div>
      <div class="ps-floating__content">
    <div class="ps-floating__actions"><button type="button" class="ps-float-btn open" id="floatSyncSap" title="Recalcular y confirmar en SAP el avance de este ítem"><i class="fa fa-refresh"></i> Sincronizar SAP</button>
      <button type="button" class="ps-float-btn add" id="floatAddRow">
        <i class="fa fa-plus"></i> Agregar Fila
      </button>
      <button type="button" class="ps-float-btn open" id="floatOpenWeeks">
        <i class="fa fa-unlock"></i> Abrir Seleccionadas
      </button>
      <button type="button" class="ps-float-btn close-all" id="floatCloseAll">
        <i class="fa fa-lock"></i> Cerrar Todas
      </button>
    </div>
    <div class="float-select-all-wrapper">
      <input type="checkbox" id="floatSelectAllChk" />
      <label for="floatSelectAllChk">Seleccionar todas</label>
    </div>
    <div id="floatTableWrap">
      <table class="ps-float-table" id="floatTable">
        <thead>
          <tr>
            <th style="width:30px"></th>
            <th>Año</th>
            <th>Semana</th>
            <th>Prog.HH</th>
            <th>Horas</th>
            <th>Av. Físico</th>
            <th>Fabric.</th>
            <th style="width:13%">Cerrar Semana</th>
            <th style="width:10%">Eliminar</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <button type="button" class="ps-float-btn save" id="floatSave" style="margin-top:10px;width:100%;text-align:center;justify-content:center">
      <i class="fa fa-floppy-o"></i> 💾 GUARDAR DATOS
    </button>
      </div>
    </div><!-- /modal-content -->
  </div><!-- /modal-dialog -->
</div><!-- /floatModal -->

<?php include_once dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>

<!-- ═══ Variables PHP → JS ═══ -->
<script>
  window.allSemanas  = <?php echo $all_semana_json; ?>;
  window.allanio     = <?php echo $all_anio_json; ?>;
  window.currentYear = new Date().getFullYear();
</script>
<script src="../../assets/js/programarsemana1.js?v=<?php echo time(); ?>"></script>
