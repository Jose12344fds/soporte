<?php
$page_title = 'Validar - v2';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_permiso('hh-meta', 'ver');
$fhCerradas = find_by_sql("SELECT DISTINCT ot FROM fh_ing_planos_cab WHERE estado=3");
$fhCodigosCerrados = array_column($fhCerradas, 'ot');
$all_problema = find_all('tipoproblema');
$Correos = find_by_Correo('usuarios', 1);
include_once dirname(dirname(__DIR__)) . '/layouts/header.php';

// Rutas API
$apiBase = '../../api/validar/';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="../../assets/css/validar1.css?v=4">

<!-- ════════════════════════════════════════════════════════
     HTML STRUCTURE
     ════════════════════════════════════════════════════════ -->
<div class="container-fluid" style="padding: 0 20px;">

  <!-- HERO -->
  <div class="hero-header">
    <div>
      <h4><i class="fa fa-check-circle"></i> Validación de Órdenes de Trabajo</h4>
      <p>Haz clic en una fila para ver items &bull; Doble clic en celda para editar &bull; Exporta a SAP desde el botón</p>
    </div>
    <span class="hero-badge" id="badgeCount"><i class="fa fa-spinner fa-spin"></i> Cargando...</span>
  </div>

  <!-- ═══ TABLA PRINCIPAL: OTs ═══ -->
  <div class="vcard">
    <div class="vcard-header primary">
      <span><i class="fa fa-list"></i> Lista de OTs en Proceso <span class="v-header-count" id="otCount"></span></span>
      <div class="v-search-wrap">
        <i class="fa fa-search v-search-icon"></i>
        <input type="text" id="otSearch" class="v-search-input" placeholder="Buscar OT, Proyecto, Descripción..." autocomplete="off">
        <button type="button" class="v-search-clear" id="otSearchClear" title="Limpiar">&times;</button>
      </div>
      <small class="v-header-hint">Clic en fila → ver items</small>
    </div>
    <div class="vcard-body">
      <div class="table-responsive">
        <table id="tblOTs" class="table table-striped table-bordered" style="width:100%">
          <thead>
            <tr>
              <th style="width:6%">ID</th>
              <th style="width:10%">Proyecto</th>
              <th style="width:32%">Descripción</th>
              <th style="width:8%">Correo</th>
              <th style="width:10%">Exportar</th>
              <th style="width:10%">Estado</th>
              <th style="width:10%">Acción</th>
              <th style="width:12%">Proyecto de Ingeniería</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ═══ SUBCONSULTA: Items con procesos ═══ -->
  <div class="vcard" id="cardSub">
    <div class="vcard-header success">
      <span><i class="fa fa-th"></i> Detalle de Items — <small id="subTitle" style="opacity:.8">selecciona una OT arriba</small></span>
      <small class="v-header-hint"><i class="fa fa-pencil"></i> Doble clic en celda para editar</small>
      <button type="button" class="btn-toggle-pe" id="btnTogglePE" title="Mostrar/Ocultar Procesos Especiales">
        <span class="icon">+</span>
        <span>Proc. Especiales</span>
        <span class="pe-badge">4</span>
      </button>
    </div>
    <div class="vcard-body" style="padding: 8px; overflow-x: auto;">
      <!-- Pista para el usuario -->
      <div id="subHint" class="text-center text-muted" style="padding:30px 0;">
        <i class="fa fa-hand-pointer-o fa-2x" style="color:#ccc;"></i>
        <p style="margin-top:10px;">Selecciona una OT de la tabla superior para ver sus items</p>
      </div>
      <table id="tblSub" class="table table-striped table-bordered" style="width:115%; min-width:1200px; display:none;">
        <thead>
          <!-- Fila grupo -->
          <tr>
            <th colspan="1"></th><th colspan="1"></th><th colspan="1"></th>
            <th colspan="1"></th><th colspan="1"></th><th colspan="1"></th>
            <th colspan="4" class="th-hab text-center">HABILITADO META</th>
            <th colspan="11" class="th-fab text-center">FABRICACIÓN HH META</th>
            <th colspan="4" class="th-acab text-center">ACABADOS HH META</th>
            <th colspan="4" class="th-pe text-center col-pe-header" style="display:none;">PROCESOS ESPECIALES</th>
            <th colspan="1" class="th-tot text-center">TOTAL</th>
          </tr>
          <!-- Fila campos -->
          <tr>
            <th>N°</th><th>Descripción</th><th>Cant</th>
            <th>HH.Ppto</th><th>Peso Ppto</th><th>Peso Sold.</th>
            <!-- HABILITADO (4) -->
            <th class="vtext th-hab">Pantógrafo Grande</th>
            <th class="vtext th-hab">Pantógrafo Chico</th>
            <th class="vtext th-hab">Hab. Perfiles</th>
            <th class="vtext th-hab">Limp. Mecánica</th>
            <!-- FABRICACION (11) -->
            <th class="vtext th-fab">Rolado</th>
            <th class="vtext th-fab">Torno Grande</th>
            <th class="vtext th-fab">Torno Chico</th>
            <th class="vtext th-fab">CNC Grande</th>
            <th class="vtext th-fab">CNC Chico</th>
            <th class="vtext th-fab">Armado</th>
            <th class="vtext th-fab">Soldeo</th>
            <th class="vtext th-fab">Plantillado</th>
            <th class="vtext th-fab">Taladro</th>
            <th class="vtext th-fab">Limp. Mecánica</th>
            <th class="vtext th-fab">Ensamble</th>
            <!-- ACABADOS (4) -->
            <th class="vtext th-acab">Granallado</th>
            <th class="vtext th-acab">Pintura</th>
            <th class="vtext th-acab">Decap./Pasiv.</th>
            <th class="vtext th-acab">Embalaje</th>
            <!-- PROC ESPECIALES (4) — DataTables maneja visibilidad via visible:false -->
            <th class="vtext th-pe col-pe">Trat.Térmico</th>
            <th class="vtext th-pe col-pe">Mecanizado</th>
            <th class="vtext th-pe col-pe">Revestimiento</th>
            <th class="vtext th-pe col-pe">Varios</th>
            <!-- TOTAL -->
            <th class="vtext th-tot">Horas Total</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</div><!-- /container-fluid -->

