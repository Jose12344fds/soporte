<?php
/**
 * Módulo Activos - Lista de Activos/Equipos
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Lista de Activos';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('activos', 'ver');

$extraCss = [
    ASSETS_URL . '/css/activos.css',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    'https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css',
];
$extraJs = [
    ASSETS_URL . '/vendor/axios/axios.min.js',
    ASSETS_URL . '/js/core/config.js',
    ASSETS_URL . '/js/core/api.js',
    ASSETS_URL . '/js/activos/activos-api.js?v=20261126b',
    ASSETS_URL . '/js/activos/activos-tabla.js',
    ASSETS_URL . '/js/activos/activos-asignacion.js',
    ASSETS_URL . '/js/activos/activos-evaluacion.js',
    ASSETS_URL . '/js/activos/activos.js',
];

include_once(__DIR__ . '/../../layouts/header.php');
?>



        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-desktop mr-2"></i>Gestión de Activos</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item active">Activos</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <div class="card">
                    <div class="card-header activos-card-header">
                        <div class="activos-title-wrap">
                       
                          <select id="activosLength" class="form-control form-control-sm" title="Cantidad de registros"><option>10</option><option>20</option><option>30</option><option>50</option></select>
                        </div>
                        <div class="activos-search"><i class="fas fa-search"></i><input type="search" id="buscarActivos" class="form-control form-control-sm" placeholder="Buscar activo, usuario, categoría..."></div>
                        <div class="card-tools">
                            <?php if (tiene_permiso('activos', 'crear')): ?>
                            <a href="agregar.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Agregar Activo
                            </a>
                            <?php endif; ?>
                            <a href="servicios.php" class="btn btn-info btn-sm" title="Administrar activos lógicos y servicios TI">
                                <i class="fas fa-cloud"></i> Servicios TI
                            </a>
                            <a href="aplicaciones.php" class="btn btn-warning btn-sm" title="Aplicaciones, contratos e instalaciones">
                                <i class="fas fa-shapes"></i> Aplicaciones
                            </a>
                            <a href="informacion.php" class="btn btn-secondary btn-sm" title="Bases de datos, planos, contratos y otra información">
                                <i class="fas fa-file-alt"></i> Información
                            </a>
                            <a href="copias.php" class="btn btn-secondary btn-sm" title="Políticas de respaldo, ejecuciones y pruebas de restauración">
                                <i class="fas fa-database"></i> Copias de seguridad
                            </a>
                            <a href="ubicaciones.php" class="btn btn-secondary btn-sm" title="Data center y ubicaciones físicas">
                                <i class="fas fa-building"></i> Ubicaciones
                            </a>
                            <a href="inventario_iso.php" class="btn btn-dark btn-sm" title="Inventario consolidado ISO 27001">
                                <i class="fas fa-clipboard-list"></i> Inventario ISO
                            </a>
                            <a href="../riesgos_sgsi/index.php" class="btn btn-danger btn-sm" title="Riesgos, controles y tratamientos del SGSI">
                                <i class="fas fa-shield-alt"></i> Matriz de riesgos
                            </a>
                            <a href="../../Reportes/ReporteFichaTecnica.php" target="_blank" class="btn btn-success btn-sm">
                                <i class="fas fa-file-excel"></i> Reporte Excel
                            </a>
                            <a href="../../Reportes/ReporteActivosCompleto.php" target="_blank" class="btn btn-outline-success btn-sm" title="Exporta activos, valoracion de seguridad e historial de asignaciones">
                                <i class="fas fa-database"></i> Excel Data Completa
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="tblActivos" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Equipo (Host)</th>
                                    <th>Código Contabilidad</th>
                                    <th>Tipo</th>
                                    <th>Categoría</th>
                                    <th>Área activo</th>
                                    <th>Criticidad</th>
                                    <th>Clasificación</th>
                                    <th>Usuario Asignado</th>
                                    <th>Características</th>
                                    <th class="text-center">Estado</th>
                                    <th>IP</th>
                                    <th>Ubicación</th>
                                    <th class="text-center" style="width: 120px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>

<!-- Modal Asignar Usuario -->
<div class="modal fade" id="modalAsignar" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-white font-weight-bold"><i class="fas fa-user-check mr-2"></i>Asignar o Liberar Equipo</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form method="post" action="<?=htmlspecialchars(BASE_URL,ENT_QUOTES,'UTF-8')?>/api/activos/equipos/asignar.php" id="formAsignarEquipo">
        <?php echo csrf_input(); ?>
        <input type="hidden" name="IdMaquina" id="asignarMaquinaId">
        <input type="hidden" name="HostEquipo" id="asignarHost">
        <input type="hidden" id="asignarUsuarioActualId">
        <div class="modal-body">
          <div class="alert alert-light border mb-3 py-2">
            <strong><i class="fas fa-desktop mr-1"></i>Equipo:</strong> <span id="asignarHostLabel" class="font-weight-bold text-primary"></span>
            <span class="badge badge-secondary ml-2">ID <span id="asignarIdLabel"></span></span>
          </div>
          <div class="assignment-current mb-3">
            <small class="text-muted text-uppercase font-weight-bold">Asignacion actual</small>
            <div class="user-name" id="asignarUsuarioActual">Sin asignar</div>
            <div class="text-muted small"><span id="asignarAreaActual">--</span><span id="asignarFechaActual"></span></div>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Responsable del equipo <span class="text-danger">*</span></label>
            <select name="user_id" id="selUsuario" class="form-control select2asig" required>
              <option value="">— Seleccionar usuario —</option>
              <option value="LIBRE" data-area="Equipo disponible">LIBRE / SIN USUARIO</option>
              
            </select>
            <div class="selected-user-preview" id="selectedUserPreview"></div>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Comentarios</label>
            <textarea name="Comentarios" id="asignarComentarios" class="form-control" maxlength="150" rows="2" placeholder="Motivo de entrega o reasignacion"></textarea>
            <small class="text-muted"><span id="asignarComentarioCount">0</span>/150</small>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold">Fecha de Asignación</label>
            <input type="date" name="Fecha" id="asignarFecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="assignment-form-card mt-3">
            <strong class="d-block mb-3"><i class="fas fa-shield-alt mr-1 text-info"></i>Datos y valoracion del activo</strong>
            <div class="row">
              <div class="col-md-6 form-group">
                <label>Area del activo <span class="text-danger">*</span></label>
                <select name="IdAreaActivo" id="asignarAreaActivo" class="form-control" required>
                  <option value="">Seleccionar...</option>
                  
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label>Criticidad automatica</label>
                <input type="text" id="asignarCriticidad" class="form-control font-weight-bold" value="SIN EVALUAR" readonly>
              </div>
              <div class="col-md-3 form-group">
                <label>Clasificacion <span class="text-danger">*</span></label>
                <select name="Clasificacion" id="asignarClasificacion" class="form-control" required>
                  <option value="">Seleccionar...</option><option value="PUBLICA">Publica</option><option value="INTERNA">Interna</option><option value="CONFIDENCIAL">Confidencial</option>
                </select>
              </div>
            </div>
            <div class="row">
              <?php foreach (['Confidencialidad','Disponibilidad','Integridad'] as $factor): ?>
              <div class="col-md-4 mb-2"><div class="asignar-factor" data-factor="<?= $factor ?>">
                <strong><?= $factor ?></strong><small>Seleccione un puntaje del 1 al 4.</small>
                <input type="hidden" class="asignar-puntaje" name="<?= $factor ?>" id="asignar<?= $factor ?>" required>
                <?php for($v=1;$v<=4;$v++): ?><button type="button" class="asignar-score" data-value="<?= $v ?>"><?= $v ?></button><?php endfor; ?>
              </div></div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="assignment-form-card mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <strong><i class="fas fa-history mr-1 text-info"></i>Ultimos movimientos</strong>
              <small class="text-muted">Historial MySQL</small>
            </div>
            <div class="assignment-history" id="asignarHistorial">
              <div class="text-center text-muted py-2"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando...</div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-info font-weight-bold">
            <i class="fas fa-save mr-1"></i>Guardar cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Evaluar Activo -->
<div class="modal fade" id="modalEvaluarActivo" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div><small class="text-white-50">Evaluación del activo</small><h5 class="modal-title font-weight-bold"><i class="fas fa-chart-line mr-2"></i>Calcular criticidad</h5></div>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body bg-light">
        <input type="hidden" id="evaluarActivoId">
        <div class="evaluacion-resumen mb-3"><small>Equipo seleccionado</small><strong class="d-block" id="evaluarActivoHost">-</strong></div>
        <div class="row">
          <div class="col-md-4 mb-3"><div class="evaluacion-factor" data-factor="Confidencialidad"><strong>Confidencialidad</strong><small>Impacto por acceso no autorizado.</small><div><?php for($v=1;$v<=4;$v++): ?><button type="button" class="evaluacion-score" data-value="<?=$v?>"><?=$v?></button><?php endfor; ?></div></div></div>
          <div class="col-md-4 mb-3"><div class="evaluacion-factor" data-factor="Disponibilidad"><strong>Disponibilidad</strong><small>Impacto si el activo no está disponible.</small><div><?php for($v=1;$v<=4;$v++): ?><button type="button" class="evaluacion-score" data-value="<?=$v?>"><?=$v?></button><?php endfor; ?></div></div></div>
          <div class="col-md-4 mb-3"><div class="evaluacion-factor" data-factor="Integridad"><strong>Integridad</strong><small>Impacto por modificación o daño.</small><div><?php for($v=1;$v<=4;$v++): ?><button type="button" class="evaluacion-score" data-value="<?=$v?>"><?=$v?></button><?php endfor; ?></div></div></div>
        </div>
        <div class="form-group">
          <label class="font-weight-bold d-block">Clasificación de la información</label>
          <div class="btn-group btn-group-toggle w-100" data-toggle="buttons" id="evaluarClasificacion">
            <label class="btn btn-outline-primary"><input type="radio" name="evaluar_clasificacion" value="PUBLICA"> Pública</label>
            <label class="btn btn-outline-primary"><input type="radio" name="evaluar_clasificacion" value="INTERNA"> Interna</label>
            <label class="btn btn-outline-primary"><input type="radio" name="evaluar_clasificacion" value="CONFIDENCIAL"> Confidencial</label>
          </div>
        </div>
        <div class="evaluacion-resultado"><div><small class="text-muted">Resultado automático</small><div class="evaluacion-nivel" id="evaluarNivel">SIN EVALUAR</div></div><span class="badge badge-secondary px-3 py-2" id="evaluarPuntaje">Puntaje máximo: 0</span></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success font-weight-bold" id="btnGuardarEvaluacion"><i class="fas fa-save mr-1"></i>Guardar evaluación</button></div>
    </div>
  </div>
</div>

<div id="activosConfig" hidden data-editar="<?=tiene_permiso('activos','editar')?'1':'0'?>" data-eliminar="<?=tiene_permiso('activos','eliminar')?'1':'0'?>"></div>
<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
