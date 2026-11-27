<?php

function activos_listado_sql(): string
{
    return <<<'SQL'
SELECT
  c.IdMaquina,
  COALESCE(g.IdAsMaq, 0) AS IdAsMaq,
  COALESCE(g.id, 0) AS user_id,
  c.Host,
  COALESCE(c.CodigoContabilidad, '') AS CodigoContabilidad,
  c.Tipo_Activo,
  c.IdTipoActivo,
  COALESCE(t.Nombre, c.Tipo_Activo, '-') AS TipoActivoNombre,
  c.CategoriaEquipo,
  c.IdAreaActivo,
  COALESCE(aa.Codigo, '-') AS AreaActivoCodigo,
  COALESCE(aa.Nombre, 'Sin área') AS AreaActivoNombre,
  c.Confidencialidad,
  c.Disponibilidad,
  c.Integridad,
  c.Criticidad,
  c.Clasificacion,
  COALESCE(u.area, '-') AS area,
  COALESCE(u.area, '') AS usuario_area,
  COALESCE(u.subarea, '') AS usuario_subarea,
  g.Fecha AS FechaAsignacion,
  COALESCE(g.Comentarios, '') AS AsignacionComentarios,
  YEAR(c.Fecha_Maqui) AS fecha,
  CONCAT(
    IFNULL(c.Procesador,''), ' / ',
    IFNULL(c.Tipo_Disco,''), ' ',
    IFNULL(c.Tamano_Disco,''), 'GB / ',
    IFNULL(c.Mem_Ram,''), 'GB RAM'
  ) AS Caracteristicas,
  c.EstadoMaquina,
  c.Ip AS Ip,
  c.Ubicacion,
  COALESCE(u.name, 'Sin asignar') AS name
FROM maquina c
LEFT JOIN tipos_activo t ON t.IdTipoActivo = c.IdTipoActivo
LEFT JOIN areas_activo aa ON aa.IdAreaActivo = c.IdAreaActivo
LEFT JOIN asig_maquina g ON g.IdAsMaq = (
    SELECT MAX(a.IdAsMaq)
    FROM asig_maquina a
    WHERE a.IdMaquina = c.IdMaquina AND a.Estado = 1
)
LEFT JOIN users u ON g.id = u.id
SQL;
}

