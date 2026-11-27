<?php
include_once '../Conexion/ConexionHana.php';

// Valor de la variable
$periodo1 = $_POST['valorCaja1'];

// Crear una instancia de la clase de conexión y conectar
$objeto = new Conexion1();
$conexion = $objeto->Conectar1();


    // Escapar las comillas simples en el valor de la variable para evitar problemas


    // Consulta SQL con el valor de la variable directamente insertado
    $consulta = "SELECT T0.\"Proyecto1\" AS PROY  FROM \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectos\" T0 WHERE T0.\"OT\" = '$periodo1'";

    // Ejecutar la consulta
    $resultado = $conexion->query($consulta);

    // Obtener los datos
    $data = $resultado->fetch(PDO::FETCH_ASSOC);


// Enviar el array final en formato JSON a AJAX
print json_encode($data, JSON_UNESCAPED_UNICODE);//envio el array final el formato json a AJAX
  
    // Configurar el encabezado de la respuesta HTTP para UTF-8


    // Enviar el array final en formato JSON a AJAX
  
$conexion = null;
?>