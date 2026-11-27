<?php
/**
 * Módulo Celulares - Editar Teléfono
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Editar Teléfono';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('celulares', 'editar');

$telefono_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar teléfono
$sql = $db->query("SELECT * FROM telefono WHERE IdTelefo = '{$telefono_id}' LIMIT 1");
$telefono = $db->fetch_assoc($sql);

if (!$telefono) {
    $session->msg('d', 'Teléfono no encontrado.');
    redirect('index.php', false);
}

// Obtener asignación actual
$sql_asig = $db->query("SELECT c.*, u.name as UsuarioNombre, u.area 
    FROM celulares c 
    LEFT JOIN users u ON u.id = c.Usuario 
    WHERE c.IdTelefo = '{$telefono_id}' 
    ORDER BY c.IdCelu DESC LIMIT 1");
$asignacion = $db->fetch_assoc($sql_asig);

// Obtener usuarios activos
$usuarios = $db->query("SELECT id, name, area FROM users WHERE status = 1 ORDER BY name");

// Obtener chips
$chips = $db->query("SELECT * FROM chip ORDER BY IdChip");

if (isset($_POST['update_telefono'])) {
    $req_fields = array('Modelo', 'Imei');
    validate_fields($req_fields);

    if (empty($errors)) {
        $Modelo = remove_junk($db->escape($_POST['Modelo']));
        $Imei = remove_junk($db->escape($_POST['Imei']));
        $Fecha = remove_junk($db->escape($_POST['Fecha'] ?? ''));
        $Num_guia = remove_junk($db->escape($_POST['Num_guia'] ?? ''));
        $Costo_Equipo = (int)$db->escape($_POST['Costo_Equipo'] ?? 0);
        $Contrato = remove_junk($db->escape($_POST['Contrato'] ?? ''));

        $sql = "UPDATE telefono SET 
                Modelo = '{$Modelo}',
                Imei = '{$Imei}',
                Fecha = '{$Fecha}',
                Num_guia = '{$Num_guia}',
                Costo_Equipo = '{$Costo_Equipo}',
                Contrato = '{$Contrato}'
                WHERE IdTelefo = '{$telefono_id}'";

        if ($db->query($sql)) {
            // Si hay cambio de asignación
            if (isset($_POST['Usuario']) && $_POST['Usuario'] != ($asignacion['Usuario'] ?? '')) {
                $Usuario = (int)$db->escape($_POST['Usuario']);
                $IdChip = (int)$db->escape($_POST['IdChip'] ?? 0);
                $FechaEntrega = remove_junk($db->escape($_POST['FechaEntrega'] ?? date('Y-m-d')));
                $Observaciones = remove_junk($db->escape($_POST['Observaciones'] ?? ''));
                $EstUso = $Usuario > 0 ? 2 : 0;

                if ($asignacion) {
                    // Actualizar asignación existente
                    $db->query("UPDATE celulares SET 
                        Usuario = '{$Usuario}',
                        IdChip = '{$IdChip}',
                        FechaEntrega = '{$FechaEntrega}',
                        Observaciones = '{$Observaciones}',
                        EstUso = '{$EstUso}'
                        WHERE IdCelu = '{$asignacion['IdCelu']}'");
                } else if ($Usuario > 0) {
                    // Crear nueva asignación
                    $db->query("INSERT INTO celulares (Usuario, IdTelefo, IdChip, FechaEntrega, Observaciones, EstUso)
                        VALUES ('{$Usuario}', '{$telefono_id}', '{$IdChip}', '{$FechaEntrega}', '{$Observaciones}', '{$EstUso}')");
                }
            }
            
            $session->msg('s', 'Teléfono actualizado exitosamente.');
            redirect('index.php', false);
        } else {
            $session->msg('d', 'Error al actualizar teléfono.');
            redirect('editar.php?id=' . $telefono_id, false);
        }
    } else {
        $session->msg('d', $errors);
        redirect('editar.php?id=' . $telefono_id, false);
    }
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-edit mr-2"></i>Editar Teléfono</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Celulares</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <form method="post" action="editar.php?id=<?php echo $telefono_id; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-mobile-alt mr-2"></i>Datos del Teléfono</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Modelo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="Modelo" value="<?php echo remove_junk($telefono['Modelo']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>IMEI <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="Imei" value="<?php echo remove_junk($telefono['Imei']); ?>" maxlength="15" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Fecha de Compra</label>
                                                <input type="date" class="form-control" name="Fecha" value="<?php echo $telefono['Fecha']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Número de Guía</label>
                                                <input type="text" class="form-control" name="Num_guia" value="<?php echo remove_junk($telefono['Num_guia']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Costo (S/.)</label>
                                                <input type="number" class="form-control" name="Costo_Equipo" value="<?php echo (int)$telefono['Costo_Equipo']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Contrato</label>
                                                <input type="text" class="form-control" name="Contrato" value="<?php echo remove_junk($telefono['Contrato']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-user mr-2"></i>Asignación Actual</h3>
                                </div>
                                <div class="card-body">
                                    <?php if ($asignacion && $asignacion['UsuarioNombre']): ?>
                                    <div class="callout callout-info">
                                        <h5><i class="fas fa-user"></i> <?php echo $asignacion['UsuarioNombre']; ?></h5>
                                        <p class="mb-0">
                                            <small>Área: <?php echo $asignacion['area'] ?? 'No especificada'; ?></small><br>
                                            <small>Desde: <?php echo $asignacion['FechaEntrega']; ?></small>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="form-group">
                                        <label>Usuario Asignado</label>
                                        <select class="form-control select2" name="Usuario">
                                            <option value="0">Sin asignar</option>
                                            <?php while($usr = $usuarios->fetch_assoc()): ?>
                                            <option value="<?php echo $usr['id']; ?>" <?php echo (($asignacion['Usuario'] ?? 0) == $usr['id']) ? 'selected' : ''; ?>>
                                                <?php echo $usr['name'] . ' (' . ($usr['area'] ?? 'Sin área') . ')'; ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Chip/Línea</label>
                                        <select class="form-control" name="IdChip">
                                            <option value="0">Sin chip</option>
                                            <?php while($ch = $chips->fetch_assoc()): ?>
                                            <option value="<?php echo $ch['IdChip']; ?>" <?php echo (($asignacion['IdChip'] ?? 0) == $ch['IdChip']) ? 'selected' : ''; ?>>
                                                <?php echo $ch['Numero'] ?? 'Chip #' . $ch['IdChip']; ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha de Entrega</label>
                                        <input type="date" class="form-control" name="FechaEntrega" value="<?php echo $asignacion['FechaEntrega'] ?? date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Observaciones</label>
                                        <textarea class="form-control" name="Observaciones" rows="2"><?php echo remove_junk($asignacion['Observaciones'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" name="update_telefono" class="btn btn-primary float-right">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </section>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
