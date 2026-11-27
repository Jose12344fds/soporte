<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/conexion_hana.php';
require_once dirname(dirname(__DIR__)) . '/includes/load.php';

if (!$session->isUserLoggedIn(true)) {
    http_response_code(401);
    exit('Sesion no valida.');
}
if (!tiene_permiso('mantenimientos', 'ver')) {
    http_response_code(403);
    exit('Sin permiso para exportar mantenimientos.');
}

function xlsx_xml($value): string {
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsx_cell(string $ref, $value, int $style = 0): string {
    return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
        . xlsx_xml($value) . '</t></is></c>';
}

function xlsx_row(int $number, array $cells, float $height = 20): string {
    return '<row r="' . $number . '" ht="' . $height . '" customHeight="1">' . implode('', $cells) . '</row>';
}

function xlsx_hana_quote(string $value): string {
    return "'" . str_replace("'", "''", $value) . "'";
}

// PDO_ODBC cannot fetch HANA NCLOB values reliably; read them as small binary chunks.
function xlsx_read_nclob(PDO $pdo, string $table, string $code, int $length): string {
    if ($length <= 0 || !in_array($table, ['@TAMA_PROGMANT', '@TAMA_PROGDET'], true)) return '';
    $length = min($length, 50000);
    $chunkSize = 25;
    $parts = (int)ceil($length / $chunkSize);
    $value = '';
    $codeQ = xlsx_hana_quote($code);
    for ($offset = 0; $offset < $parts; $offset += 100) {
        $selects = [];
        $end = min($offset + 100, $parts);
        for ($i = $offset; $i < $end; $i++) {
            $start = ($i * $chunkSize) + 1;
            $hex = 'CAST(BINTOHEX(CAST(SUBSTRING("U_Observacion",' . $start . ',' . $chunkSize
                . ') AS VARBINARY(200))) AS VARCHAR(250))';
            $selects[] = 'SELECT ' . $i . ' AS "SEQ", ' . $hex . ' AS "HEX" FROM "SBO_TAMAING_PRODUC"."'
                . $table . '" WHERE "Code" = ' . $codeQ;
        }
        $statement = $pdo->query(implode(' UNION ALL ', $selects) . ' ORDER BY "SEQ"');
        while ($part = $statement->fetch(PDO::FETCH_ASSOC)) {
            $encoded = preg_replace('/[^0-9A-Fa-f]/', '', (string)($part['HEX'] ?? ''));
            if ($encoded === '') continue;
            if (strlen($encoded) % 2 !== 0) $encoded = substr($encoded, 0, -1);
            $decoded = hex2bin($encoded);
            if ($decoded !== false) $value .= $decoded;
        }
    }
    return $value;
}

try {
    $inicio = substr(trim((string)($_GET['inicio'] ?? '')), 0, 10);
    $fin = substr(trim((string)($_GET['fin'] ?? '')), 0, 10);
    $semana = (int)($_GET['semana'] ?? 0);
    $codes = array_values(array_unique(array_filter(array_map('trim', explode(',', (string)($_GET['codigos'] ?? ''))))));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) || $semana < 1) {
        throw new InvalidArgumentException('Rango de semana invalido.');
    }
    if (!$codes || count($codes) > 500) {
        throw new InvalidArgumentException('No se recibieron ocurrencias validas para exportar.');
    }
    foreach ($codes as $code) {
        if (!preg_match('/^[A-Za-z0-9._-]{1,50}$/', $code)) {
            throw new InvalidArgumentException('La seleccion contiene un codigo de ocurrencia no valido.');
        }
    }

    $pdo = getHanaPdo();
    $sql = <<<'SQL'
