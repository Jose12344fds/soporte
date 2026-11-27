<?php
$page_title = 'Editar Activo';
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_permiso('activos', 'editar');
$activo_id = (int)($_GET['id'] ?? 0);
$activo = array (
  'IdTipoActivo' => '',
  'CategoriaEquipo' => '',
  'Host' => '',
  'CodigoContabilidad' => '',
  'IdAreaActivo' => '',
  'Modelo_Placa' => '',
  'Procesador' => '',
  'Mem_Ram' => 0,
  'Tipo_Disco' => '',
  'Tamano_Disco' => 0,
  'Mod_Pantalla' => '',
  'Ip' => '',
  'Fecha_Maqui' => '',
  'EstadoMaquina' => '',
  'Ubicacion' => '',
  'Criticidad' => '',
  'Clasificacion' => '',
  'IdMaquina' => 0,
);
$extraCss = ['https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css'];
$extraJs = [ASSETS_URL . '/vendor/axios/axios.min.js', ASSETS_URL . '/js/core/config.js', ASSETS_URL . '/js/core/api.js', ASSETS_URL . '/js/activos/activos-api.js?v=20261126b', ASSETS_URL . '/js/activos/activos-formulario.js'];
include_once(__DIR__ . '/../../layouts/header.php');
?>

        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-edit mr-2"></i>Editar Activo</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Activos</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg ?? ''); ?>

                <form method="post" action="<?=htmlspecialchars(BASE_URL,ENT_QUOTES,'UTF-8')?>/api/activos/equipos/actualizar.php" data-activo="actualizar" data-id="<?=$activo_id?>">
