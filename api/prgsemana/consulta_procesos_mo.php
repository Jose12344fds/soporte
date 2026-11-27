<?php
require_once __DIR__ . '/../hhmeta_guard.php';
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';

$idPadreOT  = $_POST['idPadreOT']  ?? null;
$codPdre    = $_POST['codPdre']    ?? null;
$projectCode = $_POST['projectCode'] ?? null;

if (!$idPadreOT || !$codPdre) {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros idPadreOT o codPdre']);
    exit;
}

try {
    $db = new Conexion();
    $cn = $db->Conectar();

    $sql = "SELECT * FROM othija WHERE IdPadreOT = :idPadreOT AND CodPdre = :codPdre";
    $params = [':idPadreOT' => $idPadreOT, ':codPdre' => $codPdre];

    if ($projectCode) {
        $sql .= " AND ProjectCode = :projectCode";
        $params[':projectCode'] = $projectCode;
    }

    $stmt = $cn->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron registros']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $cn = null;
}
