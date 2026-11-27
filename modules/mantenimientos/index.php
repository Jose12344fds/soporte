<?php
/**
 * Módulo Mantenimientos — Órdenes de Trabajo
 */
$page_title = 'Órdenes de Trabajo';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');
require_permiso('mantenimientos', 'ver');
include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
.badge-tipo-PREV  { background:#17a2b8; color:#fff; }
.badge-tipo-CORR  { background:#dc3545; color:#fff; }
.badge-tipo-PRED  { background:#6f42c1; color:#fff; }
.badge-tipo-EMERG { background:#fd7e14; color:#fff; }
.badge-estado-O   { background:#007bff; color:#fff; }
.badge-estado-C   { background:#28a745; color:#fff; }
.info-box-icon.bg-mant { background: linear-gradient(135deg,#1b4332,#2d6a4f); }
.modal-equipo .modal-content { border:0; border-radius:10px; overflow:hidden; box-shadow:0 20px 60px rgba(16,36,28,.28); }
.modal-equipo .modal-header { background:linear-gradient(135deg,#12372a,#1f7a45); border:0; padding:18px 22px; }
.modal-equipo .modal-title { font-weight:800; letter-spacing:.02em; }
.modal-equipo .modal-body { background:#f7faf8; padding:22px; }
.modal-equipo .form-card { background:#fff; border:1px solid #e1e8e4; border-radius:8px; padding:16px; }
.modal-equipo label { font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:#42524a; margin-bottom:6px; }
.modal-equipo .form-control { border-radius:7px; border-color:#cfdad4; }
.modal-equipo .form-control:focus { border-color:#2f9e5f; box-shadow:0 0 0 .15rem rgba(40,167,69,.18); }
.modal-equipo .modal-footer { background:#fff; border-top:1px solid #e7eee9; }
.badge-eq-ok { background:#d4edda; color:#155724; }
.badge-eq-off { background:#f8d7da; color:#721c24; }
.badge-eq-mid { background:#fff3cd; color:#856404; }
.ot-detail-modal .modal-content { border:0; border-radius:14px; overflow:hidden; box-shadow:0 24px 70px rgba(15,42,32,.28); }
.ot-detail-modal .modal-header { background:linear-gradient(120deg,#12372a,#1f8a55); padding:18px 24px; border:0; }
.ot-detail-modal .modal-body { background:#f4f7f5; padding:20px; }
.ot-detail-card { background:#fff; border:1px solid #dfe8e3; border-radius:11px; padding:16px; height:100%; }
.ot-detail-card-title { color:#176b45; font-size:.78rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; margin-bottom:13px; }
.ot-detail-item { display:flex; align-items:flex-start; gap:10px; margin-bottom:10px; }
.ot-detail-item i { color:#269c64; width:18px; margin-top:3px; text-align:center; }
.ot-detail-item small { display:block; color:#7a8780; line-height:1.1; }
.ot-detail-item strong { display:block; color:#22352b; font-size:.92rem; }
.ot-detail-text { white-space:pre-wrap; color:#33483d; line-height:1.55; min-height:58px; }
.ot-title-strip { display:flex; align-items:center; gap:12px; background:linear-gradient(105deg,#153f2e,#238c58); color:#fff; border-radius:10px; padding:12px 16px; margin-bottom:16px; box-shadow:0 6px 16px rgba(19,76,48,.14); }
.ot-title-strip i { width:34px; height:34px; border-radius:50%; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; }
.ot-title-strip small { display:block; color:rgba(255,255,255,.72); text-transform:uppercase; letter-spacing:.07em; font-size:.65rem; }
.ot-title-strip strong { display:block; font-size:1.08rem; letter-spacing:.04em; }
.ot-evidence-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(155px,1fr)); gap:12px; }
.ot-evidence-card { border:1px solid #dce7e1; border-radius:10px; overflow:hidden; background:#fff; cursor:pointer; transition:transform .18s ease,box-shadow .18s ease; }
.ot-evidence-card:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(22,76,51,.14); }
.ot-evidence-thumb { width:100%; height:120px; object-fit:cover; display:block; background:#e8efeb; }
.ot-evidence-meta { padding:9px 10px; font-size:.75rem; }
.ot-evidence-name { font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ot-viewer-image { max-width:100%; max-height:72vh; object-fit:contain; border-radius:8px; }
.ot-cost-panel{background:linear-gradient(135deg,#fff,#f8fbff);border:1px solid #dce6f0;border-radius:12px;padding:16px;box-shadow:0 5px 16px rgba(32,58,86,.05)}
.ot-cost-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
.ot-cost-panel-title{color:#1e4f78;font-size:.8rem;font-weight:900;letter-spacing:.06em;text-transform:uppercase}
.ot-cost-panel-title small{display:block;margin-top:3px;color:#758699;font-size:.68rem;font-weight:500;letter-spacing:0;text-transform:none}
.ot-cost-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.ot-cost-stat{display:flex;align-items:center;gap:10px;min-height:62px;padding:10px 12px;border:1px solid #e1e8ef;border-radius:10px;background:#fff}
.ot-cost-stat i{display:flex;align-items:center;justify-content:center;flex:0 0 34px;width:34px;height:34px;border-radius:9px;background:#e9f3ff;color:#2676c9}
.ot-cost-stat small{display:block;color:#7b8998;font-size:.66rem;text-transform:uppercase}.ot-cost-stat strong{display:block;color:#21384d;font-size:.92rem}
.ot-cost-preview{margin-top:12px;overflow:hidden;border:1px solid #e2e9ef;border-radius:9px;background:#fff}
.ot-cost-preview-row{display:grid;grid-template-columns:90px minmax(0,1fr) 115px 105px;gap:10px;align-items:center;padding:8px 11px;border-bottom:1px solid #edf1f5;font-size:.75rem}.ot-cost-preview-row:last-child{border-bottom:0}
.ot-cost-type{display:inline-flex;align-items:center;justify-content:center;padding:4px 8px;border-radius:20px;font-size:.62rem;font-weight:900}.ot-cost-type.repuesto{background:#e8f2ff;color:#2168b3}.ot-cost-type.servicio{background:#e6f8ef;color:#168252}
.ot-cost-modal{z-index:1070}.ot-cost-modal .modal-content{overflow:hidden;border:0;border-radius:14px;box-shadow:0 25px 70px rgba(18,41,62,.3)}
.ot-cost-modal .modal-header{padding:17px 22px;border:0;background:linear-gradient(120deg,#143f5d,#197d86)}
.ot-cost-modal .modal-body{padding:18px;background:#f3f7f9}.ot-cost-modal .modal-footer{background:#fff}
.ot-cost-form-card,.ot-cost-list-card{border:1px solid #dce6ea;border-radius:12px;background:#fff;box-shadow:0 7px 20px rgba(27,62,79,.06)}
.ot-cost-form-card{padding:17px}.ot-cost-list-card{margin-top:15px;overflow:hidden}
.ot-cost-section-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;color:#174b61;font-size:.78rem;font-weight:900;letter-spacing:.05em;text-transform:uppercase}
.ot-cost-type-picker{display:grid;grid-template-columns:1fr 1fr;gap:9px}.ot-cost-type-option{position:relative;margin:0;cursor:pointer}.ot-cost-type-option input{position:absolute;opacity:0}
.ot-cost-type-option span{display:flex;align-items:center;gap:9px;padding:11px 12px;border:1px solid #d6e1e6;border-radius:9px;background:#f9fbfc;color:#526878;font-size:.76rem;font-weight:800;transition:.16s ease}.ot-cost-type-option input:checked+span{border-color:#1b8792;background:#e7f7f7;color:#116771;box-shadow:0 0 0 3px rgba(27,135,146,.1)}
.ot-cost-modal label.form-label{margin-bottom:5px;color:#536979;font-size:.66rem;font-weight:900;letter-spacing:.04em;text-transform:uppercase}.ot-cost-modal .form-control{border-color:#d5e0e5;border-radius:8px}.ot-cost-modal .form-control:focus{border-color:#1b8792;box-shadow:0 0 0 .15rem rgba(27,135,146,.12)}
.ot-cost-total-box{display:flex;align-items:center;justify-content:space-between;min-height:62px;padding:10px 14px;border-radius:10px;background:linear-gradient(120deg,#123e5b,#1b7f87);color:#fff}.ot-cost-total-box small{display:block;color:rgba(255,255,255,.7);font-size:.65rem;text-transform:uppercase}.ot-cost-total-box strong{font-size:1.12rem}
.ot-cost-pdf-box{position:relative;display:flex;align-items:center;gap:12px;min-height:72px;padding:11px 13px;border:1px dashed #8bb9c0;border-radius:9px;background:#f2fbfb}.ot-cost-pdf-box i{color:#dc3545;font-size:1.7rem}.ot-cost-pdf-box input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}.ot-cost-pdf-name{max-width:100%;overflow:hidden;color:#315965;font-size:.72rem;font-weight:700;text-overflow:ellipsis;white-space:nowrap}
.ot-cost-table{margin:0}.ot-cost-table th{border-top:0!important;background:#eaf1f4;color:#526978;font-size:.62rem;text-transform:uppercase}.ot-cost-table td{font-size:.73rem;vertical-align:middle}.ot-cost-desc{max-width:390px;white-space:normal}.ot-cost-actions{white-space:nowrap}.ot-cost-actions .btn{width:30px;height:29px;padding:4px}
@media(max-width:900px){.ot-cost-stats{grid-template-columns:1fr 1fr}.ot-cost-preview-row{grid-template-columns:80px minmax(0,1fr) 100px}.ot-cost-preview-row .ot-cost-sap{display:none}}
@media(max-width:575px){.ot-cost-panel-head{align-items:flex-start;flex-direction:column}.ot-cost-panel-head .btn{width:100%}.ot-cost-stats{grid-template-columns:1fr}.ot-cost-preview-row{grid-template-columns:75px minmax(0,1fr);}.ot-cost-preview-row .ot-cost-amount,.ot-cost-preview-row .ot-cost-sap{display:none}}
.ot-page-header{padding:18px 0 12px}
.ot-heading{display:flex;align-items:center;gap:12px}
.ot-heading-icon{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:13px;background:linear-gradient(145deg,#e5f8ee,#f7fcf9);color:#19a766;font-size:21px;box-shadow:0 7px 18px rgba(27,151,94,.13)}
.ot-heading h1{margin:0;color:#182941;font-size:24px;font-weight:800;letter-spacing:-.35px}
.ot-heading small{display:block;margin-top:3px;color:#728097;font-size:11px;font-weight:500;letter-spacing:.01em}
.ot-page-header .breadcrumb{margin-top:10px;font-size:11px}
.ot-stats-row{margin-right:-7px;margin-left:-7px;margin-bottom:10px}
.ot-stats-row>[class*=col-]{padding-right:7px;padding-left:7px}
.ot-stat-card{position:relative;display:flex;align-items:center;gap:14px;min-height:98px;margin-bottom:14px;padding:16px 18px;overflow:hidden;border:1px solid #e7edf3;border-radius:12px;background:#fff;box-shadow:0 7px 20px rgba(31,47,70,.07);animation:otRise .38s ease both}
.ot-stat-card:after{position:absolute;right:0;bottom:0;left:0;height:3px;background:var(--stat-color);content:""}
.ot-stat-card:hover{transform:translateY(-2px);box-shadow:0 11px 25px rgba(31,47,70,.11);transition:.18s ease}
.ot-stat-icon{display:flex;align-items:center;justify-content:center;flex:0 0 48px;width:48px;height:48px;border-radius:50%;background:var(--stat-soft);color:var(--stat-color);font-size:21px}
.ot-stat-copy{display:flex;flex-direction:column;min-width:0}
.ot-stat-label{color:var(--stat-color);font-size:12px;font-weight:800}
.ot-stat-number{margin:1px 0;color:#16243a;font-size:25px;font-weight:800;line-height:1}
.ot-stat-help{color:#7b8798;font-size:10px;white-space:nowrap}
.ot-stat-open{--stat-color:#2877ee;--stat-soft:#e8f1ff}.ot-stat-closed{--stat-color:#16ad58;--stat-soft:#e3f8eb}.ot-stat-canceled{--stat-color:#ef3349;--stat-soft:#ffe8eb}.ot-stat-unassigned{--stat-color:#f2a900;--stat-soft:#fff3d7}
.ot-stats-row>[class*=col-]:nth-child(2) .ot-stat-card{animation-delay:.04s}.ot-stats-row>[class*=col-]:nth-child(3) .ot-stat-card{animation-delay:.08s}.ot-stats-row>[class*=col-]:nth-child(4) .ot-stat-card{animation-delay:.12s}
.ot-workspace-tools{display:grid;grid-template-columns:minmax(0,1fr) 290px;gap:14px;margin-bottom:14px}
#otFiltersMount{min-width:0}
#tblOT_filter{float:none!important;display:grid;grid-template-columns:minmax(250px,.8fr) minmax(430px,1.2fr);gap:14px;width:100%;max-width:none;margin:0!important;text-align:left!important}
#tblOT_filter>label.ot-search-field,.ot-period-filter,.ot-program-card{min-height:88px;border:1px solid #e7edf3;border-radius:12px;background:#fff;box-shadow:0 7px 20px rgba(31,47,70,.06)}
#tblOT_filter>label.ot-search-field{display:flex;flex-direction:column;justify-content:center;margin:0;padding:13px 15px;text-align:left}
.ot-period-filter{position:relative;display:flex;align-items:flex-end;gap:10px;margin:0;padding:30px 15px 13px;text-align:left}
.ot-filter-group-title{position:absolute;top:12px;left:15px;color:#2868c7;font-size:9px;font-weight:900;letter-spacing:.55px;text-transform:uppercase}
.ot-period-filter .ot-filter-field{display:flex;flex:1 1 145px;flex-direction:column;min-width:120px}
.ot-period-filter label,.ot-search-field .ot-filter-label{margin:0 0 5px;color:#607086;font-size:9px;font-weight:800;letter-spacing:.4px;text-transform:uppercase}
.ot-period-filter select{height:36px;padding:4px 30px 4px 9px;border:1px solid #d8e0ea;border-radius:7px;background-color:#fbfcfe;color:#273a54;font-size:11px}
.ot-period-filter select:focus{border-color:#2877ee;box-shadow:0 0 0 3px rgba(40,119,238,.11)}
.ot-period-count{display:inline-flex;align-items:center;justify-content:center;min-width:66px;height:36px;padding:0 10px;border-radius:8px;background:#e3f8eb;color:#159453;font-size:11px;font-weight:900;white-space:nowrap}
.ot-search-input-wrap{position:relative;display:block;width:100%}
.ot-search-input-wrap i{position:absolute;top:50%;left:12px;z-index:2;color:#6f82a1;transform:translateY(-50%);pointer-events:none}
#tblOT_filter .ot-search-input{width:100%!important;height:36px;margin:0!important;padding:4px 12px 4px 35px;border:1px solid #d8e0ea;border-radius:7px;background:#fbfcfe;color:#273a54;font-size:11px}
#tblOT_filter .ot-search-input:focus{border-color:#2877ee;box-shadow:0 0 0 3px rgba(40,119,238,.11);outline:0}
.ot-program-card{display:flex;align-items:center;gap:12px;width:100%;padding:14px 16px;color:#225eb8;text-align:left;cursor:pointer;transition:.18s ease}
.ot-program-card:hover{border-color:#70a2f0;color:#174c9b;transform:translateY(-2px);box-shadow:0 11px 25px rgba(38,103,202,.13)}
.ot-program-card:focus{outline:0;box-shadow:0 0 0 3px rgba(40,119,238,.13)}
.ot-program-card-icon{display:flex;align-items:center;justify-content:center;flex:0 0 40px;width:40px;height:40px;border-radius:10px;background:#eaf2ff;font-size:17px}
.ot-program-card strong{display:block;margin-bottom:3px;font-size:13px}.ot-program-card small{display:block;color:#718099;font-size:10px;line-height:1.35}
.ot-list-card{overflow:hidden;border:1px solid #e4eaf1!important;border-radius:12px;box-shadow:0 9px 26px rgba(31,47,70,.08)}
.ot-list-card .card-header{display:flex;align-items:center;min-height:58px;padding:12px 16px;border-bottom:1px solid #e9eef4;background:#fff}
.ot-list-card .card-title{color:#1a2c46;font-size:14px;font-weight:800}
.ot-list-card .card-tools{display:flex;align-items:center;gap:7px;margin-left:auto}
.ot-list-card .card-tools .btn{height:34px;margin:0!important;padding:6px 12px;border-radius:7px;font-size:11px;font-weight:700}
.ot-btn-close{border-color:#f0603c!important;background:linear-gradient(135deg,#ff794f,#ea4c2e)!important;color:#fff!important;box-shadow:0 5px 12px rgba(234,76,46,.18)}
.ot-btn-new{border-color:#19a45f!important;background:linear-gradient(135deg,#24bd72,#159b57)!important;box-shadow:0 5px 12px rgba(21,155,87,.18)}
.ot-list-card .card-body{padding:8px 14px 12px}
#tblOT{margin-bottom:5px!important;border-collapse:separate!important;border-spacing:0}
#tblOT thead th{padding:10px 8px;border-top:0!important;border-right:0;border-bottom:1px solid #dce4ed!important;border-left:0;background:#f8fafc;color:#52657d;font-size:9px;font-weight:900;letter-spacing:.35px;text-transform:uppercase;vertical-align:middle}
#tblOT tbody td{padding:8px;border-top:0;border-right:0;border-bottom:1px solid #edf1f5;border-left:0;background:#fff;color:#2e4058;font-size:11px;vertical-align:middle}
#tblOT tbody tr:hover td{background:#f7fbff}
#tblOT tbody td:nth-child(2){color:#1268d4}
#tblOT .ot-mode-badge{display:inline-flex;align-items:center;justify-content:center;min-width:35px;padding:4px 8px;border:1px solid transparent;border-radius:999px;font-size:9px;font-weight:900;letter-spacing:.45px;line-height:1;text-transform:uppercase}
#tblOT .ot-mode-badge.mr{border-color:#a9dfe3;background:#e5f7f8;color:#087b84}
#tblOT .ot-mode-badge.m{border-color:#f2d184;background:#fff5d9;color:#9b6900}
#tblOT .btn-group{gap:4px}#tblOT .btn-group .btn{width:29px;height:28px;padding:4px;border-radius:6px!important;background:#f8fbff;font-size:11px;box-shadow:none}
#tblOT .btn-group .btn-primary{border-color:#dce9fb;color:#1670dd}#tblOT .btn-group .btn-outline-info{border-color:#d6f1ea;color:#16a078}#tblOT .btn-group .btn-outline-success{border-color:#d8f0df;color:#18a452}#tblOT .btn-group .btn-outline-danger{border-color:#ffe0e4;color:#ef4053}
.ot-list-card .dataTables_info{padding:12px 4px 5px!important;color:#718096;font-size:10px}.ot-list-card .dataTables_paginate{padding-top:8px!important}.ot-list-card .page-link{min-width:32px;margin:0 2px;border-color:#e2e8f0;border-radius:6px!important;color:#49617e;font-size:11px;text-align:center}.ot-list-card .page-item.active .page-link{border-color:#2877ee;background:#2877ee;box-shadow:0 4px 10px rgba(40,119,238,.2)}
@keyframes otRise{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:1100px){.ot-workspace-tools{grid-template-columns:1fr}.ot-program-card{min-height:76px}#tblOT_filter{grid-template-columns:1fr 1.35fr}}
@media(max-width:767px){.ot-page-header{padding-top:10px}.ot-heading h1{font-size:20px}.ot-page-header .breadcrumb{float:none!important;padding-left:56px}.ot-detail-modal .modal-body{padding:12px}.ot-evidence-grid{grid-template-columns:repeat(2,minmax(0,1fr))}#tblOT_filter{grid-template-columns:1fr}.ot-period-filter{flex-wrap:wrap}.ot-period-filter .ot-filter-field{flex:1 1 125px}.ot-period-count{flex:1 1 100%;width:100%}.ot-list-card .card-header{align-items:flex-start;flex-direction:column;gap:10px}.ot-list-card .card-tools{width:100%;margin-left:0}.ot-list-card .card-tools .btn,.ot-list-card .card-tools a{flex:1}.ot-stat-help{white-space:normal}}
</style>

<!-- Content Header -->
<section class="content-header ot-page-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="ot-heading">
                    <span class="ot-heading-icon"><i class="fas fa-wrench"></i></span>
                    <div>
                        <h1>Órdenes de Trabajo</h1>
                        <small>Gestión y seguimiento de órdenes de mantenimiento</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                    <li class="breadcrumb-item active">Mantenimientos / OT</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <!-- Stats -->
        <div class="row ot-stats-row" id="statsRow">
            <div class="col-6 col-md-3">
                <div class="ot-stat-card ot-stat-open">
                    <span class="ot-stat-icon"><i class="fas fa-clipboard"></i></span>
                    <div class="ot-stat-copy">
                        <span class="ot-stat-label">Abiertas</span>
                        <strong class="ot-stat-number" id="statAbiertas">—</strong>
                        <small class="ot-stat-help">Órdenes pendientes</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="ot-stat-card ot-stat-closed">
                    <span class="ot-stat-icon"><i class="fas fa-check"></i></span>
                    <div class="ot-stat-copy">
                        <span class="ot-stat-label">Cerradas</span>
                        <strong class="ot-stat-number" id="statCerradas">—</strong>
                        <small class="ot-stat-help">Órdenes completadas</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="ot-stat-card ot-stat-canceled">
                    <span class="ot-stat-icon"><i class="fas fa-ban"></i></span>
                    <div class="ot-stat-copy">
                        <span class="ot-stat-label">Anuladas</span>
                        <strong class="ot-stat-number" id="statAnuladas">—</strong>
                        <small class="ot-stat-help">Órdenes anuladas</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="ot-stat-card ot-stat-unassigned">
                    <span class="ot-stat-icon"><i class="fas fa-user-clock"></i></span>
                    <div class="ot-stat-copy">
                        <span class="ot-stat-label">Sin responsable</span>
                        <strong class="ot-stat-number" id="statSinResponsable">—</strong>
                        <small class="ot-stat-help">Pendientes de asignar</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="ot-workspace-tools">
            <div id="otFiltersMount"></div>
            <button type="button" class="ot-program-card" id="btnVerProgSemana">
                <span class="ot-program-card-icon"><i class="fas fa-calendar-week"></i></span>
                <span>
                    <strong>Programación semanal</strong>
                    <small>Ver y gestionar las órdenes programadas por semana</small>
                </span>
            </button>
        </div>

        <!-- Tabla OT -->
        <div class="card ot-list-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Listado de Órdenes de Trabajo</h3>
                <div class="card-tools">
                    <?php if (tiene_permiso('mantenimientos', 'editar')): ?>
                    <button type="button" class="btn btn-sm ot-btn-close" id="btnCerrarOTMasivo" disabled>
                        <i class="fas fa-check-double"></i> Cerrar seleccionadas <span class="badge badge-light ml-1" id="countOTSeleccionadas">0</span>
                    </button>
                    <?php endif; ?>
                    <a href="reportes.php" class="btn btn-sm btn-info">
                        <i class="fas fa-chart-column"></i> Reportes
                    </a>
                    <a href="planes_accion.php" class="btn btn-sm btn-warning font-weight-bold">
                        <i class="fas fa-route"></i> Planes de acción
                    </a>
                    <?php if (tiene_permiso('mantenimientos', 'crear')): ?>
                    <a href="nueva_ot.php" class="btn btn-sm btn-success ot-btn-new">
                        <i class="fas fa-plus"></i> Nueva OT
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div id="spinnerOT" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-secondary"></i>
                    <p class="mt-2 text-muted">Cargando datos desde SAP…</p>
                </div>
                <div id="wrapTabla" style="display:none;">
                    <table id="tblOT" class="table table-hover table-sm w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:34px;">
                                    <input type="checkbox" id="checkTodasOT" title="Seleccionar OT abiertas visibles">
                                </th>
                                <th class="text-center" style="width:60px;">N° OT</th>
                                <th>Equipo</th>
                                <th class="text-center" style="width:110px;">Tipo</th>
                                <th class="text-center" style="width:82px;">Modalidad</th>
                                <th>Técnico</th>
                                <th class="text-center" style="width:100px;">F. Inicio</th>
                                <th class="text-center" style="width:80px;">Estado</th>
                                <th class="text-center" style="width:86px;">Tareo</th>
                                <th class="text-center" style="width:170px;">Acciones</th>
                                <th>Periodo OT</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (false): // El catalogo se gestiona exclusivamente desde equipos.php. ?>
        <!-- Catalogo de equipos SAP -->
        <div class="card equipos-card shadow-sm">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0 font-weight-bold">
                    <i class="fas fa-truck-pickup mr-2"></i>Catalogo de equipos SAP
                </h3>
                <div class="ml-auto">
                    <button type="button" class="btn btn-sm btn-light mr-1" id="btnRefreshEquipos">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    <a href="equipos.php" class="btn btn-sm btn-outline-light mr-1">
                        <i class="fas fa-external-link-alt"></i> Abrir catálogo
                    </a>
                    <a href="import_equipos_tm.php" class="btn btn-sm btn-outline-light mr-1">
                        <i class="fas fa-file-upload"></i> Importar
                    </a>
                    <?php if (tiene_permiso('mantenimientos', 'crear')): ?>
                    <button type="button" class="btn btn-sm btn-warning font-weight-bold btn-nuevo-equipo">
                        <i class="fas fa-plus-circle"></i> Agregar equipo
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3" id="eqStatsRow">
                    <div class="col-6 col-lg-3 mb-2">
                        <div class="eq-stat">
                            <div class="lbl">Total SAP</div>
                            <div class="num text-primary" id="eqStatTotal">--</div>
                            <small class="text-muted">filas en @TAMA_EQUIPOS_TM</small>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3 mb-2">
                        <div class="eq-stat">
                            <div class="lbl">Operativos</div>
                            <div class="num text-success" id="eqStatActivos">--</div>
                            <small class="text-muted">estado operativo/activo</small>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3 mb-2">
                        <div class="eq-stat">
                            <div class="lbl">De baja</div>
                            <div class="num text-danger" id="eqStatBaja">--</div>
                            <small class="text-muted">marcados como baja</small>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3 mb-2">
                        <div class="eq-stat">
                            <div class="lbl">Sin serie</div>
                            <div class="num text-warning" id="eqStatSinSerie">--</div>
                            <small class="text-muted">pendientes de completar</small>
                        </div>
                    </div>
                </div>

                <div id="spinnerEquipos" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-info"></i>
                    <p class="mt-2 text-muted">Leyendo equipos desde SAP...</p>
                </div>
                <div id="wrapTablaEquipos" style="display:none;">
                    <table id="tblEquipos" class="table table-bordered table-striped table-hover table-sm w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:80px;">Code</th>
                                <th>Equipo</th>
                                <th>Inventario / Serie</th>
                                <th>Marca / Modelo</th>
                                <th>Tipo / Ubicacion</th>
                                <th class="text-center" style="width:110px;">Estado</th>
                                <th class="text-center" style="width:110px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: PROGRAMACIÓN SEMANAL
     Reemplaza el bloque completo del div#modalProgSemana en index.php
     (líneas ~225-271 del original)
════════════════════════════════════════════════════════════════ -->

<!-- CSS del modal — añadir dentro del <style> existente o justo antes del modal -->
<style>
/* ── Modal Programación Semanal ── */
#modalProgSemana .modal-dialog {
    width: calc(100vw - 32px);
    max-width: 1800px;
    margin: 16px auto;
}
#modalProgSemana .modal-content {
    border: 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(15, 40, 80, .25);
}
#modalProgSemana .ps-header {
    background: linear-gradient(135deg, #0f4c81 0%, #1c7c8c 100%);
    padding: 16px 22px;
    display: flex;
    align-items: center;
    gap: 12px;
}
#modalProgSemana .ps-header-title {
    font-size: 16px;
    font-weight: 800;
    color: #fff;
    letter-spacing: .02em;
    flex: 1;
}
#modalProgSemana .ps-header .close {
    color: #fff;
    opacity: .8;
    font-size: 22px;
    text-shadow: none;
    margin-left: 0;
    padding: 0;
}
#modalProgSemana .ps-header .close:hover { opacity: 1; }

/* ── Barra de controles ── */
#modalProgSemana .ps-toolbar {
    background: #f0f6fb;
    border-bottom: 1px solid #d6e4ef;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}
#modalProgSemana .ps-semana-nav {
    display: flex;
    align-items: center;
    gap: 6px;
}
#modalProgSemana .ps-semana-nav .btn-nav {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid #b0c9df;
    background: #fff;
    color: #0f4c81;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: background .15s;
}
#modalProgSemana .ps-semana-nav .btn-nav:hover {
    background: #deeaf5;
}
#modalProgSemana #psSelectSemana {
    border: 1px solid #b0c9df;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #0f4c81;
    background: #fff;
    padding: 5px 10px;
    min-width: 220px;
    cursor: pointer;
    height: 32px;
}
#modalProgSemana #psSelectSemana:focus {
    border-color: #1c7c8c;
    outline: none;
    box-shadow: 0 0 0 3px rgba(28, 124, 140, .15);
}
#modalProgSemana .ps-semana-actual-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #0f4c81;
    color: #fff;
    border-radius: 20px;
    padding: 2px 10px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}
#modalProgSemana .ps-rango-label {
    font-size: 12px;
    color: #4a6880;
    font-weight: 500;
    white-space: nowrap;
}
#modalProgSemana .ps-toolbar-actions {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

/* ── Stats resumen ── */
#modalProgSemana .ps-stats {
    display: flex;
    gap: 8px;
    padding: 10px 20px 0;
    flex-wrap: wrap;
}
#modalProgSemana .ps-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid transparent;
}
#modalProgSemana .ps-stat-pill.total   { background: #e8f0fb; color: #1a3d8f; border-color: #b8cef5; }
#modalProgSemana .ps-stat-pill.pend    { background: #fff8e1; color: #795800; border-color: #ffe082; }
#modalProgSemana .ps-stat-pill.ejec    { background: #e8f5e9; color: #1b5e20; border-color: #a5d6a7; }
#modalProgSemana .ps-stat-pill.venc    { background: #fdecea; color: #8b1a14; border-color: #f1948a; }
#modalProgSemana .ps-stat-pill.fuera   { background: #f3e5f5; color: #4a148c; border-color: #ce93d8; }
#modalProgSemana .ps-stat-pill .ps-count {
    font-size: 15px;
    font-weight: 800;
}
#modalProgSemana .ps-filters {
    margin: 10px 20px 0;
    padding: 10px 12px;
    border: 1px solid #d8e6f2;
    border-radius: 8px;
    background: #fff;
    display: flex;
    align-items: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}
#modalProgSemana .ps-filter-field { min-width: 190px; flex: 1 1 220px; }
#modalProgSemana .ps-filter-search { flex: 2 1 340px; }
#modalProgSemana .ps-filter-field label {
    display: block;
    margin: 0 0 4px;
    color: #4a6880;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}
#modalProgSemana .ps-filter-field .form-control { height: 32px; font-size: 12px; border-color: #bfd2e3; }
#modalProgSemana .ps-filter-result { color: #587187; font-size: 12px; white-space: nowrap; }
@media (max-width: 767.98px) {
    #modalProgSemana .modal-dialog { width: calc(100vw - 12px); margin: 6px auto; }
    #modalProgSemana .ps-filters { margin-left: 10px; margin-right: 10px; }
    #modalProgSemana .ps-filter-field { min-width: 100%; }
}

/* ── Cuerpo ── */
#modalProgSemana .modal-body {
    padding: 12px 20px 16px;
    background: #f8fbff;
}
#modalProgSemana .ps-empty {
    text-align: center;
    padding: 36px 20px;
    color: #6c757d;
    font-size: 14px;
}
#modalProgSemana .ps-empty i {
    font-size: 36px;
    display: block;
    margin-bottom: 10px;
    opacity: .4;
}

/* ── Footer ── */
#modalProgSemana .ps-footer {
    background: #fff;
    border-top: 1px solid #e3edf5;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* ── Tabla ── */
#tblProgSemana thead th {
    background: #eef4fb;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #2c4a6e;
    border-bottom: 2px solid #c5d8ec !important;
    font-weight: 700;
    white-space: nowrap;
}
#tblProgSemana tbody tr:hover td { background: #f0f7ff; }
#tblProgSemana .ps-dia-label {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    background: #e8f0fb;
    color: #1a3d8f;
    white-space: nowrap;
}
#tblProgSemana .ps-dia-label.hoy {
    background: #0f4c81;
    color: #fff;
}
</style>

<div class="modal fade" id="modalProgSemana" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <!-- ── Header ── -->
            <div class="ps-header">
                <i class="fas fa-calendar-week fa-lg" style="color:#7dd3fc;"></i>
                <span class="ps-header-title">Programación Semanal de Mantenimiento</span>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
            </div>

            <!-- ── Barra de controles ── -->
            <div class="ps-toolbar">
                <!-- Selector de semana con navegación -->
                <div class="ps-semana-nav">
                    <button type="button" class="btn-nav" id="psBtnSemAnt" title="Semana anterior">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <select id="psSelectSemana">
                        <option value="">Cargando semanas...</option>
                    </select>
                    <button type="button" class="btn-nav" id="psBtnSemSig" title="Semana siguiente">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary ml-1" id="psBtnHoy" title="Ir a la semana actual">
                        <i class="fas fa-crosshairs mr-1"></i>Hoy
                    </button>
                </div>
                <span class="ps-rango-label" id="semanaRangoLabel">
                    <i class="fas fa-calendar-alt mr-1"></i>--
                </span>
                <!-- Acciones -->
                <div class="ps-toolbar-actions">
                    <?php if (tiene_permiso('mantenimientos', 'crear')): ?>
                    <button type="button" class="btn btn-sm btn-success" id="psBtnGenerarOTMasivo" disabled title="Generar una OT por cada ocurrencia seleccionada">
                        <i class="fas fa-magic mr-1"></i>Generar OT seleccionadas <span class="badge badge-light ml-1" id="psGenerateCount">0</span>
                    </button>
                    <?php endif; ?>
                    <?php if (tiene_permiso('mantenimientos', 'editar')): ?>
                    <button type="button" class="btn btn-sm btn-warning" id="psBtnReprogramar" disabled title="Mover ocurrencias seleccionadas">
                        <i class="fas fa-calendar-plus mr-1"></i>Reprogramar <span class="badge badge-dark ml-1" id="psSelectedCount">0</span>
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-success" id="psBtnExcel" title="Exportar a Excel">
                        <i class="fas fa-file-excel mr-1"></i>Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" id="psBtnImprimir" title="Imprimir">
                        <i class="fas fa-print mr-1"></i>Imprimir
                    </button>
                    <a href="programacion.php" class="btn btn-sm btn-outline-primary" title="Ir a programación completa">
                        <i class="fas fa-external-link-alt mr-1"></i>Ver todo
                    </a>
                </div>
            </div>

            <!-- ── Stats resumen ── -->
            <div class="ps-stats" id="psStatsBar">
                <span class="ps-stat-pill total"><i class="fas fa-list-ul"></i><span class="ps-count" id="psStTotal">0</span> Total</span>
                <span class="ps-stat-pill pend"><i class="fas fa-clock"></i><span class="ps-count" id="psStPend">0</span> Pendiente</span>
                <span class="ps-stat-pill ejec"><i class="fas fa-check-circle"></i><span class="ps-count" id="psStEjec">0</span> Ejecutado</span>
                <span class="ps-stat-pill venc"><i class="fas fa-exclamation-circle"></i><span class="ps-count" id="psStVenc">0</span> Vencido</span>
                <span class="ps-stat-pill fuera"><i class="fas fa-calendar-times"></i><span class="ps-count" id="psStFuera">0</span> Fuera de fecha</span>
            </div>

            <div class="ps-filters">
                <div class="ps-filter-field ps-filter-search">
                    <label for="psFiltroBuscar"><i class="fas fa-search mr-1"></i>Buscar</label>
                    <input type="search" id="psFiltroBuscar" class="form-control" placeholder="Plan, equipo, mantenimiento, estado u observacion">
                </div>
                <div class="ps-filter-field">
                    <label for="psFiltroEquipo"><i class="fas fa-tools mr-1"></i>Equipo</label>
                    <select id="psFiltroEquipo" class="form-control">
                        <option value="">Todos los equipos</option>
                    </select>
                </div>
                <div class="ps-filter-field">
                    <label for="psFiltroFrecuencia"><i class="fas fa-redo-alt mr-1"></i>Frecuencia</label>
                    <select id="psFiltroFrecuencia" class="form-control">
                        <option value="">Todas las frecuencias</option>
                    </select>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="psBtnLimpiarFiltros">
                    <i class="fas fa-eraser mr-1"></i>Limpiar
                </button>
                <span class="ps-filter-result" id="psFilterResult">Mostrando todos</span>
            </div>

            <!-- ── Cuerpo ── -->
            <div class="modal-body">
                <div id="spinnerProgSemana" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-3 text-muted mb-0">Cargando programación semanal...</p>
                </div>
                <div id="wrapProgSemana" style="display:none;">
                    <div id="psEmptyMsg" class="ps-empty" style="display:none;">
                        <i class="fas fa-calendar-times"></i>
                        <span id="psEmptyText">No hay actividades programadas para esta semana.</span>
                    </div>
                    <table id="tblProgSemana" class="table table-bordered table-hover table-sm w-100" style="display:none;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:34px;">
                                    <input type="checkbox" id="psCheckAll" title="Seleccionar ocurrencias disponibles">
                                </th>
                                <th>Plan / ocurrencia</th>
                                <th>Equipo</th>
                                <th class="text-center">Día / Fecha</th>
                                <th class="text-center">Modalidad</th>
                                <th class="text-center">Periodo</th>
                                <th class="text-center">Frecuencia</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">OT vinculada</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- ── Footer ── -->
            <div class="ps-footer">
                <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Se muestran ocurrencias cuya fecha programada cae dentro de la semana seleccionada.</small>
                <button type="button" class="btn btn-sm btn-secondary ml-auto" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalAsignarTecnicoOT" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content quick-ot-card">
            <div class="modal-header quick-ot-header">
                <div>
                    <div class="quick-ot-eyebrow">GESTION RAPIDA DE LA ORDEN</div>
                    <h5 class="modal-title font-weight-bold mb-0">
                        <i class="fas fa-user-clock mr-2"></i>Responsable y fechas de ejecuci&oacute;n
                        <span id="quickTechOtLabel" class="badge badge-light ml-2"></span>
                    </h5>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body quick-ot-body">
                <input type="hidden" id="quickTechDocEntry">
                <div class="quick-ot-equipment">
                    <span class="quick-ot-equipment-icon"><i class="fas fa-industry"></i></span>
                    <div><small>EQUIPO DE LA ORDEN</small><strong id="quickTechEquipo">-</strong></div>
                    <span class="quick-ot-open"><i class="fas fa-circle mr-1"></i>OT abierta</span>
                </div>

                <div class="quick-ot-section">
                    <div class="quick-ot-section-title"><span>1</span><div><strong>Responsable del trabajo</strong><small>Seleccione qui&eacute;n ejecutar&aacute; o liderar&aacute; la actividad.</small></div></div>
                    <div class="form-group mb-0">
                        <label for="quickTechSelect" class="font-weight-bold">T&eacute;cnico responsable <span class="text-danger">*</span></label>
                        <select id="quickTechSelect" class="form-control">
                            <option value="">Cargando t&eacute;cnicos...</option>
                        </select>
                    </div>
                </div>

                <div class="quick-ot-section">
                    <div class="quick-ot-section-title"><span>2</span><div><strong>Ventana de ejecuci&oacute;n</strong><small>Defina el periodo real del mantenimiento sin ingresar a editar la OT.</small></div></div>
                    <div class="quick-ot-dates">
                        <div class="quick-ot-date">
                            <div class="quick-ot-date-icon start"><i class="fas fa-play"></i></div>
                            <div class="flex-grow-1"><label for="quickTechFechaInicio">Fecha de inicio <span class="text-danger">*</span></label><input type="date" id="quickTechFechaInicio" class="form-control"><small>Inicio de la ejecuci&oacute;n.</small></div>
                        </div>
                        <div class="quick-ot-date">
                            <div class="quick-ot-date-icon end"><i class="fas fa-flag-checkered"></i></div>
                            <div class="flex-grow-1"><label for="quickTechFechaFin">Fecha de fin</label><input type="date" id="quickTechFechaFin" class="form-control"><small>Puede quedar vac&iacute;a mientras contin&uacute;e abierta.</small></div>
                        </div>
                    </div>
                    <div class="quick-ot-note"><i class="fas fa-shield-alt mr-2"></i>Se actualizar&aacute;n &uacute;nicamente el responsable y las fechas de esta OT en SAP.</div>
                </div>
            </div>
            <div class="modal-footer quick-ot-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info font-weight-bold px-4" id="btnGuardarTecnicoOT">
                    <i class="fas fa-save mr-1"></i>Guardar asignaci&oacute;n
                </button>
            </div>
        </div>
    </div>
</div>

<style>
#modalAsignarTecnicoOT .quick-ot-card{border:0;border-radius:17px;overflow:hidden;box-shadow:0 26px 75px rgba(20,49,67,.3)}
#modalAsignarTecnicoOT .quick-ot-header{padding:18px 22px;border:0;background:linear-gradient(115deg,#123c50 0%,#0b8d91 100%);color:#fff}
#modalAsignarTecnicoOT .quick-ot-eyebrow{margin-bottom:4px;color:#bce8e8;font-size:9px;font-weight:900;letter-spacing:1.4px}
#modalAsignarTecnicoOT .quick-ot-body{padding:20px;background:#f3f7f8}
#modalAsignarTecnicoOT .quick-ot-equipment{display:grid;grid-template-columns:46px minmax(0,1fr) auto;gap:12px;align-items:center;margin-bottom:14px;padding:13px 15px;border:1px solid #d8e6e8;border-radius:12px;background:#fff}
#modalAsignarTecnicoOT .quick-ot-equipment-icon{width:44px;height:44px;display:grid;place-items:center;border-radius:12px;background:#e2f4f3;color:#087f85;font-size:19px}
#modalAsignarTecnicoOT .quick-ot-equipment small,#modalAsignarTecnicoOT .quick-ot-equipment strong{display:block}
#modalAsignarTecnicoOT .quick-ot-equipment small{color:#789097;font-size:9px;font-weight:900;letter-spacing:.7px}
#modalAsignarTecnicoOT .quick-ot-equipment strong{margin-top:3px;color:#183b49;font-size:14px}
#modalAsignarTecnicoOT .quick-ot-open{padding:5px 9px;border-radius:999px;background:#dff5e9;color:#18744f;font-size:9px;font-weight:900;text-transform:uppercase}
#modalAsignarTecnicoOT .quick-ot-open i{font-size:6px}
#modalAsignarTecnicoOT .quick-ot-section{margin-top:12px;padding:16px;border:1px solid #dbe6e9;border-radius:13px;background:#fff}
#modalAsignarTecnicoOT .quick-ot-section-title{display:flex;gap:10px;align-items:center;margin-bottom:13px}
#modalAsignarTecnicoOT .quick-ot-section-title>span{width:28px;height:28px;display:grid;place-items:center;flex:0 0 28px;border-radius:9px;background:#123c50;color:#fff;font-weight:900}
#modalAsignarTecnicoOT .quick-ot-section-title strong,#modalAsignarTecnicoOT .quick-ot-section-title small{display:block}
#modalAsignarTecnicoOT .quick-ot-section-title strong{color:#173c49;font-size:13px}
#modalAsignarTecnicoOT .quick-ot-section-title small{color:#7a8d94;font-size:10px}
#modalAsignarTecnicoOT .quick-ot-dates{display:grid;grid-template-columns:1fr 1fr;gap:12px}
#modalAsignarTecnicoOT .quick-ot-date{display:flex;gap:10px;align-items:center;padding:12px;border:1px solid #dce6e9;border-radius:11px;background:#f9fbfc}
#modalAsignarTecnicoOT .quick-ot-date-icon{width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;border-radius:10px}
#modalAsignarTecnicoOT .quick-ot-date-icon.start{background:#dff5e9;color:#198754}
#modalAsignarTecnicoOT .quick-ot-date-icon.end{background:#fff0db;color:#d17a00}
#modalAsignarTecnicoOT .quick-ot-date label{display:block;margin:0 0 4px;color:#294753;font-size:10px;font-weight:900;text-transform:uppercase}
#modalAsignarTecnicoOT .quick-ot-date small{display:block;margin-top:4px;color:#7b8d94;font-size:9px}
#modalAsignarTecnicoOT .quick-ot-note{margin-top:12px;padding:9px 11px;border-radius:9px;background:#eaf5f6;color:#407078;font-size:10px}
#modalAsignarTecnicoOT .quick-ot-footer{border-top:1px solid #dde7e9;background:#fff}
@media(max-width:767px){#modalAsignarTecnicoOT .quick-ot-dates{grid-template-columns:1fr}#modalAsignarTecnicoOT .quick-ot-equipment{grid-template-columns:44px minmax(0,1fr)}#modalAsignarTecnicoOT .quick-ot-open{grid-column:2;width:max-content}}
#modalTareoOT .modal-content{border:0;border-radius:14px;overflow:hidden;box-shadow:0 24px 70px rgba(26,45,72,.28)}
#modalTareoOT .modal-header{padding:16px 20px;border:0;background:linear-gradient(135deg,#183b57 0%,#147d78 100%)}
#modalTareoOT .tareo-ot-context{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(190px,.7fr);gap:12px;margin-bottom:14px}
#modalTareoOT .tareo-ot-equipo{padding:13px 15px;border:1px solid #dce7ec;border-radius:10px;background:#f7fbfc}
#modalTareoOT .tareo-ot-equipo small{display:block;color:#708297;font-size:10px;font-weight:800;letter-spacing:.45px;text-transform:uppercase}
#modalTareoOT .tareo-ot-equipo strong{display:block;margin-top:3px;color:#20374c;font-size:14px}
#modalTareoOT .tareo-ot-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:14px}
#modalTareoOT .tareo-ot-stat{padding:12px;border:1px solid #e2e9ef;border-radius:10px;background:#fff;text-align:center}
#modalTareoOT .tareo-ot-stat strong{display:block;color:#147d78;font-size:21px;line-height:1.1}
#modalTareoOT .tareo-ot-stat small{color:#78889a;font-size:10px;font-weight:800;text-transform:uppercase}
#modalTareoOT .table thead th{border:0;background:#edf4f6;color:#435b70;font-size:10px;text-transform:uppercase;white-space:nowrap}
#modalTareoOT .table tbody td{font-size:11px;vertical-align:middle}
#modalTareoOT .tareo-hours{color:#087a70;font-weight:900}
@media(max-width:767px){#modalTareoOT .tareo-ot-context{grid-template-columns:1fr}#modalTareoOT .tareo-ot-stats{gap:5px}}
</style>

<!-- Tareo relacionado por equipo y periodo de la OT -->
<div class="modal fade" id="modalTareoOT" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header text-white">
                <div>
                    <h5 class="modal-title font-weight-bold mb-1">
                        <i class="fas fa-user-clock mr-2"></i>Tareo relacionado
                        <span id="tareoOtNumero" class="badge badge-light ml-2"></span>
                    </h5>
                    <small class="text-white-50">Cruce SAP por equipo y fecha de la orden de trabajo</small>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div id="tareoOtLoading" class="text-center py-5">
                    <i class="fas fa-circle-notch fa-spin fa-2x text-info"></i>
                    <p class="text-muted mt-2 mb-0">Consultando tareo en SAP...</p>
                </div>
                <div id="tareoOtContent" style="display:none;">
                    <div class="tareo-ot-context">
                        <div class="tareo-ot-equipo">
                            <small>Equipo relacionado</small>
                            <strong id="tareoOtEquipo">-</strong>
                        </div>
                        <div class="tareo-ot-equipo">
                            <small>Periodo consultado</small>
                            <strong id="tareoOtPeriodo">-</strong>
                        </div>
                    </div>
                    <div class="tareo-ot-stats">
                        <div class="tareo-ot-stat"><strong id="tareoOtHoras">0.00</strong><small>Horas</small></div>
                        <div class="tareo-ot-stat"><strong id="tareoOtTrabajadores">0</strong><small>Trabajadores</small></div>
                        <div class="tareo-ot-stat"><strong id="tareoOtRegistros">0</strong><small>Registros</small></div>
                    </div>
                    <div id="tareoOtNotice" class="alert alert-info py-2" style="display:none;"></div>
                    <div class="table-responsive border rounded">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Trabajador</th>
                                    <th>DNI</th>
                                    <th>OT / actividad</th>
                                    <th>Proceso</th>
                                    <th class="text-center">Col.</th>
                                    <th class="text-right">Horas</th>
                                </tr>
                            </thead>
                            <tbody id="tareoOtBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <small class="text-muted mr-auto"><i class="fas fa-link mr-1"></i>Fuente: @TAMA_TAREO_OT_CIF</small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle OT -->
<div class="modal fade" id="modalDetalleOT" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable ot-detail-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white font-weight-bold">
                    <i class="fas fa-wrench mr-2"></i>Detalle de Orden de Trabajo
                    <span id="det_docnum" class="badge badge-light ml-2"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-lg-4 mb-3 mb-lg-0">
                        <div class="ot-detail-card">
                            <div class="ot-detail-card-title"><i class="fas fa-cogs mr-1"></i> Equipo y responsable</div>
                            <div class="ot-detail-item"><i class="fas fa-industry"></i><div><small>Equipo</small><strong id="det_equipo"></strong></div></div>
                            <div class="ot-detail-item"><i class="fas fa-barcode"></i><div><small>N.° inventario</small><strong id="det_inventario"></strong></div></div>
                            <div class="ot-detail-item"><i class="fas fa-tools"></i><div><small>Tipo de mantenimiento</small><strong id="det_tipo"></strong></div></div>
                            <div class="ot-detail-item"><i class="fas fa-user-hard-hat"></i><div><small>Técnico responsable</small><strong id="det_tecnico"></strong></div></div>
                            <div class="ot-detail-item mb-0"><i class="fas fa-certificate"></i><div><small>Especialidad</small><strong id="det_esp"></strong></div></div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3 mb-lg-0">
                        <div class="ot-detail-card">
                            <div class="ot-detail-card-title"><i class="fas fa-calendar-alt mr-1"></i> Fechas y tiempos</div>
                            <div class="ot-detail-item"><i class="fas fa-play-circle"></i><div><small>Fecha de inicio</small><strong id="det_finicio"></strong></div></div>
                            <div class="ot-detail-item"><i class="fas fa-stop-circle"></i><div><small>Fecha de fin</small><strong id="det_ffin"></strong></div></div>
                            <div class="ot-detail-item"><i class="fas fa-clock"></i><div><small>Horario</small><strong><span id="det_hinicio"></span> - <span id="det_hfin"></span></strong></div></div>
                            <div class="ot-detail-item mb-0"><i class="fas fa-plus-circle"></i><div><small>Fecha de creación</small><strong id="det_created"></strong></div></div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="ot-detail-card">
                            <div class="ot-detail-card-title"><i class="fas fa-clipboard-check mr-1"></i> Seguimiento</div>
                            <div class="ot-detail-item"><i class="fas fa-signal"></i><div><small>Estado actual</small><strong id="det_estado"></strong></div></div>
                            <div class="ot-detail-item"><i class="fas fa-calendar-check"></i><div><small>Programación vinculada</small><strong id="det_prog"></strong></div></div>
                            <div class="ot-detail-item"><i class="fas fa-tags"></i><div><small>Modalidad del plan</small><strong id="det_modalidad"></strong></div></div>
                            <div class="alert alert-light border mb-0 mt-3 py-2 small"><i class="fas fa-info-circle text-info mr-1"></i>La OT conserva su historial y evidencias para trazabilidad.</div>
                        </div>
                    </div>
                </div>
                <div class="ot-title-strip">
                    <i class="fas fa-heading"></i>
                    <div><small>Título de la orden de trabajo</small><strong id="det_titulo">Sin título</strong></div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-7 mb-3 mb-lg-0"><div class="ot-detail-card"><div class="ot-detail-card-title"><i class="fas fa-wrench mr-1"></i> Trabajo realizado</div><div id="det_trabajo" class="ot-detail-text"></div></div></div>
                    <div class="col-lg-5"><div class="ot-detail-card"><div class="ot-detail-card-title"><i class="fas fa-comment-alt mr-1"></i> Observaciones</div><div id="det_observ" class="ot-detail-text"></div></div></div>
                </div>
                <div class="ot-cost-panel mb-3">
                    <div class="ot-cost-panel-head">
                        <div class="ot-cost-panel-title">
                            <i class="fas fa-boxes mr-1"></i> Repuestos y servicios
                            <small>Costos y documentos de compra asociados a esta OT.</small>
                        </div>
                        <button type="button" class="btn btn-info btn-sm font-weight-bold" id="btnGestionarCostosOT">
                            <i class="fas fa-receipt mr-1"></i><?php echo (tiene_permiso('mantenimientos', 'crear') || tiene_permiso('mantenimientos', 'editar')) ? 'Gestionar costos' : 'Ver costos'; ?>
                        </button>
                    </div>
                    <div id="det_cost_loading" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando costos...</div>
                    <div id="det_cost_content" style="display:none;">
                        <div class="ot-cost-stats">
                            <div class="ot-cost-stat"><i class="fas fa-list-ol"></i><div><small>Registros</small><strong id="det_cost_count">0</strong></div></div>
                            <div class="ot-cost-stat"><i class="fas fa-box-open"></i><div><small>Repuestos</small><strong id="det_cost_parts">0</strong></div></div>
                            <div class="ot-cost-stat"><i class="fas fa-user-cog"></i><div><small>Servicios</small><strong id="det_cost_services">0</strong></div></div>
                            <div class="ot-cost-stat"><i class="fas fa-coins"></i><div><small>Total registrado</small><strong><span id="det_cost_pen">S/ 0.00</span><br><span id="det_cost_usd">US$ 0.00</span></strong></div></div>
                        </div>
                        <div id="det_cost_preview" class="ot-cost-preview" style="display:none;"></div>
                        <div id="det_cost_empty" class="text-center text-muted py-3" style="display:none;"><i class="fas fa-receipt mr-1"></i>No se registraron repuestos ni servicios.</div>
                    </div>
                    <div id="det_cost_error" class="alert alert-warning mb-0" style="display:none;"></div>
                </div>
                <div class="ot-detail-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="ot-detail-card-title mb-0"><i class="fas fa-paperclip mr-1"></i> Evidencias y documentos</div>
                        <span class="badge badge-info badge-pill" id="det_evidence_count">0 fotos</span>
                    </div>
                    <div id="det_evidence_loading" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando evidencias...</div>
                    <div id="det_evidence_empty" class="text-center text-muted py-4" style="display:none;"><i class="far fa-images fa-2x d-block mb-2"></i>Esta OT todavía no tiene fotografías.</div>
                    <div id="det_evidence_error" class="alert alert-warning mb-0" style="display:none;"></div>
                    <div id="det_evidence_grid" class="ot-evidence-grid"></div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" id="btnCerrarOT" class="btn btn-success" data-docentry="">
                    <i class="fas fa-check-circle mr-1"></i>Cerrar OT
                </button>
                <button type="button" id="btnReabrirOT" class="btn btn-warning" data-docentry="" style="display:none;">
                    <i class="fas fa-redo mr-1"></i>Reabrir OT
                </button>
                <?php if (tiene_permiso('mantenimientos', 'editar')): ?>
                <button type="button" id="btnAnularOT" class="btn btn-outline-danger" data-docentry="">
                    <i class="fas fa-ban mr-1"></i>Anular OT
                </button>
                <a href="#" id="btnEditarOT" class="btn btn-info">
                    <i class="fas fa-edit mr-1"></i>Editar
                </a>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade ot-cost-modal" id="modalCostosOT" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white">
                <div>
                    <h5 class="modal-title font-weight-bold mb-1"><i class="fas fa-receipt mr-2"></i>Repuestos y servicios de la OT <span id="costModalOt" class="badge badge-light ml-1"></span></h5>
                    <small class="text-white-50">Registra cada costo con su codigo SAP y sustento PDF.</small>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (tiene_permiso('mantenimientos', 'crear') || tiene_permiso('mantenimientos', 'editar')): ?>
                <div class="ot-cost-form-card">
                    <div class="ot-cost-section-title"><span><i class="fas fa-plus-circle mr-1"></i><span id="costFormTitle">Nuevo registro</span></span><button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelarEdicionCosto" style="display:none;"><i class="fas fa-times mr-1"></i>Cancelar edicion</button></div>
                    <form id="formCostoOT" autocomplete="off">
                        <input type="hidden" id="costId" value="">
                        <div class="row">
                            <div class="col-lg-3 mb-3">
                                <label class="form-label d-block">Tipo de costo</label>
                                <div class="ot-cost-type-picker">
                                    <label class="ot-cost-type-option"><input type="radio" name="costTipo" value="REPUESTO" checked><span><i class="fas fa-box-open"></i>Repuesto</span></label>
                                    <label class="ot-cost-type-option"><input type="radio" name="costTipo" value="SERVICIO"><span><i class="fas fa-user-cog"></i>Servicio</span></label>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="costDescripcion">Descripcion <span class="text-danger">*</span></label>
                                <textarea id="costDescripcion" class="form-control" rows="3" maxlength="500" required placeholder="Describe el repuesto adquirido o el servicio realizado"></textarea>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="ot-cost-total-box h-100"><div><small>Importe calculado</small><span id="costFormula" class="small">1 x 0.00</span></div><strong id="costTotal">S/ 0.00</strong></div>
                            </div>
                            <div class="col-sm-4 col-lg-2 mb-3">
                                <label class="form-label" for="costCantidad">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" id="costCantidad" class="form-control" min="0.001" max="99999999999" step="0.001" value="1" required>
                            </div>
                            <div class="col-sm-4 col-lg-2 mb-3">
                                <label class="form-label" for="costPrecio">Precio unitario <span class="text-danger">*</span></label>
                                <input type="number" id="costPrecio" class="form-control" min="0" max="9999999999999" step="0.01" value="0.00" required>
                            </div>
                            <div class="col-sm-4 col-lg-2 mb-3">
                                <label class="form-label" for="costMoneda">Moneda <span class="text-danger">*</span></label>
                                <select id="costMoneda" class="form-control"><option value="PEN">Soles (S/)</option><option value="USD">Dolares (US$)</option></select>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label" for="costItemCode">Codigo del repuesto SAP</label>
                                <input type="text" id="costItemCode" class="form-control" maxlength="50" placeholder="Ej.: 120010200001">
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label" for="costCodigoSap">Codigo de compra SAP</label>
                                <input type="text" id="costCodigoSap" class="form-control" maxlength="50" placeholder="Ej.: OC-12345 o DocEntry">
                            </div>
                            <div class="col-lg-4 mb-3 mb-lg-0">
                                <label class="form-label" for="costProveedor">Proveedor</label>
                                <input type="text" id="costProveedor" class="form-control" maxlength="150" placeholder="Nombre del proveedor">
                            </div>
                            <div class="col-lg-5 mb-3 mb-lg-0">
                                <label class="form-label d-block">PDF de sustento <span class="text-danger" id="costPdfRequired">*</span></label>
                                <div class="ot-cost-pdf-box">
                                    <i class="fas fa-file-pdf"></i>
                                    <div class="min-width-0"><strong class="d-block text-dark small" id="costPdfPrompt">Seleccionar orden de compra o comprobante</strong><div class="ot-cost-pdf-name" id="costPdfName">PDF de hasta 25 MB</div></div>
                                    <input type="file" id="costPdf" accept="application/pdf,.pdf">
                                </div>
                            </div>
                            <div class="col-lg-3 d-flex align-items-end justify-content-end">
                                <button type="submit" class="btn btn-info btn-lg font-weight-bold w-100" id="btnGuardarCosto"><i class="fas fa-save mr-1"></i><span>Agregar registro</span></button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                <div class="ot-cost-list-card">
                    <div class="d-flex align-items-center justify-content-between px-3 py-3 border-bottom bg-white">
                        <div class="ot-cost-section-title mb-0"><span><i class="fas fa-list mr-1"></i>Costos registrados</span></div>
                        <span class="badge badge-info badge-pill px-3 py-2" id="costListCount">0 registros</span>
                    </div>
                    <div id="costListLoading" class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando...</div>
                    <div id="costListEmpty" class="text-center text-muted py-5" style="display:none;"><i class="fas fa-inbox fa-2x d-block mb-2"></i>Esta OT aun no tiene costos registrados.</div>
                    <div class="table-responsive" id="costListWrap" style="display:none;">
                        <table class="table table-hover ot-cost-table">
                            <thead><tr><th>Tipo</th><th>Descripcion</th><th>Cant.</th><th>P. unitario</th><th>Total</th><th>Compra SAP</th><th>PDF</th><th>Acciones</th></tr></thead>
                            <tbody id="costListBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><div class="mr-auto small text-muted"><i class="fas fa-shield-alt mr-1"></i>Cada cambio conserva usuario y fecha de actualizacion.</div><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVisorEvidenciaOT" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 text-white py-2">
                <div><strong id="visorEvidenceName">Evidencia</strong><small id="visorEvidenceNote" class="d-block text-white-50"></small></div>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center p-2 position-relative">
                <img id="visorEvidenceImage" class="ot-viewer-image" alt="Evidencia de mantenimiento">
                <button type="button" class="btn btn-light position-absolute" id="visorEvidencePrev" style="left:18px;top:50%;transform:translateY(-50%);"><i class="fas fa-chevron-left"></i></button>
                <button type="button" class="btn btn-light position-absolute" id="visorEvidenceNext" style="right:18px;top:50%;transform:translateY(-50%);"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="modal-footer border-0 py-2"><a id="visorEvidenceDownload" class="btn btn-outline-light" href="#"><i class="fas fa-download mr-1"></i>Descargar</a></div>
        </div>
    </div>
</div>

<?php if (false): // El alta y edicion de equipos se realiza en equipos.php. ?>
<div class="modal fade modal-equipo" id="modalNuevoEquipo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title mb-0" id="modalEquipoTitle"><i class="fas fa-plus-circle mr-2"></i>Agregar equipo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6 class="mb-1 font-weight-bold text-success">Gestion de equipo SAP</h6>
                    <small class="text-muted">El Code se calcula leyendo el ultimo registro de @TAMA_EQUIPOS_TM y sumando 1 al guardar.</small>
                </div>
                <div class="form-card">
                    <form id="formNuevoEquipo" autocomplete="off">
                        <input type="hidden" id="neMode" value="crear">
                        <input type="hidden" id="neOriginalCode" value="">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="font-weight-bold">Code SAP</label>
                                    <input type="text" class="form-control bg-light font-weight-bold" id="neCode" readonly>
                                    <small class="text-muted" id="neCodeHelp">--</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Codigo equipo / Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="neItemCode" maxlength="50" required placeholder="Ej: PANTO-01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Nombre equipo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="neItemName" maxlength="200" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Inventario</label>
                                    <input type="text" class="form-control" id="neInventryNo" maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Serie</label>
                                    <input type="text" class="form-control" id="neAssetSerNo" maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Marca</label>
                                    <input type="text" class="form-control" id="neMarca" maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Modelo</label>
                                    <input type="text" class="form-control" id="neModelo" maxlength="100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Tipo EMH</label>
                                    <input type="text" class="form-control" id="neTipEmh" maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Codigo tipo</label>
                                    <input type="text" class="form-control" id="neCodTipo" maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ubicacion</label>
                                    <input type="text" class="form-control" id="neUbicacion" maxlength="150">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select class="form-control" id="neEstado">
                                        <option value="OPERATIVO">OPERATIVO</option>
                                        <option value="ACTIVO">ACTIVO</option>
                                        <option value="MANTENIMIENTO">MANTENIMIENTO</option>
                                        <option value="OBSERVADO">OBSERVADO</option>
                                        <option value="DE BAJA">DE BAJA</option>
                                        <option value="INACTIVO">INACTIVO</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label>Fecha adquisicion</label>
                                    <input type="date" class="form-control" id="neFechaAdq">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label>Fecha baja</label>
                                    <input type="date" class="form-control" id="neFechaBaja">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label>Vida util</label>
                                    <input type="text" class="form-control" id="neVidaUtil" maxlength="40">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label>Criterio</label>
                                    <input type="text" class="form-control" id="neCriterio" maxlength="80">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-0">
                                    <label>Nivel criterio</label>
                                    <input type="text" class="form-control" id="neNivelCriterio" maxlength="80">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success font-weight-bold" id="btnGuardarEquipo">
                    <i class="fas fa-save mr-1"></i><span id="btnGuardarEquipoTxt">Guardar equipo</span>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$puedeEditar   = tiene_permiso('mantenimientos', 'editar')   ? 'true' : 'false';
$puedeCerrar   = tiene_permiso('mantenimientos', 'editar')   ? 'true' : 'false';
$puedeCrear    = tiene_permiso('mantenimientos', 'crear')    ? 'true' : 'false';
$baseUrl       = BASE_URL;

$inlineJs = <<<JS
var puedeEditar = {$puedeEditar};
var puedeCerrar = {$puedeCerrar};
var puedeCrear  = {$puedeCrear};
var allRows     = [];
var dtOT        = null;
var otFilterYear = String(new Date().getFullYear());
var otFilterWeek = '';
var otSeleccionadas = {};
var detalleEvidencias = [];
var detalleImagenes = [];
var detalleEvidenciaActual = 0;
var detalleOtDocentry = 0;
var detalleCostos = [];
var allProgRows     = [];
var psRowsVisibles  = [];
var dtProgSemana    = null;
var psSelectedCodes = {};
var psSearchTimer   = null;
var psRestorePage   = null;
var psRestoreScroll = null;
var psRefreshInPlace = false;

function attr(s) {
    return esc(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function badgeTipo(code) {
    var labels = {PREV:'Preventivo',CORR:'Correctivo',PRED:'Predictivo',EMERG:'Emergencia'};
    var label  = labels[code] || code || '—';
    return '<span class="badge badge-tipo-' + (code||'') + ' badge-pill px-2">' + label + '</span>';
}

function badgeEstado(s, canceled) {
    if (canceled === 'Y') return '<span class="badge badge-dark badge-pill">Anulada</span>';
    if (s === 'O') return '<span class="badge badge-estado-O badge-pill">Abierto</span>';
    if (s === 'C') return '<span class="badge badge-estado-C badge-pill">Cerrado</span>';
    return '<span class="badge badge-secondary">' + (s||'—') + '</span>';
}
function esc(s) {
    return s == null ? '' : String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function fmt(s) { return s ? s.substring(0, 10) : '—'; }
function fmtHoraSap(s) {
    var raw = String(s || '').trim();
    if (!raw) return '—';
    if (/^\d{1,2}:\d{2}$/.test(raw)) return raw;
    if (/^\d{3,4}$/.test(raw)) {
        raw = raw.padStart(4, '0');
        return raw.substring(0, 2) + ':' + raw.substring(2, 4);
    }
    return raw;
}
function rawDateOnly(s) {
    if (!s) return '';
    return String(s).split('T')[0].split(' ')[0];
}
function badgeProgEstado(s) {
    var map = {
        'Pendiente':'warning',
        'Ejecutado':'success',
        'Vencido':'danger',
        'Ejecutado fuera de fecha':'secondary'
    };
    var cls = map[s] || 'secondary';
    return '<span class="badge badge-' + cls + ' badge-pill">' + esc(s || '—') + '</span>';
}
function periodLabelProg(r) {
    if (r.U_TipoPeriodo === 'SEMANAL') return 'Sem ' + (r.U_Semana || r.U_PeriodoNum || '-');
    if (r.U_TipoPeriodo === 'QUINCENAL') return 'Qna ' + (r.U_PeriodoNum || '-');
    if (r.U_TipoPeriodo === 'MENSUAL') return 'Mes ' + (r.U_Mes || r.U_PeriodoNum || '-');
    if (r.U_TipoPeriodo === 'BIMENSUAL') return 'Bim ' + (r.U_PeriodoNum || '-');
    if (r.U_TipoPeriodo === 'TRIMESTRAL') return 'Trim ' + (r.U_PeriodoNum || '-');
    if (r.U_TipoPeriodo === 'SEMESTRAL') return 'Sem ' + (r.U_PeriodoNum || '-');
    if (r.U_TipoPeriodo === 'ANUAL') return 'Anual';
    return r.U_PeriodoNum ? 'Periodo ' + r.U_PeriodoNum : '-';
}
function otLabelProg(r) {
    if (r.U_OTDocNum) return 'OT-' + r.U_OTDocNum;
    if (r.U_OTDocEntry) return 'DocEntry ' + r.U_OTDocEntry;
    return '—';
}
function rangoSemanaActual() {
    var now = new Date();
    var day = now.getDay();
    var diffToMonday = day === 0 ? -6 : 1 - day;
    var start = new Date(now);
    start.setHours(0,0,0,0);
    start.setDate(now.getDate() + diffToMonday);
    var end = new Date(start);
    end.setDate(start.getDate() + 6);
    return { start: start, end: end };
}
function fmtDateObj(d) {
    var yyyy = d.getFullYear();
    var mm   = String(d.getMonth() + 1).padStart(2, '0');
    var dd   = String(d.getDate()).padStart(2, '0');
    return yyyy + '-' + mm + '-' + dd;
}
/** Formatea rango para mostrar, p.e.: "Lun 02/06 – Dom 08/06/2025" */
var DIAS_CORTOS = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
function fmtRangoSemana(start, end) {
    function fmtDia(d) {
        return DIAS_CORTOS[d.getDay()] + ' ' +
               String(d.getDate()).padStart(2,'0') + '/' +
               String(d.getMonth()+1).padStart(2,'0');
    }
    return fmtDia(start) + ' – ' + fmtDia(end) + '/' + end.getFullYear();
}
 function rangoSemanaOffset(offset) {
    if (psCalendarWeeks.length) {
        var index = psCurrentWeekIndex + offset;
        var week = psCalendarWeeks[index];
        if (week) {
            return {
                start: new Date(week.fecha_inicio + 'T00:00:00'),
                end: new Date(week.fecha_fin + 'T00:00:00'),
                semana: week.semana,
                anio: week.anio
            };
        }
    }
    var now = new Date();
    var day = now.getDay();
    var diffToMonday = day === 0 ? -6 : 1 - day;
    var start = new Date(now);
    start.setHours(0, 0, 0, 0);
    start.setDate(now.getDate() + diffToMonday + offset * 7);
    var end = new Date(start);
    end.setDate(start.getDate() + 6);
    return { start: start, end: end };
}
/* Número de semana ISO */
function isoWeek(d) {
    var date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    date.setUTCDate(date.getUTCDate() + 4 - (date.getUTCDay() || 7));
    var yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
    return Math.ceil((((date - yearStart) / 86400000) + 1) / 7);
}
 
/* ── Estado del selector ─────────────────────────────────── */
var psSemanaOffsetActual = 0;   /* 0 = semana actual */
var PS_OFFSET_MIN = -12;        /* 12 semanas atrás  */
var PS_OFFSET_MAX = 8;          /* 8 semanas adelante */
var psCalendarWeeks = [];
var psCurrentWeekIndex = 0;

function cargarCalendarioSemanas() {
    return fetch('$baseUrl/api/mantenimientos/get_semanas.php', { credentials: 'same-origin' })
        .then(function(response) {
            return response.json().then(function(data) { return { ok: response.ok, data: data }; });
        })
        .then(function(result) {
            if (!result.ok || result.data.error || !Array.isArray(result.data.weeks)) {
                throw new Error(result.data.error || 'No se pudo cargar dias_del_anio.');
            }
            psCalendarWeeks = result.data.weeks;
            psCurrentWeekIndex = parseInt(result.data.current_index, 10);
            PS_OFFSET_MIN = -psCurrentWeekIndex;
            PS_OFFSET_MAX = psCalendarWeeks.length - psCurrentWeekIndex - 1;
            generarOpcionesSemanas();
            actualizarNavegacion();
            if ($('#modalProgSemana').hasClass('show')) filtrarYRenderizarPS();
        })
        .catch(function(error) {
            console.error('Calendario de semanas:', error);
            generarOpcionesSemanas();
        });
}

function generarOpcionesSemanas() {
    var sel = document.getElementById('psSelectSemana');
    if (!sel) return;
    sel.innerHTML = '';
    for (var i = PS_OFFSET_MIN; i <= PS_OFFSET_MAX; i++) {
        var rango  = rangoSemanaOffset(i);
        var semNum = rango.semana || isoWeek(rango.start);
        var label  = 'Sem. ' + semNum + ' / ' + rango.start.getFullYear()
            + '  (' + fmtRangoSemana(rango.start, rango.end) + ')';
        if (i === 0) label = '★ Semana actual — ' + label.replace(/^Sem\. \d+\s+/, '');
        var totalSemana = totalProgramacionesSemana({
            fecha_inicio: fmtDateObj(rango.start),
            fecha_fin: fmtDateObj(rango.end)
        });
        if (Array.isArray(allProgRows) && totalSemana === 0) label += ' - VACIA';
        var opt   = document.createElement('option');
        opt.value = String(i);
        opt.text  = label;
        if (i === 0) opt.setAttribute('data-actual', '1');
        sel.appendChild(opt);
    }
    sel.value = String(psSemanaOffsetActual);
}

cargarCalendarioSemanas();
 

function cargarProgSemana() {
    if (!psRefreshInPlace) {
        document.getElementById('spinnerProgSemana').style.display = '';
        document.getElementById('wrapProgSemana').style.display    = 'none';
    }
 
    /* Si ya tenemos datos en caché, solo filtramos */
    if (allProgRows && allProgRows.length > 0) {
        document.getElementById('spinnerProgSemana').style.display = 'none';
        document.getElementById('wrapProgSemana').style.display    = '';
        filtrarYRenderizarPS();
        return;
    }
    
    fetch('$baseUrl/api/mantenimientos/get_prog.php?_ts=' + Date.now(), {
        credentials: 'same-origin',
        cache: 'no-store'
    })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) throw new Error(data.error);
            allProgRows = Array.isArray(data) ? data : [];
            generarOpcionesSemanas();
            document.getElementById('spinnerProgSemana').style.display = 'none';
            document.getElementById('wrapProgSemana').style.display    = '';
            filtrarYRenderizarPS();
            psRefreshInPlace = false;
        })
        .catch(function(err) {
            document.getElementById('spinnerProgSemana').style.display = 'none';
            psRefreshInPlace = false;
            Swal.fire('Error', err.message || 'No se pudo cargar la programación semanal.', 'error');
        });
}

function recargarProgSemanaConEstado() {
    psRestorePage = dtProgSemana ? dtProgSemana.page() : 0;
    var modalBody = document.querySelector('#modalProgSemana .modal-body');
    psRestoreScroll = modalBody ? modalBody.scrollTop : 0;
    psRefreshInPlace = true;
    allProgRows = [];
    cargarProgSemana();
}

function actualizarStatsPS(rows) {
    var total = rows.length, pend = 0, ejec = 0, venc = 0, fuera = 0;
    rows.forEach(function(r) {
        var s = (r.U_Estado || '').trim();
        if (s === 'Pendiente')               pend++;
        else if (s === 'Ejecutado')          ejec++;
        else if (s === 'Vencido')            venc++;
        else if (s === 'Ejecutado fuera de fecha') fuera++;
    });
    document.getElementById('psStTotal').textContent = total;
    document.getElementById('psStPend').textContent  = pend;
    document.getElementById('psStEjec').textContent  = ejec;
    document.getElementById('psStVenc').textContent  = venc;
    document.getElementById('psStFuera').textContent = fuera;
}

function diaLabel(fechaStr) {
    if (!fechaStr) return '—';
    var d     = new Date(fechaStr + 'T00:00:00');
    var hoy   = fmtDateObj(new Date());
    var isHoy = (rawDateOnly(fechaStr) === hoy);
    var dia   = DIAS_CORTOS[d.getDay()];
    var fecha = String(d.getDate()).padStart(2,'0') + '/' + String(d.getMonth()+1).padStart(2,'0');
    return '<span class="ps-dia-label' + (isHoy ? ' hoy' : '') + '">'
         + dia + ' ' + fecha
         + (isHoy ? ' <i class="fas fa-star fa-xs"></i>' : '')
         + '</span>';
}

function ocurrenciaReprogramable(row) {
    var estado = String(row.U_Estado || '').trim();
    return (puedeEditar || puedeCrear) && !row.U_OTDocEntry && !row.U_OTDocNum
        && (estado === 'Pendiente' || estado === 'Vencido');
}

function actualizarSeleccionPS() {
    var count = Object.keys(psSelectedCodes).length;
    var badge = document.getElementById('psSelectedCount');
    var button = document.getElementById('psBtnReprogramar');
    var generateBadge = document.getElementById('psGenerateCount');
    var generateButton = document.getElementById('psBtnGenerarOTMasivo');
    var checkAll = document.getElementById('psCheckAll');
    if (badge) badge.textContent = count;
    if (button) button.disabled = count === 0;
    if (generateBadge) generateBadge.textContent = count;
    if (generateButton) generateButton.disabled = count === 0;
    if (checkAll) {
        var available = (psRowsVisibles || []).filter(ocurrenciaReprogramable);
        var selected = available.filter(function(row) { return !!psSelectedCodes[String(row.Code)]; }).length;
        checkAll.checked = available.length > 0 && selected === available.length;
        checkAll.indeterminate = selected > 0 && selected < available.length;
    }
}

function limpiarSeleccionPS() {
    psSelectedCodes = {};
    actualizarSeleccionPS();
}

function actualizarOpcionesFiltrosPS(rows) {
    var equipoSelect = document.getElementById('psFiltroEquipo');
    var frecuenciaSelect = document.getElementById('psFiltroFrecuencia');
    if (!equipoSelect || !frecuenciaSelect) return;
    var equipoActual = equipoSelect.value;
    var frecuenciaActual = frecuenciaSelect.value;
    var equipos = {};
    var frecuencias = {};
    rows.forEach(function(row) {
        var equipo = String(row.U_ItemCode || '').trim();
        var nombre = String(row.ItemName || '').trim();
        var frecuencia = String(row.U_Frecuencia || '').trim();
        if (equipo) equipos[equipo] = equipo + (nombre && nombre !== equipo ? ' - ' + nombre : '');
        if (frecuencia) frecuencias[frecuencia] = frecuencia;
    });
    var equipoKeys = Object.keys(equipos).sort(function(a, b) { return equipos[a].localeCompare(equipos[b]); });
    var frecuenciaKeys = Object.keys(frecuencias).sort(function(a, b) { return a.localeCompare(b); });
    equipoSelect.innerHTML = '<option value="">Todos los equipos</option>' + equipoKeys.map(function(key) {
        return '<option value="' + attr(key) + '">' + esc(equipos[key]) + '</option>';
    }).join('');
    frecuenciaSelect.innerHTML = '<option value="">Todas las frecuencias</option>' + frecuenciaKeys.map(function(key) {
        return '<option value="' + attr(key) + '">' + esc(key) + '</option>';
    }).join('');
    equipoSelect.value = equipoKeys.indexOf(equipoActual) >= 0 ? equipoActual : '';
    frecuenciaSelect.value = frecuenciaKeys.indexOf(frecuenciaActual) >= 0 ? frecuenciaActual : '';
}

function normalizarBusquedaPS(value) {
    var text = String(value || '').toLowerCase();
    return text.normalize ? text.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : text;
}


function filtrarYRenderizarPS() {
    var rango  = rangoSemanaOffset(psSemanaOffsetActual);
    var inicio = fmtDateObj(rango.start);
    var fin    = fmtDateObj(rango.end);
 
    var baseRows = (allProgRows || []).filter(function(r) {
        var fecha = rawDateOnly(r.U_FechaProg);
        return fecha && fecha >= inicio && fecha <= fin
            && String(r.U_Estado || '').trim() !== 'Cancelado';
    }).sort(function(a, b) {
        return String(a.U_FechaProg || '').localeCompare(String(b.U_FechaProg || ''));
    });
    actualizarOpcionesFiltrosPS(baseRows);
    var buscarFiltro = normalizarBusquedaPS(document.getElementById('psFiltroBuscar').value.trim());
    var equipoFiltro = document.getElementById('psFiltroEquipo').value;
    var frecuenciaFiltro = document.getElementById('psFiltroFrecuencia').value;
    var rows = baseRows.filter(function(row) {
        var searchable = normalizarBusquedaPS([
            row.Code, row.PlanCode, row.Name, row.U_ItemCode, row.ItemName,
            row.U_TipoMant, row.TipoMantName, row.U_Frecuencia, row.U_Estado,
            row.U_Observacion, row.U_FechaProg
        ].join(' '));
        return (!buscarFiltro || searchable.indexOf(buscarFiltro) !== -1)
            && (!equipoFiltro || String(row.U_ItemCode || '') === equipoFiltro)
            && (!frecuenciaFiltro || String(row.U_Frecuencia || '') === frecuenciaFiltro);
    });
    psRowsVisibles = rows;
    Object.keys(psSelectedCodes).forEach(function(code) {
        if (!rows.some(function(row) { return String(row.Code) === code && ocurrenciaReprogramable(row); })) {
            delete psSelectedCodes[code];
        }
    });
 
    actualizarStatsPS(rows);
    var filterResult = document.getElementById('psFilterResult');
    if (filterResult) filterResult.textContent = 'Mostrando ' + rows.length + ' de ' + baseRows.length;
 
    var empty = document.getElementById('psEmptyMsg');
    var tabla = document.getElementById('tblProgSemana');
 
    if (rows.length === 0) {
        var emptyText = document.getElementById('psEmptyText');
        if (emptyText) emptyText.textContent = baseRows.length
            ? 'No hay actividades que coincidan con los filtros.'
            : 'No hay actividades programadas para esta semana.';
        if (empty) empty.style.display = '';
        if (tabla) tabla.style.display = 'none';
        if (dtProgSemana) { dtProgSemana.destroy(); dtProgSemana = null; }
        actualizarSeleccionPS();
        return;
    }
    if (empty) empty.style.display = 'none';
    if (tabla) tabla.style.display = '';
 
    if (dtProgSemana) { dtProgSemana.destroy(); dtProgSemana = null; }
 
    dtProgSemana = $('#tblProgSemana').DataTable({
        language: DATATABLES_SPANISH,
        responsive: true,
        data: rows,
        dom: 'rtip',
        columns: [
            { data: null, orderable: false, searchable: false, className: 'text-center', render: function(r) {
                if (r.U_OTDocEntry || r.U_OTDocNum) {
                    if (r.OTStatus === 'C') {
                        return '<i class="fas fa-lock text-danger" title="Bloqueada: la OT vinculada esta cerrada"></i>';
                    }
                    return '<i class="fas fa-link text-info" title="Vinculada a una OT abierta; no se puede generar otra OT ni reprogramar"></i>';
                }
                if (!ocurrenciaReprogramable(r)) {
                    return '<i class="fas fa-lock text-muted" title="La ocurrencia no esta disponible"></i>';
                }
                var code = String(r.Code || '');
                return '<input type="checkbox" class="ps-occ-check" data-code="' + attr(code) + '"'
                    + (psSelectedCodes[code] ? ' checked' : '') + ' aria-label="Seleccionar ocurrencia">';
            }},
            /* Plan */
            { data: null, render: function(r) {
                return '<strong class="text-primary">'
                     + esc(r.PlanCode || r.Code || '') + '</strong>'
                     + '<div class="text-muted small">' + esc(r.Name || '') + '</div>'
                     + '<div class="small mt-1"><i class="fas fa-calendar-check text-info mr-1"></i><b>Ocurrencia:</b> '
                     + esc(r.Code || '-') + '</div>';
            }},
            /* Equipo */
            { data: null, render: function(r) {
                return '<strong>' + esc(r.U_ItemCode || '') + '</strong>'
                     + '<div class="text-muted small">' + esc(r.ItemName || '') + '</div>';
            }},
            /* Día / Fecha */
            { data: 'U_FechaProg', className: 'text-center', render: function(d) {
                return diaLabel(d) + '<div class="text-muted small mt-1">' + (d ? d.substring(0,10) : '—') + '</div>';
            }},
            /* Modalidad del plan */
            { data: null, className: 'text-center', render: function(r) {
                var modalidad = String(r.U_Modalidad || '').trim().toUpperCase();
                if (modalidad === 'MR') return '<span class="badge badge-info px-2 py-1">MR</span>';
                if (modalidad === 'M') return '<span class="badge badge-warning px-2 py-1">M</span>';
                return '<span class="text-muted">—</span>';
            }},
            /* Periodo */
            { data: null, className: 'text-center', render: function(r) {
                return '<span class="badge badge-light border">' + esc(periodLabelProg(r)) + '</span>';
            }},
            /* Frecuencia */
            { data: 'U_Frecuencia', className: 'text-center', render: function(d) {
                return d ? '<span class="badge badge-secondary">' + esc(d) + '</span>' : '—';
            }},
            /* Estado */
            { data: 'U_Estado', className: 'text-center', render: function(d) {
                return badgeProgEstado(d);
            }},
            /* OT */
            { data: null, className: 'text-center', render: function(r) {
                var lbl = otLabelProg(r);
                return lbl !== '—'
                    ? '<span class="badge badge-info">' + esc(lbl) + '</span>'
                    : '<span class="text-muted">—</span>';
            }},
            /* Acción */
            { data: null, orderable: false, searchable: false, className: 'text-center', render: function(r) {
                if (r.U_OTDocEntry || r.U_OTDocNum) {
                    var isClosed = r.OTStatus === 'C';
                    var generated = isClosed
                        ? '<span class="badge badge-secondary"><i class="fas fa-lock mr-1"></i>OT cerrada</span>'
                        : '<span class="badge badge-info"><i class="fas fa-link mr-1"></i>OT abierta</span>';
                    if (!puedeEditar) return generated;
                    var title = isClosed ? 'La OT esta cerrada' : 'Asignar o cambiar responsable';
                    var disabled = isClosed ? ' disabled' : '';
                    return '<div class="d-inline-flex align-items-center">'
                        + generated
                        + '<button type="button" class="btn btn-xs btn-outline-info ml-1 btn-asignar-tecnico-ot"'
                        + ' data-code="' + attr(r.Code || '') + '"'
                        + ' title="' + attr(title) + '"' + disabled + '>'
                        + '<i class="fas fa-user-edit"></i></button>'
                        + '</div>'
                        + (r.OTTecnicoName
                            ? '<div class="small text-muted mt-1">' + esc(r.OTTecnicoName) + '</div>'
                            : '<div class="small text-warning mt-1">Sin responsable</div>');
                }
                if (!puedeCrear) {
                    return '<span class="text-muted">--</span>';
                }
                return '<button type="button" class="btn btn-sm btn-outline-success btn-generar-ot-prog"'
                    + ' data-code="' + attr(r.Code || '') + '"'
                    + ' data-plan="' + attr(r.PlanCode || '') + '"'
                    + ' title="Generar OT automática">'
                    + '<i class="fas fa-magic mr-1"></i>Generar OT'
                    + '</button>';
            }}
        ],
        order: [[3, 'asc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50]
    });
    if (psRestorePage !== null) {
        var pageInfo = dtProgSemana.page.info();
        var targetPage = Math.max(0, Math.min(psRestorePage, Math.max(0, pageInfo.pages - 1)));
        dtProgSemana.page(targetPage).draw('page');
        var scrollTo = psRestoreScroll;
        setTimeout(function() {
            var modalBody = document.querySelector('#modalProgSemana .modal-body');
            if (modalBody && scrollTo !== null) modalBody.scrollTop = scrollTo;
        }, 0);
        psRestorePage = null;
        psRestoreScroll = null;
    }
    actualizarSeleccionPS();
}

function cargaPendienteSemana(week) {
    return (allProgRows || []).filter(function(row) {
        var fecha = rawDateOnly(row.U_FechaProg);
        var estado = String(row.U_Estado || '').trim();
        return fecha >= week.fecha_inicio && fecha <= week.fecha_fin
            && (estado === 'Pendiente' || estado === 'Vencido');
    }).length;
}

function totalProgramacionesSemana(week) {
    if (!week) return 0;
    return (allProgRows || []).filter(function(row) {
        var fecha = rawDateOnly(row.U_FechaProg);
        return fecha >= week.fecha_inicio && fecha <= week.fecha_fin
            && String(row.U_Estado || '').trim() !== 'Cancelado';
    }).length;
}

function opcionesFechaDestino(week) {
    if (!week) return '';
    var current = new Date(week.fecha_inicio + 'T00:00:00');
    var end = new Date(week.fecha_fin + 'T00:00:00');
    var html = '';
    while (current <= end) {
        var value = fmtDateObj(current);
        html += '<option value="' + value + '">' + DIAS_CORTOS[current.getDay()] + ' '
            + String(current.getDate()).padStart(2, '0') + '/'
            + String(current.getMonth() + 1).padStart(2, '0') + '/' + current.getFullYear() + '</option>';
        current.setDate(current.getDate() + 1);
    }
    return html;
}

function psActualizarFechasDestino(index) {
    var select = document.getElementById('psFechaDestino');
    var load = document.getElementById('psCargaDestino');
    var week = psCalendarWeeks[parseInt(index, 10)];
    if (select) select.innerHTML = opcionesFechaDestino(week);
    if (load && week) {
        var total = totalProgramacionesSemana(week);
        var pending = cargaPendienteSemana(week);
        load.textContent = total === 0
            ? 'Semana vacia: no tiene ninguna programacion'
            : total + ' programaciones (' + pending + ' pendientes)';
        load.className = total === 0
            ? 'd-block text-success font-weight-bold mb-3'
            : 'd-block text-warning font-weight-bold mb-3';
    }
}

function abrirReprogramacionPS() {
    var codes = Object.keys(psSelectedCodes);
    if (!codes.length) return;
    if (!psCalendarWeeks.length) {
        Swal.fire('Calendario no disponible', 'No se pudo cargar dias_del_anio.', 'warning');
        return;
    }

    var source = rangoSemanaOffset(psSemanaOffsetActual);
    var sourceStart = fmtDateObj(source.start);
    var sourceEnd = fmtDateObj(source.end);
    var today = fmtDateObj(new Date());
    var candidates = [];
    psCalendarWeeks.forEach(function(week, index) {
        candidates.push({
            index: index,
            week: week,
            load: cargaPendienteSemana(week),
            total: totalProgramacionesSemana(week),
            isPast: week.fecha_fin < today,
            isSource: week.fecha_inicio === sourceStart && week.fecha_fin === sourceEnd
        });
    });
    var eligibleCandidates = candidates.filter(function(item) {
        return !item.isPast && !item.isSource;
    });
    if (!eligibleCandidates.length) {
        Swal.fire('Sin semanas disponibles', 'El calendario no tiene otra semana futura disponible.', 'warning');
        return;
    }
    var recommended = eligibleCandidates.slice().sort(function(a, b) {
        return a.total - b.total || a.load - b.load
            || String(a.week.fecha_inicio).localeCompare(String(b.week.fecha_inicio));
    })[0];
    var orderedCandidates = candidates.slice().sort(function(a, b) {
        return Number(a.week.anio) - Number(b.week.anio)
            || Number(a.week.semana) - Number(b.week.semana)
            || String(a.week.fecha_inicio).localeCompare(String(b.week.fecha_inicio));
    });
    function optionDestino(item) {
        var suffix = item.index === recommended.index ? ' - RECOMENDADA' : '';
        if (item.isSource) suffix = ' - SEMANA ACTUAL SELECCIONADA';
        var availability = item.isPast ? '[PASADA]' : (item.total === 0 ? '[VACIA]' : '[CON CARGA]');
        var disabled = item.isPast || item.isSource ? ' disabled' : '';
        return '<option value="' + item.index + '"' + disabled + '>' + availability + ' Semana ' + esc(item.week.semana)
            + ' / ' + esc(item.week.anio)
            + ' (' + esc(item.week.fecha_inicio) + ' a ' + esc(item.week.fecha_fin) + ') - '
            + (item.total === 0 ? 'VACIA' : item.total + ' programaciones / ' + item.load + ' pendientes')
            + suffix + '</option>';
    }
    var hasEmptyWeeks = eligibleCandidates.some(function(item) { return item.total === 0; });
    var options = (hasEmptyWeeks
        ? '<option value="" disabled>--- SEMANAS VACIAS ---</option>'
        : '<option value="" disabled>NO HAY SEMANAS VACIAS DISPONIBLES EN EL CALENDARIO</option>')
        + orderedCandidates.map(optionDestino).join('');
    var recommendedText = recommended.total === 0
        ? 'Semana vacia: no tiene ninguna programacion'
        : recommended.total + ' programaciones (' + recommended.load + ' pendientes)';

    Swal.fire({
        title: 'Reprogramar ' + codes.length + ' ocurrencia(s)',
        width: 680,
        html: '<div class="text-left">'
            + (hasEmptyWeeks
                ? '<div class="alert alert-success py-2"><i class="fas fa-calendar-check mr-1"></i>Las semanas estan ordenadas por calendario; las disponibles se identifican con [VACIA].</div>'
                : '<div class="alert alert-warning py-2"><i class="fas fa-exclamation-triangle mr-1"></i>No existen semanas vacias en el calendario disponible. Se muestra primero la semana con menor carga.</div>')
            + '<label class="font-weight-bold">Semana de destino</label>'
            + '<select id="psSemanaDestino" class="form-control mb-2" onchange="psActualizarFechasDestino(this.value)">' + options + '</select>'
            + '<small id="psCargaDestino" class="d-block text-success font-weight-bold mb-3">' + recommendedText + '</small>'
            + '<label class="font-weight-bold">Fecha exacta</label>'
            + '<select id="psFechaDestino" class="form-control mb-3">' + opcionesFechaDestino(recommended.week) + '</select>'
            + '<label class="font-weight-bold">Motivo de la reprogramacion</label>'
            + '<textarea id="psMotivoReprogramacion" class="form-control" maxlength="250" rows="3" placeholder="Ej.: Balancear la carga de mantenimiento de la semana" required></textarea>'
            + '<small class="text-muted">La frecuencia del plan maestro no sera modificada.</small>'
            + '</div>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-calendar-check mr-1"></i>Confirmar cambio',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        didOpen: function() {
            var weekSelect = document.getElementById('psSemanaDestino');
            if (weekSelect) {
                weekSelect.value = String(recommended.index);
                psActualizarFechasDestino(weekSelect.value);
            }
        },
        allowOutsideClick: function() { return !Swal.isLoading(); },
        preConfirm: function() {
            var fecha = document.getElementById('psFechaDestino').value;
            var motivo = document.getElementById('psMotivoReprogramacion').value.trim();
            if (!fecha || motivo.length < 4) {
                Swal.showValidationMessage('Seleccione la fecha e ingrese un motivo de al menos 4 caracteres.');
                return false;
            }
            return fetch('$baseUrl/api/mantenimientos/reprogramar_ocurrencias.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                credentials: 'same-origin',
                body: JSON.stringify({ codes: codes, fecha_nueva: fecha, motivo: motivo })
            }).then(function(response) {
                return response.json().then(function(json) {
                    if (!response.ok || !json.success) throw new Error(json.error || 'No se pudo reprogramar.');
                    return json;
                });
            }).catch(function(error) {
                Swal.showValidationMessage(error.message);
            });
        }
    }).then(function(result) {
        if (!result.isConfirmed || !result.value || !result.value.success) return;
        limpiarSeleccionPS();
        recargarProgSemanaConEstado();
        Swal.fire({
            icon: 'success',
            title: 'Ocurrencias reprogramadas',
            text: result.value.updated + ' ocurrencia(s) movidas a la semana ' + result.value.semana_nueva + '.',
            timer: 2200,
            showConfirmButton: false
        });
    });
}
 


function calcStats(rows) {
    var ab = 0, ce = 0, anuladas = 0, sinResponsable = 0;
    rows.forEach(function(r) {
        var anulada = r.Canceled === 'Y';
        if (anulada) anuladas++;
        else if (r.Status === 'O') ab++;
        else if (r.Status === 'C') ce++;
        if (!anulada && !String(r.U_Tecnico || '').trim() && !String(r.TecnicoName || '').trim()) {
            sinResponsable++;
        }
    });
    document.getElementById('statAbiertas').textContent = ab;
    document.getElementById('statCerradas').textContent = ce;
    document.getElementById('statAnuladas').textContent = anuladas;
    document.getElementById('statSinResponsable').textContent = sinResponsable;
}

function otPeriodDate(row) {
    return rawDateOnly((row && (row.U_FechaInicio || row.CreateDate)) || '');
}

function otPeriodKey(row) {
    var raw = otPeriodDate(row);
    if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) return '';
    var parts = raw.split('-');
    var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    if (isNaN(date.getTime())) return '';

    /* El anio ISO evita clasificar mal los dias cercanos a fin de anio. */
    var isoDate = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    isoDate.setUTCDate(isoDate.getUTCDate() + 4 - (isoDate.getUTCDay() || 7));
    var isoYear = isoDate.getUTCFullYear();
    return isoYear + '-W' + String(isoWeek(date)).padStart(2, '0');
}

function renderOtPeriodFilters(rows) {
    var years = {};
    years[String(new Date().getFullYear())] = true;
    (rows || []).forEach(function(row) {
        var key = otPeriodKey(row);
        if (key) years[key.substring(0, 4)] = true;
    });

    var yearOptions = Object.keys(years).sort(function(a, b){ return Number(b) - Number(a); })
        .map(function(year) {
            return '<option value="' + year + '">' + year + '</option>';
        }).join('');
    var weekOptions = '<option value="">Todas las semanas</option>';
    for (var week = 1; week <= 53; week++) {
        weekOptions += '<option value="' + week + '">Semana ' + week + '</option>';
    }

    var filter = ''
        + '<div class="ot-period-filter" title="Filtra por la fecha de inicio del mantenimiento">'
        + '  <span class="ot-filter-group-title"><i class="fas fa-sliders-h mr-1"></i>Filtros de periodo</span>'
        + '  <div class="ot-filter-field"><label for="otFilterYear">A&ntilde;o</label>'
        + '    <select id="otFilterYear" class="form-control form-control-sm">' + yearOptions + '</select></div>'
        + '  <div class="ot-filter-field"><label for="otFilterWeek">Semana de OT</label>'
        + '    <select id="otFilterWeek" class="form-control form-control-sm">' + weekOptions + '</select></div>'
        + '  <span class="ot-period-count" id="otPeriodCount">0 OT</span>'
        + '</div>';

    $('#tblOT_filter .ot-period-filter').remove();
    var searchLabel = $('#tblOT_filter > label').first();
    var searchInput = searchLabel.find('input').detach();
    searchInput.addClass('ot-search-input')
        .attr('placeholder', 'Buscar por OT, equipo o t\u00e9cnico...')
        .attr('aria-label', 'Buscar orden de trabajo');
    searchLabel.empty().addClass('ot-search-field')
        .append('<span class="ot-filter-label">B&uacute;squeda</span>')
        .append('<span class="ot-search-input-wrap"><i class="fas fa-search"></i></span>');
    searchLabel.find('.ot-search-input-wrap').append(searchInput);
    $('#tblOT_filter').append(filter);
    $('#tblOT_filter').appendTo('#otFiltersMount');
    $('#otFilterYear').val(otFilterYear);
    $('#otFilterWeek').val(otFilterWeek);
}

function actualizarResumenPeriodoOT() {
    if (!dtOT) return;
    var rows = dtOT.rows({ search: 'applied' }).data().toArray();
    calcStats(rows);
    $('#otPeriodCount').text(rows.length + ' OT');
}

function aplicarFiltroPeriodoOT(resetPage) {
    if (!dtOT) return;
    var pattern = '^' + otFilterYear + '-W';
    if (otFilterWeek) {
        pattern = '^' + otFilterYear + '-W' + String(otFilterWeek).padStart(2, '0') + '$';
    }
dtOT.column(10).search(pattern, true, false);
    if (resetPage !== false) dtOT.page('first');
    dtOT.draw(false);
}
function actualizarNavegacion() {
    var sel = document.getElementById('psSelectSemana');
    if (sel) sel.value = String(psSemanaOffsetActual);
 
    var rango = rangoSemanaOffset(psSemanaOffsetActual);
    var label = fmtRangoSemana(rango.start, rango.end);
    if (psSemanaOffsetActual === 0) label += '  ✦ Esta semana';
    document.getElementById('semanaRangoLabel').innerHTML =
        '<i class="fas fa-calendar-alt mr-1"></i>' + label;
 
    /* Deshabilitar botones en extremos */
    document.getElementById('psBtnSemAnt').disabled = (psSemanaOffsetActual <= PS_OFFSET_MIN);
    document.getElementById('psBtnSemSig').disabled = (psSemanaOffsetActual >= PS_OFFSET_MAX);
}


function cargarOT() {
    fetch('$baseUrl/api/mantenimientos/get_ot.php')
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.error) { console.error(data.error); return; }
            allRows = data;
            Object.keys(otSeleccionadas).forEach(function(de) {
                var vigente = data.some(function(r) {
                    return String(r.DocEntry) === de && r.Status === 'O' && r.Canceled !== 'Y';
                });
                if (!vigente) delete otSeleccionadas[de];
            });
            calcStats(data);

            document.getElementById('spinnerOT').style.display  = 'none';
            document.getElementById('wrapTabla').style.display  = '';

            if (dtOT) {
                $('#tblOT_filter').remove();
                dtOT.destroy();
            }

            dtOT = $('#tblOT').DataTable({
                language: DATATABLES_SPANISH,
                responsive: true,
                data: data,
                columns: [
                    { data: null, orderable: false, searchable: false, className: 'text-center',
                      render: function(r){
                        if (!puedeCerrar || r.Status !== 'O' || r.Canceled === 'Y') {
                            return '<i class="fas fa-lock text-muted" title="OT no disponible para cierre"></i>';
                        }
                        var de = String(r.DocEntry);
                        return '<input type="checkbox" class="check-cerrar-ot" data-de="' + attr(de) + '"'
                            + (otSeleccionadas[de] ? ' checked' : '') + ' aria-label="Seleccionar OT ' + attr(r.DocNum || de) + '">';
                      }},
                    { data: 'DocNum', className: 'text-center font-weight-bold',
                      render: function(d){ return d ? '#' + d : '—'; } },
                    { data: null, render: function(r){
                        return '<span class="font-weight-bold">' + esc(r.U_ItemCode) + '</span>'
                             + '<br><small class="text-muted">' + esc(r.ItemName) + '</small>';
                    }},
                    { data: 'U_TipoMant', className: 'text-center',
                      render: function(d){ return badgeTipo(d); } },
                    { data: null, className: 'text-center',
                      render: function(r, type){
                        var linked = String(r.U_ProgMant || '').trim() !== '' || String(r.U_ProgDet || '').trim() !== '';
                        var mode = linked ? String(r.U_Modalidad || '').trim().toUpperCase() : '';
                        if (type !== 'display') return mode;
                        if (mode === 'MR') return '<span class="ot-mode-badge mr" title="Modalidad MR">MR</span>';
                        if (mode === 'M') return '<span class="ot-mode-badge m" title="Modalidad M">M</span>';
                        return '';
                      } },
                    { data: null, render: function(r){
                        return esc(r.TecnicoName || r.U_Tecnico || '—');
                    }},
                    { data: 'U_FechaInicio', className: 'text-center',
                      render: function(d, type){
                        if (type !== 'display') return rawDateOnly(d);
                        return '<i class="far fa-calendar-alt text-muted mr-1"></i>' + fmt(d);
                      } },
                    { data: 'Status', className: 'text-center',
                      render: function(d, type, r){ return badgeEstado(d, r.Canceled); } },
                    { data: null, orderable: false, searchable: false, className: 'text-center',
                      render: function(r){
                        if (Number(r.TareoCount || 0) < 1) return '<span class="text-muted">-</span>';
                        return '<button type="button" class="btn btn-sm btn-outline-warning btn-tareo-ot"'
                            + ' data-de="' + attr(r.DocEntry) + '" title="Ver ' + attr(r.TareoCount) + ' registro(s) de tareo"'
                            + '><i class="fas fa-user-clock"></i> Ver</button>';
                      }},
                    { data: null, orderable: false, className: 'text-center',
                      render: function(r){
                        var buttons = '<div class="btn-group btn-group-sm" role="group">'
                            + '<button class="btn btn-primary btn-ver-ot" data-de="' + r.DocEntry + '" title="Ver detalle">'
                            + '<i class="fas fa-eye"></i></button>';
                        if (puedeEditar && r.Status === 'O' && r.Canceled !== 'Y') {
                            buttons += '<button class="btn btn-outline-info btn-asignar-tecnico-fila" data-de="' + r.DocEntry + '" title="Asignar técnico">'
                                + '<i class="fas fa-user-edit"></i></button>'
                                + '<button class="btn btn-outline-success btn-cerrar-ot-fila" data-de="' + r.DocEntry + '" title="Cerrar OT">'
                                + '<i class="fas fa-check"></i></button>'
                                + '<button class="btn btn-outline-danger btn-anular-ot"'
                                + ' data-de="' + r.DocEntry + '" data-num="' + attr(r.DocNum || r.DocEntry) + '" title="Anular OT">'
                                + '<i class="fas fa-ban"></i></button>';
                        }
                        return buttons + '</div>';
                      }},
                    { data: null, visible: false, searchable: true,
                      render: function(r){ return otPeriodKey(r); } }
                ],
                order: [[1,'desc']],
                dom: 'frtip',
                pageLength: 20
            });
            renderOtPeriodFilters(data);
            $('#tblOT').off('draw.dt.cierreMasivo draw.dt.otPeriod')
                .on('draw.dt.cierreMasivo', actualizarSeleccionCierreOT)
                .on('draw.dt.otPeriod', actualizarResumenPeriodoOT);
            aplicarFiltroPeriodoOT(false);
            actualizarSeleccionCierreOT();
        })
        .catch(function(e){ console.error(e); });
}

function bytesEvidencia(bytes) {
    var value = Number(bytes) || 0;
    if (value < 1024 * 1024) return Math.max(1, Math.round(value / 1024)) + ' KB';
    return (value / (1024 * 1024)).toFixed(1) + ' MB';
}

function abrirVisorEvidenciaOT(index) {
    if (!detalleImagenes.length) return;
    detalleEvidenciaActual = (index + detalleImagenes.length) % detalleImagenes.length;
    var row = detalleImagenes[detalleEvidenciaActual];
    document.getElementById('visorEvidenceImage').src = row.url;
    document.getElementById('visorEvidenceName').textContent = row.nombre_original || 'Evidencia';
    document.getElementById('visorEvidenceNote').textContent = row.descripcion || 'Sin nota';
    document.getElementById('visorEvidenceDownload').href = row.url + '&download=1';
    var multiple = detalleImagenes.length > 1;
    document.getElementById('visorEvidencePrev').style.display = multiple ? '' : 'none';
    document.getElementById('visorEvidenceNext').style.display = multiple ? '' : 'none';
    $('#modalVisorEvidenciaOT').modal('show');
}

function renderEvidenciasDetalle(rows) {
    detalleEvidencias = rows || [];
    var isDocument = function(row) {
        return /application\/(pdf|msword|vnd\.openxmlformats-officedocument\.wordprocessingml\.document)/i.test(row.mime_type || '');
    };
    detalleImagenes = detalleEvidencias.filter(function(row){ return !isDocument(row); });
    var grid = document.getElementById('det_evidence_grid');
    var count = document.getElementById('det_evidence_count');
    grid.innerHTML = '';
    var documentCount = detalleEvidencias.filter(isDocument).length;
    count.textContent = detalleImagenes.length + (detalleImagenes.length === 1 ? ' foto' : ' fotos')
        + (documentCount ? ' · ' + documentCount + (documentCount === 1 ? ' documento' : ' documentos') : '');
    document.getElementById('det_evidence_empty').style.display = detalleEvidencias.length ? 'none' : '';
    detalleEvidencias.forEach(function(row, index) {
        var isPdf = row.mime_type === 'application/pdf';
        var isWord = /application\/(msword|vnd\.openxmlformats-officedocument\.wordprocessingml\.document)/i.test(row.mime_type || '');
        var documentFile = isPdf || isWord;
        var card = document.createElement(documentFile ? 'a' : 'button');
        if (documentFile) {
            card.href = row.url + (isWord ? '&download=1' : '');
            card.target = '_blank';
            card.rel = 'noopener';
        } else {
            card.type = 'button';
        }
        card.className = 'ot-evidence-card p-0 text-left';
        card.title = (documentFile ? (isWord ? 'Descargar ' : 'Abrir ') : 'Ampliar ') + (row.nombre_original || 'evidencia');
        var preview = documentFile
            ? '<div class="ot-evidence-thumb d-flex align-items-center justify-content-center"><i class="fas ' + (isPdf ? 'fa-file-pdf text-danger' : 'fa-file-word text-primary') + ' fa-3x"></i></div>'
            : (/^image\/(jpeg|png|webp)$/i.test(row.mime_type || '')
            ? '<img class="ot-evidence-thumb" src="' + attr(row.url) + '" alt="">'
            : '<div class="ot-evidence-thumb d-flex align-items-center justify-content-center"><i class="fas fa-file-image fa-3x text-muted"></i></div>');
        card.innerHTML = preview
            + '<div class="ot-evidence-meta"><div class="ot-evidence-name">' + esc(row.nombre_original || 'Evidencia') + '</div>'
            + '<div class="text-muted">' + bytesEvidencia(row.tamano_bytes) + (row.usuario ? ' · ' + esc(row.usuario) : '') + '</div>'
            + '<div class="mt-1 text-truncate">' + esc(row.descripcion || 'Sin nota') + '</div></div>';
        if (!documentFile) {
            var imageIndex = detalleImagenes.findIndex(function(image){ return Number(image.id) === Number(row.id); });
            card.addEventListener('click', function(){ abrirVisorEvidenciaOT(imageIndex); });
        }
        grid.appendChild(card);
    });
}

function cargarEvidenciasDetalle(docentry) {
    document.getElementById('det_evidence_loading').style.display = '';
    document.getElementById('det_evidence_empty').style.display = 'none';
    document.getElementById('det_evidence_error').style.display = 'none';
    document.getElementById('det_evidence_grid').innerHTML = '';
    document.getElementById('det_evidence_count').textContent = '...';
    fetch('$baseUrl/api/mantenimientos/evidencias_ot.php?docentry=' + encodeURIComponent(docentry), {credentials:'same-origin'})
        .then(function(response) {
            return response.json().then(function(json) {
                if (!response.ok || !json.success) throw new Error(json.error || 'No se pudieron cargar las evidencias.');
                return json.evidencias || [];
            });
        })
        .then(renderEvidenciasDetalle)
        .catch(function(error) {
            detalleEvidencias = [];
            document.getElementById('det_evidence_count').textContent = '0 fotos';
            var alert = document.getElementById('det_evidence_error');
            alert.textContent = error.message;
            alert.style.display = '';
        })
        .then(function(){ document.getElementById('det_evidence_loading').style.display = 'none'; });
}

function costoDinero(value, currency) {
    var amount = Number(value) || 0;
    return new Intl.NumberFormat('es-PE', {
        style:'currency',
        currency:currency === 'USD' ? 'USD' : 'PEN',
        minimumFractionDigits:2,
        maximumFractionDigits:2
    }).format(amount);
}

function costoTipoBadge(tipo) {
    var value = String(tipo || '').toUpperCase();
    return '<span class="ot-cost-type ' + (value === 'SERVICIO' ? 'servicio' : 'repuesto') + '">'
        + (value === 'SERVICIO' ? 'Servicio' : 'Repuesto') + '</span>';
}

function renderResumenCostos(rows) {
    detalleCostos = rows || [];
    var parts = detalleCostos.filter(function(row){ return row.tipo === 'REPUESTO'; }).length;
    var services = detalleCostos.filter(function(row){ return row.tipo === 'SERVICIO'; }).length;
    var totals = detalleCostos.reduce(function(acc, row) {
        var currency = row.moneda === 'USD' ? 'USD' : 'PEN';
        acc[currency] += Number(row.total) || 0;
        return acc;
    }, {PEN:0, USD:0});

    document.getElementById('det_cost_count').textContent = detalleCostos.length;
    document.getElementById('det_cost_parts').textContent = parts;
    document.getElementById('det_cost_services').textContent = services;
    document.getElementById('det_cost_pen').textContent = costoDinero(totals.PEN, 'PEN');
    document.getElementById('det_cost_usd').textContent = costoDinero(totals.USD, 'USD');
    document.getElementById('det_cost_content').style.display = '';
    document.getElementById('det_cost_empty').style.display = detalleCostos.length ? 'none' : '';

    var preview = document.getElementById('det_cost_preview');
    preview.innerHTML = '';
    detalleCostos.slice(0, 4).forEach(function(row) {
        var item = document.createElement('div');
        item.className = 'ot-cost-preview-row';
        item.innerHTML = costoTipoBadge(row.tipo)
            + '<div><strong class="d-block text-dark">' + esc(row.descripcion) + '</strong><small class="text-muted">'
            + (row.proveedor ? esc(row.proveedor) : 'Proveedor no indicado') + '</small></div>'
            + '<div class="ot-cost-sap"><small class="text-muted d-block">Compra SAP</small><strong>' + esc(row.codigo_compra_sap || '—') + '</strong></div>'
            + '<div class="ot-cost-amount text-right"><strong>' + costoDinero(row.total, row.moneda) + '</strong></div>';
        preview.appendChild(item);
    });
    if (detalleCostos.length > 4) {
        var more = document.createElement('div');
        more.className = 'text-center text-muted small py-2';
        more.textContent = 'Hay ' + (detalleCostos.length - 4) + ' registro(s) adicional(es).';
        preview.appendChild(more);
    }
    preview.style.display = detalleCostos.length ? '' : 'none';
}

function renderListaCostos() {
    var body = document.getElementById('costListBody');
    body.innerHTML = '';
    document.getElementById('costListCount').textContent = detalleCostos.length
        + (detalleCostos.length === 1 ? ' registro' : ' registros');
    document.getElementById('costListEmpty').style.display = detalleCostos.length ? 'none' : '';
    document.getElementById('costListWrap').style.display = detalleCostos.length ? '' : 'none';

    detalleCostos.forEach(function(row) {
        var tr = document.createElement('tr');
        var actions = '<a class="btn btn-outline-danger btn-sm" href="' + attr(row.url_pdf)
            + '" target="_blank" rel="noopener" title="Abrir PDF"><i class="fas fa-file-pdf"></i></a>';
        if (puedeEditar) {
            actions += '<button type="button" class="btn btn-outline-info btn-sm btn-edit-cost" data-code="'
                + attr(row.code) + '" title="Editar"><i class="fas fa-edit"></i></button>'
                + '<button type="button" class="btn btn-outline-danger btn-sm btn-delete-cost" data-code="'
                + attr(row.code) + '" title="Anular"><i class="fas fa-ban"></i></button>';
        }
        tr.innerHTML = '<td>' + costoTipoBadge(row.tipo) + '</td>'
            + '<td class="ot-cost-desc"><strong>' + esc(row.descripcion) + '</strong>'
            + (row.item_code ? '<small class="d-block text-primary">Item SAP: ' + esc(row.item_code) + '</small>' : '')
            + (row.proveedor ? '<small class="d-block text-muted">' + esc(row.proveedor) + '</small>' : '') + '</td>'
            + '<td>' + Number(row.cantidad).toLocaleString('es-PE', {maximumFractionDigits:3}) + '</td>'
            + '<td>' + costoDinero(row.precio_unitario, row.moneda) + '</td>'
            + '<td><strong>' + costoDinero(row.total, row.moneda) + '</strong></td>'
            + '<td>' + esc(row.codigo_compra_sap || '—') + '</td>'
            + '<td><small class="d-block text-truncate" style="max-width:135px" title="' + attr(row.nombre_original || '') + '">' + esc(row.nombre_original || 'PDF') + '</small></td>'
            + '<td class="ot-cost-actions"><div class="btn-group">' + actions + '</div></td>';
        body.appendChild(tr);
    });
    document.getElementById('costListLoading').style.display = 'none';
}

function cargarCostosDetalle(docentry) {
    document.getElementById('det_cost_loading').style.display = '';
    document.getElementById('det_cost_content').style.display = 'none';
    document.getElementById('det_cost_error').style.display = 'none';
    if ($('#modalCostosOT').hasClass('show')) document.getElementById('costListLoading').style.display = '';

    return fetch('$baseUrl/api/mantenimientos/costos_ot.php?docentry=' + encodeURIComponent(docentry), {
        credentials:'same-origin', cache:'no-store'
    }).then(function(response) {
        return response.json().then(function(json) {
            if (!response.ok || !json.success) throw new Error(json.error || 'No se pudieron cargar los costos.');
            return json.costos || [];
        });
    }).then(function(rows) {
        renderResumenCostos(rows);
        renderListaCostos();
        return rows;
    }).catch(function(error) {
        detalleCostos = [];
        var alert = document.getElementById('det_cost_error');
        alert.textContent = error.message;
        alert.style.display = '';
        if ($('#modalCostosOT').hasClass('show')) {
            document.getElementById('costListLoading').style.display = 'none';
            document.getElementById('costListEmpty').style.display = '';
        }
        throw error;
    }).finally(function() {
        document.getElementById('det_cost_loading').style.display = 'none';
    });
}

function actualizarCostoCalculado() {
    var qty = Number(document.getElementById('costCantidad') ? document.getElementById('costCantidad').value : 0) || 0;
    var price = Number(document.getElementById('costPrecio') ? document.getElementById('costPrecio').value : 0) || 0;
    var currency = document.getElementById('costMoneda') ? document.getElementById('costMoneda').value : 'PEN';
    if (document.getElementById('costFormula')) document.getElementById('costFormula').textContent = qty.toLocaleString('es-PE', {maximumFractionDigits:3}) + ' x ' + price.toFixed(2);
    if (document.getElementById('costTotal')) document.getElementById('costTotal').textContent = costoDinero(qty * price, currency);
}

function actualizarTextoPdfCosto() {
    var input = document.getElementById('costPdf');
    if (!input) return;
    var file = input.files && input.files[0];
    document.getElementById('costPdfName').textContent = file ? file.name : (document.getElementById('costId').value !== '' ? 'Se conservara el PDF actual si no selecciona otro.' : 'PDF de hasta 25 MB');
}

function resetCostoForm() {
    var form = document.getElementById('formCostoOT');
    if (!form) return;
    form.reset();
    document.getElementById('costId').value = '';
    document.getElementById('costCantidad').value = '1';
    document.getElementById('costPrecio').value = '0.00';
    document.getElementById('costFormTitle').textContent = 'Nuevo registro';
    document.querySelector('#btnGuardarCosto span').textContent = 'Agregar registro';
    document.getElementById('btnCancelarEdicionCosto').style.display = 'none';
    document.getElementById('costPdfRequired').style.display = '';
    actualizarTextoPdfCosto();
    actualizarCostoCalculado();
}

function editarCosto(code) {
    var row = detalleCostos.find(function(item){ return String(item.code) === String(code); });
    if (!row || !document.getElementById('formCostoOT')) return;
    document.getElementById('costId').value = row.code;
    var typeInput = document.querySelector('input[name="costTipo"][value="' + row.tipo + '"]');
    if (typeInput) typeInput.checked = true;
    document.getElementById('costDescripcion').value = row.descripcion || '';
    document.getElementById('costCantidad').value = row.cantidad;
    document.getElementById('costPrecio').value = Number(row.precio_unitario).toFixed(2);
    document.getElementById('costMoneda').value = row.moneda || 'PEN';
    document.getElementById('costItemCode').value = row.item_code || '';
    document.getElementById('costCodigoSap').value = row.codigo_compra_sap || '';
    document.getElementById('costProveedor').value = row.proveedor || '';
    document.getElementById('costPdf').value = '';
    document.getElementById('costFormTitle').textContent = 'Editar ' + (row.tipo === 'SERVICIO' ? 'servicio' : 'repuesto');
    document.querySelector('#btnGuardarCosto span').textContent = 'Guardar cambios';
    document.getElementById('btnCancelarEdicionCosto').style.display = '';
    document.getElementById('costPdfRequired').style.display = 'none';
    actualizarTextoPdfCosto();
    actualizarCostoCalculado();
    document.getElementById('formCostoOT').scrollIntoView({behavior:'smooth', block:'center'});
}

function guardarCosto(event) {
    event.preventDefault();
    var form = document.getElementById('formCostoOT');
    if (!form.reportValidity()) return;
    var code = document.getElementById('costId').value.trim();
    var pdf = document.getElementById('costPdf').files[0];
    if (!code && !pdf) {
        Swal.fire('Falta el sustento', 'Adjunte la orden de compra o comprobante en PDF.', 'warning');
        return;
    }
    if (pdf && (pdf.size > 25 * 1024 * 1024 || !/\.pdf$/i.test(pdf.name))) {
        Swal.fire('PDF no valido', 'Seleccione un PDF de hasta 25 MB.', 'warning');
        return;
    }

    var data = new FormData();
    data.append('code', code);
    data.append('docentry', String(detalleOtDocentry));
    data.append('tipo', document.querySelector('input[name="costTipo"]:checked').value);
    data.append('descripcion', document.getElementById('costDescripcion').value.trim());
    data.append('cantidad', document.getElementById('costCantidad').value);
    data.append('precio_unitario', document.getElementById('costPrecio').value);
    data.append('moneda', document.getElementById('costMoneda').value);
    data.append('item_code', document.getElementById('costItemCode').value.trim());
    data.append('codigo_compra_sap', document.getElementById('costCodigoSap').value.trim());
    data.append('proveedor', document.getElementById('costProveedor').value.trim());
    if (pdf) data.append('pdf', pdf, pdf.name);

    var button = document.getElementById('btnGuardarCosto');
    var oldHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...';
    fetch('$baseUrl/api/mantenimientos/costos_ot.php', {method:'POST', credentials:'same-origin', body:data})
        .then(function(response) { return response.json().then(function(json){ return {ok:response.ok,json:json}; }); })
        .then(function(result) {
            if (!result.ok || !result.json.success) throw new Error(result.json.error || 'No se pudo guardar.');
            resetCostoForm();
            return cargarCostosDetalle(detalleOtDocentry).then(function() {
                App.toast(code ? 'Registro actualizado correctamente' : 'Costo registrado correctamente', 'success');
            });
        }).catch(function(error) {
            Swal.fire('No se pudo guardar', error.message, 'error');
        }).finally(function() {
            button.disabled = false;
            button.innerHTML = oldHtml;
            document.querySelector('#btnGuardarCosto span').textContent = document.getElementById('costId').value !== '' ? 'Guardar cambios' : 'Agregar registro';
        });
}

function eliminarCosto(code) {
    Swal.fire({
        icon:'warning', title:'Anular registro',
        text:'El registro se anulara en SAP. El PDF se conservara para trazabilidad.',
        showCancelButton:true, confirmButtonColor:'#dc3545', confirmButtonText:'Si, anular', cancelButtonText:'Cancelar'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        fetch('$baseUrl/api/mantenimientos/costos_ot.php', {
            method:'DELETE', credentials:'same-origin', headers:{'Content-Type':'application/json'}, body:JSON.stringify({code:String(code)})
        }).then(function(response) { return response.json().then(function(json){ return {ok:response.ok,json:json}; }); })
        .then(function(response) {
            if (!response.ok || !response.json.success) throw new Error(response.json.error || 'No se pudo eliminar.');
            resetCostoForm();
            return cargarCostosDetalle(detalleOtDocentry).then(function(){ App.toast('Registro anulado en SAP', 'success'); });
        }).catch(function(error){ Swal.fire('No se pudo anular', error.message, 'error'); });
    });
}

function mostrarDetalleOT(row, de) {
    detalleOtDocentry = Number(de) || 0;
    document.getElementById('det_docnum').textContent    = '#' + (row.DocNum || de);
    document.getElementById('det_equipo').textContent    = (row.U_ItemCode || '') + ' — ' + (row.ItemName || '');
    document.getElementById('det_inventario').textContent= row.InventryNo || '—';
    document.getElementById('det_tipo').innerHTML        = badgeTipo(row.U_TipoMant);
    document.getElementById('det_tecnico').textContent   = row.TecnicoName || row.U_Tecnico || '—';
    document.getElementById('det_esp').textContent       = row.TecnicoEsp || '—';
    document.getElementById('det_finicio').textContent   = fmt(row.U_FechaInicio);
    document.getElementById('det_ffin').textContent      = fmt(row.U_FechaFin);
    document.getElementById('det_hinicio').textContent   = fmtHoraSap(row.U_Hora_Ini);
    document.getElementById('det_hfin').textContent      = fmtHoraSap(row.U_Hora_Fin);
    document.getElementById('det_estado').innerHTML      = badgeEstado(row.Status, row.Canceled);
    document.getElementById('det_prog').textContent      = row.U_ProgMant || '—';
    var modalidad = String(row.U_Modalidad || '').trim().toUpperCase();
    document.getElementById('det_modalidad').innerHTML   = modalidad === 'MR'
        ? '<span class="badge badge-info px-2 py-1">MR</span>'
        : (modalidad === 'M' ? '<span class="badge badge-warning px-2 py-1">M</span>' : '—');
    document.getElementById('det_created').textContent   = fmt(row.CreateDate);
    document.getElementById('det_titulo').textContent    = row.U_TituFor || 'Sin título';
    document.getElementById('det_trabajo').textContent   = row.U_TrabajoRealizado || '—';
    document.getElementById('det_observ').textContent    = row.U_Observacion || '—';

    document.getElementById('btnCerrarOT').setAttribute('data-docentry', de);
    document.getElementById('btnReabrirOT').setAttribute('data-docentry', de);
    var btnAnular = document.getElementById('btnAnularOT');
    var btnEditar = document.getElementById('btnEditarOT');
    if (btnAnular) btnAnular.setAttribute('data-docentry', de);
    if (btnEditar) btnEditar.href = 'nueva_ot.php?docentry=' + de;

    var isAnulada = row.Canceled === 'Y';
    var isAbierto = (row.Status === 'O' && !isAnulada);
    document.getElementById('btnCerrarOT').style.display  = (puedeCerrar && isAbierto) ? '' : 'none';
    document.getElementById('btnReabrirOT').style.display = (puedeCerrar && !isAbierto && !isAnulada) ? '' : 'none';
    if (btnAnular) btnAnular.style.display = (puedeEditar && isAbierto) ? '' : 'none';
    if (btnEditar) btnEditar.style.display = isAnulada ? 'none' : '';

    $('#modalDetalleOT').modal('show');
    cargarEvidenciasDetalle(de);
    cargarCostosDetalle(de).catch(function(){});
}

$(document).on('click', '#btnGestionarCostosOT', function() {
    if (!detalleOtDocentry) return;
    document.getElementById('costModalOt').textContent = document.getElementById('det_docnum').textContent;
    resetCostoForm();
    renderListaCostos();
    $('#modalCostosOT').modal('show');
    setTimeout(function(){ $('.modal-backdrop').last().css('z-index', '1065'); }, 0);
    cargarCostosDetalle(detalleOtDocentry).catch(function(){});
});

$(document).on('hidden.bs.modal', '#modalCostosOT', function() {
    if ($('#modalDetalleOT').hasClass('show')) document.body.classList.add('modal-open');
});
$(document).on('input change', '#costCantidad,#costPrecio,#costMoneda', actualizarCostoCalculado);
$(document).on('change', '#costPdf', actualizarTextoPdfCosto);
$(document).on('submit', '#formCostoOT', guardarCosto);
$(document).on('click', '#btnCancelarEdicionCosto', resetCostoForm);
$(document).on('click', '.btn-edit-cost', function(){ editarCosto($(this).data('code')); });
$(document).on('click', '.btn-delete-cost', function(){ eliminarCosto($(this).data('code')); });

// Abrir modal de detalle
$(document).on('click', '.btn-ver-ot', function() {
    var de = $(this).data('de');
    var button = this;
    var oldHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    fetch('$baseUrl/api/mantenimientos/get_ot.php?docentry=' + encodeURIComponent(de), {credentials:'same-origin'})
        .then(function(response) {
            return response.json().then(function(json) {
                if (!response.ok || json.error) throw new Error(json.error || 'No se pudo cargar el detalle de la OT.');
                return Array.isArray(json) ? json[0] : null;
            });
        })
        .then(function(row) {
            if (!row) throw new Error('La OT seleccionada no existe.');
            mostrarDetalleOT(row, de);
        })
        .catch(function(error) {
            Swal.fire('No se pudo abrir', error.message, 'error');
        })
        .then(function() {
            button.disabled = false;
            button.innerHTML = oldHtml;
        });
});

function renderTareoOT(data) {
    var ot = data.ot || {};
    var summary = data.summary || {};
    var rows = Array.isArray(data.rows) ? data.rows : [];
    var inicio = rawDateOnly(ot.U_FechaInicio);
    var fin = rawDateOnly(ot.U_FechaFin) || inicio;
    document.getElementById('tareoOtNumero').textContent = '#' + (ot.DocNum || ot.DocEntry || 'OT');
    document.getElementById('tareoOtEquipo').textContent = (ot.U_ItemCode || '-') + (ot.ItemName ? ' - ' + ot.ItemName : '');
    document.getElementById('tareoOtPeriodo').textContent = inicio === fin ? fmt(inicio) : fmt(inicio) + ' al ' + fmt(fin);
    document.getElementById('tareoOtHoras').textContent = Number(summary.horas || 0).toFixed(2);
    document.getElementById('tareoOtTrabajadores').textContent = String(summary.trabajadores || 0);
    document.getElementById('tareoOtRegistros').textContent = String(summary.registros || 0);

    var notice = document.getElementById('tareoOtNotice');
    if (data.notice || !rows.length) {
        notice.textContent = data.notice || 'No se encontraron registros de tareo para este equipo dentro del periodo de la OT.';
        notice.style.display = '';
    } else {
        notice.style.display = 'none';
    }

    document.getElementById('tareoOtBody').innerHTML = rows.map(function(row) {
        var actividad = row.OtProduccion || row.ItemNo || '-';
        if (row.Descripcion) actividad += '<br><small class="text-muted">' + esc(row.Descripcion) + '</small>';
        var proceso = row.ProcesoTrabajador || row.Proceso || '-';
        return '<tr>'
            + '<td class="text-nowrap">' + esc(fmt(row.Fecha)) + '</td>'
            + '<td><strong>' + esc(row.Trabajador || '-') + '</strong></td>'
            + '<td>' + esc(row.Dni || '-') + '</td>'
            + '<td>' + esc(row.OtProduccion || row.ItemNo || '-')
            + (row.Descripcion ? '<br><small class="text-muted">' + esc(row.Descripcion) + '</small>' : '') + '</td>'
            + '<td>' + esc(proceso) + '</td>'
            + '<td class="text-center">' + esc(row.Columna || '-') + '</td>'
            + '<td class="text-right tareo-hours">' + Number(row.Horas || 0).toFixed(2) + '</td>'
            + '</tr>';
    }).join('');
}

$(document).on('click', '.btn-tareo-ot', function() {
    var de = $(this).data('de');
    document.getElementById('tareoOtNumero').textContent = '';
    document.getElementById('tareoOtLoading').style.display = '';
    document.getElementById('tareoOtContent').style.display = 'none';
    $('#modalTareoOT').modal('show');
    fetch('$baseUrl/api/mantenimientos/get_tareo_ot.php?docentry=' + encodeURIComponent(de), {credentials:'same-origin'})
        .then(function(response) {
            return response.json().then(function(json) {
                if (!response.ok || !json.success) throw new Error(json.error || 'No se pudo consultar el tareo.');
                return json;
            });
        })
        .then(function(data) {
            renderTareoOT(data);
            document.getElementById('tareoOtLoading').style.display = 'none';
            document.getElementById('tareoOtContent').style.display = '';
        })
        .catch(function(error) {
            $('#modalTareoOT').modal('hide');
            Swal.fire('No se pudo abrir el tareo', error.message, 'error');
        });
});

$(document).on('click', '#visorEvidencePrev', function(){ abrirVisorEvidenciaOT(detalleEvidenciaActual - 1); });
$(document).on('click', '#visorEvidenceNext', function(){ abrirVisorEvidenciaOT(detalleEvidenciaActual + 1); });

// Cerrar / Reabrir OT
function cambiarEstadoOT(de, accion) {
    Swal.fire({
        title: accion === 'cerrar' ? '¿Cerrar esta OT?' : '¿Reabrir esta OT?',
        text:  accion === 'cerrar' ? 'Se marcará como completada.' : 'Se volverá a estado Abierto.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then(function(res) {
        if (!res.isConfirmed) return;
        fetch('$baseUrl/api/mantenimientos/guardar_ot.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ docentry: de, accion: accion })
        })
        .then(function(r){ return r.json(); })
        .then(function(json) {
            if (json.success) {
                App.toast(accion === 'cerrar' ? 'OT cerrada correctamente' : 'OT reabierta correctamente', 'success');
                $('#modalDetalleOT').modal('hide');
                cargarOT();
            } else {
                Swal.fire('Error', json.error || 'No se pudo actualizar', 'error');
            }
        });
    });
}

function actualizarSeleccionCierreOT() {
    var cantidad = Object.keys(otSeleccionadas).length;
    var contador = document.getElementById('countOTSeleccionadas');
    var boton = document.getElementById('btnCerrarOTMasivo');
    var checkTodas = document.getElementById('checkTodasOT');
    if (contador) contador.textContent = cantidad;
    if (boton) boton.disabled = cantidad === 0;
    if (checkTodas) {
        var visibles = $('.check-cerrar-ot:visible');
        var marcadas = visibles.filter(':checked');
        checkTodas.checked = visibles.length > 0 && marcadas.length === visibles.length;
        checkTodas.indeterminate = marcadas.length > 0 && marcadas.length < visibles.length;
    }
}

function cerrarOTRequest(docentry) {
    return fetch('$baseUrl/api/mantenimientos/guardar_ot.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({docentry: docentry, accion: 'cerrar'})
    }).then(function(response) {
        return response.json().then(function(json) {
            if (!response.ok || !json.success) throw new Error(json.error || 'No se pudo cerrar la OT.');
            return json;
        });
    });
}

function cerrarOTMasivo() {
    var docentries = Object.keys(otSeleccionadas);
    if (!docentries.length) return;
    Swal.fire({
        title: 'Cerrar ' + docentries.length + ' OT seleccionadas?',
        text: 'Se procesaran una por una y sus ocurrencias quedaran ejecutadas.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Si, cerrar todas',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        var boton = document.getElementById('btnCerrarOTMasivo');
        var original = boton.innerHTML;
        var cerradas = 0;
        var fallidas = [];
        boton.disabled = true;
        boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Procesando 0/' + docentries.length;

        var cadena = Promise.resolve();
        docentries.forEach(function(de, indice) {
            cadena = cadena.then(function() {
                boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Procesando ' + (indice + 1) + '/' + docentries.length;
                return cerrarOTRequest(parseInt(de, 10))
                    .then(function() {
                        cerradas++;
                        delete otSeleccionadas[de];
                    })
                    .catch(function(error) {
                        fallidas.push('#' + de + ': ' + error.message);
                    });
            });
        });
        cadena.then(function() {
            boton.innerHTML = original;
            actualizarSeleccionCierreOT();
            cargarOT();
            if (!fallidas.length) {
                Swal.fire('Cierre completado', cerradas + ' OT fueron cerradas correctamente.', 'success');
                return;
            }
            Swal.fire({
                title: 'Cierre parcialmente completado',
                html: '<p><b>' + cerradas + '</b> cerradas y <b>' + fallidas.length + '</b> con error.</p>'
                    + '<div class="text-left small" style="max-height:180px;overflow:auto">' + fallidas.map(esc).join('<br>') + '</div>',
                icon: 'warning'
            });
        });
    });
}

function anularOT(de, numero) {
    if (!de) return;
    Swal.fire({
        title: 'Anular OT #' + esc(numero || de) + '?',
        html: '<div class="text-left">'
            + '<p>La OT se conservara para trazabilidad y su ocurrencia quedara libre para generar una nueva.</p>'
            + '<label class="font-weight-bold">Motivo de anulacion</label>'
            + '<textarea id="motivoAnularOT" class="form-control" maxlength="180" rows="3"'
            + ' placeholder="Explique por que se anula la orden"></textarea></div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, anular OT',
        cancelButtonText: 'Volver',
        confirmButtonColor: '#dc3545',
        showLoaderOnConfirm: true,
        allowOutsideClick: function() { return !Swal.isLoading(); },
        preConfirm: function() {
            var motivo = document.getElementById('motivoAnularOT').value.trim();
            if (motivo.length < 4) {
                Swal.showValidationMessage('Ingrese un motivo de al menos 4 caracteres.');
                return false;
            }
            return fetch('$baseUrl/api/mantenimientos/guardar_ot.php', {
                method:'POST',
                credentials:'same-origin',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify({docentry:de, accion:'anular', motivo:motivo})
            }).then(function(response) {
                return response.json().then(function(json) {
                    if (!response.ok || !json.success) throw new Error(json.error || 'No se pudo anular la OT.');
                    return json;
                });
            }).catch(function(error) {
                Swal.showValidationMessage(error.message);
            });
        }
    }).then(function(result) {
        if (!result.isConfirmed || !result.value || !result.value.success) return;
        $('#modalDetalleOT').modal('hide');
        cargarOT();
        recargarProgSemanaConEstado();
        Swal.fire({
            icon:'success',
            title:'OT anulada',
            text:'La OT se conservo y la ocurrencia fue liberada.',
            timer:2000,
            showConfirmButton:false
        });
    });
}
/* ── Exportar a Excel (SheetJS si disponible, sino CSV) ───── */
function exportarPSExcel() {
    var rango = rangoSemanaOffset(psSemanaOffsetActual);
    var inicio = fmtDateObj(rango.start);
    var fin    = fmtDateObj(rango.end);
    var rows = psRowsVisibles || [];
 
    if (!rows.length) {
        Swal.fire('Sin datos', 'No hay registros para exportar en esta semana.', 'info');
        return;
    }
 
    var semana = rango.semana || isoWeek(rango.start);
    var codigos = rows.map(function(row) { return String(row.Code || '').trim(); }).filter(Boolean);
    var url = '$baseUrl/api/mantenimientos/exportar_semana_xlsx.php?inicio=' + encodeURIComponent(inicio)
        + '&fin=' + encodeURIComponent(fin)
        + '&semana=' + encodeURIComponent(semana)
        + '&codigos=' + encodeURIComponent(codigos.join(','));
    window.location.href = url;
}

function filaPSExport(r) {
    return [
        r.PlanCode || r.Code || '',
        r.Code || '',
        r.Name || '',
        r.U_ItemCode || '',
        r.ItemName || '',
        rawDateOnly(r.U_FechaProg) || '',
        String(r.U_Modalidad || '').trim().toUpperCase(),
        periodLabelProg(r),
        r.U_Frecuencia || '',
        r.U_Estado || '',
        otLabelProg(r)
    ];
}

function imprimirPS() {
    var rango = rangoSemanaOffset(psSemanaOffsetActual);
    var inicio = fmtDateObj(rango.start);
    var fin = fmtDateObj(rango.end);
    var rows = psRowsVisibles || [];

    if (!rows.length) {
        Swal.fire('Sin datos', 'No hay registros para imprimir en esta semana.', 'info');
        return;
    }

    var headers = ['Plan','Ocurrencia','Nombre plan','Equipo','Nombre equipo','Fecha','Modalidad','Periodo','Frecuencia','Estado','OT'];
    var w = window.open('', '_blank');
    if (!w) {
        Swal.fire('Ventana bloqueada', 'Permite ventanas emergentes para imprimir la programacion.', 'warning');
        return;
    }

    w.document.open();
    w.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Programacion semanal</title></head><body></body></html>');
    w.document.close();

    var style = w.document.createElement('style');
    style.textContent = 'body{font-family:Arial,sans-serif;font-size:12px;color:#1f2933;margin:24px;}'
        + 'h1{font-size:18px;margin:0 0 4px;}p{margin:0 0 14px;color:#4b5563;}'
        + 'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #cbd5e1;padding:6px;vertical-align:top;}'
        + 'th{background:#eef4fb;text-align:left;font-size:11px;text-transform:uppercase;}'
        + '@media print{body{margin:12mm;}}';
    w.document.head.appendChild(style);

    var title = w.document.createElement('h1');
    title.textContent = 'Programacion semanal de mantenimiento';
    w.document.body.appendChild(title);

    var range = w.document.createElement('p');
    range.textContent = 'Del ' + inicio + ' al ' + fin;
    w.document.body.appendChild(range);

    var table = w.document.createElement('table');
    var thead = w.document.createElement('thead');
    var headTr = w.document.createElement('tr');
    headers.forEach(function(h) {
        var th = w.document.createElement('th');
        th.textContent = h;
        headTr.appendChild(th);
    });
    thead.appendChild(headTr);
    table.appendChild(thead);

    var tbody = w.document.createElement('tbody');
    rows.forEach(function(r) {
        var tr = w.document.createElement('tr');
        filaPSExport(r).forEach(function(v) {
            var td = w.document.createElement('td');
            td.textContent = v == null ? '' : String(v);
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    w.document.body.appendChild(table);

    setTimeout(function() {
        w.focus();
        w.print();
    }, 250);
}

function generarOTDesdeProg(code) {
    var row = (psRowsVisibles || allProgRows || []).find(function(r) {
        return String(r.Code || '') === String(code || '');
    });
    if (!row) {
        Swal.fire('No encontrado', 'No se encontró la programación seleccionada.', 'warning');
        return;
    }
    if (row.U_OTDocEntry || row.U_OTDocNum) {
        Swal.fire('OT ya generada', 'Esta programación ya tiene una OT vinculada.', 'info');
        return;
    }

    var payload = {
        docentry: 0,
        accion: 'guardar',
        itemcode: row.U_ItemCode || '',
        tipomant: row.U_TipoMant || '',
        tecnico: '',
        progmant: row.PlanCode || row.U_ProgMant || '',
        progdet: row.Code || '',
        fechainicio: rawDateOnly(row.U_FechaProg),
        fechafin: '',
        horainicio: '',
        horafin: '',
        trabajo: '',
        observacion: ''
    };

    if (!payload.itemcode || !payload.tipomant || !payload.fechainicio || !payload.progdet) {
        Swal.fire('Datos incompletos', 'La programación no tiene equipo, tipo, fecha o código de detalle suficiente para generar la OT.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Generar OT automática?',
        html: '<b>Plan:</b> ' + esc(payload.progmant)
            + '<br><b>Ocurrencia:</b> ' + esc(payload.progdet)
            + '<br><b>Equipo:</b> ' + esc(payload.itemcode)
            + '<br><b>Fecha inicio:</b> ' + esc(payload.fechainicio)
            + '<br><b>Tipo:</b> ' + esc(payload.tipomant),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar'
    }).then(function(res) {
        if (!res.isConfirmed) return;

        var btn = $('.btn-generar-ot-prog').filter(function() {
            return String($(this).data('code')) === String(code);
        });
        var oldHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Generando');

        fetch('$baseUrl/api/mantenimientos/guardar_ot.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        })
        .then(function(r){
            return r.json().then(function(json) {
                json._status = r.status;
                return json;
            });
        })
        .then(function(json) {
            if (!json.success) {
                if (json._status === 409 && json.duplicate) {
                    recargarProgSemanaConEstado();
                    cargarOT();
                    Swal.fire(
                        'OT ya generada',
                        'La ocurrencia ya esta vinculada a la OT #' + (json.docnum || json.docentry || ''),
                        'info'
                    );
                    return;
                }
                Swal.fire('No se pudo generar', json.error || 'Error al crear la OT.', 'error');
                btn.prop('disabled', false).html(oldHtml);
                return;
            }
            Swal.fire({
                icon: 'success',
                title: 'OT generada',
                text: 'OT #' + (json.docnum || json.docentry) + ' creada correctamente.',
                timer: 1800,
                showConfirmButton: false
            });
            recargarProgSemanaConEstado();
            cargarOT();
        })
        .catch(function(err) {
            btn.prop('disabled', false).html(oldHtml);
            Swal.fire('Error conexión', err.message, 'error');
        });
    });
}

function payloadOTProgramada(row) {
    return {
        docentry: 0,
        accion: 'guardar',
        itemcode: row.U_ItemCode || '',
        tipomant: row.U_TipoMant || '',
        tecnico: '',
        progmant: row.PlanCode || row.U_ProgMant || '',
        progdet: row.Code || '',
        fechainicio: rawDateOnly(row.U_FechaProg),
        fechafin: '',
        horainicio: '',
        horafin: '',
        trabajo: '',
        observacion: ''
    };
}

function generarOTSeleccionadas() {
    var codes = Object.keys(psSelectedCodes);
    var rows = codes.map(function(code) {
        return (allProgRows || []).find(function(row) { return String(row.Code || '') === code; });
    }).filter(function(row) { return row && !row.U_OTDocEntry && !row.U_OTDocNum; });

    if (!rows.length) {
        Swal.fire('Sin ocurrencias', 'Selecciona al menos una ocurrencia pendiente sin OT.', 'info');
        return;
    }

    var invalid = rows.filter(function(row) {
        var payload = payloadOTProgramada(row);
        return !payload.itemcode || !payload.tipomant || !payload.fechainicio || !payload.progdet;
    });
    if (invalid.length) {
        Swal.fire('Datos incompletos', invalid.length + ' ocurrencia(s) no tienen equipo, tipo, fecha o codigo de detalle.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Generar ' + rows.length + ' ordenes de trabajo?',
        html: 'Se creara <b>una OT por cada ocurrencia seleccionada</b>.<br>Las ocurrencias que ya tengan OT no se duplicaran.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Si, generar todas',
        cancelButtonText: 'Cancelar'
    }).then(function(confirm) {
        if (!confirm.isConfirmed) return;
        var results = {created: [], duplicate: [], failed: []};
        Swal.fire({title:'Generando ordenes',html:'Procesando <b id="psBulkCurrent">0</b> de <b>' + rows.length + '</b>...',allowOutsideClick:false,allowEscapeKey:false,showConfirmButton:false,didOpen:function(){Swal.showLoading();}});

        var chain = Promise.resolve();
        rows.forEach(function(row, index) {
            chain = chain.then(function() {
                var counter = document.getElementById('psBulkCurrent');
                if (counter) counter.textContent = index + 1;
                return fetch('$baseUrl/api/mantenimientos/guardar_ot.php', {
                    method:'POST',
                    credentials:'same-origin',
                    headers:{'Content-Type':'application/json'},
                    body:JSON.stringify(payloadOTProgramada(row))
                }).then(function(response) {
                    return response.json().then(function(json) {
                        if (response.ok && json.success) results.created.push({code:row.Code,ot:json.docnum || json.docentry});
                        else if (response.status === 409 && json.duplicate) results.duplicate.push({code:row.Code,ot:json.docnum || json.docentry});
                        else results.failed.push({code:row.Code,error:json.error || 'Error al crear la OT'});
                    });
                }).catch(function(error) {
                    results.failed.push({code:row.Code,error:error.message || 'Error de conexion'});
                });
            });
        });

        chain.then(function() {
            limpiarSeleccionPS();
            recargarProgSemanaConEstado();
            cargarOT();
            var icon = results.failed.length ? 'warning' : 'success';
            var html = '<div class="text-left">'
                + '<div><b>Creadas:</b> ' + results.created.length + '</div>'
                + '<div><b>Ya existentes:</b> ' + results.duplicate.length + '</div>'
                + '<div><b>No generadas:</b> ' + results.failed.length + '</div>';
            if (results.failed.length) html += '<hr><small>' + results.failed.map(function(item){return esc(item.code) + ': ' + esc(item.error);}).join('<br>') + '</small>';
            html += '</div>';
            Swal.fire({icon:icon,title:'Generacion finalizada',html:html,confirmButtonText:'Aceptar'});
        });
    });
}

function mostrarAsignacionTecnicoOT(docentry, otLabel, equipo, tecnicoActual) {
    document.getElementById('quickTechDocEntry').value = docentry;
    document.getElementById('quickTechOtLabel').textContent = otLabel;
    document.getElementById('quickTechEquipo').textContent = equipo;
    var select = document.getElementById('quickTechSelect');
    var fechaInicio = document.getElementById('quickTechFechaInicio');
    var fechaFin = document.getElementById('quickTechFechaFin');
    select.innerHTML = '<option value="">Cargando tecnicos...</option>';
    fechaInicio.value = '';
    fechaFin.value = '';
    fechaInicio.disabled = true;
    fechaFin.disabled = true;
    document.getElementById('btnGuardarTecnicoOT').disabled = true;
    $('#modalAsignarTecnicoOT').modal('show');

    var techniciansRequest = fetch('$baseUrl/api/mantenimientos/get_tecnicos.php', {credentials:'same-origin'})
        .then(function(response) {
            return response.json().then(function(json) {
                if (!response.ok || json.error) throw new Error(json.error || 'No se pudieron cargar los tecnicos.');
                return json;
            });
        });
    var orderRequest = fetch('$baseUrl/api/mantenimientos/get_ot.php?docentry=' + encodeURIComponent(docentry), {credentials:'same-origin'})
        .then(function(response) {
            return response.json().then(function(json) {
                if (!response.ok || json.error) throw new Error(json.error || 'No se pudieron cargar las fechas de la OT.');
                if (!Array.isArray(json) || !json.length) throw new Error('La orden de trabajo ya no esta disponible.');
                return json[0];
            });
        });

    Promise.all([techniciansRequest, orderRequest])
        .then(function(results) {
            var rows = results[0];
            var order = results[1];
            if (order.Status === 'C' || order.Canceled === 'Y') throw new Error('La orden de trabajo esta cerrada o anulada.');
            select.innerHTML = '<option value="">-- Seleccionar tecnico --</option>'
                + rows.map(function(tech) {
                    return '<option value="' + attr(tech.Code) + '">'
                        + esc(tech.Name) + (tech.U_Especialidad ? ' - ' + esc(tech.U_Especialidad) : '')
                        + '</option>';
                }).join('');
            select.value = order.U_Tecnico || tecnicoActual || '';
            fechaInicio.value = String(order.U_FechaInicio || '').substring(0, 10);
            fechaFin.value = String(order.U_FechaFin || '').substring(0, 10);
            fechaInicio.disabled = false;
            fechaFin.disabled = false;
            fechaFin.min = fechaInicio.value || '';
            document.getElementById('btnGuardarTecnicoOT').disabled = false;
        })
        .catch(function(error) {
            select.innerHTML = '<option value="">No disponible</option>';
            fechaInicio.disabled = true;
            fechaFin.disabled = true;
            Swal.fire('No se pudo cargar', error.message, 'error');
        });
}

function abrirAsignacionTecnicoOT(code) {
    var row = (psRowsVisibles || allProgRows || []).find(function(r) {
        return String(r.Code || '') === String(code || '');
    });
    if (!row || !row.U_OTDocEntry) {
        Swal.fire('OT no encontrada', 'La ocurrencia no tiene una OT vinculada valida.', 'warning');
        return;
    }
    if (row.OTStatus === 'C') {
        Swal.fire('OT cerrada', 'No se puede cambiar el responsable de una OT cerrada.', 'info');
        return;
    }

    mostrarAsignacionTecnicoOT(
        row.U_OTDocEntry,
        otLabelProg(row),
        (row.U_ItemCode || '') + ' - ' + (row.ItemName || ''),
        row.OTTecnico || ''
    );
}

function abrirAsignacionTecnicoFila(docentry) {
    var row = allRows.find(function(r){ return String(r.DocEntry) === String(docentry); });
    if (!row || row.Status !== 'O' || row.Canceled === 'Y') return;
    mostrarAsignacionTecnicoOT(
        row.DocEntry,
        '#' + (row.DocNum || row.DocEntry),
        (row.U_ItemCode || '') + ' - ' + (row.ItemName || ''),
        row.U_Tecnico || ''
    );
}

function guardarTecnicoOT() {
    var docentry = parseInt(document.getElementById('quickTechDocEntry').value, 10) || 0;
    var tecnico = document.getElementById('quickTechSelect').value;
    var fechaInicio = document.getElementById('quickTechFechaInicio').value;
    var fechaFin = document.getElementById('quickTechFechaFin').value;
    if (!docentry || !tecnico || !fechaInicio) {
        Swal.fire('Datos requeridos', 'Seleccione el tecnico responsable e indique la fecha de inicio.', 'warning');
        return;
    }
    if (fechaFin && fechaFin < fechaInicio) {
        Swal.fire('Rango no valido', 'La fecha de fin no puede ser anterior a la fecha de inicio.', 'warning');
        return;
    }

    var button = document.getElementById('btnGuardarTecnicoOT');
    var oldHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Guardando';
    fetch('$baseUrl/api/mantenimientos/asignar_tecnico_ot.php', {
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({docentry:docentry, tecnico:tecnico, fecha_inicio:fechaInicio, fecha_fin:fechaFin})
    })
    .then(function(response) {
        return response.json().then(function(json) {
            if (!response.ok || !json.success) throw new Error(json.error || 'No se pudo asignar el responsable.');
            return json;
        });
    })
    .then(function() {
        $('#modalAsignarTecnicoOT').modal('hide');
        recargarProgSemanaConEstado();
        cargarOT();
        Swal.fire({icon:'success', title:'Asignacion actualizada', text:'El responsable y las fechas se guardaron en SAP.', timer:1900, showConfirmButton:false});
    })
    .catch(function(error) {
        Swal.fire('No se pudo guardar', error.message, 'error');
    })
    .then(function() {
        button.disabled = false;
        button.innerHTML = oldHtml;
    });
}

$(document).on('change', '#quickTechFechaInicio', function() {
    var end = document.getElementById('quickTechFechaFin');
    end.min = this.value || '';
    if (end.value && this.value && end.value < this.value) end.value = '';
});


$(document).on('click', '#btnCerrarOT',  function(){ cambiarEstadoOT($(this).data('docentry'), 'cerrar');  });
$(document).on('click', '#btnReabrirOT', function(){ cambiarEstadoOT($(this).data('docentry'), 'reabrir'); });
$(document).on('click', '#btnAnularOT', function(){
    var de = $(this).data('docentry');
    var row = allRows.find(function(r){ return r.DocEntry == de; });
    anularOT(de, row ? row.DocNum : de);
});
$(document).on('click', '.btn-anular-ot', function(){
    anularOT($(this).data('de'), $(this).data('num'));
});
$(document).on('click', '.btn-asignar-tecnico-fila', function(){
    abrirAsignacionTecnicoFila($(this).data('de'));
});
$(document).on('click', '.btn-cerrar-ot-fila', function(){
    cambiarEstadoOT($(this).data('de'), 'cerrar');
});
$(document).on('change', '#otFilterYear', function(){
    otFilterYear = String($(this).val() || new Date().getFullYear());
    aplicarFiltroPeriodoOT(true);
});
$(document).on('change', '#otFilterWeek', function(){
    otFilterWeek = String($(this).val() || '');
    aplicarFiltroPeriodoOT(true);
});
$(document).on('change', '.check-cerrar-ot', function(){
    var de = String($(this).data('de'));
    if (this.checked) otSeleccionadas[de] = true;
    else delete otSeleccionadas[de];
    actualizarSeleccionCierreOT();
});
$(document).on('change', '#checkTodasOT', function(){
    var marcado = this.checked;
    $('.check-cerrar-ot:visible').each(function() {
        var de = String($(this).data('de'));
        this.checked = marcado;
        if (marcado) otSeleccionadas[de] = true;
        else delete otSeleccionadas[de];
    });
    actualizarSeleccionCierreOT();
});
$(document).on('click', '#btnCerrarOTMasivo', cerrarOTMasivo);

/* ── Listeners del modal ─────────────────────────────────── */
$(document).on('click', '#btnVerProgSemana', function() {
    /* Inicializar selector si no tiene opciones reales */
    var sel = document.getElementById('psSelectSemana');
    if (!sel || sel.options.length <= 1) generarOpcionesSemanas();
    psSemanaOffsetActual = 0;
    limpiarSeleccionPS();
    document.getElementById('psFiltroBuscar').value = '';
    document.getElementById('psFiltroEquipo').value = '';
    document.getElementById('psFiltroFrecuencia').value = '';
    actualizarNavegacion();
    allProgRows = [];    /* forzar recarga al abrir */
    $('#modalProgSemana').modal('show');
    cargarProgSemana();
});

$('#modalProgSemana').on('shown.bs.modal', function() {
    if (!document.getElementById('psSelectSemana').options.length ||
        document.getElementById('psSelectSemana').options[0].value === '') {
        generarOpcionesSemanas();
    }
    actualizarNavegacion();
    /* Bootstrap "enforceFocus" pelea con SweetAlert2 (que se inyecta fuera del modal),
       robando el foco del textarea/inputs del Swal y evitando que se pueda escribir.
       Se desactiva mientras este modal este abierto. */
    $(document).off('focusin.bs.modal');
});
 

/* Selector dropdown */
$(document).on('change', '#psSelectSemana', function() {
    psSemanaOffsetActual = parseInt(this.value, 10) || 0;
    limpiarSeleccionPS();
    actualizarNavegacion();
    filtrarYRenderizarPS();
});

/* Navegación flechas */
$(document).on('click', '#psBtnSemAnt', function() {
    if (psSemanaOffsetActual <= PS_OFFSET_MIN) return;
    psSemanaOffsetActual--;
    limpiarSeleccionPS();
    actualizarNavegacion();
    filtrarYRenderizarPS();
});
$(document).on('click', '#psBtnSemSig', function() {
    if (psSemanaOffsetActual >= PS_OFFSET_MAX) return;
    psSemanaOffsetActual++;
    limpiarSeleccionPS();
    actualizarNavegacion();
    filtrarYRenderizarPS();
});
 
/* Botón Hoy */
$(document).on('click', '#psBtnHoy', function() {
    psSemanaOffsetActual = 0;
    limpiarSeleccionPS();
    actualizarNavegacion();
    filtrarYRenderizarPS();
});
 
/* Excel */
$(document).on('click', '#psBtnExcel', function() {
    exportarPSExcel();
});
 
/* Imprimir */
$(document).on('click', '#psBtnImprimir', function() {
    imprimirPS();
});

$(document).on('change', '#psFiltroEquipo, #psFiltroFrecuencia', function() {
    filtrarYRenderizarPS();
});

$(document).on('input', '#psFiltroBuscar', function() {
    clearTimeout(psSearchTimer);
    psSearchTimer = setTimeout(filtrarYRenderizarPS, 180);
});

$(document).on('click', '#psBtnLimpiarFiltros', function() {
    document.getElementById('psFiltroBuscar').value = '';
    document.getElementById('psFiltroEquipo').value = '';
    document.getElementById('psFiltroFrecuencia').value = '';
    filtrarYRenderizarPS();
});

$(document).on('change', '.ps-occ-check', function() {
    var code = String($(this).data('code') || '');
    if (!code) return;
    if (this.checked) psSelectedCodes[code] = true;
    else delete psSelectedCodes[code];
    actualizarSeleccionPS();
});

$(document).on('change', '#psCheckAll', function() {
    var checked = this.checked;
    (psRowsVisibles || []).filter(ocurrenciaReprogramable).forEach(function(row) {
        var code = String(row.Code || '');
        if (checked) psSelectedCodes[code] = true;
        else delete psSelectedCodes[code];
    });
    $('.ps-occ-check').prop('checked', checked);
    actualizarSeleccionPS();
});

$(document).on('click', '#psBtnReprogramar', function() {
    abrirReprogramacionPS();
});

$(document).on('click', '#psBtnGenerarOTMasivo', function() {
    generarOTSeleccionadas();
});

$(document).on('click', '.btn-generar-ot-prog', function() {
    generarOTDesdeProg($(this).data('code'));
});
$(document).on('click', '.btn-asignar-tecnico-ot', function() {
    if (this.disabled) return;
    abrirAsignacionTecnicoOT($(this).data('code'));
});
$(document).on('click', '#btnGuardarTecnicoOT', function() {
    guardarTecnicoOT();
});

$(document).ready(function() {
    cargarOT();
});
JS;

include_once(__DIR__ . '/../../layouts/footer.php');
?>
