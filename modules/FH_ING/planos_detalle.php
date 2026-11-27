<?php
$page_title = 'Detalle de Planos e Ingeniería - FH ING';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once dirname(dirname(__DIR__)) . '/config/aps.php';
require_permiso('ots_ingeniera', 'ver');
include_once dirname(dirname(__DIR__)) . '/layouts/header.php';
$_apsEnabled = !empty(APS_CLIENT_ID) && !empty(APS_CLIENT_SECRET);
?>

<link rel="stylesheet" href="../../assets/css/fh_planos.css?v=2">
<style>
/* ── Estilos adicionales Módulo 2 ─────────────────────────────────── */
.fpd-hero {
    background: linear-gradient(135deg, #1a5c38 0%, #27ae60 100%);
    border-radius: 10px;
    padding: 22px 28px;
    color: #fff;
    margin-bottom: 18px;
}
.fpd-hero h4 { margin: 0 0 4px; font-size: 1.25rem; font-weight: 700; }
.fpd-hero p  { margin: 0; font-size: .88rem; opacity: .88; }

.fpd-inicio-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #e8f5e9;
    border: 1.5px solid #27ae60;
    color: #1a5c38;
    border-radius: 7px;
    padding: 6px 14px;
    font-size: .88rem;
    font-weight: 600;
}

/* ── Tabla de planos ────────────────────────────────────────────────── */
#tblPlanos { width: 100%; border-collapse: collapse; font-size: .875rem; }
#tblPlanos thead th {
    background: #1a5c38;
    color: #fff;
    padding: 10px 12px;
    font-weight: 600;
    white-space: nowrap;
    border-bottom: 2px solid #155c2e;
}
#tblPlanos tbody tr:nth-child(even) { background: #f6fbf7; }
#tblPlanos tbody tr:hover           { background: #e6f4ea; }
#tblPlanos td {
    padding: 8px 10px;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}
#tblPlanos .inp-cod,
#tblPlanos .inp-desc {
    width: 100%;
    padding: 5px 8px;
    border: 1.5px solid #ced4da;
    border-radius: 5px;
    font-size: .875rem;
    background: #fff;
    transition: border-color .2s;
}
#tblPlanos .inp-cod:focus,
#tblPlanos .inp-desc:focus {
    outline: none;
    border-color: #27ae60;
    box-shadow: 0 0 0 3px rgba(39,174,96,.15);
}
#tblPlanos .inp-dias {
    width: 70px;
    padding: 5px 8px;
    border: 1.5px solid #ced4da;
    border-radius: 5px;
    font-size: .875rem;
    text-align: center;
}
#tblPlanos .inp-dias:focus {
    outline: none;
    border-color: #27ae60;
    box-shadow: 0 0 0 3px rgba(39,174,96,.15);
}
#tblPlanos .td-fecha {
    font-size: .8rem;
    color: #2c7a4b;
    white-space: nowrap;
    text-align: center;
}
#tblPlanos .td-fecha.has-date { font-weight: 600; }

/* ── Archivo DWG ────────────────────────────────────────────────────── */
.dwg-upload-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.dwg-label {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    background: #f0f4f8;
    border: 1.5px dashed #adb5bd;
    border-radius: 5px;
    padding: 4px 10px;
    font-size: .8rem;
    color: #495057;
    white-space: nowrap;
    transition: border-color .2s, background .2s;
}
.dwg-label:hover { border-color: #27ae60; background: #e8f5e9; color: #1a5c38; }
.dwg-label input[type=file] { display: none; }
.dwg-uploading { font-size: .78rem; color: #888; }
.dwg-done {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .8rem;
    color: #1a5c38;
    font-weight: 600;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.dwg-download {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .8rem;
    color: #2980b9;
    text-decoration: none;
    font-weight: 600;
}
.dwg-download:hover { color: #1a6496; text-decoration: underline; }
.dwg-ver {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .8rem;
    color: #fff;
    background: #27ae60;
    border: none;
    border-radius: 5px;
    padding: 3px 10px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s;
    white-space: nowrap;
}
.dwg-ver:hover { background: #1e8449; }
.dwg-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* ── Botones de acción fila ─────────────────────────────────────────── */
.btn-del-row {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 4px;
    font-size: .9rem;
    transition: background .2s;
}
.btn-del-row:hover { background: #f8d7da; }
.btn-add-row {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #27ae60;
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: 8px 18px;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s;
}
.btn-add-row:hover { background: #1e8449; }
.btn-guardar-det {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #1a5c38, #27ae60);
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: 9px 22px;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(39,174,96,.35);
    transition: opacity .2s;
}
.btn-guardar-det:hover { opacity: .88; }
.btn-guardar-det:disabled { opacity: .55; cursor: not-allowed; }
.btn-aprobar-mod3 {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #1a3a7c, #2980b9);
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: 9px 22px;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(41,128,185,.35);
    transition: opacity .2s;
}
.btn-aprobar-mod3:hover { opacity: .88; }
.btn-aprobar-mod3:disabled { opacity: .55; cursor: not-allowed; }
.btn-revertir-mod {
    display: inline-flex; align-items: center; gap: 7px;
    background: transparent; color: #856404;
    border: 1.5px solid #ffc107;
    border-radius: 8px; padding: 8px 16px;
    font-size: .88rem; font-weight: 600; cursor: pointer;
    transition: background .2s, color .2s;
}
.btn-revertir-mod:hover { background: #fff3cd; color: #533f03; }
.btn-revertir-mod:disabled { opacity: .55; cursor: not-allowed; }

/* ── Gantt M2 colores ───────────────────────────────────────────────── */
.gantt-bar.m2 { border-radius: 4px; }

/* ── Row num badge ──────────────────────────────────────────────────── */
.row-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    color: #fff;
    font-size: .78rem;
    font-weight: 700;
    flex-shrink: 0;
}

/* ── Empty rows placeholder ─────────────────────────────────────────── */
#emptyRowsMsg {
    text-align: center;
    padding: 28px;
    color: #6c757d;
    font-size: .9rem;
}

/* ══ VISOR DE ARCHIVOS ═══════════════════════════════════════════════ */
#fileViewerOverlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,.72);
    align-items: stretch;
    justify-content: center;
}
#fileViewerOverlay.open { display: flex; align-items: flex-start; justify-content: center; }
#fileViewerPanel {
    display: flex;
    flex-direction: column;
    background: #fff;
    flex: 1;
    min-width: 0;
    overflow: hidden;
}
#fileViewerHeader {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #1a5c38;
    color: #fff;
    padding: 12px 18px;
    flex-shrink: 0;
}
#fileViewerTitle {
    flex: 1;
    font-size: .95rem;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
#fileViewerDownload {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,.18);
    color: #fff;
    border: 1.5px solid rgba(255,255,255,.45);
    border-radius: 6px;
    padding: 5px 14px;
    font-size: .82rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background .2s;
}
#fileViewerDownload:hover { background: rgba(255,255,255,.32); color:#fff; text-decoration:none; }
#fileViewerClose {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.3rem;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    line-height: 1;
    opacity: .8;
    transition: opacity .2s;
}
#fileViewerClose:hover { opacity: 1; }
#fileViewerBody {
    flex: 1;
    position: relative;
    min-height: 0;
    height: calc(96vh - 100px);
    background: #f0f0f0;
}
#fileViewerIframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}
#fileViewerFallback {
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    gap: 16px;
    padding: 40px;
    text-align: center;
    color: #495057;
}
#fileViewerFallback .fvf-icon { font-size: 3.5rem; color: #adb5bd; }
#fileViewerFallback .fvf-title { font-size: 1.05rem; font-weight: 700; color: #343a40; }
#fileViewerFallback .fvf-msg { font-size: .88rem; line-height: 1.6; max-width: 480px; }
#fileViewerFallback .fvf-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: #1a5c38; color: #fff; border: none;
    border-radius: 7px; padding: 10px 22px;
    font-size: .9rem; font-weight: 600; cursor: pointer;
    text-decoration: none;
}
#fileViewerFallback .fvf-btn:hover { background: #155c2e; color:#fff; text-decoration:none; }
#fileViewerFallback .fvf-btn-secondary {
    display: inline-flex; align-items: center; gap: 8px;
    background: transparent; color: #2980b9;
    border: 1.5px solid #2980b9;
    border-radius: 7px; padding: 7px 16px;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer;
}
#fileViewerFallback .fvf-btn-secondary:hover { background: #eaf4fb; color:#1a6496; text-decoration:none; }
.fvf-links { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-top: 4px; }

/* Loading spinner inside viewer */
#fileViewerLoading {
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    gap: 12px;
    color: #6c757d;
    font-size: .9rem;
    background: #f8f9fa;
}
#fileViewerLoading .fvl-spinner {
    width: 42px; height: 42px;
    border: 4px solid #dee2e6;
    border-top-color: #1a5c38;
    border-radius: 50%;
    animation: fvl-spin .75s linear infinite;
}
@keyframes fvl-spin { to { transform: rotate(360deg); } }

