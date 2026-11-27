<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
require_once __DIR__ . '/conexion_hana.php';

const COSTO_SAP_TABLE = '"SBO_TAMAING_PRODUC"."@TAMA_OTCOSTDET"';

function costo_out(array $data, int $status = 200): void {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function costo_mysql(): mysqli {
    static $connection = null;
    if ($connection instanceof mysqli) return $connection;
    mysqli_report(MYSQLI_REPORT_OFF);
    $connection = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($connection->connect_errno) throw new RuntimeException('No se pudo conectar con MySQL.');
    $connection->set_charset('utf8mb4');
    return $connection;
}

function costo_ensure_documents_table(mysqli $mysql): void {
    $result = $mysql->query("SHOW TABLES LIKE 'mantenimientos_ot_costos_documentos'");
    if ($result && $result->num_rows > 0) {
        $columnResult = $mysql->query(
            "SHOW COLUMNS FROM mantenimientos_ot_costos_documentos WHERE Field='id'"
        );
        $column = $columnResult ? $columnResult->fetch_assoc() : null;
        if (!$column) throw new RuntimeException('La tabla de documentos no tiene identificador.');
        if (stripos((string)($column['Extra'] ?? ''), 'auto_increment') === false) {
            if (!$mysql->query(
                'ALTER TABLE mantenimientos_ot_costos_documentos'
                . ' MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            )) throw new RuntimeException('No se pudo reparar el identificador de documentos.');
        }
        return;
    }
    $sql = file_get_contents(dirname(dirname(__DIR__)) . '/sql/10_mantenimientos_ot_costos_documentos.sql');
    if (!is_string($sql) || trim($sql) === '' || !$mysql->query($sql)) {
        throw new RuntimeException('No se pudo crear la tabla de documentos.');
    }
}

function costo_storage_root(): string {
    return dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'mantenimientos' . DIRECTORY_SEPARATOR . 'ot_costos';
}

function costo_unlink(string $relativePath): void {
    if ($relativePath === '') return;
    $root = costo_storage_root();
    $rootReal = realpath($root);
    $path = $root . DIRECTORY_SEPARATOR
        . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    $pathReal = realpath($path);
    if ($rootReal && $pathReal && str_starts_with($pathReal, $rootReal . DIRECTORY_SEPARATOR)) {
        @unlink($pathReal);
    }
}

function costo_ot_date(PDO $pdo, int $docentry): ?string {
    $stmt = $pdo->prepare(
        'SELECT "U_FechaInicio" FROM "SBO_TAMAING_PRODUC"."@TAMA_ORDTRABAJO" WHERE "DocEntry" = ?'
    );
    $stmt->execute([$docentry]);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if (!$row) return null;
    $date = substr(trim((string)($row[0] ?? '')), 0, 10);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
}

function costo_decimal($value, int $scale, string $field): float {
    $raw = str_replace(',', '.', trim((string)$value));
    $pattern = $scale === 3 ? '/^\d{1,11}(?:\.\d{1,3})?$/' : '/^\d{1,13}(?:\.\d{1,2})?$/';
    if (!preg_match($pattern, $raw)) costo_out(['error' => $field . ' no tiene un formato valido.'], 400);
    return (float)$raw;
}

function costo_new_code(PDO $pdo): string {
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = 'OTC' . date('ymdHis') . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('SELECT COUNT(*) AS "CNT" FROM ' . COSTO_SAP_TABLE . ' WHERE "Code" = ?');
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ((int)($row['CNT'] ?? array_values($row)[0] ?? 0) === 0) return $code;
    }
    throw new RuntimeException('No se pudo generar el codigo SAP del detalle.');
}

function costo_read_description(PDO $pdo, string $code, int $length): string {
    if ($length <= 0) return '';
    $safeCode = str_replace("'", "''", $code);
    $chunkSize = 25;
    $partCount = (int)ceil(min($length, 30000) / $chunkSize);
    $value = '';
    for ($offset = 0; $offset < $partCount; $offset += 100) {
        $selects = [];
        $end = min($offset + 100, $partCount);
        for ($i = $offset; $i < $end; $i++) {
            $start = ($i * $chunkSize) + 1;
            $selects[] = 'SELECT ' . $i . ' AS "SEQ",'
                . ' CAST(BINTOHEX(CAST(SUBSTRING("U_Descripcion",' . $start . ',' . $chunkSize
                . ') AS VARBINARY(200))) AS VARCHAR(250)) AS "HEX"'
                . ' FROM ' . COSTO_SAP_TABLE . ' WHERE "Code" = \'' . $safeCode . '\'';
        }
        $stmt = $pdo->query(implode(' UNION ALL ', $selects) . ' ORDER BY "SEQ"');
        while ($part = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hex = preg_replace('/[^0-9A-Fa-f]/', '', (string)($part['HEX'] ?? ''));
            if ($hex === '' || strlen($hex) % 2 !== 0) continue;
            $decoded = hex2bin($hex);
            if ($decoded !== false) $value .= $decoded;
        }
    }
    return $value;
}

function costo_document_by_code(mysqli $mysql, string $code): ?array {
    $stmt = $mysql->prepare(
        'SELECT d.*,COALESCE(u.name,u.username,\'\') AS usuario'
        . ' FROM mantenimientos_ot_costos_documentos d'
        . ' LEFT JOIN users u ON u.id=d.user_id WHERE d.sap_cost_code=? LIMIT 1'
    );
    if (!$stmt) throw new RuntimeException('No se pudo consultar el documento.');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? ($result->fetch_assoc() ?: null) : null;
}

function costo_upload_pdf(array $file, int $docentry, string $otDate): array {
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        costo_out(['error' => 'El PDF no pudo cargarse. Codigo: ' . (int)($file['error'] ?? -1)], 400);
    }
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 25 * 1024 * 1024) {
        costo_out(['error' => 'El PDF debe pesar como maximo 25 MB.'], 400);
    }
    $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
    if ($mime !== 'application/pdf') costo_out(['error' => 'Solo se permite un archivo PDF.'], 400);

    $date = DateTime::createFromFormat('Y-m-d', $otDate) ?: new DateTime();
    $relativeDir = $date->format('Y/m') . '/ot_' . $docentry;
    $targetDir = costo_storage_root() . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
        throw new RuntimeException('No se pudo crear la carpeta de documentos.');
    }
    $storedName = bin2hex(random_bytes(16)) . '.pdf';
    $target = $targetDir . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        throw new RuntimeException('No se pudo guardar el PDF en el servidor.');
    }
    return [
        'original' => mb_substr(basename((string)$file['name']), 0, 255),
        'stored' => $storedName,
        'relative' => $relativeDir . '/' . $storedName,
        'mime' => $mime,
        'size' => $size,
        'target' => $target,
    ];
}

