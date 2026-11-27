
<?php
/**
 * Módulo Licencias — Cuentas SAP Business One
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Cuentas SAP Business One';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_permiso('licencias', 'ver');

// Relacion permanente entre la licencia SAP y el maestro de usuarios.
// La columna se crea una sola vez para mantener compatibles los registros antiguos.
$colUsuarioSap = $db->query("SHOW COLUMNS FROM usuariossap LIKE 'UserId'");
if ($db->num_rows($colUsuarioSap) === 0) {
    $db->query("ALTER TABLE usuariossap ADD COLUMN UserId INT(11) UNSIGNED NULL AFTER IdUsuSap, ADD INDEX idx_usuariossap_user (UserId)");
    $db->query("UPDATE usuariossap s INNER JOIN users u ON LOWER(TRIM(u.name)) = LOWER(TRIM(s.NomUsuario)) SET s.UserId = u.id WHERE s.UserId IS NULL");
}

function usuarioActivoParaSap($db, $userId) {
    $userId = (int)$userId;
    if (!$userId) return null;
    $result = $db->query("SELECT id,name,area,subarea,correo,status FROM users WHERE id={$userId} AND status=1 LIMIT 1");
    return $db->fetch_assoc($result) ?: null;
}

function sincronizarDatosLicenciaSap($db, $cuentaAnterior, $cuenta, $contraTerminal, $usuarioSap, $contraSap, $superUsuario, $licencia, $estado) {
    if ($cuenta === '') return;
    $cuentaAnterior = $db->escape($cuentaAnterior);
    // Primero buscar la cuenta destino. Esto evita duplicarla cuando se cambia
    // el nombre de una cuenta y la nueva ya existe en el historico.
    $resultado = $db->query("SELECT IdUsuTerminal FROM usuariotreminalsap WHERE UsuTerminal='{$cuenta}' LIMIT 1");
    $destino = $db->fetch_assoc($resultado);
    if ($destino) {
        $db->query("UPDATE usuariotreminalsap SET ContraTerminal='{$contraTerminal}',UsuSap='{$usuarioSap}',ContraSap='{$contraSap}',SuperUsuario='{$superUsuario}',Licencia='{$licencia}',Estado={$estado} WHERE UsuTerminal='{$cuenta}' LIMIT 1");
    } else {
        $anterior = null;
        if ($cuentaAnterior !== '') {
            $resultado = $db->query("SELECT IdUsuTerminal FROM usuariotreminalsap WHERE UsuTerminal='{$cuentaAnterior}' LIMIT 1");
            $anterior = $db->fetch_assoc($resultado);
        }
        if ($anterior) {
            $idTerminal = (int)$anterior['IdUsuTerminal'];
            $db->query("UPDATE usuariotreminalsap SET UsuTerminal='{$cuenta}',ContraTerminal='{$contraTerminal}',UsuSap='{$usuarioSap}',ContraSap='{$contraSap}',SuperUsuario='{$superUsuario}',Licencia='{$licencia}',Estado={$estado} WHERE IdUsuTerminal={$idTerminal} LIMIT 1");
        } else {
            $db->query("INSERT INTO usuariotreminalsap (UsuTerminal,ContraTerminal,UsuSap,ContraSap,SuperUsuario,Licencia,Estado) VALUES ('{$cuenta}','{$contraTerminal}','{$usuarioSap}','{$contraSap}','{$superUsuario}','{$licencia}',{$estado})");
        }
    }
}

// ── POST handlers ─────────────────────────────────────────────────────────────
$action = trim($_POST['action'] ?? '');

if ($action === 'agregar' && tiene_permiso('licencias', 'crear')) {
    $userId = (int)($_POST['UserId'] ?? 0);
    $dejarLibre = ($userId === -1);
    $persona = usuarioActivoParaSap($db, $userId);
    $nom   = $db->escape($dejarLibre ? 'LIBRE' : trim($persona['name'] ?? ''));
    $area  = $db->escape(trim($persona['area']    ?? ''));
    $sub   = $db->escape(trim($persona['subarea'] ?? ''));
    $email = $db->escape(trim($persona['correo']  ?? ''));
    $cta   = $db->escape(trim($_POST['CuentaTer']     ?? ''));
    $cpass = $db->escape(trim($_POST['ContraTerminal']?? ''));
    $usr   = $db->escape(trim($_POST['UsuarioSap']    ?? ''));
    $pass  = $db->escape(trim($_POST['ContraSap']     ?? ''));
    $npc   = $db->escape(trim($_POST['NompuSap']      ?? ''));
    $licencia = strtoupper(trim($_POST['Licencia'] ?? ''));
    $licenciasPermitidas = ['LOGISTICA','PROFESIONAL','FINANCIERA','CMR','CRM'];
    $licencia = in_array($licencia, $licenciasPermitidas, true) ? $db->escape($licencia) : '';
    $superUsuario = (($_POST['SuperUsuario'] ?? 'NO') === 'SI') ? 'SI' : 'NO';
    $est   = (int)($_POST['Estado'] ?? 1);
    if ($persona || $dejarLibre) {
        $userIdSql = $dejarLibre ? 'NULL' : (string)$userId;
        $db->query("INSERT INTO usuariossap (UserId,NomUsuario,area,SubArea,Correo,CuentaTer,ContraTerminal,UsuarioSap,ContraSap,NompuSap,Estado)
                    VALUES ({$userIdSql},'{$nom}','{$area}','{$sub}','{$email}','{$cta}','{$cpass}','{$usr}','{$pass}','{$npc}',{$est})");
        sincronizarDatosLicenciaSap($db, '', $cta, $cpass, $usr, $pass, $superUsuario, $licencia, $est);
        $session->msg('s', 'Usuario SAP registrado correctamente.');
    } else { $session->msg('d', 'Seleccione un usuario activo de la lista.'); }
    redirect('LicenciasSap.php', false);
}

if ($action === 'editar' && tiene_permiso('licencias', 'editar')) {
    $id    = (int)($_POST['id'] ?? 0);
    $userId = (int)($_POST['UserId'] ?? 0);
    $dejarLibre = ($userId === -1);
    $persona = usuarioActivoParaSap($db, $userId);
    $cta   = $db->escape(trim($_POST['CuentaTer']     ?? ''));
    $cpass = $db->escape(trim($_POST['ContraTerminal']?? ''));
    $usr   = $db->escape(trim($_POST['UsuarioSap']    ?? ''));
    $pass  = $db->escape(trim($_POST['ContraSap']     ?? ''));
    $npc   = $db->escape(trim($_POST['NompuSap']      ?? ''));
    $licencia = strtoupper(trim($_POST['Licencia'] ?? ''));
    $licenciasPermitidas = ['LOGISTICA','PROFESIONAL','FINANCIERA','CMR','CRM'];
    $licencia = in_array($licencia, $licenciasPermitidas, true) ? $db->escape($licencia) : '';
    $superUsuario = (($_POST['SuperUsuario'] ?? 'NO') === 'SI') ? 'SI' : 'NO';
    $est   = (int)($_POST['Estado'] ?? 1);
    if ($id) {
        $actualResult = $db->query("SELECT CuentaTer FROM usuariossap WHERE IdUsuSap={$id} LIMIT 1");
        $actual = $db->fetch_assoc($actualResult);
        $cuentaAnterior = $actual ? (string)$actual['CuentaTer'] : '';
        $datosPersona = '';
        if ($dejarLibre) {
            $datosPersona = "UserId=NULL,NomUsuario='LIBRE',area='',SubArea='',Correo='',";
        } elseif ($persona) {
            $nom   = $db->escape(trim($persona['name']));
            $area  = $db->escape(trim($persona['area']));
            $sub   = $db->escape(trim($persona['subarea']));
            $email = $db->escape(trim($persona['correo']));
            $datosPersona = "UserId={$userId},NomUsuario='{$nom}',area='{$area}',SubArea='{$sub}',Correo='{$email}',";
        }
        $db->query("UPDATE usuariossap SET {$datosPersona}
                    CuentaTer='{$cta}',ContraTerminal='{$cpass}',
                    UsuarioSap='{$usr}',ContraSap='{$pass}',NompuSap='{$npc}',Estado={$est}
                    WHERE IdUsuSap={$id}");
        sincronizarDatosLicenciaSap($db, $cuentaAnterior, $cta, $cpass, $usr, $pass, $superUsuario, $licencia, $est);
        $session->msg('s', 'Usuario SAP actualizado correctamente.');
    } else { $session->msg('d', 'Datos incompletos.'); }
    redirect('LicenciasSap.php', false);
}

if ($action === 'eliminar' && tiene_permiso('licencias', 'eliminar')) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $db->query("DELETE FROM usuariossap WHERE IdUsuSap={$id}");
        $session->msg('s', 'Registro eliminado.');
    }
    redirect('LicenciasSap.php', false);
}

// ── Cargar datos ──────────────────────────────────────────────────────────────
$res = $db->query("SELECT s.*,u.status AS UsuarioStatus,u.name AS NombreMaestro,
                          t.Licencia,t.SuperUsuario
                   FROM usuariossap s
                   LEFT JOIN users u ON u.id=s.UserId
                   LEFT JOIN usuariotreminalsap t ON t.IdUsuTerminal=(SELECT MAX(t2.IdUsuTerminal) FROM usuariotreminalsap t2 WHERE t2.UsuTerminal=s.CuentaTer)
                   ORDER BY s.Estado DESC,s.NomUsuario");
$usuarios = [];
while ($row = $db->fetch_assoc($res)) { $usuarios[] = $row; }

$usuariosActivos = [];
$resActivos = $db->query("SELECT id,name,area,subarea,correo FROM users WHERE status=1 ORDER BY name");
while ($row = $db->fetch_assoc($resActivos)) { $usuariosActivos[] = $row; }

$total    = count($usuarios);
$activos  = count(array_filter($usuarios, fn($u) => (int)$u['Estado'] === 1));
$inactivos = $total - $activos;
$areas    = count(array_unique(array_column($usuarios, 'area')));

$areasSap = [
    'ALMACEN', 'ADMINISTRACION', 'CONTABILIDAD', 'TESORERIA',
    'GERENCIA COMERCIAL', 'PRESUPUESTOS', 'PROYECTOS', 'ESTRUCTURADOR',
    'ROCA', 'GERENCIA', 'SSMA', 'SISTEMAS', 'LOGISTICA', 'GOP', 'PCP',
    'PRODUCCIÓN', 'ACABADOS', 'CALIDAD', 'MANTENIMIENTO'
];

$extraCss = array_merge($extraCss ?? [], [
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    'https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css'
]);

include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
#tblSap tbody tr.usuario-cesado > td { background-color:#f8d7da !important; color:#721c24; }
#tblSap tbody tr.usuario-cesado:hover > td { background-color:#f1b0b7 !important; }
.sap-stats .card { border-radius:.65rem; }
.sap-stats .card-body { min-height:58px; padding:.55rem .9rem !important; display:flex; align-items:center; text-align:left !important; }
.sap-stats .card-body > i { font-size:1.25rem !important; margin:0 .7rem 0 0 !important; width:25px; text-align:center; }
.sap-stats .display-4 { font-size:1.45rem; line-height:1; }
.sap-stats small { display:block; font-size:.72rem; line-height:1.1; opacity:.9; }
.sap-card-header { gap:1rem; }
.sap-search { width:min(430px,42vw); }
.sap-search .input-group { background:#fff; border-radius:2rem; padding:.2rem; box-shadow:0 4px 14px rgba(0,0,0,.2); }
.sap-search .form-control { border:0; height:34px; padding-left:.9rem; background:transparent; box-shadow:none !important; }
.sap-search .btn { width:38px; height:34px; border-radius:50% !important; display:flex; align-items:center; justify-content:center; }
@media (max-width:767.98px) {
  .sap-card-header { flex-wrap:wrap; }
  .sap-search { order:3; width:100%; }
  .sap-header-actions { margin-left:auto; }
}
</style>
<!-- ════════════════ HEADER SAP ════════════════ -->
<section class="content-header">
  <div class="container-fluid">
    <div class="py-3 px-4 mb-1 rounded shadow-sm d-flex flex-wrap align-items-center text-white"
         style="background:linear-gradient(135deg,#0f2027,#203a43,#2c5364)">
      <div class="mr-3 rounded-circle d-flex align-items-center justify-content-center"
           style="width:55px;height:55px;background:rgba(255,255,255,.15)">
        <i class="fas fa-cubes" style="font-size:1.6rem"></i>
      </div>
      <div class="flex-grow-1">
        <h2 class="mb-0 font-weight-bold">SAP Business One</h2>
        <small style="opacity:.8">Gestión de cuentas y accesos al sistema ERP</small>
      </div>
      <ol class="breadcrumb bg-transparent mb-0 p-0">
        <li class="breadcrumb-item"><a href="../../home.php" class="text-white-50">Inicio</a></li>
        <li class="breadcrumb-item"><a href="index.php" class="text-white-50">Licencias</a></li>
        <li class="breadcrumb-item active text-white">SAP</li>
      </ol>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <?php echo display_msg($msg); ?>

    <!-- Stats -->
    <div class="row mb-2 sap-stats">
      <div class="col-6 col-lg-3 mb-2">
        <div class="card border-0 shadow-sm text-white h-100"
             style="background:linear-gradient(135deg,#0f2027,#2c5364)">
          <div class="card-body text-center py-3">
            <i class="fas fa-users fa-2x mb-1" style="opacity:.75"></i>
            <div class="display-4 font-weight-bold lh-1"><?= $total ?></div>
            <small>Usuarios Totales</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-2">
        <div class="card border-0 shadow-sm text-white h-100"
             style="background:linear-gradient(135deg,#11998e,#38ef7d)">
          <div class="card-body text-center py-3">
            <i class="fas fa-user-check fa-2x mb-1" style="opacity:.75"></i>
            <div class="display-4 font-weight-bold lh-1"><?= $activos ?></div>
            <small>Activos</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-2">
        <div class="card border-0 shadow-sm text-white h-100"
             style="background:linear-gradient(135deg,#f5576c,#f093fb)">
          <div class="card-body text-center py-3">
            <i class="fas fa-user-times fa-2x mb-1" style="opacity:.75"></i>
            <div class="display-4 font-weight-bold lh-1"><?= $inactivos ?></div>
            <small>Inactivos</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-2">
        <div class="card border-0 shadow-sm text-white h-100"
             style="background:linear-gradient(135deg,#f7971e,#ffd200)">
          <div class="card-body text-center py-3">
            <i class="fas fa-building fa-2x mb-1" style="opacity:.75"></i>
            <div class="display-4 font-weight-bold lh-1"><?= $areas ?></div>
            <small>Áreas</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow-sm">
      <div class="card-header d-flex align-items-center py-2 sap-card-header"
           style="background:linear-gradient(135deg,#0f2027,#2c5364)">
        <h3 class="card-title text-white mb-0 font-weight-bold flex-shrink-0">
          <i class="fas fa-id-badge mr-2"></i>Cuentas SAP Business One
        </h3>
        <div class="sap-search mx-auto" role="search">
          <div class="input-group">
            <input type="search" id="buscarSap" class="form-control" placeholder="Buscar usuario, cuenta, área..." aria-label="Buscar en licencias SAP">
            <div class="input-group-append">
              <button type="button" id="btnBuscarSap" class="btn btn-warning" title="Buscar">
                <i class="fas fa-search text-dark"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="sap-header-actions d-flex flex-shrink-0">
          <?php if (tiene_permiso('licencias', 'crear')): ?>
          <button class="btn btn-warning btn-sm font-weight-bold text-dark"
                  data-toggle="modal" data-target="#modalAgregar">
            <i class="fas fa-plus mr-1"></i>Nuevo Usuario
          </button>
          <?php endif; ?>
          <a href="../../Reportes/ReporteLicSap.php" class="btn btn-light btn-sm ml-1" target="_blank">
            <i class="fas fa-file-excel mr-1 text-success"></i>Excel
          </a>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table id="tblSap" class="table table-hover mb-0">
          <thead>
            <tr style="background:#f4f6fb">
              <th class="border-top-0 text-center" style="width:40px">#</th>
              <th class="border-top-0">Nombre / Área</th>
              <th class="border-top-0">Correo</th>
              <th class="border-top-0">Cuenta Terminal</th>
              <th class="border-top-0">Pass Terminal</th>
              <th class="border-top-0">Usuario SAP</th>
              <th class="border-top-0">Pass SAP</th>
              <th class="border-top-0">Tipo de licencia</th>
              <th class="border-top-0 text-center">Superusuario</th>
              <th class="border-top-0">Área SAP</th>
              <th class="border-top-0 text-center">Estado</th>
              <th class="border-top-0 text-center" style="width:95px">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($usuarios as $U): ?>
          <tr class="<?= ($U['UsuarioStatus'] !== null && (int)$U['UsuarioStatus'] === 0) ? 'table-danger usuario-cesado' : '' ?>">
            <td class="text-center"><code class="text-muted"><?= (int)$U['IdUsuSap'] ?></code></td>
            <td>
              <strong><?= htmlspecialchars($U['NomUsuario'], ENT_QUOTES) ?></strong><br>
              <?php if ($U['UsuarioStatus'] !== null && (int)$U['UsuarioStatus'] === 0): ?>
              <span class="badge badge-danger mb-1"><i class="fas fa-user-slash mr-1"></i>USUARIO CESADO</span><br>
              <?php endif; ?>
              <small class="text-muted">
                <i class="fas fa-building mr-1"></i><?= htmlspecialchars($U['area'], ENT_QUOTES) ?>
                <?php if ($U['SubArea']): ?> / <?= htmlspecialchars($U['SubArea'], ENT_QUOTES) ?><?php endif; ?>
              </small>
            </td>
            <td><small><i class="fas fa-envelope text-muted mr-1"></i><?= htmlspecialchars($U['Correo'], ENT_QUOTES) ?></small></td>
            <td><code class="small"><?= htmlspecialchars($U['CuentaTer'], ENT_QUOTES) ?></code></td>
            <td><?php echo renderPass($U['ContraTerminal']); ?></td>
            <td><code class="small text-primary"><?= htmlspecialchars($U['UsuarioSap'], ENT_QUOTES) ?></code></td>
            <td><?php echo renderPass($U['ContraSap']); ?></td>
            <td><span class="badge badge-info px-2 py-1"><?= htmlspecialchars($U['Licencia'] ?: 'SIN DEFINIR', ENT_QUOTES) ?></span></td>
            <td class="text-center">
              <span class="badge <?= $U['SuperUsuario'] === 'SI' ? 'badge-warning' : 'badge-secondary' ?> px-2 py-1">
                <?= $U['SuperUsuario'] === 'SI' ? 'SÍ' : 'NO' ?>
              </span>
            </td>
            <td><small><i class="fas fa-map-marker-alt text-muted mr-1"></i><?= htmlspecialchars($U['NompuSap'], ENT_QUOTES) ?></small></td>
            <td class="text-center">
              <?php if ((int)$U['Estado'] === 1): ?>
              <span class="badge badge-pill px-3 py-1" style="background:#d4edda;color:#155724;font-size:.8rem">
                <i class="fas fa-circle mr-1" style="font-size:.45rem;vertical-align:middle"></i>ACTIVO
              </span>
              <?php else: ?>
              <span class="badge badge-pill px-3 py-1" style="background:#f8d7da;color:#721c24;font-size:.8rem">
                <i class="fas fa-circle mr-1" style="font-size:.45rem;vertical-align:middle"></i>INACTIVO
              </span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <div class="btn-group btn-group-sm">
                <?php if (tiene_permiso('licencias', 'editar')): ?>
                <button class="btn btn-warning btn-editar"
                        data-id="<?= (int)$U['IdUsuSap'] ?>"
                        data-userid="<?= (int)$U['UserId'] ?>"
                        data-userstatus="<?= $U['UsuarioStatus'] === null ? '' : (int)$U['UsuarioStatus'] ?>"
                        data-libre="<?= ($U['UserId'] === null && strtoupper(trim($U['NomUsuario'])) === 'LIBRE') ? '1' : '0' ?>"
                        data-nom="<?= htmlspecialchars($U['NomUsuario'], ENT_QUOTES) ?>"
                        data-area="<?= htmlspecialchars($U['area'], ENT_QUOTES) ?>"
                        data-sub="<?= htmlspecialchars($U['SubArea'], ENT_QUOTES) ?>"
                        data-email="<?= htmlspecialchars($U['Correo'], ENT_QUOTES) ?>"
                        data-cta="<?= htmlspecialchars($U['CuentaTer'], ENT_QUOTES) ?>"
                        data-cpass="<?= htmlspecialchars($U['ContraTerminal'], ENT_QUOTES) ?>"
                        data-usr="<?= htmlspecialchars($U['UsuarioSap'], ENT_QUOTES) ?>"
                        data-pass="<?= htmlspecialchars($U['ContraSap'], ENT_QUOTES) ?>"
                        data-licencia="<?= htmlspecialchars($U['Licencia'], ENT_QUOTES) ?>"
                        data-superusuario="<?= htmlspecialchars($U['SuperUsuario'], ENT_QUOTES) ?>"
                        data-npc="<?= htmlspecialchars($U['NompuSap'], ENT_QUOTES) ?>"
                        data-est="<?= (int)$U['Estado'] ?>"
                        title="Editar"><i class="fas fa-edit"></i></button>
                <?php endif; ?>
                <?php if (tiene_permiso('licencias', 'eliminar')): ?>
                <button class="btn btn-danger btn-eliminar"
                        data-id="<?= (int)$U['IdUsuSap'] ?>"
                        data-nom="<?= htmlspecialchars($U['NomUsuario'], ENT_QUOTES) ?>"
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

<?php
// Helper: renderiza campo de contraseña enmascarado con toggle
function renderPass($val) {
    if (empty($val)) return '<span class="text-muted">—</span>';
    $safe = htmlspecialchars($val, ENT_QUOTES);
    return '<div class="d-flex align-items-center">'
         . '<span class="pass-mask mr-1" style="font-family:monospace" data-plain="'.$safe.'">••••••••</span>'
         . '<button type="button" class="btn btn-link btn-sm p-0 btn-show-pass" title="Ver"><i class="fas fa-eye text-muted" style="font-size:.8rem"></i></button>'
         . '</div>';
}
?>

<!-- ════ Modal Agregar SAP ════ -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content rounded-lg overflow-hidden">
      <div class="modal-header py-3 text-white"
           style="background:linear-gradient(135deg,#0f2027,#2c5364)">
        <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i>Nuevo Usuario SAP</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form method="post" action="LicenciasSap.php">
        <input type="hidden" name="action" value="agregar">
        <div class="modal-body">
          <div class="row">
            <!-- Col 1 -->
            <div class="col-md-6">
              <p class="text-muted small font-weight-bold text-uppercase border-bottom pb-1 mb-2">
                <i class="fas fa-user mr-1"></i>Datos del Usuario
              </p>
              <div class="form-group">
                <label class="small font-weight-bold">Nombre Completo <span class="text-danger">*</span></label>
                <select name="UserId" id="aUserId" class="form-control usuario-sap-select" required>
                  <option value="">Seleccione un usuario...</option>
                  <option value="-1">LIBRE / Sin asignar</option>
                  <?php foreach ($usuariosActivos as $persona): ?>
                  <option value="<?= (int)$persona['id'] ?>"
                          data-area="<?= htmlspecialchars($persona['area'], ENT_QUOTES) ?>"
                          data-sub="<?= htmlspecialchars($persona['subarea'], ENT_QUOTES) ?>"
                          data-email="<?= htmlspecialchars($persona['correo'], ENT_QUOTES) ?>"><?= htmlspecialchars($persona['name'], ENT_QUOTES) ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="text-muted">Solo se muestran usuarios activos.</small>
              </div>
              <div class="row">
                <div class="col-6">
                  <div class="form-group">
                    <label class="small font-weight-bold">Área</label>
                    <input type="text" name="area" id="aArea" class="form-control" readonly>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-group">
                    <label class="small font-weight-bold">Sub-Área</label>
                    <input type="text" name="SubArea" id="aSub" class="form-control" readonly>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Correo Electrónico</label>
                <input type="email" name="Correo" id="aEmail" class="form-control" readonly>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Área SAP</label>
                <select name="NompuSap" id="aNpc" class="form-control area-sap-select2" required>
                  <option value="">Seleccione un área...</option>
                  <?php foreach ($areasSap as $areaSap): ?>
                  <option value="<?= htmlspecialchars($areaSap, ENT_QUOTES) ?>"><?= htmlspecialchars($areaSap, ENT_QUOTES) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group mb-0">
                <label class="small font-weight-bold">Estado</label>
                <select name="Estado" class="form-control">
                  <option value="1">Activo</option>
                  <option value="0">Inactivo</option>
                </select>
              </div>
            </div>
            <!-- Col 2 -->
            <div class="col-md-6">
              <p class="text-muted small font-weight-bold text-uppercase border-bottom pb-1 mb-2">
                <i class="fas fa-terminal mr-1"></i>Acceso Terminal &amp; SAP
              </p>
              <div class="form-group">
                <label class="small font-weight-bold">Cuenta Terminal</label>
                <input type="text" name="CuentaTer" class="form-control" required placeholder="dominio\usuario">
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Contraseña Terminal</label>
                <div class="input-group">
                  <input type="password" name="ContraTerminal" id="aContraTer" class="form-control">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-toggle-inp" data-target="aContraTer">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Usuario SAP</label>
                <input type="text" name="UsuarioSap" class="form-control" placeholder="Ej: JPEREZ">
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Contraseña SAP</label>
                <div class="input-group">
                  <input type="password" name="ContraSap" id="aContraSap" class="form-control">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-toggle-inp" data-target="aContraSap">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-7">
                  <div class="form-group">
                    <label class="small font-weight-bold">Tipo de licencia</label>
                    <select name="Licencia" class="form-control" required>
                      <option value="">Seleccione...</option>
                      <option value="LOGISTICA">LOGÍSTICA</option>
                      <option value="PROFESIONAL">PROFESIONAL</option>
                      <option value="FINANCIERA">FINANCIERA</option>
                      <option value="CMR">CMR</option>
                      <option value="CRM">CRM</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-5">
                  <div class="form-group">
                    <label class="small font-weight-bold">Superusuario</label>
                    <select name="SuperUsuario" class="form-control">
                      <option value="NO">NO</option>
                      <option value="SI">SÍ</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning font-weight-bold text-dark">
            <i class="fas fa-save mr-1"></i>Registrar Usuario
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ════ Modal Editar SAP ════ -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content rounded-lg overflow-hidden">
      <div class="modal-header py-3 text-white"
           style="background:linear-gradient(135deg,#f7971e,#ffd200)">
        <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-edit mr-2"></i>Editar Usuario SAP</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form method="post" action="LicenciasSap.php">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <p class="text-muted small font-weight-bold text-uppercase border-bottom pb-1 mb-2">
                <i class="fas fa-user mr-1"></i>Datos del Usuario
              </p>
              <div class="form-group">
                <label class="small font-weight-bold">Nombre Completo <span class="text-danger">*</span></label>
                <select name="UserId" id="eUserId" class="form-control usuario-sap-select">
                  <option value="">Conservar usuario relacionado</option>
                  <option value="-1">LIBRE / Sin asignar</option>
                  <?php foreach ($usuariosActivos as $persona): ?>
                  <option value="<?= (int)$persona['id'] ?>"
                          data-area="<?= htmlspecialchars($persona['area'], ENT_QUOTES) ?>"
                          data-sub="<?= htmlspecialchars($persona['subarea'], ENT_QUOTES) ?>"
                          data-email="<?= htmlspecialchars($persona['correo'], ENT_QUOTES) ?>"><?= htmlspecialchars($persona['name'], ENT_QUOTES) ?></option>
                  <?php endforeach; ?>
                </select>
                <small id="eUsuarioAviso" class="text-muted">Solo se muestran usuarios activos.</small>
              </div>
              <div class="row">
                <div class="col-6">
                  <div class="form-group">
                    <label class="small font-weight-bold">Área</label>
                    <input type="text" name="area" id="eArea" class="form-control" readonly>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-group">
                    <label class="small font-weight-bold">Sub-Área</label>
                    <input type="text" name="SubArea" id="eSub" class="form-control" readonly>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Correo Electrónico</label>
                <input type="email" name="Correo" id="eEmail" class="form-control" readonly>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Área SAP</label>
                <select name="NompuSap" id="eNpc" class="form-control area-sap-select2" required>
                  <option value="">Seleccione un área...</option>
                  <?php foreach ($areasSap as $areaSap): ?>
                  <option value="<?= htmlspecialchars($areaSap, ENT_QUOTES) ?>"><?= htmlspecialchars($areaSap, ENT_QUOTES) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group mb-0">
                <label class="small font-weight-bold">Estado</label>
                <select name="Estado" id="eEst" class="form-control">
                  <option value="1">Activo</option>
                  <option value="0">Inactivo</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <p class="text-muted small font-weight-bold text-uppercase border-bottom pb-1 mb-2">
                <i class="fas fa-terminal mr-1"></i>Acceso Terminal &amp; SAP
              </p>
              <div class="form-group">
                <label class="small font-weight-bold">Cuenta Terminal</label>
                <input type="text" name="CuentaTer" id="eCta" class="form-control" required>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Contraseña Terminal</label>
                <div class="input-group">
                  <input type="text" name="ContraTerminal" id="eCpass" class="form-control">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-toggle-inp" data-target="eCpass">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Usuario SAP</label>
                <input type="text" name="UsuarioSap" id="eUsr" class="form-control">
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Contraseña SAP</label>
                <div class="input-group">
                  <input type="text" name="ContraSap" id="ePass" class="form-control">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-toggle-inp" data-target="ePass">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-7">
                  <div class="form-group">
                    <label class="small font-weight-bold">Tipo de licencia</label>
                    <select name="Licencia" id="eLicencia" class="form-control" required>
                      <option value="">Seleccione...</option>
                      <option value="LOGISTICA">LOGÍSTICA</option>
                      <option value="PROFESIONAL">PROFESIONAL</option>
                      <option value="FINANCIERA">FINANCIERA</option>
                      <option value="CMR">CMR</option>
                      <option value="CRM">CRM</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-5">
                  <div class="form-group">
                    <label class="small font-weight-bold">Superusuario</label>
                    <select name="SuperUsuario" id="eSuperUsuario" class="form-control">
                      <option value="NO">NO</option>
                      <option value="SI">SÍ</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning font-weight-bold text-dark">
            <i class="fas fa-save mr-1"></i>Guardar Cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<form id="formEliminar" method="post" action="LicenciasSap.php" style="display:none">
  <input type="hidden" name="action" value="eliminar">
  <input type="hidden" name="id" id="deleteId">
</form>

<?php
ob_start();
?>
var sapTable = $('#tblSap').DataTable({
    language: DATATABLES_SPANISH,
    pageLength: 20,
    order: [[1,'asc']],
    responsive: true,
    dom: 'rtip'
});

$('#buscarSap').on('input', function() {
    sapTable.search(this.value).draw();
});
$('#btnBuscarSap').on('click', function() {
    sapTable.search($('#buscarSap').val()).draw();
    $('#buscarSap').trigger('focus');
});

$('#aNpc').select2({
    theme: 'bootstrap4',
    width: '100%',
    dropdownParent: $('#modalAgregar'),
    placeholder: 'Seleccione un área'
});
$('#eNpc').select2({
    theme: 'bootstrap4',
    width: '100%',
    dropdownParent: $('#modalEditar'),
    placeholder: 'Seleccione un área'
});

// Mostrar/ocultar contraseña en tabla
$(document).on('click', '.btn-show-pass', function() {
    var $sp = $(this).prev('.pass-mask');
    var plain = $sp.data('plain');
    var showing = $sp.data('showing');
    $sp.text(showing ? '••••••••' : plain);
    $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    $sp.data('showing', !showing);
});

// Toggle input
$(document).on('click', '.btn-toggle-inp', function() {
    var $inp = $('#' + $(this).data('target'));
    $inp.attr('type', $inp.attr('type') === 'password' ? 'text' : 'password');
    $(this).find('i').toggleClass('fa-eye fa-eye-slash');
});

// Completar los datos desde el maestro de usuarios.
$(document).on('change', '.usuario-sap-select', function() {
    var $option = $(this).find('option:selected');
    var prefix = this.id === 'aUserId' ? 'a' : 'e';
    $('#' + prefix + 'Area').val($option.data('area') || '');
    $('#' + prefix + 'Sub').val($option.data('sub') || '');
    $('#' + prefix + 'Email').val($option.data('email') || '');
    if (prefix === 'e' && $(this).val()) {
        var mensaje = $(this).val() === '-1'
            ? 'La licencia quedará libre y sin usuario relacionado.'
            : 'Solo se muestran usuarios activos.';
        $('#eUsuarioAviso').removeClass('text-danger').addClass('text-muted').text(mensaje);
    }
});

// Abrir editar
$(document).on('click', '.btn-editar', function() {
    var $b = $(this);
    $('#editId').val($b.data('id'));
    if (String($b.data('libre')) === '1') {
        $('#eUserId').val('-1').trigger('change');
        $('#eUsuarioAviso').removeClass('text-danger').addClass('text-muted').text('Esta licencia se encuentra libre.');
    } else if (String($b.data('userstatus')) === '1') {
        $('#eUserId').val(String($b.data('userid')));
        $('#eUsuarioAviso').removeClass('text-danger').addClass('text-muted').text('Solo se muestran usuarios activos.');
    } else {
        $('#eUserId').val('');
        $('#eUsuarioAviso').removeClass('text-muted').addClass('text-danger')
            .text('El usuario relacionado está inactivo o no está vinculado. Puede conservarlo o seleccionar otro usuario activo.');
    }
    $('#eArea').val($b.data('area'));
    $('#eSub').val($b.data('sub'));
    $('#eEmail').val($b.data('email'));
    $('#eCta').val($b.data('cta'));
    $('#eCpass').val($b.data('cpass'));
    $('#eUsr').val($b.data('usr'));
    $('#ePass').val($b.data('pass'));
    $('#eLicencia').val(String($b.data('licencia') || ''));
    $('#eSuperUsuario').val(String($b.data('superusuario') || 'NO'));
    var areaSapActual = String($b.data('npc') || '');
    var existeAreaSap = $('#eNpc option').filter(function() { return this.value === areaSapActual; }).length;
    if (areaSapActual && !existeAreaSap) {
        $('#eNpc').append(new Option(areaSapActual + ' (valor anterior)', areaSapActual));
    }
    $('#eNpc').val(areaSapActual).trigger('change');
    $('#eEst').val($b.data('est'));
    $('#modalEditar').modal('show');
});

// Eliminar
$(document).on('click', '.btn-eliminar', function() {
    var id = $(this).data('id'), nom = $(this).data('nom');
    App.confirm('¿Eliminar usuario SAP?', nom, function() {
        $('#deleteId').val(id);
        $('#formEliminar').submit();
    });
});
<?php
$inlineJs = ob_get_clean();
include_once(__DIR__ . '/../../layouts/footer.php');
// El modulo actual termina aqui. Debajo permanece una copia legacy de la
// pagina que intentaba cargar endpoints Ajax inexistentes (example1/example2).
// Evitar que ese contenido residual se procese o se envie al navegador.
exit;
?>


?>


<?php include_once('layouts/header.php'); ?>


 <style type="text/css">
           td{
           width:200%;
    overflow:hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    word-wrap: break-word;
    font-size:smaller;
           }
           table.display { 
  margin:20 auto;  
  width:150%;  
} 
   td1{
           width:1%;
    overflow:hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    word-wrap: break-word;
    font-size:smaller;
           }
     table.display { 
  margin:1 auto;  
  width:150%;  
} 
   
     </style>
         
<?php include_once('layouts/header.php'); ?>
  <div class="row">
     <div class="col-md-12">
       <?php echo display_msg($msg); ?>
     </div>
    <div class="col-md-12">
      <div class="panel panel-default">
       <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          <span>Asignacion de cuentas SAP</span>
       </strong>
           <div class="col-md-1 pull-right ">       <a href="Reportes/ReporteLicSap.php" class="btn btn-success pull-right">Excel-Tama</a> </div>
        <div class="col-md-2 pull-right ">      <a href="agregar_LicenciaSap.php" class="btn btn-info pull-right">Agregar Licencia </a></div>
            
         <div class="panel-body table-responsive  col-md-12">
         <table id="example"  class="table table-bordered  " style="width:400%">
            <thead class="panel-default" style="color: #002752 !important; border: 2">
                   <tr style="border-color: #002752 !important;">
              
          <th class="text-center" colspan="2" rowspan="5" style="border-color: #002752 !important;"> <center><img src="<?php echo htmlspecialchars(REPORT_LOGO_URL, ENT_QUOTES, 'UTF-8'); ?>" WIDTH="120" HEIGHT="50"></center></th>
     <th class="text-center" colspan="5" rowspan="2" style="border-color: #002752 !important;"> SISTEMA INTEGRADO DE GESTIÓN</th>
     <th class="text-center" colspan="6"  style="border-color: #002752 !important;"> T-TI-F-12</th>
             
       
          </tr>
           <tr>
              <th class="text-center" colspan="2" style="border-color: #002752 !important;"> Fecha </th>
                 <th class="text-center" colspan="4" style="border-color: #002752 !important;"> 02/03/2020 </th>           
                      
  
          </tr>
          
             <tr>
              <th class="text-center" colspan="5" rowspan="3" style="border-color: #002752 !important;"> ASIGNACIÓN DE CUENTAS Y LICENCIAS EN ERP SAP BUSINESS ONE </th>
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> Versión  </th> 
               <th class="text-center" colspan="4" style="border-color: #002752 !important;"> 01 </th>    
                      
  
          </tr>
            <tr>
          
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> Elaborado  </th> 
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> Revisado </th>    
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> Aprobado  </th>         
  
          </tr>
            <tr>
          
                 <th class="text-center"  colspan="2" style="border-color: #002752 !important;"> TI </th> 
               <th class="text-center" colspan="2" style="border-color: #002752 !important;"> GG </th> 
               <th class="text-center" colspan="2" style="border-color: #002752 !important;"> GG </th>         
  
          </tr>
    
     
                
                
              <tr>
                <th class="text-center" style='width: 7%;font-size: small;'><small> ITEM</small></th>
                <th class="text-center" style='width: 1%;font-size: small;'><small>USUARIO TERMINAL</small></th>
                <th class="text-center" style='width: 20%;font-size: small;'><small>CONTRASEÑA</small></th>
                <th class="text-center" style='width: 20%;font-size: small;'><small> USUARIO SAP</small></th>
                <th class="text-center" style='width: 15%;font-size: small;'><small> GERENCIA</small></th>
                <th class="text-center" style='width: 15%;font-size: small;'><small> AREA</small></th>
                <th class="text-center" style='width: 20%;font-size: small;'><small> CORREO</small> </th>
                <th class="text-center" style='width: 22%;font-size: small;'><small> PUESTO SAP</small> </th>
                <th class="text-center" style='width: 35%;font-size: small;'><small> Nombre Usuario</small> </th>
                <th class="text-center" style='width: 35%;font-size: small;'><small> SUPERUSUARIO</small> </th>
                <th class="text-center" style='width: 22%;font-size: small;'><small> LICENCIA SAP</small> </th>
                <th class="text-center" style='width: 23%;font-size: small;'><small> CONTRASEÑA SAP</small></th>
                <th class="text-center" style='width: 7%;font-size: small;'><small> ESTADO/ OBSER</small></th>
                 <th class="text-center"  style='width: 10%;font-size: small;'><small> EDITAR </small></th>
                  
               
              </tr>
            </thead>
             <tbody>
                </tbody>
          </table>
              <script type="text/javascript">
$(document).ready(function() {
    $('#example').DataTable( {
         lengthMenu: [
            [20],
            ['All'],
        ],
           "ajax":{
                "url": "../../public/ajax/consulta3.php",
                "dataSrc":""
            },
            "columns":[
      
           
       {"data": "IdUsuSap"},
                {"data": "CuentaTer"},
                {"data": "ContraTerminal"},
                {"data": "UsuarioSap"},
                {"data": "area"},
                {"data": "subarea"},
                {"data": "Correo"},
                {"data": "NompuSap"},
                {"data": "NomUsuario"},
                {"data": "SuperUsuario"},
                {"data": "Licencia"},
                {"data": "ContraSap"},
   
         
           
              {"data": "Estado",
            "render": function(data){
          if (data == 1)
                {data = "<span class='badge' style='background: green'>Activo</span>";}
            else if (data == 0)
                {data = "<span class='badge' style='background: red'>Inactivo</span>";}
            
            return data;
                
              }
            },  
                    
               
      
     {    
            data: "IdUsuSap",
            class: 'clases_para_el_td',
            render: function(data, type, row){  // con row obtienes la información por fila
                return '<a href="editar_cuentaSap.php?IdUsuSap='+row.IdUsuSap+'" class="btn btn-info btn-xs" type="button" style="margin: 1px"  title="Editar" data-toggle="tooltip">EDITAR<i class="glyphicon glyphicon-edite"></i></a>';
            }
        }
                  
                 ],
        dom: "Bfrtip",
    buttons: [
      {
        extend: "excel",
        text: "<i >EXCEL</i>",
        className: "btn btn-success btn-sm icon-excel3",
        exportOptions: {
          columns: "th:not(:last-child)",
        },
      },
      {
        extend: "pdf",
        text: "<span >PDF</span>",
        className: "btn btn-danger btn-sm icon-pdf3",
        exportOptions: {
          columns: "th:not(:last-child)",
        },
      },
       {
        extend: "copy",
        text: "<span >COPY</span>",
        className: "btn btn-info btn-sm icon-pdf3",
        exportOptions: {
          columns: "th:not(:last-child)",
        },
      },
    ],
        
         "language":{
      
       "zeroRecords": "Lo sentimos. No se encontraron registros.",
             "info": "Mostrando página _PAGE_ de _PAGES_",
             "infoEmpty": "No hay registros aún.",
             "infoFiltered": "(filtrados de un total de _MAX_ registros)",
              "lengthMenu": "Mostrar _MENU_ registros por página",
             "search" : "Búsqueda",
            "LoadingRecords": "Cargando ...",
             "Processing": "Procesando...",
             "SearchPlaceholder": "Comience a teclear...",
             "paginate": {
     "previous": "Anterior",
     "next": "Siguiente", 
    
     }
      },
       columnDefs: [
      { width: "35px", targets: 0 },
      { width: "35px", targets: 1 },
      { width: "70px", targets: 2 },
      { width: "70px", targets: 3 },
      { width: "100px", targets: 4 },
      { width: "100px", targets: 5 },
      { width: "70px", targets: 6 },
      { width: "100px", targets: 7 },
      { width: "100px", targets: 8 },
       { width: "80px", targets: 9 },
        { width: "100px", targets: 10 }
    ]
    } );
} );

</script> 
        </div>
      </div>
    </div>
  </div>
  </div> 
            
            
            
            
            
            
   <div class="row">
     <div class="col-md-12">
       <?php echo display_msg($msg); ?>
     </div>
    <div class="col-md-12">
      <div class="panel panel-default">
       <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          <span>Asignacion de cuentas Orbis</span>
       </strong>
           <div class="col-md-1 pull-right ">       <a href="Reportes/ReporteLicOrb.php" class="btn btn-success pull-right">Excel-Tama</a> </div>
            <div class="col-md-2 pull-right ">       <a href="agregar_LicenciaOrbis.php" class="btn btn-info pull-right">Agregar Licencia </a> </div>
         <div class="panel-body table-responsive col-sm-12 col-md-12">
         <table id="example1"  class="table table-bordered  " style="width:400%">
            <thead class="panel-default" style="color: #002752 !important; border: 2">
                   <tr style="border-color: #002752 !important;">
              
          <th class="text-center" colspan="1" rowspan="5" style="border-color: #002752 !important;"> <center><img src="<?php echo htmlspecialchars(REPORT_LOGO_URL, ENT_QUOTES, 'UTF-8'); ?>" WIDTH="120" HEIGHT="50"></center></th>
     <th class="text-center" colspan="3" rowspan="2" style="border-color: #002752 !important;"> SISTEMA INTEGRADO DE GESTIÓN</th>
     <th class="text-center" colspan="3"  style="border-color: #002752 !important;"> T-TI-F-10</th>
             
       
          </tr>
           <tr>
              <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Fecha </th>
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> 02/03/2020 </th>           
                      
  
          </tr>
          
             <tr>
              <th class="text-center" colspan="3" rowspan="3" style="border-color: #002752 !important;"> REGISTRO DE GESTION DE AUTORIZACIONES GENERALES DEL SISTEMA ORBIS  </th>
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Versión  </th> 
               <th class="text-center" colspan="2" style="border-color: #002752 !important;"> 01 </th>    
                      
  
          </tr>
            <tr>
          
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Elaborado  </th> 
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Revisado </th>    
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Aprobado  </th>         
  
          </tr>
            <tr>
          
                 <th class="text-center"  colspan="1" style="border-color: #002752 !important;"> TI </th> 
               <th class="text-center" colspan="1" style="border-color: #002752 !important;"> GG </th> 
               <th class="text-center" colspan="1" style="border-color: #002752 !important;"> GG </th>         
  
          </tr>
    
     
                
                
              <tr>
                <th class="text-center" style='width: 7%;font-size: small;'><small> ITEM</small></th>
                <th class="text-center" style='width: 1%;font-size: small;'><small>AREA</small></th>
                <th class="text-center" style='width: 20%;font-size: small;'><small>NOMBRE Y APELLIDOS</small></th>
                <th class="text-center" style='width: 20%;font-size: small;'><small> USUARIO</small></th>
                <th class="text-center" style='width: 15%;font-size: small;'><small> PASSWORD</small></th>
                <th class="text-center" style='width: 15%;font-size: small;'><small> UTILIDAD</small></th>
                <th class="text-center" style='width: 20%;font-size: small;'><small> ESTADO</small> </th>
        
                 <th class="text-center"  style='width: 10%;font-size: small;'><small> EDITAR </small></th>
                  
               
              </tr>
            </thead>
             <tbody>
                </tbody>
          </table>
              <script type="text/javascript">
 var table = $("#example1").DataTable({
         lengthMenu: [
            [20],
            ['All'],
        ],
           "ajax":{
                "url": "consultasAjax/consulta4.php",
                "dataSrc":""
            },
            "columns":[
      
           
       {"data": "IdUsuOrbis"},
                {"data": "area"},
                {"data": "NomUsuario"},
                {"data": "CuentaOrbis"},
                {"data": "ContraOrbis"},
         
         {"data": "Utilidad"},
             
      {"data": "Estado",
            "render": function(data){
          if (data == 1)
                {data = "<span class='badge' style='background: green'>Activo</span>";}
            else if (data == 0)
                {data = "<span class='badge' style='background: red'>Inactivo</span>";}
            
            return data;
                
              }
            },
      
               
      
     {    
            data: "IdProcTer",
            class: 'clases_para_el_td',
            render: function(data, type, row){  // con row obtienes la información por fila
                return '<a href="editar_ProducTerminado.php?IdProcTer='+row.IdProcTer+'" class="btn btn-info btn-xs" type="button" style="margin: 1px"  title="Editar" data-toggle="tooltip">EDITAR<i class="glyphicon glyphicon-edite"></i></a>';
            }
        }
                  
                 ],
        dom: "Bfrtip",
    buttons: [
      {
        extend: "excel",
        text: "<i >EXCEL</i>",
        className: "btn btn-success btn-sm icon-excel3",
        exportOptions: {
          columns: "th:not(:last-child)",
        },
      },
      {
        extend: "pdf",
        text: "<span >PDF</span>",
        className: "btn btn-danger btn-sm icon-pdf3",
        exportOptions: {
          columns: "th:not(:last-child)",
        },
      },
       {
        extend: "copy",
        text: "<span >COPY</span>",
        className: "btn btn-info btn-sm icon-pdf3",
        exportOptions: {
          columns: "th:not(:last-child)",
        },
      },
    ],
        
         "language":{
      
       "zeroRecords": "Lo sentimos. No se encontraron registros.",
             "info": "Mostrando página _PAGE_ de _PAGES_",
             "infoEmpty": "No hay registros aún.",
             "infoFiltered": "(filtrados de un total de _MAX_ registros)",
              "lengthMenu": "Mostrar _MENU_ registros por página",
             "search" : "Búsqueda",
            "LoadingRecords": "Cargando ...",
             "Processing": "Procesando...",
             "SearchPlaceholder": "Comience a teclear...",
             "paginate": {
     "previous": "Anterior",
     "next": "Siguiente", 
    
     }
      },
       columnDefs: [
      { width: "35px", targets: 0 },
      { width: "35px", targets: 1 },
      { width: "70px", targets: 2 },
      { width: "70px", targets: 3 },
      { width: "100px", targets: 4 },
      { width: "100px", targets: 5 }


    ]
      } );

</script> 
        </div>
      </div>
    </div>
  </div>
  </div>           
            
         
                        
   <div class="row">
     <div class="col-md-12">
       <?php echo display_msg($msg); ?>
     </div>
    <div class="col-md-12">
      <div class="panel panel-default">
       <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          <span>Asignacion de cuentas APP TAMA</span>
       </strong>
           <div class="col-md-1 pull-right ">       <a href="Reportes/ReporteLicAppTama.php" class="btn btn-success pull-right">Excel-Tama</a> </div>
           <div class="col-md-2 pull-right ">       <a href="agregar_LicenciaAppTama.php" class="btn btn-info pull-right">Agregar Licencia </a> </div>
         <div class="panel-body table-responsive col-sm-12 col-md-12">
         <table id="example2"  class="table table-bordered  " style="width:400%">
            <thead class="panel-default" style="color: #002752 !important; border: 2">
                   <tr style="border-color: #002752 !important;">
              
          <th class="text-center" colspan="1" rowspan="5" style="border-color: #002752 !important;"> <center><img src="<?php echo htmlspecialchars(REPORT_LOGO_URL, ENT_QUOTES, 'UTF-8'); ?>" WIDTH="120" HEIGHT="50"></center></th>
     <th class="text-center" colspan="4" rowspan="2" style="border-color: #002752 !important;"> SISTEMA INTEGRADO DE GESTIÓN</th>
     <th class="text-center" colspan="3"  style="border-color: #002752 !important;"> T-TI-F-13</th>
             
       
          </tr>
           <tr>
              <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Fecha </th>
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> 02/03/2020 </th>           
                      
  
          </tr>
          
             <tr>
              <th class="text-center" colspan="4" rowspan="3" style="border-color: #002752 !important;"> REGISTRO DE GESTIÓN DE AUTORIZACIONES GENERALES DEL SISTEMA APP TAMA  </th>
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Versión  </th> 
               <th class="text-center" colspan="2" style="border-color: #002752 !important;"> 01 </th>    
                      
  
          </tr>
            <tr>
          
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Elaborado  </th> 
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Revisado </th>    
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Aprobado  </th>         
  
          </tr>
            <tr>
          
                 <th class="text-center"  colspan="1" style="border-color: #002752 !important;"> TI </th> 
               <th class="text-center" colspan="1" style="border-color: #002752 !important;"> GG </th> 
               <th class="text-center" colspan="1" style="border-color: #002752 !important;"> GG </th>         
  
          </tr>
    
     
                
                
              <tr>
                <th class="text-center" style='width: 7%;font-size: small;'><small> ITEM</small></th>
                <th class="text-center" style='width: 1%;font-size: small;'><small>AREA</small></th>
                <th class="text-center" style='width: 20%;font-size: small;'><small>NOMBRE Y APELLIDOS</small></th>
                <th class="text-center" style='width: 20%;font-size: small;'><small> USUARIO</small></th>
                <th class="text-center" style='width: 15%;font-size: small;'><small> LICENCIA</small></th>
                <th class="text-center" style='width: 15%;font-size: small;'><small> CATEGORIA</small></th>
                <th class="text-center" style='width: 20%;font-size: small;'><small> APP-HORAS</small> </th>
                <th class="text-center"  style='width: 10%;font-size: small;'><small> RECURSOS  </small></th>
                 <th class="text-center"  style='width: 10%;font-size: small;'><small> ESTADO  </small></th>
                 <th class="text-center"  style='width: 10%;font-size: small;'><small> EDITAR </small></th>
                  
               
              </tr>
            </thead>
             <tbody>
                </tbody>
          </table>
              <script type="text/javascript">
 var table = $("#example2").DataTable({
         lengthMenu: [
            [20],
            ['All'],
        ],
           "ajax":{
                "url": "consultasAjax/consulta5.php",
                "dataSrc":""
            },
            "columns":[
      
           
       {"data": "IdUsuAppTama"},
                {"data": "area"},
                {"data": "NomUsuario"},
                {"data": "CuentaTama"},
                {"data": "Utilidad"},
         
         {"data": "Categoria"},
            {"data": "Apphoras"},
      {"data": "MODULOS"},
        {"data": "Estado",
            "render": function(data){
          if (data == 1)
                {data = "<span class='badge' style='background: green'>Activo</span>";}
            else if (data == 0)
                {data = "<span class='badge' style='background: red'>Inactivo</span>";}
            
            return data;
                
              }
            },
               
      
     {    
            data: "IdProcTer",
            class: 'clases_para_el_td',
            render: function(data, type, row){  // con row obtienes la información por fila
                return '<a href="editar_ProducTerminado.php?IdProcTer='+row.IdProcTer+'" class="btn btn-info btn-xs" type="button" style="margin: 1px"  title="Editar" data-toggle="tooltip">EDITAR<i class="glyphicon glyphicon-edite"></i></a>';
            }
        }
                  
                 ],
        dom: "Bfrtip",
    buttons: [
      {
        extend: "excel",
        text: "<i >EXCEL</i>",
        className: "btn btn-success btn-sm icon-excel3",
        exportOptions: {
          columns: "th:not(:last-child)",
        },
      },
      {
        extend: "pdf",
        text: "<span >PDF</span>",
        className: "btn btn-danger btn-sm icon-pdf3",
        exportOptions: {
          columns: "th:not(:last-child)",
        },
      },
       {
        extend: "copy",
        text: "<span >COPY</span>",
        className: "btn btn-info btn-sm icon-pdf3",
        exportOptions: {
          columns: "th:not(:last-child)",
        },
      },
    ],
        
         "language":{
      
       "zeroRecords": "Lo sentimos. No se encontraron registros.",
             "info": "Mostrando página _PAGE_ de _PAGES_",
             "infoEmpty": "No hay registros aún.",
             "infoFiltered": "(filtrados de un total de _MAX_ registros)",
              "lengthMenu": "Mostrar _MENU_ registros por página",
             "search" : "Búsqueda",
            "LoadingRecords": "Cargando ...",
             "Processing": "Procesando...",
             "SearchPlaceholder": "Comience a teclear...",
             "paginate": {
     "previous": "Anterior",
     "next": "Siguiente", 
    
     }
      },
       columnDefs: [
      { width: "35px", targets: 0 },
      { width: "35px", targets: 1 },
      { width: "70px", targets: 2 },
      { width: "70px", targets: 3 },
      { width: "100px", targets: 4 },
      { width: "200px", targets: 5 }


    ]
      } );

</script> 
        </div>
      </div>
    </div>
  </div>
  </div>           
            
  
            
            
