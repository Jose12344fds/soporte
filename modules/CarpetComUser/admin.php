<?php
/**
 * Módulo CarpetComUser — Panel de administración
 * Sistema de Soporte TI - TAMA
 * Acceso: usuario con permiso 'eliminar' en CarpetComUser (admin)
 */
$page_title = 'Administrar Carpeta Compartida';
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';

require_permiso('CarpetComUser', 'eliminar');

$user = current_user();

// ── Stats desde PHP para las tarjetas de resumen ──
$stats = ['archivos' => 0, 'usuarios' => 0, 'storage' => 0, 'compartidos' => 0];

$chkTbl = $db->query("SHOW TABLES LIKE 'carpeta_archivos'");
if ($db->num_rows($chkTbl) > 0) {
    $r = $db->fetch_assoc($db->query("SELECT COUNT(*) AS c, COUNT(DISTINCT user_id) AS u, COALESCE(SUM(tamano),0) AS s FROM carpeta_archivos"));
    $stats['archivos']  = (int)$r['c'];
    $stats['usuarios']  = (int)$r['u'];
    $stats['storage']   = (int)$r['s'];

    $r2 = $db->fetch_assoc($db->query("SELECT COUNT(*) AS c FROM carpeta_compartidos"));
    $stats['compartidos'] = (int)$r2['c'];
}

function fmtSize($b) {
    if ($b < 1024)     return $b . ' B';
    if ($b < 1048576)  return round($b/1024, 1) . ' KB';
    if ($b < 1073741824) return round($b/1048576, 2) . ' MB';
    return round($b/1073741824, 2) . ' GB';
}

