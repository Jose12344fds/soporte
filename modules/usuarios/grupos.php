<?php
/**
 * Módulo Usuarios - Gestión de Grupos y Permisos
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Grupos y Permisos';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('grupos', 'ver');

$all_groups = find_all('user_groups');
$modulos = obtener_modulos();

// Guardar permisos
if (isset($_POST['guardar_permisos']) && tiene_permiso('grupos', 'editar')) {
    $group_level = (int)$_POST['group_level'];
    $permisos = isset($_POST['permisos']) ? $_POST['permisos'] : [];
    
    if (guardar_permisos_grupo($group_level, $permisos)) {
        $session->msg('s', 'Permisos actualizados correctamente.');
    } else {
        $session->msg('d', 'Error al guardar permisos.');
    }
    redirect('grupos.php', false);
}

// Agregar grupo
if (isset($_POST['agregar_grupo']) && tiene_permiso('grupos', 'crear')) {
    $gname  = trim($db->escape($_POST['group_name'] ?? ''));
    $glevel = (int)($_POST['group_level'] ?? 0);
    $gstatus = ($_POST['group_status'] ?? '0') === '1' ? '1' : '0';

    if ($gname === '') {
        $session->msg('d', 'El nombre del grupo no puede estar vacío.');
        redirect('grupos.php', false);
    }
    if ($glevel < 1 || $glevel > 100) {
        $session->msg('d', 'El nivel debe estar entre 1 y 100.');
        redirect('grupos.php', false);
    }
    // Verificar que el nivel no esté ya en uso
    $chk = $db->query("SELECT id FROM user_groups WHERE group_level = {$glevel}");
    if ($db->num_rows($chk) > 0) {
        $session->msg('d', 'Ya existe un grupo con el nivel ' . $glevel . '. Usa un nivel diferente.');
        redirect('grupos.php', false);
    }
    $db->query("INSERT INTO user_groups (group_name, group_level, group_status) VALUES ('{$gname}', {$glevel}, '{$gstatus}')");
    $session->msg('s', 'Grupo "' . htmlspecialchars($gname) . '" creado correctamente.');
    redirect('grupos.php', false);
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-user-shield mr-2"></i>Grupos y Permisos</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
                            <li class="breadcrumb-item active">Grupos</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <div class="row">
                    <!-- Lista de Grupos -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-primary d-flex align-items-center">
                                <h3 class="card-title flex-grow-1">Grupos de Usuario</h3>
                                <?php if (tiene_permiso('grupos', 'crear')): ?>
                                <button type="button" class="btn btn-sm btn-light" data-toggle="modal" data-target="#modalAgregarGrupo" title="Agregar grupo">
                                    <i class="fas fa-plus mr-1"></i>Nuevo
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Grupo</th>
                                            <th class="text-center">Nivel</th>
                                            <th class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($all_groups as $group): ?>
                                        <tr class="grupo-row" data-level="<?php echo $group['group_level']; ?>" style="cursor: pointer;">
                                            <td><?php echo remove_junk(ucwords($group['group_name'])); ?></td>
                                            <td class="text-center"><?php echo $group['group_level']; ?></td>
                                            <td class="text-center">
                                                <?php if($group['group_status'] === '1'): ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de Permisos -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-info">
                                <h3 class="card-title">
                                    <i class="fas fa-key mr-2"></i>
                                    Permisos del Grupo: <span id="grupo-nombre">Seleccione un grupo</span>
                                </h3>
                            </div>
                            <form method="post" action="grupos.php" id="formPermisos">
                                <input type="hidden" name="group_level" id="group_level" value="">
                                <div class="card-body">
                                    <div id="permisos-container">
                                        <p class="text-muted text-center">
                                            <i class="fas fa-hand-pointer fa-2x mb-2"></i><br>
                                            Haga clic en un grupo para ver y editar sus permisos
                                        </p>
                                    </div>
                                </div>
                                <div class="card-footer" id="footer-permisos" style="display: none;">
                                    <?php if (tiene_permiso('grupos', 'editar')): ?>
                                    <button type="submit" name="guardar_permisos" class="btn btn-success">
                                        <i class="fas fa-save"></i> Guardar Permisos
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </section>

<?php 
// Preparar datos de permisos para JavaScript
$permisosJson = [];
foreach ($all_groups as $group) {
    $permisosJson[$group['group_level']] = [
        'name' => $group['group_name'],
        'permisos' => obtener_permisos_grupo($group['group_level'])
    ];
}

$gruposDataJs = json_encode($permisosJson);
$modulosJs = json_encode($modulos);

$inlineJs = <<<JS
var gruposData = {$gruposDataJs};
var modulos = {$modulosJs};

$(document).ready(function() {
    $('.grupo-row').click(function() {
        var level = $(this).data('level');
        cargarPermisos(level);
        $('.grupo-row').removeClass('table-active');
        $(this).addClass('table-active');
    });
});

function cargarPermisos(level) {
    var grupo = gruposData[level];
    if (!grupo) {
        console.log('Grupo no encontrado:', level);
        return;
    }
    
    $('#grupo-nombre').text(grupo.name);
    $('#group_level').val(level);
    
    var html = '<table class="table table-bordered"><thead><tr>';
    html += '<th>Módulo</th>';
    html += '<th class="text-center">Ver</th>';
    html += '<th class="text-center">Crear</th>';
    html += '<th class="text-center">Editar</th>';
    html += '<th class="text-center">Eliminar</th>';
    html += '</tr></thead><tbody>';
    
    for (var modulo in modulos) {
        var perms = grupo.permisos[modulo] || {ver: false, crear: false, editar: false, eliminar: false};
        html += '<tr>';
        html += '<td><strong>' + modulos[modulo] + '</strong></td>';
        html += '<td class="text-center"><input type="checkbox" name="permisos[' + modulo + '][ver]" value="1" ' + (perms.ver ? 'checked' : '') + '></td>';
        html += '<td class="text-center"><input type="checkbox" name="permisos[' + modulo + '][crear]" value="1" ' + (perms.crear ? 'checked' : '') + '></td>';
        html += '<td class="text-center"><input type="checkbox" name="permisos[' + modulo + '][editar]" value="1" ' + (perms.editar ? 'checked' : '') + '></td>';
        html += '<td class="text-center"><input type="checkbox" name="permisos[' + modulo + '][eliminar]" value="1" ' + (perms.eliminar ? 'checked' : '') + '></td>';
        html += '</tr>';
    }
    
    html += '</tbody></table>';
    
    $('#permisos-container').html(html);
    $('#footer-permisos').show();
}
JS;

// Modal HTML para agregar grupo
echo '
<!-- Modal: Agregar Grupo -->
<div class="modal fade" id="modalAgregarGrupo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <form method="post" action="grupos.php">
        <div class="modal-header bg-primary text-white py-2">
          <h6 class="modal-title"><i class="fas fa-plus mr-2"></i>Nuevo Grupo</h6>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="small font-weight-bold">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="group_name" class="form-control form-control-sm" required
                   maxlength="50" placeholder="Ej: Operadores">
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Nivel <span class="text-danger">*</span></label>
            <input type="number" name="group_level" class="form-control form-control-sm" required
                   min="1" max="100" placeholder="Ej: 4">
            <small class="text-muted">Nivel único (1 = Admin mayor, valores más altos = menos privilegios)</small>
          </div>
          <div class="form-group mb-0">
            <label class="small font-weight-bold">Estado</label>
            <select name="group_status" class="form-control form-control-sm">
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" name="agregar_grupo" class="btn btn-sm btn-primary">
            <i class="fas fa-save mr-1"></i>Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>';

include_once(__DIR__ . '/../../layouts/footer.php'); 
?>