/* DXF SVG canvas — tema dark como AutoCAD */
#fileViewerDxfCanvas {
    display: none;
    width: 100%;
    min-height: 500px;
    overflow: hidden;
    background: #1e2328;
    padding: 0;
    box-sizing: border-box;
    border-radius: 4px;
    position: relative;
}
#fileViewerDxfCanvas canvas {
    display: block;
    width: 100% !important;
    height: 100% !important;
}
/* ── APS Viewer ───────────────────────────────────── */
#fileViewerApsCanvas {
    display: none;
    width: 100%;
    height: 80vh;
    min-height: 500px;
    position: relative;
    background: #1e2328;
    border-radius: 4px;
    overflow: hidden;
}
#apsProgressBar {
    position: absolute;
    bottom: 0; left: 0;
    height: 3px;
    background: #27ae60;
    width: 0%;
    transition: width .4s ease;
    z-index: 10;
}
#apsProgressMsg {
    position: absolute;
    bottom: 12px; left: 50%;
    transform: translateX(-50%);
    color: #9aabbc;
    font-size: .8rem;
    z-index: 11;
    white-space: nowrap;
}
#dxfLayoutBar {
    display: flex;
    gap: 4px;
    padding: 6px 10px;
    background: #141719;
    border-bottom: 1px solid #2e3440;
    flex-shrink: 0;
}
.dxf-layout-btn {
    background: #2e3440;
    color: #9aabbc;
    border: 1px solid #3b4554;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    padding: 4px 14px;
    font-size: .8rem;
    cursor: pointer;
    transition: background .15s;
}
.dxf-layout-btn:hover { background: #3b4554; color: #d8e0ea; }
.dxf-layout-btn.active { background: #1e2328; color: #5cb85c; border-color: #5cb85c; font-weight: 600; }
#threeDxfViewport { flex: 1; }

/* ── Annotations panel ─────────────────────────────────────── */
#fileViewerWrapper {
    display: flex;
    flex-direction: row;
    width: 96vw;
    max-width: 1300px;
    margin: 20px auto;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,.45);
    min-height: 0;
}
/* Remove old margin/radius/shadow from fileViewerPanel since wrapper now handles it */
#fileViewerPanel {
    flex: 1;
    min-width: 0;
    border-radius: 0;
    box-shadow: none;
    margin: 0;
}
#obsToggleBtn {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.18); color: #fff;
    border: 1.5px solid rgba(255,255,255,.45); border-radius: 6px;
    padding: 5px 12px; font-size: .82rem; font-weight: 600;
    white-space: nowrap; cursor: pointer; flex-shrink: 0;
    transition: background .2s;
}
#obsToggleBtn:hover, #obsToggleBtn.active { background: rgba(255,255,255,.32); }
#obsPanel {
    display: none;
    flex-direction: column;
    width: 320px;
    flex-shrink: 0;
    background: #fff;
    border-left: 1px solid #dee2e6;
}
#obsPanelHeader {
    display: flex; align-items: center; justify-content: space-between;
    background: #1a3a5c; color: #fff;
    padding: 10px 14px; flex-shrink: 0;
    font-size: .88rem; font-weight: 600;
}
#obsPanelHeader button {
    background: none; border: none; color: #fff;
    font-size: 1.2rem; cursor: pointer; line-height: 1;
    padding: 0 4px; opacity: .8;
}
#obsPanelHeader button:hover { opacity: 1; }
#obsListWrap {
    flex: 1; overflow-y: auto; padding: 10px 12px;
    display: flex; flex-direction: column; gap: 6px;
    font-size: .83rem;
}
.obs-item {
    background: #f8f9fa; border: 1px solid #e9ecef;
    border-radius: 6px; padding: 7px 10px;
}
.obs-item-meta {
    font-size: .75rem; color: #888; margin-bottom: 3px;
    display: flex; justify-content: space-between;
}
.obs-item-text { color: #343a40; line-height: 1.45; white-space: pre-wrap; word-break: break-word; }
#obsEmpty {
    text-align: center; color: #aaa; padding: 20px 10px;
    font-size: .82rem;
}
#obsForm {
    padding: 10px 12px;
    border-top: 1px solid #dee2e6;
    display: flex; flex-direction: column; gap: 7px; flex-shrink: 0;
}
#obsTexto {
    width: 100%; resize: vertical; min-height: 70px;
    border: 1.5px solid #ced4da; border-radius: 5px;
    padding: 7px 10px; font-size: .83rem; color: #343a40;
    font-family: inherit; transition: border-color .2s;
    box-sizing: border-box;
}
#obsTexto:focus { outline: none; border-color: #1a3a5c; box-shadow: 0 0 0 3px rgba(26,58,92,.12); }
#obsGuardar {
    width: 100%;
    background: #1a3a5c; color: #fff;
    border: none; border-radius: 6px;
    padding: 8px 12px; font-size: .84rem; font-weight: 600;
    cursor: pointer; display: inline-flex; align-items: center;
    justify-content: center; gap: 6px; transition: background .2s;
}
#obsGuardar:hover { background: #2980b9; }
#obsGuardar:disabled { opacity: .6; cursor: default; }
#obsMsg {
    font-size: .78rem; text-align: center; min-height: 16px;
}
</style>

