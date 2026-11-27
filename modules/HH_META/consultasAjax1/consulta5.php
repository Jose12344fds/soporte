<?php 
// Proteger salida y evitar que errores PHP impriman HTML inesperado
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

include_once '../consultasAjax/conexion.php';

// Obtener los datos del formulario
$idPrograma = isset($_POST['idPrograma']) ? $_POST['idPrograma'] : null;
$field      = isset($_POST['field']) ? $_POST['field'] : null;
$newValue   = isset($_POST['newValue']) ? $_POST['newValue'] : null;

$objeto   = new Conexion();
$conexion = $objeto->Conectar();

try {
    if ($field === "Semana") {
        // Primero obtener los valores actuales del registro en programadorsem
        $stmtProg = $conexion->prepare("SELECT otpadre, CodPdre, CodProceso, Semana FROM programadorsem WHERE IdPrograma = :idPrograma LIMIT 1");
        $stmtProg->bindParam(':idPrograma', $idPrograma, PDO::PARAM_INT);
        $stmtProg->execute();
        $progRow = $stmtProg->fetch(PDO::FETCH_ASSOC);

        if (!$progRow) {
            echo json_encode(['success' => false, 'message' => 'Registro programadorsem no encontrado']);
            exit;
        }

        // Verificar si la tabla registroconsumibles tiene la columna 'semana'
        $colCheck = $conexion->query("SHOW COLUMNS FROM registroconsumibles LIKE 'semana'");
        $hasSemanaCol = ($colCheck && $colCheck->rowCount() > 0);

        // Actualizar programadorsem
        $consultaProg = "UPDATE programadorsem SET Semana = :newValue WHERE IdPrograma = :idPrograma";
        $stmtUpdateProg = $conexion->prepare($consultaProg);
        $stmtUpdateProg->bindParam(':newValue', $newValue);
        $stmtUpdateProg->bindParam(':idPrograma', $idPrograma, PDO::PARAM_INT);
        $stmtUpdateProg->execute();

        // Si existe columna semana en registroconsumibles, actualizar allí también (solo filas que coincidan con otpadre, codpadre y proceso y la semana anterior)
        if ($hasSemanaCol) {
            $oldSemana = $progRow['Semana'];
            $consultaReg = "UPDATE registroconsumibles SET semana = :newValue WHERE otpadreCon = :otpadre AND codpadre = :codPdre AND Proceso = :codProceso AND semana = :oldSemana";
            $stmtUpdateReg = $conexion->prepare($consultaReg);
            $stmtUpdateReg->bindParam(':newValue', $newValue);
            $stmtUpdateReg->bindParam(':otpadre', $progRow['otpadre']);
            $stmtUpdateReg->bindParam(':codPdre', $progRow['CodPdre']);
            $stmtUpdateReg->bindParam(':codProceso', $progRow['CodProceso']);
            $stmtUpdateReg->bindParam(':oldSemana', $oldSemana);
            $stmtUpdateReg->execute();
        }

        echo json_encode(['success' => true]);
        $conexion = null;
        exit;
    } else {
        // Otros campos: siempre programadorsem
        $consulta = "UPDATE programadorsem SET $field = :newValue WHERE IdPrograma = :idPrograma";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindParam(':idPrograma', $idPrograma, PDO::PARAM_INT);
        $resultado->bindParam(':newValue', $newValue, PDO::PARAM_STR);
        $resultado->execute();

        if ($resultado->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        $conexion = null;
        exit;
    }
} catch (Exception $e) {
    // Log y devolver JSON limpio
    error_log('consulta5.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    $conexion = null;
    exit;
}
?>
