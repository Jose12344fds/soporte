<?php
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();
$consulta = "SELECT `IdUsuAppTama`,`NomUsuario`,`area`,`CuentaTama`,`ContraTama`,`Utilidad`,`Categoria`,`Apphoras`,CONCAT('Recursos:',`ModRecursos`,' - ','Validacion:', `ModValidacion`, ' - ','Servicios:', `ModServicios`, ' - ','Materiales:', `ModMateriales`,' - ','Planificacion:',`ModPlanificacion`,' - ','Usuario:',`ModUsuario`) AS MODULOS,`Estado` FROM `usuariosapptama`;";
$resultado = $conexion->prepare($consulta);
$resultado->execute();
$data=$resultado->fetchAll(PDO::FETCH_ASSOC);

print json_encode($data, JSON_UNESCAPED_UNICODE);//envio el array final el formato json a AJAX
$conexion=null;
?>






