<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once __DIR__ . '/conexion_hana.php';

if (!$session->isUserLoggedIn(false)) { http_response_code(401); exit('No autorizado.'); }
if (!tiene_permiso('mantenimientos', 'ver')) { http_response_code(403); exit('Sin permiso para exportar.'); }

function eqx_xml($value): string {
    $text = (string)($value ?? '');
    $text = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $text) ?? '';
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
function eqx_col(int $number): string {
    $name = '';
    while ($number > 0) { $number--; $name = chr(65 + ($number % 26)) . $name; $number = intdiv($number, 26); }
    return $name;
}
function eqx_cell(string $ref, $value, int $style = 2, bool $number = false): string {
    if ($number && $value !== null && $value !== '' && is_numeric($value)) {
        return '<c r="'.$ref.'" s="'.$style.'"><v>'.(0 + $value).'</v></c>';
    }
    return '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.eqx_xml($value).'</t></is></c>';
}
function eqx_level_style(string $level): int {
    return match (strtoupper(trim($level))) { 'ALTO' => 9, 'MEDIO' => 10, 'BAJO' => 11, default => 12 };
}
function eqx_estado_style(string $estado): int {
    $st = strtoupper(trim($estado));
    if (strpos($st, 'BAJA') !== false) return 9;
    if ($st === 'OPERATIVO' || $st === 'ACTIVO') return 11;
    if ($st === '') return 12;
    return 10;
}
function eqx_col_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $pdo->query('SELECT "' . str_replace('"', '""', $column) . '" FROM "SBO_TAMAING_PRODUC"."' . $table . '" LIMIT 1')->fetch();
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
function eqx_effective_level(array $row): string {
    $factors = ['F1_EVA','R_EVA','SHA_EVA','IC_EVA','IP_EVA','BM_EVA','CM_EVA'];
    $valid = true;
    foreach ($factors as $factor) {
        $value = (float)($row[$factor] ?? 0);
        if ($value < 1 || $value > 5) { $valid = false; break; }
    }
    $level = strtoupper(trim((string)($row['Nivel_Criterio'] ?? '')));
    $risk = (float)($row['Riesgo_EVA'] ?? 0);
    if ($valid && in_array($level, ['ALTO', 'MEDIO', 'BAJO'], true) && $risk > 0) return $level;
    return 'SIN EVALUAR';
}
function eqx_all_rows(PDO $pdo): array {
    $codRegistroColumn = eqx_col_exists($pdo, '@TAMA_EQUIPOS_TM', 'U_CodRegistro')
        ? 'U_CodRegistro'
        : (eqx_col_exists($pdo, '@TAMA_EQUIPOS_TM', 'U_CodRegisto') ? 'U_CodRegisto' : '');
    $hasCodRegistro = $codRegistroColumn !== '';
    $hasCodComtaSap = eqx_col_exists($pdo, '@TAMA_EQUIPOS_TM', 'U_CodComtaSap');
    $criticidadFields = ['U_F1_EVA', 'U_R_EVA', 'U_SHA_EVA', 'U_IC_EVA', 'U_IP_EVA', 'U_BM_EVA', 'U_CM_EVA', 'U_RIESGO_EVA'];
    $criticidadAvailable = [];
    foreach ($criticidadFields as $field) $criticidadAvailable[$field] = eqx_col_exists($pdo, '@TAMA_EQUIPOS_TM', $field);

    $sql = 'SELECT'
         . ' T0."Code",'
         . ' T0."Name",'
         . ' T0."U_ItemNameEq",'
         . ' T0."U_InventryNo",'
         . ' T0."U_AssetSerNo",'
         . ' T0."U_Marca",'
         . ' T0."U_Modelo",'
         . ' T0."U_TIP_EMH",'
         . ' T0."U_Cod_Tipo",'
         . ($hasCodComtaSap ? ' T0."U_CodComtaSap",' : ' NULL AS "U_CodComtaSap",')
         . ' T0."U_Fecha_Adq",'
         . ' T0."U_Criterio",'
         . ' T0."U_Nivel_Criterio",'
         . ' T0."U_UBICACION",'
         . ' T0."U_Estado",'
         . ' T0."U_FECHA_BAJA",'
         . ' T0."U_Vida_Util",'
         . ' T0."U_Id_Proceso",'
         . ($hasCodRegistro ? ' T0."' . $codRegistroColumn . '" AS "U_CodRegistro",' : ' NULL AS "U_CodRegistro",')
         . implode('', array_map(static function ($column) use ($criticidadAvailable) {
             return ($criticidadAvailable[$column] ? ' T0."' . $column . '"' : ' NULL') . ' AS "' . $column . '",';
         }, $criticidadFields))
         . ' P."Name" AS "ProcesoName",'
         . ' R."Name" AS "RegistroName"'
         . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" T0 '
         . ' LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_PROCESO" P ON CAST(T0."U_Id_Proceso" AS NVARCHAR) = CAST(P."Code" AS NVARCHAR)'
         . ($hasCodRegistro ? ' LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_REGISTROS" R ON CAST(T0."' . $codRegistroColumn . '" AS NVARCHAR) = CAST(R."Code" AS NVARCHAR)' : ' LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_REGISTROS" R ON 1 = 0')
         . ' ORDER BY LPAD(COALESCE(T0."Code", \'\'), 12, \'0\') DESC, T0."Name"';

    $rows = $pdo->query($sql)->fetchAll();
    $clean = static function ($v) { return trim(str_replace("\0", '', (string)($v ?? ''))); };
    $date10 = static function ($v) use ($clean) {
        $s = $clean($v);
        return preg_match('/^\d{4}-\d{2}-\d{2}/', $s, $m) ? $m[0] : '';
    };

    return array_map(function ($r) use ($clean, $date10) {
        $row = [
            'Code' => $clean($r['Code'] ?? ''),
            'ItemCode' => $clean($r['Name'] ?? ''),
            'ItemName' => $clean($r['U_ItemNameEq'] ?? ''),
            'InventryNo' => $clean($r['U_InventryNo'] ?? ''),
            'AssetSerNo' => $clean($r['U_AssetSerNo'] ?? ''),
            'Marca' => $clean($r['U_Marca'] ?? ''),
            'Modelo' => $clean($r['U_Modelo'] ?? ''),
            'TIP_EMH' => $clean($r['U_TIP_EMH'] ?? ''),
            'Cod_Tipo' => $clean($r['U_Cod_Tipo'] ?? ''),
            'CodComtaSap' => $clean($r['U_CodComtaSap'] ?? ''),
            'Ubicacion' => $clean($r['U_UBICACION'] ?? ''),
            'ProcesoName' => $clean($r['ProcesoName'] ?? ''),
            'RegistroName' => $clean($r['RegistroName'] ?? ''),
            'Estado' => $clean($r['U_Estado'] ?? ''),
            'Fecha_Adq' => $date10($r['U_Fecha_Adq'] ?? ''),
            'Fecha_Baja' => $date10($r['U_FECHA_BAJA'] ?? ''),
            'Vida_Util' => $clean($r['U_Vida_Util'] ?? ''),
            'Criterio' => $clean($r['U_Criterio'] ?? ''),
            'CodRegistro' => $clean($r['U_CodRegistro'] ?? ''),
            'Nivel_Criterio' => $clean($r['U_Nivel_Criterio'] ?? ''),
            'Riesgo_EVA' => $clean($r['U_RIESGO_EVA'] ?? ''),
            'F1_EVA' => $clean($r['U_F1_EVA'] ?? ''),
            'R_EVA' => $clean($r['U_R_EVA'] ?? ''),
            'SHA_EVA' => $clean($r['U_SHA_EVA'] ?? ''),
            'IC_EVA' => $clean($r['U_IC_EVA'] ?? ''),
            'IP_EVA' => $clean($r['U_IP_EVA'] ?? ''),
            'BM_EVA' => $clean($r['U_BM_EVA'] ?? ''),
            'CM_EVA' => $clean($r['U_CM_EVA'] ?? ''),
        ];
        $row['NivelEfectivo'] = eqx_effective_level($row);
        return $row;
    }, $rows);
}
function eqx_filtered_rows(PDO $pdo): array {
    $rows = eqx_all_rows($pdo);
    $search = mb_strtolower(trim((string)($_GET['buscar'] ?? '')), 'UTF-8');
    $nivel = strtoupper(trim((string)($_GET['nivel'] ?? '')));
    if ($nivel === 'SIN_EVALUAR') $nivel = 'SIN EVALUAR';
    $estado = strtoupper(trim((string)($_GET['estado'] ?? '')));

    return array_values(array_filter($rows, static function (array $row) use ($search, $nivel, $estado): bool {
        $haystack = mb_strtolower(implode(' ', [
            $row['Code'], $row['ItemCode'], $row['ItemName'], $row['InventryNo'],
            $row['AssetSerNo'], $row['Marca'], $row['Modelo'], $row['Ubicacion'],
        ]), 'UTF-8');
        return ($search === '' || mb_strpos($haystack, $search, 0, 'UTF-8') !== false)
            && ($nivel === '' || $row['NivelEfectivo'] === $nivel)
            && ($estado === '' || strtoupper(trim($row['Estado'])) === $estado);
    }));
}

