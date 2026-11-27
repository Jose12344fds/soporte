<?php
$page_title = 'Verificacion de cruce - Estructuracion';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_permiso('estructuracion', 'ver');
include_once dirname(dirname(__DIR__)) . '/layouts/header.php';
?>

<style>
.verify-wrap { padding: 18px 22px; }
.verify-panel { background:#fff; border:1px solid #dbe3ec; border-radius:12px; box-shadow:0 4px 18px rgba(15,35,60,.07); margin-bottom:18px; overflow:hidden; }
.verify-head { padding:12px 16px; background:#173b57; color:#fff; font-weight:700; display:flex; justify-content:space-between; align-items:center; }
.verify-body { padding:16px; }
.verify-search { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
.verify-field { min-width:210px; }
.verify-field label { display:block; font-size:.76rem; font-weight:700; color:#52606d; margin-bottom:4px; }
.verify-grid { display:grid; grid-template-columns:repeat(4,minmax(150px,1fr)); gap:12px; }
.verify-stat { padding:14px; border-radius:10px; background:#f4f8fb; border-left:4px solid #277da1; }
.verify-stat span { display:block; color:#64748b; font-size:.72rem; text-transform:uppercase; font-weight:700; }
.verify-stat strong { display:block; color:#173b57; font-size:1.25rem; margin-top:3px; }
.verify-table { width:100%; font-size:.8rem; margin:0; }
.verify-table th { white-space:nowrap; background:#edf3f7; color:#334155; }
.verify-table td,.verify-table th { padding:8px 10px; border:1px solid #dce4ea; }
.verify-empty { padding:22px; text-align:center; color:#8a4b08; background:#fff7e6; }
.verify-ok,.verify-warn { padding:10px 14px; border-radius:8px; font-weight:700; }
.verify-ok { color:#166534; background:#dcfce7; }
.verify-warn { color:#9a3412; background:#ffedd5; }
.source-count { background:rgba(255,255,255,.18); border-radius:12px; padding:2px 9px; font-size:.75rem; }
@media(max-width:800px){ .verify-grid{grid-template-columns:repeat(2,1fr)} .verify-wrap{padding:10px} }
</style>

<div class="content-wrapper">
  <div class="content-header"><div class="container-fluid"><div class="row mb-2">
    <div class="col-sm-7"><h1 class="m-0" style="font-size:1.3rem;font-weight:700;color:#173b57"><i class="fas fa-project-diagram mr-2"></i>Verificacion de cruce por OT e item</h1></div>
    <div class="col-sm-5 text-sm-right"><a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>/modules/Estructuracion/junte.php"><i class="fas fa-arrow-left mr-1"></i>Volver al cruce</a></div>
  </div></div></div>
  <section class="content"><div class="container-fluid verify-wrap">
    <div class="verify-panel"><div class="verify-body">
      <form id="verifyForm" class="verify-search">
        <div class="verify-field"><label for="ot">ORDEN DE TRABAJO</label><input class="form-control" id="ot" value="OTC-004754" required></div>
        <div class="verify-field"><label for="item">NUMERO DE ITEM</label><input class="form-control" id="item" value="120010200001" required></div>
        <button class="btn btn-primary" id="btnVerify"><i class="fas fa-search mr-1"></i>Verificar las 3 tablas</button>
      </form>
    </div></div>
    <div id="message"></div>
    <div id="result" style="display:none">
      <div class="verify-panel"><div class="verify-head"><span>Resultado consolidado</span><span id="relationStatus"></span></div><div class="verify-body">
        <div class="verify-grid" id="stats"></div><div id="description" class="mt-3 text-muted"></div>
      </div></div>
      <div id="sources"></div>
    </div>
  </div></section>
</div>

<script>
(function () {
  const api = '<?= BASE_URL ?>/api/estructuracion/verificar_cruce.php';
  const el = id => document.getElementById(id);
  const esc = value => {
    const node = document.createElement('div');
    node.textContent = value == null ? '' : value;
    return node.innerHTML;
  };
  const number = value => Number(value || 0).toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2});

  function sourceCard(title, rows, columns) {
    const body = rows.length
      ? '<div class="table-responsive"><table class="verify-table"><thead><tr>' + columns.map(c => '<th>' + esc(c.label) + '</th>').join('') + '</tr></thead><tbody>' + rows.map(row => '<tr>' + columns.map(c => '<td>' + esc(row[c.key]) + '</td>').join('') + '</tr>').join('') + '</tbody></table></div>'
      : '<div class="verify-empty"><i class="fas fa-exclamation-circle mr-1"></i>No hay registros para esta OT e item en esta fuente.</div>';
    return '<div class="verify-panel"><div class="verify-head"><span>' + esc(title) + '</span><span class="source-count">' + rows.length + ' fila(s)</span></div>' + body + '</div>';
  }

  async function verify(event) {
    if (event) event.preventDefault();
    const ot = el('ot').value.trim();
    const item = el('item').value.trim();
    el('btnVerify').disabled = true;
    el('btnVerify').innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Consultando';
    el('message').innerHTML = '';
    el('result').style.display = 'none';
    try {
      const response = await fetch(api + '?ot=' + encodeURIComponent(ot) + '&item=' + encodeURIComponent(item), {
        headers: {'Accept': 'application/json'},
        credentials: 'same-origin'
      });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.error || 'Error al consultar las fuentes.');
      const c = data.consolidado;
      const exact = data.solicitud_abierta.length > 0;
      el('relationStatus').innerHTML = exact ? '<span class="verify-ok">Coincide en las 3 tablas</span>' : '<span class="verify-warn">Sin coincidencia en solicitud abierta</span>';
      el('stats').innerHTML = [
        ['Comprado', c.CantComp], ['Planificado', c.CantPlanTot], ['Pendiente OF', c.CantPendTot], ['Solicitud abierta', c.CantSolicitudAbierta]
      ].map(s => '<div class="verify-stat"><span>' + s[0] + '</span><strong>' + number(s[1]) + '</strong></div>').join('');
      el('description').innerHTML = '<strong>' + esc(c.OT) + ' / ' + esc(c.CodItem) + '</strong> - ' + esc(c.DescItemOC || 'Sin descripcion') + (c.Unidad ? ' | Unidad: <strong>' + esc(c.Unidad) + '</strong>' : '');
      el('sources').innerHTML =
        sourceCard('1. TAMA_REPHISTCOMP_WEB', data.historial_compras, [{key:'OT',label:'OT'},{key:'CodItem',label:'Item'},{key:'Anio',label:'Anio'},{key:'DescItemOC',label:'Descripcion'},{key:'EstadoL',label:'Estado'},{key:'CantComp',label:'Cantidad comprada'}]) +
        sourceCard('2. TAMA_OFDetalleXItem', data.detalle_of, [{key:'CodProy',label:'OT'},{key:'CodItemHijo',label:'Item'},{key:'NomHijo',label:'Descripcion'},{key:'CantPlanTot',label:'Planificado'},{key:'CantPendTot',label:'Pendiente'}]) +
        sourceCard('3. TAMA_COMPRA_SOLICITU_ABIER', data.solicitud_abierta, [{key:'PROYECTO',label:'Proyecto'},{key:'DOCNUM',label:'Documento'},{key:'NUM ARTICULO',label:'Item'},{key:'DESC ARTICULO',label:'Descripcion'},{key:'UNI',label:'Unidad'},{key:'CANTIDAD',label:'Cantidad'},{key:'FECHA',label:'Fecha'}]) +
        (data.mismo_articulo_otros_proyectos.length ? sourceCard('Diagnostico: mismo item en otros proyectos', data.mismo_articulo_otros_proyectos, [{key:'PROYECTO',label:'Proyecto'},{key:'NUM ARTICULO',label:'Item'},{key:'UNI',label:'Unidad'},{key:'CANTIDAD',label:'Cantidad'}]) : '');
      el('result').style.display = 'block';
    } catch (error) {
      el('message').innerHTML = '<div class="alert alert-danger">' + esc(error.message || 'No fue posible consultar las fuentes.') + '</div>';
    } finally {
      el('btnVerify').disabled = false;
      el('btnVerify').innerHTML = '<i class="fas fa-search mr-1"></i>Verificar las 3 tablas';
    }
  }
  el('verifyForm').addEventListener('submit', verify);
  verify();
})();
</script>

<?php include_once dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
