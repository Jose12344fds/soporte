<?php
/**
 * Módulo Licencias - Vista de Licencias SAP
 * Sistema de Soporte TI - TAMA
 */

$page_title = 'Licencias SAP Business One';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

// Verificar permisos
require_permiso('licencias', 'ver');

// Obtener todas las licencias
$all_licenses = find_all('licencias');

// Filtrar solo licencias SAP
$sap_licenses = array_filter($all_licenses, function($lic) {
    return isset($lic['Tipo_Licencias']) && strpos(strtoupper($lic['Tipo_Licencias']), 'SAP') !== false;
});

include_once(__DIR__ . '/../../layouts/header.php');
?>

<!-- Content Header -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-database mr-2"></i>Licencias SAP Business One</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Licencias</a></li>
                    <li class="breadcrumb-item active">SAP</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <?php echo display_msg($msg); ?>

        <!-- Botones de acción -->
        <div class="row mb-3">
            <div class="col-md-12">
                <?php if (tiene_permiso('licencias', 'crear')): ?>
                <a href="../../index.php?module=licencias&action=agregar_sap" class="btn btn-success">
                    <i class="fas fa-plus"></i> Agregar Licencia SAP
                </a>
                <?php endif; ?>
                <a href="../../Reportes/ReporteLicSap.php" class="btn btn-info ml-2" target="_blank">
                    <i class="fas fa-file-excel"></i> Descargar Reporte
                </a>
            </div>
        </div>

        <!-- Tabla de Licencias SAP -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title">
                            <i class="fas fa-table mr-2"></i>
                            ASIGNACIÓN DE CUENTAS Y LICENCIAS SAP (<?php echo count($sap_licenses); ?>)
                        </h3>
                    </div>
                    <div class="card-body table-responsive">
                        <?php if (count($sap_licenses) > 0): ?>
                        <table class="table table-striped table-hover table-bordered" id="sapLicensesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 5%">ID</th>
                                    <th style="width: 12%">Tipo SAP</th>
                                    <th style="width: 15%">Código/Serial</th>
                                    <th style="width: 15%">Usuario SAP</th>
                                    <th style="width: 12%">Nombre Completo</th>
                                    <th style="width: 10%">Empresa/Área</th>
                                    <th style="width: 10%">Fecha Asignación</th>
                                    <th style="width: 8%">Estado</th>
                                    <th style="width: 17%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sap_licenses as $license): ?>
                                <tr>
                                    <td><?php echo isset($license['IdLicencias']) ? (int)$license['IdLicencias'] : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php echo isset($license['Tipo_Licencias']) ? remove_junk($license['Tipo_Licencias']) : 'SAP'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?php echo isset($license['Serial']) ? remove_junk($license['Serial']) : 'N/A'; ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo isset($license['Usuario']) ? remove_junk($license['Usuario']) : 'N/A'; ?></strong>
                                    </td>
                                    <td><?php echo isset($license['Nombre']) ? remove_junk($license['Nombre']) : 'N/A'; ?></td>
                                    <td><?php echo isset($license['NomEquipo']) ? remove_junk($license['NomEquipo']) : 'N/A'; ?></td>
                                    <td><?php echo isset($license['Fecha']) ? read_date1($license['Fecha']) : 'N/A'; ?></td>
                                    <td>
                                        <?php 
                                        $estado = isset($license['Utilizado']) ? strtoupper($license['Utilizado']) : 'INACTIVO';
                                        $badge_class = ($estado === 'ACTIVO' || $estado === '1') ? 'badge-success' : 'badge-secondary';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $estado; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (tiene_permiso('licencias', 'editar')): ?>
                                        <a href="../../index.php?module=licencias&action=editar_sap&id=<?php echo (int)$license['IdLicencias']; ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (tiene_permiso('licencias', 'eliminar')): ?>
                                        <a href="../../index.php?module=licencias&action=eliminar_sap&id=<?php echo (int)$license['IdLicencias']; ?>" 
                                           class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta licencia?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <script>
                            $(document).ready(function() {
                                $('#sapLicensesTable').DataTable({
                                    "language": {
                                        "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
                                    },
                                    "pageLength": 25
                                });
                            });
                        </script>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No hay licencias SAP registradas.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
