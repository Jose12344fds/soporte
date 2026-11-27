<?php
$page_title = 'Gestión de Capacidad TI';
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/../incidencias/bootstrap.php';

inc_schema();
capacidad_ti_schema();
csrf_validate_or_fail();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php', false);

$action = trim((string)($_POST['action'] ?? ''));
$action = strtolower($action);
// Respaldo para formularios abiertos antes de una actualización o enviados
// por navegadores que no conservaron correctamente el campo oculto `action`.
$knownActions = [
    'save_hypervisor','save_vm','save_software','link_software','save_service',
    'link_service','link_service_software','save_config','save_measurement',
    'save_application','save_component','link_application_vm','link_service_application',
    'close_alert','link_alert_ticket','create_alert_ticket','delete_record'
];
if (!in_array($action, $knownActions, true)) {
    if (array_key_exists('NombreServicio', $_POST)) {
        $action = 'save_service';
    } elseif (array_key_exists('NombreSoftware', $_POST)) {
        $action = 'save_software';
    } elseif (array_key_exists('NombreVM', $_POST)) {
        $action = 'save_vm';
    } elseif (array_key_exists('TipoHipervisor', $_POST)) {
        $action = 'save_hypervisor';
    } elseif (array_key_exists('NombreAplicacion', $_POST)) {
        $action = 'save_application';
    } elseif (array_key_exists('NombreComponente', $_POST)) {
        $action = 'save_component';
    } elseif (array_key_exists('FechaEvaluacion', $_POST) && array_key_exists('Recurso', $_POST)) {
        $action = 'save_measurement';
    }
}
$editing = (int)($_POST['id'] ?? 0) > 0
    || in_array($action, ['close_alert','link_alert_ticket','create_alert_ticket'], true);
require_permiso('capacidad_ti', $editing ? 'editar' : 'crear');

