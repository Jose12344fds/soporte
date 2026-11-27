<?php
/**
 * Módulo CarpetComUser — Portal de documentos del usuario
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Carpeta Compartida';
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';

require_permiso('CarpetComUser', 'ver');

$user    = current_user();
$userId  = (int)$user['id'];
$isAdmin = (int)($user['user_level'] ?? 99) <= 1;
$puedeSubir = tiene_permiso('CarpetComUser', 'crear');
$uploadLimitLabel = (string)ini_get('upload_max_filesize');
$uploadLimitNumber = (float)$uploadLimitLabel;
$uploadLimitUnit = strtolower(substr(trim($uploadLimitLabel), -1));
$uploadLimitBytes = (int)($uploadLimitNumber * 1024 * 1024);
if ($uploadLimitUnit === 'g') $uploadLimitBytes = (int)($uploadLimitNumber * 1024 * 1024 * 1024);
if ($uploadLimitUnit === 'k') $uploadLimitBytes = (int)($uploadLimitNumber * 1024);

$extraCss = [
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    'https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css',
];

include_once __DIR__ . '/../../layouts/header.php';
?>

<style>
/* ── Zona de carga ─────────────────────────────── */
.drop-zone {
    border: 3px dashed #c3cde0;
    border-radius: 18px;
    padding: 48px 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    background: #f8f9ff;
}
.drop-zone.dragover {
    border-color: #667eea;
    background: #eef0ff;
}
.drop-zone .dz-icon { font-size: 3.5rem; color: #c3cde0; }
.drop-zone.dragover .dz-icon { color: #667eea; }

/* ── Lista de archivos pendientes ──────────────── */
.pending-list { list-style: none; padding: 0; margin: 0; }
.pending-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px; border-radius: 8px;
    background: #fff; border: 1px solid #e9ecef;
    margin-bottom: 6px; font-size: .9rem;
}
.pending-item .pi-icon { font-size: 1.4rem; flex-shrink: 0; }
.pending-item .pi-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pending-item .pi-size { color: #6c757d; font-size: .8rem; flex-shrink: 0; }
.pending-item .pi-remove { cursor: pointer; color: #dc3545; }
.pending-item .pi-prog { width: 100%; height: 4px; border-radius: 2px; background: #e9ecef; }
.pending-item .pi-prog-bar { height: 4px; border-radius: 2px; background: linear-gradient(135deg,#667eea,#764ba2); transition: width .3s; }

/* ── Tarjetas de archivo ───────────────────────── */
.file-card {
    border-radius: 14px; border: 1px solid #e9ecef;
    padding: 18px 16px; background: #fff;
    transition: box-shadow .2s, transform .1s;
    height: 100%;
}
.file-card:hover { box-shadow: 0 4px 18px rgba(102,126,234,.15); transform: translateY(-2px); }
.file-card .fc-icon { font-size: 2.8rem; margin-bottom: 10px; }
.file-card .fc-name {
    font-size: .88rem; font-weight: 600;
    word-break: break-all;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}
.file-card .fc-meta { font-size: .76rem; color: #6c757d; margin-top: 4px; }
.file-card .fc-actions { margin-top: 12px; display: flex; gap: 6px; flex-wrap: wrap; }
.file-card .fc-actions .btn { font-size: .75rem; padding: 4px 8px; border-radius: 6px; }

/* ── Badges de compartidos ─────────────────────── */
.shared-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .72rem; padding: 2px 8px; border-radius: 20px;
    background: #eef0ff; color: #667eea; margin: 2px;
    cursor: pointer;
}
.shared-badge:hover { background: #dde2ff; }

/* ── Tabs ──────────────────────────────────────── */
.nav-tabs .nav-link { border-radius: 10px 10px 0 0; font-weight: 600; }
.nav-tabs .nav-link.active { color: #667eea; border-bottom-color: #fff; }

/* ── Empty state ───────────────────────────────── */
.empty-state { text-align: center; padding: 60px 20px; color: #9ea8b5; }
.empty-state i { font-size: 4rem; margin-bottom: 16px; opacity: .5; }

/* ── Miniaturas de imagen ─────────────────────── */
.fc-thumb {
    width: 100%; height: 120px; object-fit: cover;
    border-radius: 10px; margin-bottom: 10px;
    cursor: pointer; background: #f0f0f0;
    transition: opacity .2s, transform .15s;
    display: block;
}
.fc-thumb:hover { opacity: .88; transform: scale(1.02); }

/* ── Etiqueta de área ──────────────────────────── */
.area-tag {
    display: inline-block; font-size: .68rem;
    padding: 1px 8px; border-radius: 20px;
    background: #e8f5e9; color: #2e7d32;
    font-weight: 600; margin-bottom: 4px;
}

/* ── Posición relativa en tarjeta (necesaria para checkbox) */
.file-card { position: relative; }

/* ── Checkbox de selección sobre la tarjeta ──────────────── */
.fc-select-check {
    position: absolute; top: 8px; right: 8px; z-index: 10;
    width: 26px; height: 26px; cursor: pointer; margin: 0; display: block;
}
.fc-select-check input { display: none; }
.fc-check-icon {
    display: flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 50%;
    border: 2px solid #c3cde0; background: rgba(255,255,255,.9);
    font-size: .85rem; transition: all .15s;
}
.fc-select-check input:checked ~ .fc-check-icon {
    background: #667eea; border-color: #667eea; color: #fff;
}
.fc-select-check input:checked ~ .fc-check-icon::after {
    content: '\2713'; color: #fff; font-weight: bold;
}
.file-card.fc-selected {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,.2);
}

/* ── Toolbar de descarga masiva ──────────────────────────── */
.bulk-toolbar {
    display: none; align-items: center; gap: 12px; flex-wrap: wrap;
    padding: 10px 16px; border-radius: 10px;
    background: #eef0ff; border: 1px solid #cdd4ff;
    margin-bottom: 14px;
}
.bulk-toolbar.visible { display: flex; }
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-folder-open mr-2" style="color:#667eea"></i>Carpeta Compartida</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                    <li class="breadcrumb-item active">Carpeta Compartida</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php if ($isAdmin): ?>
        <div class="alert alert-info alert-dismissible py-2">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-shield-alt mr-2"></i>
            <strong>Modo administrador.</strong>
            <a href="admin.php" class="ml-2 btn btn-sm btn-outline-primary">
                <i class="fas fa-cog mr-1"></i>Panel de Administración
            </a>
        </div>
        <?php endif; ?>

        <?php if ($puedeSubir): ?>
        <!-- ── Zona de carga ──────────────────────────────── -->
        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cloud-upload-alt mr-2"></i>Subir Archivos</h3>
                <div class="card-tools">
                    <small class="text-muted">PNG, JPG, PDF, Word, Excel, videos (MP4, MOV, WebM, AVI, MKV) — máx. <?php echo htmlspecialchars($uploadLimitLabel); ?> c/u</small>
                </div>
            </div>
            <div class="card-body">
                <div class="drop-zone" id="dropZone">
                    <i class="fas fa-cloud-upload-alt dz-icon"></i>
                    <p class="mt-3 mb-1 font-weight-bold">Arrastra tus archivos aquí</p>
                    <p class="text-muted mb-3">o haz clic para seleccionar</p>
                    <input type="file" id="fileInput" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.mp4,.mov,.webm,.avi,.mkv" style="display:none">
                    <button type="button" class="btn btn-outline-primary btn-sm"
                            onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-folder-open mr-1"></i>Seleccionar archivos
                    </button>
                </div>

                <!-- Lista de archivos pendientes -->
                <ul class="pending-list mt-3" id="pendingList"></ul>

                <div id="uploadActions" style="display:none" class="mt-3 d-flex gap-3 align-items-center" style="gap:10px">
                    <button id="btnUpload" class="btn btn-primary"
                            style="background:linear-gradient(135deg,#667eea,#764ba2);border:none;border-radius:10px">
                        <i class="fas fa-paper-plane mr-2"></i>Subir todos
                    </button>
                    <button class="btn btn-outline-secondary btn-sm ml-2" onclick="limpiarPendientes()">
                        <i class="fas fa-trash mr-1"></i>Limpiar
                    </button>
                    <span id="uploadStatus" class="text-muted ml-2"></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Tabs mis archivos / compartidos ─────────────── -->
        <div class="card">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="archivosTab">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tabMios">
                            <i class="fas fa-folder mr-1"></i>Mis Archivos
                            <span class="badge badge-primary ml-1" id="cntMios">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tabCompartidos">
                            <i class="fas fa-share-alt mr-1"></i>Compartidos conmigo
                            <span class="badge badge-info ml-1" id="cntComp">0</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body tab-content pt-3">
                <!-- Mis archivos -->
                <div class="tab-pane active" id="tabMios">
                    <div id="loadingMios" class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <!-- Toolbar selección múltiple -->
                    <div id="toolbarMios" class="bulk-toolbar">
                        <div class="custom-control custom-checkbox mb-0">
                            <input type="checkbox" class="custom-control-input" id="chkSelectAll">
                            <label class="custom-control-label font-weight-bold" for="chkSelectAll">Todos</label>
                        </div>
                        <span id="selCount" class="text-muted small"></span>
                        <div class="ml-auto d-flex flex-wrap" style="gap:8px">
                            <button class="btn btn-sm btn-primary" onclick="descargarSeleccionados()">
                                <i class="fas fa-download mr-1"></i>Descargar seleccionados
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarSeleccionados()">
                                <i class="fas fa-trash mr-1"></i>Eliminar seleccionados
                            </button>
                        </div>
                    </div>
                    <div class="row" id="gridMios"></div>
                </div>
                <!-- Compartidos conmigo -->
                <div class="tab-pane" id="tabCompartidos">
                    <div id="loadingComp" class="text-center py-4">
                        <div class="spinner-border text-info"></div>
                    </div>
                    <div class="row" id="gridComp"></div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Modal compartir -->
<div class="modal fade" id="modalCompartir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff">
                <h5 class="modal-title"><i class="fas fa-share-alt mr-2"></i>Compartir archivo</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2" id="shareFileName"></p>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="font-weight-bold mb-0">Seleccionar usuarios:</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="chkSoloArea" checked>
                        <label class="custom-control-label small" for="chkSoloArea">Solo mi área</label>
                    </div>
                </div>
                <select id="selectUsuarios" multiple style="width:100%"></select>

                <!-- Ya compartido con -->
                <div id="yaCompartidoCon" class="mt-3" style="display:none">
                    <label class="font-weight-bold">Ya compartido con:</label>
                    <div id="yaCompartidoList"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarCompartir"
                        style="background:linear-gradient(135deg,#667eea,#764ba2);border:none">
                    <i class="fas fa-share-alt mr-1"></i>Compartir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal preview imagen -->
<div class="modal fade" id="modalPreview" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-2 d-flex align-items-center">
                <h6 class="modal-title mr-auto" id="previewTitle"></h6>
                <a id="previewDownloadBtn" href="#" class="btn btn-sm btn-outline-secondary mr-2" title="Descargar">
                    <i class="fas fa-download"></i>
                </a>
                <button type="button" class="close ml-0" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center p-2" style="min-height:220px">
                <div id="previewLoading" class="py-5">
                    <div class="spinner-border text-primary" style="width:3rem;height:3rem"></div>
                    <p class="mt-2 text-muted">Cargando imagen...</p>
                </div>
                <img id="previewImg" src="" style="max-width:100%;max-height:80vh;border-radius:8px;display:none" alt="preview">
            </div>
        </div>
    </div>
</div>

<!-- Modal preview PDF -->
<div class="modal fade" id="modalPdf" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-2 d-flex align-items-center"
                 style="background:linear-gradient(135deg,#e53935,#e35d5b);color:#fff">
                <h6 class="modal-title mr-auto">
                    <i class="fas fa-file-pdf mr-2"></i><span id="pdfTitleText"></span>
                </h6>
                <a id="pdfDownloadBtn" href="#" class="btn btn-sm btn-outline-light mr-2" title="Descargar">
                    <i class="fas fa-download"></i>
                </a>
                <button type="button" class="close ml-0 text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0" style="height:83vh">
                <iframe id="pdfFrame" src="" style="width:100%;height:100%;border:none"></iframe>
            </div>
        </div>
    </div>
</div>

<?php
$BASE_URL_API = BASE_URL . '/api/CarpetComUser';
ob_start();
?>
var API = '<?php echo $BASE_URL_API; ?>';
var pendingFiles = [];
var MAX_UPLOAD_FILE_BYTES = <?php echo $uploadLimitBytes; ?>;
var uploadSessionTotal = 0;
var uploadSessionDone = 0;
var archivoCompartirId = null;
function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? (meta.getAttribute('content') || '') : '';
}
var archivosRegistry = {}; // { id: archivoObj } — evita JSON.stringify en onclick

// ── Iconos y colores por extensión ──────────────────────────
function fileIcon(ext) {
    ext = (ext||'').toLowerCase();
    if (['jpg','jpeg','png'].indexOf(ext) >= 0) return {icon:'fas fa-file-image', color:'#764ba2'};
    if (['mp4','mov','webm','avi','mkv'].indexOf(ext) >= 0) return {icon:'fas fa-file-video', color:'#e67e22'};
    if (ext === 'pdf')                           return {icon:'fas fa-file-pdf',   color:'#dc3545'};
    if (['doc','docx'].indexOf(ext) >= 0)        return {icon:'fas fa-file-word',  color:'#4285f4'};
    if (['xls','xlsx'].indexOf(ext) >= 0)        return {icon:'fas fa-file-excel', color:'#0f9d58'};
    return {icon:'fas fa-file', color:'#6c757d'};
}
function formatSize(b) {
    if (b < 1024)     return b + ' B';
    if (b < 1048576)  return (b/1024).toFixed(1) + ' KB';
    return (b/1048576).toFixed(2) + ' MB';
}
function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Zona de carga ───────────────────────────────────────────
var dropZone  = document.getElementById('dropZone');
var fileInput = document.getElementById('fileInput');

if (dropZone) {
    dropZone.addEventListener('dragover',  function(e){ e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', function(){ dropZone.classList.remove('dragover'); });
    dropZone.addEventListener('drop', function(e){
        e.preventDefault();
        dropZone.classList.remove('dragover');
        addFiles(e.dataTransfer.files);
    });
}
if (fileInput) {
    fileInput.addEventListener('change', function(){ addFiles(this.files); this.value = ''; });
}

function addFiles(fileList) {
    var allowed = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx','mp4','mov','webm','avi','mkv'];
    for (var i = 0; i < fileList.length; i++) {
        var f   = fileList[i];
        var ext = f.name.split('.').pop().toLowerCase();
        if (allowed.indexOf(ext) < 0) {
            App.toast(f.name + ': tipo no permitido', 'warning');
            continue;
        }
        if (f.size > MAX_UPLOAD_FILE_BYTES) {
            App.toast(f.name + ': excede el límite activo de ' + formatSize(MAX_UPLOAD_FILE_BYTES), 'warning');
            continue;
        }
        pendingFiles.push(f);
    }
    renderPending();
}

function renderPending() {
    var ul = document.getElementById('pendingList');
    var actions = document.getElementById('uploadActions');
    ul.innerHTML = '';
    pendingFiles.forEach(function(f, idx) {
        var fi = fileIcon(f.name.split('.').pop());
        var li = document.createElement('li');
        li.className = 'pending-item';
        li.id = 'pi-' + idx;
        li.innerHTML =
            '<i class="pi-icon ' + fi.icon + '" style="color:' + fi.color + '"></i>' +
            '<span class="pi-name">' + escHtml(f.name) + '</span>' +
            '<span class="pi-size">' + formatSize(f.size) + '</span>' +
            '<i class="pi-remove fas fa-times" onclick="removePending(' + idx + ')"></i>' +
            '<div style="width:100%;margin-top:4px"><div class="pi-prog"><div class="pi-prog-bar" id="bar-' + idx + '" style="width:0%"></div></div></div>';
        ul.appendChild(li);
    });
    if (actions) actions.style.display = pendingFiles.length ? 'flex' : 'none';
}

function removePending(idx) {
    pendingFiles.splice(idx, 1);
    renderPending();
}

function limpiarPendientes() {
    pendingFiles = [];
    renderPending();
}

// Enviar lotes pequenos para no superar post_max_size ni agotar el tiempo.
document.getElementById('btnUpload') && document.getElementById('btnUpload').addEventListener('click', function() {
    if (!pendingFiles.length) return;
    uploadSessionTotal = pendingFiles.length;
    uploadSessionDone = 0;
    uploadBatch();
});

function nextUploadBatch() {
    var maxFiles = 4;
    var maxBytes = 80 * 1024 * 1024;
    var batch = [];
    var bytes = 0;
    for (var i = 0; i < pendingFiles.length && batch.length < maxFiles; i++) {
        var file = pendingFiles[i];
        if (batch.length && bytes + file.size > maxBytes) break;
        batch.push(file);
        bytes += file.size;
    }
    return batch;
}

function uploadBatch() {
    var batch = nextUploadBatch();
    if (!batch.length) {
        document.getElementById('uploadStatus').textContent = '¡Listo! ' + uploadSessionDone + ' archivo(s) subido(s).';
        App.toast(uploadSessionDone + ' archivo(s) subido(s) correctamente.', 'success');
        pendingFiles = [];
        renderPending();
        loadArchivos();
        return;
    }

    var fd = new FormData();
    batch.forEach(function(f){ fd.append('files[]', f); });

    document.getElementById('btnUpload').disabled = true;
    document.getElementById('uploadStatus').textContent = 'Subiendo ' + (uploadSessionDone + 1) + '–'
        + (uploadSessionDone + batch.length) + ' de ' + uploadSessionTotal + '...';

    // Progreso visual
    var xhr = new XMLHttpRequest();
    xhr.upload.addEventListener('progress', function(e){
        if (!e.lengthComputable) return;
        var pct = Math.round(e.loaded / e.total * 100);
        for (var j = 0; j < batch.length; j++) {
            var bar = document.getElementById('bar-' + j);
            if (bar) bar.style.width = pct + '%';
        }
    });
    xhr.addEventListener('load', function(){
        var res = null;
        try { res = JSON.parse(xhr.responseText); } catch(e) {
            console.error('Respuesta no valida al subir archivos:', xhr.responseText);
        }
        if (!res) {
            App.toast('El servidor devolvió una respuesta no válida. Ningún archivo pendiente será descartado.', 'error');
            document.getElementById('btnUpload').disabled = false;
            return;
        }
        if (xhr.status < 200 || xhr.status >= 300) {
            App.toast(res.error || 'No se pudo subir. Codigo HTTP ' + xhr.status, 'error');
            document.getElementById('btnUpload').disabled = false;
            return;
        }
        if (res.error) {
            App.toast(res.error, 'error');
            document.getElementById('btnUpload').disabled = false;
            return;
        }

        var uploaded = Array.isArray(res.subidos) ? res.subidos : [];
        var errors = Array.isArray(res.errores) ? res.errores : [];
        if (errors.length) {
            var uploadedIndexes = {};
            uploaded.forEach(function(item){ uploadedIndexes[Number(item.indice)] = true; });
            pendingFiles = pendingFiles.filter(function(file, index){
                return index >= batch.length || !uploadedIndexes[index];
            });
            uploadSessionDone += uploaded.length;
            renderPending();
            errors.forEach(function(message){ App.toast(message, 'error'); });
            document.getElementById('uploadStatus').textContent = uploadSessionDone + ' de ' + uploadSessionTotal
                + ' subido(s). Quedan ' + pendingFiles.length + ' por revisar.';
            document.getElementById('btnUpload').disabled = false;
            return;
        }

        if (!res.ok || uploaded.length !== batch.length) {
            App.toast('El servidor no confirmó todos los archivos del lote. Los pendientes se conservaron.', 'error');
            document.getElementById('btnUpload').disabled = false;
            return;
        }

        pendingFiles.splice(0, batch.length);
        uploadSessionDone += batch.length;
        renderPending();
        document.getElementById('btnUpload').disabled = false;
        uploadBatch();
    });
    xhr.addEventListener('error', function(){
        App.toast('Error de conexión al subir archivos.', 'error');
        document.getElementById('btnUpload').disabled = false;
    });
    xhr.addEventListener('timeout', function(){
        App.toast('El lote tardó demasiado. Los archivos pendientes se conservaron para reintentar.', 'error');
        document.getElementById('btnUpload').disabled = false;
    });
    xhr.open('POST', API + '/upload.php');
    xhr.timeout = 300000;
    var token = csrfToken();
    if (token) {
        xhr.setRequestHeader('X-CSRF-Token', token);
    }
    xhr.send(fd);
}

// ── Cargar y renderizar archivos ────────────────────────────
function loadArchivos() {
    $.get(API + '/get_archivos.php', function(res) {
        if (!res.ok) return;

        $('#loadingMios').hide();
        $('#loadingComp').hide();

        $('#cntMios').text(res.mis_archivos.length);
        $('#cntComp').text(res.compartidos.length);

        renderGrid('#gridMios', res.mis_archivos, true);
        renderGrid('#gridComp', res.compartidos, false);
        actualizarToolbar();
    });
}

function renderGrid(selector, archivos, esMio) {
    var $grid = $(selector);
    $grid.empty();
    if (!archivos.length) {
        $grid.html('<div class="col-12"><div class="empty-state"><i class="fas fa-folder-open"></i><p>No hay archivos aquí todavía.</p></div></div>');
        return;
    }
    archivos.forEach(function(a) {
        archivosRegistry[a.id] = a; // guardar en registro para acceder sin pasar strings en onclick
        var ext   = (a.extension || '').toLowerCase();
        var fi    = fileIcon(ext);
        var esImg = ['jpg','jpeg','png'].indexOf(ext) >= 0;
        var esPdf = ext === 'pdf';
        var partes = (a.created_at || '').substring(0,10).split('-');
        var fecha  = partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : '';

        var card = '<div class="col-6 col-md-4 col-lg-3 mb-3"><div class="file-card" data-id="' + a.id + '">';

        // Checkbox de selección (solo en mis archivos)
        if (esMio) {
            card += '<label class="fc-select-check" onclick="event.stopPropagation()">'
                  + '<input type="checkbox" class="file-check" value="' + a.id + '">'
                  + '<span class="fc-check-icon"></span></label>';
        }

        // Miniatura real para imágenes — sin loading=lazy para evitar la intervención del navegador
        if (esImg) {
            card += '<img class="fc-thumb" src="' + API + '/descargar.php?id=' + a.id + '&preview=1"'
                  + ' onclick="previewImg(' + a.id + ')"'
                  + ' onerror="this.style.display=\'none\'" alt="">';
        } else {
            card += '<div class="text-center fc-icon"><i class="' + fi.icon + '" style="color:' + fi.color + '"></i></div>';
        }

        card += '<div class="fc-name" title="' + escHtml(a.nombre_original) + '">' + escHtml(a.nombre_original) + '</div>';
        card += '<div class="fc-meta">' + formatSize(parseInt(a.tamano)) + ' &bull; ' + fecha + '</div>';
        if (a.area) {
            card += '<span class="area-tag"><i class="fas fa-building mr-1"></i>' + escHtml(a.area) + '</span>';
        }
        if (!esMio) {
            card += '<div class="fc-meta mt-1"><i class="fas fa-user mr-1"></i>' + escHtml(a.nombre_usuario) + '</div>';
        }

        card += '<div class="fc-actions">';
        // Descargar individual
        card += '<a href="' + API + '/descargar.php?id=' + a.id + '" class="btn btn-outline-secondary" title="Descargar"><i class="fas fa-download"></i></a>';
        // Ver imagen
        if (esImg) {
            card += '<button class="btn btn-outline-info" title="Ver imagen" onclick="previewImg(' + a.id + ')"><i class="fas fa-eye"></i></button>';
        }
        // Ver PDF
        if (esPdf) {
            card += '<button class="btn btn-outline-danger" title="Ver PDF" onclick="previewPdf(' + a.id + ')"><i class="fas fa-eye"></i></button>';
        }
        // Compartir y eliminar (solo propietario)
        if (esMio) {
            card += '<button class="btn btn-outline-primary" title="Compartir" onclick="abrirCompartir(' + a.id + ')"><i class="fas fa-share-alt"></i></button>';
            card += '<button class="btn btn-outline-danger" title="Eliminar" onclick="eliminarArchivo(' + a.id + ')"><i class="fas fa-trash"></i></button>';
        }
        card += '</div></div></div>';
        $grid.append(card);
    });
}

// ── Preview imagen ──────────────────────────────────────────
function previewImg(id) {
    var nombre = archivosRegistry[id] ? archivosRegistry[id].nombre_original : '';
    $('#previewTitle').text(nombre);
    $('#previewDownloadBtn').attr('href', API + '/descargar.php?id=' + id);
    $('#previewImg').hide().attr('src', '');
    $('#previewLoading').show().html('<div class="spinner-border text-primary" style="width:3rem;height:3rem"></div><p class="mt-2 text-muted">Cargando imagen...</p>');
    $('#modalPreview').modal('show');
    var img = new Image();
    img.onload = function() {
        $('#previewLoading').hide();
        $('#previewImg').attr('src', img.src).show();
    };
    img.onerror = function() {
        $('#previewLoading').html('<p class="text-danger mt-3"><i class="fas fa-exclamation-triangle mr-2"></i>No se pudo cargar la imagen.</p>');
    };
    img.src = API + '/descargar.php?id=' + id + '&preview=1';
}

// ── Preview PDF ──────────────────────────────────────
function previewPdf(id) {
    var nombre = archivosRegistry[id] ? archivosRegistry[id].nombre_original : '';
    $('#pdfTitleText').text(nombre);
    $('#pdfDownloadBtn').attr('href', API + '/descargar.php?id=' + id);
    $('#pdfFrame').attr('src', API + '/descargar.php?id=' + id + '&preview=1');
    $('#modalPdf').modal('show');
}
// Limpiar iframe al cerrar para detener descarga
$('#modalPdf').on('hidden.bs.modal', function() {
    $('#pdfFrame').attr('src', '');
});

// ── Eliminar ───────────────────────────────────────────────
function eliminarArchivo(id) {
    var nombre = archivosRegistry[id] ? archivosRegistry[id].nombre_original : 'este archivo';
    App.confirm('¿Eliminar archivo?', '"' + nombre + '" será eliminado permanentemente.', function() {
        $.ajax({
            url: API + '/eliminar.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({id: id}),
            success: function(res) {
                if (res.ok) {
                    App.toast('Archivo eliminado.', 'success');
                    loadArchivos();
                } else {
                    App.toast(res.error || 'Error al eliminar.', 'error');
                }
            }
        });
    });
}

// ── Compartir ──────────────────────────────────────────────
function abrirCompartir(id) {
    var nombre = archivosRegistry[id] ? archivosRegistry[id].nombre_original : '';
    archivoCompartirId = id;
    $('#shareFileName').html('<i class="fas fa-file mr-1"></i>' + escHtml(nombre));
    $('#yaCompartidoCon').hide();
    cargarUsuarios();
    $('#modalCompartir').modal('show');
}

function cargarUsuarios() {
    // Destruir select2 si ya estaba inicializado para evitar duplicados
    if ($('#selectUsuarios').hasClass('select2-hidden-accessible')) {
        $('#selectUsuarios').select2('destroy');
    }
    var soloArea = $('#chkSoloArea').is(':checked') ? '1' : '0';
    $.get(API + '/get_usuarios.php', { area: soloArea }, function(res) {
        var $sel = $('#selectUsuarios');
        $sel.empty();
        $('#noUsersMsg').remove();

        if (res.ok && res.usuarios && res.usuarios.length) {
            // Agrupar por área
            var byArea = {};
            res.usuarios.forEach(function(u) {
                var a = u.area || 'Sin área';
                if (!byArea[a]) byArea[a] = [];
                byArea[a].push(u);
            });
            Object.keys(byArea).forEach(function(area) {
                var $grp = $('<optgroup>').attr('label', area);
                byArea[area].forEach(function(u) {
                    var label = u.name + (u.subarea ? ' – ' + u.subarea : '');
                    $grp.append(new Option(label, u.id));
                });
                $sel.append($grp);
            });
        } else {
            $sel.after('<p id="noUsersMsg" class="text-warning small mt-2"><i class="fas fa-exclamation-triangle mr-1"></i>'
                + (soloArea === '1' ? 'No hay otros usuarios en tu área.' : 'No hay usuarios disponibles.')
                + '</p>');
        }

        $sel.select2({
            theme: 'bootstrap4',
            placeholder: 'Seleccionar usuarios...',
            dropdownParent: $('#modalCompartir'),
            width: '100%'
        });
    }).fail(function() {
        App.toast('Error al cargar usuarios', 'error');
    });
}

$('#chkSoloArea').on('change', cargarUsuarios);

$('#btnConfirmarCompartir').on('click', function() {
    var usuarios = $('#selectUsuarios').val();
    if (!usuarios || !usuarios.length) {
        App.toast('Selecciona al menos un usuario.', 'warning');
        return;
    }
    $.ajax({
        url: API + '/compartir.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ archivo_id: archivoCompartirId, usuarios: usuarios.map(Number) }),
        success: function(res) {
            if (res.ok) {
                App.toast('Compartido con ' + res.compartidos + ' usuario(s).', 'success');
                $('#modalCompartir').modal('hide');
            } else {
                App.toast(res.error || 'Error al compartir.', 'error');
            }
        }
    });
});

// ── Selección múltiple y descarga masiva ──────────────────
function actualizarToolbar() {
    var total  = $('#gridMios .file-check').length;
    var checks = $('#gridMios .file-check:checked').length;
    if (checks > 0) {
        $('#toolbarMios').addClass('visible');
        $('#selCount').text(checks + ' de ' + total + ' seleccionado(s)');
        var $chk = $('#chkSelectAll');
        if (checks === total) { $chk.prop('checked', true).prop('indeterminate', false); }
        else { $chk.prop('checked', false).prop('indeterminate', true); }
    } else {
        $('#toolbarMios').removeClass('visible');
        $('#selCount').text('');
        $('#chkSelectAll').prop('checked', false).prop('indeterminate', false);
    }
    // resaltar tarjetas seleccionadas
    $('#gridMios .file-check').each(function() {
        $(this).closest('.file-card').toggleClass('fc-selected', $(this).is(':checked'));
    });
}

function toggleSelectAll() {
    var todos = $('#chkSelectAll').is(':checked');
    $('#gridMios .file-check').prop('checked', todos);
    actualizarToolbar();
}

function descargarSeleccionados() {
    var ids = [];
    $('#gridMios .file-check:checked').each(function() { ids.push($(this).val()); });
    if (!ids.length) { App.toast('Selecciona al menos un archivo.', 'warning'); return; }
    App.toast('Descargando ' + ids.length + ' archivo(s)...', 'info');
    ids.forEach(function(id, i) {
        setTimeout(function() {
            var a = document.createElement('a');
            a.href = API + '/descargar.php?id=' + id;
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            setTimeout(function() { if (a.parentNode) a.parentNode.removeChild(a); }, 2000);
        }, i * 800);
    });
}

// ── Init ───────────────────────────────────────────────────
function eliminarSeleccionados() {
    var ids = [];
    $('#gridMios .file-check:checked').each(function() { ids.push(parseInt($(this).val(), 10)); });
    ids = ids.filter(Boolean);
    if (!ids.length) { App.toast('Selecciona al menos un archivo.', 'warning'); return; }

    var nombres = ids.map(function(id) {
        return archivosRegistry[id] ? archivosRegistry[id].nombre_original : '#' + id;
    });

    App.confirm(
        'Eliminar ' + ids.length + ' archivo' + (ids.length > 1 ? 's' : '') + '?',
        'Se eliminaran permanentemente: ' + nombres.slice(0, 5).join(', ') + (nombres.length > 5 ? ' (+' + (nombres.length - 5) + ' mas)' : ''),
        function() {
            $.ajax({
                url: API + '/eliminar.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ ids: ids }),
                success: function(res) {
                    if (res.ok) {
                        App.toast((res.deleted || 0) + ' archivo(s) eliminado(s).', 'success');
                        $('#chkSelectAll').prop('checked', false).prop('indeterminate', false);
                        loadArchivos();
                    } else {
                        App.toast(res.error || 'Error al eliminar.', 'error');
                    }
                },
                error: function() {
                    App.toast('Error de conexion al eliminar.', 'error');
                }
            });
        }
    );
}

$(document).ready(function() {
    loadArchivos();
    // Seleccionar todos
    $('#chkSelectAll').on('change', toggleSelectAll);
    // Delegado para checkboxes de tarjetas (se re-renderizan con loadArchivos)
    $(document).on('change', '#gridMios .file-check', actualizarToolbar);
});
<?php
$inlineJs = ob_get_clean();
include_once __DIR__ . '/../../layouts/footer.php';
?>
