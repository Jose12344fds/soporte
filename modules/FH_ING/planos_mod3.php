<?php
$page_title = 'Módulo 2 — FH ING';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once dirname(dirname(__DIR__)) . '/config/aps.php';
require_permiso('ots_ingeniera', 'ver');
include_once dirname(dirname(__DIR__)) . '/layouts/header.php';
$_apsEnabled = !empty(APS_CLIENT_ID) && !empty(APS_CLIENT_SECRET);
?>
<link rel="stylesheet" href="../../assets/css/fh_planos.css?v=2">
<style>
/* ── Hero ──────────────────────────────────────────────────────── */
.m3-hero {
    background: linear-gradient(135deg, #1a3a5c 0%, #2980b9 100%);
    border-radius: 10px;
    padding: 22px 28px;
    color: #fff;
    margin-bottom: 18px;
}
.m3-hero h4 { margin: 0 0 4px; font-size: 1.25rem; font-weight: 700; }
.m3-hero p  { margin: 0; font-size: .88rem; opacity: .88; }

/* ── Cards ──────────────────────────────────────────────────────── */
.m3-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    margin-bottom: 18px;
    overflow: hidden;
}
.m3-card-header {
    background: linear-gradient(135deg, #1a3a5c, #2980b9);
    color: #fff;
    padding: 12px 18px;
    font-weight: 600;
    font-size: .95rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.m3-card-header .badge-count {
    background: rgba(255,255,255,.25);
    border-radius: 20px;
    padding: 2px 10px;
    font-size: .8rem;
    font-weight: 700;
}
.m3-card-body { padding: 18px; }

/* ── Info bar OT seleccionada ───────────────────────────────────── */
.m3-info-bar {
    background: #e8f4fd;
    border: 1.5px solid #aed6f1;
    border-radius: 8px;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.m3-info-bar strong { color: #1a3a5c; font-size: .95rem; }
.m3-info-bar small  { color: #5d6d7e; font-size: .82rem; }
.btn-cambiar-ot {
    background: none; border: 1.5px solid #2980b9;
    color: #2980b9; border-radius: 6px;
    padding: 4px 12px; font-size: .82rem; font-weight: 600;
    cursor: pointer; margin-left: auto;
    transition: background .2s;
}
.btn-cambiar-ot:hover { background: #2980b9; color: #fff; }

/* ── Tabla Módulo 2 ─────────────────────────────────────────────── */
#tblMod3 { width: 100%; border-collapse: collapse; font-size: .875rem; }
#tblMod3 thead th {
    background: #1a3a5c;
    color: #fff;
    padding: 10px 12px;
    font-weight: 600;
    white-space: nowrap;
    border-bottom: 2px solid #154360;
}
#tblMod3 tbody tr:nth-child(even) { background: #f0f6fc; }
#tblMod3 tbody tr:hover           { background: #d6eaf8; }
#tblMod3 td {
    padding: 8px 10px;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}
.td-cod-plano {
    font-family: monospace;
    font-size: .85rem;
    font-weight: 700;
    color: #1a3a5c;
    white-space: nowrap;
}
.inp-desc-mod3 {
    width: 100%;
    padding: 5px 8px;
    border: 1.5px solid #ced4da;
    border-radius: 5px;
    font-size: .875rem;
    background: #fff;
    resize: vertical;
    min-height: 36px;
    transition: border-color .2s;
}
.inp-desc-mod3:focus {
    outline: none;
    border-color: #2980b9;
    box-shadow: 0 0 0 3px rgba(41,128,185,.15);
}
.inp-dias-m3 {
    width: 75px;
    padding: 5px 8px;
    border: 1.5px solid #ced4da;
    border-radius: 5px;
    font-size: .875rem;
    text-align: center;
    transition: border-color .2s;
}
.inp-dias-m3:focus {
    outline: none;
    border-color: #2980b9;
    box-shadow: 0 0 0 3px rgba(41,128,185,.15);
}

/* ── Upload ─────────────────────────────────────────────────────── */
.m3-upload-wrap { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.m3-upload-label {
    display: inline-flex; align-items: center; gap: 5px;
    cursor: pointer; background: #f0f4f8;
    border: 1.5px dashed #adb5bd;
    border-radius: 5px; padding: 4px 10px;
    font-size: .8rem; color: #495057; white-space: nowrap;
    transition: border-color .2s, background .2s;
}
.m3-upload-label:hover { border-color: #2980b9; background: #e8f4fd; color: #1a3a5c; }
.m3-upload-label input[type=file] { display: none; }
.m3-uploading { font-size: .78rem; color: #888; }
.m3-done {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .8rem; color: #1a3a5c; font-weight: 600;
    max-width: 200px; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap;
}
.m3-done i { color: #27ae60; }

/* ── Alertas ────────────────────────────────────────────────────── */
.m3-alert { display:none; border-radius:6px; padding:10px 14px; margin-bottom:12px; font-size:.88rem; }
.m3-alert.success { display:flex; background:#e8f5e9; border:1.5px solid #27ae60; color:#1a5c38; gap:8px; }
.m3-alert.error   { display:flex; background:#fdf3f3; border:1.5px solid #e74c3c; color:#922b21; gap:8px; }

/* ── Botón guardar ──────────────────────────────────────────────── */
.btn-guardar-m3 {
    background: linear-gradient(135deg,#1a3a5c,#2980b9);
    color: #fff; border: none; border-radius: 8px;
    padding: 10px 26px; font-size: .95rem; font-weight: 700;
    cursor: pointer; transition: opacity .2s;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-guardar-m3:disabled { opacity: .6; cursor: default; }

/* ── Empty / Loading ────────────────────────────────────────────── */
.m3-empty {
    text-align: center; padding: 40px 20px;
    color: #7f8c8d; font-size: .9rem;
}
.m3-empty i { font-size: 2.5rem; display: block; margin-bottom: 12px; color: #bdc3c7; }

/* ── Gantt M3 ─────────────────────────────────────────────────────── */
.gantt-bar.m3 { border-radius: 4px; }
#m3GanttSection .fp-card-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
.m3-gantt-date-label { color:#fff; font-size:.82rem; display:flex; align-items:center; gap:6px; margin:0; font-weight:normal; }
.m3-gantt-date-label input[type=date] {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.4);
    border-radius: 5px;
    color: #fff;
    padding: 3px 8px;
    font-size: .82rem;
    outline: none;
    cursor: pointer;
}
.m3-gantt-date-label input[type=date]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
/* ── Botón finalizar ────────────────────────────────────────────────────── */
.btn-finalizar-m3 {
    background: linear-gradient(135deg,#1a5c38,#27ae60);
    color: #fff; border: none; border-radius: 8px;
    padding: 10px 26px; font-size: .95rem; font-weight: 700;
    cursor: pointer; transition: opacity .2s;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-finalizar-m3:disabled { opacity: .6; cursor: default; }
.btn-revertir-m3 {
    display: inline-flex; align-items: center; gap: 7px;
    background: transparent; color: #856404;
    border: 1.5px solid #ffc107;
    border-radius: 8px; padding: 8px 16px;
    font-size: .88rem; font-weight: 600; cursor: pointer;
    transition: background .2s, color .2s;
}
.btn-revertir-m3:hover { background: #fff3cd; color: #533f03; }
.btn-revertir-m3:disabled { opacity: .55; cursor: not-allowed; }
.finalizado-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #d4edda; color: #155724;
    border: 1.5px solid #c3e6cb; border-radius: 8px;
    padding: 8px 18px; font-size: .9rem; font-weight: 700;
}

/* ── Botón Ver archivo mod3 ─────────────────────────────────────────── */
.m3-ver-btn {
    display: inline-flex; align-items: center; gap: 4px;
    background: #1a3a5c; color: #fff;
    border: none; border-radius: 5px;
    padding: 3px 9px; font-size: .78rem; font-weight: 600;
    cursor: pointer; transition: background .2s; white-space: nowrap;
}
.m3-ver-btn:hover { background: #2980b9; }
/* Botón Historial en fila */
.m3-btn-hist { background:none; border:1.5px solid #dee2e6; border-radius:5px; padding:3px 8px; font-size:.74rem; cursor:pointer; display:inline-flex; align-items:center; gap:4px; color:#6c757d; transition:border-color .15s,color .15s,background .15s; white-space:nowrap; }
.m3-btn-hist:hover { border-color:#2980b9; color:#2980b9; background:#f0f6ff; }
.m3-btn-hist .hist-badge { background:#2980b9; color:#fff; border-radius:10px; padding:1px 6px; font-size:.65rem; font-weight:700; margin-left:1px; }

/* ══ Panel Historial Mod3 ══════════════════════════════════════════════ */
#m3HistOverlay { display:none; position:fixed; inset:0; z-index:9100; background:rgba(10,12,18,.82); align-items:center; justify-content:flex-end; }
#m3HistOverlay.open { display:flex; }
#m3HistPanel { background:#fff; width:420px; max-width:97vw; height:100vh; display:flex; flex-direction:column; box-shadow:-8px 0 40px rgba(0,0,0,.35); overflow:hidden; }
#m3HistHeader { background:linear-gradient(135deg,#1a3a5c 0%,#2980b9 100%); color:#fff; padding:16px 18px; flex-shrink:0; display:flex; gap:10px; align-items:flex-start; }
#m3HistHeader .ht-info { flex:1; }
#m3HistHeader .ht-info h5 { margin:0 0 3px; font-size:1rem; font-weight:700; }
#m3HistHeader .ht-info small { opacity:.82; font-size:.78rem; display:block; }
#m3HistCloseBtn { background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer; line-height:1; padding:0 3px; flex-shrink:0; }
#m3HistCloseBtn:hover { color:#ffb3b3; }
#m3HistBody { flex:1; overflow-y:auto; padding:20px 18px; display:flex; flex-direction:column; gap:0; }
/* Entradas de historial (reutiliza clases hv-* de planos.php) */
.hv-entry { display:flex; gap:12px; padding-bottom:18px; position:relative; }
.hv-entry:not(:last-child)::before { content:''; position:absolute; left:15px; top:34px; bottom:0; width:2px; background:#e2e8f0; }
.hv-dot { flex-shrink:0; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.78rem; font-weight:800; border:2.5px solid; margin-top:2px; position:relative; z-index:1; }
.hv-dot.current { background:#1a3a5c; color:#fff; border-color:#1a3a5c; box-shadow:0 0 0 3px rgba(26,58,92,.18); }
.hv-dot.old { background:#fff; color:#6c757d; border-color:#dee2e6; }
.hv-card { flex:1; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:9px; padding:11px 13px; }
.hv-entry.current-ver .hv-card { background:#f0f6ff; border-color:#93c5fd; }
.hv-card-top { display:flex; align-items:center; gap:7px; margin-bottom:5px; flex-wrap:wrap; }
.hv-badge-curr { background:#1a3a5c; color:#fff; font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:12px; }
.hv-badge-ver { background:#e9ecef; color:#495057; font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:12px; }
.hv-filename { font-size:.83rem; font-weight:600; color:#1e293b; word-break:break-all; flex:1; }
.hv-ext { font-size:.7rem; font-weight:700; padding:2px 7px; border-radius:4px; }
.hv-ext.t-cad { background:#ebf5fb; color:#2980b9; }
.hv-ext.t-pdf { background:#fdedec; color:#e74c3c; }
.hv-ext.t-other { background:#f2f3f4; color:#7f8c8d; }
.hv-meta { font-size:.74rem; color:#6c757d; display:flex; align-items:center; gap:4px; flex-wrap:wrap; margin-bottom:8px; }
.hv-actions { display:flex; gap:6px; flex-wrap:wrap; }
.hv-btn { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:6px; font-size:.76rem; font-weight:600; cursor:pointer; border:1.5px solid; text-decoration:none; transition:background .15s; }
.hv-btn.view { background:#1a3a5c; color:#fff; border-color:#1a3a5c; }
.hv-btn.view:hover { background:#2980b9; text-decoration:none; color:#fff; }
.hv-btn.dl { background:#fff; color:#1a5c38; border-color:#a5d6a7; }
.hv-btn.dl:hover { background:#e8f5e9; text-decoration:none; color:#1a5c38; }


#m3ViewerOverlay {
    display: none;
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,.72);
    align-items: stretch; justify-content: center;
}
#m3ViewerOverlay.open { display: flex; }
#m3ViewerPanel {
    display: flex; flex-direction: column;
    background: #fff;
    width: 96vw; max-width: 1300px;
    margin: 20px auto;
    border-radius: 10px; overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,.45);
}
#m3ViewerHeader {
    display: flex; align-items: center; gap: 12px;
    background: #1a3a5c; color: #fff;
    padding: 12px 18px; flex-shrink: 0;
}
#m3ViewerTitle {
    flex: 1; font-size: .95rem; font-weight: 600;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
#m3ViewerDownload {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.18); color: #fff;
    border: 1.5px solid rgba(255,255,255,.45); border-radius: 6px;
    padding: 5px 14px; font-size: .82rem; font-weight: 600;
    text-decoration: none; white-space: nowrap; cursor: pointer;
}
#m3ViewerDownload:hover { background: rgba(255,255,255,.32); color:#fff; text-decoration:none; }
#m3ViewerClose {
    background: none; border: none; color: #fff;
    font-size: 1.3rem; cursor: pointer; padding: 2px 6px;
    border-radius: 4px; line-height: 1;
}
#m3ViewerClose:hover { background: rgba(255,255,255,.18); }
#m3ViewerBody {
    flex: 1; display: flex; flex-direction: column;
    background: #1a1e23; min-height: 0; position: relative;
}
#m3ViewerLoading {
    display: none; position: absolute; inset: 0; z-index: 5;
    align-items: center; justify-content: center; gap: 12px;
    background: #1a1e23; color: #aaa; font-size: .9rem;
}
.m3vl-spinner {
    width: 36px; height: 36px; border-radius: 50%;
    border: 3px solid #333; border-top-color: #2980b9;
    animation: m3spin .8s linear infinite;
}
@keyframes m3spin { to { transform: rotate(360deg); } }
#m3ViewerIframe {
    display: none; flex: 1; border: none; width: 100%; min-height: 480px;
}
#m3ViewerDxfCanvas {
    display: none; flex: 1; background: #1a1e23;
    overflow: auto; padding: 8px; min-height: 480px;
}
#m3ViewerDxfCanvas canvas { display: block; }
#m3ViewerApsCanvas {
    display: none; flex: 1; position: relative; min-height: 480px;
}
#m3ApsProgressBar {
    height: 4px; background: #27ae60; width: 0%;
    transition: width .4s; position: absolute; top: 0; left: 0; right: 0;
}
#m3ApsProgressMsg {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    color: #aaa; font-size: .88rem;
}
#m3ViewerFallback {
    display: none; flex: 1; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 12px; padding: 30px; text-align: center; color: #ccc;
}
#m3ViewerFallback .fvf-icon { font-size: 3rem; color: #555; }
#m3ViewerFallback .fvf-title { font-size: 1rem; font-weight: 600; color: #ddd; }
#m3ViewerFallback .fvf-msg { font-size: .85rem; color: #888; max-width: 460px; }
#m3ViewerFallback .fvf-links { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-top: 6px; }
.m3fvf-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: #2980b9; color: #fff; border-radius: 6px;
    padding: 7px 16px; font-size: .85rem; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none;
}
.m3fvf-btn:hover { background: #1a5c8a; color:#fff; text-decoration:none; }
.m3fvf-btn-sec {
    display: inline-flex; align-items: center; gap: 6px;
    background: #2c3e50; color: #ccc; border-radius: 6px;
    padding: 7px 14px; font-size: .83rem;
    text-decoration: none; border: none; cursor: pointer;
}
.m3fvf-btn-sec:hover { background: #1a2530; color:#fff; text-decoration:none; }

/* ── Panel de observaciones dentro del visor ──────────────────── */
#m3ViewerContent { flex:1; display:flex; min-height:0; }
#m3ObsToggleBtn { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.18); color:#fff; border:1.5px solid rgba(255,255,255,.45); border-radius:6px; padding:5px 12px; font-size:.82rem; font-weight:600; white-space:nowrap; cursor:pointer; flex-shrink:0; transition:background .2s; }
#m3ObsToggleBtn:hover, #m3ObsToggleBtn.active { background:rgba(255,255,255,.32); }
#m3ObsBadge { background:#e74c3c; color:#fff; border-radius:10px; padding:1px 6px; font-size:.72rem; font-weight:700; margin-left:2px; }
#m3ObsPanel { display:none; flex-direction:column; width:310px; flex-shrink:0; background:#fff; border-left:1px solid #dee2e6; }
#m3ObsPanel.open { display:flex; }
#m3ObsPanelHeader { display:flex; align-items:center; justify-content:space-between; background:#1a3a5c; color:#fff; padding:10px 14px; flex-shrink:0; font-size:.88rem; font-weight:600; }
#m3ObsPanelHeader button { background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer; line-height:1; padding:0 4px; opacity:.8; }
#m3ObsPanelHeader button:hover { opacity:1; }
#m3ObsListWrap { flex:1; overflow-y:auto; padding:10px 12px; display:flex; flex-direction:column; gap:6px; font-size:.83rem; }
.m3obs-item { background:#f8f9fa; border:1px solid #e9ecef; border-radius:6px; padding:7px 10px; }
.m3obs-item-meta { font-size:.75rem; color:#888; margin-bottom:3px; display:flex; justify-content:space-between; }
.m3obs-item-text { color:#343a40; line-height:1.45; white-space:pre-wrap; word-break:break-word; }
.m3obs-img-row { display:flex; flex-wrap:wrap; gap:5px; margin-top:6px; }
.m3obs-thumb { width:58px; height:58px; object-fit:cover; border-radius:6px; cursor:pointer; border:1.5px solid #dee2e6; transition:border-color .15s,transform .15s; }
.m3obs-thumb:hover { border-color:#2980b9; transform:scale(1.06); }
#m3ObsEmpty { text-align:center; color:#aaa; padding:20px 10px; font-size:.82rem; }
#m3ObsForm { padding:10px 12px; border-top:1px solid #dee2e6; display:flex; flex-direction:column; gap:7px; flex-shrink:0; }
#m3ObsTexto { width:100%; resize:vertical; min-height:70px; border:1.5px solid #ced4da; border-radius:5px; padding:7px 10px; font-size:.83rem; color:#343a40; font-family:inherit; box-sizing:border-box; }
#m3ObsTexto:focus { outline:none; border-color:#1a3a5c; }
#m3ObsGuardar { width:100%; background:#1a3a5c; color:#fff; border:none; border-radius:6px; padding:8px 12px; font-size:.84rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
#m3ObsGuardar:hover { background:#2980b9; }
#m3ObsGuardar:disabled { opacity:.6; cursor:default; }
#m3ObsMsg { font-size:.78rem; text-align:center; min-height:16px; }
#m3ObsImgPreview { display:flex; flex-wrap:wrap; gap:5px; margin-bottom:5px; min-height:0; }
#m3ObsImgLabel { display:inline-flex; align-items:center; gap:5px; cursor:pointer; color:#6c757d; font-size:.75rem; padding:4px 9px; border:1.5px dashed #ced4da; border-radius:5px; transition:border-color .15s,color .15s,background .15s; white-space:nowrap; }
#m3ObsImgLabel:hover { border-color:#2980b9; color:#2980b9; }
#m3ObsImgLabel.drag-over { border-color:#27ae60; color:#27ae60; background:#eafaf1; }
#m3ObsImgLabel.paste-ok { border-color:#27ae60; color:#27ae60; background:#eafaf1; animation:obsLblFlash .45s ease; }
.m3obs-img-prev-item { position:relative; width:50px; height:50px; }
.m3obs-img-prev-item img { width:100%; height:100%; object-fit:cover; border-radius:5px; border:1.5px solid #dee2e6; }
.m3obs-img-prev-del { position:absolute; top:-5px; right:-5px; width:17px; height:17px; border-radius:50%; background:#dc3545; color:#fff; border:none; font-size:.55rem; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:2; }
@keyframes obsLblFlash { 0%,100%{background:#eafaf1;} 50%{background:#a9dfbf;} }
</style>

<div class="container-fluid" style="max-width:1400px; padding-top:10px;">

  <!-- ══ SELECTOR DE OTs ════════════════════════════════════════════ -->
  <div id="m3OtSection">
    <div class="m3-hero">
      <h4><i class="fas fa-drafting-compass"></i> Módulo 2 — FH ING</h4>
      <p>Selecciona una OT aprobada del Módulo 2 para registrar el detalle de construcción</p>
    </div>

    <div class="m3-card">
      <div class="m3-card-header">
        <span><i class="fas fa-list"></i> OTs disponibles (aprobadas Módulo 2)</span>
        <span class="badge-count" id="m3BadgeOTs">—</span>
      </div>
      <div class="m3-card-body">
        <div style="margin-bottom:12px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
          <input type="text" id="m3FilterOTs" class="form-control"
            style="max-width:320px; border-radius:7px; border:1.5px solid #dee2e6; padding:7px 12px; font-size:.9rem;"
            placeholder="Filtrar por OT o proyecto...">
          <small class="text-muted"><i class="fas fa-hand-pointer"></i> Haz clic en una fila para trabajar con esa OT</small>
        </div>
        <div id="m3OtLoading" class="m3-empty">
          <i class="fas fa-spinner fa-spin"></i>Cargando OTs...
        </div>
        <div id="m3OtEmpty" class="m3-empty" style="display:none;">
          <i class="fas fa-inbox"></i>
          No hay OTs aprobadas. Ve a <strong>Módulo 2</strong> y aprueba una OT primero.
        </div>
        <div id="m3OtWrapper" style="display:none;">
          <div class="table-responsive">
            <table class="table table-hover" id="tblOTsMod3">
              <thead>
                <tr>
                  <th style="width:130px;">Código OT</th>
                  <th>Proyecto</th>
                  <th>Elaborado por</th>
                  <th style="width:120px;text-align:center;">Acción</th>
                </tr>
              </thead>
              <tbody id="m3OtBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ DETALLE MÓDULO 3 ═══════════════════════════════════════════ -->
  <div id="m3DetalleSection" style="display:none;">

    <!-- Info OT -->
    <div class="m3-info-bar">
      <i class="fas fa-file-alt" style="color:#2980b9; font-size:1.2rem;"></i>
      <div>
        <strong id="m3InfoOT">—</strong>
        <small id="m3InfoProy" style="display:block;">—</small>
      </div>
      <button class="btn-cambiar-ot" id="m3BtnCambiar">
        <i class="fas fa-arrow-left"></i> Cambiar OT
      </button>
    </div>

    <!-- Tabla de detalle -->
    <div class="m3-card">
      <div class="m3-card-header">
        <span><i class="fas fa-table"></i> Detalle — Módulo 2</span>
        <span class="badge-count" id="m3BadgeFilas">—</span>
      </div>
      <div class="m3-card-body" style="padding:0;">
        <div id="m3TablaLoading" class="m3-empty" style="padding:30px;">
          <i class="fas fa-spinner fa-spin"></i>Cargando filas...
        </div>
        <div id="m3TablaEmpty" class="m3-empty" style="display:none;">
          <i class="fas fa-inbox"></i>
          Esta OT no tiene planos de origen. Registra los planos en el Módulo 1 primero.
        </div>
        <div id="m3TablaWrapper" style="display:none;">
          <div class="table-responsive">
            <table id="tblMod3">
              <thead>
                <tr>
                  <th style="width:140px;">Código Plano</th>
                  <th>Nueva Descripción</th>
                  <th style="width:220px;">Archivo Adjunto</th>
                  <th style="width:90px;text-align:center;">Días</th><th style="width:115px;">Inicio</th><th style="width:115px;">Fin</th>
                </tr>
              </thead>
              <tbody id="m3TblBody"></tbody>
            </table>
          </div>
          <!-- Guardar -->
          <div style="padding:16px 18px; border-top:1px solid #dee2e6; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
            <div id="m3Alert" class="m3-alert"></div>
            <button class="btn-guardar-m3" id="m3BtnGuardar">
              <i class="fas fa-save"></i> Guardar Todo
            </button>
            <small class="text-muted">Los cambios se guardan por fila de plano</small>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ GANTT MÓDULO 3 ══════════════════════════════════════════════ -->
    <div class="fp-gantt-card" id="m3GanttSection" style="display:none; margin-top:4px;">
      <div class="fp-card-header" style="background:linear-gradient(135deg,#1a3a5c,#2980b9);">
        <span><i class="fas fa-chart-bar"></i> Cronograma — Módulo 2</span>
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
          <label class="m3-gantt-date-label">
            <i class="fas fa-calendar-alt"></i> Fecha inicio:
            <input type="date" id="m3GanttFechaInicio" readonly title="Calculado desde el fin de Ingeniería">
          </label>
          <span class="badge-count" id="m3GanttBadge">—</span>
        </div>
      </div>
      <div id="m3GanttContainer"></div>
    </div>

    <!-- ══ FINALIZAR PROYECTO ══════════════════════════════════════ -->
    <div id="m3FinalizarSection" style="display:none; margin-top:14px;">
      <div class="m3-card">
        <div class="m3-card-body" style="padding:14px 18px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
          <div id="m3FinalizarAlert" class="m3-alert" style="display:none; width:100%; margin-bottom:0;"></div>
          <button class="btn-finalizar-m3" id="m3BtnFinalizar">
            <i class="fas fa-flag-checkered"></i> Terminar Módulo 2
          </button>
          <div class="finalizado-badge" id="m3FinalizadoBadge" style="display:none;">
            <i class="fas fa-check-double"></i> Proyecto Finalizado
          </div>
          <button class="btn-revertir-m3" id="m3BtnDevolverMod2" title="Regresar esta OT al Módulo 2">
            <i class="fas fa-undo"></i> ← Devolver a Módulo 1
          </button>
          <small class="text-muted">Termina esta etapa. El cierre definitivo se realiza desde el Registro de OT.</small>
        </div>
      </div>
    </div>

  </div>
</div>


<!-- ══ HISTORIAL DE VERSIONES MOD3 ══════════════════════════════════ -->
<div id="m3HistOverlay">
  <div id="m3HistPanel">
    <div id="m3HistHeader">
      <div class="ht-info">
        <h5><i class="fas fa-history" style="margin-right:7px;"></i>Historial de versiones</h5>
        <small id="m3HistSubtitle">—</small>
      </div>
      <button id="m3HistCloseBtn" title="Cerrar">&times;</button>
    </div>
    <div id="m3HistBody">
      <div id="m3HistLoading" style="text-align:center;padding:30px;color:#6c757d;font-size:.88rem;">
        <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;margin-bottom:8px;display:block;"></i>Cargando historial…
      </div>
    </div>
  </div>
</div>

<!-- ══ VISOR DE ARCHIVOS MOD3 ════════════════════════════════════════ -->
<div id="m3ViewerOverlay">
  <div id="m3ViewerPanel">
    <div id="m3ViewerHeader">
      <i class="fas fa-file-alt" style="font-size:1.1rem;flex-shrink:0;"></i>
      <span id="m3ViewerTitle">—</span>
      <button id="m3ObsToggleBtn" title="Ver/ocultar observaciones">
        <i class="fas fa-comments"></i> Observaciones <span id="m3ObsBadge" style="display:none;"></span>
      </button>
      <a id="m3ViewerDownload" href="#" download title="Descargar archivo">
        <i class="fas fa-download"></i> Descargar
      </a>
      <button id="m3ViewerClose" onclick="cerrarVisorM3()" title="Cerrar">&times;</button>
    </div>
    <div id="m3ViewerContent">
    <div id="m3ViewerBody">
      <div id="m3ViewerLoading">
        <div class="m3vl-spinner"></div>
        <span>Cargando archivo…</span>
      </div>
      <iframe id="m3ViewerIframe" src="about:blank" allowfullscreen></iframe>
      <div id="m3ViewerDxfCanvas"></div>
      <div id="m3ViewerApsCanvas">
        <div id="m3ApsProgressBar"></div>
        <div id="m3ApsProgressMsg"></div>
      </div>
      <div id="m3ViewerFallback">
        <div class="fvf-icon"><i class="fas fa-drafting-compass"></i></div>
        <div class="fvf-title" id="m3FvfTitle">Archivo DWG — no se puede previsualizar en el navegador</div>
        <div class="fvf-msg" id="m3FvfMsg">
          Los archivos DWG requieren <strong>AutoCAD u otro programa compatible</strong> para visualizarse.
        </div>
        <div class="fvf-links">
          <a class="m3fvf-btn" href="#" id="m3FvfDownload">
            <i class="fas fa-download"></i> Descargar archivo
          </a>
          <a class="m3fvf-btn-sec" href="https://www.autodesk.com/products/dwg-trueview/overview" target="_blank" rel="noopener">
            <i class="fas fa-external-link-alt"></i> DWG TrueView (gratis)
          </a>
        </div>
      </div>
    </div>
    <!-- ── Panel de observaciones ── -->
    <div id="m3ObsPanel">
      <div id="m3ObsPanelHeader">
        <span><i class="fas fa-comments" style="margin-right:6px;"></i><span id="m3ObsPanelTitle">Observaciones</span></span>
        <button id="m3ObsCloseBtn" title="Cerrar panel">&times;</button>
      </div>
      <div style="display:flex;gap:6px;padding:10px;"><button type="button" id="m2ObsActual">Módulo 2 · nuevas</button><button type="button" id="m2ObsAnterior">Módulo 1 · antecedentes</button></div><div id="m3ObsListWrap">
        <div id="m3ObsLoading" style="display:none;text-align:center;padding:12px;color:#888;font-size:.82rem;"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>
        <div id="m3ObsEmpty" style="display:none;">Sin observaciones aún.</div>
      </div>
      <div id="m3ObsForm">
        <textarea id="m3ObsTexto" placeholder="Escribe una observación sobre este plano…"></textarea>
        <div id="m3ObsImgPreview"></div>
        <label id="m3ObsImgLabel" title="Clic para seleccionar · Arrastra aquí · Pega con Ctrl+V">
          <i class="fas fa-camera"></i> Imagen ref. <span class="obs-ctrl-hint">Ctrl+V</span>
          <input type="file" id="m3ObsImgInput" accept="image/*" multiple style="display:none;">
        </label>
        <button id="m3ObsGuardar"><i class="fas fa-plus"></i> Agregar nota</button>
        <div id="m3ObsMsg"></div>
      </div>
    </div>
    </div><!-- fin m3ViewerContent -->
  </div>
</div>

<!-- ══ JAVASCRIPT ════════════════════════════════════════════════ -->
<link rel="stylesheet" href="../../assets/css/fh_revision.css?v=20261027">
<script src="../../assets/js/fh_revision.js?v=20261027"></script>
<script src="../../assets/js/fh_calendario.js?v=20261027"></script>
<script>
(function () {
    'use strict';

    var API_OTS      = '../../api/fh_ing/get_ot_detalle.php?modulo=3';
    var API_GET      = '../../api/fh_ing/get_mod3.php';
    var API_GUARDAR  = '../../api/fh_ing/guardar_mod3.php';
    var API_UPLOAD   = '../../api/fh_ing/upload_mod3.php';
    var API_ESTADO   = '../../api/fh_ing/cambiar_estado.php';
    var API_DOWN_M3  = '../../api/fh_ing/descargar_mod3.php';
    var API_DXF_M3   = '../../api/fh_ing/get_dxf_mod3.php';
    var API_APS_M3   = '../../api/fh_ing/aps_ensure_mod3.php';
    var API_APS_TOK  = '../../api/fh_ing/aps_token.php';
    var API_VER_M3   = '../../api/fh_ing/ver_plano_mod3.php';
    var API_OBS_GET  = '../../api/fh_ing/get_obs_plano.php';
    var API_OBS_SAVE = '../../api/fh_ing/guardar_obs_plano.php';
    var API_OBS_IMG  = '../../api/fh_ing/upload_obs_imagen.php';
    var API_OBS_IMG_VER = '../../api/fh_ing/ver_obs_imagen.php';
    <?php echo 'var APS_ENABLED_M3 = ' . ($_apsEnabled ?? false ? 'true' : 'false') . ';'; ?>

    var selectedOT   = '';
    var selectedProy = '';
    var m3Rows       = []; // [{id_planos, cod_plano, id_mod3, desc_mod3, arch_plano, arch_original, dias}]

    /* ─── Estado obs panel ───────────────────────────────────────── */
    var _m3ObsPanelOpen  = false;
    var _m3ObsIdPlanos   = 0;
    var _m3ObsCodPlano   = '';
    var _m3ObsImgFiles   = [];

    /* ─── Colores Gantt ──────────────────────────────────────────── */
    var COLORS = ['#2980b9','#27ae60','#e67e22','#8e44ad','#16a085','#c0392b','#2c3e50','#d35400','#1abc9c','#e74c3c'];

    /* ─── Helpers fecha ───────────────────────────────────────────── */
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
    function todayStr() {
        var d = new Date();
        return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
    }

    /* ─── Helpers ─────────────────────────────────────────────── */
    function escHtml(v) {
        if (v == null) return '';
        return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showAlert(type, msg) {
        var el = document.getElementById('m3Alert');
        el.className = 'm3-alert ' + type;
        el.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + escHtml(msg);
        el.style.display = 'flex';
        setTimeout(function() { el.style.display = 'none'; }, 5000);
    }

    /* ─── Cargar OTs ──────────────────────────────────────────── */
    function cargarOTs() {
        fetch(API_OTS)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var rows = Array.isArray(data) ? data : [];
                document.getElementById('m3BadgeOTs').textContent = rows.length;

                if (rows.length === 0) {
                    document.getElementById('m3OtLoading').style.display = 'none';
                    document.getElementById('m3OtEmpty').style.display   = '';
                    return;
                }

                var tbody = document.getElementById('m3OtBody');
                tbody.innerHTML = '';
                rows.forEach(function(r) {
                    var tr = document.createElement('tr');
                    tr.style.cursor = 'pointer';
                    tr.innerHTML =
                        '<td><strong>' + escHtml(r.ot) + '</strong></td>' +
                        '<td>' + escHtml(r.proyecto || r.nombre || '—') + '</td>' +
                        '<td>' + escHtml(r.elaborado_por || r.nombre_responsable || '—') + '</td>' +
                        '<td style="text-align:center;"><button class="btn btn-sm btn-primary" style="font-size:.78rem;padding:3px 10px;">Seleccionar</button></td>';
                    tr.addEventListener('click', function() {
                        seleccionarOT(r.ot, r.proyecto || r.nombre || '');
                    });
                    tbody.appendChild(tr);
                });

                // Filtro
                document.getElementById('m3FilterOTs').addEventListener('input', function() {
                    var q = this.value.toLowerCase();
                    Array.from(tbody.querySelectorAll('tr')).forEach(function(tr) {
                        tr.style.display = tr.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
                    });
                });

                document.getElementById('m3OtLoading').style.display  = 'none';
                document.getElementById('m3OtWrapper').style.display   = '';
            })
            .catch(function(err) {
                document.getElementById('m3OtLoading').innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i> Error: ' + escHtml(err.message);
            });
    }

    /* ─── Seleccionar OT ──────────────────────────────────────── */
    function seleccionarOT(ot, proy) {
        selectedOT   = ot;
        selectedProy = proy;
        document.getElementById('m3OtSection').style.display     = 'none';
        document.getElementById('m3DetalleSection').style.display = '';
        document.getElementById('m3InfoOT').textContent   = 'OT: ' + ot;
        document.getElementById('m3InfoProy').textContent = proy;
        cargarDetalle(ot);
    }

    /* ─── Cargar filas Módulo 2 + datos Módulo 2 ──────────────── */
    function cargarDetalle(ot) {
        document.getElementById('m3TablaLoading').style.display = '';
        document.getElementById('m3TablaEmpty').style.display   = 'none';
        document.getElementById('m3TablaWrapper').style.display = 'none';

        fetch(API_GET + '?ot=' + encodeURIComponent(ot))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('m3TablaLoading').style.display = 'none';
                if (!data.success || !data.rows || data.rows.length === 0) {
                    document.getElementById('m3TablaEmpty').style.display = '';
                    return;
                }
                m3Rows = data.rows;
                // Usar fecha_inicio_mod3 de la API como base del Gantt
                if (data.fecha_inicio_mod3) {
                    document.getElementById('m3GanttFechaInicio').value = data.fecha_inicio_mod3;
                }
                renderTabla(m3Rows);
                document.getElementById('m3BadgeFilas').textContent = m3Rows.length + ' fila' + (m3Rows.length !== 1 ? 's' : '');
                document.getElementById('m3TablaWrapper').style.display = '';
                document.getElementById('m3FinalizarSection').style.display = 'flex';
                renderGanttMod3(m3Rows);
            })
            .catch(function(err) {
                document.getElementById('m3TablaLoading').innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i> Error: ' + escHtml(err.message);
            });
    }

    /* ─── Render tabla ────────────────────────────────────────── */
    function renderTabla(rows) {
        var tbody = document.getElementById('m3TblBody');
        tbody.innerHTML = '';
        rows.forEach(function(r, idx) {
            var tr = document.createElement('tr');
            tr.dataset.idx = idx;

            var archHtml = buildArchHtml(idx, r);

            tr.innerHTML =
                '<td class="td-cod-plano">' + escHtml(r.cod_plano) + '</td>' +
                '<td><textarea class="inp-desc-mod3" data-idx="' + idx + '" rows="2" placeholder="Descripción del trabajo realizado…">' + escHtml(r.desc_mod3) + '</textarea></td>' +
                '<td>' + archHtml + '</td>' +
                '<td style="text-align:center;">' +
                  '<input type="number" class="inp-dias-m3" data-idx="' + idx + '" ' +
                  'value="' + escHtml(String(r.dias || 0)) + '" ' +
                  'min="0" max="9999" step="0.001" placeholder="1.5">' +
                '</td><td class="m2-fecha-inicio"></td><td class="m2-fecha-fin"></td>';

            tbody.appendChild(tr);
            bindRowEvents(tr, idx, r);
        });
    }

    function buildArchHtml(idx, r) {
        var inputId = 'fileM3_' + idx;
        if (r.arch_original) {
            return '<div class="m3-upload-wrap">' +
                '<span class="m3-done"><i class="fas fa-paperclip"></i>' + escHtml(r.arch_original) + '</span>' +
                '<button type="button" class="m3-ver-btn m3-btn-ver"' +
                  ' data-file="' + escHtml(r.arch_plano) + '"' +
                  ' data-name="' + escHtml(r.arch_original) + '"' +
                  ' data-id-planos="' + escHtml(r.id_planos) + '"' +
                  ' data-cod="' + escHtml(r.cod_plano) + '">' +
                  '<i class="fas fa-eye"></i> Ver</button>' +
                '<button type="button" class="m3-btn-hist"' +
                  ' data-id-planos="' + escHtml(r.id_planos) + '"' +
                  ' data-cod="' + escHtml(r.cod_plano) + '">' +
                  '<i class="fas fa-history"></i> Hist.</button>' +
                '<label class="m3-upload-label" title="Cambiar archivo">' +
                  '<i class="fas fa-sync-alt"></i>' +
                  '<input type="file" id="' + inputId + '" data-idx="' + idx + '" accept=".dwg,.dxf,.pdf,.png,.jpg,.jpeg">' +
                '</label>' +
                '</div>';
        }
        return '<div class="m3-upload-wrap">' +
            '<label class="m3-upload-label">' +
              '<i class="fas fa-paperclip"></i> Adjuntar archivo' +
              '<input type="file" id="' + inputId + '" data-idx="' + idx + '" accept=".dwg,.dxf,.pdf,.png,.jpg,.jpeg">' +
            '</label>' +
            '<span class="m3-uploading" id="uploadMsg_' + idx + '" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Subiendo…</span>' +
            '</div>';
    }

    function bindRowEvents(tr, idx, r) {
        // Textarea sync
        var ta = tr.querySelector('.inp-desc-mod3');
        if (ta) ta.addEventListener('change', function() { m3Rows[idx].desc_mod3 = this.value; });

        // Horas sync
        var inp = tr.querySelector('.inp-dias-m3');
        if (inp) inp.addEventListener('input', function() {
            var v = Math.round((parseFloat(this.value) || 0)*1000)/1000;
            if (v < 0) v = 0;
            this.value = v;
            m3Rows[idx].dias = v;
            renderGanttMod3(m3Rows);
        });

        // Ver archivo
        var btnVer = tr.querySelector('.m3-btn-ver');
        if (btnVer) {
            btnVer.addEventListener('click', function() {
                abrirVisorM3(
                    this.getAttribute('data-file'),
                    this.getAttribute('data-name'),
                    parseInt(this.getAttribute('data-id-planos')) || 0,
                    this.getAttribute('data-cod') || ''
                );
            });
        }

        // Botón historial
        var btnHist = tr.querySelector('.m3-btn-hist');
        if (btnHist) {
            btnHist.addEventListener('click', function() {
                _m3AbrirHistorial(
                    parseInt(this.getAttribute('data-id-planos')) || 0,
                    this.getAttribute('data-cod') || ''
                );
            });
        }

        // File upload
        var fileInput = tr.querySelector('input[type=file]');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (!this.files || !this.files[0]) return;
                uploadFile(this.files[0], idx, tr);
            });
        }
    }

    /* ─── Upload archivo ──────────────────────────────────────── */
    function uploadFile(file, idx, tr) {
        var msgEl = tr.querySelector('.m3-uploading') || document.getElementById('uploadMsg_' + idx);
        if (msgEl) msgEl.style.display = '';

        var fd = new FormData();
        fd.append('file', file);

        fetch(API_UPLOAD, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.ok) throw new Error(data.error || 'Error al subir');
                m3Rows[idx].arch_plano    = data.saved_name;
                m3Rows[idx].arch_original = data.original_name;
                // Actualizar celda sin re-renderizar toda la tabla
                var td = tr.querySelectorAll('td')[2];
                td.innerHTML = buildArchHtml(idx, m3Rows[idx]);
                bindRowEvents(tr, idx, m3Rows[idx]);
            })
            .catch(function(err) {
                showAlert('error', 'Error al subir archivo: ' + err.message);
                if (msgEl) msgEl.style.display = 'none';
            });
    }

    /* ─── Guardar todo ────────────────────────────────────────── */
    function guardarModulo2(){
        if(!selectedOT)return Promise.resolve(false);
        if(Array.from(document.querySelectorAll('.m3-uploading')).some(function(e){return e.style.display!=='none'&&e.offsetParent!==null;})){showAlert('error','Espera a que terminen las cargas de documentos.');return Promise.resolve(false);}
        var btn=document.getElementById('m3BtnGuardar');btn.disabled=true;
        var rows=m3Rows.map(function(r,idx){return Object.assign({},r,{desc_mod3:document.querySelector('.inp-desc-mod3[data-idx="'+idx+'"]').value,dias:Number(document.querySelector('.inp-dias-m3[data-idx="'+idx+'"]').value)||0});});
        return fetch(API_GUARDAR,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ot:selectedOT,unidad:'dias',rows:rows})})
            .then(function(r){return r.json();}).then(function(data){if(!data.success)throw Error(data.message||'No se pudo guardar.');
                m3Rows=rows.map(function(r){return Object.assign({},r,(data.rows||[]).find(function(x){return Number(x.id_planos)===Number(r.id_planos);})||{});});
                if(data.fecha_inicio_mod3)document.getElementById('m3GanttFechaInicio').value=data.fecha_inicio_mod3;
                renderGanttMod3(m3Rows);showAlert('success','Módulo 2 guardado: días y fechas actualizados.');return true;
            }).catch(function(err){showAlert('error',err.message);return false;}).finally(function(){btn.disabled=false;});
    }
    document.getElementById('m3BtnGuardar').addEventListener('click',guardarModulo2);

    /* ─── Devolver a Módulo 1 ───────────────────────────────────── */
        document.getElementById('m3BtnDevolverMod2').addEventListener('click', function() {
            if (!selectedOT) return;
            if (!confirm('¿Devolver "' + selectedOT + '" al Módulo 1?\nLa OT volverá a estar disponible en el Módulo 1 y saldrá del Módulo 2.')) return;
            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Devolviendo…';
            fetch(API_ESTADO, {
                method:  'POST',
                headers: {'Content-Type': 'application/json'},
                body:    JSON.stringify({ ot: selectedOT, estado: 1 }),
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var ot = selectedOT;
                    selectedOT   = '';
                    selectedProy = '';
                    m3Rows       = [];
                    document.getElementById('m3OtSection').style.display      = '';
                    document.getElementById('m3DetalleSection').style.display  = 'none';
                    document.getElementById('m3TblBody').innerHTML             = '';
                    document.getElementById('m3Alert').style.display           = 'none';
                    document.getElementById('m3GanttSection').style.display    = 'none';
                    document.getElementById('m3FinalizarSection').style.display = 'none';
                    document.getElementById('m3BtnFinalizar').style.display    = '';
                    document.getElementById('m3BtnFinalizar').disabled         = false;
                    document.getElementById('m3BtnFinalizar').innerHTML        = '<i class="fas fa-flag-checkered"></i> Terminar Módulo 2';
                    document.getElementById('m3FinalizadoBadge').style.display = 'none';
                    document.getElementById('m3FinalizarAlert').style.display  = 'none';
                    // Quitar la fila de la tabla de OTs sin recargar
                    Array.from(document.querySelectorAll('#m3OtBody tr')).forEach(function(tr) {
                        if (tr.textContent.indexOf(ot) !== -1) tr.remove();
                    });
                } else {
                    alert(data.message || 'Error al devolver.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-undo"></i> ← Devolver a Módulo 1';
                }
            })
            .catch(function(err) {
                alert('Error: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-undo"></i> ← Devolver a Módulo 1';
            });
        });

    /* ─── Terminar Módulo 2 ──────────────────────────────────── */
        document.getElementById('m3BtnFinalizar').addEventListener('click', function() {
        if (!selectedOT) return;
        if (!confirm('¿Terminar el Módulo 2 de "' + selectedOT + '"?\nSaldrá de este módulo y quedará pendiente de cierre en el Registro de OT.')) return;
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizando…';

        guardarModulo2().then(function(ok){
            if(!ok)throw Error('No se finalizó: primero debe guardarse el detalle.');
            return fetch(API_ESTADO,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ot:selectedOT,estado:4})});
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                btn.style.display = 'none';
                document.getElementById('m3FinalizadoBadge').style.display = '';
                // Show a persistent success alert
                var al = document.getElementById('m3FinalizarAlert');
                al.className = 'm3-alert success';
                al.innerHTML = '<i class="fas fa-flag-checkered"></i> Módulo 2 terminado. <a href="ot_detalle.php">Ir al Registro de OT para revisar y cerrar el proyecto</a>';
                document.getElementById('m3DetalleSection').style.display='none';
                document.getElementById('m3GanttSection').style.display='none';
                document.getElementById('m3OtSection').style.display='';
                cargarOTs();
                al.style.display = 'flex';
            } else {
                alert(data.message || 'Error al finalizar.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-flag-checkered"></i> Terminar Módulo 2';
            }
        })
        .catch(function(err) {
            alert('Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-flag-checkered"></i> Terminar Módulo 2';
        });
    });

    /* ─── Cambiar OT ──────────────────────────────────────────── */
    document.getElementById('m3BtnCambiar').addEventListener('click', function() {
        selectedOT   = '';
        selectedProy = '';
        m3Rows       = [];
        document.getElementById('m3OtSection').style.display     = '';
        document.getElementById('m3DetalleSection').style.display = 'none';
        document.getElementById('m3TblBody').innerHTML = '';
        document.getElementById('m3Alert').style.display         = 'none';
        document.getElementById('m3GanttSection').style.display  = 'none';
        document.getElementById('m3FinalizarSection').style.display = 'none';
        document.getElementById('m3BtnFinalizar').style.display  = '';
        document.getElementById('m3BtnFinalizar').disabled       = false;
        document.getElementById('m3BtnFinalizar').innerHTML      = '<i class="fas fa-flag-checkered"></i> Terminar Módulo 2';
        document.getElementById('m3FinalizadoBadge').style.display = 'none';
        document.getElementById('m3FinalizarAlert').style.display  = 'none';
        document.getElementById('m3BtnDevolverMod2').disabled       = false;
        document.getElementById('m3BtnDevolverMod2').innerHTML      = '<i class="fas fa-undo"></i> ← Devolver a Módulo 1';
    });

    /* ─── Gantt Módulo 2 ─────────────────────────────────────────── */
    function renderGanttMod3(rows){
        var section=document.getElementById('m3GanttSection'),container=document.getElementById('m3GanttContainer');
        var inicio=document.getElementById('m3GanttFechaInicio').value||todayStr(),cursor=0;
        var items=rows.map(function(r,i){var tramo=FHCalendar.slot(inicio,cursor,Number(r.dias)||0);cursor=tramo.cursor;
            var tr=document.querySelector('#m3TblBody tr[data-idx="'+i+'"]');
            if(tr){tr.querySelector('.m2-fecha-inicio').textContent=tramo.fecha_inicio?fmtDate(parseDate(tramo.fecha_inicio)):'—';tr.querySelector('.m2-fecha-fin').textContent=tramo.fecha_fin?fmtDate(parseDate(tramo.fecha_fin)):'—';var input=tr.querySelector('.inp-dias-m3');input.title=Number(((Number(r.dias)||0)*8).toFixed(2))+' horas';}
            return Object.assign({},r,tramo);
        }).filter(function(r){return r.fecha_inicio;});
        if(!items.length){section.style.display='none';return;}
        var first=parseDate(items[0].fecha_inicio),last=parseDate(items[items.length-1].fecha_fin),n=daysBetween(first,last)+1;
        var html='<p style="padding:12px;color:#475569;">Lunes a viernes · 8 h/día · 1,2 d = 9,6 h · 1,5 d = 12 h. Inicio después de Ingeniería.</p><div style="overflow:auto;"><table class="table table-bordered table-sm" style="font-size:.8rem;"><thead><tr><th>Plano</th><th>Días / horas</th><th>Inicio</th><th>Fin</th>';
        var days=[];for(var k=0;k<n;k++){var date=addDays(first,k);var iso=date.getFullYear()+'-'+String(date.getMonth()+1).padStart(2,'0')+'-'+String(date.getDate()).padStart(2,'0');days.push(iso);html+='<th style="min-width:44px;background:'+(date.getDay()===0||date.getDay()===6?'#e2e8f0':'#eef4ff')+'">'+date.getDate()+'/'+(date.getMonth()+1)+'</th>';}
        html+='</tr></thead><tbody>';items.forEach(function(r,i){var color=COLORS[i%COLORS.length];html+='<tr><td>'+escHtml(r.cod_plano||'')+'</td><td>'+r.dias+' d / '+Number(r.horas.toFixed(2))+' h</td><td>'+fmtDate(parseDate(r.fecha_inicio))+'</td><td>'+fmtDate(parseDate(r.fecha_fin))+'</td>';
            days.forEach(function(day){var seg=r.segmentos.find(function(x){return x.fecha===day;});html+='<td style="text-align:center;'+(seg?'background:linear-gradient(to right,transparent '+seg.inicio*100+'%,'+color+' '+seg.inicio*100+'%,'+color+' '+(seg.inicio+seg.fraccion)*100+'%,transparent '+(seg.inicio+seg.fraccion)*100+'%);color:#111;':'')+'">'+(seg?Number(seg.horas.toFixed(2))+' h':'')+'</td>';});html+='</tr>';});
        var total=items.reduce(function(sum,r){return sum+Number(r.dias);},0);html+='</tbody></table></div><p style="padding:12px;font-weight:700;">Total: '+Number(total.toFixed(2))+' días · '+Number((total*8).toFixed(2))+' horas · Fin '+fmtDate(last)+'</p>';
        container.innerHTML=html;document.getElementById('m3GanttBadge').textContent=items.length+' planos';section.style.display='';
    }

    /* ─── Visor de archivos Mod3 ──────────────────────────────────── */
    var _m3ApsViewer    = null;
    var _m3ApsPollTimer = null;
    var _m3PendingTimer = null;
    var _m3ThreeDxf     = null;
    var _m3DxfParsed    = null;
    var _m3CurrentFile  = '';   // saved_name del archivo abierto

    var M3_PREVIEW_EXTS = ['dxf', 'dwg', 'dws', 'dwt'];
    var M3_IMG_EXTS     = ['png', 'jpg', 'jpeg'];

    function _m3Reset() {
        if (_m3ApsPollTimer) { clearTimeout(_m3ApsPollTimer); _m3ApsPollTimer = null; }
        if (_m3PendingTimer) { clearInterval(_m3PendingTimer); _m3PendingTimer = null; }
        if (_m3ThreeDxf) { try { _m3ThreeDxf.dispose ? _m3ThreeDxf.dispose() : null; } catch(e){} _m3ThreeDxf = null; }
        _m3DxfParsed = null;
        document.getElementById('m3ViewerIframe').style.display    = 'none';
        document.getElementById('m3ViewerIframe').src              = 'about:blank';
        document.getElementById('m3ViewerDxfCanvas').style.display = 'none';
        document.getElementById('m3ViewerDxfCanvas').innerHTML     = '';
        document.getElementById('m3ViewerApsCanvas').style.display = 'none';
        document.getElementById('m3ViewerFallback').style.display  = 'none';
        document.getElementById('m3ViewerLoading').style.display   = 'none';
        var tabs = document.getElementById('m3ApsSheetTabs');
        if (tabs) tabs.remove();
    }

    function abrirVisorM3(savedFile, originalName, idPlanos, codPlano) {
        if (!savedFile) return;
        _m3CurrentFile  = savedFile;
        _m3ObsIdPlanos  = idPlanos  || 0;
        _m3ObsCodPlano  = codPlano  || '';
        var overlay = document.getElementById('m3ViewerOverlay');
        var title   = document.getElementById('m3ViewerTitle');
        var dlBtn   = document.getElementById('m3ViewerDownload');
        var ext     = (originalName || savedFile).split('.').pop().toLowerCase();

        title.textContent = originalName || savedFile;
        dlBtn.href = API_DOWN_M3 + '?file=' + encodeURIComponent(savedFile);
        dlBtn.setAttribute('download', originalName || savedFile);
        document.getElementById('m3FvfDownload').href = dlBtn.href;
        document.getElementById('m3FvfDownload').setAttribute('download', originalName || savedFile);

        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        _m3Reset();
        // actualizar título del panel obs y recargar si está abierto
        var obsTitle = document.getElementById('m3ObsPanelTitle');
        if (obsTitle) obsTitle.textContent = 'Obs — ' + escHtml(codPlano || '');
        _m2ObsModulo=2;
        _m3ResetObsBadge();
        if (_m3ObsPanelOpen && _m3ObsIdPlanos) _m3CargarObs(_m3ObsIdPlanos);

        if (M3_IMG_EXTS.indexOf(ext) !== -1) {
            var iframe = document.getElementById('m3ViewerIframe');
            iframe.style.display = 'block';
            iframe.src = API_DOWN_M3 + '?file=' + encodeURIComponent(savedFile) + '&ver=1';
        } else if (ext === 'pdf') {
            document.getElementById('m3ViewerLoading').style.display = 'flex';
            var iframe = document.getElementById('m3ViewerIframe');
            iframe.style.display = 'block';
            iframe.onload = function() { document.getElementById('m3ViewerLoading').style.display = 'none'; };
            iframe.src = API_DOWN_M3 + '?file=' + encodeURIComponent(savedFile) + '&ver=1';
        } else if (M3_PREVIEW_EXTS.indexOf(ext) !== -1) {
            document.getElementById('m3ViewerLoading').style.display = 'flex';
            if (APS_ENABLED_M3) {
                _m3RenderizarConAPS(savedFile, originalName);
            } else {
                _m3RenderizarConThreeDxf(savedFile, originalName);
            }
        } else {
            _m3ShowFallback(null, savedFile, originalName);
        }
    }

    window.cerrarVisorM3 = function() {
        document.getElementById('m3ViewerOverlay').classList.remove('open');
        document.body.style.overflow = '';
        _m3Reset();
        _m3CurrentFile = '';
        _m3ObsIdPlanos = 0;
        _m3ObsCodPlano = '';
    };

    function _m3ShowFallback(errMsg, savedFile, originalName) {
        document.getElementById('m3ViewerLoading').style.display = 'none';
        document.getElementById('m3FvfTitle').textContent = errMsg
            ? 'No se pudo previsualizar el archivo'
            : 'Archivo DWG — no se puede previsualizar en el navegador';
        if (errMsg) {
            document.getElementById('m3FvfMsg').innerHTML = escHtml(errMsg) +
                '<br>Descárgalo para abrirlo con un programa compatible.';
        }
        document.getElementById('m3ViewerFallback').style.display = 'flex';
    }

    /* ── APS ─────────────────────────────────────────────────────── */
    function _m3RenderizarConAPS(savedFile, originalName) {
        var loading    = document.getElementById('m3ViewerLoading');
        var apsCanvas  = document.getElementById('m3ViewerApsCanvas');
        var dxfCanvas  = document.getElementById('m3ViewerDxfCanvas');
        var progressBar = document.getElementById('m3ApsProgressBar');
        var progressMsg = document.getElementById('m3ApsProgressMsg');

        loading.style.display   = 'flex';
        apsCanvas.style.display = 'none';
        dxfCanvas.style.display = 'none';
        if (_m3ApsPollTimer) { clearTimeout(_m3ApsPollTimer); _m3ApsPollTimer = null; }

        function pollEnsure() {
            fetch(API_APS_M3 + '?file=' + encodeURIComponent(savedFile))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'success') {
                        _m3LoadApsViewer(data.urn, apsCanvas, loading);
                    } else if (data.status === 'failed') {
                        console.warn('[APS-M3] Fallo traducción, usando three-dxf');
                        loading.style.display = 'none';
                        _m3RenderizarConThreeDxf(savedFile, originalName);
                    } else {
                        var pct = data.progress || 0;
                        loading.style.display   = 'none';
                        apsCanvas.style.display = 'block';
                        progressBar.style.width = pct + '%';
                        progressMsg.textContent = data.message || 'Traduciendo plano…';
                        _m3ApsPollTimer = setTimeout(pollEnsure, 5000);
                    }
                })
                .catch(function(err) {
                    console.warn('[APS-M3] Error:', err.message);
                    loading.style.display = 'none';
                    _m3RenderizarConThreeDxf(savedFile, originalName);
                });
        }
        pollEnsure();
    }

    function _m3LoadApsViewer(urn, container, loading) {
        function _initAndLoad() {
            if (_m3ApsViewer) {
                _m3ApsLoadDoc(_m3ApsViewer, urn);
                return;
            }
            Autodesk.Viewing.Initializer({
                env: 'AutodeskProduction2', api: 'streamingV2',
                getAccessToken: function(cb) {
                    fetch(API_APS_TOK)
                        .then(function(r) { return r.json(); })
                        .then(function(d) { cb(d.access_token, d.expires_in); })
                        .catch(function() { cb('', 0); });
                }
            }, function() {
                _m3ApsViewer = new Autodesk.Viewing.GuiViewer3D(container, { extensions: ['Autodesk.DocumentBrowser'] });
                if (_m3ApsViewer.start() > 0) {
                    loading.style.display = 'none';
                    _m3RenderizarConThreeDxf(null, null);
                    return;
                }
                _m3ApsLoadDoc(_m3ApsViewer, urn);
            });
        }

        if (typeof Autodesk === 'undefined') {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
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
                loading.style.display = 'none';
                _m3RenderizarConThreeDxf(null, null);
            };
            document.body.appendChild(script);
        } else {
            loading.style.display   = 'none';
            container.style.display = 'block';
            _initAndLoad();
        }
    }

    function _m3ApsLoadDoc(viewer, urn) {
        Autodesk.Viewing.Document.load('urn:' + urn,
            function(doc) {
                var root = doc.getRoot();
                var views2d = root.search({ type: 'geometry', role: '2d' });
                var views3d = root.search({ type: 'geometry', role: '3d' });
                var allViews = views2d.concat(views3d);
                if (!allViews.length) allViews = root.search({ type: 'geometry' });
                if (!allViews.length) return;
                var defaultView = views2d.length ? views2d[0] : allViews[0];
                viewer.loadDocumentNode(doc, defaultView);
            },
            function(code, msg) { console.warn('[APS-M3] Error doc:', code, msg); }
        );
    }

    /* ── three-dxf ───────────────────────────────────────────────── */
    function _m3RenderizarConThreeDxf(savedFile, originalName) {
        var container = document.getElementById('m3ViewerDxfCanvas');
        var loading   = document.getElementById('m3ViewerLoading');
        loading.style.display   = 'flex';
        container.style.display = 'none';
        container.innerHTML     = '';
        _m3DxfParsed = null;

        if (!savedFile) {
            loading.style.display = 'none';
            _m3ShowFallback('Previsualización no disponible', savedFile, originalName);
            return;
        }

        fetch(API_DXF_M3 + '?file=' + encodeURIComponent(savedFile))
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function(dxfText) {
                var parser = new DxfParser();
                try { _m3DxfParsed = parser.parseSync(dxfText); }
                catch(e) { throw new Error('Error al parsear DXF: ' + e.message); }

                var allEnts = _m3DxfParsed.entities || [];
                var modelEnts = allEnts.filter(function(e) { return !e.inPaperSpace; });
                var entities = modelEnts.length ? modelEnts : allEnts;

                var dxfCopy = {};
                for (var k in _m3DxfParsed) { if (_m3DxfParsed.hasOwnProperty(k)) dxfCopy[k] = _m3DxfParsed[k]; }
                dxfCopy.entities = entities;

                container.style.display = 'block';
                var viewport = document.createElement('div');
                viewport.id = 'm3ThreeDxfVp';
                container.appendChild(viewport);

                var w = container.offsetWidth || 900;
                var h = Math.max(480, Math.round(w * 0.6));
                viewport.style.width  = w + 'px';
                viewport.style.height = h + 'px';

                try {
                    _m3ThreeDxf = new ThreeDxf.Viewer(dxfCopy, viewport, w, h, { font: null });
                } catch(e) {
                    viewport.innerHTML = '<div style="color:#e74c3c;padding:20px;font-size:.9rem;">Error al renderizar: ' + escHtml(e.message) + '</div>';
                }
                loading.style.display = 'none';
            })
            .catch(function(err) {
                loading.style.display = 'none';
                _m3RenderizarComoSVG(_m3CurrentFile, originalName);
            });
    }

    /* ── SVG fallback (igual que planos_detalle) ────────────────────── */
    function _m3RenderizarComoSVG(savedFile, originalName) {
        var canvas  = document.getElementById('m3ViewerDxfCanvas');
        var loading = document.getElementById('m3ViewerLoading');
        loading.style.display = 'flex';

        fetch(API_VER_M3 + '?file=' + encodeURIComponent(savedFile), { cache: 'no-store' })
            .then(function(r) {
                var ct = r.headers.get('Content-Type') || '';
                if (ct.indexOf('json') !== -1) {
                    return r.json().then(function(data) {
                        if (data.error === 'pending') throw new Error('__pending__');
                        throw new Error(data.error || 'Error desconocido');
                    });
                }
                if (!r.ok) throw new Error('Error HTTP ' + r.status);
                return r.text();
            })
            .then(function(svgString) {
                if (!svgString || svgString.indexOf('<svg') === -1)
                    throw new Error('Respuesta no es SVG válido');
                canvas.innerHTML = svgString;
                var svgEl = canvas.querySelector('svg');
                if (svgEl) {
                    svgEl.removeAttribute('width');
                    svgEl.removeAttribute('height');
                    svgEl.style.cssText = 'width:100%;height:auto;display:block;';
                    svgEl.setAttribute('preserveAspectRatio', 'xMidYMid meet');
                    if (svgString.indexOf('non-scaling-stroke') === -1) {
                        var vs = document.createElementNS('http://www.w3.org/2000/svg', 'style');
                        vs.textContent = 'path,line,polyline,polygon,circle,ellipse{vector-effect:non-scaling-stroke}';
                        var defs = svgEl.querySelector('defs');
                        if (defs) defs.insertBefore(vs, defs.firstChild);
                        else svgEl.insertBefore(vs, svgEl.firstChild);
                        svgEl.querySelectorAll('style').forEach(function(s) {
                            s.textContent = s.textContent.replace(/stroke-width:\s*([\d.]+)/g, function(_, n) {
                                var v = parseFloat(n);
                                return 'stroke-width:' + (isNaN(v) ? n : Math.max(0.5, Math.round(v / 4 * 100) / 100));
                            });
                        });
                    }
                }
                loading.style.display = 'none';
                canvas.style.display  = 'block';
            })
            .catch(function(err) {
                var msg = err.message || '';
                if (msg === '__pending__') { _m3ShowPending(savedFile, originalName); return; }
                loading.style.display = 'none';
                _m3ShowFallback(msg || null, savedFile, originalName);
            });
    }

    var _m3PendingTimer = null;
    function _m3ShowPending(savedFile, originalName) {
        document.getElementById('m3ViewerLoading').style.display = 'none';
        var fb = document.getElementById('m3ViewerFallback');
        fb.style.display = 'flex';
        fb.innerHTML =
            '<div class="m3fvf-icon"><i class="fas fa-cog fa-spin"></i></div>' +
            '<div class="m3fvf-title">Previsualización en generación</div>' +
            '<div class="m3fvf-msg">El archivo está siendo convertido en el servidor. ' +
            'Se actualizará automáticamente cuando esté listo.</div>' +
            '<div class="m3fvf-msg" style="font-size:.8rem;color:#888;margin-top:8px;">Reintentando en <span id="m3PendingCountdown">15</span>s...</div>';

        var secs = 15;
        if (_m3PendingTimer) clearInterval(_m3PendingTimer);
        _m3PendingTimer = setInterval(function() {
            secs--;
            var cd = document.getElementById('m3PendingCountdown');
            if (cd) cd.textContent = secs;
            if (secs <= 0) {
                clearInterval(_m3PendingTimer);
                _m3PendingTimer = null;
                if (document.getElementById('m3ViewerOverlay').classList.contains('open')) {
                    fb.style.display = 'none';
                    document.getElementById('m3ViewerLoading').style.display = 'flex';
                    abrirVisorM3(_m3CurrentFile, originalName);
                }
            }
        }, 1000);
    }

    /* ─── Historial de versiones Mod3 ─────────────────────────── */
    var API_M3_HIST     = '../../api/fh_ing/get_mod3_dwg_history.php';
    var API_M3_HIST_DL  = '../../api/fh_ing/descargar_hist_mod3.php';
    var API_M3_HIST_DWN = '../../api/fh_ing/descargar_mod3.php';
    var _m3HistIdPlanos = 0;
    var _m3HistCodPlano = '';

    function _m3AbrirHistorial(idPlanos, codPlano) {
        _m3HistIdPlanos = idPlanos;
        _m3HistCodPlano = codPlano;
        document.getElementById('m3HistSubtitle').textContent = codPlano;
        var body = document.getElementById('m3HistBody');
        body.innerHTML = '<div id="m3HistLoading" style="text-align:center;padding:30px;color:#6c757d;font-size:.88rem;"><i class="fas fa-spinner fa-spin" style="font-size:1.5rem;margin-bottom:8px;display:block;"></i>Cargando historial…</div>';
        document.getElementById('m3HistOverlay').classList.add('open');
        _m3CargarHistorial();
    }
    function _m3CerrarHistorial() {
        document.getElementById('m3HistOverlay').classList.remove('open');
    }
    function _m3CargarHistorial() {
        fetch(API_M3_HIST + '?id_planos=' + encodeURIComponent(_m3HistIdPlanos))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) { _m3HistRenderError(data.error || 'Error'); return; }
                var sub = (data.cod_plano || '') + (data.desc_mod3 ? ' · ' + data.desc_mod3 : '');
                document.getElementById('m3HistSubtitle').textContent = sub;
                var body     = document.getElementById('m3HistBody');
                body.innerHTML = '';
                var historial = data.historial || [];
                var actual    = data.actual || null;
                if (!historial.length) {
                    body.innerHTML = '<div style="text-align:center;padding:40px 20px;color:#aaa;font-size:.88rem;"><i class="fas fa-folder-open" style="font-size:2.5rem;color:#ddd;display:block;margin-bottom:10px;"></i>Solo hay una versión de este plano.<br><small>Cuando cambies el archivo, las versiones anteriores aparecerán aquí.</small></div>';
                    return;
                }
                var total = historial.length + 1;
                // ── Versión actual ──────────────────────────────────
                var cn    = actual ? (actual.arch_original || actual.arch_plano || '—') : '—';
                var cbn   = cn.split('/').pop();
                var cext  = cbn.split('.').pop().toLowerCase();
                var cextC = _m3HistExtClass(cext);
                body.insertAdjacentHTML('beforeend',
                    '<div class="hv-entry current-ver">' +
                    '<div class="hv-dot current">v' + total + '</div>' +
                    '<div class="hv-card">' +
                    '<div class="hv-card-top"><span class="hv-badge-curr">ACTUAL</span>' +
                    '<span class="hv-filename">' + escHtml(cbn) + '</span>' +
                    '<span class="hv-ext ' + cextC + '">' + cext.toUpperCase() + '</span></div>' +
                    '<div class="hv-meta"><i class="fas fa-calendar-alt"></i> Versión vigente</div>' +
                    '<div class="hv-actions">' +
                    (actual && actual.arch_plano ? '<a class="hv-btn view" href="' + API_M3_HIST_DWN + '?file=' + encodeURIComponent(actual.arch_plano) + '&ver=1" target="_blank"><i class="fas fa-eye"></i> Ver</a>' : '') +
                    (actual && actual.arch_plano ? '<a class="hv-btn dl"   href="' + API_M3_HIST_DWN + '?file=' + encodeURIComponent(actual.arch_plano) + '"       ><i class="fas fa-download"></i> Descargar</a>' : '') +
                    '</div></div></div>'
                );
                // ── Versiones anteriores ────────────────────────────
                historial.forEach(function(h, i) {
                    var vnum  = total - 1 - i;
                    var fname = h.arch_original || h.arch_plano || '—';
                    var bname = fname.split('/').pop();
                    var ext   = bname.split('.').pop().toLowerCase();
                    var extC  = _m3HistExtClass(ext);
                    var size  = h.tamano ? _m3HistFmtSize(h.tamano) : '';
                    var meta  = [h.usuario, h.fecha_str, size].filter(Boolean).join(' · ');
                    body.insertAdjacentHTML('beforeend',
                        '<div class="hv-entry">' +
                        '<div class="hv-dot old">v' + vnum + '</div>' +
                        '<div class="hv-card">' +
                        '<div class="hv-card-top"><span class="hv-badge-ver">v' + vnum + '</span>' +
                        '<span class="hv-filename">' + escHtml(bname) + '</span>' +
                        '<span class="hv-ext ' + extC + '">' + ext.toUpperCase() + '</span></div>' +
                        '<div class="hv-meta"><i class="fas fa-user"></i> ' + escHtml(meta) + '</div>' +
                        '<div class="hv-actions">' +
                        '<a class="hv-btn view" href="' + API_M3_HIST_DL + '?id=' + h.id + '&ver=1" target="_blank"><i class="fas fa-eye"></i> Ver</a>' +
                        '<a class="hv-btn dl"   href="' + API_M3_HIST_DL + '?id=' + h.id + '"       ><i class="fas fa-download"></i> Descargar</a>' +
                        '</div></div></div>'
                    );
                });
            })
            .catch(function(e) { _m3HistRenderError(e.message); });
    }
    function _m3HistRenderError(msg) {
        document.getElementById('m3HistBody').innerHTML =
            '<div style="text-align:center;padding:30px;color:#e74c3c;font-size:.88rem;">' +
            '<i class="fas fa-exclamation-triangle" style="font-size:2rem;margin-bottom:10px;display:block;"></i>' + escHtml(msg) + '</div>';
    }
    function _m3HistExtClass(ext) {
        if (['dwg','dxf','dws','dwt'].indexOf(ext) !== -1) return 'hv-ext t-cad';
        if (ext === 'pdf') return 'hv-ext t-pdf';
        return 'hv-ext t-other';
    }
    function _m3HistFmtSize(bytes) {
        if (bytes >= 1048576) return (bytes/1048576).toFixed(1) + ' MB';
        if (bytes >= 1024)    return (bytes/1024).toFixed(0) + ' KB';
        return bytes + ' B';
    }

    /* ─── Observaciones Mod3 ───────────────────────────────────── */
    window._m3VerObsImg = function(id) { FHReview.openGallery([{url:API_OBS_IMG_VER+'?id='+encodeURIComponent(id),title:'Imagen de la anotación'}],0); };

    function _m3ResetObsBadge() {
        var badge = document.getElementById('m3ObsBadge');
        if (badge) { badge.textContent = ''; badge.style.display = 'none'; }
    }

    function _m3SetObsPanel(open) {
        _m3ObsPanelOpen = open;
        var panel = document.getElementById('m3ObsPanel');
        var btn   = document.getElementById('m3ObsToggleBtn');
        if (open) {
            panel.classList.add('open');
            btn.classList.add('active');
            if (_m3ObsIdPlanos) _m3CargarObs(_m3ObsIdPlanos);
        } else {
            panel.classList.remove('open');
            btn.classList.remove('active');
        }
    }

    var _m2ObsModulo=2;
    function _m3CargarObs(id) {
        if (!id) return;
        var wrap    = document.getElementById('m3ObsListWrap');
        var loading = document.getElementById('m3ObsLoading');
        var empty   = document.getElementById('m3ObsEmpty');
        loading.style.display = '';
        empty.style.display   = 'none';
        wrap.querySelectorAll('.m3obs-item').forEach(function(e) { e.remove(); });
        fetch(API_OBS_GET + '?id_planos=' + encodeURIComponent(id)+'&modulo='+_m2ObsModulo)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                loading.style.display = 'none';
                var list = Array.isArray(data) ? data : (data.obs || []);
                if (!list.length) { empty.style.display = ''; return; }
                var badge = document.getElementById('m3ObsBadge');
                badge.textContent = list.length;
                badge.style.display = '';
                list.forEach(function(o) {
                    var div = document.createElement('div');
                    div.className = 'm3obs-item';
                    var imgHtml = '';
                    if (o.imagenes && o.imagenes.length) {
                        imgHtml = '<div class="m3obs-img-row">';
                        o.imagenes.forEach(function(im) {
                            imgHtml += '<img class="m3obs-thumb" src="' + API_OBS_IMG_VER + '?id=' + im.id + '"' +
                                ' title="' + escHtml(im.nombre || '') + '"' +
                                ' onclick="window._m3VerObsImg(' + im.id + ')" alt="">';
                        });
                        imgHtml += '</div>';
                    }
                    div.innerHTML =
                        '<div class="m3obs-item-meta"><span>' + escHtml(o.usuario || '') + '</span><span>' + escHtml(o.fecha || '') + '</span></div>' +
                        '<div class="m3obs-item-text">' + escHtml(o.texto || '') + '</div>' + imgHtml;
                    wrap.appendChild(div);
                });
            })
            .catch(function() { loading.style.display = 'none'; });
    }

    function _m3GuardarObs() {
        var texto = document.getElementById('m3ObsTexto').value.trim();
        var msg   = document.getElementById('m3ObsMsg');
        if (!texto) { msg.style.color = '#e74c3c'; msg.textContent = 'Escribe una observación antes de guardar.'; return; }
        if (!_m3ObsIdPlanos) { msg.style.color = '#e74c3c'; msg.textContent = 'El plano debe estar guardado para agregar observaciones.'; return; }
        var btn = document.getElementById('m3ObsGuardar');
        btn.disabled = true;
        msg.textContent = '';
        fetch(API_OBS_SAVE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_planos: _m3ObsIdPlanos, ot: selectedOT, cod_plano: _m3ObsCodPlano, texto: texto, modulo: 2 })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var newId = data.obs && data.obs.id ? data.obs.id : 0;
                document.getElementById('m3ObsTexto').value = '';
                msg.style.color = '#27ae60';
                msg.textContent = '✓ Nota guardada.';
                if (newId && _m3ObsImgFiles.length) {
                    var uploads = _m3ObsImgFiles.map(function(f) {
                        var fd = new FormData();
                        fd.append('image', f);
                        fd.append('id_obs', newId);
                        return fetch(API_OBS_IMG, { method: 'POST', body: fd }).then(function(r) { return r.json(); });
                    });
                    Promise.all(uploads).then(function() {
                        _m3ObsImgFiles = [];
                        document.getElementById('m3ObsImgPreview').innerHTML = '';
                        document.getElementById('m3ObsImgInput').value = '';
                        _m2ObsModulo=2;_m3CargarObs(_m3ObsIdPlanos);
                    });
                } else {
                    _m3ObsImgFiles = [];
                    document.getElementById('m3ObsImgPreview').innerHTML = '';
                    document.getElementById('m3ObsImgInput').value = '';
                    _m3CargarObs(_m3ObsIdPlanos);
                }
            } else {
                msg.style.color = '#e74c3c';
                msg.textContent = 'Error: ' + (data.error || data.message || 'No se pudo guardar.');
            }
        })
        .catch(function() { msg.style.color = '#e74c3c'; msg.textContent = 'Error de conexión.'; })
        .finally(function() { btn.disabled = false; });
    }

    function _m3ObsImgSelected(filesArg) {
        var input   = document.getElementById('m3ObsImgInput');
        var files   = filesArg || input.files;
        var preview = document.getElementById('m3ObsImgPreview');
        Array.from(files).forEach(function(f) {
            if (!f.type.match(/^image\//)) return;
            _m3ObsImgFiles.push(f);
            var item = document.createElement('div');
            item.className = 'm3obs-img-prev-item';
            var url = URL.createObjectURL(f);
            var idx = _m3ObsImgFiles.length - 1;
            item.innerHTML = '<img src="' + url + '" alt="">' +
                '<button class="m3obs-img-prev-del" onclick="this.parentNode.remove();_m3ObsImgFiles.splice(' + idx + ',1);" title="Quitar">&times;</button>';
            preview.appendChild(item);
        });
        input.value = '';
    }

    /* ─── Init ────────────────────────────────────────────────── */
    document.getElementById('m2ObsActual').addEventListener('click',function(){_m2ObsModulo=2;_m3CargarObs(_m3ObsIdPlanos);document.getElementById('m3ObsTexto').placeholder='Nueva observación del Módulo 2';});
    document.getElementById('m2ObsAnterior').addEventListener('click',function(){_m2ObsModulo=1;_m3CargarObs(_m3ObsIdPlanos);document.getElementById('m3ObsTexto').placeholder='Las notas nuevas se guardan en el Módulo 2';});
    // Set Gantt date picker to today
    document.getElementById('m3GanttFechaInicio').value = todayStr();
    document.getElementById('m3GanttFechaInicio').addEventListener('change', function() {
        renderGanttMod3(m3Rows);
    });

    /* Obs panel events */
    document.getElementById('m3ObsToggleBtn').addEventListener('click', function() { _m3SetObsPanel(!_m3ObsPanelOpen); });
    document.getElementById('m3ObsCloseBtn').addEventListener('click',  function() { _m3SetObsPanel(false); });
    /* Historial events */
    document.getElementById('m3HistCloseBtn').addEventListener('click',  _m3CerrarHistorial);
    document.getElementById('m3HistOverlay').addEventListener('click',   function(e) { if (e.target === this) _m3CerrarHistorial(); });
    document.getElementById('m3ObsGuardar').addEventListener('click',   _m3GuardarObs);
    document.getElementById('m3ObsImgInput').addEventListener('change', function() { _m3ObsImgSelected(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { cerrarVisorM3(); _m3CerrarHistorial(); } });
    /* Drag-drop en el label de imagen obs */
    var m3ObsLbl = document.getElementById('m3ObsImgLabel');
    m3ObsLbl.addEventListener('dragover',  function(e) { e.preventDefault(); m3ObsLbl.classList.add('drag-over'); });
    m3ObsLbl.addEventListener('dragleave', function(e) { if (!m3ObsLbl.contains(e.relatedTarget)) m3ObsLbl.classList.remove('drag-over'); });
    m3ObsLbl.addEventListener('drop',      function(e) {
        e.preventDefault();
        m3ObsLbl.classList.remove('drag-over');
        if (e.dataTransfer.files && e.dataTransfer.files.length) _m3ObsImgSelected(e.dataTransfer.files);
    });
    /* Pegar con Ctrl+V */
    document.addEventListener('paste', function(e) {
        if (!_m3ObsPanelOpen) return;
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        var imgFiles = [];
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.match(/^image\//)) { var f = items[i].getAsFile(); if (f) imgFiles.push(f); }
        }
        if (!imgFiles.length) return;
        e.preventDefault();
        _m3ObsImgSelected(imgFiles);
        m3ObsLbl.classList.remove('paste-ok');
        void m3ObsLbl.offsetWidth;
        m3ObsLbl.classList.add('paste-ok');
        setTimeout(function() { m3ObsLbl.classList.remove('paste-ok'); }, 600);
    });

    cargarOTs();

}());
</script>
<script src="../../assets/vendor/three-dxf/three.min.js"></script>
<script src="../../assets/vendor/three-dxf/dxf-parser.js"></script>
<script src="../../assets/vendor/three-dxf/three-dxf.min.js"></script>

<?php include_once dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
