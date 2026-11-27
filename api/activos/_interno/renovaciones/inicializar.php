<?php
require_once __DIR__ . '/../comun.php';

/** Crea el primer periodo durante el guardado, dentro de la misma transaccion. */
function activos_renovacion_inicial(string $origin, int $id): void
{
    global $db;
    if ($origin === 'aplicacion') {
        $source = 'activos_aplicaciones_contratos';
        $target = 'activos_aplicaciones_renovaciones';
        $key = 'IdContrato';
        $fields = 'CAST(FechaInicio AS CHAR) FechaInicio,CAST(FechaVencimiento AS CHAR) FechaFin,Proveedor,Costo,Moneda,TipoConcepto';
    } elseif ($origin === 'servicio') {
        $source = 'servicios_ti';
        $target = 'activos_servicios_renovaciones';
        $key = 'IdServicio';
        $fields = 'CAST(FechaInicio AS CHAR) FechaInicio,CAST(FechaFin AS CHAR) FechaFin,Proveedor';
    } else {
        throw new InvalidArgumentException('Origen de renovacion no valido.');
    }
    $filter = $origin === 'aplicacion' ? " AND EstadoContrato IN ('VIGENTE','VENCIDO')" : '';
    $row = find_by_sql("SELECT $fields FROM $source WHERE $key=$id$filter LIMIT 1")[0] ?? null;
    $end = activos_fecha_normalizar($row['FechaFin'] ?? null);
    if ($row === null || $end === null || $end <= '2000-01-01') return;
    $start = activos_fecha_normalizar($row['FechaInicio']);
    $notice = activos_fecha_aviso_mes_anterior($end);
    $label = ($row['TipoConcepto'] ?? 'Vigencia') . ' ' . substr($end, 0, 4);
    $columns = "$key,Etiqueta,FechaInicio,FechaFin,FechaAviso,Estado,Proveedor,CreadoPor,ClaveOrigen";
    $values = "$id," . iso_sql_str($label) . ',' . iso_sql_date($start ?? '') . ','
        . iso_sql_date($end) . ',' . iso_sql_date($notice) . ",'VIGENTE',"
        . "'" . $db->escape($row['Proveedor'] ?? '') . "',0," . $id;
    if ($origin === 'aplicacion') {
        $columns .= ',Importe,Moneda';
        $values .= ',' . iso_sql_num((string)$row['Costo']) . ',' . iso_sql_str($row['Moneda']);
    }
    $db->query("INSERT INTO $target ($columns) SELECT $values FROM DUAL
        WHERE NOT EXISTS (SELECT 1 FROM $target WHERE $key=$id)
        ON DUPLICATE KEY UPDATE IdRenovacion=IdRenovacion");
}
