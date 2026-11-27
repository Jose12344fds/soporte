(function (window) {
    'use strict';
    const base = 'api/activos/equipos/';
    window.ActivosAPI = {
        listar: params => AppApi.get(base, { params: params }),
        obtener: id => AppApi.get(base, { params: { id: id } }),
        catalogos: id => AppApi.get(base + 'catalogos.php', { params: { id: id || 0 } }),
        registrar: values => AppApi.post(base, values),
        actualizar: values => AppApi.put(base, values),
        eliminar: id => AppApi.delete(base, { data: { id: id } }),
        asignar: values => AppApi.post(base + 'asignar.php', values),
        historial: id => AppApi.get(base + 'historial.php', { params: { id: id } }),
        evaluar: values => AppApi.post(base + 'guardar_evaluacion.php', values)
    };
})(window);

function activosOpcionesAsignacion(catalogs) {
    const users = document.getElementById('selUsuario');
    users.replaceChildren(new Option('Seleccionar usuario', ''), new Option('LIBRE / SIN USUARIO', 'LIBRE'));
    catalogs.usuarios.forEach(function (user) {
        const option = new Option(user.name + (user.area ? ' - ' + user.area : ''), user.id);
        option.dataset.area = user.area || '';
        option.dataset.subarea = user.subarea || '';
        users.add(option);
    });
    const areas = document.getElementById('asignarAreaActivo');
    areas.replaceChildren(new Option('Seleccionar...', ''));
    catalogs.areas.forEach(function (area) {
        areas.add(new Option(area.Codigo + ' - ' + area.Nombre, area.IdAreaActivo));
    });
}
