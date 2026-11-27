<?php
include_once '../Conexion/ConexionHana.php';

// Valor de la variable (GET o POST)
$periodo1 = isset($_REQUEST['periodo1']) ? $_REQUEST['periodo1'] : ''; 

// Crear una instancia de la clase de conexión y conectar
$objeto = new Conexion1();
$conexion = $objeto->Conectar1();

// Consulta para obtener el número total de paneles
$countQuery = "
    SELECT T0.\"Cantd\" AS cantidadx     
    FROM \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectosPCP\" T0
    WHERE T0.\"OT\" = '$periodo1'
    GROUP BY T0.\"Cantd\"
";

try {
    $countResult = $conexion->query($countQuery);
    $countData = $countResult->fetch(PDO::FETCH_ASSOC);
    $totalPanels = isset($countData['CANTIDADX']) ? (int)$countData['CANTIDADX'] : 0;
} catch (PDOException $e) {
    echo json_encode(array('error' => 'Error en la consulta: ' . $e->getMessage()));
    exit;
}

// Consulta para obtener los detalles de cada panel (único por ItemOT)
$detailsQuery = "
    SELECT 
        T0.\"ItemOT\" AS ID, 
        T0.\"Proyecto11\" AS TITLE,
        T0.\"Cdigproducto\" AS CODPRODUC,
        T0.\"Descripci\" AS DESC,
        T0.\"NumOF\" AS NUMOF,
        T0.\"NroSemIngresOT\" AS SEM,
        T0.\"Cantd\" AS CANTI,
        T0.\"ItemOT\" AS ITEM,
        T0.\"FechaIngreOC\" AS FEAING,
        T0.\"FecReqCli\"  AS FEAREQU,
        T0.\"NumerOCenviacliente\"  AS OC,

          MAX(T0.\"HHFab\") AS HHFABB,
      MAX(T0.\"PesPptoTotaKg\") AS PESPPTOTAKGG,
      MAX(T0.\"PeSoldaduraKg\") AS PESOLDADURAKGG,

        MAX(T1.\"PesoNetoTotalKg\")  AS PESONETOTOTALKG,
        MAX(T1.\"RendimientohhtonPpto\")  AS RENDIMEINTOHHTONPPTO,                 
        MAX(T1.\"Fecha_Terminada\")  AS FECHATERMINADA,
        MAX(T1.\"Estado\")  AS ESTADO
    FROM \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectosPCP\" T0
    LEFT JOIN  \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyHoras_pcp\" T1 
        ON T0.\"OT\" = T1.\"OT\" 
       AND T0.\"Cdigproducto\" = T1.\"Cdproducto\"
    WHERE T0.\"OT\" = '$periodo1'
    GROUP BY 
        T0.\"ItemOT\", T0.\"Proyecto11\", T0.\"Cdigproducto\", 
        T0.\"Descripci\", T0.\"NumOF\", T0.\"NroSemIngresOT\", 
        T0.\"Cantd\", T0.\"FechaIngreOC\", T0.\"FecReqCli\", 
        T0.\"NumerOCenviacliente\", T0.\"HHFab\", T0.\"PesPptoTotaKg\", T0.\"PeSoldaduraKg\"
";

try {
    $detailsResult = $conexion->query($detailsQuery);
    $detailsData = $detailsResult->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json; charset=utf-8');

    // Limpiar las fechas
    $detailsData = array_map(function($item) {
        $item['FEAING'] = str_replace('\/', '/', $item['FEAING']);
        $item['FEAREQU'] = str_replace('\/', '/', $item['FEAREQU']);
        $item['FECHATERMINADA'] = str_replace('\/', '/', $item['FECHATERMINADA']);

        if ($item['FEAING']) $item['FEAING'] = date("Y/m/d", strtotime($item['FEAING']));
        if ($item['FEAREQU']) $item['FEAREQU'] = date("Y/m/d", strtotime($item['FEAREQU']));
        if ($item['FECHATERMINADA']) $item['FECHATERMINADA'] = date("Y/m/d", strtotime($item['FECHATERMINADA']));

        return array_map('utf8_encode', $item);
    }, $detailsData);

} catch (PDOException $e) {
    echo json_encode(array('error' => 'Error en la consulta: ' . $e->getMessage()));
    exit;
}

// Crear un array final con el conteo y los detalles
$response = array(
    'total' => $totalPanels,
    'details' => $detailsData
);

// Enviar el JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Cerrar la conexión
$conexion = null;
?>
