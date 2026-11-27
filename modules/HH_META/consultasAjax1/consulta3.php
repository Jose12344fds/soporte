<?php
include_once '../consultasAjax/conexion.php';

// Decodificar el JSON recibido
$datos = json_decode($_POST['datos'], true); // El true convierte a un array asociativo

// Verifica si los datos fueron decodificados correctamente
if (is_array($datos)) {
    // Conectar a la base de datos
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

    // Consulta SQL para verificar si la combinación de anio y semana ya existe (solo en los datos que se envían en la solicitud)
    $consulta_verificar = "SELECT COUNT(*) FROM programadorsem WHERE Aniio = :anio AND Semana = :semana AND CodPdre = :codPdre AND CodProceso = :codProceso";
    $resultado_verificar = $conexion->prepare($consulta_verificar);

    // Consulta SQL de inserción (columna Fabricacion con F mayúscula según esquema)
    $consulta_insert = "INSERT INTO programadorsem (otpadre, CodProyecto, CodProceso, CodPdre, Aniio, Semana, Avance, TotHoras, AvanceReal, Estado, Fabricacion) 
                        VALUES (:otpadre, :codProyecto, :codProceso, :codPdre, :anio, :semana, :porcentaje, :total, '0', '1', :fabricacion)";

    // Preparar la consulta de inserción
    $resultado_insert = $conexion->prepare($consulta_insert);

    // Iterar sobre cada fila de datos y realizar la inserción
    $inserted = [];
    $skipped = [];
    $errors = [];
    // Preparar statement para insertar en programacion_historico si es necesario
    $consulta_insert_hist = "INSERT INTO programacion_historico (id_programa, anio, semana, avance, tot_horas, version_tipo, fecha_creacion, otpadre, CodProyecto, CodProceso, CodPdre) VALUES (:id_programa, :anio, :semana, :avance, :tot_horas, :version_tipo, NOW(), :otpadre, :codProyecto, :codProceso, :codPdre)";
    $resultado_insert_hist = $conexion->prepare($consulta_insert_hist);

    // Preparar statements para verificar/crear baseline por proceso
    $stmt_check_baseline = $conexion->prepare("SELECT COUNT(*) as total FROM programacion_historico WHERE version_tipo = 'BASELINE' AND otpadre = :otpadre AND CodProyecto = :codProyecto AND CodProceso = :codProceso AND CodPdre = :codPdre");
    $stmt_select_programadorsem = $conexion->prepare("SELECT IdPrograma, Aniio, Semana, Avance, TotHoras FROM programadorsem WHERE otpadre = :otpadre AND CodProyecto = :codProyecto AND CodProceso = :codProceso AND CodPdre = :codPdre ORDER BY Aniio, Semana");
    $stmt_insert_baseline = $conexion->prepare("INSERT INTO programacion_historico (id_programa, anio, semana, avance, tot_horas, version_tipo, fecha_creacion, otpadre, CodProyecto, CodProceso, CodPdre) VALUES (:id_programa, :anio, :semana, :avance, :tot_horas, 'BASELINE', NOW(), :otpadre, :codProyecto, :codProceso, :codPdre)");

    // Iniciar transacción para mayor seguridad
    try {
        $conexion->beginTransaction();

        // Trackear procesos únicos que recibieron inserciones para luego crear baseline si corresponde
        $procesos_afectados = [];

        // PRE-CALCULAR: para cada proceso entrante verificar si ya existe BASELINE.
        // Esto evita insertar registros HISTORICO cuando vamos a crear una BASELINE inmediatamente
        $incoming_processes = [];
        foreach ($datos as $d_row) {
            if (isset($d_row['idPadreOT'], $d_row['proyecto'], $d_row['proceso'], $d_row['codPdre'])) {
                $pkey = $d_row['idPadreOT'] . '||' . $d_row['proyecto'] . '||' . $d_row['proceso'] . '||' . $d_row['codPdre'];
                $incoming_processes[$pkey] = [
                    'otpadre' => $d_row['idPadreOT'],
                    'CodProyecto' => $d_row['proyecto'],
                    'CodProceso' => $d_row['proceso'],
                    'CodPdre' => $d_row['codPdre']
                ];
            }
        }

        // Ahora verificar para cada proceso entrante si ya existe BASELINE
        $baselineExists = [];
        foreach ($incoming_processes as $pkey => $procInfo) {
            $stmt_check_baseline->execute([
                ':otpadre' => $procInfo['otpadre'],
                ':codProyecto' => $procInfo['CodProyecto'],
                ':codProceso' => $procInfo['CodProceso'],
                ':codPdre' => $procInfo['CodPdre']
            ]);
            $count = (int) $stmt_check_baseline->fetchColumn();
            $baselineExists[$pkey] = ($count > 0);
        }

        foreach ($datos as $idx => $fila) {
        // Verificar si la fila tiene todos los campos necesarios
        if (isset($fila['idPadreOT'], $fila['proyecto'], $fila['proceso'], $fila['codPdre'], $fila['anio'], $fila['semana'], $fila['porcentaje'], $fila['totalHoras'])) {
            try {
                // Ejecutar verificación usando ejecución con parámetros (evita problemas de bind reuse)
                $resultado_verificar->execute([
                    ':anio' => $fila['anio'],
                    ':semana' => $fila['semana'],
                    ':codPdre' => $fila['codPdre'],
                    ':codProceso' => $fila['proceso']
                ]);
                $existe = $resultado_verificar->fetchColumn();

                // Si la semana ya existe, registramos como "skipped" y continuamos
                if ($existe > 0) {
                    $skipped[] = "Semana duplicada: La semana {$fila['semana']} para el año {$fila['anio']} ya existe en el proyecto {$fila['proyecto']} y proceso {$fila['proceso']}";
                    continue;
                }

                // Intentar insertar
                $ok = $resultado_insert->execute([
                    ':otpadre' => $fila['idPadreOT'],
                    ':codProyecto' => $fila['proyecto'],
                    ':codProceso' => $fila['proceso'],
                    ':codPdre' => $fila['codPdre'],
                    ':anio' => $fila['anio'],
                    ':semana' => $fila['semana'],
                    ':porcentaje' => $fila['porcentaje'],
                    ':total' => $fila['totalHoras'],
                    ':fabricacion' => isset($fila['fabricacion']) ? $fila['fabricacion'] : 'tama'
                ]);

                if ($ok && $resultado_insert->rowCount() > 0) {
                    // Obtener el IdPrograma generado automáticamente
                    $idPrograma = $conexion->lastInsertId();
                    
                    $inserted[] = [
                        'index' => $idx,
                        'IdPrograma' => $idPrograma, // Agregar IdPrograma para baseline
                        'anio' => $fila['anio'],
                        'semana' => $fila['semana'],
                        'proyecto' => $fila['proyecto'],
                        'proceso' => $fila['proceso']
                    ];

                    // Si el cliente pidió guardar histórico para esta fila, insertarlo
                    if (isset($fila['guardar_historico']) && $fila['guardar_historico']) {
                        // Solo insertar HISTORICO si ya existía una BASELINE para este proceso.
                        // Si no existía baseline y vamos a crearla ahora, omitir HISTORICO para evitar duplicados visuales.
                        $procKey = $fila['idPadreOT'] . '||' . $fila['proyecto'] . '||' . $fila['proceso'] . '||' . $fila['codPdre'];
                        $shouldInsertHist = true;
                        if (isset($baselineExists[$procKey]) && $baselineExists[$procKey] === false) {
                            // No existe baseline aún -> omitimos insertar HISTORICO (la baseline se creará más abajo)
                            $shouldInsertHist = false;
                        }

                        if ($shouldInsertHist) {
                            try {
                                $resultado_insert_hist->execute([
                                    ':id_programa' => $idPrograma,
                                    ':anio' => $fila['anio'],
                                    ':semana' => $fila['semana'],
                                    ':avance' => $fila['porcentaje'],
                                    ':tot_horas' => $fila['totalHoras'],
                                    ':version_tipo' => 'HISTORICO',
                                    ':otpadre' => $fila['idPadreOT'],
                                    ':codProyecto' => $fila['proyecto'],
                                    ':codPdre' => $fila['codPdre'],
                                    ':codProceso' => $fila['proceso']
                                ]);
                            } catch (Exception $exh) {
                                // Registrar error pero continuar (histórico no crítico)
                                $errors[] = "Error insertando historico para IdPrograma={$idPrograma}: " . $exh->getMessage();
                            }
                        } else {
                            // Log interno: omitimos HISTORICO porque se creará BASELINE a continuación
                            // (no es crítico para la respuesta al cliente)
                        }
                        }

                    // Marcar este proceso como afectado (clave única por proceso)
                    $procKey = $fila['idPadreOT'] . '||' . $fila['proyecto'] . '||' . $fila['proceso'] . '||' . $fila['codPdre'];
                    $procesos_afectados[$procKey] = [
                        'otpadre' => $fila['idPadreOT'],
                        'CodProyecto' => $fila['proyecto'],
                        'CodProceso' => $fila['proceso'],
                        'CodPdre' => $fila['codPdre']
                    ];
                } else {
                    $errors[] = "No se pudo insertar la fila: anio={$fila['anio']}, semana={$fila['semana']}, proyecto={$fila['proyecto']}, proceso={$fila['proceso']}";
                }
            } catch (Exception $ex) {
                $errors[] = "Error en fila (anio={$fila['anio']}, semana={$fila['semana']}): " . $ex->getMessage();
            }
        } else {
            $errors[] = "Fila incompleta en índice {$idx}";
        }
    }

        // --- Para cada proceso afectado, verificar y crear BASELINE si es necesario ---
        foreach ($procesos_afectados as $proc) {
            // Verificar existencia de baseline
            $stmt_check_baseline->execute([
                ':otpadre' => $proc['otpadre'],
                ':codProyecto' => $proc['CodProyecto'],
                ':codProceso' => $proc['CodProceso'],
                ':codPdre' => $proc['CodPdre']
            ]);
            $baseline_count = (int) $stmt_check_baseline->fetchColumn();
            if ($baseline_count === 0) {
                // Obtener registros actuales del proceso
                $stmt_select_programadorsem->execute([
                    ':otpadre' => $proc['otpadre'],
                    ':codProyecto' => $proc['CodProyecto'],
                    ':codProceso' => $proc['CodProceso'],
                    ':codPdre' => $proc['CodPdre']
                ]);
                $registros_proceso = $stmt_select_programadorsem->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($registros_proceso)) {
                    foreach ($registros_proceso as $r) {
                        // Insertar cada registro como BASELINE
                        $stmt_insert_baseline->execute([
                            ':id_programa' => $r['IdPrograma'],
                            ':anio' => $r['Aniio'],
                            ':semana' => $r['Semana'],
                            ':avance' => $r['Avance'],
                            ':tot_horas' => $r['TotHoras'],
                            ':otpadre' => $proc['otpadre'],
                            ':codProyecto' => $proc['CodProyecto'],
                            ':codProceso' => $proc['CodProceso'],
                            ':codPdre' => $proc['CodPdre']
                        ]);
                    }
                }
            }
        }

        // Commit transaction (todo OK)
        $conexion->commit();

        // Preparar la respuesta resumida
        $response = [
            'success' => count($inserted) > 0,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => ''
        ];

        if (count($inserted) > 0) {
            $response['message'] = count($inserted) . ' registro(s) insertado(s).' . (count($skipped) > 0 ? ' ' . count($skipped) . ' fila(s) ignorada(s) por duplicado.' : '');
        } else {
            if (count($skipped) > 0 && count($errors) === 0) {
                $response['message'] = 'Ningún registro fue insertado. Todas las filas están duplicadas.';
            } elseif (count($errors) > 0) {
                $response['message'] = 'Se encontraron errores al insertar algunas filas.';
            } else {
                $response['message'] = 'No se insertaron registros.';
            }
        }

        echo json_encode($response);

    } catch (Exception $e) {
        // Rollback si hay cualquier fallo
        try { $conexion->rollBack(); } catch (Exception $__) {}
        $errorMsg = 'Error procesando inserciones: ' . $e->getMessage();
        echo json_encode(['success' => false, 'message' => $errorMsg, 'errors' => [$errorMsg]]);
    }
} else {
    // Si los datos no fueron decodificados correctamente
    echo json_encode(["success" => false, "message" => "Error al procesar los datos"]);
}

// Cerrar la conexión
$conexion = null;
?>
