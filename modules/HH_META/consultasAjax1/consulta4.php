<?php
header('Content-Type: application/json; charset=utf-8');
include_once '../consultasAjax/conexion.php';

// Obtener el id del programa desde el POST
$idPrograma = $_POST['IdPrograma'] ?? null;

// Log para debugging (temporal)
error_log("CONSULTA4.PHP - IdPrograma recibido: " . $idPrograma);

// Validar que se recibió el IdPrograma
if (!$idPrograma) {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió el IdPrograma'
    ]);
    exit;
}

try {
    // Crear la conexión
    $objeto   = new Conexion();
    $conexion = $objeto->Conectar();

    // Eliminación directa e independiente de otras tablas
    $consulta = "DELETE FROM programadorsem WHERE IdPrograma = :idPrograma";
    $resultado = $conexion->prepare($consulta);
    $id = (int)$idPrograma;
    $resultado->bindParam(':idPrograma', $id, PDO::PARAM_INT);

    if ($resultado->execute()) {
        if ($resultado->rowCount() > 0) {
            error_log("CONSULTA4.PHP - Eliminación exitosa de IdPrograma: " . $id);
            echo json_encode([
                'success' => true,
                'message' => 'Registro eliminado correctamente'
            ]);
        } else {
            error_log("CONSULTA4.PHP - No se encontró registro con IdPrograma: " . $id);
            echo json_encode([
                'success' => false,
                'message' => 'No se encontró el registro con ID: ' . $id
            ]);
        }
    } else {
        $errorInfo = $resultado->errorInfo();
        error_log("CONSULTA4.PHP - Error al ejecutar DELETE: " . ($errorInfo[2] ?? 'desconocido'));
        echo json_encode([
            'success' => false,
            'message' => 'Error al ejecutar la eliminación'
        ]);
    }
}
catch (Exception $e) {
    error_log('CONSULTA4.PHP - Excepción: ' . $e->getMessage());
    http_response_code(200); // aseguramos 200 para que el cliente pueda leer el JSON
    echo json_encode([
        'success' => false,
        'message' => 'Excepción del servidor: ' . $e->getMessage()
    ]);
}

if (isset($conexion)) { $conexion = null; }
?>
