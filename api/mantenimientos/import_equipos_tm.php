<?php
/**
 * API temporal: importar CSV a SAP @TAMA_EQUIPOS_TM.
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/import_equipos_tm.log');
set_time_limit(300);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion_hana.php';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';

if (!$session->isUserLoggedIn(false)) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$fields = [
    'Name' => ['required' => true],
    'U_ItemNameEq' => ['required' => false],
    'U_InventryNo' => ['required' => false],
    'U_AssetSerNo' => ['required' => false],
    'U_Marca' => ['required' => false],
    'U_Modelo' => ['required' => false],
    'U_TIP_EMH' => ['required' => false],
    'U_Cod_Tipo' => ['required' => false],
    'U_Fecha_Adq' => ['required' => false, 'date' => true],
    'U_Criterio' => ['required' => false],
    'U_Nivel_Criterio' => ['required' => false],
    'U_UBICACION' => ['required' => false],
    'U_Estado' => ['required' => false],
    'U_FECHA_BAJA' => ['required' => false, 'date' => true],
    'U_Vida_Util' => ['required' => false],
];

$aliases = [
    'cod_equipo' => 'Name',
    'codequipo' => 'Name',
    'itemcode' => 'Name',
    'name' => 'Name',
    'nombre' => 'U_ItemNameEq',
    'nombre_equipo' => 'U_ItemNameEq',
    'nombre_de_los_equipos' => 'U_ItemNameEq',
    'u_itemnameeq' => 'U_ItemNameEq',
    'inventario' => 'U_InventryNo',
    'inventariono' => 'U_InventryNo',
    'u_inventryno' => 'U_InventryNo',
    'serie' => 'U_AssetSerNo',
    'assetserno' => 'U_AssetSerNo',
    'u_assetserno' => 'U_AssetSerNo',
    'marca' => 'U_Marca',
    'u_marca' => 'U_Marca',
    'modelo' => 'U_Modelo',
    'u_modelo' => 'U_Modelo',
    'tipo_emh' => 'U_TIP_EMH',
    'u_tip_emh' => 'U_TIP_EMH',
    'codigo_tipo' => 'U_Cod_Tipo',
    'cod_tipo' => 'U_Cod_Tipo',
    'u_cod_tipo' => 'U_Cod_Tipo',
    'fecha_adq' => 'U_Fecha_Adq',
    'fecha_adquisicion' => 'U_Fecha_Adq',
    'u_fecha_adq' => 'U_Fecha_Adq',
    'criterio' => 'U_Criterio',
    'u_criterio' => 'U_Criterio',
    'nivel_criterio' => 'U_Nivel_Criterio',
    'u_nivel_criterio' => 'U_Nivel_Criterio',
    'ubicacion' => 'U_UBICACION',
    'u_ubicacion' => 'U_UBICACION',
    'estado' => 'U_Estado',
    'u_estado' => 'U_Estado',
    'fecha_baja' => 'U_FECHA_BAJA',
    'u_fecha_baja' => 'U_FECHA_BAJA',
    'vida_util' => 'U_Vida_Util',
    'vida_hitus' => 'U_Vida_Util',
    'u_vida_util' => 'U_Vida_Util',
];

function jsend($data, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function norm_key($v) {
    $s = trim(mb_strtolower((string)$v, 'UTF-8'));
    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
    $s = preg_replace('/[^a-z0-9]+/', '_', $s);
    return trim($s, '_');
}

function detect_delimiter($file) {
    $fh = fopen($file, 'r');
    $line = $fh ? fgets($fh) : '';
    if ($fh) fclose($fh);
    $candidates = [';' => substr_count($line, ';'), ',' => substr_count($line, ','), "\t" => substr_count($line, "\t")];
    arsort($candidates);
    return key($candidates) ?: ';';
}

function csv_row($fh, $delimiter) {
    $row = fgetcsv($fh, 0, $delimiter);
    if ($row && isset($row[0])) {
        $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
    }
    return $row;
}

function build_header_map($headers, $aliases, $fields) {
    $map = [];
    $detected = [];
    foreach ($headers as $i => $header) {
        $raw = trim((string)$header);
        if ($raw === '') continue;
        $key = norm_key($raw);
        $field = $aliases[$key] ?? null;
        if (!$field && isset($fields[$raw])) $field = $raw;
        if (!$field && isset($fields[$key])) $field = $key;
        if ($field && isset($fields[$field]) && !isset($map[$field])) {
            $map[$field] = $i;
            $detected[] = $raw . ' -> ' . $field;
        } else {
            $detected[] = $raw;
        }
    }
    return [$map, $detected];
}

function parse_date_value($v) {
    $s = trim((string)($v ?? ''));
    if ($s !== '' && $s[0] === '#') return null;
    if ($s === '') return null;
    if (is_numeric($s)) {
        $days = (int)$s;
        if ($days > 20000 && $days < 90000) {
            return gmdate('Y-m-d', ($days - 25569) * 86400);
        }
    }
    $s = str_replace('.', '/', $s);
    foreach (['Y-m-d', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'm/d/Y', 'n/j/Y', 'Y/m/d'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $s);
        if ($dt) return $dt->format('Y-m-d');
    }
    $ts = strtotime(str_replace('/', '-', $s));
    return $ts ? date('Y-m-d', $ts) : null;
}

function clean_value($v) {
    $s = trim((string)($v ?? ''));
    return ($s !== '' && $s[0] === '#') ? '' : $s;
}

function hana_text($v) {
    if ($v === null || $v === '') return $v;
    $s = (string)$v;
    if (mb_check_encoding($s, 'UTF-8')) {
        $converted = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        if ($converted !== false) return $converted;
    }
    return $s;
}

function quote_sql($pdo, $v) {
    if ($v === null || $v === '') return 'NULL';
    return "'" . str_replace("'", "''", hana_text($v)) . "'";
}

function fixed_position_map($fields) {
    $ordered = [
        'Name',
        'U_ItemNameEq',
        'U_InventryNo',
        'U_AssetSerNo',
        'U_Marca',
        'U_Modelo',
        'U_TIP_EMH',
        'U_Cod_Tipo',
        'U_Fecha_Adq',
        'U_Criterio',
        'U_Nivel_Criterio',
        'U_UBICACION',
        'U_Estado',
        'U_FECHA_BAJA',
        'U_Vida_Util',
    ];
    $map = [];
    foreach ($ordered as $i => $field) {
        if ($field !== null && isset($fields[$field])) $map[$field] = $i;
    }
    return $map;
}

function xlsx_col_index($cellRef) {
    preg_match('/^([A-Z]+)/i', (string)$cellRef, $m);
    $letters = strtoupper($m[1] ?? '');
    $n = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $n = $n * 26 + (ord($letters[$i]) - 64);
    }
    return max(0, $n - 1);
}

function xlsx_shared_strings($file) {
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) throw new RuntimeException('No se pudo abrir Excel XLSX.');
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    $zip->close();
    if ($xml === false) return [];
    $sx = @simplexml_load_string($xml);
    if (!$sx) return [];
    $out = [];
    foreach ($sx->si as $si) {
        if (isset($si->t)) {
            $out[] = (string)$si->t;
        } else {
            $txt = '';
            foreach ($si->r as $r) $txt .= (string)$r->t;
            $out[] = $txt;
        }
    }
    return $out;
}

function xlsx_first_sheet_xml($file) {
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) throw new RuntimeException('No se pudo abrir Excel XLSX.');
    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($xml === false) throw new RuntimeException('No se encontro la primera hoja del Excel.');
    return $xml;
}

function xlsx_cell_value($cell, $shared) {
    $type = (string)($cell['t'] ?? '');
    if ($type === 'inlineStr') {
        return trim((string)($cell->is->t ?? ''));
    }
    $v = (string)($cell->v ?? '');
    if ($type === 's') {
        $idx = (int)$v;
        return trim((string)($shared[$idx] ?? ''));
    }
    return trim($v);
}

function build_equipo_row($raw, $map, $fields, $lineNo) {
    $row = [];
    foreach ($fields as $field => $meta) {
        $value = isset($map[$field]) ? ($raw[$map[$field]] ?? '') : '';
        $row[$field] = !empty($meta['date']) ? parse_date_value($value) : clean_value($value);
    }
    $name = strtoupper($row['Name']);
    $row['Name'] = $name;
    if ($name === '') return [null, ['row' => $lineNo, 'name' => '', 'error' => 'Name/codigo vacio']];
    if (($map['U_Fecha_Adq'] ?? null) !== null && trim((string)($raw[$map['U_Fecha_Adq']] ?? '')) !== '' && !$row['U_Fecha_Adq']) {
        return [null, ['row' => $lineNo, 'name' => $name, 'error' => 'U_Fecha_Adq invalida']];
    }
    if (($map['U_FECHA_BAJA'] ?? null) !== null && trim((string)($raw[$map['U_FECHA_BAJA']] ?? '')) !== '' && !$row['U_FECHA_BAJA']) {
        $row['U_FECHA_BAJA'] = null;
    }
    $row['_excel_row'] = $lineNo;
    return [$row, null];
}

function read_xlsx_batch_rows($file, $startRow, $chunk, $aliases, $fields, $sinCabecera = false) {
    $shared = xlsx_shared_strings($file);
    $xml = xlsx_first_sheet_xml($file);
    $sx = @simplexml_load_string($xml);
    if (!$sx) throw new RuntimeException('No se pudo leer la hoja del Excel.');

    $map = $sinCabecera ? fixed_position_map($fields) : null;
    $rows = [];
    $errors = [];
    $hasData = false;

    foreach ($sx->sheetData->row as $rowXml) {
        $lineNo = (int)($rowXml['r'] ?? 0);
        if ($lineNo <= 0) continue;
        $raw = [];
        foreach ($rowXml->c as $cell) {
            $idx = xlsx_col_index((string)$cell['r']);
            $raw[$idx] = xlsx_cell_value($cell, $shared);
        }

        if (!$sinCabecera && $lineNo === 1) {
            [$map, $detected] = build_header_map($raw, $aliases, $fields);
            $missing = [];
            foreach ($fields as $field => $meta) {
                if (!empty($meta['required']) && !isset($map[$field])) $missing[] = $field;
            }
            if ($missing) {
                throw new RuntimeException('Faltan columnas obligatorias: ' . implode(', ', $missing) . '. Cabeceras: ' . implode(' | ', $detected));
            }
            continue;
        }

        if ($lineNo < $startRow) continue;
        if ($lineNo >= $startRow + $chunk) break;

        $nonEmpty = false;
        foreach ($raw as $v) {
            if (trim((string)$v) !== '') { $nonEmpty = true; break; }
        }
        if (!$nonEmpty) continue;
        $hasData = true;

        [$built, $err] = build_equipo_row($raw, $map, $fields, $lineNo);
        if ($err) $errors[] = $err;
        else $rows[] = $built;
    }
    return [$rows, $errors, $hasData];
}

function read_batch_rows($file, $startRow, $chunk, $aliases, $fields, $sinCabecera = false) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'xlsx') {
        return read_xlsx_batch_rows($file, $startRow, $chunk, $aliases, $fields, $sinCabecera);
    }

    $delimiter = detect_delimiter($file);
    $fh = fopen($file, 'r');
    if (!$fh) throw new RuntimeException('No se pudo abrir archivo temporal.');

    if ($sinCabecera) {
        $map = fixed_position_map($fields);
        $lineNo = 0;
    } else {
        $headers = csv_row($fh, $delimiter);
        if (!$headers) throw new RuntimeException('El CSV no tiene cabecera.');
        [$map, $detected] = build_header_map($headers, $aliases, $fields);

        $missing = [];
        foreach ($fields as $field => $meta) {
            if (!empty($meta['required']) && !isset($map[$field])) $missing[] = $field;
        }
        if ($missing) {
            throw new RuntimeException('Faltan columnas obligatorias: ' . implode(', ', $missing) . '. Cabeceras: ' . implode(' | ', $detected));
        }
        $lineNo = 1;
    }

    $rows = [];
    $errors = [];
    $hasData = false;
    while (($raw = csv_row($fh, $delimiter)) !== false) {
        $lineNo++;
        if ($lineNo < $startRow) continue;
        if ($lineNo >= $startRow + $chunk) break;
        $nonEmpty = false;
        foreach ($raw as $v) {
            if (trim((string)$v) !== '') { $nonEmpty = true; break; }
        }
        if (!$nonEmpty) continue;
        $hasData = true;

        $row = [];
        foreach ($fields as $field => $meta) {
            $value = isset($map[$field]) ? ($raw[$map[$field]] ?? '') : '';
            $row[$field] = !empty($meta['date']) ? parse_date_value($value) : clean_value($value);
        }
        $name = strtoupper($row['Name']);
        $row['Name'] = $name;
        if ($name === '') {
            $errors[] = ['row' => $lineNo, 'name' => '', 'error' => 'Name/codigo vacio'];
            continue;
        }
        if (($map['U_Fecha_Adq'] ?? null) !== null && trim((string)($raw[$map['U_Fecha_Adq']] ?? '')) !== '' && !$row['U_Fecha_Adq']) {
            $errors[] = ['row' => $lineNo, 'name' => $name, 'error' => 'U_Fecha_Adq invalida'];
            continue;
        }
        if (($map['U_FECHA_BAJA'] ?? null) !== null && trim((string)($raw[$map['U_FECHA_BAJA']] ?? '')) !== '' && !$row['U_FECHA_BAJA']) {
            $row['U_FECHA_BAJA'] = null;
        }
        $row['_excel_row'] = $lineNo;
        $rows[] = $row;
    }
    fclose($fh);
    return [$rows, $errors, $hasData];
}

function next_code($pdo) {
    $row = $pdo->query(
        'SELECT COALESCE(MAX(CAST("Code" AS INTEGER)), 0) + 1 AS "NEXT_CODE"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM"'
        . " WHERE \"Code\" IS NOT NULL AND LENGTH(\"Code\") > 0 AND \"Code\" LIKE_REGEXPR '^[0-9]+$'"
    )->fetch();
    return (int)($row['NEXT_CODE'] ?? array_values($row)[0] ?? 1);
}

function import_rows($rows, $actualizar) {
    if (!$rows) return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
    $pdo = getHanaPdo();
    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];
    $code = next_code($pdo);

    foreach ($rows as $row) {
        try {
            $name = $row['Name'];
            $exists = $pdo->query(
                'SELECT "Code" FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" WHERE "Name" = ' . quote_sql($pdo, $name)
            )->fetch();

            $cols = ['U_ItemNameEq','U_InventryNo','U_AssetSerNo','U_Marca','U_Modelo','U_TIP_EMH','U_Cod_Tipo','U_Fecha_Adq','U_Criterio','U_Nivel_Criterio','U_UBICACION','U_Estado','U_FECHA_BAJA','U_Vida_Util'];
            if ($exists) {
                if (!$actualizar) { $skipped++; continue; }
                $sets = [];
                foreach ($cols as $c) {
                    $sets[] = '"' . $c . '" = ' . quote_sql($pdo, $row[$c] ?? null);
                }
                $sql = 'UPDATE "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" SET ' . implode(',', $sets)
                     . ' WHERE "Name" = ' . quote_sql($pdo, $name);
                $pdo->exec($sql);
                $updated++;
            } else {
                $fieldSql = '"Code","Name","' . implode('","', $cols) . '"';
                $values = [quote_sql($pdo, (string)$code), quote_sql($pdo, $name)];
                foreach ($cols as $c) $values[] = quote_sql($pdo, $row[$c] ?? null);
                $sql = 'INSERT INTO "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" (' . $fieldSql . ') VALUES (' . implode(',', $values) . ')';
                $pdo->exec($sql);
                $code++;
                $inserted++;
            }
        } catch (Throwable $e) {
            $errors[] = ['row' => $row['_excel_row'] ?? '', 'name' => $row['Name'] ?? '', 'error' => $e->getMessage()];
        }
    }

    return ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
}

try {
    if (isset($_GET['template'])) {
        ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla_equipos_tm.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array_keys($fields), ';');
        fputcsv($out, ['EQ-001','Equipo ejemplo','INV-001','SER-001','Marca','Modelo','TIPO EMH','COD-TIPO','2026-06-11','Critico','Alto','Planta','Activo','','10'], ';');
        fclose($out);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsend(['success' => false, 'error' => 'Metodo no permitido'], 405);
    $action = $_POST['action'] ?? '';

    if ($action === 'prepare') {
        if (!isset($_FILES['archivo']) || ($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            jsend(['success' => false, 'error' => 'No se recibio archivo valido'], 400);
        }
        $dir = dirname(dirname(__DIR__)) . '/uploads/tmp_import_equipos_tm';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $ext = strtolower(pathinfo($_FILES['archivo']['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
            jsend(['success' => false, 'error' => 'Solo se permite Excel .xlsx o CSV'], 400);
        }
        $token = 'equipos_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
        $dest = $dir . '/' . $token . '.' . $ext;
        if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $dest)) {
            jsend(['success' => false, 'error' => 'No se pudo guardar archivo temporal'], 500);
        }
        $_SESSION['import_equipos_tm'][$token] = [
            'file' => $dest,
            'actualizar' => !empty($_POST['actualizar']),
            'sin_cabecera' => true,
        ];
        jsend([
            'success' => true,
            'token' => $token,
            'chunk' => 1000,
            'start' => 1,
            'message' => 'Archivo preparado. Inicie la importacion por lotes.'
        ]);
    }

    if ($action === 'batch') {
        $token = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['token'] ?? '');
        $start = max(1, (int)($_POST['start'] ?? 1));
        $chunk = max(1, min(2000, (int)($_POST['chunk'] ?? 1000)));
        $info = $_SESSION['import_equipos_tm'][$token] ?? null;
        if (!$info || empty($info['file']) || !is_file($info['file'])) {
            jsend(['success' => false, 'error' => 'Archivo temporal no encontrado. Vuelva a subirlo.'], 400);
        }
        [$rows, $parseErrors, $hasData] = read_batch_rows($info['file'], $start, $chunk, $aliases, $fields, !empty($info['sin_cabecera']));
        if (!$hasData) {
            @unlink($info['file']);
            unset($_SESSION['import_equipos_tm'][$token]);
            jsend(['success' => true, 'done' => true, 'inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors_count' => 0, 'errors' => [], 'message' => 'Importacion terminada.']);
        }
        $res = import_rows($rows, !empty($info['actualizar']));
        $allErrors = array_merge($parseErrors, array_map(function($e){ return ['row' => '', 'name' => $e['name'] ?? '', 'error' => $e['error'] ?? 'Error']; }, $res['errors']));
        jsend([
            'success' => true,
            'done' => false,
            'inserted' => $res['inserted'],
            'updated' => $res['updated'],
            'skipped' => $res['skipped'],
            'errors_count' => count($allErrors),
            'errors' => array_slice($allErrors, 0, 30),
            'nextStart' => $start + $chunk,
            'message' => 'Lote ' . $start . '-' . ($start + $chunk - 1) . ': '
                . $res['inserted'] . ' insertado(s), '
                . $res['updated'] . ' actualizado(s), '
                . $res['skipped'] . ' omitido(s), '
                . count($allErrors) . ' error(es).',
        ]);
    }

    jsend(['success' => false, 'error' => 'Accion no reconocida'], 400);
} catch (Throwable $e) {
    error_log('import_equipos_tm: ' . $e->getMessage());
    jsend(['success' => false, 'error' => $e->getMessage()], 500);
}
