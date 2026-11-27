<?php
/**
 * Módulo Reportes - Reporte VPN
 */
$page_title = 'Reporte VPN';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('reportes', 'ver');

include_once(__DIR__ . '/../../layouts/header.php');
?>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-chart-line mr-2"></i>Reporte VPN</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Reportes</a></li>
                            <li class="breadcrumb-item active">VPN</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Reporte de Accesos VPN</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-success btn-sm" onclick="exportarExcel()">
                                <i class="fas fa-file-excel"></i> Exportar Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="tblReporteVPN" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Solicitante</th>
                                    <th>Empresa</th>
                                    <th>Motivo</th>
                                    <th>Fecha/Hora</th>
                                    <th>Nivel</th>
                                    <th>Tiempo</th>
                                    <th>Culminado</th>
                                    <th>Responsable</th>
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
    $('#tblReporteVPN').DataTable({
        language: DATATABLES_SPANISH,
        responsive: true,
        dom: 'Bfrtip',
        buttons: ['excel', 'pdf', 'print'],
        ajax: {
            url: '../../public/ajax/consulta6.php',
            dataSrc: ''
        },
        columns: [
            { data: 'Id_IngVpn' },
            { data: 'Solicitante' },
            { data: 'Empresa' },
            { data: 'Motivo' },
            { data: 'dateff' },
            { data: 'Nivel_Usu' },
            { data: 'Tiempo' },
            { data: 'Culminado' },
            { data: 'Responsable' }
        ],
        order: [[0, 'desc']]
    });
});

function exportarExcel() {
    $('.buttons-excel').click();
}
JS;

include_once(__DIR__ . '/../../layouts/footer.php'); 
?>
