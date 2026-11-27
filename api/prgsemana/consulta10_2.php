<?php
require_once __DIR__ . '/../hhmeta_guard.php';
try {
    
    require_once __DIR__ . '/Conexion.php';
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

    $idHijOT = $_POST['IdHijOT']; // ID de la fila a actualizar

    // VALIDACIÓN: Verificar que todas las semanas programadas estén cerradas
    $consultaValidacion = "SELECT COUNT(*) as total_pendientes 
                          FROM programadorsem 
                          WHERE otpadre = :idHijOT 
                          AND Estado != 2";
    
    $stmtValidacion = $conexion->prepare($consultaValidacion);
    $stmtValidacion->bindParam(':idHijOT', $idHijOT);
    $stmtValidacion->execute();
    $resultado = $stmtValidacion->fetch(PDO::FETCH_ASSOC);
    
    // Si hay semanas pendientes, no permitir cerrar el proyecto
    if ($resultado['total_pendientes'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'No se puede cerrar el proyecto. Aún hay ' . $resultado['total_pendientes'] . ' semana(s) de procesos sin cerrar. Por favor, cierra todas las semanas programadas antes de cerrar el proyecto.'
        ]);
        exit;
    }

    // Si todas las semanas están cerradas, proceder con el cierre del proyecto
    $consulta = "UPDATE otpadre SET Estado_OT = 3 WHERE IdPadre = :idHijOT;";
    $stmt = $conexion->prepare($consulta);
    $stmt->bindParam(':idHijOT', $idHijOT);

    $consulta2 = "UPDATE otpadre T0
    LEFT JOIN programadorsem T1 ON T0.IdPadre = T1.otpadre
    SET T1.Estado = 2
    WHERE T0.IdPadre = :idHijOT1 ;";
    $stmt2 = $conexion->prepare($consulta2);
    $stmt2->bindParam(':idHijOT1', $idHijOT);

    // Ejecutar la consulta
    if ($stmt->execute() && $stmt2->execute()) {
        echo json_encode(['success' => true, 'message' => 'Proyecto cerrado correctamente. Todas las semanas han sido cerradas.']);
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar los datos: ' . $errorInfo[2]]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conexion = null; // Cerrar la conexión
}
