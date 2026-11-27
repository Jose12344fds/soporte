<?php
require_once __DIR__ . '/../hhmeta_guard.php';
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/Conexion.php';
require_once __DIR__.'/sincronizar_avance.php';

try {
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

    // Recibir ids desde POST (array ids[])
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids) || count($ids) === 0) {
        // Intentar decodificar JSON crudo
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (isset($json['ids']) && is_array($json['ids'])) {
            $ids = $json['ids'];
        }
    }

    // Normalizar y validar
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)$ids), function($v){ return $v > 0; })));
    if (count($ids) === 0) {
        echo json_encode(['success' => false, 'message' => 'No se recibieron IDs válidos']);
        exit;
    }

    // Construir placeholders dinámicos
    $placeholders = [];
    foreach ($ids as $i => $id) { $placeholders[] = ':id' . $i; }
    $lookup=$conexion->prepare('SELECT DISTINCT otpadre AS idPadreOT,CodPdre AS codPdre FROM programadorsem WHERE IdPrograma IN ('.implode(',', $placeholders).')');
    foreach($ids as $i=>$id)$lookup->bindValue(':id'.$i,$id,PDO::PARAM_INT);$lookup->execute();$items=$lookup->fetchAll(PDO::FETCH_ASSOC);
    $sql = 'UPDATE programadorsem SET Estado = 2 WHERE IdPrograma IN (' . implode(',', $placeholders) . ')';
    $stmt = $conexion->prepare($sql);
    foreach ($ids as $i => $id) { $stmt->bindValue(':id' . $i, $id, PDO::PARAM_INT); }

    if (!$stmt->execute()) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception($errorInfo[2] ?? 'Error al ejecutar actualización');
    }

    $sapSync=psSincronizarItems($conexion,$items);
    echo json_encode([
        'sapSync'=>$sapSync,'warnings'=>$sapSync['warnings'],
        'success' => true,
        'afectados' => $stmt->rowCount(),
        'ids' => $ids
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conexion = null;
}
