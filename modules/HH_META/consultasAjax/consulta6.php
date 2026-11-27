<?php
include_once './conexion.php';
$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Error decoding JSON: ' . json_last_error_msg()]);
    exit;
}

$objeto = new Conexion();
$conexion = $objeto->Conectar();

try {
    // Verificar si el ProjectCode ya existe
    $sqlCheck = "SELECT COUNT(*) FROM otpadre WHERE ProjectCode = :projectCode";
    $stmtCheck = $conexion->prepare($sqlCheck);
    $stmtCheck->bindParam(':projectCode', $data['proyecto']);
    $stmtCheck->execute();
    $result = $stmtCheck->fetchColumn();

    // Si el ProjectCode ya existe, no se realiza la inserción
    if ($result > 0) {
        echo json_encode(['error' => 'ProjectCode already exists.']);
        exit;
    }

    // Inserción si el ProjectCode no existe
    $conexion->beginTransaction();

    $sql = "INSERT INTO otpadre (IdPadre, ProjectCode, NPdre, NivProyecto, Horas, Estado_OT) VALUES (:IdPadre, :projectCode, :Nproyecto, :Nivelproyecto, :Horas, '1')";
    $stmt = $conexion->prepare($sql);

    // Vincula los parámetros
    $stmt->bindParam(':IdPadre', $data['numValuePE']);
    $stmt->bindParam(':projectCode', $data['proyecto']);
    $stmt->bindParam(':Nproyecto', $data['nproyecto']);
    $stmt->bindParam(':Nivelproyecto', $data['nivelpro']);
    $stmt->bindParam(':Horas', $data['HrHnivelpro']);

    // Ejecuta la consulta
    if ($stmt->execute()) {
        $conexion->commit();
        echo json_encode(['message' => 'Data inserted successfully.']);
    } else {
        throw new Exception('Error executing the query: ' . implode(', ', $stmt->errorInfo()));
    }

} catch (PDOException $e) {
    $conexion->rollBack();
    echo json_encode(['error' => 'Error inserting data: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Unexpected error: ' . $e->getMessage()]);
} finally {
    $conexion = null; // Cierra la conexión
}