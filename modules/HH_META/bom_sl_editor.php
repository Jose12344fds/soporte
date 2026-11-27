<?php
$page_title = 'Editor BOM SAP Service Layer';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_permiso('hh-meta', 'ver');
include_once dirname(dirname(__DIR__)) . '/layouts/header.php';
?>

<style>
  .bom-hero {
    background: linear-gradient(135deg, #16324f 0%, #24527a 55%, #3a7ca5 100%);
    color: #fff;
    border-radius: 18px;
    padding: 28px 30px;
    margin-bottom: 22px;
    box-shadow: 0 14px 30px rgba(22, 50, 79, 0.18);
  }
  .bom-hero h2 {
    margin: 0 0 8px;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 0.2px;
  }
  .bom-hero p {
    margin: 0;
    max-width: 760px;
    color: rgba(255,255,255,0.9);
    font-size: 14px;
    line-height: 1.55;
  }
  .bom-card {
    background: #fff;
    border: 1px solid #e6ebf1;
    border-radius: 18px;
    box-shadow: 0 10px 24px rgba(28, 43, 58, 0.08);
    overflow: hidden;
  }
  .bom-card-head {
    padding: 18px 22px;
    border-bottom: 1px solid #edf1f5;
    background: linear-gradient(180deg, #fbfcfd 0%, #f4f7fa 100%);
  }
  .bom-card-head strong {
    font-size: 18px;
    color: #16324f;
  }
  .bom-card-body {
    padding: 22px;
  }
  .bom-tip {
    background: #eef7f2;
    border: 1px solid #cfe8d8;
    color: #275d3b;
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 18px;
    font-size: 13px;
  }
  #estado {
    margin: 14px 0;
    padding: 10px 14px;
    border-radius: 10px;
    background: #f5f8fb;
    border: 1px solid #e0e7ef;
    color: #5f6f82;
    font-weight: 600;
  }
  #tblMo thead th {
    background: #16324f;
    color: #fff;
    border-color: #16324f;
    vertical-align: middle;
    font-size: 12px;
  }
  #tblMo tbody td {
    vertical-align: middle;
  }
  #tblMo tbody tr:nth-child(even) {
    background: #fbfcfe;
  }
  #resultado {
    background: #101922;
    color: #d9e7f5;
    border: 0;
    border-radius: 14px;
    padding: 14px;
    min-height: 160px;
  }
</style>

