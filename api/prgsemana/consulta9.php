<?php
require_once __DIR__ . '/../hhmeta_guard.php';
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/Conexion.php';
    $conexion = (new Conexion())->Conectar();

    $idHijOT = isset($_POST['IdHijOT']) ? (int)$_POST['IdHijOT'] : 0;
    $campo = trim((string)($_POST['fieldName'] ?? ''));
    $valor = str_replace(',', '.', trim((string)($_POST['newValue'] ?? '')));
    $camposPermitidos = [
        'PorcenCalProy','MO001_1','MO001_2','MO002','MO004','MO014','MO008_1','MO008_2',
        'MO007_1','MO007_2','MO003','MO015','MO005','MO009','MO004P','MO006','MO012',
        'MO011','MO020','MO010','MO050','MO060','MO070','MO080'
    ];

    if ($idHijOT <= 0) throw new RuntimeException('El item del proceso no es valido.');
    if (!in_array($campo, $camposPermitidos, true)) throw new RuntimeException('El campo solicitado no esta permitido.');
    if ($valor === '' || !is_numeric($valor) || (float)$valor < 0) throw new RuntimeException('Ingrese un valor numerico mayor o igual que cero.');

    $conexion->beginTransaction();
    $itemStmt = $conexion->prepare('SELECT IdPadreOT, CodPdre FROM othija WHERE IdHijOT = ? FOR UPDATE');
    $itemStmt->execute([$idHijOT]);
    $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) throw new RuntimeException('No se encontro el item que desea actualizar.');

    $update = $conexion->prepare("UPDATE othija SET `{$campo}` = ? WHERE IdHijOT = ?");
    $update->execute([(float)$valor, $idHijOT]);

    $filasRecalculadas = 0;
    if (strpos($campo, 'MO') === 0) {
        $sync = $conexion->prepare(
            'UPDATE programadorsem
             SET TotHoras = ROUND((COALESCE(Avance, 0) / 100) * ?, 2)
             WHERE otpadre = ? AND CodPdre = ? AND CodProceso = ?'
        );
        $sync->execute([(float)$valor, $item['IdPadreOT'], $item['CodPdre'], $campo]);
        $filasRecalculadas = $sync->rowCount();
    }

    $conexion->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Datos actualizados correctamente.',
        'horas_proceso' => (float)$valor,
        'programaciones_recalculadas' => $filasRecalculadas
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($conexion) && $conexion instanceof PDO && $conexion->inTransaction()) $conexion->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    $conexion = null;
}
