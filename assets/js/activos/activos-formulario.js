$(document).ready(async function () {
    const form = document.querySelector('form[data-activo]');
    if (!form) return;
    const fieldset = form.querySelector('fieldset');
    const edit = form.dataset.activo === 'actualizar';
    const id = Number(form.dataset.id || 0);

    function fillSelect(name, rows, key, label) {
        const select = form.elements.namedItem(name);
        select.replaceChildren(new Option('Seleccionar...', ''));
        rows.forEach(function (row) {
            const option = new Option(label(row), row[key]);
            if (name === 'IdTipoActivo') {
                option.dataset.categoria = row.CategoriaPredeterminada;
            }
            select.add(option);
        });
    }

    function refreshScore() {
        const values = Array.from(form.querySelectorAll('.puntaje-seguridad')).map(input => Number(input.value || 0));
        const level = ({ 1: 'BAJO', 2: 'MEDIO', 3: 'ALTO', 4: 'MUY ALTO' })[Math.max(...values)] || 'Pendiente';
        form.querySelector('#Criticidad').value = level;
        form.querySelectorAll('.grupo-puntaje').forEach(function (group) {
            const value = group.parentElement.querySelector('.puntaje-seguridad').value;
            group.querySelectorAll('[data-value]').forEach(button => button.classList.toggle('active', button.dataset.value === value));
        });
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (form.dataset.busy === '1' || fieldset.disabled || !form.reportValidity()) return;
        const values = Object.fromEntries(new FormData(form));
        form.dataset.busy = '1';
        fieldset.disabled = true;
        try {
            const response = edit ? await ActivosAPI.actualizar(values) : await ActivosAPI.registrar(values);
            await Swal.fire('Guardado', response.message, 'success');
            window.location.assign(AppConfig.baseUrl + '/modules/activos/index.php');
        } catch (error) {
            Swal.fire('No se pudo guardar', error.message, 'error');
        } finally {
            delete form.dataset.busy;
            fieldset.disabled = false;
        }
    });

    try {
        const responses = await Promise.all([ActivosAPI.catalogos(id), edit ? ActivosAPI.obtener(id) : Promise.resolve({ data: null })]);
        const catalogs = responses[0].data;
        const current = responses[1].data;
        fillSelect('IdTipoActivo', catalogs.tipos, 'IdTipoActivo', row => row.Nombre);
        fillSelect('IdAreaActivo', catalogs.areas, 'IdAreaActivo', row => row.Codigo + ' - ' + row.Nombre);
        ['office', 'windows'].forEach(function (name) {
            fillSelect(name, catalogs[name], 'Serial', row => row.Nombre + ' - ' + row.Serial);
        });
        if (current) {
            Object.keys(current).forEach(function (name) {
                const input = form.elements.namedItem(name);
                if (!input || current[name] === null) return;
                const value = String(current[name]);
                if (input.tagName === 'SELECT' && value && !Array.from(input.options).some(option => option.value === value)) {
                    input.add(new Option('(Valor actual) ' + value, value));
                }
                input.value = value;
            });
        }
        $('.select2').select2({ allowClear: true, width: '100%', placeholder: 'Seleccionar licencia' });
        $('.select2-area').select2({ width: '100%', placeholder: 'Seleccionar area' });
        $('#Tipo_Activo').on('change', function () {
            const option = this.options[this.selectedIndex];
            if (option && option.dataset.categoria) $('#CategoriaEquipo').val(option.dataset.categoria).trigger('change');
        });
        $('.puntaje-seguridad').on('change', refreshScore);
        $('.grupo-puntaje .btn').on('click', function () {
            this.closest('.form-group').querySelector('.puntaje-seguridad').value = this.dataset.value;
            refreshScore();
        });
        refreshScore();
        fieldset.disabled = false;
    } catch (error) {
        const alert = form.querySelector('[data-form-error]');
        alert.textContent = error.message;
        alert.classList.remove('d-none');
    }
});
