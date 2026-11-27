<?php
$page_title = 'Bitácora de incidentes';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/../../Reportes/_xlsx_corporativo.php';

inc_schema();
require_permiso('incidencias', 'ver');

$currentYear = (int)date('Y');
$year = (int)($_GET['anio'] ?? $currentYear);
if ($year < 2020 || $year > $currentYear + 1) $year = $currentYear;
$scope = inc_ticket_scope_sql('t');

function inc_report_duration(?string $start, ?string $end): string {
    if (!$start) return '';
    $from = strtotime($start); $to = $end ? strtotime($end) : time();
    if ($from === false || $to === false || $to < $from) return '';
    $minutes = (int)floor(($to - $from) / 60);
    $days = intdiv($minutes, 1440); $hours = intdiv($minutes % 1440, 60); $mins = $minutes % 60;
    return ($days > 0 ? $days.' d ' : '').$hours.' h '.$mins.' min';
}

function inc_chart_text(string $value, int $limit = 32): string {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    return strlen($text) > $limit ? substr($text, 0, $limit - 3).'...' : $text;
}
function inc_chart_base(int $width, int $height, string $title) {
    $image = imagecreatetruecolor($width, $height);
    imageantialias($image, true);
    $white=imagecolorallocate($image,255,255,255);$ink=imagecolorallocate($image,23,58,94);$muted=imagecolorallocate($image,92,113,124);
    imagefill($image,0,0,$white);imagerectangle($image,0,0,$width-1,$height-1,imagecolorallocate($image,216,229,231));
    imagestring($image,5,24,18,inc_chart_text($title,90),$ink);
    imagestring($image,2,24,42,'Fuente: Mesa de Ayuda - corte '.date('d/m/Y H:i'),$muted);
    return $image;
}
function inc_chart_flow(array $labels, array $created, array $resolved, string $path, int $year): void {
    $w=1200;$h=430;$im=inc_chart_base($w,$h,'TICKETS CREADOS Y RESUELTOS EN EL MISMO MES '.$year);$left=58;$top=76;$right=30;$bottom=48;$pw=$w-$left-$right;$ph=$h-$top-$bottom;
    $grid=imagecolorallocate($im,226,235,236);$muted=imagecolorallocate($im,92,113,124);$gold=imagecolorallocate($im,237,174,55);$green=imagecolorallocate($im,22,139,114);
    $max=max(1,max(array_merge($created,$resolved)));$group=$pw/max(1,count($labels));
    for($i=0;$i<=5;$i++){ $y=(int)round($top+$ph-$ph*$i/5);imageline($im,$left,$y,$w-$right,$y,$grid);imagestring($im,2,8,$y-7,(string)round($max*$i/5),$muted); }
    foreach($labels as $i=>$label){$x=(int)round($left+$group*$i+$group/2);$bw=max(7,(int)($group*.23));$cH=(int)round($ph*$created[$i]/$max);$rH=(int)round($ph*$resolved[$i]/$max);imagefilledrectangle($im,$x-$bw-2,$top+$ph-$cH,$x-3,$top+$ph,$gold);imagefilledrectangle($im,$x+3,$top+$ph-$rH,$x+$bw+2,$top+$ph,$green);imagestring($im,2,$x-12,$top+$ph+10,$label,$muted);if($created[$i]>0)imagestring($im,2,$x-$bw-6,$top+$ph-$cH-16,(string)$created[$i],$gold);if($resolved[$i]>0)imagestring($im,2,$x+3,$top+$ph-$rH-16,(string)$resolved[$i],$green);}
    imagefilledrectangle($im,850,22,864,34,$gold);imagestring($im,2,870,22,'Tickets creados',$muted);imagefilledrectangle($im,1000,22,1014,34,$green);imagestring($im,2,1020,22,'Resueltos del mes',$muted);imagepng($im,$path);imagedestroy($im);
}
function inc_chart_categories(array $labels, array $values, string $path, int $year): void {
    $w=920;$h=430;$im=inc_chart_base($w,$h,'DEMANDA POR CATEGORIA '.$year);$left=250;$top=78;$right=35;$bottom=25;$pw=$w-$left-$right;$ph=$h-$top-$bottom;$max=$values?max(1,max($values)):1;$teal=imagecolorallocate($im,15,137,151);$muted=imagecolorallocate($im,75,96,107);$count=max(1,count($labels));$slot=$ph/$count;
    foreach($labels as $i=>$label){$y=(int)round($top+$slot*$i+$slot*.18);$barH=max(12,(int)($slot*.58));$barW=(int)round($pw*$values[$i]/$max);imagestring($im,2,14,$y+3,inc_chart_text($label,34),$muted);imagefilledrectangle($im,$left,$y,$left+$barW,$y+$barH,$teal);imagestring($im,2,$left+$barW+7,$y+3,(string)$values[$i],$muted);}imagepng($im,$path);imagedestroy($im);
}
function inc_chart_priority(array $labels, array $values, string $path, int $year): void {
    $w=720;$h=430;$im=inc_chart_base($w,$h,'TICKETS POR PRIORIDAD '.$year);$colors=[imagecolorallocate($im,217,72,93),imagecolorallocate($im,237,124,75),imagecolorallocate($im,237,174,55),imagecolorallocate($im,39,138,98)];$total=max(1,array_sum($values));$start=-90;
    foreach($values as $i=>$value){$end=$start+360*$value/$total;imagefilledarc($im,245,245,270,270,(int)$start,(int)$end,$colors[$i],IMG_ARC_PIE);$start=$end;}imagefilledellipse($im,245,245,145,145,imagecolorallocate($im,255,255,255));imagestring($im,5,210,235,(string)array_sum($values),imagecolorallocate($im,23,58,94));imagestring($im,2,198,258,'tickets',imagecolorallocate($im,92,113,124));foreach($labels as $i=>$label){$y=125+$i*55;imagefilledrectangle($im,430,$y,448,$y+18,$colors[$i]);imagestring($im,3,460,$y,inc_chart_text($label,20).'  '.$values[$i],imagecolorallocate($im,75,96,107));}imagepng($im,$path);imagedestroy($im);
}

