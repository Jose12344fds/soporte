<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
require_once __DIR__ . '/conexion_hana.php';

function equipo_manual_out(array $data, int $status = 200): void {
    while (ob_get_level()) ob_end_clean();
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function equipo_manual_table(): void {
    global $db;
    $db->query("CREATE TABLE IF NOT EXISTS mantenimientos_equipo_manuales (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        sap_equipo_code VARCHAR(50) NOT NULL,
        item_code VARCHAR(100) NULL,
        nombre_original VARCHAR(255) NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_relativa VARCHAR(500) NOT NULL,
        mime_type VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
        tamano_bytes BIGINT UNSIGNED NOT NULL,
        user_id INT(11) UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_equipo_manual_code (sap_equipo_code),
        UNIQUE KEY uq_equipo_manual_archivo (nombre_archivo),
        KEY idx_equipo_manual_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function equipo_manual_root(): string {
    return dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'mantenimientos' . DIRECTORY_SEPARATOR . 'manuales_equipos';
}

function equipo_manual_code($value): string {
    $code = trim((string)$value);
    if ($code === '' || mb_strlen($code) > 50 || !preg_match('/^[A-Za-z0-9._-]+$/', $code)) {
        equipo_manual_out(['error' => 'Código SAP del equipo inválido.'], 400);
    }
    return $code;
}

function equipo_manual_unlink(string $relativePath): void {
    $root = equipo_manual_root();
    $path = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    $rootReal = realpath($root); $pathReal = realpath($path);
    if ($rootReal && $pathReal && str_starts_with($pathReal, $rootReal . DIRECTORY_SEPARATOR)) @unlink($pathReal);
}

if (!$session->isUserLoggedIn(false)) equipo_manual_out(['error' => 'No autorizado.'], 401);
equipo_manual_table();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
global $db;

try {
    if ($method === 'GET') {
        if (!tiene_permiso('mantenimientos', 'ver')) equipo_manual_out(['error' => 'Sin permiso.'], 403);
        $code = equipo_manual_code($_GET['code'] ?? '');
        $result = $db->query("SELECT id,sap_equipo_code,item_code,nombre_original,tamano_bytes,created_at,updated_at
            FROM mantenimientos_equipo_manuales WHERE sap_equipo_code='".$db->escape($code)."' LIMIT 1");
        $row = $db->fetch_assoc($result);
        if ($row) {
            $row['id'] = (int)$row['id']; $row['tamano_bytes'] = (int)$row['tamano_bytes'];
            $row['url'] = rtrim(BASE_URL, '/') . '/api/mantenimientos/ver_manual_equipo.php?id=' . $row['id'];
        }
        equipo_manual_out(['success'=>true, 'manual'=>$row ?: null]);
    }

    if ($method === 'DELETE') {
        if (!tiene_permiso('mantenimientos', 'editar')) equipo_manual_out(['error'=>'Sin permiso.'], 403);
        $input = json_decode((string)file_get_contents('php://input'), true) ?: [];
        $code = equipo_manual_code($input['code'] ?? '');
        $result = $db->query("SELECT id,ruta_relativa FROM mantenimientos_equipo_manuales WHERE sap_equipo_code='".$db->escape($code)."' LIMIT 1");
        $row = $db->fetch_assoc($result);
        if (!$row) equipo_manual_out(['error'=>'El equipo no tiene un manual cargado.'], 404);
        $db->query('DELETE FROM mantenimientos_equipo_manuales WHERE id='.(int)$row['id'].' LIMIT 1');
        equipo_manual_unlink((string)$row['ruta_relativa']);
        equipo_manual_out(['success'=>true]);
    }

    if ($method !== 'POST') equipo_manual_out(['error'=>'Método no permitido.'], 405);
    if (!tiene_permiso('mantenimientos', 'crear') && !tiene_permiso('mantenimientos', 'editar')) equipo_manual_out(['error'=>'Sin permiso para cargar manuales.'], 403);
    $code = equipo_manual_code($_POST['code'] ?? '');
    $itemCode = mb_substr(trim((string)($_POST['itemcode'] ?? '')), 0, 100);
    if (!isset($_FILES['manual'])) equipo_manual_out(['error'=>'Seleccione un archivo PDF.'], 400);
    $file = $_FILES['manual'];
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) equipo_manual_out(['error'=>'El PDF no pudo cargarse. Código: '.(int)$file['error']], 400);

    $pdo = getHanaPdo();
    $stmt = $pdo->prepare('SELECT "Code" FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" WHERE "Code" = ?');
    $stmt->execute([$code]);
    if (!$stmt->fetch()) equipo_manual_out(['error'=>'El equipo indicado no existe en SAP.'], 404);
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 25 * 1024 * 1024) equipo_manual_out(['error'=>'El manual debe pesar como máximo 25 MB.'], 400);
    $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
    if ($mime !== 'application/pdf') equipo_manual_out(['error'=>'El archivo seleccionado no es un PDF válido.'], 400);
    $signature = file_get_contents((string)$file['tmp_name'], false, null, 0, 5);
    if ($signature !== '%PDF-') equipo_manual_out(['error'=>'El contenido del archivo no corresponde a un PDF.'], 400);

    $relativeDir = date('Y/m').'/equipo_'.$code;
    $targetDir = equipo_manual_root().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) throw new RuntimeException('No se pudo crear la carpeta de manuales.');
    $storedName = bin2hex(random_bytes(16)).'.pdf';
    $target = $targetDir.DIRECTORY_SEPARATOR.$storedName;
    if (!move_uploaded_file((string)$file['tmp_name'], $target)) throw new RuntimeException('No se pudo guardar el manual en el servidor.');
    $relativePath = $relativeDir.'/'.$storedName;
    $oldResult = $db->query("SELECT ruta_relativa FROM mantenimientos_equipo_manuales WHERE sap_equipo_code='".$db->escape($code)."' LIMIT 1");
    $old = $db->fetch_assoc($oldResult); $user = current_user(); $userId = (int)($user['id'] ?? 0);
    $original = mb_substr(basename((string)$file['name']), 0, 255);
    $sql = "INSERT INTO mantenimientos_equipo_manuales (sap_equipo_code,item_code,nombre_original,nombre_archivo,ruta_relativa,mime_type,tamano_bytes,user_id) VALUES ("
        ."'".$db->escape($code)."',".($itemCode===''?'NULL':"'".$db->escape($itemCode)."'").",'".$db->escape($original)."','".$db->escape($storedName)."','".$db->escape($relativePath)."','application/pdf',{$size},".($userId>0?$userId:'NULL').")"
        ." ON DUPLICATE KEY UPDATE item_code=VALUES(item_code),nombre_original=VALUES(nombre_original),nombre_archivo=VALUES(nombre_archivo),ruta_relativa=VALUES(ruta_relativa),tamano_bytes=VALUES(tamano_bytes),user_id=VALUES(user_id),updated_at=CURRENT_TIMESTAMP";
    try { $db->query($sql); } catch (Throwable $e) { @unlink($target); throw $e; }
    if ($old && !empty($old['ruta_relativa']) && $old['ruta_relativa'] !== $relativePath) equipo_manual_unlink((string)$old['ruta_relativa']);
    equipo_manual_out(['success'=>true,'message'=>'Manual técnico guardado correctamente.'], 201);
} catch (Throwable $e) {
    equipo_manual_out(['error'=>'No se pudo procesar el manual técnico del equipo.'], 500);
}
