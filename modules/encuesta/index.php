<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Módulo Encuestas — Portal principal (listado y gestión)
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Encuestas';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('encuesta', 'ver');

// Si el usuario no puede crear ni editar, es usuario regular → portal de respuestas
if (!tiene_permiso('encuesta', 'crear') && !tiene_permiso('encuesta', 'editar')) {
    redirect('mis_encuestas.php', false);
    exit;
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
/* ── Estilos propios del módulo encuesta ───────────────────── */
.enc-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    transition: transform .2s, box-shadow .2s;
}
.enc-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.13); }

.enc-badge-activa  { background: linear-gradient(135deg,#28a745,#20c997); color:#fff; }
.enc-badge-inactiva{ background: linear-gradient(135deg,#6c757d,#868e96); color:#fff; }

.stat-box {
    border-radius: 12px;
    padding: 20px 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.stat-box .stat-icon {
    position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
    font-size: 3rem; opacity: .2;
}
.stat-box h3 { font-size: 2rem; font-weight: 700; margin: 0; }
.stat-box p  { margin: 0; font-size: .95rem; opacity: .9; }

.bg-enc-1 { background: linear-gradient(135deg,#667eea,#764ba2); }
.bg-enc-2 { background: linear-gradient(135deg,#f093fb,#f5576c); }
.bg-enc-3 { background: linear-gradient(135deg,#4facfe,#00f2fe); }
.bg-enc-4 { background: linear-gradient(135deg,#43e97b,#38f9d7); }

.table th { font-size: .83rem; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; }
.badge-tipo { font-size: .78rem; padding: 4px 10px; border-radius: 20px; }
</style>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-poll mr-2" style="color:#667eea"></i>Gestión de Encuestas</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item active">Encuestas</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <!-- Tarjetas de resumen -->
                <div class="row mb-3">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stat-box bg-enc-1">
                            <div class="stat-icon"><i class="fas fa-poll"></i></div>
                            <h3 id="statTotal">-</h3>
                            <p>Total Encuestas</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stat-box bg-enc-2">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <h3 id="statActivas">-</h3>
                            <p>Activas</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stat-box bg-enc-3">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <h3 id="statRespuestas">-</h3>
                            <p>Respuestas Totales</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stat-box bg-enc-4">
                            <div class="stat-icon"><i class="fas fa-question-circle"></i></div>
                            <h3 id="statPreguntas">-</h3>
                            <p>Preguntas Creadas</p>
                        </div>
                    </div>
                </div>

                <!-- Tabla principal -->
                <div class="card enc-card">
                    <div class="card-header" style="background: linear-gradient(135deg,#667eea,#764ba2); color:#fff; border-radius: 12px 12px 0 0;">
                        <h3 class="card-title"><i class="fas fa-list-ul mr-2"></i>Listado de Encuestas</h3>
                        <div class="card-tools">
                            <?php if (tiene_permiso('encuesta', 'crear')): ?>
                            <a href="agregar.php" class="btn btn-light btn-sm">
                                <i class="fas fa-plus-circle mr-1"></i> Nueva Encuesta
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="tblEncuestas" class="table table-hover table-bordered w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center" style="width:50px">#</th>
                                    <th>Título</th>
                                    <th class="text-center">Preguntas</th>
                                    <th class="text-center">Respuestas</th>
                                    <th>Fecha Creación</th>
                                    <th>Fecha Cierre</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center" style="width:160px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>

<?php
$puedeEditar   = tiene_permiso('encuesta', 'editar')   ? 'true' : 'false';
$puedeEliminar = tiene_permiso('encuesta', 'eliminar') ? 'true' : 'false';

$inlineJs = <<<JS
var puedeEditar   = {$puedeEditar};
var puedeEliminar = {$puedeEliminar};

$(document).ready(function () {

    var table = $('#tblEncuestas').DataTable({
        language: DATATABLES_SPANISH,
        responsive: true,
        processing: true,
        ajax: {
            url: '../../api/encuesta/get_encuestas.php',
            dataSrc: function (json) {
                actualizarStats(json);
                return json;
            }
        },
        columns: [
            { data: 'id_encuesta', className: 'text-center' },
            { data: 'titulo', render: function(d, t, row) {
                return '<strong>' + d + '</strong>' +
                       (row.descripcion ? '<br><small class="text-muted">' + row.descripcion.substring(0,80) + (row.descripcion.length>80?'…':'') + '</small>' : '');
            }},
            { data: 'total_preguntas', className: 'text-center', render: function(d) {
                return '<span class="badge badge-info badge-tipo">' + d + '</span>';
            }},
            { data: 'total_respuestas', className: 'text-center', render: function(d) {
                return '<span class="badge badge-primary badge-tipo">' + d + '</span>';
            }},
            { data: 'fecha_creacion', render: function(d) {
                if (!d) return '-';
                var dt = new Date(d); return dt.toLocaleDateString('es-PE');
            }},
            { data: 'fecha_cierre', render: function(d) {
                if (!d) return '<span class="text-muted">Sin límite</span>';
                var dt = new Date(d); var hoy = new Date();
                return dt < hoy
                    ? '<span class="text-danger"><i class="fas fa-calendar-times mr-1"></i>' + dt.toLocaleDateString('es-PE') + '</span>'
                    : '<span class="text-success"><i class="fas fa-calendar-check mr-1"></i>' + dt.toLocaleDateString('es-PE') + '</span>';
            }},
            { data: 'activa', className: 'text-center', render: function(d) {
                return d == 1
                    ? '<span class="badge enc-badge-activa"><i class="fas fa-circle mr-1" style="font-size:.6rem"></i>Activa</span>'
                    : '<span class="badge enc-badge-inactiva"><i class="fas fa-circle mr-1" style="font-size:.6rem"></i>Inactiva</span>';
            }},
            { data: 'id_encuesta', className: 'text-center', orderable: false, render: function(d, t, row) {
                var html = '<div class="btn-group btn-group-sm">';
                if (row.activa == 1) {
                    html += '<a href="responder.php?id=' + d + '" class="btn btn-success" title="Responder Encuesta"><i class="fas fa-clipboard-check"></i></a>';
                }
                html += '<a href="reporte.php?id=' + d + '" class="btn btn-info" title="Ver Resultados"><i class="fas fa-chart-bar"></i></a>';
                if (puedeEditar) {
                    html += '<a href="editar.php?id=' + d + '" class="btn btn-warning" title="Editar"><i class="fas fa-edit"></i></a>';
                    html += '<button onclick="toggleActiva(' + d + ', this)" class="btn btn-' + (row.activa==1?'secondary':'success') + '" title="' + (row.activa==1?'Desactivar':'Activar') + '"><i class="fas fa-' + (row.activa==1?'pause':'play') + '"></i></button>';
                }
                if (puedeEliminar) {
                    html += '<button onclick="eliminarEncuesta(' + d + ', \'' + row.titulo.replace(/'/g,"\\'") + '\')" class="btn btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>';
                }
                html += '</div>';
                return html;
            }}
        ],
        order: [[0, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fas fa-file-excel mr-1"></i>Excel', className: 'btn-success btn-sm' },
            { extend: 'print',      text: '<i class="fas fa-print mr-1"></i>Imprimir', className: 'btn-secondary btn-sm' }
        ]
    });

    function actualizarStats(data) {
        var activas    = data.filter(function(d){ return d.activa == 1; }).length;
        var respuestas = data.reduce(function(s, d){ return s + parseInt(d.total_respuestas||0); }, 0);
        var preguntas  = data.reduce(function(s, d){ return s + parseInt(d.total_preguntas||0);  }, 0);
        $('#statTotal').text(data.length);
        $('#statActivas').text(activas);
        $('#statRespuestas').text(respuestas);
        $('#statPreguntas').text(preguntas);
    }
});

function toggleActiva(id, btn) {
    Swal.fire({
        title: '¿Cambiar estado?',
        text: 'Se cambiará el estado activo/inactivo de esta encuesta.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#667eea'
    }).then(function(r) {
        if (!r.isConfirmed) return;
        $.post('../../api/encuesta/toggle_activa.php', { id_encuesta: id }, function(res) {
            if (res.ok) {
                App.toast('Estado actualizado', 'success');
                $('#tblEncuestas').DataTable().ajax.reload(null, false);
            } else {
                App.toast('Error al actualizar', 'error');
            }
        });
    });
}

function eliminarEncuesta(id, titulo) {
    Swal.fire({
        title: '¿Eliminar encuesta?',
        html: 'Se eliminará <b>"' + titulo + '"</b> junto con todas sus preguntas y respuestas.<br><br><span class="text-danger font-weight-bold">Esta acción no se puede deshacer.</span>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33'
    }).then(function(r) {
        if (!r.isConfirmed) return;
        $.post('../../api/encuesta/eliminar_encuesta.php', { id_encuesta: id }, function(res) {
            if (res.ok) {
                App.toast('Encuesta eliminada', 'success');
                $('#tblEncuestas').DataTable().ajax.reload(null, false);
            } else {
                App.toast('Error al eliminar', 'error');
            }
        });
    });
}
JS;

include_once(__DIR__ . '/../../layouts/footer.php');
?>
