<?php
/**
 * Administración de Subpáginas por Módulo
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Gestión de Subpáginas';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

// Verificar permiso
require_permiso('admin_modulos', 'ver');

global $db;

// Obtener módulos
$sql_modulos = "SELECT id, slug, nombre, icono FROM modulos WHERE estado=1 ORDER BY orden ASC";
$result_modulos = $db->query($sql_modulos);
$modulos = [];
if ($result_modulos && $db->num_rows($result_modulos) > 0) {
    while ($row = $db->fetch_assoc($result_modulos)) {
        $modulos[] = $row;
    }
}

// Filtrar por módulo si viene en GET
$modulo_filtro = isset($_GET['modulo']) ? trim($_GET['modulo']) : null;

// Obtener todas las páginas
$sql = "SELECT mp.*, m.nombre as modulo_nombre, m.slug as modulo_slug FROM module_pages mp
        LEFT JOIN modulos m ON mp.modulo_slug = m.slug
        WHERE 1=1 ";

if ($modulo_filtro) {
    $sql .= " AND mp.modulo_slug = '" . $db->escape($modulo_filtro) . "'";
}

$sql .= " ORDER BY mp.modulo_slug, mp.parent_id, mp.orden ASC";
$result = $db->query($sql);

$pages = [];
if ($result && $db->num_rows($result) > 0) {
    while ($row = $db->fetch_assoc($result)) {
        $row['url'] = app_relative_url($row['url']);
        $pages[] = $row;
    }
}

// Agrupar por módulo y padre para fácil visualización
$pages_por_modulo = [];
foreach ($pages as $page) {
    $slug = $page['modulo_slug'];
    if (!isset($pages_por_modulo[$slug])) {
        $pages_por_modulo[$slug] = [];
    }
    $pages_por_modulo[$slug][] = $page;
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-sitemap mr-2"></i>Gestión de Subpáginas</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Módulos</a></li>
                    <li class="breadcrumb-item active">Subpáginas</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">

        <!-- Filtro por módulo -->
        <div class="row mb-3">
            <div class="col-md-6">
                <select class="form-control" id="filtroModulo" onchange="filtrarPorModulo(this.value)">
                    <option value="">-- Ver Todos los Módulos --</option>
                    <?php foreach ($modulos as $mod): ?>
                        <option value="<?php echo htmlspecialchars($mod['slug']); ?>" 
                            <?php echo ($modulo_filtro === $mod['slug']) ? 'selected' : ''; ?>>
                            <i class="<?php echo htmlspecialchars($mod['icono']); ?>"></i>
                            <?php echo htmlspecialchars($mod['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 text-right">
                <button class="btn btn-success" data-toggle="modal" data-target="#modalAgregarPage">
                    <i class="fas fa-plus mr-1"></i>Agregar Subpágina
                </button>
            </div>
        </div>

        <!-- Tabla de Subpáginas (View Simple) -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            Lista de Subpáginas
                            <?php if ($modulo_filtro): ?>
                                <small>(Filtrado por módulo)</small>
                            <?php endif; ?>
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-info"><?php echo count($pages); ?> páginas totales</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pages)): ?>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="15%">Módulo</th>
                                        <th width="30%">Título</th>
                                        <th width="35%">URL</th>
                                        <th width="5%">Orden</th>
                                        <th width="10%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pages as $page): ?>
                                        <tr>
                                            <td><code><?php echo $page['id']; ?></code></td>
                                            <td>
                                                <span class="badge badge-primary"><?php echo htmlspecialchars($page['modulo_slug']); ?></span>
                                            </td>
                                            <td>
                                                <i class="<?php echo htmlspecialchars($page['icon']); ?> mr-2"></i>
                                                <strong><?php echo htmlspecialchars($page['title']); ?></strong>
                                                <?php if ($page['parent_id']): ?>
                                                    <br><small class="text-muted">Padre: ID <?php echo $page['parent_id']; ?></small>
                                                <?php else: ?>
                                                    <br><small class="badge badge-success">Nivel Raíz</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><code><?php echo htmlspecialchars(substr($page['url'], 0, 40)); ?></code></td>
                                            <td><?php echo $page['orden']; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="editarPage(<?php echo $page['id']; ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="eliminarPage(<?php echo $page['id']; ?>, '<?php echo addslashes($page['title']); ?>')" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <?php if ($modulo_filtro): ?>
                                    No hay subpáginas para el módulo <strong><?php echo htmlspecialchars($modulo_filtro); ?></strong>
                                <?php else: ?>
                                    No hay subpáginas registradas. Comienza agregando una nueva.
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Agregar Subpágina -->
<div class="modal fade" id="modalAgregarPage" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nueva Subpágina</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAgregarPage">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Módulo <span class="text-danger">*</span></label>
                                <select class="form-control" id="pageModulo" name="modulo_slug" required onchange="cargarPaginasProyecto()">
                                    <option value="">-- Selecciona un Módulo --</option>
                                    <?php foreach ($modulos as $mod): ?>
                                        <option value="<?php echo htmlspecialchars($mod['slug']); ?>">
                                            <?php echo htmlspecialchars($mod['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Página Padre (opcional)</label>
                                <select class="form-control" id="pagePadre" name="parent_id">
                                    <option value="">-- Sin padre (nivel raíz) --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="pageTitle" name="title" placeholder="ej: Agregar Computadora" required>
                    </div>

                    <div class="form-group">
                        <label>URL <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="pageUrl" name="url" placeholder="ej: /modules/activos/agregar.php" required>
                        <small class="form-text text-muted">Para páginas internas use /modules/... El servidor se toma de la configuración general.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Icono <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="pageIcon" name="icon" placeholder="ej: fas fa-plus-circle" value="fas fa-file" required>
                                <small class="form-text text-muted">Font Awesome icon class</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Orden</label>
                                <input type="number" class="form-control" id="pageOrden" name="orden" value="1" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="pageEstado" name="estado" checked>
                            <label class="custom-control-label" for="pageEstado">Activo</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarNuevaPage()">Guardar Subpágina</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Subpágina -->
<div class="modal fade" id="modalEditarPage" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Subpágina</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formEditarPage">
                    <input type="hidden" id="editPageId" name="id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Módulo</label>
                                <input type="text" class="form-control" id="editPageModulo" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Página Padre (opcional)</label>
                                <select class="form-control" id="editPagePadre" name="parent_id">
                                    <option value="">-- Sin padre (nivel raíz) --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editPageTitle" name="title" required>
                    </div>

                    <div class="form-group">
                        <label>URL <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editPageUrl" name="url" required>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Icono</label>
                                <input type="text" class="form-control" id="editPageIcon" name="icon" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Orden</label>
                                <input type="number" class="form-control" id="editPageOrden" name="orden" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="editPageEstado" name="estado">
                            <label class="custom-control-label" for="editPageEstado">Activo</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarEdicionPage()">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<style>
.tree-view {
    padding: 15px;
    background-color: #f9f9f9;
}

.tree-item {
    margin: 5px 0;
    padding: 10px;
    background-color: white;
    border-left: 3px solid #007bff;
    border-radius: 4px;
    transition: all 0.3s;
}

.tree-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.tree-item.nivel-1 {
    margin-left: 20px;
    border-left-color: #17a2b8;
}

.tree-item.nivel-2 {
    margin-left: 40px;
    border-left-color: #28a745;
}

.tree-item.nivel-3 {
    margin-left: 60px;
    border-left-color: #ffc107;
}

.tree-item-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tree-item-info {
    flex: 1;
}

.tree-item-title {
    font-weight: 500;
    color: #333;
}

.tree-item-url {
    font-size: 0.85em;
    color: #666;
    font-family: monospace;
}

.tree-item-icon {
    margin-right: 10px;
    color: #007bff;
}

.tree-item-badge {
    margin-left: 10px;
}

.tree-item-actions {
    display: flex;
    gap: 5px;
}

.btn-tree-action {
    padding: 4px 8px;
    font-size: 12px;
}
</style>

<script>
// Cargar páginas del módulo seleccionado
function cargarPaginasProyecto() {
    const modulo_slug = document.getElementById('pageModulo').value;
    const selectPadre = document.getElementById('pagePadre');
    selectPadre.innerHTML = '<option value="">-- Sin padre (nivel raíz) --</option>';

    if (!modulo_slug) return;

    fetch('../../api/pages.php?action=obtener_por_modulo&modulo=' + encodeURIComponent(modulo_slug))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.pages && data.pages.length > 0) {
                data.pages.forEach(page => {
                    const option = document.createElement('option');
                    option.value = page.id;
                    const indent = page.parent_id ? '  └─ ' : '';
                    option.textContent = indent + page.title;
                    selectPadre.appendChild(option);
                });
            }
        })
        .catch(err => console.error('Error:', err));
}

// Guardar nueva subpágina
function guardarNuevaPage() {
    const formData = new FormData(document.getElementById('formAgregarPage'));
    formData.append('action', 'agregar');
    formData.append('estado', document.getElementById('pageEstado').checked ? 1 : 0);

    fetch('../../api/pages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Subpágina agregada exitosamente');
            $('#modalAgregarPage').modal('hide');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo agregar'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error en la solicitud');
    });
}

// Editar subpágina
function editarPage(pageId) {
    fetch('../../api/pages.php?action=obtener&id=' + pageId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const page = data.page;
                document.getElementById('editPageId').value = page.id;
                document.getElementById('editPageModulo').value = page.modulo_nombre || page.modulo_slug;
                document.getElementById('editPageTitle').value = page.title;
                document.getElementById('editPageUrl').value = page.url;
                document.getElementById('editPageIcon').value = page.icon;
                document.getElementById('editPageOrden').value = page.orden;
                document.getElementById('editPageEstado').checked = page.estado == 1;

                // Cargar opciones de padre
                cargarOpcionesPadreEditar(page.modulo_slug, page.parent_id);

                $('#modalEditarPage').modal('show');
            }
        });
}

// Cargar opciones de padre en edición
function cargarOpcionesPadreEditar(modulo_slug, parent_id_actual) {
    const selectPadre = document.getElementById('editPagePadre');
    selectPadre.innerHTML = '<option value="">-- Sin padre (nivel raíz) --</option>';

    fetch('../../api/pages.php?action=obtener_por_modulo&modulo=' + encodeURIComponent(modulo_slug))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.pages) {
                data.pages.forEach(page => {
                    if (page.id != document.getElementById('editPageId').value) {
                        const option = document.createElement('option');
                        option.value = page.id;
                        const indent = page.parent_id ? '  └─ ' : '';
                        option.textContent = indent + page.title;
                        if (page.id == parent_id_actual) option.selected = true;
                        selectPadre.appendChild(option);
                    }
                });
            }
        });
}

// Guardar edición de subpágina
function guardarEdicionPage() {
    const formData = new FormData(document.getElementById('formEditarPage'));
    formData.append('action', 'actualizar');
    formData.append('estado', document.getElementById('editPageEstado').checked ? 1 : 0);

    fetch('../../api/pages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Subpágina actualizada exitosamente');
            $('#modalEditarPage').modal('hide');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo actualizar'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error en la solicitud');
    });
}

// Eliminar subpágina
function eliminarPage(pageId, title) {
    if (confirm('¿Estás seguro que deseas eliminar esta subpágina?\n\n' + title)) {
        fetch('../../api/pages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=eliminar&id=' + pageId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Subpágina eliminada exitosamente');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'No se pudo eliminar'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error en la solicitud');
        });
    }
}

// Filtrar por módulo
function filtrarPorModulo(modulo) {
    if (modulo) {
        window.location.href = '?modulo=' + encodeURIComponent(modulo);
    } else {
        window.location.href = '';
    }
}

// Renderizar árbol
function renderTree(pages, parentId = null) {
    const childPages = pages.filter(p => (p.parent_id == parentId || (parentId === null && !p.parent_id)));
    
    if (childPages.length === 0) return '';

    let html = '<div>';
    childPages.forEach((page, index) => {
        const nivel = countNivel(pages, page.id);
        const hasChildren = pages.some(p => p.parent_id == page.id);
        
        html += '<div class="tree-item nivel-' + nivel + '">';
        html += '<div class="tree-item-content">';
        html += '<div class="tree-item-info">';
        html += '<div class="tree-item-title"><i class="' + page.icon + ' tree-item-icon"></i>' + page.title + '</div>';
        html += '<div class="tree-item-url">' + page.url + '</div>';
        html += '</div>';
        html += '<div class="tree-item-actions">';
        html += '<button class="btn btn-sm btn-info btn-tree-action" onclick="editarPage(' + page.id + ')" title="Editar">';
        html += '<i class="fas fa-edit"></i>';
        html += '</button>';
        html += '<button class="btn btn-sm btn-danger btn-tree-action" onclick="eliminarPage(' + page.id + ', \'' + page.title.replace(/'/g, "\\'") + '\')" title="Eliminar">';
        html += '<i class="fas fa-trash"></i>';
        html += '</button>';
        if (hasChildren) {
            html += '<span class="badge badge-warning">' + countChildren(pages, page.id) + ' hijos</span>';
        }
        html += '</div>';
        html += '</div>';
        html += renderTree(pages, page.id);
        html += '</div>';
    });
    html += '</div>';

    return html;
}

function countNivel(pages, pageId) {
    let page = pages.find(p => p.id == pageId);
    let nivel = 1;
    while (page && page.parent_id) {
        page = pages.find(p => p.id == page.parent_id);
        nivel++;
    }
    return nivel;
}

function countChildren(pages, parentId) {
    return pages.filter(p => p.parent_id == parentId).length;
}

$(document).ready(function() {
    // Recargar frontend tree
    location.reload = (function() {
        let original = location.reload;
        return function() {
            document.querySelectorAll('.tree-view').forEach(el => {
                el.innerHTML = renderTree(<?php echo json_encode($pages); ?>, null);
            });
        };
    })();
});
</script>

<?php

/**
 * Función para renderizar árbol de páginas (PHP)
 */