include_once __DIR__ . '/../../layouts/header.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    <i class="fas fa-cog mr-2" style="color:#667eea"></i>
                    Administrar Carpeta Compartida
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Carpeta Compartida</a></li>
                    <li class="breadcrumb-item active">Administración</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <!-- ── Tarjetas de resumen ──────────────────────────── -->
        <div class="row">
            <div class="col-6 col-lg-3">
                <div class="info-box">
                    <span class="info-box-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)">
                        <i class="fas fa-file text-white"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total archivos</span>
                        <span class="info-box-number"><?php echo number_format($stats['archivos']); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success">
                        <i class="fas fa-users text-white"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Usuarios con archivos</span>
                        <span class="info-box-number"><?php echo $stats['usuarios']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning">
                        <i class="fas fa-hdd text-white"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Almacenamiento usado</span>
                        <span class="info-box-number"><?php echo fmtSize($stats['storage']); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info">
                        <i class="fas fa-share-alt text-white"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Compartidos totales</span>
                        <span class="info-box-number"><?php echo number_format($stats['compartidos']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Filtros ─────────────────────────────────────── -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filtros</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Usuario</label>
                            <select id="filtroUsuario" class="form-control form-control-sm">
                                <option value="">— Todos —</option>
                                <?php
                                $usrs = $db->query("SELECT DISTINCT u.id, u.name FROM users u
                                    INNER JOIN carpeta_archivos a ON a.user_id = u.id ORDER BY u.name");
                                while ($u = $db->fetch_assoc($usrs)) {
                                    echo '<option value="' . (int)$u['id'] . '">' . htmlspecialchars($u['name'], ENT_QUOTES) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Área</label>
                            <select id="filtroArea" class="form-control form-control-sm">
                                <option value="">— Todas —</option>
                                <?php
                                $areas = $db->query("SELECT DISTINCT area FROM carpeta_archivos WHERE area IS NOT NULL AND area != '' ORDER BY area");
                                while ($a = $db->fetch_assoc($areas)) {
                                    echo '<option value="' . htmlspecialchars($a['area'], ENT_QUOTES) . '">' . htmlspecialchars($a['area'], ENT_QUOTES) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Tipo</label>
                            <select id="filtroTipo" class="form-control form-control-sm">
                                <option value="">— Todos —</option>
                                <option value="jpg,jpeg,png">Imágenes</option>
                                <option value="pdf">PDF</option>
                                <option value="mp4,mov,webm,avi,mkv">Videos</option>
                                <option value="doc,docx">Word</option>
                                <option value="xls,xlsx">Excel</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Barra de acciones masivas (visible al seleccionar) ── -->
        <div id="bulkToolbar" class="card bg-warning mb-2" style="display:none">
            <div class="card-body py-2 d-flex align-items-center">
                <span id="bulkCount" class="font-weight-bold mr-3">0 seleccionados</span>
                <button class="btn btn-sm btn-danger mr-2" onclick="eliminarSeleccionados()">
                    <i class="fas fa-trash mr-1"></i>Eliminar seleccionados
                </button>
                <button class="btn btn-sm btn-secondary" onclick="deseleccionarTodo()">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
            </div>
        </div>

        <!-- ── Tabla de todos los archivos ─────────────────── -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Todos los archivos</h3>
                <div class="card-tools">
                    <button class="btn btn-sm btn-outline-secondary" onclick="recargarTabla()">
                        <i class="fas fa-sync-alt mr-1"></i>Actualizar
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table id="tablaAdmin" class="table table-striped table-hover table-sm" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:36px"><input type="checkbox" id="chkAll" title="Seleccionar todo"></th>
                            <th>Archivo</th>
                            <th>Usuario</th>
                            <th>Área</th>
                            <th>Tipo</th>
                            <th>Tamaño</th>
                            <th>Fecha</th>
                            <th>Compartido con</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAdmin"></tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<?php
$BASE_URL_API = BASE_URL . '/api/CarpetComUser';
ob_start();
?>
var API      = '<?php echo $BASE_URL_API; ?>';
var tablaData = [];
var adminRegistry = {}; // id -> archivo obj para evitar JSON.stringify en onclick

function fileIcon(ext) {
    ext = (ext||'').toLowerCase();
    if (['jpg','jpeg','png'].indexOf(ext) >= 0) return {icon:'fas fa-file-image', color:'#764ba2'};
    if (['mp4','mov','webm','avi','mkv'].indexOf(ext) >= 0) return {icon:'fas fa-file-video', color:'#e67e22'};
    if (ext === 'pdf')                           return {icon:'fas fa-file-pdf',   color:'#dc3545'};
    if (['doc','docx'].indexOf(ext) >= 0)        return {icon:'fas fa-file-word',  color:'#4285f4'};
    if (['xls','xlsx'].indexOf(ext) >= 0)        return {icon:'fas fa-file-excel', color:'#0f9d58'};
    return {icon:'fas fa-file', color:'#6c757d'};
}
function formatSize(b) {
    b = parseInt(b)||0;
    if (b < 1024)    return b + ' B';
    if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
    return (b/1048576).toFixed(2) + ' MB';
}
function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Cargar todos los archivos vía API (admin ve todos)
function cargarDatos() {
    $.get(API + '/get_admin_archivos.php', function(res) {
        if (res && res.archivos) {
            tablaData = res.archivos;
            // Poblar registro para uso en onclick
            tablaData.forEach(function(a) { adminRegistry[a.id] = a; });
            aplicarFiltros();
        }
    });
}

function aplicarFiltros() {
    var usuario = $('#filtroUsuario').val();
    var area    = $('#filtroArea').val();
    var tipo    = $('#filtroTipo').val();
    var tipos   = tipo ? tipo.split(',') : [];

    var filtrado = tablaData.filter(function(a) {
        if (usuario && String(a.user_id) !== usuario)         return false;
        if (area    && a.area !== area)                        return false;
        if (tipos.length && tipos.indexOf(a.extension) < 0)   return false;
        return true;
    });

    renderTabla(filtrado);
}

function renderTabla(data) {
    // 1. Destruir DataTables PRIMERO para que no interfiera al limpiar el tbody
    if ($.fn.DataTable.isDataTable('#tablaAdmin')) {
        $('#tablaAdmin').DataTable().destroy();
    }
    // 2. Limpiar filas
    var tbody = document.getElementById('tbodyAdmin');
    tbody.innerHTML = '';

    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No hay archivos que coincidan con los filtros.</td></tr>';
    }

    data.forEach(function(a) {
        var fi = fileIcon(a.extension);
        var compartidos = (a.compartidos || []).map(function(c) {
            return '<span class="badge badge-light border mr-1">' + escHtml(c) + '</span>';
        }).join('');
        var partes = (a.created_at || '').substring(0,10).split('-');
        var fecha  = partes.length === 3 ? partes[2]+'/'+partes[1]+'/'+partes[0] : '';

        var esImg = ['jpg','jpeg','png'].indexOf((a.extension||'').toLowerCase()) >= 0;
        var esPdf = (a.extension||'').toLowerCase() === 'pdf';

        var tr = '<tr>';
        tr += '<td class="text-center"><input type="checkbox" class="chkFila" value="' + a.id + '"></td>';
        tr += '<td><i class="' + fi.icon + ' mr-1" style="color:' + fi.color + '"></i>' + escHtml(a.nombre_original) + '</td>';
        tr += '<td>' + escHtml(a.nombre_usuario) + '</td>';
        tr += '<td>' + escHtml(a.area) + '</td>';
        tr += '<td><span class="badge badge-secondary">' + escHtml((a.extension||'').toUpperCase()) + '</span></td>';
        tr += '<td>' + formatSize(a.tamano) + '</td>';
        tr += '<td>' + fecha + '</td>';
        tr += '<td>' + (compartidos || '<span class="text-muted small">—</span>') + '</td>';
        tr += '<td style="white-space:nowrap">';
        tr += '<a href="' + API + '/descargar.php?id=' + a.id + '" class="btn btn-xs btn-outline-secondary mr-1" title="Descargar"><i class="fas fa-download"></i></a>';
        if (esImg) {
            tr += '<button class="btn btn-xs btn-outline-info mr-1" title="Ver imagen" onclick="adminPreviewImg(' + a.id + ')"><i class="fas fa-eye"></i></button>';
        }
        if (esPdf) {
            tr += '<button class="btn btn-xs btn-outline-danger mr-1" title="Ver PDF" onclick="adminPreviewPdf(' + a.id + ')"><i class="fas fa-eye"></i></button>';
        }
        tr += '<button class="btn btn-xs btn-outline-danger" title="Eliminar" onclick="eliminar(' + a.id + ')"><i class="fas fa-trash"></i></button>';
        tr += '</td></tr>';
        tbody.insertAdjacentHTML('beforeend', tr);
    });

    // 3. Inicializar DataTables
    var dt = $('#tablaAdmin').DataTable({
        language: DATATABLES_SPANISH,
        pageLength: 25,
        order: [[6, 'desc']], // col 6 = Fecha (col 0 es checkbox)
        responsive: true,
        columnDefs: [{ orderable: false, targets: 0 }], // col checkbox no ordena
        dom: 'Bfrtip',
        buttons: ['excel','print']
    });

    // Recalcular selección al cambiar página
    dt.on('draw', function() {
        $('#chkAll').prop('checked', false);
        actualizarToolbar();
    });
}

function adminPreviewImg(id) {
    var a = adminRegistry[id];
    if (!a) return;
    Swal.fire({
        title: a.nombre_original,
        imageUrl: API + '/descargar.php?id=' + id + '&preview=1',
        imageAlt: a.nombre_original,
        imageWidth: '100%',
        width: '90%',
        showConfirmButton: false,
        showCloseButton: true
    });
}

function adminPreviewPdf(id) {
    var a = adminRegistry[id];
    if (!a) return;
    window.open(API + '/descargar.php?id=' + id + '&preview=1', '_blank');
}

function eliminar(id) {
    var nombre = adminRegistry[id] ? adminRegistry[id].nombre_original : 'este archivo';
    App.confirm('¿Eliminar archivo?', '"' + nombre + '" será eliminado permanentemente.', function() {
        $.ajax({
            url: API + '/eliminar.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({id: id}),
            success: function(res) {
                if (res.ok) {
                    App.toast('Archivo eliminado.', 'success');
                    cargarDatos();
                } else {
                    App.toast(res.error || 'Error al eliminar.', 'error');
                }
            }
        });
    });
}

function recargarTabla() { cargarDatos(); }

// ── Selección masiva ──────────────────────────────────────────
function actualizarToolbar() {
    var n = $('.chkFila:checked').length;
    if (n > 0) {
        $('#bulkCount').text(n + ' seleccionado' + (n > 1 ? 's' : ''));
        $('#bulkToolbar').show();
    } else {
        $('#bulkToolbar').hide();
    }
}

function deseleccionarTodo() {
    $('.chkFila').prop('checked', false);
    $('#chkAll').prop('checked', false);
    actualizarToolbar();
}

function eliminarSeleccionados() {
    var ids = $.map($('.chkFila:checked'), function(el) { return parseInt(el.value); });
    if (!ids.length) return;
    var nombres = ids.map(function(id) {
        return adminRegistry[id] ? adminRegistry[id].nombre_original : '#' + id;
    });
    App.confirm(
        '¿Eliminar ' + ids.length + ' archivo' + (ids.length > 1 ? 's' : '') + '?',
        'Se eliminarán permanentemente: ' + nombres.slice(0, 5).join(', ') + (nombres.length > 5 ? ' (+' + (nombres.length - 5) + ' más)' : ''),
        function() {
            $.ajax({
                url: API + '/eliminar.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ ids: ids }),
                success: function(res) {
                    if (res.ok) {
                        App.toast(res.deleted + ' archivo(s) eliminado(s).', 'success');
                        cargarDatos();
                    } else {
                        App.toast(res.error || 'Error al eliminar.', 'error');
                    }
                }
            });
        }
    );
}

$('#filtroUsuario, #filtroArea, #filtroTipo').on('change', aplicarFiltros);

// Delegated: chkAll y chkFila (se re-renderizan en cada cargarDatos)
$(document).on('change', '#chkAll', function() {
    var checked = $(this).is(':checked');
    // Solo marcar las filas visibles en la página actual
    $('#tablaAdmin tbody .chkFila:visible').prop('checked', checked);
    actualizarToolbar();
});
$(document).on('change', '.chkFila', function() {
    var total   = $('#tablaAdmin tbody .chkFila:visible').length;
    var checked = $('#tablaAdmin tbody .chkFila:visible:checked').length;
    $('#chkAll').prop('indeterminate', checked > 0 && checked < total);
    $('#chkAll').prop('checked', checked === total && total > 0);
    actualizarToolbar();
});

$(document).ready(function() {
    cargarDatos();
});
<?php
$inlineJs = ob_get_clean();
include_once __DIR__ . '/../../layouts/footer.php';
?>
