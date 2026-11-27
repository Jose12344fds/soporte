<?php
/**
 * ConsultasPrgSemana.php — Consultas MySQL para módulo ProgramarSemana v2
 * Migrado desde consultasAjax1/consulta*.php
 */
require_once __DIR__ . '/ConexionLocal.php';

class ConsultasPrgSemana {

    /* ════════════════════════════════════════════
       LISTA MO para subquery UNION dinámica
       ════════════════════════════════════════════ */
    private static $MO_COLUMNS = [
        'MO001_1','MO001_2','MO002','MO004','MO014',
        'MO008_1','MO008_2','MO007_1','MO007_2',
        'MO003','MO015','MO005','MO009','MO004P',
        'MO006','MO012','MO011','MO020','MO010',
        'MO050','MO060','MO070','MO080'
    ];

    /**
     * Genera la sub-query UNION que mapea cada columna MO a filas.
     * Se usa en getProgramacion, getSumAvance, getEstadoProgramado.
     */
    private static function buildUnionSubquery(int $idPadreOT, string $codPdreQuoted): string {
        $parts = [];
        foreach (self::$MO_COLUMNS as $mo) {
            $parts[] = "SELECT IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab,
                        PesPptoTotaKg, PeSoldaduraKg, IdPadreOT,
                        '{$mo}' AS MO_Column, `{$mo}` AS MO_Value
                 FROM othija
                 WHERE IdPadreOT = {$idPadreOT} AND CodPdre = {$codPdreQuoted}";
        }
        return '(' . implode("\nUNION\n", $parts) . ')';
    }

    /* ════════════════════════════════════════════
       1. OTs validadas (Estado_OT = 2)
       Origen: consultasAjax1/consulta.php
       ════════════════════════════════════════════ */
    public static function getOTsValidadas(): array {
        $pdo = ConexionLocal::conectar();
        $stmt = $pdo->query("SELECT * FROM otpadre WHERE Estado_OT = 2");
        return $stmt->fetchAll();
    }

    /* ════════════════════════════════════════════
       2. Items de subconsulta (por IdPadre + tipo)
       Origen: consultasAjax/consulta8.php
       ════════════════════════════════════════════ */
    public static function getItems(int $idPadre, int $tipo = 1): array {
        $pdo = ConexionLocal::conectar();

        $moGroups = [
            1 => 'T1.MO001_1+T1.MO001_2+T1.MO002+T1.MO004+T1.MO014+T1.MO008_1+T1.MO008_2+T1.MO007_1+T1.MO007_2+T1.MO003+T1.MO015+T1.MO005+T1.MO009+T1.MO004P+T1.MO006+T1.MO012+T1.MO011+T1.MO020+T1.MO010+IFNULL(T1.MO050,0)+IFNULL(T1.MO060,0)+IFNULL(T1.MO070,0)+IFNULL(T1.MO080,0)',
            2 => 'T1.MO001_1+T1.MO001_2+T1.MO002+T1.MO004+T1.MO014',
            3 => 'T1.MO008_1+T1.MO008_2+T1.MO007_1+T1.MO007_2',
            4 => 'T1.MO003+T1.MO005+T1.MO009+T1.MO004P+T1.MO006',
            5 => 'T1.MO015',
            6 => 'T1.MO012+T1.MO011+T1.MO020+T1.MO010'
        ];

        if (!isset($moGroups[$tipo])) return [];

        $sut = $moGroups[$tipo];
        $sql = "SELECT T0.*, T0.ProjectCode AS proyecto, T1.*,
                       IFNULL(T1.MO050,0) AS MO050, IFNULL(T1.MO060,0) AS MO060,
                       IFNULL(T1.MO070,0) AS MO070, IFNULL(T1.MO080,0) AS MO080,
                       ({$sut}) AS SUT
                FROM otpadre T0
                LEFT JOIN othija T1 ON T0.IdPadre = T1.IdPadreOT
                WHERE T0.IdPadre = :idPadre";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idPadre' => $idPadre]);
        return $stmt->fetchAll();
    }

