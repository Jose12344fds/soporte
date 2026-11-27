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
    max-width: 220px;
    border-radius: 7px;
    border: 1.5px solid #d1d5db;
    font-size: .85rem;
}
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

      <!-- ── Tarjeta de resultados 1────────────── -->
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

          <!-- Alerta sin resultados -->
          <div id="alertVacio">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            No se encontraron registros para la OT indicada.
          </div>

          <!-- Tabla -->
          <div id="tableWrap" style="display:none;">
            <table id="tblHistComp" class="table table-bordered table-striped table-hover table-sm w-100">
              <thead>
                <tr>
                  <th>Año</th>
                  <th>Cód. Item</th>
                  <th>Descripción OC</th>
                  <th>Estado Lín.</th>
                  <th>OT Cod</th>
                  <th class="text-right">Cant. Comp.</th>
                </tr>
              </thead>
              <tbody id="tbodyHistComp"></tbody>
            </table>
          </div>

        </div>
      </div>
          <!-- ── Tarjeta de resultados 2 ────────── -->
      <div class="est-card" id="cardResultados2" style="display:none;">
        <div class="est-card-header">
          <span>
            <i class="fas fa-table mr-2"></i>
            Resultados
            <span id="otChipWrap2" class="ml-2"></span>
          </span>
          <span class="badge-total" id="badgeTotal2">0</span>
        </div>
        <div class="est-card-body">

          <!-- Spinner -->
          <div id="spinnerWrap2" style="display:flex;">
            <div class="est-spinner"></div>
            <span>Consultando SAP HANA…</span>
          </div>

          <!-- Alerta sin resultados -->
          <div id="alertVacio2">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            No se encontraron registros para la OT indicada.
          </div>

          <!-- Tabla -->
          <div id="tableWrap2" style="display:none;">
            <table id="tblofitem" class="table table-bordered table-striped table-hover table-sm w-100">
              <thead>
                <tr>
                  <th>CodProy</th>
                  <th>CodItemHijo</th>
                  <th>CantPlanTot</th>
                  <th>CantPendTot</th>
                </tr>
              </thead>
              <tbody id="tbodyOFITEM"></tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </section>
</div>

