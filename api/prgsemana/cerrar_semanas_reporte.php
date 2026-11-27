<?php
require_once __DIR__ . '/../hhmeta_guard.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/validar_cierre_reporte.php';
require_once __DIR__.'/sincronizar_avance.php';
header('Content-Type: application/json; charset=utf-8');

$conexion = null;
try {
    $claves = $_POST['filas'] ?? [];
    if (!is_array($claves) || !$claves || count($claves) > 1000) {
        throw new RuntimeException('Selecciona entre 1 y 1000 filas para realizar el cierre.');
    }
    $claves = array_values(array_unique($claves, SORT_STRING));
    sort($claves, SORT_STRING);
    $filas = [];
    foreach ($claves as $clave) {
        if (!is_string($clave) || strlen($clave) > 2048) {
            throw new RuntimeException('La selección no es válida. Abre nuevamente el reporte.');
        }
        $raw = base64_decode($clave, true);
        $campos = $raw === false ? null : json_decode($raw, true);
        if (!is_array($campos) || array_keys($campos) !== range(0, 5)) {
            throw new RuntimeException('La selección está incompleta. Abre nuevamente el reporte.');
        }
        foreach ($campos as $campo) {
            if (!is_string($campo) || $campo === '' || strlen($campo) > 255) {
                throw new RuntimeException('Faltan datos para identificar una semana. Contacta a soporte.');
            }
        }
        $filas[] = $campos;
    }

    $conexion = Conexion::Conectar();
    $conexion->beginTransaction();
    // Confirmar una única coincidencia antes de modificar cualquier semana.
    $where = 'otpadre = ? AND CodProyecto = ? AND CodPdre = ? AND CodProceso = ? AND Aniio = ? AND Semana = ? AND Estado = 1';
    $consulta = $conexion->prepare('SELECT IdPrograma, Avance, AvanceReal FROM programadorsem WHERE ' . $where . ' FOR UPDATE');
    $actualizarAvance = $conexion->prepare('UPDATE programadorsem SET AvanceReal = ? WHERE IdPrograma = ? AND Estado = 1');
    foreach ($filas as $campos) {
        $consulta->execute($campos);
        $coincidencias = $consulta->fetchAll(PDO::FETCH_ASSOC);
        if (count($coincidencias) === 0) {
            throw new RuntimeException('Una semana seleccionada ya no está pendiente. Actualiza el reporte antes de cerrar.');
        }
        if (count($coincidencias) !== 1) {
            throw new RuntimeException('Hay programaciones duplicadas para una semana seleccionada. No se cerró ninguna fila; solicita su revisión a soporte.');
        }
        $actual = $coincidencias[0];
        if ((float)($actual['AvanceReal'] ?? 0) <= 0) {
            $avanceProgramado = (float)($actual['Avance'] ?? 0);
            if ($avanceProgramado <= 0 || $avanceProgramado > 100) {
                throw new RuntimeException('La semana ' . $campos[5] . '/' . $campos[4] . ' no tiene un PROG.HH válido para copiar al avance físico.');
            }
            $actualizarAvance->execute([$avanceProgramado, $actual['IdPrograma']]);
            if ($actualizarAvance->rowCount() !== 1) throw new RuntimeException('No pudimos guardar el avance físico de una semana.');
        }
    }
    // Bloquear todas las semanas de cada proceso para calcular acumulados
    // incluyendo semanas cerradas y validar el efecto de toda la selección.
    $grupos = [];
    foreach ($filas as $campos) {
        $claveGrupo = json_encode(array_slice($campos, 0, 4));
        $grupos[$claveGrupo][] = $campos;
    }
    ksort($grupos, SORT_STRING);
    $consultaGrupo = $conexion->prepare(
        'SELECT Aniio, Semana, Avance, AvanceReal, Estado FROM programadorsem
         WHERE otpadre = ? AND CodProyecto = ? AND CodPdre = ? AND CodProceso = ? FOR UPDATE'
    );
    $avisos = [];
    foreach ($grupos as $seleccionadas) {
        $grupo = array_slice($seleccionadas[0], 0, 4);
        $consultaGrupo->execute($grupo);
        $semanas = [];
        foreach ($consultaGrupo->fetchAll(PDO::FETCH_NUM) as $fila) {
            $semanas[] = array_combine(['Aniio', 'Semana', 'Avance', 'AvanceReal', 'Estado'], $fila);
        }
        $avisos = array_merge($avisos, validarCierreSemanasReporte($semanas, $seleccionadas, $grupo));
    }
    $actualizar = $conexion->prepare('UPDATE programadorsem SET Estado = 2 WHERE ' . $where);
    $afectados = 0;
    foreach ($filas as $campos) {
        $actualizar->execute($campos);
        if ($actualizar->rowCount() !== 1) {
            throw new RuntimeException('No pudimos confirmar el cierre de una semana. No se guardaron cambios.');
        }
        $afectados++;
    }
    $conexion->commit();
    $items=[];foreach($filas as $campos)$items[]=['idPadreOT'=>$campos[0],'codPdre'=>$campos[2]];
    $sapSync=psSincronizarItems($conexion,$items);$avisos=array_merge($avisos,$sapSync['warnings']);
    echo json_encode(['success' => true, 'afectados' => $afectados, 'claves' => $claves, 'sapSync'=>$sapSync, 'warnings' => array_values(array_unique($avisos))], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($conexion && $conexion->inTransaction()) $conexion->rollBack();
    http_response_code(400);
    $mensaje = $e instanceof PDOException
        ? 'No pudimos guardar el cierre. Inténtalo nuevamente o contacta a soporte.'
        : $e->getMessage();
    error_log('Cierre reporte semanas: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
}
