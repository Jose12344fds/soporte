<?php
include_once '../Conexion/ConexionHana.php';

// Valor de la variable
$periodo1 = $_POST['valorCaja1'];

// Crear una instancia de la clase de conexión y conectar
$objeto = new Conexion1();
$conexion = $objeto->Conectar1();

// Consulta SQL con el valor de la variable directamente insertado
$consulta = "SELECT  *
FROM \"SBO_TAMAING_PRODUC\".\"OWOR\" T0
WHERE T0.\"Project\" = '$periodo1' AND T0.\"Warehouse\"='PRODU' ";

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