<!-- ═══════════════════════════════════════════════════════
     MODAL EXPORTAR SAP
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalExportar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl-custom" role="document">
    <div class="modal-content modal-export">

      <div class="modal-header export-head">
        <div class="export-title-wrap">
          <span class="export-title-icon"><i class="fa fa-cloud-upload"></i></span>
          <div>
            <span class="export-eyebrow">Integracion SAP</span>
            <h5 class="modal-title">Exportar datos de produccion</h5>
            <p>Selecciona un item, valida sus horas y confirma el envio.</p>
          </div>
        </div>
        <button type="button" class="close export-close" data-dismiss="modal" aria-label="Cerrar">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body export-body">
        <div class="row">
          <!-- IZQUIERDA: Info + Items + Botón -->
          <div class="col-lg-6 export-column">
            <!-- Info proyecto -->
            <div class="vcard export-card export-card-project">
              <div class="vcard-header purple">
                <span><i class="fa fa-info-circle"></i> Información del Proyecto</span>
              </div>
              <div class="vcard-body" style="padding:0;">
                <table id="tblModalInfo" class="table table-striped" style="width:100%; margin:0;">
                  <thead><tr>
                    <th class="text-center"><i class="fa fa-hashtag"></i> ID Padre</th>
                    <th class="text-center"><i class="fa fa-code"></i> Proyecto</th>
                    <th class="text-center"><i class="fa fa-file-text"></i> Nombre</th>
                  </tr></thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
            <!-- Items del proyecto -->
            <div class="vcard export-card export-card-items">
              <div class="vcard-header purple">
                <span><i class="fa fa-list"></i> ITEMS DEL PROYECTO</span>
                <small style="opacity:.7">Clic en item → ver cruce a la derecha</small>
              </div>
              <div class="vcard-body" style="padding:0;">
                <table id="tblModalItems" class="table table-striped" style="width:100%; margin:0;">
                  <thead><tr>
                    <th class="text-center" style="width:10%">N°</th>
                    <th class="text-center" style="width:30%">Cod</th>
                    <th class="text-center" style="width:60%">Items</th>
                  </tr></thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
            <!-- Botón enviar -->
          </div>

          <!-- DERECHA: Tabla cruzada -->
          <div class="col-lg-6 export-column">
            <div class="vcard export-card export-card-review">
              <div class="vcard-header purple">
                <span><i class="fa fa-clock-o"></i> Horas y Cantidad Planificada</span>
              </div>
              <div class="vcard-body" style="padding:0;">
                <!-- Pista para seleccionar item -->
                <div id="cruceHint" class="text-center text-muted export-empty-state">
                  <i class="fa fa-arrow-left fa-2x" style="color:#ccc;"></i>
                  <p style="margin-top:10px;">Selecciona un item de la izquierda para ver el cruce de datos</p>
                </div>
                <table id="tblCruce" class="table table-striped" style="width:100%; margin:0; display:none;">
                  <thead><tr>
                    <th class="text-center"><i class="fa fa-users"></i> Grupo</th>
                    <th class="text-center"><i class="fa fa-cogs"></i> Proceso</th>
                    <th class="text-center"><i class="fa fa-clock-o"></i> Hr.Meta</th>
                    <th class="text-center"><i class="fa fa-calculator"></i> Cant.Planif.</th>
                  </tr></thead>
                  <tfoot><tr>
                    <th colspan="2"><i class="fa fa-bar-chart"></i> Totales</th>
                    <th class="text-center" id="totalHrMeta">0</th>
                    <th class="text-center" id="totalCantPlan">0</th>
                  </tr></tfoot>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer export-foot">
        <div class="export-foot-note">
          <i class="fa fa-shield"></i>
          <span><strong>Revision final</strong><small>Confirma que las horas y cantidades sean correctas.</small></span>
        </div>
        <button id="btnEnviarSAP" class="btn-enviar-sap">
          <span>Enviar datos a SAP</span><i class="fa fa-arrow-right"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<?php include_once dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>

