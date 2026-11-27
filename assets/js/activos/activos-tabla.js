function renderizarTextoActivo(value, type) {
    return type === 'display' ? escHtml(value == null ? '-' : value) : value;
}

function escHtml(value) {
    return value == null ? '' : String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
function attrHtml(value) {
    return escHtml(value).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}


function crearTablaActivos(permisos){
var puedeEditar=permisos.editar;
var puedeEliminar=permisos.eliminar;
var puedeAsignar=permisos.editar;
    var tablaActivos = $('#tblActivos').DataTable({
        language: DATATABLES_SPANISH,
        responsive: true,
        processing: true,
        serverSide: true,
        ajax: function(request, callback) {
            ActivosAPI.listar(request)
                .then(function(response) { callback(response); })
                .catch(function(error) {
                    callback({ draw: request.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                    Swal.fire('No se pudo cargar el listado', error.message, 'error');
                });
        },
        columns: [
            { data: 'IdMaquina', className: 'text-center' },
            { data: 'Host', render: renderizarTextoActivo },
            {
                data: 'CodigoContabilidad',
                defaultContent: '',
                render: function(data) {
                    return data ? '<span class="font-weight-bold text-primary">' + escHtml(data) + '</span>' : '<span class="text-muted">Sin código</span>';
                }
            },
            { data: 'TipoActivoNombre', render: renderizarTextoActivo, defaultContent: '-' },
            {
                data: 'CategoriaEquipo',
                render: function(data) {
                    if (!data) return '<span class="badge badge-secondary">Sin categoría</span>';
                    var labels = {COMPUTADORAS:'Computadoras', PERIFERICOS:'Periféricos', INFRAESTRUCTURA:'Infraestructura'};
                    var colors = {COMPUTADORAS:'primary', PERIFERICOS:'info', INFRAESTRUCTURA:'dark'};
                    return '<span class="badge badge-' + (colors[data] || 'secondary') + '">' + escHtml(labels[data] || data) + '</span>';
                }
            },
            { data: 'AreaActivoCodigo', render: renderizarTextoActivo, defaultContent: '-' },
            {
                data: 'Criticidad',
                className: 'text-center',
                render: function(data) {
                    if (!data) return '<span class="badge badge-secondary">Pendiente</span>';
                    var color={BAJO:'success',MEDIO:'warning',ALTO:'orange','MUY ALTO':'danger'}[data] || 'secondary';
                    var style=color==='orange'?' style="background:#fd7e14;color:#fff"':'';
                    return '<span class="badge badge-' + (color==='orange'?'':color) + '"' + style + '>' + escHtml(data) + '</span>';
                }
            },
            { data: 'Clasificacion', render: renderizarTextoActivo, defaultContent: '-' },
            { data: 'name', render: renderizarTextoActivo },
            { data: 'Caracteristicas', render: renderizarTextoActivo },
            { 
                data: 'EstadoMaquina',
                className: 'text-center',
                render: function(data) {
                    if (!data) return '<span class="badge badge-secondary">-</span>';
                    var estado = data.toLowerCase();
                    if (estado.includes('activo') || estado.includes('bueno')) {
                        return '<span class="badge badge-success">' + escHtml(data) + '</span>';
                    } else if (estado.includes('malo') || estado.includes('baja')) {
                        return '<span class="badge badge-danger">' + escHtml(data) + '</span>';
                    }
                    return '<span class="badge badge-warning">' + escHtml(data) + '</span>';
                }
            },
            { data: 'Ip', render: renderizarTextoActivo },
            { data: 'Ubicacion', render: renderizarTextoActivo },
            {
                data: null,
                className: 'text-center',
                orderable: false,
                render: function(data) {
                    var html = '<div class="btn-group btn-group-sm">';
                    if (puedeAsignar) {
                        html += '<button type="button" class="btn btn-info btn-asignar"'
                             + ' data-id="' + attrHtml(data.IdMaquina) + '"'
                             + ' data-host="' + attrHtml(data.Host) + '"'
                             + ' data-user-id="' + attrHtml(data.user_id || '') + '"'
                             + ' data-user="' + attrHtml(data.name || '') + '"'
                             + ' data-area="' + attrHtml(data.usuario_area || '') + '"'
                             + ' data-date="' + attrHtml(data.FechaAsignacion || '') + '"'
                             + ' data-area-activo="' + attrHtml(data.IdAreaActivo || '') + '"'
                             + ' data-confidencialidad="' + attrHtml(data.Confidencialidad || '') + '"'
                             + ' data-disponibilidad="' + attrHtml(data.Disponibilidad || '') + '"'
                             + ' data-integridad="' + attrHtml(data.Integridad || '') + '"'
                             + ' data-clasificacion="' + attrHtml(data.Clasificacion || '') + '"'
                             + ' title="' + (data.user_id ? 'Reasignar usuario' : 'Asignar usuario') + '"><i class="fas ' + (data.user_id ? 'fa-user-edit' : 'fa-user-check') + '"></i></button>';
                    }
                    if (puedeEditar) {
                        html += '<button type="button" class="btn btn-success btn-evaluar-activo"'
                             + ' data-id="' + attrHtml(data.IdMaquina) + '" data-host="' + attrHtml(data.Host) + '"'
                             + ' data-c="' + attrHtml(data.Confidencialidad || '') + '" data-d="' + attrHtml(data.Disponibilidad || '') + '"'
                             + ' data-i="' + attrHtml(data.Integridad || '') + '" data-clasificacion="' + attrHtml(data.Clasificacion || '') + '"'
                             + ' title="Evaluar criticidad"><i class="fas fa-chart-line"></i></button>';
                    }
                    if (puedeEditar) {
                        html += '<a href="editar.php?id=' + data.IdMaquina + '" class="btn btn-warning" title="Editar"><i class="fas fa-edit"></i></a>';
                    }
                    if (puedeEliminar) {
                        html += '<button type="button" class="btn btn-danger btn-eliminar-act"'
                             + ' data-id="' + data.IdMaquina + '"'
                             + ' title="Eliminar"><i class="fas fa-trash"></i></button>';
                    }
                    html += '</div>';
                    return html;
                }
            }
        ],
        dom: 'rtip',
        pageLength: 10,
        lengthMenu: [10,20,30,50],
        order: [[0, 'asc']]
    });

    var searchTimer;
    $('#buscarActivos').on('input', function(){ var value=this.value; clearTimeout(searchTimer); searchTimer=setTimeout(function(){tablaActivos.search(value).draw();},300); });
    $('#activosLength').on('change', function(){ tablaActivos.page.len(parseInt(this.value,10)).draw(); });


return tablaActivos;
}