function activos_listar(PDO $connection): array
{
    $statement = $connection->prepare(activos_listado_sql() . ' ORDER BY c.IdMaquina ASC');
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function activos_listado_tabla(PDO $connection, array $input): array
{
    $columns = ['IdMaquina','Host','CodigoContabilidad','TipoActivoNombre','CategoriaEquipo','AreaActivoCodigo',
        'Criticidad','Clasificacion','name','Caracteristicas','EstadoMaquina','Ip','Ubicacion'];
    $search = is_array($input['search'] ?? null) ? (string)($input['search']['value'] ?? '') : '';
    $terms = preg_split('/\s+/u', mb_substr(trim($search), 0, 200, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $conditions = [];
    $parameters = [];
    foreach ($terms as $term) {
        $matches = [];
        $pattern = '%' . strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        foreach ($columns as $column) {
            $matches[] = "CONVERT(`$column` USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ? ESCAPE '!'";
            $parameters[] = $pattern;
        }
        $conditions[] = '(' . implode(' OR ', $matches) . ')';
    }
    $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
    $base = '(' . activos_listado_sql() . ') activos';
    $total = (int)$connection->query('SELECT COUNT(*) FROM maquina')->fetchColumn();
    $count = $connection->prepare('SELECT COUNT(*) FROM ' . $base . $where);
    $count->execute($parameters);
    $filtered = (int)$count->fetchColumn();
    $order = is_array($input['order'][0] ?? null) ? $input['order'][0] : [];
    $column = $columns[(int)($order['column'] ?? 0)] ?? 'IdMaquina';
    $direction = strtolower((string)($order['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
    $start = max(0, (int)($input['start'] ?? 0));
    $length = max(1, min(100, (int)($input['length'] ?? 10)));
    $statement = $connection->prepare('SELECT * FROM ' . $base . $where . " ORDER BY `$column` $direction,IdMaquina ASC LIMIT ? OFFSET ?");
    foreach ($parameters as $index => $value) {
        $statement->bindValue($index + 1, $value, PDO::PARAM_STR);
    }
    $statement->bindValue(count($parameters) + 1, $length, PDO::PARAM_INT);
    $statement->bindValue(count($parameters) + 2, $start, PDO::PARAM_INT);
    $statement->execute();
    return ['draw' => max(0, (int)($input['draw'] ?? 0)), 'recordsTotal' => $total,
        'recordsFiltered' => $filtered, 'data' => $statement->fetchAll(PDO::FETCH_ASSOC)];
}

function activos_catalogos(PDO $connection): array
{
    $users = $connection->prepare(
        "SELECT id, name, area, subarea FROM users"
        . " WHERE status = 1 AND estado = 1 AND tipo_registro = 'USUARIO' ORDER BY name"
    );
    $users->execute();
    $areas = $connection->prepare(
        'SELECT IdAreaActivo, Codigo, Nombre FROM areas_activo WHERE Activo = 1 ORDER BY Orden, Nombre'
    );
    $areas->execute();

    return [
        'usuarios' => $users->fetchAll(PDO::FETCH_ASSOC),
        'areas' => $areas->fetchAll(PDO::FETCH_ASSOC),
    ];
}

function activos_obtener(PDO $connection, int $id, bool $lock = false): array
{
    if ($id <= 0) {
        throw new InvalidArgumentException('Seleccione un activo valido.');
    }
    $statement = $connection->prepare('SELECT * FROM maquina WHERE IdMaquina = ?' . ($lock ? ' FOR UPDATE' : ''));
    $statement->execute([$id]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new OutOfBoundsException('El activo no existe.');
    }
    return $row;
}

function activos_catalogos_formulario(PDO $connection, int $id = 0): array
{
    $current = $id > 0 ? activos_obtener($connection, $id) : ['office' => '', 'windows' => ''];
    $catalogs = activos_catalogos($connection);
    $catalogs['tipos'] = $connection->query('SELECT IdTipoActivo,Codigo,Nombre,CategoriaPredeterminada FROM tipos_activo WHERE Activo=1 ORDER BY Orden,Nombre')->fetchAll(PDO::FETCH_ASSOC);
    $statement = $connection->prepare("SELECT IdLicencias,Nombre,Serial FROM licencias WHERE Tipo_Licencias LIKE ? AND (Utilizado IS NULL OR Utilizado='' OR Utilizado='LIBRE' OR Serial=?) ORDER BY Nombre");
    foreach (['office' => '%Office%', 'windows' => '%Windows%'] as $field => $type) {
        $statement->execute([$type, $current[$field]]);
        $catalogs[$field] = $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    return $catalogs;
}

function activos_transaccion(PDO $connection, callable $operation): array
{
    $connection->beginTransaction();
    try {
        $result = $operation();
        $connection->commit();
        return $result;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function activos_area_valida(PDO $connection, int $id): void
{
    $statement = $connection->prepare('SELECT IdAreaActivo FROM areas_activo WHERE IdAreaActivo=? AND Activo=1');
    $statement->execute([$id]);
    if (!$statement->fetchColumn()) {
        throw new InvalidArgumentException('Seleccione un area activa.');
    }
}

function activos_bloquear_licencias(PDO $connection, array $values): array
{
    $serials = array_values(array_unique(array_filter(array_map('trim', $values), static fn($value) => $value !== '')));
    sort($serials, SORT_STRING);
    if (!$serials) {
        return [];
    }
    $statement = $connection->prepare('SELECT * FROM licencias WHERE Serial IN (' . implode(',', array_fill(0, count($serials), '?')) . ') ORDER BY Serial,IdLicencias FOR UPDATE');
    $statement->execute($serials);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function activos_licencia_en_uso(PDO $connection, string $serial, int $exceptId): bool
{
    $statement = $connection->prepare('SELECT IdMaquina FROM maquina WHERE IdMaquina<>? AND (office=? OR windows=?) LIMIT 1 FOR UPDATE');
    $statement->execute([$exceptId, $serial, $serial]);
    return (bool)$statement->fetchColumn();
}

function activos_sincronizar_licencias(PDO $connection, array $oldSerials, array $newSerials, int $id, string $host): void
{
    $newSerials = array_values(array_unique(array_filter($newSerials, static fn($serial) => $serial !== '')));
    $release = $connection->prepare("UPDATE licencias SET Utilizado='LIBRE',NomEquipo='' WHERE Serial=?");
    foreach (array_diff(array_unique($oldSerials), $newSerials, ['']) as $serial) {
        if (!activos_licencia_en_uso($connection, $serial, $id)) {
            $release->execute([$serial]);
        }
    }
    $assign = $connection->prepare("UPDATE licencias SET Utilizado='USADO',NomEquipo=? WHERE Serial=?");
    foreach ($newSerials as $serial) {
        $assign->execute([$host, $serial]);
    }
}

function activos_guardar(PDO $connection, array $input, ?int $id = null): array
{
    return activos_transaccion($connection, static function () use ($connection, $input, $id): array {
        $previous = $id !== null ? activos_obtener($connection, $id, true) : null;
        $values = activos_datos_equipo($input, $previous);
        $type = $connection->prepare('SELECT Codigo,CategoriaPredeterminada FROM tipos_activo WHERE IdTipoActivo=? AND Activo=1');
        $type->execute([$values['IdTipoActivo']]);
        $typeRow = $type->fetch(PDO::FETCH_ASSOC);
        if (!$typeRow) {
            throw new InvalidArgumentException('Seleccione un tipo de activo valido.');
        }
        $values['Tipo_Activo'] = $typeRow['Codigo'];
        if (in_array(strtoupper($typeRow['Codigo']), ['PC', 'LAPTOP'], true)) {
            $values['CategoriaEquipo'] = 'COMPUTADORAS';
        }
        if (!in_array($values['CategoriaEquipo'], ['COMPUTADORAS', 'PERIFERICOS', 'INFRAESTRUCTURA'], true)) {
            throw new InvalidArgumentException('Seleccione una categoria valida.');
        }
        activos_area_valida($connection, $values['IdAreaActivo']);
        $oldSerials = array_map('trim', [$previous['office'] ?? '', $previous['windows'] ?? '']);
        $newSerials = [$values['office'], $values['windows']];
        $licenses = activos_bloquear_licencias($connection, array_merge($oldSerials, $newSerials));
        foreach (array_unique(array_diff($newSerials, $oldSerials, [''])) as $serial) {
            $matching = array_values(array_filter($licenses, static fn($row) => $row['Serial'] === $serial));
            if (!$matching) {
                throw new InvalidArgumentException('La licencia seleccionada no existe.');
            }
            if (activos_licencia_en_uso($connection, $serial, $id ?? 0)
                || !in_array(strtoupper(trim((string)$matching[0]['Utilizado'])), ['', 'LIBRE'], true)) {
                throw new DomainException('La licencia seleccionada ya esta en uso.');
            }
        }
        $fields = array_keys($values);
        if ($id === null) {
            $sql = 'INSERT INTO maquina (`' . implode('`,`', $fields) . '`) VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            $connection->prepare($sql)->execute(array_values($values));
            $id = (int)$connection->lastInsertId();
        } else {
            $assignments = array_map(static fn($field) => '`' . $field . '`=?', $fields);
            $connection->prepare('UPDATE maquina SET ' . implode(',', $assignments) . ' WHERE IdMaquina=?')->execute([...array_values($values), $id]);
        }
        activos_sincronizar_licencias($connection, $oldSerials, $newSerials, $id, $values['Host']);
        return ['id' => $id];
    });
}

function activos_historial(PDO $connection, int $id): array
{
    activos_obtener($connection, $id);
    $current = $connection->prepare('SELECT a.IdAsMaq,a.id user_id,a.Fecha,a.Estado,a.Comentarios,u.name usuario,u.area,u.subarea FROM asig_maquina a LEFT JOIN users u ON u.id=a.id WHERE a.IdMaquina=? AND a.Estado=1 ORDER BY a.IdAsMaq DESC LIMIT 1');
    $current->execute([$id]);
    $history = $connection->prepare('SELECT h.id,h.fecha_asignacion,h.comentarios,h.accion,h.created_at,ua.name usuario_anterior,un.name usuario_nuevo,ur.name registrado_por_nombre FROM activos_asignacion_historial h LEFT JOIN users ua ON ua.id=h.usuario_anterior_id LEFT JOIN users un ON un.id=h.usuario_nuevo_id LEFT JOIN users ur ON ur.id=h.registrado_por WHERE h.IdMaquina=? ORDER BY h.id DESC LIMIT 10');
    $history->execute([$id]);
    return ['current' => $current->fetch(PDO::FETCH_ASSOC) ?: null, 'history' => $history->fetchAll(PDO::FETCH_ASSOC)];
}

function activos_asignar(PDO $connection, array $input, int $operatorId): array
{
    return activos_transaccion($connection, static function () use ($connection, $input, $operatorId): array {
        $id = activos_id($input, 'IdMaquina');
        activos_obtener($connection, $id, true);
        $values = activos_valoracion($input);
        $areaId = (int)($input['IdAreaActivo'] ?? 0);
        activos_area_valida($connection, $areaId);
        $rawUser = activos_texto($input, 'user_id', 20);
        $release = $rawUser === 'LIBRE';
        $userId = $release ? null : filter_var($rawUser, FILTER_VALIDATE_INT);
        if (!$release) {
            $user = $connection->prepare("SELECT id FROM users WHERE id=? AND status=1 AND estado=1 AND tipo_registro='USUARIO' FOR UPDATE");
            $user->execute([$userId]);
            if (!$userId || !$user->fetchColumn()) {
                throw new InvalidArgumentException('Seleccione un usuario activo o LIBRE / SIN USUARIO.');
            }
        }
        $date = activos_fecha(activos_texto($input, 'Fecha', 10, date('Y-m-d')));
        $comment = activos_texto($input, 'Comentarios', 150);
        $statement = $connection->prepare('SELECT * FROM asig_maquina WHERE IdMaquina=? ORDER BY IdAsMaq DESC FOR UPDATE');
        $statement->execute([$id]);
        $assignments = $statement->fetchAll(PDO::FETCH_ASSOC);
        $active = array_values(array_filter($assignments, static fn($row) => (int)$row['Estado'] === 1));
        $previousId = $active ? (int)$active[0]['id'] : null;
        if ($release) {
            $connection->prepare('DELETE FROM asig_maquina WHERE IdMaquina=?')->execute([$id]);
            $action = $assignments ? 'LIBERACION' : null;
        } elseif ($assignments) {
            $assignmentId = (int)$assignments[0]['IdAsMaq'];
            $connection->prepare('UPDATE asig_maquina SET Estado=0 WHERE IdMaquina=? AND IdAsMaq<>?')->execute([$id, $assignmentId]);
            $connection->prepare('UPDATE asig_maquina SET id=?,Fecha=?,Comentarios=?,Estado=1 WHERE IdAsMaq=?')->execute([$userId, $date, $comment, $assignmentId]);
            $action = $previousId === null ? 'ASIGNACION' : ($previousId === $userId ? 'ACTUALIZACION' : 'REASIGNACION');
        } else {
            $connection->prepare('INSERT INTO asig_maquina (id,IdMaquina,Fecha,Estado,Comentarios) VALUES (?,?,?,1,?)')->execute([$userId, $id, $date, $comment]);
            $action = 'ASIGNACION';
        }
        $connection->prepare('UPDATE maquina SET IdAreaActivo=?,Confidencialidad=?,Disponibilidad=?,Integridad=?,Criticidad=?,Clasificacion=? WHERE IdMaquina=?')->execute([$areaId, $values['Confidencialidad'], $values['Disponibilidad'], $values['Integridad'], $values['Criticidad'], $values['Clasificacion'], $id]);
        if ($action !== null) {
            $connection->prepare('INSERT INTO activos_asignacion_historial (IdMaquina,usuario_anterior_id,usuario_nuevo_id,fecha_asignacion,comentarios,accion,registrado_por) VALUES (?,?,?,?,?,?,?)')->execute([$id, $previousId, $userId, $date, $comment, $action, $operatorId]);
        }
        return ['id' => $id];
    });
}

function activos_evaluar(PDO $connection, array $input): array
{
    return activos_transaccion($connection, static function () use ($connection, $input): array {
        $id = activos_id($input);
        activos_obtener($connection, $id, true);
        $values = activos_valoracion([
            'Confidencialidad' => $input['confidencialidad'] ?? '',
            'Disponibilidad' => $input['disponibilidad'] ?? '',
            'Integridad' => $input['integridad'] ?? '',
            'Clasificacion' => $input['clasificacion'] ?? '',
        ]);
        $connection->prepare('UPDATE maquina SET Confidencialidad=?,Disponibilidad=?,Integridad=?,Criticidad=?,Clasificacion=? WHERE IdMaquina=?')->execute([$values['Confidencialidad'], $values['Disponibilidad'], $values['Integridad'], $values['Criticidad'], $values['Clasificacion'], $id]);
        return ['criticidad' => $values['Criticidad'], 'clasificacion' => $values['Clasificacion'], 'puntaje_maximo' => max($values['Confidencialidad'], $values['Disponibilidad'], $values['Integridad'])];
    });
}

function activos_eliminar(PDO $connection, int $id): void
{
    activos_transaccion($connection, static function () use ($connection, $id): array {
        $previous = activos_obtener($connection, $id, true);
        $tables = ['activos_aplicaciones_instalaciones'=>'IdMaquina', 'activos_mantenimiento_planes'=>'IdMaquina',
            'activos_mantenimientos'=>'IdMaquina', 'evt_eventos'=>'IdMaquina', 'hipervisor'=>'IdMaquina',
            'inc_tickets'=>'IdMaquina', 'maquina_virtual'=>'IdMaquina', 'usuariosf08'=>'maquina_id'];
        $exists = $connection->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        foreach ($tables as $table => $column) {
            $exists->execute([$table, $column]);
            if (!$exists->fetchColumn()) {
                continue;
            }
            $linked = $connection->prepare("SELECT 1 FROM `$table` WHERE `$column`=? LIMIT 1 FOR UPDATE");
            $linked->execute([$id]);
            if ($linked->fetchColumn()) {
                throw new DomainException('El activo tiene registros vinculados en ' . $table . '. Revise esos vinculos antes de eliminarlo.');
            }
        }
        activos_bloquear_licencias($connection, [$previous['office'], $previous['windows']]);
        $connection->prepare('DELETE FROM asig_maquina WHERE IdMaquina=?')->execute([$id]);
        $connection->prepare('DELETE FROM maquina WHERE IdMaquina=?')->execute([$id]);
        activos_sincronizar_licencias($connection, array_map('trim', [$previous['office'], $previous['windows']]), [], $id, '');
        return [];
    });
}
