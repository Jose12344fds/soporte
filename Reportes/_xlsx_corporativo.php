<?php
declare(strict_types=1);

function tx_xml($value): string {
    $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string)($value ?? '')) ?? '';
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function tx_col(int $number): string {
    $name = '';
    while ($number > 0) { $number--; $name = chr(65 + ($number % 26)) . $name; $number = intdiv($number, 26); }
    return $name;
}

function tx_date_serial(string $value): ?float {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) return null;
    $timestamp = strtotime(substr($value, 0, 10) . ' 00:00:00 UTC');
    return $timestamp === false ? null : ($timestamp / 86400) + 25569;
}

function tx_cell(string $ref, $value, string $type = 'text', int $style = 2): string {
    if ($value === null) $value = '';
    if ($type === 'date' && ($serial = tx_date_serial((string)$value)) !== null) {
        return '<c r="'.$ref.'" s="4"><v>'.$serial.'</v></c>';
    }
    if ($type === 'number' && $value !== '' && is_numeric($value)) {
        return '<c r="'.$ref.'" s="'.$style.'"><v>'.(0 + $value).'</v></c>';
    }
    if ($type === 'boolean') {
        $text = ((string)$value === '1' || $value === true) ? 'SÍ' : 'NO';
        return '<c r="'.$ref.'" s="3" t="inlineStr"><is><t>'.tx_xml($text).'</t></is></c>';
    }
    $value = (string)$value;
    if (mb_strlen($value, 'UTF-8') > 32767) {
        $value = mb_substr($value, 0, 32766, 'UTF-8').'…';
    }
    return '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.tx_xml($value).'</t></is></c>';
}

function tx_sheet_xml(array $sheet): string {
    $columns = $sheet['columns']; $rows = $sheet['rows'];
    $realCount = count($columns); $layoutCount = max($realCount, (int)($sheet['layoutCount'] ?? 19)); $lastLayout = tx_col($layoutCount); $lastData = tx_col($realCount);
    $metaStart = $layoutCount - 3; $meta1 = tx_col($metaStart); $meta2 = tx_col($metaStart + 1); $meta3 = tx_col($metaStart + 2); $meta4 = tx_col($metaStart + 3);
    $titleEnd = tx_col($metaStart - 1); $xmlRows = [];
    $docRows = [
        1 => ['C'=>['SISTEMA INTEGRADO DE GESTIÓN',7], $meta1=>[$sheet['code'] ?? 'T-TI-F-02',9]],
        2 => [$meta1=>['FECHA',9], $meta2=>[$sheet['date'] ?? date('d/m/Y'),10]],
        3 => ['C'=>[$sheet['title'],8], $meta1=>['VERSIÓN',9], $meta2=>[$sheet['version'] ?? '01',10]],
        4 => [$meta1=>['Elaborado',9], $meta2=>['Revisado',9], $meta3=>['Aprobado',9]],
        5 => [$meta1=>[$sheet['elaborado'] ?? 'TI',10], $meta2=>[$sheet['revisado'] ?? 'GG',10], $meta3=>[$sheet['aprobado'] ?? 'GG',10]],
    ];
    for ($row=1; $row<=5; $row++) {
        $cells=[];
        for($column=1;$column<=$layoutCount;$column++){
            $name=tx_col($column); $meta=$docRows[$row][$name]??['',10]; $cells[]=tx_cell($name.$row,$meta[0],'text',$meta[1]);
        }
        $xmlRows[]='<row r="'.$row.'" ht="'.(in_array($row,[1,3],true)?24:20).'" customHeight="1">'.implode('',$cells).'</row>';
    }
    $groups = $sheet['groups'] ?? [];
    $headerRow = $groups ? 8 : 7;
    $dataStart = $headerRow + 1;
    if ($groups) {
        $cells=[];
        foreach($columns as $i=>$column)$cells[]=tx_cell(tx_col($i+1).'7','', 'text',1);
        foreach($groups as $group)$cells[(int)$group['start']-1]=tx_cell(tx_col((int)$group['start']).'7',$group['label'],'text',1);
        $xmlRows[]='<row r="7" ht="28" customHeight="1">'.implode('',$cells).'</row>';
    }
    $cells=[]; foreach($columns as $i=>$column)$cells[]=tx_cell(tx_col($i+1).$headerRow,$column['label'],'text',1);
    $xmlRows[]='<row r="'.$headerRow.'" ht="52" customHeight="1">'.implode('',$cells).'</row>';
    foreach($rows as $index=>$row){
        $excelRow=$index+$dataStart; $cells=[];
        foreach($columns as $i=>$column){
            $type=$column['type']??'text'; $style=$type==='number'?3:2;
            $cells[]=tx_cell(tx_col($i+1).$excelRow,$row[$column['key']]??'',$type,$style);
        }
        $xmlRows[]='<row r="'.$excelRow.'" ht="'.(float)($sheet['rowHeight'] ?? 27).'" customHeight="1">'.implode('',$cells).'</row>';
    }
    $cols='';
    foreach($columns as $i=>$column){$width=max(9,min(48,(float)($column['width']??18)));$cols.='<col min="'.($i+1).'" max="'.($i+1).'" width="'.$width.'" customWidth="1"/>';}
    for($i=$realCount+1;$i<=$layoutCount;$i++)$cols.='<col min="'.$i.'" max="'.$i.'" width="10" customWidth="1"/>';
    $merges=['A1:B5','C1:'.$titleEnd.'2','C3:'.$titleEnd.'5',$meta1.'1:'.$lastLayout.'1',$meta2.'2:'.$lastLayout.'2',$meta2.'3:'.$lastLayout.'3',$meta3.'4:'.$lastLayout.'4',$meta3.'5:'.$lastLayout.'5'];
    foreach($groups as $group)$merges[]=tx_col((int)$group['start']).'7:'.tx_col((int)$group['end']).'7';
    $mergeXml='';foreach($merges as $merge)$mergeXml.='<mergeCell ref="'.$merge.'"/>';
    $lastRow=max($headerRow,count($rows)+$headerRow);
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetViews><sheetView showGridLines="0" workbookViewId="0"><pane ySplit="'.$headerRow.'" topLeftCell="A'.($headerRow+1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="18"/><cols>'.$cols.'</cols><sheetData>'.implode('',$xmlRows).'</sheetData><autoFilter ref="A'.$headerRow.':'.$lastData.$lastRow.'"/><mergeCells count="'.count($merges).'">'.$mergeXml.'</mergeCells><pageMargins left="0.25" right="0.25" top="0.4" bottom="0.4" header="0.2" footer="0.2"/><pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/><drawing r:id="rId1"/></worksheet>';
}