    /* ════════════════════════════════════════════
       3. Programación por proceso (flotante)
       Origen: consultasAjax1/consulta2.php
       ════════════════════════════════════════════ */
    public static function getProgramacion(int $idPadreOT, string $codPdre, string $proceso): array {
        $pdo = ConexionLocal::conectar();
        $stmt = $pdo->prepare("SELECT IdPrograma, otpadre, CodProyecto, CodProceso, CodPdre,
            Aniio, Semana, Avance, TotHoras, AvanceReal, Estado, Fabricacion
            FROM programadorsem WHERE otpadre = ? AND CodPdre = ? AND CodProceso = ?
            ORDER BY Aniio, CAST(Semana AS UNSIGNED), IdPrograma");
        $stmt->execute([$idPadreOT, $codPdre, $proceso]);
        $columns = ['IdPrograma','otpadre','CodProyecto','CodProceso','CodPdre',
            'Aniio','Semana','Avance','TotHoras','AvanceReal','Estado','Fabricacion'];
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $values) {
            $row = array_combine($columns, $values);
            $row['clave_seleccion'] = base64_encode(json_encode([
                (string)$row['otpadre'], (string)$row['CodProyecto'], (string)$row['CodPdre'],
                (string)$row['CodProceso'], (string)$row['Aniio'], (string)$row['Semana']
            ], JSON_UNESCAPED_UNICODE));
            $rows[] = $row;
        }
        return $rows;
    }

    /* ════════════════════════════════════════════
       4. SUM(Avance) de un proceso
       Origen: consultasAjax1/consulta2_1.php
       ════════════════════════════════════════════ */
    public static function getSumAvance(int $idPadreOT, string $codPdre, string $proceso): float {
        $pdo = ConexionLocal::conectar();
        $codPdreQ = $pdo->quote($codPdre);
        $procesoQ = $pdo->quote($proceso);
        $union = self::buildUnionSubquery($idPadreOT, $codPdreQ);

        $sql = "SELECT SUM(T0.Avance) AS total
                FROM programadorsem T0
                LEFT JOIN {$union} T1
                  ON T1.IdPadreOT = T0.otpadre AND T1.CodPdre = T0.CodPdre AND T1.MO_Column = T0.CodProceso
                WHERE T0.otpadre = {$idPadreOT}
                  AND T0.CodPdre = {$codPdreQ}
                  AND T0.CodProceso = {$procesoQ}";

        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        return (float)($row['total'] ?? 0);
    }

    /* ════════════════════════════════════════════
       5. Estado programado de un proceso
       Origen: consultasAjax1/consulta2_2.php
       ════════════════════════════════════════════ */
    public static function getEstadoProgramado(int $idPadreOT, string $codPdre, string $proceso): int {
        $pdo = ConexionLocal::conectar();
        $codPdreQ = $pdo->quote($codPdre);
        $procesoQ = $pdo->quote($proceso);
        $union = self::buildUnionSubquery($idPadreOT, $codPdreQ);

        $sql = "SELECT
                    CASE
                        WHEN GROUP_CONCAT(DISTINCT T0.Estado) = '2' THEN 2
                        WHEN GROUP_CONCAT(DISTINCT T0.Estado) = '1' THEN 1
                        WHEN GROUP_CONCAT(DISTINCT T0.Estado) = '1,2' THEN 1
                        ELSE 1
                    END AS RESULT
                FROM programadorsem T0
                LEFT JOIN {$union} T1
                  ON T1.IdPadreOT = T0.otpadre AND T1.CodPdre = T0.CodPdre AND T1.MO_Column = T0.CodProceso
                WHERE T0.otpadre = {$idPadreOT}
                  AND T0.CodPdre = {$codPdreQ}
                  AND T0.CodProceso = {$procesoQ}";

        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        return (int)($row['RESULT'] ?? 1);
    }

