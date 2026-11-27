<?php
require_once __DIR__ . '/calendario.php';
function fh_modulo2_inicio($ot) {
    global $db; $oe=$db->escape($ot);
    $r=find_by_sql("SELECT MAX(fecha_fin) AS fecha_fin FROM pln_dtll_Ing WHERE ot='$oe'");
    $fin=$r[0]['fecha_fin'] ?? null;
    $base=$fin?(new DateTimeImmutable($fin))->modify('+1 day')->format('Y-m-d'):(new DateTimeImmutable('now',new DateTimeZone('America/Bogota')))->format('Y-m-d');
    return fh_work_date($base,0);
}
function fh_modulo2_fechas($rows,$inicio) {
    $cursor=0;
    foreach($rows as &$r){$r['dias']=(float)($r['dias'] ?? 0);$r=array_merge($r,fh_schedule_slot($inicio,$cursor,$r['dias']));}
    unset($r);return $rows;
}
