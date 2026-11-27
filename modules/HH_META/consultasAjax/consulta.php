<?php
include_once '../Conexion/ConexionHana.php';
// Valor de la variable
$periodo1 = $_POST['periodo1'];


// Crear una instancia de la clase de conexión y conectar
$objeto = new Conexion1();
$conexion = $objeto->Conectar1();
$consulta = "SELECT 
         T0.\"OT\" ,T0.\"Cliente\" ,T0.\"Proyecto11\" , T0.\"Cantd\" as \"PlannedParentQuantity\",T0.\"Cdigproducto\",T0.\"Descripci\",T0.\"PlanodelclienteTAMA\", T0.\"Tipoproyecto\", T0.\"NumOF\", T0.\"ItemOT\", T0.\"Cantd\" ,T0.\"Year\",T0.\"HHFab\", T0.\"PesPptoTotaKg\", T0.\"PeSoldaduraKg\", T0.\"PesPptoUniKg\", T0.\"NroSemIngresOT\" AS \"SEM\", T0.\"Cantd\" AS \"CANTI\", T0.\"FechaIngreOC\", T0.\"FecReqCli\", T0.\"FecReqCli\", T0.\"NumerOCenviacliente\" ,T0.\"Estado\", T0.\"RendihtoPpto\", T0.\"Fecha_Terminada\", T0.\"OT\" AS \"CodProyect\" , T0.\"PeNetTotKg\"

  FROM \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectosPCP\" T0 WHERE T0.\"OT\" = '$periodo1'";

    // Ejecutar la consulta
    $resultado = $conexion->query($consulta);

    // Obtener los datos
    $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json; charset=utf-8');
    
    $data = array_map(function($item) {
    return array_map('utf8_encode', $item);
}, $data);


// Enviar el array final en formato JSON a AJAX
print json_encode($data, JSON_UNESCAPED_UNICODE);//envio el array final el formato json a AJAX
  
    // Configurar el encabezado de la respuesta HTTP para UTF-8


    // Enviar el array final en formato JSON a AJAX
  
$conexion = null;
?>