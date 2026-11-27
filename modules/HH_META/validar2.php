<?php
$page_title = 'Validar';
require_once('includes/load.php');
page_require_level(2);

$all_problema = find_all('tipoproblema');
$Correos = find_by_Correo('usuarios', 1);

?>
<link href="css/styles_1.css">
<!-- Font Awesome para iconos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<?php include_once('layouts/header.php'); ?>
<style>

    #subQueryResults th, #subQueryResults td {
    padding: 1px;
}
/* Ocultar los botones de incremento y decremento en los campos de tipo number */
  input[type="number"]::-webkit-outer-spin-button,
  input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none; /* Para WebKit browsers (Chrome, Safari) */
    margin: 0;
  }
  input[type="number"] {
    -moz-appearance: textfield; /* Para Firefox */
  }
/* Establecer el tamaño máximo del modal */
.modal-dialog {
    max-width: 95% !important;  /* Usar 95% del ancho de la pantalla */
    width: 95% !important;
    margin: 20px auto !important;
}

.modal-content {
    width: 100% !important;
    max-width: none !important;
    max-height: 90vh !important;
    margin: 0 !important;
    border-radius: 15px !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border-bottom: 2px solid rgba(255,255,255,0.2) !important;
    border-radius: 15px 15px 0 0 !important;
    padding: 20px 30px !important;
}

.modal-title {
    font-size: 1.5rem !important;
    font-weight: 600 !important;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3) !important;
}

.modal-body {
    background: #f8f9fa !important;
    padding: 30px !important;
    overflow-y: auto !important;
    max-height: 70vh !important;
}

.modal-footer {
    background: #f8f9fa !important;
    border-top: 2px solid #e9ecef !important;
    border-radius: 0 0 15px 15px !important;
    padding: 20px 30px !important;
}

/* Estilos para las tablas del modal */
#modalDataTable {
    background: white !important;
    border-radius: 10px !important;
    overflow: hidden !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    margin-bottom: 25px !important;
}

#modalDataTable thead th {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    color: white !important;
    font-weight: 600 !important;
    text-align: center !important;
    padding: 15px !important;
    border: none !important;
    font-size: 14px !important;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2) !important;
}

#modalDataTable tbody td {
    padding: 12px 15px !important;
    text-align: center !important;
    border-bottom: 1px solid #e9ecef !important;
    font-weight: 500 !important;
    transition: all 0.3s ease !important;
}

#modalDataTable tbody tr:hover {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}

/* Estilos para subQueryResults1 */
#subQueryResults1 {
    background: white !important;
    border-radius: 10px !important;
    overflow: hidden !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    margin-bottom: 20px !important;
}

#subQueryResults1 thead th {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    color: white !important;
    font-weight: 600 !important;
    text-align: center !important;
    padding: 12px !important;
    border: none !important;
    font-size: 13px !important;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2) !important;
}

#subQueryResults1 tbody td {
    padding: 10px 12px !important;
    text-align: center !important;
    border-bottom: 1px solid #e9ecef !important;
    font-size: 13px !important;
    transition: all 0.3s ease !important;
}

#subQueryResults1 tbody tr:hover {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important;
    cursor: pointer !important;
}

/* Fila seleccionada en Items del Proyecto */
#subQueryResults1 tbody tr.selected-item {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    color: #1a1a1a !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 12px rgba(79, 172, 254, 0.5) !important;
    transform: scale(1.02) !important;
}

/* ========================================
   ESTILOS PARA PROCESOS ESPECIALES
   ======================================== */
.procesos-especiales-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    margin-bottom: 10px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    color: white;
    border: none;
    border-radius: 25px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(238, 90, 36, 0.35);
    transition: all 0.3s ease;
}
.procesos-especiales-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(238, 90, 36, 0.45);
}
.procesos-especiales-toggle.active {
    background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.35);
}
.procesos-especiales-toggle .toggle-icon {
    transition: transform 0.3s ease;
}
.procesos-especiales-toggle.active .toggle-icon {
    transform: rotate(45deg);
}
.col-proceso-especial {
    background-color: #ff6b6b !important;
    color: white !important;
}
.col-proceso-especial-hidden {
    display: none !important;
}
.proceso-especial-badge {
    display: inline-block;
    background: #ff6b6b;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    margin-left: 5px;
    font-weight: bold;
}
/* ======================================== */

#subQueryResults1 tbody tr.selected-item td {
    color: #1a1a1a !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
}

/* Alternativas de colores para fila seleccionada (texto oscuro):
   Opción 1 (Azul claro): #4facfe a #00f2fe - texto oscuro
   Opción 2 (Verde menta): #56ccf2 a #2f80ed - texto oscuro
   Opción 3 (Naranja suave): #fdbb2d a #f7971e - texto oscuro
   Opción 4 (Amarillo): #fee140 a #fa709a - texto oscuro
   Opción 5 (Cyan suave): #a1c4fd a #c2e9fb - texto oscuro
*/