    /* ════════════════════════════════════════════
       6. Guardar programación (INSERT batch)
       Origen: consultasAjax1/consulta3.php
       ════════════════════════════════════════════ */
    public static function guardarProgramacion(array $datos): array {
        $pdo = ConexionLocal::conectar();

        $stmtVerify  = $pdo->prepare("SELECT COUNT(*) FROM programadorsem WHERE otpadre=:ot AND CodProyecto=:cy AND Aniio=:a AND Semana=:s AND CodPdre=:cp AND CodProceso=:pr");
        $stmtInsert  = $pdo->prepare("INSERT INTO programadorsem (otpadre, CodProyecto, CodProceso, CodPdre, Aniio, Semana, Avance, TotHoras, AvanceReal, Estado, Fabricacion)
                                       VALUES (:ot,:cy,:pr,:cp,:a,:s,:av,:th,:ar,'1',:fab)");
        $stmtHist    = $pdo->prepare("INSERT INTO programacion_historico (id_programa,anio,semana,avance,tot_horas,version_tipo,fecha_creacion,otpadre,CodProyecto,CodProceso,CodPdre)
                                       VALUES (:id,:a,:s,:av,:th,:vt,NOW(),:ot,:cy,:pr,:cp)");
        $stmtCheckBL = $pdo->prepare("SELECT COUNT(*) as total FROM programacion_historico WHERE version_tipo='BASELINE' AND otpadre=:ot AND CodProyecto=:cy AND CodProceso=:pr AND CodPdre=:cp");
        $stmtSelProg = $pdo->prepare("SELECT IdPrograma,Aniio,Semana,Avance,TotHoras FROM programadorsem WHERE otpadre=:ot AND CodProyecto=:cy AND CodProceso=:pr AND CodPdre=:cp ORDER BY Aniio,Semana");
        $stmtInsBL   = $pdo->prepare("INSERT INTO programacion_historico (id_programa,anio,semana,avance,tot_horas,version_tipo,fecha_creacion,otpadre,CodProyecto,CodProceso,CodPdre)
                                       VALUES (:id,:a,:s,:av,:th,'BASELINE',NOW(),:ot,:cy,:pr,:cp)");

        $inserted = [];
        $skipped  = [];
        $errors   = [];

        try {
            $pdo->beginTransaction();

            $totales = [];
            foreach ($datos as $f) {
                foreach (['idPadreOT','proyecto','proceso','codPdre','anio','semana','porcentaje','totalHoras'] as $campo) {
                    if (!isset($f[$campo])) throw new RuntimeException('Hay una fila nueva incompleta.');
                }
                $av = $f['porcentaje']; $real = $f['avanceReal'] ?? 0;
                if (!is_numeric($av) || $av <= 0 || $av > 100 || !is_numeric($real) || $real < 0 || $real > 100
                    || !is_numeric($f['totalHoras']) || $f['totalHoras'] < 0) throw new RuntimeException('Revisa porcentajes (0 a 100) y horas.');
                $year = (int)$f['anio'];
                $maxWeek = (int)(new DateTimeImmutable($year . '-12-28'))->format('W');
                if ($year < 2000 || $year > 2200 || (int)$f['semana'] < 1 || (int)$f['semana'] > $maxWeek) throw new RuntimeException('Año o semana no válido.');
                $key = json_encode([$f['idPadreOT'],$f['proyecto'],$f['proceso'],$f['codPdre']]);
                if (!isset($totales[$key])) $totales[$key] = ['fila'=>$f, 'suma'=>0];
                $totales[$key]['suma'] += (float)$av;
            }
            foreach ($totales as $grupo) {
                $f = $grupo['fila'];
                $check = $pdo->prepare('SELECT Avance FROM programadorsem WHERE otpadre=? AND CodProyecto=? AND CodProceso=? AND CodPdre=? FOR UPDATE');
                $check->execute([$f['idPadreOT'],$f['proyecto'],$f['proceso'],$f['codPdre']]);
                $total = $grupo['suma'] + array_sum($check->fetchAll(PDO::FETCH_COLUMN));
                if (round(abs($total - 100), 6) > 0.01) throw new RuntimeException('PROG.HH del proceso debe sumar 100 %. Actualmente suma ' . number_format($total, 2) . ' %.');
            }

            // Determinar procesos afectados y si ya tienen baseline
            $processes = [];
            foreach ($datos as $d) {
                if (!isset($d['idPadreOT'],$d['proyecto'],$d['proceso'],$d['codPdre'])) continue;
                $key = "{$d['idPadreOT']}||{$d['proyecto']}||{$d['proceso']}||{$d['codPdre']}";
                $processes[$key] = $d;
            }

            $baselineExists = [];
            foreach ($processes as $key => $d) {
                $stmtCheckBL->execute([':ot'=>$d['idPadreOT'],':cy'=>$d['proyecto'],':pr'=>$d['proceso'],':cp'=>$d['codPdre']]);
                $baselineExists[$key] = ((int)$stmtCheckBL->fetchColumn()) > 0;
            }

            foreach ($datos as $idx => $f) {
                if (!isset($f['idPadreOT'],$f['proyecto'],$f['proceso'],$f['codPdre'],$f['anio'],$f['semana'],$f['porcentaje'],$f['totalHoras'])) {
                    $errors[] = "Fila {$idx}: datos incompletos";
                    continue;
                }

                // Verificar duplicado
                $stmtVerify->execute([':ot'=>$f['idPadreOT'],':cy'=>$f['proyecto'],':a'=>$f['anio'],':s'=>$f['semana'],':cp'=>$f['codPdre'],':pr'=>$f['proceso']]);
                if ($stmtVerify->fetchColumn() > 0) {
                    throw new RuntimeException("La semana {$f['semana']}/{$f['anio']} ya existe. No se guardaron filas.");
                }

                $ok = $stmtInsert->execute([
                    ':ot'=>$f['idPadreOT'], ':cy'=>$f['proyecto'], ':pr'=>$f['proceso'], ':cp'=>$f['codPdre'],
                    ':a'=>$f['anio'], ':s'=>$f['semana'], ':av'=>$f['porcentaje'], ':th'=>$f['totalHoras'],
                    ':ar'=>$f['avanceReal'] ?? 0, ':fab'=>$f['fabricacion'] ?? 'tama'
                ]);

                if ($ok && $stmtInsert->rowCount() > 0) {
                    $idProg = $pdo->lastInsertId();
                    $inserted[] = ['index'=>$idx,'IdPrograma'=>$idProg,'anio'=>$f['anio'],'semana'=>$f['semana'],'proyecto'=>$f['proyecto'],'proceso'=>$f['proceso']];
                } else {
                    $errors[] = "Fila {$idx}: no se insertó";
                }
            }

            // Crear BASELINE automático para procesos nuevos
            foreach ($processes as $key => $d) {
                if (!empty($baselineExists[$key])) continue;

                $stmtSelProg->execute([':ot'=>$d['idPadreOT'],':cy'=>$d['proyecto'],':pr'=>$d['proceso'],':cp'=>$d['codPdre']]);
                $rows = $stmtSelProg->fetchAll();
                foreach ($rows as $r) {
                    try {
                        $stmtInsBL->execute([
                            ':id'=>$r['IdPrograma'],':a'=>$r['Aniio'],':s'=>$r['Semana'],
                            ':av'=>$r['Avance'],':th'=>$r['TotHoras'],
                            ':ot'=>$d['idPadreOT'],':cy'=>$d['proyecto'],':pr'=>$d['proceso'],':cp'=>$d['codPdre']
                        ]);
                    } catch (Exception $exBL) {
                        // No bloquear por error de baseline
                    }
                }
            }

            $pdo->commit();

            return [
                'success'  => count($inserted) > 0,
                'message'  => count($inserted) . ' registro(s) insertados',
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'errors'   => $errors
            ];

        } catch (Exception $e) {
            try { $pdo->rollBack(); } catch (Exception $__) {}
            return ['success'=>false,'message'=>'Error: '.$e->getMessage(),'errors'=>[$e->getMessage()]];
        }
    }

    /* ════════════════════════════════════════════
       7. Eliminar programación
       Origen: consultasAjax1/consulta4.php
       ════════════════════════════════════════════ */
    public static function eliminarProgramacion(int $idPrograma): array {
        $pdo = ConexionLocal::conectar();
        $stmt = $pdo->prepare('DELETE FROM programadorsem WHERE IdPrograma = ? AND COALESCE(Estado, 1) <> 2');
        $stmt->execute([$idPrograma]);
        return $stmt->rowCount() === 1 ? ['success'=>true,'message'=>'Semana eliminada']
            : ['success'=>false,'message'=>'La semana no existe o está cerrada. Actualiza el proceso.'];
    }

    /* ════════════════════════════════════════════
       8. Actualizar campo individual
       Origen: consultasAjax1/consulta5.php
       ════════════════════════════════════════════ */
    private static $CAMPOS_PERMITIDOS = ['Avance','AvanceReal','Semana','Aniio','Estado','Fabricacion','TotHoras'];

    public static function actualizarCampo(int $idPrograma, string $field, $newValue, $totalHoras = null): array {
        if (!in_array($field, ['Avance','AvanceReal','Semana','Aniio','Fabricacion','TotHoras'], true)) throw new RuntimeException('Campo no permitido.');
        if (in_array($field, ['Avance','AvanceReal'], true)) {
            $newValue = str_replace([',','%'], ['.',''], trim((string)$newValue));
            if (!is_numeric($newValue) || $newValue < 0 || $newValue > 100) throw new RuntimeException('El porcentaje debe estar entre 0 y 100.');
        }
        if ($totalHoras !== null && (!is_numeric($totalHoras) || $totalHoras < 0)) throw new RuntimeException('Las horas no son válidas.');
        $pdo = ConexionLocal::conectar();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT Estado, otpadre, CodProyecto, CodPdre, CodProceso, Aniio, Semana FROM programadorsem WHERE IdPrograma = ? FOR UPDATE');
            $stmt->execute([$idPrograma]); $values = $stmt->fetch(PDO::FETCH_NUM);
            if (!$values) throw new RuntimeException('No se encontró la semana. Vuelve a abrir el proceso.');
            $row = array_combine(['Estado','otpadre','CodProyecto','CodPdre','CodProceso','Aniio','Semana'], $values);
            if ((int)$row['Estado'] === 2) throw new RuntimeException('La semana está cerrada. Ábrela antes de editar.');
            $totalProgHh = null;
            if ($field === 'Avance') {
                $sumStmt = $pdo->prepare('SELECT IdPrograma, Avance FROM programadorsem WHERE otpadre=? AND CodProyecto=? AND CodPdre=? AND CodProceso=? FOR UPDATE');
                $sumStmt->execute([$row['otpadre'],$row['CodProyecto'],$row['CodPdre'],$row['CodProceso']]);
                $totalProgHh = (float)$newValue;
                foreach ($sumStmt->fetchAll(PDO::FETCH_ASSOC) as $programacion) {
                    if ((int)$programacion['IdPrograma'] !== $idPrograma) $totalProgHh += (float)$programacion['Avance'];
                }
                if ($totalProgHh > 100.010001) {
                    throw new RuntimeException('La suma de PROG.HH no puede superar 100 %. Con este cambio sería '.number_format($totalProgHh, 2, '.', '').' %.');
                }
            }
            if ($field === 'Semana' || $field === 'Aniio') {
                $year = $field === 'Aniio' ? (int)$newValue : (int)$row['Aniio'];
                $week = $field === 'Semana' ? (int)$newValue : (int)$row['Semana'];
                $maxWeek = (int)(new DateTimeImmutable($year . '-12-28'))->format('W');
                if ($year < 2000 || $year > 2200 || $week < 1 || $week > $maxWeek) throw new RuntimeException('El año o la semana no es válido.');
                $duplicate = $pdo->prepare('SELECT COUNT(*) FROM programadorsem WHERE otpadre=? AND CodProyecto=? AND CodPdre=? AND CodProceso=? AND Aniio=? AND Semana=? AND IdPrograma<>?');
                $duplicate->execute([$row['otpadre'],$row['CodProyecto'],$row['CodPdre'],$row['CodProceso'],$year,$week,$idPrograma]);
                if ($duplicate->fetchColumn() > 0) throw new RuntimeException('Ya existe otra fila para esa semana.');
            }
            if ($field === 'Avance' && $totalHoras !== null) {
                $update = $pdo->prepare('UPDATE programadorsem SET Avance=?, TotHoras=? WHERE IdPrograma=? AND COALESCE(Estado,1) <> 2');
                $update->execute([$newValue,$totalHoras,$idPrograma]);
            } else {
                $update = $pdo->prepare("UPDATE programadorsem SET {$field}=? WHERE IdPrograma=? AND COALESCE(Estado,1) <> 2");
                $update->execute([$newValue,$idPrograma]);
            }
            if ($field === 'Semana' && (string)$newValue !== (string)$row['Semana']) {
                try {
                    $col = $pdo->query("SHOW COLUMNS FROM registroconsumibles LIKE 'semana'");
                    if ($col && $col->rowCount()) $pdo->prepare('UPDATE registroconsumibles SET semana=? WHERE otpadreCon=? AND codpadre=? AND Proceso=? AND semana=?')
                        ->execute([$newValue,$row['otpadre'],$row['CodPdre'],$row['CodProceso'],$row['Semana']]);
                } catch (Exception $e) {}
            }
            if ($field === 'Semana' || $field === 'Aniio') $row[$field] = $newValue;
            $clave = base64_encode(json_encode([
                (string)$row['otpadre'], (string)$row['CodProyecto'], (string)$row['CodPdre'],
                (string)$row['CodProceso'], (string)$row['Aniio'], (string)$row['Semana']
            ], JSON_UNESCAPED_UNICODE));
            $pdo->commit();
            return ['success'=>true,'message'=>'Cambio guardado','clave_seleccion'=>$clave,'total_prog_hh'=>$totalProgHh];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /* ════════════════════════════════════════════
       9. Actualizar TotHoras
       Origen: consultasAjax1/consulta6.php
       ════════════════════════════════════════════ */
    public static function actualizarHoras(int $idPrograma, $totalHoras): array {
        $pdo  = ConexionLocal::conectar();
        $stmt = $pdo->prepare("UPDATE programadorsem SET TotHoras = :th WHERE IdPrograma = :id");
        $stmt->execute([':th'=>$totalHoras,':id'=>$idPrograma]);
        return ['success'=>true,'message'=>'TotHoras actualizado'];
    }
}