function tx_export(array $sheets, string $filename, string $logoPath): void {
    if (!class_exists('ZipArchive')) throw new RuntimeException('El servidor no tiene habilitada la extensión ZIP.');
    if (!is_file($logoPath) || !is_readable($logoPath)) throw new RuntimeException('No se encontró el logotipo institucional.');
    $styles='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="yyyy-mm-dd"/></numFmts><fonts count="6"><font><sz val="10"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="9"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="15"/><name val="Arial"/></font><font><i/><color rgb="FF526675"/><sz val="10"/><name val="Arial"/></font><font><b/><color rgb="FF000000"/><sz val="13"/><name val="Arial"/></font><font><b/><color rgb="FF000000"/><sz val="9"/><name val="Arial"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF087F8C"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF173A5E"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"><color rgb="FF9AAEB8"/></left><right style="thin"><color rgb="FF9AAEB8"/></right><top style="thin"><color rgb="FF9AAEB8"/></top><bottom style="thin"><color rgb="FF9AAEB8"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="11"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    $temp=tempnam(sys_get_temp_dir(),'txlsx_'); $zip=new ZipArchive();
    if($zip->open($temp,ZipArchive::OVERWRITE)!==true)throw new RuntimeException('No se pudo crear el archivo Excel.');
    $types='<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
    $workbookSheets='';$workbookRels='';
    foreach($sheets as $index=>$sheet){
        $n=$index+1;$types.='<Override PartName="/xl/worksheets/sheet'.$n.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/drawings/drawing'.$n.'.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
        $workbookSheets.='<sheet name="'.tx_xml($sheet['name']).'" sheetId="'.$n.'" r:id="rId'.$n.'"/>';
        $workbookRels.='<Relationship Id="rId'.$n.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$n.'.xml"/>';
        $zip->addFromString('xl/worksheets/sheet'.$n.'.xml',tx_sheet_xml($sheet));
        $zip->addFromString('xl/worksheets/_rels/sheet'.$n.'.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing'.$n.'.xml"/></Relationships>');
        $drawing='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><xdr:twoCellAnchor editAs="oneCell"><xdr:from><xdr:col>0</xdr:col><xdr:colOff>70000</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>70000</xdr:rowOff></xdr:from><xdr:to><xdr:col>2</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>5</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="Logo TAMA"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:xfrm/><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:twoCellAnchor>';
        $drawingRels='<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/logo.png"/>';
        foreach(($sheet['images'] ?? []) as $imageIndex=>$image){
            $imagePath=(string)($image['path']??''); if(!is_file($imagePath)||!is_readable($imagePath))continue;
            $rid=$imageIndex+2;$picId=$imageIndex+2;$from=$image['from']??['col'=>0,'row'=>8];$to=$image['to']??['col'=>9,'row'=>28];
            $drawing.='<xdr:twoCellAnchor editAs="oneCell"><xdr:from><xdr:col>'.(int)($from['col']??0).'</xdr:col><xdr:colOff>50000</xdr:colOff><xdr:row>'.(int)($from['row']??8).'</xdr:row><xdr:rowOff>50000</xdr:rowOff></xdr:from><xdr:to><xdr:col>'.(int)($to['col']??9).'</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>'.(int)($to['row']??28).'</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="'.$picId.'" name="'.tx_xml($image['name']??('Grafico '.$picId)).'"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId'.$rid.'"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:xfrm/><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:twoCellAnchor>';
            $mediaName='sheet'.$n.'_image'.$rid.'.png';$zip->addFile($imagePath,'xl/media/'.$mediaName);$drawingRels.='<Relationship Id="rId'.$rid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/'.$mediaName.'"/>';
        }
        $drawing.='</xdr:wsDr>';$drawingRels.='</Relationships>';
        $zip->addFromString('xl/drawings/drawing'.$n.'.xml',$drawing);
        $zip->addFromString('xl/drawings/_rels/drawing'.$n.'.xml.rels',$drawingRels);
    }
    $types.='</Types>';$styleId=count($sheets)+1;$workbookRels.='<Relationship Id="rId'.$styleId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
    $zip->addFromString('[Content_Types].xml',$types);
    $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$workbookSheets.'</sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$workbookRels.'</Relationships>');
    $zip->addFile($logoPath,'xl/media/logo.png');$zip->addFromString('xl/styles.xml',$styles);$zip->close();
    while(ob_get_level())ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Content-Length: '.filesize($temp));header('Cache-Control: private, max-age=0, must-revalidate');readfile($temp);@unlink($temp);exit;
}