/* Estilos para crossedTable */
#crossedTable {
    background: white !important;
    border-radius: 10px !important;
    overflow: hidden !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

#crossedTable thead th {
    background: linear-gradient(135deg, #a8e6cf 0%, #88d8c0 100%) !important;
    color: #2c3e50 !important;
    font-weight: 600 !important;
    text-align: center !important;
    padding: 12px 8px !important;
    border: none !important;
    font-size: 12px !important;
    text-shadow: 1px 1px 2px rgba(255,255,255,0.5) !important;
}

#crossedTable tbody td {
    padding: 10px 8px !important;
    text-align: center !important;
    border-bottom: 1px solid #e9ecef !important;
    font-size: 12px !important;
    transition: all 0.3s ease !important;
}

#crossedTable tbody tr:hover {
    background: linear-gradient(135deg, #e8f5e8 0%, #d4f1d4 100%) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important;
}

#crossedTable tfoot th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    font-weight: 700 !important;
    text-align: center !important;
    padding: 12px 8px !important;
    border: none !important;
    font-size: 13px !important;
}

/* Estilos para los paneles dentro del modal */
.panel-heading {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border-radius: 8px 8px 0 0 !important;
    padding: 15px 20px !important;
    border: none !important;
    font-weight: 600 !important;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2) !important;
}

.panel-default {
    border: none !important;
    border-radius: 10px !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    overflow: hidden !important;
    margin-bottom: 20px !important;
}

.panel-body {
    padding: 0 !important;
    background: white !important;
}

/* Botón de enviar datos */
#enviarDatosBtn {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    border: none !important;
    color: white !important;
    padding: 12px 25px !important;
    border-radius: 25px !important;
    font-weight: 600 !important;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2) !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
    transition: all 0.3s ease !important;
}

#enviarDatosBtn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3) !important;
}

.modal-body {
    text-align: left !important;  /* Alinear contenido a la izquierda */
}

        .fila-seleccionada {
    background-color: #5fc8f5 !important; /* Un azul claro, puedes cambiarlo */
}
   

  </style>
    <script>
$(document).on('click', '.btn-correo', function () {
    let id = $(this).data('id'); // Obtener el ID del botón
console.log(id);
    // Confirmación (opcional)
    if (!confirm("¿Deseas enviar un correo con esta información?")) return;

    $.ajax({
        url: 'enviarCorreo_Validar.php',  // PHP que procesará el correo
        method: 'POST',
        data: { id: id },
        success: function (response) {
            alert("Correo enviado correctamente.");
            console.log(response); // Puedes mostrar una respuesta del servidor
        },
        error: function () {
            alert("Error al enviar el correo.");
        }
    });
});
</script>
<script type="text/javascript">
    
        let subQueryResultsCompa = [];  // Almacenar resultados de la segunda subconsulta
    let subQueryData11 = [];
    let selectedRowData = {}; 
    let Codo2 = ""; // Variable para almacenar DocEntry
    let modalTable; // Declarar modalTable globalmente
    
