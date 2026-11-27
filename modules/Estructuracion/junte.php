<?php
$page_title = 'Historial de Compras — Estructuración';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_permiso('estructuracion', 'ver');
include_once dirname(dirname(__DIR__)) . '/layouts/header.php';
?>

<style>
/* ── Layout base ──────────────────────────────────────────────── */
.est-page-wrap { padding: 18px 22px; }

/* ── Cards ────────────────────────────────────────────────────── */
.est-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    margin-bottom: 20px;
    overflow: hidden;
}
.est-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    color: #fff;
    font-weight: 700;
    font-size: .88rem;
}
.est-card-header i { opacity: .85; }
.est-card-body { padding: 18px 20px; }

/* ── Badge total ──────────────────────────────────────────────── */
.badge-total {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 24px;
    padding: 0 8px;
    background: rgba(255,255,255,.22);
    border: 1.5px solid rgba(255,255,255,.4);
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    border-radius: 12px;
}

/* ── Formulario búsqueda ──────────────────────────────────────── */
.est-search-row {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.est-search-row .est-field { display: flex; flex-direction: column; gap: 4px; }
.est-search-row label {
    font-size: .78rem;
    font-weight: 600;
    color: #374151;
}
.est-search-row .form-control {
    width: 100%;
    max-width: none;
    height: 44px;
    border-radius: 7px;
    border: 1.5px solid #d1d5db;
    font-size: .85rem;
}
.est-search-row .est-field { flex: 1; min-width: 280px; max-width: 620px; }
.est-search-row .btn-buscar { height:44px; padding-left:28px; padding-right:28px; }
.report-intro { display:grid; grid-template-columns:1.25fr repeat(3,1fr); gap:14px; margin-bottom:20px; }
.intro-main,.intro-step { background:#fff; border:1px solid #dce5ef; border-radius:11px; padding:18px; box-shadow:0 2px 10px rgba(15,35,60,.05); }
.intro-main { background:linear-gradient(135deg,#eef5ff,#f8fbff); }
.intro-main i { color:#2563eb; font-size:1.5rem; margin-bottom:10px; }
.intro-main h3 { margin:0 0 5px; color:#173b57; font-size:1rem; font-weight:700; }
.intro-main p,.intro-step p { margin:0; color:#64748b; font-size:.78rem; line-height:1.5; }
.intro-step span { display:grid; place-items:center; width:28px; height:28px; margin-bottom:9px; border-radius:8px; background:#173b57; color:#fff; font-size:.75rem; font-weight:700; }
.intro-step strong { display:block; color:#334155; font-size:.82rem; margin-bottom:3px; }
.report-kpis { display:grid; grid-template-columns:repeat(6,minmax(120px,1fr)); gap:10px; margin-bottom:14px; }
.report-kpi { padding:12px 14px; background:#f7fafc; border:1px solid #e2e8f0; border-radius:9px; }
.report-kpi span { display:block; color:#64748b; font-size:.66rem; font-weight:700; text-transform:uppercase; }
.report-kpi strong { display:block; margin-top:3px; color:#173b57; font-size:1.05rem; }
.table-tool-actions { display:flex; align-items:flex-end; gap:8px; margin-left:auto; }
.btn-report-tool { height:34px; border:1px solid #cbd5e1; border-radius:7px; padding:0 12px; background:#fff; color:#334155; font-size:.75rem; font-weight:700; }
.btn-report-tool:hover { border-color:#2563eb; color:#2563eb; }
#tblHistComp thead th { position:sticky; top:0; z-index:2; background:#eaf1f7; white-space:nowrap; }
.est-search-row .form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}
.est-hint {
    font-size: .74rem;
    color: #6b7280;
    margin-top: 2px;
}

/* ── Botón buscar ─────────────────────────────────────────────── */
.btn-buscar {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: 8px 22px;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .15s;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    height: 38px;
}
.btn-buscar:hover { opacity: .88; }
.btn-buscar:disabled { opacity: .6; cursor: not-allowed; }

/* ── OT chip resultado ────────────────────────────────────────── */
.ot-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eff6ff;
    border: 1.5px solid #bfdbfe;
    color: #1e40af;
    border-radius: 20px;
    padding: 4px 14px;
    font-size: .82rem;
    font-weight: 700;
}

/* ── Estado LIN badge ─────────────────────────────────────────── */
.badge-estado {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
}
.badge-abierto  { background: #dcfce7; color: #166534; }
.badge-cerrado  { background: #fee2e2; color: #991b1b; }
.badge-otro     { background: #f3f4f6; color: #374151; }

/* ── Spinner ──────────────────────────────────────────────────── */
#spinnerWrap {
    display: none;
    align-items: center;
    gap: 10px;
    padding: 20px 0;
    color: #6b7280;
    font-size: .88rem;
}
.est-spinner {
    width: 26px; height: 26px;
    border: 3px solid #e5e7eb;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin .8s linear infinite;
    flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Alert vacío ──────────────────────────────────────────────── */
#alertVacio {
    display: none;
    padding: 14px 18px;
    background: #fffbeb;
    border: 1.5px solid #fcd34d;
    border-radius: 8px;
    color: #92400e;
    font-size: .87rem;
}

.est-table-tools { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:14px; padding:12px; background:#f5f8fc; border:1px solid #dce5ef; border-radius:9px; }
.est-table-tools .est-field { min-width:220px; flex:1; }
.est-table-tools label { display:block; margin-bottom:4px; color:#475569; font-size:.72rem; font-weight:700; text-transform:uppercase; }
.btn-detalle { border:0; border-radius:7px; padding:6px 11px; background:#173b57; color:#fff; font-size:.76rem; font-weight:700; white-space:nowrap; }
.btn-detalle:hover { background:#2563eb; color:#fff; }
.cross-modal .modal-content { border:0; border-radius:14px; overflow:hidden; box-shadow:0 22px 65px rgba(15,35,60,.28); }
.cross-modal .modal-header { background:#173b57; color:#fff; border:0; }
.cross-modal .close { color:#fff; opacity:.8; text-shadow:none; }
.cross-summary { display:grid; grid-template-columns:repeat(5,minmax(130px,1fr)); gap:10px; margin-bottom:15px; }
.cross-stat { padding:12px; border-radius:9px; background:#f1f6f9; border-left:3px solid #277da1; }
.cross-stat span { display:block; color:#64748b; font-size:.66rem; font-weight:700; text-transform:uppercase; }
.cross-stat strong { color:#173b57; font-size:1.05rem; }
.cross-source { margin-top:12px; border:1px solid #dbe3ec; border-radius:10px; overflow:hidden; }
.cross-source-head { display:flex; justify-content:space-between; padding:9px 12px; background:#eaf1f5; color:#173b57; font-size:.78rem; font-weight:700; }
.cross-source table { margin:0; font-size:.74rem; }
.cross-empty { padding:18px; text-align:center; background:#fff8e8; color:#8a4b08; }
@media(max-width:900px){ .report-intro{grid-template-columns:1fr 1fr} .report-kpis{grid-template-columns:repeat(2,1fr)} }
@media(max-width:700px){ .cross-summary{grid-template-columns:repeat(2,1fr)} .est-table-tools .est-field{min-width:100%} .report-intro{grid-template-columns:1fr} .table-tool-actions{width:100%;margin-left:0} }
</style>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
           <div class="col-sm-6">
          <h1 class="m-0" style="font-size:1.3rem; font-weight:700; color:#1e3a5f;">
            <i class="fas fa-boxes mr-2" style="color:#2563eb;"></i>Historial de Compras — SAP
          </h1>
            </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/home.php">Inicio</a></li>
            <li class="breadcrumb-item active">Estructuración</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid est-page-wrap">

      <!-- ── Tarjeta de búsqueda ──────────────────────────────── -->
      <div class="est-card">
        <div class="est-card-header">
          <span><i class="fas fa-search mr-2"></i>Consultar OT</span>
        </div>
        <div class="est-card-body">
          <div class="est-search-row">
            <div class="est-field">
              <label for="inputOT">Código OT</label>
              <input type="text" id="inputOT" class="form-control"
                     placeholder="Ej: 4682 o OTC-004682"
                     maxlength="20" autocomplete="off">
              <span class="est-hint">Ingresa el número (4682) o el código completo (OTC-004682)</span>
            </div>
            <button class="btn-buscar" id="btnBuscar">
              <i class="fas fa-search"></i> Buscar
            </button>
          </div>
        </div>
      </div>

      <div class="report-intro" id="reportIntro">
        <div class="intro-main"><i class="fas fa-chart-bar"></i><h3>Consulta consolidada de compras</h3><p>Visualiza en una sola linea lo comprado, planificado, pendiente, solicitado y consumido para cada item de la OT.</p></div>
        <div class="intro-step"><span>1</span><strong>Ingresa la OT</strong><p>Puedes escribir solamente el numero o el codigo completo.</p></div>
        <div class="intro-step"><span>2</span><strong>Filtra el reporte</strong><p>Busca por item, descripcion o tipo de material.</p></div>
        <div class="intro-step"><span>3</span><strong>Revisa el detalle</strong><p>Consulta las cuatro fuentes SAP desde el boton de cada linea.</p></div>
      </div>

    <!-- ── Tarjeta de resultados ────────────── -->
<div class="est-card" id="cardResultados" style="display:none;">

    <div class="est-card-header">

        <span>
            <i class="fas fa-table mr-2"></i>

            Resultados

            <span id="otChipWrap" class="ml-2"></span>
        </span>

        <span class="badge-total" id="badgeTotal">0</span>

    </div>

    <div class="est-card-body">

        <!-- Spinner -->
        <div id="spinnerWrap" style="display:flex;">

            <div class="est-spinner"></div>

            <span>Consultando SAP HANA…</span>

        </div>

        <!-- Alerta -->
        <div id="alertVacio" style="display:none;">

            <i class="fas fa-exclamation-triangle mr-2"></i>

            No se encontraron registros para la OT indicada.

        </div>

        <!-- Tabla -->
        <div id="tableWrap" style="display:none;">

            <div class="report-kpis" id="reportKpis"></div>

            <div class="est-table-tools">
                <div class="est-field">
                    <label for="filterItem">Filtrar por item</label>
                    <input type="text" id="filterItem" class="form-control form-control-sm" placeholder="Codigo del item">
                </div>
                <div class="est-field">
                    <label for="filterDescription">Filtrar por descripcion</label>
                    <input type="text" id="filterDescription" class="form-control form-control-sm" placeholder="Texto de la descripcion">
                </div>
                <div class="est-field">
                    <label for="filterType">Filtrar por tipo</label>
                    <select id="filterType" class="form-control form-control-sm">
                        <option value="">Todos los tipos</option>
                    </select>
                </div>
                <div class="table-tool-actions">
                    <button type="button" class="btn-report-tool" id="btnClearFilters"><i class="fas fa-eraser mr-1"></i>Limpiar filtros</button>
                    <button type="button" class="btn-report-tool" id="btnExportCsv"><i class="fas fa-file-csv mr-1"></i>Exportar CSV</button>
                </div>
            </div>

            <div class="table-responsive">

                <table id="tblHistComp"
                       class="table table-bordered table-striped table-hover table-sm w-100">

                    <thead>

                        <tr>

                            <th>Año</th>

                            <th>Cód. Item</th>

                            <th>Descripción</th>

                            <th>Tipo de item</th>

                            <th>Estado</th>

                            <th class="text-right">
                                Cant. Comprada
                            </th>

                            <th class="text-right">
                                Cant. Plan
                            </th>

                            <th class="text-right">
                                Cant. Pend.
                            </th>

                            <th>Unidad</th>

                            <th class="text-right">
                                Cant. Solicitud Abierta
                            </th>

                            <th class="text-right">
                                Cant. Consumida
                            </th>

                            <th class="text-center">Detalle</th>

                        </tr>

                    </thead>

                    <tbody id="tbodyHistComp"></tbody>

                </table>

            </div>

        </div>

    </div>

</div>

    </div>
  </section>
</div>

<div class="modal fade cross-modal" id="modalCruceDetalle" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div><small style="opacity:.7">VERIFICACION POR LINEA</small><h5 class="modal-title" id="crossDetailTitle">Detalle del cruce</h5></div>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button>
      </div>
      <div class="modal-body" id="crossDetailBody">
        <div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin mr-2"></i>Consultando las cuatro fuentes...</div>
      </div>
    </div>
  </div>
</div>

<?php include_once dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
<script>

(function () {

    'use strict';

    let dtTableHist = null;
    let reportRows = [];

    // ─────────────────────────────────────────────
    // Escape HTML
    // ─────────────────────────────────────────────

    function escHtml(str) {

        if (str == null) return '';

        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ─────────────────────────────────────────────
    // Formato número
    // ─────────────────────────────────────────────

    function fmtNum(v) {

        if (v == null || v === '') return '0';

        const n = parseFloat(v);

        return isNaN(n)
            ? escHtml(v)
            : n.toLocaleString('es-PE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
    }

    function consolidarPorItem(rows) {
        const grouped = new Map();
        const numericFields = ['CantComp', 'CantPlanTot', 'CantPendTot', 'CantSolicitudAbierta', 'CantConsumida'];

        rows.forEach(row => {
            const ot = String(row.OT || '').trim().toUpperCase();
            const item = String(row.CodItem || '').trim().toUpperCase();
            const key = ot + '|' + item;

            if (!grouped.has(key)) {
                grouped.set(key, Object.assign({}, row));
                numericFields.forEach(field => { grouped.get(key)[field] = parseFloat(row[field]) || 0; });
                return;
            }

            const current = grouped.get(key);
            numericFields.forEach(field => { current[field] += parseFloat(row[field]) || 0; });
            ['Anio', 'DescItemOC', 'TipoItem', 'EstadoL', 'Unidad'].forEach(field => {
                if (!current[field] && row[field]) current[field] = row[field];
            });
        });

        return Array.from(grouped.values());
    }

    // ─────────────────────────────────────────────
    // Badge estado
    // ─────────────────────────────────────────────

    function badgeEstado(val) {

        if (!val) {

            return `
                <span class="badge badge-secondary">
                    —
                </span>
            `;
        }

        const v = val.toLowerCase();

        if (
            v.includes('abiert') ||
            v === 'o'
        ) {

            return `
                <span class="badge badge-success">
                    ${escHtml(val)}
                </span>
            `;
        }

        if (
            v.includes('cerrad') ||
            v === 'c'
        ) {

            return `
                <span class="badge badge-danger">
                    ${escHtml(val)}
                </span>
            `;
        }

        return `
            <span class="badge badge-secondary">
                ${escHtml(val)}
            </span>
        `;
    }

    // ─────────────────────────────────────────────
    // Render tabla
    // ─────────────────────────────────────────────

    function renderHistorial(rows) {

        reportRows = rows;

        const sum = field => rows.reduce((total, row) => total + (parseFloat(row[field]) || 0), 0);
        const typeCount = new Set(rows.map(row => row.TipoItem || 'SIN TIPO')).size;
        document.getElementById('reportKpis').innerHTML = [
            ['Items consolidados', rows.length],
            ['Tipos encontrados', typeCount],
            ['Total comprado', fmtNum(sum('CantComp'))],
            ['Total planificado', fmtNum(sum('CantPlanTot'))],
            ['Total pendiente', fmtNum(sum('CantPendTot'))],
            ['Total consumido', fmtNum(sum('CantConsumida'))]
        ].map(kpi => `<div class="report-kpi"><span>${kpi[0]}</span><strong>${kpi[1]}</strong></div>`).join('');

        if ($.fn.DataTable.isDataTable('#tblHistComp')) {

            $('#tblHistComp').DataTable().destroy();
        }

        const tbody = document.getElementById('tbodyHistComp');

        tbody.innerHTML = '';

        rows.forEach(r => {

            const tr = document.createElement('tr');

            tr.innerHTML = `

                <td>${escHtml(r.Anio || '')}</td>

                <td>
                    <code>${escHtml(r.CodItem)}</code>
                </td>

                <td>${escHtml(r.DescItemOC || '')}</td>

                <td><span class="badge badge-info">${escHtml(r.TipoItem || 'SIN TIPO')}</span></td>

                <td>${badgeEstado(r.EstadoL || '')}</td>

                <td class="text-right">
                    ${fmtNum(r.CantComp)}
                </td>

                <td class="text-right">
                    ${fmtNum(r.CantPlanTot)}
                </td>

                <td class="text-right">
                    ${fmtNum(r.CantPendTot)}
                </td>

                <td>${escHtml(r.Unidad || '')}</td>

                <td class="text-right">
                    ${fmtNum(r.CantSolicitudAbierta)}
                </td>

                <td class="text-right">
                    ${fmtNum(r.CantConsumida)}
                </td>

                <td class="text-center">
                    <button type="button" class="btn-detalle" data-ot="${escHtml(r.OT || '')}" data-item="${escHtml(r.CodItem || '')}">
                        <i class="fas fa-eye mr-1"></i> Ver detalle
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });

        dtTableHist = $('#tblHistComp').DataTable({

            destroy: true,

            responsive: true,

            autoWidth: false,

            pageLength: 25,

            order: [[1, 'asc']],

            columnDefs: [{ targets: 11, orderable: false, searchable: false }],

            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            }
        });

        $('#filterItem').off('input.junte').on('input.junte', function () {
            dtTableHist.column(1).search(this.value).draw();
        });
        $('#filterDescription').off('input.junte').on('input.junte', function () {
            dtTableHist.column(2).search(this.value).draw();
        });
        const types = Array.from(new Set(rows.map(row => row.TipoItem || 'SIN TIPO'))).sort((a, b) => a.localeCompare(b, 'es'));
        $('#filterType').html('<option value="">Todos los tipos</option>' + types.map(type => `<option value="${escHtml(type)}">${escHtml(type)}</option>`).join(''));
        $('#filterType').off('change.junte').on('change.junte', function () {
            const value = $.fn.dataTable.util.escapeRegex(this.value);
            dtTableHist.column(3).search(value ? '^' + value + '$' : '', true, false).draw();
        });
    }

    function detailTable(title, rows, columns) {
        const body = rows.length
            ? `<div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr>${columns.map(c => `<th>${escHtml(c.label)}</th>`).join('')}</tr></thead><tbody>${rows.map(row => `<tr>${columns.map(c => `<td>${escHtml(row[c.key] == null ? '' : row[c.key])}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`
            : '<div class="cross-empty"><i class="fas fa-exclamation-circle mr-1"></i>No hay registros en esta fuente.</div>';
        return `<section class="cross-source"><div class="cross-source-head"><span>${escHtml(title)}</span><span>${rows.length} fila(s)</span></div>${body}</section>`;
    }

    async function verDetalle(ot, item) {
        const body = document.getElementById('crossDetailBody');
        document.getElementById('crossDetailTitle').textContent = `${ot} / ${item}`;
        body.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin mr-2"></i>Consultando las cuatro fuentes...</div>';
        $('#modalCruceDetalle').modal('show');

        try {
            const response = await fetch(`<?= BASE_URL ?>/api/estructuracion/verificar_cruce.php?ot=${encodeURIComponent(ot)}&item=${encodeURIComponent(item)}`, {
                headers: {'Accept': 'application/json'}, credentials: 'same-origin'
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.error || 'No se pudo consultar el detalle.');
            const c = data.consolidado;
            body.innerHTML = `
                <div class="cross-summary">
                    <div class="cross-stat"><span>Comprado</span><strong>${fmtNum(c.CantComp)}</strong></div>
                    <div class="cross-stat"><span>Planificado</span><strong>${fmtNum(c.CantPlanTot)}</strong></div>
                    <div class="cross-stat"><span>Pendiente OF</span><strong>${fmtNum(c.CantPendTot)}</strong></div>
                    <div class="cross-stat"><span>Solicitud abierta</span><strong>${fmtNum(c.CantSolicitudAbierta)}</strong></div>
                    <div class="cross-stat"><span>Consumido</span><strong>${fmtNum(c.CantConsumida)}</strong></div>
                </div>
                <div class="mb-2 text-muted"><strong>${escHtml(c.DescItemOC || 'Sin descripcion')}</strong>${c.Unidad ? ` | Unidad: ${escHtml(c.Unidad)}` : ''}</div>
                ${detailTable('1. TAMA_REPHISTCOMP_WEB', data.historial_compras, [{key:'OT',label:'OT'},{key:'CodItem',label:'Item'},{key:'Anio',label:'Anio'},{key:'DescItemOC',label:'Descripcion'},{key:'EstadoL',label:'Estado'},{key:'CantComp',label:'Cantidad comprada'}])}
                ${detailTable('2. TAMA_OFDetalleXItem', data.detalle_of, [{key:'CodProy',label:'OT'},{key:'CodItemHijo',label:'Item'},{key:'NomHijo',label:'Descripcion'},{key:'CantPlanTot',label:'Planificado'},{key:'CantPendTot',label:'Pendiente'}])}
                ${detailTable('3. TAMA_COMPRA_SOLICITU_ABIER', data.solicitud_abierta, [{key:'PROYECTO',label:'Proyecto'},{key:'DOCNUM',label:'Documento'},{key:'NUM ARTICULO',label:'Item'},{key:'DESC ARTICULO',label:'Descripcion'},{key:'UNI',label:'Unidad'},{key:'CANTIDAD',label:'Cantidad'},{key:'FECHA',label:'Fecha'}])}
                ${detailTable('4. TAMA_CONSUMOS', data.consumos, [{key:'CodOT',label:'OT'},{key:'Cod. Elemento',label:'Item'},{key:'Desc. Elemento',label:'Descripcion'},{key:'Fec. Contab',label:'Fecha contable'},{key:'Semana',label:'Semana'},{key:'Tipo',label:'Tipo'},{key:'Nro.OF',label:'Nro. OF'},{key:'Cantidad',label:'Cantidad consumida'}])}
            `;
        } catch (error) {
            body.innerHTML = `<div class="alert alert-danger mb-0">${escHtml(error.message || 'No se pudo cargar el detalle.')}</div>`;
        }
    }

    // ─────────────────────────────────────────────
    // Buscar
    // ─────────────────────────────────────────────

    async function buscar() {

        const val = document
            .getElementById('inputOT')
            .value
            .trim();

        if (!val) {

            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Ingresa una OT.',
                confirmButtonColor: '#2563eb'
            });

            return;
        }

        document.getElementById('btnBuscar').disabled = true;

        document.getElementById('reportIntro').style.display = 'none';

        document.getElementById('cardResultados').style.display = '';

        document.getElementById('spinnerWrap').style.display = 'flex';

        document.getElementById('tableWrap').style.display = 'none';

        document.getElementById('alertVacio').style.display = 'none';

        try {

            const response = await fetch(
                `<?= BASE_URL ?>/api/estructuracion/get_com_of.php?ot=${encodeURIComponent(val)}`
            );

            const json = await response.json();

            document.getElementById('spinnerWrap').style.display = 'none';

            if (json.error) {

                Swal.fire({
                    icon: 'error',
                    title: 'Error SAP',
                    text: json.error,
                    confirmButtonColor: '#2563eb'
                });

                return;
            }

            const rows = consolidarPorItem(json.data || []);

            document.getElementById('badgeTotal').textContent = rows.length;

            document.getElementById('otChipWrap').innerHTML = `
                <span class="ot-chip">
                    <i class="fas fa-tag"></i>
                    ${escHtml(json.ot || val)}
                </span>
            `;

            if (rows.length > 0) {

                document.getElementById('tableWrap').style.display = '';

                renderHistorial(rows);

            } else {

                document.getElementById('alertVacio').style.display = '';
            }

        } catch (err) {

            console.error(err);

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message,
                confirmButtonColor: '#2563eb'
            });

        } finally {

            document.getElementById('btnBuscar').disabled = false;
        }
    }

    // ─────────────────────────────────────────────
    // Eventos
    // ─────────────────────────────────────────────

    document
        .getElementById('btnBuscar')
        .addEventListener('click', buscar);

    document
        .getElementById('inputOT')
        .addEventListener('keydown', function (e) {

            if (e.key === 'Enter') {

                buscar();
            }
        });

    document.getElementById('tbodyHistComp').addEventListener('click', function (event) {
        const button = event.target.closest('.btn-detalle');
        if (button) verDetalle(button.dataset.ot, button.dataset.item);
    });

    document.getElementById('btnClearFilters').addEventListener('click', function () {
        document.getElementById('filterItem').value = '';
        document.getElementById('filterDescription').value = '';
        document.getElementById('filterType').value = '';
        if (dtTableHist) dtTableHist.search('').columns().search('').draw();
    });

    document.getElementById('btnExportCsv').addEventListener('click', function () {
        if (!reportRows.length) return;
        const columns = [
            ['OT', 'OT'], ['Anio', 'Anio'], ['CodItem', 'Item'], ['DescItemOC', 'Descripcion'],
            ['TipoItem', 'Tipo de item'], ['EstadoL', 'Estado'], ['CantComp', 'Cantidad comprada'],
            ['CantPlanTot', 'Cantidad planificada'], ['CantPendTot', 'Cantidad pendiente'],
            ['Unidad', 'Unidad'], ['CantSolicitudAbierta', 'Solicitud abierta'],
            ['CantConsumida', 'Cantidad consumida']
        ];
        const csvCell = value => '"' + String(value == null ? '' : value).replace(/"/g, '""') + '"';
        const csv = '\uFEFF' + columns.map(column => csvCell(column[1])).join(';') + '\r\n' +
            reportRows.map(row => columns.map(column => csvCell(row[column[0]])).join(';')).join('\r\n');
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([csv], {type: 'text/csv;charset=utf-8'}));
        link.download = `cruce_${String(reportRows[0].OT || 'OT').replace(/[^A-Za-z0-9_-]/g, '')}.csv`;
        document.body.appendChild(link);
        link.click();
        URL.revokeObjectURL(link.href);
        link.remove();
    });

})();

</script>
