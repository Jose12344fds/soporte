<?php
require_once __DIR__ . '/../comun.php';
require_once __DIR__ . '/../renovaciones/inicializar.php';
require_once ACTIVOS_ROOT . '/api/activos/_interno/servicios/renovaciones.php';
function servicio_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function servicio_post(string $name): string {
    return trim((string)($_POST[$name] ?? ''));
}

function servicio_date_sql(string $value): string {
    global $db;
    return $value === '' ? 'NULL' : "'".$db->escape($value)."'";
}


function activos_servicios_catalogos(): array
{

return [];
}

function activos_servicios_listar(): array
{
 global $db;
 extract(activos_servicios_catalogos(), EXTR_SKIP);
$servicios = find_by_sql("SELECT s.*,(SELECT IdRenovacion FROM activos_servicios_renovaciones r WHERE r.IdServicio=s.IdServicio AND r.Estado='VIGENTE' ORDER BY r.FechaFin DESC LIMIT 1) IdPeriodoVigente,(SELECT COUNT(*) FROM activos_servicios_contratos c WHERE c.IdServicio=s.IdServicio) TotalContratos,(SELECT COUNT(*) FROM activos_servicios_contratos c WHERE c.IdServicio=s.IdServicio AND c.EstadoContrato='VIGENTE') ContratosVigentes FROM servicios_ti s ORDER BY (s.EstadoServicio='ACTIVO') DESC,s.NombreServicio");
$contracts = find_by_sql("SELECT x.*,s.NombreServicio,s.CodigoServicio FROM activos_servicios_contratos x INNER JOIN servicios_ti s ON s.IdServicio=x.IdServicio ORDER BY (x.EstadoContrato='VIGENTE') DESC,x.FechaVencimiento");
$totalActivos = count(array_filter($servicios, fn($s) => $s['EstadoServicio'] === 'ACTIVO'));
$totalCriticos = count(array_filter($servicios, fn($s) => in_array($s['Criticidad'], ['ALTO','MUY ALTO'], true)));
$totalVencidos = count(array_filter($servicios, fn($s) => $s['FechaFin'] && $s['FechaFin'] < date('Y-m-d')));
$monthlyPen = 0.0;
$monthlyUsd = 0.0;
foreach ($contracts as $contract) {
    if ($contract['EstadoContrato'] !== 'VIGENTE') continue;
    $monthlyCost = $contract['ModalidadPago'] === 'MENSUAL' ? (float)$contract['Costo'] : ($contract['ModalidadPago'] === 'ANUAL' ? (float)$contract['Costo'] / 12 : 0);
    if ($contract['Moneda'] === 'USD') $monthlyUsd += $monthlyCost;
    else $monthlyPen += $monthlyCost;
}
$contractExpiring = count(array_filter($contracts, fn($c) => $c['EstadoContrato'] === 'VIGENTE' && $c['FechaVencimiento'] && $c['FechaVencimiento'] <= date('Y-m-d', strtotime('+60 days'))));


 return array_merge(activos_servicios_catalogos(), compact('servicios','contracts','totalActivos','totalCriticos','totalVencidos','monthlyPen','monthlyUsd','contractExpiring'));
}

