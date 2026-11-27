<?php
/** Calendario FH ING: lunes a viernes, jornada de 8 horas, sin feriados. */
function fh_work_date($inicio, $indice) {
    $d = new DateTimeImmutable($inicio, new DateTimeZone('America/Bogota'));
    while ((int)$d->format('N') > 5) $d = $d->modify('+1 day');
    $semanas = intdiv((int)$indice, 5);
    if ($semanas) $d = $d->modify('+' . ($semanas * 7) . ' days');
    for ($i=0; $i < $indice % 5; $i++) {
        do { $d = $d->modify('+1 day'); } while ((int)$d->format('N') > 5);
    }
    return $d->format('Y-m-d');
}
function fh_schedule_slot($inicio, &$cursor, $dias) {
    $segundos = (int)round((float)$dias * 28800);
    if ($segundos < 0 || $segundos > 28800 * 10000) throw new RuntimeException('Duración fuera de rango.');
    if (!$segundos) return ['fecha_inicio'=>null,'fecha_fin'=>null,'horas'=>0,'inicio_jornada'=>0,'fin_jornada'=>0,'segmentos'=>[]];
    $desde = $cursor; $hasta = $cursor + $segundos;
    $di = intdiv($desde, 28800); $df = intdiv($hasta - 1, 28800);
    $segmentos = [];
    for ($i=$di; $i<=$df; $i++) {
        $a=max($desde, $i*28800)-$i*28800; $b=min($hasta, ($i+1)*28800)-$i*28800;
        $segmentos[]=['fecha'=>fh_work_date($inicio,$i),'inicio'=>$a/28800,'fraccion'=>($b-$a)/28800,'horas'=>($b-$a)/3600];
    }
    $cursor=$hasta;
    return ['fecha_inicio'=>fh_work_date($inicio,$di),'fecha_fin'=>fh_work_date($inicio,$df),
        'horas'=>$segundos/3600,'inicio_jornada'=>($desde%28800)/3600,'fin_jornada'=>($hasta-$df*28800)/3600,'segmentos'=>$segmentos];
}
function fh_schedule_activities($actividades, $inicio) {
    $cursor=0;
    foreach ($actividades as &$act) {
        $act['total_dias']=0; $act['total_horas']=0; $act['fecha_inicio']=null; $act['fecha_fin']=null;
        foreach ($act['planos'] as &$p) {
            $p=array_merge($p, fh_schedule_slot($inicio,$cursor,$p['dias'] ?? 0));
            $act['total_dias']+=(float)($p['dias'] ?? 0); $act['total_horas']+=$p['horas'];
            if ($p['fecha_inicio']) { if (!$act['fecha_inicio']) $act['fecha_inicio']=$p['fecha_inicio']; $act['fecha_fin']=$p['fecha_fin']; }
        }
        unset($p);
    }
    unset($act);
    return $actividades;
}