try {
    if (!class_exists('ZipArchive')) throw new RuntimeException('El servidor no tiene habilitada la extensión ZIP.');
    $rows = eqx_filtered_rows(getHanaPdo());
    if (!$rows) throw new RuntimeException('No hay equipos para los filtros seleccionados.');

    foreach ($rows as $i => &$row) {
        $row['Item'] = $i + 1;
        $row['Comentarios'] = '';
        $row['Registro'] = trim(($row['CodRegistro'] ?? '') . (($row['CodRegistro'] ?? '') !== '' && ($row['RegistroName'] ?? '') !== '' ? ' - ' : '') . ($row['RegistroName'] ?? ''));
    }
    unset($row);

    $headers = ['ITEM','COD TIPO','CÓDIGO DE CONTABILIDAD','TIPO EMH','NOMBRE DEL EQUIPO','MARCA','MODELO','SERIE','FECHA DE ADQUISICIÓN','CRITICIDAD','NIVEL DE CRITICIDAD','UBICACIÓN','ESTADO','FECHA DE BAJA','VIDA ÚTIL','COMENTARIOS','REGISTRO'];
    $keys = ['Item','Cod_Tipo','CodComtaSap','TIP_EMH','ItemName','Marca','Modelo','AssetSerNo','Fecha_Adq','Criterio','NivelEfectivo','Ubicacion','Estado','Fecha_Baja','Vida_Util','Comentarios','Registro'];
    $widths = [8,14,23,18,34,16,18,17,17,16,19,26,14,15,11,28,24];
    $sheetRows = [];

    $docRows = [
        1 => ['C' => ['SISTEMA INTEGRADO DE GESTIÓN', 5], 'P' => ['FECHA:', 6], 'Q' => [date('d/m/Y'), 7]],
        3 => ['C' => ['CATÁLOGO DE EQUIPOS SAP', 16]],
    ];
    for ($r = 1; $r <= 5; $r++) {
        $cells = [];
        for ($c = 1; $c <= 17; $c++) {
            $col = eqx_col($c);
            $meta = $docRows[$r][$col] ?? ['', 7];
            $cells[] = eqx_cell($col . $r, $meta[0], $meta[1]);
        }
        $sheetRows[] = '<row r="' . $r . '" ht="' . ($r === 1 || $r === 3 ? 24 : 19) . '" customHeight="1">' . implode('', $cells) . '</row>';
    }
    $sheetRows[] = '<row r="6" ht="8" customHeight="1"/>';
    $cells = [];
    foreach ($headers as $i => $label) $cells[] = eqx_cell(eqx_col($i + 1) . '7', $label, 1);
    $sheetRows[] = '<row r="7" ht="30" customHeight="1">' . implode('', $cells) . '</row>';

    foreach ($rows as $i => $row) {
        $excelRow = $i + 8;
        $cells = [];
        foreach ($keys as $j => $key) {
            $style = 2;
            $number = false;
            $value = $row[$key] ?? '';
            if ($key === 'Item') { $style = 8; $number = true; }
            elseif ($key === 'NivelEfectivo') { $style = eqx_level_style((string)$value); }
            elseif ($key === 'Estado') { $style = eqx_estado_style((string)$value); }
            elseif ($key === 'Riesgo_EVA') {
                $style = 14; $number = true;
                $value = ($row['NivelEfectivo'] === 'SIN EVALUAR' || $value === '') ? '' : ((float)$value) / 100;
            }
            elseif (in_array($key, ['Cod_Tipo', 'CodComtaSap', 'TIP_EMH', 'AssetSerNo', 'Fecha_Adq', 'Fecha_Baja', 'Vida_Util', 'Registro'], true)) { $style = 3; }
            $cells[] = eqx_cell(eqx_col($j + 1) . $excelRow, $value, $style, $number);
        }
        $sheetRows[] = '<row r="' . $excelRow . '" ht="24" customHeight="1">' . implode('', $cells) . '</row>';
    }

    $lastRow = count($rows) + 7;
    $cols = '';
    foreach ($widths as $i => $width) $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $width . '" customWidth="1"/>';
    $merges = ['A1:B5', 'C1:O2', 'C3:O5'];
    $mergeXml = '';
    foreach ($merges as $merge) $mergeXml .= '<mergeCell ref="' . $merge . '"/>';
    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetViews><sheetView showGridLines="0" workbookViewId="0"><pane xSplit="3" ySplit="7" topLeftCell="D8" activePane="bottomRight" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="18"/><cols>' . $cols . '</cols><sheetData>' . implode('', $sheetRows) . '</sheetData><autoFilter ref="A7:Q' . $lastRow . '"/><mergeCells count="' . count($merges) . '">' . $mergeXml . '</mergeCells><pageMargins left="0.2" right="0.2" top="0.35" bottom="0.35" header="0.15" footer="0.15"/><pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/><drawing r:id="rId1"/></worksheet>';

    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="0%"/></numFmts><fonts count="5"><font><sz val="9"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="9"/><name val="Arial"/></font><font><b/><sz val="12"/><name val="Arial"/></font><font><b/><sz val="10"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="9"/><name val="Arial"/></font></fonts><fills count="9"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF4472C4"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF8EA9DB"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFF5055"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFC000"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF70AD47"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF9EABB4"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9E2F3"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"><color rgb="FF202020"/></left><right style="thin"><color rgb="FF202020"/></right><top style="thin"><color rgb="FF202020"/></top><bottom style="thin"><color rgb="FF202020"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="17"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="164" fontId="3" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="1" fillId="7" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="8" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="164" fontId="3" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    $drawing = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><xdr:twoCellAnchor editAs="oneCell"><xdr:from><xdr:col>0</xdr:col><xdr:colOff>90000</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>60000</xdr:rowOff></xdr:from><xdr:to><xdr:col>2</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>5</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="Logo TAMA"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:xfrm/><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:twoCellAnchor></xdr:wsDr>';

    $tmp = tempnam(sys_get_temp_dir(), 'eqx_');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('No se pudo crear el archivo Excel.');
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Equipos" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>');
    $zip->addFromString('xl/drawings/drawing1.xml', $drawing);
    $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/logo.png"/></Relationships>');
    $logo = dirname(dirname(__DIR__)) . '/assets/img/logo.png';
    if (!is_file($logo)) throw new RuntimeException('No se encontró el logo institucional.');
    $zip->addFile($logo, 'xl/media/logo.png');
    $zip->addFromString('xl/styles.xml', $styles);
    $zip->close();

    while (ob_get_level()) ob_end_clean();
    $filename = 'Catalogo_Equipos_SAP_' . date('Y-m-d_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($tmp);
    @unlink($tmp);
} catch (Throwable $e) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No se pudo generar el Excel: ' . $e->getMessage();
}
