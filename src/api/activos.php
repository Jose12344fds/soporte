<?php
/**
 * API Router - Activos Endpoint
 * Maneja operaciones CRUD para activos/maquinas
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

require_once __DIR__ . '/../../includes/load.php';
ensure_maquina_ubicacion_schema();

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
            } elseif (isset($_GET['id'])) {
                getActivo($_GET['id']);
            } else {
                getAllActivos();
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            createActivo($data);
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            updateActivo($data);
            break;
            
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            deleteActivo($data['id']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getAllActivos() {
    global $db;
    $sql = "SELECT m.*, u.name as Usuario 
            FROM maquina m 
            LEFT JOIN asig_maquina am ON m.IdMaquina = am.IdMaquina 
            LEFT JOIN users u ON am.id = u.id 
            ORDER BY m.IdMaquina DESC";
    $result = $db->query($sql);
    $activos = $db->while_loop($result);
    echo json_encode(['data' => $activos, 'success' => true]);
}

function getActivo($id) {
    global $db;
    $id = (int) $id;
    $sql = "SELECT m.*, u.name as Usuario, am.id as user_id, am.Fecha, am.Estado as AsigEstado
            FROM maquina m 
            LEFT JOIN asig_maquina am ON m.IdMaquina = am.IdMaquina 
            LEFT JOIN users u ON am.id = u.id 
            WHERE m.IdMaquina = {$id} LIMIT 1";
    $result = $db->query($sql);
    $activo = $db->fetch_assoc($result);
    
    if ($activo) {
        echo json_encode(['data' => $activo, 'success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Activo no encontrado']);
    }
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
        $where = "WHERE m.Host LIKE '%{$search}%' OR m.area LIKE '%{$search}%' OR u.name LIKE '%{$search}%'";
    }
    
    $totalQuery = "SELECT COUNT(*) as total FROM maquina";
    $totalResult = $db->query($totalQuery);
    $totalRow = $db->fetch_assoc($totalResult);
    $recordsTotal = $totalRow['total'];
    
    $filteredQuery = "SELECT COUNT(*) as total FROM maquina m 
                      LEFT JOIN asig_maquina am ON m.IdMaquina = am.IdMaquina 
                      LEFT JOIN users u ON am.id = u.id {$where}";
    $filteredResult = $db->query($filteredQuery);
    $filteredRow = $db->fetch_assoc($filteredResult);
    $recordsFiltered = $filteredRow['total'];
    
    $sql = "SELECT m.IdMaquina, m.Host, m.area, m.Fecha_Maqui, m.EstadoMaquina, m.Ip, 
                   m.Procesador, m.Mem_Ram, m.Tipo_Disco, m.Tamano_Disco, m.Comentarios,
                   u.name as Usuario
            FROM maquina m 
            LEFT JOIN asig_maquina am ON m.IdMaquina = am.IdMaquina 
            LEFT JOIN users u ON am.id = u.id {$where} 
            ORDER BY m.IdMaquina DESC 
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

function createActivo($data) {
    global $db;
    
    if (empty($data['Host'])) {
        http_response_code(400);
        echo json_encode(['error' => 'El nombre del equipo es requerido']);
        return;
    }
    
    $host = $db->escape($data['Host']);
    $idTipoActivo = (int)($data['IdTipoActivo'] ?? 0);
    $tipoCatalogo = $idTipoActivo > 0 ? find_by_sql("SELECT IdTipoActivo,Codigo,CategoriaPredeterminada FROM tipos_activo WHERE IdTipoActivo={$idTipoActivo} AND Activo=1 LIMIT 1") : [];
    $tipoCatalogo = $tipoCatalogo[0] ?? null;
    $tipoActivo = strtoupper(trim((string)($tipoCatalogo['Codigo'] ?? $data['Tipo_Activo'] ?? 'OTRO')));
    $categoria = strtoupper(trim((string)($data['CategoriaEquipo'] ?? '')));
    if ($categoria === '' && $tipoCatalogo) $categoria = strtoupper((string)$tipoCatalogo['CategoriaPredeterminada']);
    if (in_array($tipoActivo, ['PC', 'LAPTOP'], true)) $categoria = 'COMPUTADORAS';
    if (!in_array($categoria, ['PERIFERICOS', 'INFRAESTRUCTURA', 'COMPUTADORAS'], true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Categoría de equipo no válida']);
        return;
    }
    $tipoActivo = $db->escape($tipoActivo);
    $categoria = $db->escape($categoria);
    $area = $db->escape($data['area'] ?? '');
    $fecha = $db->escape($data['Fecha_Maqui'] ?? date('Y-m-d'));
    $estado = $db->escape($data['EstadoMaquina'] ?? 'ACTIVO');
    $ip = $db->escape($data['Ip'] ?? '');
    $procesador = $db->escape($data['Procesador'] ?? '');
    $memRam = $db->escape($data['Mem_Ram'] ?? '');
    $tipoDisco = $db->escape($data['Tipo_Disco'] ?? '');
    $tamanoDisco = $db->escape($data['Tamano_Disco'] ?? '');
    $comentarios = $db->escape($data['Comentarios'] ?? '');
    
    $idTipoSql = $idTipoActivo > 0 ? (string)$idTipoActivo : 'NULL';
    $sql = "INSERT INTO maquina (Host, Tipo_Activo, IdTipoActivo, CategoriaEquipo, area, Fecha_Maqui, EstadoMaquina, Ip, Procesador, Mem_Ram, Tipo_Disco, Tamano_Disco, Comentarios) 
            VALUES ('{$host}', '{$tipoActivo}', {$idTipoSql}, '{$categoria}', '{$area}', '{$fecha}', '{$estado}', '{$ip}', '{$procesador}', '{$memRam}', '{$tipoDisco}', '{$tamanoDisco}', '{$comentarios}')";
    
    if ($db->query($sql)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Activo creado correctamente',
            'id' => $db->insert_id()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el activo']);
    }
}

function updateActivo($data) {
    global $db;
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de activo requerido']);
        return;
    }
    
    $id = (int) $data['id'];
    $updates = [];
    
    $fields = ['Host', 'Tipo_Activo', 'IdTipoActivo', 'CategoriaEquipo', 'area', 'Fecha_Maqui', 'EstadoMaquina', 'Ip', 'Procesador', 'Mem_Ram', 'Tipo_Disco', 'Tamano_Disco', 'Comentarios'];
    
    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $updates[] = "{$field} = '" . $db->escape($data[$field]) . "'";
        }
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay datos para actualizar']);
        return;
    }
    
    $sql = "UPDATE maquina SET " . implode(', ', $updates) . " WHERE IdMaquina = {$id}";
    
    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Activo actualizado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el activo']);
    }
}

function deleteActivo($id) {
    global $db;
    $id = (int) $id;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de activo inválido']);
        return;
    }
    
    // Primero eliminar asignaciones
    $db->query("DELETE FROM asig_maquina WHERE IdMaquina = {$id}");
    
    $sql = "DELETE FROM maquina WHERE IdMaquina = {$id} LIMIT 1";
    
    if ($db->query($sql) && $db->affected_rows() > 0) {
        echo json_encode(['success' => true, 'message' => 'Activo eliminado correctamente']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Activo no encontrado o no se pudo eliminar']);
    }
}
