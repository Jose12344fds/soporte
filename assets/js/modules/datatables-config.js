/**
 * DataTables Configuration Module
 * Configuración centralizada para DataTables con AdminLTE
 */

const DataTablesConfig = {
    // Configuración por defecto en español
    language: {
        "decimal": "",
        "emptyTable": "No hay datos disponibles",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
        "infoEmpty": "Mostrando 0 a 0 de 0 registros",
        "infoFiltered": "(filtrado de _MAX_ registros totales)",
        "infoPostFix": "",
        "thousands": ",",
        "lengthMenu": "Mostrar _MENU_ registros",
        "loadingRecords": "Cargando...",
        "processing": "<div class='spinner-border text-primary' role='status'><span class='sr-only'>Cargando...</span></div>",
        "search": "Buscar:",
        "zeroRecords": "No se encontraron registros",
        "paginate": {
            "first": "<i class='fas fa-angle-double-left'></i>",
            "last": "<i class='fas fa-angle-double-right'></i>",
            "next": "<i class='fas fa-angle-right'></i>",
            "previous": "<i class='fas fa-angle-left'></i>"
        },
        "aria": {
            "sortAscending": ": activar para ordenar ascendente",
            "sortDescending": ": activar para ordenar descendente"
        },
        "buttons": {
            "copy": "Copiar",
            "csv": "CSV",
            "excel": "Excel",
            "pdf": "PDF",
            "print": "Imprimir",
            "colvis": "Columnas"
        }
    },
    
    // Opciones por defecto
    defaults: {
        responsive: true,
        autoWidth: false,
        processing: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        stateSave: true,
        stateDuration: 60 * 60 * 24 // 24 horas
    },
    
    // Opciones con botones de exportación
    withExport: {
        dom: '<"row"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"B><"col-sm-12 col-md-4"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            {
                extend: 'copy',
                className: 'btn btn-default btn-sm',
                exportOptions: { columns: ':visible:not(.no-export)' }
            },
            {
                extend: 'excel',
                className: 'btn btn-success btn-sm',
                exportOptions: { columns: ':visible:not(.no-export)' }
            },
            {
                extend: 'pdf',
                className: 'btn btn-danger btn-sm',
                exportOptions: { columns: ':visible:not(.no-export)' }
            },
            {
                extend: 'print',
                className: 'btn btn-info btn-sm',
                exportOptions: { columns: ':visible:not(.no-export)' }
            }
        ]
    },
    
    /**
     * Inicializar una tabla con configuración por defecto
     * @param {string} selector - Selector de la tabla
     * @param {object} options - Opciones adicionales
     * @returns {DataTable}
     */
    init: function(selector, options = {}) {
        const config = {
            ...this.defaults,
            language: this.language,
            ...options
        };
        return $(selector).DataTable(config);
    },
    
    /**
     * Inicializar tabla con Ajax
     * @param {string} selector - Selector de la tabla
     * @param {string} ajaxUrl - URL del endpoint AJAX
     * @param {array} columns - Definición de columnas
     * @param {object} options - Opciones adicionales
     * @returns {DataTable}
     */
    initAjax: function(selector, ajaxUrl, columns, options = {}) {
        const config = {
            ...this.defaults,
            language: this.language,
            serverSide: true,
            ajax: {
                url: ajaxUrl,
                type: 'POST',
                data: function(d) {
                    return $.extend({}, d, options.extraData || {});
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al cargar los datos'
                    });
                }
            },
            columns: columns,
            ...options
        };
        return $(selector).DataTable(config);
    },
    
    /**
     * Inicializar tabla con exportación
     * @param {string} selector - Selector de la tabla
     * @param {object} options - Opciones adicionales
     * @returns {DataTable}
     */
    initWithExport: function(selector, options = {}) {
        const config = {
            ...this.defaults,
            ...this.withExport,
            language: this.language,
            ...options
        };
        return $(selector).DataTable(config);
    },
    
    /**
     * Renderizar botones de acción
     * @param {number|string} id - ID del registro
     * @param {object} options - Opciones de botones
     * @returns {string}
     */
    renderActions: function(id, options = {}) {
        const defaults = {
            view: true,
            edit: true,
            delete: true,
            viewUrl: null,
            editUrl: null,
            viewHandler: null,
            editHandler: null,
            deleteHandler: 'deleteRecord'
        };
        
        const opts = { ...defaults, ...options };
        let buttons = '<div class="btn-group btn-group-sm">';
        
        if (opts.view) {
            if (opts.viewUrl) {
                buttons += `<a href="${opts.viewUrl}?id=${id}" class="btn btn-info" title="Ver"><i class="fas fa-eye"></i></a>`;
            } else if (opts.viewHandler) {
                buttons += `<button type="button" class="btn btn-info" onclick="${opts.viewHandler}(${id})" title="Ver"><i class="fas fa-eye"></i></button>`;
            }
        }
        
        if (opts.edit) {
            if (opts.editUrl) {
                buttons += `<a href="${opts.editUrl}?id=${id}" class="btn btn-warning" title="Editar"><i class="fas fa-edit"></i></a>`;
            } else if (opts.editHandler) {
                buttons += `<button type="button" class="btn btn-warning" onclick="${opts.editHandler}(${id})" title="Editar"><i class="fas fa-edit"></i></button>`;
            }
        }
        
        if (opts.delete) {
            buttons += `<button type="button" class="btn btn-danger" onclick="${opts.deleteHandler}(${id})" title="Eliminar"><i class="fas fa-trash"></i></button>`;
        }
        
        buttons += '</div>';
        return buttons;
    },
    
    /**
     * Renderizar badge de estado
     * @param {string} status - Estado
     * @param {object} statusMap - Mapeo de estados a clases
     * @returns {string}
     */
    renderStatus: function(status, statusMap = null) {
        const defaultMap = {
            'activo': 'success',
            'inactivo': 'danger',
            'pendiente': 'warning',
            'completado': 'success',
            'en_proceso': 'info',
            'cancelado': 'secondary',
            '1': 'success',
            '0': 'danger'
        };
        
        const map = statusMap || defaultMap;
        const statusLower = String(status).toLowerCase();
        const badgeClass = map[statusLower] || 'secondary';
        const label = statusLower === '1' ? 'Activo' : (statusLower === '0' ? 'Inactivo' : status);
        
        return `<span class="badge badge-${badgeClass}">${label}</span>`;
    }
};

// Exportar para uso global
window.DataTablesConfig = DataTablesConfig;
