<?php
/**
 * Módulo Administración de Módulos
 * Sistema de Soporte TI - TAMA
 */
$page_title = 'Gestión de Módulos';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

// Verificar permiso
require_permiso('admin_modulos', 'ver');

// Obtener módulos de la BD
global $db;
$sql = "SELECT id, slug, nombre, descripcion, icono, estado, orden FROM modulos ORDER BY orden ASC, nombre ASC";
$result = $db->query($sql);
$modulos = [];
if ($result && $db->num_rows($result) > 0) {
    while ($row = $db->fetch_assoc($result)) {
        $modulos[] = $row;
    }
}

// Obtener grupos para dropdown
$sql_grupos = "SELECT id, group_level, group_name FROM user_groups WHERE group_status = 1 ORDER BY group_level ASC";
$result_grupos = $db->query($sql_grupos);
$grupos = [];
if ($result_grupos && $db->num_rows($result_grupos) > 0) {
    while ($row = $db->fetch_assoc($result_grupos)) {
        $grupos[] = $row;
    }
}

include_once(__DIR__ . '/../../layouts/header.php');
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-cube mr-2"></i>Gestión de Módulos</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                    <li class="breadcrumb-item active">Módulos</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Módulos del Sistema</h3>
                        <div class="card-tools">
                            <a href="pages.php" class="btn btn-sm btn-info mr-2">
                                <i class="fas fa-sitemap mr-1"></i>Gestionar Subpáginas
                            </a>
                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalAgregarModulo">
                                <i class="fas fa-plus mr-1"></i>Agregar Módulo
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="tablasModulos" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Slug</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($modulos)): ?>
                                    <?php foreach ($modulos as $modulo): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($modulo['orden']); ?></td>
                                            <td><code><?php echo htmlspecialchars($modulo['slug']); ?></code></td>
                                            <td>
                                                <i class="<?php echo htmlspecialchars($modulo['icono']); ?> mr-2"></i>
                                                <?php echo htmlspecialchars($modulo['nombre']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($modulo['descripcion'], 0, 50)); ?></td>
                                            <td>
                                                <span class="badge <?php echo $modulo['estado'] == 1 ? 'badge-success' : 'badge-danger'; ?>">
                                                    <?php echo $modulo['estado'] == 1 ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-xs btn-success" onclick="abrirSubpaginas('<?php echo htmlspecialchars($modulo['slug']); ?>')" title="Gestionar Subpáginas">
                                                    <i class="fas fa-sitemap"></i>
                                                </button>
                                                <button class="btn btn-xs btn-primary" onclick="editarModulo(<?php echo $modulo['id']; ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-xs btn-info" onclick="verPermisosModulo(<?php echo $modulo['id']; ?>, '<?php echo htmlspecialchars($modulo['slug']); ?>')" title="Permisos">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                                <button class="btn btn-xs btn-danger" onclick='eliminarModulo(<?php echo json_encode((int)$modulo['id']); ?>, <?php echo json_encode($modulo['slug']); ?>)' title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay módulos registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Agregar Módulo -->
<div class="modal fade" id="modalAgregarModulo" tabindex="-1" role="dialog" aria-labelledby="modalAgregarModuloLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarModuloLabel">Agregar Nuevo Módulo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAgregarModulo">
                    <div class="form-group">
                        <label>Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="slug" name="slug" placeholder="ej: mis_activos" required>
                        <small id="slugHelp" class="form-text text-muted">Se genera automáticamente desde el nombre. Solo minúsculas, números, guiones y guiones bajos</small>
                    </div>
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del módulo" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Icono <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="icono" name="icono" value="fas fa-cube" placeholder="ej: fas fa-cube" required>
                        <small class="form-text text-muted">Icono de <a href="https://fontawesome.com" target="_blank">Font Awesome</a></small>
                    </div>
                    <div class="form-group">
                        <label>Orden</label>
                        <input type="number" class="form-control" id="orden" name="orden" value="1" min="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarNuevoModulo()">Guardar Módulo</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Módulo -->
<div class="modal fade" id="modalEditarModulo" tabindex="-1" role="dialog" aria-labelledby="modalEditarModuloLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarModuloLabel">Editar Módulo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formEditarModulo">
                    <input type="hidden" id="moduloId" name="id">
                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" class="form-control" id="slugEditar" name="slug" disabled>
                        <small class="form-text text-muted">El slug no puede ser modificado</small>
                    </div>
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombreEditar" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" id="descripcionEditar" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Icono <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="iconoEditar" name="icono" required>
                    </div>
                    <div class="form-group">
                        <label>Orden</label>
                        <input type="number" class="form-control" id="ordenEditar" name="orden" min="1">
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="estadoEditar" name="estado">
                            <label class="custom-control-label" for="estadoEditar">Activo</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarEdicionModulo()">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Permisos Módulo -->
