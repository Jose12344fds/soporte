<?php
/** Exportacion XLSX completa del modulo Activos. */
ob_start();
error_reporting(0);
ini_set('display_errors', '0');
require_once __DIR__ . '/../includes/load.php';
require_once __DIR__ . '/../public/ajax/conexion.php';

if (!$session->isUserLoggedIn(true)) { http_response_code(401); exit('Sesion no valida.'); }
if (!tiene_permiso('activos', 'ver')) { http_response_code(403); exit('Sin permiso para exportar activos.'); }

function ax_xml($value): string {
    $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string)$value);
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
function ax_col(int $number): string {
    $name = '';
    while ($number > 0) { $number--; $name = chr(65 + ($number % 26)) . $name; $number = intdiv($number, 26); }
    return $name;
}
function ax_date_serial(string $value): ?float {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) return null;
    $timestamp = strtotime(substr($value, 0, 10) . ' 00:00:00 UTC');
    return $timestamp === false ? null : ($timestamp / 86400) + 25569;
}
function ax_cell(string $ref, $value, string $type = 'text', int $style = 2): string {
    if ($value === null) $value = '';
    if ($type === 'date' && ($serial = ax_date_serial((string)$value)) !== null) {
        return '<c r="'.$ref.'" s="4"><v>'.$serial.'</v></c>';
    }
    if ($type === 'number' && $value !== '' && is_numeric($value)) {
        return '<c r="'.$ref.'" s="'.$style.'"><v>'.(0 + $value).'</v></c>';
    }
    return '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.ax_xml($value).'</t></is></c>';
}
function ax_sheet(string $title, array $columns, array $rows, bool $officialHeader = false, bool $withLogo = false): string {
    $lastColumn = ax_col(max(1, count($columns)));
    $headerRow = $officialHeader ? 7 : 4;
    $dataStartRow = $headerRow + 1;
    $lastRow = max($headerRow, count($rows) + $headerRow);
    $xmlRows = [];

    if ($officialHeader) {
        $officialRows = [
            1 => ['C'=>['SISTEMA INTEGRADO DE GESTIÓN',7], 'P'=>['T-TI-F-02',9]],
            2 => ['P'=>['FECHA',9], 'Q'=>['2/03/2020',10]],
            3 => ['C'=>['INVENTARIO DE ACTIVOS FISICOS DE SISTEMAS  ',8], 'P'=>['VERSIÓN',9], 'Q'=>['1',10]],
            4 => ['P'=>['Elaborado',9], 'Q'=>['Revisado',9], 'R'=>['Aprobado',9]],
            5 => ['P'=>['TI',10], 'Q'=>['GG',10], 'R'=>['GG',10]],
        ];
        foreach ($officialRows as $rowNumber => $definedCells) {
            $cells = []; 
            for ($columnNumber = 1; $columnNumber <= count($columns); $columnNumber++) {
                $columnName = ax_col($columnNumber);
                $value = $definedCells[$columnName][0] ?? '';
                $style = $definedCells[$columnName][1] ?? 10;
                $cells[] = ax_cell($columnName.$rowNumber, $value, 'text', $style);
            }
            $height = in_array($rowNumber, [1,3], true) ? 24 : 20;
            $xmlRows[] = '<row r="'.$rowNumber.'" ht="'.$height.'" customHeight="1">'.implode('', $cells).'</row>';
        }
        $xmlRows[] = '<row r="6" ht="8" customHeight="1"/>';
    } else {
        $xmlRows[] = '<row r="1" ht="28" customHeight="1">'.ax_cell('A1', $title, 'text', 5).'</row>';
        $xmlRows[] = '<row r="2" ht="19" customHeight="1">'.ax_cell('A2', 'Generado: '.date('d/m/Y H:i').' | Registros: '.count($rows), 'text', 6).'</row>';
    }

    $cells = [];
    foreach ($columns as $i => $column) $cells[] = ax_cell(ax_col($i + 1).$headerRow, $column['label'], 'text', 1);
    $xmlRows[] = '<row r="'.$headerRow.'" ht="42" customHeight="1">'.implode('', $cells).'</row>';
    foreach ($rows as $rowIndex => $row) {
        $excelRow = $rowIndex + $dataStartRow; $cells = [];
        foreach ($columns as $columnIndex => $column) {
            $cells[] = ax_cell(ax_col($columnIndex + 1).$excelRow, $row[$column['key']] ?? '', $column['type'] ?? 'text', 2);
        }
        $xmlRows[] = '<row r="'.$excelRow.'" ht="27" customHeight="1">'.implode('', $cells).'</row>';
    }
    $cols = '';
    foreach ($columns as $i => $column) {
        $pos = $i + 1; $width = max(10, min(45, (float)($column['width'] ?? 18)));
        $cols .= '<col min="'.$pos.'" max="'.$pos.'" width="'.$width.'" customWidth="1"/>';
    }
    $merges = $officialHeader
        ? ['A1:B5','C1:O2','C3:O5','P1:S1','Q2:S2','Q3:S3','R4:S4','R5:S5']
        : ['A1:'.$lastColumn.'1','A2:'.$lastColumn.'2'];
    $mergeXml = '';
    foreach ($merges as $merge) $mergeXml .= '<mergeCell ref="'.$merge.'"/>';
    $drawingXml = $withLogo ? '<drawing r:id="rId1"/>' : '';
    $relationshipNamespace = $withLogo ? ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"' : '';
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'.$relationshipNamespace.'>'
        .'<sheetViews><sheetView showGridLines="0" workbookViewId="0"><pane ySplit="'.$headerRow.'" topLeftCell="A'.$dataStartRow.'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        .'<sheetFormatPr defaultRowHeight="18"/><cols>'.$cols.'</cols><sheetData>'.implode('', $xmlRows).'</sheetData>'
        .'<autoFilter ref="A'.$headerRow.':'.$lastColumn.$lastRow.'"/>'
        .'<mergeCells count="'.count($merges).'">'.$mergeXml.'</mergeCells>'
        .'<pageMargins left="0.25" right="0.25" top="0.4" bottom="0.4" header="0.2" footer="0.2"/>'
        .'<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/>'.$drawingXml.'</worksheet>';
}

