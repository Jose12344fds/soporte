<?php
include_once '../Conexion/ConexionHana.php';

// Valor de la variable


// Crear una instancia de la clase de conexión y conectar
$objeto = new Conexion1();
$conexion = $objeto->Conectar1();


    // Escapar las comillas simples en el valor de la variable para evitar problemas


    // Consulta SQL con el valor de la variable directamente insertado
 $consulta = "SELECT   T0.\"CodigoPadre\", MAX(T0.\"NombrePadre\") AS \"NombrePadre\"  FROM \"SBO_TAMAING_PRODUC\".\"TAMA_OFDetalleXItem_PCP\" T0    GROUP BY   T0.\"CodigoPadre\"";

    // Ejecutar la consulta
    $resultado = $conexion->query($consulta);

    // Obtener los datos
    $data = $resultado->fetchALL(PDO::FETCH_ASSOC);

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