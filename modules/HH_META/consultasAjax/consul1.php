


<?php
include_once '../consultasAjax/conexionAjax.php';

$objeto = new Conexion();
$conexion = $objeto->Conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
    $estado = isset($_POST['estado']) ? $_POST['estado'] : 0;

    if (!empty($ids)) {
        $ids = implode(',', array_map('intval', $ids));
        $query = "UPDATE DETACONSU_EPP SET EST_DETA = :estado WHERE ID_DETALLECONSUMO IN ($ids)";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } else {
        echo json_encode(['success' => false]);
    }
}
?>