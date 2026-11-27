<?php
/**
 * Módulo Celulares - Agregar Teléfono
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Agregar Teléfono';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('celulares', 'crear');

if (isset($_POST['add_telefono'])) {
    $req_fields = array('Modelo', 'Imei');
    validate_fields($req_fields);

    if (empty($errors)) {
        $Modelo = remove_junk($db->escape($_POST['Modelo']));
        $Imei = remove_junk($db->escape($_POST['Imei']));
        $Fecha = remove_junk($db->escape($_POST['Fecha'] ?? date('Y-m-d')));
        $Num_guia = remove_junk($db->escape($_POST['Num_guia'] ?? ''));
        $Costo_Equipo = (int)$db->escape($_POST['Costo_Equipo'] ?? 0);
        $Contrato = remove_junk($db->escape($_POST['Contrato'] ?? ''));

        $sql = "INSERT INTO telefono (Modelo, Imei, Fecha, Num_guia, Costo_Equipo, Contrato)
                VALUES ('{$Modelo}', '{$Imei}', '{$Fecha}', '{$Num_guia}', '{$Costo_Equipo}', '{$Contrato}')";

        if ($db->query($sql)) {
            $session->msg('s', 'Teléfono agregado exitosamente.');
            redirect('index.php', false);
        } else {
            $session->msg('d', 'Error al agregar teléfono.');
            redirect('agregar.php', false);
        }
    } else {
        $session->msg('d', $errors);
        redirect('agregar.php', false);
    }
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-plus-circle mr-2"></i>Agregar Teléfono</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Celulares</a></li>
                            <li class="breadcrumb-item active">Agregar</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-mobile-alt mr-2"></i>Datos del Teléfono</h3>
                            </div>
                            <form method="post" action="agregar.php">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Modelo <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="Modelo" placeholder="Ej: SAMSUNG Galaxy A03" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>IMEI <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="Imei" placeholder="15 dígitos" maxlength="15" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Fecha de Compra</label>
                                                <input type="date" class="form-control" name="Fecha" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Número de Guía</label>
                                                <input type="text" class="form-control" name="Num_guia" placeholder="Ej: 007-N2107433">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Costo del Equipo (S/.)</label>
                                                <input type="number" class="form-control" name="Costo_Equipo" placeholder="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Contrato</label>
                                                <input type="text" class="form-control" name="Contrato" placeholder="Ej: SI POR 6 MESES">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" name="add_telefono" class="btn btn-primary float-right">
                                        <i class="fas fa-save"></i> Guardar Teléfono
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </section>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