function cap_e($value): string { global $db; return $db->escape(trim((string)$value)); }
function cap_num($value): float { return max(0, (float)str_replace(',', '.', (string)$value)); }
function cap_date($value): string {
    $value = trim((string)$value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
}
function cap_enum($value, array $allowed, string $default): string {
    $value = strtoupper(trim((string)$value));
    return in_array($value, $allowed, true) ? $value : $default;
}
function cap_find_alert(int $id): ?array {
    if ($id <= 0) return null;
    $rows = find_by_sql("SELECT a.*,v.NombreVM,v.IP_VM,v.IdMaquina,m.Host,h.FechaEvaluacion
        FROM alerta_capacidad a
        INNER JOIN maquina_virtual v ON v.IdVM=a.IdVM
        INNER JOIN maquina m ON m.IdMaquina=v.IdMaquina
        LEFT JOIN historial_capacidad h ON h.IdHistorial=a.IdHistorial
        WHERE a.IdAlerta={$id} LIMIT 1");
    return $rows[0] ?? null;
}

try {
    switch ($action) {
        case 'save_hypervisor':
            $id = (int)($_POST['id'] ?? 0); $machine = (int)($_POST['IdMaquina'] ?? 0);
            $type = cap_e($_POST['TipoHipervisor'] ?? '');
            if ($machine <= 0 || $type === '') throw new RuntimeException('Seleccione el servidor y el tipo de hipervisor.');
            $maker = cap_e($_POST['Fabricante'] ?? ''); $version = cap_e($_POST['Version'] ?? '');
            $license = cap_e($_POST['Licencia'] ?? '');
            $cores = (int)($_POST['NucleosCPU'] ?? 0);
            $frequency = cap_num($_POST['FrecuenciaCPUMHz'] ?? 0);
            if ($cores <= 0 || $frequency <= 0) throw new RuntimeException('Indique la cantidad de CPU del host y su frecuencia en MHz. Estos datos son necesarios para calcular el uso de las VM.');
            $status = cap_enum($_POST['Estado'] ?? '', ['ACTIVO','INACTIVO','MANTENIMIENTO'], 'ACTIVO');
            $duplicate = $db->query("SELECT IdHipervisor FROM hipervisor WHERE IdMaquina={$machine} AND IdHipervisor<>{$id} LIMIT 1");
            if ($db->num_rows($duplicate)) throw new RuntimeException('Ese servidor ya tiene un hipervisor registrado.');
            if ($id > 0) {
                $db->query("UPDATE hipervisor SET IdMaquina={$machine},TipoHipervisor='{$type}',Fabricante='{$maker}',Version='{$version}',Licencia='{$license}',NucleosCPU={$cores},FrecuenciaCPUMHz={$frequency},Estado='{$status}' WHERE IdHipervisor={$id}");
            } else {
                $db->query("INSERT INTO hipervisor (IdMaquina,TipoHipervisor,Fabricante,Version,Licencia,NucleosCPU,FrecuenciaCPUMHz,Estado) VALUES ({$machine},'{$type}','{$maker}','{$version}','{$license}',{$cores},{$frequency},'{$status}')");
            }
            cap_redirect('Hipervisor guardado correctamente.');

        case 'save_vm':
            $id = (int)($_POST['id'] ?? 0); $machine = (int)($_POST['IdMaquina'] ?? 0);
            $hypervisor = (int)($_POST['IdHipervisor'] ?? 0); $name = cap_e($_POST['NombreVM'] ?? '');
            if ($machine <= 0 || $name === '') throw new RuntimeException('El servidor físico y el nombre de la VM son obligatorios.');
            $os = cap_e($_POST['SistemaOperativo'] ?? ''); $cpu = cap_num($_POST['CPUAsignada'] ?? 0);
            $ram = cap_num($_POST['RAMAsignada'] ?? 0); $disk = cap_num($_POST['DiscoAsignado'] ?? 0);
            if ($cpu <= 0 || $ram <= 0 || $disk <= 0) throw new RuntimeException('CPU, RAM y disco asignados deben ser mayores que cero.');
            $ip = cap_e($_POST['IP_VM'] ?? ''); $status = cap_enum($_POST['EstadoVM'] ?? '', ['ACTIVA','APAGADA','MANTENIMIENTO','RETIRADA'], 'ACTIVA');
            $date = cap_date($_POST['FechaCreacion'] ?? ''); $dateSql = $date === '' ? 'NULL' : "'{$date}'";
            $note = cap_e($_POST['Observacion'] ?? ''); $hypervisorSql = $hypervisor > 0 ? (string)$hypervisor : 'NULL';
            $duplicate = $db->query("SELECT IdVM FROM maquina_virtual WHERE NombreVM='{$name}' AND IdVM<>{$id} LIMIT 1");
            if ($db->num_rows($duplicate)) throw new RuntimeException('Ya existe una máquina virtual con ese nombre.');
            if ($id > 0) {
                $db->query("UPDATE maquina_virtual SET IdMaquina={$machine},IdHipervisor={$hypervisorSql},NombreVM='{$name}',SistemaOperativo='{$os}',CPUAsignada={$cpu},RAMAsignada={$ram},DiscoAsignado={$disk},IP_VM='{$ip}',EstadoVM='{$status}',FechaCreacion={$dateSql},Observacion='{$note}' WHERE IdVM={$id}");
            } else {
                $db->query("INSERT INTO maquina_virtual (IdMaquina,IdHipervisor,NombreVM,SistemaOperativo,CPUAsignada,RAMAsignada,DiscoAsignado,IP_VM,EstadoVM,FechaCreacion,Observacion) VALUES ({$machine},{$hypervisorSql},'{$name}','{$os}',{$cpu},{$ram},{$disk},'{$ip}','{$status}',{$dateSql},'{$note}')");
                $id = $db->insert_id();
                $user = cap_e((current_user()['name'] ?? ''));
                $db->query("INSERT INTO configuracion_capacidad_vm (IdVM,CPUAsignada,RAMAsignada,DiscoAsignado,Responsable,Observacion) VALUES ({$id},{$cpu},{$ram},{$disk},'{$user}','Configuración inicial')");
            }
            cap_redirect('Máquina virtual guardada correctamente.', 's', 'virtualizacion');

        case 'save_application':
            $id=(int)($_POST['id']??0); $name=cap_e($_POST['NombreAplicacion']??'');
            if($name==='') throw new RuntimeException('El nombre de la aplicación es obligatorio.');
            $type=cap_e($_POST['TipoAplicacion']??'Aplicación Empresarial');
            $version=cap_e($_POST['Version']??''); $maker=cap_e($_POST['Fabricante']??'');
            $environment=cap_enum($_POST['Ambiente']??'', ['PRODUCCION','PRUEBAS','DESARROLLO','CONTINGENCIA'], 'PRODUCCION');
            $critical=cap_enum($_POST['Criticidad']??'', ['BAJA','MEDIA','ALTA','CRITICA'], 'MEDIA');
            $owner=cap_e($_POST['Responsable']??'');
            $status=cap_enum($_POST['EstadoAplicacion']??'', ['ACTIVA','INACTIVA','MANTENIMIENTO'], 'ACTIVA');
            $description=cap_e($_POST['Descripcion']??'');
            $duplicate=$db->query("SELECT IdAplicacion FROM aplicacion WHERE NombreAplicacion='{$name}' AND IdAplicacion<>{$id} LIMIT 1");
            if($db->num_rows($duplicate)) throw new RuntimeException('Ya existe una aplicación con ese nombre.');
            if($id>0) {
                $db->query("UPDATE aplicacion SET NombreAplicacion='{$name}',TipoAplicacion='{$type}',Version='{$version}',Fabricante='{$maker}',Ambiente='{$environment}',Criticidad='{$critical}',Responsable='{$owner}',EstadoAplicacion='{$status}',Descripcion='{$description}' WHERE IdAplicacion={$id}");
            } else {
                $db->query("INSERT INTO aplicacion (NombreAplicacion,TipoAplicacion,Version,Fabricante,Ambiente,Criticidad,Responsable,EstadoAplicacion,Descripcion) VALUES ('{$name}','{$type}','{$version}','{$maker}','{$environment}','{$critical}','{$owner}','{$status}','{$description}')");
            }
            cap_redirect('Aplicación guardada correctamente.', 's', 'aplicaciones');

        case 'save_component':
            $id=(int)($_POST['id']??0); $application=(int)($_POST['IdAplicacion']??0);
            $name=cap_e($_POST['NombreComponente']??''); $type=cap_e($_POST['TipoComponente']??'');
            if($application<=0||$name===''||$type==='') throw new RuntimeException('Seleccione la aplicación e indique nombre y tipo del componente.');
            $version=cap_e($_POST['Version']??''); $maker=cap_e($_POST['Fabricante']??'');
            $status=cap_enum($_POST['EstadoComponente']??'', ['ACTIVO','INACTIVO','MANTENIMIENTO'], 'ACTIVO');
            $note=cap_e($_POST['Observacion']??'');
            $duplicate=$db->query("SELECT IdComponente FROM software_componente WHERE IdAplicacion={$application} AND NombreComponente='{$name}' AND IdComponente<>{$id} LIMIT 1");
            if($db->num_rows($duplicate)) throw new RuntimeException('Ese componente ya está registrado para la aplicación.');
            if($id>0) {
                $db->query("UPDATE software_componente SET IdAplicacion={$application},NombreComponente='{$name}',TipoComponente='{$type}',Version='{$version}',Fabricante='{$maker}',EstadoComponente='{$status}',Observacion='{$note}' WHERE IdComponente={$id}");
            } else {
                $db->query("INSERT INTO software_componente (IdAplicacion,NombreComponente,TipoComponente,Version,Fabricante,EstadoComponente,Observacion) VALUES ({$application},'{$name}','{$type}','{$version}','{$maker}','{$status}','{$note}')");
            }
            cap_redirect('Componente técnico guardado.', 's', 'aplicaciones');

        case 'link_application_vm':
            $id=(int)($_POST['id']??0); $application=(int)($_POST['IdAplicacion']??0); $vm=(int)($_POST['IdVM']??0);
            if($application<=0||$vm<=0) throw new RuntimeException('Seleccione la aplicación y la máquina virtual.');
            $environment=cap_enum($_POST['Ambiente']??'', ['PRODUCCION','PRUEBAS','DESARROLLO','CONTINGENCIA'], 'PRODUCCION');
            $role=cap_e($_POST['RolVM']??''); $date=cap_date($_POST['FechaDespliegue']??'');
            $dateSql=$date===''?'NULL':"'{$date}'";
            $status=cap_enum($_POST['EstadoDespliegue']??'', ['ACTIVO','INACTIVO','MANTENIMIENTO'], 'ACTIVO');
            $note=cap_e($_POST['Observacion']??'');
            if($id>0) {
                $db->query("UPDATE aplicacion_vm SET IdAplicacion={$application},IdVM={$vm},Ambiente='{$environment}',RolVM='{$role}',FechaDespliegue={$dateSql},EstadoDespliegue='{$status}',Observacion='{$note}' WHERE IdAplicacionVM={$id}");
            } else {
                $db->query("INSERT INTO aplicacion_vm (IdAplicacion,IdVM,Ambiente,RolVM,FechaDespliegue,EstadoDespliegue,Observacion) VALUES ({$application},{$vm},'{$environment}','{$role}',{$dateSql},'{$status}','{$note}') ON DUPLICATE KEY UPDATE Ambiente=VALUES(Ambiente),RolVM=VALUES(RolVM),FechaDespliegue=VALUES(FechaDespliegue),EstadoDespliegue=VALUES(EstadoDespliegue),Observacion=VALUES(Observacion)");
            }
            cap_redirect('Infraestructura vinculada a la aplicación.', 's', 'aplicaciones');

        case 'link_service_application':
            $id=(int)($_POST['id']??0); $service=(int)($_POST['IdServicio']??0); $application=(int)($_POST['IdAplicacion']??0);
            if($service<=0||$application<=0) throw new RuntimeException('Seleccione el servicio y la aplicación.');
            $module=cap_e($_POST['Modulo']??''); $note=cap_e($_POST['Observacion']??'');
            if($id>0) {
                $db->query("UPDATE servicio_aplicacion SET IdServicio={$service},IdAplicacion={$application},Modulo='{$module}',Observacion='{$note}' WHERE IdServicioAplicacion={$id}");
            } else {
                $db->query("INSERT INTO servicio_aplicacion (IdServicio,IdAplicacion,Modulo,Observacion) VALUES ({$service},{$application},'{$module}','{$note}') ON DUPLICATE KEY UPDATE Modulo=VALUES(Modulo),Observacion=VALUES(Observacion)");
            }
            cap_redirect('Servicio relacionado con la aplicación.', 's', 'aplicaciones');

        case 'save_software':
            $id = (int)($_POST['id'] ?? 0); $name = cap_e($_POST['NombreSoftware'] ?? '');
            $type = cap_e($_POST['TipoSoftware'] ?? '');
            if ($name === '' || $type === '') throw new RuntimeException('Nombre y tipo de software son obligatorios.');
            $maker=cap_e($_POST['Fabricante']??''); $version=cap_e($_POST['Version']??''); $licenseType=cap_e($_POST['TipoLicencia']??'');
            $license=cap_e($_POST['NumeroLicencia']??''); $start=cap_date($_POST['FechaInicioLicencia']??''); $end=cap_date($_POST['FechaFinLicencia']??'');
            $critical=cap_enum($_POST['Criticidad']??'', ['BAJA','MEDIA','ALTA','CRITICA'], 'MEDIA');
            $status=cap_enum($_POST['EstadoSoftware']??'', ['ACTIVO','INACTIVO','VENCIDO'], 'ACTIVO'); $note=cap_e($_POST['Observacion']??'');
            $startSql=$start===''?'NULL':"'{$start}'"; $endSql=$end===''?'NULL':"'{$end}'";
            if ($id > 0) $db->query("UPDATE software SET NombreSoftware='{$name}',TipoSoftware='{$type}',Fabricante='{$maker}',Version='{$version}',TipoLicencia='{$licenseType}',NumeroLicencia='{$license}',FechaInicioLicencia={$startSql},FechaFinLicencia={$endSql},Criticidad='{$critical}',EstadoSoftware='{$status}',Observacion='{$note}' WHERE IdSoftware={$id}");
            else $db->query("INSERT INTO software (NombreSoftware,TipoSoftware,Fabricante,Version,TipoLicencia,NumeroLicencia,FechaInicioLicencia,FechaFinLicencia,Criticidad,EstadoSoftware,Observacion) VALUES ('{$name}','{$type}','{$maker}','{$version}','{$licenseType}','{$license}',{$startSql},{$endSql},'{$critical}','{$status}','{$note}')");
            cap_redirect('Software guardado correctamente.', 's', 'software');

        case 'link_software':
            $id=(int)($_POST['id']??0);
            $vm=(int)($_POST['IdVM']??0); $software=(int)($_POST['IdSoftware']??0);
            if($vm<=0||$software<=0) throw new RuntimeException('Seleccione la VM y el software.');
            $date=cap_date($_POST['FechaInstalacion']??''); $dateSql=$date===''?'NULL':"'{$date}'";
            $version=cap_e($_POST['VersionInstalada']??''); $status=cap_enum($_POST['EstadoInstalacion']??'', ['ACTIVO','INACTIVO','DESINSTALADO'], 'ACTIVO'); $note=cap_e($_POST['Observacion']??'');
            if($id>0) $db->query("UPDATE vm_software SET IdVM={$vm},IdSoftware={$software},FechaInstalacion={$dateSql},VersionInstalada='{$version}',EstadoInstalacion='{$status}',Observacion='{$note}' WHERE IdVMSoftware={$id}");
            else $db->query("INSERT INTO vm_software (IdVM,IdSoftware,FechaInstalacion,VersionInstalada,EstadoInstalacion,Observacion) VALUES ({$vm},{$software},{$dateSql},'{$version}','{$status}','{$note}') ON DUPLICATE KEY UPDATE FechaInstalacion=VALUES(FechaInstalacion),VersionInstalada=VALUES(VersionInstalada),EstadoInstalacion=VALUES(EstadoInstalacion),Observacion=VALUES(Observacion)");
            cap_redirect('Instalacion de software guardada.', 's', 'software');

        case 'save_service':
            $id=(int)($_POST['id']??0); $name=cap_e($_POST['NombreServicio']??'');
            if($name==='') throw new RuntimeException('El nombre del servicio es obligatorio.');
            $description=cap_e($_POST['Descripcion']??''); $category=cap_e($_POST['Categoria']??'');
            $critical=cap_enum($_POST['Criticidad']??'', ['BAJA','MEDIA','ALTA','CRITICA'], 'MEDIA');
            $owner=cap_e($_POST['Responsable']??''); $sla=cap_e($_POST['SLA']??'');
            $status=cap_enum($_POST['EstadoServicio']??'', ['ACTIVO','INACTIVO','MANTENIMIENTO'], 'ACTIVO');
            if($id>0) $db->query("UPDATE servicio_ti SET NombreServicio='{$name}',Descripcion='{$description}',Categoria='{$category}',Criticidad='{$critical}',Responsable='{$owner}',SLA='{$sla}',EstadoServicio='{$status}' WHERE IdServicio={$id}");
            else $db->query("INSERT INTO servicio_ti (NombreServicio,Descripcion,Categoria,Criticidad,Responsable,SLA,EstadoServicio) VALUES ('{$name}','{$description}','{$category}','{$critical}','{$owner}','{$sla}','{$status}')");
            cap_redirect('Servicio TI guardado correctamente.', 's', 'servicios');

        case 'link_service':
            $service=(int)($_POST['IdServicio']??0); $vm=(int)($_POST['IdVM']??0); $role=cap_e($_POST['RolVM']??'');
            if($service<=0||$vm<=0) throw new RuntimeException('Seleccione el servicio y la VM.');
            $db->query("INSERT INTO servicio_vm (IdServicio,IdVM,RolVM) VALUES ({$service},{$vm},'{$role}') ON DUPLICATE KEY UPDATE RolVM=VALUES(RolVM)");
            cap_redirect('Servicio vinculado a la VM.', 's', 'servicios');

        case 'link_service_software':
            $service=(int)($_POST['IdServicio']??0); $software=(int)($_POST['IdSoftware']??0);
            if($service<=0||$software<=0) throw new RuntimeException('Seleccione el servicio y el software que lo soporta.');
            $role=cap_e($_POST['RolSoftware']??''); $note=cap_e($_POST['Observacion']??'');
            $db->query("INSERT INTO servicio_software (IdServicio,IdSoftware,RolSoftware,Observacion) VALUES ({$service},{$software},'{$role}','{$note}') ON DUPLICATE KEY UPDATE RolSoftware=VALUES(RolSoftware),Observacion=VALUES(Observacion)");
            cap_redirect('Software relacionado con el servicio TI.', 's', 'servicios');

        case 'save_config':
            $vm=(int)($_POST['IdVM']??0); $cpu=cap_num($_POST['CPUAsignada']??0); $ram=cap_num($_POST['RAMAsignada']??0); $disk=cap_num($_POST['DiscoAsignado']??0);
            $warning=cap_num($_POST['UmbralAdvertencia']??75); $critical=cap_num($_POST['UmbralCritico']??90);
            if($vm<=0||$cpu<=0||$ram<=0||$disk<=0) throw new RuntimeException('Complete VM, CPU, RAM y disco.');
            if($warning<=0||$warning>=100||$critical<=$warning||$critical>100) throw new RuntimeException('Los umbrales deben ser válidos: advertencia menor que crítico.');
            $owner=cap_e($_POST['Responsable']??''); $note=cap_e($_POST['Observacion']??'');
            $db->query("UPDATE configuracion_capacidad_vm SET Activa=0 WHERE IdVM={$vm}");
            $db->query("INSERT INTO configuracion_capacidad_vm (IdVM,CPUAsignada,RAMAsignada,DiscoAsignado,UmbralAdvertencia,UmbralCritico,Responsable,Observacion,Activa) VALUES ({$vm},{$cpu},{$ram},{$disk},{$warning},{$critical},'{$owner}','{$note}',1)");
            $db->query("UPDATE maquina_virtual SET CPUAsignada={$cpu},RAMAsignada={$ram},DiscoAsignado={$disk} WHERE IdVM={$vm}");
            cap_redirect('Configuración de capacidad actualizada.', 's', 'mediciones');

        case 'save_measurement':
            $vm=(int)($_POST['IdVM']??0); $resource=cap_enum($_POST['Recurso']??'', ['CPU','RAM','DISCO','RED'], '');
            $usage=cap_num($_POST['ConsumoActual']??0);
            if($vm<=0||$resource==='') throw new RuntimeException('Seleccione la VM y el recurso medido.');
            $capacityRows=find_by_sql("SELECT
                    COALESCE(NULLIF(c.CPUAsignada,0),v.CPUAsignada) CPUAsignada,
                    COALESCE(NULLIF(c.RAMAsignada,0),v.RAMAsignada) RAMAsignada,
                    COALESCE(NULLIF(c.DiscoAsignado,0),v.DiscoAsignado) DiscoAsignado,
                    COALESCE(h.FrecuenciaCPUMHz,0) FrecuenciaCPUMHz
                FROM maquina_virtual v
                LEFT JOIN hipervisor h ON h.IdHipervisor=v.IdHipervisor
                LEFT JOIN configuracion_capacidad_vm c ON c.IdVM=v.IdVM AND c.Activa=1
                WHERE v.IdVM={$vm} ORDER BY c.IdConfiguracion DESC LIMIT 1");
            if(!$capacityRows) throw new RuntimeException('La máquina virtual seleccionada no existe.');
            $capacity=$capacityRows[0];
            if($resource==='CPU') {
                $vcpu=(float)$capacity['CPUAsignada']; $frequency=(float)$capacity['FrecuenciaCPUMHz'];
                if($frequency<=0) throw new RuntimeException('Configure primero la frecuencia MHz del servidor físico que aloja esta VM.');
                $total=round($vcpu*$frequency,2);
            } elseif($resource==='RAM') {
                $total=(float)$capacity['RAMAsignada'];
            } elseif($resource==='DISCO') {
                $total=(float)$capacity['DiscoAsignado'];
            } else {
                $total=cap_num($_POST['CapacidadTotal']??0);
            }
            if($total<=0) throw new RuntimeException('La capacidad total configurada debe ser mayor que cero.');
            $percent=round(($usage/$total)*100,2); $percent=min(9999.99,max(0,$percent));
            $configRows=find_by_sql("SELECT UmbralAdvertencia,UmbralCritico FROM configuracion_capacidad_vm WHERE IdVM={$vm} AND Activa=1 ORDER BY IdConfiguracion DESC LIMIT 1");
            $warning=(float)($configRows[0]['UmbralAdvertencia']??75); $critical=(float)($configRows[0]['UmbralCritico']??90);
            $state=$percent >= $critical ? 'CRITICO' : ($percent >= $warning ? 'ADVERTENCIA' : 'NORMAL');
            $threshold=$state==='CRITICO'?$critical:$warning; $note=cap_e($_POST['Observacion']??'');
            $date=trim((string)($_POST['FechaEvaluacion']??''));
            $timestamp=preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',$date)?str_replace('T',' ',$date).':00':date('Y-m-d H:i:s');
            $uid=cap_user_id();
            $db->query("INSERT INTO historial_capacidad (IdVM,FechaEvaluacion,Recurso,CapacidadTotal,ConsumoActual,PorcentajeUso,UmbralAlerta,Estado,RegistradoPor,Observacion) VALUES ({$vm},'{$timestamp}','{$resource}',{$total},{$usage},{$percent},{$threshold},'{$state}',{$uid},'{$note}')");
            $historyId=$db->insert_id();
            if($state!=='NORMAL') {
                $level=$state==='CRITICO'?'ROJO':'AMARILLO';
                $description=cap_e("{$resource} al {$percent}% (umbral {$threshold}%). Revisar capacidad de la VM.");
                $db->query("INSERT INTO alerta_capacidad (IdVM,IdHistorial,Recurso,PorcentajeDetectado,NivelAlerta,Descripcion) VALUES ({$vm},{$historyId},'{$resource}',{$percent},'{$level}','{$description}')");
            }
            cap_redirect('Medición registrada: '.$percent.'% - '.$state.'.', 's', 'mediciones');

        case 'close_alert':
            require_permiso('capacidad_ti','editar');
            $id=(int)($_POST['id']??0); $comment=cap_e($_POST['ComentarioAtencion']??''); $uid=cap_user_id();
            if($id<=0) throw new RuntimeException('Alerta inválida.');
            $db->query("UPDATE alerta_capacidad SET EstadoAlerta='ATENDIDA',AtendidaPor={$uid},FechaAtencion=NOW(),ComentarioAtencion='{$comment}' WHERE IdAlerta={$id}");
            cap_redirect('Alerta marcada como atendida.', 's', 'alertas');

        case 'link_alert_ticket':
            require_permiso('capacidad_ti','editar');
            if (!tiene_permiso('incidencias','ver')) throw new RuntimeException('No tiene permiso para consultar incidencias.');
            $id=(int)($_POST['IdAlerta']??0); $ticketId=(int)($_POST['IdIncidencia']??0);
            $alert=cap_find_alert($id);
            $ticketRows=$ticketId>0 ? find_by_sql("SELECT IdTicket,Folio,Estado FROM inc_tickets
                WHERE IdTicket={$ticketId} AND Estado NOT IN ('RESUELTO','CERRADO','CANCELADO') LIMIT 1") : [];
            if(!$alert||!$ticketRows) throw new RuntimeException('La alerta o la incidencia abierta seleccionada no existe.');
            if((int)$alert['IdIncidencia']>0) {
                cap_redirect('La alerta ya está vinculada a una incidencia.', 'w', 'alertas');
            }
            $db->query("UPDATE alerta_capacidad SET IdIncidencia={$ticketId} WHERE IdAlerta={$id}");
            inc_add_timeline(
                $ticketId,
                'ALERTA_CAPACIDAD',
                'Se vinculó la alerta CAP-'.str_pad((string)$id,7,'0',STR_PAD_LEFT).
                ' de '.$alert['Recurso'].' al '.$alert['PorcentajeDetectado'].'% en '.$alert['NombreVM'].'.',
                false
            );
            cap_redirect('Alerta vinculada a '.$ticketRows[0]['Folio'].'.', 's', 'alertas');

        case 'create_alert_ticket':
            require_permiso('capacidad_ti','editar');
            if (!tiene_permiso('incidencias','crear')) throw new RuntimeException('No tiene permiso para crear incidencias.');
            $id=(int)($_POST['IdAlerta']??0);
            $alert=cap_find_alert($id);
            if(!$alert) throw new RuntimeException('La alerta no existe.');
            if((int)$alert['IdIncidencia']>0) {
                cap_redirect('La alerta ya está vinculada a una incidencia.', 'w', 'alertas');
            }

            $resourceSql=$db->escape((string)$alert['Recurso']);
            $open=find_by_sql("SELECT DISTINCT t.IdTicket,t.Folio
                FROM alerta_capacidad a
                INNER JOIN inc_tickets t ON t.IdTicket=a.IdIncidencia
                WHERE a.IdVM=".(int)$alert['IdVM']."
                  AND a.Recurso='{$resourceSql}'
                  AND t.Estado NOT IN ('RESUELTO','CERRADO','CANCELADO')
                ORDER BY t.IdTicket DESC LIMIT 1");
            if($open) {
                $ticketId=(int)$open[0]['IdTicket'];
                $db->query("UPDATE alerta_capacidad SET IdIncidencia={$ticketId} WHERE IdAlerta={$id}");
                inc_add_timeline(
                    $ticketId,
                    'ALERTA_CAPACIDAD',
                    'Nueva evidencia vinculada: alerta CAP-'.str_pad((string)$id,7,'0',STR_PAD_LEFT).
                    ' de '.$alert['Recurso'].' al '.$alert['PorcentajeDetectado'].'% en '.$alert['NombreVM'].'.',
                    false
                );
                cap_redirect('Ya existía una incidencia abierta para esa VM y recurso. La alerta fue agregada a '.$open[0]['Folio'].'.', 'w', 'alertas');
            }

            $categories=find_by_sql("SELECT IdCategoria FROM inc_categorias WHERE Codigo='HW' AND Activa=1 LIMIT 1");
            if(!$categories) $categories=find_by_sql("SELECT IdCategoria FROM inc_categorias WHERE Activa=1 ORDER BY IdCategoria LIMIT 1");
            if(!$categories) throw new RuntimeException('No existe una categoría activa en Mesa de Ayuda.');

            if(strtoupper((string)$alert['NivelAlerta'])==='ROJO') { $impact=4; $urgency=4; }
            else { $impact=3; $urgency=3; }
            $priority=inc_priority($impact,$urgency);
            $sla=inc_sla_for_priority($priority);
            if(!$sla) throw new RuntimeException('No existe una política SLA para la prioridad '.$priority.'.');

            $user=current_user();
            $userId=(int)($user['id']??0);
            if($userId<=0) throw new RuntimeException('No se pudo identificar al usuario que crea la incidencia.');
            $title=mb_substr('[CAPACIDAD] '.$alert['NombreVM'].' - '.$alert['Recurso'].' al '.$alert['PorcentajeDetectado'].'%',0,180);
            $description="Incidencia originada desde Gestión de Capacidad TI.\n\n".
                'Alerta: CAP-'.str_pad((string)$id,7,'0',STR_PAD_LEFT)."\n".
                'Fecha de medición: '.($alert['FechaEvaluacion']?:$alert['FechaAlerta'])."\n".
                'VM / servidor: '.$alert['NombreVM'].' / '.$alert['Host']."\n".
                'IP: '.($alert['IP_VM']?:'-')."\n".
                'Recurso: '.$alert['Recurso']."\n".
                'Uso detectado: '.$alert['PorcentajeDetectado']."%\n".
                'Nivel: '.$alert['NivelAlerta']."\n\n".
                $alert['Descripcion'];
            $requesterNameSql=$db->escape((string)($user['name']??$user['username']??'TI'));
            $requesterEmailSql=$db->escape((string)($user['correo']??''));
            $requesterAreaSql=$db->escape((string)($user['area']??'TI'));
            $now=time();
            $responseDue=date('Y-m-d H:i:s',$now+(int)$sla['MinutosRespuesta']*60);
            $resolutionDue=date('Y-m-d H:i:s',$now+(int)$sla['MinutosResolucion']*60);
            $machineId=(int)$alert['IdMaquina'];

            $db->query('START TRANSACTION');
            try {
                $db->query("INSERT INTO inc_tickets
                    (Tipo,Canal,Titulo,Descripcion,IdCategoria,IdMaquina,SolicitanteId,SolicitanteNombre,SolicitanteCorreo,SolicitanteArea,Impacto,Urgencia,Prioridad,IdSLA,Estado,GrupoAsignacion,FechaObjetivoRespuesta,FechaObjetivoResolucion,CreadoPor,ActualizadoPor)
                    VALUES ('INCIDENTE','MONITOREO','".$db->escape($title)."','".$db->escape($description)."',".(int)$categories[0]['IdCategoria'].",".($machineId>0?$machineId:'NULL').",{$userId},'{$requesterNameSql}','{$requesterEmailSql}','{$requesterAreaSql}',{$impact},{$urgency},'{$priority}',".(int)$sla['IdSLA'].",'NUEVO','SOPORTE TI','{$responseDue}','{$resolutionDue}',{$userId},{$userId})");
                $ticketId=(int)$db->insert_id();
                $folio='INC-'.date('Y').'-'.str_pad((string)$ticketId,6,'0',STR_PAD_LEFT);
                $db->query("UPDATE inc_tickets SET Folio='".$db->escape($folio)."' WHERE IdTicket={$ticketId}");
                $db->query("UPDATE alerta_capacidad SET IdIncidencia={$ticketId} WHERE IdAlerta={$id}");
                inc_add_timeline($ticketId,'CREACION','Incidencia creada desde Gestión de Capacidad TI por la alerta CAP-'.str_pad((string)$id,7,'0',STR_PAD_LEFT).'.',true,null,'NUEVO');
                $db->query('COMMIT');
            } catch(Throwable $error) {
                $db->query('ROLLBACK');
                throw $error;
            }
            cap_redirect($folio.' fue creada y vinculada a la alerta.', 's', 'alertas');

        case 'delete_record':
            require_permiso('capacidad_ti','eliminar');
            $id=(int)($_POST['id']??0); $entity=(string)($_POST['entity']??'');
            $map=['hypervisor'=>['hipervisor','IdHipervisor'],'vm'=>['maquina_virtual','IdVM'],'software'=>['software','IdSoftware'],'vm_software'=>['vm_software','IdVMSoftware'],'application'=>['aplicacion','IdAplicacion'],'component'=>['software_componente','IdComponente'],'application_vm'=>['aplicacion_vm','IdAplicacionVM'],'service'=>['servicio_ti','IdServicio'],'service_vm'=>['servicio_vm','IdServicioVM'],'service_software'=>['servicio_software','IdServicioSoftware'],'service_application'=>['servicio_aplicacion','IdServicioAplicacion']];
            if($id<=0||!isset($map[$entity])) throw new RuntimeException('Registro inválido.');
            [$table,$key]=$map[$entity]; $db->query("DELETE FROM {$table} WHERE {$key}={$id}");
            cap_redirect('Registro eliminado correctamente.');

        default:
            throw new RuntimeException('Acción no reconocida.');
    }
} catch (Throwable $e) {
    cap_redirect($e->getMessage(), 'd');
}
