<?php
include_once '../Conexion/ConexionHana.php';

// Valor de la variable

$periodo1 = $_POST['periodo1'];

// Crear una instancia de la clase de conexión y conectar
$objeto = new Conexion1();
$conexion = $objeto->Conectar1();

    // Consulta SQL con el valor de la variable directamente insertado
   $consulta = " select T0.\"OT\", T0.\"Descripci\",T0.\"Nivelproyecto\",T0.\"Cantd\",T0.\"PesPptoTotaKg\",T0.\"HHFab\",T0.\"PeSoldaduraKg\" from \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectosPCP\" T0
 WHERE T0.\"Cdigproducto\" = '$periodo1'
";

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