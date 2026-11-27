<?php
/**
 * Módulo Licencias — Licencias Microsoft (Office & Windows)
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Licencias de Software';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_permiso('licencias', 'ver');

// ── POST handlers ─────────────────────────────────────────────────────────────
$action = trim($_POST['action'] ?? '');

if ($action === 'agregar' && tiene_permiso('licencias', 'crear')) {
    $tipo   = $db->escape(trim($_POST['Tipo_Licencias'] ?? ''));
    $serial = $db->escape(trim($_POST['Serial'] ?? ''));
    $nombre = $db->escape(trim($_POST['Nombre'] ?? ''));
    $fecha  = $db->escape(trim($_POST['Fecha'] ?? date('Y-m-d')));
    $obs    = $db->escape(trim($_POST['Observaciones'] ?? ''));
    $util   = $db->escape(trim($_POST['Utilizado'] ?? 'LIBRE'));
    $equipo = $db->escape(trim($_POST['NomEquipo'] ?? ''));
    if ($tipo && $serial) {
        $db->query("INSERT INTO licencias (Tipo_Licencias,Serial,Nombre,Fecha,Observaciones,Utilizado,NomEquipo)
                    VALUES ('{$tipo}','{$serial}','{$nombre}','{$fecha}','{$obs}','{$util}','{$equipo}')");
        $session->msg('s', 'Licencia agregada correctamente.');
    } else { $session->msg('d', 'Tipo y Serial son obligatorios.'); }
    redirect('LicenciasOffice.php', false);
}

if ($action === 'editar' && tiene_permiso('licencias', 'editar')) {
    $id     = (int)($_POST['id'] ?? 0);
    $tipo   = $db->escape(trim($_POST['Tipo_Licencias'] ?? ''));
    $serial = $db->escape(trim($_POST['Serial'] ?? ''));
    $nombre = $db->escape(trim($_POST['Nombre'] ?? ''));
    $fecha  = $db->escape(trim($_POST['Fecha'] ?? date('Y-m-d')));
    $obs    = $db->escape(trim($_POST['Observaciones'] ?? ''));
    $util   = $db->escape(trim($_POST['Utilizado'] ?? 'LIBRE'));
    $equipo = $db->escape(trim($_POST['NomEquipo'] ?? ''));
    if ($id && $tipo && $serial) {
        $db->query("UPDATE licencias SET Tipo_Licencias='{$tipo}',Serial='{$serial}',Nombre='{$nombre}',
                    Fecha='{$fecha}',Observaciones='{$obs}',Utilizado='{$util}',NomEquipo='{$equipo}'
                    WHERE IdLicencias={$id}");
        $session->msg('s', 'Licencia actualizada correctamente.');
    } else { $session->msg('d', 'Datos incompletos.'); }
    redirect('LicenciasOffice.php', false);
}

if ($action === 'eliminar' && tiene_permiso('licencias', 'eliminar')) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $lic = $db->fetch_assoc($db->query("SELECT Serial FROM licencias WHERE IdLicencias={$id}"));
        if ($lic && !empty($lic['Serial'])) {
            $s = $db->escape($lic['Serial']);
            $db->query("UPDATE maquina SET office='' WHERE office='{$s}'");
            $db->query("UPDATE maquina SET windows='' WHERE windows='{$s}'");
        }
        $db->query("DELETE FROM licencias WHERE IdLicencias={$id}");
        $session->msg('s', 'Licencia eliminada.');
    }
    redirect('LicenciasOffice.php', false);
}

// ── Cargar datos ──────────────────────────────────────────────────────────────
$res = $db->query("SELECT * FROM licencias ORDER BY Tipo_Licencias, IdLicencias DESC");
$licencias = [];
while ($row = $db->fetch_assoc($res)) { $licencias[] = $row; }

$total  = count($licencias);
$libres = count(array_filter($licencias, fn($l) => strtoupper(trim($l['Utilizado'] ?? '')) !== 'USADO'));
$usadas = $total - $libres;
$cOff   = count(array_filter($licencias, fn($l) => stripos($l['Tipo_Licencias'] ?? '', 'office') !== false));
$cWin   = count(array_filter($licencias, fn($l) => stripos($l['Tipo_Licencias'] ?? '', 'windows') !== false));

include_once(__DIR__ . '/../../layouts/header.php');
?>

?>
<!-- ════════════════ HEADER ════════════════ -->
<section class="content-header">
  <div class="container-fluid">
    <div class="py-3 px-4 mb-1 rounded shadow-sm d-flex flex-wrap align-items-center text-white"
         style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)">
      <i class="fab fa-microsoft mr-3" style="font-size:2.5rem;opacity:.9"></i>
      <div class="flex-grow-1">
        <h2 class="mb-0 font-weight-bold">Licencias de Software</h2>
        <small style="opacity:.8">Microsoft Office &amp; Windows — Control de activaciones</small>
      </div>
      <ol class="breadcrumb bg-transparent mb-0 p-0">
        <li class="breadcrumb-item"><a href="../../home.php" class="text-white-50">Inicio</a></li>
        <li class="breadcrumb-item"><a href="index.php" class="text-white-50">Licencias</a></li>
        <li class="breadcrumb-item active text-white">Software</li>
      </ol>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <?php echo display_msg($msg); ?>

    <!-- Stats -->
    <div class="row mb-3">
      <div class="col-6 col-lg-3 mb-2">
        <div class="card border-0 shadow-sm text-white h-100" style="background:linear-gradient(135deg,#667eea,#764ba2)">
          <div class="card-body text-center py-3">
            <i class="fas fa-layer-group fa-2x mb-1" style="opacity:.75"></i>
            <div class="display-4 font-weight-bold lh-1"><?= $total ?></div>
            <small>Total Licencias</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-2">
        <div class="card border-0 shadow-sm text-white h-100" style="background:linear-gradient(135deg,#11998e,#38ef7d)">
          <div class="card-body text-center py-3">
            <i class="fas fa-check-circle fa-2x mb-1" style="opacity:.75"></i>
            <div class="display-4 font-weight-bold lh-1"><?= $libres ?></div>
            <small>Disponibles</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-2">
        <div class="card border-0 shadow-sm text-white h-100" style="background:linear-gradient(135deg,#f093fb,#f5576c)">
          <div class="card-body text-center py-3">
            <i class="fas fa-desktop fa-2x mb-1" style="opacity:.75"></i>
            <div class="display-4 font-weight-bold lh-1"><?= $usadas ?></div>
            <small>En Uso</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-2">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex justify-content-around align-items-center py-3">
            <div class="text-center">
              <i class="fas fa-file-word fa-2x text-primary mb-1"></i>
              <div class="h3 font-weight-bold text-primary mb-0"><?= $cOff ?></div>
              <small class="text-muted">Office</small>
            </div>
            <div class="border-right" style="height:50px"></div>
            <div class="text-center">
              <i class="fab fa-windows fa-2x text-info mb-1"></i>
              <div class="h3 font-weight-bold text-info mb-0"><?= $cWin ?></div>
              <small class="text-muted">Windows</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Card tabla -->
    <div class="card shadow-sm">
      <div class="card-header d-flex align-items-center py-2"
           style="background:linear-gradient(135deg,#667eea,#764ba2)">
        <h3 class="card-title text-white mb-0 font-weight-bold">
          <i class="fas fa-key mr-2"></i>Registro de Licencias
        </h3>
        <div class="ml-auto">
          <?php if (tiene_permiso('licencias', 'crear')): ?>
          <button class="btn btn-light btn-sm font-weight-bold" data-toggle="modal" data-target="#modalAgregar">
            <i class="fas fa-plus mr-1"></i>Nueva Licencia
          </button>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table id="tblLicencias" class="table table-hover mb-0">
          <thead>
            <tr style="background:#f4f6fb">
              <th class="border-top-0 text-center" style="width:45px">#</th>
              <th class="border-top-0">Tipo / Producto</th>
              <th class="border-top-0">Clave de Licencia</th>
              <th class="border-top-0 text-center">Estado</th>
              <th class="border-top-0">Equipo</th>
              <th class="border-top-0">Fecha</th>
              <th class="border-top-0">Observaciones</th>
              <th class="border-top-0 text-center" style="width:100px">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($licencias as $L):
            $util   = strtoupper(trim($L['Utilizado'] ?? ''));
            $libre  = ($util === 'LIBRE' || $util === '');
            $isOff  = stripos($L['Tipo_Licencias'] ?? '', 'office') !== false;
            $icono  = $isOff ? 'fas fa-file-word text-primary' : 'fab fa-windows text-info';
          ?>
          <tr>
            <td class="text-center"><code class="text-muted"><?= (int)$L['IdLicencias'] ?></code></td>
            <td>
              <i class="<?= $icono ?> mr-1"></i>
              <strong><?= htmlspecialchars($L['Tipo_Licencias'], ENT_QUOTES) ?></strong><br>
              <small class="text-muted"><?= htmlspecialchars($L['Nombre'] ?? '', ENT_QUOTES) ?></small>
            </td>
            <td>
              <div class="d-flex align-items-center">
                <span class="key-value mr-1"
                      style="font-family:monospace;letter-spacing:1px"
                      data-plain="<?= htmlspecialchars($L['Serial'], ENT_QUOTES) ?>">
                  <?= str_repeat('●', min(strlen($L['Serial']), 22)) ?>
                </span>
                <button type="button" class="btn btn-link btn-sm p-0 mr-1 btn-eye" title="Ver/ocultar"><i class="fas fa-eye text-muted"></i></button>
                <button type="button" class="btn btn-link btn-sm p-0 btn-copy"
                        data-copy="<?= htmlspecialchars($L['Serial'], ENT_QUOTES) ?>" title="Copiar">
                  <i class="far fa-copy text-muted"></i>
                </button>
              </div>
            </td>
            <td class="text-center">
              <?php if ($libre): ?>
              <span class="badge badge-pill px-3 py-1" style="background:#d4edda;color:#155724;font-size:.8rem">
                <i class="fas fa-circle mr-1" style="font-size:.45rem;vertical-align:middle"></i>LIBRE
              </span>
              <?php else: ?>
              <span class="badge badge-pill px-3 py-1" style="background:#fff3cd;color:#856404;font-size:.8rem">
                <i class="fas fa-circle mr-1" style="font-size:.45rem;vertical-align:middle"></i>EN USO
              </span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($L['NomEquipo'])): ?>
              <i class="fas fa-desktop text-secondary mr-1"></i>
              <small><?= htmlspecialchars($L['NomEquipo'], ENT_QUOTES) ?></small>
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td><small><?= $L['Fecha'] ? date('d/m/Y', strtotime($L['Fecha'])) : '—' ?></small></td>
            <td><small class="text-muted"><?= htmlspecialchars($L['Observaciones'] ?? '', ENT_QUOTES) ?></small></td>
            <td class="text-center">
              <div class="btn-group btn-group-sm">
                <?php if (tiene_permiso('licencias', 'editar')): ?>
                <button class="btn btn-warning btn-editar"
                        data-id="<?= (int)$L['IdLicencias'] ?>"
                        data-tipo="<?= htmlspecialchars($L['Tipo_Licencias'], ENT_QUOTES) ?>"
                        data-serial="<?= htmlspecialchars($L['Serial'], ENT_QUOTES) ?>"
                        data-nombre="<?= htmlspecialchars($L['Nombre'] ?? '', ENT_QUOTES) ?>"
                        data-fecha="<?= htmlspecialchars($L['Fecha'] ?? '', ENT_QUOTES) ?>"
                        data-obs="<?= htmlspecialchars($L['Observaciones'] ?? '', ENT_QUOTES) ?>"
                        data-util="<?= htmlspecialchars($L['Utilizado'] ?? '', ENT_QUOTES) ?>"
                        data-equipo="<?= htmlspecialchars($L['NomEquipo'] ?? '', ENT_QUOTES) ?>"
                        title="Editar"><i class="fas fa-edit"></i></button>
                <?php endif; ?>
                <?php if (tiene_permiso('licencias', 'eliminar')): ?>
                <button class="btn btn-danger btn-eliminar"
                        data-id="<?= (int)$L['IdLicencias'] ?>"
                        data-desc="<?= htmlspecialchars($L['Tipo_Licencias'] . ' — ' . substr($L['Serial'],0,20) . '...', ENT_QUOTES) ?>"
                        title="Eliminar"><i class="fas fa-trash"></i></button>
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
  </div>
</section>

<!-- ════ Modal Agregar ════ -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-lg overflow-hidden">
      <div class="modal-header py-3 text-white" style="background:linear-gradient(135deg,#667eea,#764ba2)">
        <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i>Nueva Licencia</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form method="post" action="LicenciasOffice.php">
        <input type="hidden" name="action" value="agregar">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Tipo de Licencia <span class="text-danger">*</span></label>
                <select name="Tipo_Licencias" class="form-control" required>
                  <option value="">— Seleccionar —</option>
                  <optgroup label="Microsoft Office">
                    <option>Microsoft Office 2016</option>
                    <option>Microsoft Office 2019</option>
                    <option>Microsoft Office 2021</option>
                    <option>Microsoft Office 365</option>
                    <option>Microsoft Project 2019</option>
                    <option>Microsoft Visio 2019</option>
                  </optgroup>
                  <optgroup label="Windows">
                    <option>Windows 10 Home</option>
                    <option>Windows 10 Pro</option>
                    <option>Windows 11 Home</option>
                    <option>Windows 11 Pro</option>
                  </optgroup>
                  <option value="Otro">Otro</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Nombre / Descripción</label>
                <input type="text" name="Nombre" class="form-control" placeholder="Ej: Office Professional Plus 2019">
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Clave de Licencia (Serial) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="text" name="Serial" id="addSerial" class="form-control"
                         placeholder="XXXXX-XXXXX-XXXXX-XXXXX-XXXXX" required
                         style="font-family:monospace;letter-spacing:2px">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-toggle-inp" data-target="addSerial">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Estado</label>
                <select name="Utilizado" class="form-control">
                  <option value="LIBRE">LIBRE</option>
                  <option value="USADO">USADO</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Equipo Asignado</label>
                <input type="text" name="NomEquipo" class="form-control" placeholder="Hostname del PC">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Fecha</label>
                <input type="date" name="Fecha" class="form-control" value="<?= date('Y-m-d') ?>">
              </div>
            </div>
            <div class="col-12">
              <div class="form-group mb-0">
                <label class="small font-weight-bold text-uppercase text-muted">Observaciones</label>
                <textarea name="Observaciones" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary font-weight-bold">
            <i class="fas fa-save mr-1"></i>Guardar Licencia
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ════ Modal Editar ════ -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-lg overflow-hidden">
      <div class="modal-header py-3 text-white" style="background:linear-gradient(135deg,#f093fb,#f5576c)">
        <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-2"></i>Editar Licencia</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form method="post" action="LicenciasOffice.php">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Tipo de Licencia <span class="text-danger">*</span></label>
                <select name="Tipo_Licencias" id="editTipo" class="form-control" required>
                  <option value="">— Seleccionar —</option>
                  <optgroup label="Microsoft Office">
                    <option>Microsoft Office 2016</option>
                    <option>Microsoft Office 2019</option>
                    <option>Microsoft Office 2021</option>
                    <option>Microsoft Office 365</option>
                    <option>Microsoft Project 2019</option>
                    <option>Microsoft Visio 2019</option>
                  </optgroup>
                  <optgroup label="Windows">
                    <option>Windows 10 Home</option>
                    <option>Windows 10 Pro</option>
                    <option>Windows 11 Home</option>
                    <option>Windows 11 Pro</option>
                  </optgroup>
                  <option value="Otro">Otro</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Nombre / Descripción</label>
                <input type="text" name="Nombre" id="editNombre" class="form-control">
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Clave de Licencia (Serial) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="text" name="Serial" id="editSerial" class="form-control" required
                         style="font-family:monospace;letter-spacing:2px">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-toggle-inp" data-target="editSerial">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Estado</label>
                <select name="Utilizado" id="editUtil" class="form-control">
                  <option value="LIBRE">LIBRE</option>
                  <option value="USADO">USADO</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Equipo Asignado</label>
                <input type="text" name="NomEquipo" id="editEquipo" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold text-uppercase text-muted">Fecha</label>
                <input type="date" name="Fecha" id="editFecha" class="form-control">
              </div>
            </div>
            <div class="col-12">
              <div class="form-group mb-0">
                <label class="small font-weight-bold text-uppercase text-muted">Observaciones</label>
                <textarea name="Observaciones" id="editObs" class="form-control" rows="2"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning font-weight-bold text-white">
            <i class="fas fa-save mr-1"></i>Actualizar Licencia
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Formulario oculto para eliminar -->
<form id="formEliminar" method="post" action="LicenciasOffice.php" style="display:none">
  <input type="hidden" name="action" value="eliminar">
  <input type="hidden" name="id" id="deleteId">
</form>

<?php
ob_start();
?>
$('#tblLicencias').DataTable({
    language: DATATABLES_SPANISH,
    pageLength: 20,
    order: [[0,'desc']],
    responsive: true,
    dom: 'Bfrtip',
    buttons: ['excel','print']
});

// Ver / ocultar serial en tabla
$(document).on('click', '.btn-eye', function() {
    var $sp  = $(this).prev('.key-value');
    var plain = $sp.data('plain');
    var showing = $sp.data('showing');
    $sp.text(showing ? '●'.repeat(Math.min(plain.length, 22)) : plain);
    $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    $sp.data('showing', !showing);
});

// Copiar serial
$(document).on('click', '.btn-copy', function() {
    navigator.clipboard.writeText($(this).data('copy')).then(function() {
        App.toast('Serial copiado', 'success');
    });
});

// Toggle input password/text en modales
$(document).on('click', '.btn-toggle-inp', function() {
    var $inp = $('#' + $(this).data('target'));
    $inp.attr('type', $inp.attr('type') === 'password' ? 'text' : 'password');
    $(this).find('i').toggleClass('fa-eye fa-eye-slash');
});

// Abrir modal editar
$(document).on('click', '.btn-editar', function() {
    var $b = $(this);
    $('#editId').val($b.data('id'));
    $('#editNombre').val($b.data('nombre'));
    $('#editSerial').val($b.data('serial'));
    $('#editFecha').val($b.data('fecha'));
    $('#editObs').val($b.data('obs'));
    $('#editEquipo').val($b.data('equipo'));
    $('#editUtil').val($b.data('util') || 'LIBRE');
    // Seleccionar tipo (incluye valores no listados)
    var tipo = $b.data('tipo');
    var $sel = $('#editTipo');
    $sel.val(tipo);
    if (!$sel.val()) {
        $sel.append('<option value="'+tipo+'" selected>'+tipo+'</option>');
    }
    $('#modalEditar').modal('show');
});

// Eliminar
$(document).on('click', '.btn-eliminar', function() {
    var id = $(this).data('id'), desc = $(this).data('desc');
    App.confirm('¿Eliminar licencia?', desc, function() {
        $('#deleteId').val(id);
        $('#formEliminar').submit();
    });
});
<?php
$inlineJs = ob_get_clean();
include_once(__DIR__ . '/../../layouts/footer.php');
?>