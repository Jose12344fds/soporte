<?php
require_once __DIR__ . '/renovaciones.php';
function sf_schema(): void {
 global $db;
 sr_schema();
 $db->query("CREATE TABLE IF NOT EXISTS activos_servicios_facturas (
 IdFactura INT UNSIGNED NOT NULL AUTO_INCREMENT,IdServicio INT(11) NOT NULL,
 TipoPeriodo VARCHAR(10) NOT NULL,Anio SMALLINT UNSIGNED NOT NULL,Mes TINYINT UNSIGNED NULL,
 NumeroFactura VARCHAR(80) NOT NULL DEFAULT '',FechaEmision DATE NULL,Importe DECIMAL(14,2) NULL,Moneda VARCHAR(3) NOT NULL DEFAULT 'PEN',Notas VARCHAR(1000) NOT NULL DEFAULT '',
 NombreOriginal VARCHAR(250) NOT NULL,NombreGuardado VARCHAR(80) NOT NULL,Mime VARCHAR(80) NOT NULL,Tamano BIGINT UNSIGNED NOT NULL,Sha256 CHAR(64) NOT NULL,CreadoPor INT NOT NULL,FechaRegistro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(IdFactura),UNIQUE KEY uq_servicio_factura_archivo(IdServicio,Sha256),KEY idx_servicio_factura_periodo(IdServicio,Anio,Mes)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $col=$db->query("SHOW COLUMNS FROM activos_servicios_facturas LIKE 'IdRenovacion'");if(!$db->num_rows($col))$db->query("ALTER TABLE activos_servicios_facturas ADD COLUMN IdRenovacion INT UNSIGNED NULL,ADD KEY idx_factura_renovacion(IdRenovacion)");
 $col=$db->query("SHOW COLUMNS FROM activos_servicios_facturas LIKE 'TipoDocumento'");if(!$db->num_rows($col))$db->query("ALTER TABLE activos_servicios_facturas ADD COLUMN TipoDocumento VARCHAR(20) NOT NULL DEFAULT 'FACTURA'");
}
function sf_metadata(array $p): array {
 $tipo=strtoupper(trim((string)($p['tipo_periodo']??'MENSUAL')));$year=(int)($p['anio']??0);$month=(int)($p['mes']??0);
 if(!in_array($tipo,['MENSUAL','ANUAL'],true)||$year<2000||$year>2100||($tipo==='MENSUAL'&&($month<1||$month>12)))throw new RuntimeException('Selecciona un año y periodo válidos.');
 $date=trim((string)($p['fecha_emision']??''));if($date!==''){$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$d||$d->format('Y-m-d')!==$date)throw new RuntimeException('Fecha de emisión inválida.');}
 $amount=str_replace(',','.',trim((string)($p['importe']??'')));if($amount!==''&&(!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/',$amount)))throw new RuntimeException('El importe debe ser positivo y tener como máximo dos decimales.');
 $currency=strtoupper(trim((string)($p['moneda']??'PEN')));if(!in_array($currency,['PEN','USD'],true))throw new RuntimeException('Moneda no válida.');
 $number=trim((string)($p['numero_factura']??''));$notes=trim((string)($p['notas']??''));if(mb_strlen($number)>80||mb_strlen($notes)>1000)throw new RuntimeException('El número o la descripción excede el tamaño permitido.');
 return ['tipo'=>$tipo,'anio'=>$year,'mes'=>$tipo==='ANUAL'?null:$month,'numero'=>$number,'fecha'=>$date?:null,'importe'=>$amount===''?null:number_format((float)$amount,2,'.',''),'moneda'=>$currency,'notas'=>$notes];
}
function sf_file(string $path,string $name,int $size): array {
 if($size<=0||$size>15*1024*1024)throw new RuntimeException('Cada archivo debe pesar entre 1 byte y 15 MB.');
 $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));$mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
 if($ext==='pdf'){if($mime!=='application/pdf'||strpos((string)file_get_contents($path,false,null,0,1024),'%PDF-')===false)throw new RuntimeException('El archivo no es un PDF válido.');$mime='application/pdf';}
 elseif(in_array($ext,['png','jpg','jpeg'],true)){$info=@getimagesize($path);$expected=$ext==='png'?'image/png':'image/jpeg';if(!$info||($info['mime']??'')!==$expected)throw new RuntimeException('La imagen no es válida.');$mime=$expected;}
 elseif($ext==='xml'){$xml=file_get_contents($path);if(preg_match('/<!\s*(DOCTYPE|ENTITY)/i',$xml))throw new RuntimeException('El XML contiene declaraciones no permitidas.');$doc=new DOMDocument();$prev=libxml_use_internal_errors(true);$ok=$doc->loadXML($xml,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($prev);if(!$ok)throw new RuntimeException('El XML no es válido.');$mime='application/xml';}
 else throw new RuntimeException('Admite archivos PDF, XML, JPG y PNG.');
 return ['mime'=>$mime,'hash'=>hash_file('sha256',$path),'nombre'=>mb_substr(basename(str_replace('\\','/',$name)),0,250)];
}
function sf_directory(): string {
 $dir=dirname(__DIR__, 4).'/storage/servicios_facturas';
 if(!is_dir($dir)&&!mkdir($dir,0750,true))throw new RuntimeException('No se pudo preparar el archivo de facturas.');
 if(!is_file($dir.'/.htaccess')&&file_put_contents($dir.'/.htaccess',"Require all denied\nOptions -Indexes\n")===false)throw new RuntimeException('No se pudo proteger el archivo de facturas.');
 if(!is_file($dir.'/web.config'))file_put_contents($dir.'/web.config','<?xml version="1.0"?><configuration><system.webServer><security><authorization><remove users="*" roles="" verbs=""/><add accessType="Deny" users="*"/></authorization></security></system.webServer></configuration>');
 return $dir;
}
function sf_ini_bytes(string $v): int {$v=trim($v);$n=(float)$v;$suffix=strtolower(substr($v,-1));return (int)($n*(['k'=>1024,'m'=>1048576,'g'=>1073741824][$suffix]??1));}
