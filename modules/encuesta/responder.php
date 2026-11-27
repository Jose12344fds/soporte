<?php
/**
 * Módulo Encuestas — Formulario de respuesta para el usuario
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Responder Encuesta';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('encuesta', 'ver');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    $session->msg('d', 'ID de encuesta invalido.');
    redirect('mis_encuestas.php', false);
    exit;
}

$user   = current_user();
$userId = (int)$user['id'];

// Verificar si el usuario ya respondió esta encuesta
require_once(__DIR__ . '/../../api/encuesta/ConsultasEncuesta.php');
if (ConsultasEncuesta::yaRespondio($id, $userId)) {
    $session->msg('s', 'Ya respondiste esta encuesta. Gracias por participar.');
    redirect('mis_encuestas.php', false);
    exit;
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
/* ─── Formulario de respuesta ───────────────────────────── */
.enc-responder {
    max-width: 760px;
    margin: 0 auto;
}
.enc-header-card {
    border-radius: 16px;
    background: linear-gradient(135deg,#667eea,#764ba2);
    color: #fff;
    padding: 36px 40px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.enc-header-card::before {
    content:'';position:absolute;top:-40px;right:-40px;
    width:180px;height:180px;border-radius:50%;
    background:rgba(255,255,255,.08);
}
.enc-header-card h2 { font-weight:700;margin-bottom:8px; }
.enc-header-card p  { opacity:.85;margin-bottom:0; }

.pregunta-block {
    border-radius: 12px;
    border: 1px solid #e9ecef;
    padding: 24px;
    margin-bottom: 20px;
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
}
.pregunta-block:focus-within {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,.1);
}
.pregunta-block .num {
    width: 32px;height:32px;border-radius:50%;
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:#fff;font-weight:700;font-size:.85rem;
    display:inline-flex;align-items:center;justify-content:center;
    flex-shrink:0;margin-right:12px;
}
.pregunta-block .texto-pregunta {
    font-weight:600;font-size:1rem;
}
.pregunta-block .requerida-star { color:#dc3545;margin-left:4px; }

/* Opciones radio/checkbox */
.opcion-label {
    display:flex;align-items:center;gap:10px;
    padding:10px 14px;border-radius:8px;cursor:pointer;
    border:2px solid #e9ecef;margin-bottom:8px;
    transition:all .15s;
}
.opcion-label:hover { border-color:#667eea;background:#f0f3ff; }
.opcion-label input[type=radio],
.opcion-label input[type=checkbox] { display:none; }
.opcion-label .opcion-check {
    width:22px;height:22px;border-radius:50%;border:2px solid #ced4da;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
    transition:all .15s;
}
.opcion-label.checkbox-type .opcion-check { border-radius:4px; }
.opcion-label.selected {
    border-color:#667eea;background:#f0f3ff;
}
.opcion-label.selected .opcion-check {
    background:#667eea;border-color:#667eea;color:#fff;
}

/* Escala */
.escala-wrapper { display:flex;gap:10px;flex-wrap:wrap; }
.escala-btn {
    flex:1;min-width:80px;max-width:130px;
    padding:14px 8px;border-radius:10px;
    border:2px solid #e9ecef;cursor:pointer;text-align:center;
    transition:all .15s;
}
.escala-btn:hover { border-color:#667eea;background:#f0f3ff; }
.escala-btn.selected { border-color:#667eea;background:#667eea;color:#fff; }
.escala-btn .star { font-size:1.4rem;display:block;margin-bottom:4px; }
.escala-btn .label { font-size:.72rem;font-weight:500; }

/* Barra de progreso flotante */
.progress-bar-wrapper {
    position: sticky; top: 60px; z-index: 100;
    background: #fff; padding: 10px 0; margin-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
}
.progress { border-radius: 20px; height: 8px; }
.progress-bar { background: linear-gradient(135deg,#667eea,#764ba2); border-radius: 20px; transition: width .4s; }
</style>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-clipboard-check mr-2" style="color:#667eea"></i>Responder Encuesta</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                    
                            <li class="breadcrumb-item active">Responder</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">

                <!-- Cargando -->
                <div id="loadingPanel" class="text-center py-5">
                    <div class="spinner-border text-primary" style="width:3rem;height:3rem"></div>
                    <p class="mt-3 text-muted">Cargando encuesta...</p>
                </div>

                <!-- Ya respondió -->
                <div id="yaRespondioPanel" style="display:none" class="enc-responder">
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle" style="font-size:5rem;color:#28a745"></i>
                        </div>
                        <h3 class="font-weight-bold">¡Ya respondiste esta encuesta!</h3>
                        <p class="text-muted">Tu respuesta fue registrada anteriormente. Gracias por participar.</p>
                        <a href="mis_encuestas.php" class="btn btn-primary mt-3" style="background:linear-gradient(135deg,#667eea,#764ba2);border:none">
                            <i class="fas fa-arrow-left mr-2"></i>Volver a Mis Encuestas
                        </a>
                    </div>
                </div>

                <!-- Formulario principal -->
                <div id="mainPanel" style="display:none" class="enc-responder">

                    <!-- Barra progreso -->
                    <div class="progress-bar-wrapper">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted font-weight-bold" id="progLabel">0% completado</small>
                            <small class="text-muted" id="progFrac">0 / 0</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" id="progressBar" style="width:0%"></div>
                        </div>
                    </div>

                    <!-- Header de la encuesta -->
                    <div class="enc-header-card">
                        <h2 id="encTitulo">Cargando...</h2>
                        <p id="encDesc"></p>
                        <div class="mt-3 d-flex gap-3" style="gap:20px">
                            <small><i class="fas fa-question-circle mr-1"></i><span id="encNumPreg">0</span> preguntas</small>
                            <small id="encCierreInfo"></small>
                        </div>
                    </div>

                    <!-- Preguntas dinámicas -->
                    <div id="preguntasContainer"></div>

                    <!-- Botón enviar -->
                    <div class="text-center mt-4 mb-5">
                        <button id="btnEnviar" onclick="enviarRespuestas()" class="btn btn-lg btn-success px-5"
                                style="border-radius:30px;background:linear-gradient(135deg,#43e97b,#38f9d7);border:none;color:#fff;font-weight:700;box-shadow:0 4px 15px rgba(67,233,123,.4)">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Respuestas
                        </button>
                    </div>
                </div>

            </div>
        </section>

<?php
$encId  = (int)$id;
ob_start();
?>
var ENC_ID  = <?php echo $encId; ?>;
var USERID  = <?php echo $userId; ?>;
var encuesta = null;
var preguntas = [];
var respuestasMap = {};

$(document).ready(function() {
    $.when(
        $.get('../../api/encuesta/get_encuestas.php', { activas: 1 }),
        $.get('../../api/encuesta/get_preguntas.php', { id: ENC_ID })
    ).done(function(resEnc, resPreg) {
        var todas = resEnc[0];
        encuesta  = todas.find(function(e){ return e.id_encuesta == ENC_ID; });

        if (!encuesta) {
            Swal.fire({ icon:'error', title:'Encuesta no disponible', text:'Esta encuesta no existe o no está activa.', confirmButtonColor:'#667eea' })
                .then(function(){ window.location.href = 'mis_encuestas.php'; });
            return;
        }

        // Verificar si ya respondió (vía API que retorna error si ya respondio)
        // La verificación real se hace al enviar, pero mostramos el formulario
        preguntas = resPreg[0];

        $('#encTitulo').text(encuesta.titulo);
        $('#encDesc').text(encuesta.descripcion || '');
        $('#encNumPreg').text(preguntas.length);

        if (encuesta.fecha_cierre) {
            $('#encCierreInfo').html('<i class="fas fa-calendar-alt mr-1"></i>Cierra: ' + encuesta.fecha_cierre);
        }

        renderPreguntas();
        actualizarProgreso();

        $('#loadingPanel').hide();
        $('#mainPanel').show();
    }).fail(function() {
        App.toast('Error al cargar la encuesta', 'error');
    });
});

function renderPreguntas() {
    var container = $('#preguntasContainer');
    container.empty();
    preguntas.forEach(function(p, i) {
        var html = '<div class="pregunta-block" data-pregid="' + p.id_pregunta + '">';
        html += '<div class="d-flex align-items-start mb-3">';
        html += '  <span class="num">' + (i+1) + '</span>';
        html += '  <span class="texto-pregunta">' + escHtml(p.texto);
        if (p.requerida) html += '<span class="requerida-star">*</span>';
        html += '  </span></div>';

        if (p.tipo === 'unica') {
            p.opciones.forEach(function(op) {
                html += '<label class="opcion-label" data-pregid="' + p.id_pregunta + '" data-opid="' + op.id_opcion + '" onclick="seleccionarUnica(this,' + p.id_pregunta + ',' + op.id_opcion + ')">';
                html += '  <input type="radio" name="preg_' + p.id_pregunta + '" value="' + op.id_opcion + '">';
                html += '  <span class="opcion-check"><i class="fas fa-check" style="font-size:.7rem"></i></span>';
                html += '  <span>' + escHtml(op.texto) + '</span>';
                html += '</label>';
            });
        } else if (p.tipo === 'multiple') {
            p.opciones.forEach(function(op) {
                html += '<label class="opcion-label checkbox-type" data-pregid="' + p.id_pregunta + '" data-opid="' + op.id_opcion + '" onclick="toggleMultiple(this,' + p.id_pregunta + ',' + op.id_opcion + ')">';
                html += '  <input type="checkbox" name="preg_' + p.id_pregunta + '" value="' + op.id_opcion + '">';
                html += '  <span class="opcion-check"><i class="fas fa-check" style="font-size:.7rem"></i></span>';
                html += '  <span>' + escHtml(op.texto) + '</span>';
                html += '</label>';
            });
        } else if (p.tipo === 'escala') {
            var labels = ['','Muy insatisfecho','Insatisfecho','Neutral','Satisfecho','Muy satisfecho'];
            var stars  = ['','1','2','3','4','5'];
            html += '<div class="escala-wrapper">';
            p.opciones.forEach(function(op) {
                var val = op.valor;
                html += '<div class="escala-btn" data-pregid="' + p.id_pregunta + '" data-opid="' + op.id_opcion + '" data-val="' + val + '" onclick="seleccionarEscala(this,' + p.id_pregunta + ',' + op.id_opcion + ')">';
                html += '  <strong style="font-size:1.4rem;display:block;margin-bottom:4px">' + val + '</strong>';
                html += '  <div class="label">' + (labels[val] || '') + '</div>';
                html += '</div>';
            });
            html += '</div>';
        } else if (p.tipo === 'abierta') {
            html += '<textarea class="form-control" rows="3" placeholder="Escribe tu respuesta aqui..." oninput="registrarAbierta(' + p.id_pregunta + ', this.value)"></textarea>';
        }

        html += '</div>';
        container.append(html);
    });
}

function seleccionarUnica(el, pregId, opId) {
    $('[data-pregid="' + pregId + '"].opcion-label').removeClass('selected');
    $(el).addClass('selected');
    respuestasMap[pregId] = [{ id_opcion: opId, texto_respuesta: null }];
    actualizarProgreso();
}
function toggleMultiple(el, pregId, opId) {
    $(el).toggleClass('selected');
    var seleccionadas = $('[data-pregid="' + pregId + '"].opcion-label.selected');
    respuestasMap[pregId] = [];
    seleccionadas.each(function() {
        respuestasMap[pregId].push({ id_opcion: parseInt($(this).data('opid')), texto_respuesta: null });
    });
    if (!respuestasMap[pregId].length) delete respuestasMap[pregId];
    actualizarProgreso();
}
function seleccionarEscala(el, pregId, opId) {
    $('[data-pregid="' + pregId + '"].escala-btn').removeClass('selected');
    $(el).addClass('selected');
    respuestasMap[pregId] = [{ id_opcion: opId, texto_respuesta: null }];
    actualizarProgreso();
}
function registrarAbierta(pregId, val) {
    if (val.trim()) {
        respuestasMap[pregId] = [{ id_opcion: null, texto_respuesta: val.trim() }];
    } else {
        delete respuestasMap[pregId];
    }
    actualizarProgreso();
}

function actualizarProgreso() {
    var respondidas = Object.keys(respuestasMap).length;
    var total       = preguntas.length;
    var pct = total > 0 ? Math.round(respondidas / total * 100) : 0;
    $('#progressBar').css('width', pct + '%');
    $('#progLabel').text(pct + '% completado');
    $('#progFrac').text(respondidas + ' / ' + total);
}

function enviarRespuestas() {
    // Validar requeridas
    var falta = null;
    preguntas.forEach(function(p, i) {
        if (p.requerida && !respuestasMap[p.id_pregunta] && !falta) {
            falta = i + 1;
        }
    });
    if (falta) {
        Swal.fire({ icon:'warning', title:'Pregunta sin responder', text:'La pregunta ' + falta + ' es obligatoria.', confirmButtonColor:'#667eea' });
        $('[data-pregid]').eq(falta - 1)[0].scrollIntoView({ behavior:'smooth', block:'center' });
        return;
    }

    // Armar payload
    var detalles = [];
    preguntas.forEach(function(p) {
        var resp = respuestasMap[p.id_pregunta] || [];
        resp.forEach(function(r) {
            detalles.push({ id_pregunta: p.id_pregunta, id_opcion: r.id_opcion, texto_respuesta: r.texto_respuesta });
        });
    });

    var btn = $('#btnEnviar');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...');

    var payload = { id_encuesta: ENC_ID, id_usuario: USERID, respuestas: detalles };

    $.ajax({
        url: '../../api/encuesta/guardar_respuesta.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function(res) {
            if (res.ok) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Encuesta finalizada!',
                    html: 'Has terminado tu encuesta. ¡Gracias por participar!',
                    confirmButtonColor: '#667eea',
                    confirmButtonText: 'Volver a Mis Encuestas'
                }).then(function() {
                    window.location.href = 'mis_encuestas.php';
                });
            } else if (res.msg) {
                // Ya respondió
                $('#mainPanel').hide();
                $('#yaRespondioPanel').show();
            } else {
                App.toast(res.error || 'Error al guardar', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i>Enviar Respuestas');
            }
        },
        error: function() {
            App.toast('Error de conexión', 'error');
            btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i>Enviar Respuestas');
        }
    });
}

function escHtml(str) {
    if (!str) return '';
    return str.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
<?php
$inlineJs = ob_get_clean();
include_once(__DIR__ . '/../../layouts/footer.php');
?>
