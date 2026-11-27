<?php
/**
 * Módulo Celulares - Asignar Teléfono a Usuario
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Asignar Teléfono';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('celulares', 'editar');

// Obtener teléfonos disponibles
$telefonos_disponibles = $db->query("SELECT t.* FROM telefono t 
    LEFT JOIN celulares c ON c.IdTelefo = t.IdTelefo AND c.EstUso = 2
    WHERE c.IdCelu IS NULL OR c.EstUso != 2
    ORDER BY t.Modelo");

// Obtener usuarios activos
$usuarios = $db->query("SELECT id, name, area, cargo FROM users WHERE status = 1 AND estado = 1 ORDER BY name");

// Obtener chips disponibles
$chips = $db->query("SELECT * FROM chip ORDER BY IdChip");

// Si viene un ID de teléfono preseleccionado
$telefono_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (isset($_POST['asignar_telefono'])) {
    $req_fields = array('IdTelefo', 'Usuario');
    validate_fields($req_fields);

    if (empty($errors)) {
        $IdTelefo = (int)$db->escape($_POST['IdTelefo']);
        $Usuario = (int)$db->escape($_POST['Usuario']);
        $IdChip = (int)$db->escape($_POST['IdChip'] ?? 0);
        $FechaEntrega = remove_junk($db->escape($_POST['FechaEntrega'] ?? date('Y-m-d')));
        $Observaciones = remove_junk($db->escape($_POST['Observaciones'] ?? ''));
        $EstUso = 2; // En uso

        $sql = "INSERT INTO celulares (Usuario, IdTelefo, IdChip, FechaEntrega, Observaciones, EstUso)
                VALUES ('{$Usuario}', '{$IdTelefo}', '{$IdChip}', '{$FechaEntrega}', '{$Observaciones}', '{$EstUso}')";

        if ($db->query($sql)) {
            $session->msg('s', 'Teléfono asignado exitosamente.');
            redirect('index.php', false);
        } else {
            $session->msg('d', 'Error al asignar teléfono.');
            redirect('asignar.php', false);
        }
    } else {
        $session->msg('d', $errors);
        redirect('asignar.php', false);
    }
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-user-plus mr-2"></i>Asignar Teléfono</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Celulares</a></li>
                            <li class="breadcrumb-item active">Asignar</li>
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
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Asignar Teléfono a Usuario</h3>
                            </div>
                            <form method="post" action="asignar.php">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Teléfono <span class="text-danger">*</span></label>
                                                <select class="form-control select2" name="IdTelefo" required>
                                                    <option value="">Seleccionar teléfono...</option>
                                                    <?php while($tel = $telefonos_disponibles->fetch_assoc()): ?>
                                                    <option value="<?php echo $tel['IdTelefo']; ?>" <?php echo ($telefono_id == $tel['IdTelefo']) ? 'selected' : ''; ?>>
                                                        <?php echo $tel['Modelo'] . ' - IMEI: ' . $tel['Imei']; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Usuario <span class="text-danger">*</span></label>
                                                <select class="form-control select2" name="Usuario" required>
                                                    <option value="">Seleccionar usuario...</option>
                                                    <?php while($usr = $usuarios->fetch_assoc()): ?>
                                                    <option value="<?php echo $usr['id']; ?>">
                                                        <?php echo $usr['name'] . ' (' . ($usr['area'] ?? 'Sin área') . ')'; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Chip/Línea</label>
                                                <select class="form-control" name="IdChip">
                                                    <option value="0">Sin chip</option>
                                                    <?php while($ch = $chips->fetch_assoc()): ?>
                                                    <option value="<?php echo $ch['IdChip']; ?>">
                                                        <?php echo $ch['Numero'] ?? 'Chip #' . $ch['IdChip']; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Fecha de Entrega</label>
                                                <input type="date" class="form-control" name="FechaEntrega" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Observaciones</label>
                                        <textarea class="form-control" name="Observaciones" rows="3" placeholder="Ej: Buen estado, entregado con funda y cargador"></textarea>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" name="asignar_telefono" class="btn btn-success float-right">
                                        <i class="fas fa-check"></i> Asignar Teléfono
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </section>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