function costo_insert_document(
    mysqli $mysql,
    string $sapCode,
    int $docentry,
    array $pdf,
    ?int $userId,
    ?int $legacyId = null
): int {
    $stmt = $mysql->prepare(
        'INSERT INTO mantenimientos_ot_costos_documentos'
        . ' (sap_cost_code,ot_docentry,nombre_original,nombre_archivo,ruta_relativa,mime_type,'
        . 'tamano_bytes,user_id,legacy_cost_id) VALUES (?,?,?,?,?,?,?,?,?)'
    );
    if (!$stmt) throw new RuntimeException('No se pudo preparar el documento.');
    $stmt->bind_param(
        'sissssiii',
        $sapCode, $docentry, $pdf['original'], $pdf['stored'], $pdf['relative'], $pdf['mime'],
        $pdf['size'], $userId, $legacyId
    );
    if (!$stmt->execute()) throw new RuntimeException('No se pudo registrar el PDF en MySQL.');
    return (int)$mysql->insert_id;
}

function costo_insert_sap(PDO $pdo, array $data): void {
    $stmt = $pdo->prepare(
        'INSERT INTO ' . COSTO_SAP_TABLE
        . ' ("Code","Name","U_OTDocEntry","U_Tipo","U_Descripcion","U_ItemCode","U_Cantidad",'
        . '"U_PrecioUnit","U_Moneda","U_Total","U_DocCompra","U_Proveedor","U_IdArchivo",'
        . '"U_Activo","U_Usuario","U_FechaReg") VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $data['code'], $data['name'], $data['ot_docentry'], $data['tipo'], $data['descripcion'],
        $data['item_code'], $data['cantidad'], $data['precio'], $data['moneda'], $data['total'],
        $data['doc_compra'], $data['proveedor'], $data['document_id'], 'Y', $data['usuario'], $data['fecha'],
    ]);
}

