<?php
/**
 * Módulo Usuarios - Agregar Usuario
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Agregar Usuario';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('usuarios', 'crear');
ensure_users_tipo_registro();

$groups = find_all('user_groups');

// Obtener áreas para el select
$allowedAreas = ['RAD', 'GO', 'GG', 'GC', 'AD'];

if (isset($_POST['add_user'])) {
    $req_fields = array('name', 'username', 'password', 'level', 'area');
    validate_fields($req_fields);
    $postedArea = strtoupper(trim((string)($_POST['area'] ?? '')));
    if (!in_array($postedArea, $allowedAreas, true)) $errors = 'Seleccione una area valida: RAD, GO, GG, GC o AD.';

    if (empty($errors)) {
        $name = remove_junk($db->escape($_POST['name']));
        $username = remove_junk($db->escape($_POST['username']));
        $password = remove_junk($db->escape($_POST['password']));
        $user_level = (int)$db->escape($_POST['level']);
        $status = isset($_POST['status']) ? 1 : 0;
        $estado = isset($_POST['estado']) ? 1 : 0;
        $tipo_registro = strtoupper((string)($_POST['tipo_registro'] ?? 'USUARIO'));
        if (!in_array($tipo_registro, ['USUARIO', 'CORREO'], true)) $tipo_registro = 'USUARIO';
        $tipo_registro = $db->escape($tipo_registro);
        $es_personal_ti = isset($_POST['es_personal_ti']) ? 1 : 0;
        
        // Campos adicionales
        $UserHomeTama = remove_junk($db->escape($_POST['UserHomeTama'] ?? ''));
        $PasswordHomeTama = remove_junk($db->escape($_POST['PasswordHomeTama'] ?? ''));
        $cargo = remove_junk($db->escape($_POST['cargo'] ?? ''));
        $correo = remove_junk($db->escape($_POST['correo'] ?? ''));
        $PasswordCorreo = remove_junk($db->escape($_POST['PasswordCorreo'] ?? ''));
        $area = $db->escape($postedArea);
        $subarea = remove_junk($db->escape($_POST['subarea'] ?? ''));
        $telefono = (int)$db->escape($_POST['telefono'] ?? 0);
        $tipocorreo = (int)$db->escape($_POST['tipocorreo'] ?? 0);

        $sql = "INSERT INTO users (name, username, password, UserHomeTama, PasswordHomeTama, cargo, user_level, status, tipo_registro, es_personal_ti, estado, correo, PasswordCorreo, area, subarea, telefono, tipocorreo)
                VALUES ('{$name}', '{$username}', '" . sha1($password) . "', '{$UserHomeTama}', '{$PasswordHomeTama}', '{$cargo}', '{$user_level}', '{$status}', '{$tipo_registro}', '{$es_personal_ti}', '{$estado}', '{$correo}', '{$PasswordCorreo}', '{$area}', '{$subarea}', '{$telefono}', '{$tipocorreo}')";

        if ($db->query($sql)) {
            $session->msg('s', 'Usuario agregado exitosamente.');
            redirect('index.php', false);
        } else {
            $session->msg('d', 'Error al agregar usuario.');
            redirect('agregar.php', false);
        }
    } else {
        $session->msg('d', $errors);
        redirect('agregar.php', false);
    }
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-user-plus mr-2"></i>Agregar Usuario</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
                            <li class="breadcrumb-item active">Agregar</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <form method="post" action="agregar.php">
                    <div class="row">
                        <!-- Columna Izquierda - Datos Básicos -->
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-user mr-2"></i>Datos de Acceso</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Nombre Completo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Nombre de Usuario <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="username" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Contraseña <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Rol de Usuario <span class="text-danger">*</span></label>
                                        <select class="form-control" name="level" required>
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($groups as $group): ?>
                                            <option value="<?php echo (int)$group['group_level']; ?>">
                                                <?php echo remove_junk(ucwords($group['group_name'])); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Tipo de registro <span class="text-danger">*</span></label>
                                        <select class="form-control" name="tipo_registro" required>
                                            <option value="USUARIO" selected>Usuario</option>
                                            <option value="CORREO">Correo</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Cargo</label>
                                        <input type="text" class="form-control" name="cargo" placeholder="Ej: Analista de Sistemas">
                                    </div>
                                    <div class="form-group"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="es_personal_ti" name="es_personal_ti"><label class="custom-control-label" for="es_personal_ti"><i class="fas fa-tools text-info mr-1"></i>Personal de TI</label></div><small class="text-muted">Habilita al usuario como responsable de mantenimientos de equipos TI.</small></div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="status" name="status" checked>
                                                    <label class="custom-control-label" for="status">Status Activo</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="estado" name="estado" checked>
                                                    <label class="custom-control-label" for="estado">Estado Habilitado</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Home TAMA -->
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-home mr-2"></i>Credenciales Home TAMA</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Usuario Home TAMA</label>
                                        <input type="text" class="form-control" name="UserHomeTama" placeholder="Usuario de Home TAMA">
                                    </div>
                                    <div class="form-group">
                                        <label>Contraseña Home TAMA</label>
                                        <input type="password" class="form-control" name="PasswordHomeTama" placeholder="Contraseña de Home TAMA">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha - Datos de Contacto -->
                        <div class="col-md-6">
                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-envelope mr-2"></i>Datos de Correo</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Correo Electrónico</label>
                                        <input type="email" class="form-control" name="correo" placeholder="correo@tama.com.pe">
                                    </div>
                                    <div class="form-group">
                                        <label>Contraseña del Correo</label>
                                        <input type="password" class="form-control" name="PasswordCorreo" placeholder="Contraseña del correo">
                                    </div>
                                    <div class="form-group">
                                        <label>Tipo de Correo</label>
                                        <select class="form-control" name="tipocorreo">
                                            <option value="0">Sin correo</option>
                                            <option value="1">Outlook</option>
                                            <option value="2">Gmail</option>
                                            <option value="3">Otro</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-warning">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-building mr-2"></i>Datos Organizacionales</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Área</label>
                                        <select class="form-control" name="area" required>
                                            <option value="">Seleccione un &aacute;rea...</option>
                                            <?php foreach($allowedAreas as $areaOption): ?>
                                            <option value="<?php echo $areaOption; ?>" <?php echo (($_POST['area'] ?? '') === $areaOption) ? 'selected' : ''; ?>><?php echo $areaOption; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Subárea</label>
                                        <input type="text" class="form-control" name="subarea" placeholder="Ej: Soporte TI">
                                    </div>
                                    <div class="form-group">
                                        <label>Teléfono</label>
                                        <input type="number" class="form-control" name="telefono" placeholder="999999999" maxlength="9">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" name="add_user" class="btn btn-primary float-right">
                                        <i class="fas fa-save"></i> Guardar Usuario
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </section>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
