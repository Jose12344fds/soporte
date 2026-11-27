<?php
/**
 * Módulo Encuestas — Reporte de resultados
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Resultados de Encuesta';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('encuesta', 'ver');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    $session->msg('d', 'ID de encuesta inválido.');
    redirect('index.php', false);
    exit;
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* ─── Reporte de Encuestas ─────────────────────────────── */
.reporte-header {
    border-radius:16px;
    background: linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);
    color:#fff;padding:32px 36px;margin-bottom:28px;
    position:relative;overflow:hidden;
}
.reporte-header::after {
    content:'';position:absolute;bottom:-30px;right:-30px;
    width:160px;height:160px;border-radius:50%;
    background:rgba(255,255,255,.05);
}
.reporte-header h2 { font-weight:700;margin-bottom:6px; }
.reporte-header .meta { opacity:.7;font-size:.9rem; }

.stat-pill {
    display:inline-flex;align-items:center;gap:8px;
    padding:8px 18px;border-radius:30px;font-weight:600;
    font-size:.9rem;
}
.stat-pill-blue  { background:rgba(102,126,234,.15);color:#667eea;border:1px solid rgba(102,126,234,.3); }
.stat-pill-green { background:rgba(40,167,69,.15);color:#28a745;border:1px solid rgba(40,167,69,.3); }
.stat-pill-orange{ background:rgba(255,152,0,.15);color:#f57c00;border:1px solid rgba(255,152,0,.3); }

.pregunta-result-card {
    border-radius:12px;border:none;
    box-shadow:0 2px 12px rgba(0,0,0,.07);
    margin-bottom:24px;overflow:hidden;
}
.pregunta-result-card .prq-header {
    padding:14px 20px;
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:#fff;
}
.pregunta-result-card .prq-header .num {
    width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.25);
    font-weight:700;font-size:.8rem;display:inline-flex;align-items:center;justify-content:center;
    margin-right:10px;
}
.pregunta-result-card .prq-body { padding:20px; }

.barra-opcion { margin-bottom:12px; }
.barra-opcion .label { font-size:.9rem;font-weight:500;margin-bottom:4px; }
.barra-opcion .bar-track {
    height:28px;border-radius:6px;background:#f0f3ff;
    position:relative;overflow:hidden;
}
.barra-opcion .bar-fill {
    height:100%;border-radius:6px;
    background:linear-gradient(135deg,#667eea,#764ba2);
    transition:width .8s ease;position:absolute;left:0;top:0;
}
.barra-opcion .bar-pct {
    position:absolute;right:8px;top:50%;transform:translateY(-50%);
    font-size:.8rem;font-weight:700;color:#374151;z-index:1;
}
.barra-opcion .bar-count {
    font-size:.78rem;color:#6c757d;margin-top:2px;
}

.tabla-participantes { font-size:.88rem; }
.tabla-participantes th { font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;color:#6c757d; }

.respuesta-abierta {
    background:#f8f9fa;border-left:3px solid #667eea;
    border-radius:0 8px 8px 0;padding:10px 14px;margin-bottom:10px;
    font-size:.9rem;
}
.respuesta-abierta .meta { font-size:.78rem;color:#adb5bd;margin-top:4px; }

.chart-container { position:relative;height:260px; }

/* Tabs custom */
.nav-tabs-enc .nav-link {
    border:none;border-bottom:3px solid transparent;
    color:#6c757d;font-weight:500;padding:10px 20px;
}
.nav-tabs-enc .nav-link.active {
    color:#667eea;border-bottom-color:#667eea;background:transparent;
}
.nav-tabs-enc .nav-link:hover { color:#667eea; }
</style>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-chart-pie mr-2" style="color:#667eea"></i>Resultados</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Encuestas</a></li>
                            <li class="breadcrumb-item active">Resultados</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">

                <!-- Cargando -->
                <div id="loadingPanel" class="text-center py-5">
                    <div class="spinner-border" style="width:3rem;height:3rem;color:#667eea"></div>
                    <p class="mt-3 text-muted">Cargando resultados...</p>
                </div>

                <!-- Panel principal -->
                <div id="mainPanel" style="display:none">

                    <!-- Header del reporte -->
                    <div class="reporte-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 id="repTitulo">-</h2>
                                <p class="meta" id="repDesc"></p>
                                <div class="d-flex flex-wrap mt-3" style="gap:10px">
                                    <span class="stat-pill stat-pill-blue"><i class="fas fa-users"></i><span id="repTotal">0</span> participantes</span>
                                    <span class="stat-pill stat-pill-green"><i class="fas fa-question-circle"></i><span id="repPreg">0</span> preguntas</span>
                                    <span class="stat-pill stat-pill-orange" id="repEstadoPill"><i class="fas fa-circle"></i> Activa</span>
                                    <span class="stat-pill" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);">
                                        <i class="fas fa-calendar-alt"></i><span id="repFecha">-</span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4 text-right mt-3 mt-md-0">
                                <a id="btnDescCSV" href="../../api/encuesta/exportar_csv.php?id=<?php echo $id ?>" 
                                   class="btn btn-light btn-sm mr-2">
                                    <i class="fas fa-file-csv mr-1"></i>Descargar CSV
                                </a>
                                <button onclick="window.print()" class="btn btn-outline-light btn-sm">
                                    <i class="fas fa-print mr-1"></i>Imprimir
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs-enc mb-4" style="border-bottom:2px solid #e9ecef">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tabResultados">
                                <i class="fas fa-chart-bar mr-1"></i>Resultados por Pregunta
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tabParticipantes">
                                <i class="fas fa-users mr-1"></i>Participantes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tabAbiertas">
                                <i class="fas fa-comments mr-1"></i>Respuestas Abiertas
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- Tab 1: Resultados -->
                        <div class="tab-pane active" id="tabResultados">
                            <div id="resultadosPregContainer"></div>
                        </div>

                        <!-- Tab 2: Participantes -->
                        <div class="tab-pane" id="tabParticipantes">
                            <div class="card" style="border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.07)">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0 font-weight-bold"><i class="fas fa-users mr-2 text-primary"></i>Lista de Participantes</h5>
                                </div>
                                <div class="card-body p-0">
                                    <table id="tblParticipantes" class="table tabla-participantes table-hover mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Nombre</th>
                                                <th>Área</th>
                                                <th>Fecha de Respuesta</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyParticipantes"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Respuestas abiertas -->
                        <div class="tab-pane" id="tabAbiertas">
                            <div id="abiertasContainer"></div>
                        </div>

                    </div>

                </div>
            </div>
        </section>

<?php
$inlineJs = <<<JS
var ENC_ID = {$id};
var resultados = null;

$(document).ready(function() {
    $.get('../../api/encuesta/get_resultados.php', { id: ENC_ID }, function(data) {
        resultados = data;
        popularHeader(data);
        renderResultados(data);
        renderParticipantes(data.participantes);
        renderAbiertos(data.abiertos);
        $('#loadingPanel').hide();
        $('#mainPanel').show();
    }).fail(function() {
        App.toast('Error al cargar resultados', 'error');
    });
});

function popularHeader(data) {
    var enc = data.encuesta;
    $('#repTitulo').text(enc.titulo);
    if (enc.descripcion) $('#repDesc').text(enc.descripcion);
    $('#repTotal').text(data.total);
    $('#repPreg').text(countPregs(data.opciones));
    var estado = enc.activa == 1 ? 'Activa' : 'Inactiva';
    $('#repEstadoPill').html('<i class="fas fa-circle"></i> ' + estado);
    if (!enc.activa) $('#repEstadoPill').css('background','rgba(108,117,125,.15)').css('color','#6c757d');
    var fecha = enc.fecha_cierre ? enc.fecha_cierre : 'Sin fecha límite';
    $('#repFecha').text(fecha);
}

function countPregs(opciones) {
    var ids = {};
    opciones.forEach(function(o){ ids[o.id_pregunta] = 1; });
    return Object.keys(ids).length;
}

// ── Resultados por pregunta ────────────────────────────────
function renderResultados(data) {
    var container = $('#resultadosPregContainer');
    container.empty();

    // Agrupar opciones por pregunta
    var preguntasMap = {};
    data.opciones.forEach(function(row) {
        var pid = row.id_pregunta;
        if (!preguntasMap[pid]) {
            preguntasMap[pid] = { texto: row.pregunta, tipo: row.tipo, total: 0, opciones: [] };
        }
        preguntasMap[pid].opciones.push({ texto: row.opcion, votos: parseInt(row.votos||0) });
        preguntasMap[pid].total += parseInt(row.votos||0);
    });

    var i = 1;
    Object.keys(preguntasMap).forEach(function(pid) {
        var p   = preguntasMap[pid];
        var cid = 'chart_' + pid;

        var html  = '<div class="pregunta-result-card">';
        html += '<div class="prq-header"><span class="num">' + i + '</span>' + escHtml(p.texto) + '</div>';
        html += '<div class="prq-body">';

        if (p.total === 0) {
            html += '<p class="text-muted text-center py-3"><i class="fas fa-inbox mr-2"></i>Sin respuestas aún</p>';
        } else {
            html += '<div class="row">';
            // Barras
            html += '<div class="col-md-7">';
            p.opciones.forEach(function(op) {
                var pct = p.total > 0 ? Math.round(op.votos / p.total * 100) : 0;
                html += '<div class="barra-opcion">';
                html += '  <div class="label">' + escHtml(op.texto || '—') + '</div>';
                html += '  <div class="bar-track">';
                html += '    <div class="bar-fill" style="width:' + pct + '%"></div>';
                html += '    <span class="bar-pct">' + pct + '%</span>';
                html += '  </div>';
                html += '  <div class="bar-count">' + op.votos + ' de ' + p.total + ' respuesta' + (p.total!==1?'s':'') + '</div>';
                html += '</div>';
            });
            html += '</div>';
            // Gráfico donut
            html += '<div class="col-md-5 d-flex align-items-center justify-content-center">';
            html += '  <div class="chart-container" style="width:100%"><canvas id="' + cid + '"></canvas></div>';
            html += '</div>';
            html += '</div>';
        }

        html += '</div></div>';
        container.append(html);

        // Renderizar gráfico si hay datos
        if (p.total > 0) {
            renderChart(cid, p);
        }

        i++;
    });

    if (!Object.keys(preguntasMap).length) {
        container.html('<div class="text-center py-5 text-muted"><i class="fas fa-chart-bar fa-3x mb-3"></i><br>No hay datos de respuestas cerradas todavía</div>');
    }
}

var chartInstances = {};
function renderChart(canvasId, p) {
    var labels = p.opciones.map(function(o){ return o.texto || '—'; });
    var values = p.opciones.map(function(o){ return o.votos; });
    var colors = generateColors(labels.length);

    if (chartInstances[canvasId]) { chartInstances[canvasId].destroy(); }

    var ctx = document.getElementById(canvasId).getContext('2d');
    chartInstances[canvasId] = new Chart(ctx, {
        type: 'doughnut',
        data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12, padding: 10 } },
                tooltip: { callbacks: {
                    label: function(ctx) {
                        var total = ctx.dataset.data.reduce(function(a,b){return a+b;},0);
                        var pct   = total > 0 ? Math.round(ctx.raw / total * 100) : 0;
                        return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                    }
                }}
            }
        }
    });
}

function generateColors(n) {
    var palette = ['#667eea','#764ba2','#f093fb','#f5576c','#4facfe','#00f2fe','#43e97b','#38f9d7','#fa709a','#fee140'];
    var out = [];
    for (var i = 0; i < n; i++) out.push(palette[i % palette.length]);
    return out;
}

// ── Participantes ──────────────────────────────────────────
function renderParticipantes(data) {
    var tbody = $('#tbodyParticipantes');
    tbody.empty();
    if (!data.length) {
        tbody.append('<tr><td colspan="4" class="text-center text-muted py-4">Sin participantes todavía</td></tr>');
        return;
    }
    data.forEach(function(p, i) {
        var fecha = new Date(p.fecha).toLocaleString('es-PE');
        tbody.append('<tr><td>' + (i+1) + '</td><td>' + escHtml(p.name) + '</td><td>' + escHtml(p.area||'-') + '</td><td>' + fecha + '</td></tr>');
    });
}

// ── Respuestas abiertas ────────────────────────────────────
function renderAbiertos(data) {
    var container = $('#abiertasContainer');
    container.empty();

    if (!data.length) {
        container.html('<div class="text-center py-5 text-muted"><i class="fas fa-comments fa-3x mb-3"></i><br>No hay respuestas abiertas todavía</div>');
        return;
    }

    // Agrupar por pregunta
    var grupos = {};
    data.forEach(function(r) {
        var pid = r.id_pregunta;
        if (!grupos[pid]) grupos[pid] = { pregunta: r.pregunta, respuestas: [] };
        grupos[pid].respuestas.push(r);
    });

    Object.values(grupos).forEach(function(g) {
        var html  = '<div class="card mb-4" style="border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.07)">';
        html += '<div class="card-header" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:12px 12px 0 0">';
        html += '  <h6 class="mb-0"><i class="fas fa-quote-left mr-2"></i>' + escHtml(g.pregunta) + '</h6>';
        html += '</div><div class="card-body">';
        g.respuestas.forEach(function(r) {
            var fecha = new Date(r.fecha).toLocaleString('es-PE');
            html += '<div class="respuesta-abierta">';
            html += '  <div>' + escHtml(r.texto_respuesta) + '</div>';
            html += '  <div class="meta"><i class="fas fa-user mr-1"></i>' + escHtml(r.usuario || 'Anónimo') + ' &nbsp;·&nbsp; <i class="fas fa-clock mr-1"></i>' + fecha + '</div>';
            html += '</div>';
        });
        html += '</div></div>';
        container.append(html);
    });
}

function escHtml(str) {
    if (!str) return '';
    return str.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
JS;

include_once(__DIR__ . '/../../layouts/footer.php');
?>
