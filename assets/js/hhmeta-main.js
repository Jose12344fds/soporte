/**
 * HH_META - JavaScript Principal
 * Control central de eventos, inicialización y manejo de flujos
 * Integra hhmeta-api.js para todas las operaciones AJAX
 */

class HHMetaUI {
    constructor() {
        this.isLoading = false;
        this.currentOT = null;
        this.currentPeriodo = null;
        this.init();
    }

    /**
     * Inicializar UI y enlazar eventos
     */
    init() {
        this.bindEvents();
        this.setupImageModal();
        console.log('HH_META UI initialized');
    }

    /**
     * Enlazar todos los eventos del formulario
     */
    bindEvents() {
        // Búsqueda de OT
        const searchBtn = document.getElementById('searchOTBtn');
        const periodInput = document.getElementById('periodInput');
        
        if (searchBtn) {
            searchBtn.addEventListener('click', () => this.buscarOT());
        }
        
        if (periodInput) {
            periodInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.buscarOT();
            });
        }

        // Evento al cambiar OT seleccionada
        const otSelect = document.getElementById('selectOT');
        if (otSelect) {
            otSelect.addEventListener('change', (e) => this.onOTChange(e));
        }

        // Botón de guardar cambios
        const saveBtn = document.getElementById('saveChanges');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.guardarCambios());
        }

        // Búsqueda de items
        const itemSearchInput = document.getElementById('itemSearchInput');
        if (itemSearchInput) {
            itemSearchInput.addEventListener('input', (e) => this.filtrarItems(e.target.value));
        }
    }

    /**
     * Buscar OT por período
     */
    async buscarOT() {
        const periodInput = document.getElementById('periodInput');
        const periodo = periodInput.value.trim();

        if (!periodo) {
            Swal.fire('Error', 'Ingrese un período', 'error');
            return;
        }

        this.isLoading = true;
        this.showLoadingSpinner();

        try {
            const datos = await HHMetaAPI.obtenerOT(periodo);
            this.currentPeriodo = periodo;
            this.poblarSelectOT(datos);
            Swal.fire('Éxito', `Se encontraron ${datos.length} órdenes de trabajo`, 'success');
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        } finally {
            this.isLoading = false;
            this.hideLoadingSpinner();
        }
    }

    /**
     * Poblar selector de OT
     */
    poblarSelectOT(datos) {
        const otSelect = document.getElementById('selectOT');
        if (!otSelect) return;

        otSelect.innerHTML = '<option value="">Seleccionar OT...</option>';
        
        datos.forEach(ot => {
            const option = document.createElement('option');
            option.value = ot.id || ot.OT_ID;
            option.textContent = `${ot.OT || ot.OT_NUM} - ${ot.descripcion || ot.DESC}`;
            otSelect.appendChild(option);
        });
    }

    /**
     * Evento al cambiar OT seleccionada
     */
    async onOTChange(event) {
        const otId = event.target.value;
        
        if (!otId) {
            document.getElementById('itemsContainer').innerHTML = '';
            return;
        }

        this.currentOT = otId;
        this.showLoadingSpinner();

        try {
            const items = await HHMetaAPI.obtenerItems(this.currentPeriodo);
            this.mostrarItems(items);
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        } finally {
            this.hideLoadingSpinner();
        }
    }

    /**
     * Mostrar items en tabla
     */
    mostrarItems(datos) {
        const container = document.getElementById('itemsContainer');
        if (!container) return;

        if (!datos || datos.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No hay items para mostrar</div>';
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Horas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        datos.forEach((item, index) => {
            const estado = item.estado || item.ESTADO || 'Pendiente';
            const badgeClass = estado === 'Completado' ? 'badge-success' : 'badge-warning';
            
            html += `
                <tr data-item-id="${item.id || index}">
                    <td>${item.item_num || item.ITEM_NUM || ''}</td>
                    <td class="text-truncate">${item.descripcion || item.DESC || ''}</td>
                    <td><span class="badge ${badgeClass}">${estado}</span></td>
                    <td><input type="number" class="form-control form-control-sm" value="${item.horas || 0}" min="0" step="0.5"></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="hhmeta.editarItem(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="hhmeta.eliminarItem(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;

        // Inicializar DataTable si está disponible
        if ($.fn.dataTable) {
            if ($.fn.dataTable.isDataTable('#itemsTable')) {
                $('#itemsTable').DataTable().destroy();
            }
            $('#itemsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
                },
                pageLength: 10,
                responsive: true
            });
        }
    }

    /**
     * Filtrar items por búsqueda
     */
    filtrarItems(termino) {
        const filas = document.querySelectorAll('#itemsTable tbody tr');
        termino = termino.toLowerCase();

        filas.forEach(fila => {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(termino) ? '' : 'none';
        });
    }

    /**
     * Editar item (actualizar horas, estado, etc)
     */
    async editarItem(button) {
        const fila = button.closest('tr');
        const itemId = fila.dataset.itemId;
        const horasInput = fila.querySelector('input[type="number"]');
        const horas = horasInput.value;

        if (!horas || horas < 0) {
            Swal.fire('Error', 'Ingrese un valor válido de horas', 'error');
            return;
        }

        this.showLoadingSpinner();

        try {
            const resultado = await HHMetaAPI.actualizarEstado({
                item_id: itemId,
                horas: horas,
                ot_id: this.currentOT
            });

            Swal.fire('Éxito', 'Item actualizado correctamente', 'success');
            // Recargar items
            await this.onOTChange({ target: { value: this.currentOT } });
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        } finally {
            this.hideLoadingSpinner();
        }
    }

    /**
     * Eliminar item
     */
    async eliminarItem(button) {
        const resultado = await Swal.fire({
            title: 'Confirmar',
            text: '¿Desea eliminar este item?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (!resultado.isConfirmed) return;

        const fila = button.closest('tr');
        fila.remove();

        Swal.fire('Eliminado', 'Item eliminado correctamente', 'success');
    }

    /**
     * Guardar todos los cambios
     */
    async guardarCambios() {
        if (!this.currentOT) {
            Swal.fire('Error', 'Seleccione una OT primero', 'error');
            return;
        }

        this.showLoadingSpinner();

        try {
            const resultado = await HHMetaAPI.actualizarEstado({
                ot_id: this.currentOT,
                save_all: true
            });

            Swal.fire('Éxito', 'Cambios guardados correctamente', 'success');
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        } finally {
            this.hideLoadingSpinner();
        }
    }

    /**
     * Configurar modal de imagen
     */
    setupImageModal() {
        const showBtn = document.getElementById('showImageBtn');
        const popup = document.getElementById('imagePopup');
        const closeBtn = document.querySelector('.close-popup');
        const zoomImg = document.getElementById('zoomableImage');

        if (!showBtn || !popup) return;

        // Abrir modal
        showBtn.addEventListener('click', () => {
            popup.classList.add('active');
        });

        // Cerrar modal
        closeBtn?.addEventListener('click', () => {
            popup.classList.remove('active');
        });

        popup.addEventListener('click', (e) => {
            if (e.target === popup) {
                popup.classList.remove('active');
            }
        });

        // Controles de zoom
        document.getElementById('zoomIn')?.addEventListener('click', () => {
            const scale = parseFloat(zoomImg.dataset.scale || 1) + 0.2;
            zoomImg.dataset.scale = scale;
            zoomImg.style.transform = `scale(${scale})`;
        });

        document.getElementById('zoomOut')?.addEventListener('click', () => {
            const scale = Math.max(0.5, parseFloat(zoomImg.dataset.scale || 1) - 0.2);
            zoomImg.dataset.scale = scale;
            zoomImg.style.transform = `scale(${scale})`;
        });

        document.getElementById('zoomReset')?.addEventListener('click', () => {
            zoomImg.dataset.scale = 1;
            zoomImg.style.transform = 'scale(1)';
        });
    }

    /**
     * Mostrar spinner de carga
     */
    showLoadingSpinner() {
        let spinner = document.getElementById('loadingSpinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.id = 'loadingSpinner';
            spinner.innerHTML = `
                <div class="d-flex justify-content-center align-items-center" style="height: 100px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <span class="ms-2">Cargando datos...</span>
                </div>
            `;
            document.getElementById('itemsContainer')?.appendChild(spinner);
        }
        spinner.style.display = 'block';
    }

    /**
     * Ocultar spinner de carga
     */
    hideLoadingSpinner() {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) spinner.style.display = 'none';
    }
}

// Instanciar al cargar el documento
let hhmeta;
document.addEventListener('DOMContentLoaded', () => {
    hhmeta = new HHMetaUI();
});

// Exportar para uso en scripts externos si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HHMetaUI;
}
