<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once __DIR__ . '/conexion_hana.php';

if (!$session->isUserLoggedIn(false)) { http_response_code(401); exit('No autorizado.'); }
if (!tiene_permiso('mantenimientos', 'ver')) { http_response_code(403); exit('Sin permiso para exportar.'); }

function crx_xml($value): string {
    $text = (string)($value ?? '');
    $text = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $text) ?? '';
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
function crx_col(int $number): string {
    $name = '';
    while ($number > 0) { $number--; $name = chr(65 + ($number % 26)) . $name; $number = intdiv($number, 26); }
    return $name;
}
function crx_cell(string $ref, $value, int $style = 2, bool $number = false): string {
    if ($number && $value !== null && $value !== '' && is_numeric($value)) {
        return '<c r="'.$ref.'" s="'.$style.'"><v>'.(0 + $value).'</v></c>';
    }
    return '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.crx_xml($value).'</t></is></c>';
}
function crx_level_style(string $level): int {
    return match (strtoupper(trim($level))) { 'ALTO' => 9, 'MEDIO' => 10, 'BAJO' => 11, default => 12 };
}
function crx_filtered_rows(PDO $pdo): array {
    $equipment = $pdo->query(<<<'SQL'
SELECT
    E."Code", COALESCE(NULLIF(TRIM(E."Name"), ''), E."Code") AS "ItemCode",
    COALESCE(NULLIF(TRIM(E."U_ItemNameEq"), ''), E."Name") AS "ItemName",
    COALESCE(NULLIF(TRIM(E."U_Cod_Tipo"), ''), 'SIN-CODIGO') AS "FamilyCode",
    COALESCE(NULLIF(TRIM(E."U_TIP_EMH"), ''), 'SIN FAMILIA') AS "FamilyName",
    E."U_F1_EVA" AS "F1", E."U_R_EVA" AS "R", E."U_SHA_EVA" AS "SHA",
    E."U_IC_EVA" AS "IC", E."U_IP_EVA" AS "IP", E."U_BM_EVA" AS "BM", E."U_CM_EVA" AS "CM"
FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" E
ORDER BY "FamilyCode", "ItemCode"
SQL)->fetchAll(PDO::FETCH_ASSOC);

    $planRows = $pdo->query(
        'SELECT "U_ItemCode" AS "ItemCode", "U_Frecuencia" AS "Frecuencia"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_PROGMANT"'
        . ' WHERE COALESCE("U_Activo", \'Y\') <> \'N\' AND COALESCE("U_ItemCode", \'\') <> \'\''
    )->fetchAll(PDO::FETCH_ASSOC);
    $frequencies = [];
    foreach ($planRows as $plan) {
        $item = trim((string)($plan['ItemCode'] ?? ''));
        $frequency = trim((string)($plan['Frecuencia'] ?? ''));
        if ($item !== '' && $frequency !== '') $frequencies[$item][$frequency] = true;
    }

    $tareoRows = $pdo->query(
        'SELECT TRIM("U_EQUIPO_TM") AS "ItemCode", COUNT(*) AS "Registros", SUM(COALESCE("U_HORAS",0)) AS "Horas"'
        . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_TAREO_OT_CIF"'
        . ' WHERE COALESCE(TRIM("U_EQUIPO_TM"), \'\') <> \'\' GROUP BY TRIM("U_EQUIPO_TM")'
    )->fetchAll(PDO::FETCH_ASSOC);
    $tareo = [];
    foreach ($tareoRows as $row) $tareo[trim((string)$row['ItemCode'])] = ['registros'=>(int)$row['Registros'], 'horas'=>round((float)$row['Horas'], 2)];

    $weekMap = ['SEMANAL'=>1,'QUINCENAL'=>2,'MENSUAL'=>4,'BIMESTRAL'=>8,'TRIMESTRAL'=>13,'CUATRIMESTRAL'=>17,'SEMESTRAL'=>26,'ANUAL'=>52];
    $rows = [];
    foreach ($equipment as $row) {
        $scores = [];
        foreach (['F1','R','SHA','IC','IP','BM','CM'] as $factor) {
            $value = (float)($row[$factor] ?? 0);
            $scores[$factor] = $value >= 1 && $value <= 5 ? $value : 0;
        }
        $evaluated = count(array_filter($scores, static fn(float $v): bool => $v > 0)) === 7;
        $failure = $evaluated ? ($scores['F1'] + $scores['R']) / 2 : 0;
        $consequence = $evaluated ? 0.2 * ($scores['SHA'] + $scores['IC'] + $scores['IP'] + $scores['BM'] + $scores['CM']) : 0;
        $risk = $evaluated ? ($failure * $consequence / 25) * 100 : 0;
        $level = !$evaluated ? 'SIN EVALUAR' : ($risk > 36.99 ? 'ALTO' : ($risk >= 16.99 ? 'MEDIO' : 'BAJO'));
        $item = trim((string)($row['ItemCode'] ?? ''));
        $itemFreq = array_keys($frequencies[$item] ?? []); natcasesort($itemFreq);
        $freqWeeks = [];
        foreach ($itemFreq as $frequency) { $weeks=$weekMap[strtoupper(trim($frequency))]??null; $freqWeeks[]=$weeks===null?$frequency:$frequency.' ('.$weeks.')'; }
        $time = $tareo[$item] ?? ['registros'=>0,'horas'=>0];
        $rows[] = [
            'familia_codigo'=>trim((string)$row['FamilyCode']), 'familia_nombre'=>trim((string)$row['FamilyName']),
            'codigo'=>$item, 'nombre'=>trim((string)$row['ItemName']), 'criticidad'=>$level, 'riesgo'=>round($risk,2),
            'falla_relevancia'=>round($failure,2), 'consecuencia'=>round($consequence,2),
            'frecuencia'=>implode(', ',$itemFreq), 'frecuencia_semanas'=>implode(', ',$freqWeeks),
            'tareo_registros'=>$time['registros'], 'tareo_horas'=>$time['horas'],
            'F1'=>$scores['F1']?:null, 'R'=>$scores['R']?:null, 'SHA'=>$scores['SHA']?:null,
            'IC'=>$scores['IC']?:null, 'IP'=>$scores['IP']?:null, 'BM'=>$scores['BM']?:null, 'CM'=>$scores['CM']?:null,
        ];
    }

    $search = mb_strtolower(trim((string)($_GET['buscar'] ?? '')), 'UTF-8');
    $family = trim((string)($_GET['familia'] ?? ''));
    $level = strtoupper(trim((string)($_GET['nivel'] ?? '')));
    return array_values(array_filter($rows, static function(array $row) use ($search,$family,$level): bool {
        $familyKey = $row['familia_codigo'].'|'.$row['familia_nombre'];
        $haystack = mb_strtolower(implode(' ', [$row['familia_codigo'],$row['familia_nombre'],$row['codigo'],$row['nombre']]), 'UTF-8');
        return ($search === '' || mb_strpos($haystack,$search,0,'UTF-8') !== false)
            && ($family === '' || $familyKey === $family)
            && ($level === '' || $row['criticidad'] === $level);
    }));
}