try {
    $rows = find_by_sql("SELECT t.IdTicket,t.Folio,t.Tipo,t.FechaRegistro,t.FechaResolucion,t.FechaCierre,t.Descripcion,
            t.SolicitanteNombre,t.SolicitanteArea,t.GrupoAsignacion,t.Prioridad,t.AsignadoNombre,
            t.TipoTratamiento,t.InformeTratamiento,t.SacGenerada,t.CodigoSAC,t.ObservacionesTratamiento,
            c.Nombre Categoria,m.Host,m.Tipo_Activo,m.Ip,
            sv.CodigoServicio,sv.NombreServicio,sv.TipoServicio,
            ca.NombreAplicacion,ca.Ambiente,
            cs.NombreServicio NombreServicioCapacidad,cs.Categoria CategoriaServicioCapacidad,
            COALESCE(NULLIF(resp.area,''),NULLIF(t.GrupoAsignacion,''),'TI') AreaResponsable,
            EXISTS(SELECT 1 FROM inc_adjuntos ad WHERE ad.IdTicket=t.IdTicket AND ad.Activo=1) TieneEvidencia
        FROM inc_tickets t
        LEFT JOIN inc_categorias c ON c.IdCategoria=t.IdCategoria
        LEFT JOIN maquina m ON m.IdMaquina=t.IdMaquina
        LEFT JOIN servicios_ti sv ON sv.IdServicio=t.IdServicio
        LEFT JOIN aplicacion ca ON ca.IdAplicacion=t.IdAplicacionCapacidad
        LEFT JOIN servicio_ti cs ON cs.IdServicio=t.IdServicioCapacidad
        LEFT JOIN users resp ON resp.id=t.AsignadoA
        WHERE {$scope} AND YEAR(t.FechaRegistro)={$year}
        ORDER BY t.FechaRegistro,t.IdTicket");

    foreach ($rows as &$row) {
        $row['FechaNotificacion'] = $row['FechaRegistro'];
        $row['FechaCierreAdministrativo'] = $row['FechaCierre'];
        $row['FechaCierre'] = $row['FechaResolucion'] ?: $row['FechaCierre'];
        $row['Duracion'] = inc_report_duration($row['FechaRegistro'], $row['FechaCierre']);
        $row['Clasificacion'] = inc_type_label((string)$row['Tipo']);
        $row['NivelCriticidad'] = inc_priority_label((string)$row['Prioridad']);
        $assets = [];
        if (!empty($row['NombreAplicacion'])) $assets[] = 'Aplicación: '.$row['NombreAplicacion'].($row['Ambiente'] ? ' / '.$row['Ambiente'] : '');
        elseif (!empty($row['NombreServicioCapacidad'])) $assets[] = 'Servicio TI: '.$row['NombreServicioCapacidad'].($row['CategoriaServicioCapacidad'] ? ' / '.$row['CategoriaServicioCapacidad'] : '');
        elseif (!empty($row['NombreServicio'])) $assets[] = trim(($row['CodigoServicio'] ? $row['CodigoServicio'].' - ' : '').$row['NombreServicio']);
        if (!empty($row['Host'])) $assets[] = trim($row['Host'].($row['Tipo_Activo'] ? ' / '.$row['Tipo_Activo'] : '').($row['Ip'] ? ' / '.$row['Ip'] : ''));
        $row['ActivoSistema'] = $assets ? implode("\n", $assets) : 'No especificado';
        $row['EvidenciaAdjunta'] = (int)$row['TieneEvidencia'] === 1 ? 'SÍ' : 'NO';
        $row['InformeTratamiento'] = $row['InformeTratamiento'] ?: 'NO';
        $row['SacGenerada'] = $row['SacGenerada'] === 'NO_APLICA' ? 'NO APLICA' : ($row['SacGenerada'] ?: 'NO APLICA');
    }
    unset($row);

    $createdRows = find_by_sql("SELECT MONTH(t.FechaRegistro) Mes,COUNT(*) Total,SUM(t.Estado IN ('RESUELTO','CERRADO') AND t.FechaResolucion IS NOT NULL AND YEAR(t.FechaResolucion)=YEAR(t.FechaRegistro) AND MONTH(t.FechaResolucion)=MONTH(t.FechaRegistro)) Resueltos FROM inc_tickets t WHERE {$scope} AND YEAR(t.FechaRegistro)={$year} GROUP BY MONTH(t.FechaRegistro)");
    $categoryRows = find_by_sql("SELECT COALESCE(c.Nombre,'Sin categoria') Nombre,COUNT(*) Total FROM inc_tickets t LEFT JOIN inc_categorias c ON c.IdCategoria=t.IdCategoria WHERE {$scope} AND YEAR(t.FechaRegistro)={$year} GROUP BY t.IdCategoria,c.Nombre ORDER BY Total DESC LIMIT 8");
    $priorityRows = find_by_sql("SELECT t.Prioridad,COUNT(*) Total FROM inc_tickets t WHERE {$scope} AND YEAR(t.FechaRegistro)={$year} GROUP BY t.Prioridad");
    $monthLabels=['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $monthly=[];for($m=1;$m<=12;$m++)$monthly[$m]=['Creados'=>0,'Resueltos'=>0];
    foreach($createdRows as $item){$monthly[(int)$item['Mes']]['Creados']=(int)$item['Total'];$monthly[(int)$item['Mes']]['Resueltos']=(int)$item['Resueltos'];}
    $chartRows=[];$created=[];$resolved=[];
    $categoryLabels=array_column($categoryRows,'Nombre');$categoryValues=array_map('intval',array_column($categoryRows,'Total'));
    $priorityMap=['CRITICA'=>0,'ALTA'=>0,'MEDIA'=>0,'BAJA'=>0];foreach($priorityRows as $item)$priorityMap[(string)$item['Prioridad']]=(int)$item['Total'];
    for($m=1;$m<=12;$m++){
        $created[]=$monthly[$m]['Creados'];$resolved[]=$monthly[$m]['Resueltos'];
        $chartRows[]=['Mes'=>$monthLabels[$m-1],'Creados'=>$monthly[$m]['Creados'],'Resueltos'=>$monthly[$m]['Resueltos'],
            'Categoria'=>$categoryLabels[$m-1]??'','TotalCategoria'=>$categoryValues[$m-1]??'',
            'Prioridad'=>$m<=4?array_keys($priorityMap)[$m-1]:'','TotalPrioridad'=>$m<=4?array_values($priorityMap)[$m-1]:''];
    }
    $chartFiles=[tempnam(sys_get_temp_dir(),'inc_flow_'),tempnam(sys_get_temp_dir(),'inc_cat_'),tempnam(sys_get_temp_dir(),'inc_pri_')];
    inc_chart_flow($monthLabels,$created,$resolved,$chartFiles[0],$year);
    inc_chart_categories($categoryLabels,$categoryValues,$chartFiles[1],$year);
    inc_chart_priority(['Critica','Alta','Media','Baja'],array_values($priorityMap),$chartFiles[2],$year);
    register_shutdown_function(static function()use($chartFiles){foreach($chartFiles as $file)if(is_file($file))@unlink($file);});

    $columns = [
        ['key'=>'FechaNotificacion','label'=>'FECHA Y HORA DE NOTIFICACIÓN','width'=>22],
        ['key'=>'FechaCierre','label'=>'FECHA Y HORA DE CIERRE','width'=>22],
        ['key'=>'Duracion','label'=>'DURACIÓN','width'=>16],
        ['key'=>'SolicitanteNombre','label'=>'REPORTADO POR','width'=>25],
        ['key'=>'SolicitanteArea','label'=>'GERENCIA / ÁREA','width'=>22],
        ['key'=>'Descripcion','label'=>'DESCRIPCIÓN DEL EVENTO / INCIDENTE / SOLICITUD','width'=>48],
        ['key'=>'AreaResponsable','label'=>'GERENCIA / ÁREA RESPONSABLE','width'=>24],
        ['key'=>'ActivoSistema','label'=>'ACTIVO / SISTEMA INVOLUCRADO','width'=>32],
        ['key'=>'EvidenciaAdjunta','label'=>'EVIDENCIA ADJUNTA (SÍ/NO)','width'=>18],
        ['key'=>'Clasificacion','label'=>'CLASIFICACIÓN','width'=>18],
        ['key'=>'NivelCriticidad','label'=>'NIVEL DE CRITICIDAD','width'=>18],
        ['key'=>'TipoTratamiento','label'=>'TIPO DE TRATAMIENTO','width'=>21],
        ['key'=>'AsignadoNombre','label'=>'RESPONSABLE','width'=>24],
        ['key'=>'InformeTratamiento','label'=>'INFORME DE TRATAMIENTO (SÍ/NO)','width'=>21],
        ['key'=>'SacGenerada','label'=>'SAC GENERADA (SÍ/NO/NO APLICA)','width'=>22],
        ['key'=>'CodigoSAC','label'=>'CÓDIGO SAC','width'=>18],
        ['key'=>'ObservacionesTratamiento','label'=>'OBSERVACIONES / LECCIONES APRENDIDAS','width'=>45],
    ];
    $groups = [
        ['label'=>'NOTIFICACIÓN','start'=>1,'end'=>3],
        ['label'=>'INVESTIGACIÓN Y EVALUACIÓN','start'=>4,'end'=>9],
        ['label'=>'INVESTIGACIÓN Y TRATAMIENTO','start'=>10,'end'=>17],
    ];
    $chartColumns=[
        ['key'=>'Mes','label'=>'MES','width'=>12],['key'=>'Creados','label'=>'TICKETS CREADOS','type'=>'number','width'=>18],['key'=>'Resueltos','label'=>'RESUELTOS EN EL MISMO MES','type'=>'number','width'=>25],
        ['key'=>'Categoria','label'=>'CATEGORIA','width'=>30],['key'=>'TotalCategoria','label'=>'TOTAL CATEGORIA','type'=>'number','width'=>17],['key'=>'Prioridad','label'=>'PRIORIDAD','width'=>16],['key'=>'TotalPrioridad','label'=>'TOTAL PRIORIDAD','type'=>'number','width'=>17],
    ];
    tx_export([[
        'name'=>'Bitácora '.$year,
        'title'=>'BITÁCORA DE INCIDENTES Y VULNERABILIDADES DE SEGURIDAD DE LA INFORMACIÓN',
        'code'=>'T-SI-F-13','date'=>date('d/m/Y'),'version'=>'03','elaborado'=>'TI','revisado'=>'ROCA','aprobado'=>'GG',
        'groups'=>$groups,'rowHeight'=>45,'columns'=>$columns,'rows'=>$rows,
    ],[
        'name'=>'Graficos '.$year,
        'title'=>'INDICADORES GRAFICOS DE LA MESA DE AYUDA '.$year,
        'code'=>'T-SI-F-13','date'=>date('d/m/Y'),'version'=>'03','elaborado'=>'TI','revisado'=>'ROCA','aprobado'=>'GG',
        'columns'=>$chartColumns,'rows'=>$chartRows,'rowHeight'=>24,
        'images'=>[
            ['path'=>$chartFiles[0],'name'=>'Tickets creados y resueltos','from'=>['col'=>0,'row'=>21],'to'=>['col'=>11,'row'=>42]],
            ['path'=>$chartFiles[1],'name'=>'Demanda por categoria','from'=>['col'=>11,'row'=>21],'to'=>['col'=>18,'row'=>42]],
            ['path'=>$chartFiles[2],'name'=>'Tickets por prioridad','from'=>['col'=>0,'row'=>43],'to'=>['col'=>8,'row'=>64]],
        ],
    ]], 'Bitacora_Incidentes_T-SI-F-13_'.$year.'_'.date('Y-m-d_His').'.xlsx', dirname(__DIR__, 2).'/assets/img/logo.png');
} catch (Throwable $error) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500); header('Content-Type: text/plain; charset=UTF-8');
    echo 'No se pudo generar la bitácora T-SI-F-13: '.$error->getMessage();
}
