<?php
/**
 * Módulo Usuarios - Editar Usuario
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Editar Usuario';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('usuarios', 'editar');
ensure_users_tipo_registro();

$groups = find_all('user_groups');
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$e_user = find_by_id('users', $user_id);

// Obtener áreas para el select
$allowedAreas = ['RAD', 'GO', 'GG', 'GC', 'AD'];

if (!$e_user) {
    $session->msg('d', 'Usuario no encontrado.');
    redirect('index.php', false);
}

if (isset($_POST['update_user'])) {
    $req_fields = array('name', 'username', 'level', 'area');
    validate_fields($req_fields);
    $postedArea = strtoupper(trim((string)($_POST['area'] ?? '')));
    if (!in_array($postedArea, $allowedAreas, true)) $errors = 'Seleccione una area valida: RAD, GO, GG, GC o AD.';

    if (empty($errors)) {
        $name = remove_junk($db->escape($_POST['name']));
        $username = remove_junk($db->escape($_POST['username']));
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

        $sql = "UPDATE users SET 
                name = '{$name}', 
                username = '{$username}', 
                user_level = '{$user_level}',
                status = '{$status}',
                tipo_registro = '{$tipo_registro}',
                es_personal_ti = '{$es_personal_ti}',
                estado = '{$estado}',
                UserHomeTama = '{$UserHomeTama}',
                cargo = '{$cargo}',
                correo = '{$correo}',
                area = '{$area}',
                subarea = '{$subarea}',
                telefono = '{$telefono}',
                tipocorreo = '{$tipocorreo}'";

        // Actualizar contraseña solo si se ingresó una nueva
        if (!empty($_POST['password'])) {
            $password = remove_junk($db->escape($_POST['password']));
            $sql .= ", password = '" . sha1($password) . "'";
        }
        
        // Actualizar contraseña Home TAMA si se ingresó
        if (!empty($_POST['PasswordHomeTama'])) {
            $sql .= ", PasswordHomeTama = '{$PasswordHomeTama}'";
        }
        
        // Actualizar contraseña correo si se ingresó
        if (!empty($_POST['PasswordCorreo'])) {
            $sql .= ", PasswordCorreo = '{$PasswordCorreo}'";
        }

        $sql .= " WHERE id = '{$user_id}'";

        if ($db->query($sql)) {
            $session->msg('s', 'Usuario actualizado exitosamente.');
            redirect('index.php', false);
        } else {
            $session->msg('d', 'Error al actualizar usuario.');
            redirect('editar.php?id=' . $user_id, false);
        }
    } else {
        $session->msg('d', $errors);
        redirect('editar.php?id=' . $user_id, false);
    }
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-user-edit mr-2"></i>Editar Usuario</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <form method="post" action="editar.php?id=<?php echo $user_id; ?>">
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
                                        <input type="text" class="form-control" name="name" value="<?php echo remove_junk($e_user['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Nombre de Usuario <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="username" value="<?php echo remove_junk($e_user['username']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Nueva Contraseña</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="password" id="password" placeholder="Dejar vacío para mantener actual">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-muted">Contraseña actual está encriptada (SHA1)</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Rol de Usuario <span class="text-danger">*</span></label>
                                        <select class="form-control" name="level" required>
                                            <?php foreach ($groups as $group): ?>
                                            <option value="<?php echo (int)$group['group_level']; ?>" <?php echo ($e_user['user_level'] == $group['group_level']) ? 'selected' : ''; ?>>
                                                <?php echo remove_junk(ucwords($group['group_name'])); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Tipo de registro <span class="text-danger">*</span></label>
                                        <select class="form-control" name="tipo_registro" required>
                                            <option value="USUARIO" <?php echo (($e_user['tipo_registro'] ?? 'USUARIO') === 'USUARIO') ? 'selected' : ''; ?>>Usuario</option>
                                            <option value="CORREO" <?php echo (($e_user['tipo_registro'] ?? '') === 'CORREO') ? 'selected' : ''; ?>>Correo</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Cargo</label>
                                        <input type="text" class="form-control" name="cargo" value="<?php echo remove_junk($e_user['cargo'] ?? ''); ?>" placeholder="Ej: Analista de Sistemas">
                                    </div>
                                    <div class="form-group"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="es_personal_ti" name="es_personal_ti" <?php echo !empty($e_user['es_personal_ti']) ? 'checked' : ''; ?>><label class="custom-control-label" for="es_personal_ti"><i class="fas fa-tools text-info mr-1"></i>Personal de TI</label></div><small class="text-muted">Puede ser asignado como responsable de mantenimientos de equipos TI.</small></div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="status" name="status" <?php echo ($e_user['status'] == '1') ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="status">Status Activo</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="estado" name="estado" <?php echo ($e_user['estado'] == '1') ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="estado">Estado Habilitado</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!empty($e_user['last_login'])): ?>
                                    <div class="callout callout-info">
                                        <small><i class="fas fa-clock"></i> Último acceso: <?php echo $e_user['last_login']; ?></small>
                                    </div>
                                    <?php endif; ?>
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
                                        <input type="text" class="form-control" name="UserHomeTama" value="<?php echo remove_junk($e_user['UserHomeTama'] ?? ''); ?>" placeholder="Usuario de Home TAMA">
                                    </div>
                                    <div class="form-group">
                                        <label>Contraseña Home TAMA</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="PasswordHomeTama" id="PasswordHomeTama" value="<?php echo remove_junk($e_user['PasswordHomeTama'] ?? ''); ?>">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('PasswordHomeTama')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-info"><i class="fas fa-info-circle"></i> Visible para administradores</small>
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
                                        <input type="email" class="form-control" name="correo" value="<?php echo remove_junk($e_user['correo'] ?? ''); ?>" placeholder="correo@tama.com.pe">
                                    </div>
                                    <div class="form-group">
                                        <label>Contraseña del Correo</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="PasswordCorreo" id="PasswordCorreo" value="<?php echo remove_junk($e_user['PasswordCorreo'] ?? ''); ?>">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('PasswordCorreo')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-info"><i class="fas fa-info-circle"></i> Visible para administradores</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Tipo de Correo</label>
                                        <select class="form-control" name="tipocorreo">
                                            <option value="0" <?php echo ($e_user['tipocorreo'] ?? 0) == 0 ? 'selected' : ''; ?>>Sin correo</option>
                                            <option value="1" <?php echo ($e_user['tipocorreo'] ?? 0) == 1 ? 'selected' : ''; ?>>Outlook</option>
                                            <option value="2" <?php echo ($e_user['tipocorreo'] ?? 0) == 2 ? 'selected' : ''; ?>>Gmail</option>
                                            <option value="3" <?php echo ($e_user['tipocorreo'] ?? 0) == 3 ? 'selected' : ''; ?>>Otro</option>
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
                                            <option value="<?php echo $areaOption; ?>" <?php echo (strtoupper(trim((string)($e_user['area'] ?? ''))) === $areaOption) ? 'selected' : ''; ?>><?php echo $areaOption; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Subárea</label>
                                        <input type="text" class="form-control" name="subarea" value="<?php echo remove_junk($e_user['subarea'] ?? ''); ?>" placeholder="Ej: Soporte TI">
                                    </div>
                                    <div class="form-group">
                                        <label>Teléfono</label>
                                        <input type="number" class="form-control" name="telefono" value="<?php echo (int)($e_user['telefono'] ?? 0); ?>" placeholder="999999999" maxlength="9">
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
                                    <button type="submit" name="update_user" class="btn btn-primary float-right">
                                        <i class="fas fa-save"></i> Actualizar Usuario
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </section>

<script>
function togglePassword(inputId) {
    var input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}
</script>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
