(function (window, document) {
    'use strict';

    const baseElement = document.querySelector('meta[name="app-base-url"]');

    window.AppConfig = Object.freeze({
        baseUrl: baseElement ? baseElement.content.replace(/\/+$/, '') : '',
        csrfToken: function () {
            const element = document.querySelector('meta[name="csrf-token"]');
            return element ? element.content : '';
        }
    });
})(window, document);
