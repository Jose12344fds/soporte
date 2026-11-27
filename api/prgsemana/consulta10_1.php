<?php
require_once __DIR__ . '/../hhmeta_guard.php';
try {
    
    require_once __DIR__ . '/Conexion.php';
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

$idHijOT = $_POST['IdHijOT']; // ID de la fila a actualizar


    $consulta = "UPDATE otpadre SET Estado_OT = 1 WHERE IdPadre = :idHijOT;";
    $stmt = $conexion->prepare($consulta);

    // Vincular los parámetros

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