<!-- ════════════════════════════════════════════════════════
     JAVASCRIPT — Después de footer.php (jQuery ya cargado)
     ════════════════════════════════════════════════════════ -->
<link rel="stylesheet" href="../../assets/css/fh_revision.css?v=20261027">
<link rel="stylesheet" href="../../assets/css/fh_proyecto_consulta.css?v=20261027b">
<script src="../../assets/js/fh_revision.js?v=20261027"></script>
<script src="../../assets/js/fh_proyecto_consulta.js?v=20261027b"></script>
<script>
window.FH_CLOSED_OT=Object.fromEntries((<?= json_encode($fhCodigosCerrados,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>).map(function(ot){return [FHClosedProject.normalize(ot),ot];}));
</script>
<script>
$(function () {
  'use strict';

  /* ── Helpers SweetAlert2 ── */
  function toast(icon, title, text) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({ icon: icon, title: title, text: text, timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
    } else { alert(title + (text ? ': ' + text : '')); }
  }
  function confirmAction(title, text) {
    if (typeof Swal !== 'undefined') {
      return Swal.fire({
        title: title, text: text, icon: 'question',
        showCancelButton: true, confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar', confirmButtonColor: '#1cc88a'
      }).then(function(r) { return r.isConfirmed; });
    }
    return Promise.resolve(confirm(title + '\n' + (text || '')));
  }

  /* ── Rutas API ── */
  const API = {
    otsActivas:     '<?php echo $apiBase; ?>get_ots_activas.php',
    itemsOT:        '<?php echo $apiBase; ?>get_items_ot.php',
    itemsValidar:   '<?php echo $apiBase; ?>get_items_validar.php',
    procesosItem:   '<?php echo $apiBase; ?>get_procesos_item.php',
    recursosSAP:    '<?php echo $apiBase; ?>get_recursos_sap.php',
    updateCelda:    '<?php echo $apiBase; ?>update_celda.php',
    cambiarEstado:  '<?php echo $apiBase; ?>cambiar_estado.php',
    exportarSAP:    '<?php echo $apiBase; ?>exportar_sap.php'
  };

  /* ── Estado global ── */
  let subQueryResultsCompa = [];   // procesos locales (consulta16)
  let subQueryData11       = [];   // recursos SAP
  let selectedRowData      = {};   // fila seleccionada en modal
  let Codo2                = '';   // CodPdre seleccionado

  /* ════════════════════════════════════════════
     1. TABLA PRINCIPAL — OTs activas
     ════════════════════════════════════════════ */
  const mainTable = $('#tblOTs').DataTable({
    lengthMenu: [[10, 20, -1], [10, 20, 'Todos']],
    responsive: true,
    dom: 'rtip',
    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
    ajax: {
      url: API.otsActivas,
      method: 'POST',
      dataSrc: function(json) {
        if (!json || !Array.isArray(json)) {
          console.error('Respuesta OTs inválida:', json);
          toast('error', 'Error al cargar OTs', json && json.message ? json.message : 'Respuesta no válida');
          return [];
        }
        return json;
      },
      error: function(xhr, status, err) {
        console.error('Error AJAX OTs:', status, err, xhr.responseText);
        toast('error', 'Error de conexión', 'No se pudo cargar la lista de OTs.');
      }
    },
    columns: [
      { data: 'IdPadre' },
      { data: 'ProjectCode' },
      { data: 'NPdre' },
      { data: 'IdPadre', render: () =>
          '<button class="btn-acc" disabled title="Envio de correo pendiente de configuracion"><i class="fa fa-envelope"></i> Correo</button>' },
      { data: 'IdPadre', render: id =>
          `<button class="btn-acc exportar" data-id="${id}"><i class="fa fa-upload"></i> Exportar SAP</button>` },
      { data: 'Estado_OT', render: v => {
          if (v == 0) return '<span class="badge-estado-proceso deshabilitado">DESHABILITADO</span>';
          if (v == 1) return '<span class="badge-estado-proceso en-proceso">EN PROCESO</span>';
          return v;
      }},
      { data: 'IdPadre', render: id =>
          `<button class="btn-acc cambiar" data-id="${id}"><i class="fa fa-check"></i> Validar</button>` }
      ,{data:null,orderable:false,searchable:false,render:function(data,type,row){
          var ot=window.FH_CLOSED_OT[FHClosedProject.normalize(row.ProjectCode)];
          if(type!=='display')return ot?'Proyecto cerrado':'';
          return ot?'<button type="button" class="btn-acc fhc-open-project" data-ot="'+FHClosedProject.escape(ot)+'" title="Consultar expediente cerrado, procesos y planos"><i class="fa fa-folder-open"></i> Ver proyecto</button>':'<span class="text-muted" title="Sin proyecto de Ingeniería cerrado para esta OT">—</span>';
      }}
    ],
    drawCallback: function () {
      var total  = this.api().rows().count();
      var filter = this.api().rows({ search: 'applied' }).count();
      var txt = total === filter ? total + ' OTs' : filter + ' / ' + total;
      $('#badgeCount').html('<i class="fa fa-database"></i> ' + total + ' OTs en proceso');
      $('#otCount').text(txt);
    }
  });

  /* ── Click en fila → cargar subconsulta ── */
  $('#tblOTs tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('button').length) return;
    $('#tblOTs tbody tr').removeClass('fila-activa');
    $(this).addClass('fila-activa');
    var d = mainTable.row(this).data();
    if (d) {
      $('#subHint').hide();
      $('#tblSub').show();
      $('#subTitle').text('OT: ' + d.ProjectCode + ' — ' + d.NPdre);
      fetchSubQuery(d.IdPadre);
    }
  });

  /* ── Botón Correo ── */
  $('#tblOTs tbody').on('click', '.correo', function (e) {
    e.stopPropagation();
    var id = $(this).data('id');
    confirmAction('¿Enviar correo?', 'Se enviará un correo con la información de esta OT.').then(function(ok) {
      if (!ok) return;
      $.post(API.enviarCorreo, { id: id }, function() {
        toast('success', 'Correo enviado', 'Se envió correctamente.');
      }).fail(function() { toast('error', 'Error', 'No se pudo enviar el correo.'); });
    });
  });

  /* ── Botón Exportar SAP → Abrir modal ── */
  $('#tblOTs tbody').on('click', '.exportar', function (e) {
    e.stopPropagation();
    const row = $(this).closest('tr');
    const d   = mainTable.row(row).data();
    if (!d) return;

    // Cargar info en modal
    dtModalInfo.clear().row.add(d).draw();
    fetchItemsModal(d.IdPadre);

    // Limpiar tabla cruce si existe
    if (dtCruce) { dtCruce.clear().draw(); }
    $('#cruceHint').show();
    $('#tblCruce').hide();

    setTimeout(function(){ $('#modalExportar').modal('show'); }, 80);
  });

  /* ── Botón Cambiar Estado ── */
  $('#tblOTs tbody').on('click', '.cambiar', function (e) {
    e.stopPropagation();
    var id = $(this).data('id');
    confirmAction('¿Validar esta OT?', 'El estado cambiará a Validado.').then(function(ok) {
      if (!ok) return;
      $.post(API.cambiarEstado, { IdHijOT: id }, function (resp) {
        var r = typeof resp === 'string' ? JSON.parse(resp) : resp;
        if (r.success) { toast('success', 'Validado', r.message); } else { toast('error', 'Error', r.message); }
        mainTable.ajax.reload(null, false);
        fetchSubQuery();
      }).fail(function() { toast('error', 'Error', 'No se pudo cambiar el estado.'); });
    });
  });

  /* ── Buscador personalizado en franja azul ── */
  var otSearchTimer;
  $('#otSearch').on('input', function () {
    var val = $(this).val();
    $('#otSearchClear').toggle(val.length > 0);
    clearTimeout(otSearchTimer);
    otSearchTimer = setTimeout(function () { mainTable.search(val).draw(); }, 250);
  });
  $('#otSearchClear').on('click', function () {
    $('#otSearch').val('');
    $(this).hide();
    mainTable.search('').draw();
  });

  /* ════════════════════════════════════════════
     2. SUBCONSULTA — Items con procesos editables
     ════════════════════════════════════════════ */

  /* ── Helper: render badge coloreado por grupo ── */
  function renderNum(grp) {
    return function (data, type) {
      if (type !== 'display') return data == null ? 0 : data;
      var val = parseFloat(data) || 0;
      if (val === 0) return '<span class="val-zero">—</span>';
      return '<span class="val-pos val-pos-' + grp + '">' + val + '</span>';
    };
  }

  var subTable = $('#tblSub').DataTable({
    columns: [
      { data: 'ITEM', defaultContent: '' },
      { data: 'NPdre', defaultContent: '' },
      { data: 'CANTI', defaultContent: '' },
      { data: 'HHFab', defaultContent: '0' },
      { data: 'PesPptoTotaKg', defaultContent: '0' },
      { data: 'PeSoldaduraKg', defaultContent: '0' },
      // Habilitado
      { data: 'MO001_1', defaultContent: '0' },
      { data: 'MO001_2', defaultContent: '0' },
      { data: 'MO002', defaultContent: '0' },
      { data: 'MO004', defaultContent: '0' },
      // Fabricación
      { data: 'MO014', defaultContent: '0' },
      { data: 'MO008_1', defaultContent: '0' },
      { data: 'MO008_2', defaultContent: '0' },
      { data: 'MO007_1', defaultContent: '0' },
      { data: 'MO007_2', defaultContent: '0' },
      { data: 'MO003', defaultContent: '0' },
      { data: 'MO015', defaultContent: '0' },
      { data: 'MO005', defaultContent: '0' },
      { data: 'MO009', defaultContent: '0' },
      { data: 'MO004P', defaultContent: '0' },
      { data: 'MO006', defaultContent: '0' },
      // Acabados
      { data: 'MO012', defaultContent: '0' },
      { data: 'MO011', defaultContent: '0' },
      { data: 'MO020', defaultContent: '0' },
      { data: 'MO010', defaultContent: '0' },
      // Procesos Especiales
      { data: 'MO050', visible: false, defaultContent: '0', className: 'col-pe cell-edit' },
      { data: 'MO060', visible: false, defaultContent: '0', className: 'col-pe cell-edit' },
      { data: 'MO070', visible: false, defaultContent: '0', className: 'col-pe cell-edit' },
      { data: 'MO080', visible: false, defaultContent: '0', className: 'col-pe' },
      // SUT
      { data: 'SUT', defaultContent: '0', render: function(v, type) {
          if (type !== 'display') return v != null ? parseFloat(v) || 0 : 0;
          var val = v != null ? parseFloat(v) || 0 : 0;
          if (val === 0) return '<span class="val-zero">—</span>';
          return '<span class="val-pos val-pos-tot">' + val.toFixed(1) + '</span>';
      }}
    ],
    columnDefs: [
      { targets: [0], className: 'col-num text-center' },
      { targets: [1], className: 'col-desc', render: function(d, t) {
          if (t !== 'display' || !d) return d || '';
          return '<span title="' + d.replace(/"/g, '&quot;') + '">' + d + '</span>';
      }},
      { targets: [2,3,4,5], className: 'col-info' },
      { targets: [6,7,8,9],                          className: 'cell-edit cell-hab',  render: renderNum('hab')  },
      { targets: [10,11,12,13,14,15,16,17,18,19,20], className: 'cell-edit cell-fab',  render: renderNum('fab')  },
      { targets: [21,22,23,24],                       className: 'cell-edit cell-acab', render: renderNum('acab') },
      { targets: [25,26,27,28],                       className: 'cell-edit cell-pe',   render: renderNum('pe')   },
      { targets: [29], className: 'cell-total' }
    ],
    order: [[0, 'asc']],
    paging: false,
    searching: false,
    info: false,
    language: { emptyTable: 'Selecciona una OT para ver sus items' }
  });

  var currentIdPadre = null;

  function fetchSubQuery(idPadre) {
    if (idPadre) currentIdPadre = idPadre;
    if (!currentIdPadre) return;
    console.log('[validar1] fetchSubQuery → idPadre:', currentIdPadre);
    $.ajax({
      url: API.itemsValidar,
      method: 'POST',
      data: { idPadre: currentIdPadre, tipoConsulta: 1 },
      dataType: 'json',
      success: function (data) {
        console.log('[validar1] Respuesta items:', data);
        if (data && Array.isArray(data)) {
          subTable.clear().rows.add(data).draw();
          subTable.columns.adjust();
          // Auto-scroll al bloque de detalle para que el footer quede debajo
          setTimeout(function () {
            $('html, body').animate({ scrollTop: $('#cardSub').offset().top - 72 }, 380);
          }, 100);
        } else {
          subTable.clear().draw();
          console.warn('[validar1] Respuesta no es array:', data);
          toast('warning', 'Sin items', 'No se encontraron items para esta OT.');
        }
      },
      error: function (xhr, status, err) {
        console.error('[validar1] Error subconsulta:', status, err);
        console.error('[validar1] Response text:', xhr.responseText);
        subTable.clear().draw();
        toast('error', 'Error', 'No se pudieron cargar los items. Revisa la consola para más detalle.');
      }
    });
  }

  /* ── Toggle Procesos Especiales ── */
  $('#btnTogglePE').on('click', function () {
    const $btn = $(this);
    const show = !$btn.hasClass('active');
    [25, 26, 27, 28].forEach(i => subTable.column(i).visible(show));
    $btn.toggleClass('active', show).find('.icon').text(show ? '×' : '+');
    $('.col-pe-header').toggle(show);
  });

  /* ── Inline editing (dblclick) ── */
  const editableFields = [
    'MO001_1','MO001_2','MO002','MO004',
    'MO014','MO008_1','MO008_2','MO007_1','MO007_2','MO003',
    'MO015','MO005','MO009','MO004P','MO006',
    'MO012','MO011','MO020','MO010',
    'MO050','MO060','MO070','MO080'
  ];

  $('#tblSub tbody').on('dblclick', 'td', function () {
    const cell = subTable.cell(this);
    const ci   = cell.index().column;
    const rowD = subTable.row(cell.index().row).data();

    // Columnas editables van de 6 a 28 (índices), SUT(29) no editable
    if (ci < 6 || ci > 28) return;
    const fieldName = editableFields[ci - 6];
    if (!fieldName) return;

    var origVal = rowD[fieldName] || 0;
    var $td = $(this);
    if ($td.find('input').length) return;
    $td.css({ 'background': '#fff3cd', 'padding': '2px' });

    var $input = $('<input type="number" step="0.1">')
      .css({ width: '100%', textAlign: 'center', fontSize: '12px', border: '2px solid #4e73df', borderRadius: '4px', padding: '4px', background: '#fff' })
      .val(origVal)
      .on('blur', function () {
        var nv = $(this).val();
        $td.css({ 'background': '', 'padding': '' });
        if (nv !== '' && nv !== String(origVal)) {
          $.post(API.updateCelda, {
            IdHijOT: rowD.IdHijOT,
            fieldName: fieldName,
            newValue: nv
          }, function (resp) {
            var r = typeof resp === 'string' ? JSON.parse(resp) : resp;
            if (r.success) {
              cell.data(nv).draw(false);
              toast('success', 'Guardado', fieldName + ' actualizado a ' + nv);
              fetchSubQuery();
            } else {
              toast('error', 'Error', r.message);
              cell.data(origVal).draw(false);
            }
          }, 'json').fail(function() { toast('error', 'Error', 'No se pudo guardar.'); cell.data(origVal).draw(false); });
        } else {
          cell.data(origVal).draw();
        }
      })
      .on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $(this).blur(); }
        if (e.key === 'Escape') { $(this).val(origVal); $(this).blur(); }
        if (e.key === 'Tab') {
          e.preventDefault(); $(this).blur();
          var $next = $td.next('td');
          if ($next.length) setTimeout(function(){ $next.trigger('dblclick'); }, 50);
        }
      });

    $td.html($input);
    $input.focus().select();
  });

  /* ════════════════════════════════════════════
     3. MODAL EXPORTAR — DataTables
     ════════════════════════════════════════════ */

  /* ── Info proyecto ── */
  const dtModalInfo = $('#tblModalInfo').DataTable({
    responsive: true,
    paging: false, searching: false, info: false,
    dom: 't',
    columns: [
      { data: 'IdPadre' },
      { data: 'ProjectCode' },
      { data: 'NPdre' }
    ]
  });

  /* ── Items del proyecto ── */
  const dtModalItems = $('#tblModalItems').DataTable({
    searching: false, info: false, paging: false,
    columns: [
      { data: 'ITEM' },
      { data: 'CodPdre' },
      { data: 'NPdre' },
      { data: 'ProjectCode', visible: false },
      { data: 'IdPadreOT', visible: false },
      { data: 'IdHijOT', visible: false }
    ]
  });

  function fetchItemsModal(idPadre) {
    dtModalItems.clear().draw();
    $.post(API.itemsOT, { idPadre: idPadre }, function (data) {
      if (data && Array.isArray(data) && data.length) {
        dtModalItems.clear().rows.add(data).draw();
      }
    }, 'json').fail(function(err) { console.error('Error items modal:', err); toast('error', 'Error', 'No se pudieron cargar los items del proyecto.'); });
  }

  /* ── Click en item del modal → cargar procesos + recursos ── */
  $('#tblModalItems tbody').on('click', 'tr', function () {
    $('#tblModalItems tbody tr').removeClass('item-activo');
    $(this).addClass('item-activo');

    var d = dtModalItems.row(this).data();
    if (!d) return;

    var idPadre   = d.IdPadre || d.IdPadreOT;
    var codPdre   = d.CodPdre;
    var proyecto  = d.OTPADRE || d.ProjectCode;
    var idHijOT   = d.IdHijOT;

    selectedRowData = { idPadre: idPadre, codPdre: codPdre, Proyectoow: proyecto, Code1: codPdre, Code2: idHijOT };

    if (idPadre && codPdre) {
      $('#cruceHint').hide();
      $('#tblCruce').show();
      fetchAndCombineData(idPadre, codPdre, codPdre, proyecto, idHijOT);
      fetchProcesosLocal(idPadre, codPdre, idHijOT);
      fetchRecursosSAP(codPdre, proyecto);
    }
  });

  /* ── Fetch procesos locales (consulta16 equiv.) ── */
  async function fetchProcesosLocal(idPadre, codPdre, idHijOT) {
    try {
      const resp = await $.post(API.procesosItem, { idPadre, codPdre, idHijOT }, null, 'json');
      subQueryResultsCompa = resp && resp.length ? resp : [];
    } catch (e) {
      subQueryResultsCompa = [];
      console.error('Error procesos locales:', e);
    }
  }

  /* ── Fetch recursos SAP (HANA) ── */
  async function fetchRecursosSAP(itemCode, proyecto) {
    Codo2 = itemCode;
    try {
      const resp = await $.post(API.recursosSAP, { ItemCod: itemCode, proy: proyecto }, null, 'json');
      if (resp && resp.length) {
        subQueryData11 = resp.map(r => ({
          StageId: r.StageId, ChildNum: r.ChildNum,
          ResName: r.ResName, Code: r.Code, Quantity: r.Quantity
        }));
      } else {
        subQueryData11 = [];
      }
    } catch (e) {
      subQueryData11 = [];
      console.error('Error recursos SAP:', e);
    }
  }

  /* ════════════════════════════════════════════
     4. ENVIAR A SAP — Matching + PATCH
     ════════════════════════════════════════════ */
  $('#btnEnviarSAP').on('click', function () {
    if (!Codo2 || !subQueryData11.length) {
      toast('warning', 'Sin datos', 'Selecciona un item y carga los datos antes de enviar.');
      return;
    }
    useSubQueryData(Codo2, subQueryData11);
  });

  /**
   * Algoritmo de matching 3 niveles:
   *  1) Exacto por StageId + Code
   *  2) StageId null + mismo Code + mismo nombre
   *  3) Fallback: mismo Code libre
   */
  function useSubQueryData(itemCode, sapData) {
    const norm = s => (s || '').toString().trim().toUpperCase();

    // Pool SAP con flag _used
    const sapPool = sapData.map((item, idx) => ({
      StageId:  item.StageId,
      ChildNum: item.ChildNum,
      Code:     item.Code,
      ResName:  item.ResName,
      Quantity: parseFloat(item.Quantity) === 0 ? 0.001 : item.Quantity,
      _used: false,
      _idx: idx
    }));

    const warnings = [];

    subQueryResultsCompa.forEach(local => {
      const code     = norm(local.proceso);
      const stageRaw = local.orden != null && local.orden !== '' ? local.orden : local.ordenes;
      const stageNum = stageRaw == null || stageRaw === '' ? null : Number(stageRaw);
      const localQtyRaw = local.Valor1 !== undefined ? local.Valor1 : local.Valor;
      const localQty = parseFloat(localQtyRaw) === 0 ? 0.001 : localQtyRaw;

      let match = null;

      // 1) Exacto StageId + Code
      if (stageNum !== null) {
        match = sapPool.find(x => !x._used && x.StageId != null && Number(x.StageId) === stageNum && norm(x.Code) === code);
      }
      // 2) StageId null + nombre
      if (!match) {
        match = sapPool.find(x => !x._used && x.StageId == null && norm(x.Code) === code && norm(x.ResName) === norm(local.Campo));
      }
      // 3) Fallback mismo Code
      if (!match) {
        match = sapPool.find(x => !x._used && norm(x.Code) === code);
      }

      if (match) {
        match._used = true;
        match.Quantity = localQty;
      } else {
        warnings.push(`Sin match SAP para ${local.proceso} stage ${stageNum}`);
      }
    });

    if (warnings.length) console.warn('SAP export advertencias:', warnings);

    // Payload
    const updatedLines = sapPool.map(item => ({
      ChildNum: item.ChildNum,
      ItemCode: item.Code,
      Quantity: item.Quantity
    }));
    const body = { TreeType: 'iProductionTree', ProductTreeLines: updatedLines };

    $.ajax({
      url: API.exportarSAP,
      method: 'POST',
      dataType: 'json',
      data: { var_json: JSON.stringify(body), ord_dato: itemCode },
      success: function (r) {
        if (r.success) {
          toast('success', 'Exportado', r.message);
          refreshCrossedData();
        } else {
          toast('error', 'Error SAP', r.message);
        }
      },
      error: function() { toast('error', 'Error', 'No se pudo conectar con SAP.'); }
    });
  }

  /* ════════════════════════════════════════════
     5. TABLA CRUZADA — Hr.Meta vs Cant.Planificada
     ════════════════════════════════════════════ */
  let dtCruce = null;

  function refreshCrossedData() {
    if (selectedRowData.idPadre && selectedRowData.codPdre) {
      const { idPadre, codPdre, Proyectoow, Code1, Code2 } = selectedRowData;
      fetchAndCombineData(idPadre, codPdre, Code1, Proyectoow, Code2);
    }
  }

  async function fetchAndCombineData(idPadre, codPdre, Code1, proyecto, idHijOT) {
    try {
      const [resp2, resp6] = await Promise.all([
        $.post(API.procesosItem, { idPadre, codPdre, idHijOT }, null, 'json'),
        $.post(API.recursosSAP,  { ItemCod: Code1, proy: proyecto }, null, 'json')
      ]);

      if (!resp2 || !resp6) { console.error('Datos vacíos en cruce.'); return; }

      const norm = s => (s || '').toString().trim().toUpperCase();
      const crossedData = [];

      // Pool SAP por código
      const sapPools = {};
      resp6.forEach((item, idx) => {
        const c = norm(item.Code);
        if (!sapPools[c]) sapPools[c] = [];
        sapPools[c].push({ ...item, _used: false, _idx: idx });
      });
      Object.values(sapPools).forEach(pool =>
        pool.sort((a, b) => {
          const sa = a.StageId == null ? 9999 : Number(a.StageId);
          const sb = b.StageId == null ? 9999 : Number(b.StageId);
          return sa !== sb ? sa - sb : a._idx - b._idx;
        })
      );

      resp2.forEach(item2 => {
        const code     = norm(item2.proceso);
        const stage    = item2.orden != null ? String(item2.orden) : '';
        const stageNum = stage === '' ? null : Number(stage);
        const pool     = sapPools[code] || [];
        let match      = null;

        if (stage !== '') match = pool.find(x => !x._used && x.StageId != null && Number(x.StageId) === stageNum);
        if (!match)       match = pool.find(x => !x._used && x.StageId == null && norm(x.ResName) === norm(item2.Campo));
        if (!match)       match = pool.find(x => !x._used);

        if (match) {
          match._used = true;
          crossedData.push({
            Grupo: item2.Campo,
            Proceso: item2.proceso,
            HrMetaOperaciones: parseFloat(item2.Valor),
            CantidadPlanificada: parseFloat(match.Quantity),
            ordenes: item2.ordenes
          });
        }
      });

      renderCrossedTable(crossedData);
    } catch (e) {
      console.error('Error cruce datos:', e);
    }
  }

  function renderCrossedTable(data) {
    if (dtCruce) {
      dtCruce.clear().rows.add(data).draw();
    } else {
      dtCruce = $('#tblCruce').DataTable({
        data: data,
        columns: [
          { data: 'Grupo' },
          { data: 'Proceso' },
          {
            data: 'HrMetaOperaciones',
            render: function (value, type) {
              const number = parseFloat(value) || 0;
              return type === 'display' || type === 'filter' ? number.toFixed(2) : number;
            }
          },
          { data: 'CantidadPlanificada' },
          { data: 'ordenes', visible: false }
        ],
        searching: false, info: false, paging: false,
        order: [[4, 'asc']],
        footerCallback: function () {
          const api = this.api();
          const sumCol = idx => api.column(idx, { page: 'current' }).data()
            .reduce((a, b) => a + (parseFloat(b) || 0), 0);
          $('#totalHrMeta').text(sumCol(2).toFixed(2));
          $('#totalCantPlan').html(sumCol(3).toFixed(1));
        }
      });
    }
  }

  /* ════════════════════════════════════════════
     MODAL — Inicialización manual
     ════════════════════════════════════════════ */
  $('#modalExportar').modal({ backdrop: true, keyboard: true, show: false });

});
</script>
