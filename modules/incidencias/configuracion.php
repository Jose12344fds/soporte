<?php
$page_title = 'Configuración de Mesa de Ayuda';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/bootstrap.php';

inc_schema();
require_permiso('incidencias', 'editar');

$categories = find_by_sql(
    "SELECT c.*,COUNT(t.IdTicket) Tickets
     FROM inc_categorias c LEFT JOIN inc_tickets t ON t.IdCategoria=c.IdCategoria
     GROUP BY c.IdCategoria ORDER BY c.Activa DESC,c.Nombre"
);
$slas = find_by_sql(
    "SELECT s.*,COUNT(t.IdTicket) Tickets
     FROM inc_sla_politicas s LEFT JOIN inc_tickets t ON t.IdSLA=s.IdSLA
     GROUP BY s.IdSLA ORDER BY FIELD(s.Prioridad,'CRITICA','ALTA','MEDIA','BAJA')"
);
$extraCss = [BASE_URL.'/assets/css/incidencias.css'];
include_once __DIR__.'/../../layouts/header.php';
?>

<section class="content inc-page"><div class="container-fluid pt-3 pb-4">
    <?= display_msg($msg) ?>
    <div class="inc-hero mb-3"><div class="d-lg-flex justify-content-between align-items-center"><div><div class="inc-hero-kicker">Gobierno de la Mesa de Ayuda</div><h1><i class="fas fa-sliders-h mr-2"></i>Configuración operativa</h1><p>Administre la clasificación del catálogo y los compromisos de atención que calculan automáticamente cada ticket.</p></div><div class="inc-hero-actions mt-3 mt-lg-0"><a class="btn btn-outline-light" href="index.php"><i class="fas fa-arrow-left mr-1"></i>Bandeja</a><a class="btn btn-outline-light" href="reportes.php"><i class="fas fa-chart-line mr-1"></i>Indicadores</a></div></div></div>

    <div class="alert alert-info border-0 shadow-sm"><i class="fas fa-info-circle mr-2"></i><strong>Regla vigente:</strong> los tiempos SLA se calculan en minutos calendario. Los estados “Pendiente del usuario” y “Pendiente de tercero” pausan el objetivo de resolución. Las categorías utilizadas no se eliminan; se desactivan para conservar trazabilidad.</div>

    <div class="row">
        <div class="col-xl-7 mb-3"><div class="card h-100">
            <div class="inc-table-head"><div><h2><i class="fas fa-sitemap mr-2 text-info"></i>Catálogo de categorías</h2><small class="text-muted">Ordena los casos y facilita el análisis de demanda.</small></div><button class="btn btn-info" id="newCategory"><i class="fas fa-plus mr-1"></i>Nueva categoría</button></div>
            <div class="table-responsive"><table class="table inc-table mb-0"><thead><tr><th>Categoría</th><th>Descripción</th><th>Tickets</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
            <?php foreach ($categories as $category): ?><tr>
                <td><span class="d-inline-flex align-items-center"><span class="inc-file-icon mr-2" style="background:<?= inc_h($category['Color']) ?>"><i class="<?= inc_h($category['Icono']) ?>"></i></span><span><strong><?= inc_h($category['Nombre']) ?></strong><small class="inc-subtext"><?= inc_h($category['Codigo']) ?></small></span></span></td>
                <td><?= inc_h($category['Descripcion']) ?></td><td><span class="badge badge-light"><?= (int)$category['Tickets'] ?></span></td>
                <td><span class="badge <?= (int)$category['Activa']?'badge-success':'badge-secondary' ?>"><?= (int)$category['Activa']?'Activa':'Inactiva' ?></span></td>
                <td><button class="btn btn-sm btn-outline-warning edit-category" data-id="<?= (int)$category['IdCategoria'] ?>" data-code="<?= inc_h($category['Codigo']) ?>" data-name="<?= inc_h($category['Nombre']) ?>" data-description="<?= inc_h($category['Descripcion']) ?>" data-icon="<?= inc_h($category['Icono']) ?>" data-color="<?= inc_h($category['Color']) ?>" data-active="<?= (int)$category['Activa'] ?>"><i class="fas fa-pencil-alt"></i></button></td>
            </tr><?php endforeach; ?>
            </tbody></table></div>
        </div></div>

        <div class="col-xl-5 mb-3"><div class="card h-100">
            <div class="inc-table-head"><div><h2><i class="fas fa-stopwatch mr-2 text-info"></i>Políticas SLA</h2><small class="text-muted">Objetivos por prioridad calculada.</small></div><button class="btn btn-info" id="newSla"><i class="fas fa-plus mr-1"></i>Nuevo SLA</button></div>
            <div class="p-3">
            <?php foreach ($slas as $sla): ?><div class="inc-config-card p-3 mb-2"><div class="d-flex justify-content-between align-items-start"><div><span class="inc-badge inc-priority-<?= inc_h($sla['Prioridad']) ?>"><?= inc_h(inc_priority_label($sla['Prioridad'])) ?></span><h3 class="h6 font-weight-bold mt-2 mb-1"><?= inc_h($sla['Nombre']) ?></h3><small class="text-muted"><?= inc_h($sla['Descripcion']) ?></small></div><button class="btn btn-sm btn-outline-warning edit-sla" data-id="<?= (int)$sla['IdSLA'] ?>" data-name="<?= inc_h($sla['Nombre']) ?>" data-priority="<?= inc_h($sla['Prioridad']) ?>" data-response="<?= (int)$sla['MinutosRespuesta'] ?>" data-resolution="<?= (int)$sla['MinutosResolucion'] ?>" data-description="<?= inc_h($sla['Descripcion']) ?>" data-active="<?= (int)$sla['Activa'] ?>"><i class="fas fa-pencil-alt"></i></button></div><div class="row mt-3"><div class="col-6"><span class="inc-label">Primera respuesta</span><strong><?= inc_duration($sla['MinutosRespuesta']) ?></strong></div><div class="col-6"><span class="inc-label">Resolución</span><strong><?= inc_duration($sla['MinutosResolucion']) ?></strong></div></div><div class="mt-2"><span class="badge <?= (int)$sla['Activa']?'badge-success':'badge-secondary' ?>"><?= (int)$sla['Activa']?'Vigente':'Inactiva' ?></span><small class="text-muted ml-2"><?= (int)$sla['Tickets'] ?> ticket(s) relacionados</small></div></div><?php endforeach; ?>
            </div>
        </div></div>
    </div>
