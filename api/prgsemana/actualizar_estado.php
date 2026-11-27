<?php
require_once __DIR__ . '/../hhmeta_guard.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    require_once __DIR__ . '/Conexion.php';
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

    // Obtener parámetros del POST
    $idPrograma = $_POST['idPrograma'] ?? null;
    $estado = $_POST['estado'] ?? null;

    if (!$idPrograma || !$estado) {
        echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
        exit;
    }

    // Si se está cerrando (estado = 2), validar reglas de AvanceReal
    if ($estado == 2) {
        // Obtener el proceso relacionado al idPrograma para buscar todas las semanas del mismo proceso
        $consultaInfo = "SELECT otpadre, CodProyecto, CodPdre, CodProceso FROM programadorsem WHERE IdPrograma = :idPrograma LIMIT 1";
        $stmtInfo = $conexion->prepare($consultaInfo);
        $stmtInfo->bindParam(':idPrograma', $idPrograma, PDO::PARAM_INT);
        $stmtInfo->execute();
        $infoPrograma = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        if ($infoPrograma) {
            // Contar cuántas semanas ABIERTAS (Estado = 1) hay en este proceso
            $consultaAbiertas = "SELECT COUNT(*) as semanasAbiertas 
                                 FROM programadorsem 
                                 WHERE otpadre = :otpadre 
                                 AND CodProyecto = :codProyecto 
                                 AND CodPdre = :codPdre 
                                 AND CodProceso = :codProceso
                                 AND Estado = 1";
            $stmtAbiertas = $conexion->prepare($consultaAbiertas);
            $stmtAbiertas->bindParam(':otpadre', $infoPrograma['otpadre']);
            $stmtAbiertas->bindParam(':codProyecto', $infoPrograma['CodProyecto']);
            $stmtAbiertas->bindParam(':codPdre', $infoPrograma['CodPdre']);
            $stmtAbiertas->bindParam(':codProceso', $infoPrograma['CodProceso']);
            $stmtAbiertas->execute();
            $resultadoAbiertas = $stmtAbiertas->fetch(PDO::FETCH_ASSOC);

            $semanasAbiertas = intval($resultadoAbiertas['semanasAbiertas'] ?? 0);

            // Calcular la suma de porcentajes de AvanceReal (AV.FISICO) y Avance (PROG.HH) para este proceso
            $consultaSuma = "SELECT SUM(AvanceReal) as totalAvanceReal, SUM(Avance) as totalAvance 
                             FROM programadorsem 
                             WHERE otpadre = :otpadre 
                             AND CodProyecto = :codProyecto 
                             AND CodPdre = :codPdre 
                             AND CodProceso = :codProceso";
            $stmtSuma = $conexion->prepare($consultaSuma);
            $stmtSuma->bindParam(':otpadre', $infoPrograma['otpadre']);
            $stmtSuma->bindParam(':codProyecto', $infoPrograma['CodProyecto']);
            $stmtSuma->bindParam(':codPdre', $infoPrograma['CodPdre']);
            $stmtSuma->bindParam(':codProceso', $infoPrograma['CodProceso']);
            $stmtSuma->execute();
            $resultado = $stmtSuma->fetch(PDO::FETCH_ASSOC);

            $totalAvanceReal = floatval($resultado['totalAvanceReal'] ?? 0);
            $totalAvance = floatval($resultado['totalAvance'] ?? 0);
            $tolerance = 0.01;
            $warnings = [];

            // Obtener la semana de la fila actual para detectar semanas posteriores
            $consultaSemana = "SELECT Semana FROM programadorsem WHERE IdPrograma = :idPrograma LIMIT 1";
            $stmtSemana = $conexion->prepare($consultaSemana);
            $stmtSemana->bindParam(':idPrograma', $idPrograma, PDO::PARAM_INT);
            $stmtSemana->execute();
            $resSemana = $stmtSemana->fetch(PDO::FETCH_ASSOC);
            $currentSemana = intval($resSemana['Semana'] ?? 0);

            // Contar y listar semanas posteriores (después de la actual) con AvanceReal = 0
            $consultaCerosPosteriores = "SELECT GROUP_CONCAT(DISTINCT Semana ORDER BY Semana ASC) as semanasPost 
                                         FROM programadorsem 
                                         WHERE otpadre = :otpadre 
                                         AND CodProyecto = :codProyecto 
                                         AND CodPdre = :codPdre 
                                         AND CodProceso = :codProceso
                                         AND Estado = 1
                                         AND (AvanceReal = 0 OR AvanceReal IS NULL)
                                         AND Semana > :currentSemana";
            $stmtCerosPost = $conexion->prepare($consultaCerosPosteriores);
            $stmtCerosPost->bindParam(':otpadre', $infoPrograma['otpadre']);
            $stmtCerosPost->bindParam(':codProyecto', $infoPrograma['CodProyecto']);
            $stmtCerosPost->bindParam(':codPdre', $infoPrograma['CodPdre']);
            $stmtCerosPost->bindParam(':codProceso', $infoPrograma['CodProceso']);
            $stmtCerosPost->bindParam(':currentSemana', $currentSemana, PDO::PARAM_INT);
            $stmtCerosPost->execute();
            $resCerosPost = $stmtCerosPost->fetch(PDO::FETCH_ASSOC);
            $semanasPosteriores = $resCerosPost['semanasPost'] ?? '';
            $countPosteriores = $semanasPosteriores ? count(explode(',', $semanasPosteriores)) : 0;

            if ($countPosteriores > 0) {
                // Añadir advertencia sobre semanas posteriores con AvanceReal = 0 pero NO bloquear
                $warnings[] = [
                    'message' => 'Hay ' . $countPosteriores . ' semana(s) POSTERIORES con AV.FISICO = 0 (semanas: ' . $semanasPosteriores . '). Revisa o reprograma PROG.HH si eliminas esas filas.',
                    'details' => [
                        'semanasPosteriores' => $semanasPosteriores,
                        'countPosteriores' => $countPosteriores,
                        'currentSemana' => $currentSemana,
                        'totalAvance' => round($totalAvance,2),
                        'totalAvanceReal' => round($totalAvanceReal,2)
                    ]
                ];
            }

            // VALIDACIÓN 1: Si esta es la última semana abierta, ambas sumas deben ser 100%
            if ($semanasAbiertas <= 1) {
                if (abs($totalAvanceReal - 100) > $tolerance) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No se puede cerrar la última semana. La suma de Avance Físico (Real) debe ser 100%. Suma actual: ' . number_format($totalAvanceReal, 2) . '%',
                        'diagnostic' => [
                            'totalAvanceReal' => round($totalAvanceReal,2),
                            'totalAvance' => round($totalAvance,2),
                            'semanasAbiertas' => $semanasAbiertas
                        ]
                    ]);
                    exit;
                }
                if (abs($totalAvance - 100) > $tolerance) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No se puede cerrar la última semana. La suma de PROG.HH debe ser 100%. Suma actual: ' . number_format($totalAvance, 2) . '%',
                        'diagnostic' => [
                            'totalAvanceReal' => round($totalAvanceReal,2),
                            'totalAvance' => round($totalAvance,2),
                            'semanasAbiertas' => $semanasAbiertas
                        ]
                    ]);
                    exit;
                }
            }

            // Recuperar todas las semanas abiertas ordenadas para validar acumulados y ceros previos
            $consultaOrden = "SELECT IdPrograma, Avance, AvanceReal, Semana 
                              FROM programadorsem 
                              WHERE otpadre = :otpadre 
                              AND CodProyecto = :codProyecto 
                              AND CodPdre = :codPdre 
                              AND CodProceso = :codProceso
                              AND Estado = 1
                              ORDER BY Semana, IdPrograma";
            $stmtOrden = $conexion->prepare($consultaOrden);
            $stmtOrden->bindParam(':otpadre', $infoPrograma['otpadre']);
            $stmtOrden->bindParam(':codProyecto', $infoPrograma['CodProyecto']);
            $stmtOrden->bindParam(':codPdre', $infoPrograma['CodPdre']);
            $stmtOrden->bindParam(':codProceso', $infoPrograma['CodProceso']);
            $stmtOrden->execute();

            $cumAvance = 0.0;
            $cumAvanceReal = 0.0;
            $zerosBefore = 0;
            $zerosBeforeList = [];
            $found = false;
            $otherWeeksWithAvance = 0;

            // Primero contamos otras semanas con AvanceReal > 0 (excluyendo la actual)
            $consultaOtrosConAv = "SELECT COUNT(*) as cnt FROM programadorsem WHERE otpadre = :otpadre AND CodProyecto = :codProyecto AND CodPdre = :codPdre AND CodProceso = :codProceso AND Estado = 1 AND (AvanceReal > 0) AND IdPrograma != :idPrograma";
            $stmtOtros = $conexion->prepare($consultaOtrosConAv);
            $stmtOtros->bindParam(':otpadre', $infoPrograma['otpadre']);
            $stmtOtros->bindParam(':codProyecto', $infoPrograma['CodProyecto']);
            $stmtOtros->bindParam(':codPdre', $infoPrograma['CodPdre']);
            $stmtOtros->bindParam(':codProceso', $infoPrograma['CodProceso']);
            $stmtOtros->bindParam(':idPrograma', $idPrograma, PDO::PARAM_INT);
            $stmtOtros->execute();
            $resOtros = $stmtOtros->fetch(PDO::FETCH_ASSOC);
            $otherWeeksWithAvance = intval($resOtros['cnt'] ?? 0);

            while ($row = $stmtOrden->fetch(PDO::FETCH_ASSOC)) {
                $rowId = intval($row['IdPrograma']);
                $rowAv = floatval($row['Avance'] ?? 0);
                $rowAvReal = floatval($row['AvanceReal'] ?? 0);
                $rowSemana = $row['Semana'];

                if ($rowId === intval($idPrograma)) {
                    // sumar la fila actual y detener
                    $cumAvance += $rowAv;
                    $cumAvanceReal += $rowAvReal;
                    $found = true;
                    break;
                }

                // fila previa a la actual
                if ($rowAvReal <= 0) {
                    $zerosBefore++;
                    $zerosBeforeList[] = $rowSemana;
                }
                $cumAvance += $rowAv;
                $cumAvanceReal += $rowAvReal;
            }

            // Si hay semanas con AvanceReal = 0 ANTES de la fila que intentas cerrar,
            // añadimos una advertencia pero NO bloqueamos el cierre aquí; se bloqueará
            // solo si la fila actual tiene AvanceReal = 0 más abajo.
            if ($zerosBefore > 0) {
                $warnings[] = [
                    'message' => 'Hay ' . $zerosBefore . ' semana(s) PREVIAS con AV.FISICO = 0 (semanas: ' . implode(', ', $zerosBeforeList) . '). Revisa o reprograma PROG.HH si eliminas esas filas.',
                    'details' => [
                        'zerosBefore' => $zerosBefore,
                        'zerosBeforeList' => $zerosBeforeList,
                        'cumAvance' => round($cumAvance,2),
                        'cumAvanceReal' => round($cumAvanceReal,2),
                        'totalAvance' => round($totalAvance,2),
                        'totalAvanceReal' => round($totalAvanceReal,2),
                        'otherWeeksWithAvance' => $otherWeeksWithAvance
                    ]
                ];
                // no exit; continuar validaciones siguientes
            }

            // Si la fila actual fue encontrada, sólo impedir cerrar si su AV.FISICO es 0
            if ($found) {
                if (!isset($rowAvReal)) {
                    // seguridad: obtener AvanceReal directamente si no está en la variable
                    $consultaActual = "SELECT AvanceReal FROM programadorsem WHERE IdPrograma = :idPrograma LIMIT 1";
                    $stmtActual = $conexion->prepare($consultaActual);
                    $stmtActual->bindParam(':idPrograma', $idPrograma, PDO::PARAM_INT);
                    $stmtActual->execute();
                    $filaAct = $stmtActual->fetch(PDO::FETCH_ASSOC);
                    $rowAvReal = floatval($filaAct['AvanceReal'] ?? 0);
                }

                if ($rowAvReal <= 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No se puede cerrar una semana con AV.FISICO = 0. Introduce un avance físico o elimina la fila.',
                        'details' => [
                            'cumAvance' => round($cumAvance,2),
                            'cumAvanceReal' => round($cumAvanceReal,2),
                            'rowAvanceReal' => round($rowAvReal,2),
                            'totalAvance' => round($totalAvance,2),
                            'totalAvanceReal' => round($totalAvanceReal,2)
                        ]
                    ]);
                    exit;
                }

                // Si la suma TOTAL de AvanceReal (todas las filas: cerradas + abiertas) llega a 100%
                // pero existen semanas posteriores abiertas con AvanceReal = 0, no permitir cerrar.
                // Se usa totalAvanceReal (no cumAvanceReal) para incluir filas ya cerradas.
                if ($totalAvanceReal >= (100 - $tolerance) && $countPosteriores > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No se puede cerrar esta semana porque la suma TOTAL de AV.FISICO ya es ' . number_format($totalAvanceReal, 2) . '% (≥100%), pero existen ' . $countPosteriores . ' semana(s) POSTERIORES con AV.FISICO = 0 (semanas: ' . $semanasPosteriores . '). Elimina esas filas y reprograma PROG.HH antes de cerrar.',
                        'details' => [
                            'totalAvance' => round($totalAvance,2),
                            'totalAvanceReal' => round($totalAvanceReal,2),
                            'countPosteriores' => $countPosteriores,
                            'semanasPosteriores' => $semanasPosteriores
                        ]
                    ]);
                    exit;
                }
            }

            // Nota: la validación de 100% global se aplica únicamente cuando queda 1 o 0 semanas abiertas
            // (ya manejado en la VALIDACIÓN 1 más arriba). No bloquearemos aquí por ser "última significativa".
        }
    }

    // Actualizar el estado en la tabla programadorsem
    $consulta = "UPDATE programadorsem SET Estado = :estado WHERE IdPrograma = :idPrograma";
    $stmt = $conexion->prepare($consulta);
    
    $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
    $stmt->bindParam(':idPrograma', $idPrograma, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            $response = [
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'idPrograma' => $idPrograma,
                'nuevoEstado' => $estado
            ];
            if (!empty($warnings)) $response['warnings'] = $warnings;
            // Añadir diagnóstico (si está disponible) para depuración
            $response['diagnostic'] = [
                'cumAvance' => isset($cumAvance) ? round($cumAvance,2) : null,
                'cumAvanceReal' => isset($cumAvanceReal) ? round($cumAvanceReal,2) : null,
                'totalAvance' => isset($totalAvance) ? round($totalAvance,2) : null,
                'totalAvanceReal' => isset($totalAvanceReal) ? round($totalAvanceReal,2) : null,
                'countPosteriores' => isset($countPosteriores) ? $countPosteriores : 0,
                'semanasPosteriores' => isset($semanasPosteriores) ? $semanasPosteriores : '' ,
                'currentSemana' => isset($currentSemana) ? $currentSemana : null,
                'semanasAbiertas' => isset($semanasAbiertas) ? $semanasAbiertas : null
            ];
            echo json_encode($response);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No se encontró el registro para actualizar',
                'idPrograma' => $idPrograma
            ]);
        }
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $errorInfo[2]]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conexion = null;
}
?>
