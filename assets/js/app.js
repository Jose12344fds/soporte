/**
 * Main Application JavaScript
 * Sistema de Soporte TI v2.0
 */

const App = {
    /**
     * Inicializar la aplicación
     */
    init: function() {
        this.initTooltips();
        this.initPopovers();
        this.initDeleteConfirmation();
        this.initFormValidation();
        this.hidePreloader();
    },
    
    /**
     * Inicializar tooltips de Bootstrap
     */
    initTooltips: function() {
        $('[data-toggle="tooltip"]').tooltip();
    },
    
    /**
     * Inicializar popovers de Bootstrap
     */
    initPopovers: function() {
        $('[data-toggle="popover"]').popover();
    },
    
    /**
     * Inicializar confirmación de eliminación global
     */
    initDeleteConfirmation: function() {
        $(document).on('click', '.btn-delete, [data-delete]', function(e) {
            e.preventDefault();
            const url = $(this).attr('href') || $(this).data('url');
            const name = $(this).data('name') || 'este registro';
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Deseas eliminar ${name}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed && url) {
                    window.location.href = url;
                }
            });
        });
    },
    
    /**
     * Inicializar validación de formularios
     */
    initFormValidation: function() {
        $('form[data-validate]').on('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            $(this).addClass('was-validated');
        });
    },
    
    /**
     * Ocultar preloader
     */
    hidePreloader: function() {
        setTimeout(function() {
            $('.preloader').fadeOut('slow');
        }, 500);
    },
    
    /**
     * Mostrar notificación toast
     * @param {string} message - Mensaje
     * @param {string} type - Tipo (success, error, warning, info)
     */
    toast: function(message, type = 'success') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        
        Toast.fire({
            icon: type,
            title: message
        });
    },
    
    /**
     * Mostrar alerta
     * @param {string} title - Título
     * @param {string} message - Mensaje
     * @param {string} type - Tipo (success, error, warning, info)
     */
    alert: function(title, message, type = 'info') {
        Swal.fire({
            title: title,
            text: message,
            icon: type,
            confirmButtonColor: '#3085d6'
        });
    },
    
    /**
     * Confirmar acción
     * @param {string} title - Título
     * @param {string} message - Mensaje
     * @param {function} callback - Callback si confirma
     */
    confirm: function(title, message, callback) {
        Swal.fire({
            title: title,
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed && typeof callback === 'function') {
                callback();
            }
        });
    },
    
    /**
     * Formatear fecha
     * @param {string} date - Fecha
     * @param {string} format - Formato
     * @returns {string}
     */
    formatDate: function(date, format = 'DD/MM/YYYY') {
        if (!date) return '-';
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        
        return format
            .replace('DD', day)
            .replace('MM', month)
            .replace('YYYY', year);
    },
    
    /**
     * Formatear moneda
     * @param {number} amount - Cantidad
     * @param {string} currency - Moneda
     * @returns {string}
     */
    formatCurrency: function(amount, currency = 'PEN') {
        return new Intl.NumberFormat('es-PE', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },
    
    /**
     * Cargar contenido vía AJAX en un modal
     * @param {string} url - URL a cargar
     * @param {string} modalId - ID del modal
     */
    loadModal: function(url, modalId = '#ajax-modal') {
        $(modalId + ' .modal-body').html(
            '<div class="text-center p-4"><div class="spinner-border" role="status"></div></div>'
        );
        $(modalId).modal('show');
        
        $.get(url)
            .done(function(response) {
                $(modalId + ' .modal-body').html(response);
            })
            .fail(function() {
                $(modalId + ' .modal-body').html(
                    '<div class="alert alert-danger">Error al cargar el contenido</div>'
                );
            });
    },
    
    /**
     * Refrescar tabla DataTables
     * @param {string} tableId - ID de la tabla
     */
    refreshTable: function(tableId) {
        const table = $(tableId).DataTable();
        table.ajax.reload(null, false);
    }
};

// Función global para eliminar registros
function deleteRecord(id, endpoint, tableName = '#dataTable') {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            API.delete(endpoint, { id: id })
                .then(response => {
                    App.toast('Registro eliminado correctamente', 'success');
                    App.refreshTable(tableName);
                })
                .catch(error => {
                    App.alert('Error', 'No se pudo eliminar el registro', 'error');
                });
        }
    });
}

// Inicializar cuando el DOM esté listo
$(document).ready(function() {
    App.init();
});

// Exportar para uso global
window.App = App;
