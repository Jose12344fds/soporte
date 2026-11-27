<?php
/**
 * Módulo Encuestas — Crear nueva encuesta
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Nueva Encuesta';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('encuesta', 'crear');

$user = current_user();

include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
/* ── Constructor de Encuesta ─────────────────────────────── */
.enc-wizard .nav-step { display:flex; align-items:center; gap:10px; padding:14px 20px; cursor:pointer; border-left:3px solid transparent; transition:all .2s; }
.enc-wizard .nav-step.active { border-left-color:#667eea; background:rgba(102,126,234,.08); color:#667eea; font-weight:600; }
.enc-wizard .nav-step .step-num { width:28px;height:28px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0; }
.enc-wizard .nav-step.active .step-num { background:#667eea;color:#fff; }
.enc-wizard .nav-step.done .step-num { background:#28a745;color:#fff; }

.pregunta-card {
    border: 1px solid #dee2e6; border-radius: 10px; padding: 18px;
    margin-bottom: 14px; background: #fff; position: relative;
    transition: box-shadow .2s;
}
.pregunta-card:hover { box-shadow: 0 4px 15px rgba(102,126,234,.15); }
.pregunta-card .drag-handle { cursor:grab; color:#adb5bd; }
.pregunta-card .drag-handle:active { cursor:grabbing; }
.pregunta-card .card-num {
    width:30px;height:30px;border-radius:50%;
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-weight:700;font-size:.85rem;flex-shrink:0;
}
.opcion-item { display:flex; gap:8px; align-items:center; margin-bottom:8px; }
.opcion-item input { flex:1; }
.tipo-badge { font-size:.75rem; border-radius:20px; padding:3px 10px; font-weight:600; }
.tipo-badge.unica    { background:#e3f2fd;color:#1565c0; }
.tipo-badge.multiple { background:#f3e5f5;color:#6a1b9a; }
.tipo-badge.abierta  { background:#e8f5e9;color:#2e7d32; }
.tipo-badge.escala   { background:#fff8e1;color:#e65100; }

#sortable-preguntas .ui-sortable-placeholder {
    border: 2px dashed #667eea !important; background: #f0f3ff !important;
    border-radius: 10px; height: 60px; visibility: visible !important;
}

.step-panel { display:none; }
.step-panel.active { display:block; }

.wizard-progress { display:flex; gap:0; margin-bottom: 28px; }
.wizard-progress .wp-step {
    flex:1; text-align:center; padding:10px;
    background:#f8f9fa; border-top:3px solid #dee2e6;
    font-size:.85rem; color:#6c757d;
}
.wizard-progress .wp-step.active { background:#f0f3ff; border-top-color:#667eea; color:#667eea; font-weight:600; }
.wizard-progress .wp-step.done   { background:#f0fff4; border-top-color:#28a745; color:#28a745; }
</style>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-plus-circle mr-2" style="color:#667eea"></i>Nueva Encuesta</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Encuestas</a></li>
                            <li class="breadcrumb-item active">Nueva</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">

                <!-- Indicador de pasos -->
                <div class="wizard-progress">
                    <div class="wp-step active" id="wp1"><i class="fas fa-info-circle mr-1"></i> 1. Datos Generales</div>
                    <div class="wp-step"         id="wp2"><i class="fas fa-question-circle mr-1"></i> 2. Preguntas</div>
                    <div class="wp-step"         id="wp3"><i class="fas fa-check-double mr-1"></i> 3. Revisión</div>
                </div>

                <!-- ── PASO 1: Datos generales ─────────────────────── -->
                <div id="step1" class="step-panel active">
                    <div class="row">
                        <div class="col-md-8 offset-md-2">
                            <div class="card" style="border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)">
                                <div class="card-header" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:12px 12px 0 0">
                                    <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Información de la Encuesta</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Título <span class="text-danger">*</span></label>
                                        <input type="text" id="enc_titulo" class="form-control form-control-lg"
                                               placeholder="Ej: Encuesta de Satisfacción TI 2026" maxlength="255">
                                        <small class="text-muted"><span id="tituloCount">0</span>/255 caracteres</small>
                                    </div>
                                    <div class="form-group">
                                        <label class="font-weight-bold">Descripción</label>
                                        <textarea id="enc_descripcion" class="form-control" rows="3"
                                                  placeholder="Breve descripción del objetivo de la encuesta..."></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Fecha de Cierre</label>
                                                <input type="date" id="enc_cierre" class="form-control"
                                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                                <small class="text-muted">Dejar vacío = sin fecha límite</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Estado</label>
                                                <div class="d-flex align-items-center mt-2" style="gap:12px">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="enc_activa" checked>
                                                        <label class="custom-control-label font-weight-normal" for="enc_activa">
                                                            <span id="estadoLabel" class="text-success">Activa</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-right">
                                    <a href="index.php" class="btn btn-outline-secondary mr-2">
                                        <i class="fas fa-times mr-1"></i>Cancelar
                                    </a>
                                    <button onclick="irPaso2()" class="btn btn-primary" style="background:linear-gradient(135deg,#667eea,#764ba2);border:none">
                                        <i class="fas fa-arrow-right mr-1"></i>Siguiente: Preguntas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── PASO 2: Constructor de preguntas ───────────── -->
                <div id="step2" class="step-panel">
                    <div class="row">
                        <div class="col-md-9">
                            <div class="card" style="border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)">
                                <div class="card-header" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:12px 12px 0 0">
                                    <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Constructor de Preguntas</h3>
                                    <div class="card-tools">
                                        <span class="badge badge-light" id="contPregCount">0 preguntas</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="sortable-preguntas"></div>
                                    <div class="text-center mt-3" id="emptyMsg">
                                        <img src="https://cdn-icons-png.flaticon.com/512/4076/4076549.png" width="80" class="mb-3 opacity-50">
                                        <p class="text-muted">No hay preguntas aún. Agrega la primera usando el panel derecho.</p>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <button onclick="irPaso1()" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left mr-1"></i>Atrás
                                    </button>
                                    <button onclick="irPaso3()" class="btn btn-primary" style="background:linear-gradient(135deg,#667eea,#764ba2);border:none">
                                        <i class="fas fa-eye mr-1"></i>Revisar y Guardar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Panel lateral: agregar preguntas -->
                        <div class="col-md-3">
                            <div class="card sticky-top" style="top:80px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 font-weight-bold"><i class="fas fa-plus-circle mr-1 text-primary"></i>Agregar Pregunta</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Tipo de pregunta</label>
                                        <select id="newTipo" class="form-control form-control-sm">
                                            <option value="unica">🔘 Opción Única (radio)</option>
                                            <option value="multiple">☑️ Opción Múltiple (checkbox)</option>
                                            <option value="abierta">📝 Respuesta Abierta</option>
                                            <option value="escala">⭐ Escala de Satisfacción</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Texto de la pregunta</label>
                                        <textarea id="newTexto" class="form-control form-control-sm" rows="3"
                                                  placeholder="Escribe tu pregunta aquí..."></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="newRequerida" checked>
                                        <label class="form-check-label small" for="newRequerida">Respuesta requerida</label>
                                    </div>
                                    <button onclick="agregarPregunta()" class="btn btn-primary btn-block btn-sm" style="background:linear-gradient(135deg,#667eea,#764ba2);border:none">
                                        <i class="fas fa-plus mr-1"></i>Agregar Pregunta
                                    </button>
                                </div>
                                <div class="card-footer px-3 pt-0 pb-3 bg-white border-0">
                                    <hr>
                                    <small class="text-muted d-block mb-2"><i class="fas fa-info-circle mr-1"></i>Consejos:</small>
                                    <ul class="small text-muted pl-3 mb-0">
                                        <li>Arrastra para reordenar</li>
                                        <li>Escala: genera opciones 1–5 automáticamente</li>
                                        <li>Múltiple: se puede marcar más de una opción</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── PASO 3: Revisión ───────────────────────────── -->
                <div id="step3" class="step-panel">
                    <div class="row">
                        <div class="col-md-8 offset-md-2">
                            <div class="card" style="border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)">
                                <div class="card-header" style="background:linear-gradient(135deg,#43e97b,#38f9d7);color:#fff;border-radius:12px 12px 0 0">
                                    <h3 class="card-title"><i class="fas fa-eye mr-2"></i>Revisión Final</h3>
                                </div>
                                <div class="card-body" id="reviewPanel"></div>
                                <div class="card-footer d-flex justify-content-between">
                                    <button onclick="irPaso2()" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left mr-1"></i>Editar Preguntas
                                    </button>
                                    <button onclick="guardarEncuesta()" class="btn btn-success" id="btnGuardar">
                                        <i class="fas fa-save mr-1"></i>Guardar Encuesta
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
var USERID = {$userId};
var tipoBadges = {
    unica:    { label:'Única',    cls:'unica'    },
    multiple: { label:'Múltiple', cls:'multiple' },
    abierta:  { label:'Abierta',  cls:'abierta'  },
    escala:   { label:'Escala',   cls:'escala'   }
};

// ── Navegación entre pasos ──────────────────────────────────
function mostrarPaso(n) {
    [1,2,3].forEach(function(i) {
        $('#step' + i).toggleClass('active', i === n);
        $('#wp'   + i).toggleClass('active', i === n).toggleClass('done', i < n);
    });
    window.scrollTo(0,0);
}
function irPaso1() { mostrarPaso(1); }
function irPaso2() {
    var titulo = $('#enc_titulo').val().trim();
    if (!titulo) {
        Swal.fire({ icon:'warning', title:'Campo requerido', text:'Ingresa el título de la encuesta.', confirmButtonColor:'#667eea' });
        return;
    }
    mostrarPaso(2);
    inicializarSortable();
}
function irPaso3() {
    if (preguntasData.length === 0) {
        Swal.fire({ icon:'warning', title:'Sin preguntas', text:'Agrega al menos una pregunta antes de continuar.', confirmButtonColor:'#667eea' });
        return;
    }
    mostrarPaso(3);
    renderReview();
}

// ── Contador título ─────────────────────────────────────────
$('#enc_titulo').on('input', function() { $('#tituloCount').text(this.value.length); });
$('#enc_activa').on('change', function() {
    $('#estadoLabel').text(this.checked ? 'Activa' : 'Inactiva')
                     .toggleClass('text-success', this.checked)
                     .toggleClass('text-muted',   !this.checked);
});

// ── Constructor de preguntas ────────────────────────────────
var sortableInit = false;
function inicializarSortable() {
    if (sortableInit) return;
    sortableInit = true;
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
    if (!texto) {
        Swal.fire({ icon:'warning', title:'Campo vacío', text:'Escribe el texto de la pregunta.', confirmButtonColor:'#667eea' });
        return;
    }
    var opciones = [];
    if (tipo === 'escala') {
        opciones = [
            {texto:'1 - Muy insatisfecho', valor:1},
            {texto:'2 - Insatisfecho',     valor:2},
            {texto:'3 - Neutral',          valor:3},
            {texto:'4 - Satisfecho',       valor:4},
            {texto:'5 - Muy satisfecho',   valor:5},
        ];
    } else if (tipo === 'unica' || tipo === 'multiple') {
        opciones = [{texto:'', valor:1}];
    }
    var pregunta = { texto: texto, tipo: tipo, requerida: requerida, opciones: opciones };
    preguntasData.push(pregunta);
    renderPreguntaCard(pregunta, preguntasData.length - 1);
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
    html += '           onchange="updatePregunta(' + idx + ', \'texto\', this.value)">';
    html += '  </div>';
    html += '  <span class="badge tipo-badge ' + badge.cls + ' ml-2">' + badge.label + '</span>';
    html += '  <button onclick="eliminarPregunta(' + idx + ')" class="btn btn-sm btn-outline-danger ml-1"><i class="fas fa-trash"></i></button>';
    html += '</div>';

    if (p.tipo !== 'abierta') {
        html += '<div class="ml-5 opciones-wrapper">';
        html += renderOpciones(p.opciones, idx, p.tipo);
        html += '<button onclick="agregarOpcion(' + idx + ')" class="btn btn-sm btn-outline-primary mt-2 ' + (p.tipo==='escala'?'d-none':'') + '">';
        html += '  <i class="fas fa-plus mr-1"></i>Agregar opción';
        html += '</button>';
        html += '</div>';
    } else {
        html += '<div class="ml-5"><textarea class="form-control form-control-sm text-muted" rows="2" disabled placeholder="El usuario escribirá su respuesta aquí..."></textarea></div>';
    }

    html += '<div class="mt-2 ml-5">';
    html += '  <div class="custom-control custom-switch custom-switch-sm">';
    html += '    <input type="checkbox" class="custom-control-input" id="req' + idx + '"';
    html += (p.requerida ? ' checked' : '') + ' onchange="updatePregunta(' + idx + ',\'requerida\',this.checked?1:0)">';
    html += '    <label class="custom-control-label small" for="req' + idx + '">Respuesta requerida</label>';
    html += '  </div>';
    html += '</div>';
    html += '</div>';

    $('#sortable-preguntas').append(html);
    inicializarSortable();
}

function renderOpciones(opciones, pIdx, tipo) {
    var html = '';
    opciones.forEach(function(op, oIdx) {
        var icono = tipo === 'unica' ? 'far fa-dot-circle' : (tipo === 'escala' ? 'fas fa-star' : 'far fa-check-square');
        html += '<div class="opcion-item" data-oidx="' + oIdx + '">';
        html += '  <i class="' + icono + ' text-muted mr-1"></i>';
        html += '  <input type="text" class="form-control form-control-sm" placeholder="Opción ' + (oIdx+1) + '"';
        html += '         value="' + escHtml(op.texto) + '" ' + (tipo==='escala'?'readonly':'');
        html += '         onchange="updateOpcion(' + pIdx + ',' + oIdx + ',this.value)">';
        if (tipo !== 'escala') {
            html += '  <button onclick="eliminarOpcion(' + pIdx + ',' + oIdx + ')" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>';
        }
        html += '</div>';
    });
    return html;
}

function updatePregunta(idx, field, value) {
    preguntasData[idx][field] = value;
}
function updateOpcion(pIdx, oIdx, value) {
    preguntasData[pIdx].opciones[oIdx].texto = value;
}
function agregarOpcion(pIdx) {
    var oIdx = preguntasData[pIdx].opciones.length;
    var newOp = { texto: '', valor: oIdx + 1 };
    preguntasData[pIdx].opciones.push(newOp);
    var wrapper = $('[data-idx="' + pIdx + '"] .opciones-wrapper');
    var btn = wrapper.find('button');
    var tipo = preguntasData[pIdx].tipo;
    var icono = tipo === 'unica' ? 'far fa-dot-circle' : 'far fa-check-square';
    var newHtml = '<div class="opcion-item" data-oidx="' + oIdx + '">';
    newHtml += '  <i class="' + icono + ' text-muted mr-1"></i>';
    newHtml += '  <input type="text" class="form-control form-control-sm" placeholder="Opción ' + (oIdx+1) + '"';
    newHtml += '         value="" onchange="updateOpcion(' + pIdx + ',' + oIdx + ',this.value)">';
    newHtml += '  <button onclick="eliminarOpcion(' + pIdx + ',' + oIdx + ')" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>';
    newHtml += '</div>';
    btn.before(newHtml);
}
function eliminarOpcion(pIdx, oIdx) {
    preguntasData[pIdx].opciones.splice(oIdx, 1);
    redrawPreguntaCard(pIdx);
}
function eliminarPregunta(idx) {
    preguntasData.splice(idx, 1);
    $('#sortable-preguntas').empty();
    preguntasData.forEach(function(p, i) { renderPreguntaCard(p, i); });
    if (preguntasData.length === 0) $('#emptyMsg').show();
    actualizarContador();
}
function redrawPreguntaCard(idx) {
    $('[data-idx="' + idx + '"]').replaceWith('');
    var p = preguntasData[idx];
    renderPreguntaCard(p, idx);
}
function renumerarPreguntas() {
    var newOrder = [];
    $('#sortable-preguntas .pregunta-card').each(function(i) {
        var idx = parseInt($(this).data('idx'));
        newOrder.push(preguntasData[idx]);
        $(this).find('.card-num').text(i + 1);
    });
    preguntasData = newOrder;
    $('#sortable-preguntas .pregunta-card').each(function(i) {
        $(this).attr('data-idx', i);
    });
}
function actualizarContador() {
    var n = preguntasData.length;
    $('#contPregCount').text(n + (n === 1 ? ' pregunta' : ' preguntas'));
}

// ── Revisión final ───────────────────────────────────────────
function renderReview() {
    var enc   = { titulo: $('#enc_titulo').val(), descripcion: $('#enc_descripcion').val(), cierre: $('#enc_cierre').val(), activa: $('#enc_activa').is(':checked') };
    var html  = '<div class="mb-4 p-3 rounded" style="background:#f8f9fa;border-left:4px solid #667eea">';
    html += '  <h5 class="font-weight-bold mb-1">' + escHtml(enc.titulo) + '</h5>';
    if (enc.descripcion) html += '<p class="text-muted mb-1">' + escHtml(enc.descripcion) + '</p>';
    html += '  <small class="text-muted">Cierre: ' + (enc.cierre || 'Sin límite') + ' &nbsp;|&nbsp; Estado: ';
    html += enc.activa ? '<span class="text-success font-weight-bold">Activa</span>' : '<span class="text-muted">Inactiva</span>';
    html += '</small></div>';

    html += '<h6 class="font-weight-bold mb-3"><i class="fas fa-list-ol mr-1"></i>' + preguntasData.length + ' Preguntas</h6>';
    preguntasData.forEach(function(p, i) {
        var badge = tipoBadges[p.tipo];
        html += '<div class="mb-3 p-3 rounded border">';
        html += '  <div class="d-flex align-items-start">';
        html += '    <span class="font-weight-bold mr-2 text-primary">' + (i+1) + '.</span>';
        html += '    <div class="flex-grow-1">';
        html += '      <span>' + escHtml(p.texto) + '</span>';
        if (p.requerida) html += ' <span class="text-danger" title="Requerida">*</span>';
        html += '      <span class="badge tipo-badge ' + badge.cls + ' ml-2">' + badge.label + '</span>';
        html += '    </div>';
        html += '  </div>';
        if (p.tipo !== 'abierta' && p.opciones.length) {
            html += '  <ul class="mt-2 mb-0 pl-4">';
            p.opciones.forEach(function(op) {
                if (op.texto) html += '<li class="small text-muted">' + escHtml(op.texto) + '</li>';
            });
            html += '  </ul>';
        }
        html += '</div>';
    });

    $('#reviewPanel').html(html);
}

// ── Guardar encuesta ─────────────────────────────────────────
function guardarEncuesta() {
    var btn = $('#btnGuardar');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...');

    // Sincronizar textos de opciones
    preguntasData.forEach(function(p, pIdx) {
        $('[data-idx="' + pIdx + '"] .opciones-wrapper .opcion-item').each(function(oIdx) {
            var val = $(this).find('input').val();
            if (p.opciones[oIdx]) p.opciones[oIdx].texto = val;
        });
        var textoInput = $('[data-idx="' + pIdx + '"] input[data-field="texto"]').val();
        if (textoInput) p.texto = textoInput;
    });

    var payload = {
        titulo:       $('#enc_titulo').val().trim(),
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
                Swal.fire({
                    icon: 'success',
                    title: '¡Encuesta creada!',
                    text: 'La encuesta fue guardada correctamente.',
                    confirmButtonColor: '#667eea'
                }).then(function() {
                    window.location.href = 'index.php';
                });
            } else {
                App.toast(res.error || 'Error al guardar', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Guardar Encuesta');
            }
        },
        error: function() {
            App.toast('Error de conexión', 'error');
            btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Guardar Encuesta');
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
