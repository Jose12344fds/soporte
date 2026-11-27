<?php
require_once __DIR__ . '/../hhmeta_guard.php';
// Configuración estricta - SOLO JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(0);

// Función para enviar SOLO JSON
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Función para manejar errores
function handleError($message, $details = '') {
    $log_file = __DIR__ . '/baseline_debug.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " ERROR PROCESO: $message\n$details\n", FILE_APPEND);
    
    sendJsonResponse([
        'success' => false,
        'error' => $message,
        'debug_info' => 'Ver baseline_debug.log para detalles'
    ]);
}

try {
    // Log de inicio
    $log_file = __DIR__ . '/baseline_debug.log';
    file_put_contents($log_file, "\n=== VERIFICAR BASELINE PROCESO " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
    
    // Verificar parámetros POST
    if (!isset($_POST['otpadre']) || !isset($_POST['cod_proyecto']) || !isset($_POST['cod_proceso']) || !isset($_POST['cod_pdre'])) {
        handleError('Parámetros requeridos faltantes', 'otpadre, cod_proyecto, cod_proceso y cod_pdre son obligatorios');
    }
    
    $otpadre = $_POST['otpadre'];
    $cod_proyecto = $_POST['cod_proyecto'];
    $cod_proceso = $_POST['cod_proceso'];
    $cod_pdre = $_POST['cod_pdre'];
    
    file_put_contents($log_file, "Parámetros: otpadre=$otpadre, proyecto=$cod_proyecto, proceso=$cod_proceso, pdre=$cod_pdre\n", FILE_APPEND);
    
    // Conexión a BD (usar mismo host/puerto que el resto del proyecto)
    $conexion = new mysqli('127.0.0.1:8111', 'root', '', 'pcp', 8111);
    
    if ($conexion->connect_error) {
        handleError('Error de conexión', $conexion->connect_error);
    }
    
    file_put_contents($log_file, "Conexión exitosa\n", FILE_APPEND);
    
    // Verificar si existe baseline para este proceso
    $stmt_baseline = $conexion->prepare("
        SELECT COUNT(*) as total 
        FROM programacion_historico 
        WHERE version_tipo = 'BASELINE' 
        AND otpadre = ? AND CodProyecto = ? AND CodProceso = ? AND CodPdre = ?
    ");
    
    $stmt_baseline->bind_param("ssss", $otpadre, $cod_proyecto, $cod_proceso, $cod_pdre);
    $stmt_baseline->execute();
    // Log query info
    file_put_contents($log_file, "Executed baseline check for proceso={$cod_proceso}, otpadre={$otpadre}, proyecto={$cod_proyecto}, pdre={$cod_pdre}\n", FILE_APPEND);
    $baseline_count = $stmt_baseline->get_result()->fetch_assoc()['total'];
    
    $tiene_baseline = ($baseline_count > 0);
    
    file_put_contents($log_file, "Baseline count: $baseline_count, Tiene: " . ($tiene_baseline ? 'SI' : 'NO') . "\n", FILE_APPEND);
    
    sendJsonResponse([
        'success' => true,
        'tiene_baseline' => $tiene_baseline,
        'baseline_count' => $baseline_count,
        'proceso' => $cod_proceso,
        'otpadre' => $otpadre,
        'cod_proyecto' => $cod_proyecto,
        'cod_pdre' => $cod_pdre
    ]);
    
} catch (Exception $e) {
    handleError('Excepción', $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
} catch (Error $e) {
    handleError('Error fatal', $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
}
?>
