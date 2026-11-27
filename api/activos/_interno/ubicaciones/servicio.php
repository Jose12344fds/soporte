<?php
require_once __DIR__ . '/../comun.php';
require_once __DIR__ . '/imagenes.php';

function activos_ubicaciones_catalogos(): array
{
$tiposUbicacion = ['DATA CENTER','SALA DE SERVIDORES','OFICINA','ALMACEN','PLANTA','GARITA','SITIO EXTERNO','NUBE / PROVEEDOR','DOMICILIO','OTRO'];
$estadosUbicacion = ['ACTIVA','INACTIVA','EN REVISION'];


return compact('tiposUbicacion','estadosUbicacion');
}

function activos_ubicaciones_listar(): array
{
 global $db;
 extract(activos_ubicaciones_catalogos(), EXTR_SKIP);
$ubicaciones = find_by_sql("SELECT u.*,
    (SELECT COUNT(*) FROM maquina m WHERE m.IdUbicacion=u.IdUbicacion) TotalEquipos,
    (SELECT COUNT(*) FROM copias_seguridad c WHERE c.IdUbicacionDestino=u.IdUbicacion) TotalCopias,
    ".iso_eval_cols('UBICACION', 'u.IdUbicacion')."
    FROM ubicaciones_fisicas u ORDER BY (u.EstadoUbicacion='ACTIVA') DESC, u.NombreUbicacion");
$locationImageMap = ubi_img_map();
foreach ($ubicaciones as &$locationRow) $locationRow['Imagenes'] = $locationImageMap[(int)$locationRow['IdUbicacion']] ?? [];
unset($locationRow);
$maquinas = find_by_sql("SELECT IdMaquina,Host,Tipo_Activo,CategoriaEquipo,Ubicacion,IdUbicacion FROM maquina ORDER BY CategoriaEquipo,Host");
$roles = find_by_sql("SELECT r.*,".iso_eval_cols('ROL', 'r.IdRol')." FROM activos_roles_clave r ORDER BY (r.EstadoRol='ACTIVO') DESC,r.NombreRol") ?: [];

$totalSinUbicacion = count(array_filter($maquinas, fn($m) => empty($m['IdUbicacion'])));
$totalSinEvaluar = count(array_filter($ubicaciones, fn($u) => empty($u['Confidencialidad'])));
$totalDC = count(array_filter($ubicaciones, fn($u) => in_array($u['TipoUbicacion'], ['DATA CENTER','SALA DE SERVIDORES'], true)));
$ubicOptions = '';
foreach ($ubicaciones as $u) if ($u['EstadoUbicacion'] === 'ACTIVA') $ubicOptions .= '<option value="'.(int)$u['IdUbicacion'].'">'.iso_h($u['CodigoUbicacion'].' · '.$u['NombreUbicacion']).'</option>';
$categorias = array_values(array_unique(array_filter(array_column($maquinas, 'CategoriaEquipo'))));


 return array_merge(activos_ubicaciones_catalogos(), compact('ubicaciones','locationImageMap','maquinas','roles','totalSinUbicacion','totalSinEvaluar','totalDC','ubicOptions','categorias'));
}

function activos_ubicaciones_guardar(): array
{
 global $db, $session;
 $messages = [];
 extract(activos_ubicaciones_catalogos(), EXTR_SKIP);

    $action = iso_p('action');
    $errors = [];

    if ($action === 'save_location') {
        $id = (int)($_POST['IdUbicacion'] ?? 0);
        $locationFiles = ubi_img_normalize_files($_FILES['ImagenesUbicacion'] ?? []);
        try { ubi_img_validate_files($locationFiles); } catch (Throwable $error) { $errors[] = $error->getMessage(); }
        if (count($locationFiles) > 4) $errors[] = 'Puede seleccionar como máximo 4 imágenes por ubicación.';
        if (!tiene_permiso('activos', $id > 0 ? 'editar' : 'crear')) $errors[] = 'No tiene permiso para guardar ubicaciones.';
        $code = strtoupper(iso_p('CodigoUbicacion'));
        $name = iso_p('NombreUbicacion');
        $type = strtoupper(iso_p('TipoUbicacion'));
        $state = strtoupper(iso_p('EstadoUbicacion'));
        if ($code === '' || mb_strlen($code, 'UTF-8') > 30) $errors[] = 'Ingrese un código de hasta 30 caracteres.';
        if ($name === '' || mb_strlen($name, 'UTF-8') > 120) $errors[] = 'Ingrese el nombre de la ubicación (máx. 120 caracteres).';
        if (!in_array($type, $tiposUbicacion, true)) $errors[] = 'Seleccione un tipo de ubicación válido.';
        if (!in_array($state, $estadosUbicacion, true)) $errors[] = 'Seleccione un estado válido.';
        if (!$errors && find_by_sql("SELECT IdUbicacion FROM ubicaciones_fisicas WHERE CodigoUbicacion='".$db->escape($code)."' AND IdUbicacion<>{$id} LIMIT 1")) $errors[] = "El código {$code} ya está registrado.";
        $cia = iso_read_cia($errors);

        if (!$errors) {
            $cols = [
                'CodigoUbicacion' => "'".$db->escape($code)."'",
                'NombreUbicacion' => "'".$db->escape($name)."'",
                'TipoUbicacion' => "'".$db->escape($type)."'",
                'Sede' => iso_sql_str(iso_p('Sede')),
                'Edificio' => iso_sql_str(iso_p('Edificio')),
                'Piso' => iso_sql_str(iso_p('Piso')),
                'Area' => iso_sql_str(iso_p('Area')),
                'DetalleUbicacion' => iso_sql_str(iso_p('DetalleUbicacion')),
                'AccesoRestringido' => (string)iso_flag('AccesoRestringido'),
                'ControlAcceso' => iso_sql_str(iso_p('ControlAcceso')),
                'Videovigilancia' => (string)iso_flag('Videovigilancia'),
                'DeteccionIncendio' => (string)iso_flag('DeteccionIncendio'),
                'Climatizacion' => (string)iso_flag('Climatizacion'),
                'EnergiaRespaldo' => (string)iso_flag('EnergiaRespaldo'),
                'Responsable' => iso_sql_str(iso_p('Responsable')),
                'EstadoUbicacion' => "'".$db->escape($state)."'",
            ] + iso_cia_cols($cia);
            $saved = iso_upsert('ubicaciones_fisicas', 'IdUbicacion', $id, $cols);
            if ($saved > 0) {
                iso_save_eval('UBICACION', $saved, $cia);
                try {
                    $photoCount = ubi_img_sync($saved, $locationFiles, (array)($_POST['EliminarImagenes'] ?? []), (int)(current_user()['id'] ?? 0));
                    activos_modulo_mensaje($messages, 's', ($id ? 'Ubicación actualizada.' : 'Ubicación registrada.') . " Galería: {$photoCount} imagen(es).");
                } catch (Throwable $error) {
                    $errors[] = 'Los datos se guardaron, pero la galería no pudo actualizarse: ' . $error->getMessage();
                }
            }
            else $errors[] = 'No se pudo guardar la ubicación.';
        }
    } elseif ($action === 'save_role') {
        $id = (int)($_POST['IdRol'] ?? 0);
        if (!tiene_permiso('activos', $id > 0 ? 'editar' : 'crear')) $errors[] = 'No tiene permiso para guardar roles clave.';
        $code = strtoupper(iso_p('CodigoRol'));
        $name = iso_p('NombreRol');
        $description = iso_p('DescripcionRol');
        $process = iso_p('ProcesoRol');
        $owner = iso_p('PropietarioRol');
        $holder = iso_p('TitularActual');
        $substitute = iso_p('Suplente');
        $hasPersonalData = ($holder !== '' || $substitute !== '') ? 1 : iso_flag('DatosPersonales');
        $state = strtoupper(iso_p('EstadoRol'));
        if (!preg_match('/^PER-[A-Z0-9-]{2,26}$/', $code)) $errors[] = 'Use un código único con formato PER-TI-001.';
        if ($name === '' || mb_strlen($name, 'UTF-8') > 120) $errors[] = 'Ingrese el nombre del cargo o rol clave.';
        if (mb_strlen($description, 'UTF-8') < 10) $errors[] = 'Describa las responsabilidades de continuidad del rol.';
        if ($process === '' || $owner === '') $errors[] = 'Proceso y propietario del activo son obligatorios.';
        if (!in_array($state, ['ACTIVO','INACTIVO','EN REVISION'], true)) $errors[] = 'Seleccione un estado válido para el rol.';
        if (!$errors && find_by_sql("SELECT IdRol FROM activos_roles_clave WHERE CodigoRol='".$db->escape($code)."' AND IdRol<>{$id} LIMIT 1")) $errors[] = "El código {$code} ya está registrado.";
        $cia = iso_read_cia($errors);
        if (!$errors) {
            $cols = [
                'CodigoRol' => "'".$db->escape($code)."'", 'NombreRol' => "'".$db->escape($name)."'",
                'Descripcion' => "'".$db->escape($description)."'", 'Proceso' => "'".$db->escape($process)."'",
                'Propietario' => "'".$db->escape($owner)."'", 'TitularActual' => iso_sql_str($holder),
                'Suplente' => iso_sql_str($substitute), 'DatosPersonales' => (string)$hasPersonalData,
                'RequisitoLegal' => iso_sql_str(iso_p('RequisitoLegalRol')), 'UbicacionLogica' => iso_sql_str(iso_p('UbicacionLogicaRol')),
                'EstadoRol' => "'".$db->escape($state)."'", 'Observaciones' => iso_sql_str(iso_p('ObservacionesRol')),
                'CreadoPor' => iso_sql_str(iso_user()),
            ] + iso_cia_cols($cia);
            $saved = iso_upsert('activos_roles_clave', 'IdRol', $id, $cols);
            if ($saved > 0) {
                iso_save_eval('ROL', $saved, $cia);
                activos_modulo_mensaje($messages, 's', $id ? 'Rol clave actualizado.' : 'Rol clave registrado en el inventario ISO.');
            } else $errors[] = 'No se pudo guardar el rol clave.';
        }
    } elseif ($action === 'assign_machines') {
        if (!tiene_permiso('activos', 'editar')) $errors[] = 'No tiene permiso para editar equipos.';
        if (!$errors) {
            $valid = [];
            foreach (find_by_sql("SELECT IdUbicacion FROM ubicaciones_fisicas") as $r) $valid[(int)$r['IdUbicacion']] = true;
            $current = [];
            foreach (find_by_sql("SELECT IdMaquina,IdUbicacion FROM maquina") as $r) $current[(int)$r['IdMaquina']] = (int)$r['IdUbicacion'];
            $changed = 0;
            foreach ((array)($_POST['loc'] ?? []) as $mid => $lid) {
                $mid = (int)$mid; $lid = (int)$lid;
                if (!isset($current[$mid]) || $current[$mid] === $lid) continue;
                if ($lid > 0 && !isset($valid[$lid])) continue;
                if ($db->query("UPDATE maquina SET IdUbicacion=".($lid > 0 ? $lid : 'NULL')." WHERE IdMaquina={$mid}")) $changed++;
            }
            activos_modulo_mensaje($messages, 's', "Se actualizó la ubicación de {$changed} equipo(s).");
        }
    } else {
        $errors[] = 'Operación no reconocida.';
    }
    if ($errors) activos_modulo_mensaje($messages, 'd', $errors);
    $targetTab = $action === 'assign_machines' ? '#tabEquipos' : ($action === 'save_role' ? '#tabRoles' : '');
    return activos_modulo_resultado($messages);

 return activos_modulo_resultado($messages);
}
