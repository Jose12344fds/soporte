<?php
// Ajusta la ruta según sea necesario
$file_path = 'conexionloginSap.php';
include_once $file_path;

// Función para actualizar salida de mercancía
function ActualizarSalidaMercancia($token_sesion, $var_json, $ord_dato) {
    $url_actualizar_salida = "https://192.168.250.32:50000/b1s/v1/ProductionOrders($ord_dato)";
    $Campos1 = json_encode($var_json);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_actualizar_salida);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $Campos1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($Campos1),
        'Cookie: B1SESSION=' . $token_sesion
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $data = curl_exec($ch);
    $response = json_decode($data, true);
    $error_message = curl_error($ch);
    curl_close($ch);

    if ($data === false) {
        return 'Error en la solicitud cURL: ' . $error_message;
    } elseif (isset($response['error'])) {
        return 'Error en la actualización SAP: ' . $response['error']['message']['value'];
    } else {
        return 'Solicitud enviada con éxito.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agre_acon'])) {
    $var_json = $_POST['var_json'] ?? null;
    $ord_dato = $_POST['ord_dato'] ?? null;

    // Agregar depuración
    error_log('var_json: ' . print_r($var_json, true));
    error_log('ord_dato: ' . print_r($ord_dato, true));
$FF=$ord_dato+1000;
var_dump($FF);
    // Verificar que las variables no sean nulas
    if ($var_json && $ord_dato) {
        $token_sesion = LoginSAP1();
        $sap_result = ActualizarSalidaMercancia($token_sesion, json_decode($var_json, true), $ord_dato);

        // Retornar resultado
        echo json_encode(['success' => strpos($sap_result, 'éxito') !== false, 'message' => $sap_result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos insuficientes.']);
    }
} else {
    echo json_encode(['error' => 'Método no permitido o formulario no enviado.']);
}
?>
<!DOCTYPE html> 
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Título</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
</head>
<body>

    <div class="container mt-4">
        <div class="input-group mb-3">
            <label class="input-group-text">Ingrese el N de OT a planificar:</label>
            <select id="periodo1" class="form-select js-example-basic-single" required onchange="gg($(this));">
                <option value="">Selecciona una OT</option>
                <?php
                include_once '../Conexion/ConexionHana.php';
                $objeto = new Conexion1();
                $conexion = $objeto->Conectar1();
                $consulta = 'SELECT T0."Project" FROM "ZZZ_TAMAING_SIRE"."OWOR" T0 WHERE T0."Status"= \'R\' GROUP BY T0."Project"';
                $resultado = $conexion->prepare($consulta);
                $resultado->execute();
                $data1 = $resultado->fetchAll(PDO::FETCH_ASSOC);
                foreach ($data1 as $usu): ?>
                    <option data-name1="<?php echo $usu['Project']; ?>" value="<?php echo $usu['Project']; ?>">
                        <?php echo $usu['Project']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading clearfix">
                        <strong>Item's</strong>
                    </div>
                    <div class="panel-body table-responsive">
                        <table id="subQueryResults" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center">N°</th>
                                    <th class="text-center">Items</th>
                                    <th class="text-center">DocEntry</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading clearfix">
                        <strong>Item's</strong>
                    </div>
                    <div class="panel-body table-responsive">
                        <table id="subQueryResults2" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center">Items</th>
                                    <th class="text-center">Cantidad Planificada</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="BD.php" class="clearfix">   
        <button type="submit" name="agre_acon" class="btn btn-danger col-md-2" style="margin: 10px;">Agregar</button>
    </form>

    <script type="text/javascript">
        let subQueryTable;
        let subQueryTable2;

        $(document).ready(() => {
            $('.js-example-basic-single').select2();
        });

        window.gg = function(combo) {
            const proyecto = combo.find(':selected').data('name1');
            $.ajax({
                data: { valorCaja1: proyecto },
                url: 'ConsultaSAP.php',
                type: 'post',
                success: function(data) {
                    initializeDataTable(data);
                },
                error: function(xhr, status, error) {
                    console.error("Error en la consulta: ", error);
                }
            });
        }

        function initializeDataTable(data) {
            if ($.fn.DataTable.isDataTable('#subQueryResults')) {
                $('#subQueryResults').DataTable().destroy();
            }

            subQueryTable = $("#subQueryResults").DataTable({
                data: data,
                columns: [
                    { data: "ItemCode" },
                    { data: "ProdName" },
                    { data: "DocEntry" }
                ],
                searching: false,
                info: false,
                paging: false
            });
        }

        let subQueryData11 = [];
        let docEntryGlobal = ""; // Variable para almacenar DocEntry

        function fetchSubQueryResults2(DocEntry1, ItemCode1) {
            docEntryGlobal = DocEntry1;
            $.ajax({
                url: "ConsultaSAP1.php",
                method: "POST",
                data: { DocEnt: DocEntry1, ItemCod: ItemCode1 },
                dataType: "json",
                success: function(data) {
                    subQueryData11 = data.map(item => ({
                        M0: item.M0,
                        PlannedQty: item.PlannedQty,
                        Line: item.Line
                    }));

                    // Inicializa la tabla solo después de haber configurado subQueryData
                    if ($.fn.DataTable.isDataTable('#subQueryResults2')) {
                        $('#subQueryResults2').DataTable().destroy();
                    }

                    // Inicializa la tabla con los datos
                    subQueryTable2 = $("#subQueryResults2").DataTable({
                        data: subQueryData11,
                        columns: [
                            { data: "M0" },
                            { data: "PlannedQty" }
                        ],
                        searching: false,
                        info: false,
                        paging: false
                    });

                    useSubQueryData(docEntryGlobal, subQueryData11); 
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error al obtener subconsulta:', textStatus, errorThrown);
                }
            });
        }

function useSubQueryData(docEntryGlobal, subQueryData11) {
    const datos_procesados = subQueryData11.map(item => ({
        LineNumber: parseInt(item.Line, 10),
        ItemNo: item.M0,
        BaseQuantity: parseFloat(item.PlannedQty).toString()
    }));
    
    const var_json = {
        ProductionOrderStatus: "R",
        Remarks: "U444",
        ProductionOrderLines: datos_procesados
    };

    console.log('Datos a enviar:', datos_procesados, docEntryGlobal); // Añadir un log para verificar datos

    $.ajax({
        url: 'BD.php',
        method: 'POST',
        data: {
            var_json: JSON.stringify(datos_procesados),
            ord_dato: docEntryGlobal
        },
        success: function(respuesta) {
            console.log("Resultado de la actualización:", respuesta);
            // Aquí puedes manejar la respuesta, mostrar un mensaje, etc.
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error al enviar datos:", textStatus, errorThrown);
        }
    });
}

        $('#subQueryResults tbody').on('click', 'tr', function () {
            var data = subQueryTable.row(this).data();
            if (data) {
                var DocEntry1 = data.DocEntry;
                var ItemCode1 = data.ItemCode;
                fetchSubQueryResults2(DocEntry1, ItemCode1);
            }
        });
    </script>

</body>
</html>
