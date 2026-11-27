<?php
/**
 * Participantes que respondieron una encuesta
 * Sistema de Soporte TI - TAMA
 */
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

// --- RESPUESTA AJAX PARA MODAL ---
if (isset($_GET['ajax'], $_GET['uid']) && $_GET['ajax'] && isset($_GET['id'])) {
  $uid = (int)$_GET['uid'];
  $id_encuesta = (int)$_GET['id'];
  // Obtener preguntas y respuestas
  $pregs = [];
  $sql = "SELECT p.id_pregunta, p.texto, p.tipo FROM preguntas p WHERE p.id_encuesta = {$id_encuesta} ORDER BY p.orden, p.id_pregunta";
  $res = $db->query($sql);
  while ($row = $db->fetch_assoc($res)) $pregs[] = $row;
  // Obtener respuestas del usuario
  $resp = [];
  $sql = "SELECT d.id_pregunta, d.texto_respuesta, o.texto AS opcion
            FROM respuestas r
            INNER JOIN detalle_respuestas d ON d.id_respuesta = r.id_respuesta
            LEFT JOIN opciones o ON d.id_opcion = o.id_opcion
            WHERE r.id_encuesta = {$id_encuesta} AND r.id_usuario = {$uid}";
  $res = $db->query($sql);
  while ($row = $db->fetch_assoc($res)) {
    $resp[$row['id_pregunta']][] = $row;
  }
  // Render HTML
  foreach ($pregs as $i => $p): ?>
    <div class="mb-3">
      <div class="font-weight-bold mb-1"><?= ($i+1) . '. ' . htmlspecialchars($p['texto'], ENT_QUOTES) ?></div>
      <div>
        <?php if (!empty($resp[$p['id_pregunta']])): foreach ($resp[$p['id_pregunta']] as $r): ?>
          <?php if ($p['tipo'] === 'abierta'): ?>
            <span class="badge badge-secondary">Respuesta abierta:</span> <?= htmlspecialchars($r['texto_respuesta'], ENT_QUOTES) ?><br>
          <?php else: ?>
            <span class="badge badge-info">Opción:</span> <?= htmlspecialchars($r['opcion'], ENT_QUOTES) ?><br>
          <?php endif; ?>
        <?php endforeach; else: ?>
          <span class="text-muted">Sin respuesta</span>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; exit;
}

$id_encuesta = isset($_GET['id']) ? (int)$_GET['id'] : 1;
// Usuarios que respondieron
$sql = "SELECT u.id, u.name, u.area, u.cargo, r.fecha
  FROM respuestas r
  INNER JOIN users u ON r.id_usuario = u.id
  WHERE r.id_encuesta = {$id_encuesta}
  ORDER BY r.fecha DESC";
$res = $db->query($sql);
$participantes = [];
while ($row = $db->fetch_assoc($res)) { $participantes[] = $row; }

// Usuarios habilitados que NO han respondido
$sqlNo = "SELECT u.id, u.name, u.area, u.cargo
    FROM users u
    WHERE u.estado = 1
      AND u.id NOT IN (SELECT r.id_usuario FROM respuestas r WHERE r.id_encuesta = {$id_encuesta})
    ORDER BY u.name";
$resNo = $db->query($sqlNo);
$noRespondieron = [];
while ($row = $db->fetch_assoc($resNo)) { $noRespondieron[] = $row; }

include_once(__DIR__ . '/../../layouts/header.php');
?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><i class="fas fa-users mr-2"></i>Participantes de la Encuesta</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Encuestas</a></li>
          <li class="breadcrumb-item active">Participantes</li>
        </ol>
      </div>
    </div>
  </div>
</section>
<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Usuarios que respondieron</h3>
      </div>

      <div class="card-body">
        <h5 class="mb-3"><i class="fas fa-check-circle text-success mr-2"></i>Usuarios que <b>respondieron</b> la encuesta</h5>
        <table class="table table-bordered table-hover mb-4">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Área</th>
              <th>Cargo</th>
              <th>Fecha de Respuesta</th>
              <th>Ver Respuestas</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php $n=1; foreach ($participantes as $p): ?>
            <tr>
              <td><?= $n++ ?></td>
              <td><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($p['area'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($p['cargo'], ENT_QUOTES) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
              <td>
                <button class="btn btn-info btn-sm ver-respuestas" data-uid="<?= $p['id'] ?>" data-nombre="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>">
                  <i class="fas fa-eye"></i> Ver
                </button>
              </td>
              <td>
                <button class="btn btn-danger btn-sm eliminar-respuesta"
                        data-uid="<?= $p['id'] ?>"
                        data-nombre="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
                        title="Eliminar respuesta para que pueda volver a responder">
                  <i class="fas fa-trash"></i> Resetear
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <h5 class="mt-4 mb-3"><i class="fas fa-times-circle text-danger mr-2"></i>Usuarios que <b>NO han respondido</b> la encuesta</h5>
        <table class="table table-bordered table-hover">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Área</th>
              <th>Cargo</th>
            </tr>
          </thead>
          <tbody>
            <?php $n=1; foreach ($noRespondieron as $p): ?>
            <tr>
              <td><?= $n++ ?></td>
              <td><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($p['area'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($p['cargo'], ENT_QUOTES) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <!-- Modal para mostrar respuestas -->
        <div class="modal fade" id="modalRespuestas" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-eye mr-2"></i>Respuestas de <span id="respNombre"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
              </div>
              <div class="modal-body" id="respContenido">
                <div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
<script>
var ENC_ID = <?= (int)($id_encuesta ?? 1) ?>;

$(document).on('click', '.ver-respuestas', function() {
  var uid = $(this).data('uid');
  var nombre = $(this).data('nombre');
  $('#respNombre').text(nombre);
  $('#respContenido').html('<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
  $('#modalRespuestas').modal('show');
  $.get('participantes.php', { ajax: 1, id: ENC_ID, uid: uid }, function(html) {
    $('#respContenido').html(html);
  });
});

$(document).on('click', '.eliminar-respuesta', function() {
  var uid    = $(this).data('uid');
  var nombre = $(this).data('nombre');
  var $row   = $(this).closest('tr');
  Swal.fire({
    title: '¿Resetear respuesta?',
    html: 'Se eliminarán todas las respuestas de <b>' + nombre + '</b> y podrá volver a completar la encuesta.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then(function(result) {
    if (!result.isConfirmed) return;
    $.post('../../api/encuesta/eliminar_respuesta.php',
      { id_encuesta: ENC_ID, id_usuario: uid },
      function(res) {
        if (res.ok) {
          Swal.fire({ icon: 'success', title: 'Respuesta eliminada', text: nombre + ' podrá responder nuevamente.', timer: 2000, showConfirmButton: false });
          $row.fadeOut(400, function(){ $(this).remove(); });
        } else {
          Swal.fire('Error', res.error || 'No se pudo eliminar', 'error');
        }
      }, 'json'
    ).fail(function() {
      Swal.fire('Error', 'Error de conexión', 'error');
    });
  });
});
</script>