function activos_servicios_guardar(): array
{
 global $db, $session;
 $messages = [];
 extract(activos_servicios_catalogos(), EXTR_SKIP);

    $action = servicio_post('action');
    if ($action === 'save_service_contract') {
      try {
        $errors = [];
        $id = (int)($_POST['IdContrato'] ?? 0);
        $service = (int)($_POST['IdServicio'] ?? 0);
        $concept = strtoupper(servicio_post('TipoConcepto'));
        $mode = strtoupper(servicio_post('ModalidadPago'));
        $currency = strtoupper(servicio_post('Moneda'));
        $cost = (float)($_POST['Costo'] ?? 0);
        $state = strtoupper(servicio_post('EstadoContrato'));
        $start = servicio_post('FechaInicio');
        $end = servicio_post('FechaVencimiento');
        $rawText = [
            'CodigoContrato' => servicio_post('CodigoContrato'),
            'Proveedor' => servicio_post('Proveedor'),
            'CodigoSAP' => servicio_post('CodigoSAP'),
            'OrdenCompra' => servicio_post('OrdenCompra'),
            'Observaciones' => servicio_post('Observaciones'),
        ];
        if (!tiene_permiso('activos', $id > 0 ? 'editar' : 'crear')) $errors[] = 'No tiene permiso para guardar este contrato.';
        if ($service <= 0 || !find_by_sql("SELECT IdServicio FROM servicios_ti WHERE IdServicio={$service} LIMIT 1")) $errors[] = 'Seleccione un servicio válido.';
        if (!in_array($concept, ['SOPORTE','SUSCRIPCION','MANTENIMIENTO','COMUNICACIONES','OTRO'], true)) $errors[] = 'Concepto inválido.';
        if (!in_array($mode, ['MENSUAL','ANUAL','PERPETUO','PAGO UNICO'], true)) $errors[] = 'Modalidad inválida.';
        if (!in_array($currency, ['PEN','USD'], true) || $cost < 0) $errors[] = 'Costo o moneda inválidos.';
        if (!in_array($state, ['VIGENTE','VENCIDO','CANCELADO','ANULADO'], true)) $errors[] = 'Estado de contrato inválido.';
        if ($start !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $errors[] = 'La fecha de inicio no es válida.';
        if ($end !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $errors[] = 'La fecha de vencimiento no es válida.';
        if ($start !== '' && $end !== '' && $end < $start) $errors[] = 'El vencimiento no puede ser anterior al inicio.';
        if (!$errors) {
            $identifierConditions = [];
            foreach (['CodigoContrato','CodigoSAP','OrdenCompra'] as $field) {
                if ($rawText[$field] !== '') $identifierConditions[] = "({$field}='".$db->escape($rawText[$field])."')";
            }
            if ($identifierConditions && find_by_sql("SELECT IdContrato FROM activos_servicios_contratos WHERE IdServicio={$service} AND (".implode(' OR ', $identifierConditions).") AND IdContrato<>{$id} LIMIT 1")) {
                $errors[] = 'Ya existe un contrato de este servicio con el mismo código, SAP u orden de compra.';
            }
        }
        if (!$errors) {
            $text = [];
            foreach ($rawText as $key => $value) $text[$key] = $db->escape($value);
            $purchase = servicio_date_sql(servicio_post('FechaCompra'));
            $startSql = servicio_date_sql($start);
            $endSql = servicio_date_sql($end);
            $renew = !empty($_POST['RenovacionAutomatica']) ? 1 : 0;
            if ($id > 0) {
                $sql = "UPDATE activos_servicios_contratos SET IdServicio={$service},CodigoContrato='{$text['CodigoContrato']}',TipoConcepto='{$concept}',ModalidadPago='{$mode}',Proveedor='{$text['Proveedor']}',Costo={$cost},Moneda='{$currency}',CodigoSAP='{$text['CodigoSAP']}',OrdenCompra='{$text['OrdenCompra']}',FechaCompra={$purchase},FechaInicio={$startSql},FechaVencimiento={$endSql},RenovacionAutomatica={$renew},EstadoContrato='{$state}',Observaciones='{$text['Observaciones']}' WHERE IdContrato={$id}";
            } else {
                $sql = "INSERT INTO activos_servicios_contratos (IdServicio,CodigoContrato,TipoConcepto,ModalidadPago,Proveedor,Costo,Moneda,CodigoSAP,OrdenCompra,FechaCompra,FechaInicio,FechaVencimiento,RenovacionAutomatica,EstadoContrato,Observaciones) VALUES ({$service},'{$text['CodigoContrato']}','{$concept}','{$mode}','{$text['Proveedor']}',{$cost},'{$currency}','{$text['CodigoSAP']}','{$text['OrdenCompra']}',{$purchase},{$startSql},{$endSql},{$renew},'{$state}','{$text['Observaciones']}')";
            }
            if ($db->query($sql)) activos_modulo_mensaje($messages, 's', $id > 0 ? 'Contrato del servicio actualizado correctamente.' : 'Contrato del servicio registrado correctamente.');
            else $errors[] = 'No se pudo guardar el contrato del servicio.';
        }
        if ($errors) activos_modulo_mensaje($messages, 'd', $errors);
      } catch (\Throwable $e) {
        activos_modulo_mensaje($messages, 'd', 'Error interno al guardar el contrato: '.$e->getMessage());
      }
        return activos_modulo_resultado($messages);
    }
    if (!tiene_permiso('activos', 'crear') && !tiene_permiso('activos', 'editar')) {
        activos_modulo_mensaje($messages, 'd', 'No tiene permiso para guardar servicios TI.');
        return activos_modulo_resultado($messages);
    }

    $id = (int)($_POST['IdServicio'] ?? 0);
    $codigo = strtoupper(servicio_post('CodigoServicio'));
    $nombre = servicio_post('NombreServicio');
    $tipo = servicio_post('TipoServicio');
    $categoria = servicio_post('CategoriaServicio');
    $descripcion = servicio_post('Descripcion');
    $proveedor = servicio_post('Proveedor');
    $responsable = servicio_post('Responsable');
    $confidencialidad = (int)($_POST['Confidencialidad'] ?? 0);
    $integridad = (int)($_POST['Integridad'] ?? 0);
    $disponibilidad = (int)($_POST['Disponibilidad'] ?? 0);
    $clasificacion = strtoupper(servicio_post('Clasificacion'));
    $estado = strtoupper(servicio_post('EstadoServicio'));
    $fechaInicio = servicio_post('FechaInicio');
    $fechaFin = servicio_post('FechaFin');
    if($id>0){$periodoVigente=find_by_sql("SELECT FechaInicio,FechaFin FROM activos_servicios_renovaciones WHERE IdServicio=$id AND Estado='VIGENTE' ORDER BY FechaFin DESC LIMIT 1");if($periodoVigente){$fechaInicio=$periodoVigente[0]['FechaInicio']??'';$fechaFin=$periodoVigente[0]['FechaFin'];}}
    $sla = servicio_post('SLA');
    $observaciones = servicio_post('Observaciones');
    $ubicacion = servicio_post('Ubicacion');
    $errors = [];

    if ($codigo === '' || mb_strlen($codigo, 'UTF-8') > 50) $errors[] = 'Ingrese un código de servicio válido de hasta 50 caracteres.';
    if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 100) $errors[] = 'Ingrese el nombre del servicio de hasta 100 caracteres.';
    foreach ([$confidencialidad, $integridad, $disponibilidad] as $score) {
        if ($score < 1 || $score > 4) { $errors[] = 'Confidencialidad, integridad y disponibilidad deben calificarse del 1 al 4.'; break; }
    }
    if (!in_array($clasificacion, ['PUBLICO','INTERNO','CONFIDENCIAL','RESTRINGIDO'], true)) $errors[] = 'Seleccione una clasificación válida.';
    if (!in_array($estado, ['ACTIVO','INACTIVO','SUSPENDIDO','EN REVISION'], true)) $errors[] = 'Seleccione un estado válido.';
    if ($fechaInicio !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) $errors[] = 'La fecha de inicio no es válida.';
    if ($fechaFin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) $errors[] = 'La fecha de fin no es válida.';
    if ($fechaInicio !== '' && $fechaFin !== '' && $fechaFin < $fechaInicio) $errors[] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
    $safeCode = $db->escape($codigo);
    $duplicate = find_by_sql("SELECT IdServicio FROM servicios_ti WHERE CodigoServicio='{$safeCode}' AND IdServicio<>".$id." LIMIT 1");
    if ($duplicate) $errors[] = 'El código '.$codigo.' ya está registrado.';

    $criticidad = [1=>'BAJO',2=>'MEDIO',3=>'ALTO',4=>'MUY ALTO'][max($confidencialidad, $integridad, $disponibilidad)] ?? 'SIN EVALUAR';
    if (!$errors) {
        $values = [
            'CodigoServicio'=>$codigo,'NombreServicio'=>$nombre,'TipoServicio'=>$tipo,'CategoriaServicio'=>$categoria,
            'Descripcion'=>$descripcion,'Proveedor'=>$proveedor,'Responsable'=>$responsable,'Criticidad'=>$criticidad,
            'Clasificacion'=>$clasificacion,'EstadoServicio'=>$estado,'SLA'=>$sla,'Observaciones'=>$observaciones,'Ubicacion'=>$ubicacion,
        ];
        foreach ($values as $key => $value) $values[$key] = $db->escape($value);
        $fechaInicioSql = $fechaInicio === '' ? 'NULL' : "'".$db->escape($fechaInicio)."'";
        $fechaFinSql = $fechaFin === '' ? 'NULL' : "'".$db->escape($fechaFin)."'";
        if ($id > 0) {
            if (!tiene_permiso('activos', 'editar')) {
                activos_modulo_mensaje($messages, 'd', 'No tiene permiso para editar servicios TI.');
                return activos_modulo_resultado($messages);
            }
            $sql = "UPDATE servicios_ti SET CodigoServicio='{$values['CodigoServicio']}',NombreServicio='{$values['NombreServicio']}',TipoServicio='{$values['TipoServicio']}',CategoriaServicio='{$values['CategoriaServicio']}',Descripcion='{$values['Descripcion']}',Proveedor='{$values['Proveedor']}',Responsable='{$values['Responsable']}',Confidencialidad={$confidencialidad},Integridad={$integridad},Disponibilidad={$disponibilidad},Criticidad='{$values['Criticidad']}',Clasificacion='{$values['Clasificacion']}',EstadoServicio='{$values['EstadoServicio']}',FechaInicio={$fechaInicioSql},FechaFin={$fechaFinSql},SLA='{$values['SLA']}',Observaciones='{$values['Observaciones']}',Ubicacion='{$values['Ubicacion']}' WHERE IdServicio={$id}";
        } else {
            if (!tiene_permiso('activos', 'crear')) {
                activos_modulo_mensaje($messages, 'd', 'No tiene permiso para registrar servicios TI.');
                return activos_modulo_resultado($messages);
            }
            $sql = "INSERT INTO servicios_ti (CodigoServicio,NombreServicio,TipoServicio,CategoriaServicio,Descripcion,Proveedor,Responsable,Confidencialidad,Integridad,Disponibilidad,Criticidad,Clasificacion,EstadoServicio,FechaInicio,FechaFin,SLA,Observaciones,Ubicacion) VALUES ('{$values['CodigoServicio']}','{$values['NombreServicio']}','{$values['TipoServicio']}','{$values['CategoriaServicio']}','{$values['Descripcion']}','{$values['Proveedor']}','{$values['Responsable']}',{$confidencialidad},{$integridad},{$disponibilidad},'{$values['Criticidad']}','{$values['Clasificacion']}','{$values['EstadoServicio']}',{$fechaInicioSql},{$fechaFinSql},'{$values['SLA']}','{$values['Observaciones']}','{$values['Ubicacion']}')";
        }
        if ($db->query($sql)) {
            activos_renovacion_inicial('servicio', $id > 0 ? $id : (int)$db->insert_id());
            activos_modulo_mensaje($messages, 's', $id > 0 ? 'Servicio TI actualizado correctamente.' : 'Servicio TI registrado correctamente.');
        }
        else activos_modulo_mensaje($messages, 'd', 'No se pudo guardar el servicio TI.');
    } else {
        activos_modulo_mensaje($messages, 'd', $errors);
    }
    return activos_modulo_resultado($messages);

 return activos_modulo_resultado($messages);
}

function activos_servicios_reporte(): array
{
 global $db;
    $services=find_by_sql("SELECT s.*,
      (SELECT COUNT(*) FROM activos_servicios_contratos c WHERE c.IdServicio=s.IdServicio) TotalContratos,
      (SELECT COUNT(*) FROM activos_servicios_contratos c WHERE c.IdServicio=s.IdServicio AND c.EstadoContrato='VIGENTE') ContratosVigentes,
      (SELECT COALESCE(SUM(CASE WHEN c.ModalidadPago='MENSUAL' THEN c.Costo WHEN c.ModalidadPago='ANUAL' THEN c.Costo/12 ELSE 0 END),0) FROM activos_servicios_contratos c WHERE c.IdServicio=s.IdServicio AND c.EstadoContrato='VIGENTE' AND c.Moneda='PEN') CostoMensualPEN,
      (SELECT COALESCE(SUM(CASE WHEN c.ModalidadPago='MENSUAL' THEN c.Costo WHEN c.ModalidadPago='ANUAL' THEN c.Costo/12 ELSE 0 END),0) FROM activos_servicios_contratos c WHERE c.IdServicio=s.IdServicio AND c.EstadoContrato='VIGENTE' AND c.Moneda='USD') CostoMensualUSD
      FROM servicios_ti s ORDER BY s.NombreServicio");
    $contracts=find_by_sql("SELECT x.*,s.CodigoServicio,s.NombreServicio FROM activos_servicios_contratos x INNER JOIN servicios_ti s ON s.IdServicio=x.IdServicio ORDER BY s.NombreServicio,x.FechaVencimiento,x.IdContrato");

 return compact('services','contracts');
}
