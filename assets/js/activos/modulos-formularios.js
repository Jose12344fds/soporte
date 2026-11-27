(function () {
    'use strict';
    const meta = document.querySelector('meta[name="app-base-url"]');
    const base = (meta ? meta.content : '').replace(/\/$/, '');
    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('form[data-activos-api]');
        if (!form) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (form.dataset.sending === '1' || !form.reportValidity()) return;
        form.dataset.sending = '1';
        const buttons = Array.from(form.querySelectorAll('button,input[type="submit"]'));
        const states = buttons.map(button => button.disabled);
        let error = form.querySelector('[data-api-error]');
        if (!error) {
            error = document.createElement('div');
            error.className = 'alert alert-danger';
            error.setAttribute('data-api-error', '');
            error.setAttribute('role', 'alert');
            (form.querySelector('.modal-body') || form).prepend(error);
        }
        error.hidden = true;
        try {
            // Construir el cuerpo antes de deshabilitar controles.
            const body = new FormData(form);
            buttons.forEach(button => { button.disabled = true; });
            const token = document.querySelector('meta[name="csrf-token"]');
            const response = await fetch(base + '/api/activos/' + form.dataset.activosApi + '/', {
                method: 'POST', credentials: 'same-origin', body,
                headers: {Accept: 'application/json', 'X-CSRF-Token': token ? token.content : ''}
            });
            const result = await response.json();
            if (!response.ok || result.success === false) throw new Error(result.error || 'No se pudo guardar.');
            const action = body.get('action');
            if (action === 'save_record') location.hash = 'tabRegistros';
            if (action === 'save_role') location.hash = 'tabRoles';
            if (action === 'assign_machines') location.hash = 'tabEquipos';
            if (action === 'save_service_contract') location.search = '?tab=contratos';
            else location.reload();
        } catch (failure) {
            error.textContent = failure.message || 'No se pudo guardar. Intente nuevamente.';
            error.hidden = false;
            buttons.forEach((button, index) => { button.disabled = states[index]; });
            form.dataset.sending = '0';
        }
    }, true);
})();
