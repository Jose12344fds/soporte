<?php
require_once __DIR__ . '/../comun.php';
require_once ACTIVOS_ROOT . '/api/activos/_interno/servicios/renovaciones.php';
require_once ACTIVOS_ROOT . '/api/activos/_interno/aplicaciones/renovaciones.php';
function activos_renovaciones_listar(): array
{
 global $db;
$today=sr_today();
$rows=find_by_sql("SELECT r.*,s.NombreServicio,s.CodigoServicio,s.EstadoServicio FROM activos_servicios_renovaciones r INNER JOIN servicios_ti s ON s.IdServicio=r.IdServicio ORDER BY (r.Estado='VIGENTE') DESC,r.FechaFin,r.IdRenovacion DESC");
foreach($rows as &$r)$r['Origen']='servicio';unset($r);
$table=$db->query("SHOW TABLES LIKE 'activos_aplicaciones_contratos'");if($db->num_rows($table)){
 $appRows=find_by_sql("SELECT r.*,r.IdContrato AS IdServicio,CONCAT(a.NombreAplicacion,' · ',c.TipoConcepto) AS NombreServicio,CONCAT(a.CodigoAplicacion,' · ',COALESCE(NULLIF(c.CodigoContrato,''),c.TipoConcepto)) AS CodigoServicio,IF(a.EstadoAplicacion='ACTIVA' AND c.EstadoContrato IN ('VIGENTE','VENCIDO'),'ACTIVO','INACTIVO') AS EstadoServicio FROM activos_aplicaciones_renovaciones r INNER JOIN activos_aplicaciones_contratos c ON c.IdContrato=r.IdContrato INNER JOIN activos_aplicaciones a ON a.IdAplicacionActivo=c.IdAplicacionActivo");foreach($appRows as &$r)$r['Origen']='aplicacion';unset($r);$rows=array_merge($rows,$appRows);
}
usort($rows,fn($a,$b)=>(($b['Estado']==='VIGENTE')<=>($a['Estado']==='VIGENTE'))?:strcmp($a['FechaFin'],$b['FechaFin']));
$vigentes=count(array_filter($rows,fn($r)=>$r['Estado']==='VIGENTE'));$due=count(array_filter($rows,fn($r)=>$r['Estado']==='VIGENTE'&&$r['EstadoServicio']==='ACTIVO'&&$r['FechaAviso']<=$today&&$r['FechaFin']>=$today));$expired=count(array_filter($rows,fn($r)=>$r['Estado']==='VIGENTE'&&$r['EstadoServicio']==='ACTIVO'&&$r['FechaFin']<$today));

 return compact('today','rows','vigentes','due','expired');
}
