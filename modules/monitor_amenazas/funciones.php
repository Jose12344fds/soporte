<?php

if (!function_exists('ma_h')) {
    function ma_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function ma_q($value) { global $db; return "'".$db->escape((string)$value)."'"; }
function ma_user_id() { $u=current_user(); return (int)($u['id']??0); }
function ma_user_name() { $u=current_user(); return trim((string)($u['name']??$u['username']??'Sistema')); }

function ma_schema() {
    global $db;
    static $ready=false; if($ready)return;
    $db->query("CREATE TABLE IF NOT EXISTS ti_amenazas (
      IdAmenaza BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, Fuente VARCHAR(30) NOT NULL,
      ClaveExterna VARCHAR(100) NOT NULL, Titulo VARCHAR(500) NOT NULL, Descripcion TEXT NULL,
      Cve VARCHAR(40) NULL, Severidad VARCHAR(20) NOT NULL DEFAULT 'SIN CLASIFICAR',
      FechaPublicacion DATE NULL, FechaLimite DATE NULL, Producto VARCHAR(300) NULL,
      Fabricante VARCHAR(150) NULL, Recomendacion TEXT NULL, UrlFuente VARCHAR(700) NOT NULL,
      ExplotacionConocida TINYINT(1) NOT NULL DEFAULT 0, FechaImportacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY(IdAmenaza), UNIQUE KEY uq_ti_amenaza_fuente(Fuente,ClaveExterna),
      KEY idx_ti_amenaza_cve(Cve), KEY idx_ti_amenaza_fecha(FechaPublicacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE IF NOT EXISTS ti_amenazas_evaluaciones (
      IdEvaluacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, IdAmenaza BIGINT UNSIGNED NOT NULL,
      FechaEvaluacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, Aplica VARCHAR(20) NOT NULL,
      Prioridad VARCHAR(20) NOT NULL, Estado VARCHAR(30) NOT NULL, ActivosAfectados TEXT NULL,
      VersionInstalada VARCHAR(250) NULL, Analisis TEXT NOT NULL, Accion TEXT NULL,
      Responsable VARCHAR(150) NOT NULL, FechaCompromiso DATE NULL, Evidencia VARCHAR(700) NULL,
      IdRiesgo BIGINT UNSIGNED NULL, EvaluadoPor INT NOT NULL DEFAULT 0,
      PRIMARY KEY(IdEvaluacion), KEY idx_ti_eval_amenaza(IdAmenaza,FechaEvaluacion),
      CONSTRAINT fk_ti_eval_amenaza FOREIGN KEY(IdAmenaza) REFERENCES ti_amenazas(IdAmenaza) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE IF NOT EXISTS ti_amenazas_sync (
      IdSync BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, Fuente VARCHAR(30) NOT NULL,
      Inicio DATETIME NOT NULL, Fin DATETIME NULL, Estado VARCHAR(20) NOT NULL,
      Leidos INT NOT NULL DEFAULT 0, Nuevos INT NOT NULL DEFAULT 0, Actualizados INT NOT NULL DEFAULT 0,
      Mensaje VARCHAR(1000) NULL, EjecutadoPor INT NOT NULL DEFAULT 0, PRIMARY KEY(IdSync)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $module=$db->fetch_assoc($db->query("SELECT id FROM modulos WHERE slug='monitor_amenazas' LIMIT 1"));
    if(!$module){$next=(int)($db->fetch_assoc($db->query('SELECT COALESCE(MAX(id),0)+1 id FROM modulos'))['id']??1);$db->query("INSERT INTO modulos(id,slug,nombre,descripcion,icono,estado,orden) VALUES($next,'monitor_amenazas','Inteligencia de Amenazas','Monitoreo ISO 27001 A.5.7','fas fa-radar',1,49)");}
    foreach(find_by_sql('SELECT group_level FROM user_groups') as $g){$lvl=(int)$g['group_level'];$manage=$lvl===1?1:0;$db->query("INSERT IGNORE INTO permisos_modulos(group_level,modulo,puede_ver,puede_crear,puede_editar,puede_eliminar) VALUES($lvl,'monitor_amenazas',1,$manage,$manage,$manage)");}
    $ready=true;
}

function ma_http($url) {
    if(!preg_match('#^https://(www\.)?(cisa\.gov|fortiguard\.com)/#i',$url)) throw new RuntimeException('Fuente no autorizada.');
    $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>3,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>60,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'TAMA-SGSI-A57/1.0']);
    $body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
    if($body===false||$code<200||$code>=300)throw new RuntimeException('Error HTTP '.$code.($err?': '.$err:'')); return $body;
}
function ma_upsert($r) {
    global $db; $source=ma_q($r['Fuente']);$key=ma_q($r['ClaveExterna']);
    $exists=$db->fetch_assoc($db->query("SELECT IdAmenaza FROM ti_amenazas WHERE Fuente=$source AND ClaveExterna=$key LIMIT 1"));
    $fields="Titulo=".ma_q($r['Titulo']).",Descripcion=".ma_q($r['Descripcion']).",Cve=".ma_q($r['Cve']).",Severidad=".ma_q($r['Severidad']).",FechaPublicacion=".($r['FechaPublicacion']?ma_q($r['FechaPublicacion']):'NULL').",FechaLimite=".($r['FechaLimite']?ma_q($r['FechaLimite']):'NULL').",Producto=".ma_q($r['Producto']).",Fabricante=".ma_q($r['Fabricante']).",Recomendacion=".ma_q($r['Recomendacion']).",UrlFuente=".ma_q($r['UrlFuente']).",ExplotacionConocida=".(int)$r['ExplotacionConocida'];
    if($exists){$db->query("UPDATE ti_amenazas SET $fields WHERE IdAmenaza=".(int)$exists['IdAmenaza']);return 'updated';}
    $db->query("INSERT INTO ti_amenazas SET Fuente=$source,ClaveExterna=$key,$fields");return 'new';
}
function ma_sync_all() {
    global $db; $lock=$db->fetch_assoc($db->query("SELECT GET_LOCK('tama_monitor_amenazas_sync',2) ok")); if((int)($lock['ok']??0)!==1)throw new RuntimeException('Otra sincronizacion esta en curso.');
    $summary=['read'=>0,'new'=>0,'updated'=>0];
    try {
      $json=json_decode(ma_http('https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json'),true,512,JSON_THROW_ON_ERROR);
      foreach(($json['vulnerabilities']??[]) as $v){$r=['Fuente'=>'CISA KEV','ClaveExterna'=>$v['cveID'],'Titulo'=>$v['cveID'].' - '.$v['vulnerabilityName'],'Descripcion'=>$v['shortDescription']??'','Cve'=>$v['cveID'],'Severidad'=>'CRITICA','FechaPublicacion'=>$v['dateAdded']??null,'FechaLimite'=>$v['dueDate']??null,'Producto'=>$v['product']??'','Fabricante'=>$v['vendorProject']??'','Recomendacion'=>$v['requiredAction']??'','UrlFuente'=>'https://www.cisa.gov/known-exploited-vulnerabilities-catalog','ExplotacionConocida'=>1];$summary['read']++;$summary[ma_upsert($r)]++;}
      $rss=ma_http('https://www.fortiguard.com/rss/ir.xml'); libxml_use_internal_errors(true);$xml=simplexml_load_string($rss,'SimpleXMLElement',LIBXML_NONET|LIBXML_NOCDATA);if(!$xml)throw new RuntimeException('RSS Fortinet invalido.');
      foreach($xml->channel->item as $item){$link=trim((string)$item->link);$key=basename(parse_url($link,PHP_URL_PATH));if(!$key)$key=sha1($link);$text=trim(strip_tags((string)$item->description));preg_match('/CVE-\d{4}-\d{4,}/i',$text.' '.(string)$item->title,$m);$r=['Fuente'=>'FORTINET PSIRT','ClaveExterna'=>$key,'Titulo'=>trim((string)$item->title),'Descripcion'=>$text,'Cve'=>$m[0]??'','Severidad'=>'POR EVALUAR','FechaPublicacion'=>date('Y-m-d',strtotime((string)$item->pubDate)),'FechaLimite'=>null,'Producto'=>'Fortinet','Fabricante'=>'Fortinet','Recomendacion'=>'Revisar versiones afectadas y recomendacion oficial.','UrlFuente'=>$link,'ExplotacionConocida'=>0];$summary['read']++;$summary[ma_upsert($r)]++;}
      return $summary;
    } finally {$db->query("SELECT RELEASE_LOCK('tama_monitor_amenazas_sync')");}
}
