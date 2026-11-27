<?php
require_once __DIR__ . '/../../hhmeta_guard.php';
/**
 * API JSON: Guardar items hija (othija)
 * INSERT múltiple en tabla othija con verificación de duplicados
 * 
 * Recibe JSON: [ { ITEM, Year, ProjectCode, CodPdre, NPdre, ... }, ... ]
 * Responde JSON: { ok, total, inserted, skipped, errors, detail[] }
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

try {
    // 1. Leer y validar JSON de entrada
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        ob_end_clean();
        echo json_encode([
            'ok' => false,
            'error' => 'JSON inválido',
            'detail' => json_last_error_msg(),
            'raw_input' => substr($raw, 0, 500)
        ]);
        exit;
    }

    if (!is_array($data) || count($data) === 0) {
        ob_end_clean();
        echo json_encode([
            'ok' => false,
            'error' => 'Se esperaba un array JSON con al menos 1 elemento',
            'received_type' => gettype($data)
        ]);
        exit;
    }

    // 2. Conectar a BD
    $conexion = getConexionLocal();
    require_once __DIR__ . '/nombre_completo.php';
    hhmeta_asegurar_nombre_completo($conexion);

    // 3. Preparar queries
    $sqlCheckPadre = "SELECT IdPadreOT FROM otpadre WHERE ProjectCode = :pc";
    $stmtCheckPadre = $conexion->prepare($sqlCheckPadre);

    $sqlCheckDup = "SELECT COUNT(*) FROM othija WHERE OTPADRE = :otpadre AND CodPdre = :codpdre AND NumOF = :numof";
    $stmtCheckDup = $conexion->prepare($sqlCheckDup);

    $sqlInsert = "INSERT INTO othija (
        Tipo, Year, ProjectCode, CodPdre, NPdre, ClientName, 
        Cantidad, HHFab, PesPptoTotaKg, PeSoldaduraKg,
        MO001_1, MO001_2, MO002, MO004, MO014, MO008_1, MO008_2, MO007_1, MO007_2, 
        MO003, MO015, MO005, MO009, MO004P, MO006, 
        MO012, MO011, MO020, MO010, 
        MO050, MO060, MO070, MO080,
        PesPptoUniKg, NumOF, SEM, CANTI, ITEM, 
        FEAING, FEAREQU, OC, ESTADO, 
        PESONETOTOTALKG, RENDIMEINTOHHTONPPTO, FECHATERMINADA, 
        OTPADRE, Estado_OT, IdPadreOT, PorcenCalProy
    ) VALUES (
        :tipo, :year, :projectCode, :codPdre, :npdre, :clientName, 
        :cantidad, :HHFab, :PesPptoTotaKg, :PeSoldaduraKg,
        :mo001_1, :mo001_2, :mo002, :mo004, :mo014, :mo008_1, :mo008_2, :mo007_1, :mo007_2,
        :mo003, :mo015, :mo005, :mo009, :mo004P, :mo006, 
        :mo012, :mo011, :mo020, :mo010, 
        0, 0, 0, 0,
        :PesPptoUniKg, :NumOF, :SEM, :CANTI, :ITEM,
        :FEAING, :FEAREQU, :OC, :ESTADO,
        :PESONETOTOTALKG, :RENDIMEINTOHHTONPPTO, :FECHATERMINADA,
        :CodProyect, 1, :IdPadreOT, 0
    )";
    $stmtInsert = $conexion->prepare($sqlInsert);

    // 4. Procesar cada fila
    $conexion->beginTransaction();

    $inserted = 0;
    $skipped = 0;
    $repairedNames = 0;
    $errCount = 0;
    $detail = [];

    foreach ($data as $index => $row) {
        $codProyect = trim($row['CodProyect'] ?? '');
        $codPdre = trim($row['CodPdre'] ?? '');
        $numof = trim($row['Numof'] ?? '');
        $rowLabel = "Fila $index (CodPdre: $codPdre)";

        // Validar campos esenciales
        if (empty($codProyect) || empty($codPdre)) {
            $detail[] = ['row' => $index, 'status' => 'error', 'msg' => "CodProyect o CodPdre vacío"];
            $errCount++;
            continue;
        }

        // Buscar IdPadreOT en otpadre
        $stmtCheckPadre->execute([':pc' => $codProyect]);
        $padre = $stmtCheckPadre->fetch();

        $idPadreOT = null;
        if ($padre) {
            $idPadreOT = (int)$padre['IdPadreOT'];
        } else {
            // Si no existe en otpadre, usar el valor enviado
            $idPadreOT = intval($row['IdPadreOT'] ?? 0);
            if ($idPadreOT <= 0) {
                $detail[] = ['row' => $index, 'status' => 'error', 'msg' => "OT Padre no encontrado para $codProyect y IdPadreOT no proporcionado"];
                $errCount++;
                continue;
            }
        }

        // Verificar duplicados
        $stmtCheckDup->execute([':otpadre' => $codProyect, ':codpdre' => $codPdre, ':numof' => $numof]);
        if ($stmtCheckDup->fetchColumn() > 0) {
            // Recuperar sólo nombres con el recorte conocido, sin duplicar ítems ni cambiar sus horas.
            $nombreCompleto=trim((string)($row['NPdre']??''));
            $nombres=$conexion->prepare("SELECT NPdre FROM othija WHERE OTPADRE=:ot AND CodPdre=:cod AND NumOF=:num");
            $clave=[':ot'=>$codProyect,':cod'=>$codPdre,':num'=>$numof];$nombres->execute($clave);
            foreach($nombres->fetchAll(PDO::FETCH_ASSOC) as $nombreActual){
                if(hhmeta_nombre_recuperable((string)$nombreActual['NPdre'],$nombreCompleto)){
                    $reparar=$conexion->prepare("UPDATE othija SET NPdre=:completo WHERE OTPADRE=:ot AND CodPdre=:cod AND NumOF=:num AND NPdre=:anterior");
                    $reparar->execute(array_merge($clave,[':completo'=>$nombreCompleto,':anterior'=>$nombreActual['NPdre']]));
                    $repairedNames+=$reparar->rowCount();
                }
            }
            $detail[] = ['row' => $index, 'status' => 'duplicate', 'msg' => "Ya existe: $codProyect + $codPdre + $numof"];
            $skipped++;
            continue;
        }

        // Insertar
        $stmtInsert->execute([
            ':tipo'         => trim($row['Tipo'] ?? ''),
            ':year'         => intval($row['Year'] ?? 0),
            ':projectCode'  => trim($row['ProjectCode'] ?? ''),
            ':codPdre'      => $codPdre,
            ':npdre'        => trim($row['NPdre'] ?? ''),
            ':clientName'   => trim($row['ClientName'] ?? ''),
            ':cantidad'     => floatval($row['PlannedParentQuantity'] ?? 0),
            ':HHFab'        => floatval($row['HHFab'] ?? 0),
            ':PesPptoTotaKg'=> floatval($row['PesPptoTotaKg'] ?? 0),
            ':PeSoldaduraKg'=> floatval($row['PeSoldaduraKg'] ?? 0),
            ':mo001_1'      => floatval($row['MO001_1'] ?? 0),
            ':mo001_2'      => floatval($row['MO001_2'] ?? 0),
            ':mo002'        => floatval($row['MO002'] ?? 0),
            ':mo004'        => floatval($row['MO004'] ?? 0),
            ':mo014'        => floatval($row['MO014'] ?? 0),
            ':mo008_1'      => floatval($row['MO008_1'] ?? 0),
            ':mo008_2'      => floatval($row['MO008_2'] ?? 0),
            ':mo007_1'      => floatval($row['MO007_1'] ?? 0),
            ':mo007_2'      => floatval($row['MO007_2'] ?? 0),
            ':mo003'        => floatval($row['MO003'] ?? 0),
            ':mo015'        => floatval($row['MO015'] ?? 0),
            ':mo005'        => floatval($row['MO005'] ?? 0),
            ':mo009'        => floatval($row['MO009'] ?? 0),
            ':mo004P'       => floatval($row['MO004P'] ?? 0),
            ':mo006'        => floatval($row['MO006'] ?? 0),
            ':mo012'        => floatval($row['MO012'] ?? 0),
            ':mo011'        => floatval($row['MO011'] ?? 0),
            ':mo020'        => floatval($row['MO020'] ?? 0),
            ':mo010'        => floatval($row['MO010'] ?? 0),
            ':PesPptoUniKg' => floatval($row['PesPptoUniKg'] ?? 0),
            ':NumOF'        => trim($row['Numof'] ?? ''),
            ':SEM'          => trim($row['SEM'] ?? ''),
            ':CANTI'        => trim($row['CANTI'] ?? '0'),
            ':ITEM'         => trim($row['ITEM'] ?? ''),
            ':FEAING'       => trim($row['FEAING'] ?? ''),
            ':FEAREQU'      => trim($row['FEAREQU'] ?? ''),
            ':OC'           => trim($row['OC'] ?? '0'),
            ':ESTADO'       => trim($row['ESTADO'] ?? 'Producción'),
            ':PESONETOTOTALKG'       => floatval($row['PESONETOTOTALKG'] ?? 0),
            ':RENDIMEINTOHHTONPPTO'  => floatval($row['RENDIMEINTOHHTONPPTO'] ?? 0),
            ':FECHATERMINADA'        => trim($row['FECHATERMINADA'] ?? '0000-00-00'),
            ':CodProyect'   => $codProyect,
            ':IdPadreOT'    => $idPadreOT
        ]);

        $detail[] = ['row' => $index, 'status' => 'inserted', 'msg' => "OK: $codPdre ($numof)"];
        $inserted++;
    }

    $conexion->commit();

    ob_end_clean();
    echo json_encode([
        'ok' => true,
        'total' => count($data),
        'inserted' => $inserted,
        'skipped' => $skipped,
        'repaired_names' => $repairedNames,
        'errors' => $errCount,
        'detail' => $detail
    ]);

} catch (Throwable $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    ob_end_clean();
    echo json_encode([
        'ok' => false,
        'error' => 'Error en guardar_othija',
        'detail' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
