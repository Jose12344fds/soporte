<?php
/**
 * Módulo Licencias - Vista de Licencias Office
 * Sistema de Soporte TI - TAMA
 */

$page_title = 'Licencias Microsoft Office';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

// Verificar permisos
require_permiso('licencias', 'ver');

// Obtener todas las licencias Office
$all_licenses = find_all('licencias');

// Filtrar solo licencias Office
$office_licenses = array_filter($all_licenses, function($lic) {
    return isset($lic['Tipo_Licencias']) && strpos(strtoupper($lic['Tipo_Licencias']), 'OFFICE') !== false;
});

// Obtener usuarios para referencias
$all_users = find_all('users');

include_once(__DIR__ . '/../../layouts/header.php');
?>

<!-- Content Header -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fab fa-microsoft mr-2"></i>Licencias Microsoft Office</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Licencias</a></li>
                    <li class="breadcrumb-item active">Office</li>
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
                <a href="../../index.php?module=licencias&action=agregar_office" class="btn btn-success">
                    <i class="fas fa-plus"></i> Agregar Licencia Office
                </a>
                <?php endif; ?>
                <a href="../../Reportes/ReporteLicAppTama.php" class="btn btn-info ml-2" target="_blank">
                    <i class="fas fa-file-excel"></i> Descargar Reporte
                </a>
            </div>
        </div>

        <!-- Tabla de Licencias -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title">
                            <i class="fas fa-table mr-2"></i>
                            Listado de Licencias Office (<?php echo count($office_licenses); ?>)
                        </h3>
                    </div>
                    <div class="card-body table-responsive">
                        <?php if (count($office_licenses) > 0): ?>
                        <table class="table table-striped table-hover" id="licensesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 5%">ID</th>
                                    <th style="width: 10%">Tipo</th>
                                    <th style="width: 15%">Serial</th>
                                    <th style="width: 15%">Producto</th>
                                    <th style="width: 10%">Equipo</th>
                                    <th style="width: 10%">Usuario</th>
                                    <th style="width: 10%">Fecha</th>
                                    <th style="width: 8%">Estado</th>
                                    <th style="width: 17%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($office_licenses as $license): ?>
                                <tr>
                                    <td><?php echo isset($license['IdLicencias']) ? (int)$license['IdLicencias'] : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo isset($license['Tipo_Licencias']) ? remove_junk($license['Tipo_Licencias']) : 'N/A'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?php echo isset($license['Serial']) ? remove_junk($license['Serial']) : 'N/A'; ?></code>
                                    </td>
                                    <td><?php echo isset($license['Nombre']) ? remove_junk($license['Nombre']) : 'N/A'; ?></td>
                                    <td><?php echo isset($license['NomEquipo']) ? remove_junk($license['NomEquipo']) : 'N/A'; ?></td>
                                    <td><?php echo isset($license['Usuario']) ? remove_junk($license['Usuario']) : 'N/A'; ?></td>
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
                                        <a href="../../index.php?module=licencias&action=editar_office&id=<?php echo (int)$license['IdLicencias']; ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (tiene_permiso('licencias', 'eliminar')): ?>
                                        <a href="../../index.php?module=licencias&action=eliminar_office&id=<?php echo (int)$license['IdLicencias']; ?>" 
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
                                $('#licensesTable').DataTable({
                                    "language": {
                                        "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
                                    },
                                    "pageLength": 25
                                });
                            });
                        </script>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No hay licencias Office registradas.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