try {
    if (!class_exists('ZipArchive')) throw new RuntimeException('El servidor no tiene habilitada la extensión ZIP.');
    $rows = crx_filtered_rows(getHanaPdo());
    if (!$rows) throw new RuntimeException('No hay equipos para los filtros seleccionados.');

    $headers = ['FAMILIA','CÓDIGO EQUIPO','NOMBRE DEL EQUIPO','NIVEL DE CRITICIDAD','RIESGO','FRECUENCIA DE FALLA + RELEVANCIA EN CASO DE FALLA','CONSECUENCIA (C)','LIBRE','FRECUENCIA DEL PLAN','FRECUENCIA (SEMANAS)','TAREO REGISTROS','TAREO HORAS','F1','R','SHA','IC','IP','BM','CM'];
    $keys = ['familia','codigo','nombre','criticidad','riesgo','falla_relevancia','consecuencia','libre','frecuencia','frecuencia_semanas','tareo_registros','tareo_horas','F1','R','SHA','IC','IP','BM','CM'];
    $widths = [30,16,48,19,11,28,18,9,22,22,15,13,7,7,7,7,7,7,7];
    $sheetRows = [];
    $docRows = [
        1 => ['C'=>['SISTEMA INTEGRADO DE GESTIÓN',5],'P'=>['T-MT-F-04',6]],
        2 => ['P'=>['FECHA:',6],'Q'=>[date('d/m/Y'),7]],
        3 => ['C'=>['RELACIÓN DE EQUIPOS Y MÁQUINAS CRÍTICAS',16],'P'=>['VERSIÓN:',6],'Q'=>['01',7]],
        4 => ['P'=>['Elaborado:',6],'Q'=>['Revisado:',6],'R'=>['Aprobado:',6]],
        5 => ['P'=>['MT',7],'Q'=>['GO',7],'R'=>['GG',7]],
    ];
    for ($r=1;$r<=5;$r++) {
        $cells=[]; for($c=1;$c<=19;$c++){ $col=crx_col($c); $meta=$docRows[$r][$col]??['',7]; $cells[]=crx_cell($col.$r,$meta[0],$meta[1]); }
        $sheetRows[]='<row r="'.$r.'" ht="'.($r===1||$r===3?24:19).'" customHeight="1">'.implode('',$cells).'</row>';
    }
    $sheetRows[]='<row r="6" ht="8" customHeight="1"/>';
    $cells=[]; foreach($headers as $i=>$label)$cells[]=crx_cell(crx_col($i+1).'7',$label,1); $sheetRows[]='<row r="7" ht="48" customHeight="1">'.implode('',$cells).'</row>';
    foreach($rows as $i=>$row){
        $excelRow=$i+8; $row['familia']=$row['familia_codigo'].' - '.$row['familia_nombre']; $row['libre']=''; $cells=[];
        foreach($keys as $j=>$key){
            $style=2; $number=in_array($key,['riesgo','falla_relevancia','consecuencia','tareo_registros','tareo_horas','F1','R','SHA','IC','IP','BM','CM'],true);
            if($key==='familia')$style=8; elseif($key==='criticidad')$style=crx_level_style((string)$row[$key]); elseif($key==='riesgo')$style=14; elseif(in_array($key,['F1','R','SHA','IC','IP','BM','CM'],true))$style=13; elseif($number||in_array($key,['codigo','libre'],true))$style=3;
            $value=$row[$key]??''; if($key==='riesgo'&&$value!=='')$value=((float)$value)/100;
            $cells[]=crx_cell(crx_col($j+1).$excelRow,$value,$style,$number);
        }
        $sheetRows[]='<row r="'.$excelRow.'" ht="28" customHeight="1">'.implode('',$cells).'</row>';
    }
    $lastRow=count($rows)+7; $cols=''; foreach($widths as $i=>$width)$cols.='<col min="'.($i+1).'" max="'.($i+1).'" width="'.$width.'" customWidth="1"/>';
    $merges=['A1:B5','C1:O2','C3:O5','P1:S1','Q2:S2','Q3:S3','R4:S4','R5:S5']; $mergeXml=''; foreach($merges as $merge)$mergeXml.='<mergeCell ref="'.$merge.'"/>';
    $sheetXml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetViews><sheetView showGridLines="0" workbookViewId="0"><pane xSplit="3" ySplit="7" topLeftCell="D8" activePane="bottomRight" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="18"/><cols>'.$cols.'</cols><sheetData>'.implode('',$sheetRows).'</sheetData><autoFilter ref="A7:S'.$lastRow.'"/><mergeCells count="'.count($merges).'">'.$mergeXml.'</mergeCells><pageMargins left="0.2" right="0.2" top="0.35" bottom="0.35" header="0.15" footer="0.15"/><pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/><drawing r:id="rId1"/></worksheet>';

    $styles='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="0%"/></numFmts><fonts count="5"><font><sz val="9"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="9"/><name val="Arial"/></font><font><b/><sz val="12"/><name val="Arial"/></font><font><b/><sz val="10"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="9"/><name val="Arial"/></font></fonts><fills count="9"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF4472C4"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF8EA9DB"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFF5055"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFC000"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF70AD47"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF9EABB4"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9E2F3"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"><color rgb="FF202020"/></left><right style="thin"><color rgb="FF202020"/></right><top style="thin"><color rgb="FF202020"/></top><bottom style="thin"><color rgb="FF202020"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="17"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="164" fontId="3" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="1" fillId="7" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="8" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="164" fontId="3" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    $drawing='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><xdr:twoCellAnchor editAs="oneCell"><xdr:from><xdr:col>0</xdr:col><xdr:colOff>90000</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>60000</xdr:rowOff></xdr:from><xdr:to><xdr:col>2</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>5</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="Logo TAMA"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:xfrm/><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:twoCellAnchor></xdr:wsDr>';

    $tmp=tempnam(sys_get_temp_dir(),'crit_'); $zip=new ZipArchive(); if($zip->open($tmp,ZipArchive::OVERWRITE)!==true)throw new RuntimeException('No se pudo crear el archivo Excel.');
    $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/></Types>');
    $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Criticidad" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->addFromString('xl/worksheets/sheet1.xml',$sheetXml);
    $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>');
    $zip->addFromString('xl/drawings/drawing1.xml',$drawing);
    $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/logo.png"/></Relationships>');
    $logo=dirname(dirname(__DIR__)).'/assets/img/logo.png'; if(!is_file($logo))throw new RuntimeException('No se encontró el logo institucional.'); $zip->addFile($logo,'xl/media/logo.png');
    $zip->addFromString('xl/styles.xml',$styles); $zip->close();
    while(ob_get_level())ob_end_clean(); $filename='Relacion_Equipos_Maquinas_Criticas_'.date('Y-m-d_His').'.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="'.$filename.'"'); header('Content-Length: '.filesize($tmp)); header('Cache-Control: private, max-age=0, must-revalidate'); readfile($tmp); @unlink($tmp);
} catch(Throwable $e) {
    while(ob_get_level())ob_end_clean(); http_response_code(500); header('Content-Type: text/plain; charset=utf-8'); echo 'No se pudo generar el Excel: '.$e->getMessage();
}
