<?php
/**
 * Módulo Celulares/Teléfonos - Lista
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Gestión de Celulares';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('celulares', 'ver');

include_once(__DIR__ . '/../../layouts/header.php');
?>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-mobile-alt mr-2"></i>Gestión de Celulares</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item active">Celulares</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <!-- Tarjetas de resumen -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3 id="totalTelefonos">-</h3>
                                <p>Total Teléfonos</p>
                            </div>
                            <div class="icon"><i class="fas fa-mobile-alt"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3 id="asignados">-</h3>
                                <p>Asignados</p>
                            </div>
                            <div class="icon"><i class="fas fa-user-check"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3 id="disponibles">-</h3>
                                <p>Disponibles</p>
                            </div>
                            <div class="icon"><i class="fas fa-box-open"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3 id="deBaja">-</h3>
                                <p>De Baja</p>
                            </div>
                            <div class="icon"><i class="fas fa-times-circle"></i></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Inventario de Teléfonos</h3>
                        <div class="card-tools">
                            <?php if (tiene_permiso('celulares', 'crear')): ?>
                            <a href="agregar.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Agregar Teléfono
                            </a>
                            <a href="asignar.php" class="btn btn-success btn-sm ml-1">
                                <i class="fas fa-user-plus"></i> Asignar a Usuario
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="tblCelulares" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Modelo</th>
                                    <th>IMEI</th>
                                    <th>Usuario Asignado</th>
                                    <th>Área</th>
                                    <th>Fecha Entrega</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

<?php 
$puedeEditar = tiene_permiso('celulares', 'editar') ? 'true' : 'false';
$puedeEliminar = tiene_permiso('celulares', 'eliminar') ? 'true' : 'false';

$inlineJs = <<<JS
var puedeEditar = {$puedeEditar};
var puedeEliminar = {$puedeEliminar};
var dataCelulares = [];

$(document).ready(function() {
    var table = $('#tblCelulares').DataTable({
        language: DATATABLES_SPANISH,
        responsive: true,
        processing: true,
        ajax: {
            url: '../../public/ajax/consulta4.php',
            dataSrc: function(json) {
                dataCelulares = json;
                actualizarResumen(json);
                return json;
            }
        },
        columns: [
            { data: 'IdTelefo', className: 'text-center' },
            { data: 'Modelo' },
            { data: 'Imei' },
            { data: 'UsuarioAsignado', render: function(d, t, row) {
                return d ? d : '<span class="text-muted">Sin asignar</span>';
            }},
            { data: 'area', render: function(d) {
                return d ? d : '-';
            }},
            { data: 'FechaEntrega', render: function(d) {
                return d ? d : '-';
            }},
            { data: 'EstUso', className: 'text-center', render: function(d) {
                if (d == 2) return '<span class="badge badge-success">En uso</span>';
                if (d == 1) return '<span class="badge badge-info">Asignado</span>';
                if (d == 0) return '<span class="badge badge-secondary">Disponible</span>';
                return '<span class="badge badge-warning">Sin estado</span>';
            }},
            { data: 'IdTelefo', className: 'text-center', orderable: false, render: function(d, t, row) {
                var html = '<div class="btn-group btn-group-sm">';
                html += '<a href="ver.php?id=' + d + '" class="btn btn-info" title="Ver detalles"><i class="fas fa-eye"></i></a>';
                if (puedeEditar) {
                    html += '<a href="editar.php?id=' + d + '" class="btn btn-warning" title="Editar"><i class="fas fa-edit"></i></a>';
                    if (!row.UsuarioAsignado) {
                        html += '<a href="asignar.php?id=' + d + '" class="btn btn-success" title="Asignar"><i class="fas fa-user-plus"></i></a>';
                    }
                }
                html += '</div>';
                return html;
            }}
        ],
        order: [[0, 'desc']]
    });
});

function actualizarResumen(data) {
    var total = data.length;
    var asignados = data.filter(function(d) { return d.EstUso == 2; }).length;
    var disponibles = data.filter(function(d) { return d.EstUso == 0 || d.EstUso == null; }).length;
    var baja = data.filter(function(d) { return d.EstUso == 3; }).length;
    
    $('#totalTelefonos').text(total);
    $('#asignados').text(asignados);
    $('#disponibles').text(disponibles);
    $('#deBaja').text(baja);
}
JS;

include_once(__DIR__ . '/../../layouts/footer.php'); 
?>
