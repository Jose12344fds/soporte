<?php
require_once __DIR__ . '/../../hhmeta_guard.php';
/**
 * API JSON: Guardar OT Padre
 * INSERT en tabla otpadre si el ProjectCode no existe
 * 
 * Recibe JSON: { numValuePE, proyecto, nproyecto, nivelpro, HrHnivelpro }
 * Responde JSON: { ok, message, inserted, idPadre } o { ok:false, error, detail }
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

    // 2. Validar campos requeridos
    $proyecto = trim($data['proyecto'] ?? '');
    $numValuePE = intval($data['numValuePE'] ?? 0);
    $nproyecto = trim($data['nproyecto'] ?? '');
    $nivelpro = trim($data['nivelpro'] ?? '');
    $horas = trim($data['HrHnivelpro'] ?? '');

    if (empty($proyecto)) {
        ob_end_clean();
        echo json_encode([
            'ok' => false,
            'error' => 'Campo "proyecto" (ProjectCode) es requerido',
            'data_received' => $data
        ]);
        exit;
    }

    // 3. Conectar a BD
    $conexion = getConexionLocal();

    // 4. Verificar si el ProjectCode ya existe
    $stmtCheck = $conexion->prepare("SELECT IdPadreOT, IdPadre FROM otpadre WHERE ProjectCode = :pc");
    $stmtCheck->execute([':pc' => $proyecto]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        // Ya existe — retornar el IdPadreOT existente (no es error)
        ob_end_clean();
        echo json_encode([
            'ok' => true,
            'inserted' => false,
            'message' => 'ProjectCode ya existe en otpadre',
            'idPadreOT' => (int)$existing['IdPadreOT'],
            'idPadre' => (int)$existing['IdPadre']
        ]);
        exit;
    }

    // 5. Insertar nuevo registro
    // Insertar sin especificar IdPadre e IdPadreOT (ambas auto_increment)
    $conexion->beginTransaction();

    $sql = "INSERT INTO otpadre (ProjectCode, NPdre, NivProyecto, Horas, Estado_OT) 
            VALUES (:projectCode, :Nproyecto, :Nivelproyecto, :Horas, '1')";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ':projectCode'    => $proyecto,
        ':Nproyecto'      => $nproyecto,
        ':Nivelproyecto'  => $nivelpro,
        ':Horas'          => $horas
    ]);

    $newId = $conexion->lastInsertId(); // Obtener IdPadreOT auto-generado
    
    // Sincronizar: IdPadre = IdPadreOT (ambas deben tener el mismo valor)
    // Buscar por IdPadreOT (que ya existe) y actualizar IdPadre
    $sqlSync = "UPDATE otpadre SET IdPadre = :id WHERE IdPadreOT = :id";
    $stmtSync = $conexion->prepare($sqlSync);
    $stmtSync->execute([':id' => $newId]);
    
    $conexion->commit();

    ob_end_clean();
    echo json_encode([
        'ok' => true,
        'inserted' => true,
        'message' => 'OT Padre insertado correctamente',
        'idPadreOT' => (int)$newId,
        'idPadre' => (int)$newId  // Usar el auto-generated ID, no $numValuePE
    ]);

} catch (Throwable $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    ob_end_clean();
    echo json_encode([
        'ok' => false,
        'error' => 'Error en guardar_otpadre',
        'detail' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
