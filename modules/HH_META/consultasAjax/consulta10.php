<?php
  include_once '../consultasAjax/conexion.php';
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

try {

$idHijOT = $_POST['IdHijOT']; // ID de la fila a actualizar


    $consulta = "UPDATE otpadre SET Estado_OT =2 WHERE IdPadre = :idHijOT;";
    $stmt = $conexion->prepare($consulta);

    // Vincular los parámetros

    $stmt->bindParam(':idHijOT', $idHijOT);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Se ha valido correctamente, ahora a Exportar a Sap']);
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar los datos: ' . $errorInfo[2]]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conexion = null; // Cerrar la conexión
}