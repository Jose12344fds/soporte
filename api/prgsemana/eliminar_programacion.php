<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/* api/prgsemana/eliminar_programacion.php — DELETE por IdPrograma */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasPrgSemana.php';

try {
    $input = $_POST;
    if (!$input) {
        $raw = file_get_contents('php://input');
        $json = json_decode((string)$raw, true);
        if (is_array($json)) $input = $json;
    }
    $id = (int)($input['IdPrograma'] ?? $input['idPrograma'] ?? $input['id_programa'] ?? 0);
    if (!$id) {
        $idPadreOT = (int)($input['idPadreOT'] ?? $input['otpadre'] ?? 0);
        $codPdre = trim((string)($input['codPdre'] ?? $input['CodPdre'] ?? ''));
        $proceso = trim((string)($input['proceso'] ?? $input['CodProceso'] ?? ''));
        $anio = (int)($input['anio'] ?? $input['Aniio'] ?? 0);
        $semana = (int)($input['semana'] ?? $input['Semana'] ?? 0);
        if (!$idPadreOT || $codPdre === '' || $proceso === '' || !$anio || !$semana) {
            echo json_encode(['success'=>false,'message'=>'No se pudo identificar la semana a eliminar. Actualice la programación e inténtelo nuevamente.']);
            exit;
        }
        $pdo = ConexionLocal::conectar();
        $stmt = $pdo->prepare('SELECT IdPrograma FROM programadorsem WHERE otpadre = ? AND CodPdre = ? AND CodProceso = ? AND Aniio = ? AND Semana = ? AND COALESCE(Estado, 1) <> 2 LIMIT 1');
        $stmt->execute([$idPadreOT, $codPdre, $proceso, $anio, $semana]);
        $id = (int)$stmt->fetchColumn();
        if (!$id) {
            echo json_encode(['success'=>false,'message'=>'La semana no existe, ya fue eliminada o está cerrada. Actualice la programación.']);
            exit;
        }
    }
    echo json_encode(ConsultasPrgSemana::eliminarProgramacion($id), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
