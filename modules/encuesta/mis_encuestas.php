<?php
/**
 * Módulo Encuestas — Portal del usuario: lista de encuestas para responder
 */
$page_title = 'Mis Encuestas';
require_once(__DIR__ . '/../../includes/load.php');
require_once(__DIR__ . '/../../config/app.php');

require_permiso('encuesta', 'ver');

$user = current_user();
include_once(__DIR__ . '/../../layouts/header.php');
?>

<style>
.enc-user-card {
    border-radius: 14px;
    border: 1px solid #e9ecef;
    background: #fff;
    transition: transform .2s, box-shadow .2s;
    overflow: hidden;
}
.enc-user-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.10);
}
.enc-user-card .card-top {
    background: linear-gradient(135deg,#667eea,#764ba2);
    padding: 20px 22px 14px;
    color: #fff;
}
.enc-user-card .card-top h5 {
    font-weight: 700; margin-bottom: 4px;
}
.enc-user-card .card-top p {
    font-size: .85rem; opacity: .85; margin: 0;
}
.enc-user-card .card-body {
    padding: 16px 22px;
}
.badge-pendiente {
    background: linear-gradient(135deg,#43e97b,#38f9d7);
    color: #fff; border-radius: 20px; padding: 4px 12px; font-size: .78rem;
}
.badge-respondida {
    background: linear-gradient(135deg,#6c757d,#868e96);
    color: #fff; border-radius: 20px; padding: 4px 12px; font-size: .78rem;
}
.enc-empty {
    text-align: center; padding: 60px 20px; color: #adb5bd;
}
.enc-empty i { font-size: 4rem; display: block; margin-bottom: 16px; }
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-clipboard-list mr-2" style="color:#667eea"></i>Mis Encuestas</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../../home.php">Inicio</a></li>
                    <li class="breadcrumb-item active">Mis Encuestas</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <?php echo display_msg($msg); ?>

        <div id="loadingPanel" class="text-center py-5">
            <div class="spinner-border text-primary" style="width:3rem;height:3rem"></div>
            <p class="mt-3 text-muted">Cargando encuestas...</p>
        </div>

        <div id="encuestasContainer" class="row" style="display:none"></div>

        <div id="emptyPanel" class="enc-empty" style="display:none">
            <i class="fas fa-inbox"></i>
            <h4>No hay encuestas disponibles</h4>
            <p>Por el momento no hay encuestas activas para responder.</p>
        </div>
    </div>
</section>

<?php
$userId = (int)$user['id'];
ob_start();
?>
var USERID = <?php echo $userId; ?>;

$(document).ready(function() {
    $.get('../../api/encuesta/get_mis_encuestas.php')
     .done(function(data) {
        $('#loadingPanel').hide();

        if (!data || data.length === 0) {
            $('#emptyPanel').show();
            return;
        }

        var container = $('#encuestasContainer');
        container.show();

        data.forEach(function(enc) {
            var yaRespondio = enc.ya_respondio;
            var cierre = enc.fecha_cierre
                ? '<small><i class="fas fa-calendar-alt mr-1"></i>Cierra: ' + enc.fecha_cierre + '</small>'
                : '<small class="text-muted">Sin fecha de cierre</small>';

            var badge = yaRespondio
                ? '<span class="badge-respondida"><i class="fas fa-check mr-1"></i>Ya respondida</span>'
                : '<span class="badge-pendiente"><i class="fas fa-circle mr-1" style="font-size:.5rem"></i>Pendiente</span>';

            var boton = yaRespondio
                ? '<button class="btn btn-secondary btn-sm" disabled><i class="fas fa-check-circle mr-1"></i>Ya respondiste esta encuesta</button>'
                : '<a href="responder.php?id=' + enc.id_encuesta + '" class="btn btn-success btn-sm px-4">' +
                  '<i class="fas fa-clipboard-check mr-1"></i>Responder</a>';

            var html = '<div class="col-md-6 col-lg-4 mb-4">' +
                '<div class="enc-user-card">' +
                  '<div class="card-top">' +
                    '<div class="d-flex justify-content-between align-items-start">' +
                      '<h5>' + escHtml(enc.titulo) + '</h5>' +
                      badge +
                    '</div>' +
                    '<p>' + escHtml(enc.descripcion || '') + '</p>' +
                  '</div>' +
                  '<div class="card-body">' +
                    '<div class="d-flex justify-content-between align-items-center mb-3">' +
                      '<small><i class="fas fa-question-circle mr-1 text-muted"></i>' + enc.total_preguntas + ' preguntas</small>' +
                      cierre +
                    '</div>' +
                    boton +
                  '</div>' +
                '</div>' +
            '</div>';

            container.append(html);
        });
     })
     .fail(function() {
        $('#loadingPanel').hide();
        $('#emptyPanel').show();
        App.toast('Error al cargar las encuestas', 'error');
     });
});

function escHtml(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}
<?php
$inlineJs = ob_get_clean();
include_once(__DIR__ . '/../../layouts/footer.php');
?>
