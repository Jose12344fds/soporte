/**
 * API Module - Centraliza todas las llamadas AJAX
 * Sistema de Soporte TI v2.0
 */

const API = {
    // Base URL para las llamadas API
    baseUrl: '/soporte/src/api',
    
    /**
     * Realizar petición GET
     * @param {string} endpoint - Ruta del endpoint
     * @param {object} params - Parámetros de consulta
     * @returns {Promise}
     */
    get: function(endpoint, params = {}) {
        const url = new URL(this.baseUrl + endpoint, window.location.origin);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        
        return fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(this.handleResponse)
        .catch(this.handleError);
    },
    
    /**
     * Realizar petición POST
     * @param {string} endpoint - Ruta del endpoint
     * @param {object} data - Datos a enviar
     * @returns {Promise}
     */
    post: function(endpoint, data = {}) {
        return fetch(this.baseUrl + endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(this.handleResponse)
        .catch(this.handleError);
    },
    
    /**
     * Realizar petición POST con FormData
     * @param {string} endpoint - Ruta del endpoint
     * @param {FormData} formData - Datos del formulario
     * @returns {Promise}
     */
    postForm: function(endpoint, formData) {
        return fetch(this.baseUrl + endpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(this.handleResponse)
        .catch(this.handleError);
    },
    
    /**
     * Realizar petición PUT
     * @param {string} endpoint - Ruta del endpoint
     * @param {object} data - Datos a enviar
     * @returns {Promise}
     */
    put: function(endpoint, data = {}) {
        return fetch(this.baseUrl + endpoint, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(this.handleResponse)
        .catch(this.handleError);
    },
    
    /**
     * Realizar petición DELETE
     * @param {string} endpoint - Ruta del endpoint
     * @param {object} data - Datos opcionales
     * @returns {Promise}
     */
    delete: function(endpoint, data = {}) {
        return fetch(this.baseUrl + endpoint, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(this.handleResponse)
        .catch(this.handleError);
    },
    
    /**
     * Manejar respuesta de la API
     * @param {Response} response
     * @returns {Promise}
     */
    handleResponse: function(response) {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Error en la solicitud');
            });
        }
        return response.json();
    },
    
    /**
     * Manejar errores
     * @param {Error} error
     */
    handleError: function(error) {
        console.error('API Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Ha ocurrido un error inesperado',
            confirmButtonColor: '#3085d6'
        });
        throw error;
    }
};

// Módulos específicos de la API
API.users = {
    getAll: () => API.get('/users.php'),
    getById: (id) => API.get('/users.php', { id }),
    create: (data) => API.post('/users.php', data),
    update: (id, data) => API.put('/users.php', { id, ...data }),
    delete: (id) => API.delete('/users.php', { id }),
    datatable: () => '/soporte/src/api/users.php?action=datatable'
};

API.activos = {
    getAll: () => API.get('/activos.php'),
    getById: (id) => API.get('/activos.php', { id }),
    create: (data) => API.post('/activos.php', data),
    update: (id, data) => API.put('/activos.php', { id, ...data }),
    delete: (id) => API.delete('/activos.php', { id }),
    datatable: () => '/soporte/src/api/activos.php?action=datatable'
};

API.incidencias = {
    getAll: () => API.get('/incidencias.php'),
    getById: (id) => API.get('/incidencias.php', { id }),
    create: (data) => API.post('/incidencias.php', data),
    update: (id, data) => API.put('/incidencias.php', { id, ...data }),
    delete: (id) => API.delete('/incidencias.php', { id }),
    datatable: () => '/soporte/src/api/incidencias.php?action=datatable'
};

API.mantenimientos = {
    getAll: () => API.get('/mantenimientos.php'),
    getById: (id) => API.get('/mantenimientos.php', { id }),
    create: (data) => API.post('/mantenimientos.php', data),
    update: (id, data) => API.put('/mantenimientos.php', { id, ...data }),
    delete: (id) => API.delete('/mantenimientos.php', { id }),
    datatable: () => '/soporte/src/api/mantenimientos.php?action=datatable'
};

API.licencias = {
    getAll: (tipo) => API.get('/licencias.php', { tipo }),
    getById: (id) => API.get('/licencias.php', { id }),
    create: (data) => API.post('/licencias.php', data),
    update: (id, data) => API.put('/licencias.php', { id, ...data }),
    delete: (id) => API.delete('/licencias.php', { id })
};

API.vpn = {
    getAll: () => API.get('/vpn.php'),
    getById: (id) => API.get('/vpn.php', { id }),
    create: (data) => API.post('/vpn.php', data),
    update: (id, data) => API.put('/vpn.php', { id, ...data }),
    delete: (id) => API.delete('/vpn.php', { id }),
    datatable: () => '/soporte/src/api/vpn.php?action=datatable'
};

// Exportar para uso global
window.API = API;
