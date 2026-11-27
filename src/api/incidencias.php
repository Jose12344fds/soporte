<?php
/**
 * API Router - Incidencias Endpoint
 * Maneja operaciones CRUD para incidencias
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

require_once __DIR__ . '/../../includes/load.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            if ($action === 'datatable') {
                handleDatatable();
            } elseif ($action === 'stats') {
                getStats();
            } elseif (isset($_GET['id'])) {
                getIncidencia($_GET['id']);
            } else {
                getAllIncidencias();
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            createIncidencia($data);
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            updateIncidencia($data);
            break;
            
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            deleteIncidencia($data['id']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getAllIncidencias() {
    global $db;
    $sql = "SELECT i.*, t.DescripcionTP as TipoProblema, d.DescripcionD as Dificultad 
            FROM incidencias i 
            LEFT JOIN tipoproblema t ON i.CodTipoPro = t.CodTipoPro 
            LEFT JOIN dificultad d ON i.CodDifi = d.CodDifi 
            ORDER BY i.IdInci DESC";
    $result = $db->query($sql);
    $incidencias = $db->while_loop($result);
    echo json_encode(['data' => $incidencias, 'success' => true]);
}

function getIncidencia($id) {
    global $db;
    $id = (int) $id;
    $sql = "SELECT i.*, t.DescripcionTP as TipoProblema, d.DescripcionD as Dificultad 
            FROM incidencias i 
            LEFT JOIN tipoproblema t ON i.CodTipoPro = t.CodTipoPro 
            LEFT JOIN dificultad d ON i.CodDifi = d.CodDifi 
            WHERE i.IdInci = {$id} LIMIT 1";
    $result = $db->query($sql);
    $incidencia = $db->fetch_assoc($result);
    
    if ($incidencia) {
        echo json_encode(['data' => $incidencia, 'success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Incidencia no encontrada']);
    }
}

function getStats() {
    global $db;
    
    $stats = [
        'total' => 0,
        'pendientes' => 0,
        'en_proceso' => 0,
        'resueltas' => 0
    ];
    
    $sql = "SELECT Estado, COUNT(*) as total FROM incidencias GROUP BY Estado";
    $result = $db->query($sql);
    
    while ($row = $db->fetch_assoc($result)) {
        $stats['total'] += $row['total'];
        switch ($row['Estado']) {
            case 'Pendiente':
                $stats['pendientes'] = $row['total'];
                break;
            case 'En Proceso':
                $stats['en_proceso'] = $row['total'];
                break;
            case 'Resuelto':
            case 'Cerrado':
                $stats['resueltas'] += $row['total'];
                break;
        }
    }
    
    echo json_encode(['data' => $stats, 'success' => true]);
}

function handleDatatable() {
    global $db;
    
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    $where = '';
    if (!empty($search)) {
        $search = $db->escape($search);
        $where = "WHERE i.Titulo LIKE '%{$search}%' OR i.NomUsu LIKE '%{$search}%' OR i.Estado LIKE '%{$search}%'";
    }
    
    $totalQuery = "SELECT COUNT(*) as total FROM incidencias";
    $totalResult = $db->query($totalQuery);
    $totalRow = $db->fetch_assoc($totalResult);
    $recordsTotal = $totalRow['total'];
    
    $filteredQuery = "SELECT COUNT(*) as total FROM incidencias i {$where}";
    $filteredResult = $db->query($filteredQuery);
    $filteredRow = $db->fetch_assoc($filteredResult);
    $recordsFiltered = $filteredRow['total'];
    
    $sql = "SELECT i.IdInci, i.Titulo, i.NomUsu, i.Fecha, i.Estado, i.DescripcionI,
                   t.DescripcionTP as TipoProblema, d.DescripcionD as Dificultad
            FROM incidencias i 
            LEFT JOIN tipoproblema t ON i.CodTipoPro = t.CodTipoPro 
            LEFT JOIN dificultad d ON i.CodDifi = d.CodDifi {$where} 
            ORDER BY i.IdInci DESC 
            LIMIT {$start}, {$length}";
    $result = $db->query($sql);
    $data = $db->while_loop($result);
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
}

function createIncidencia($data) {
    global $db;
    
    if (empty($data['Titulo']) || empty($data['NomUsu'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Título y usuario son requeridos']);
        return;
    }
    
    $titulo = $db->escape($data['Titulo']);
    $descripcion = $db->escape($data['DescripcionI'] ?? '');
    $nomUsu = $db->escape($data['NomUsu']);
    $fecha = $db->escape($data['Fecha'] ?? date('Y-m-d'));
    $estado = $db->escape($data['Estado'] ?? 'Pendiente');
    $codTipoPro = isset($data['CodTipoPro']) ? (int) $data['CodTipoPro'] : 1;
    $codDifi = isset($data['CodDifi']) ? (int) $data['CodDifi'] : 1;
    
    $sql = "INSERT INTO incidencias (Titulo, DescripcionI, NomUsu, Fecha, Estado, CodTipoPro, CodDifi) 
            VALUES ('{$titulo}', '{$descripcion}', '{$nomUsu}', '{$fecha}', '{$estado}', {$codTipoPro}, {$codDifi})";
    
    if ($db->query($sql)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Incidencia creada correctamente',
            'id' => $db->insert_id()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear la incidencia']);
    }
}

function updateIncidencia($data) {
    global $db;
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de incidencia requerido']);
        return;
    }
    
    $id = (int) $data['id'];
    $updates = [];
    
    if (!empty($data['Titulo'])) {
        $updates[] = "Titulo = '" . $db->escape($data['Titulo']) . "'";
    }
    if (!empty($data['DescripcionI'])) {
        $updates[] = "DescripcionI = '" . $db->escape($data['DescripcionI']) . "'";
    }
    if (!empty($data['NomUsu'])) {
        $updates[] = "NomUsu = '" . $db->escape($data['NomUsu']) . "'";
    }
    if (!empty($data['Estado'])) {
        $updates[] = "Estado = '" . $db->escape($data['Estado']) . "'";
    }
    if (isset($data['CodTipoPro'])) {
        $updates[] = "CodTipoPro = " . (int) $data['CodTipoPro'];
    }
    if (isset($data['CodDifi'])) {
        $updates[] = "CodDifi = " . (int) $data['CodDifi'];
    }
    if (!empty($data['Solucion'])) {
        $updates[] = "Solucion = '" . $db->escape($data['Solucion']) . "'";
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay datos para actualizar']);
        return;
    }
    
    $sql = "UPDATE incidencias SET " . implode(', ', $updates) . " WHERE IdInci = {$id}";
    
    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Incidencia actualizada correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar la incidencia']);
    }
}

function deleteIncidencia($id) {
    global $db;
    $id = (int) $id;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de incidencia inválido']);
        return;
    }
    
    $sql = "DELETE FROM incidencias WHERE IdInci = {$id} LIMIT 1";
    
    if ($db->query($sql) && $db->affected_rows() > 0) {
        echo json_encode(['success' => true, 'message' => 'Incidencia eliminada correctamente']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Incidencia no encontrada o no se pudo eliminar']);
    }
}
