<?php
/**
 * Módulo Licencias — Cuentas Orbis
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Cuentas Orbis';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_permiso('licencias', 'ver');
ensure_users_tipo_registro();

// Repara instalaciones antiguas donde IdUsuOrbis quedo sin clave primaria ni
// AUTO_INCREMENT. Un ID cero no puede usarse porque cero representa "sin vínculo".
$orbisIdColumnResult = $db->query("SHOW COLUMNS FROM `usuariosorbis` LIKE 'IdUsuOrbis'");
$orbisIdColumn = $db->fetch_assoc($orbisIdColumnResult);
if ($orbisIdColumn && stripos((string)($orbisIdColumn['Extra'] ?? ''), 'auto_increment') === false) {
    $zeroAccount = $db->fetch_assoc($db->query("SELECT `IdUsuOrbis` FROM `usuariosorbis` WHERE `IdUsuOrbis`=0 LIMIT 1"));
    if ($zeroAccount) {
        $nextAccount = $db->fetch_assoc($db->query("SELECT COALESCE(MAX(`IdUsuOrbis`),0)+1 AS next_id FROM `usuariosorbis`"));
        $nextAccountId = max(1, (int)($nextAccount['next_id'] ?? 1));
        $db->query("UPDATE `usuariosorbis` SET `IdUsuOrbis`={$nextAccountId} WHERE `IdUsuOrbis`=0 LIMIT 1");
        $db->query("UPDATE `usuariosf08` SET `orbis_account_id`={$nextAccountId} WHERE `orbis_account_id`=0");
    }
    $orbisIdIndex = $db->query("SHOW INDEX FROM `usuariosorbis` WHERE `Column_name`='IdUsuOrbis'");
    if ($db->num_rows($orbisIdIndex) === 0) {
        $db->query("ALTER TABLE `usuariosorbis` ADD PRIMARY KEY (`IdUsuOrbis`)");
    }
    $db->query("ALTER TABLE `usuariosorbis` MODIFY `IdUsuOrbis` INT NOT NULL AUTO_INCREMENT");
}

// Relacion estable con el maestro de colaboradores. Los registros historicos
// se enlazan por nombre una sola vez sin perder la informacion existente.
$userIdColumn = $db->query("SHOW COLUMNS FROM `usuariosorbis` LIKE 'UserId'");
if ($db->num_rows($userIdColumn) === 0) {
    $db->query("ALTER TABLE `usuariosorbis` ADD COLUMN `UserId` INT NULL AFTER `IdUsuOrbis`");
}
$userIdIndex = $db->query("SHOW INDEX FROM `usuariosorbis` WHERE Key_name='idx_usuariosorbis_user'");
if ($db->num_rows($userIdIndex) === 0) {
    $db->query("ALTER TABLE `usuariosorbis` ADD INDEX `idx_usuariosorbis_user` (`UserId`)");
}
$db->query("UPDATE `usuariosorbis` o
  INNER JOIN `users` u ON TRIM(LOWER(u.`name`))=TRIM(LOWER(o.`NomUsuario`))
  SET o.`UserId`=u.`id`
  WHERE o.`UserId` IS NULL AND TRIM(o.`NomUsuario`)<>''");

// ── POST handlers ─────────────────────────────────────────────────────────────
$action = trim($_POST['action'] ?? '');

if ($action === 'agregar' && tiene_permiso('licencias', 'crear')) {
    $userId = (int)($_POST['UserId'] ?? 0);
    $cta   = $db->escape(trim($_POST['CuentaOrbis'] ?? ''));
    $pass  = $db->escape(trim($_POST['ContraOrbis'] ?? ''));
    $util  = $db->escape(trim($_POST['Utilidad']    ?? ''));
    $est   = (int)($_POST['Estado'] ?? 1);
    $masterUser = $userId > 0 ? $db->fetch_assoc($db->query("SELECT `id`,`name`,`area`,`subarea` FROM `users`
      WHERE `id`={$userId} AND `status`=1 AND `estado`=1 AND COALESCE(`tipo_registro`,'USUARIO')='USUARIO' LIMIT 1")) : null;
    if ($masterUser) {
        $nom = $db->escape(trim((string)$masterUser['name']));
        $area = $db->escape(trim((string)$masterUser['area']));
        $sub = $db->escape(trim((string)$masterUser['subarea']));
        $db->query("INSERT INTO usuariosorbis (UserId,NomUsuario,area,SubArea,CuentaOrbis,ContraOrbis,Utilidad,Estado)
                    VALUES ({$userId},'{$nom}','{$area}','{$sub}','{$cta}','{$pass}','{$util}',{$est})");
        $session->msg('s', 'Usuario Orbis registrado correctamente.');
    } else { $session->msg('d', 'Seleccione un colaborador activo de la lista.'); }
    redirect('LicenciasOrbis.php', false);
}

if ($action === 'editar' && tiene_permiso('licencias', 'editar')) {
    $id   = (int)($_POST['id'] ?? 0);
    $userId = (int)($_POST['UserId'] ?? 0);
    $cta  = $db->escape(trim($_POST['CuentaOrbis'] ?? ''));
    $pass = $db->escape(trim($_POST['ContraOrbis'] ?? ''));
    $util = $db->escape(trim($_POST['Utilidad']    ?? ''));
    $est  = (int)($_POST['Estado'] ?? 1);
    $masterUser = $userId > 0 ? $db->fetch_assoc($db->query("SELECT `id`,`name`,`area`,`subarea` FROM `users`
      WHERE `id`={$userId} AND `status`=1 AND `estado`=1 AND COALESCE(`tipo_registro`,'USUARIO')='USUARIO' LIMIT 1")) : null;
    if ($id && $masterUser) {
        $nom = $db->escape(trim((string)$masterUser['name']));
        $area = $db->escape(trim((string)$masterUser['area']));
        $sub = $db->escape(trim((string)$masterUser['subarea']));
        $db->query("UPDATE usuariosorbis SET UserId={$userId},NomUsuario='{$nom}',area='{$area}',SubArea='{$sub}',
                    CuentaOrbis='{$cta}',ContraOrbis='{$pass}',Utilidad='{$util}',Estado={$est}
                    WHERE IdUsuOrbis={$id}");
        $session->msg('s', 'Usuario Orbis actualizado correctamente.');
    } else { $session->msg('d', 'Datos incompletos.'); }
    redirect('LicenciasOrbis.php', false);
}

if ($action === 'eliminar' && tiene_permiso('licencias', 'eliminar')) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $db->query("DELETE FROM usuariosorbis WHERE IdUsuOrbis={$id}");
        $session->msg('s', 'Registro eliminado.');
    }
    redirect('LicenciasOrbis.php', false);
}

// ── Cargar datos ──────────────────────────────────────────────────────────────
$res      = $db->query("SELECT * FROM usuariosorbis ORDER BY Estado DESC, NomUsuario");
$usuarios = [];
while ($row = $db->fetch_assoc($res)) { $usuarios[] = $row; }
$usuariosMaestro = find_by_sql("SELECT `id`,`name`,`area`,`subarea` FROM `users`
  WHERE `status`=1 AND `estado`=1 AND COALESCE(`tipo_registro`,'USUARIO')='USUARIO'
  ORDER BY `name`");
$utilidades = ['PLANILLA','CONTROL_HORAS','GESTION_DE_PERSONAS','HERRAMIENTAS','TAREO','ADMINISTRADOR'];
foreach ($usuarios as $usuarioOrbis) {
    $utilidadExistente = trim((string)($usuarioOrbis['Utilidad'] ?? ''));
    if ($utilidadExistente !== '' && !in_array($utilidadExistente, $utilidades, true)) {
        $utilidades[] = $utilidadExistente;
    }
}

$total     = count($usuarios);
$activos   = count(array_filter($usuarios, fn($u) => (int)$u['Estado'] === 1));
$inactivos = $total - $activos;
$areas     = count(array_unique(array_column($usuarios, 'area')));
$extraCss = ['https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'];

include_once(__DIR__ . '/../../layouts/header.php');
?>
<style>
.orbis-table-toolbar{display:grid;grid-template-columns:1fr minmax(280px,480px) 1fr;gap:14px;align-items:center;background:linear-gradient(135deg,#0b6e4f,#17a589)}
.orbis-inactive-filter{display:inline-flex;align-items:center;gap:8px;width:max-content;margin:0;padding:8px 12px;border:1px solid rgba(255,255,255,.7);border-radius:9px;background:#fff;color:#243746;font-size:12px;font-weight:700;cursor:pointer;user-select:none;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.orbis-inactive-filter input{width:16px;height:16px;margin:0;accent-color:#13866d}.orbis-inactive-filter i{color:#1677d2}
.orbis-table-search{position:relative;width:100%}.orbis-table-search i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#6d858d;pointer-events:none}.orbis-table-search input{width:100%;height:38px;padding:7px 38px;border:0;border-radius:20px;background:#fff;color:#29434d;font-size:12px;box-shadow:0 3px 10px rgba(0,0,0,.16);outline:0}.orbis-table-search input:focus{box-shadow:0 0 0 3px rgba(255,255,255,.28),0 3px 10px rgba(0,0,0,.16)}
.orbis-table-actions{display:flex;justify-content:flex-end}
@media(max-width:767px){.orbis-table-toolbar{grid-template-columns:1fr auto}.orbis-table-search{grid-column:1/-1;grid-row:2}.orbis-table-actions{grid-column:2;grid-row:1}.orbis-inactive-filter{grid-column:1;grid-row:1}}
</style>
<!-- ════════════════ HEADER ORBIS ════════════════ -->
<section class="content-header">
  <div class="container-fluid">
    <div class="py-3 px-4 mb-1 rounded shadow-sm d-flex flex-wrap align-items-center text-white"
         style="background:linear-gradient(135deg,#0b6e4f,#17a589,#1abc9c)">
      <div class="mr-3 rounded-circle d-flex align-items-center justify-content-center"
           style="width:55px;height:55px;background:rgba(255,255,255,.18)">
        <i class="fas fa-circle-notch" style="font-size:1.6rem"></i>
      </div>
      <div class="flex-grow-1">
        <h2 class="mb-0 font-weight-bold">Cuentas Orbis</h2>
        <small style="opacity:.85">Gestión de usuarios y credenciales del sistema Orbis</small>
      </div>
      <ol class="breadcrumb bg-transparent mb-0 p-0">
        <li class="breadcrumb-item"><a href="../../home.php" class="text-white-50">Inicio</a></li>
        <li class="breadcrumb-item"><a href="index.php" class="text-white-50">Licencias</a></li>
        <li class="breadcrumb-item active text-white">Orbis</li>
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
        <div class="card border-0 shadow-sm text-white h-100"
             style="background:linear-gradient(135deg,#0b6e4f,#1abc9c)">
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
             style="background:linear-gradient(135deg,#667eea,#764ba2)">
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
      <div class="card-header orbis-table-toolbar py-2">
        <label class="orbis-inactive-filter" for="mostrarInactivosOrbis" title="Incluye las cuentas inactivas en la lista">
          <input type="checkbox" id="mostrarInactivosOrbis">
          <span>+ inactivos</span>
          <i class="fas fa-info-circle" aria-hidden="true"></i>
        </label>
        <div class="orbis-table-search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="buscarOrbis" placeholder="Buscar usuario, cuenta, &aacute;rea o utilidad..." autocomplete="off" aria-label="Buscar cuentas Orbis">
        </div>
        <div class="orbis-table-actions">
          <?php if (tiene_permiso('licencias', 'crear')): ?>
          <button class="btn btn-light btn-sm font-weight-bold"
                  data-toggle="modal" data-target="#modalAgregar">
            <i class="fas fa-plus mr-1"></i>Nuevo Usuario
          </button>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table id="tblOrbis" class="table table-hover mb-0">
          <thead>
            <tr style="background:#f4f6fb">
              <th class="border-top-0 text-center" style="width:45px">#</th>
              <th class="border-top-0">Nombre / Área</th>
              <th class="border-top-0">Cuenta Orbis</th>
              <th class="border-top-0">Contraseña</th>
              <th class="border-top-0">Utilidad</th>
              <th class="border-top-0 text-center">Estado</th>
              <th class="border-top-0 text-center" style="width:95px">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($usuarios as $U): ?>
          <tr data-status="<?= (int)$U['Estado'] === 1 ? 'ACTIVO' : 'INACTIVO' ?>">
            <td class="text-center"><code class="text-muted"><?= (int)$U['IdUsuOrbis'] ?></code></td>
            <td>
              <strong><?= htmlspecialchars($U['NomUsuario'], ENT_QUOTES) ?></strong><br>
              <small class="text-muted">
                <i class="fas fa-building mr-1"></i><?= htmlspecialchars($U['area'], ENT_QUOTES) ?>
                <?php if ($U['SubArea']): ?> / <?= htmlspecialchars($U['SubArea'], ENT_QUOTES) ?><?php endif; ?>
              </small>
            </td>
            <td><code class="small text-success"><?= htmlspecialchars($U['CuentaOrbis'], ENT_QUOTES) ?></code></td>
            <td>
              <?php
              $pass = $U['ContraOrbis'];
              if (!empty($pass)):
                  $safe = htmlspecialchars($pass, ENT_QUOTES);
              ?>
              <div class="d-flex align-items-center">
                <span class="pass-mask mr-1" style="font-family:monospace"
                      data-plain="<?= $safe ?>">••••••••</span>
                <button type="button" class="btn btn-link btn-sm p-0 btn-show-pass" title="Ver">
                  <i class="fas fa-eye text-muted" style="font-size:.8rem"></i>
                </button>
              </div>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($U['Utilidad'])): ?>
              <span class="badge badge-light border"><?= htmlspecialchars($U['Utilidad'], ENT_QUOTES) ?></span>
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
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
                        data-id="<?= (int)$U['IdUsuOrbis'] ?>"
                        data-userid="<?= (int)($U['UserId'] ?? 0) ?>"
                        data-nom="<?= htmlspecialchars($U['NomUsuario'], ENT_QUOTES) ?>"
                        data-area="<?= htmlspecialchars($U['area'], ENT_QUOTES) ?>"
                        data-sub="<?= htmlspecialchars($U['SubArea'], ENT_QUOTES) ?>"
                        data-cta="<?= htmlspecialchars($U['CuentaOrbis'], ENT_QUOTES) ?>"
                        data-pass="<?= htmlspecialchars($U['ContraOrbis'], ENT_QUOTES) ?>"
                        data-util="<?= htmlspecialchars($U['Utilidad'], ENT_QUOTES) ?>"
                        data-est="<?= (int)$U['Estado'] ?>"
                        title="Editar"><i class="fas fa-edit"></i></button>
                <?php endif; ?>
                <?php if (tiene_permiso('licencias', 'eliminar')): ?>
                <button class="btn btn-danger btn-eliminar"
                        data-id="<?= (int)$U['IdUsuOrbis'] ?>"
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

<!-- ════ Modal Agregar Orbis ════ -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-lg overflow-hidden">
      <div class="modal-header py-3 text-white"
           style="background:linear-gradient(135deg,#0b6e4f,#1abc9c)">
        <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i>Nuevo Usuario Orbis</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form method="post" action="LicenciasOrbis.php">
        <input type="hidden" name="action" value="agregar">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Colaborador <span class="text-danger">*</span></label>
                <select name="UserId" id="aUserId" class="form-control usuario-orbis-select" required>
                  <option value="">Buscar y seleccionar colaborador...</option>
                  <?php foreach ($usuariosMaestro as $persona): ?>
                  <option value="<?= (int)$persona['id'] ?>" data-area="<?= htmlspecialchars($persona['area'], ENT_QUOTES) ?>" data-subarea="<?= htmlspecialchars($persona['subarea'], ENT_QUOTES) ?>"><?= htmlspecialchars($persona['name'], ENT_QUOTES) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="small font-weight-bold">Área</label>
                <input type="text" name="area" id="aArea" class="form-control" readonly placeholder="Se completa automáticamente">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="small font-weight-bold">Sub-Área</label>
                <input type="text" name="SubArea" id="aSub" class="form-control" readonly placeholder="Se completa automáticamente">
              </div>
            </div>
            <div class="col-md-5">
              <div class="form-group">
                <label class="small font-weight-bold">Cuenta Orbis</label>
                <input type="text" name="CuentaOrbis" class="form-control" placeholder="Ej: mlopez">
              </div>
            </div>
            <div class="col-md-5">
              <div class="form-group">
                <label class="small font-weight-bold">Contraseña Orbis</label>
                <div class="input-group">
                  <input type="password" name="ContraOrbis" id="aContraOrb" class="form-control">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-toggle-inp" data-target="aContraOrb">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label class="small font-weight-bold">Estado</label>
                <select name="Estado" class="form-control">
                  <option value="1">Activo</option>
                  <option value="0">Inactivo</option>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group mb-0">
                <label class="small font-weight-bold">Utilidad / Descripción</label>
                <select name="Utilidad" class="form-control">
                  <option value="">Seleccionar utilidad / perfil...</option>
                  <?php foreach ($utilidades as $utilidad): ?><option value="<?= htmlspecialchars($utilidad, ENT_QUOTES) ?>"><?= htmlspecialchars(str_replace('_', ' ', $utilidad), ENT_QUOTES) ?></option><?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn font-weight-bold text-white"
                  style="background:linear-gradient(135deg,#0b6e4f,#1abc9c)">
            <i class="fas fa-save mr-1"></i>Registrar Usuario
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ════ Modal Editar Orbis ════ -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-lg overflow-hidden">
      <div class="modal-header py-3 text-white"
           style="background:linear-gradient(135deg,#f7971e,#ffd200)">
        <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-edit mr-2"></i>Editar Usuario Orbis</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form method="post" action="LicenciasOrbis.php">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Colaborador <span class="text-danger">*</span></label>
                <select name="UserId" id="eUserId" class="form-control usuario-orbis-select" required>
                  <option value="">Buscar y seleccionar colaborador...</option>
                  <?php foreach ($usuariosMaestro as $persona): ?>
                  <option value="<?= (int)$persona['id'] ?>" data-area="<?= htmlspecialchars($persona['area'], ENT_QUOTES) ?>" data-subarea="<?= htmlspecialchars($persona['subarea'], ENT_QUOTES) ?>"><?= htmlspecialchars($persona['name'], ENT_QUOTES) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="small font-weight-bold">Área</label>
                <input type="text" name="area" id="eArea" class="form-control" readonly>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="small font-weight-bold">Sub-Área</label>
                <input type="text" name="SubArea" id="eSub" class="form-control" readonly>
              </div>
            </div>
            <div class="col-md-5">
              <div class="form-group">
                <label class="small font-weight-bold">Cuenta Orbis</label>
                <input type="text" name="CuentaOrbis" id="eCta" class="form-control">
              </div>
            </div>
            <div class="col-md-5">
              <div class="form-group">
                <label class="small font-weight-bold">Contraseña Orbis</label>
                <div class="input-group">
                  <input type="text" name="ContraOrbis" id="ePass" class="form-control">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-toggle-inp" data-target="ePass">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label class="small font-weight-bold">Estado</label>
                <select name="Estado" id="eEst" class="form-control">
                  <option value="1">Activo</option>
                  <option value="0">Inactivo</option>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group mb-0">
                <label class="small font-weight-bold">Utilidad / Descripción</label>
                <select name="Utilidad" id="eUtil" class="form-control">
                  <option value="">Seleccionar utilidad / perfil...</option>
                  <?php foreach ($utilidades as $utilidad): ?><option value="<?= htmlspecialchars($utilidad, ENT_QUOTES) ?>"><?= htmlspecialchars(str_replace('_', ' ', $utilidad), ENT_QUOTES) ?></option><?php endforeach; ?>
                </select>
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

<form id="formEliminar" method="post" action="LicenciasOrbis.php" style="display:none">
  <input type="hidden" name="action" value="eliminar">
  <input type="hidden" name="id" id="deleteId">
</form>

<?php
ob_start();
?>
var mostrarInactivosOrbis = false;
$('.usuario-orbis-select').each(function() {
    var $select = $(this);
    $select.select2({
        width: '100%',
        placeholder: 'Buscar colaborador por nombre...',
        allowClear: true,
        dropdownParent: $select.closest('.modal')
    });
});

function completarAreaOrbis(select, areaSelector, subareaSelector) {
    var $option = $(select).find('option:selected');
    $(areaSelector).val($option.data('area') || '');
    $(subareaSelector).val($option.data('subarea') || '');
}

$('#aUserId').on('change', function() {
    completarAreaOrbis(this, '#aArea', '#aSub');
});
$('#eUserId').on('change', function() {
    completarAreaOrbis(this, '#eArea', '#eSub');
});

$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (!settings.nTable || settings.nTable.id !== 'tblOrbis') return true;
    if (mostrarInactivosOrbis) return true;
    return String($(settings.aoData[dataIndex].nTr).data('status')) === 'ACTIVO';
});

var tablaOrbis = $('#tblOrbis').DataTable({
    language: DATATABLES_SPANISH,
    order: [[1,'asc']],
    responsive: true,
    paging: false,
    dom: 'rtip'
});

$('#buscarOrbis').on('input', function() {
    tablaOrbis.search(this.value).draw();
});

$('#mostrarInactivosOrbis').on('change', function() {
    mostrarInactivosOrbis = this.checked;
    tablaOrbis.draw();
});

// Mostrar/ocultar contraseña en tabla
$(document).on('click', '.btn-show-pass', function() {
    var $sp    = $(this).prev('.pass-mask');
    var plain  = $sp.data('plain');
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

// Abrir editar
$(document).on('click', '.btn-editar', function() {
    var $b = $(this);
    $('#editId').val($b.data('id'));
    $('#eUserId').val(String($b.data('userid') || '')).trigger('change');
    if (!$b.data('userid')) {
        $('#eArea').val($b.data('area'));
        $('#eSub').val($b.data('sub'));
    }
    $('#eCta').val($b.data('cta'));
    $('#ePass').val($b.data('pass'));
    $('#eUtil').val($b.data('util'));
    $('#eEst').val($b.data('est'));
    $('#modalEditar').modal('show');
});

// Eliminar
$(document).on('click', '.btn-eliminar', function() {
    var id = $(this).data('id'), nom = $(this).data('nom');
    App.confirm('¿Eliminar usuario Orbis?', nom, function() {
        $('#deleteId').val(id);
        $('#formEliminar').submit();
    });
});
<?php
$inlineJs = ob_get_clean();
include_once(__DIR__ . '/../../layouts/footer.php');
?>