<div class="modal fade" id="modalPermisosModulo" tabindex="-1" role="dialog" aria-labelledby="modalPermisosModuloLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPermisosModuloLabel">Permisos - <span id="modulo-nombre-permisos"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="contenedor-permisos"></div>
                <input type="hidden" id="moduloIdPermisos">
                <input type="hidden" id="moduloSlugPermisos">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="guardarPermisosModulo()">Guardar Permisos</button>
            </div>
        </div>
    </div>
</div>

<script>
// Función para abrir subpáginas de un módulo
function abrirSubpaginas(modulo_slug) {
    window.location.href = 'pages.php?modulo=' + encodeURIComponent(modulo_slug);
}

// Función para editar módulo
function editarModulo(moduloId) {
    fetch('../../api/modulos.php?action=obtener&id=' + moduloId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const mod = data.modulo;
                document.getElementById('moduloId').value = mod.id;
                document.getElementById('slugEditar').value = mod.slug;
                document.getElementById('nombreEditar').value = mod.nombre;
                document.getElementById('descripcionEditar').value = mod.descripcion;
                document.getElementById('iconoEditar').value = mod.icono;
                document.getElementById('ordenEditar').value = mod.orden;
                document.getElementById('estadoEditar').checked = mod.estado == 1;
                $('#modalEditarModulo').modal('show');
            } else {
                alert('Error: ' + (data.message || 'No se pudo cargar el módulo'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al cargar los datos del módulo');
        });
}

// Función para guardar edición
function guardarEdicionModulo() {
    const formData = new FormData(document.getElementById('formEditarModulo'));
    formData.append('action', 'actualizar');
    formData.append('estado', document.getElementById('estadoEditar').checked ? 1 : 0);

    fetch('../../api/modulos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Módulo actualizado exitosamente');
            $('#modalEditarModulo').modal('hide');
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

// Convertir nombre a slug automáticamente
function generarSlug(nombre) {
    return nombre
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')           // Eliminar caracteres especiales
        .replace(/\s+/g, '_')                // Espacios a guiones bajos
        .replace(/_{2,}/g, '_')              // Múltiples guiones bajos a uno
        .replace(/^_+|_+$/g, '');            // Remover guiones iniciales/finales
}

// Validar slug en tiempo real
function validarSlug(slug) {
    const patron = /^[a-z0-9_-]+$/;
    return patron.test(slug) && slug.length >= 3;
}

// Evento cuando cambia el nombre
document.getElementById('nombre').addEventListener('input', function() {
    const nombre = this.value.trim();
    if (nombre.length > 0) {
        const slugAuto = generarSlug(nombre);
        document.getElementById('slug').value = slugAuto;
        
        // Validar y mostrar estado
        const esValido = validarSlug(slugAuto);
        const slugInput = document.getElementById('slug');
        
        if (esValido) {
            slugInput.classList.remove('is-invalid');
            slugInput.classList.add('is-valid');
            document.getElementById('slugHelp').innerHTML = 
                '<small class="text-success"><i class="fas fa-check"></i> Slug válido: <code>' + slugAuto + '</code></small>';
        } else {
            slugInput.classList.remove('is-valid');
            slugInput.classList.add('is-invalid');
            document.getElementById('slugHelp').innerHTML = 
                '<small class="text-danger"><i class="fas fa-times"></i> Slug inválido: <code>' + slugAuto + '</code></small>';
        }
    }
});

// Validar slug cuando cambia manualmente
document.getElementById('slug').addEventListener('input', function() {
    const slug = this.value.trim();
    const esValido = slug.length >= 3 && validarSlug(slug);
    
    if (slug.length > 0) {
        if (esValido) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            document.getElementById('slugHelp').innerHTML = 
                '<small class="text-success"><i class="fas fa-check"></i> Slug válido: <code>' + slug + '</code></small>';
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
            document.getElementById('slugHelp').innerHTML = 
                '<small class="text-danger"><i class="fas fa-times"></i> Solo minúsculas, números, guiones y guiones bajos. Mínimo 3 caracteres.</small>';
        }
    }
});

// Función para guardar nuevo módulo
function guardarNuevoModulo() {
    const slug = document.getElementById('slug').value.trim();
    const nombre = document.getElementById('nombre').value.trim();
    
    // Validar en cliente primero
    if (!validarSlug(slug)) {
        Swal.fire({
            icon: 'error',
            title: 'Slug inválido',
            html: '<p>El slug debe contener:</p><ul style="text-align: left;"><li>Solo minúsculas (a-z)</li><li>Números (0-9)</li><li>Guiones (-) o guiones bajos (_)</li><li>Mínimo 3 caracteres</li></ul><p><strong>Slug actual:</strong> <code>' + slug + '</code></p>'
        });
        return;
    }
    
    if (nombre.length < 3) {
        Swal.fire('Error', 'El nombre debe tener al menos 3 caracteres', 'error');
        return;
    }
    
    const formData = new FormData(document.getElementById('formAgregarModulo'));
    formData.append('action', 'agregar');

    fetch('../../api/modulos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: data.message || 'Módulo agregado correctamente',
                timer: 2000
            }).then(() => {
                $('#modalAgregarModulo').modal('hide');
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'No se pudo agregar el módulo', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Error en la solicitud', 'error');
    });
}