function ax_type(string $mysqlType): string {
    if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|numeric|float|double)/i', $mysqlType)) return 'number';
    if (preg_match('/^date(\(|$)/i', $mysqlType)) return 'date';
    return 'text';
}
function ax_html_fallback(array $assetColumns, array $assets, array $historyColumns, array $history): void {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Activos_Data_Completa_'.date('Y-m-d_His').'.xls"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    echo '<html><head><meta charset="UTF-8"></head><body><style>table{border-collapse:collapse;font-family:Arial;font-size:9pt;margin-bottom:28px}th,td{border:1px solid #b7c6ce;padding:5px;vertical-align:top}th{background:#087f8c;color:#fff;font-weight:bold}h2{background:#173a5e;color:#fff;padding:10px}</style>';
    foreach ([['INVENTARIO COMPLETO DE ACTIVOS Y VALORACION DE SEGURIDAD',$assetColumns,$assets],['HISTORIAL COMPLETO DE ASIGNACIONES',$historyColumns,$history]] as $sheet) {
        echo '<h2>'.htmlspecialchars($sheet[0],ENT_QUOTES,'UTF-8').'</h2><table><thead><tr>';
        foreach ($sheet[1] as $column) echo '<th>'.htmlspecialchars($column['label'],ENT_QUOTES,'UTF-8').'</th>';
        echo '</tr></thead><tbody>';
        foreach ($sheet[2] as $row) { echo '<tr>'; foreach ($sheet[1] as $column) echo '<td>'.htmlspecialchars((string)($row[$column['key']] ?? ''),ENT_QUOTES,'UTF-8').'</td>'; echo '</tr>'; }
        echo '</tbody></table>';
    }
    echo '</body></html>';
    exit;
}

