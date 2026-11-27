<?php
/**
 * Módulo Licencias - Vista Principal
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Gestión de Licencias';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('licencias', 'ver');

include_once(__DIR__ . '/../../layouts/header.php');
?>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-key mr-2"></i>Gestión de Licencias</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item active">Licencias</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap">
                            <div><strong><i class="fas fa-shapes mr-1 text-info"></i>Aplicaciones, suscripciones y soporte</strong><small class="d-block text-muted">Administre costos mensuales o anuales, contratos, renovaciones e instalaciones por equipo.</small></div>
                            <a href="../activos/aplicaciones.php#contracts" class="btn btn-info btn-sm mt-2 mt-md-0"><i class="fas fa-arrow-right mr-1"></i>Abrir gestión de aplicaciones</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header bg-primary">
                                <h3 class="card-title"><i class="fab fa-microsoft mr-2"></i>Licencias Microsoft Office</h3>
                            </div>
                            <div class="card-body">
                                <p>Gestión de licencias Microsoft Office en la empresa</p>
                                <a href="../../dispatcher.php?module=licencias&action=office" class="btn btn-block btn-primary btn-lg">
                                    <i class="fas fa-file-word fa-3x mb-2"></i><br>
                                    Ver Licencias Office
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-info">
                            <div class="card-header bg-info">
                                <h3 class="card-title"><i class="fas fa-database mr-2"></i>Licencias SAP Business One</h3>
                            </div>
                            <div class="card-body">
                                <p>Gestión de cuentas y licencias SAP Business One</p>
                                <a href="../../dispatcher.php?module=licencias&action=sap" class="btn btn-block btn-info btn-lg">
                                    <i class="fas fa-cubes fa-3x mb-2"></i><br>
                                    Ver Licencias SAP
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
