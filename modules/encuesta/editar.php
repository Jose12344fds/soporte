<?php
/**
 * Módulo Encuestas — Editar encuesta existente
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Editar Encuesta';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('encuesta', 'editar');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    $session->msg('d', 'ID de encuesta inválido.');
    redirect('index.php', false);
    exit;
}

$user = current_user();
include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
/* Reutiliza los mismos estilos del constructor (agregar.php) */
.pregunta-card {
    border:1px solid #dee2e6;border-radius:10px;padding:18px;margin-bottom:14px;
    background:#fff;position:relative;transition:box-shadow .2s;
}
.pregunta-card:hover { box-shadow: 0 4px 15px rgba(102,126,234,.15); }
.pregunta-card .drag-handle { cursor:grab;color:#adb5bd; }
.pregunta-card .card-num {
    width:30px;height:30px;border-radius:50%;
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-weight:700;font-size:.85rem;flex-shrink:0;
}
.opcion-item { display:flex;gap:8px;align-items:center;margin-bottom:8px; }
.opcion-item input { flex:1; }
.tipo-badge { font-size:.75rem;border-radius:20px;padding:3px 10px;font-weight:600; }
.tipo-badge.unica    { background:#e3f2fd;color:#1565c0; }
.tipo-badge.multiple { background:#f3e5f5;color:#6a1b9a; }
.tipo-badge.abierta  { background:#e8f5e9;color:#2e7d32; }
.tipo-badge.escala   { background:#fff8e1;color:#e65100; }
#sortable-preguntas .ui-sortable-placeholder {
    border:2px dashed #667eea !important;background:#f0f3ff !important;
    border-radius:10px;height:60px;visibility:visible !important;
}
</style>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-edit mr-2" style="color:#667eea"></i>Editar Encuesta</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Encuestas</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <!-- Cargando datos -->
                <div id="loadingPanel" class="text-center py-5">
                    <div class="spinner-border text-primary" style="width:3rem;height:3rem"></div>
                    <p class="mt-3 text-muted">Cargando encuesta...</p>
                </div>

                <div id="mainPanel" style="display:none">
                    <div class="row">
                        <div class="col-md-9">
                            <!-- Datos generales -->
                            <div class="card mb-3" style="border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)">
                                <div class="card-header" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:12px 12px 0 0">
                                    <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Datos Generales</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Título <span class="text-danger">*</span></label>
                                                <input type="text" id="enc_titulo" class="form-control form-control-lg" maxlength="255">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="font-weight-bold">Descripción</label>
                                        <textarea id="enc_descripcion" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Fecha de Cierre</label>
                                                <input type="date" id="enc_cierre" class="form-control">
                                                <small class="text-muted">Dejar vacío = sin fecha límite</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Estado</label>
                                                <div class="d-flex align-items-center mt-2">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="enc_activa">
                                                        <label class="custom-control-label" for="enc_activa">
                                                            <span id="estadoLabel">Activa</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Constructor de preguntas -->
                            <div class="card" style="border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)">
                                <div class="card-header" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:12px 12px 0 0">
                                    <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Preguntas</h3>
                                    <div class="card-tools">
                                        <span class="badge badge-light" id="contPregCount">0 preguntas</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="sortable-preguntas"></div>
                                    <div class="text-center mt-2" id="emptyMsg" style="display:none">
                                        <p class="text-muted">Sin preguntas. Agrega usando el panel derecho.</p>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times mr-1"></i>Cancelar
                                    </a>
                                    <button onclick="guardarEncuesta()" class="btn btn-success" id="btnGuardar">
                                        <i class="fas fa-save mr-1"></i>Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Panel lateral -->
                        <div class="col-md-3">
                            <div class="card sticky-top" style="top:80px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 font-weight-bold"><i class="fas fa-plus-circle mr-1 text-primary"></i>Agregar Pregunta</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Tipo</label>
                                        <select id="newTipo" class="form-control form-control-sm">
                                            <option value="unica">🔘 Opción Única</option>
                                            <option value="multiple">☑️ Múltiple</option>
                                            <option value="abierta">📝 Abierta</option>
                                            <option value="escala">⭐ Escala</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Texto</label>
                                        <textarea id="newTexto" class="form-control form-control-sm" rows="3" placeholder="Pregunta..."></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="newRequerida" checked>
                                        <label class="form-check-label small" for="newRequerida">Requerida</label>
                                    </div>
                                    <button onclick="agregarPregunta()" class="btn btn-primary btn-block btn-sm" style="background:linear-gradient(135deg,#667eea,#764ba2);border:none">
                                        <i class="fas fa-plus mr-1"></i>Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

<?php
$userId = (int)$user['id'];
$inlineJs = <<<JS
var preguntasData = [];
var USERID  = {$userId};
var ENC_ID  = {$id};

var tipoBadges = {
    unica:    { label:'Única',    cls:'unica'    },
    multiple: { label:'Múltiple', cls:'multiple' },
    abierta:  { label:'Abierta',  cls:'abierta'  },
    escala:   { label:'Escala',   cls:'escala'   }
};

$(document).ready(function() {
    // Cargar encuesta y preguntas
    $.when(
        $.get('../../api/encuesta/get_encuestas.php'),
        $.get('../../api/encuesta/get_preguntas.php', { id: ENC_ID })
    ).done(function(resEnc, resPreg) {
        var encuestas = resEnc[0];
        var enc = encuestas.find(function(e){ return e.id_encuesta == ENC_ID; });
        if (!enc) { App.toast('Encuesta no encontrada', 'error'); return; }

        $('#enc_titulo').val(enc.titulo);
        $('#enc_descripcion').val(enc.descripcion || '');
        $('#enc_cierre').val(enc.fecha_cierre || '');
        $('#enc_activa').prop('checked', enc.activa == 1);
        actualizarEstadoLabel();

        preguntasData = resPreg[0];
        preguntasData.forEach(function(p, i) { renderPreguntaCard(p, i); });
        actualizarContador();

        $('#loadingPanel').hide();
        $('#mainPanel').show();
        inicializarSortable();
    });

    $('#enc_activa').on('change', actualizarEstadoLabel);
});

function actualizarEstadoLabel() {
    var checked = $('#enc_activa').is(':checked');
    $('#estadoLabel').text(checked ? 'Activa' : 'Inactiva')
                     .toggleClass('text-success', checked)
                     .toggleClass('text-muted', !checked);
}

function inicializarSortable() {
    $('#sortable-preguntas').sortable({
        handle: '.drag-handle',
        placeholder: 'ui-sortable-placeholder',
        update: function() { renumerarPreguntas(); }
    });
}

function agregarPregunta() {
    var tipo     = $('#newTipo').val();
    var texto    = $('#newTexto').val().trim();
    var requerida= $('#newRequerida').is(':checked') ? 1 : 0;
    if (!texto) { App.toast('Escribe el texto de la pregunta', 'warning'); return; }
    var opciones = [];
    if (tipo === 'escala') {
        opciones = [
            {texto:'1 - Muy insatisfecho', valor:1},{texto:'2 - Insatisfecho',valor:2},
            {texto:'3 - Neutral',valor:3},{texto:'4 - Satisfecho',valor:4},
            {texto:'5 - Muy satisfecho',valor:5}
        ];
    } else if (tipo !== 'abierta') {
        opciones = [{texto:'', valor:1}];
    }
    var p = { texto:texto, tipo:tipo, requerida:requerida, opciones:opciones };
    preguntasData.push(p);
    renderPreguntaCard(p, preguntasData.length - 1);
    $('#newTexto').val('');
    actualizarContador();
}

function renderPreguntaCard(p, idx) {
    $('#emptyMsg').hide();
    var badge = tipoBadges[p.tipo];
    var html  = '<div class="pregunta-card" data-idx="' + idx + '">';
    html += '<div class="d-flex align-items-start gap-3 mb-3">';
    html += '  <i class="fas fa-grip-vertical drag-handle mr-2 mt-1"></i>';
    html += '  <div class="card-num mr-2">' + (idx + 1) + '</div>';
    html += '  <div class="flex-grow-1">';
    html += '    <input type="text" class="form-control form-control-sm font-weight-bold"';
    html += '           value="' + escHtml(p.texto) + '" data-field="texto"';
    html += '           onchange="updatePregunta(' + idx + ',\'texto\',this.value)">';
    html += '  </div>';
    html += '  <span class="badge tipo-badge ' + badge.cls + ' ml-2">' + badge.label + '</span>';
    html += '  <button onclick="eliminarPregunta(' + idx + ')" class="btn btn-sm btn-outline-danger ml-1"><i class="fas fa-trash"></i></button>';
    html += '</div>';
    if (p.tipo !== 'abierta') {
        html += '<div class="ml-5 opciones-wrapper">' + renderOpciones(p.opciones, idx, p.tipo);
        html += '<button onclick="agregarOpcion(' + idx + ')" class="btn btn-sm btn-outline-primary mt-2 ' + (p.tipo==='escala'?'d-none':'') + '"><i class="fas fa-plus mr-1"></i>Opción</button></div>';
    } else {
        html += '<div class="ml-5"><textarea class="form-control form-control-sm text-muted" rows="2" disabled placeholder="Respuesta abierta..."></textarea></div>';
    }
    html += '<div class="mt-2 ml-5"><div class="custom-control custom-switch custom-switch-sm">';
    html += '  <input type="checkbox" class="custom-control-input" id="req' + idx + '"' + (p.requerida?' checked':'') + ' onchange="updatePregunta(' + idx + ',\'requerida\',this.checked?1:0)">';
    html += '  <label class="custom-control-label small" for="req' + idx + '">Requerida</label>';
    html += '</div></div></div>';
    $('#sortable-preguntas').append(html);
}

function renderOpciones(opciones, pIdx, tipo) {
    var html = '';
    opciones.forEach(function(op, oIdx) {
        var icono = tipo==='unica'?'far fa-dot-circle':(tipo==='escala'?'fas fa-star':'far fa-check-square');
        html += '<div class="opcion-item" data-oidx="' + oIdx + '">';
        html += '  <i class="' + icono + ' text-muted mr-1"></i>';
        html += '  <input type="text" class="form-control form-control-sm" placeholder="Opción ' + (oIdx+1) + '" value="' + escHtml(op.texto) + '" ' + (tipo==='escala'?'readonly':'') + ' onchange="updateOpcion(' + pIdx + ',' + oIdx + ',this.value)">';
        if (tipo !== 'escala') {
            html += '  <button onclick="eliminarOpcion(' + pIdx + ',' + oIdx + ')" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>';
        }
        html += '</div>';
    });
    return html;
}

function updatePregunta(idx, field, value) { preguntasData[idx][field] = value; }
function updateOpcion(pIdx, oIdx, value)   { preguntasData[pIdx].opciones[oIdx].texto = value; }
function agregarOpcion(pIdx) {
    var oIdx = preguntasData[pIdx].opciones.length;
    preguntasData[pIdx].opciones.push({ texto:'', valor: oIdx+1 });
    redrawPreguntaCard(pIdx);
}
function eliminarOpcion(pIdx, oIdx) {
    preguntasData[pIdx].opciones.splice(oIdx, 1);
    redrawPreguntaCard(pIdx);
}
function eliminarPregunta(idx) {
    preguntasData.splice(idx, 1);
    $('#sortable-preguntas').empty();
    preguntasData.forEach(function(p, i) { renderPreguntaCard(p, i); });
    if (!preguntasData.length) $('#emptyMsg').show();
    actualizarContador();
}
function redrawPreguntaCard(idx) {
    var card = $('[data-idx="' + idx + '"]');
    card.remove();
    renderPreguntaCard(preguntasData[idx], idx);
}
function renumerarPreguntas() {
    var newOrder = [];
    $('#sortable-preguntas .pregunta-card').each(function(i) {
        var idx = parseInt($(this).data('idx'));
        newOrder.push(preguntasData[idx]);
        $(this).find('.card-num').text(i+1);
    });
    preguntasData = newOrder;
    $('#sortable-preguntas .pregunta-card').each(function(i){ $(this).attr('data-idx', i); });
}
function actualizarContador() {
    var n = preguntasData.length;
    $('#contPregCount').text(n + (n===1?' pregunta':' preguntas'));
}

function guardarEncuesta() {
    var titulo = $('#enc_titulo').val().trim();
    if (!titulo) { App.toast('El título es obligatorio', 'warning'); return; }
    if (!preguntasData.length) { App.toast('Agrega al menos una pregunta', 'warning'); return; }

    var btn = $('#btnGuardar');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...');

    // Sincronizar textos
    preguntasData.forEach(function(p, pIdx) {
        $('[data-idx="' + pIdx + '"] .opciones-wrapper .opcion-item').each(function(oIdx) {
            if (p.opciones[oIdx]) p.opciones[oIdx].texto = $(this).find('input').val();
        });
        var t = $('[data-idx="' + pIdx + '"] input[data-field="texto"]').val();
        if (t) p.texto = t;
    });

    var payload = {
        id_encuesta:  ENC_ID,
        titulo:       titulo,
        descripcion:  $('#enc_descripcion').val().trim(),
        fecha_cierre: $('#enc_cierre').val() || null,
        activa:       $('#enc_activa').is(':checked') ? 1 : 0,
        userId:       USERID,
        preguntas:    preguntasData
    };

    $.ajax({
        url: '../../api/encuesta/guardar_encuesta.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function(res) {
            if (res.ok) {
                Swal.fire({ icon:'success', title:'¡Cambios guardados!', text:'La encuesta se actualizó correctamente.', confirmButtonColor:'#667eea' })
                    .then(function(){ window.location.href = 'index.php'; });
            } else {
                App.toast(res.error || 'Error', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Guardar Cambios');
            }
        },
        error: function() {
            App.toast('Error de conexión', 'error');
            btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Guardar Cambios');
        }
    });
}

function escHtml(str) {
    if (!str) return '';
    return str.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
JS;

include_once(__DIR__ . '/../../layouts/footer.php');
?>