try {
    ensure_maquina_ubicacion_schema();
    ensure_asig_maquina_schema();
    ensure_activos_asignacion_historial();
    $pdo = Conexion::Conectar();
    $assetColumns = [
        ['key'=>'id_activo','label'=>'ID ACTIVO','type'=>'number','width'=>11],
        ['key'=>'host','label'=>'EQUIPO / HOST','type'=>'text','width'=>18],
        ['key'=>'tipo_registro','label'=>'TIPO (REGISTRO)','type'=>'text','width'=>16],
        ['key'=>'categoria','label'=>'CATEGORIA','type'=>'text','width'=>18],
        ['key'=>'confidencialidad','label'=>"CONFIDENCIALIDAD\n(1-4)",'type'=>'number','width'=>20],
        ['key'=>'disponibilidad','label'=>"DISPONIBILIDAD\n(1-4)",'type'=>'number','width'=>19],
        ['key'=>'integridad','label'=>'INTEGRIDAD (1-4)','type'=>'number','width'=>17],
        ['key'=>'criticidad','label'=>'CRITICIDAD','type'=>'number','width'=>13],
        ['key'=>'modelo_placa','label'=>'MODELO / PLACA','type'=>'text','width'=>28],
        ['key'=>'memoria_ram','label'=>'MEMORIA RAM (GB)','type'=>'number','width'=>18],
        ['key'=>'tipo_disco','label'=>'TIPO DISCO','type'=>'text','width'=>15],
        ['key'=>'tamano_disco','label'=>"TAMANO DISCO\n(GB)",'type'=>'number','width'=>18],
        ['key'=>'modelo_pantalla','label'=>'MODELO PANTALLA','type'=>'text','width'=>23],
        ['key'=>'ip','label'=>'IP','type'=>'text','width'=>17],
        ['key'=>'fecha_adquisicion','label'=>'FECHA ADQUISICION','type'=>'date','width'=>19],
        ['key'=>'procesador','label'=>'PROCESADOR','type'=>'text','width'=>38],
        ['key'=>'estado_activo','label'=>'ESTADO DEL ACTIVO','type'=>'text','width'=>20],
        ['key'=>'area_codigo','label'=>"CODIGO AREA\nACTIVO",'type'=>'text','width'=>18],
        ['key'=>'usuario_asignado','label'=>'USUARIO ASIGNADO','type'=>'text','width'=>32],
    ];
    $assetsSql = "SELECT m.IdMaquina id_activo,m.Host host,m.Tipo_Activo tipo_registro,
      m.CategoriaEquipo categoria,m.Confidencialidad confidencialidad,m.Disponibilidad disponibilidad,
      m.Integridad integridad,
      CASE WHEN m.Confidencialidad IS NULL AND m.Disponibilidad IS NULL AND m.Integridad IS NULL THEN NULL
           ELSE GREATEST(COALESCE(m.Confidencialidad,0),COALESCE(m.Disponibilidad,0),COALESCE(m.Integridad,0)) END criticidad,
      m.Modelo_Placa modelo_placa,m.Mem_Ram memoria_ram,m.Tipo_Disco tipo_disco,m.Tamano_Disco tamano_disco,
      m.Mod_Pantalla modelo_pantalla,m.Ip ip,m.Fecha_Maqui fecha_adquisicion,m.Procesador procesador,
      m.EstadoMaquina estado_activo,aa.Codigo area_codigo,COALESCE(u.name,'Sin asignar') usuario_asignado
      FROM maquina m
      LEFT JOIN areas_activo aa ON aa.IdAreaActivo=m.IdAreaActivo
      LEFT JOIN (SELECT IdMaquina,MAX(IdAsMaq) IdAsMaq FROM asig_maquina WHERE Estado=1 GROUP BY IdMaquina) ultima ON ultima.IdMaquina=m.IdMaquina
      LEFT JOIN asig_maquina am ON am.IdAsMaq=ultima.IdAsMaq
      LEFT JOIN users u ON u.id=am.id ORDER BY m.IdMaquina";
    $assets = $pdo->query($assetsSql)->fetchAll(PDO::FETCH_ASSOC);

    $historyColumns = [
        ['key'=>'id','label'=>'ID MOVIMIENTO','type'=>'number','width'=>16],['key'=>'IdMaquina','label'=>'ID ACTIVO','type'=>'number','width'=>14],
        ['key'=>'Host','label'=>'EQUIPO / HOST','type'=>'text','width'=>22],['key'=>'TipoActivo','label'=>'TIPO ACTIVO','type'=>'text','width'=>18],
        ['key'=>'accion','label'=>'ACCION','type'=>'text','width'=>17],['key'=>'fecha_asignacion','label'=>'FECHA ASIGNACION','type'=>'date','width'=>18],
        ['key'=>'usuario_anterior_id','label'=>'ID USUARIO ANTERIOR','type'=>'number','width'=>20],['key'=>'usuario_anterior','label'=>'USUARIO ANTERIOR','type'=>'text','width'=>28],
        ['key'=>'usuario_nuevo_id','label'=>'ID USUARIO NUEVO','type'=>'number','width'=>18],['key'=>'usuario_nuevo','label'=>'USUARIO NUEVO','type'=>'text','width'=>28],
        ['key'=>'comentarios','label'=>'COMENTARIOS','type'=>'text','width'=>38],['key'=>'registrado_por','label'=>'ID REGISTRADO POR','type'=>'number','width'=>18],
        ['key'=>'registrado_por_nombre','label'=>'REGISTRADO POR','type'=>'text','width'=>28],['key'=>'created_at','label'=>'FECHA Y HORA DEL REGISTRO','type'=>'text','width'=>22]
    ];
    $historySql = "SELECT h.id,h.IdMaquina,m.Host,COALESCE(t.Nombre,m.Tipo_Activo) TipoActivo,h.accion,h.fecha_asignacion,
      h.usuario_anterior_id,ua.name usuario_anterior,h.usuario_nuevo_id,un.name usuario_nuevo,h.comentarios,h.registrado_por,
      ur.name registrado_por_nombre,h.created_at FROM activos_asignacion_historial h LEFT JOIN maquina m ON m.IdMaquina=h.IdMaquina
      LEFT JOIN tipos_activo t ON t.IdTipoActivo=m.IdTipoActivo LEFT JOIN users ua ON ua.id=h.usuario_anterior_id
      LEFT JOIN users un ON un.id=h.usuario_nuevo_id LEFT JOIN users ur ON ur.id=h.registrado_por ORDER BY h.IdMaquina,h.created_at,h.id";
    $history = $pdo->query($historySql)->fetchAll(PDO::FETCH_ASSOC); $pdo = null;
    // Respaldo para servidores PHP sin ZipArchive: siempre entrega un Excel descargable.
    if (!class_exists('ZipArchive')) ax_html_fallback($assetColumns, $assets, $historyColumns, $history);

    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="yyyy-mm-dd"/></numFmts><fonts count="6"><font><sz val="10"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="10"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="16"/><name val="Calibri"/></font><font><i/><color rgb="FF526675"/><sz val="10"/><name val="Calibri"/></font><font><b/><color rgb="FF000000"/><sz val="14"/><name val="Calibri"/></font><font><b/><color rgb="FF000000"/><sz val="10"/><name val="Calibri"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF087F8C"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF173A5E"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"><color rgb="FF000000"/></left><right style="thin"><color rgb="FF000000"/></right><top style="thin"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="11"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    $sheets = [
        ['name'=>'Activos completos','xml'=>ax_sheet('REGISTRO DE FICHA TECNICA DE EQUIPOS',$assetColumns,$assets,true,true)],
        ['name'=>'Historial asignaciones','xml'=>ax_sheet('HISTORIAL COMPLETO DE ASIGNACIONES DE ACTIVOS',$historyColumns,$history)]
    ];
    $logoPath = dirname(__DIR__).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'logo.png';
    if (!is_file($logoPath) || !is_readable($logoPath)) throw new RuntimeException('No se encontró el logotipo institucional.');
    $drawingXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><xdr:twoCellAnchor editAs="oneCell"><xdr:from><xdr:col>0</xdr:col><xdr:colOff>70000</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>70000</xdr:rowOff></xdr:from><xdr:to><xdr:col>2</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>5</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="Logo TAMA"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:xfrm/><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:twoCellAnchor></xdr:wsDr>';
    $temp = tempnam(sys_get_temp_dir(), 'activos_'); $zip = new ZipArchive();
    if ($zip->open($temp, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('No se pudo crear el archivo Excel.');
    $types = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
    $workbookSheets = ''; $workbookRels = '';
    foreach ($sheets as $index => $sheet) {
        $n = $index + 1; $types .= '<Override PartName="/xl/worksheets/sheet'.$n.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $workbookSheets .= '<sheet name="'.ax_xml($sheet['name']).'" sheetId="'.$n.'" r:id="rId'.$n.'"/>';
        $workbookRels .= '<Relationship Id="rId'.$n.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$n.'.xml"/>';
        $zip->addFromString('xl/worksheets/sheet'.$n.'.xml', $sheet['xml']);
    }
    $types .= '</Types>'; $styleId = count($sheets) + 1;
    $workbookRels .= '<Relationship Id="rId'.$styleId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
    $zip->addFromString('[Content_Types].xml',$types);
    $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$workbookSheets.'</sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$workbookRels.'</Relationships>');
    $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>');
    $zip->addFromString('xl/drawings/drawing1.xml',$drawingXml);
    $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/logo.png"/></Relationships>');
    $zip->addFile($logoPath,'xl/media/logo.png');
    $zip->addFromString('xl/styles.xml',$stylesXml); $zip->close();
    while (ob_get_level()) ob_end_clean();
    $filename = 'Activos_Data_Completa_'.date('Y-m-d_His').'.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"'); header('Content-Length: '.filesize($temp));
    header('Cache-Control: private, max-age=0, must-revalidate'); readfile($temp); @unlink($temp);
} catch (Throwable $e) {
    while (ob_get_level()) ob_end_clean(); http_response_code(500); header('Content-Type: text/plain; charset=utf-8');
    echo 'No se pudo generar el Excel de activos: '.$e->getMessage();
}
