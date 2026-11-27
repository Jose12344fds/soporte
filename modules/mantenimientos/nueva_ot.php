<?php
/**
 * Módulo Mantenimientos — Nueva / Editar Orden de Trabajo
 */
$page_title = 'Nueva Orden de Trabajo';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_permiso('mantenimientos', 'ver');

$docentry  = isset($_GET['docentry']) ? (int)$_GET['docentry'] : 0;
$prog_code = isset($_GET['prog'])     ? trim($_GET['prog'])     : '';
$es_edicion = ($docentry > 0);

if ($es_edicion) {
    $page_title = 'Editar Orden de Trabajo';
}

$extraCss = ['https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
             'https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css'];

include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
.ot-shell { max-width: 1480px; margin: 0 auto; }
.ot-hero { background:linear-gradient(135deg,#12372a 0%,#1f7a45 58%,#20a37a 100%); padding:18px 0 14px; }
.ot-panel { border:1px solid #dfe7e3; border-left:4px solid #28a745; border-radius:8px; box-shadow:0 8px 22px rgba(23,52,39,.07); }
.ot-panel .card-header { border-bottom:1px solid #edf1ef; }
.equipment-toolbar { display:grid; grid-template-columns:minmax(320px,1fr) auto; gap:10px; align-items:end; }
.equipment-toolbar label { font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:#5d6b63; margin-bottom:5px; }
.equipment-toolbar .btn { height:38px; white-space:nowrap; }
.equipment-toolbar .equipment-search { min-width:0; }
.equipment-toolbar .select2-container { width:100% !important; }
.equipment-toolbar .select2-selection--single { min-height:38px !important; display:flex !important; align-items:center; }
.equipment-toolbar .select2-selection__rendered { line-height:1.35 !important; white-space:normal !important; padding-top:6px !important; padding-bottom:6px !important; padding-right:32px !important; }
.equipment-toolbar .select2-selection__arrow { height:36px !important; }
.equipment-hint { display:flex; align-items:center; gap:8px; color:#6c757d; font-size:12px; margin-top:8px; }
.equipment-hint i { color:#1f7a45; }
.modal-equipo .modal-content { border:0; border-radius:10px; overflow:hidden; box-shadow:0 20px 60px rgba(16,36,28,.28); }
.modal-equipo .modal-header { background:linear-gradient(135deg,#12372a,#1f7a45); border:0; padding:18px 22px; }
.modal-equipo .modal-title { font-weight:800; letter-spacing:.02em; }
.modal-equipo .modal-body { background:#f7faf8; padding:22px; }
.modal-equipo .form-card { background:#fff; border:1px solid #e1e8e4; border-radius:8px; padding:16px; }
.modal-equipo label { font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:#42524a; margin-bottom:6px; }
.modal-equipo .form-control { border-radius:7px; border-color:#cfdad4; }
.modal-equipo .form-control:focus { border-color:#2f9e5f; box-shadow:0 0 0 .15rem rgba(40,167,69,.18); }
.modal-equipo .modal-footer { background:#fff; border-top:1px solid #e7eee9; }
.evidence-drop { position:relative; border:2px dashed #8bbfa1; border-radius:12px; background:linear-gradient(135deg,#f3fbf6,#eef8ff); padding:22px; text-align:center; cursor:pointer; transition:.2s ease; }
.evidence-drop:hover, .evidence-drop.is-dragging { border-color:#1f9d55; background:#edf9f1; transform:translateY(-1px); }
.evidence-drop input { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
.evidence-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:14px; }
.evidence-card { position:relative; border:1px solid #dce8e1; border-radius:10px; overflow:hidden; background:#fff; box-shadow:0 4px 14px rgba(26,61,43,.07); }
.evidence-card:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(26,61,43,.14); transition:.2s ease; }
.evidence-thumb { height:160px; width:100%; object-fit:cover; background:#edf2ef; display:block; cursor:zoom-in; }
.evidence-file-icon { height:160px; display:flex; align-items:center; justify-content:center; background:#edf2ef; color:#5d7768; font-size:40px; }
.evidence-meta { padding:11px; font-size:11px; min-width:0; }
.evidence-name { font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.evidence-note { color:#52645a; margin-top:4px; min-height:16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.evidence-actions { position:absolute; top:7px; right:7px; display:flex; gap:5px; }
.evidence-actions .btn { width:30px; height:30px; padding:0; border-radius:50%; box-shadow:0 2px 7px rgba(0,0,0,.2); }
.evidence-viewer .modal-content { background:#101713; border:0; border-radius:14px; overflow:hidden; box-shadow:0 25px 80px rgba(0,0,0,.5); }
.evidence-viewer .modal-header { border:0; background:#17231d; color:#fff; padding:13px 18px; }
.evidence-viewer-stage { min-height:65vh; display:flex; align-items:center; justify-content:center; position:relative; padding:18px 64px; background:radial-gradient(circle at center,#26372e,#0c110e 72%); }
.evidence-viewer-image { max-width:100%; max-height:68vh; object-fit:contain; border-radius:6px; box-shadow:0 12px 40px rgba(0,0,0,.4); }
.evidence-viewer-nav { position:absolute; top:50%; transform:translateY(-50%); width:44px; height:56px; border:0; border-radius:9px; background:rgba(255,255,255,.14); color:#fff; font-size:24px; }
.evidence-viewer-nav:hover { background:rgba(40,167,69,.8); }
.evidence-viewer-prev { left:12px; }
.evidence-viewer-next { right:12px; }
.evidence-viewer-footer { background:#17231d; color:#fff; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:16px; }
.evidence-viewer-caption { min-width:0; }
.evidence-viewer-caption strong, .evidence-viewer-caption span { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.evidence-viewer-caption span { color:#aebcb4; font-size:12px; margin-top:3px; }
.evidence-empty { border:1px dashed #b8ccc0; border-radius:11px; padding:28px; text-align:center; background:#f8fbf9; color:#66776d; }
.evidence-pending { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; margin-bottom:15px; border:1px solid #ffe08a; border-radius:9px; background:#fff9e8; }
.evidence-upload-modal .modal-content { border:0; border-radius:13px; overflow:hidden; box-shadow:0 22px 65px rgba(14,47,30,.3); }
.evidence-upload-modal .modal-header { border:0; padding:17px 21px; background:linear-gradient(135deg,#176338,#20a064); color:#fff; }
.evidence-upload-modal .modal-body { padding:22px; background:#f7faf8; }
.pdf-drop { position:relative; border:2px dashed #e2a84a; border-radius:12px; background:#fff9ec; padding:18px; text-align:center; cursor:pointer; }
.pdf-drop:hover { border-color:#d98b16; background:#fff5dc; }
.pdf-drop input { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
.otc-card{border:1px solid #dce7ed;border-left:4px solid #187b86!important}.otc-card .card-header{background:#fff}.otc-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:13px}.otc-stat{padding:11px;border:1px solid #e0e8ed;border-radius:9px;background:#f8fbfc}.otc-stat small{display:block;color:#718390;font-size:10px;text-transform:uppercase}.otc-stat strong{color:#183f55;font-size:15px}.otc-table th{border-top:0!important;background:#edf3f6;color:#526978;font-size:10px;text-transform:uppercase}.otc-table td{font-size:12px;vertical-align:middle}.otc-modal .modal-content{overflow:hidden;border:0;border-radius:13px;box-shadow:0 24px 70px rgba(16,47,64,.3)}.otc-modal .modal-header{border:0;background:linear-gradient(120deg,#143f5d,#197d86);color:#fff}.otc-modal .modal-body{background:#f4f8fa}.otc-form{padding:16px;border:1px solid #dce6ea;border-radius:11px;background:#fff}.otc-modal label{color:#526979;font-size:11px;font-weight:800;text-transform:uppercase}.otc-modal .form-control{border-color:#d4e0e5;border-radius:8px}.otc-pdf{position:relative;padding:13px;border:1px dashed #d48b8b;border-radius:9px;background:#fff7f7}.otc-pdf input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}.otc-total{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-radius:9px;background:#174d67;color:#fff}.otc-total small{color:#c9dce5}.otc-actions{white-space:nowrap}
@media (max-width: 768px) {
    .equipment-toolbar { grid-template-columns:1fr; }
    .equipment-toolbar .btn { width:100%; }
    .otc-summary{grid-template-columns:1fr}
}
</style>

<!-- Hero Banner -->
<div class="ot-hero">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <div class="mr-3">
                <div style="background:rgba(255,255,255,0.18); border-radius:50%; width:56px; height:56px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-<?= $es_edicion ? 'edit' : 'plus-circle' ?> fa-2x text-white"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <h3 class="mb-0 text-white font-weight-bold">
                    <?= $es_edicion
                        ? 'Editar OT &nbsp;<span class="badge badge-light text-success">#' . $docentry . '</span>'
                        : 'Nueva Orden de Trabajo' ?>
                </h3>
                <small class="text-white-50">
                    <?= $es_edicion ? 'Modifica los datos de la orden de trabajo guardada' : 'Completa los campos para registrar una nueva OT' ?>
                </small>
            </div>
            <div>
                <ol class="breadcrumb bg-transparent mb-0 py-0 pr-0" style="font-size:13px;">
                    <li class="breadcrumb-item"><a href="../../home.php" class="text-white-50">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="index.php" class="text-white-50">Mantenimientos</a></li>
                    <li class="breadcrumb-item text-white active"><?= $es_edicion ? 'Editar OT' : 'Nueva OT' ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content mt-3">
    <div class="container-fluid">
        <form id="formOT" autocomplete="off">
            <input type="hidden" id="docentry" value="<?= $docentry ?>">

            <div class="row">
                <!-- ═══════════════════════ COLUMNA PRINCIPAL ═══════════════════════ -->
                <div class="col-md-8">

                    <!-- ── CARD: Equipo ── -->
                    <div class="card ot-panel">
                        <div class="card-header bg-white border-bottom">
                            <h3 class="card-title font-weight-bold text-success mb-0">
                                <i class="fas fa-truck-pickup mr-2"></i>Equipo
                                <span class="text-danger ml-1" title="Requerido">*</span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="equipment-toolbar">
                                <div class="equipment-search">
                                    <label for="selectEquipo">Buscar equipo</label>
                                    <select id="selectEquipo" name="itemcode" class="form-control" style="width:100%;" required>
                                        <option value="">Buscar por codigo, nombre, inventario o serie</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-outline-success btn-sm" id="btnNuevoEquipo">
                                    <i class="fas fa-plus mr-1"></i>Agregar equipo
                                </button>
                            </div>
                            <div class="equipment-hint">
                                <i class="fas fa-search"></i>
                                <span>Escribe codigo, nombre, inventario o serie del equipo.</span>
                            </div>
                            <!-- Info equipo: 4 cajas de color -->
                            <div id="infoEquipo" class="mt-3" style="display:none;">
                                <div class="row no-gutters">
                                    <div class="col-6 col-md-3 p-1">
                                        <div class="text-center p-2 rounded h-100" style="background:#e8f5e9; border:1px solid #c8e6c9;">
                                            <div class="text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.5px;">
                                                <i class="fas fa-barcode mr-1"></i>Inventario
                                            </div>
                                            <strong id="eqInventario" class="text-dark" style="font-size:13px;">—</strong>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 p-1">
                                        <div class="text-center p-2 rounded h-100" style="background:#e3f2fd; border:1px solid #bbdefb;">
                                            <div class="text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.5px;">
                                                <i class="fas fa-fingerprint mr-1"></i>Serie
                                            </div>
                                            <strong id="eqSerie" class="text-dark" style="font-size:13px;">—</strong>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 p-1">
                                        <div class="text-center p-2 rounded h-100" style="background:#fff3e0; border:1px solid #ffe0b2;">
                                            <div class="text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.5px;">
                                                <i class="fas fa-tag mr-1"></i>Marca
                                            </div>
                                            <strong id="eqMarca" class="text-dark" style="font-size:13px;">—</strong>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 p-1">
                                        <div class="text-center p-2 rounded h-100" style="background:#f3e5f5; border:1px solid #e1bee7;">
                                            <div class="text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.5px;">
                                                <i class="fas fa-cogs mr-1"></i>Modelo
                                            </div>
                                            <strong id="eqModelo" class="text-dark" style="font-size:13px;">—</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── CARD: Datos Mantenimiento ── -->
                    <div class="card shadow-sm" style="border:1px solid #dee2e6; border-left:4px solid #007bff;">
                        <div class="card-header bg-white border-bottom">
                            <h3 class="card-title font-weight-bold text-primary mb-0">
                                <i class="fas fa-clipboard-list mr-2"></i>Datos del Mantenimiento
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark">
                                            <i class="fas fa-tag mr-1 text-primary"></i>
                                            Tipo de Mantenimiento <span class="text-danger">*</span>
                                        </label>
                                        <select id="selectTipo" name="tipomant" class="form-control" required>
                                            <option value="">— Seleccionar —</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark">
                                            <i class="fas fa-hard-hat mr-1 text-primary"></i>
                                            Técnico Responsable
                                        </label>
                                        <select id="selectTecnico" name="tecnico" class="form-control">
                                            <option value="">— Sin asignar —</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="detalleSvtGroup" style="display:none;">
                                <label class="font-weight-bold text-dark" for="detalleSvt">
                                    <i class="fas fa-id-card mr-1 text-info"></i>
                                    Nombre o información de SVT <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="detalleSvt" name="detalle_svt" class="form-control" maxlength="254"
                                       placeholder="Ingrese el nombre o la información correspondiente a SVT">
                                <small class="text-muted"><span id="detalleSvtCount">0</span>/254 caracteres. Se guardará en el campo U_DetalleSVT de SAP.</small>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold text-dark">
                                    <i class="fas fa-link mr-1 text-primary"></i>
                                    Programación asociada
                                    <span class="text-muted font-weight-normal ml-1" style="font-size:12px;">(opcional)</span>
                                </label>
                                <select id="selectProg" name="progmant" class="form-control">
                                    <option value="">— Sin programación —</option>
                                </select>
                                <small class="text-muted">Selecciona el equipo primero para ver programaciones disponibles</small>
                            </div>

                            <!-- Rango de fechas -->
                            <div class="p-3 rounded" style="background:#f8f9fa; border:1px solid #e9ecef;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-md-0">
                                            <label class="font-weight-bold text-dark">
                                                <i class="fas fa-calendar-alt mr-1 text-success"></i>
                                                Fecha Inicio <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" id="fechaInicio" name="fechainicio"
                                                   class="form-control" required
                                                   value="<?= date('Y-m-d') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold text-dark">
                                                <i class="fas fa-calendar-check mr-1 text-info"></i>
                                                Fecha Fin
                                                <span class="text-muted font-weight-normal ml-1" style="font-size:12px;">(opcional)</span>
                                            </label>
                                            <input type="date" id="fechaFin" name="fechafin" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group mb-md-0">
                                            <label class="font-weight-bold text-dark">
                                                <i class="fas fa-clock mr-1 text-primary"></i>
                                                Hora Inicio
                                            </label>
                                            <input type="time" id="horaInicio" name="horainicio" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold text-dark">
                                                <i class="fas fa-clock mr-1 text-primary"></i>
                                                Hora Fin
                                            </label>
                                            <input type="time" id="horaFin" name="horafin" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── CARD: Descripción ── -->
                    <div class="card shadow-sm" style="border:1px solid #dee2e6; border-left:4px solid #fd7e14;">
                        <div class="card-header bg-white border-bottom">
                            <h3 class="card-title font-weight-bold mb-0" style="color:#fd7e14;">
                                <i class="fas fa-wrench mr-2"></i>Descripción del Trabajo
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="font-weight-bold text-dark">
                                    Título de la OT <span class="text-muted font-weight-normal">(máximo 150 caracteres)</span>
                                </label>
                                <input type="text" id="titulo" name="titulo" class="form-control font-weight-bold"
                                       maxlength="150" placeholder="Ej.: Reparación del sistema de accionamiento">
                                <small class="text-muted"><span id="tituloCount">0</span>/150 · Identificación breve del trabajo.</small>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold text-dark">
                                    Trabajo Realizado <span class="text-danger">*</span>
                                </label>
                                <textarea id="trabajo" name="trabajo" class="form-control" rows="4"
                                          placeholder="Describe detalladamente el trabajo de mantenimiento realizado..." required></textarea>
                            </div>
                            <div class="form-group mb-0">
                                <label class="font-weight-bold text-dark">Observaciones</label>
                                <textarea id="observacion" name="observacion" class="form-control" rows="2"
                                          placeholder="Piezas utilizadas, notas adicionales, próxima revisión..."></textarea>
                            </div>
                            <div class="form-group mt-3 mb-0">
                                <label class="font-weight-bold text-dark">
                                    <i class="fas fa-route text-warning mr-1"></i> Plan de acción
                                </label>
                                <textarea id="planAccion" name="plan_accion" class="form-control" rows="5" maxlength="30000"
                                          placeholder="Describa detalladamente las acciones posteriores, responsables, controles o mejoras que deben ejecutarse..."></textarea>
                                <small class="form-text text-muted">
                                    Cuando este campo tenga contenido, la OT aparecerá en la bandeja de Planes de Acción para generar y dar seguimiento a su orden relacionada.
                                </small>
                                <div id="planAccionConfig" class="small mt-2" style="display:none;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD: Repuestos y servicios -->
                    <div class="card shadow-sm otc-card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title font-weight-bold mb-0" style="color:#187b86;"><i class="fas fa-boxes mr-2"></i>Repuestos y servicios</h3>
                            <?php if ($es_edicion): ?>
                            <button type="button" class="btn btn-info btn-sm font-weight-bold" id="btnAbrirCostosOt"><i class="fas fa-plus mr-1"></i>Gestionar</button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (!$es_edicion): ?>
                            <div class="alert alert-light border mb-0"><i class="fas fa-info-circle text-info mr-1"></i>Guarda primero la OT para poder registrar repuestos, servicios y sus documentos.</div>
                            <?php else: ?>
                            <div id="otcPageLoading" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando desde SAP...</div>
                            <div id="otcPageContent" style="display:none;">
                                <div class="otc-summary">
                                    <div class="otc-stat"><small>Registros SAP</small><strong id="otcPageCount">0</strong></div>
                                    <div class="otc-stat"><small>Total soles</small><strong id="otcPagePen">S/ 0.00</strong></div>
                                    <div class="otc-stat"><small>Total dolares</small><strong id="otcPageUsd">US$ 0.00</strong></div>
                                </div>
                                <div class="table-responsive" id="otcPageTableWrap" style="display:none;">
                                    <table class="table table-sm table-hover otc-table mb-0"><thead><tr><th>Tipo</th><th>Detalle</th><th>Cantidad</th><th>Total</th><th>Compra SAP</th><th>PDF</th></tr></thead><tbody id="otcPageBody"></tbody></table>
                                </div>
                                <div id="otcPageEmpty" class="text-center text-muted py-3" style="display:none;">No hay repuestos ni servicios registrados.</div>
                            </div>
                            <div id="otcPageError" class="alert alert-warning mb-0" style="display:none;"></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CARD: Evidencias fotograficas -->
                    <div class="card shadow-sm" style="border:1px solid #dce8e1; border-left:4px solid #17a2b8;">
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                            <h3 class="card-title font-weight-bold text-info mb-0">
                                <i class="fas fa-paperclip mr-2"></i>Evidencias y documentos
                            </h3>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-info mr-2" id="evidenceCount">0 fotos</span>
                                <span class="badge badge-danger mr-2" id="documentCount">Sin documentos</span>
                                <button type="button" class="btn btn-info btn-sm font-weight-bold" data-toggle="modal" data-target="#modalEvidenceUpload">
                                    <i class="fas fa-plus mr-1"></i>Agregar archivos
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="evidenceAlert" class="alert alert-warning" style="display:none;"></div>
                            <div id="evidencePendingWrap" class="evidence-pending" style="display:none;">
                                <div>
                                    <strong class="text-warning"><i class="fas fa-clock mr-1"></i><span id="evidencePendingCount">0</span> foto(s) pendientes</strong>
                                    <div class="text-muted small">Se cargarán cuando guardes la orden de trabajo.</div>
                                </div>
                                <button type="button" class="btn btn-outline-warning btn-sm" data-toggle="modal" data-target="#modalEvidenceUpload">
                                    Revisar selección
                                </button>
                            </div>
                            <div id="evidenceExistingWrap" style="display:none;">
                                <div class="d-flex align-items-center mb-2">
                                    <strong class="text-dark"><i class="fas fa-history mr-1 text-info"></i>Evidencias guardadas</strong>
                                </div>
                                <div id="evidenceExisting" class="evidence-grid"></div>
                            </div>
                            <div id="evidenceEmpty" class="evidence-empty">
                                <i class="far fa-images fa-2x text-info mb-2"></i>
                                <div class="font-weight-bold">Aún no hay evidencias guardadas</div>
                                <small>Usa “Agregar evidencias” para seleccionar las fotografías del mantenimiento.</small>
                            </div>
                        </div>
                    </div>

                </div><!-- /col-md-8 -->

                <!-- ═══════════════════════ PANEL LATERAL ═══════════════════════ -->
                <div class="col-md-4">

                    <!-- Alerta -->
                    <div id="alertGuardar" class="alert" style="display:none;"></div>

                    <!-- Card Guardar (gradiente) -->
                    <div class="card shadow" style="border:none; overflow:hidden;">
                        <div style="background:linear-gradient(135deg,#1a7431 0%,#28a745 60%,#20c997 100%); padding:22px; text-align:center;">
                            <div style="background:rgba(255,255,255,0.18); border-radius:50%; width:52px; height:52px; display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px;">
                                <i class="fas fa-<?= $es_edicion ? 'edit' : 'plus-circle' ?> fa-lg text-white"></i>
                            </div>
                            <h5 class="text-white font-weight-bold mb-1">
                                <?= $es_edicion ? 'Editando OT #' . $docentry : 'Nueva Orden' ?>
                            </h5>
                            <small class="text-white-50">
                                <?= $es_edicion ? 'Los cambios reemplazarán el registro actual' : 'Se creará un nuevo registro en SAP' ?>
                            </small>
                        </div>
                        <div class="card-body bg-white p-3">
                            <button type="submit" class="btn btn-success btn-block btn-lg font-weight-bold shadow-sm mb-2" id="btnGuardar">
                                <i class="fas fa-save mr-2"></i>
                                <?= $es_edicion ? 'Actualizar OT' : 'Registrar OT' ?>
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-arrow-left mr-2"></i>Cancelar
                            </a>
                        </div>
                    </div>

                    <!-- Tipos de Mantenimiento -->
                    <div class="card shadow-sm" style="border:1px solid #dee2e6; border-left:4px solid #17a2b8;">
                        <div class="card-header bg-white border-bottom">
                            <h3 class="card-title font-weight-bold text-info mb-0">
                                <i class="fas fa-info-circle mr-2"></i>Tipos de Mantenimiento
                            </h3>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge mr-2 py-1 px-2" style="background:#17a2b8;color:#fff;font-size:11px;min-width:46px;">PREV</span>
                                <span class="text-dark" style="font-size:13px;">Preventivo — programado</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge mr-2 py-1 px-2" style="background:#dc3545;color:#fff;font-size:11px;min-width:46px;">CORR</span>
                                <span class="text-dark" style="font-size:13px;">Correctivo — falla ocurrida</span>
                            </div>
                     
                       
                        </div>
                    </div>

                </div><!-- /col-md-4 -->
            </div><!-- /row -->
        </form>
    </div>
</section>

<?php if ($es_edicion): ?>
<div class="modal fade otc-modal" id="modalCostosNuevaOt" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><div><h5 class="modal-title font-weight-bold"><i class="fas fa-receipt mr-2"></i>Repuestos y servicios - OT #<?= $docentry ?></h5><small class="text-white-50">La fila se guarda en SAP y el PDF en MySQL.</small></div><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <?php if (tiene_permiso('mantenimientos', 'crear') || tiene_permiso('mantenimientos', 'editar')): ?>
                <form id="otcForm" class="otc-form mb-3" autocomplete="off">
                    <input type="hidden" id="otcCode" value="">
                    <div class="d-flex justify-content-between align-items-center mb-3"><strong id="otcFormTitle" class="text-info"><i class="fas fa-plus-circle mr-1"></i>Nuevo registro</strong><button type="button" id="otcCancelEdit" class="btn btn-sm btn-outline-secondary" style="display:none;">Cancelar edicion</button></div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>Tipo *</label><select id="otcTipo" class="form-control" required><option value="REPUESTO">Repuesto</option><option value="SERVICIO">Servicio</option></select></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Descripcion *</label><textarea id="otcDescripcion" class="form-control" rows="2" maxlength="30000" required></textarea></div></div>
                        <div class="col-md-3"><div class="otc-total"><div><small>Importe</small><div id="otcFormula">1 x 0.00</div></div><strong id="otcTotal">S/ 0.00</strong></div></div>
                        <div class="col-sm-4 col-lg-2"><div class="form-group"><label>Cantidad *</label><input type="number" id="otcCantidad" class="form-control" min="0.001" step="0.001" value="1" required></div></div>
                        <div class="col-sm-4 col-lg-2"><div class="form-group"><label>Precio unitario *</label><input type="number" id="otcPrecio" class="form-control" min="0" step="0.01" value="0.00" required></div></div>
                        <div class="col-sm-4 col-lg-2"><div class="form-group"><label>Moneda *</label><select id="otcMoneda" class="form-control"><option value="PEN">Soles</option><option value="USD">Dolares</option></select></div></div>
                        <div class="col-md-6 col-lg-3"><div class="form-group"><label>Codigo del repuesto SAP</label><input type="text" id="otcItemCode" class="form-control" maxlength="50"></div></div>
                        <div class="col-md-6 col-lg-3"><div class="form-group"><label>Codigo de compra SAP</label><input type="text" id="otcDocCompra" class="form-control" maxlength="50"></div></div>
                        <div class="col-lg-4"><div class="form-group mb-lg-0"><label>Proveedor</label><input type="text" id="otcProveedor" class="form-control" maxlength="150"></div></div>
                        <div class="col-lg-5"><label>PDF de sustento <span id="otcPdfRequired" class="text-danger">*</span></label><div class="otc-pdf"><i class="fas fa-file-pdf text-danger mr-2"></i><strong id="otcPdfName">Seleccionar PDF, maximo 25 MB</strong><input type="file" id="otcPdf" accept="application/pdf,.pdf"></div></div>
                        <div class="col-lg-3 d-flex align-items-end"><button type="submit" id="otcSave" class="btn btn-info btn-lg btn-block font-weight-bold"><i class="fas fa-save mr-1"></i><span>Agregar</span></button></div>
                    </div>
                </form>
                <?php endif; ?>
                <div class="card mb-0"><div class="card-header bg-white"><strong>Registros activos en SAP</strong><span id="otcModalCount" class="badge badge-info float-right">0</span></div><div class="table-responsive"><table class="table table-hover otc-table mb-0"><thead><tr><th>Tipo</th><th>Detalle</th><th>Cant.</th><th>Total</th><th>Compra SAP</th><th>PDF</th><th>Acciones</th></tr></thead><tbody id="otcModalBody"></tbody></table></div><div id="otcModalEmpty" class="text-center text-muted py-4" style="display:none;">Sin registros.</div></div>
            </div>
            <div class="modal-footer"><small class="mr-auto text-muted">Los registros anulados permanecen en SAP para trazabilidad.</small><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade modal-equipo" id="modalNuevoEquipo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title mb-0"><i class="fas fa-plus-circle mr-2"></i>Agregar equipo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6 class="mb-1 font-weight-bold text-success">Alta rapida de equipo</h6>
                    <small class="text-muted">El codigo interno Code se calcula automaticamente en SAP.</small>
                </div>
                <div class="form-card">
                <form id="formNuevoEquipo" autocomplete="off">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Codigo equipo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="neItemCode" maxlength="50" required placeholder="Ej: ITEM00002">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold">Nombre equipo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="neItemName" maxlength="200" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Inventario</label>
                                <input type="text" class="form-control" id="neInventryNo" maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Serie</label>
                                <input type="text" class="form-control" id="neAssetSerNo" maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Categoria</label>
                                <select class="form-control" id="neCategoria" required>
                                    <option value="">Seleccionar</option>
                                    <option value="MANTENIM">Mantenimiento</option>
                                    <option value="SISTEMAS">Sistemas</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-md-0">
                                <label>Marca</label>
                                <input type="text" class="form-control" id="neMarca" maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label>Modelo</label>
                                <input type="text" class="form-control" id="neModelo" maxlength="100">
                            </div>
                        </div>
                    </div>
                </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success font-weight-bold" id="btnGuardarEquipo">
                    <i class="fas fa-save mr-1"></i>Guardar equipo
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade evidence-upload-modal" id="modalEvidenceUpload" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title font-weight-bold mb-1"><i class="fas fa-paperclip mr-2"></i>Agregar evidencias y documento</h5>
                    <small class="text-white-50">Selecciona las fotografías y, opcionalmente, informes PDF o Word.</small>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="evidence-drop" id="evidenceDrop">
                    <input type="file" id="evidencias" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" multiple>
                    <i class="fas fa-cloud-upload-alt fa-2x text-success mb-2"></i>
                    <div class="font-weight-bold text-dark">Selecciona o arrastra las fotos del mantenimiento</div>
                    <small class="text-muted">JPG, PNG, WEBP o HEIC. Máximo 10 fotos, 15 MB cada una.</small>
                </div>
                <div class="pdf-drop mt-3" id="pdfDrop">
                    <input type="file" id="evidenciaDocumentos" accept="application/pdf,.pdf,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" multiple>
                    <i class="fas fa-file-alt fa-2x text-danger mb-2"></i>
                    <div class="font-weight-bold text-dark">Selecciona el informe PDF o Word</div>
                    <small class="text-muted">Máximo 1 PDF y 1 Word por OT, hasta 25 MB cada uno.</small>
                    <div id="documentSelectedNames" class="small font-weight-bold text-danger mt-2"></div>
                </div>
                <div class="form-group mt-3">
                    <label class="font-weight-bold text-dark">Nota de la evidencia <span class="text-muted font-weight-normal">(opcional)</span></label>
                    <input type="text" id="evidenciaDescripcion" class="form-control" maxlength="250"
                           placeholder="Ej.: Estado final del motor luego del mantenimiento">
                </div>
                <div id="evidencePreview" class="evidence-grid mt-3"></div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto"><i class="fas fa-info-circle mr-1"></i>Las fotos se guardarán junto con la OT.</small>
                <button type="button" class="btn btn-outline-danger" id="btnLimpiarEvidencias">
                    <i class="fas fa-times mr-1"></i>Limpiar
                </button>
                <button type="button" class="btn btn-success font-weight-bold" data-dismiss="modal">
                    <i class="fas fa-check mr-1"></i>Confirmar selección
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade evidence-viewer" id="modalEvidenceViewer" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0"><i class="fas fa-images mr-2 text-success"></i>Evidencias de la OT</h5>
                <div class="d-flex align-items-center">
                    <span class="badge badge-success mr-3" id="evidenceViewerCount">1 / 1</span>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="evidence-viewer-stage">
                <button type="button" class="evidence-viewer-nav evidence-viewer-prev" id="evidencePrev" aria-label="Anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <img id="evidenceViewerImage" class="evidence-viewer-image" alt="Evidencia de mantenimiento">
                <div id="evidenceViewerUnsupported" class="text-center text-white" style="display:none;">
                    <i class="fas fa-file-image fa-4x mb-3 text-success"></i>
                    <p>Este formato no puede previsualizarse en el navegador.</p>
                </div>
                <button type="button" class="evidence-viewer-nav evidence-viewer-next" id="evidenceNext" aria-label="Siguiente">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="evidence-viewer-footer">
                <div class="evidence-viewer-caption">
                    <strong id="evidenceViewerName">Evidencia</strong>
                    <span id="evidenceViewerMeta"></span>
                </div>
                <a id="evidenceViewerDownload" class="btn btn-success btn-sm" href="#" download>
                    <i class="fas fa-download mr-1"></i>Descargar
                </a>
            </div>
        </div>
    </div>
</div>

<?php

$baseUrl    = BASE_URL;
$progCodeJs = htmlspecialchars($prog_code, ENT_QUOTES);
$btnLabel   = $es_edicion ? 'Actualizar OT' : 'Registrar OT';
$csrfTokenJs = json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$canManageCostsJs = (tiene_permiso('mantenimientos', 'crear') || tiene_permiso('mantenimientos', 'editar')) ? 'true' : 'false';

$inlineJs = <<<JS
\$('.preloader').fadeOut(300);

var docentry    = parseInt(document.getElementById('docentry').value) || 0;
var progCodeURL = '$progCodeJs';
var equiposCache = {};
var otCsrfToken = $csrfTokenJs;
var canManageOtCosts = {$canManageCostsJs};

function otSecureOptions(options) {
    var opts = Object.assign({}, options || {});
    opts.credentials = 'same-origin';
    opts.headers = new Headers(opts.headers || {});
    if (otCsrfToken) opts.headers.set('X-CSRF-Token', otCsrfToken);

    if (opts.body instanceof FormData) {
        opts.body.set('_csrf', otCsrfToken);
    } else if (typeof opts.body === 'string' && opts.headers.get('Content-Type') === 'application/json') {
        try {
            var jsonBody = JSON.parse(opts.body);
            if (jsonBody && !Array.isArray(jsonBody)) jsonBody._csrf = otCsrfToken;
            opts.body = JSON.stringify(jsonBody);
        } catch (e) {}
    }
    return opts;
}

function renovarTokenOt() {
    return fetch('$baseUrl/api/mantenimientos/csrf.php?_=' + Date.now(), {
        credentials:'same-origin',
        cache:'no-store'
    }).then(function(response) {
        return response.json().then(function(data) {
            if (!response.ok || !data.success || !data.token) {
                throw new Error(data.error || 'No se pudo renovar la seguridad de la sesion.');
            }
            otCsrfToken = data.token;
            window.CSRF_TOKEN = data.token;
        });
    });
}

function otFetch(url, options, retryCsrf) {
    return fetch(url, otSecureOptions(options)).then(function(response) {
        if (response.status === 419 && retryCsrf !== false) {
            return renovarTokenOt().then(function() {
                return otFetch(url, options, false);
            });
        }
        return response;
    });
}
var otcRows = [];

function otcEscape(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function(char) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
}

function otcMoney(value, currency) {
    return new Intl.NumberFormat('es-PE', {
        style:'currency', currency:currency === 'USD' ? 'USD' : 'PEN',
        minimumFractionDigits:2, maximumFractionDigits:2
    }).format(Number(value) || 0);
}

function otcBadge(type) {
    return type === 'SERVICIO'
        ? '<span class="badge badge-success">Servicio</span>'
        : '<span class="badge badge-primary">Repuesto</span>';
}

function otcRender() {
    if (docentry <= 0) return;
    var pen = 0, usd = 0;
    otcRows.forEach(function(row) {
        if (row.moneda === 'USD') usd += Number(row.total) || 0;
        else pen += Number(row.total) || 0;
    });
    document.getElementById('otcPageCount').textContent = otcRows.length;
    document.getElementById('otcPagePen').textContent = otcMoney(pen, 'PEN');
    document.getElementById('otcPageUsd').textContent = otcMoney(usd, 'USD');
    document.getElementById('otcPageContent').style.display = '';
    document.getElementById('otcPageEmpty').style.display = otcRows.length ? 'none' : '';
    document.getElementById('otcPageTableWrap').style.display = otcRows.length ? '' : 'none';
    document.getElementById('otcModalCount').textContent = otcRows.length;
    document.getElementById('otcModalEmpty').style.display = otcRows.length ? 'none' : '';

    var pageBody = document.getElementById('otcPageBody');
    var modalBody = document.getElementById('otcModalBody');
    pageBody.innerHTML = '';
    modalBody.innerHTML = '';
    otcRows.forEach(function(row) {
        var detail = '<strong>' + otcEscape(row.descripcion) + '</strong>'
            + (row.item_code ? '<small class="d-block text-primary">Item SAP: ' + otcEscape(row.item_code) + '</small>' : '')
            + (row.proveedor ? '<small class="d-block text-muted">' + otcEscape(row.proveedor) + '</small>' : '');
        var pdf = row.url_pdf
            ? '<a class="btn btn-sm btn-outline-danger" target="_blank" rel="noopener" href="' + otcEscape(row.url_pdf) + '"><i class="fas fa-file-pdf"></i></a>'
            : '<span class="text-muted">—</span>';
        var pageTr = document.createElement('tr');
        pageTr.innerHTML = '<td>' + otcBadge(row.tipo) + '</td><td>' + detail + '</td><td>'
            + Number(row.cantidad).toLocaleString('es-PE', {maximumFractionDigits:3}) + '</td><td><strong>'
            + otcMoney(row.total, row.moneda) + '</strong></td><td>' + otcEscape(row.codigo_compra_sap || '—')
            + '</td><td>' + pdf + '</td>';
        pageBody.appendChild(pageTr);

        var actions = pdf;
        if (canManageOtCosts) {
            actions += ' <button type="button" class="btn btn-sm btn-outline-info otc-edit" data-code="'
                + otcEscape(row.code) + '" title="Editar"><i class="fas fa-edit"></i></button>'
                + ' <button type="button" class="btn btn-sm btn-outline-danger otc-delete" data-code="'
                + otcEscape(row.code) + '" title="Anular"><i class="fas fa-ban"></i></button>';
        }
        var modalTr = document.createElement('tr');
        modalTr.innerHTML = '<td>' + otcBadge(row.tipo) + '</td><td>' + detail + '</td><td>'
            + Number(row.cantidad).toLocaleString('es-PE', {maximumFractionDigits:3}) + '</td><td><strong>'
            + otcMoney(row.total, row.moneda) + '</strong></td><td>' + otcEscape(row.codigo_compra_sap || '—')
            + '</td><td>' + otcEscape(row.nombre_original || 'PDF') + '</td><td class="otc-actions">' + actions + '</td>';
        modalBody.appendChild(modalTr);
    });
}

function cargarCostosOt() {
    if (docentry <= 0) return Promise.resolve([]);
    document.getElementById('otcPageLoading').style.display = '';
    document.getElementById('otcPageError').style.display = 'none';
    return fetch('$baseUrl/api/mantenimientos/costos_ot.php?docentry=' + docentry + '&_=' + Date.now(), {
        credentials:'same-origin', cache:'no-store'
    }).then(function(response) {
        return response.json().then(function(data){ return {ok:response.ok,data:data}; });
    }).then(function(result) {
        if (!result.ok || !result.data.success) throw new Error(result.data.error || 'No se pudieron cargar los costos.');
        otcRows = result.data.costos || [];
        otcRender();
        return otcRows;
    }).catch(function(error) {
        var alert = document.getElementById('otcPageError');
        alert.textContent = error.message;
        alert.style.display = '';
        throw error;
    }).finally(function(){ document.getElementById('otcPageLoading').style.display = 'none'; });
}

function otcCalculate() {
    if (!document.getElementById('otcCantidad')) return;
    var quantity = Number(document.getElementById('otcCantidad').value) || 0;
    var price = Number(document.getElementById('otcPrecio').value) || 0;
    var currency = document.getElementById('otcMoneda').value;
    document.getElementById('otcFormula').textContent = quantity.toLocaleString('es-PE', {maximumFractionDigits:3}) + ' x ' + price.toFixed(2);
    document.getElementById('otcTotal').textContent = otcMoney(quantity * price, currency);
}

function otcReset() {
    var form = document.getElementById('otcForm');
    if (!form) return;
    form.reset();
    document.getElementById('otcCode').value = '';
    document.getElementById('otcCantidad').value = '1';
    document.getElementById('otcPrecio').value = '0.00';
    document.getElementById('otcFormTitle').innerHTML = '<i class="fas fa-plus-circle mr-1"></i>Nuevo registro';
    document.querySelector('#otcSave span').textContent = 'Agregar';
    document.getElementById('otcCancelEdit').style.display = 'none';
    document.getElementById('otcPdfRequired').style.display = '';
    document.getElementById('otcPdfName').textContent = 'Seleccionar PDF, maximo 25 MB';
    otcCalculate();
}

function otcEdit(code) {
    var row = otcRows.find(function(item){ return String(item.code) === String(code); });
    if (!row || !document.getElementById('otcForm')) return;
    document.getElementById('otcCode').value = row.code;
    document.getElementById('otcTipo').value = row.tipo;
    document.getElementById('otcDescripcion').value = row.descripcion || '';
    document.getElementById('otcCantidad').value = row.cantidad;
    document.getElementById('otcPrecio').value = Number(row.precio_unitario).toFixed(2);
    document.getElementById('otcMoneda').value = row.moneda || 'PEN';
    document.getElementById('otcItemCode').value = row.item_code || '';
    document.getElementById('otcDocCompra').value = row.codigo_compra_sap || '';
    document.getElementById('otcProveedor').value = row.proveedor || '';
    document.getElementById('otcPdf').value = '';
    document.getElementById('otcPdfName').textContent = 'Se conservara: ' + (row.nombre_original || 'PDF actual');
    document.getElementById('otcPdfRequired').style.display = 'none';
    document.getElementById('otcFormTitle').innerHTML = '<i class="fas fa-edit mr-1"></i>Editar registro SAP';
    document.querySelector('#otcSave span').textContent = 'Guardar cambios';
    document.getElementById('otcCancelEdit').style.display = '';
    otcCalculate();
}

function otcSave(event) {
    event.preventDefault();
    var form = document.getElementById('otcForm');
    if (!form.reportValidity()) return;
    var code = document.getElementById('otcCode').value.trim();
    var pdf = document.getElementById('otcPdf').files[0];
    if (!code && !pdf) { Swal.fire('Falta el PDF', 'Adjunte el sustento del repuesto o servicio.', 'warning'); return; }
    var data = new FormData();
    data.append('code', code); data.append('docentry', String(docentry));
    data.append('tipo', document.getElementById('otcTipo').value);
    data.append('descripcion', document.getElementById('otcDescripcion').value.trim());
    data.append('cantidad', document.getElementById('otcCantidad').value);
    data.append('precio_unitario', document.getElementById('otcPrecio').value);
    data.append('moneda', document.getElementById('otcMoneda').value);
    data.append('item_code', document.getElementById('otcItemCode').value.trim());
    data.append('codigo_compra_sap', document.getElementById('otcDocCompra').value.trim());
    data.append('proveedor', document.getElementById('otcProveedor').value.trim());
    if (pdf) data.append('pdf', pdf, pdf.name);
    var button = document.getElementById('otcSave');
    button.disabled = true;
    otFetch('$baseUrl/api/mantenimientos/costos_ot.php', {method:'POST',body:data})
        .then(function(response){ return response.json().then(function(json){return {ok:response.ok,json:json};}); })
        .then(function(result){
            if (!result.ok || !result.json.success) throw new Error(result.json.error || 'No se pudo guardar.');
            otcReset();
            return cargarCostosOt().then(function(){ App.toast(code ? 'Registro SAP actualizado' : 'Registro guardado en SAP', 'success'); });
        }).catch(function(error){ Swal.fire('No se pudo guardar', error.message, 'error'); })
        .finally(function(){ button.disabled=false; });
}

function otcDelete(code) {
    Swal.fire({icon:'warning',title:'Anular registro',text:'Se marcara inactivo en SAP y el PDF se conservara.',showCancelButton:true,confirmButtonColor:'#dc3545',confirmButtonText:'Si, anular',cancelButtonText:'Cancelar'}).then(function(result){
        if (!result.isConfirmed) return;
        otFetch('$baseUrl/api/mantenimientos/costos_ot.php', {method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({code:String(code)})})
            .then(function(response){return response.json().then(function(json){return {ok:response.ok,json:json};});})
            .then(function(resultDelete){if(!resultDelete.ok||!resultDelete.json.success)throw new Error(resultDelete.json.error||'No se pudo anular.');otcReset();return cargarCostosOt().then(function(){App.toast('Registro anulado en SAP','success');});})
            .catch(function(error){Swal.fire('No se pudo anular',error.message,'error');});
    });
}

if (document.getElementById('btnAbrirCostosOt')) document.getElementById('btnAbrirCostosOt').addEventListener('click', function(){ otcReset(); otcRender(); $('#modalCostosNuevaOt').modal('show'); });
if (document.getElementById('otcForm')) document.getElementById('otcForm').addEventListener('submit', otcSave);
if (document.getElementById('otcCancelEdit')) document.getElementById('otcCancelEdit').addEventListener('click', otcReset);
if (document.getElementById('otcPdf')) document.getElementById('otcPdf').addEventListener('change', function(){ document.getElementById('otcPdfName').textContent = this.files[0] ? this.files[0].name : 'Seleccionar PDF, maximo 25 MB'; });
['otcCantidad','otcPrecio','otcMoneda'].forEach(function(id){ var input=document.getElementById(id); if(input) input.addEventListener('input',otcCalculate); });
$(document).on('click','.otc-edit',function(){otcEdit($(this).data('code'));});
$(document).on('click','.otc-delete',function(){otcDelete($(this).data('code'));});
var evidenceRows = [];
var evidenceImageRows = [];
var evidenceIndex = 0;

document.getElementById('titulo').addEventListener('input', function() {
    document.getElementById('tituloCount').textContent = this.value.length;
});

function evidenceEscape(value) {
    return String(value || '').replace(/[&<>"']/g, function(char) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
}

function evidenceBytes(bytes) {
    var value = Number(bytes || 0);
    if (value < 1024 * 1024) return (value / 1024).toFixed(1) + ' KB';
    return (value / (1024 * 1024)).toFixed(1) + ' MB';
}

function isPdfEvidence(row) {
    return (row.mime_type || '') === 'application/pdf';
}

function isWordEvidence(row) {
    return /application\/(msword|vnd\.openxmlformats-officedocument\.wordprocessingml\.document)/i.test(row.mime_type || '');
}

function isDocumentEvidence(row) {
    return isPdfEvidence(row) || isWordEvidence(row);
}

function actualizarContadorEvidencias(guardadas) {
    var nuevas = document.getElementById('evidencias').files.length;
    var fotosGuardadas = evidenceRows.filter(function(row){ return !isDocumentEvidence(row); }).length;
    var documentosGuardados = evidenceRows.filter(isDocumentEvidence).length;
    var documentosNuevos = document.getElementById('evidenciaDocumentos').files.length;
    var total = nuevas + fotosGuardadas;
    document.getElementById('evidenceCount').textContent = total + (total === 1 ? ' foto' : ' fotos');
    var docsTotal = documentosGuardados + documentosNuevos;
    document.getElementById('documentCount').textContent = docsTotal
        ? docsTotal + (docsTotal === 1 ? ' documento' : ' documentos')
        : 'Sin documentos';
}

function mostrarPreviewEvidencias() {
    var input = document.getElementById('evidencias');
    var container = document.getElementById('evidencePreview');
    container.innerHTML = '';
    Array.prototype.forEach.call(input.files, function(file) {
        var card = document.createElement('div');
        card.className = 'evidence-card';
        var preview = /^image\/(jpeg|png|webp)$/.test(file.type)
            ? '<img class="evidence-thumb" src="' + URL.createObjectURL(file) + '" alt="">'
            : '<div class="evidence-file-icon"><i class="fas fa-file-image"></i></div>';
        card.innerHTML = preview
            + '<div class="evidence-meta"><div class="evidence-name" title="' + evidenceEscape(file.name) + '">'
            + evidenceEscape(file.name) + '</div><span class="text-muted">' + evidenceBytes(file.size) + '</span></div>';
        container.appendChild(card);
    });
    var documentInput = document.getElementById('evidenciaDocumentos');
    var selectedDocuments = Array.prototype.slice.call(documentInput.files);
    document.getElementById('documentSelectedNames').innerHTML = selectedDocuments.map(function(file) {
        return '<div>' + evidenceEscape(file.name) + ' · ' + evidenceBytes(file.size) + '</div>';
    }).join('');
    var pending = input.files.length + selectedDocuments.length;
    document.getElementById('evidencePendingCount').textContent = pending;
    document.getElementById('evidencePendingWrap').style.display = pending ? '' : 'none';
    actualizarContadorEvidencias(document.querySelectorAll('#evidenceExisting .evidence-card').length);
}

document.getElementById('btnLimpiarEvidencias').addEventListener('click', function() {
    document.getElementById('evidencias').value = '';
    document.getElementById('evidenciaDocumentos').value = '';
    mostrarPreviewEvidencias();
});

function renderEvidenceViewer() {
    if (!evidenceImageRows.length) return;
    if (evidenceIndex < 0) evidenceIndex = evidenceImageRows.length - 1;
    if (evidenceIndex >= evidenceImageRows.length) evidenceIndex = 0;
    var row = evidenceImageRows[evidenceIndex];
    var previewable = /image\/(jpeg|png|webp)/i.test(row.mime_type || '');
    var image = document.getElementById('evidenceViewerImage');
    var unsupported = document.getElementById('evidenceViewerUnsupported');
    image.style.display = previewable ? '' : 'none';
    unsupported.style.display = previewable ? 'none' : '';
    if (previewable) image.src = row.url;
    document.getElementById('evidenceViewerCount').textContent = (evidenceIndex + 1) + ' / ' + evidenceImageRows.length;
    document.getElementById('evidenceViewerName').textContent = row.nombre_original || 'Evidencia';
    var details = [];
    if (row.descripcion) details.push(row.descripcion);
    if (row.usuario) details.push('Subido por ' + row.usuario);
    if (row.created_at) details.push(row.created_at);
    details.push(evidenceBytes(row.tamano_bytes));
    document.getElementById('evidenceViewerMeta').textContent = details.join(' · ');
    document.getElementById('evidenceViewerDownload').href = row.url + '&download=1';
    var multiple = evidenceImageRows.length > 1;
    document.getElementById('evidencePrev').style.display = multiple ? '' : 'none';
    document.getElementById('evidenceNext').style.display = multiple ? '' : 'none';
}

function abrirCarruselEvidencias(index) {
    evidenceIndex = Number(index) || 0;
    renderEvidenceViewer();
    \$('#modalEvidenceViewer').modal('show');
}

function moverCarruselEvidencias(step) {
    evidenceIndex += step;
    renderEvidenceViewer();
}

document.getElementById('evidencePrev').addEventListener('click', function(){ moverCarruselEvidencias(-1); });
document.getElementById('evidenceNext').addEventListener('click', function(){ moverCarruselEvidencias(1); });
document.addEventListener('keydown', function(event) {
    if (!\$('#modalEvidenceViewer').hasClass('show')) return;
    if (event.key === 'ArrowLeft') moverCarruselEvidencias(-1);
    if (event.key === 'ArrowRight') moverCarruselEvidencias(1);
});

function cargarEvidencias() {
    if (docentry <= 0) return Promise.resolve();
    return fetch('$baseUrl/api/mantenimientos/evidencias_ot.php?docentry=' + docentry, {credentials:'same-origin'})
        .then(function(response) { return response.json().then(function(data){ return {ok:response.ok, data:data}; }); })
        .then(function(result) {
            if (!result.ok) throw new Error(result.data.error || 'No se pudieron listar las evidencias.');
            document.getElementById('evidenceAlert').style.display = 'none';
            var rows = result.data.evidencias || [];
            evidenceRows = rows;
            evidenceImageRows = rows.filter(function(row){ return !isDocumentEvidence(row); });
            var wrap = document.getElementById('evidenceExistingWrap');
            var container = document.getElementById('evidenceExisting');
            container.innerHTML = '';
            rows.forEach(function(row, index) {
                var isPreviewable = /image\/(jpeg|png|webp)/i.test(row.mime_type || '');
                var isPdf = isPdfEvidence(row);
                var isWord = isWordEvidence(row);
                var isDocument = isPdf || isWord;
                var imageIndex = evidenceImageRows.findIndex(function(image){ return Number(image.id) === Number(row.id); });
                var visual = isDocument
                    ? '<a class="d-block" href="' + row.url + (isWord ? '&download=1' : '') + '" target="_blank" rel="noopener"><span class="evidence-file-icon ' + (isPdf ? 'text-danger' : 'text-primary') + '"><i class="fas ' + (isPdf ? 'fa-file-pdf' : 'fa-file-word') + '"></i></span></a>'
                    : (isPreviewable
                        ? '<button type="button" class="p-0 border-0 w-100" onclick="abrirCarruselEvidencias(' + imageIndex + ')"><img class="evidence-thumb" src="' + row.url + '" alt=""></button>'
                        : '<button type="button" class="p-0 border-0 w-100" onclick="abrirCarruselEvidencias(' + imageIndex + ')"><span class="evidence-file-icon"><i class="fas fa-file-image"></i></span></button>');
                var card = document.createElement('div');
                card.className = 'evidence-card';
                card.innerHTML = visual
                    + '<div class="evidence-actions">'
                    + '<a class="btn btn-light btn-sm" href="' + row.url + '&download=1" title="Descargar"><i class="fas fa-download"></i></a>'
                    + '<button type="button" class="btn btn-danger btn-sm" title="Eliminar" onclick="eliminarEvidencia(' + Number(row.id) + ')"><i class="fas fa-trash"></i></button>'
                    + '</div><div class="evidence-meta"><div class="evidence-name" title="' + evidenceEscape(row.nombre_original) + '">'
                    + evidenceEscape(row.nombre_original) + '</div><span class="text-muted">' + evidenceBytes(row.tamano_bytes)
                    + (row.usuario ? ' · ' + evidenceEscape(row.usuario) : '') + '</span>'
                    + '<div class="evidence-note" title="' + evidenceEscape(row.descripcion || '') + '">'
                    + evidenceEscape(row.descripcion || 'Sin nota') + '</div></div>';
                container.appendChild(card);
            });
            wrap.style.display = rows.length ? '' : 'none';
            document.getElementById('evidenceEmpty').style.display = rows.length ? 'none' : '';
            actualizarContadorEvidencias(rows.length);
        })
        .catch(function(error) {
            console.error('Evidencias:', error);
            var alert = document.getElementById('evidenceAlert');
            alert.textContent = error.message;
            alert.style.display = '';
            document.getElementById('evidenceExistingWrap').style.display = 'none';
            document.getElementById('evidenceEmpty').style.display = '';
        });
}

function eliminarEvidencia(id) {
    Swal.fire({
        icon:'warning',
        title:'Eliminar evidencia',
        text:'La fotografia se eliminara permanentemente.',
        showCancelButton:true,
        confirmButtonColor:'#dc3545',
        confirmButtonText:'Si, eliminar',
        cancelButtonText:'Cancelar'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        otFetch('$baseUrl/api/mantenimientos/evidencias_ot.php', {
            method:'DELETE',
            credentials:'same-origin',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id:id})
        })
        .then(function(response){ return response.json().then(function(data){ return {ok:response.ok,data:data}; }); })
        .then(function(resultDelete) {
            if (!resultDelete.ok) throw new Error(resultDelete.data.error || 'No se pudo eliminar.');
            return cargarEvidencias();
        })
        .catch(function(error){ Swal.fire('No se pudo eliminar', error.message, 'error'); });
    });
}

function subirEvidencias(otDocEntry) {
    var input = document.getElementById('evidencias');
    var documentInput = document.getElementById('evidenciaDocumentos');
    if (!input.files.length && !documentInput.files.length) return Promise.resolve({cantidad:0});
    var formData = new FormData();
    formData.append('docentry', String(otDocEntry));
    formData.append('descripcion', document.getElementById('evidenciaDescripcion').value.trim());
    Array.prototype.forEach.call(input.files, function(file) {
        formData.append('evidencias[]', file, file.name);
    });
    Array.prototype.forEach.call(documentInput.files, function(file) {
        formData.append('evidencias[]', file, file.name);
    });
    return otFetch('$baseUrl/api/mantenimientos/evidencias_ot.php', {
        method:'POST',
        credentials:'same-origin',
        body:formData
    }).then(function(response) {
        return response.json().then(function(data) {
            if (!response.ok || !data.success) throw new Error(data.error || 'No se pudieron cargar las evidencias.');
            return data;
        });
    });
}

document.getElementById('evidencias').addEventListener('change', mostrarPreviewEvidencias);
document.getElementById('evidenciaDocumentos').addEventListener('change', function() {
    var files = Array.prototype.slice.call(this.files);
    var pdfFiles = files.filter(function(file){ return file.type === 'application/pdf' || /\.pdf$/i.test(file.name); });
    var wordFiles = files.filter(function(file){ return /\.(doc|docx)$/i.test(file.name); });
    var invalidFiles = files.filter(function(file){ return !/\.(pdf|doc|docx)$/i.test(file.name); });
    var hasPdf = evidenceRows.some(isPdfEvidence);
    var hasWord = evidenceRows.some(isWordEvidence);
    if (invalidFiles.length || pdfFiles.length > 1 || wordFiles.length > 1 || (hasPdf && pdfFiles.length) || (hasWord && wordFiles.length)) {
        this.value = '';
        var message = invalidFiles.length
            ? 'Seleccione únicamente archivos PDF, DOC o DOCX.'
            : (hasPdf && pdfFiles.length)
                ? 'Esta OT ya tiene un PDF. Elimine el actual antes de reemplazarlo.'
                : (hasWord && wordFiles.length)
                    ? 'Esta OT ya tiene un Word. Elimine el actual antes de reemplazarlo.'
                    : 'Seleccione como máximo un PDF y un documento Word.';
        Swal.fire('Selección no válida', message, 'warning');
    }
    mostrarPreviewEvidencias();
});
var evidenceDrop = document.getElementById('evidenceDrop');
['dragenter','dragover'].forEach(function(eventName) {
    evidenceDrop.addEventListener(eventName, function(){ evidenceDrop.classList.add('is-dragging'); });
});
['dragleave','drop'].forEach(function(eventName) {
    evidenceDrop.addEventListener(eventName, function(){ evidenceDrop.classList.remove('is-dragging'); });
});

function fmtHoraSap(value) {
    var raw = String(value || '').trim();
    if (!raw) return '';
    if (/^\d{1,2}:\d{2}$/.test(raw)) return raw;
    if (/^\d{3,4}$/.test(raw)) {
        var normalized = raw.padStart(4, '0');
        return normalized.substring(0, 2) + ':' + normalized.substring(2, 4);
    }
    return raw.substring(0, 5);
}

// ── Select2 para equipo (búsqueda AJAX) ─────────────────────────
\$(function() {
    \$('#selectEquipo').select2({
        theme: 'bootstrap4',
        placeholder: '— Buscar equipo por código o nombre —',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '$baseUrl/api/mantenimientos/get_equipos.php',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    search: params.term
                };
            },
            processResults: function(data) {
                if (!Array.isArray(data)) {
                    Swal.fire('Error Equipos', (data.error || JSON.stringify(data)), 'error');
                    return { results: [] };
                }
                return { results: data };
            },
            cache: true
        }
    });

    \$('#selectEquipo').on('select2:select', function(e) {
        var d = e.params.data;
        equiposCache[d.id] = d;
        mostrarInfoEquipo(d);
        cargarProgramaciones(d.id);
    });
    \$('#selectEquipo').on('select2:unselect', function() {
        document.getElementById('infoEquipo').style.display = 'none';
        \$('#selectProg').html('<option value="">— Sin programación —</option>');
    });
});

function mostrarInfoEquipo(d) {
    document.getElementById('eqInventario').textContent = d.InventryNo || '—';
    document.getElementById('eqSerie').textContent      = d.AssetSerNo || '—';
    document.getElementById('eqMarca').textContent      = d.Marca      || '—';
    document.getElementById('eqModelo').textContent     = d.Modelo     || '—';
    document.getElementById('infoEquipo').style.display = '';
}

document.getElementById('btnNuevoEquipo').addEventListener('click', function() {
    document.getElementById('formNuevoEquipo').reset();
    $('#modalNuevoEquipo').modal('show');
});

document.getElementById('btnGuardarEquipo').addEventListener('click', function() {
    var payload = {
        itemcode:   document.getElementById('neItemCode').value.trim(),
        itemname:   document.getElementById('neItemName').value.trim(),
        inventryno: document.getElementById('neInventryNo').value.trim(),
        assetserno: document.getElementById('neAssetSerNo').value.trim(),
        marca:      document.getElementById('neMarca').value.trim(),
        modelo:     document.getElementById('neModelo').value.trim(),
        categoria:  document.getElementById('neCategoria').value.trim()
    };
    if (!payload.itemcode || !payload.itemname || !payload.categoria) {
        Swal.fire('Campos requeridos', 'Complete Codigo equipo, Nombre equipo y Categoria.', 'warning');
        return;
    }

    var btn = document.getElementById('btnGuardarEquipo');
    var oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...';

    otFetch('$baseUrl/api/mantenimientos/guardar_equipo.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    })
    .then(function(r){ return r.json(); })
    .then(function(json) {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
        if (!json.success) {
            Swal.fire('No se pudo guardar', json.error || 'Error al guardar equipo.', 'error');
            return;
        }

        var row = {
            id: json.ItemCode,
            text: json.ItemCode + ' - ' + json.ItemName,
            ItemCode: json.ItemCode,
            ItemName: json.ItemName,
            InventryNo: json.InventryNo,
            AssetSerNo: json.AssetSerNo,
            Marca: json.Marca,
            Modelo: json.Modelo,
            Fuente: json.Fuente
        };
        equiposCache[row.id] = row;
        var opt = new Option(row.text, row.id, true, true);
        $('#selectEquipo').append(opt).trigger('change');
        mostrarInfoEquipo(row);
        cargarProgramaciones(row.id);
        $('#modalNuevoEquipo').modal('hide');
        Swal.fire({ icon:'success', title:'Equipo agregado', timer: 1400, showConfirmButton:false });
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
        Swal.fire('Error conexion', err.message, 'error');
    });
});
function cargarProgramaciones(itemcode) {
    fetch('$baseUrl/api/mantenimientos/get_prog.php?itemcode=' + encodeURIComponent(itemcode))
        .then(function(r){ return r.json(); })
        .then(function(data) {
            var sel = document.getElementById('selectProg');
            sel.innerHTML = '<option value="">— Sin programación —</option>';
            if (Array.isArray(data)) {
                data.forEach(function(p) {
                    if (p.U_Estado === 'Cancelado') return;
                    var opt = document.createElement('option');
                    opt.value = p.Code;
                    opt.textContent = p.Code + ' — ' + p.Name + ' (' + (p.U_Frecuencia||'') + ')';
                    sel.appendChild(opt);
                });
            }
            if (progCodeURL) { sel.value = progCodeURL; }
        });
}

function cargarTipo() {
    fetch('$baseUrl/api/mantenimientos/get_tipomant.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!Array.isArray(data)) {
                Swal.fire('Error Tipos Mant.', (data.error || JSON.stringify(data)), 'error');
                return;
            }
            if (data.length === 0) {
                Swal.fire('Sin datos', 'La tabla @TAMA_TIPOMANT está vacía en SAP.', 'warning');
                return;
            }
            var sel = document.getElementById('selectTipo');
            data.forEach(function(t) {
                var opt = document.createElement('option');
                opt.value = t.Code;
                opt.textContent = t.Code + ' — ' + t.Name;
                sel.appendChild(opt);
            });
        })
        .catch(function(err) {
            Swal.fire('Error conexión', 'get_tipomant.php: ' + err.message, 'error');
        });
}

function cargarTecnicos() {
    fetch('$baseUrl/api/mantenimientos/get_tecnicos.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!Array.isArray(data)) {
                Swal.fire('Error Técnicos', (data.error || JSON.stringify(data)), 'error');
                return;
            }
            if (data.length === 0) {
                Swal.fire('Sin datos', 'La tabla @TAMA_TECNICOS está vacía.', 'warning');
            }
            var sel = document.getElementById('selectTecnico');
            data.forEach(function(t) {
                var opt = document.createElement('option');
                opt.value = t.Code;
                opt.textContent = t.Name + (t.U_Especialidad ? ' (' + t.U_Especialidad + ')' : '');
                opt.setAttribute('data-name', t.Name || '');
                sel.appendChild(opt);
            });
            if (docentry > 0) { precargarDatos(); }
        })
        .catch(function(err) {
            Swal.fire('Error conexión', 'get_tecnicos.php: ' + err.message, 'error');
        });
}

// ── Precargar datos para edición (CORREGIDO) ────────────────────
function precargarDatos() {
    fetch('$baseUrl/api/mantenimientos/get_ot.php?docentry=' + docentry)
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data || !data[0]) return;
            var row = data[0];

            // Equipo — Select2
            var opt = new Option(row.U_ItemCode + ' — ' + row.ItemName, row.U_ItemCode, true, true);
            \$('#selectEquipo').append(opt).trigger('change');

            // Info equipo con datos REALES de la API (incluye Marca, Modelo, AssetSerNo)
            mostrarInfoEquipo({
                InventryNo: row.InventryNo,
                AssetSerNo: row.AssetSerNo,
                Marca:      row.Marca,
                Modelo:     row.Modelo
            });

            cargarProgramaciones(row.U_ItemCode);
            document.getElementById('selectTipo').value    = row.U_TipoMant || '';
            document.getElementById('selectTecnico').value = row.U_Tecnico  || '';
            document.getElementById('detalleSvt').value = row.U_DetalleSVT || '';
            document.getElementById('detalleSvtCount').textContent = String(row.U_DetalleSVT || '').length;
            actualizarCampoSvt();

            // Fechas — HANA ODBC devuelve '2026-06-01 00:00:00.0000000', tomar solo los 10 primeros chars
            var fi = (row.U_FechaInicio || '').substring(0, 10).trim();
            var ff = (row.U_FechaFin    || '').substring(0, 10).trim();
            if (fi.length === 10) { document.getElementById('fechaInicio').value = fi; }
            if (ff.length === 10) { document.getElementById('fechaFin').value    = ff; }
            document.getElementById('horaInicio').value = fmtHoraSap(row.U_Hora_Ini);
            document.getElementById('horaFin').value    = fmtHoraSap(row.U_Hora_Fin);

            document.getElementById('titulo').value      = row.U_TituFor || '';
            document.getElementById('tituloCount').textContent = String(row.U_TituFor || '').length;
            document.getElementById('trabajo').value     = row.U_TrabajoRealizado || '';
            document.getElementById('observacion').value = row.U_Observacion      || '';
            cargarPlanAccionOrigen();

            setTimeout(function() {
                if (row.U_ProgDet) {
                    document.getElementById('selectProg').value = row.U_ProgDet;
                } else if (row.U_ProgMant) {
                    document.getElementById('selectProg').value = row.U_ProgMant;
                }
            }, 800);
        });
}

function mostrarEstadoPlanAccion(message, type) {
    var box = document.getElementById('planAccionConfig');
    if (!box) return;
    box.style.display = '';
    box.className = 'small mt-2 text-' + (type || 'muted');
    box.textContent = message;
}

function cargarPlanAccionOrigen() {
    if (!docentry || docentry <= 0) return Promise.resolve();
    return otFetch('$baseUrl/api/mantenimientos/plan_accion_ot.php?action=source&docentry=' + encodeURIComponent(docentry))
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.success) throw new Error(data.error || 'No se pudo consultar el Plan de Acción.');
            document.getElementById('planAccion').value = data.plan_accion || '';
            mostrarEstadoPlanAccion('Plan de Acción conectado con SAP.', 'success');
        })
        .catch(function(error) {
            mostrarEstadoPlanAccion(error.message, 'warning');
        });
}

function guardarPlanAccionOrigen(savedDocEntry) {
    var value = document.getElementById('planAccion').value.trim();
    var wasExistingOt = Number(document.getElementById('docentry').value || 0) > 0;
    if (value === '' && !wasExistingOt) return Promise.resolve({success:true, skipped:true});
    return otFetch('$baseUrl/api/mantenimientos/plan_accion_ot.php?action=save_source', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({docentry: savedDocEntry, plan_accion: value})
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        if (!data.success) throw new Error(data.error || 'No se pudo guardar el Plan de Acción.');
        return data;
    });
}

function tecnicoSeleccionadoEsSvt() {
    var select = document.getElementById('selectTecnico');
    var option = select.options[select.selectedIndex];
    var code = String(select.value || '').trim().toUpperCase();
    var name = option ? String(option.getAttribute('data-name') || '').trim().toUpperCase() : '';
    return code === 'SVT' || name === 'SVT';
}

function actualizarCampoSvt() {
    var isSvt = tecnicoSeleccionadoEsSvt();
    var group = document.getElementById('detalleSvtGroup');
    var input = document.getElementById('detalleSvt');
    group.style.display = isSvt ? '' : 'none';
    input.required = isSvt;
    if (!isSvt) {
        input.value = '';
        document.getElementById('detalleSvtCount').textContent = '0';
    }
}

document.getElementById('selectTecnico').addEventListener('change', actualizarCampoSvt);
document.getElementById('detalleSvt').addEventListener('input', function() {
    document.getElementById('detalleSvtCount').textContent = this.value.length;
});

// ── Submit ───────────────────────────────────────────────────────
document.getElementById('formOT').addEventListener('submit', function(e) {
    e.preventDefault();

    var itemcode = document.getElementById('selectEquipo').value;
    var tipo     = document.getElementById('selectTipo').value;
    var tecnico  = document.getElementById('selectTecnico').value;
    var detalleSvt = document.getElementById('detalleSvt').value.trim();
    var prog     = document.getElementById('selectProg').value;
    var finicio  = document.getElementById('fechaInicio').value;
    var ffin     = document.getElementById('fechaFin').value;
    var horaIni  = document.getElementById('horaInicio').value;
    var horaFin  = document.getElementById('horaFin').value;
    var titulo   = document.getElementById('titulo').value.trim();
    var trabajo  = document.getElementById('trabajo').value.trim();
    var observ   = document.getElementById('observacion').value.trim();

    if (!itemcode || !tipo || !finicio || !trabajo) {
        Swal.fire('Campos requeridos', 'Complete Equipo, Tipo, Fecha Inicio y Trabajo Realizado.', 'warning');
        return;
    }
    if (tecnicoSeleccionadoEsSvt() && !detalleSvt) {
        Swal.fire('Información SVT requerida', 'Ingrese el nombre o la información correspondiente a SVT.', 'warning');
        return;
    }

    var btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando…';

    var alertDiv = document.getElementById('alertGuardar');
    alertDiv.style.display = 'none';

    var payload = {
        docentry: docentry, accion: 'guardar',
        itemcode: itemcode, tipomant: tipo, tecnico: tecnico, detalle_svt: detalleSvt,
        progmant: prog, progdet: prog, fechainicio: finicio, fechafin: ffin,
        horainicio: horaIni, horafin: horaFin,
        titulo: titulo, trabajo: trabajo, observacion: observ
    };

    otFetch('$baseUrl/api/mantenimientos/guardar_ot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    })
    .then(function(r){ return r.json(); })
    .then(function(json) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>$btnLabel';
        if (json.success) {
            var savedDocEntry = Number(json.docentry || docentry);
            var savedMessage = json.action === 'inserted'
                ? 'OT #' + (json.docnum || json.docentry) + ' registrada correctamente.'
                : 'OT actualizada correctamente.';
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando Plan de Acción...';
            guardarPlanAccionOrigen(savedDocEntry)
                .then(function() {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando evidencias...';
                    return subirEvidencias(savedDocEntry);
                })
                .then(function(uploadResult) {
                    var evidenceMessage = uploadResult.cantidad
                        ? ' Se guardaron ' + uploadResult.cantidad + ' evidencia(s).'
                        : '';
                    return Swal.fire({
                        icon:'success',
                        title:'Guardado',
                        text:savedMessage + evidenceMessage,
                        timer:2200,
                        showConfirmButton:false
                    });
                })
                .then(function(){ window.location.href = 'index.php'; })
                .catch(function(pendingError) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save mr-2"></i>$btnLabel';
                    docentry = savedDocEntry;
                    document.getElementById('docentry').value = savedDocEntry;
                    Swal.fire({
                        icon:'warning',
                        title:'OT guardada, complemento pendiente',
                        html:evidenceEscape(savedMessage) + '<br><br><strong>No se completó el Plan de Acción o las evidencias:</strong><br>' + evidenceEscape(pendingError.message),
                        confirmButtonText:'Permanecer y reintentar'
                    });
                });
            return;
            var msg = json.action === 'inserted'
                ? 'OT #' + (json.docnum || json.docentry) + ' registrada correctamente.'
                : 'OT actualizada correctamente.';
            Swal.fire({ icon:'success', title:'¡Guardado!', text: msg, timer: 2000, showConfirmButton: false })
                .then(function(){ window.location.href = 'index.php'; });
        } else {
            alertDiv.className = 'alert alert-danger';
            alertDiv.textContent = json.error || 'Error al guardar.';
            alertDiv.style.display = '';
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>$btnLabel';
        alertDiv.className = 'alert alert-danger';
        alertDiv.textContent = 'Error de conexión: ' + err.message;
        alertDiv.style.display = '';
    });
});

// ── Init ─────────────────────────────────────────────────────────
\$(document).ready(function() {
    cargarTipo();
    cargarTecnicos();
    cargarEvidencias();
    cargarCostosOt().catch(function(){});

    if (progCodeURL && !docentry) {
        fetch('$baseUrl/api/mantenimientos/get_prog.php?code=' + encodeURIComponent(progCodeURL))
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (data && data[0]) {
                    var p   = data[0];
                    var opt = new Option(p.U_ItemCode + ' — ' + p.ItemName, p.U_ItemCode, true, true);
                    \$('#selectEquipo').append(opt).trigger('change');
                    mostrarInfoEquipo({ InventryNo: p.InventryNo, AssetSerNo: '', Marca: '', Modelo: '' });
                    cargarProgramaciones(p.U_ItemCode);
                    setTimeout(function() {
                        document.getElementById('selectTipo').value = p.U_TipoMant || '';
                    }, 500);
                }
            });
    }
});
JS;

include_once(__DIR__ . '/../../layouts/footer.php');
?>
