<?php
/**
 * Importador temporal de equipos a SAP @TAMA_EQUIPOS_TM.
 * Formato: Excel XLSX o CSV. Code se calcula automaticamente; Name viene del archivo.
 */
$page_title = 'Importar Equipos TM';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_permiso('mantenimientos', 'ver');

$apiUrl = rtrim(BASE_URL, '/') . '/api/mantenimientos/import_equipos_tm.php';

include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
.imp-shell { max-width:1180px; margin:0 auto; }
.imp-hero { background:linear-gradient(135deg,#12372a,#1f7a45); padding:18px 0; color:#fff; }
.imp-card { border:1px solid #dfe7e3; border-left:4px solid #28a745; border-radius:8px; box-shadow:0 8px 22px rgba(23,52,39,.07); }
.imp-grid { display:grid; grid-template-columns:1fr auto auto auto; gap:10px; align-items:end; }
.imp-progress { border:1px solid #d7dee8; border-radius:8px; padding:14px; background:#fbfdff; display:none; }
.imp-bar { height:12px; background:#e5edf7; border-radius:999px; overflow:hidden; }
.imp-bar span { display:block; height:100%; width:0%; background:#1f7a45; transition:width .2s ease; }
.imp-log { max-height:260px; overflow:auto; background:#0f172a; color:#dbeafe; border-radius:6px; padding:10px; font-family:Consolas,monospace; font-size:12px; white-space:pre-wrap; }
.imp-fields { display:flex; flex-wrap:wrap; gap:6px; }
.imp-fields span { border:1px solid #d7dee8; border-radius:999px; padding:4px 8px; font-size:12px; background:#fff; color:#334155; }
@media (max-width:768px){ .imp-grid{grid-template-columns:1fr;} }
</style>

<div class="imp-hero">
  <div class="container-fluid imp-shell">
    <h3 class="mb-0 font-weight-bold"><i class="fas fa-file-upload mr-2"></i>Importar equipos a @TAMA_EQUIPOS_TM</h3>
    <small class="text-white-50">Carga temporal por lotes. El campo Code se genera automaticamente.</small>
  </div>
</div>

<section class="content mt-3">
  <div class="container-fluid imp-shell">
    <div class="card imp-card">
      <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <strong class="text-success">Archivo Excel</strong>
          <a class="btn btn-outline-success btn-sm" href="<?= htmlspecialchars($apiUrl) ?>?template=1">
            <i class="fas fa-download mr-1"></i>Descargar plantilla
          </a>
        </div>
      </div>
      <div class="card-body">
        <form id="formImportEquipos" enctype="multipart/form-data">
          <div class="imp-grid">
            <div>
              <label class="font-weight-bold">Archivo</label>
              <input type="file" class="form-control" name="archivo" accept=".xlsx,.csv" required>
              <small class="text-muted">Puedes subir Excel .xlsx. Si no tiene cabecera, deja marcado Sin cabecera.</small>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" value="1" id="actualizar" name="actualizar" checked>
              <label class="form-check-label" for="actualizar">Actualizar si existe Name</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" value="1" id="sinCabecera" name="sin_cabecera" checked>
              <label class="form-check-label" for="sinCabecera">Sin cabecera</label>
            </div>
            <button type="submit" class="btn btn-success">
              <i class="fas fa-play mr-1"></i>Preparar carga
            </button>
          </div>
        </form>

        <div class="mt-3">
          <div class="text-muted small mb-2">Campos reconocidos:</div>
          <div class="imp-fields">
            <?php foreach (['Name *','U_ItemNameEq','U_InventryNo','U_AssetSerNo','U_Marca','U_Modelo','U_TIP_EMH','U_Cod_Tipo','U_Fecha_Adq','U_Criterio','U_Nivel_Criterio','U_UBICACION','U_Estado','U_FECHA_BAJA','U_Vida_Util'] as $f): ?>
              <span><?= htmlspecialchars($f) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="alert alert-info mt-3 mb-0 py-2 small">
            En modo <b>Sin cabecera</b>: A=Name, B=U_ItemNameEq, C=U_InventryNo, D=U_AssetSerNo,
            E=U_Marca, F=U_Modelo, G=U_TIP_EMH, H=U_Cod_Tipo, I=U_Fecha_Adq,
            J=U_Criterio, K=U_Nivel_Criterio, L=U_UBICACION, M=U_Estado,
            N=U_FECHA_BAJA, O=U_Vida_Util. El Code SAP no va en el Excel; se genera automatico.
          </div>
        </div>

        <div class="imp-progress mt-3" id="boxProgress">
          <div class="d-flex justify-content-between">
            <strong id="statusText">Listo</strong>
            <span id="counterText" class="text-muted small"></span>
          </div>
          <div class="imp-bar my-2"><span id="bar"></span></div>
          <div class="mb-2">
            <button class="btn btn-primary btn-sm" type="button" id="btnStart">
              <i class="fas fa-database mr-1"></i>Iniciar importacion
            </button>
          </div>
          <div class="imp-log" id="log"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
(function(){
  const apiUrl = <?= json_encode($apiUrl) ?>;
  const form = document.getElementById('formImportEquipos');
  const box = document.getElementById('boxProgress');
  const btnStart = document.getElementById('btnStart');
  const logEl = document.getElementById('log');
  const bar = document.getElementById('bar');
  const statusText = document.getElementById('statusText');
  const counterText = document.getElementById('counterText');
  let token = '';
  let start = 2;
  let chunk = 1000;
  let totals = {inserted:0, updated:0, skipped:0, errors:0};

  function log(msg){ logEl.textContent += msg + "\n"; logEl.scrollTop = logEl.scrollHeight; }
  function setStatus(msg){ statusText.textContent = msg; }
  function updateCounter(){
    counterText.textContent = `Insertados: ${totals.inserted} | Actualizados: ${totals.updated} | Omitidos: ${totals.skipped} | Errores: ${totals.errors}`;
  }

  form.addEventListener('submit', async function(e){
    e.preventDefault();
    logEl.textContent = '';
    totals = {inserted:0, updated:0, skipped:0, errors:0};
    updateCounter();
    setStatus('Subiendo archivo...');
    box.style.display = 'block';
    bar.style.width = '0%';
    btnStart.disabled = true;

    const fd = new FormData(form);
    fd.append('action', 'prepare');
    const resp = await fetch(apiUrl, {method:'POST', body:fd});
    const json = await resp.json();
    if (!resp.ok || !json.success) {
      setStatus('Error');
      log(json.error || 'No se pudo preparar el archivo.');
      return;
    }
    token = json.token;
    start = json.start || 2;
    chunk = json.chunk || 1000;
    setStatus('Archivo listo');
    log(json.message);
    btnStart.disabled = false;
  });

  async function processNext(){
    const body = new URLSearchParams({action:'batch', token, start:String(start), chunk:String(chunk)});
    const resp = await fetch(apiUrl, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
      body:body.toString()
    });
    const json = await resp.json();
    if (!resp.ok || !json.success) throw new Error(json.error || 'Error en lote');

    totals.inserted += json.inserted || 0;
    totals.updated += json.updated || 0;
    totals.skipped += json.skipped || 0;
    totals.errors += json.errors_count || 0;
    updateCounter();
    log(json.message);
    (json.errors || []).forEach(e => log(`OMITIDA fila ${e.row}: ${e.error} | Name=${e.name || ''}`));

    if (json.done) {
      bar.style.width = '100%';
      setStatus('Terminado');
      btnStart.disabled = false;
      btnStart.textContent = 'Carga terminada';
      return;
    }
    start = json.nextStart;
    bar.style.width = Math.min(95, (start % 20000) / 200) + '%';
    setTimeout(processNext, 80);
  }

  btnStart.addEventListener('click', function(){
    btnStart.disabled = true;
    btnStart.textContent = 'Procesando...';
    setStatus('Procesando lotes...');
    processNext().catch(err => {
      setStatus('Error');
      log('ERROR: ' + err.message);
      btnStart.disabled = false;
      btnStart.textContent = 'Reintentar desde fila ' + start;
    });
  });
})();
</script>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