<?=csrf_input()?>
<div class="alert alert-danger d-none" data-form-error role="alert"></div>
<fieldset disabled>
                    <input type="hidden" name="IdMaquina" value="<?php echo $activo_id; ?>">
                    <div class="row">
                        <!-- Columna Izquierda -->
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-desktop mr-2"></i>Datos del Equipo</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Tipo de Activo <span class="text-danger">*</span></label>
                                                <select class="form-control" name="IdTipoActivo" id="Tipo_Activo" required>
                                                    <option value="">Seleccionar...</option>
                                                    
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Categoría de equipo <span class="text-danger">*</span></label>
                                                <select class="form-control" name="CategoriaEquipo" id="CategoriaEquipo" required>
                                                    <option value="">Seleccionar...</option>
                                                    <option value="COMPUTADORAS" <?php echo (($activo['CategoriaEquipo'] ?? '') === 'COMPUTADORAS') ? 'selected' : ''; ?>>Computadoras</option>
                                                    <option value="PERIFERICOS" <?php echo (($activo['CategoriaEquipo'] ?? '') === 'PERIFERICOS') ? 'selected' : ''; ?>>Periféricos</option>
                                                    <option value="INFRAESTRUCTURA" <?php echo (($activo['CategoriaEquipo'] ?? '') === 'INFRAESTRUCTURA') ? 'selected' : ''; ?>>Infraestructura</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Hostname <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="Host" value="<?php echo remove_junk($activo['Host']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Código de Contabilidad</label>
                                                <input type="text" class="form-control" name="CodigoContabilidad" maxlength="100" value="<?php echo htmlspecialchars((string)($activo['CodigoContabilidad'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ej. ACT-2026-001">
                                                <small class="form-text text-muted">Código proporcionado por Contabilidad.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Área del activo <span class="text-danger">*</span></label>
                                        <select class="form-control select2-area" name="IdAreaActivo" id="IdAreaActivo" required>
                                            <option value="">Seleccionar...</option>
                                            
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Modelo de Placa</label>
                                        <input type="text" class="form-control" name="Modelo_Placa" value="<?php echo remove_junk($activo['Modelo_Placa']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Procesador</label>
                                        <input type="text" class="form-control" name="Procesador" value="<?php echo remove_junk($activo['Procesador']); ?>">
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Memoria RAM (GB)</label>
                                                <input type="number" class="form-control" name="Mem_Ram" value="<?php echo (int)$activo['Mem_Ram']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Tipo de Disco</label>
                                                <select class="form-control" name="Tipo_Disco">
                                                    <option value="">Seleccionar...</option>
                                                    <option value="SOLIDO" <?php echo ($activo['Tipo_Disco'] == 'SOLIDO') ? 'selected' : ''; ?>>SSD (Sólido)</option>
                                                    <option value="MECANICO" <?php echo ($activo['Tipo_Disco'] == 'MECANICO') ? 'selected' : ''; ?>>HDD (Mecánico)</option>
                                                    <option value="NVME" <?php echo ($activo['Tipo_Disco'] == 'NVME') ? 'selected' : ''; ?>>NVMe</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Tamaño Disco (GB)</label>
                                                <input type="number" class="form-control" name="Tamano_Disco" value="<?php echo (int)$activo['Tamano_Disco']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Modelo Pantalla</label>
                                                <input type="text" class="form-control" name="Mod_Pantalla" value="<?php echo remove_junk($activo['Mod_Pantalla']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Columna Derecha -->
                        <div class="col-md-6">
                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Red y Estado</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Dirección IP</label>
                                                <input type="text" class="form-control" name="Ip" value="<?php echo remove_junk($activo['Ip']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Fecha de Registro</label>
                                                <input type="date" class="form-control" name="Fecha_Maqui" value="<?php echo $activo['Fecha_Maqui']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Estado del Equipo</label>
                                        <select class="form-control" name="EstadoMaquina">
                                            <option value="OPERATIVO" <?php echo ($activo['EstadoMaquina'] == 'OPERATIVO') ? 'selected' : ''; ?>>OPERATIVO</option>
                                            <option value="EN MANTENIMIENTO" <?php echo ($activo['EstadoMaquina'] == 'EN MANTENIMIENTO') ? 'selected' : ''; ?>>EN MANTENIMIENTO</option>
                                            <option value="DE BAJA" <?php echo ($activo['EstadoMaquina'] == 'DE BAJA') ? 'selected' : ''; ?>>DE BAJA</option>
                                            <option value="EN ALMACEN" <?php echo ($activo['EstadoMaquina'] == 'EN ALMACEN') ? 'selected' : ''; ?>>EN ALMACÉN</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Ubicación <span class="text-danger">*</span></label>
                                        <select class="form-control" name="Ubicacion" required>
                                            <option value="DOMICILIO" <?php echo ($activo['Ubicacion'] == 'DOMICILIO') ? 'selected' : ''; ?>>DOMICILIO</option>
                                            <option value="EMPRESA" <?php echo ($activo['Ubicacion'] == 'EMPRESA') ? 'selected' : ''; ?>>EMPRESA</option>
                                            <option value="PUNO" <?php echo ($activo['Ubicacion'] == 'PUNO') ? 'selected' : ''; ?>>PUNO</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                                <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-key mr-2"></i>Licencias</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Licencia Office</label>
                                        <select class="form-control select2" name="office" id="office"><option value="">Sin licencia</option></select>
                                    </div>
                                    <div class="form-group">
                                        <label>Licencia Windows</label>
                                        <select class="form-control select2" name="windows" id="windows"><option value="">Sin licencia</option></select>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-outline card-info">
                              <div class="card-header"><h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Valoración de seguridad</h3></div>
                              <div class="card-body">
                                <p class="text-muted small mb-3">Seleccione un nivel del 1 al 4 para cada criterio. En activos hist&oacute;ricos puede dejar este bloque pendiente y editar solamente el &aacute;rea u otros datos.</p>
                                <?php foreach (['Confidencialidad','Disponibilidad','Integridad'] as $campoSeg): $actual=(int)($activo[$campoSeg] ?? 0); ?>
                                <div class="form-group d-flex align-items-center justify-content-between border-bottom pb-2">
                                  <label class="mb-0 mr-3"><?php echo $campoSeg; ?></label>
                                  <input type="hidden" name="<?php echo $campoSeg; ?>" class="puntaje-seguridad" value="<?php echo $actual ?: ''; ?>">
                                  <div class="btn-group btn-group-sm grupo-puntaje" role="group">
                                    <?php for($p=1;$p<=4;$p++): ?><button type="button" class="btn btn-outline-info <?php echo $actual===$p?'active':''; ?>" data-value="<?php echo $p; ?>"><?php echo $p; ?></button><?php endfor; ?>
                                  </div>
                                </div>
                                <?php endforeach; ?>
                                <div class="row align-items-end">
                                  <div class="col-md-6"><div class="form-group mb-md-0"><label>Criticidad automática</label><input id="Criticidad" class="form-control font-weight-bold" value="<?php echo htmlspecialchars($activo['Criticidad'] ?? 'Pendiente', ENT_QUOTES); ?>" readonly></div></div>
                                  <div class="col-md-6"><div class="form-group mb-0"><label>Clasificación</label><select class="form-control" name="Clasificacion"><option value="">Pendiente</option><?php foreach (['PUBLICA'=>'Pública','INTERNA'=>'Interna','CONFIDENCIAL'=>'Confidencial'] as $valor=>$texto): ?><option value="<?php echo $valor; ?>" <?php echo (($activo['Clasificacion'] ?? '')===$valor)?'selected':''; ?>><?php echo $texto; ?></option><?php endforeach; ?></select></div></div>
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
                                    <button type="submit" name="update_activo" class="btn btn-primary float-right">
                                        <i class="fas fa-save"></i> Actualizar Activo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset></form>

            </div>
        </section>



<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
