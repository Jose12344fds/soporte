<?php
require_once __DIR__ . '/../comun.php';
const COPIA_PRUEBA_MAX_DIAS = 180;

function activos_copias_catalogos(): array
{
$tiposCopia = ['COMPLETA','INCREMENTAL','DIFERENCIAL','SNAPSHOT','REPLICACION','EXPORTACION BD'];
$medios = ['NAS','SERVIDOR','DISCO EXTERNO','CINTA','NUBE','OTRO'];
$frecuencias = ['DIARIA','SEMANAL','QUINCENAL','MENSUAL','TRIMESTRAL','ANUAL','BAJO DEMANDA'];
$estadosCopia = ['ACTIVA','SUSPENDIDA','RETIRADA'];
$resultados = ['EXITOSO','PARCIAL','FALLIDO'];
$tiposRespaldables = ['MAQUINA', 'APLICACION', 'SERVICIO', 'INFORMACION'];


return compact('tiposCopia','medios','frecuencias','estadosCopia','resultados','tiposRespaldables');
}

function activos_copias_listar(): array
{
 global $db;
 extract(activos_copias_catalogos(), EXTR_SKIP);
$copias = find_by_sql("SELECT c.*, CONCAT(u.CodigoUbicacion,' · ',u.NombreUbicacion) UbicacionDestino, ".iso_eval_cols('COPIA', 'c.IdCopia')."
    FROM copias_seguridad c LEFT JOIN ubicaciones_fisicas u ON u.IdUbicacion=c.IdUbicacionDestino
    ORDER BY (c.EstadoCopia='ACTIVA') DESC, c.NombreCopia");
$registros = find_by_sql("SELECT r.*, c.CodigoCopia, c.NombreCopia FROM copias_seguridad_registros r INNER JOIN copias_seguridad c ON c.IdCopia=r.IdCopia ORDER BY r.FechaEvento DESC, r.IdRegistro DESC LIMIT 500");
$ubicaciones = find_by_sql("SELECT IdUbicacion,CodigoUbicacion,NombreUbicacion FROM ubicaciones_fisicas WHERE EstadoUbicacion='ACTIVA' ORDER BY NombreUbicacion");
$relMap = iso_rel_map('COPIA', 'RESPALDA');

$limite = date('Y-m-d', strtotime('-'.COPIA_PRUEBA_MAX_DIAS.' days'));
$activas = array_filter($copias, fn($c) => $c['EstadoCopia'] === 'ACTIVA');
$sinExterna = count(array_filter($activas, fn($c) => !$c['CopiaExterna']));
$sinCifrar = count(array_filter($activas, fn($c) => !$c['Cifrado']));
$sinPrueba = count(array_filter($activas, fn($c) => empty($c['FechaUltimaPrueba']) || $c['FechaUltimaPrueba'] < $limite));
$ultimaFallida = count(array_filter($activas, fn($c) => $c['ResultadoUltimaEjecucion'] === 'FALLIDO'));
$copiaOptions = '';
foreach ($copias as $c) $copiaOptions .= '<option value="'.(int)$c['IdCopia'].'">'.iso_h($c['CodigoCopia'].' · '.$c['NombreCopia']).'</option>';


 return array_merge(activos_copias_catalogos(), compact('activas','limite','copias','registros','ubicaciones','relMap','sinExterna','sinCifrar','sinPrueba','ultimaFallida','copiaOptions'));
}

function activos_copias_guardar(): array
{
 global $db, $session;
 $messages = [];
 extract(activos_copias_catalogos(), EXTR_SKIP);

    $action = iso_p('action');
    $errors = [];

    if ($action === 'save_backup') {
        $id = (int)($_POST['IdCopia'] ?? 0);
        if (!tiene_permiso('activos', $id > 0 ? 'editar' : 'crear')) $errors[] = 'No tiene permiso para guardar copias de seguridad.';
        $code = strtoupper(iso_p('CodigoCopia'));
        $name = iso_p('NombreCopia');
        $type = strtoupper(iso_p('TipoCopia'));
        $state = strtoupper(iso_p('EstadoCopia'));
        $media = strtoupper(iso_p('MedioAlmacenamiento'));
        $freq = strtoupper(iso_p('Frecuencia'));
        $hora = iso_p('HoraEjecucion');
        if ($code === '' || mb_strlen($code, 'UTF-8') > 30) $errors[] = 'Ingrese un código de hasta 30 caracteres.';
        if ($name === '' || mb_strlen($name, 'UTF-8') > 150) $errors[] = 'Ingrese el nombre de la copia (máx. 150 caracteres).';
        if (!in_array($type, $tiposCopia, true)) $errors[] = 'Tipo de copia inválido.';
        if (!in_array($state, $estadosCopia, true)) $errors[] = 'Estado inválido.';
        if ($media !== '' && !in_array($media, $medios, true)) $errors[] = 'Medio de almacenamiento inválido.';
        if ($freq !== '' && !in_array($freq, $frecuencias, true)) $errors[] = 'Frecuencia inválida.';
        if ($hora !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) $errors[] = 'La hora de ejecución no es válida.';
        if (!$errors && find_by_sql("SELECT IdCopia FROM copias_seguridad WHERE CodigoCopia='".$db->escape($code)."' AND IdCopia<>{$id} LIMIT 1")) $errors[] = "El código {$code} ya está registrado.";
        $cia = iso_read_cia($errors);

        if (!$errors) {
            $cols = [
                'CodigoCopia' => "'".$db->escape($code)."'",
                'NombreCopia' => "'".$db->escape($name)."'",
                'TipoCopia' => "'".$db->escape($type)."'",
                'Alcance' => iso_sql_str(iso_p('Alcance')),
                'Herramienta' => iso_sql_str(iso_p('Herramienta')),
                'MedioAlmacenamiento' => iso_sql_str($media),
                'Frecuencia' => iso_sql_str($freq),
                'HoraEjecucion' => $hora === '' ? 'NULL' : "'".$db->escape($hora)."'",
                'RetencionDias' => iso_sql_int(iso_p('RetencionDias')),
                'IdUbicacionDestino' => iso_sql_int(iso_p('IdUbicacionDestino')),
                'DetalleDestino' => iso_sql_str(iso_p('DetalleDestino')),
                'CopiaExterna' => (string)iso_flag('CopiaExterna'),
                'Cifrado' => (string)iso_flag('Cifrado'),
                'RPOHoras' => iso_sql_int(iso_p('RPOHoras')),
                'RTOHoras' => iso_sql_int(iso_p('RTOHoras')),
                'Responsable' => iso_sql_str(iso_p('Responsable')),
                'Proveedor' => iso_sql_str(iso_p('Proveedor')),
                'EstadoCopia' => "'".$db->escape($state)."'",
                'Observaciones' => iso_sql_str(iso_p('Observaciones')),
            ] + iso_cia_cols($cia);
            $saved = iso_upsert('copias_seguridad', 'IdCopia', $id, $cols);
            if ($saved > 0) {
                iso_save_eval('COPIA', $saved, $cia);
                iso_rel_sync('COPIA', $saved, 'RESPALDA', (array)($_POST['Respalda'] ?? []), $tiposRespaldables);
                activos_modulo_mensaje($messages, 's', $id ? 'Copia de seguridad actualizada.' : 'Copia de seguridad registrada.');
            } else $errors[] = 'No se pudo guardar la copia de seguridad.';
        }
    } elseif ($action === 'save_record') {
        $copia = (int)($_POST['IdCopia'] ?? 0);
        $tipo = strtoupper(iso_p('TipoRegistro'));
        $res = strtoupper(iso_p('Resultado'));
        $fecha = str_replace('T', ' ', iso_p('FechaEvento'));
        if (!tiene_permiso('activos', 'crear')) $errors[] = 'No tiene permiso para registrar eventos.';
        if ($copia <= 0 || !find_by_sql("SELECT IdCopia FROM copias_seguridad WHERE IdCopia={$copia} LIMIT 1")) $errors[] = 'Seleccione una copia válida.';
        if (!in_array($tipo, ['EJECUCION', 'PRUEBA_RESTAURACION'], true)) $errors[] = 'Tipo de registro inválido.';
        if (!in_array($res, $resultados, true)) $errors[] = 'Resultado inválido.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $fecha)) $errors[] = 'La fecha del evento no es válida.';
        if (!$errors) {
            if (strlen($fecha) === 10) $fecha .= ' 00:00:00';
            $ok = iso_upsert('copias_seguridad_registros', 'IdRegistro', 0, [
                'IdCopia' => (string)$copia,
                'TipoRegistro' => "'".$db->escape($tipo)."'",
                'FechaEvento' => "'".$db->escape($fecha)."'",
                'Resultado' => "'".$db->escape($res)."'",
                'TamanoGB' => iso_sql_num(iso_p('TamanoGB')),
                'DuracionMin' => iso_sql_int(iso_p('DuracionMin')),
                'EjecutadoPor' => "'".$db->escape(iso_p('EjecutadoPor') ?: iso_user())."'",
                'Evidencia' => iso_sql_str(iso_p('Evidencia')),
                'Observaciones' => iso_sql_str(iso_p('Observaciones')),
            ]);
            if ($ok > 0) {
                // Mantiene el resumen en la ficha de la copia con el evento más reciente.
                $f = $db->escape($fecha); $r = $db->escape($res);
                if ($tipo === 'EJECUCION') $db->query("UPDATE copias_seguridad SET FechaUltimaEjecucion='{$f}',ResultadoUltimaEjecucion='{$r}' WHERE IdCopia={$copia} AND (FechaUltimaEjecucion IS NULL OR FechaUltimaEjecucion<='{$f}')");
                else $db->query("UPDATE copias_seguridad SET FechaUltimaPrueba=DATE('{$f}'),ResultadoUltimaPrueba='{$r}' WHERE IdCopia={$copia} AND (FechaUltimaPrueba IS NULL OR FechaUltimaPrueba<=DATE('{$f}'))");
                activos_modulo_mensaje($messages, 's', 'Evento registrado.');
            } else $errors[] = 'No se pudo registrar el evento.';
        }
    } else {
        $errors[] = 'Operación no reconocida.';
    }
    if ($errors) activos_modulo_mensaje($messages, 'd', $errors);
    return activos_modulo_resultado($messages);

 return activos_modulo_resultado($messages);
}
