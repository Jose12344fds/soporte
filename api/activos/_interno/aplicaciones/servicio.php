<?php
require_once __DIR__ . '/../comun.php';
require_once __DIR__ . '/../renovaciones/inicializar.php';
require_once ACTIVOS_ROOT . '/api/activos/_interno/aplicaciones/renovaciones.php';
function app_h($v): string { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function app_p(string $key): string { return trim((string)($_POST[$key]??'')); }
function app_date_sql(string $value): string { return iso_sql_date($value); }


function activos_aplicaciones_catalogos(): array
{

return [];
}

function activos_aplicaciones_listar(): array
{
 global $db;
 extract(activos_aplicaciones_catalogos(), EXTR_SKIP);
$apps=find_by_sql("SELECT a.*,".iso_eval_cols('APLICACION','a.IdAplicacionActivo').",c.NombreAplicacion AplicacionCapacidad,(SELECT COUNT(*) FROM activos_aplicaciones_contratos x WHERE x.IdAplicacionActivo=a.IdAplicacionActivo AND x.EstadoContrato='VIGENTE') TotalContratos,(SELECT COUNT(*) FROM activos_aplicaciones_instalaciones i WHERE i.IdAplicacionActivo=a.IdAplicacionActivo AND i.EstadoInstalacion='ACTIVA') TotalInstalaciones,(SELECT COALESCE(SUM(CASE WHEN x.ModalidadPago='MENSUAL' THEN x.Costo WHEN x.ModalidadPago='ANUAL' THEN x.Costo/12 ELSE 0 END),0) FROM activos_aplicaciones_contratos x WHERE x.IdAplicacionActivo=a.IdAplicacionActivo AND x.EstadoContrato='VIGENTE' AND x.Moneda='PEN') CostoMensual,(SELECT COALESCE(SUM(CASE WHEN x.ModalidadPago='MENSUAL' THEN x.Costo WHEN x.ModalidadPago='ANUAL' THEN x.Costo/12 ELSE 0 END),0) FROM activos_aplicaciones_contratos x WHERE x.IdAplicacionActivo=a.IdAplicacionActivo AND x.EstadoContrato='VIGENTE' AND x.Moneda='USD') CostoMensualUSD FROM activos_aplicaciones a LEFT JOIN aplicacion c ON c.IdAplicacion=a.IdAplicacionCapacidad ORDER BY a.NombreAplicacion");
$contracts=find_by_sql("SELECT x.*,a.NombreAplicacion FROM activos_aplicaciones_contratos x INNER JOIN activos_aplicaciones a ON a.IdAplicacionActivo=x.IdAplicacionActivo ORDER BY (x.EstadoContrato='VIGENTE') DESC,x.FechaVencimiento");
$installations=find_by_sql("SELECT i.*,a.NombreAplicacion,m.Host FROM activos_aplicaciones_instalaciones i INNER JOIN activos_aplicaciones a ON a.IdAplicacionActivo=i.IdAplicacionActivo INNER JOIN maquina m ON m.IdMaquina=i.IdMaquina ORDER BY a.NombreAplicacion,m.Host");
$capacityApps=find_by_sql("SELECT IdAplicacion,NombreAplicacion FROM aplicacion ORDER BY NombreAplicacion");$machines=find_by_sql("SELECT IdMaquina,Host FROM maquina ORDER BY Host");
$apps = activos_fechas_normalizar_registros($apps, ['FechaFinSoporte']);
$contracts = activos_fechas_normalizar_registros($contracts, ['FechaCompra', 'FechaInicio', 'FechaVencimiento']);
$installations = activos_fechas_normalizar_registros($installations, ['FechaInstalacion', 'FechaVencimiento']);
$monthlyPen=0.0;$monthlyUsd=0.0;foreach($contracts as $contract){if($contract['EstadoContrato']!=='VIGENTE')continue;$monthlyCost=$contract['ModalidadPago']==='MENSUAL'?(float)$contract['Costo']:($contract['ModalidadPago']==='ANUAL'?(float)$contract['Costo']/12:0);if($contract['Moneda']==='USD')$monthlyUsd+=$monthlyCost;else $monthlyPen+=$monthlyCost;}$expiring=count(array_filter($contracts,fn($c)=>$c['EstadoContrato']==='VIGENTE'&&$c['FechaVencimiento']&&$c['FechaVencimiento']<=date('Y-m-d',strtotime('+60 days'))));

 return array_merge(activos_aplicaciones_catalogos(), compact('apps','contracts','installations','capacityApps','machines','monthlyPen','monthlyUsd','expiring'));
}

function activos_aplicaciones_guardar(): array
{
 global $db, $session;
 $messages = [];
 extract(activos_aplicaciones_catalogos(), EXTR_SKIP);

    $action=app_p('action'); $errors=[];
    $editingApplication=$action==='save_application'&&(int)($_POST['IdAplicacionActivo']??0)>0;
    $editingContract=$action==='save_contract'&&(int)($_POST['IdContrato']??0)>0;
    $requiredPermission=($editingApplication||$editingContract)?'editar':'crear';
    if(!tiene_permiso('activos',$requiredPermission)) $errors[]='No tiene permiso para realizar esta operación.';
    if($action==='save_application'){
        $id=(int)($_POST['IdAplicacionActivo']??0); $code=strtoupper(app_p('CodigoAplicacion')); $name=app_p('NombreAplicacion');
        $type=strtoupper(app_p('TipoAplicacion')); $state=strtoupper(app_p('EstadoAplicacion')); $capacity=(int)($_POST['IdAplicacionCapacidad']??0);
        if($code===''||$name==='')$errors[]='Código y nombre son obligatorios.';
        if(!in_array($type,['ESCRITORIO','SERVIDOR','SAAS','WEB','MOVIL','OTRO'],true))$errors[]='Tipo de aplicación inválido.';
        if(!in_array($state,['ACTIVA','INACTIVA','RETIRADA'],true))$errors[]='Estado inválido.';
        $safeCode=$db->escape($code); if(find_by_sql("SELECT IdAplicacionActivo FROM activos_aplicaciones WHERE CodigoAplicacion='{$safeCode}' AND IdAplicacionActivo<>{$id} LIMIT 1"))$errors[]='El código ya está registrado.';
        if($capacity>0 && find_by_sql("SELECT IdAplicacionActivo FROM activos_aplicaciones WHERE IdAplicacionCapacidad={$capacity} AND IdAplicacionActivo<>{$id} LIMIT 1"))$errors[]='La aplicación de Capacidad TI ya está vinculada.';
        $entorno=strtoupper(app_p('Entorno'));if($entorno!==''&&!in_array($entorno,['PRODUCCION','PRUEBAS','DESARROLLO'],true))$errors[]='Entorno inválido.';
        $finSoporte=app_p('FechaFinSoporte');if($finSoporte!==''&&!activos_fecha_valida($finSoporte))$errors[]='La fecha de fin de soporte no es válida.';
        $cia=iso_read_cia($errors);
        if(!$errors){$fields=['NombreAplicacion'=>$name,'Fabricante'=>app_p('Fabricante'),'Proveedor'=>app_p('Proveedor'),'Responsable'=>app_p('Responsable'),'Descripcion'=>app_p('Descripcion')];foreach($fields as $k=>$v)$fields[$k]=$db->escape($v);$capacitySql=$capacity>0?(string)$capacity:'NULL';
            if($id>0)$sql="UPDATE activos_aplicaciones SET IdAplicacionCapacidad={$capacitySql},CodigoAplicacion='{$safeCode}',NombreAplicacion='{$fields['NombreAplicacion']}',TipoAplicacion='{$type}',Fabricante='{$fields['Fabricante']}',Proveedor='{$fields['Proveedor']}',Responsable='{$fields['Responsable']}',Descripcion='{$fields['Descripcion']}',EstadoAplicacion='{$state}' WHERE IdAplicacionActivo={$id}";
            else $sql="INSERT INTO activos_aplicaciones(IdAplicacionCapacidad,CodigoAplicacion,NombreAplicacion,TipoAplicacion,Fabricante,Proveedor,Responsable,Descripcion,EstadoAplicacion) VALUES({$capacitySql},'{$safeCode}','{$fields['NombreAplicacion']}','{$type}','{$fields['Fabricante']}','{$fields['Proveedor']}','{$fields['Responsable']}','{$fields['Descripcion']}','{$state}')";
            $db->query($sql);
            $savedId=$id>0?$id:(int)((find_by_sql("SELECT LAST_INSERT_ID() AS id")[0]['id'])??0);
            if($savedId>0){iso_upsert('activos_aplicaciones','IdAplicacionActivo',$savedId,['CustodioTecnico'=>iso_sql_str(app_p('CustodioTecnico')),'VersionAplicacion'=>iso_sql_str(app_p('VersionAplicacion')),'Entorno'=>iso_sql_str($entorno),'FechaFinSoporte'=>iso_sql_date($finSoporte)]+iso_cia_cols($cia));iso_save_eval('APLICACION',$savedId,$cia);}
            activos_modulo_mensaje($messages, 's',$id?'Aplicación actualizada.':'Aplicación registrada.');}
    }elseif($action==='save_contract'){
        $id=(int)($_POST['IdContrato']??0);$app=(int)($_POST['IdAplicacionActivo']??0);$concept=strtoupper(app_p('TipoConcepto'));$mode=strtoupper(app_p('ModalidadPago'));$currency=strtoupper(app_p('Moneda'));$cost=(float)($_POST['Costo']??0);$qty=max(1,(int)($_POST['CantidadLicencias']??1));
        if($app<=0||!find_by_sql("SELECT IdAplicacionActivo FROM activos_aplicaciones WHERE IdAplicacionActivo={$app} LIMIT 1"))$errors[]='Seleccione una aplicación válida.';
        if(!in_array($concept,['LICENCIA','SOPORTE','SUSCRIPCION','MANTENIMIENTO'],true))$errors[]='Concepto inválido.';
        if(!in_array($mode,['MENSUAL','ANUAL','PERPETUO','PAGO UNICO'],true))$errors[]='Modalidad inválida.';
        if(!in_array($currency,['PEN','USD'],true)||$cost<0)$errors[]='Costo o moneda inválidos.';
        $state=strtoupper(app_p('EstadoContrato'));if(!in_array($state,['VIGENTE','VENCIDO','CANCELADO','ANULADO'],true))$errors[]='Estado de contrato inválido.';
        $start=app_p('FechaInicio');$end=app_p('FechaVencimiento');
        foreach (['FechaCompra' => app_p('FechaCompra'), 'FechaInicio' => $start, 'FechaVencimiento' => $end] as $field => $date) {
            if ($date !== '' && !activos_fecha_valida($date)) $errors[] = 'La fecha de ' . $field . ' no es valida.';
        }if($start&&$end&&$end<$start)$errors[]='El vencimiento no puede ser anterior al inicio.';
        $rawText=['CodigoContrato'=>app_p('CodigoContrato'),'Proveedor'=>app_p('Proveedor'),'CodigoSAP'=>app_p('CodigoSAP'),'OrdenCompra'=>app_p('OrdenCompra'),'Observaciones'=>app_p('Observaciones')];
        if($id>0){
            $old=ar_contract($id);if(!$old)$errors[]='Contrato no encontrado.';
            $period=find_by_sql("SELECT * FROM activos_aplicaciones_renovaciones WHERE IdContrato=$id AND Estado='VIGENTE' LIMIT 1");
            if($period){
                if($old&&(int)$old[0]['IdAplicacionActivo']!==$app)$errors[]='Un contrato con historial no puede trasladarse a otra aplicación.';
                $start=$period[0]['FechaInicio']??'';$end=$period[0]['FechaFin'];$cost=$period[0]['Importe']===null?($old[0]['Costo']??0):(float)$period[0]['Importe'];$currency=$period[0]['Moneda'];$rawText['Proveedor']=$period[0]['Proveedor'];
            }
        }

        if(!$errors){
            $identifierConditions=[];
            foreach(['CodigoContrato','CodigoSAP','OrdenCompra'] as $field){if($rawText[$field]!=='')$identifierConditions[]="({$field}='".$db->escape($rawText[$field])."')";}
            if($id===0&&$identifierConditions&&find_by_sql("SELECT IdContrato FROM activos_aplicaciones_contratos WHERE IdAplicacionActivo={$app} AND (".implode(' OR ',$identifierConditions).") LIMIT 1"))$errors[]='Ya existe un contrato de esta aplicación con el mismo código, SAP u orden de compra. Edite el registro existente.';
            $exactConditions="IdAplicacionActivo={$app} AND TipoConcepto='".$db->escape($concept)."' AND ModalidadPago='".$db->escape($mode)."' AND Proveedor='".$db->escape($rawText['Proveedor'])."' AND CantidadLicencias={$qty} AND Costo={$cost} AND Moneda='".$db->escape($currency)."' AND COALESCE(CodigoContrato,'')='".$db->escape($rawText['CodigoContrato'])."' AND COALESCE(CodigoSAP,'')='".$db->escape($rawText['CodigoSAP'])."' AND COALESCE(OrdenCompra,'')='".$db->escape($rawText['OrdenCompra'])."' AND (FechaInicio <=> ".iso_sql_date($start).") AND (FechaVencimiento <=> ".iso_sql_date($end).") AND IdContrato<>{$id}";
            if($id===0&&find_by_sql("SELECT IdContrato FROM activos_aplicaciones_contratos WHERE {$exactConditions} LIMIT 1"))$errors[]='Este contrato ya está registrado con los mismos datos. Use Editar si desea corregirlo.';
        }
        if(!$errors){$text=[];foreach($rawText as $k=>$v)$text[$k]=$db->escape($v);$purchase=app_date_sql(app_p('FechaCompra'));$startSql=app_date_sql($start);$endSql=app_date_sql($end);$renew=!empty($_POST['RenovacionAutomatica'])?1:0;
            if($id>0)$db->query("UPDATE activos_aplicaciones_contratos SET IdAplicacionActivo={$app},CodigoContrato='{$text['CodigoContrato']}',TipoConcepto='{$concept}',ModalidadPago='{$mode}',Proveedor='{$text['Proveedor']}',CantidadLicencias={$qty},Costo={$cost},Moneda='{$currency}',CodigoSAP='{$text['CodigoSAP']}',OrdenCompra='{$text['OrdenCompra']}',FechaCompra={$purchase},FechaInicio={$startSql},FechaVencimiento={$endSql},RenovacionAutomatica={$renew},EstadoContrato='{$state}',Observaciones='{$text['Observaciones']}' WHERE IdContrato={$id}");
            else $db->query("INSERT INTO activos_aplicaciones_contratos(IdAplicacionActivo,CodigoContrato,TipoConcepto,ModalidadPago,Proveedor,CantidadLicencias,Costo,Moneda,CodigoSAP,OrdenCompra,FechaCompra,FechaInicio,FechaVencimiento,RenovacionAutomatica,EstadoContrato,Observaciones) VALUES({$app},'{$text['CodigoContrato']}','{$concept}','{$mode}','{$text['Proveedor']}',{$qty},{$cost},'{$currency}','{$text['CodigoSAP']}','{$text['OrdenCompra']}',{$purchase},{$startSql},{$endSql},{$renew},'{$state}','{$text['Observaciones']}')");
            activos_renovacion_inicial('aplicacion', $id > 0 ? $id : (int)$db->insert_id());
            activos_modulo_mensaje($messages, 's',$id?'Contrato actualizado.':'Contrato o licencia registrado.');}
    }elseif($action==='save_installation'){
        $app=(int)($_POST['IdAplicacionActivo']??0);$machine=(int)($_POST['IdMaquina']??0);
        foreach (['FechaInstalacion', 'FechaVencimiento'] as $field) {
            $date = app_p($field);
            if ($date !== '' && !activos_fecha_valida($date)) $errors[] = 'La fecha de ' . $field . ' no es valida.';
        }
if($app<=0||$machine<=0)$errors[]='Seleccione la aplicación y el equipo.';
        if(!$errors){$version=$db->escape(app_p('VersionInstalada'));$license=$db->escape(app_p('CodigoLicencia'));$notes=$db->escape(app_p('Observaciones'));$date=app_date_sql(app_p('FechaInstalacion'));$expiry=app_date_sql(app_p('FechaVencimiento'));
            $db->query("INSERT INTO activos_aplicaciones_instalaciones(IdAplicacionActivo,IdMaquina,VersionInstalada,CodigoLicencia,FechaInstalacion,FechaVencimiento,EstadoInstalacion,Observaciones) VALUES({$app},{$machine},'{$version}','{$license}',{$date},{$expiry},'ACTIVA','{$notes}') ON DUPLICATE KEY UPDATE VersionInstalada=VALUES(VersionInstalada),CodigoLicencia=VALUES(CodigoLicencia),FechaInstalacion=VALUES(FechaInstalacion),FechaVencimiento=VALUES(FechaVencimiento),EstadoInstalacion='ACTIVA',Observaciones=VALUES(Observaciones)");activos_modulo_mensaje($messages, 's','Instalación vinculada al equipo.');}
    }else $errors[]='Operación no reconocida.';
    if($errors)activos_modulo_mensaje($messages, 'd',$errors);return activos_modulo_resultado($messages);

 return activos_modulo_resultado($messages);
}

function activos_aplicaciones_reporte(): array
{
 global $db;
    $applications=find_by_sql("SELECT a.*,
      c.NombreAplicacion AplicacionCapacidad,
      (SELECT COUNT(*) FROM activos_aplicaciones_contratos x WHERE x.IdAplicacionActivo=a.IdAplicacionActivo) TotalContratos,
      (SELECT COUNT(*) FROM activos_aplicaciones_contratos x WHERE x.IdAplicacionActivo=a.IdAplicacionActivo AND x.EstadoContrato='VIGENTE') ContratosVigentes,
      (SELECT COUNT(*) FROM activos_aplicaciones_instalaciones i WHERE i.IdAplicacionActivo=a.IdAplicacionActivo) TotalInstalaciones,
      (SELECT COUNT(*) FROM activos_aplicaciones_instalaciones i WHERE i.IdAplicacionActivo=a.IdAplicacionActivo AND i.EstadoInstalacion='ACTIVA') InstalacionesActivas,
      (SELECT COALESCE(SUM(CASE WHEN x.ModalidadPago='MENSUAL' THEN x.Costo WHEN x.ModalidadPago='ANUAL' THEN x.Costo/12 ELSE 0 END),0) FROM activos_aplicaciones_contratos x WHERE x.IdAplicacionActivo=a.IdAplicacionActivo AND x.EstadoContrato='VIGENTE' AND x.Moneda='PEN') CostoMensualPEN,
      (SELECT COALESCE(SUM(CASE WHEN x.ModalidadPago='MENSUAL' THEN x.Costo WHEN x.ModalidadPago='ANUAL' THEN x.Costo/12 ELSE 0 END),0) FROM activos_aplicaciones_contratos x WHERE x.IdAplicacionActivo=a.IdAplicacionActivo AND x.EstadoContrato='VIGENTE' AND x.Moneda='USD') CostoMensualUSD
      FROM activos_aplicaciones a LEFT JOIN aplicacion c ON c.IdAplicacion=a.IdAplicacionCapacidad ORDER BY a.NombreAplicacion");
    $contracts=find_by_sql("SELECT x.*,a.CodigoAplicacion,a.NombreAplicacion FROM activos_aplicaciones_contratos x INNER JOIN activos_aplicaciones a ON a.IdAplicacionActivo=x.IdAplicacionActivo ORDER BY a.NombreAplicacion,x.FechaVencimiento,x.IdContrato");
    $installations=find_by_sql("SELECT i.*,a.CodigoAplicacion,a.NombreAplicacion,m.Host EquipoHost FROM activos_aplicaciones_instalaciones i INNER JOIN activos_aplicaciones a ON a.IdAplicacionActivo=i.IdAplicacionActivo LEFT JOIN maquina m ON m.IdMaquina=i.IdMaquina ORDER BY a.NombreAplicacion,m.Host,i.IdInstalacion");

 return compact('applications','contracts','installations');
}
