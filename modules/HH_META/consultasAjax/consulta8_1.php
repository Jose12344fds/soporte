<?php
include_once '../consultasAjax/conexion.php';

try {
    // Verifica si idPadre está definido
    if (isset($_POST['idPadre'])) {
        $idPadre = $_POST['idPadre'];

        $objeto = new Conexion();
        $conexion = $objeto->Conectar();

        $consulta = "SELECT T0.*,T0.ProjectCode AS proyecto, T1.*,(T1.MO001_1+T1.MO001_2+T1.MO002+T1.MO004+T1.MO014+T1.MO008_1+T1.MO008_2+T1.MO007_1+T1.MO007_2) AS SUT
                     FROM otpadre T0 
                         left JOIN othija T1 ON T0.IdPadre = T1.IdPadreOT 
                
                     WHERE T0.IdPadre = :idPadre"; // Cambia la consulta según tus necesidades
        $stmt = $conexion->prepare($consulta);
        $stmt->bindParam(':idPadre', $idPadre);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Devuelve los datos o un array vacío si no hay resultados
        echo json_encode($data ?: [], JSON_UNESCAPED_UNICODE);
    } else {
        // Si no se recibe idPadre, devuelve un array vacío
        echo json_encode([], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    $conexion = null; // Cierra la conexión
}
?>