/**
 * HH_META - API Client
 * Cliente AJAX centralizado para todas las operaciones del módulo HH_META
 * Reemplaza los múltiples AJAX en hhmeta_cons.php
 */

const HHMetaAPI = {
    baseURL: '../../src/api/hhmeta.php',
    
    /**
     * Obtener datos de una Orden de Trabajo (OT)
     * @param {string} periodo - Número de OT/Período
     * @returns {Promise}
     */
    obtenerOT: async function(periodo) {
        try {
            const formData = new FormData();
            formData.append('action', 'obtener_ot');
            formData.append('periodo', periodo);
            
            const response = await fetch(this.baseURL, { 
                method: 'POST', 
                body: formData 
            });
            
            const data = await response.json();
            if (!data.success) throw new Error(data.message);
            
            return data.data;
        } catch (error) {
            console.error('Error obtenerOT:', error);
            Swal.fire('Error', error.message, 'error');
            throw error;
        }
    },
    
    /**
     * Obtener items de un proyecto
     * @param {string} proyecto - ID del proyecto
     * @returns {Promise}
     */
    obtenerItems: async function(proyecto) {
        try {
            const formData = new FormData();
            formData.append('action', 'obtener_items');
            formData.append('proyecto', proyecto);
            
            const response = await fetch(this.baseURL, { 
                method: 'POST', 
                body: formData 
            });
            
            const data = await response.json();
            if (!data.success) throw new Error(data.message);
            
            return data.data;
        } catch (error) {
            console.error('Error obtenerItems:', error);
            Swal.fire('Error', error.message, 'error');
            throw error;
        }
    },
    
    /**
     * Obtener tabla de datos para DataTable
     * @returns {Promise}
     */
    obtenerTabla: async function() {
        try {
            const formData = new FormData();
            formData.append('action', 'obtener_tabla');
            
            const response = await fetch(this.baseURL, { 
                method: 'POST', 
                body: formData 
            });
            
            const data = await response.json();
            if (!data.success) throw new Error(data.message);
            
            return data.data;
        } catch (error) {
            console.error('Error obtenerTabla:', error);
            Swal.fire('Error', error.message, 'error');
            throw error;
        }
    },
    
    /**
     * Obtener nombres de items
     * @returns {Promise}
     */
    obtenerNombresItem: async function() {
        try {
            const formData = new FormData();
            formData.append('action', 'obtener_nombres_item');
            
            const response = await fetch(this.baseURL, { 
                method: 'POST', 
                body: formData 
            });
            
            const data = await response.json();
            if (!data.success) throw new Error(data.message);
            
            return data.data;
        } catch (error) {
            console.error('Error obtenerNombresItem:', error);
            throw error;
        }
    },
    
    /**
     * Obtener códigos de plano
     * @returns {Promise}
     */
    obtenerCodPlano: async function() {
        try {
            const formData = new FormData();
            formData.append('action', 'obtener_cod_plano');
            
            const response = await fetch(this.baseURL, { 
                method: 'POST', 
                body: formData 
            });
            
            const data = await response.json();
            if (!data.success) throw new Error(data.message);
            
            return data.data;
        } catch (error) {
            console.error('Error obtenerCodPlano:', error);
            throw error;
        }
    },
    
    /**
     * Actualizar estado
     * @param {Object} datos - Datos a actualizar
     * @returns {Promise}
     */
    actualizarEstado: async function(datos) {
        try {
            const formData = new FormData();
            formData.append('action', 'actualizar_estado');
            
            Object.keys(datos).forEach(key => {
                formData.append(key, datos[key]);
            });
            
            const response = await fetch(this.baseURL, { 
                method: 'POST', 
                body: formData 
            });
            
            const data = await response.json();
            if (!data.success) throw new Error(data.message);
            
            Swal.fire('Éxito', data.message, 'success');
            return data.data;
        } catch (error) {
            console.error('Error actualizarEstado:', error);
            Swal.fire('Error', error.message, 'error');
            throw error;
        }
    }
};

/**
 * Ejemplo de uso en hhmeta_cons.php:
 * 
 * // Obtener OT
 * HHMetaAPI.obtenerOT('12345').then(datos => {
 *     console.log('Datos OT:', datos);
 * });
 * 
 * // Obtener items
 * HHMetaAPI.obtenerItems('PROJ001').then(items => {
 *     console.log('Items:', items);
 * });
 * 
 * // Actualizar estado
 * HHMetaAPI.actualizarEstado({ id: 1, estado: 'Completado' }).then(() => {
 *     location.reload();
 * });
 */