</div></section>

<div class="modal fade inc-modal" id="categoryModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="post" action="acciones.php">
    <?= csrf_input() ?><input type="hidden" name="action" value="guardar_categoria"><input type="hidden" name="IdCategoria" id="categoryId" value="0">
    <div class="modal-header"><div><div class="inc-hero-kicker">Catálogo de atención</div><h5 class="modal-title" id="categoryTitle">Nueva categoría</h5></div><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body"><div class="inc-form-panel"><div class="row"><div class="col-md-4"><div class="form-group"><label class="inc-label">Código *</label><input class="form-control inc-control" name="Codigo" id="categoryCode" maxlength="30" required></div></div><div class="col-md-8"><div class="form-group"><label class="inc-label">Nombre *</label><input class="form-control inc-control" name="Nombre" id="categoryName" maxlength="100" required></div></div></div><div class="form-group"><label class="inc-label">Descripción</label><textarea class="form-control inc-control" name="Descripcion" id="categoryDescription" rows="3"></textarea></div><div class="row"><div class="col-md-8"><div class="form-group"><label class="inc-label">Ícono Font Awesome</label><input class="form-control inc-control" name="Icono" id="categoryIcon" value="fas fa-ticket-alt"></div></div><div class="col-md-4"><div class="form-group"><label class="inc-label">Color</label><input class="form-control inc-control p-1" type="color" name="Color" id="categoryColor" value="#128698"></div></div></div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="categoryActive" name="Activa" value="1" checked><label class="custom-control-label" for="categoryActive">Categoría disponible para nuevos tickets</label></div></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-info"><i class="fas fa-save mr-1"></i>Guardar categoría</button></div>
