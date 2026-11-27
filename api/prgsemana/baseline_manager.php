<?php
require_once __DIR__ . '/../hhmeta_guard.php';
// Configuración estricta - SOLO JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(0); // Suprimir TODOS los errores HTML

// Función para enviar SOLO JSON
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Función para manejar errores
function handleError($message, $details = '') {
    $log_file = __DIR__ . '/baseline_debug.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " ERROR: $message\n$details\n", FILE_APPEND);
    
    sendJsonResponse([
        'success' => false,
        'error' => $message,
        'debug_info' => 'Ver baseline_debug.log para detalles'
    ]);
}

// Capturar cualquier error o output no deseado
ob_start();

try {
    // Log de inicio
    $log_file = __DIR__ . '/baseline_debug.log';
    file_put_contents($log_file, "\n=== BASELINE MANAGER " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
    
    // Verificar parámetros POST
    if (!isset($_POST['accion']) || !isset($_POST['id_programa'])) {
        handleError('Parámetros requeridos faltantes', 'accion y id_programa son obligatorios');
    }
    
    $accion = $_POST['accion'];
    $id_programa = $_POST['id_programa'];
    
    file_put_contents($log_file, "Acción: $accion, ID: $id_programa\n", FILE_APPEND);
    
    // Conexión a BD
    $conexion = new mysqli('127.0.0.1:8111', 'root', '', 'pcp');
    
    if ($conexion->connect_error) {
        handleError('Error de conexión', $conexion->connect_error);
    }
    
    file_put_contents($log_file, "Conexión exitosa\n", FILE_APPEND);
    
    // Procesar acciones
    switch ($accion) {
        case 'verificar_baseline':
            verificarBaseline($conexion, $id_programa, $log_file);
            break;
            
        case 'crear_baseline':
            crearBaseline($conexion, $id_programa, $log_file);
            break;
            
        case 'obtener_comparacion':
            obtenerComparacion($conexion, $id_programa, $log_file);
            break;
            
        default:
            handleError('Acción no válida', "Acción '$accion' no es válida");
    }
    
} catch (Exception $e) {
    handleError('Excepción', $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
} catch (Error $e) {
    handleError('Error fatal', $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
}

// Funciones específicas
function verificarBaseline($conexion, $id_programa, $log_file) {
    file_put_contents($log_file, "=== VERIFICAR BASELINE ID: $id_programa ===\n", FILE_APPEND);
    
    // Obtener info del programa
    $stmt = $conexion->prepare("SELECT otpadre, CodProyecto, CodProceso, CodPdre, tiene_baseline, fecha_baseline FROM programadorsem WHERE IdPrograma = ?");
    $stmt->bind_param("s", $id_programa);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        handleError('Programa no encontrado', "No existe el programa con ID $id_programa");
    }
    
    $programa = $result->fetch_assoc();
    file_put_contents($log_file, "Programa info: " . json_encode($programa) . "\n", FILE_APPEND);
    
    // Verificar si existe baseline en historial
    $stmt_baseline = $conexion->prepare("
        SELECT COUNT(*) as total 
        FROM programacion_historico 
        WHERE version_tipo = 'BASELINE' 
        AND otpadre = ? AND CodProyecto = ? AND CodProceso = ? AND CodPdre = ?
    ");
    $stmt_baseline->bind_param("ssss", 
        $programa['otpadre'], 
        $programa['CodProyecto'], 
        $programa['CodProceso'], 
        $programa['CodPdre']
    );
    $stmt_baseline->execute();
    $baseline_count = $stmt_baseline->get_result()->fetch_assoc()['total'];
    
    $tiene_baseline = ($baseline_count > 0);
    
    file_put_contents($log_file, "Baseline count: $baseline_count, Tiene: " . ($tiene_baseline ? 'SI' : 'NO') . "\n", FILE_APPEND);
    
    sendJsonResponse([
        'success' => true,
        'tiene_baseline' => $tiene_baseline,
        'fecha_baseline' => $programa['fecha_baseline'],
        'proceso' => $programa['CodProceso'],
        'baseline_count' => $baseline_count
    ]);
}

function crearBaseline($conexion, $id_programa, $log_file) {
    file_put_contents($log_file, "=== CREAR BASELINE ID: $id_programa ===\n", FILE_APPEND);
    
    // Obtener info del programa
    $stmt = $conexion->prepare("SELECT otpadre, CodProyecto, CodProceso, CodPdre FROM programadorsem WHERE IdPrograma = ? LIMIT 1");
    $stmt->bind_param("s", $id_programa);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        handleError('Programa no encontrado', "No existe el programa con ID $id_programa");
    }
    
    $programa_info = $result->fetch_assoc();
    file_put_contents($log_file, "Programa info: " . json_encode($programa_info) . "\n", FILE_APPEND);
    
    // Verificar anti-duplicados
    $stmt_check = $conexion->prepare("
        SELECT COUNT(*) as total 
        FROM programacion_historico 
        WHERE version_tipo = 'BASELINE' 
        AND otpadre = ? AND CodProyecto = ? AND CodProceso = ? AND CodPdre = ?
    ");
    $stmt_check->bind_param("ssss", 
        $programa_info['otpadre'], 
        $programa_info['CodProyecto'], 
        $programa_info['CodProceso'], 
        $programa_info['CodPdre']
    );
    $stmt_check->execute();
    $existing_count = $stmt_check->get_result()->fetch_assoc()['total'];
    
    if ($existing_count > 0) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Baseline ya existe para proceso ' . $programa_info['CodProceso'],
            'baseline_existentes' => $existing_count,
            'skipped' => true
        ]);
    }
    
    // Obtener todos los registros del proceso
    $stmt_registros = $conexion->prepare("
        SELECT IdPrograma, Aniio, Semana, Avance, TotHoras 
        FROM programadorsem 
        WHERE otpadre = ? AND CodProyecto = ? AND CodProceso = ? AND CodPdre = ?
        ORDER BY Aniio, Semana
    ");
    $stmt_registros->bind_param("ssss", 
        $programa_info['otpadre'], 
        $programa_info['CodProyecto'], 
        $programa_info['CodProceso'], 
        $programa_info['CodPdre']
    );
    $stmt_registros->execute();
    $registros = $stmt_registros->get_result()->fetch_all(MYSQLI_ASSOC);
    
    file_put_contents($log_file, "Registros encontrados: " . count($registros) . "\n", FILE_APPEND);
    
    if (empty($registros)) {
        handleError('No hay registros para crear baseline', "No se encontraron registros del proceso");
    }
    
    // Insertar cada registro como baseline
    $stmt_insert = $conexion->prepare("
        INSERT INTO programacion_historico 
        (id_programa, anio, semana, avance, tot_horas, version_tipo, fecha_creacion, otpadre, CodProyecto, CodProceso, CodPdre) 
        VALUES (?, ?, ?, ?, ?, 'BASELINE', NOW(), ?, ?, ?, ?)
    ");
    
    $insertados = 0;
    foreach ($registros as $registro) {
        $stmt_insert->bind_param("siiddssss", 
            $registro['IdPrograma'], 
            $registro['Aniio'], 
            $registro['Semana'], 
            $registro['Avance'], 
            $registro['TotHoras'],
            $programa_info['otpadre'],
            $programa_info['CodProyecto'],
            $programa_info['CodProceso'],
            $programa_info['CodPdre']
        );
        
        if ($stmt_insert->execute()) {
            $insertados++;
            file_put_contents($log_file, "Insertado: ID={$registro['IdPrograma']}, Semana={$registro['Semana']}\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, "Error insert: " . $stmt_insert->error . "\n", FILE_APPEND);
        }
    }
    
    // Actualizar flag de baseline
    $stmt_update = $conexion->prepare("
        UPDATE programadorsem 
        SET tiene_baseline = 1, fecha_baseline = NOW() 
        WHERE otpadre = ? AND CodProyecto = ? AND CodProceso = ? AND CodPdre = ?
    ");
    $stmt_update->bind_param("ssss", 
        $programa_info['otpadre'], 
        $programa_info['CodProyecto'], 
        $programa_info['CodProceso'], 
        $programa_info['CodPdre']
    );
    $stmt_update->execute();
    
    sendJsonResponse([
        'success' => true,
        'message' => "Baseline creado exitosamente para proceso {$programa_info['CodProceso']}",
        'registros_insertados' => $insertados,
        'proceso' => $programa_info['CodProceso']
    ]);
}

function obtenerComparacion($conexion, $id_programa, $log_file) {
    file_put_contents($log_file, "=== OBTENER COMPARACION ID: $id_programa ===\n", FILE_APPEND);
    
    // Obtener info del programa
    $stmt = $conexion->prepare("SELECT otpadre, CodProyecto, CodProceso, CodPdre FROM programadorsem WHERE IdPrograma = ? LIMIT 1");
    $stmt->bind_param("s", $id_programa);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        handleError('Programa no encontrado', "No existe el programa con ID $id_programa");
    }
    
    $programa_info = $result->fetch_assoc();
    file_put_contents($log_file, "Programa info: " . json_encode($programa_info) . "\n", FILE_APPEND);
    
    // Obtener datos del baseline
    $stmt_baseline = $conexion->prepare("
        SELECT anio, semana, avance, tot_horas, fecha_creacion
        FROM programacion_historico 
        WHERE version_tipo = 'BASELINE' 
        AND otpadre = ? AND CodProyecto = ? AND CodProceso = ? AND CodPdre = ?
        ORDER BY anio, semana
    ");
    $stmt_baseline->bind_param("ssss", 
        $programa_info['otpadre'], 
        $programa_info['CodProyecto'], 
        $programa_info['CodProceso'], 
        $programa_info['CodPdre']
    );
    $stmt_baseline->execute();
    $datos_baseline = $stmt_baseline->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Obtener datos actuales
    $stmt_actual = $conexion->prepare("
        SELECT Aniio as anio, Semana as semana, Avance as avance, TotHoras as tot_horas
        FROM programadorsem 
        WHERE otpadre = ? AND CodProyecto = ? AND CodProceso = ? AND CodPdre = ?
        ORDER BY Aniio, Semana
    ");
    $stmt_actual->bind_param("ssss", 
        $programa_info['otpadre'], 
        $programa_info['CodProyecto'], 
        $programa_info['CodProceso'], 
        $programa_info['CodPdre']
    );
    $stmt_actual->execute();
    $datos_actuales = $stmt_actual->get_result()->fetch_all(MYSQLI_ASSOC);
    
    file_put_contents($log_file, "Baseline records: " . count($datos_baseline) . "\n", FILE_APPEND);
    file_put_contents($log_file, "Actual records: " . count($datos_actuales) . "\n", FILE_APPEND);
    
    // Generar resumen de cambios
    $resumen = [];
    $cambios_detectados = false;
    
    // Crear mapas para comparación fácil
    $baseline_map = [];
    foreach ($datos_baseline as $item) {
        $key = $item['anio'] . '-' . $item['semana'];
        $baseline_map[$key] = $item;
    }
    
    $actual_map = [];
    foreach ($datos_actuales as $item) {
        $key = $item['anio'] . '-' . $item['semana'];
        $actual_map[$key] = $item;
    }
    
    // Verificar semanas eliminadas
    foreach ($baseline_map as $key => $baseline_item) {
        if (!isset($actual_map[$key])) {
            $resumen[] = "Semana {$baseline_item['anio']}-{$baseline_item['semana']} eliminada (antes: {$baseline_item['avance']}% / {$baseline_item['tot_horas']}h)";
            $cambios_detectados = true;
        }
    }
    
    // Verificar semanas nuevas y modificadas
    foreach ($actual_map as $key => $actual_item) {
        if (!isset($baseline_map[$key])) {
            $resumen[] = "Semana {$actual_item['anio']}-{$actual_item['semana']} agregada (nuevo: {$actual_item['avance']}% / {$actual_item['tot_horas']}h)";
            $cambios_detectados = true;
        } else {
            $baseline_item = $baseline_map[$key];
            if ($baseline_item['avance'] != $actual_item['avance'] || $baseline_item['tot_horas'] != $actual_item['tot_horas']) {
                $resumen[] = "Semana {$actual_item['anio']}-{$actual_item['semana']} modificada (antes: {$baseline_item['avance']}% / {$baseline_item['tot_horas']}h → ahora: {$actual_item['avance']}% / {$actual_item['tot_horas']}h)";
                $cambios_detectados = true;
            }
        }
    }
    
    if (!$cambios_detectados) {
        $resumen[] = "No hay cambios respecto a la línea base";
    }
    
    sendJsonResponse([
        'success' => true,
        'message' => 'Comparación generada exitosamente',
        'datos_baseline' => $datos_baseline,
        'datos_actuales' => $datos_actuales,
        'comparacion' => [
            'resumen' => $resumen,
            'tiene_cambios' => $cambios_detectados,
            'total_baseline' => count($datos_baseline),
            'total_actual' => count($datos_actuales)
        ],
        'proceso' => $programa_info['CodProceso']
    ]);
}
?>
