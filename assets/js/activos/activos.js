$(document).ready(function () {
    const config = document.getElementById('activosConfig');
    const table = crearTablaActivos({ editar: config.dataset.editar === '1', eliminar: config.dataset.eliminar === '1' });
    iniciarAsignacionActivos(table);
    iniciarEvaluacionActivos(table);

    $(document).on('click', '.btn-eliminar-act', async function () {
        const button = this;
        if (button.disabled) return;
        const result = await Swal.fire({
            title: 'Eliminar activo?', text: 'Se eliminara el equipo seleccionado.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar'
        });
        if (!result.isConfirmed || button.disabled) return;
        button.disabled = true;
        try {
            const response = await ActivosAPI.eliminar(button.dataset.id);
            table.ajax.reload(null, false);
            Swal.fire('Activo eliminado', response.message, 'success');
        } catch (error) {
            Swal.fire('No se pudo eliminar', error.message, 'error');
        } finally {
            button.disabled = false;
        }
    });
});