function renderTree($pages, $parentId = null) {
    $childPages = array_filter($pages, function($p) use ($parentId) {
        if ($parentId === null) {
            return $p['parent_id'] == $parentId || $p['parent_id'] === '0';
        }
        return $p['parent_id'] == $parentId;
    });

    if (empty($childPages)) return '';

    $html = '<div>';
    foreach ($childPages as $page) {
        $nivel = getNivel($pages, $page['id']);
        $hasChildren = !empty(array_filter($pages, fn($p) => $p['parent_id'] == $page['id']));

        $html .= '<div class="tree-item nivel-' . $nivel . '">';
        $html .= '<div class="tree-item-content">';
        $html .= '<div class="tree-item-info">';
        $html .= '<div class="tree-item-title">';
        $html .= '<i class="' . htmlspecialchars($page['icon']) . ' tree-item-icon"></i>';
        $html .= htmlspecialchars($page['title']);
        $html .= '</div>';
        $html .= '<div class="tree-item-url">' . htmlspecialchars($page['url']) . '</div>';
        $html .= '</div>';
        $html .= '<div class="tree-item-actions">';
        $html .= '<button class="btn btn-sm btn-info btn-tree-action" onclick="editarPage(' . $page['id'] . ')" title="Editar">';
        $html .= '<i class="fas fa-edit"></i>';
        $html .= '</button>';
        $html .= '<button class="btn btn-sm btn-danger btn-tree-action" onclick="eliminarPage(' . $page['id'] . ', \'' . addslashes($page['title']) . '\')" title="Eliminar">';
        $html .= '<i class="fas fa-trash"></i>';
        $html .= '</button>';
        
        if ($hasChildren) {
            $childCount = count(array_filter($pages, fn($p) => $p['parent_id'] == $page['id']));
            $html .= '<span class="badge badge-warning">' . $childCount . ' hijos</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
        $html .= renderTree($pages, $page['id']);
        $html .= '</div>';
    }
    $html .= '</div>';

    return $html;
}

function getNivel($pages, $pageId) {
    $nivel = 1;
    $current = array_filter($pages, fn($p) => $p['id'] == $pageId);
    $current = reset($current);

    while ($current && $current['parent_id']) {
        $parent = array_filter($pages, fn($p) => $p['id'] == $current['parent_id']);
        $current = reset($parent);
        $nivel++;
    }

    return $nivel;
}

?>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
