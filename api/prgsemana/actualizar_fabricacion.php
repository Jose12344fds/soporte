<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * Actualizar el campo fabricacion en programadorsem
 */
require_once __DIR__ . '/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_POST['idPrograma']) || !isset($_POST['fabricacion'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos']);
        exit;
    }
    
    $idPrograma = $_POST['idPrograma'];
    $fabricacion = $_POST['fabricacion'];
    
    // Validar que fabricacion sea un valor válido
    if (!in_array($fabricacion, ['tama', 'svt'])) {
        echo json_encode(['success' => false, 'message' => 'Valor de fabricacion inválido']);
        exit;
    }
    
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();
    
    // Verificar que el registro exista y esté abierto (Estado = 1)
    $checkQuery = "SELECT Estado FROM programadorsem WHERE IdPrograma = :idPrograma";
    $checkStmt = $conexion->prepare($checkQuery);
    $checkStmt->execute([':idPrograma' => $idPrograma]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
        exit;
    }
    
    if ($row['Estado'] == 2) {
        echo json_encode(['success' => false, 'message' => 'No se puede modificar una semana cerrada']);
        exit;
    }
    
    // Actualizar fabricacion (columna puede ser Fabricacion con F mayúscula)
    $updateQuery = "UPDATE programadorsem SET Fabricacion = :fabricacion WHERE IdPrograma = :idPrograma";
    $updateStmt = $conexion->prepare($updateQuery);
    $updateStmt->execute([
        ':fabricacion' => $fabricacion,
        ':idPrograma' => $idPrograma
    ]);
    
    if ($updateStmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Fabricación actualizada correctamente']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Sin cambios (mismo valor)']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