function costo_migrate_legacy(mysqli $mysql, PDO $pdo): void {
    $exists = $mysql->query("SHOW TABLES LIKE 'mantenimientos_ot_costos'");
    if (!$exists || $exists->num_rows === 0) return;
    $result = $mysql->query(
        'SELECT l.*,COALESCE(u.name,u.username,\'Migracion\') AS usuario'
        . ' FROM mantenimientos_ot_costos l LEFT JOIN users u ON u.id=l.user_id'
        . ' LEFT JOIN mantenimientos_ot_costos_documentos d ON d.legacy_cost_id=l.id'
        . ' WHERE d.id IS NULL ORDER BY l.id'
    );
    if (!$result) throw new RuntimeException('No se pudo revisar la migracion anterior.');

    while ($legacy = $result->fetch_assoc()) {
        $docentry = (int)$legacy['ot_docentry'];
        if (costo_ot_date($pdo, $docentry) === null) continue;
        $code = costo_new_code($pdo);
        $pdf = [
            'original' => (string)$legacy['nombre_original'],
            'stored' => (string)$legacy['nombre_archivo'],
            'relative' => (string)$legacy['ruta_relativa'],
            'mime' => (string)$legacy['mime_type'],
            'size' => (int)$legacy['tamano_bytes'],
        ];
        $mysql->begin_transaction();
        $pdo->beginTransaction();
        try {
            $documentId = costo_insert_document(
                $mysql, $code, $docentry, $pdf,
                (int)$legacy['user_id'] > 0 ? (int)$legacy['user_id'] : null,
                (int)$legacy['id']
            );
            costo_insert_sap($pdo, [
                'code' => $code,
                'name' => substr('OT' . $docentry . '-' . $code, 0, 100),
                'ot_docentry' => $docentry,
                'tipo' => (string)$legacy['tipo'],
                'descripcion' => (string)$legacy['descripcion'],
                'item_code' => null,
                'cantidad' => (float)$legacy['cantidad'],
                'precio' => (float)$legacy['precio_unitario'],
                'moneda' => (string)$legacy['moneda'],
                'total' => round((float)$legacy['cantidad'] * (float)$legacy['precio_unitario'], 2),
                'doc_compra' => $legacy['codigo_compra_sap'] ?: null,
                'proveedor' => $legacy['proveedor'] ?: null,
                'document_id' => $documentId,
                'usuario' => mb_substr((string)$legacy['usuario'], 0, 50),
                'fecha' => substr((string)$legacy['created_at'], 0, 10),
            ]);
            $pdo->commit();
            $mysql->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $mysql->rollback();
            throw $e;
        }
    }
}

function costo_fetch_rows(mysqli $mysql, PDO $pdo, int $docentry): array {
    $stmt = $pdo->prepare(
        'SELECT "Code","U_OTDocEntry","U_Tipo","U_ItemCode","U_Cantidad","U_PrecioUnit",'
        . '"U_Moneda","U_Total","U_DocCompra","U_Proveedor","U_IdArchivo","U_Activo",'
        . '"U_Usuario","U_FechaReg",LENGTH("U_Descripcion") AS "DESC_LEN"'
        . ' FROM ' . COSTO_SAP_TABLE
        . ' WHERE "U_OTDocEntry" = ? AND COALESCE("U_Activo",\'Y\') = \'Y\' ORDER BY "Code" DESC'
    );
    $stmt->execute([$docentry]);
    $rows = [];
    while ($sap = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $code = trim((string)($sap['Code'] ?? ''));
        if ($code === '') continue;
        $document = costo_document_by_code($mysql, $code);
        $quantity = (float)($sap['U_Cantidad'] ?? 0);
        $price = (float)($sap['U_PrecioUnit'] ?? 0);
        $documentId = (int)($document['id'] ?? $sap['U_IdArchivo'] ?? 0);
        $rows[] = [
            'code' => $code,
            'ot_docentry' => (int)($sap['U_OTDocEntry'] ?? $docentry),
            'tipo' => trim((string)($sap['U_Tipo'] ?? '')),
            'descripcion' => costo_read_description($pdo, $code, (int)($sap['DESC_LEN'] ?? 0)),
            'item_code' => trim((string)($sap['U_ItemCode'] ?? '')),
            'cantidad' => $quantity,
            'precio_unitario' => $price,
            'moneda' => trim((string)($sap['U_Moneda'] ?? 'PEN')),
            'total' => (float)($sap['U_Total'] ?? round($quantity * $price, 2)),
            'codigo_compra_sap' => trim((string)($sap['U_DocCompra'] ?? '')),
            'proveedor' => trim((string)($sap['U_Proveedor'] ?? '')),
            'document_id' => $documentId,
            'nombre_original' => (string)($document['nombre_original'] ?? ''),
            'mime_type' => (string)($document['mime_type'] ?? ''),
            'tamano_bytes' => (int)($document['tamano_bytes'] ?? 0),
            'usuario' => (string)($document['usuario'] ?? $sap['U_Usuario'] ?? ''),
            'created_at' => (string)($document['created_at'] ?? $sap['U_FechaReg'] ?? ''),
            'updated_at' => (string)($document['updated_at'] ?? ''),
            'url_pdf' => $documentId > 0
                ? rtrim(BASE_URL, '/') . '/api/mantenimientos/ver_costo_ot.php?id=' . $documentId
                : '',
        ];
    }
    return $rows;
}

