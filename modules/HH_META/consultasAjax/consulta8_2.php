<?php
include_once '../consultasAjax/conexion.php';

try {
    // Verifica si idPadre está definido
    if (isset($_POST['idPadre'])) {
        $idPadre = $_POST['idPadre'];

        $objeto = new Conexion();
        $conexion = $objeto->Conectar();

        $consulta = "SELECT T0.*,T0.ProjectCode AS proyecto, T1.*,(T1.MO003+T1.MO015+T1.MO005+T1.MO009+T1.MO004P+T1.MO006+T1.MO012+T1.MO011+T1.MO020+T1.MO010) AS SUT
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