(function (window) {
    'use strict';

    const client = window.axios.create({
        baseURL: window.AppConfig.baseUrl + '/',
        timeout: 30000,
        responseType: 'json',
        headers: { Accept: 'application/json' },
        transitional: { silentJSONParsing: false }
    });

    client.interceptors.request.use(function (request) {
        const method = (request.method || 'get').toLowerCase();
        const target = new URL(client.getUri(request), window.location.origin);
        if (!['get', 'head', 'options'].includes(method) && target.origin === window.location.origin) {
            const token = window.AppConfig.csrfToken();
            if (token) {
                request.headers.set('X-CSRF-Token', token);
            }
        }
        return request;
    });

    client.interceptors.response.use(function (response) {
        const payload = response.data;
        if (payload && payload.success === false) {
            throw new Error(payload.error || payload.message || 'No se pudo completar la operacion.');
        }
        return payload;
    }, function (error) {
        const payload = error.response && error.response.data;
        if (payload && typeof payload === 'object' && (payload.error || payload.message)) {
            error.message = payload.error || payload.message;
        } else if (error.code === 'ECONNABORTED') {
            error.message = 'La solicitud excedio el tiempo de espera.';
        } else {
            error.message = 'No se pudo obtener una respuesta valida del servidor.';
        }
        return Promise.reject(error);
    });

    window.AppApi = client;
})(window);
