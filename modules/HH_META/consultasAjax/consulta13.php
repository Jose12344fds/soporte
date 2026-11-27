<?php
try {
    include_once '../consultasAjax/conexion.php';
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

$idHijOT = $_POST['IdHijOTC']; // ID de la fila a actualizar
$campoActualizado = $_POST['fieldName']; // Nombre del campo a actualizar
$valorActualizado = $_POST['newValue']; // Nuevo valor para el campo

    error_log("IdHijOT recibido: " . $idHijOT);
    error_log("Campo a actualizar: " . $campoActualizado);
    error_log("Nuevo valor: " . $valorActualizado);

    $consulta = "UPDATE consumibles SET $campoActualizado = :valor WHERE IdHijOTC = :idHijOT;";
    $stmt = $conexion->prepare($consulta);

    // Vincular los parámetros
    $stmt->bindParam(':valor', $valorActualizado);
    $stmt->bindParam(':idHijOT', $idHijOT);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Datos actualizados correctamente.']);
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar los datos: ' . $errorInfo[2]]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conexion = null; // Cerrar la conexión
}