<?php
/**
 * Módulo Reportes - Reporte de Activos
 */
$page_title = 'Reporte de Activos';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('reportes', 'ver');

include_once(__DIR__ . '/../../layouts/header.php');
?>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-chart-bar mr-2"></i>Reporte de Activos</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Reportes</a></li>
                            <li class="breadcrumb-item active">Activos</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Reporte General de Activos</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-success btn-sm" onclick="exportarExcel()">
                                <i class="fas fa-file-excel"></i> Exportar Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="tblReporte" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Equipo</th>
                                    <th>Área</th>
                                    <th>Usuario</th>
                                    <th>Características</th>
                                    <th>Estado</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

<?php 
$inlineJs = <<<'JS'
$(document).ready(function() {
    $('#tblReporte').DataTable({
        language: DATATABLES_SPANISH,
        responsive: true,
        dom: 'Bfrtip',
        buttons: ['excel', 'pdf', 'print'],
        ajax: {
            url: '../../api/activos/equipos/listar.php',
            dataSrc: 'data'
        },
        columns: [
            { data: 'IdAsMaq' },
            { data: 'Host' },
            { data: 'area' },
            { data: 'name' },
            { data: 'Caracteristicas' },
            { data: 'EstadoMaquina' },
            { data: 'Ip' }
        ]
    });
});

function exportarExcel() {
    $('.buttons-excel').click();
}
JS;

include_once(__DIR__ . '/../../layouts/footer.php'); 
?>