SELECT
    COALESCE(NULLIF(T3."U_Cod_Tipo", ''), '') AS "CodTipo",
    COALESCE(NULLIF(T3."U_TIP_EMH", ''), '') AS "TipoEMH",
    COALESCE(NULLIF(T3."U_ItemNameEq", ''), '') AS "Equipo",
    T1."Code" AS "DetalleCode",
    T0."Code" AS "PlanCode",
    T0."Name" AS "PlanName",
    CAST(LENGTH(T1."U_Observacion") AS VARCHAR(20)) AS "DetalleObsLen",
    CAST(LENGTH(T0."U_Observacion") AS VARCHAR(20)) AS "PlanObsLen",
    COALESCE(NULLIF(T3."Name", ''), NULLIF(T1."U_ItemCode", ''), T0."U_ItemCode", '') AS "EquipoName",
    COALESCE(NULLIF(T3."Code", ''), '') AS "EquipoCode",
    COALESCE(NULLIF(T3."U_UBICACION", ''), '') AS "Ubicacion",
    COALESCE(NULLIF(T0."U_Frecuencia", ''), NULLIF(T1."U_TipoPeriodo", ''), '') AS "Frecuencia",
    CAST(SUBSTRING(TO_NVARCHAR(T0."U_Modalidad"), 1, 10) AS VARCHAR(10)) AS "Modalidad",
    SUBSTRING(TO_VARCHAR(T1."U_FechaProg"), 1, 10) AS "Fecha"
FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGDET" T1
INNER JOIN "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT" T0 ON T1."U_ProgMant" = T0."Code"
LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" T3
    ON COALESCE(NULLIF(T1."U_ItemCode", ''), T0."U_ItemCode") = T3."Name"
WHERE SUBSTRING(TO_VARCHAR(T1."U_FechaProg"), 1, 10) BETWEEN ? AND ?
  AND UPPER(TRIM(COALESCE(T1."U_Estado", ''))) <> 'CANCELADO'
  AND T1."Code" IN (__VISIBLE_CODES__)
ORDER BY COALESCE(NULLIF(T3."U_Cod_Tipo", ''), ''),
         COALESCE(NULLIF(T3."U_TIP_EMH", ''), ''),
         COALESCE(NULLIF(T3."U_ItemNameEq", ''), ''),
         T1."U_FechaProg", T1."Code"