</form></div></div></div>

<div class="modal fade inc-modal" id="slaModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="post" action="acciones.php">
    <?= csrf_input() ?><input type="hidden" name="action" value="guardar_sla"><input type="hidden" name="IdSLA" id="slaId" value="0">
    <div class="modal-header"><div><div class="inc-hero-kicker">Compromiso de servicio</div><h5 class="modal-title" id="slaTitle">Nueva política SLA</h5></div><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body"><div class="inc-form-panel"><div class="row"><div class="col-md-7"><div class="form-group"><label class="inc-label">Nombre *</label><input class="form-control inc-control" name="Nombre" id="slaName" required></div></div><div class="col-md-5"><div class="form-group"><label class="inc-label">Prioridad *</label><select class="form-control inc-control" name="Prioridad" id="slaPriority"><?php foreach (['CRITICA','ALTA','MEDIA','BAJA'] as $priority): ?><option value="<?= $priority ?>"><?= inc_h(inc_priority_label($priority)) ?></option><?php endforeach; ?></select></div></div></div><div class="row"><div class="col-md-6"><div class="form-group"><label class="inc-label">Respuesta (minutos) *</label><input type="number" min="1" class="form-control inc-control" name="MinutosRespuesta" id="slaResponse" required></div></div><div class="col-md-6"><div class="form-group"><label class="inc-label">Resolución (minutos) *</label><input type="number" min="1" class="form-control inc-control" name="MinutosResolucion" id="slaResolution" required></div></div></div><div class="form-group"><label class="inc-label">Alcance</label><textarea class="form-control inc-control" name="Descripcion" id="slaDescription" rows="3"></textarea></div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="slaActive" name="Activa" value="1" checked><label class="custom-control-label" for="slaActive">Política vigente</label></div></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-info"><i class="fas fa-save mr-1"></i>Guardar SLA</button></div>
</form></div></div></div>

<?php
$pageScripts = <<<'JS'
$(function () {
    $('#newCategory').on('click', function () {
        $('#categoryTitle').text('Nueva categoría');
        $('#categoryId').val(0); $('#categoryCode,#categoryName,#categoryDescription').val('');
        $('#categoryIcon').val('fas fa-ticket-alt'); $('#categoryColor').val('#128698'); $('#categoryActive').prop('checked', true);
        $('#categoryModal').modal('show');
    });
    $('.edit-category').on('click', function () {
        var b=$(this); $('#categoryTitle').text('Editar categoría'); $('#categoryId').val(b.data('id'));
        $('#categoryCode').val(b.data('code')); $('#categoryName').val(b.data('name')); $('#categoryDescription').val(b.data('description'));
        $('#categoryIcon').val(b.data('icon')); $('#categoryColor').val(b.data('color')); $('#categoryActive').prop('checked',String(b.data('active'))==='1');
        $('#categoryModal').modal('show');
    });
    $('#newSla').on('click', function () {
        $('#slaTitle').text('Nueva política SLA'); $('#slaId').val(0); $('#slaName,#slaResponse,#slaResolution,#slaDescription').val('');
        $('#slaPriority').val('MEDIA'); $('#slaActive').prop('checked',true); $('#slaModal').modal('show');
    });
    $('.edit-sla').on('click', function () {
        var b=$(this); $('#slaTitle').text('Editar política SLA'); $('#slaId').val(b.data('id')); $('#slaName').val(b.data('name'));
        $('#slaPriority').val(b.data('priority')); $('#slaResponse').val(b.data('response')); $('#slaResolution').val(b.data('resolution'));
        $('#slaDescription').val(b.data('description')); $('#slaActive').prop('checked',String(b.data('active'))==='1'); $('#slaModal').modal('show');
    });
});
JS;
include_once __DIR__.'/../../layouts/footer.php';
?>

