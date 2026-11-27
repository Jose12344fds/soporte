<?php
include_once '../consultasAjax/conexion.php';

// Obtener los datos enviados por POST
$idPrograma = $_POST['idPrograma'];
$totalHoras = $_POST['totalHoras'];

$objeto = new Conexion();
$conexion = $objeto->Conectar();

// Consulta SQL para actualizar el campo TotHoras
$consulta = "UPDATE programadorsem SET TotHoras = :totalHoras WHERE IdPrograma = :idPrograma";
$resultado = $conexion->prepare($consulta);

// Vincular parámetros
$resultado->bindParam(':totalHoras', $totalHoras, PDO::PARAM_STR);
$resultado->bindParam(':idPrograma', $idPrograma, PDO::PARAM_INT);

// Ejecutar la consulta
if ($resultado->execute()) {
    echo json_encode(["status" => "success", "message" => "TotalHoras actualizado correctamente"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al actualizar TotalHoras"]);
}

$conexion = null;
?>