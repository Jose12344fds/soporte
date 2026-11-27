<?php
require_once dirname(__DIR__, 4) . '/api/activos/_interno/renovaciones/repositorio.php';
/** Periodos de renovación y avisos de Servicios TI. */
function sr_schema(): void {
 global $db;static $ready=false;if($ready)return;
 $db->query("CREATE TABLE IF NOT EXISTS activos_servicios_renovaciones (
 IdRenovacion INT UNSIGNED NOT NULL AUTO_INCREMENT,IdServicio INT NOT NULL,Etiqueta VARCHAR(100) NOT NULL,
 FechaInicio DATE NULL,FechaFin DATE NOT NULL,FechaAviso DATE NOT NULL,Estado VARCHAR(12) NOT NULL DEFAULT 'BORRADOR',
 IdAnterior INT UNSIGNED NULL,ClaveOrigen INT NULL,Proveedor VARCHAR(100) NOT NULL DEFAULT '',Importe DECIMAL(14,2) NULL,Moneda VARCHAR(3) NOT NULL DEFAULT 'PEN',Notas VARCHAR(2000) NOT NULL DEFAULT '',
 CreadoPor INT NOT NULL DEFAULT 0,CreadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,ActualizadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(IdRenovacion),UNIQUE KEY uq_srv_origen(ClaveOrigen),KEY idx_srv_renovacion(IdServicio,Estado),KEY idx_srv_aviso(Estado,FechaAviso)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $db->query("CREATE TABLE IF NOT EXISTS activos_servicios_avisos_vistos (IdUsuario INT NOT NULL,IdRenovacion INT UNSIGNED NOT NULL,FirmaAviso CHAR(64) NOT NULL,VistoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(IdUsuario,IdRenovacion)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$ready=true;
}
function sr_backfill(): void
{
    global $db;
    sr_schema();
    $table = $db->query("SHOW TABLES LIKE 'servicios_ti'");
    if (!$db->num_rows($table)) {
        return;
    }
    activos_servicios_preparar_renovaciones(database_connection());
}
function sr_today(): string {return (new DateTimeImmutable('now',new DateTimeZone('America/Bogota')))->format('Y-m-d');}
function sr_valid_date(string $date): bool { return activos_fecha_valida($date); }
function sr_notice_date(string $fin): string
{
    return activos_fecha_aviso_mes_anterior($fin);
}
function sr_validate(array $p): array {
 $start=trim((string)($p['fecha_inicio']??''));$end=trim((string)($p['fecha_fin']??''));
 if(!sr_valid_date($start)||!sr_valid_date($end)||$end<$start)throw new RuntimeException('Revisa el inicio y fin del periodo.');
 $notice=trim((string)($p['fecha_aviso']??''))?:sr_notice_date($end);if(!sr_valid_date($notice)||$notice>$end)throw new RuntimeException('El aviso debe ser una fecha válida anterior o igual al vencimiento.');
 $label=trim((string)($p['etiqueta']??''))?:('Periodo '.substr($start,0,4).' / '.substr($end,0,4));$provider=trim((string)($p['proveedor']??''));$notes=trim((string)($p['notas']??''));
 if(mb_strlen($label)>100||mb_strlen($provider)>100||mb_strlen($notes)>2000)throw new RuntimeException('El nombre, proveedor o notas excede el tamaño permitido.');
 $amount=str_replace(',','.',trim((string)($p['importe']??'')));if($amount!==''&&!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/',$amount))throw new RuntimeException('Importe inválido.');
 $currency=strtoupper((string)($p['moneda']??'PEN'));if(!in_array($currency,['PEN','USD'],true))throw new RuntimeException('Moneda no válida.');
 return ['etiqueta'=>$label,'inicio'=>$start,'fin'=>$end,'aviso'=>$notice,'proveedor'=>$provider,'importe'=>$amount===''?null:number_format((float)$amount,2,'.',''),'moneda'=>$currency,'notas'=>$notes];
}
function sr_alert(array $r,string $today): array {
 $due=$r['FechaFin'];$kind=$due<$today?'VENCIDO':'POR_RENOVAR';$days=(int)(new DateTimeImmutable($today))->diff(new DateTimeImmutable($due))->format('%r%a');
 return ['tipo'=>$kind,'dias'=>$days,'firma'=>hash('sha256',$r['IdRenovacion'].'|'.$due.'|'.$r['FechaAviso'].'|'.$kind)];
}
function sr_alerts(int $uid): array {
 global $db;$today=sr_today();$te=$db->escape($today);
 $rows=find_by_sql("SELECT r.*,s.NombreServicio,s.CodigoServicio,v.FirmaAviso FROM activos_servicios_renovaciones r INNER JOIN servicios_ti s ON s.IdServicio=r.IdServicio LEFT JOIN activos_servicios_avisos_vistos v ON v.IdRenovacion=r.IdRenovacion AND v.IdUsuario=$uid WHERE r.Estado='VIGENTE' AND s.EstadoServicio='ACTIVO' AND r.FechaAviso<='$te' ORDER BY r.FechaFin,r.IdRenovacion");
 $rows = array_values(array_filter($rows, static fn($row) => activos_fecha_valida($row['FechaFin']) && activos_fecha_valida($row['FechaAviso'])));
 foreach($rows as &$r){$a=sr_alert($r,$today);$r['tipo']=$a['tipo'];$r['dias']=$a['dias'];$r['firma']=$a['firma'];$r['leido']=hash_equals((string)($r['FirmaAviso']??''),$a['firma']);}unset($r);return $rows;
}