// Función para eliminar módulo
function eliminarModulo(moduloId, slug) {
    if (confirm('¿Estás seguro que deseas eliminar este módulo?\n\nSlug: ' + slug)) {
        const datos = new URLSearchParams({
            action: 'eliminar',
            id: String(moduloId),
            slug: slug
        });

        fetch('../../api/modulos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: datos.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Módulo eliminado exitosamente');
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

// Función para ver permisos
function verPermisosModulo(moduloId, slug) {
    document.getElementById('modulo-nombre-permisos').textContent = slug;
    
    fetch('../../api/get_permisos_modulo.php?modulo=' + slug)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<table class="table table-sm">';
                html += '<thead><tr><th>Grupo</th><th>Ver</th><th>Crear</th><th>Editar</th><th>Eliminar</th></tr></thead><tbody>';
                
                const grupos = <?php echo json_encode($grupos); ?>;
                
                grupos.forEach(grupo => {
                    const perm = data.permisos.find(p => p.group_level == grupo.group_level) || {};
                    html += '<tr>';
                    html += '<td><strong>' + grupo.group_name + '</strong></td>';
                    html += '<td><input type="checkbox" class="perms" data-grupo="' + grupo.group_level + '" data-accion="puede_ver" ' + (perm.puede_ver ? 'checked' : '') + '></td>';
                    html += '<td><input type="checkbox" class="perms" data-grupo="' + grupo.group_level + '" data-accion="puede_crear" ' + (perm.puede_crear ? 'checked' : '') + '></td>';
                    html += '<td><input type="checkbox" class="perms" data-grupo="' + grupo.group_level + '" data-accion="puede_editar" ' + (perm.puede_editar ? 'checked' : '') + '></td>';
                    html += '<td><input type="checkbox" class="perms" data-grupo="' + grupo.group_level + '" data-accion="puede_eliminar" ' + (perm.puede_eliminar ? 'checked' : '') + '></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                document.getElementById('contenedor-permisos').innerHTML = html;
                document.getElementById('moduloIdPermisos').value = moduloId;
                document.getElementById('moduloSlugPermisos').value = slug;
                $('#modalPermisosModulo').modal('show');
            } else {
                alert('Error: ' + (data.message || 'No se pudo cargar los permisos'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al cargar permisos');
        });
}

// Función para guardar permisos
function guardarPermisosModulo() {
    const slug = document.getElementById('moduloSlugPermisos').value;
    const permisos = [];
    
    document.querySelectorAll('.perms').forEach(checkbox => {
        permisos.push({
            group_level: checkbox.dataset.grupo,
            accion: checkbox.dataset.accion,
            valor: checkbox.checked ? 1 : 0
        });
    });
    
    fetch('../../api/guardar_permisos_modulo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            modulo: slug,
            permisos: permisos
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Permisos guardados exitosamente');
            $('#modalPermisosModulo').modal('hide');
        } else {
            alert('Error: ' + (data.message || 'No se pudo guardar'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error en la solicitud');
    });
}

// Inicializar DataTable
$(document).ready(function() {
    $('#tablasModulos').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.15/i18n/Spanish.json"
        },
        "order": [[0, "asc"]]
    });
});
</script>

<?php include_once(__DIR__ . '/../../layouts/footer.php'); ?>
