<?php
/** Reporte Excel de planificación por proceso, jornadas L-V de 8 horas. */
error_reporting(0);
ini_set('display_errors', 0);
require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_permiso('ots_ingeniera', 'ver');
require_once __DIR__ . '/calendario.php';
$ot=trim((string)($_GET['ot'] ?? ''));
if ($ot==='') { http_response_code(400); exit('OT requerida.'); }
$oe=$db->escape($ot);
$cab=find_by_sql("SELECT * FROM fh_ing_planos_cab WHERE ot='$oe' LIMIT 1");
$det=find_by_sql("SELECT proyecto, elaborado_por FROM fh_ing_ot_detalle WHERE ot='$oe' ORDER BY id DESC LIMIT 1");
$inicio=$cab[0]['fecha_inicio'] ?? date('Y-m-d');
$proyecto=$det[0]['proyecto'] ?? $cab[0]['proyecto'] ?? '';
$autor=$det[0]['elaborado_por'] ?? '';
$rows=find_by_sql("SELECT p.*, a.nombre AS actividad_nombre FROM pln_dtll_Ing p
    INNER JOIN fh_ing_actividades a ON a.id=p.actividad_id WHERE p.ot='$oe' AND a.activo=1
    ORDER BY a.orden, a.id, p.orden, p.id_planos");
$group=[];
foreach ($rows as $r) {
    $aid=(int)$r['actividad_id'];
    if (!isset($group[$aid])) $group[$aid]=['actividad_id'=>$aid,'nombre'=>$r['actividad_nombre'],'planos'=>[]];
    $group[$aid]['planos'][]=$r;
}
$acts=fh_schedule_activities(array_values($group),$inicio);
$finIngenieria=null;foreach($acts as &$act){if($act['fecha_fin'])$finIngenieria=$act['fecha_fin'];$act['nombre']='Módulo 1 · '.$act['nombre'];}unset($act);
$detalle=find_by_sql("SELECT p.cod_plano, m.desc_mod3 AS desc_plano, m.dias/8 AS dias FROM pln_mod3_Ing m INNER JOIN pln_dtll_Ing p ON p.id_planos=m.id_planos WHERE m.ot='$oe' AND m.id_mod3=(SELECT MAX(v.id_mod3) FROM pln_mod3_Ing v WHERE v.id_planos=m.id_planos) ORDER BY p.orden,p.id_planos");
if($detalle){$inicioDetalle=$finIngenieria?fh_work_date((new DateTimeImmutable($finIngenieria))->modify('+1 day')->format('Y-m-d'),0):$inicio;$etapa=fh_schedule_activities([['nombre'=>'Módulo 2 · Detalle','planos'=>$detalle]],$inicioDetalle);$acts=array_merge($acts,$etapa);}
$total=array_sum(array_column($acts,'total_dias'));
$fin=null; foreach ($acts as $a) if ($a['fecha_fin']) $fin=$a['fecha_fin'];
$start=new DateTimeImmutable(fh_work_date($inicio,0));
$end=new DateTimeImmutable($fin ?: $start->format('Y-m-d'));
$weekly=$start->diff($end)->days>90;
$periods=[];
for ($d=$start;$d<=$end;$d=$d->modify($weekly?'+7 days':'+1 day')) {
    $last=$weekly?$d->modify('+6 days'):$d;
    $periods[]=['inicio'=>$d->format('Y-m-d'),'fin'=>$last->format('Y-m-d'),'nombre'=>$d->format('d/m'),'laboral'=>(int)$d->format('N')<=5];
}
function hx($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function num($v){return rtrim(rtrim(number_format((float)$v,2,',',''),'0'),',');}
function fd($s){return $s?(new DateTimeImmutable($s))->format('d/m/Y'):'—';}
$colors=['#2563eb','#0f766e','#7c3aed','#d97706','#0369a1','#be123c'];
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="Cronograma_'.preg_replace('/[^A-Za-z0-9_-]/','_',$ot).'.xls"');
header('Cache-Control: no-store');
?>
<html><head><meta charset="utf-8"><style>
body{font-family:Calibri,Arial,sans-serif;color:#203047;font-size:11pt}table{border-collapse:collapse;margin-bottom:22px}td,th{padding:7px 10px;border:1px solid #dbe3ef;vertical-align:middle}th{background:#e8eef7;font-weight:bold;text-align:left}.title{background:#17365a;color:white;font-size:20pt;font-weight:bold}.sub{background:#f1f5f9;color:#475569}.metric{background:#eff6ff;font-weight:bold;font-size:13pt}.total{background:#17365a;color:white;font-weight:bold}.num{text-align:right;mso-number-format:"0.00"}.weekend{background:#eef0f3;color:#94a3b8}.bar{text-align:center;color:white;font-weight:bold;font-size:9pt}.process{color:white;font-weight:bold}
</style></head><body>
<table style="width:100%"><tr><td colspan="7" class="title">Cronograma de Ingeniería · <?= hx($ot) ?></td></tr>
<tr><td colspan="7" class="sub"><?= hx($proyecto) ?></td></tr>
<tr><td colspan="4">Elaborado por: <?= hx($autor) ?></td><td colspan="3">Emitido: <?= hx((new DateTimeImmutable('now',new DateTimeZone('America/Bogota')))->format('d/m/Y H:i')) ?></td></tr>
<tr><td colspan="2" class="metric"><?= num($total) ?> días laborables</td><td colspan="2" class="metric"><?= num($total*8) ?> horas planificadas</td><td colspan="3" class="metric"><?= fd($start->format('Y-m-d')) ?> → <?= fd($fin) ?></td></tr>
<tr><td colspan="7" class="sub">Lunes a viernes · 8 horas por jornada · 0,5 días = 4 horas · Sin feriados. Las horas corresponden a planificación, no a tiempo real registrado.</td></tr>
</table>
<h2>1. Resumen por proceso</h2>
<table><tr><th>Proceso</th><th>Planos</th><th>Días laborables</th><th>Horas</th><th>Inicio</th><th>Fin</th></tr>
<?php foreach($acts as $i=>$a): ?><tr><td style="border-left:5px solid <?= $colors[$i%count($colors)] ?>"><?= hx($a['nombre']) ?></td><td><?= count($a['planos']) ?></td><td class="num"><?= num($a['total_dias']) ?></td><td class="num"><?= num($a['total_horas']) ?></td><td><?= fd($a['fecha_inicio']) ?></td><td><?= fd($a['fecha_fin']) ?></td></tr><?php endforeach; ?>
<tr class="total"><td colspan="2">TOTAL DEL PROYECTO</td><td><?= num($total) ?></td><td><?= num($total*8) ?></td><td colspan="2"></td></tr></table>
<h2>2. Detalle por plano</h2>
<table><tr><th>Proceso</th><th>Código</th><th>Descripción</th><th>Días</th><th>Horas</th><th>Inicio</th><th>Fin</th></tr>
<?php foreach($acts as $i=>$a): foreach($a['planos'] as $p): ?>
<tr><td><?= hx($a['nombre']) ?></td><td style="mso-number-format:'\@'"><?= hx($p['cod_plano']) ?></td><td><?= hx($p['desc_plano']) ?></td><td class="num"><?= num($p['dias']) ?></td><td class="num"><?= num($p['horas']) ?></td><td><?= fd($p['fecha_inicio']) ?><?= $p['fecha_inicio']?' · '.num($p['inicio_jornada']).' h de jornada':'' ?></td><td><?= fd($p['fecha_fin']) ?><?= $p['fecha_fin']?' · '.num($p['fin_jornada']).' h de jornada':'' ?></td></tr>
<?php endforeach; endforeach; ?></table>
<h2>3. Diagrama de Gantt · <?= $weekly?'horas por semana':'horas por día' ?></h2>
<table><tr><th>Proceso / plano</th><th>Duración</th><?php foreach($periods as $period): ?><th class="<?= !$weekly&&!$period['laboral']?'weekend':'' ?>"><?= hx($period['nombre']) ?></th><?php endforeach; ?></tr>
<?php foreach($acts as $i=>$a): $color=$colors[$i%count($colors)]; ?>
<tr><td colspan="<?= count($periods)+2 ?>" class="process" style="background:<?= $color ?>"><?= hx($a['nombre']) ?> · <?= num($a['total_dias']) ?> d / <?= num($a['total_horas']) ?> h</td></tr>
<?php foreach($a['planos'] as $p): ?><tr><td><?= hx($p['cod_plano'].' · '.$p['desc_plano']) ?></td><td><?= num($p['dias']) ?> d / <?= num($p['horas']) ?> h</td>
<?php foreach($periods as $period): $h=0; foreach($p['segmentos'] as $seg) if($seg['fecha']>=$period['inicio']&&$seg['fecha']<=$period['fin'])$h+=$seg['horas']; ?>
<td class="<?= $h?'bar':(!$weekly&&!$period['laboral']?'weekend':'') ?>" style="min-width:35px;<?= $h?'background:'.$color.';':'' ?>"><?= $h?num($h).' h':'' ?></td>
<?php endforeach; ?></tr><?php endforeach; endforeach; ?></table>
</body></html>