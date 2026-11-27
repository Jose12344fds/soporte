<?php
require_once __DIR__.'/ConsultasPAVREAL.php';
/** Recalcula desde los datos guardados; no confía en códigos SAP enviados por el navegador. */
function psCalcularAvance($cn,$idPadreOT,$codPdre): array {
 if((int)$idPadreOT<=0||trim((string)$codPdre)==='')throw new RuntimeException('OT o ítem no válido.');
 $moCols=['MO001_1','MO001_2','MO002','MO004','MO014','MO008_1','MO008_2','MO007_1','MO007_2','MO003','MO015','MO005','MO009','MO004P','MO006','MO012','MO011','MO020','MO010'];
    // Traer la fila de othija (contiene las horas planificadas por proceso)
    $stmt = $cn->prepare("SELECT * FROM othija WHERE IdPadreOT = :idPadreOT AND CodPdre = :codPdre LIMIT 1");
    $stmt->execute([':idPadreOT' => $idPadreOT, ':codPdre' => $codPdre]);
    $filaOthija = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$filaOthija) throw new RuntimeException('No se encontró registro en othija.');
    $projectCode=trim((string)($filaOthija['ProjectCode']??''));if($projectCode==='')throw new RuntimeException('El ítem no tiene código de proyecto SAP.');

    // Total de horas planificadas del proyecto (suma de las MOxx)
    $totalHorasProyecto = 0;
    foreach ($moCols as $col) {
        if (isset($filaOthija[$col]) && is_numeric($filaOthija[$col])) {
            $totalHorasProyecto += (float)$filaOthija[$col];
        }
    }

    if ($totalHorasProyecto <= 0) throw new RuntimeException('El ítem no tiene horas planificadas para calcular su avance.');

    // Traer todas las filas de programadorsem para sumar la contribución real por semana
    $stmt2 = $cn->prepare("SELECT CodProceso, TotHoras, AvanceReal FROM programadorsem WHERE otpadre = :idPadreOT AND CodPdre = :codPdre AND Fabricacion = 'tama'");
    $stmt2->execute([':idPadreOT' => $idPadreOT, ':codPdre' => $codPdre]);
    $procRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar por proceso y sumar los porcentajes de AvanceReal por fila (se acumulan por proceso)
    // Nota: se interpreta AvanceReal por fila como porcentaje relativo al proceso; la suma de filas puede llegar a 100%.
    $byProc = [];
    foreach ($procRows as $r) {
        $p = $r['CodProceso'];
        $tot = isset($r['TotHoras']) && is_numeric($r['TotHoras']) ? (float)$r['TotHoras'] : 0;
        $adv = isset($r['AvanceReal']) && is_numeric($r['AvanceReal']) ? (float)$r['AvanceReal'] : 0;
        if (!isset($byProc[$p])) $byProc[$p] = ['planHours' => 0, 'sumAdv' => 0, 'programHoursSum' => 0];
        $byProc[$p]['programHoursSum'] += $tot; // suma de TotHoras programadas para info
        $byProc[$p]['sumAdv'] += $adv; // acumulado de porcentajes por fila
    }

    $detalle = [];
    $horasAvanzadas = 0;
    foreach ($byProc as $proceso => $vals) {
        // Preferir las horas desde othija si existen, sino usar la suma de TotHoras programadas
        $horasProceso = isset($filaOthija[$proceso]) && is_numeric($filaOthija[$proceso]) ? (float)$filaOthija[$proceso] : $vals['programHoursSum'];
        // El avance por proceso es la suma de AvanceReal por filas, limitada a 100%
        $avanceProceso = min(100, $vals['sumAdv']);
        $aportadas = $horasProceso * ($avanceProceso / 100);
        $horasAvanzadas += $aportadas;
        $detalle[] = [
            'proceso' => $proceso,
            'horasPlan' => $horasProceso,
            'horasProgramadasSum' => $vals['programHoursSum'],
            'avanceProceso' => $avanceProceso,
            'horasAportadas' => $aportadas
        ];
    }

    // Determinar porcentaje máximo desde la columna PorcenCalProy en othija
    $maxPercent = 100;
    if (isset($filaOthija['PorcenCalProy']) && is_numeric($filaOthija['PorcenCalProy'])) {
        $val = (float)$filaOthija['PorcenCalProy'];
        if ($val > 0) {
            // Limitar entre 0 y 100
            $maxPercent = max(0, min(100, $val));
        }
    }

    // Calcular porcentaje ponderado y redondear a 2 decimales
    $avanceRealProyecto = 0;
    if ($totalHorasProyecto > 0) {
        $avanceRealProyecto = round((($horasAvanzadas / $totalHorasProyecto) * $maxPercent), 2);
    }
    if ($avanceRealProyecto > $maxPercent) $avanceRealProyecto = $maxPercent;


 return ['avanceRealProyecto'=>$avanceRealProyecto,'maxPercent'=>$maxPercent,'totalHorasProyecto'=>$totalHorasProyecto,'horasAvanzadas'=>$horasAvanzadas,'detalle'=>$detalle,'projectCode'=>$projectCode,'idPadreOT'=>(int)$idPadreOT,'codPdre'=>$codPdre];
}
function psSincronizarAvance($cn,$idPadreOT,$codPdre): array {
 $calculo=psCalcularAvance($cn,$idPadreOT,$codPdre);$avanceRealProyecto=$calculo['avanceRealProyecto'];$projectCode=$calculo['projectCode'];
    // Actualizar en othija si existe la columna AvanceRealProyecto
    $colExists = false;
    try {
        $check = $cn->query("SHOW COLUMNS FROM othija LIKE 'AvanceRealProyecto'");
        $colExists = $check && $check->rowCount() > 0;
    } catch (Exception $e) {
        // ignorar
    }

    if ($colExists) {
        $upd = $cn->prepare("UPDATE othija SET AvanceRealProyecto = :avance WHERE IdPadreOT = :idPadreOT AND CodPdre = :codPdre");
        $upd->execute([
            ':avance' => $avanceRealProyecto,
            ':idPadreOT' => $idPadreOT,
            ':codPdre' => $codPdre
        ]);
    }


 $sap=new ConsultasPAVREAL();$sapUpdate=$sap->actualizarAvanceRealProyecto($projectCode,$codPdre,$avanceRealProyecto);
 return $calculo+['success'=>(bool)$sapUpdate['success'],'sapUpdate'=>$sapUpdate,'message'=>$sapUpdate['success']?'Avance confirmado en SAP.':'El avance local se guardó, pero SAP no confirmó la actualización.'];
}
/** Una sincronización por ítem, aunque se cierren varios procesos del mismo ítem. */
function psSincronizarItems($cn,array $items): array {
 $results=[];$warnings=[];$unique=[];
 foreach($items as $item){$ot=(int)($item['idPadreOT']??0);$cod=(string)($item['codPdre']??'');$unique[json_encode([$ot,$cod])]=[$ot,$cod];}
 foreach($unique as [$ot,$cod]){try{$result=psSincronizarAvance($cn,$ot,$cod);if(!$result['success'])throw new RuntimeException($result['sapUpdate']['error']??'SAP no confirmó la actualización.');$results[]=$result;}catch(Throwable $e){error_log('Avance SAP OT '.$ot.' item '.$cod.': '.$e->getMessage());$warnings[]='OT '.$ot.' / ítem '.$cod.': cierre guardado; avance pendiente de confirmar en SAP.';$results[]=['success'=>false,'idPadreOT'=>$ot,'codPdre'=>$cod];}}
 return ['success'=>!$warnings,'results'=>$results,'warnings'=>$warnings];
}
