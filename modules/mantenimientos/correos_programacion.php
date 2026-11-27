<?php
/**
 * Configuracion de destinatarios para correo semanal de programacion.
 */
$page_title = 'Correos Programacion';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_permiso('mantenimientos', 'ver');
include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
.mail-card .card-header { background:linear-gradient(135deg,#14532d,#16a34a); color:#fff; }
.mail-stat { border:1px solid #dfe7e3; border-radius:8px; padding:12px; background:#fff; min-height:76px; }
.mail-stat .num { font-size:24px; font-weight:800; line-height:1; }
.mail-stat .lbl { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#6c757d; }
.badge-mail-on { background:#d4edda; color:#155724; }
.badge-mail-off { background:#f8d7da; color:#721c24; }
.mail-sub { font-size:12px; color:#6c757d; line-height:1.2; }
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-envelope mr-2 text-success"></i>Correos de Programacion</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="programacion.php">Programacion</a></li>
                    <li class="breadcrumb-item active">Correos</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card mail-card shadow-sm">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0 font-weight-bold">
                    <i class="fas fa-users mr-2"></i>Destinatarios del listado semanal
                </h3>
                <div class="ml-auto">
                    <a href="programacion.php" class="btn btn-sm btn-light mr-1">
                        <i class="fas fa-calendar-check"></i> Programacion
                    </a>
                    <?php if (tiene_permiso('mantenimientos', 'editar')): ?>
                    <button type="button" class="btn btn-sm btn-warning font-weight-bold" id="btnNuevoCorreo">
                        <i class="fas fa-plus-circle"></i> Agregar destino
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6 col-md-3 mb-2">
                        <div class="mail-stat">
                            <div class="lbl">Total</div>
                            <div class="num text-primary" id="statTotalCorreo">--</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="mail-stat">
                            <div class="lbl">Activos</div>
                            <div class="num text-success" id="statActivosCorreo">--</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="mail-stat">
                            <div class="lbl">Con Excel</div>
                            <div class="num text-info" id="statExcelCorreo">--</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="mail-stat">
                            <div class="lbl">Inactivos</div>
                            <div class="num text-danger" id="statInactivosCorreo">--</div>
                        </div>
                    </div>
                </div>

                <div id="spinnerCorreos" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-success"></i>
                    <p class="mt-2 text-muted">Cargando destinatarios...</p>
                </div>

                <div id="setupAlert" class="alert alert-warning" style="display:none;"></div>

                <div id="wrapCorreos" style="display:none;">
                    <table id="tblCorreosProg" class="table table-bordered table-striped table-hover table-sm w-100">
                        <thead>
                            <tr>
                                <th>Destino</th>
                                <th>Correo</th>
                                <th class="text-center">Dia / Hora</th>
                                <th class="text-center">Formato</th>
                                <th class="text-center">Estado</th>
                                <th>Ultimo envio</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="modalCorreoProg" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold mb-0" id="modalCorreoTitle">
                    <i class="fas fa-plus-circle mr-2"></i>Agregar destino
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formCorreoProg" autocomplete="off">
                    <input type="hidden" id="mailId" value="">
                    <div class="form-group">
                        <label class="font-weight-bold">Usuario del sistema</label>
                        <select id="mailUserId" class="form-control">
                            <option value="">-- Seleccionar usuario o dejar manual --</option>
                        </select>
                        <small class="text-muted">Al seleccionar usuario se completa nombre y correo.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Nombre destino</label>
                                <input type="text" class="form-control" id="mailNombre" maxlength="120" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Correo destino</label>
                                <input type="email" class="form-control" id="mailCorreo" maxlength="160" required placeholder="usuario@tama.pe">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Dia de envio</label>
                                <select id="mailDia" class="form-control">
                                    <option value="1">Lunes</option>
                                    <option value="2">Martes</option>
                                    <option value="3">Miercoles</option>
                                    <option value="4">Jueves</option>
                                    <option value="5">Viernes</option>
                                    <option value="6">Sabado</option>
                                    <option value="7">Domingo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Hora</label>
                                <input type="time" class="form-control" id="mailHora" value="08:00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>Opciones</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mailActivo" checked>
                                <label class="form-check-label" for="mailActivo">Activo</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mailHtml" checked>
                                <label class="form-check-label" for="mailHtml">Recibe resumen HTML</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mailExcel" checked>
                                <label class="form-check-label" for="mailExcel">Recibe Excel</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Observacion</label>
                        <textarea class="form-control" id="mailObservacion" rows="2" maxlength="255"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <?php if (tiene_permiso('mantenimientos', 'editar')): ?>
                <button type="button" class="btn btn-success" id="btnGuardarCorreo">
                    <i class="fas fa-save mr-1"></i>Guardar
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$puedeEditar = tiene_permiso('mantenimientos', 'editar') ? 'true' : 'false';
$baseUrl = BASE_URL;

$inlineJs = <<<JS
var puedeEditarCorreo = {$puedeEditar};
var correosRows = [];
var usuariosCorreo = [];
var dtCorreos = null;
var dias = {1:'Lunes',2:'Martes',3:'Miercoles',4:'Jueves',5:'Viernes',6:'Sabado',7:'Domingo'};

function esc(s) {
    return s == null ? '' : String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function boolInt(v) { return Number(v || 0) === 1; }
function setTxt(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }
function badgeEstadoMail(v) {
    return boolInt(v)
        ? '<span class="badge badge-mail-on badge-pill">Activo</span>'
        : '<span class="badge badge-mail-off badge-pill">Inactivo</span>';
}
function statsCorreo(rows) {
    var total = rows.length, activos = 0, excel = 0;
    rows.forEach(function(r) {
        if (boolInt(r.activo)) activos++;
        if (boolInt(r.recibe_excel)) excel++;
    });
    setTxt('statTotalCorreo', total);
    setTxt('statActivosCorreo', activos);
    setTxt('statExcelCorreo', excel);
    setTxt('statInactivosCorreo', total - activos);
}
function fillUsuarios() {
    var sel = document.getElementById('mailUserId');
    sel.innerHTML = '<option value="">-- Seleccionar usuario o dejar manual --</option>';
    usuariosCorreo.forEach(function(u) {
        var opt = document.createElement('option');
        opt.value = u.id;
        opt.textContent = (u.name || '') + ' - ' + (u.correo || '');
        opt.dataset.nombre = u.name || '';
        opt.dataset.correo = u.correo || '';
        sel.appendChild(opt);
    });
}
function cargarCorreos() {
    document.getElementById('spinnerCorreos').style.display = '';
    document.getElementById('wrapCorreos').style.display = 'none';
    document.getElementById('setupAlert').style.display = 'none';

    fetch('$baseUrl/api/mantenimientos/get_correo_prog.php')
        .then(function(r){ return r.json().then(function(j){ j._ok = r.ok; return j; }); })
        .then(function(json) {
            document.getElementById('spinnerCorreos').style.display = 'none';
            if (!json._ok || !json.success) {
                var box = document.getElementById('setupAlert');
                box.innerHTML = esc(json.error || 'No se pudo cargar.') +
                    (json.setup_url ? ' <a href="' + esc(json.setup_url) + '">Crear tabla</a>' : '');
                box.style.display = '';
                statsCorreo([]);
                return;
            }

            correosRows = json.data || [];
            usuariosCorreo = json.usuarios || [];
            fillUsuarios();
            statsCorreo(correosRows);
            document.getElementById('wrapCorreos').style.display = '';

            if (dtCorreos) {
                dtCorreos.destroy();
                $('#tblCorreosProg tbody').empty();
            }

            dtCorreos = $('#tblCorreosProg').DataTable({
                language: DATATABLES_SPANISH,
                responsive: true,
                data: correosRows,
                columns: [
                    { data:null, render:function(r){
                        return '<strong>' + esc(r.nombre_destino || '') + '</strong>'
                            + '<div class="mail-sub">' + esc(r.usuario_login || '') + '</div>';
                    }},
                    { data:'correo_destino', render:function(d){ return esc(d); } },
                    { data:null, className:'text-center', render:function(r){
                        return '<strong>' + esc(dias[r.dia_envio] || r.dia_envio || '') + '</strong>'
                            + '<div class="mail-sub">' + esc((r.hora_envio || '').substring(0,5)) + '</div>';
                    }},
                    { data:null, className:'text-center', render:function(r){
                        var parts = [];
                        if (boolInt(r.recibe_html)) parts.push('<span class="badge badge-primary">HTML</span>');
                        if (boolInt(r.recibe_excel)) parts.push('<span class="badge badge-success">Excel</span>');
                        return parts.join(' ') || '<span class="text-muted">--</span>';
                    }},
                    { data:'activo', className:'text-center', render:function(d){ return badgeEstadoMail(d); } },
                    { data:'ultimo_envio', render:function(d){ return esc(d || '--'); } },
                    { data:null, orderable:false, className:'text-center', render:function(r){
                        if (!puedeEditarCorreo) return '<span class="text-muted">--</span>';
                        return '<div class="btn-group btn-group-sm">'
                            + '<button class="btn btn-info btn-edit-mail" data-id="' + esc(r.id) + '" title="Editar"><i class="fas fa-edit"></i></button>'
                            + '<button class="btn btn-danger btn-del-mail" data-id="' + esc(r.id) + '" title="Eliminar"><i class="fas fa-trash"></i></button>'
                            + '</div>';
                    }}
                ],
                order:[[4,'desc'],[0,'asc']],
                dom:'Bfrtip',
                buttons:[
                    { extend:'excelHtml5', text:'<i class="fas fa-file-excel mr-1"></i>Excel', className:'btn btn-sm btn-success' },
                    { extend:'print', text:'<i class="fas fa-print mr-1"></i>Imprimir', className:'btn btn-sm btn-secondary' }
                ],
                pageLength:20
            });
        })
        .catch(function(err) {
            document.getElementById('spinnerCorreos').style.display = 'none';
            Swal.fire('Error', err.message || 'No se pudo cargar', 'error');
        });
}
function resetFormCorreo() {
    document.getElementById('formCorreoProg').reset();
    document.getElementById('mailId').value = '';
    document.getElementById('mailDia').value = '1';
    document.getElementById('mailHora').value = '08:00';
    document.getElementById('mailActivo').checked = true;
    document.getElementById('mailHtml').checked = true;
    document.getElementById('mailExcel').checked = true;
    document.getElementById('modalCorreoTitle').innerHTML = '<i class="fas fa-plus-circle mr-2"></i>Agregar destino';
}
function setFormCorreo(r) {
    resetFormCorreo();
    document.getElementById('mailId').value = r.id || '';
    document.getElementById('mailUserId').value = r.user_id || '';
    document.getElementById('mailNombre').value = r.nombre_destino || '';
    document.getElementById('mailCorreo').value = r.correo_destino || '';
    document.getElementById('mailDia').value = r.dia_envio || '1';
    document.getElementById('mailHora').value = (r.hora_envio || '08:00').substring(0,5);
    document.getElementById('mailActivo').checked = boolInt(r.activo);
    document.getElementById('mailHtml').checked = boolInt(r.recibe_html);
    document.getElementById('mailExcel').checked = boolInt(r.recibe_excel);
    document.getElementById('mailObservacion').value = r.observacion || '';
    document.getElementById('modalCorreoTitle').innerHTML = '<i class="fas fa-edit mr-2"></i>Editar destino';
}
function payloadCorreo(action) {
    return {
        action: action || 'save',
        id: document.getElementById('mailId').value,
        user_id: document.getElementById('mailUserId').value,
        nombre_destino: document.getElementById('mailNombre').value.trim(),
        correo_destino: document.getElementById('mailCorreo').value.trim(),
        dia_envio: document.getElementById('mailDia').value,
        hora_envio: document.getElementById('mailHora').value,
        activo: document.getElementById('mailActivo').checked ? 1 : 0,
        recibe_html: document.getElementById('mailHtml').checked ? 1 : 0,
        recibe_excel: document.getElementById('mailExcel').checked ? 1 : 0,
        observacion: document.getElementById('mailObservacion').value.trim()
    };
}

$(document).on('click', '#btnNuevoCorreo', function() {
    resetFormCorreo();
    $('#modalCorreoProg').modal('show');
});
$(document).on('change', '#mailUserId', function() {
    var opt = this.options[this.selectedIndex];
    if (!opt || !this.value) return;
    document.getElementById('mailNombre').value = opt.dataset.nombre || '';
    document.getElementById('mailCorreo').value = opt.dataset.correo || '';
});
$(document).on('click', '.btn-edit-mail', function() {
    var id = String($(this).data('id'));
    var r = correosRows.find(function(x){ return String(x.id) === id; });
    if (!r) return;
    setFormCorreo(r);
    $('#modalCorreoProg').modal('show');
});
$(document).on('click', '.btn-del-mail', function() {
    var id = String($(this).data('id'));
    Swal.fire({
        title:'Eliminar destino?',
        text:'Ya no recibira el listado semanal.',
        icon:'warning',
        showCancelButton:true,
        confirmButtonText:'Si, eliminar',
        cancelButtonText:'Cancelar'
    }).then(function(res) {
        if (!res.isConfirmed) return;
        fetch('$baseUrl/api/mantenimientos/guardar_correo_prog.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'delete', id:id})
        })
        .then(function(r){ return r.json(); })
        .then(function(j) {
            if (!j.success) throw new Error(j.error || 'No se pudo eliminar');
            App.toast('Destino eliminado', 'success');
            cargarCorreos();
        })
        .catch(function(err){ Swal.fire('Error', err.message, 'error'); });
    });
});
$(document).on('click', '#btnGuardarCorreo', function() {
    var payload = payloadCorreo('save');
    if (!payload.nombre_destino || !payload.correo_destino) {
        Swal.fire('Campos requeridos', 'Complete nombre y correo destino.', 'warning');
        return;
    }
    var btn = this;
    btn.disabled = true;
    fetch('$baseUrl/api/mantenimientos/guardar_correo_prog.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(payload)
    })
    .then(function(r){ return r.json(); })
    .then(function(j) {
        btn.disabled = false;
        if (!j.success) throw new Error(j.error || 'No se pudo guardar');
        $('#modalCorreoProg').modal('hide');
        App.toast('Destino guardado', 'success');
        cargarCorreos();
    })
    .catch(function(err) {
        btn.disabled = false;
        Swal.fire('Error', err.message, 'error');
    });
});

$(document).ready(function() {
    cargarCorreos();
});
JS;

include_once(__DIR__ . '/../../layouts/footer.php');
?>