$(document).ready(function () {

// Inicializar modal manualmente para evitar auto-cierre
$('#Exportar').modal({
    backdrop: true,
    keyboard: true,
    show: false
});

// Evitar que el modal se cierre automáticamente por errores JS
$('#Exportar').on('hide.bs.modal', function (e) {
    console.log('Intentando cerrar modal');
    // Solo permitir cerrar si se hace explícitamente
    if (!$(e.target).data('manual-close')) {
        console.log('Previniendo cierre automático del modal');
        // No prevenir el cierre, solo logearlo
    }
});

// Manejar eventos del modal
$('#Exportar').on('show.bs.modal', function () {
    console.log('Modal está intentando abrirse');
});

$('#Exportar').on('shown.bs.modal', function () {
    console.log('Modal se abrió correctamente');
});

$('#Exportar').on('hidden.bs.modal', function () {
    console.log('Modal se cerró');
});

// Inicialización de la tabla DataTable
    var mainTable = $("#example").DataTable({
        lengthMenu: [[10, 14, -1], [10, 14, 'All']],
        responsive: true,
        paging: true,
        dom: 'Bfrtip', 
        language: {  url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        ajax: { url: "consultasAjax/consulta7.php", method: "POST", dataSrc: "" },
        columns: [
            { "data": "IdPadre" },
            { "data": "ProjectCode" },
            { "data": "NPdre" },
            { "data": "IdPadre", render: function(data) {  return '<button type="button" class="btn btn-secondary btn-correo" data-id="' + data + '" data-toggle="modal" data-target="#Correo"><strong>Correo</strong></button>'; }},
            { "data": "IdPadre", render: function(data) {
                return '<button type="button" class="btn btn-primary btn-exportar" data-id="' + data + '"><strong>Exportar Sap</strong></button>';
            }},
            { "data": "Estado_OT", render: function(data) {
                if (data == 0) return "<span class='btn btn-warning' style='background: red'>Deshabilitado</span>";
                if (data == 1) return "<span class='btn btn-warning' style='font-weight: bold;'><strong>PROCESO</strong></span>";
                return data;
            }},
            { "data": "IdPadre", render: function(data) {
                return '<button class="btn btn-success btn-xs btn-cambiar" data-id="' + data + '" title="Cambiar Estado">Cambiar Estado</button>';
            }}
        ]
    });

// Inicialización de la tabla del modal
modalTable = $("#modalDataTable").DataTable({
    responsive: true,
    paging: false,  // Desactivar la paginación
    searching: false,  // Desactivar la búsqueda
    info: false,  // Desactivar la información (total de filas, etc.)
    dom: 't',  // Solo mostrar la tabla (sin botones, búsqueda, etc.)
    columns: [
        { "data": "IdPadre" },
        { "data": "ProjectCode" },
        { "data": "NPdre" }
    ]
});

$('#modalDataTable tbody').on('click', 'tr', function() {
    const data = modalTable.row(this).data();
    const idPadre = data.IdPadre;
    const ProjectCode = data.ProjectCode;
    if (idPadre) {
        fetchSubQueryResults1(idPadre);
    }
});

// Capturar clic en el botón 'Exportar Sap'
$('#example tbody').on('click', '.btn-exportar', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    console.log('Botón Exportar SAP clickeado');
    
    // Obtener la fila correspondiente al botón que se ha hecho clic
    var row = $(this).closest('tr');  // Obtener la fila de la tabla
    var rowData = mainTable.row(row).data();  // Obtener los datos de esa fila
    
    // Aquí puedes ver qué datos contiene la fila
    console.log('Datos de la fila:', rowData);  // Solo para depuración

    // Limpiar y cargar datos en modalTable
    modalTable.clear().draw();

    // Crear una nueva fila con los datos de la fila seleccionada
    modalTable.row.add({
        "IdPadre": rowData.IdPadre,
        "ProjectCode": rowData.ProjectCode,
        "NPdre": rowData.NPdre,
        "Estado_OT": rowData.Estado_OT
    }).draw(); // Agregar la fila y actualizar la tabla

    // Mostrar el modal con un pequeño delay
    console.log('Intentando abrir modal Exportar');
    setTimeout(function() {
        $('#Exportar').modal('show');
    }, 100);
    
    // Verificar si el modal se abrió
    setTimeout(function() {
        if ($('#Exportar').hasClass('show')) {
            console.log('Modal abierto exitosamente');
        } else {
            console.log('Error: Modal no se abrió');
        }
    }, 500);
});

  async function fetchSubQueryResults1(idPadre) {
            try {
                const response = await $.ajax({
                    url: "consultasAjax/consulta12.php",
                    method: "POST",
                    data: { idPadre },
                    dataType: "json"
                });
                if (response && response.length) {
                    subQueryTable5.clear().rows.add(response).draw();
                } else {
                    console.error('No se recibieron datos válidos en la subconsulta 1.');
                }
            } catch (error) {
                console.error('Error al obtener la subconsulta 1:', error);
            }}

  const subQueryTable5 = $("#subQueryResults1").DataTable({
            ajax: "consultasAjax/consulta12.php",
            columns: [
                { data: "ITEM" },
                { data: "CodPdre" },
                { data: "NPdre" },
                { data: "ProjectCode", visible: false },
                { data: "IdPadreOT", visible: false },
                { data: "IdHijOT", visible: false }
            ],
            searching: false,
            info: false,
            paging: false
        });
  $('#subQueryResults1 tbody').on('click', 'tr', function() {
            // Remover la clase 'selected-item' de todas las filas
            $('#subQueryResults1 tbody tr').removeClass('selected-item');
            
            // Agregar la clase 'selected-item' a la fila clickeada
            $(this).addClass('selected-item');
            
            const data = subQueryTable5.row(this).data();
            const idPadre = data.IdPadre;
            const codPdre = data.CodPdre;
            const Proyectoow = data.OTPADRE;
                const Code1 = data.CodPdre;
                 const Code2 = data.IdHijOT;
      
                 selectedRowData = { idPadre, codPdre, Proyectoow, Code1, Code2 };
                console.log('Fila seleccionada datos:', selectedRowData);
            if (idPadre && codPdre) {
                fetchAndCombineData(idPadre, codPdre, Code1, Proyectoow, Code2);
                fetchSubQueryResults2(idPadre, codPdre, Code2);
                fetchSubQueryResults6(codPdre,Proyectoow);
            }
        });

 async function fetchSubQueryResults2(idPadre, codPdre, idHijOT) {
    try {
        console.log('fetchSubQueryResults2 params:', { idPadre, codPdre, idHijOT });
        const response = await $.ajax({
            url: "consultasAjax/consulta16.php",
            method: "POST",
            data: { idPadre, codPdre, idHijOT },
            dataType: "json"
        });
         if (response && response.length) {
            subQueryResultsCompa = response;
                         console.log('dddddddd1',response);

        } else {
            subQueryResultsCompa = [];
            console.error('No se recibieron datos válidos en la subconsulta 2.');
        }
    } catch (error) {
        console.error('Error al obtener la subconsulta 2:', error);
    }
}
 async function fetchSubQueryResults6(Code1,Proyectoow) {
    Codo2 = Code1;
    try {
        const response = await $.ajax({
            url: "consultasAjax/ConsultaSAP1.php",
            method: "POST",
            data: { ItemCod: Code1, proy: Proyectoow },
            dataType: "json"
        });
      if (response && response.length) {
         // Crear subQueryData11 con la transformación de los datos
             console.log('dddddddd',response);
            subQueryData11 = response.map(item => ({
                StageId: item.StageId,
                ChildNum: item.ChildNum,
                ResName: item.ResName,
                Code: item.Code,
                Quantity: item.Quantity
            }));
            } else {
            console.error('No se recibieron datos válidos en la subconsulta 3.');
        }
    } catch (error) {
        console.error('Error al obtener la subconsulta 3:', error);
    }
}
        $('#enviarDatosBtn').on('click', function() {
            if (Codo2 && subQueryData11.length > 0) {
               useSubQueryData(Codo2, subQueryData11); // Ejecutar la función con los datos
            } else {
                alert("Por favor, selecciona una OT y carga los datos antes de enviar.");    } });


function useSubQueryData(Codo2, subQueryData11) {
    const norm = s => (s || '').toString().trim().toUpperCase();

    // Pool SAP con control de uso para no duplicar
    const sapPool = subQueryData11.map((item, idx) => ({
        StageId: item.StageId,
        ChildNum: item.ChildNum,
        Code: item.Code,
        ResName: item.ResName,
        Quantity: parseFloat(item.Quantity) === 0 ? 0.001 : item.Quantity,
        _used: false,
        _idx: idx
    }));

    const warnings = [];

    // Recorremos los procesos locales (consulta16) para asignar cantidad a la línea SAP correcta
    subQueryResultsCompa.forEach(local => {
        const code = norm(local.proceso);
        const stageRaw = local.orden != null && local.orden !== '' ? local.orden : local.ordenes;
        const stageNum = stageRaw === undefined || stageRaw === null || stageRaw === '' ? null : Number(stageRaw);
        const localQtyRaw = local.Valor1 !== undefined ? local.Valor1 : local.Valor;
        const localQty = parseFloat(localQtyRaw) === 0 ? 0.001 : localQtyRaw;

        let match = null;

        // 1) Match exacto por StageId y Code
        if (stageNum !== null) {
            match = sapPool.find(x => !x._used && x.StageId != null && Number(x.StageId) === stageNum && norm(x.Code) === code);
        }
        // 2) Si StageId en SAP es null, matchear por nombre
        if (!match) {
            match = sapPool.find(x => !x._used && x.StageId == null && norm(x.Code) === code && norm(x.ResName) === norm(local.Campo));
        }
        // 3) Último recurso: misma Code libre
        if (!match) {
            match = sapPool.find(x => !x._used && norm(x.Code) === code);
        }

        if (match) {
            match._used = true;
            match.Quantity = localQty;
            if (code === 'MO004') {
                console.log('SAP export MO004 match', { stageLocal: stageNum, stageSap: match.StageId, resName: match.ResName, qty: match.Quantity, idx: match._idx });
            }
        } else {
            warnings.push(`Sin match SAP para ${local.proceso} stage ${stageNum}`);
            if (code === 'MO004') {
                console.warn('SAP export MO004 sin match', { stageLocal: stageNum, pool: sapPool });
            }
        }
    });

    if (warnings.length) {
        console.warn('SAP export advertencias', warnings);
    }

    // Armar payload final preservando todas las líneas SAP (con overrides aplicados)
    const updatedData = sapPool.map(item => ({
        ChildNum: item.ChildNum,
        ItemCode: item.Code,
        Quantity: item.Quantity
    }));
    const var_json = {
        "TreeType": "iProductionTree",  // Tipo de árbol de producción (puedes modificar este valor)
        ProductTreeLines: updatedData   // Asignamos las líneas de producción actualizadas
    };
    $.ajax({
        url: 'consultasAjax/BD_1.php',  // URL del servidor que manejará la solicitud
        method: 'POST',
        dataType: 'json',  // Aseguramos que la respuesta será procesada como JSON
        contentType: 'application/x-www-form-urlencoded',  // Establecemos el tipo de contenido
        data: {
            var_json: JSON.stringify(var_json),  // Convertir el objeto a JSON antes de enviarlo
            ord_dato: Codo2  // Enviar el documento de entrada
        },
        success: function(respuesta) {
            if (respuesta.success) {
                alert("Actualización exitosa: " + respuesta.message);
                  fetchAndCombineDataAfterUpdate();
                } else {
                alert("Error en la actualización: " + respuesta.message);
            }},
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error al enviar datos:", textStatus, errorThrown);
            alert("Ocurrió un error al enviar la solicitud.");
        }
    });}


function fetchAndCombineDataAfterUpdate() {
    if (selectedRowData.idPadre && selectedRowData.codPdre) {
       const { idPadre, codPdre, Proyectoow, Code1, Code2 } = selectedRowData;
       console.log('Refetching data after update for', selectedRowData);
        fetchAndCombineData(idPadre, codPdre, Code1, Proyectoow, Code2); // Usamos los valores almacenados de la fila seleccionada
    }
}
let crossedTable;  
async function fetchAndCombineData(idPadre, codPdre, Code1, Proyectoow, idHijOT) {
    try {
        const response2 = await $.ajax({
            url: "consultasAjax/consulta16.php",
            method: "POST",
            data: { idPadre, codPdre, idHijOT },
            dataType: "json"
        });
        const response6 = await $.ajax({
            url: "consultasAjax/ConsultaSAP1.php",
            method: "POST",
            data: { ItemCod: Code1, proy: Proyectoow },
            dataType: "json"
        });
           if (response2 && response6) {
            const crossedData = [];

            const norm = s => (s || '').toString().trim().toUpperCase();

            // Armar pools SAP por código y StageId, marcando uso para no duplicar
            const sapPools = {};
            response6.forEach((item, idx) => {
                const code = norm(item.Code);
                if (!sapPools[code]) sapPools[code] = [];
                sapPools[code].push({ ...item, _used: false, _idx: idx });
            });
            // Orden estable por StageId y orden de llegada
            Object.keys(sapPools).forEach(code => {
                sapPools[code].sort((a, b) => {
                    const sa = a.StageId == null ? 9999 : Number(a.StageId);
                    const sb = b.StageId == null ? 9999 : Number(b.StageId);
                    if (sa !== sb) return sa - sb;
                    return a._idx - b._idx;
                });
            });

            const warnings = [];

            response2.forEach(item2 => {
                const code = norm(item2.proceso);
                const stage = item2.orden != null ? String(item2.orden) : '';
                const stageNum = stage === '' ? null : Number(stage);
                const pool = sapPools[code] || [];
                let match = null;

                // 1) Coincidencia exacta por StageId si existe
                if (stage !== '') {
                    match = pool.find(x => !x._used && x.StageId != null && Number(x.StageId) === stageNum);
                }
                // 2) Coincidencia por nombre solo si StageId está vacío en SAP
                if (!match) {
                    match = pool.find(x => !x._used && x.StageId == null && norm(x.ResName) === norm(item2.Campo));
                }
                // 3) Último recurso: misma code libre
                if (!match) {
                    match = pool.find(x => !x._used);
                }

                if (match) {
                    match._used = true;
                    if (code === 'MO004') {
                        console.log('MO004 match', { stageOrigen: stage, stageSap: match.StageId, resName: match.ResName, qty: match.Quantity, usedIndex: match._idx });
                    }
                    crossedData.push({
                        Grupo: item2.Campo,
                        Proceso: item2.proceso,
                        HrMetaOperaciones: parseFloat(item2.Valor),
                        CantidadPlanificada: parseFloat(match.Quantity),
                        ordenes: item2.ordenes,
                        stageOrigen: stage,
                        stageSap: match.StageId
                    });
                } else {
                    warnings.push(`Sin match para ${item2.proceso} (${item2.Campo}) con stage ${stage}`);
                    if (code === 'MO004') {
                        console.warn('MO004 sin match', { stageOrigen: stage, pool });
                    }
                }
            });

            if (warnings.length) {
                console.warn('Cruce SAP: advertencias', warnings);
            }

            if ($.fn.dataTable.isDataTable('#crossedTable')) {
                crossedTable.clear().rows.add(crossedData).draw();
            } else {
                // Inicializar la tabla si no se ha inicializado aún
                crossedTable = $("#crossedTable").DataTable({
                    data: crossedData,
                    columns: [
                        { data: "Grupo" },
                        { data: "Proceso" },
                        { data: "HrMetaOperaciones" },
                        { data: "CantidadPlanificada" },
                        { data: "ordenes" , visible: false }
                    ],
                    searching: false,
                    info: false,
                    paging: false,
                    order: [[4, 'asc']], //
                    footerCallback: function(row, data, start, end, display) {
                        var api = this.api();

                        // Sumar la columna de "Hr.Meta Operaciones" (columna 2)
                        var totalHrMetaOperaciones = api.column(2, { page: 'current' }).data().reduce(function(a, b) {
                            return a + parseFloat(b) || 0;  }, 0);

                        // Sumar la columna de "Cantidad Planificada" (columna 3)
                        var totalCantidadPlanificada = api.column(3, { page: 'current' }).data().reduce(function(a, b) {
                            return a + parseFloat(b) || 0;    }, 0);
                        $('#totalHrMetaOperaciones').html(totalHrMetaOperaciones.toFixed(1));
                        $('#totalCantidadPlanificada').html(totalCantidadPlanificada.toFixed(1));       }});
            }

        } else {
            console.error('No se recibieron datos válidos.');
        }
    } catch (error) {
        console.error('Error al obtener o cruzar los datos:', error);
    }
} 



    // Función para manejar el cambio de estado
    $('#example tbody').on('click', '.btn-cambiar', function() {
        var idHijOT = $(this).data('id');
        $.ajax({
            url: 'consultasAjax/consulta10.php', method: 'POST', data: { IdHijOT: idHijOT },
            success: function(response) {
                var result = JSON.parse(response);
                alert(result.success ? result.message : 'Error: ' + result.message);
                mainTable.ajax.reload(null, false);
                fetchSubQueryResults(); // Actualizar tabla secundaria
            },
            error: function() { console.error('Error al cambiar el estado'); }
        });
    });

    var subQueryTable = $("#subQueryResults").DataTable({
        ajax: "consultasAjax/consultaValidar.php",
        columns: [
           { data: "ITEM" }, { data: "NPdre" }, { data: "CANTI" }, { data: "HHFab" }, { data: "PesPptoTotaKg" }, { data: "PeSoldaduraKg" },
            { data: "MO001_1" },{ data: "MO001_2" }, { data: "MO002" }, { data: "MO004" }, { data: "MO014" }, { data: "MO008_1" },{ data: "MO008_2" },
            { data: "MO007_1" },{ data: "MO007_2" }, { data: "MO003" }, { data: "MO015" }, { data: "MO005" }, { data: "MO009" },
            { data: "MO004P" }, { data: "MO006" }, { data: "MO012" }, { data: "MO011" }, { data: "MO020" },
            { data: "MO010" },
            { data: "MO050", visible: false, defaultContent: "0", className: "col-proceso-especial" },
            { data: "MO060", visible: false, defaultContent: "0", className: "col-proceso-especial" },
            { data: "MO070", visible: false, defaultContent: "0", className: "col-proceso-especial" },
            { data: "MO080", visible: false, defaultContent: "0", className: "col-proceso-especial" },
            { data: "SUT", render: function(data) { return parseFloat(data).toFixed(1); }}
    ],
    order: [[0, 'asc']]
    });

    // Toggle para mostrar/ocultar Procesos Especiales
    $('#toggleProcesosEspeciales').on('click', function() {
        var $btn = $(this);
        var isActive = $btn.hasClass('active');
        
        // Columnas de procesos especiales (índices 25, 26, 27, 28 - antes de SUT que está en 29)
        var columnsMO050 = subQueryTable.column(25);
        var columnsMO060 = subQueryTable.column(26);
        var columnsMO070 = subQueryTable.column(27);
        var columnsMO080 = subQueryTable.column(28);
        
        if (isActive) {
            // Ocultar columnas
            columnsMO050.visible(false);
            columnsMO060.visible(false);
            columnsMO070.visible(false);
            columnsMO080.visible(false);
            $btn.removeClass('active');
            $btn.find('.toggle-icon').text('+');
            // Ocultar headers del grupo
            $('.th-procesos-especiales').hide();
        } else {
            // Mostrar columnas
            columnsMO050.visible(true);
            columnsMO060.visible(true);
            columnsMO070.visible(true);
            columnsMO080.visible(true);
            $btn.addClass('active');
            $btn.find('.toggle-icon').text('×');
            // Mostrar headers del grupo
            $('.th-procesos-especiales').show();
        }
    });

    // Función para obtener resultados de la subconsulta
    function fetchSubQueryResults(idPadre) {
        $.ajax({
            url: "consultasAjax/consultaValidar.php", method: "POST", data: { idPadre: idPadre, tipoConsulta:1 }, dataType: "json",
            success: function(data) { subQueryTable.clear().rows.add(data).draw(); },
            error: function() { console.error('Error al obtener subconsulta'); }
        }); }



$('#example tbody').on('click', 'tr', function () {
    // Quitar la clase a todas las filas
    $('#example tbody tr').removeClass('fila-seleccionada');
    $(this).addClass('fila-seleccionada');

    // Obtener el ID y llamar a la subconsulta
    var data = mainTable.row(this).data();
    var idPadre = data.IdPadre;
    fetchSubQueryResults(idPadre);
});







$('#subQueryResults tbody').on('dblclick', 'td', function () {
    var cell = subQueryTable.cell(this);  // Obtén la celda donde se hizo doble clic
    var rowData = subQueryTable.row(cell.index().row).data();  // Asegúrate de obtener los datos de la fila correcta

    var columnIndex = cell.index().column;  // Obtén el índice de la columna
    console.log('Columna seleccionada: ' + columnIndex);
    console.log('Datos de la fila:', rowData);  // Verifica que obtienes los datos correctos

    // Definir las columnas editables (incluyendo Procesos Especiales)
    var editableColumns = ["MO001_1", "MO001_2", "MO002", "MO004", "MO014", "MO008_1", "MO008_2", "MO007_1", "MO007_2", "MO003", "MO015", "MO005", "MO009", "MO004P", "MO006", "MO012", "MO011", "MO020", "MO010", "SUT", "MO050", "MO060", "MO070"];

    // Validar que la columna es editable
    if (columnIndex < 6 || columnIndex >= editableColumns.length + 6) return;

    var fieldName = editableColumns[columnIndex - 6];  // Obtener el nombre del campo editable
    
    // No permitir editar SUT (es calculado)
    if (fieldName === "SUT") return;
    
    var originalValue = rowData[fieldName];  // Obtener el valor actual de la celda

    var input = $('<input type="number" class="form-control" />').val(originalValue).on('blur', function() {
        var newValue = $(this).val();
        if (newValue !== originalValue) {
            $.ajax({
                url: "consultasAjax/consulta9.php",  // URL de la actualización
                method: "POST",
                data: {
                    IdHijOT: rowData.IdHijOT,  // Enviar el Id de la fila
                    fieldName: fieldName,  // Enviar el campo que se está actualizando
                    newValue: newValue  // Enviar el nuevo valor
                },
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        cell.data(newValue).draw();  // Actualizar la celda con el nuevo valor
                        fetchSubQueryResults(rowData.IdPadre);  // Recargar la subconsulta
                    } else {
                        alert('Error al actualizar: ' + result.message);
                    }
                },
                error: function() {
                    console.error('Error en la solicitud AJAX');
                }
            });
        } else {
            cell.data(originalValue).draw();  // Restaurar el valor original si no hubo cambios
        }
    });

    // Reemplazar la celda con un input para editar
    $(cell.node()).html(input).focus();
});


});
</script>
<div class="container col-md-12">
    <div class="row">
        <div class="col-md-12"><?php echo display_msg($msg); ?></div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading"><strong><span class="glyphicon glyphicon-th"></span> Lista de validaciones</strong></div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="example" class="table table-striped" style="width:98%">
                            <thead>
                                <tr>
                                    <th style="width:5%;">ID</th><th style="width:10%;">PROYECTO</th><th style="width:30%;">DESCRIPCION</th><th>CORREO</th><th>EXPORTAR</th><th>ESTADO</th><th>CONSU</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>    </div> </div> </div> </div> </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center;">
                    <strong><span class="glyphicon glyphicon-th"></span> Resultados de Subconsulta</strong>
                    <button type="button" class="procesos-especiales-toggle" id="toggleProcesosEspeciales" title="Mostrar/Ocultar Procesos Especiales">
                        <span class="toggle-icon">+</span>
                        <span>Procesos Especiales</span>
                        <span class="proceso-especial-badge">3</span>
                    </button>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="subQueryResults" class="table table-striped" border="1" style="width:110%">
                            <thead>
                                <tr>
                                    <th colspan="1" style="width:2%;"><th colspan="1" style="width:11%;"></th><th colspan="1" style="width:2%;">  <th colspan="1" style="width:3%;"></th><th colspan="1" style="width:3%;"></th><th colspan="1" style="width:3%;"></th>
                                    <th colspan="4" style="width:12%;background-color: #2ecc71; color: black;">HABILITADO META</th>
                                    <th colspan="11" style="width:30%;background-color: #faf854; color: black;">FABRICACIÓN HH META</th>
                                    <th colspan="4" style="width:12%;background-color: #5490fa; color: black;">ACABADOS HH META</th>
                                    <th colspan="4" class="th-procesos-especiales" style="display:none;width:10%;background-color: #ff6b6b; color: white;">PROCESOS ESPECIALES</th>
                                    <th colspan="1" style="width:4%;background-color: #9b59b6; color: white;">TOTAL</th>
                                </tr>
                                <tr> 
                                    <th style="font-size: 14px;">N°</th><th style="font-size: 14px;">Descripción</th><th style="font-size: 14px;">Cant</th><th style="font-size: 14px;">HH. Ppto</th><th style="font-size: 14px;">Peso Ppto</th><th style="font-size: 14px;">Peso Soldadura</th>
                                    <th class="vertical-text" style="background-color: #2ecc71; color: black;width: 70px;font-size: 14px;">Pantógrafo Grande</th>
                                    <th class="vertical-text" style="background-color: #2ecc71; color: black;width: 70px;font-size: 14px;">Pantógrafo Chico</th>
                                    <th class="vertical-text" style="background-color: #2ecc71; color: black;width: 70px;font-size: 14px;">Habilitado Perfiles</th>
                                    <th class="vertical-text" style="background-color: #2ecc71; color: black;width: 70px;font-size: 14px;">Limpieza Mecanica</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">Rolado</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">Torno Grande</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">Torno Chico</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">CNC Grande</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">CNC Chico</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">Armado</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">Soldeo</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">Plantillado</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">Taladro</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">Limpieza Mecanica</th>
                                    <th class="vertical-text" style="background-color: #faf854; color: black;width: 70px;font-size: 14px;">Ensamble</th>
                                    <th class="vertical-text" style="background-color: #5490fa; color: black;width: 70px;font-size: 14px;">Granallado</th>
                                    <th class="vertical-text" style="background-color: #5490fa; color: black;width: 70px;font-size: 14px;">Pintura</th>
                                    <th class="vertical-text" style="background-color: #5490fa; color: black;width: 70px;font-size: 14px;">Decapado Pasivado</th>
                                    <th class="vertical-text" style="background-color: #5490fa; color: black;width: 70px;font-size: 14px;">Embalaje</th>
                                    <th class="vertical-text th-procesos-especiales" style="display:none;background-color: #ff6b6b; color: white;width: 70px;font-size: 14px;">Tratam. Térmico</th>
                                    <th class="vertical-text th-procesos-especiales" style="display:none;background-color: #ff6b6b; color: white;width: 70px;font-size: 14px;">Mecanizado</th>
                                    <th class="vertical-text th-procesos-especiales" style="display:none;background-color: #ff6b6b; color: white;width: 70px;font-size: 14px;">Revestimiento</th>
                                    <th class="vertical-text th-procesos-especiales" style="display:none;background-color: #ff6b6b; color: white;width: 70px;font-size: 14px;">Varios</th>
                                    <th class="vertical-text" style="background-color: #9b59b6; color: white;width: 70px;font-size: 14px;">Horas totales</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Exportar -->
