<?php
/**
 * Módulo Usuarios - Lista de Usuarios
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Lista de Usuarios';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

// Verificar permisos
require_permiso('usuarios', 'ver');

$all_users = find_all_user();
$active_users_count = 0;
$inactive_users_count = 0;
$user_type_count = 0;
$mail_type_count = 0;
foreach ($all_users as $user_row) {
    if ((string)($user_row['status'] ?? '') === '1') $active_users_count++;
    else $inactive_users_count++;
    if (($user_row['tipo_registro'] ?? 'USUARIO') === 'CORREO') $mail_type_count++;
    else $user_type_count++;
}
$can_send_users_mail = tiene_permiso('usuarios', 'editar');
$mail_users = array_values(array_filter($all_users, static function (array $user): bool {
    return (string)($user['status'] ?? '') === '1' && filter_var(trim((string)($user['correo'] ?? '')), FILTER_VALIDATE_EMAIL);
}));

include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
.users-card { border: 0; box-shadow: 0 8px 28px rgba(31, 52, 73, .10); }
.users-card .card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    padding: 10px 14px;
    background: linear-gradient(135deg, #f8fbfd, #eef5f8);
    border-bottom: 1px solid #dbe6ec;
}
.users-card .card-title { color: #173b4d; font-weight: 800; margin-right: 8px; }
.users-card .users-header-controls { display: flex; align-items: center; gap: 8px; flex: 1; flex-wrap: wrap; }
.users-card .users-status-tabs { display: inline-flex; padding: 3px; background: #dfe9ee; border-radius: 8px; }
.users-card .users-status-tabs .btn { border: 0; border-radius: 6px; font-weight: 700; }
.users-card .users-include-inactive {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    height: 34px;
    padding: 0 12px;
    margin: 0;
    background: #fff;
    border: 1px solid #c6d6de;
    border-radius: 8px;
    color: #294d5f;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
}
.users-card .users-include-inactive:hover { border-color: #28a745; background: #f5fff7; }
.users-card .users-include-inactive input { width: 16px; height: 16px; margin: 0; cursor: pointer; }
.users-card .users-include-inactive .info-icon { color: #1769d2; }
.users-card .users-filter-dropdown .dropdown-toggle {
    height: 34px;
    border-color: #c6d6de;
    border-radius: 8px;
    color: #476476;
    background: #fff;
    font-size: 12px;
    font-weight: 700;
}
.users-card .users-filter-dropdown .dropdown-toggle::after { display: none; }
.users-card .users-filter-dropdown .dropdown-menu {
    min-width: 230px;
    padding: 8px;
    border: 1px solid #d7e1e7;
    border-radius: 9px;
    box-shadow: 0 10px 28px rgba(31, 52, 73, .18);
}
.users-card .users-filter-dropdown .filter-title { padding: 5px 8px 8px; color: #718794; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; }
.users-card .users-filter-dropdown .filter-option {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 0;
    padding: 9px 8px;
    border-radius: 6px;
    color: #263f4d;
    cursor: pointer;
}
.users-card .users-filter-dropdown .filter-option:hover { background: #f0f6fa; }
.users-card .users-filter-dropdown .filter-option input { width: 16px; height: 16px; margin: 0; }
.users-card .users-search { position: relative; flex: 1 1 260px; max-width: 430px; }
.users-card .users-search i { position: absolute; left: 11px; top: 9px; color: #78909c; }
.users-card .users-search input { height: 34px; padding-left: 34px; border-color: #c6d6de; border-radius: 8px; }
.users-card .card-tools { margin-left: auto; }
.users-card .card-body { padding: 8px 12px 12px; }
.users-card #tblUsuarios { width: 100% !important; font-size: 12px; }
.users-card #tblUsuarios th { padding: 7px 6px; white-space: nowrap; }
.users-card #tblUsuarios td { padding: 5px 6px; vertical-align: middle; }
.users-card .dataTables_info, .users-card .dataTables_paginate { font-size: 12px; padding-top: 8px; }
@media (max-width: 767.98px) {
    .users-card .users-header-controls, .users-card .users-search { max-width: none; width: 100%; }
    .users-card .card-tools { margin-left: 0; }
}
.users-mail-modal .modal-dialog { max-width: 1120px; }
.users-mail-modal .modal-content { border: 0; border-radius: 14px; overflow: hidden; box-shadow: 0 22px 60px rgba(20,52,70,.24); }
.users-mail-modal .modal-header { padding: 18px 22px; color: #fff; background: linear-gradient(135deg,#176ee8,#35a2f4); border: 0; }
.users-mail-layout { min-height: 520px; }
.users-mail-picker { background: #f6f9fc; border-right: 1px solid #dce6ee; }
.users-mail-search { position: relative; }
.users-mail-search i { position: absolute; left: 13px; top: 12px; color: #748b9a; }
.users-mail-search input { padding-left: 38px; height: 42px; border-radius: 9px; }
.users-mail-list { max-height: 390px; overflow-y: auto; margin-top: 12px; padding-right: 4px; }
.users-mail-person { display:flex; align-items:center; gap:11px; padding:10px; margin-bottom:6px; background:#fff; border:1px solid #e1e9ef; border-radius:9px; cursor:pointer; transition:.15s ease; }
.users-mail-person:hover, .users-mail-person.selected { border-color:#7bb5fa; background:#edf5ff; }
.users-mail-person input { width:17px; height:17px; }
.users-mail-avatar { width:38px; height:38px; display:flex; align-items:center; justify-content:center; flex:0 0 38px; border-radius:50%; background:#bdd0e8; color:#fff; font-weight:800; }
.users-mail-person strong, .users-mail-person small { display:block; }
.users-mail-person small { color:#718493; word-break:break-all; }
.users-mail-selected { min-height:44px; padding:6px; border:1px solid #cedbe5; border-radius:9px; background:#fff; display:flex; gap:5px; flex-wrap:wrap; align-items:center; }
.users-mail-chip { padding:5px 8px; border-radius:7px; background:#e9f1fb; color:#31556e; font-size:12px; font-weight:700; }
.users-mail-editor { min-height:210px; max-height:330px; overflow:auto; padding:14px; border:1px solid #cedbe5; border-radius:0 0 9px 9px; outline:0; background:#fff; line-height:1.55; }
.users-mail-editor:empty:before { content:attr(data-placeholder); color:#96a5af; }
.users-mail-toolbar { padding:7px; border:1px solid #cedbe5; border-bottom:0; border-radius:9px 9px 0 0; background:#f6f8fa; }
.users-mail-toolbar button { width:34px; height:31px; padding:0; }
.users-mail-counter { font-size:12px; font-weight:700; color:#176ee8; }
@media (max-width: 767.98px) { .users-mail-picker { border-right:0; border-bottom:1px solid #dce6ee; } .users-mail-layout { min-height:0; } }
</style>

        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-users mr-2"></i>Gestión de Usuarios</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item active">Usuarios</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <div class="card users-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-2"></i>Lista de Usuarios
                        </h3>
                        <div class="users-header-controls">
                            <div class="dropdown users-filter-dropdown">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" id="btnFiltrosUsuario" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-filter mr-1"></i>Filtros
                                </button>
                                <div class="dropdown-menu" aria-labelledby="btnFiltrosUsuario">
                                    <div class="filter-title">Tipo de registro</div>
                                    <label class="filter-option">
                                        <input type="checkbox" class="user-type-check" value="USUARIO" checked>
                                        <span>Usuario</span>
                                        <span class="badge badge-primary ml-auto"><?php echo $user_type_count; ?></span>
                                    </label>
                                    <label class="filter-option">
                                        <input type="checkbox" class="user-type-check" value="CORREO">
                                        <span>Correo</span>
                                        <span class="badge badge-warning ml-auto"><?php echo $mail_type_count; ?></span>
                                    </label>
                                </div>
                            </div>
                            <label class="users-include-inactive" for="incluirUsuariosInactivos">
                                <input type="checkbox" id="incluirUsuariosInactivos">
                                <span>+ inactivos</span>
                                <i class="fas fa-info-circle info-icon" title="Agrega <?php echo $inactive_users_count; ?> usuarios inactivos al listado"></i>
                            </label>
                            <div class="users-search">
                                <i class="fas fa-search"></i>
                                <input type="search" id="buscarUsuario" class="form-control form-control-sm" placeholder="Buscar nombre, usuario, correo, area o rol">
                            </div>
                        </div>
                        <div class="card-tools">
                            <?php if ($can_send_users_mail): ?>
                            <button type="button" class="btn btn-success btn-sm mr-1" id="btnAbrirCorreoUsuarios">
                                <i class="fas fa-envelope mr-1"></i> Enviar correos
                            </button>
                            <?php endif; ?>
                            <?php if (tiene_permiso('usuarios', 'crear')): ?>
                            <a href="agregar.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Agregar Usuario
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="tblUsuarios" class="table table-bordered table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Rol</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Personal TI</th>
                                    <th>Último Login</th>
                                    <th>Correo</th>
                                    <th>Área</th>
                                    <th class="text-center" style="width: 100px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($all_users as $a_user): ?>
                                <tr data-status="<?php echo ((string)$a_user['status'] === '1' ? '1' : '0'); ?>" data-type="<?php echo (($a_user['tipo_registro'] ?? 'USUARIO') === 'CORREO' ? 'CORREO' : 'USUARIO'); ?>">
                                    <td class="text-center"><?php echo (int)$a_user['id']; ?></td>
                                    <td><?php echo remove_junk(ucwords($a_user['name'])); ?></td>
                                    <td><?php echo remove_junk($a_user['username']); ?></td>
                                    <td class="text-center">
                                        <?php if (($a_user['tipo_registro'] ?? 'USUARIO') === 'CORREO'): ?>
                                            <span class="badge badge-warning"><i class="fas fa-envelope mr-1"></i>Correo</span>
                                        <?php else: ?>
                                            <span class="badge badge-primary"><i class="fas fa-user mr-1"></i>Usuario</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?php echo remove_junk(ucwords($a_user['group_name'])); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if($a_user['status'] === '1'): ?>
                                            <span class="badge badge-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input user-ti-check" id="userTi<?php echo (int)$a_user['id']; ?>" data-user-id="<?php echo (int)$a_user['id']; ?>" <?php echo !empty($a_user['es_personal_ti']) ? 'checked' : ''; ?> <?php echo !tiene_permiso('usuarios','editar') || ($a_user['tipo_registro'] ?? 'USUARIO') !== 'USUARIO' ? 'disabled' : ''; ?>><label class="custom-control-label" for="userTi<?php echo (int)$a_user['id']; ?>" title="Habilitar como responsable de mantenimiento TI"></label></div></td>
                                    <td><?php echo read_date($a_user['last_login']); ?></td>
                                    <td><?php echo remove_junk($a_user['correo']); ?></td>
                                    <td><?php echo remove_junk($a_user['area']); ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <?php if (($a_user['tipo_registro'] ?? 'USUARIO') === 'USUARIO' && tiene_permiso('accesos_usuario', 'ver')): ?>
                                            <a href="../accesos_usuario/index.php?user_id=<?php echo (int)$a_user['id']; ?>" class="btn btn-info" title="Ver accesos F08">
                                                <i class="fas fa-user-shield"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (tiene_permiso('usuarios', 'editar')): ?>
                                            <a href="editar.php?id=<?php echo (int)$a_user['id']; ?>" class="btn btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (tiene_permiso('usuarios', 'eliminar')): ?>
                                            <button type="button" class="btn btn-danger" title="Eliminar" onclick="eliminarUsuario(<?php echo (int)$a_user['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>

<?php if ($can_send_users_mail): ?>
<div class="modal fade users-mail-modal" id="modalCorreoUsuarios" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document"><div class="modal-content">
        <div class="modal-header"><div><h5 class="modal-title font-weight-bold"><i class="fas fa-envelope-open-text mr-2"></i>Enviar correos a colaboradores</h5><small class="text-white-50">Seleccione los destinatarios y redacte una comunicación interna.</small></div><button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button></div>
        <form id="formCorreoUsuarios" enctype="multipart/form-data">
            <?php echo csrf_input(); ?>
            <div class="modal-body p-0"><div class="row no-gutters users-mail-layout">
                <div class="col-md-4 users-mail-picker p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="font-weight-bold mb-0">Seleccionar usuarios</h6><button type="button" class="btn btn-link btn-sm p-0" id="seleccionarCorreosVisibles">Seleccionar visibles</button></div>
                    <div class="users-mail-search"><i class="fas fa-search"></i><input type="search" class="form-control" id="buscarDestinatario" placeholder="Buscar nombre, correo o área..."></div>
                    <div class="users-mail-list" id="listaDestinatarios">
                        <?php foreach ($mail_users as $mail_user):
                            $name = trim((string)$mail_user['name']);
                            $initials = ''; foreach (array_slice(preg_split('/\s+/', $name) ?: [], 0, 2) as $word) $initials .= mb_substr($word, 0, 1);
                            $search = mb_strtolower($name.' '.($mail_user['correo'] ?? '').' '.($mail_user['area'] ?? ''), 'UTF-8'); ?>
                        <label class="users-mail-person" data-search="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="checkbox" class="mail-recipient-check" name="destinatarios[]" value="<?php echo (int)$mail_user['id']; ?>" data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="users-mail-avatar"><?php echo htmlspecialchars(mb_strtoupper($initials), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span><strong><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars((string)$mail_user['correo'], ENT_QUOTES, 'UTF-8'); ?></small><small><?php echo htmlspecialchars((string)($mail_user['area'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-2 users-mail-counter"><span id="totalDestinatarios">0</span> usuario(s) seleccionado(s)</div>
                </div>
                <div class="col-md-8 p-4">
                    <h6 class="font-weight-bold mb-3">Redactar correo</h6>
                    <div class="form-group"><label>Para:</label><div class="users-mail-selected" id="destinatariosSeleccionados"><span class="text-muted small px-2">Seleccione uno o más colaboradores</span></div></div>
                    <div class="form-group"><label for="asuntoCorreoUsuarios">Asunto *</label><input type="text" class="form-control" id="asuntoCorreoUsuarios" name="asunto" maxlength="180" required placeholder="Ej. Información importante"></div>
                    <div class="form-group"><label>Mensaje *</label><div class="users-mail-toolbar">
                        <button type="button" class="btn btn-light btn-sm mail-format" data-command="bold" title="Negrita"><b>B</b></button>
                        <button type="button" class="btn btn-light btn-sm mail-format" data-command="italic" title="Cursiva"><i>I</i></button>
                        <button type="button" class="btn btn-light btn-sm mail-format" data-command="underline" title="Subrayado"><u>U</u></button>
                        <button type="button" class="btn btn-light btn-sm mail-format" data-command="insertUnorderedList" title="Lista"><i class="fas fa-list-ul"></i></button>
                        <button type="button" class="btn btn-light btn-sm mail-format" data-command="insertOrderedList" title="Lista numerada"><i class="fas fa-list-ol"></i></button>
                        <button type="button" class="btn btn-light btn-sm" id="insertarEnlaceCorreo" title="Enlace"><i class="fas fa-link"></i></button>
                    </div><div id="editorCorreoUsuarios" class="users-mail-editor" contenteditable="true" data-placeholder="Escriba aquí el mensaje..."></div><input type="hidden" name="mensaje" id="mensajeCorreoUsuarios"></div>
                    <div class="form-group mb-0"><label for="adjuntoCorreoUsuarios"><i class="fas fa-paperclip mr-1"></i>Adjuntar archivo <small class="text-muted">(opcional, máximo 10 MB)</small></label><div class="custom-file"><input type="file" class="custom-file-input" id="adjuntoCorreoUsuarios" name="adjunto" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.png,.jpg,.jpeg,.zip"><label class="custom-file-label" for="adjuntoCorreoUsuarios">Seleccionar archivo</label></div></div>
                </div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary px-4" id="btnEnviarCorreosUsuarios"><i class="fas fa-paper-plane mr-1"></i>Enviar correos</button></div>
        </form>
    </div></div>
</div>
<?php endif; ?>

<input type="hidden" id="usersCsrfToken" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

<?php 
$inlineJs = <<<'JS'
var usersListMode = 'active';
var usersTypeFilters = { USUARIO: true, CORREO: false };
var usersTable = null;

function normalizeMailSearch(value) {
    return (value || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function refreshMailRecipients() {
    var selected = $('.mail-recipient-check:checked');
    $('#totalDestinatarios').text(selected.length);
    $('.users-mail-person').removeClass('selected');
    selected.each(function(){ $(this).closest('.users-mail-person').addClass('selected'); });
    var target = $('#destinatariosSeleccionados').empty();
    if (!selected.length) target.append('<span class="text-muted small px-2">Seleccione uno o más colaboradores</span>');
    selected.each(function(){ $('<span class="users-mail-chip"></span>').text($(this).data('name')).appendTo(target); });
}

$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (!settings.nTable || settings.nTable.id !== 'tblUsuarios') return true;
    var row = settings.aoData[dataIndex] && settings.aoData[dataIndex].nTr;
    if (!row) return false;
    var statusMatches = usersListMode === 'all' || String(row.getAttribute('data-status')) === '1';
    var typeMatches = !!usersTypeFilters[String(row.getAttribute('data-type'))];
    return statusMatches && typeMatches;
});

$(document).ready(function() {
    usersTable = $('#tblUsuarios').DataTable({
        language: DATATABLES_SPANISH,
        responsive: true,
        autoWidth: false,
        dom: 'rtip',
        pageLength: 25,
        order: [[1, 'asc']]
    });

    $('#buscarUsuario').on('input', function() {
        usersTable.search(this.value).draw();
    });

    $('#incluirUsuariosInactivos').on('change', function() {
        usersListMode = this.checked ? 'all' : 'active';
        usersTable.page('first').draw();
    });

    $('.users-filter-dropdown .dropdown-menu').on('click', function(event) {
        event.stopPropagation();
    });

    $('.user-type-check').on('change', function() {
        var checked = $('.user-type-check:checked');
        if (!checked.length) {
            this.checked = true;
            return;
        }
        usersTypeFilters.USUARIO = $('.user-type-check[value="USUARIO"]').prop('checked');
        usersTypeFilters.CORREO = $('.user-type-check[value="CORREO"]').prop('checked');
        $('#btnFiltrosUsuario').toggleClass('btn-primary text-white', usersTypeFilters.CORREO)
            .toggleClass('btn-outline-secondary', !usersTypeFilters.CORREO);
        usersTable.page('first').draw();
    });

    $('#btnAbrirCorreoUsuarios').on('click', function(){ $('#modalCorreoUsuarios').modal('show'); });
    $(document).on('change', '.mail-recipient-check', refreshMailRecipients);
    $('#buscarDestinatario').on('input', function(){
        var query = normalizeMailSearch(this.value);
        $('.users-mail-person').each(function(){ $(this).toggle(normalizeMailSearch($(this).data('search')).indexOf(query) !== -1); });
    });
    $('#seleccionarCorreosVisibles').on('click', function(){
        var visible = $('.users-mail-person:visible .mail-recipient-check');
        var shouldSelect = visible.filter(':not(:checked)').length > 0;
        visible.prop('checked', shouldSelect); refreshMailRecipients();
        $(this).text(shouldSelect ? 'Quitar visibles' : 'Seleccionar visibles');
    });
    $('.mail-format').on('click', function(){ document.execCommand($(this).data('command'), false, null); $('#editorCorreoUsuarios').focus(); });
    $('#insertarEnlaceCorreo').on('click', function(){ var url=window.prompt('Ingrese la dirección del enlace:','https://'); if(url) document.execCommand('createLink',false,url); });
    $('#adjuntoCorreoUsuarios').on('change', function(){ var name=this.files&&this.files[0]?this.files[0].name:'Seleccionar archivo'; $(this).next('.custom-file-label').text(name); });
    $('#formCorreoUsuarios').on('submit', function(event){
        event.preventDefault();
        var selected = $('.mail-recipient-check:checked').length;
        var message = $('#editorCorreoUsuarios').html().trim();
        if (!selected) { Swal.fire('Faltan destinatarios','Seleccione al menos un usuario.','warning'); return; }
        if (!$('#asuntoCorreoUsuarios').val().trim() || !$('#editorCorreoUsuarios').text().trim()) { Swal.fire('Correo incompleto','Ingrese el asunto y el mensaje.','warning'); return; }
        $('#mensajeCorreoUsuarios').val(message);
        var button=$('#btnEnviarCorreosUsuarios'), data=new FormData(this);
        button.prop('disabled',true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Enviando...');
        fetch('enviar_correos.php',{method:'POST',body:data,credentials:'same-origin'})
            .then(function(response){return response.json().then(function(json){if(!response.ok||!json.success)throw new Error(json.error||json.message||'No se pudo enviar.');return json;});})
            .then(function(json){
                var detail=json.failed?'No enviados: '+(json.failed_names||[]).join(', '):'Todos los destinatarios fueron procesados.';
                Swal.fire({icon:json.failed?'warning':'success',title:'Envío terminado',text:json.message+' '+detail});
                if(!json.failed){ $('#modalCorreoUsuarios').modal('hide'); document.getElementById('formCorreoUsuarios').reset(); $('#editorCorreoUsuarios').empty(); $('.mail-recipient-check').prop('checked',false); $('.custom-file-label').text('Seleccionar archivo'); refreshMailRecipients(); }
            })
            .catch(function(error){Swal.fire('No se pudo enviar',error.message,'error');})
            .finally(function(){button.prop('disabled',false).html('<i class="fas fa-paper-plane mr-1"></i>Enviar correos');});
    });

    $(document).on('change', '.user-ti-check', function() {
        var checkbox = this;
        checkbox.disabled = true;
        fetch('actualizar_personal_ti.php', {
            method:'POST', credentials:'same-origin',
            headers:{'Content-Type':'application/json','X-CSRF-Token':document.getElementById('usersCsrfToken').value},
            body:JSON.stringify({user_id:parseInt(checkbox.dataset.userId,10),es_personal_ti:checkbox.checked?1:0})
        }).then(function(response){return response.json().then(function(json){if(!response.ok||!json.success)throw new Error(json.error||'No se pudo actualizar.');return json;});})
        .then(function(json){checkbox.disabled=false;Swal.fire({icon:'success',title:json.es_personal_ti?'Personal TI habilitado':'Personal TI deshabilitado',timer:1200,showConfirmButton:false});})
        .catch(function(error){checkbox.checked=!checkbox.checked;checkbox.disabled=false;Swal.fire('No se pudo guardar',error.message,'error');});
    });
});

function eliminarUsuario(id) {
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'eliminar.php?id=' + id;
        }
    });
}
JS;

include_once(__DIR__ . '/../../layouts/footer.php'); 
?>
