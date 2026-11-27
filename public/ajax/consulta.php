<?php
include_once 'conexion.php';

$objeto = new Conexion();
$conexion = $objeto->Conectar();

$consulta = "SELECT * FROM usuariosf08  ";
$resultado = $conexion->prepare($consulta);
$resultado->execute();
$data1=$resultado->fetchAll(PDO::FETCH_ASSOC);

print json_encode($data1, JSON_UNESCAPED_UNICODE);//envio el array final el formato json a AJAX
$conexion=null;
?>