<div class="container-fluid" style="max-width: 1200px;">
  <div class="bom-hero">
    <h2>Editor de Recursos MO</h2>
    <p>
      Carga la lista de materiales del producto, revisa los procesos <code>MO</code> y ajusta sus cantidades
      sin perder el orden técnico que SAP necesita para guardar correctamente.
    </p>
  </div>

  <div class="bom-card">
    <div class="bom-card-head">
      <strong>Modificar Mano de Obra de la BOM</strong>
    </div>
    <div class="bom-card-body">
      <div class="bom-tip">
        Edita solo las cantidades necesarias. El sistema conserva automáticamente la estructura interna de SAP
        para evitar errores al actualizar.
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="itemCode">ItemCode padre</label>
            <input type="text" id="itemCode" class="form-control" placeholder="Ej: 182012400002-02">
          </div>
        </div>
        <div class="col-md-6" style="display:flex;align-items:flex-end;gap:8px;">
          <button type="button" id="btnCargar" class="btn btn-info">Cargar MO</button>
          <button type="button" id="btnGuardar" class="btn btn-primary" disabled>Guardar cambios</button>
        </div>
      </div>

      <div id="estado"></div>

      <div class="table-responsive">
        <table class="table table-bordered table-sm" id="tblMo" style="display:none;">
          <thead>
            <tr>
              <th>ItemCode</th>
              <th>Nombre</th>
              <th>StageID</th>
              <th>ChildNum</th>
              <th>VisualOrder</th>
              <th>Quantity</th>
              <th>Warehouse</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="form-group">
        <label for="resultado">Resultado</label>
        <pre id="resultado"></pre>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const btnCargar = document.getElementById('btnCargar');
  const btnGuardar = document.getElementById('btnGuardar');
  const itemCodeInput = document.getElementById('itemCode');
  const estado = document.getElementById('estado');
  const out = document.getElementById('resultado');
  const table = document.getElementById('tblMo');
  const tbody = table.querySelector('tbody');

  let currentRows = [];

  function setEstado(msg, isError) {
    estado.textContent = msg || '';
    estado.style.color = isError ? '#c0392b' : '#6c757d';
  }

  function renderRows(rows) {
    currentRows = Array.isArray(rows) ? rows : [];
    tbody.innerHTML = '';

    if (currentRows.length === 0) {
      table.style.display = 'none';
      btnGuardar.disabled = true;
      return;
    }

    let html = '';
    currentRows.forEach((row, idx) => {
      html += '<tr data-idx="' + idx + '">' +
        '<td><strong>' + esc(row.ItemCode) + '</strong></td>' +
        '<td>' + esc(row.ItemName || '') + '</td>' +
        '<td>' + esc(row.StageID) + '</td>' +
        '<td>' + esc(row.ChildNum) + '</td>' +
        '<td>' + esc(row.VisualOrder) + '</td>' +
        '<td><input type="number" class="form-control form-control-sm qty-input" step="0.001" value="' + numRaw(row.Quantity) + '"></td>' +
        '<td>' + esc(row.Warehouse || '') + '</td>' +
      '</tr>';
    });

    tbody.innerHTML = html;
    table.style.display = '';
    btnGuardar.disabled = false;
  }

  function getUpdates() {
    return Array.from(tbody.querySelectorAll('tr')).map(tr => {
      const idx = parseInt(tr.getAttribute('data-idx'), 10);
      const src = currentRows[idx];
      const qty = parseFloat(tr.querySelector('.qty-input').value);
      return {
        ItemCode: src.ItemCode,
        StageID: src.StageID,
        ChildNum: src.ChildNum,
        Quantity: isNaN(qty) ? 0 : qty
      };
    });
  }

  btnCargar.addEventListener('click', async function() {
    const itemCode = itemCodeInput.value.trim();
    if (!itemCode) {
      setEstado('Ingresa el ItemCode padre.', true);
      return;
    }

    btnCargar.disabled = true;
    btnGuardar.disabled = true;
    table.style.display = 'none';
    out.textContent = '';
    setEstado('Cargando BOM desde SAP...', false);

    try {
      const res = await fetch('../../api/hhmeta/service_layer_product_tree.php?itemCode=' + encodeURIComponent(itemCode));
      if (!res.ok) throw new Error(res.status === 401 ? 'La sesion vencio.' : 'Error HTTP ' + res.status);
      const data = await res.json();
      out.textContent = JSON.stringify(data, null, 2);

      if (!data.success) {
        renderRows([]);
        setEstado(data.message || 'No se pudo cargar la BOM.', true);
        return;
      }

      renderRows(data.moLines || []);
      setEstado('Se cargaron ' + (data.moLines || []).length + ' línea(s) MO desde SAP.', false);
    } catch (err) {
      renderRows([]);
      setEstado('Error: ' + err.message, true);
      out.textContent = err.message;
    } finally {
      btnCargar.disabled = false;
    }
  });

  btnGuardar.addEventListener('click', async function() {
    const itemCode = itemCodeInput.value.trim();
    if (!itemCode) {
      setEstado('Ingresa el ItemCode padre.', true);
      return;
    }

    const updates = getUpdates();
    btnGuardar.disabled = true;
    setEstado('Guardando cambios en SAP...', false);

    try {
      const res = await fetch('../../api/hhmeta/service_layer_product_tree.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ itemCode, updates })
      });
      if (!res.ok) throw new Error(res.status === 401 ? 'La sesion vencio.' : 'Error HTTP ' + res.status);
      const data = await res.json();
      out.textContent = JSON.stringify(data, null, 2);

      if (!data.success) {
        setEstado(data.message || 'No se pudo actualizar la BOM.', true);
        return;
      }

      setEstado('MO actualizados correctamente en SAP.', false);
    } catch (err) {
      setEstado('Error: ' + err.message, true);
      out.textContent = err.message;
    } finally {
      btnGuardar.disabled = false;
    }
  });

  function esc(v) {
    if (v == null) return '';
    const d = document.createElement('div');
    d.textContent = String(v);
    return d.innerHTML;
  }

  function numRaw(v) {
    const n = parseFloat(v);
    return isNaN(n) ? '0' : String(n);
  }
})();
</script>

<?php include_once dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
