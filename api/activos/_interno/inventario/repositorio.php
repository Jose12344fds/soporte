<?php
declare(strict_types=1);

function iso_inventory_map(array $rows, string $id): array {
    $map = [];
    foreach ($rows as $row) $map[(int)$row[$id]] = $row;
    return $map;
}

function iso_inventory_relation_text(array $relations, array $catalog): string {
    $labels = [];
    foreach ($relations as $relation) {
        [$type, $id] = explode(':', $relation, 2);
        $labels[] = $catalog[$type][(int)$id] ?? $relation;
    }
    return implode(' | ', array_values(array_unique(array_filter($labels))));
}

function iso_inventory_rows(): array {

    $base = find_by_sql("SELECT * FROM vw_inventario_activos_iso ORDER BY FIELD(TipoActivo,'MAQUINA','APLICACION','SERVICIO','INFORMACION','COPIA','UBICACION'), Codigo") ?: [];
    $evaluations = find_by_sql("SELECT e.* FROM evaluaciones_cia e INNER JOIN (SELECT TipoActivo,IdActivo,MAX(IdEvaluacion) IdEvaluacion FROM evaluaciones_cia GROUP BY TipoActivo,IdActivo) x ON x.IdEvaluacion=e.IdEvaluacion") ?: [];
    $evalMap = [];
    foreach ($evaluations as $evaluation) $evalMap[$evaluation['TipoActivo'].':'.(int)$evaluation['IdActivo']] = $evaluation;

    $machines = iso_inventory_map(find_by_sql("SELECT m.*,aa.Codigo AreaCodigo,aa.Nombre AreaNombre,u.name PropietarioAsignado,u.area UsuarioArea,am.Comentarios AsignacionObservaciones,uf.CodigoUbicacion,uf.NombreUbicacion,uf.TipoUbicacion FROM maquina m LEFT JOIN areas_activo aa ON aa.IdAreaActivo=m.IdAreaActivo LEFT JOIN (SELECT IdMaquina,MAX(IdAsMaq) IdAsMaq FROM asig_maquina GROUP BY IdMaquina) lasta ON lasta.IdMaquina=m.IdMaquina LEFT JOIN asig_maquina am ON am.IdAsMaq=lasta.IdAsMaq LEFT JOIN users u ON u.id=am.id LEFT JOIN ubicaciones_fisicas uf ON uf.IdUbicacion=m.IdUbicacion") ?: [], 'IdMaquina');
    $applications = iso_inventory_map(find_by_sql("SELECT a.*,cap.NombreAplicacion AplicacionCapacidad,(SELECT GROUP_CONCAT(DISTINCT m.Host ORDER BY m.Host SEPARATOR ' | ') FROM activos_aplicaciones_instalaciones ai INNER JOIN maquina m ON m.IdMaquina=ai.IdMaquina WHERE ai.IdAplicacionActivo=a.IdAplicacionActivo AND ai.EstadoInstalacion='ACTIVA') EquiposInstalados,(SELECT COUNT(*) FROM activos_aplicaciones_contratos c WHERE c.IdAplicacionActivo=a.IdAplicacionActivo) TotalContratos,(SELECT COUNT(*) FROM activos_aplicaciones_contratos c WHERE c.IdAplicacionActivo=a.IdAplicacionActivo AND c.EstadoContrato='VIGENTE') ContratosVigentes FROM activos_aplicaciones a LEFT JOIN aplicacion cap ON cap.IdAplicacion=a.IdAplicacionCapacidad") ?: [], 'IdAplicacionActivo');
    $services = iso_inventory_map(find_by_sql("SELECT s.*,(SELECT COUNT(*) FROM activos_servicios_contratos c WHERE c.IdServicio=s.IdServicio) TotalContratos,(SELECT COUNT(*) FROM activos_servicios_contratos c WHERE c.IdServicio=s.IdServicio AND c.EstadoContrato='VIGENTE') ContratosVigentes FROM servicios_ti s") ?: [], 'IdServicio');
    $information = iso_inventory_map(find_by_sql('SELECT * FROM activos_informacion') ?: [], 'IdActivoInformacion');
    $backups = iso_inventory_map(find_by_sql("SELECT c.*,u.CodigoUbicacion,u.NombreUbicacion,u.TipoUbicacion FROM copias_seguridad c LEFT JOIN ubicaciones_fisicas u ON u.IdUbicacion=c.IdUbicacionDestino") ?: [], 'IdCopia');
    $locations = iso_inventory_map(find_by_sql("SELECT u.*,(SELECT COUNT(*) FROM maquina m WHERE m.IdUbicacion=u.IdUbicacion) TotalEquipos,(SELECT COUNT(*) FROM copias_seguridad c WHERE c.IdUbicacionDestino=u.IdUbicacion) TotalCopias FROM ubicaciones_fisicas u") ?: [], 'IdUbicacion');
    $roles = find_by_sql('SELECT * FROM activos_roles_clave ORDER BY CodigoRol') ?: [];

    $catalog = iso_asset_catalog();
    $relationMap = [];
    foreach ((find_by_sql('SELECT TipoOrigen,IdOrigen,TipoDestino,IdDestino,TipoRelacion FROM activos_relaciones ORDER BY IdRelacion') ?: []) as $relation) {
        $key = $relation['TipoOrigen'].':'.(int)$relation['IdOrigen'].':'.$relation['TipoRelacion'];
        $relationMap[$key][] = $relation['TipoDestino'].':'.(int)$relation['IdDestino'];
    }

    $result = [];
    foreach ($base as $row) {
        $type = (string)$row['TipoActivo'];
        $id = (int)$row['IdActivo'];
        $evaluation = $evalMap[$type.':'.$id] ?? [];
        $item = [
            'IdActivo'=>(string)$row['Codigo'], 'Estado'=>(string)$row['Estado'], 'Proceso'=>'',
            'Propietario'=>(string)($row['Responsable'] ?? ''), 'NombreActivo'=>(string)$row['Nombre'],
            'Descripcion'=>'', 'TipoActivo'=>ISO_TIPOS_ACTIVO[$type] ?? $type, 'Ubicacion'=>'',
            'SistemaRepositorio'=>'', 'DatosPersonales'=>'', 'RequisitoLegal'=>'',
            'Confidencialidad'=>$row['Confidencialidad'], 'Integridad'=>$row['Integridad'], 'Disponibilidad'=>$row['Disponibilidad'],
            'Criticidad'=>(string)($row['Criticidad'] ?: 'SIN EVALUAR'), 'Clasificacion'=>(string)($row['Clasificacion'] ?? ''),
            'UltimaRevision'=>(string)($evaluation['FechaEvaluacion'] ?? ''), 'Observaciones'=>(string)($evaluation['Justificacion'] ?? ''),
            '_Tipo'=>$type, '_Id'=>$id, '_ProximaRevision'=>(string)($evaluation['ProximaRevision'] ?? ''),
        ];

        if ($type === 'MAQUINA' && isset($machines[$id])) {
            $d = $machines[$id];
            $item['Proceso'] = trim(($d['AreaCodigo'] ?? '').(($d['AreaNombre'] ?? '') ? ' - '.$d['AreaNombre'] : ''));
            $item['Propietario'] = (string)($d['PropietarioAsignado'] ?? '');
            $item['Descripcion'] = trim(implode(' | ', array_filter([$d['Tipo_Activo'] ?? '',$d['Modelo_Placa'] ?? '',$d['Procesador'] ?? '',($d['Mem_Ram'] ?? '') !== '' ? $d['Mem_Ram'].' GB RAM' : ''])));
            $item['Ubicacion'] = !empty($d['NombreUbicacion']) ? trim($d['CodigoUbicacion'].' - '.$d['NombreUbicacion'].' ('.$d['TipoUbicacion'].')') : (string)($d['Ubicacion'] ?? '');
            $item['SistemaRepositorio'] = trim($d['Host'].(!empty($d['Ip']) ? ' | IP '.$d['Ip'] : ''));
            $item['Observaciones'] = trim(implode(' | ', array_filter([$d['AsignacionObservaciones'] ?? '',$item['Observaciones']])));
        } elseif ($type === 'APLICACION' && isset($applications[$id])) {
            $d = $applications[$id];
            $item['Proceso'] = (string)($d['Entorno'] ?? '');
            $item['Descripcion'] = (string)($d['Descripcion'] ?? '');
            $item['Ubicacion'] = (string)($d['EquiposInstalados'] ?? (($d['TipoAplicacion'] ?? '') === 'SAAS' ? 'NUBE / PROVEEDOR' : ''));
            $item['SistemaRepositorio'] = (string)($d['AplicacionCapacidad'] ?? '');
            $item['RequisitoLegal'] = ((int)$d['TotalContratos'] > 0) ? ((int)$d['ContratosVigentes'].' de '.(int)$d['TotalContratos'].' contrato(s) vigente(s)') : '';
            $item['Observaciones'] = trim(implode(' | ', array_filter([$d['CustodioTecnico'] ? 'Custodio: '.$d['CustodioTecnico'] : '',$d['FechaFinSoporte'] ? 'Fin soporte: '.$d['FechaFinSoporte'] : '',$item['Observaciones']])));
        } elseif ($type === 'SERVICIO' && isset($services[$id])) {
            $d = $services[$id];
            $item['Proceso'] = (string)($d['CategoriaServicio'] ?? '');
            $item['Descripcion'] = (string)($d['Descripcion'] ?? '');
            $item['Ubicacion'] = (string)($d['Ubicacion'] ?? '');
            $item['SistemaRepositorio'] = trim(implode(' | ', array_filter([$d['Proveedor'] ? 'Proveedor: '.$d['Proveedor'] : '',$d['SLA'] ? 'SLA: '.$d['SLA'] : ''])));
            $item['RequisitoLegal'] = ((int)$d['TotalContratos'] > 0) ? ((int)$d['ContratosVigentes'].' de '.(int)$d['TotalContratos'].' contrato(s) vigente(s)') : '';
            $item['Observaciones'] = trim(implode(' | ', array_filter([$d['Observaciones'] ?? '',$item['Observaciones']])));
        } elseif ($type === 'INFORMACION' && isset($information[$id])) {
            $d = $information[$id];
            $stored = iso_inventory_relation_text($relationMap['INFORMACION:'.$id.':ALMACENADA_EN'] ?? [], $catalog);
            $item['Proceso'] = trim(implode(' | ', array_filter([$d['Proceso'] ?? '',$d['AreaResponsable'] ?? ''])));
            $item['Propietario'] = (string)($d['PropietarioInformacion'] ?? '');
            $item['Descripcion'] = (string)($d['Descripcion'] ?? '');
            $item['Ubicacion'] = (string)($d['UbicacionLogica'] ?? '');
            $item['SistemaRepositorio'] = $stored;
            $item['DatosPersonales'] = (int)$d['DatosPersonales'] === 1 ? 'SI' : 'NO';
            $item['RequisitoLegal'] = (string)($d['RequisitoLegal'] ?? '');
            $item['Observaciones'] = trim(implode(' | ', array_filter([$d['Observaciones'] ?? '',$d['CustodioTecnico'] ? 'Custodio: '.$d['CustodioTecnico'] : '',$d['PlazoRetencion'] ? 'Retencion: '.$d['PlazoRetencion'] : '',$item['Observaciones']])));
        } elseif ($type === 'COPIA' && isset($backups[$id])) {
            $d = $backups[$id];
            $covered = iso_inventory_relation_text($relationMap['COPIA:'.$id.':RESPALDA'] ?? [], $catalog);
            $item['Descripcion'] = (string)($d['Alcance'] ?? '');
            $item['Ubicacion'] = trim(implode(' | ', array_filter([!empty($d['NombreUbicacion']) ? $d['CodigoUbicacion'].' - '.$d['NombreUbicacion'] : '',$d['DetalleDestino'] ?? ''])));
            $item['SistemaRepositorio'] = trim(implode(' | ', array_filter([$covered,$d['Herramienta'] ? 'Herramienta: '.$d['Herramienta'] : '',$d['MedioAlmacenamiento'] ?? ''])));
            $item['Observaciones'] = trim(implode(' | ', array_filter([$d['Frecuencia'] ? 'Frecuencia: '.$d['Frecuencia'] : '',$d['Observaciones'] ?? '',$item['Observaciones']])));
        } elseif ($type === 'UBICACION' && isset($locations[$id])) {
            $d = $locations[$id];
            $item['Proceso'] = (string)($d['Area'] ?? '');
            $item['Descripcion'] = (string)($d['DetalleUbicacion'] ?? '');
            $item['Ubicacion'] = trim(implode(' | ', array_filter([$d['Sede'] ?? '',$d['Edificio'] ?? '',$d['Piso'] ? 'Piso '.$d['Piso'] : ''])));
            $item['SistemaRepositorio'] = (int)$d['TotalEquipos'].' equipo(s) | '.(int)$d['TotalCopias'].' copia(s)';
            $item['Observaciones'] = trim(implode(' | ', array_filter([$d['ControlAcceso'] ? 'Control: '.$d['ControlAcceso'] : '',$item['Observaciones']])));
        }
        $result[] = $item;
    }
    foreach ($roles as $role) {
        $id = (int)$role['IdRol'];
        $evaluation = $evalMap['ROL:'.$id] ?? [];
        $people = [];
        if (trim((string)$role['TitularActual']) !== '') $people[] = 'Titular actual: '.$role['TitularActual'];
        if (trim((string)$role['Suplente']) !== '') $people[] = 'Suplente: '.$role['Suplente'];
        $result[] = [
            'IdActivo'=>(string)$role['CodigoRol'], 'Estado'=>(string)$role['EstadoRol'],
            'Proceso'=>(string)$role['Proceso'], 'Propietario'=>(string)$role['Propietario'],
            'NombreActivo'=>(string)$role['NombreRol'], 'Descripcion'=>(string)$role['Descripcion'],
            'TipoActivo'=>ISO_TIPOS_ACTIVO['ROL'], 'Ubicacion'=>(string)($role['UbicacionLogica'] ?? ''),
            'SistemaRepositorio'=>implode(' | ', $people),
            'DatosPersonales'=>(int)$role['DatosPersonales'] === 1 ? 'SI - incluye nombre del titular/suplente' : 'NO CONTIENE DATOS PERSONALES',
            'RequisitoLegal'=>(string)($role['RequisitoLegal'] ?? ''),
            'Confidencialidad'=>$role['Confidencialidad'], 'Integridad'=>$role['Integridad'], 'Disponibilidad'=>$role['Disponibilidad'],
            'Criticidad'=>(string)($role['Criticidad'] ?: 'SIN EVALUAR'), 'Clasificacion'=>(string)($role['Clasificacion'] ?? ''),
            'UltimaRevision'=>(string)($evaluation['FechaEvaluacion'] ?? ''),
            'Observaciones'=>trim(implode(' | ', array_filter([$role['Observaciones'] ?? '',$evaluation['Justificacion'] ?? '']))),
            '_Tipo'=>'ROL', '_Id'=>$id, '_ProximaRevision'=>(string)($evaluation['ProximaRevision'] ?? ''),
        ];
    }
    usort($result, static fn(array $a, array $b): int => strnatcasecmp($a['IdActivo'], $b['IdActivo']));
    return $result;
}
