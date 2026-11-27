<?php
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$consulta = "SELECT * FROM usuariossap T0 INNER JOIN usuariotreminalsap T1 ON `T1`.`UsuTerminal`=`T0`.`CuentaTer` INNER JOIN users T2 ON `T2`.`name`=`T0`.`NomUsuario`";
$resultado = $conexion->prepare($consulta);
$resultado->execute();
$data=$resultado->fetchAll(PDO::FETCH_ASSOC);

print json_encode($data, JSON_UNESCAPED_UNICODE);//envio el array final el formato json a AJAX
$conexion=null;
?>



