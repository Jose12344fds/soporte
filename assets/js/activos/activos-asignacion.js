function renderAssignmentHistory(rows) {
    var container = document.getElementById('asignarHistorial');
    if (!Array.isArray(rows) || !rows.length) {
        container.innerHTML = '<div class="text-center text-muted py-2">Aun no hay movimientos registrados.</div>';
        return;
    }
    container.innerHTML = rows.map(function(row) {
        var from = row.usuario_anterior || 'Sin asignar';
        var to = row.usuario_nuevo || 'Sin asignar';
        return '<div class="history-item">'
            + '<div><span class="badge badge-light border mr-1">' + escHtml(row.accion || '') + '</span>'
            + '<strong>' + escHtml(from) + '</strong> <i class="fas fa-arrow-right mx-1 text-muted"></i> <strong>' + escHtml(to) + '</strong></div>'
            + '<div class="text-muted">' + escHtml(row.fecha_asignacion || '')
            + (row.comentarios ? ' · ' + escHtml(row.comentarios) : '')
            + (row.registrado_por_nombre ? ' · Por ' + escHtml(row.registrado_por_nombre) : '') + '</div>'
            + '</div>';
    }).join('');
}

function loadAssignmentDetails(id) {
    document.getElementById('asignarHistorial').innerHTML = '<div class="text-center text-muted py-2"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando...</div>';
    ActivosAPI.historial(id)
        .then(function(result) {
            if (String(document.getElementById('asignarMaquinaId').value) !== String(id)) return;
            var current = result.current;
            document.getElementById('asignarUsuarioActualId').value = current ? (current.user_id || '') : '';
            document.getElementById('asignarUsuarioActual').textContent = current ? (current.usuario || 'Usuario no encontrado') : 'Sin asignar';
            document.getElementById('asignarAreaActual').textContent = current ? ([current.area, current.subarea].filter(Boolean).join(' / ') || 'Sin area') : 'Equipo disponible';
            document.getElementById('asignarFechaActual').textContent = current && current.Fecha ? ' · Desde ' + current.Fecha : '';
            renderAssignmentHistory(result.history);
        })
        .catch(function(error) {
            if (String(document.getElementById('asignarMaquinaId').value) !== String(id)) return;
            document.getElementById('asignarHistorial').innerHTML = '<div class="text-danger small">' + escHtml(error.message) + '</div>';
        });
}