<div class="container-fluid" style="max-width:1400px; padding-top:10px;">

  <!-- ══ SELECTOR DE OTs (estado=1) ══════════════════════════════════ -->
  <div id="otSelectorSection">
    <div class="fpd-hero">
      <h4><i class="fas fa-file-alt"></i> Detalle de Planos e Ingeniería — Módulo 2</h4>
      <p>Selecciona una OT aprobada del Módulo 1 para registrar el detalle de planos</p>
    </div>
    <div class="fp-act-card" style="margin-top:-6px;">
      <div class="fp-card-header">
        <span><i class="fas fa-list"></i> OTs Aprobadas (Módulo 1)</span>
        <span class="badge-count" id="badgeOTs">—</span>
      </div>
      <div class="fp-card-body">
        <div style="margin-bottom:12px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
          <div class="input-group" style="max-width:360px;">
            <div class="input-group-prepend">
              <span class="input-group-text" style="background:#f8f9fa; border-radius:7px 0 0 7px; border:1.5px solid #dee2e6; border-right:none; color:#6c757d;">
                <i class="fas fa-search"></i>
              </span>
            </div>
            <input type="text" id="filterOTs" class="form-control" style="border-radius:0 7px 7px 0; border:1.5px solid #dee2e6; border-left:none; padding:7px 12px; font-size:.9rem;" placeholder="Ej: 4135 &nbsp;ó&nbsp; OTC-004135">
          </div>
          <small class="text-muted"><i class="fas fa-hand-pointer"></i> Clic en fila para seleccionar &nbsp;·&nbsp; <kbd>Enter</kbd> si hay 1 resultado</small>
        </div>
        <div id="otListLoading" class="fp-empty">
          <i class="fas fa-spinner fa-spin"></i><p>Cargando OTs...</p>
        </div>
        <div id="otListEmpty" class="fp-empty" style="display:none;">
          <i class="fas fa-inbox"></i>
          <p>No hay OTs aprobadas. Ve a <strong>Planos e Ingeniería Básica</strong> y aprueba una OT primero.</p>
        </div>
        <div id="otListWrapper" style="display:none;">
          <div class="table-responsive">
            <table class="table table-hover" id="tblOTs">
              <thead>
                <tr>
                  <th style="width:130px;">Código OT</th>
                  <th>Proyecto</th>
                  <th>Elaborado por</th>
                  <th>Actualizado al</th>
                  <th style="width:110px;text-align:center;">Detalle</th>
                </tr>
              </thead>
              <tbody id="tblOTsBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ INFO CARD ═══════════════════════════════════════════════════ -->
  <div class="fp-info-card" id="infoCard" style="display:none;">
    <button id="btnCambiarOT" style="background:none;border:none;color:#1a5c38;font-size:.85rem;font-weight:600;cursor:pointer;padding:6px 10px;border-radius:6px;white-space:nowrap;" title="Volver al listado">
      <i class="fas fa-arrow-left"></i> Cambiar OT
    </button>
    <span class="ot-badge" id="infoOT" style="background:linear-gradient(135deg,#1a5c38,#27ae60);">—</span>
    <div class="proy-nombre" id="infoProyecto">
      <small>Nombre del proyecto</small>—
    </div>
    <div id="inicioMod2Wrap" style="margin-left:auto;">
      <span class="fpd-inicio-badge">
        <i class="fas fa-calendar-day"></i>
        Inicio Fase 2: <strong id="lblFechaInicioMod2">—</strong>
      </span>
    </div>
  </div>

  <!-- ══ PLANOS CARD ══════════════════════════════════════════════════ -->
  <div class="fp-act-card" id="planosCard" style="display:none;">
    <div class="fp-card-header" style="background:linear-gradient(135deg,#1a5c38,#27ae60);">
      <span><i class="fas fa-file-alt"></i> Tabla de Planos — pln_dtll_Ing</span>
      <span class="badge-count" id="badgePlanos">—</span>
    </div>
    <div class="fp-card-body">
      <div id="detAlert" class="fp-alert"></div>

      <div id="planosLoading" class="fp-empty">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Cargando planos...</p>
      </div>

      <div id="planosTableWrapper" style="display:none;">
        <div class="table-responsive">
          <table id="tblPlanos">
            <thead>
              <tr>
                <th style="width:32px;">#</th>
                <th style="min-width:120px;">Cód. Plano</th>
                <th style="min-width:200px;">Descripción</th>
                <th style="min-width:180px;">Archivo DWG</th>
                <th style="width:75px;text-align:center;">Días</th>
                <th style="width:100px;text-align:center;">F. Inicio</th>
                <th style="width:100px;text-align:center;">F. Fin</th>
                <th style="width:36px;"></th>
              </tr>
            </thead>
            <tbody id="tblPlanosBody">
              <tr id="emptyRowsMsg"><td colspan="8">— Sin filas. Agrega con el botón de abajo —</td></tr>
            </tbody>
          </table>
        </div>

        <div style="margin-top:16px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
          <button class="btn-add-row" id="btnAddRow">
            <i class="fas fa-plus"></i> Agregar fila
          </button>
          <button class="btn-guardar-det" id="btnGuardar">
            <i class="fas fa-save"></i> Guardar Todo
          </button>
          <small class="text-muted">
            <i class="fas fa-info-circle"></i>
            Las fechas se calculan automáticamente en secuencia.
            Formatos válidos para Días: 0.25, 0.5, 1, 1.5, 2...
          </small>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ GANTT MÓDULO 2 ═══════════════════════════════════════════════ -->
  <div class="fp-gantt-card" id="ganttSection" style="display:none;">
    <div class="fp-card-header" style="background:linear-gradient(135deg,#1a5c38,#27ae60);">
      <span><i class="fas fa-chart-bar"></i> Cronograma — Detalle de Planos</span>
      <span class="badge-count" id="ganttBadge">—</span>
    </div>
    <div id="ganttContainer"></div>
  </div>

  <!-- ══ APROBACIÓN MÓDULO 3 ══════════════════════════════════════════ -->
  <div id="aprobarSection" style="display:none; margin-bottom:16px;">
    <div class="fp-act-card">
      <div class="fp-card-body" style="padding:14px 18px;">
        <div id="aprobarAlert" class="fp-alert"></div>
        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
          <button class="btn-aprobar-mod3" id="btnAprobarMod3">
            <i class="fas fa-check-double"></i> Aprobar → Módulo 3
          </button>
          <span id="aprobadoBadge" style="display:none;" class="badge badge-primary" style="font-size:.9rem; padding:8px 14px;">
            <i class="fas fa-check-double"></i>&nbsp; Aprobado — OT en Módulo 3
          </span>
          <a id="btnIrMod3" href="planos_mod3.php" style="display:none;
              background:linear-gradient(135deg,#1a3a5c,#2980b9); color:#fff;
              border-radius:8px; padding:8px 18px; font-size:.9rem; font-weight:700;
              text-decoration:none; display:none; align-items:center; gap:7px;">
            <i class="fas fa-arrow-right"></i> Ir a Módulo 3
          </a>
          <button class="btn-revertir-mod" id="btnDevolverMod1" title="Regresar esta OT al Módulo 1">
            <i class="fas fa-undo"></i> ← Devolver a Módulo 1
          </button>
          <small class="text-muted">Aprueba el detalle de planos para habilitar el Módulo 3.</small>
        </div>
      </div>
    </div>
  </div>

</div><!-- /container-fluid -->

<!-- ══ VISOR DE ARCHIVOS (modal de pantalla completa) ══════════════ -->
<div id="fileViewerOverlay">
  <div id="fileViewerWrapper">
  <div id="fileViewerPanel">
    <div id="fileViewerHeader">
      <i class="fas fa-file-alt" style="font-size:1.1rem; flex-shrink:0;"></i>
      <span id="fileViewerTitle">—</span>
      <a id="fileViewerDownload" href="#" download title="Descargar archivo">
        <i class="fas fa-download"></i> Descargar
      </a>
      <button id="obsToggleBtn" title="Mostrar/ocultar anotaciones"><i class="fas fa-sticky-note"></i> Anotaciones</button>
      <button id="fileViewerClose" title="Cerrar">&times;</button>
    </div>
    <div id="fileViewerBody">
      <!-- Loading spinner -->
      <div id="fileViewerLoading">
        <div class="fvl-spinner"></div>
        <span>Cargando archivo…</span>
      </div>
      <!-- PDF / inline -->
      <iframe id="fileViewerIframe" src="about:blank" allowfullscreen></iframe>
      <!-- DXF → three-dxf WebGL (fallback) -->
      <div id="fileViewerDxfCanvas"></div>
      <!-- APS Viewer (principal) -->
      <div id="fileViewerApsCanvas">
        <div id="apsProgressBar"></div>
        <div id="apsProgressMsg"></div>
      </div>
      <!-- Fallback: DWG u otros no previsualizable -->
      <div id="fileViewerFallback">
        <div class="fvf-icon"><i class="fas fa-drafting-compass"></i></div>
        <div class="fvf-title" id="fvfTitle">Archivo DWG — no se puede previsualizar en el navegador</div>
        <div class="fvf-msg" id="fvfMsg">
          Los archivos DWG requieren <strong>TBM, AutoCAD u otro programa compatible</strong> para visualizarse.<br>
          Descarga el archivo y ábrelo con uno de estos visores gratuitos:
        </div>
        <div class="fvf-links">
          <a class="fvf-btn" href="#" id="fvfDownloadBtn">
            <i class="fas fa-download"></i> Descargar archivo
          </a>
          <a class="fvf-btn-secondary" href="https://www.autodesk.com/products/dwg-trueview/overview" target="_blank" rel="noopener">
            <i class="fas fa-external-link-alt"></i> Autodesk DWG TrueView (gratis)
          </a>
          <a class="fvf-btn-secondary" href="https://www.dwgfastview.com/" target="_blank" rel="noopener">
            <i class="fas fa-external-link-alt"></i> DWG FastView Web
          </a>
        </div>
      </div>
    </div>
  </div>
  <!-- ══ Panel de Anotaciones ════════════════════════ -->
  <div id="obsPanel">
    <div id="obsPanelHeader">
      <span><i class="fas fa-sticky-note"></i> Anotaciones</span>
      <button id="obsCloseBtn" title="Cerrar panel">&times;</button>
    </div>
    <div id="obsListWrap">
      <div id="obsLoading" style="text-align:center;color:#aaa;padding:14px;font-size:.82rem;"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>
      <div id="obsEmpty" style="display:none;">Sin anotaciones aún. Sé el primero en agregar una nota.</div>
    </div>
    <div id="obsForm">
      <textarea id="obsTexto" placeholder="Escribe una anotación o nota sobre este plano…"></textarea>
      <button id="obsGuardar"><i class="fas fa-plus"></i> Agregar nota</button>
      <div id="obsMsg"></div>
    </div>
  </div>
  </div><!-- /#fileViewerWrapper -->
</div>



<!-- ══ JAVASCRIPT ═══════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    var API_OTS     = '../../api/fh_ing/get_ot_detalle.php?modulo=2';
    var API_GET     = '../../api/fh_ing/get_planos_detalle.php';
    var API_UPLOAD  = '../../api/fh_ing/upload_plano_dwg.php';
    var API_GUARDAR = '../../api/fh_ing/guardar_planos_detalle.php';
    var API_ESTADO  = '../../api/fh_ing/cambiar_estado.php';
    var API_DOWN    = '../../api/fh_ing/descargar_plano_dwg.php';
    var API_VER     = '../../api/fh_ing/ver_plano.php';
    var API_DXF     = '../../api/fh_ing/get_dxf.php';
    var API_APS_TOKEN  = '../../api/fh_ing/aps_token.php';
    var API_APS_ENSURE = '../../api/fh_ing/aps_ensure.php';
    var APS_ENABLED    = true;
    console.log('[APS] APS_ENABLED:', APS_ENABLED);
    var API_OBS_GET  = '../../api/fh_ing/get_obs_plano.php';
    var API_OBS_SAVE = '../../api/fh_ing/guardar_obs_plano.php';

    var todasOTs     = [];
    var selectedOT   = '';
    var selectedProy = '';
    var fechaInicioMod2 = '';
    var rowCounter   = 0;
    var _apsViewer   = null;
    var _apsPollTimer = null;
    var _obsIdPlanos  = 0;
    var _obsCodPlano  = '';
    var _obsPanelOpen = false;

    var COLORS = ['#27ae60','#2980b9','#e67e22','#8e44ad','#16a085','#c0392b','#2c3e50','#d35400','#1abc9c','#e74c3c'];

    /* ─── Helpers ───────────────────────────────────────────────── */
    function escHtml(v) {
        if (v == null) return '';
        return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function parseDate(s) {
        if (!s) return null;
        var p = String(s).split('-');
        return new Date(parseInt(p[0]), parseInt(p[1])-1, parseInt(p[2]));
    }
    function addDays(d, n) {
        var r = new Date(d); r.setDate(r.getDate() + n); return r;
    }
    function daysBetween(a, b) {
        return Math.round((b - a) / 86400000);
    }
    function fmtDate(d) {
        if (!d) return '—';
        return String(d.getDate()).padStart(2,'0') + '/' +
               String(d.getMonth()+1).padStart(2,'0') + '/' + d.getFullYear();
    }
    function fmtShort(d) {
        if (!d) return '';
        var m = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        return String(d.getDate()).padStart(2,'0') + ' ' + m[d.getMonth()];
    }
    function fmtDateStr(s) {
        if (!s) return '—';
        var p = String(s).substring(0,10).split('-');
        return p.length === 3 ? p[2]+'/'+p[1]+'/'+p[0] : s;
    }
    function today() {
        var d = new Date();
        return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
    }
    function showAlert(id, type, msg) {
        var el = document.getElementById(id);
        el.className = 'fp-alert ' + type + ' show';
        var icon = type === 'success' ? 'fa-check-circle' : (type === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle');
        el.innerHTML = '<i class="fas ' + icon + '"></i> ' + msg;
    }
    function hideAlert(id) {
        var el = document.getElementById(id);
        el.className = 'fp-alert';
        el.innerHTML = '';
    }

    /* ─── Init ──────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('filterOTs').addEventListener('input', filtrarOTs);
        document.getElementById('filterOTs').addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            var visibles = document.querySelectorAll('#tblOTsBody tr[data-ot]');
            if (visibles.length === 1) {
                var tr = visibles[0];
                tr.click();
            }
        });
        document.getElementById('btnCambiarOT').addEventListener('click', volverAlListado);
        document.getElementById('btnAddRow').addEventListener('click', function () { addRow(null); });
        document.getElementById('btnGuardar').addEventListener('click', guardarTodo);
        document.getElementById('btnAprobarMod3').addEventListener('click', aprobarModulo3);
        document.getElementById('btnDevolverMod1').addEventListener('click', devolverModulo1);
        cargarOTsAprobadas();

        // Obs panel buttons
        document.getElementById('obsToggleBtn').addEventListener('click', function() { _setObsPanel(!_obsPanelOpen); });
        document.getElementById('obsCloseBtn').addEventListener('click', function() { _setObsPanel(false); });
        document.getElementById('obsGuardar').addEventListener('click', _guardarObs);
    });

    /* ─── Cargar OTs aprobadas (estado=1) ───────────────────────── */
    function cargarOTsAprobadas() {
        fetch(API_OTS)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) throw new Error(data.error);
                todasOTs = Array.isArray(data) ? data : [];
                renderOTList(todasOTs);
            })
            .catch(function (err) {
                document.getElementById('otListLoading').innerHTML =
                    '<i class="fas fa-exclamation-triangle"></i><p>Error: ' + escHtml(err.message) + '</p>';
            });
    }

    function renderOTList(lista) {
        var loading = document.getElementById('otListLoading');
        var empty   = document.getElementById('otListEmpty');
        var wrapper = document.getElementById('otListWrapper');
        var badge   = document.getElementById('badgeOTs');

        loading.style.display = 'none';
        badge.textContent = lista.length + ' OT' + (lista.length !== 1 ? 's' : '');

        if (!lista.length) {
            empty.style.display   = '';
            wrapper.style.display = 'none';
            return;
        }
        empty.style.display   = 'none';
        wrapper.style.display = '';

        var html = '';
        lista.forEach(function (r) {
            var ep = parseInt(r.estado_plano || 1);
            var detBadge = ep >= 2
                ? '<span class="badge badge-primary"><i class="fas fa-check-double"></i> Aprobado M2</span>'
                : (r._tiene_plano ? '<span class="badge badge-success"><i class="fas fa-check"></i> Guardado</span>'
                                  : '<span class="badge badge-secondary">—</span>');
            html += '<tr data-ot="'+escHtml(r.ot)+'" data-proy="'+escHtml(r.proyecto)+'" data-estado="'+ep+'" style="cursor:pointer;">' +
                '<td><strong>'+escHtml(r.ot)+'</strong></td>' +
                '<td>'+escHtml(r.proyecto)+'</td>' +
                '<td>'+(r.elaborado_por ? escHtml(r.elaborado_por) : '<span class="text-muted">—</span>')+'</td>' +
                '<td>'+(r.actualizado_al ? fmtDateStr(r.actualizado_al) : '<span class="text-muted">—</span>')+'</td>' +
                '<td style="text-align:center;">'+detBadge+'</td>' +
                '</tr>';
        });
        document.getElementById('tblOTsBody').innerHTML = html;
        document.querySelectorAll('#tblOTsBody tr[data-ot]').forEach(function (tr) {
            tr.addEventListener('click', function () {
                seleccionarOT(tr.getAttribute('data-ot'), tr.getAttribute('data-proy'), parseInt(tr.getAttribute('data-estado')||'1'));
            });
        });
    }

    function filtrarOTs() {
        var raw  = document.getElementById('filterOTs').value.trim();
        var term = raw.toLowerCase();
        if (!term) { renderOTList(todasOTs); return; }
        // Si el usuario escribe solo dígitos (ej: "4135"), también intenta con cero-padding
        var altTerm = '';
        if (/^\d+$/.test(raw)) {
            altTerm = raw.padStart(6, '0'); // "4135" → "004135"
        }
        renderOTList(todasOTs.filter(function (r) {
            var ot   = (r.ot      || '').toLowerCase();
            var proj = (r.proyecto|| '').toLowerCase();
            return ot.includes(term) || proj.includes(term) ||
                   (altTerm && ot.includes(altTerm));
        }));
    }

    function volverAlListado() {
        selectedOT = ''; selectedProy = ''; fechaInicioMod2 = ''; rowCounter = 0;
        document.getElementById('otSelectorSection').style.display = '';
        document.getElementById('infoCard').style.display           = 'none';
        document.getElementById('planosCard').style.display         = 'none';
        document.getElementById('ganttSection').style.display       = 'none';
        document.getElementById('aprobarSection').style.display     = 'none';
        document.getElementById('filterOTs').value = '';
        renderOTList(todasOTs);
    }

    /* ─── Seleccionar OT ────────────────────────────────────────── */
    function seleccionarOT(ot, proyecto, estadoActual) {
        selectedOT   = ot;
        selectedProy = proyecto;
        rowCounter   = 0;
        document.getElementById('infoOT').textContent = ot;
        document.getElementById('infoProyecto').innerHTML = '<small>Nombre del proyecto</small>' + escHtml(proyecto);
        document.getElementById('otSelectorSection').style.display = 'none';
        document.getElementById('infoCard').style.display   = '';
        document.getElementById('planosCard').style.display = '';
        document.getElementById('ganttSection').style.display = 'none';
        document.getElementById('aprobarSection').style.display = 'none';
        hideAlert('detAlert');

        cargarPlanos(ot, estadoActual);
    }

    /* ─── Cargar planos existentes ──────────────────────────────── */
    function cargarPlanos(ot, estadoActual) {
        var loading = document.getElementById('planosLoading');
        var wrapper = document.getElementById('planosTableWrapper');
        loading.style.display = '';
        wrapper.style.display = 'none';

        fetch(API_GET + '?ot=' + encodeURIComponent(ot))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) throw new Error(data.error);
                fechaInicioMod2 = data.fecha_inicio_mod2 || today();
                document.getElementById('lblFechaInicioMod2').textContent = fmtDateStr(fechaInicioMod2);

                loading.style.display = 'none';
                wrapper.style.display = '';

                renderRowsFromSaved(data.rows || []);
                recalcularFechas();

                // Gantt si hay filas guardadas con fechas
                if (data.rows && data.rows.length) {
                    try { renderGantt(data.rows); } catch (e) { console.warn('[Gantt] render error:', e); }
                }

                // Sección aprobar
                var estado = data.estado_mod1 || estadoActual || 1;
                mostrarSeccionAprobar(estado);
            })
            .catch(function (err) {
                loading.style.display = 'none';
                wrapper.style.display = '';
                showAlert('detAlert', 'error', 'Error al cargar planos: ' + escHtml(err.message));
                renderRowsFromSaved([]);
                recalcularFechas();
            });
    }

    /* ─── Renderizar filas existentes ───────────────────────────── */
    function renderRowsFromSaved(rows) {
        var tbody = document.getElementById('tblPlanosBody');
        tbody.innerHTML = '';
        rowCounter = 0;
        if (!rows.length) {
            tbody.innerHTML = '<tr id="emptyRowsMsg"><td colspan="8">— Sin filas. Agrega con el botón de abajo —</td></tr>';
            document.getElementById('badgePlanos').textContent = '0 planos';
            return;
        }
        rows.forEach(function (r) { addRow(r); });
        document.getElementById('badgePlanos').textContent = rows.length + ' plano' + (rows.length !== 1 ? 's' : '');
    }

    /* ─── Agregar fila (nueva o con datos) ──────────────────────── */
    function addRow(data) {
        // Eliminar fila vacía placeholder
        var emp = document.getElementById('emptyRowsMsg');
        if (emp) emp.remove();

        rowCounter++;
        var rid    = 'row_' + rowCounter;
        var color  = COLORS[(rowCounter - 1) % COLORS.length];
        var idDB   = data ? (data.id_planos || '') : '';
        var cod    = data ? escHtml(data.cod_plano    || '') : '';
        var desc   = data ? escHtml(data.desc_plano   || '') : '';
        var arch   = data ? (data.arch_plano   || '') : '';
        var archOr = data ? (data.arch_original || '') : '';
        var dias   = data ? (parseFloat(data.dias) || 0) : 0;

        // Construir celda de archivo
        var archHtml;
        if (arch) {
            archHtml =
                '<div class="dwg-upload-wrap">' +
                '<div class="dwg-actions">' +
                '<button class="dwg-ver" type="button" data-id="' + escHtml(idDB) + '" data-name="' + escHtml(archOr || arch) + '" data-cod="' + escHtml(cod) + '" title="Ver archivo en esta página">' +
                '<i class="fas fa-eye"></i> Ver</button>' +
                '<a class="dwg-download" href="' + API_DOWN + '?id=' + escHtml(idDB) + '" title="Descargar">' +
                '<i class="fas fa-download"></i></a>' +
                '<span class="dwg-done" style="font-size:.78rem;color:#555;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + escHtml(archOr || arch) + '">' + escHtml(archOr || arch) + '</span>' +
                '</div>' +
                '<label class="dwg-label" title="Reemplazar archivo">' +
                '<i class="fas fa-sync-alt"></i>' +
                '<input type="file" accept=".dwg,.dxf,.pdf,.dws,.dwt" data-row="' + rid + '">' +
                '</label>' +
                '<span class="dwg-uploading" id="uploading_' + rid + '" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>' +
                '<span class="dwg-done" id="done_' + rid + '" style="display:none;"></span>' +
                '</div>';
        } else {
            archHtml =
                '<div class="dwg-upload-wrap">' +
                '<label class="dwg-label">' +
                '<i class="fas fa-upload"></i> Elegir DWG' +
                '<input type="file" accept=".dwg,.dxf,.pdf,.dws,.dwt" data-row="' + rid + '">' +
                '</label>' +
                '<span class="dwg-uploading" id="uploading_' + rid + '" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>' +
                '<span class="dwg-done" id="done_' + rid + '" style="display:none;"></span>' +
                '</div>';
        }

        var tr = document.createElement('tr');
        tr.id = rid;
        tr.setAttribute('data-id-db', idDB);
        tr.setAttribute('data-arch-plano', arch);
        tr.setAttribute('data-arch-original', archOr);
        tr.innerHTML =
            '<td><span class="row-num" style="background:'+color+'">'+rowCounter+'</span></td>' +
            '<td><input class="inp-cod"  type="text"   placeholder="T033138"            value="'+cod+'"></td>' +
            '<td><input class="inp-desc" type="text"   placeholder="ELUTRATION MANIFOLD" value="'+desc+'"></td>' +
            '<td>' + archHtml + '</td>' +
            '<td><input class="inp-dias" type="number" min="0" step="0.25" value="'+dias+'"></td>' +
            '<td class="td-fecha" id="fi_'+rid+'">—</td>' +
            '<td class="td-fecha" id="ff_'+rid+'">—</td>' +
            '<td><button class="btn-del-row" data-row="'+rid+'" title="Eliminar fila"><i class="fas fa-times"></i></button></td>';

        document.getElementById('tblPlanosBody').appendChild(tr);

        // Event: días cambia → recalcular
        tr.querySelector('.inp-dias').addEventListener('input', recalcularFechas);

        // Event: file input → auto-upload
        var fileInp = tr.querySelector('input[type=file]');
        if (fileInp) {
            fileInp.addEventListener('change', function () { subirArchivo(this, rid, tr); });
        }

        // Event: eliminar fila
        tr.querySelector('.btn-del-row').addEventListener('click', function () { eliminarFila(rid); });

        // Event: ver archivo (botón dentro de la celda)
        var btnVer = tr.querySelector('.dwg-ver[data-id]');
        if (btnVer) {
            btnVer.addEventListener('click', function () {
                abrirVisor(this.getAttribute('data-id'), this.getAttribute('data-name'), this.getAttribute('data-cod'));
            });
        }

        actualizarBadgePlanos();
        // Si es fila nueva (no cargada desde BD), hacer scroll y recalcular fechas
        if (!data) {
            tr.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            recalcularFechas();
        }
    }

    /* ─── Visor de archivos ─────────────────────────────────── */
    // DWG/DWS/DWT → el servidor busca el DXF hermano y renderiza SVG
    // PDF → iframe nativo del navegador
    var PREVIEW_EXTS = ['dxf', 'dwg', 'dws', 'dwt'];  // todos van por ver_plano.php

    function _visorReset() {
        if (_pendingTimer) { clearInterval(_pendingTimer); _pendingTimer = null; }
        if (_apsPollTimer) { clearTimeout(_apsPollTimer); _apsPollTimer = null; }
        document.getElementById('fileViewerIframe').style.display    = 'none';
        document.getElementById('fileViewerIframe').src              = 'about:blank';
        document.getElementById('fileViewerDxfCanvas').style.display = 'none';
        document.getElementById('fileViewerDxfCanvas').innerHTML     = '';
        document.getElementById('fileViewerApsCanvas').style.display = 'none';
        var tabs = document.getElementById('apsSheetTabs');
        if (tabs) tabs.remove();
        document.getElementById('fileViewerFallback').style.display  = 'none';
        document.getElementById('fileViewerLoading').style.display   = 'none';
    }

    function _visorShowFallback(errMsg, id, nombre) {
        var titleEl = document.getElementById('fvfTitle');
        var msgEl   = document.getElementById('fvfMsg');
        var dlBtn   = document.getElementById('fvfDownloadBtn');
        dlBtn.href      = API_DOWN + '?id=' + encodeURIComponent(id);
        dlBtn.setAttribute('download', nombre || 'archivo');

        // Distinguir "preview pendiente" de error genérico
        var isPending = errMsg && (
            errMsg.indexOf('generar_svgs') !== -1 ||
            errMsg.indexOf('proc_blocked')  !== -1 ||
            errMsg.indexOf('Preview no disponible') !== -1
        );

        if (isPending) {
            titleEl.textContent = 'Previsualización no generada aún';
            msgEl.innerHTML = 'El SVG de este plano aún no ha sido generado. '
                + 'Ejecuta en el servidor:<br>'
                + '<code style="background:#f4f4f4;padding:2px 6px;border-radius:3px;font-size:12px;">'
                + 'C:/Python313/python.exe src/helpers/generar_svgs.py'
                + '</code><br><br>'
                + 'Mientras tanto, puedes descargarlo para abrirlo con AutoCAD u otro programa.';
        } else {
            titleEl.textContent = 'No se pudo previsualizar el archivo';
            msgEl.innerHTML = (errMsg || 'Ocurrió un error al procesar el archivo en el servidor.') +
                '<br>Descárgalo para abrirlo con un programa compatible.';
        }
        document.getElementById('fileViewerFallback').style.display = 'flex';
    }

    function abrirVisor(id, nombre, codPlano) {
        var overlay = document.getElementById('fileViewerOverlay');
        var title   = document.getElementById('fileViewerTitle');
        var dlBtn   = document.getElementById('fileViewerDownload');

        var ext   = (nombre || '').split('.').pop().toLowerCase();
        var isCad = PREVIEW_EXTS.indexOf(ext) !== -1;

        title.textContent = nombre || 'Archivo';
        dlBtn.href        = API_DOWN + '?id=' + encodeURIComponent(id);
        dlBtn.setAttribute('download', nombre || 'archivo');

        // Store obs context
        _obsIdPlanos  = parseInt(id, 10) || 0;
        _obsCodPlano  = codPlano || '';
        // If obs panel was open before, reload observations for new file
        if (_obsPanelOpen) { _cargarObs(_obsIdPlanos); }

        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        _visorReset();

        if (isCad) {
            document.getElementById('fileViewerLoading').style.display = 'flex';
            renderizarDXF(id, nombre);
        } else {
            // PDF u otro: iframe
            var iframe = document.getElementById('fileViewerIframe');
            iframe.style.display = 'block';
            iframe.src = API_DOWN + '?id=' + encodeURIComponent(id) + '&ver=1';
        }
    }

    function cerrarVisor() {
        document.getElementById('fileViewerOverlay').classList.remove('open');
        document.body.style.overflow = '';
        _visorReset();
        // Close obs panel too
        _setObsPanel(false);
    }

    /* ─── Viewer DXF three-dxf (WebGL) ─────────────────────────── */
    // ── Viewer DXF con three-dxf (WebGL) — soporta Model + Layouts ────────
    var _threeDxfRenderer = null;
    var _dxfParsed        = null;   // DXF parseado en memoria para cambio de layout

    /* Layouts: three-dxf no soporta paper space (VIEWPORT entities).
       Se muestra solo Model (espacio modelo) donde está toda la geometría. */
    function _getLayouts(dxf) {
        var allEnts   = dxf.entities || [];
        var modelEnts = allEnts.filter(function(e) { return !e.inPaperSpace; });
        return [{ name: 'Model', entities: modelEnts.length ? modelEnts : allEnts }];
    }

    function _destroyRenderer() {
        if (_threeDxfRenderer) {
            try { _threeDxfRenderer.dispose ? _threeDxfRenderer.dispose() : null; } catch(e){}
            _threeDxfRenderer = null;
        }
    }

    function _mountLayout(layoutEntities, viewport) {
        _destroyRenderer();
        viewport.innerHTML = '';
        var container = document.getElementById('fileViewerDxfCanvas');
        var w = container.offsetWidth  || 900;
        var h = Math.max(480, Math.round(w * 0.6));
        viewport.style.width  = w + 'px';
        viewport.style.height = h + 'px';
        // Crear copia del DXF con las entidades del layout seleccionado
        var dxfCopy = {};
        for (var k in _dxfParsed) { if (_dxfParsed.hasOwnProperty(k)) dxfCopy[k] = _dxfParsed[k]; }
        dxfCopy.entities = layoutEntities;
        try {
            _threeDxfRenderer = new ThreeDxf.Viewer(dxfCopy, viewport, w, h, { font: null });
        } catch(e) {
            console.warn('[DXF] Error montando layout:', e.message);
            viewport.innerHTML = '<div style="color:#e74c3c;padding:20px;font-size:.9rem;">Error al renderizar este layout: ' + e.message + '</div>';
        }
    }

    /* ─── Renderizador principal: APS Viewer ──────────────────────── */
    function renderizarDXF(id, nombre) {
        if (APS_ENABLED) {
            _renderizarConAPS(id, nombre);
        } else {
            _renderizarConThreeDxf(id, nombre);
        }
    }

    function _renderizarConAPS(id, nombre) {
        var loading    = document.getElementById('fileViewerLoading');
        var apsCanvas  = document.getElementById('fileViewerApsCanvas');
        var dxfCanvas  = document.getElementById('fileViewerDxfCanvas');
        var progressBar = document.getElementById('apsProgressBar');
        var progressMsg = document.getElementById('apsProgressMsg');

        loading.style.display   = 'flex';
        apsCanvas.style.display = 'none';
        dxfCanvas.style.display = 'none';
        if (_apsPollTimer) { clearTimeout(_apsPollTimer); _apsPollTimer = null; }

        var _apsStartTime = Date.now();
        var _apsFallbackShown = false;

        function pollEnsure() {
            fetch(API_APS_ENSURE + '?id=' + encodeURIComponent(id))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'success') {
                        _apsFallbackShown = false;
                        _loadApsViewer(data.urn, apsCanvas, loading);
                    } else if (data.status === 'failed') {
                        console.warn('[APS] Falló traducción, usando three-dxf:', data.message);
                        loading.style.display = 'none';
                        _renderizarConThreeDxf(id, nombre);
                    } else {
                        // pending o inprogress → mostrar progreso y reintentar
                        var secs = Math.round((Date.now() - _apsStartTime) / 1000);
                        loading.style.display   = 'none';
                        apsCanvas.style.display = 'block';
                        progressBar.style.width = Math.max(data.progress || 0, 2) + '%';
                        // Mensaje según etapa
                        var msg = data.message || 'Procesando…';
                        if ((data.progress || 0) <= 3) {
                            msg = 'Subiendo archivo a la nube… (' + secs + 's)';
                        } else if ((data.progress || 0) < 100) {
                            msg = 'Traduciendo plano: ' + (data.progress || 0) + '% (' + secs + 's)';
                        }
                        progressMsg.textContent = msg;
                        // Mostrar botón de descarga si lleva más de 90 segundos
                        if (!_apsFallbackShown && secs > 90) {
                            _apsFallbackShown = true;
                            var dlHtml = '<div style="margin-top:8px;font-size:12px;color:#888;">Procesando en segundo plano…' +
                                '<a href="' + API_DOWN + '?id=' + encodeURIComponent(id) + '" download="' + (nombre || 'plano') +
                                '" style="margin-left:6px;color:#3498db;">Descargar DWG</a></div>';
                            var existing = apsCanvas.querySelector('.aps-dl-hint');
                            if (!existing) {
                                var el = document.createElement('div');
                                el.className = 'aps-dl-hint';
                                el.innerHTML = dlHtml;
                                apsCanvas.appendChild(el);
                            }
                        }
                        _apsPollTimer = setTimeout(pollEnsure, 3000);
                    }
                })
                .catch(function(err) {
                    console.warn('[APS] Error en ensure, usando three-dxf:', err.message);
                    loading.style.display = 'none';
                    _renderizarConThreeDxf(id, nombre);
                });
        }
        pollEnsure();
    }

    function _loadApsViewer(urn, container, loading) {
        var progressBar = document.getElementById('apsProgressBar');
        var progressMsg = document.getElementById('apsProgressMsg');
        progressBar.style.width = '0%';
        progressMsg.textContent = '';

        function _initAndLoad() {
            if (_apsViewer) {
                // Viewer ya inicializado — solo cargar nuevo documento
                _apsLoadDoc(_apsViewer, urn);
                return;
            }
            Autodesk.Viewing.Initializer({
                env:            'AutodeskProduction2',
                api:            'streamingV2',
                getAccessToken: function(cb) {
                    fetch(API_APS_TOKEN)
                        .then(function(r) { return r.json(); })
                        .then(function(d) { cb(d.access_token, d.expires_in); })
                        .catch(function()  { cb('', 0); });
                }
            }, function() {
                _apsViewer = new Autodesk.Viewing.GuiViewer3D(container, {
                    extensions: ['Autodesk.DocumentBrowser']
                });
                var startCode = _apsViewer.start();
                if (startCode > 0) {
                    console.warn('[APS] Viewer.start() error code:', startCode);
                    loading.style.display = 'none';
                    _renderizarConThreeDxf(null, null);
                    return;
                }
                _apsLoadDoc(_apsViewer, urn);
            });
        }

        if (typeof Autodesk === 'undefined') {
            // APS Viewer script aún no cargado — carga dinámica
            var link = document.createElement('link');
            link.rel  = 'stylesheet';
            link.href = 'https://developer.api.autodesk.com/modelderivative/v2/viewers/7.*/style.min.css';
            document.head.appendChild(link);

            var script = document.createElement('script');
            script.src = 'https://developer.api.autodesk.com/modelderivative/v2/viewers/7.*/viewer3D.min.js';
            script.onload = function() {
                loading.style.display   = 'none';
                container.style.display = 'block';
                _initAndLoad();
            };
            script.onerror = function() {
                console.warn('[APS] No se pudo cargar el viewer (sin internet?)');
                loading.style.display = 'none';
                _renderizarConThreeDxf(null, null);
            };
            document.body.appendChild(script);
        } else {
            loading.style.display   = 'none';
            container.style.display = 'block';
            _initAndLoad();
        }
    }

    function _apsLoadDoc(viewer, urn) {
        Autodesk.Viewing.Document.load(
            'urn:' + urn,
            function(doc) {
                var root = doc.getRoot();
                // Layouts (papel) = role:'2d' | Model space = role:'3d'
                var views2d = root.search({ type: 'geometry', role: '2d' });
                var views3d = root.search({ type: 'geometry', role: '3d' });
                var allViews = views2d.concat(views3d);
                if (!allViews.length) {
                    allViews = root.search({ type: 'geometry' });
                }
                if (!allViews.length) {
                    console.warn('[APS] No hay vistas en el documento');
                    return;
                }
                // Cargar primer layout 2D (si existe), sino el primero disponible
                var defaultView = views2d.length ? views2d[0] : allViews[0];
                viewer.loadDocumentNode(doc, defaultView);
                // Mostrar tabs si hay más de una vista
                if (allViews.length > 1) {
                    _buildApsSheetTabs(viewer, doc, allViews, 0);
                }
            },
            function(code, msg) {
                console.warn('[APS] Error cargando documento:', code, msg);
            }
        );
    }

    function _buildApsSheetTabs(viewer, doc, views, activeIdx) {
        var existing = document.getElementById('apsSheetTabs');
        if (existing) existing.remove();

        var tabsDiv = document.createElement('div');
        tabsDiv.id = 'apsSheetTabs';
        tabsDiv.style.cssText = 'position:absolute;top:0;left:0;right:0;z-index:100;'
            + 'background:#1a1e23;border-bottom:1px solid #333;display:flex;overflow-x:auto;flex-shrink:0;';

        views.forEach(function(view, idx) {
            var tab = document.createElement('button');
            tab.textContent = view.data.name || ('Vista ' + (idx + 1));
            tab.dataset.idx = idx;
            tab.style.cssText = 'padding:5px 14px;background:' + (idx === activeIdx ? '#27ae60' : 'transparent')
                + ';color:#ddd;border:none;border-right:1px solid #333;cursor:pointer;white-space:nowrap;font-size:12px;flex-shrink:0;';
            tab.addEventListener('click', function() {
                viewer.loadDocumentNode(doc, view);
                Array.from(tabsDiv.children).forEach(function(b) {
                    b.style.background = 'transparent';
                    b.style.color = '#ddd';
                });
                tab.style.background = '#27ae60';
                tab.style.color = '#fff';
            });
            tabsDiv.appendChild(tab);
        });

        var container = document.getElementById('fileViewerApsCanvas');
        container.appendChild(tabsDiv);
    }

    /* ─── Fallback: three-dxf WebGL ─────────────────────────────── */
    function _renderizarConThreeDxf(id, nombre) {
        var container = document.getElementById('fileViewerDxfCanvas');
        var loading   = document.getElementById('fileViewerLoading');

        loading.style.display   = 'flex';
        container.style.display = 'none';
        container.innerHTML     = '';
        _destroyRenderer();
        _dxfParsed = null;

        if (!id) {
            loading.style.display = 'none';
            _visorShowFallback('APS Viewer no disponible', null, nombre);
            return;
        }

        fetch(API_DXF + '?id=' + encodeURIComponent(id))
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function(dxfText) {
                var parser = new DxfParser();
                try { _dxfParsed = parser.parseSync(dxfText); }
                catch(e) { throw new Error('Error al parsear DXF: ' + e.message); }

                var layouts = _getLayouts(_dxfParsed);
                container.style.display = 'block';

                var viewport = document.createElement('div');
                viewport.id = 'threeDxfViewport';
                container.appendChild(viewport);

                loading.style.display = 'none';
                _mountLayout(layouts[0].entities, viewport);
            })
            .catch(function(err) {
                console.warn('[three-dxf] Error:', err.message);
                loading.style.display = 'none';
                _renderizarDXFcomoSVG(id, nombre);
            });
    }

    // Fallback: viewer SVG original (por si three-dxf falla con el DXF)
    function _renderizarDXFcomoSVG(id, nombre) {
        var url = API_VER + '?id=' + encodeURIComponent(id);
        var canvas  = document.getElementById('fileViewerDxfCanvas');
        var loading = document.getElementById('fileViewerLoading');
        loading.style.display = 'flex';

        fetch(url, { cache: 'no-store' })
            .then(function (r) {
                var ct = r.headers.get('Content-Type') || '';
                if (ct.indexOf('json') !== -1) {
                    return r.json().then(function (data) {
                        if (data.error === 'pending') throw new Error('__pending__');
                        throw new Error(data.error || 'Error desconocido');
                    });
                }
                if (!r.ok) throw new Error('Error HTTP ' + r.status);
                return r.text();
            })
            .then(function (svgString) {
                if (!svgString || svgString.indexOf('<svg') === -1)
                    throw new Error('Respuesta no es SVG válido');
                canvas.innerHTML = svgString;
                var svgEl = canvas.querySelector('svg');
                if (svgEl) {
                    svgEl.removeAttribute('width');
                    svgEl.removeAttribute('height');
                    svgEl.style.cssText = 'width:100%;height:auto;display:block;';
                    svgEl.setAttribute('preserveAspectRatio', 'xMidYMid meet');
                    // VENS para SVGs sin escala
                    if (svgString.indexOf('non-scaling-stroke') === -1) {
                        var vs = document.createElementNS('http://www.w3.org/2000/svg','style');
                        vs.textContent = 'path,line,polyline,polygon,circle,ellipse{vector-effect:non-scaling-stroke}';
                        var defs = svgEl.querySelector('defs');
                        if (defs) defs.insertBefore(vs, defs.firstChild);
                        else svgEl.insertBefore(vs, svgEl.firstChild);
                        svgEl.querySelectorAll('style').forEach(function(s) {
                            s.textContent = s.textContent.replace(/stroke-width:\s*([\d.]+)/g, function(_,n) {
                                var v = parseFloat(n);
                                return 'stroke-width:' + (isNaN(v)?n:Math.max(0.5,Math.round(v/4*100)/100));
                            });
                        });
                    }
                }
                loading.style.display = 'none';
                canvas.style.display  = 'block';
            })
            .catch(function(err) {
                var msg = err.message || '';
                if (msg === '__pending__') { _visorShowPending(id, nombre); return; }
                loading.style.display = 'none';
                _visorShowFallback(msg || null, id, nombre);
            });
    }

    var _pendingTimer = null;
    function _visorShowPending(id, nombre) {
        document.getElementById('fileViewerLoading').style.display = 'none';
        var fb = document.getElementById('fileViewerFallback');
        fb.style.display = 'flex';
        fb.innerHTML =
            '<div class="fvf-icon"><i class="fas fa-cog fa-spin"></i></div>' +
            '<div class="fvf-title">Previsualización en generación</div>' +
            '<div class="fvf-msg">El archivo está siendo convertido en el servidor. ' +
            'Se actualizará automáticamente cuando esté listo.</div>' +
            '<div class="fvf-msg" style="font-size:.8rem;color:#888;margin-top:8px;">Reintentando en <span id="pendingCountdown">15</span>s...</div>';

        var secs = 15;
        if (_pendingTimer) clearInterval(_pendingTimer);
        _pendingTimer = setInterval(function () {
            secs--;
            var cd = document.getElementById('pendingCountdown');
            if (cd) cd.textContent = secs;
            if (secs <= 0) {
                clearInterval(_pendingTimer);
                _pendingTimer = null;
                // Solo reintentar si el visor sigue abierto
                if (document.getElementById('fileViewerOverlay').classList.contains('open')) {
                    fb.style.display = 'none';
                    document.getElementById('fileViewerLoading').style.display = 'flex';
                    renderizarDXF(id, nombre);
                }
            }
        }, 1000);
    }

    document.getElementById('fileViewerClose').addEventListener('click', cerrarVisor);
    document.getElementById('fileViewerOverlay').addEventListener('click', function (e) {
        if (e.target === this) cerrarVisor();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrarVisor();
    });

    /* ─── Subir archivo DWG (auto-upload on change) ─────────────── */
    function subirArchivo(input, rid, tr) {
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];

        var uploading = document.getElementById('uploading_' + rid);
        var done      = document.getElementById('done_' + rid);
        uploading.style.display = '';
        if (done) done.style.display = 'none';

        var fd = new FormData();
        fd.append('file', file);

        fetch(API_UPLOAD, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                uploading.style.display = 'none';
                if (res.ok) {
                    tr.setAttribute('data-arch-plano',    res.saved_name);
                    tr.setAttribute('data-arch-original', res.original_name);
                    if (done) {
                        done.style.display = '';
                        done.innerHTML = '<i class="fas fa-check-circle" style="color:#27ae60;"></i> ' + escHtml(res.original_name);
                    }
                    showAlert('detAlert', 'success',
                        '<b>Archivo subido correctamente.</b> La previsualización se generará automáticamente en unos minutos.'
                    );
                } else {
                    showAlert('detAlert', 'error', 'Error al subir archivo: ' + escHtml(res.error || 'desconocido'));
                }
            })
            .catch(function (err) {
                uploading.style.display = 'none';
                showAlert('detAlert', 'error', 'Error de conexión al subir archivo: ' + escHtml(err.message));
            });
    }

    /* ─── Eliminar fila ─────────────────────────────────────────── */
    function eliminarFila(rid) {
        var tr = document.getElementById(rid);
        if (!tr) return;
        tr.remove();
        renumerarFilas();
        recalcularFechas();
        actualizarBadgePlanos();
        // Si no quedan filas, mostrar placeholder
        var tbody = document.getElementById('tblPlanosBody');
        if (!tbody.querySelector('tr[id^="row_"]')) {
            tbody.innerHTML = '<tr id="emptyRowsMsg"><td colspan="8">— Sin filas. Agrega con el botón de abajo —</td></tr>';
        }
    }

    function renumerarFilas() {
        var filas = document.querySelectorAll('#tblPlanosBody tr[id^="row_"]');
        filas.forEach(function (tr, i) {
            var badge = tr.querySelector('.row-num');
            if (badge) {
                badge.textContent = (i + 1);
                badge.style.background = COLORS[i % COLORS.length];
            }
        });
    }

    function actualizarBadgePlanos() {
        var n = document.querySelectorAll('#tblPlanosBody tr[id^="row_"]').length;
        document.getElementById('badgePlanos').textContent = n + ' plano' + (n !== 1 ? 's' : '');
    }

    /* ─── Recalcular fechas en tiempo real ──────────────────────── */
    function recalcularFechas() {
        if (!fechaInicioMod2) return;
        var filas = document.querySelectorAll('#tblPlanosBody tr[id^="row_"]');
        var current = parseDate(fechaInicioMod2);

        filas.forEach(function (tr) {
            var rid   = tr.id;
            var dias  = parseFloat(tr.querySelector('.inp-dias').value) || 0;
            var tdFi  = document.getElementById('fi_' + rid);
            var tdFf  = document.getElementById('ff_' + rid);

            if (dias > 0) {
                var ceilDias = Math.ceil(dias);
                var fi = new Date(current);
                var ff = addDays(current, ceilDias - 1);
                if (tdFi) { tdFi.textContent = fmtDate(fi); tdFi.classList.add('has-date'); }
                if (tdFf) { tdFf.textContent = fmtDate(ff); tdFf.classList.add('has-date'); }
                current = addDays(ff, 1);
            } else {
                if (tdFi) { tdFi.textContent = '—'; tdFi.classList.remove('has-date'); }
                if (tdFf) { tdFf.textContent = '—'; tdFf.classList.remove('has-date'); }
            }
        });
    }

    /* ─── Guardar todo ──────────────────────────────────────────── */
    function guardarTodo() {
        if (!selectedOT) { showAlert('detAlert','error','Selecciona una OT primero.'); return; }

        var filas  = document.querySelectorAll('#tblPlanosBody tr[id^="row_"]');
        if (!filas.length) { showAlert('detAlert','error','Agrega al menos una fila antes de guardar.'); return; }

        // Verificar si algún archivo está subiendo aún
        var uploading = document.querySelectorAll('.dwg-uploading');
        for (var u = 0; u < uploading.length; u++) {
            if (uploading[u].style.display !== 'none') {
                showAlert('detAlert','error','Espera a que termine la subida de archivos.'); return;
            }
        }

        var rows = [];
        filas.forEach(function (tr) {
            rows.push({
                cod_plano:     tr.querySelector('.inp-cod').value.trim(),
                desc_plano:    tr.querySelector('.inp-desc').value.trim(),
                arch_plano:    tr.getAttribute('data-arch-plano')    || '',
                arch_original: tr.getAttribute('data-arch-original') || '',
                dias:          parseFloat(tr.querySelector('.inp-dias').value) || 0,
            });
        });

        var btn = document.getElementById('btnGuardar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        hideAlert('detAlert');

        fetch(API_GUARDAR, {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify({ ot: selectedOT, fecha_inicio: fechaInicioMod2, rows: rows }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                showAlert('detAlert', 'success', data.message);
                renderGantt(data.rows);
                // Mostrar sección de aprobación si no estaba
                document.getElementById('aprobarSection').style.display = '';
                // Actualizar datos-id-db en filas (para descarga futura)
                var filasDom = document.querySelectorAll('#tblPlanosBody tr[id^="row_"]');
                data.rows.forEach(function (r, i) {
                    if (filasDom[i]) filasDom[i].setAttribute('data-id-db', r.id_planos);
                });
            } else {
                showAlert('detAlert', 'error', data.message || 'Error al guardar.');
            }
        })
        .catch(function (err) {
            showAlert('detAlert', 'error', 'Error: ' + err.message);
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Guardar Todo';
        });
    }

    /* ─── Sección de aprobación ─────────────────────────────────── */
    function mostrarSeccionAprobar(estado) {
        var sec       = document.getElementById('aprobarSection');
        var btn       = document.getElementById('btnAprobarMod3');
        var badge     = document.getElementById('aprobadoBadge');
        var irMod3    = document.getElementById('btnIrMod3');
        var btnRevert = document.getElementById('btnDevolverMod1');
        sec.style.display = '';
        if (estado >= 2) {
            btn.style.display       = 'none';
            badge.style.display     = '';
            irMod3.style.display    = 'inline-flex';
            btnRevert.style.display = 'inline-flex';
        } else {
            btn.style.display       = '';
            badge.style.display     = 'none';
            irMod3.style.display    = 'none';
            btnRevert.style.display = 'inline-flex';
        }
    }

    function devolverModulo1() {
        if (!selectedOT) return;
        if (!confirm('¿Devolver "' + selectedOT + '" al Módulo 1?\nSe perderá la aprobación y la OT volverá a estar disponible en el Módulo 1.')) return;
        var btn = document.getElementById('btnDevolverMod1');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Devolviendo…';
        hideAlert('aprobarAlert');
        fetch(API_ESTADO, {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify({ ot: selectedOT, estado: 0 }),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                todasOTs = todasOTs.filter(function(r) { return r.ot !== selectedOT; });
                volverAlListado();
            } else {
                showAlert('aprobarAlert', 'error', data.message || 'Error.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-undo"></i> ← Devolver a Módulo 1';
            }
        })
        .catch(function(err) {
            showAlert('aprobarAlert', 'error', 'Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-undo"></i> ← Devolver a Módulo 1';
        });
    }

    function aprobarModulo3() {
        if (!selectedOT) return;
        var btn = document.getElementById('btnAprobarMod3');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aprobando...';
        hideAlert('aprobarAlert');

        fetch(API_ESTADO, {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify({ ot: selectedOT, estado: 2 }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                showAlert('aprobarAlert', 'success', data.message);
                mostrarSeccionAprobar(2);
                todasOTs = todasOTs.filter(function (r) { return r.ot !== selectedOT; });
            } else {
                showAlert('aprobarAlert', 'error', data.message || 'Error.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-double"></i> Aprobar → Módulo 3';
            }
        })
        .catch(function (err) {
            showAlert('aprobarAlert', 'error', 'Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-double"></i> Aprobar → Módulo 3';
        });
    }

    /* ─── Render Gantt ──────────────────────────────────────────── */
    function renderGantt(rows) {
        var active = (rows || []).filter(function (r) { return parseFloat(r.dias) > 0 && r.fecha_inicio && r.fecha_fin; });
        var section   = document.getElementById('ganttSection');
        var container = document.getElementById('ganttContainer');
        if (!active.length) { section.style.display = 'none'; return; }

        var pStart    = parseDate(active[0].fecha_inicio);
        var pEnd      = parseDate(active[active.length - 1].fecha_fin);
        var totalDays = daysBetween(pStart, pEnd) + 1;
        if (totalDays <= 0) { section.style.display = 'none'; return; }

        // Segmentos por mes
        var meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        var segments = [];
        var cur = new Date(pStart.getFullYear(), pStart.getMonth(), 1);
        while (cur <= pEnd) {
            var segStart = cur < pStart ? pStart : new Date(cur);
            var nextM    = new Date(cur.getFullYear(), cur.getMonth() + 1, 1);
            var segEnd   = nextM > pEnd ? pEnd : new Date(nextM.getTime() - 86400000);
            var segDays  = daysBetween(segStart, segEnd) + 1;
            var pct      = (segDays / totalDays * 100).toFixed(3);
            segments.push({ label: meses[cur.getMonth()] + ' ' + cur.getFullYear(), pct: pct });
            cur = nextM;
        }

        var html = '<div class="gantt-wrapper">';
        // Header
        html += '<div class="gantt-row gantt-header-row"><div class="gantt-left-col">';
        html += '<div class="gh-act"><i class="fas fa-file-alt" style="margin-right:6px;"></i>Plano</div>';
        html += '<div class="gh-dias">Días</div></div>';
        html += '<div class="gantt-timeline-col"><div class="gantt-months">';
        segments.forEach(function (s) {
            html += '<div class="gantt-month-seg" style="width:'+s.pct+'%">'+s.label+'</div>';
        });
        html += '</div></div></div>';

        active.forEach(function (r, i) {
            var color      = COLORS[i % COLORS.length];
            var actStart   = parseDate(r.fecha_inicio);
            var offsetPct  = (daysBetween(pStart, actStart) / totalDays * 100).toFixed(3);
            var widthPct   = (parseFloat(r.dias) / totalDays * 100).toFixed(3);
            var tooltip    = fmtDate(actStart) + ' → ' + fmtDate(parseDate(r.fecha_fin)) + ' (' + r.dias + ' días)';
            var barLabel   = fmtShort(actStart) + ' – ' + fmtShort(parseDate(r.fecha_fin));
            var label      = (r.cod_plano || '') + (r.desc_plano ? ' — ' + r.desc_plano : '');

            html += '<div class="gantt-row gantt-act-row">';
            html += '<div class="gantt-left-col">';
            html += '<div class="gh-act"><span class="act-dot" style="background:'+color+'"></span>' + escHtml(label.substring(0,40)) + '</div>';
            html += '<div class="gh-dias">' + r.dias + '</div>';
            html += '</div>';
            html += '<div class="gantt-timeline-col"><div class="gantt-bar-track">';
            html += '<div class="gantt-bar m2" style="margin-left:'+offsetPct+'%;width:'+widthPct+'%;background:'+color+';" title="'+escHtml(tooltip)+'">';
            html += '<span class="gantt-bar-label">'+barLabel+'</span>';
            html += '</div></div></div></div>';
        });

        // Resumen
        var totalDiasSum = active.reduce(function (s, r) { return s + parseFloat(r.dias); }, 0);
        html += '<div class="gantt-row gantt-summary-row">';
        html += '<div class="gantt-left-col"><div class="gh-act"><strong>Total</strong></div>';
        html += '<div class="gh-dias"><strong>' + totalDiasSum + '</strong></div></div>';
        html += '<div class="gantt-timeline-col"><div class="gantt-summary-info">';
        html += '<i class="fas fa-calendar-check"></i> ' + fmtDate(pStart) + ' → ' + fmtDate(pEnd) + ' &nbsp;|&nbsp; <strong>' + totalDays + '</strong> días calendario';
        html += '</div></div></div>';
        html += '</div>';

        container.innerHTML = html;
        document.getElementById('ganttBadge').textContent = active.length + ' plano' + (active.length !== 1 ? 's' : '');
        section.style.display = '';
    }

    /* ─── Panel de Anotaciones ──────────────────────────────────── */
    function _setObsPanel(open) {
        _obsPanelOpen = !!open;
        var panel  = document.getElementById('obsPanel');
        var btn    = document.getElementById('obsToggleBtn');
        var wrap   = document.getElementById('fileViewerWrapper');
        if (open) {
            panel.style.display = 'flex';
            btn.classList.add('active');
            wrap.style.maxWidth = '1600px';
            if (_obsIdPlanos > 0) { _cargarObs(_obsIdPlanos); }
        } else {
            panel.style.display = 'none';
            btn.classList.remove('active');
            wrap.style.maxWidth = '';
        }
    }

    function _cargarObs(idPlanos) {
        var listWrap = document.getElementById('obsListWrap');
        var loading  = document.getElementById('obsLoading');
        var empty    = document.getElementById('obsEmpty');
        // Remove previous obs items
        listWrap.querySelectorAll('.obs-item').forEach(function(el){ el.remove(); });
        loading.style.display = '';
        empty.style.display = 'none';
        fetch(API_OBS_GET + '?id_planos=' + encodeURIComponent(idPlanos))
            .then(function(r){ return r.json(); })
            .then(function(data) {
                loading.style.display = 'none';
                if (!data.success) { empty.style.display = ''; return; }
                if (!data.obs || !data.obs.length) { empty.style.display = ''; return; }
                data.obs.forEach(function(o) { listWrap.appendChild(_mkObsItem(o)); });
                listWrap.scrollTop = listWrap.scrollHeight;
            })
            .catch(function() { loading.style.display = 'none'; empty.style.display = ''; });
    }

    function _mkObsItem(o) {
        var div = document.createElement('div');
        div.className = 'obs-item';
        div.innerHTML =
            '<div class="obs-item-meta"><span>' + escHtml(o.usuario) + '</span><span>' + escHtml(o.fecha) + '</span></div>' +
            '<div class="obs-item-text">' + escHtml(o.texto) + '</div>';
        return div;
    }

    function _guardarObs() {
        var textoEl = document.getElementById('obsTexto');
        var msgEl   = document.getElementById('obsMsg');
        var btn     = document.getElementById('obsGuardar');
        var texto   = textoEl.value.trim();
        if (!texto) { msgEl.style.color = '#c0392b'; msgEl.textContent = 'El texto no puede estar vacío.'; return; }
        if (_obsIdPlanos <= 0) { msgEl.style.color = '#c0392b'; msgEl.textContent = 'Abre un archivo primero.'; return; }
        btn.disabled = true;
        msgEl.textContent = '';
        fetch(API_OBS_SAVE, {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify({
                id_planos: _obsIdPlanos,
                ot:        selectedOT,
                cod_plano: _obsCodPlano,
                texto:     texto,
            }),
        })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.success && data.obs) {
                var listWrap = document.getElementById('obsListWrap');
                var empty    = document.getElementById('obsEmpty');
                empty.style.display = 'none';
                listWrap.appendChild(_mkObsItem(data.obs));
                listWrap.scrollTop = listWrap.scrollHeight;
                textoEl.value = '';
                msgEl.style.color = '#27ae60';
                msgEl.textContent = 'Nota agregada.';
                setTimeout(function(){ msgEl.textContent = ''; }, 2500);
            } else {
                msgEl.style.color = '#c0392b';
                msgEl.textContent = data.error || 'Error al guardar.';
            }
        })
        .catch(function() { msgEl.style.color = '#c0392b'; msgEl.textContent = 'Error de red.'; })
        .finally(function(){ btn.disabled = false; });
    }

}());
</script>
<script src="../../assets/vendor/three-dxf/three.min.js"></script>
<script src="../../assets/vendor/three-dxf/dxf-parser.js"></script>
<script src="../../assets/vendor/three-dxf/three-dxf.min.js"></script>

<?php include_once dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
