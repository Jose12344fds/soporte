<?php
require_once dirname(__DIR__) . '/fechas.php';

function activos_id(array $input, string $field = 'id', bool $optional = false): int
{
    $value = $input[$field] ?? 0;
    if ($optional && in_array($value, [0, '0', '', null], true)) {
        return 0;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || is_bool($value) || is_float($value)) {
        throw new InvalidArgumentException('Seleccione un identificador valido para ' . $field . '.');
    }
    return $id;
}

function activos_texto(array $input, string $field, int $length, string $default = ''): string
{
    $value = $input[$field] ?? $default;
    if (!is_scalar($value)) {
        throw new InvalidArgumentException('Valor invalido para ' . $field . '.');
    }
    $value = trim((string)$value);
    if (mb_strlen($value, 'UTF-8') > $length) {
        throw new InvalidArgumentException($field . ' supera los ' . $length . ' caracteres.');
    }
    return $value;
}

function activos_fecha(string $value): string
{
    $date = activos_fecha_normalizar($value);
    if ($date === null) {
        throw new InvalidArgumentException('La fecha no es valida.');
    }
    return $date;
}

function activos_valoracion(array $input, bool $required = true): array
{
    $values = [];
    foreach (['Confidencialidad', 'Disponibilidad', 'Integridad'] as $field) {
        $values[$field] = activos_texto($input, $field, 2);
    }
    $classification = strtoupper(activos_texto($input, 'Clasificacion', 20));
    if (!$required && $classification === '' && !array_filter($values, static fn($value) => $value !== '')) {
        return [];
    }
    foreach ($values as $field => $value) {
        if (!in_array($value, ['1', '2', '3', '4'], true)) {
            throw new InvalidArgumentException('Complete los tres puntajes de seguridad del 1 al 4.');
        }
        $values[$field] = (int)$value;
    }
    if (!in_array($classification, ['PUBLICA', 'INTERNA', 'CONFIDENCIAL'], true)) {
        throw new InvalidArgumentException('Seleccione una clasificacion valida.');
    }
    $levels = [1 => 'BAJO', 2 => 'MEDIO', 3 => 'ALTO', 4 => 'MUY ALTO'];
    return $values + ['Criticidad' => $levels[max($values)], 'Clasificacion' => $classification];
}

function activos_datos_equipo(array $input, ?array $previous): array
{
    $limits = ['Host'=>30, 'CodigoContabilidad'=>100, 'Modelo_Placa'=>50, 'Tipo_Disco'=>50,
        'Mod_Pantalla'=>100, 'Ip'=>15, 'Procesador'=>40, 'office'=>100, 'windows'=>100,
        'EstadoMaquina'=>20, 'Ubicacion'=>20, 'CategoriaEquipo'=>30];
    $values = [];
    foreach ($limits as $field => $length) {
        $values[$field] = activos_texto($input, $field, $length, (string)($previous[$field] ?? ''));
    }
    if ($values['Host'] === '') {
        throw new InvalidArgumentException('El hostname es obligatorio.');
    }
    $values['Ubicacion'] = strtoupper($values['Ubicacion']);
    if (!in_array($values['Ubicacion'], ['DOMICILIO', 'EMPRESA', 'PUNO'], true)) {
        throw new InvalidArgumentException('Seleccione una ubicacion valida.');
    }
    foreach (['IdTipoActivo', 'IdAreaActivo', 'Mem_Ram', 'Tamano_Disco'] as $field) {
        $value = $input[$field] ?? ($previous[$field] ?? 0);
        $value = $value === '' ? 0 : $value;
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 0) {
            throw new InvalidArgumentException('Valor numerico invalido para ' . $field . '.');
        }
        $values[$field] = (int)$value;
    }
    $date = activos_texto($input, 'Fecha_Maqui', 10);
    if ($date !== '' && $date !== '0000-00-00') {
        $values['Fecha_Maqui'] = activos_fecha($date);
    } elseif ($previous === null) {
        $values['Fecha_Maqui'] = date('Y-m-d');
    }
    $values['EstadoMaquina'] = $values['EstadoMaquina'] ?: 'OPERATIVO';
    $securityUnchanged = $previous !== null;
    foreach (['Confidencialidad', 'Disponibilidad', 'Integridad', 'Clasificacion'] as $field) {
        if (array_key_exists($field, $input)
            && (!is_scalar($input[$field]) || (string)$input[$field] !== (string)($previous[$field] ?? ''))) {
            $securityUnchanged = false;
        }
    }
    return $values + ($securityUnchanged ? [] : activos_valoracion($input, $previous === null));
}