SQL;
    $sql = str_replace('__VISIBLE_CODES__', implode(',', array_fill(0, count($codes), '?')), $sql);
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$inicio, $fin], $codes));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) throw new RuntimeException('No hay programaciones para la semana seleccionada.');

    $detailCache = [];
    $planCache = [];
    foreach ($rows as &$row) {
        $detailCode = (string)$row['DetalleCode'];
        $planCode = (string)$row['PlanCode'];
        if (!array_key_exists($detailCode, $detailCache)) {
            $detailCache[$detailCode] = xlsx_read_nclob($pdo, '@TAMA_PROGDET', $detailCode, (int)$row['DetalleObsLen']);
        }
        if (!array_key_exists($planCode, $planCache)) {
            $planCache[$planCode] = xlsx_read_nclob($pdo, '@TAMA_PROGMANT', $planCode, (int)$row['PlanObsLen']);
        }
        $row['Trabajo'] = trim($detailCache[$detailCode]) ?: (trim($planCache[$planCode]) ?: (string)$row['PlanName']);
        unset($row['DetalleCode'], $row['PlanCode'], $row['PlanName'], $row['DetalleObsLen'], $row['PlanObsLen']);
    }
    unset($row);

    $sheetRows = [];
    // Every cell in merged header ranges is emitted so Excel renders a complete thin black border.
    $sheetRows[] = xlsx_row(1, [
        xlsx_cell('A1', 'TAMA INGENIEROS S.A.C.', 10), xlsx_cell('B1', '', 10),
        xlsx_cell('C1', 'SISTEMA INTEGRADO DE GESTION', 1), xlsx_cell('D1', '', 1), xlsx_cell('E1', '', 1), xlsx_cell('F1', '', 1),
        xlsx_cell('G1', 'T-MT-PR-01', 3), xlsx_cell('H1', '', 3), xlsx_cell('I1', '', 3), xlsx_cell('J1', '', 3),
    ], 22);
    $sheetRows[] = xlsx_row(2, [
        xlsx_cell('A2', '', 10), xlsx_cell('B2', '', 10),
        xlsx_cell('C2', 'PROGRAMA SEMANAL DE MANTENIMIENTO PREVENTIVO ' . substr($inicio, 0, 4), 1),
        xlsx_cell('D2', '', 1), xlsx_cell('E2', '', 1), xlsx_cell('F2', '', 1),
        xlsx_cell('G2', 'FECHA', 2), xlsx_cell('H2', date('d/m/Y'), 3), xlsx_cell('I2', '', 3), xlsx_cell('J2', '', 3),
    ], 20);
    $sheetRows[] = xlsx_row(3, [
        xlsx_cell('A3', '', 10), xlsx_cell('B3', '', 10),
        xlsx_cell('C3', '', 1), xlsx_cell('D3', '', 1), xlsx_cell('E3', '', 1), xlsx_cell('F3', '', 1),
        xlsx_cell('G3', 'VERSION', 2), xlsx_cell('H3', '2', 3), xlsx_cell('I3', '', 3), xlsx_cell('J3', '', 3),
    ], 18);
    $sheetRows[] = xlsx_row(4, [
        xlsx_cell('A4', '', 10), xlsx_cell('B4', '', 10),
        xlsx_cell('C4', '', 1), xlsx_cell('D4', '', 1), xlsx_cell('E4', '', 1), xlsx_cell('F4', '', 1),
        xlsx_cell('G4', 'ELABORADO:', 3),
        xlsx_cell('H4', 'REVISADO:', 3),
        xlsx_cell('I4', 'APROBADO:', 3), xlsx_cell('J4', '', 3),
    ], 18);
    $sheetRows[] = xlsx_row(5, [
        xlsx_cell('A5', 'SEMANA', 4), xlsx_cell('B5', (string)$semana, 4),
        xlsx_cell('C5', 'Rango: ' . date('d/m/Y', strtotime($inicio)) . ' - ' . date('d/m/Y', strtotime($fin)), 3),
        xlsx_cell('D5', '', 3), xlsx_cell('E5', '', 3), xlsx_cell('F5', '', 3),
        xlsx_cell('G5', 'MT', 11), xlsx_cell('H5', 'GO', 11), xlsx_cell('I5', 'GG', 11),
    ], 19);
    $headers = ['MAQUINA TIPO', 'MAQUINA / EQUIPO', 'TRABAJO A REALIZAR', 'CODIGO', 'UBICACION', 'FRECUENCIA', 'MODALIDAD', 'TECNICO RESPONSABLE', 'FECHA'];
    $headerCells = [];
    foreach (range('A', 'I') as $index => $column) $headerCells[] = xlsx_cell($column . '6', $headers[$index], 5);
    $sheetRows[] = xlsx_row(6, $headerCells, 25);

    $mergeCells = ['A1:B4', 'C1:F1', 'C2:F4', 'G1:J1', 'H2:J2', 'H3:J3', 'C5:F5'];
    $groupStart = 7;
    $previousEquipment = null;
    $machineGroupStart = 7;
    $previousMachineType = null;
    foreach ($rows as $index => $row) {
        $excelRow = $index + 7;
        $tipo = trim((string)$row['CodTipo']);
        $tipoEmh = trim((string)$row['TipoEMH']);
        $maquinaTipo = implode(' - ', array_values(array_filter([$tipo, $tipoEmh], static fn($v) => $v !== '')));
        $equipo = trim((string)$row['Equipo']);
        $codigo = implode(' - ', array_values(array_filter([trim((string)$row['EquipoName']), trim((string)$row['EquipoCode'])], static fn($v) => $v !== '')));

        if ($previousEquipment !== null && $equipo !== $previousEquipment) {
            if ($excelRow - 1 > $groupStart) $mergeCells[] = 'B' . $groupStart . ':B' . ($excelRow - 1);
            $groupStart = $excelRow;
        }
        $isFirstInGroup = $previousEquipment === null || $equipo !== $previousEquipment;
        $previousEquipment = $equipo;

        if ($previousMachineType !== null && $maquinaTipo !== $previousMachineType) {
            if ($previousMachineType !== '' && $excelRow - 1 > $machineGroupStart) {
                $mergeCells[] = 'A' . $machineGroupStart . ':A' . ($excelRow - 1);
            }
            $machineGroupStart = $excelRow;
        }
        $isFirstMachineType = $previousMachineType === null || $maquinaTipo !== $previousMachineType;
        $previousMachineType = $maquinaTipo;

        $sheetRows[] = xlsx_row($excelRow, [
            xlsx_cell('A' . $excelRow, $isFirstMachineType ? $maquinaTipo : '', 7),
            xlsx_cell('B' . $excelRow, $isFirstInGroup ? $equipo : '', 7),
            xlsx_cell('C' . $excelRow, $row['Trabajo'], 8),
            xlsx_cell('D' . $excelRow, $codigo, 7),
            xlsx_cell('E' . $excelRow, $row['Ubicacion'], 7),
            xlsx_cell('F' . $excelRow, $row['Frecuencia'], 7),
            xlsx_cell('G' . $excelRow, $row['Modalidad'], 9),
            xlsx_cell('H' . $excelRow, '', 9),
            xlsx_cell('I' . $excelRow, '', 9),
        ], 26);
    }
    $lastRow = count($rows) + 6;
    if ($lastRow > $groupStart) $mergeCells[] = 'B' . $groupStart . ':B' . $lastRow;
    if ($previousMachineType !== '' && $lastRow > $machineGroupStart) {
        $mergeCells[] = 'A' . $machineGroupStart . ':A' . $lastRow;
    }

    $mergeXml = '<mergeCells count="' . count($mergeCells) . '">';
    foreach ($mergeCells as $merge) $mergeXml .= '<mergeCell ref="' . $merge . '"/>';
    $mergeXml .= '</mergeCells>';

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
        . '<dimension ref="A1:J' . $lastRow . '"/><sheetViews><sheetView showGridLines="0" workbookViewId="0"><pane ySplit="6" topLeftCell="A7" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="15"/><cols>'
        . '<col min="1" max="1" width="18" customWidth="1"/><col min="2" max="2" width="28" customWidth="1"/>'
        . '<col min="3" max="3" width="55" customWidth="1"/><col min="4" max="4" width="22" customWidth="1"/>'
        . '<col min="5" max="5" width="18" customWidth="1"/><col min="6" max="6" width="16" customWidth="1"/>'
        . '<col min="7" max="7" width="14" customWidth="1"/><col min="8" max="10" width="19" customWidth="1"/></cols><sheetData>' . implode('', $sheetRows) . '</sheetData>'
        . $mergeXml . '<printOptions horizontalCentered="1"/><pageMargins left="0.15" right="0.15" top="0.2" bottom="0.2" header="0.1" footer="0.1"/>'
        . '<pageSetup orientation="landscape" paperSize="9" pageOrder="downThenOver" fitToWidth="1" fitToHeight="0" horizontalDpi="300" verticalDpi="300"/>'
        . '<rowBreaks count="0" manualBreakCount="0"/></worksheet>';

    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="6"><font><sz val="8"/><name val="Calibri"/></font><font><b/><sz val="13"/><name val="Calibri"/></font><font><b/><sz val="9"/><name val="Calibri"/></font><font><b/><color rgb="FF000000"/><sz val="9"/><name val="Calibri"/></font><font><b/><color rgb="FF1F3344"/><sz val="12"/><name val="Calibri"/></font><font><b/><u/><color rgb="FF000000"/><sz val="9"/><name val="Calibri"/></font></fonts>
<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFC928"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF3F6F8"/><bgColor indexed="64"/></patternFill></fill></fills>
<borders count="2"><border/><border><left style="thin"><color rgb="FF000000"/></left><right style="thin"><color rgb="FF000000"/></right><top style="thin"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="12"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

    $temp = tempnam(sys_get_temp_dir(), 'mnt_week_');
    $zip = new ZipArchive();
    if ($zip->open($temp, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('No se pudo crear el archivo Excel.');
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Programa semanal" sheetId="1" r:id="rId1"/></sheets><definedNames><definedName name="_xlnm.Print_Titles" localSheetId="0">\'Programa semanal\'!$6:$6</definedName></definedNames></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->addFromString('xl/styles.xml', $stylesXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    while (ob_get_level()) ob_end_clean();
    $filename = 'programa_mantenimiento_semana_' . $semana . '_' . substr($inicio, 0, 4) . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($temp));
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($temp);
    @unlink($temp);
} catch (Throwable $e) {
    while (ob_get_level()) ob_end_clean();
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
}