<div class="modal fade" id="Exportar" tabindex="-1" role="dialog" aria-labelledby="exportarModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportarModalLabel">
                    <i class="fa fa-upload"></i> Exportar Datos a SAP
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <!-- Columna Izquierda: Información del Proyecto + Items del Proyecto -->
                    <div class="col-md-6">
                        <!-- Información del Proyecto -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4><i class="fa fa-info-circle"></i> Información del Proyecto</h4>
                            </div>
                            <div class="panel-body">
                                <table id="modalDataTable" class="table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th><i class="fa fa-hashtag"></i> ID Padre</th>
                                            <th><i class="fa fa-code"></i> Código Proyecto</th>
                                            <th><i class="fa fa-file-text"></i> Nombre Proyecto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Aquí se inyectarán los datos de la fila -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Items del Proyecto -->
                        <div class="panel panel-default">
                            <div class="panel-heading clearfix">
                                <h4><i class="fa fa-list"></i> ITEMS DEL PROYECTO</h4>
                            </div>
                            <div class="panel-body table-responsive">
                                <table id="subQueryResults1" class="table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 10%;">N°</th>
                                            <th class="text-center" style="width: 45%;">Cod</th>
                                            <th class="text-center" style="width: 45%;">Items</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Botón Enviar -->
                        <div class="text-center" style="padding: 20px 0;">
                            <button id="enviarDatosBtn" class="btn btn-primary btn-lg">
                                <i class="fa fa-upload"></i> Enviar Datos a SAP
                            </button>
                        </div>
                    </div>
                    
                    <!-- Columna Derecha: Horas y Cantidad Planificada -->
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading clearfix">
                                <h4><i class="fa fa-clock-o"></i> Horas y Cantidad Planificada</h4>
                            </div>
                            <div class="panel-body table-responsive">
                                <table id="crossedTable" class="table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="text-center"><i class="fa fa-users"></i> Grupo</th>
                                            <th class="text-center"><i class="fa fa-cogs"></i> Proceso</th>
                                            <th class="text-center"><i class="fa fa-clock-o"></i> Hr.Meta Operaciones</th>
                                            <th class="text-center"><i class="fa fa-calculator"></i> Cantidad Planificada</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2"><i class="fa fa-sum"></i> Totales</th>
                                            <th class="text-center" id="totalHrMetaOperaciones"></th> 
                                            <th class="text-center" id="totalCantidadPlanificada"></th> 
                                        </tr>
                                    </tfoot>
                                    <tbody></tbody>
                                </table> 
                            </div> 
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <small class="text-muted">
                    <i class="fa fa-info-circle"></i>
                    Revisa los datos antes de exportar a SAP
                </small>
            </div>
        </div>
    </div>
</div>
