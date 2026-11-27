<?php
$page_title = 'Programar Semana';
require_once('includes/load.php');
page_require_level(2);
// Obtener la fecha actual en formato YYYY-MM-DD
$id = date('Y-m-d');
$all_problema = find_all_EXT($id);
$activo1 = remove_junk($all_problema['semana']);

 $all_semana_rows = find_by_sql("SELECT YEAR(fecha) AS anio, semana FROM dias_del_anio WHERE YEAR(fecha) IN (".date('Y').", ".(date('Y')-1).") GROUP BY YEAR(fecha), semana ORDER BY YEAR(fecha), semana");
 // Construir un mapa año -> [semanas]
 $all_semana = [];
 if ($all_semana_rows && is_array($all_semana_rows)) {
     foreach ($all_semana_rows as $r) {
         $y = $r['anio'];
         if (!isset($all_semana[$y])) $all_semana[$y] = [];
         if (!in_array($r['semana'], $all_semana[$y])) $all_semana[$y][] = $r['semana'];
     }
 }
 $all_semana_json = json_encode($all_semana);
 $all_anio = find_allq('dias_del_anio');
 $all_anio_json = json_encode($all_anio);
?>

<?php include_once('layouts/header.php'); ?>
<style>
:root{
    --panel-bg:#fff;
    --panel-border:#e3e6ef;
    --panel-shadow:0 12px 30px rgba(15,23,42,0.08);
    --text-muted:#6b7280;
}
.planning-container{padding:10px 15px 32px;}
.panel.planning-panel{border:1px solid var(--panel-border);border-radius:10px;box-shadow:var(--panel-shadow);margin-bottom:26px;background:var(--panel-bg);}
.planning-panel .panel-heading{
    background:linear-gradient(135deg,#0b2f73,#082152);
    color:#4c9bfc;
    border-radius:10px 10px 0 0;
    padding:14px 18px;
    font-weight:700;
    display:flex;
    align-items:center;
    gap:10px;
    box-shadow:0 6px 12px rgba(0,0,0,0.18);
}
.planning-panel .panel-heading .glyphicon{font-size:14px;}
.planning-panel .panel-body{padding:18px 22px;}
.planning-table{width:100%;min-width:960px;}
.meta-table{width:100%;min-width:1400px;border:1px solid #dfe3ec;}
.meta-table thead th{font-size:12px;text-align:center;vertical-align:middle;border:1px solid #cfd5e1;color:#0f172a;padding:6px 8px;}
.meta-table .th-group{font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
.th-habilitado{background:#2ecc71!important;color:#052b14!important;}
.th-fabricacion{background:#faf854!important;color:#303217!important;}
.th-acabado{background:#5490fa!important;color:#031f47!important;}
.th-especial{background:#ff6b6b!important;color:#fff!important;}
.th-total{background:#9b59b6!important;color:#fff!important;}
.th-neutral{background:#f4f5f7!important;color:#374151!important;}
.th-procesos-especiales{display:none;}
.procesos-especiales-toggle{background:linear-gradient(135deg,#ff6b6b,#e55050);border:none;padding:8px 14px;border-radius:6px;color:#fff;font-weight:600;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;margin-left:15px;transition:all .2s;box-shadow:0 4px 12px rgba(255,107,107,0.3);}
.procesos-especiales-toggle:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(255,107,107,0.4);}
.procesos-especiales-toggle.active{background:linear-gradient(135deg,#e55050,#c73e3e);}
.procesos-especiales-toggle .toggle-icon{font-weight:bold;font-size:14px;}
.th-w-15{width:15px;}
.th-w-25{width:25px;}
.th-w-45{width:45px;}
.th-w-55{width:55px;}
.th-w-68{width:68px;}
.th-w-70{width:70px;}
.th-w-120{width:120px;}
.th-w-150{width:150px;}
.th-w-190{width:190px;}
.vertical-text{writing-mode:vertical-rl;transform:rotate(180deg);text-align:center;font-size:11px;white-space:nowrap;padding:8px 4px;}
.report-actions{text-align:center;margin:26px 0 18px;}
.btn-report{background:linear-gradient(135deg,#e67e22,#d35400);border:none;padding:12px 22px;border-radius:8px;color:#fff;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:8px;box-shadow:0 10px 20px rgba(212,84,0,0.25);transition:transform .2s ease,box-shadow .2s ease;}
.btn-report:hover{transform:translateY(-1px);box-shadow:0 14px 26px rgba(212,84,0,0.35);color:#fff;}
.btn-report:active{transform:translateY(0);box-shadow:0 6px 12px rgba(212,84,0,0.25);}
.modal-report{display:none;position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:10000;padding:40px 24px;overflow:auto;}
.modal-report__dialog{background:#fff;border-radius:12px;margin:0 auto;max-width:1400px;width:95%;min-height:70vh;box-shadow:0 25px 50px rgba(15,23,42,0.35);display:flex;flex-direction:column;}
.modal-report__header{background:linear-gradient(135deg,#e67e22,#d35400);color:#fff;padding:16px 24px;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between;}
.modal-report__header h3{margin:0;font-size:1.2rem;}
.modal-report__close{background:none;border:none;color:#fff;font-size:28px;cursor:pointer;line-height:1;}
.modal-report__frame{border:none;flex:1;}
.floating-panel{position:fixed;top:20vh;left:50%;transform:translateX(-50%);z-index:9999;background:#fff;border-radius:14px;box-shadow:0 25px 45px rgba(15,23,42,0.25);padding:58px 28px 28px;min-width:420px;}
.floating-panel__btn{border:none;border-radius:6px;padding:10px 14px;font-weight:600;color:#fff;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease;}
.floating-panel__btn:hover{transform:translateY(-1px);box-shadow:0 10px 18px rgba(0,0,0,0.15);}
.floating-panel__btn-close{position:absolute;top:10px;right:10px;background:#e74c3c;}
.floating-panel__btn-resize{position:absolute;top:10px;right:56px;background:#faba52;color:#2d1f00;}
.floating-panel__actions{display:flex;flex-wrap:wrap;gap:10px;margin:10px 0 20px;}
.floating-panel__action{background:#2563eb;color:#fff;border:none;border-radius:6px;padding:10px 16px;font-weight:600;cursor:pointer;transition:background .2s ease;}
.floating-panel__action:hover{background:#1d4ed8;}
.floating-panel__action--secondary{background:#b44c43;}
.floating-panel__action--success{background:#16a34a;}
.floating-panel__action--warning{background:#f97316;}
.floating-panel__table{width:100%;border:1px solid #d1d5db;border-collapse:collapse;font-size:13px;}
.floating-panel__table th,
.floating-panel__table td{border:1px solid #d1d5db;padding:6px 8px;text-align:left;}
.floating-panel__table thead{background:#f3f4f6;font-weight:600;}
@media (max-width:992px){
    .planning-panel .panel-body{padding:16px;}
    .planning-table,
    .meta-table{min-width:900px;}
    .floating-panel{width:90vw;left:50%;transform:translate(-50%, -50%);top:50%;}
}
</style>
<script src="js/programarSemana.js?v=20251106"></script>
<script type="text/javascript">
$(document).ready(function () {
    console.log('ProgramarSemana.php - DOM Ready');
    console.log('MIGRACIÓN COMPLETADA - Todas las funciones JavaScript están en js/programarSemana.js');
    
    // Variables globales para JavaScript externo
    var allSemanas = <?php echo $all_semana_json; ?>;
    var allanio = <?php echo  $all_anio_json; ?>;
    
    // Hacer las variables disponibles globalmente para el archivo JavaScript externo
    window.allSemanas = allSemanas;
    window.allanio = allanio;
    window.currentYear = new Date().getFullYear();
    
    // La inicialización del botón de reporte se hace desde js/programarSemana.js
    // para evitar listeners duplicados aquí no llamamos a initializeReporteProcesosNoCerrados()
});
</script>

<div class="container-fluid planning-container">
    <div class="row">
        <div class="col-md-12">
            <?php echo display_msg($msg); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default planning-panel">
                <div class="panel-heading">
                    <strong><span class="glyphicon glyphicon-th"></span> Calcular semanas</strong>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="example" class="table table-striped planning-table">
                            <thead>
                            <tr>
                                <th class="th-w-45">ID</th>
                                <th>Proyecto</th>
                                <th>Descripción</th>
                                <th>Correo</th>
                                <th>Estado</th>
                                <th>Cerrar OT</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default planning-panel">
                <div class="panel-heading">
                    <strong><span class="glyphicon glyphicon-th"></span> Resultados de Subconsulta</strong>
                    <button type="button" id="toggleProcesosEspeciales" class="procesos-especiales-toggle">
                        <span class="toggle-icon">+</span>
                        <span>Procesos Especiales</span>
                    </button>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="subQueryResults" class="table table-striped table-bordered meta-table">
                            <thead>
                            <tr>
                                <th class="th-group th-habilitado th-w-15"></th>
                                <th class="th-group th-habilitado th-w-25"></th>
                                <th class="th-group th-habilitado th-w-25"></th>
                                <th class="th-group th-habilitado th-w-150"></th>
                                <th class="th-group th-habilitado th-w-45"></th>
                                <th class="th-group th-habilitado th-w-55"></th>
                                <th class="th-group th-habilitado th-w-55"></th>
                                <th class="th-group th-habilitado th-w-190" colspan="4">Habilitado Meta</th>
                                <th class="th-group th-fabricacion" colspan="11">Fabricación HH Meta</th>
                                <th class="th-group th-acabado" colspan="4">Acabados HH Meta</th>
                                <th class="th-group th-especial th-procesos-especiales" colspan="4">Procesos Especiales</th>
                                <th class="th-group th-total" colspan="1">Total</th>
                            </tr>
                            <tr>
                                <th class="th-neutral">N°</th>
                                <th class="th-neutral">Tipo</th>
                                <th class="th-neutral">Cantd</th>
                                <th class="th-neutral th-w-120">NPdre</th>
                                <th class="th-neutral">HH. Ppto</th>
                                <th class="th-neutral">Peso Ppto</th>
                                <th class="th-neutral">Peso Soldadura</th>
                                <th class="vertical-text th-habilitado th-w-68">Pantógrafo Grande</th>
                                <th class="vertical-text th-habilitado th-w-68">Pantógrafo Chico</th>
                                <th class="vertical-text th-habilitado th-w-68">Habilitado Perfiles</th>
                                <th class="vertical-text th-habilitado th-w-68">Limpieza Mecánica</th>
                                <th class="vertical-text th-fabricacion th-w-68">Rolado</th>
                                <th class="vertical-text th-fabricacion th-w-68">Torno Grande</th>
                                <th class="vertical-text th-fabricacion th-w-68">Torno Chico</th>
                                <th class="vertical-text th-fabricacion th-w-70">CNC Grande</th>
                                <th class="vertical-text th-fabricacion th-w-68">CNC Chico</th>
                                <th class="vertical-text th-fabricacion th-w-68">Armado</th>
                                <th class="vertical-text th-fabricacion th-w-68">Soldeo</th>
                                <th class="vertical-text th-fabricacion th-w-68">Plantillado</th>
                                <th class="vertical-text th-fabricacion th-w-70">Taladro</th>
                                <th class="vertical-text th-fabricacion th-w-68">Limpieza Mecánica</th>
                                <th class="vertical-text th-fabricacion th-w-68">Ensamble</th>
                                <th class="vertical-text th-acabado th-w-68">Granallado</th>
                                <th class="vertical-text th-acabado th-w-68">Pintura</th>
                                <th class="vertical-text th-acabado th-w-68">Decapado Pasivado</th>
                                <th class="vertical-text th-acabado th-w-68">Embalaje</th>
                                <th class="vertical-text th-especial th-w-68 th-procesos-especiales">Tratam. Térmico</th>
                                <th class="vertical-text th-especial th-w-68 th-procesos-especiales">Mecanizado</th>
                                <th class="vertical-text th-especial th-w-68 th-procesos-especiales">Revestimiento</th>
                                <th class="vertical-text th-especial th-w-68 th-procesos-especiales">Varios</th>
                                <th class="vertical-text th-total th-w-68">HH. Meta</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="report-actions">
        <button id="reporte-procesos-no-cerrados" class="btn-report" type="button">
            <span class="glyphicon glyphicon-list-alt"></span>
            Reporte de Procesos No Cerrados
        </button>
    </div>
</div>

<div id="modal-reporte" class="modal-report" aria-hidden="true">
    <div class="modal-report__dialog">
        <div class="modal-report__header">
            <h3><i class="glyphicon glyphicon-list-alt"></i> Reporte de Procesos No Cerrados</h3>
            <button type="button" class="modal-report__close" onclick="cerrarModalReporte()" aria-label="Cerrar">&times;</button>
        </div>
        <iframe id="iframe-reporte" class="modal-report__frame" src=""></iframe>
    </div>
</div>

<script>
function abrirModalReporte(e) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const modal = document.getElementById('modal-reporte');
    const iframe = document.getElementById('iframe-reporte');
    iframe.src = 'reporte_procesos_no_cerrados.php';
    modal.style.display = 'block';
    return false;
}

function cerrarModalReporte() {
    const modal = document.getElementById('modal-reporte');
    const iframe = document.getElementById('iframe-reporte');
    modal.style.display = 'none';
    iframe.src = '';
}

document.addEventListener('click', function(event) {
    const modal = document.getElementById('modal-reporte');
    if (!modal || modal.style.display !== 'block') {
        return;
    }
    if (event.target === modal) {
        cerrarModalReporte();
    }
});
</script>
<div id="div-flotante" class="floating-panel" style="display:none;">
    <button id="close-btn" class="floating-panel__btn floating-panel__btn-close" type="button">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
    <button id="increase-size-btn" class="floating-panel__btn floating-panel__btn-resize" type="button">
        <span class="glyphicon glyphicon-fullscreen"></span>
    </button>

    <div class="floating-panel__actions">
        <button id="add-row-flotante-btn" class="floating-panel__action floating-panel__action--success" type="button">Agregar Fila</button>
        <button id="add-Abrir-Semanas" class="floating-panel__action floating-panel__action--secondary" type="button">Abrir Semanas</button>
    </div>

    <div class="tabla-interna">
        <table class="tabla-dinamica floating-panel__table">
            <thead>
            <tr>
                <th>Año</th>
                <th>Semana</th>
                <th>Porcentaje</th>
                <th>Control Horas</th>
                <th>Eliminar</th>
            </tr>
            </thead>
            <tbody>
            <!-- Las filas se insertarán dinámicamente aquí -->
            </tbody>
        </table>
    </div>

    <button id="guardarDatos" class="floating-panel__action floating-panel__action--warning" type="button">Guardar Datos</button>
</div>
<script>
// ...existing code...
window.addEventListener('DOMContentLoaded', function() {
    const div = document.getElementById('div-flotante');
    const barra = document.getElementById('barra-flotante');
    const resizeBtn = document.getElementById('resize-btn');
    const closeBtn = document.getElementById('close-btn');
    let isMaximized = false;
    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;

    // --- Botón de agrandar/restaurar ---
    if (resizeBtn && div) {
        resizeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!isMaximized) {
                div.style.width = '95vw';
                div.style.height = '90vh';
                div.style.left = '50%';
                div.style.top = '50%';
                div.style.transform = 'translate(-50%, -50%)';
                div.style.resize = 'none';
                isMaximized = true;
            } else {
                div.style.width = '';
                div.style.height = '';
                div.style.left = '';
                div.style.top = '';
                div.style.transform = '';
                div.style.resize = '';
                isMaximized = false;
            }
        });
    }

    // --- Botón de cerrar ---
    if (closeBtn && div) {
        closeBtn.addEventListener('click', function() {
            div.style.display = 'none';
        });
    }

    // --- Movimiento del div flotante solo desde la barra superior ---
    if (barra) barra.style.cursor = 'move';

    function onPointerMove(e) {
        if (!isDragging) return;
        let clientX = e.clientX;
        let clientY = e.clientY;
        div.style.left = (clientX - offsetX) + 'px';
        div.style.top = (clientY - offsetY) + 'px';
        div.style.position = 'fixed';
    }

    function onPointerUp() {
        if (isDragging) {
            isDragging = false;
            document.body.style.userSelect = '';
            window.removeEventListener('pointermove', onPointerMove);
            window.removeEventListener('pointerup', onPointerUp);
        }
    }

    if (barra) {
        barra.addEventListener('pointerdown', function(e) {
            if (e.target.tagName === 'BUTTON') return;
            if (isMaximized) return;
            // Fijar tamaño actual para evitar que cambie al mover
            const rect = div.getBoundingClientRect();
            div.style.width = rect.width + 'px';
            div.style.height = rect.height + 'px';
            if (!div.style.left) div.style.left = rect.left + 'px';
            if (!div.style.top) div.style.top = rect.top + 'px';
            div.style.transform = '';
            isDragging = true;
            offsetX = e.clientX - rect.left;
            offsetY = e.clientY - rect.top;
            document.body.style.userSelect = 'none';
            window.addEventListener('pointermove', onPointerMove);
            window.addEventListener('pointerup', onPointerUp);
        });
    }
});
</script>
<?php include_once('layouts/footer.php'); ?>