(function ($) {
    'use strict';

    var state = { equipment: [], correctiveEquipment: [], report: null, charts: {}, initialized: false };
    var monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function apiUrl(params) {
        var base = $('#btnDisponibilidadCriticos').data('api');
        return base + '?' + new URLSearchParams(params).toString();
    }

    function dateText(value) {
        if (!value) return '-';
        var parts = String(value).slice(0, 10).split('-');
        return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : value;
    }

    function numberText(value) {
        return Number(value || 0).toLocaleString('es-PE', { maximumFractionDigits: 2 });
    }

    function percentText(value) {
        return Number(value || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
    }

    function selectedEquipment() {
        var value = $('#dispEquipo').val().trim();
        var requestedCode = value.split('|')[0].trim().toUpperCase();
        var exact = state.equipment.find(function (row) { return row.code.toUpperCase() === requestedCode; });
        if (exact) return exact;
        var normalized = value.toLowerCase();
        var matches = state.equipment.filter(function (row) {
            return row.code.toLowerCase() === normalized || row.name.toLowerCase() === normalized;
        });
        return matches.length === 1 ? matches[0] : null;
    }

    function renderSelection() {
        var equipment = selectedEquipment();
        if (!equipment) {
            $('#dispSeleccion').html('<span class="text-muted">Ningun equipo</span>');
            return;
        }
        var level = equipment.criticality || 'SIN EVALUAR';
        var css = ['ALTO', 'MEDIO', 'BAJO'].indexOf(level) >= 0 ? level : '';
        $('#dispSeleccion').html('<strong>' + esc(equipment.code) + '</strong><span class="disp-crit ' + css + '">' + esc(level) + '</span>');
    }

    function renderQuickEquipment() {
        var rows = state.correctiveEquipment;
        $('#dispQuickCount').text(rows.length);
        if (!rows.length) {
            $('#dispQuickGrid').html('<div class="disp-quick-empty" style="grid-column:1/-1"><i class="fas fa-circle-check mr-1 text-success"></i>No hay equipos con OT correctivas en las semanas de este mes.</div>');
            return;
        }
        $('#dispQuickGrid').html(rows.map(function (row) {
            var level = row.criticality || 'SIN EVALUAR';
            var css = ['ALTO', 'MEDIO', 'BAJO'].indexOf(level) >= 0 ? level : '';
            return '<button type="button" class="disp-quick-item" data-disp-equipment="' + esc(row.code) + '"><span><strong>' + esc(row.code) + '<span class="disp-crit ' + css + '">' + esc(level) + '</span></strong><small>' + esc(row.name) + '</small></span><span class="badge badge-danger">' + row.orders + ' OT</span></button>';
        }).join(''));
    }

    function populateMeta(data, preferredMonth) {
        var currentYear = String(data.anio);
        $('#dispAnio').html((data.meta.years || []).map(function (year) {
            return '<option value="' + year + '"' + (String(year) === currentYear ? ' selected' : '') + '>' + year + '</option>';
        }).join(''));

        var months = data.meta.months || [];
        var selectedMonth = months.indexOf(Number(preferredMonth)) >= 0 ? Number(preferredMonth) : Number(data.mes || months[0]);
        $('#dispMes').html(months.map(function (month) {
            return '<option value="' + month + '"' + (month === selectedMonth ? ' selected' : '') + '>' + monthNames[month - 1] + '</option>';
        }).join(''));

        state.equipment = data.meta.equipment || [];
        state.correctiveEquipment = data.meta.correctiveEquipment || [];
        $('#dispEquiposList').html(state.equipment.map(function (row) {
            var level = row.criticality ? ' - ' + row.criticality : '';
            return '<option value="' + esc(row.code + ' | ' + row.name) + '">' + esc(level) + '</option>';
        }).join(''));
        renderSelection();
        renderQuickEquipment();
        state.initialized = true;
    }

    function loadMeta(year, preferredMonth) {
        $('#dispConsultar').prop('disabled', true);
        return fetch(apiUrl({ anio: year, mes: preferredMonth || new Date().getMonth() + 1 }), { credentials: 'same-origin' })
            .then(function (response) {
                return response.json().then(function (body) {
                    if (!response.ok) throw new Error(body.error || 'No se pudo cargar la configuracion.');
                    return body;
                });
            })
            .then(function (data) { populateMeta(data, preferredMonth); })
            .catch(function (error) { Swal.fire('No se pudo preparar el reporte', error.message, 'error'); })
            .finally(function () { $('#dispConsultar').prop('disabled', false); });
    }

    function metricRows() {
        return [
            { key: 'available', label: 'TIEMPO DISPONIBLE', format: numberText },
            { key: 'corrective_downtime', label: 'TIEMPO MUERTO CORRECTIVO', format: numberText, css: 'metric-dead' },
            { key: 'preventive_downtime', label: 'TIEMPO MUERTO PREVENTIVO', format: numberText, css: 'metric-preventive' },
            { key: 'downtime', label: 'TIEMPO MUERTO TOTAL', format: numberText, css: 'metric-dead metric-dead-total' },
            { key: 'failures', label: 'NUMERO DE FALLAS', format: numberText },
            { key: 'mtbf', label: 'MTBF (min / falla)', format: numberText },
            { key: 'mttr', label: 'MTTR (min / intervencion)', format: numberText, css: 'metric-mttr' },
            { key: 'check', label: 'COMPROBACION', format: numberText },
            { separator: true },
            { key: 'availability', label: 'DISPONIBILIDAD', format: percentText, css: 'metric-percent' },
            { ideal: true, label: 'DISPONIBILIDAD IDEAL' },
            { key: 'reliability', label: 'CONFIABILIDAD', format: percentText, css: 'metric-percent' },
            { ideal: true, label: 'CONFIABILIDAD IDEAL' },
            { key: 'maintainability', label: 'MANTENIBILIDAD', format: percentText, css: 'metric-percent' },
            { ideal: true, label: 'MANTENIBILIDAD IDEAL' }
        ];
    }

    function renderMetricChart(canvasId, title, key) {
        if (typeof Chart === 'undefined') return;
        if (state.charts[key]) state.charts[key].destroy();
        var canvas = document.getElementById(canvasId);
        var labels = state.report.weeks.map(function (week) { return 'Semana ' + week.week; });
        var actual = state.report.weeks.map(function (week) { return Number(week[key] || 0); });
        var ideal = state.report.weeks.map(function () { return 100; });
        var valueLabels = {
            id: 'dispLabels' + key,
            afterDatasetsDraw: function (chart) {
                var context = chart.ctx;
                context.save();
                context.fillStyle = '#17384a';
                context.font = '700 9px sans-serif';
                context.textAlign = 'center';
                chart.data.datasets.forEach(function (dataset, datasetIndex) {
                    chart.getDatasetMeta(datasetIndex).data.forEach(function (bar, index) {
                        context.fillText(Number(dataset.data[index]).toFixed(2) + '%', bar.x, bar.y - 5);
                    });
                });
                context.restore();
            }
        };
        state.charts[key] = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: title, data: actual, backgroundColor: '#4d88d3', borderColor: '#2864ad', borderWidth: 1, borderRadius: 4 },
                    { label: title + ' ideal', data: ideal, backgroundColor: '#f49a52', borderColor: '#d96f27', borderWidth: 1, borderRadius: 4 }
                ]
            },
            plugins: [valueLabels],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 18 } },
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { size: 9, weight: '700' } } }, tooltip: { callbacks: { label: function (context) { return ' ' + context.dataset.label + ': ' + Number(context.raw).toFixed(2) + '%'; } } } },
                scales: { y: { beginAtZero: true, min: 0, max: 110, ticks: { callback: function (value) { return value <= 100 ? value + '%' : ''; }, font: { size: 8 } }, grid: { color: '#dfe7e8' } }, x: { grid: { display: false }, ticks: { font: { size: 8, weight: '700' } } } }
            }
        });
    }

    function renderCharts() {
        renderMetricChart('dispChartAvailability', 'Disponibilidad', 'availability');
        renderMetricChart('dispChartReliability', 'Confiabilidad', 'reliability');
        renderMetricChart('dispChartMaintainability', 'Mantenibilidad', 'maintainability');
    }

    function renderReport(data) {
        state.report = data;
        var equipment = data.equipment;
        var totals = data.totals;
        var level = equipment.criticality || 'SIN EVALUAR';
        var levelCss = ['ALTO', 'MEDIO', 'BAJO'].indexOf(level) >= 0 ? level : '';
        $('#dispReportTitle').html(esc(equipment.code + ' - ' + equipment.name) + '<span class="disp-crit ' + levelCss + '">' + esc(level) + '</span>');
        $('#dispReportSubtitle').text(monthNames[data.mes - 1] + ' ' + data.anio + ' | Riesgo: ' + numberText(equipment.risk) + '% | Semanas obtenidas de dias_del_anio');
        $('#dispKpis').html(
            '<div class="disp-kpi avail"><small>Disponibilidad mensual</small><strong>' + percentText(totals.availability) + '</strong><span>Ideal: 100%</span></div>' +
            '<div class="disp-kpi dead"><small>Tiempo muerto total</small><strong>' + numberText(totals.downtime) + '</strong><span>Correctivo: ' + numberText(totals.corrective_downtime) + ' min | Preventivo: ' + numberText(totals.preventive_downtime) + ' min</span></div>' +
            '<div class="disp-kpi fail"><small>Numero de fallas</small><strong>' + numberText(totals.failures) + '</strong><span>OT correctivas unicas</span></div>' +
            '<div class="disp-kpi rel"><small>Confiabilidad</small><strong>' + percentText(totals.reliability) + '</strong><span>Ideal: 100%</span></div>' +
            '<div class="disp-kpi main"><small>Mantenibilidad</small><strong>' + percentText(totals.maintainability) + '</strong><span>Ideal: 100%</span></div>'
        );

        var head = '<tr><th>Indicador</th>' + data.weeks.map(function (week) {
            var holidays = (week.holidays || []).length ? '<span class="disp-holiday"><i class="fas fa-umbrella-beach mr-1"></i>' + week.holidays.length + ' feriado(s)</span>' : '';
            return '<th>Semana ' + week.week + '<span class="week-range">' + dateText(week.start) + ' - ' + dateText(week.end) + '</span>' + holidays + '</th>';
        }).join('') + '<th>Total mes</th></tr>';
        $('#dispHead').html(head);

        $('#dispBody').html(metricRows().map(function (metric) {
            if (metric.separator) return '<tr class="metric-separator"><td colspan="' + (data.weeks.length + 2) + '"></td></tr>';
            var cells = data.weeks.map(function (week) {
                return '<td>' + (metric.ideal ? '100.00%' : metric.format(week[metric.key])) + '</td>';
            }).join('');
            var total = metric.ideal ? '100.00%' : metric.format(totals[metric.key]);
            return '<tr class="' + (metric.css || '') + '"><td>' + metric.label + '</td>' + cells + '<td><strong>' + total + '</strong></td></tr>';
        }).join(''));

        $('#dispOrderCount').text(data.orders.length + ' OT');
        if (!data.orders.length) {
            $('#dispOrders').html('<tr><td colspan="5" class="text-center text-muted py-4">No existen OT correctivas o preventivas en las semanas seleccionadas.</td></tr>');
        } else {
            $('#dispOrders').html(data.orders.map(function (order) {
                var period = dateText(order.startDate) + ' ' + order.startTime + '<br><span class="text-muted">hasta ' + dateText(order.endDate) + ' ' + order.endTime + '</span>';
                var type = order.type === 'CORRECTIVO' ? 'Correctivo' : 'Preventivo';
                var typeClass = order.type === 'CORRECTIVO' ? 'badge-danger' : 'badge-primary';
                var work = '<strong>' + esc(order.technician || 'Sin tecnico') + '</strong><div class="text-muted">' + esc(order.title || ('Mantenimiento ' + type.toLowerCase())) + '</div>';
                return '<tr><td><span class="badge badge-info">OT-' + esc(order.docNum) + '</span><div class="mt-1"><span class="badge ' + typeClass + '">' + type + '</span></div></td><td>' + period + '</td><td>' + (order.weeks || []).map(function (week) { return '<span class="badge badge-light border mr-1">S' + week + '</span>'; }).join('') + '</td><td><strong>' + numberText(order.minutes) + '</strong></td><td>' + work + '</td></tr>';
            }).join(''));
        }
        $('#dispLoading,#dispEmpty').hide();
        $('#dispReport').show();
        renderCharts();
    }

    function generateReport() {
        var equipment = selectedEquipment();
        if (!equipment) {
            Swal.fire('Seleccione un equipo', 'Escriba o seleccione un codigo y nombre de la lista.', 'info');
            return;
        }
        renderSelection();
        $('#dispReport,#dispEmpty').hide();
        $('#dispLoading').show();
        $('#dispConsultar').prop('disabled', true);
        fetch(apiUrl({ anio: $('#dispAnio').val(), mes: $('#dispMes').val(), equipo: equipment.code }), { credentials: 'same-origin' })
            .then(function (response) {
                return response.json().then(function (body) {
                    if (!response.ok) throw new Error(body.error || 'No se pudo generar el reporte.');
                    return body;
                });
            })
            .then(renderReport)
            .catch(function (error) {
                $('#dispLoading').hide();
                $('#dispEmpty').show().text(error.message);
                Swal.fire('No se pudo calcular', error.message, 'error');
            })
            .finally(function () { $('#dispConsultar').prop('disabled', false); });
    }

    function exportExcel() {
        var data = state.report;
        if (!data || typeof XLSX === 'undefined') {
            Swal.fire('Exportacion no disponible', 'Genere el reporte antes de exportarlo.', 'info');
            return;
        }
        var headers = ['INDICADOR'].concat(data.weeks.map(function (week) { return 'SEMANA ' + week.week + ' (' + dateText(week.start) + ' - ' + dateText(week.end) + ')'; })).concat(['TOTAL MES']);
        var rows = [[data.equipment.code + ' - ' + data.equipment.name], [monthNames[data.mes - 1] + ' ' + data.anio], [], headers];
        metricRows().forEach(function (metric) {
            if (metric.separator) { rows.push([]); return; }
            var values = data.weeks.map(function (week) { return metric.ideal ? 1 : week[metric.key]; });
            values.push(metric.ideal ? 1 : data.totals[metric.key]);
            rows.push([metric.label].concat(values));
        });
        var detail = [['OT', 'Tipo mantenimiento', 'Fecha inicio', 'Hora inicio', 'Fecha fin', 'Hora fin', 'Semanas', 'Tiempo muerto (min)', 'Tecnico', 'Trabajo']];
        data.orders.forEach(function (order) {
            detail.push([order.docNum, order.type, order.startDate, order.startTime, order.endDate, order.endTime, (order.weeks || []).join(', '), order.minutes, order.technician, order.title]);
        });
        var workbook = XLSX.utils.book_new();
        var sheet = XLSX.utils.aoa_to_sheet(rows);
        var detailSheet = XLSX.utils.aoa_to_sheet(detail);
        sheet['!cols'] = [{ wch: 28 }].concat(data.weeks.map(function () { return { wch: 24 }; })).concat([{ wch: 15 }]);
        detailSheet['!cols'] = [{ wch: 10 }, { wch: 20 }, { wch: 14 }, { wch: 12 }, { wch: 14 }, { wch: 12 }, { wch: 14 }, { wch: 22 }, { wch: 24 }, { wch: 50 }];
        for (var row = 4; row < rows.length; row++) {
            var label = rows[row][0] || '';
            if (label.indexOf('DISPONIBILIDAD') === 0 || label.indexOf('CONFIABILIDAD') === 0 || label.indexOf('MANTENIBILIDAD') === 0) {
                for (var col = 1; col < rows[row].length; col++) {
                    var cell = sheet[XLSX.utils.encode_cell({ r: row, c: col })];
                    if (cell) {
                        if (label.indexOf('IDEAL') < 0) cell.v = Number(cell.v || 0) / 100;
                        cell.z = '0.00%';
                    }
                }
            }
        }
        XLSX.utils.book_append_sheet(workbook, sheet, 'Disponibilidad semanal');
        XLSX.utils.book_append_sheet(workbook, detailSheet, 'OT consideradas');
        XLSX.writeFile(workbook, 'Disponibilidad_' + data.equipment.code + '_' + data.anio + '_' + String(data.mes).padStart(2, '0') + '.xlsx');
    }

    $(function () {
        $('#btnDisponibilidadCriticos').on('click', function () {
            $('#modalDisponibilidadCriticos').modal('show');
            var year = $('#rpAnio').val() || new Date().getFullYear();
            var month = Number(year) === new Date().getFullYear() ? new Date().getMonth() + 1 : 1;
            loadMeta(year, month);
        });
        $('#dispAnio').on('change', function () { loadMeta(this.value, $('#dispMes').val()); });
        $('#dispMes').on('change', function () {
            state.report = null;
            $('#dispReport').hide();
            $('#dispEmpty').show().html('<i class="fas fa-chart-line fa-2x mb-2 d-block text-info"></i>Seleccione un equipo para generar el nuevo periodo.');
            loadMeta($('#dispAnio').val(), this.value);
        });
        $('#dispEquipo').on('input change', renderSelection);
        $('#dispQuickToggle').on('click', function () { $('#dispQuickPanel').slideToggle(160); });
        $(document).on('click', '[data-disp-equipment]', function () {
            var code = String($(this).data('disp-equipment'));
            var equipment = state.equipment.find(function (row) { return row.code === code; });
            if (!equipment) return;
            $('#dispEquipo').val(equipment.code + ' | ' + equipment.name);
            renderSelection();
            $('#dispQuickPanel').slideUp(120);
            generateReport();
        });
        $('#dispConsultar').on('click', generateReport);
        $('#dispExcel').on('click', exportExcel);
        $('#dispEquipo').on('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); generateReport(); }
        });
    });
})(jQuery);