function iniciarAsignacionActivos(tablaActivos){
    // Select2 para modal asignar
    var assignmentSelect = $('#selUsuario');
    if (assignmentSelect.hasClass('select2-hidden-accessible')) assignmentSelect.select2('destroy');
    assignmentSelect.nextAll('.select2-container').remove();
    assignmentSelect.select2({
        dropdownParent: $('#modalAsignar'),
        width: '100%',
        theme: 'bootstrap4',
        placeholder: 'Buscar usuario activo...'
    });

    assignmentSelect.on('change', function() {
        var option = this.options[this.selectedIndex];
        var preview = document.getElementById('selectedUserPreview');
        if (!this.value || !option) {
            preview.style.display = 'none';
            preview.textContent = '';
            return;
        }
        if (this.value === 'LIBRE') {
            preview.textContent = 'El equipo quedara libre y sin usuario asignado.';
            preview.style.display = 'block';
            return;
        }
        var area = option.getAttribute('data-area') || 'Sin area';
        var subarea = option.getAttribute('data-subarea') || '';
        preview.textContent = 'Se asignara a: ' + option.text.trim() + (subarea ? ' / ' + subarea : '');
        preview.style.display = 'block';
    });

    $('#asignarComentarios').on('input', function() {
        document.getElementById('asignarComentarioCount').textContent = this.value.length;
    });

    function actualizarCriticidadAsignar() {
        var valores = $('.asignar-puntaje').map(function(){ return parseInt(this.value, 10) || 0; }).get();
        var maximo = Math.max.apply(null, valores);
        $('#asignarCriticidad').val(({1:'BAJO',2:'MEDIO',3:'ALTO',4:'MUY ALTO'})[maximo] || 'SIN EVALUAR');
    }

    $('#modalAsignar').on('click', '.asignar-score', function() {
        var factor = $(this).closest('.asignar-factor');
        factor.find('.asignar-score').removeClass('active');
        $(this).addClass('active');
        factor.find('.asignar-puntaje').val($(this).data('value'));
        actualizarCriticidadAsignar();
    });

    function cargarValoracionAsignar(button) {
        $('#asignarAreaActivo').val(String(button.attr('data-area-activo') || ''));
        $('#asignarClasificacion').val(String(button.attr('data-clasificacion') || '').toUpperCase());
        ['Confidencialidad','Disponibilidad','Integridad'].forEach(function(nombre) {
            var valor = String(button.attr('data-' + nombre.toLowerCase()) || '');
            var factor = $('.asignar-factor[data-factor="' + nombre + '"]');
            factor.find('.asignar-puntaje').val(valor);
            factor.find('.asignar-score').removeClass('active').filter('[data-value="' + valor + '"]').addClass('active');
        });
        actualizarCriticidadAsignar();
    }

    // Abrir modal asignar
    var aperturaActual=0;
    $(document).on('click', '.btn-asignar', async function() {
        var apertura=++aperturaActual;
        var catalogos;
        try { catalogos=(await ActivosAPI.catalogos()).data; } catch(error) { Swal.fire('No se pudo abrir la asignacion',error.message,'error'); return; }
        if(apertura!==aperturaActual)return;
        activosOpcionesAsignacion(catalogos);
        var button = $(this);
        var id   = button.data('id');
        var host = $(this).data('host');
        $('#asignarMaquinaId').val(id);
        $('#asignarHost').val(host);
        $('#asignarHostLabel').text(host);
        $('#asignarIdLabel').text(id);
        $('#asignarUsuarioActualId').val($(this).data('user-id') || '');
        $('#asignarUsuarioActual').text($(this).data('user') || 'Sin asignar');
        $('#asignarAreaActual').text($(this).data('area') || 'Equipo disponible');
        $('#asignarFechaActual').text($(this).data('date') ? ' · Desde ' + $(this).data('date') : '');
        $('#selUsuario').val('').trigger('change');
        $('#asignarComentarios').val('');
        $('#asignarComentarioCount').text('0');
        var now = new Date();
        var localDate = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
        $('#asignarFecha').val(localDate);
        cargarValoracionAsignar(button);
        document.getElementById('formAsignarEquipo').removeAttribute('data-confirmed');
        $('#modalAsignar').modal('show');
        loadAssignmentDetails(id);
    });

    $('#formAsignarEquipo').on('submit', async function(event) {
        event.preventDefault();
        var form = this;
        if (form.dataset.busy === '1' || !form.reportValidity()) return;
        if ($('.asignar-puntaje').filter(function(){return !this.value;}).length) {
            Swal.fire('Valoracion incompleta', 'Seleccione los tres puntajes.', 'warning');
            return;
        }
        var selected = String($('#selUsuario').val() || '');
        var current = String($('#asignarUsuarioActualId').val() || '');
        if (selected === 'LIBRE' || (current && selected !== current)) {
            var result = await Swal.fire({
                title: selected === 'LIBRE' ? 'Confirmar liberacion?' : 'Confirmar reasignacion?',
                text: 'Se actualizara el responsable del equipo.',
                icon: 'question', showCancelButton: true,
                confirmButtonText: 'Confirmar', cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;
        }
        if (form.dataset.busy === '1') return;
        var button = form.querySelector('[type="submit"]');
        form.dataset.busy = '1';
        button.disabled = true;
        try {
            var response = await ActivosAPI.asignar(Object.fromEntries(new FormData(form)));
            $('#modalAsignar').modal('hide');
            tablaActivos.ajax.reload(null, false);
            Swal.fire('Asignacion guardada', response.message, 'success');
        } catch (error) {
            Swal.fire('No se pudo guardar', error.message, 'error');
        } finally {
            delete form.dataset.busy;
            button.disabled = false;
        }
    });
}