<?php include_once dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
<script>
(function () {

    'use strict';

    let dtTableHist = null;
    let dtTableOF   = null;

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    function escHtml(str) {

        if (str == null) return '';

        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fmtNum(v) {

        if (v == null || v === '') return '—';

        const n = parseFloat(v);

        return isNaN(n)
            ? escHtml(v)
            : n.toLocaleString('es-PE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 6
            });
    }

    function badgeEstado(val) {

        if (!val) {
            return '<span class="badge-estado badge-otro">—</span>';
        }

        const v = val.toLowerCase();

        if (v.includes('abiert') || v === 'o') {
            return `<span class="badge-estado badge-abierto">${escHtml(val)}</span>`;
        }

        if (v.includes('cerrad') || v === 'c') {
            return `<span class="badge-estado badge-cerrado">${escHtml(val)}</span>`;
        }

        return `<span class="badge-estado badge-otro">${escHtml(val)}</span>`;
    }

    // ─────────────────────────────────────────────
    // Render Historial Compras
    // ─────────────────────────────────────────────

    function renderHistorial(rows) {

        // Destruir DataTable anterior
        if ($.fn.DataTable.isDataTable('#tblHistComp')) {

            $('#tblHistComp').DataTable().destroy();
        }

        const tbody = document.getElementById('tbodyHistComp');

        tbody.innerHTML = '';

        rows.forEach(r => {

            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${escHtml(r.Anio)}</td>
                <td><code>${escHtml(r.CodItem)}</code></td>
                <td>${escHtml(r.DescItemOC)}</td>
                <td>${badgeEstado(r.EstadoL)}</td>
                <td><strong>${escHtml(r.OT)}</strong></td>
                <td class="text-right">${fmtNum(r.CantComp)}</td>
            `;

            tbody.appendChild(tr);
        });

        dtTableHist = $('#tblHistComp').DataTable({

            destroy: true,

            responsive: true,

            pageLength: 25,

            order: [[0, 'desc'], [1, 'asc']],

            language: typeof DATATABLES_SPANISH !== 'undefined'
                ? DATATABLES_SPANISH
                : {},

            dom: 'Bfrtip',

            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel mr-1"></i>Excel',
                    className: 'btn btn-sm btn-success'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print mr-1"></i>Imprimir',
                    className: 'btn btn-sm btn-secondary'
                }
            ],

            columnDefs: [
                {
                    targets: 5,
                    className: 'text-right'
                }
            ]
        });
    }

    // ─────────────────────────────────────────────
    // Render OF ITEM
    // ─────────────────────────────────────────────

    function renderOF(rows) {

        // Destruir DataTable anterior
        if ($.fn.DataTable.isDataTable('#tblofitem')) {

            $('#tblofitem').DataTable().destroy();
        }

        const tbody = document.getElementById('tbodyOFITEM');

        tbody.innerHTML = '';

        rows.forEach(r => {

            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${escHtml(r.CodProy)}</td>
                <td>${escHtml(r.CodItemHijo)}</td>
                <td class="text-right">${fmtNum(r.CantPlanTot)}</td>
                <td class="text-right">${fmtNum(r.CantPendTot)}</td>
            `;

            tbody.appendChild(tr);
        });

        dtTableOF = $('#tblofitem').DataTable({

            destroy: true,

            responsive: true,

            pageLength: 25,

            order: [[0, 'desc'], [1, 'asc']],

            language: typeof DATATABLES_SPANISH !== 'undefined'
                ? DATATABLES_SPANISH
                : {},

            dom: 'Bfrtip',

            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel mr-1"></i>Excel',
                    className: 'btn btn-sm btn-success'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print mr-1"></i>Imprimir',
                    className: 'btn btn-sm btn-secondary'
                }
            ]
        });
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
                text: 'Ingresa el número de OT.',
                confirmButtonColor: '#2563eb'
            });

            return;
        }

        document.getElementById('btnBuscar').disabled = true;

        // =========================
        // CARD HISTORIAL
        // =========================

        document.getElementById('cardResultados').style.display = '';
        document.getElementById('spinnerWrap').style.display = 'flex';
        document.getElementById('tableWrap').style.display = 'none';
        document.getElementById('alertVacio').style.display = 'none';

        // =========================
        // CARD OF ITEM
        // =========================

        document.getElementById('cardResultados2').style.display = '';
        document.getElementById('spinnerWrap2').style.display = 'flex';
        document.getElementById('tableWrap2').style.display = 'none';
        document.getElementById('alertVacio2').style.display = 'none';

        try {

            const [histRes, ofRes] = await Promise.all([

                fetch(`<?= BASE_URL ?>/api/estructuracion/get_hist_comp.php?ot=${encodeURIComponent(val)}`),

                fetch(`<?= BASE_URL ?>/api/estructuracion/get_of_item.php?ot=${encodeURIComponent(val)}`)
            ]);

            const histJson = await histRes.json();
            const ofJson   = await ofRes.json();

            // ───────────────────────
            // HISTORIAL
            // ───────────────────────

            document.getElementById('spinnerWrap').style.display = 'none';

            if (histJson.error) {

                Swal.fire({
                    icon: 'error',
                    title: 'Error SAP',
                    text: histJson.error,
                    confirmButtonColor: '#2563eb'
                });

            } else {

                const rowsHist = histJson.data || [];

                document.getElementById('badgeTotal').textContent = rowsHist.length;

                document.getElementById('otChipWrap').innerHTML = `
                    <span class="ot-chip">
                        <i class="fas fa-tag"></i>
                        ${escHtml(histJson.ot || val)}
                    </span>
                `;

                if (rowsHist.length > 0) {

                    document.getElementById('tableWrap').style.display = '';

                    renderHistorial(rowsHist);

                } else {

                    document.getElementById('alertVacio').style.display = '';
                }
            }

            // ───────────────────────
            // OF ITEM
            // ───────────────────────

            document.getElementById('spinnerWrap2').style.display = 'none';

            if (ofJson.error) {

                Swal.fire({
                    icon: 'error',
                    title: 'Error SAP',
                    text: ofJson.error,
                    confirmButtonColor: '#2563eb'
                });

            } else {

                const rowsOF = ofJson.data || [];

                document.getElementById('badgeTotal2').textContent = rowsOF.length;

                document.getElementById('otChipWrap2').innerHTML = `
                    <span class="ot-chip">
                        <i class="fas fa-tag"></i>
                        ${escHtml(ofJson.ot || val)}
                    </span>
                `;

                if (rowsOF.length > 0) {

                    document.getElementById('tableWrap2').style.display = '';

                    renderOF(rowsOF);

                } else {

                    document.getElementById('alertVacio2').style.display = '';
                }
            }

        } catch (err) {

            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
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

})();
</script>