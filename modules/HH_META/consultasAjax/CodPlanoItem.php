<?php
include_once '../Conexion/ConexionHana.php';

// Valor de la variable


// Crear una instancia de la clase de conexión y conectar
$objeto = new Conexion1();
$conexion = $objeto->Conectar1();


    // Escapar las comillas simples en el valor de la variable para evitar problemas


    // Consulta SQL con el valor de la variable directamente insertado
 $consulta = "                    
        SELECT  T2.\"PlanodelclienteTAMA\", max(T0.\"CodigoPadre\")  as \"CodigoPadre\"
        FROM \"SBO_TAMAING_PRODUC\".\"TAMA_OFDetalleXItem_PCP\" T0 
         LEFT JOIN \"SBO_TAMAING_PRODUC\".\"TAMA_CONTROL_PROYECTOS\" T1 ON T1.\"Cod Proyecto\" = T0.\"CodProy\"
         LEFT JOIN \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectosPCP\" T2 ON T2.\"Cdigproducto\" = T0.\"CodigoPadre\" AND  T2.\"OT\" =  T0.\"CodProy\"
          GROUP BY   T2.\"PlanodelclienteTAMA\"
         ";

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