if (!$session->isUserLoggedIn(false)) costo_out(['error' => 'No autorizado.'], 401);

$newPdfTarget = '';
try {
    $mysql = costo_mysql();
    $pdo = getHanaPdo();
    costo_ensure_documents_table($mysql);
    costo_migrate_legacy($mysql, $pdo);
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($method === 'GET') {
        if (!tiene_permiso('mantenimientos', 'ver')) costo_out(['error' => 'Sin permiso.'], 403);
        $docentry = (int)($_GET['docentry'] ?? 0);
        if ($docentry <= 0) costo_out(['error' => 'docentry es obligatorio.'], 400);
        costo_out(['success' => true, 'costos' => costo_fetch_rows($mysql, $pdo, $docentry)]);
    }

    if ($method === 'DELETE') {
        if (!tiene_permiso('mantenimientos', 'editar')) costo_out(['error' => 'Sin permiso para anular.'], 403);
        $input = request_json_body();
        $code = mb_substr(trim((string)($input['code'] ?? '')), 0, 50);
        if ($code === '') costo_out(['error' => 'El codigo SAP es obligatorio.'], 400);
        $stmt = $pdo->prepare(
            'UPDATE ' . COSTO_SAP_TABLE . ' SET "U_Activo"=\'N\' WHERE "Code"=? AND "U_Activo"=\'Y\''
        );
        $stmt->execute([$code]);
        if ($stmt->rowCount() < 1) costo_out(['error' => 'Registro no encontrado o ya anulado.'], 404);
        costo_out(['success' => true]);
    }

    if ($method !== 'POST') costo_out(['error' => 'Metodo no permitido.'], 405);
    if (!tiene_permiso('mantenimientos', 'crear') && !tiene_permiso('mantenimientos', 'editar')) {
        costo_out(['error' => 'Sin permiso para registrar costos.'], 403);
    }

    $code = mb_substr(trim((string)($_POST['code'] ?? '')), 0, 50);
    $docentry = (int)($_POST['docentry'] ?? 0);
    $tipo = strtoupper(trim((string)($_POST['tipo'] ?? '')));
    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    $itemCode = mb_substr(trim((string)($_POST['item_code'] ?? '')), 0, 50);
    $moneda = strtoupper(trim((string)($_POST['moneda'] ?? 'PEN')));
    $docCompra = mb_substr(trim((string)($_POST['codigo_compra_sap'] ?? '')), 0, 50);
    $proveedor = mb_substr(trim((string)($_POST['proveedor'] ?? '')), 0, 150);
    $cantidad = costo_decimal($_POST['cantidad'] ?? '', 3, 'La cantidad');
    $precio = costo_decimal($_POST['precio_unitario'] ?? '', 2, 'El precio unitario');
    if ($docentry <= 0) costo_out(['error' => 'La OT es obligatoria.'], 400);
    if (!in_array($tipo, ['REPUESTO', 'SERVICIO'], true)) costo_out(['error' => 'Tipo no valido.'], 400);
    if (mb_strlen($descripcion) < 3 || mb_strlen($descripcion) > 30000) {
        costo_out(['error' => 'La descripcion debe tener entre 3 y 30000 caracteres.'], 400);
    }
    if ($cantidad <= 0 || $precio < 0) costo_out(['error' => 'Cantidad o precio no valido.'], 400);
    if (!in_array($moneda, ['PEN', 'USD'], true)) costo_out(['error' => 'Moneda no valida.'], 400);
    $otDate = costo_ot_date($pdo, $docentry);
    if ($otDate === null) costo_out(['error' => 'La OT indicada no existe en SAP.'], 404);

    $current = null;
    if ($code !== '') {
        $stmt = $pdo->prepare(
            'SELECT "Code","U_OTDocEntry","U_Activo" FROM ' . COSTO_SAP_TABLE
            . ' WHERE "Code"=? AND "U_OTDocEntry"=?'
        );
        $stmt->execute([$code, $docentry]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$current || ($current['U_Activo'] ?? 'Y') !== 'Y') {
            costo_out(['error' => 'El registro SAP que intenta editar no existe o esta anulado.'], 404);
        }
    }

    $hasPdf = isset($_FILES['pdf'])
        && (int)($_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $oldDocument = $code !== '' ? costo_document_by_code($mysql, $code) : null;
    if ($code === '' && !$hasPdf) costo_out(['error' => 'Adjunte el PDF de sustento.'], 400);
    if ($code !== '' && !$hasPdf && !$oldDocument) costo_out(['error' => 'El registro no tiene PDF; adjunte uno.'], 400);
    $pdf = $hasPdf ? costo_upload_pdf($_FILES['pdf'], $docentry, $otDate) : null;
    if ($pdf) $newPdfTarget = $pdf['target'];

    $user = current_user();
    $userId = (int)($user['id'] ?? 0);
    $userIdValue = $userId > 0 ? $userId : null;
    $userLabel = mb_substr(trim((string)($user['name'] ?? $user['username'] ?? 'Sistema')), 0, 50);
    $total = round($cantidad * $precio, 2);
    $isNew = $code === '';
    if ($isNew) $code = costo_new_code($pdo);

    $mysql->begin_transaction();
    $pdo->beginTransaction();
    try {
        $documentId = (int)($oldDocument['id'] ?? 0);
        if ($pdf && $oldDocument) {
            $stmt = $mysql->prepare(
                'UPDATE mantenimientos_ot_costos_documentos SET nombre_original=?,nombre_archivo=?,'
                . 'ruta_relativa=?,mime_type=?,tamano_bytes=?,user_id=? WHERE id=?'
            );
            $stmt->bind_param(
                'ssssiii', $pdf['original'], $pdf['stored'], $pdf['relative'], $pdf['mime'],
                $pdf['size'], $userIdValue, $documentId
            );
            if (!$stmt->execute()) throw new RuntimeException('No se pudo reemplazar el PDF.');
        } elseif ($pdf) {
            $documentId = costo_insert_document($mysql, $code, $docentry, $pdf, $userIdValue);
        }

        if ($isNew) {
            costo_insert_sap($pdo, [
                'code' => $code,
                'name' => substr('OT' . $docentry . '-' . $code, 0, 100),
                'ot_docentry' => $docentry,
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'item_code' => $itemCode !== '' ? $itemCode : null,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'moneda' => $moneda,
                'total' => $total,
                'doc_compra' => $docCompra !== '' ? $docCompra : null,
                'proveedor' => $proveedor !== '' ? $proveedor : null,
                'document_id' => $documentId,
                'usuario' => $userLabel,
                'fecha' => date('Y-m-d'),
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE ' . COSTO_SAP_TABLE . ' SET "U_Tipo"=?,"U_Descripcion"=?,"U_ItemCode"=?,'
                . '"U_Cantidad"=?,"U_PrecioUnit"=?,"U_Moneda"=?,"U_Total"=?,"U_DocCompra"=?,'
                . '"U_Proveedor"=?,"U_IdArchivo"=?,"U_Usuario"=? WHERE "Code"=? AND "U_OTDocEntry"=?'
            );
            $stmt->execute([
                $tipo, $descripcion, $itemCode !== '' ? $itemCode : null, $cantidad, $precio, $moneda,
                $total, $docCompra !== '' ? $docCompra : null, $proveedor !== '' ? $proveedor : null,
                $documentId, $userLabel, $code, $docentry,
            ]);
        }
        $pdo->commit();
        $mysql->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mysql->rollback();
        if ($newPdfTarget !== '') @unlink($newPdfTarget);
        throw $e;
    }
    if ($pdf && $oldDocument && $oldDocument['ruta_relativa'] !== $pdf['relative']) {
        costo_unlink((string)$oldDocument['ruta_relativa']);
    }
    $rows = costo_fetch_rows($mysql, $pdo, $docentry);
    $saved = null;
    foreach ($rows as $row) if ($row['code'] === $code) { $saved = $row; break; }
    costo_out(['success' => true, 'costo' => $saved], $isNew ? 201 : 200);
} catch (Throwable $e) {
    if ($newPdfTarget !== '' && is_file($newPdfTarget)) @unlink($newPdfTarget);
    error_log('Costos OT SAP/MySQL: ' . $e->getMessage());
    costo_out(['error' => 'No se pudo procesar el repuesto o servicio.'], 500);
}
