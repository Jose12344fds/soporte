<?php
$page_title = 'Agregar Activo';
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_permiso('activos', 'crear');
$activo_id = 0;
$extraCss = ['https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css'];
$extraJs = [ASSETS_URL . '/vendor/axios/axios.min.js', ASSETS_URL . '/js/core/config.js', ASSETS_URL . '/js/core/api.js', ASSETS_URL . '/js/activos/activos-api.js?v=20261126b', ASSETS_URL . '/js/activos/activos-formulario.js'];
include_once(__DIR__ . '/../../layouts/header.php');
?>

        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-plus-circle mr-2"></i>Agregar Activo</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Activos</a></li>
                            <li class="breadcrumb-item active">Agregar</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>

                <form method="post" action="<?=htmlspecialchars(BASE_URL,ENT_QUOTES,'UTF-8')?>/api/activos/equipos/registrar.php" data-activo="registrar" data-id="<?=$activo_id?>">
<?=csrf_input()?>
<div class="alert alert-danger d-none" data-form-error role="alert"></div>
<fieldset disabled>
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
                                                    <option value="COMPUTADORAS">Computadoras</option>
                                                    <option value="PERIFERICOS">Periféricos</option>
                                                    <option value="INFRAESTRUCTURA">Infraestructura</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Hostname <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="Host" placeholder="TM-XX-PCXXX" required>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Código de Contabilidad</label>
                                                <input type="text" class="form-control" name="CodigoContabilidad" maxlength="100" placeholder="Ej. ACT-2026-001">
                                                <small class="form-text text-muted">Código proporcionado por Contabilidad.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Área del activo <span class="text-danger">*</span></label>
                                        <select class="form-control" name="IdAreaActivo" required>
                                            <option value="">Seleccionar...</option>
                                            
                                        </select>
                                    </div>
                                    <div class="card border-info mb-3">
                                      <div class="card-header py-2 bg-info text-white"><strong><i class="fas fa-shield-alt mr-1"></i>Valoración de seguridad</strong></div>
                                      <div class="card-body py-3">
                                        <div class="row">
                                          <?php foreach (['Confidencialidad','Disponibilidad','Integridad'] as $campoSeg): ?>
                                          <div class="col-md-4"><div class="form-group">
                                            <label><?php echo $campoSeg; ?> <span class="text-danger">*</span></label>
                                            <select class="form-control puntaje-seguridad" name="<?php echo $campoSeg; ?>" required>
                                              <option value="">Puntaje...</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option>
                                            </select>
                                          </div></div>
                                          <?php endforeach; ?>
                                        </div>
                                        <div class="form-group"><label>Criticidad automática</label><input id="Criticidad" class="form-control font-weight-bold" value="Pendiente" readonly></div>
                                        <label class="d-block">Clasificación <span class="text-danger">*</span></label>
                                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                          <?php foreach (['PUBLICA'=>'Pública','INTERNA'=>'Interna','CONFIDENCIAL'=>'Confidencial'] as $valor=>$texto): ?>
                                          <label class="btn btn-outline-primary"><input type="radio" name="Clasificacion" value="<?php echo $valor; ?>" required> <?php echo $texto; ?></label>
                                          <?php endforeach; ?>
                                        </div>
                                      </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Modelo de Placa</label>
                                        <input type="text" class="form-control" name="Modelo_Placa" placeholder="Ej: GIGABYTE B360 HD3">
                                    </div>
                                    <div class="form-group">
                                        <label>Procesador</label>
                                        <input type="text" class="form-control" name="Procesador" placeholder="Ej: Intel Core i7-8700K">
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Memoria RAM (GB)</label>
                                                <input type="number" class="form-control" name="Mem_Ram" placeholder="16">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Tipo de Disco</label>
                                                <select class="form-control" name="Tipo_Disco">
                                                    <option value="">Seleccionar...</option>
                                                    <option value="SOLIDO">SSD (Sólido)</option>
                                                    <option value="MECANICO">HDD (Mecánico)</option>
                                                    <option value="NVME">NVMe</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Tamaño Disco (GB)</label>
                                                <input type="number" class="form-control" name="Tamano_Disco" placeholder="480">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Modelo Pantalla</label>
                                                <input type="text" class="form-control" name="Mod_Pantalla" placeholder="LG 24MK430">
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
                                                <input type="text" class="form-control" name="Ip" placeholder="192.168.1.100">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Fecha de Registro</label>
                                                <input type="date" class="form-control" name="Fecha_Maqui" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Estado del Equipo</label>
                                        <select class="form-control" name="EstadoMaquina">
                                            <option value="OPERATIVO">OPERATIVO</option>
                                            <option value="EN MANTENIMIENTO">EN MANTENIMIENTO</option>
                                            <option value="DE BAJA">DE BAJA</option>
                                            <option value="EN ALMACEN">EN ALMACÉN</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Ubicación <span class="text-danger">*</span></label>
                                        <select class="form-control" name="Ubicacion" required>
                                            <option value="DOMICILIO">DOMICILIO</option>
                                            <option value="EMPRESA" selected>EMPRESA</option>
                                            <option value="PUNO">PUNO</option>
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
                                    <button type="submit" name="add_activo" class="btn btn-primary float-right">
                                        <i class="fas fa-save"></i> Guardar Activo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset></form>

            </div>
        </section>



<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
