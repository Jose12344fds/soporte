<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
require_once __DIR__ . '/conexion_hana.php';

function evidencia_out(array $data, int $status = 200): void {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function evidencia_ensure_table(): bool {
    global $db;
    $result = $db->query("SHOW TABLES LIKE 'mantenimientos_ot_evidencias'");
    if ($db->num_rows($result) > 0) {
        $columnResult = $db->query(
            "SHOW COLUMNS FROM mantenimientos_ot_evidencias WHERE Field = 'id'"
        );
        $column = $db->fetch_assoc($columnResult);
        if (!$column) return false;

        if (stripos((string)($column['Extra'] ?? ''), 'auto_increment') === false) {
            $db->query(
                'ALTER TABLE mantenimientos_ot_evidencias'
                . ' MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        }
        return true;
    }

    $sql = file_get_contents(dirname(dirname(__DIR__)) . '/sql/06_mantenimientos_ot_evidencias.sql');
    if (!is_string($sql) || trim($sql) === '') return false;
    $db->query($sql);
    $result = $db->query("SHOW TABLES LIKE 'mantenimientos_ot_evidencias'");
    return $db->num_rows($result) > 0;
}

function evidencia_ot_date(int $docentry): ?string {
    $pdo = getHanaPdo();
    $stmt = $pdo->prepare(
        'SELECT "U_FechaInicio" FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" WHERE "DocEntry" = ?'
    );
    $stmt->execute([$docentry]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return substr(trim((string)($row['U_FechaInicio'] ?? array_values($row)[0] ?? '')), 0, 10);
}

function evidencia_storage_root(): string {
    return dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'mantenimientos' . DIRECTORY_SEPARATOR . 'ot_evidencias';
}

function evidencia_can_write(): bool {
    return tiene_permiso('mantenimientos', 'crear') || tiene_permiso('mantenimientos', 'editar');
}

if (!$session->isUserLoggedIn(false)) {
    evidencia_out(['error' => 'No autorizado'], 401);
}
if (!evidencia_ensure_table()) {
    evidencia_out(['error' => 'No se pudo preparar la tabla mantenimientos_ot_evidencias.'], 503);
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    global $db;

    if ($method === 'GET') {
        if (!tiene_permiso('mantenimientos', 'ver')) {
            evidencia_out(['error' => 'Sin permiso para ver evidencias.'], 403);
        }
        $docentry = (int)($_GET['docentry'] ?? 0);
        if ($docentry <= 0) evidencia_out(['error' => 'docentry es obligatorio.'], 400);

        $result = $db->query(
            'SELECT e.id, e.ot_docentry, e.nombre_original, e.mime_type, e.tamano_bytes,'
            . ' e.descripcion, e.user_id, e.created_at, COALESCE(u.name, u.username, \'\') AS usuario'
            . ' FROM mantenimientos_ot_evidencias e'
            . ' LEFT JOIN users u ON u.id = e.user_id'
            . ' WHERE e.ot_docentry = ' . $docentry
            . ' ORDER BY e.created_at DESC, e.id DESC'
        );
        $rows = [];
        while ($row = $db->fetch_assoc($result)) {
            $row['id'] = (int)$row['id'];
            $row['ot_docentry'] = (int)$row['ot_docentry'];
            $row['tamano_bytes'] = (int)$row['tamano_bytes'];
            $row['url'] = rtrim(BASE_URL, '/') . '/api/mantenimientos/ver_evidencia_ot.php?id=' . $row['id'];
            $rows[] = $row;
        }
        evidencia_out(['success' => true, 'evidencias' => $rows]);
    }

    if ($method === 'DELETE') {
        if (!tiene_permiso('mantenimientos', 'editar')) {
            evidencia_out(['error' => 'Sin permiso para eliminar evidencias.'], 403);
        }
        $input = json_decode((string)file_get_contents('php://input'), true) ?: [];
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) evidencia_out(['error' => 'id es obligatorio.'], 400);

        $result = $db->query(
            'SELECT id, ruta_relativa FROM mantenimientos_ot_evidencias WHERE id = ' . $id . ' LIMIT 1'
        );
        $row = $db->fetch_assoc($result);
        if (!$row) evidencia_out(['error' => 'Evidencia no encontrada.'], 404);

        $root = evidencia_storage_root();
        $path = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $row['ruta_relativa']);
        $rootReal = realpath($root);
        $pathReal = realpath($path);
        if ($pathReal && $rootReal && str_starts_with($pathReal, $rootReal . DIRECTORY_SEPARATOR)) {
            @unlink($pathReal);
        }
        $db->query('DELETE FROM mantenimientos_ot_evidencias WHERE id = ' . $id . ' LIMIT 1');
        evidencia_out(['success' => true]);
    }

    if ($method !== 'POST') evidencia_out(['error' => 'Metodo no permitido.'], 405);
    if (!evidencia_can_write()) evidencia_out(['error' => 'Sin permiso para cargar evidencias.'], 403);

    $docentry = (int)($_POST['docentry'] ?? 0);
    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    if ($docentry <= 0) evidencia_out(['error' => 'docentry es obligatorio.'], 400);
    $otDate = evidencia_ot_date($docentry);
    if ($otDate === null) evidencia_out(['error' => 'La OT indicada no existe.'], 404);
    if (mb_strlen($descripcion) > 250) evidencia_out(['error' => 'La descripcion no puede superar 250 caracteres.'], 400);
    if (!isset($_FILES['evidencias'])) evidencia_out(['error' => 'Seleccione al menos una foto o un PDF.'], 400);

    $files = $_FILES['evidencias'];
    $names = is_array($files['name']) ? $files['name'] : [$files['name']];
    $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
    $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
    if (count($names) > 12) evidencia_out(['error' => 'Solo se permiten 10 fotos, 1 PDF y 1 Word por carga.'], 400);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
    $totalSize = array_sum(array_map('intval', $sizes));
    if ($totalSize > 100 * 1024 * 1024) evidencia_out(['error' => 'La carga total no puede superar 100 MB.'], 400);

    $validated = [];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $imageCount = 0;
    $pdfCount = 0;
    $wordCount = 0;
    foreach ($names as $i => $original) {
        if ((int)$errors[$i] !== UPLOAD_ERR_OK) {
            evidencia_out(['error' => 'Un archivo no pudo cargarse. Codigo: ' . (int)$errors[$i]], 400);
        }
        $size = (int)$sizes[$i];
        $mime = (string)$finfo->file($tmpNames[$i]);
        if (!isset($allowed[$mime])) {
            evidencia_out(['error' => 'Formato no permitido: ' . basename((string)$original)], 400);
        }
        $isPdf = $mime === 'application/pdf';
        $isWord = in_array($mime, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ], true);
        if ($size <= 0 || $size > (($isPdf || $isWord) ? 25 : 15) * 1024 * 1024) {
            evidencia_out(['error' => ($isPdf || $isWord)
                ? 'Cada documento debe pesar como maximo 25 MB.'
                : 'Cada foto debe pesar como maximo 15 MB.'], 400);
        }
        if ($isPdf) $pdfCount++;
        elseif ($isWord) $wordCount++;
        else $imageCount++;
        $validated[] = [
            'original' => mb_substr(basename((string)$original), 0, 255),
            'tmp' => $tmpNames[$i],
            'size' => $size,
            'mime' => $mime,
            'extension' => $allowed[$mime],
        ];
    }
    if ($imageCount > 10 || $pdfCount > 1 || $wordCount > 1) {
        evidencia_out(['error' => 'Por OT se permiten hasta 10 fotos, 1 PDF y 1 documento Word.'], 400);
    }
    if ($pdfCount === 1) {
        $existingPdf = $db->query(
            "SELECT COUNT(*) AS total FROM mantenimientos_ot_evidencias"
            . " WHERE ot_docentry = {$docentry} AND mime_type = 'application/pdf'"
        );
        $existingPdfRow = $db->fetch_assoc($existingPdf);
        if ((int)($existingPdfRow['total'] ?? 0) > 0) {
            evidencia_out(['error' => 'Esta OT ya tiene un PDF. Elimine el documento actual para reemplazarlo.'], 409);
        }
    }
    if ($wordCount === 1) {
        $existingWord = $db->query(
            "SELECT COUNT(*) AS total FROM mantenimientos_ot_evidencias"
            . " WHERE ot_docentry = {$docentry} AND mime_type IN"
            . " ('application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document')"
        );
        $existingWordRow = $db->fetch_assoc($existingWord);
        if ((int)($existingWordRow['total'] ?? 0) > 0) {
            evidencia_out(['error' => 'Esta OT ya tiene un documento Word. Elimine el actual para reemplazarlo.'], 409);
        }
    }

    $storageDate = DateTime::createFromFormat('Y-m-d', $otDate) ?: new DateTime();
    $relativeDir = $storageDate->format('Y') . '/'
        . $storageDate->format('m') . '/ot_' . $docentry;
    $targetDir = evidencia_storage_root() . DIRECTORY_SEPARATOR . $relativeDir;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
        throw new RuntimeException('No se pudo crear la carpeta de evidencias.');
    }

    $user = current_user();
    $userId = (int)($user['id'] ?? 0);
    mysqli_report(MYSQLI_REPORT_OFF);
    $insertDb = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($insertDb->connect_errno) {
        throw new RuntimeException('No se pudo conectar con la base de datos de evidencias.');
    }
    $insertDb->set_charset('utf8mb4');
    $insertStmt = $insertDb->prepare(
        'INSERT INTO mantenimientos_ot_evidencias'
        . ' (ot_docentry,nombre_original,nombre_archivo,ruta_relativa,mime_type,tamano_bytes,descripcion,user_id)'
        . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$insertStmt) {
        error_log('No se pudo preparar la evidencia de mantenimiento: ' . $insertDb->error);
        throw new RuntimeException('No se pudo preparar el registro de la evidencia.');
    }

    $saved = [];
    foreach ($validated as $file) {
        $storedName = bin2hex(random_bytes(16)) . '.' . $file['extension'];
        $target = $targetDir . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($file['tmp'], $target)) {
            throw new RuntimeException('No se pudo guardar el archivo ' . $file['original'] . '.');
        }
        $relativePath = $relativeDir . '/' . $storedName;
        $originalName = $file['original'];
        $mimeType = $file['mime'];
        $fileSize = (int)$file['size'];
        $descriptionValue = $descripcion !== '' ? $descripcion : null;
        $userIdValue = $userId > 0 ? $userId : null;
        $insertStmt->bind_param(
            'issssisi',
            $docentry,
            $originalName,
            $storedName,
            $relativePath,
            $mimeType,
            $fileSize,
            $descriptionValue,
            $userIdValue
        );
        if (!$insertStmt->execute()) {
            @unlink($target);
            error_log('No se pudo registrar la evidencia ' . $file['original'] . ': ' . $insertStmt->error);
            throw new RuntimeException('No se pudo registrar la evidencia en la base de datos.');
        }
        $saved[] = [
            'id' => (int)$insertDb->insert_id,
            'nombre_original' => $file['original'],
        ];
    }
    $insertStmt->close();
    $insertDb->close();
    evidencia_out(['success' => true, 'guardadas' => $saved, 'cantidad' => count($saved)], 201);
} catch (Throwable $e) {
    error_log('Evidencias OT: ' . $e->getMessage());
    evidencia_out(['error' => 'No se pudieron procesar las evidencias.'], 500);
}
