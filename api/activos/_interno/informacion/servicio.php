<?php
require_once __DIR__ . '/../comun.php';

function activos_informacion_catalogos(): array
{
$tiposInfo = ['BASE DE DATOS','DOCUMENTOS DE INGENIERIA / PLANOS','CONTABLE / FINANCIERA','RECURSOS HUMANOS','CONTRATOS / LEGAL','CORREO ELECTRONICO','CONFIGURACIONES / CREDENCIALES','REGISTROS / LOGS','PROYECTOS / CLIENTES','OTRO'];
$formatos = ['DIGITAL','FISICO (PAPEL)','MIXTO'];
$estados = ['ACTIVO','INACTIVO','EN REVISION','ARCHIVADO'];
$tiposAlmacen = ['MAQUINA', 'APLICACION', 'SERVICIO', 'UBICACION'];


return compact('tiposInfo','formatos','estados','tiposAlmacen');
}

function activos_informacion_listar(): array
{
 global $db;
 extract(activos_informacion_catalogos(), EXTR_SKIP);
$activos = find_by_sql("SELECT i.*,
    (SELECT COUNT(*) FROM activos_relaciones r WHERE r.TipoOrigen='COPIA' AND r.TipoRelacion='RESPALDA' AND r.TipoDestino='INFORMACION' AND r.IdDestino=i.IdActivoInformacion) TotalRespaldos,
    ".iso_eval_cols('INFORMACION', 'i.IdActivoInformacion')."
    FROM activos_informacion i ORDER BY (i.EstadoActivo='ACTIVO') DESC, i.NombreActivo");
$relMap = iso_rel_map('INFORMACION', 'ALMACENADA_EN');

$sinRespaldo = count(array_filter($activos, fn($a) => $a['EstadoActivo'] === 'ACTIVO' && (int)$a['TotalRespaldos'] === 0));
$sinEvaluar = count(array_filter($activos, fn($a) => empty($a['Confidencialidad'])));
$altaCrit = count(array_filter($activos, fn($a) => in_array($a['Criticidad'], ['ALTO', 'MUY ALTO'], true)));
$datosPers = count(array_filter($activos, fn($a) => (int)$a['DatosPersonales'] === 1));


 return array_merge(activos_informacion_catalogos(), compact('activos','relMap','sinRespaldo','sinEvaluar','altaCrit','datosPers'));
}

function activos_informacion_guardar(): array
{
 global $db, $session;
 $messages = [];
 extract(activos_informacion_catalogos(), EXTR_SKIP);

    $errors = [];
    if (iso_p('action') !== 'save_info') $errors[] = 'Operación no reconocida.';
    $id = (int)($_POST['IdActivoInformacion'] ?? 0);
    if (!tiene_permiso('activos', $id > 0 ? 'editar' : 'crear')) $errors[] = 'No tiene permiso para guardar activos de información.';
    $code = strtoupper(iso_p('CodigoActivo'));
    $name = iso_p('NombreActivo');
    $type = strtoupper(iso_p('TipoInformacion'));
    $format = strtoupper(iso_p('FormatoSoporte'));
    $state = strtoupper(iso_p('EstadoActivo'));
    if ($code === '' || mb_strlen($code, 'UTF-8') > 30) $errors[] = 'Ingrese un código de hasta 30 caracteres.';
    if ($name === '' || mb_strlen($name, 'UTF-8') > 150) $errors[] = 'Ingrese el nombre del activo (máx. 150 caracteres).';
    if (!in_array($type, $tiposInfo, true)) $errors[] = 'Seleccione un tipo de información válido.';
    if ($format !== '' && !in_array($format, $formatos, true)) $errors[] = 'Formato inválido.';
    if (!in_array($state, $estados, true)) $errors[] = 'Estado inválido.';
    if (!$errors && find_by_sql("SELECT IdActivoInformacion FROM activos_informacion WHERE CodigoActivo='".$db->escape($code)."' AND IdActivoInformacion<>{$id} LIMIT 1")) $errors[] = "El código {$code} ya está registrado.";
    $cia = iso_read_cia($errors);

    if (!$errors) {
        $cols = [
            'CodigoActivo' => "'".$db->escape($code)."'",
            'NombreActivo' => "'".$db->escape($name)."'",
            'TipoInformacion' => "'".$db->escape($type)."'",
            'Descripcion' => iso_sql_str(iso_p('Descripcion')),
            'Proceso' => iso_sql_str(iso_p('Proceso')),
            'AreaResponsable' => iso_sql_str(iso_p('AreaResponsable')),
            'PropietarioInformacion' => iso_sql_str(iso_p('PropietarioInformacion')),
            'CustodioTecnico' => iso_sql_str(iso_p('CustodioTecnico')),
            'FormatoSoporte' => iso_sql_str($format),
            'UbicacionLogica' => iso_sql_str(iso_p('UbicacionLogica')),
            'DatosPersonales' => (string)iso_flag('DatosPersonales'),
            'RequisitoLegal' => iso_sql_str(iso_p('RequisitoLegal')),
            'PlazoRetencion' => iso_sql_str(iso_p('PlazoRetencion')),
            'EstadoActivo' => "'".$db->escape($state)."'",
            'Observaciones' => iso_sql_str(iso_p('Observaciones')),
        ] + iso_cia_cols($cia);
        $saved = iso_upsert('activos_informacion', 'IdActivoInformacion', $id, $cols);
        if ($saved > 0) {
            iso_save_eval('INFORMACION', $saved, $cia);
            iso_rel_sync('INFORMACION', $saved, 'ALMACENADA_EN', (array)($_POST['AlmacenadaEn'] ?? []), $tiposAlmacen);
            activos_modulo_mensaje($messages, 's', $id ? 'Activo de información actualizado.' : 'Activo de información registrado.');
        } else $errors[] = 'No se pudo guardar el activo de información.';
    }
    if ($errors) activos_modulo_mensaje($messages, 'd', $errors);
    return activos_modulo_resultado($messages);

 return activos_modulo_resultado($messages);
}
