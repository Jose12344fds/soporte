<?php
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();
$year = filter_input(INPUT_GET, 'anio', FILTER_VALIDATE_INT);
$sql = "SELECT *, CONCAT(Fecha,' ',Hora) AS dateff FROM vpn";
$params = [];
if ($year && $year >= 2000 && $year <= ((int)date('Y') + 1)) {
    $sql .= ' WHERE YEAR(Fecha) = ?';
    $params[] = $year;
}
$sql .= ' ORDER BY Fecha DESC, Hora DESC, Id_IngVpn DESC';
$resultado = $conexion->prepare($sql);
$resultado->execute($params);
$data = $resultado->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = null;
