<?php
/**
 * API Router - HH_META (Módulo de Control de Horas)
 * Centraliza todas las consultas a SAP HANA para OT, Proyectos, Items, Horas, etc.
 * 
 * Replaza: consultasAjax/*.php
 * 
 * Endpoints:
 * POST ?action=obtener_ot - Obtener datos de OT
 * POST ?action=obtener_items - Obtener items del proyecto
 * POST ?action=obtener_tabla - Obtener tabla de datos
 * POST ?action=obtener_nombres_item - Nombres de items
 * POST ?action=obtener_cod_plano - Código de plano
 * POST ?action=actualizar_estado - Actualizar estado
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/load.php';

$response = ['success' => false, 'message' => 'Acción no especificada'];

try {
    ob_end_clean();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Validar que sea POST
    if ($method !== 'POST') {
        throw new Exception('Solo se aceptan solicitudes POST');
    }
    
    // Cargar conexión a SAP HANA si existe
    $conexion_hana = null;
    if (file_exists(__DIR__ . '/../hhmeta/conexion_hana.php')) {
        // Aquí va tu lógica de conexión a HANA
    }
    
    switch ($action) {
        case 'obtener_ot':
            $response = obtenerOT();
            break;
        case 'obtener_items':
            $response = obtenerItems();
            break;
        case 'obtener_tabla':
            $response = obtenerTabla();
            break;
        case 'obtener_nombres_item':
            $response = obtenerNombresItem();
            break;
        case 'obtener_cod_plano':
            $response = obtenerCodPlano();
            break;
        case 'obtener_proyectos':
            $response = obtenerProyectosSAP();
            break;
        case 'actualizar_estado':
            $response = actualizarEstado();
            break;
        default:
            $response = [
                'success' => false, 
                'message' => 'Acción no reconocida: ' . $action,
                'acciones_disponibles' => [
                    'obtener_ot',
                    'obtener_items',
                    'obtener_tabla',
                    'obtener_nombres_item',
                    'obtener_cod_plano',
                    'obtener_proyectos',
                    'actualizar_estado'
                ]
            ];
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

/**
 * Obtener datos de una Orden de Trabajo (OT)
 */
function obtenerOT() {
    $periodo = trim($_POST['periodo'] ?? $_POST['OT'] ?? '');
    
    if (empty($periodo)) {
        return ['success' => false, 'message' => 'Período/OT requerido'];
    }
    
    try {
        // Incluir la consulta original
        ob_start();
        include __DIR__ . '/../../modules/HH_META/consultasAjax/consulta.php';
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Error en respuesta JSON'];
        }
        
        return [
            'success' => true,
            'data' => $data ?: []
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Obtener items de un proyecto
 */
function obtenerItems() {
    $proyecto = trim($_POST['proyecto'] ?? '');
    
    if (empty($proyecto)) {
        return ['success' => false, 'message' => 'Proyecto requerido'];
    }
    
    try {
        ob_start();
        include __DIR__ . '/../../modules/HH_META/consultasAjax/obtener_items.php';
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Error en respuesta JSON'];
        }
        
        return [
            'success' => true,
            'data' => $data ?: []
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Obtener tabla de datos (DataTable)
 */
function obtenerTabla() {
    try {
        ob_start();
        include __DIR__ . '/../../modules/HH_META/consultasAjax/consulta3.php';
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Error en respuesta JSON'];
        }
        
        return [
            'success' => true,
            'data' => $data ?: []
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Obtener nombres de items
 */
function obtenerNombresItem() {
    try {
        ob_start();
        include __DIR__ . '/../../modules/HH_META/consultasAjax/NombreItem.php';
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Error en respuesta JSON'];
        }
        
        return [
            'success' => true,
            'data' => $data ?: []
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Obtener código de plano
 */
function obtenerCodPlano() {
    try {
        ob_start();
        include __DIR__ . '/../../modules/HH_META/consultasAjax/CodPlanoItem.php';
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Error en respuesta JSON'];
        }
        
        return [
            'success' => true,
            'data' => $data ?: []
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Actualizar estado
 */
function actualizarEstado() {
    $datos = $_POST;
    
    try {
        ob_start();
        include __DIR__ . '/../../modules/HH_META/consultasAjax/actualizar_estado.php';
        $output = ob_get_clean();
        
        $result = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Error en respuesta JSON'];
        }
        
        return [
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'data' => $result
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Contar variables OT Padre
 */
function CountVariabel() {
    global $db;
    try {
        $sql = "SELECT COUNT(*) as NUM FROM otpadre";
        $result = $db->query($sql);
        if ($result && $row = $db->fetch_assoc($result)) {
            return array(array('NUM' => (int)$row['NUM'] + 1));
        }
        return array(array('NUM' => 1));
    } catch (Exception $e) {
        error_log('Error en CountVariabel(): ' . $e->getMessage());
        return array(array('NUM' => 1));
    }
}

/**
 * Obtener datos de OT Padre
 */
function OT() {
    global $db;
    try {
        $sql = "SELECT * FROM otpadre";
        return find_by_sql($sql);
    } catch (Exception $e) {
        error_log('Error en OT(): ' . $e->getMessage());
        return array();
    }
}

/**
 * Obtener proyectos desde SAP HANA
 * Filtra por horas de fabricación
 */
function obtenerProyectosSAP() {
    try {
        // Obtener filtro de horas
        $horaOption = $_POST['horaOption'] ?? $_GET['horaOption'] ?? 'mayor';
        
        // Incluir helper de SAP
        require_once __DIR__ . '/../helpers/sap-conexion.php';
        
        // Crear instancia y obtener proyectos
        $sap = new SAPHANAConnection();
        $proyectos = $sap->obtenerProyectos($horaOption);
        $sap->desconectar();
        
        // Si no hay conexión a SAP, intentar desde base de datos local como fallback
        if (empty($proyectos)) {
            global $db;
            
            // Fallback: intentar consultar desde local si existe tabla
            $sql = "SELECT 
                        CAST(ID_OT AS CHAR) as OT,
                        Nombre_Proyecto as Proyecto1,
                        Nivel_Proyecto as Nivelproyecto,
                        CAST(Peso_Presup as DECIMAL(10,2)) as TotalPesPptoTotaKg,
                        CAST(Peso_Real as DECIMAL(10,2)) as PeNetTotKg,
                        CAST(Horas_Fabrica as DECIMAL(10,2)) as HHFab
                    FROM otpadre 
                    WHERE 1=1";
            
            if ($horaOption === 'mayor') {
                $sql .= " AND Horas_Fabrica > 300";
            } else if ($horaOption === 'menor') {
                $sql .= " AND Horas_Fabrica < 300";
            }
            
            $sql .= " ORDER BY Horas_Fabrica DESC LIMIT 100";
            
            $result = $db->query($sql);
            
            $proyectos = [];
            if ($result) {
                while ($row = $db->fetch_assoc($result)) {
                    $proyectos[] = $row;
                }
            }
        }
        
        return [
            'success' => true,
            'data' => $proyectos ?: [],
            'count' => count($proyectos),
            'filtro' => $horaOption
        ];
        
    } catch (Exception $e) {
        error_log('Error en obtenerProyectosSAP(): ' . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => []
        ];
    }
}
?>
