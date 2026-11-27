<?php
include_once 'conexion.php';
$periodo1=intval($_POST['periodo1']);
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$consulta = "SELECT * FROM ctrol_proceso_inyecc T0 INNER JOIN maquina_pro T1 ON T1.`Id_MaquinaPro`=T0.`Id_MaquinaPro` INNER JOIN producto_pro T2 ON T2.`Id_Producto`=T0.`Id_Producto` INNER JOIN material_pro T3 ON T3.`Id_Material`=T0.`Id_Material` INNER JOIN operario_pro T4 ON T4.`Id_Operario`=T0.`Id_Operario` INNER JOIN colores_pro T5 ON T5.`Id_Color`=T0.`Id_Color`  WHERE YEAR(Fecha)=$periodo1  ORDER BY T0.`IdProInyec` DESC";
$resultado = $conexion->prepare($consulta);
$resultado->execute();
$data=$resultado->fetchAll(PDO::FETCH_ASSOC);

print json_encode($data, JSON_UNESCAPED_UNICODE);//envio el array final el formato json a AJAX
$conexion=null;
?>






