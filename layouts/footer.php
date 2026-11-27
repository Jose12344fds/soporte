    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer" style="position: static !important; bottom: auto !important; z-index: auto !important;">
        <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="#"><?php echo defined('APP_NAME') ? APP_NAME : 'Sistema Soporte'; ?></a>.</strong>
        Todos los derechos reservados.
    </footer>

</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<?php echo script_tag('https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js'); ?>

<!-- jQuery UI - Plugin profesional drag/resize -->
<?php echo stylesheet_tag('https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css'); ?>
<?php echo script_tag('https://code.jquery.com/ui/1.13.2/jquery-ui.min.js'); ?>

<!-- Select2 JS -->
<?php echo script_tag('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js'); ?>

<!-- Bootstrap 4 -->
<?php echo script_tag('https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js'); ?>

<!-- AdminLTE App -->
<?php echo script_tag('https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js'); ?>

<!-- DataTables & Plugins -->
<?php echo script_tag('https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js'); ?>
<?php echo script_tag('https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js'); ?>
<?php echo script_tag('https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js'); ?>
<?php echo script_tag('https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js'); ?>
<?php echo script_tag('https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js'); ?>
<?php echo script_tag('https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js'); ?>
<?php echo script_tag('https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js'); ?>
<?php echo script_tag('https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js'); ?>
<?php echo script_tag('https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js'); ?>
<?php echo script_tag('https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js'); ?>
<?php echo script_tag('https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js'); ?>

<!-- SweetAlert2 -->
<?php echo script_tag('https://cdn.jsdelivr.net/npm/sweetalert2@11'); ?>

<!-- App Utilities -->
<script>
window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function appendCsrfToForm(form) {
    if (!form || form.querySelector('input[name="_csrf"]')) {
        return;
    }
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_csrf';
    input.value = window.CSRF_TOKEN || '';
    form.appendChild(input);
}

function attachCsrfToBody(body) {
    if (!window.CSRF_TOKEN || !body) {
        return body;
    }
    if (body instanceof FormData) {
        if (!body.has('_csrf')) {
            body.append('_csrf', window.CSRF_TOKEN);
        }
        return body;
    }
    if (body instanceof URLSearchParams) {
        if (!body.has('_csrf')) {
            body.append('_csrf', window.CSRF_TOKEN);
        }
        return body;
    }
    if (typeof body === 'string') {
        const trimmed = body.trim();
        if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
            try {
                const json = JSON.parse(body);
                if (json && typeof json === 'object' && !Array.isArray(json) && !Object.prototype.hasOwnProperty.call(json, '_csrf')) {
                    json._csrf = window.CSRF_TOKEN;
                    return JSON.stringify(json);
                }
            } catch (e) {
                return body;
            }
        }
    }
    return body;
}

if (window.fetch) {
    const originalFetch = window.fetch.bind(window);
    window.fetch = function(resource, options) {
        const opts = Object.assign({}, options || {});
        opts.headers = new Headers(opts.headers || {});
        if (window.CSRF_TOKEN && !opts.headers.has('X-CSRF-Token')) {
            opts.headers.set('X-CSRF-Token', window.CSRF_TOKEN);
        }
        if (opts.body) {
            opts.body = attachCsrfToBody(opts.body);
        }
        return originalFetch(resource, opts);
    };
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form').forEach(appendCsrfToForm);
});

// Configuracion DataTables en espanol
var DATATABLES_SPANISH = {
    "decimal": "",
    "emptyTable": "No hay datos disponibles",
    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
    "infoFiltered": "(filtrado de _MAX_ registros totales)",
    "lengthMenu": "Mostrar _MENU_ registros",
    "loadingRecords": "Cargando...",
    "processing": "Procesando...",
    "search": "Buscar:",
    "zeroRecords": "No se encontraron registros",
    "paginate": {
        "first": "Primero",
        "last": "Ultimo",
        "next": "Siguiente",
        "previous": "Anterior"
    }
};

var App = {
    toast: function(message, type) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type || 'success',
            title: message,
            showConfirmButton: false,
            timer: 3000
        });
    },
    confirm: function(title, text, callback) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Si',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed && callback) callback();
        });
    }
};

$(document).ajaxSend(function(event, jqXHR, settings) {
    if (window.CSRF_TOKEN) {
        jqXHR.setRequestHeader('X-CSRF-Token', window.CSRF_TOKEN);
    }
    if (settings && settings.data instanceof FormData && !settings.data.has('_csrf')) {
        settings.data.append('_csrf', window.CSRF_TOKEN);
    }
});

$(document).ready(function() {
    $('.preloader').fadeOut('slow');
});
</script>

<?php if (isset($extraJs) && is_array($extraJs)): ?>
    <?php foreach ($extraJs as $js): ?>
        <?php echo script_tag($js); ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($pageScripts)): ?>
<script>
<?php echo $pageScripts; ?>
</script>
<?php endif; ?>

<?php if (isset($inlineJs)): ?>
<script>
<?php echo $inlineJs; ?>
</script>
<?php endif; ?>

<script>
(function () {
    function applyStaticFooter() {
        var el = document.querySelector('footer.main-footer');
        if (!el) return;
        el.style.setProperty('position', 'static', 'important');
        el.style.setProperty('bottom', 'auto', 'important');
        el.style.setProperty('left', 'auto', 'important');
        el.style.setProperty('right', 'auto', 'important');
        el.style.setProperty('z-index', 'auto', 'important');
    }

    function init() {
        applyStaticFooter();

        var footer = document.querySelector('footer.main-footer');
        if (!footer) return;

        var busy = false;
        new MutationObserver(function () {
            if (busy) return;
            busy = true;
            applyStaticFooter();
            busy = false;
        }).observe(footer, { attributes: true, attributeFilter: ['style'] });

        new MutationObserver(function () {
            var b = document.body;
            if (b.classList.contains('layout-footer-fixed')) {
                b.classList.remove('layout-footer-fixed');
            }
            if (!b.classList.contains('layout-footer-not-fixed')) {
                b.classList.add('layout-footer-not-fixed');
            }
            applyStaticFooter();
        }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    window.addEventListener('load', applyStaticFooter);
})();
</script>
</body>
</html>
<?php if (isset($db)) { $db->db_disconnect(); } ?>
