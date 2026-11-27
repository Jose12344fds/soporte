<?php

/** Lee una fecha DATE de MySQL. Los valores ausentes o invalidos se representan con null. */
function activos_fecha_normalizar(?string $value): ?string
{
    $value = trim((string)$value);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
        return null;
    }
    $year = (int)$parts[1];
    if ($year < 1000 || !checkdate((int)$parts[2], (int)$parts[3], $year)) {
        return null;
    }
    return $value;
}

function activos_fecha_valida(?string $value): bool
{
    return activos_fecha_normalizar($value) !== null;
}

/** Resta un mes y ajusta el dia al ultimo disponible en el mes anterior. */
function activos_fecha_aviso_mes_anterior(string $value): string
{
    $normalized = activos_fecha_normalizar($value);
    if ($normalized === null) {
        throw new RuntimeException('Fecha de fin invalida.');
    }
    $date = new DateTimeImmutable($normalized);
    $previousMonth = $date->modify('first day of this month')->modify('-1 month');
    $day = min((int)$date->format('d'), (int)$previousMonth->format('t'));
    return $previousMonth->setDate(
        (int)$previousMonth->format('Y'),
        (int)$previousMonth->format('m'),
        $day
    )->format('Y-m-d');
}

function activos_fechas_normalizar_registros(array $rows, array $fields): array
{
    foreach ($rows as &$row) {
        foreach ($fields as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = activos_fecha_normalizar($row[$field]);
            }
        }
    }
    unset($row);
    return $rows;
}
