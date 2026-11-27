<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/_interno/comun.php';

function activos_modulo_api(string $section, string $method): void
{
    $sections = activos_modulo_operaciones();
    if (!isset($sections[$section])) {
        api_json_response(['error' => 'Recurso no disponible.'], 404);
    }
    activos_api($method === 'GET' ? 'ver' : ['crear', 'editar'], $method,
        static function (PDO $connection, array $input) use ($section, $method, $sections): array {
            global $db;
            require_once __DIR__ . '/_interno/' . $section . '/servicio.php';
            if ($method === 'GET') {
                return ['data' => ('activos_' . $section . '_listar')()];
            }
            $action = $input['action'] ?? '';
            if (!is_string($action) || !array_key_exists($action, $sections[$section])) {
                throw new InvalidArgumentException('Operacion no reconocida.');
            }
            $idKey = $sections[$section][$action];
            $id = is_string($idKey) ? ($input[$idKey] ?? '0') : '0';
            if (is_array($id) || !ctype_digit((string)$id)) {
                throw new InvalidArgumentException('Identificador no valido.');
            }
            $permission = $idKey === false || (int)$id > 0 ? 'editar' : 'crear';
            if (!tiene_permiso('activos', $permission)) {
                api_json_response(['error' => 'No tiene permiso para esta operacion.'], 403);
            }
            $references = [
                'IdServicio' => ['servicios_ti', 'IdServicio'],
                'IdAplicacionActivo' => ['activos_aplicaciones', 'IdAplicacionActivo'],
                'IdAplicacionCapacidad' => ['aplicacion', 'IdAplicacion'],
                'IdMaquina' => ['maquina', 'IdMaquina'],
                'IdCopia' => ['copias_seguridad', 'IdCopia'],
                'IdActivoInformacion' => ['activos_informacion', 'IdActivoInformacion'],
                'IdUbicacion' => ['ubicaciones_fisicas', 'IdUbicacion'],
                'IdUbicacionDestino' => ['ubicaciones_fisicas', 'IdUbicacion'],
                'IdRol' => ['activos_roles_clave', 'IdRol'],
                'IdContrato' => [$section === 'servicios' ? 'activos_servicios_contratos' : 'activos_aplicaciones_contratos', 'IdContrato'],
            ];
            foreach ($references as $key => [$table, $column]) {
                if (!array_key_exists($key, $input) || $input[$key] === '') continue;
                if (!is_scalar($input[$key]) || !ctype_digit((string)$input[$key])) {
                    throw new InvalidArgumentException('Identificador no valido: ' . $key);
                }
                if ((int)$input[$key] > 0) {
                    $query = $connection->prepare("SELECT 1 FROM `$table` WHERE `$column`=?");
                    $query->execute([(int)$input[$key]]);
                    if (!$query->fetchColumn()) throw new OutOfBoundsException('El registro seleccionado no existe: ' . $key);
                }
            }
            foreach ($input as $key => $value) {
                if (is_array($value) && !in_array($key, ['loc','Respalda','AlmacenadaEn','EliminarImagenes'], true)) {
                    throw new InvalidArgumentException('Formato de campo no valido: ' . $key);
                }
                if (str_starts_with($key, 'Fecha') || $key === 'ProximaRevision') {
                    if (!is_scalar($value) && $value !== null) throw new InvalidArgumentException('Fecha no valida.');
                    $value = trim((string)$value);
                    if ($value !== '' && activos_fecha_normalizar(substr($value, 0, 10)) === null) {
                        throw new InvalidArgumentException('La fecha indicada en ' . $key . ' no es valida.');
                    }
                    if ($key === 'FechaEvento' && $value !== '') {
                        $dateTime = str_replace('T', ' ', $value);
                        $format = strlen($dateTime) === 16 ? '!Y-m-d H:i' : (strlen($dateTime) === 10 ? '!Y-m-d' : '!Y-m-d H:i:s');
                        $parsed = DateTimeImmutable::createFromFormat($format, $dateTime);
                        $errors = DateTimeImmutable::getLastErrors();
                        if (!$parsed || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
                            throw new InvalidArgumentException('Fecha u hora del evento no valida.');
                        }
                    }
                }
            }
            $previous = $_POST;
            $_POST = $input;
            $db->query('START TRANSACTION');
            try {
                $result = ('activos_' . $section . '_guardar')();
                if (($result['success'] ?? false) !== true) {
                    throw new InvalidArgumentException($result['error'] ?? 'No se pudo guardar.');
                }
                $db->query('COMMIT');
                return $result;
            } catch (Throwable $error) {
                $db->query('ROLLBACK');
                throw $error;
            } finally {
                $_POST = $previous;
            }
        }
    );
}

function activos_modulo_consulta(string $section, string $operation): void
{
    $allowed = [
        'servicios' => ['reporte'], 'aplicaciones' => ['reporte'],
        'renovaciones' => ['listar'], 'inventario' => ['listar'],
    ];
    if (!in_array($operation, $allowed[$section] ?? [], true)) {
        api_json_response(['error' => 'Consulta no disponible.'], 404);
    }
    activos_api('ver', 'GET', static function (PDO $connection, array $input) use ($section, $operation): array {
        require_once __DIR__ . '/_interno/' . $section . '/servicio.php';
        return ['data' => ('activos_' . $section . '_' . $operation)()];
    });
}
