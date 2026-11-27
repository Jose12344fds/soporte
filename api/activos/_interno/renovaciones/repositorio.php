<?php
require_once dirname(__DIR__, 4) . '/config/database.php';
require_once dirname(__DIR__) . '/fechas.php';

function activos_aplicaciones_preparar_renovaciones(PDO $connection): void
{
    $contracts = $connection->query(
        "SELECT c.IdContrato,c.TipoConcepto,CAST(c.FechaInicio AS CHAR) AS FechaInicio,
                CAST(c.FechaVencimiento AS CHAR) AS FechaVencimiento,c.Proveedor,c.Costo,c.Moneda
         FROM activos_aplicaciones_contratos c
         WHERE c.EstadoContrato IN ('VIGENTE','VENCIDO')
           AND NOT EXISTS (
               SELECT 1 FROM activos_aplicaciones_renovaciones r WHERE r.IdContrato=c.IdContrato
           )"
    )->fetchAll(PDO::FETCH_ASSOC);

    $insert = $connection->prepare(
        "INSERT INTO activos_aplicaciones_renovaciones
            (IdContrato,Etiqueta,FechaInicio,FechaFin,FechaAviso,Estado,Proveedor,Importe,Moneda,CreadoPor,ClaveOrigen)
         SELECT ?,?,?,?,?,'VIGENTE',?,?,?,0,?
         FROM DUAL
         WHERE NOT EXISTS (
             SELECT 1 FROM activos_aplicaciones_renovaciones WHERE IdContrato=?
         )
         ON DUPLICATE KEY UPDATE IdRenovacion=IdRenovacion"
    );

    foreach ($contracts as $contract) {
        $end = activos_fecha_normalizar($contract['FechaVencimiento']);
        if ($end === null || $end <= '2000-01-01') {
            continue;
        }
        $insert->execute([
            $contract['IdContrato'],
            $contract['TipoConcepto'] . ' ' . substr($end, 0, 4),
            activos_fecha_normalizar($contract['FechaInicio']),
            $end,
            activos_fecha_aviso_mes_anterior($end),
            $contract['Proveedor'] ?? '',
            $contract['Costo'],
            $contract['Moneda'],
            $contract['IdContrato'],
            $contract['IdContrato'],
        ]);
    }
}

function activos_servicios_preparar_renovaciones(PDO $connection): void
{
    $services = $connection->query(
        'SELECT s.IdServicio,CAST(s.FechaInicio AS CHAR) AS FechaInicio,
                CAST(s.FechaFin AS CHAR) AS FechaFin,s.Proveedor
         FROM servicios_ti s
         WHERE NOT EXISTS (
             SELECT 1 FROM activos_servicios_renovaciones r WHERE r.IdServicio=s.IdServicio
         )'
    )->fetchAll(PDO::FETCH_ASSOC);

    $insert = $connection->prepare(
        "INSERT INTO activos_servicios_renovaciones
            (IdServicio,Etiqueta,FechaInicio,FechaFin,FechaAviso,Estado,Proveedor,CreadoPor,ClaveOrigen)
         SELECT ?,?,?,?,?,'VIGENTE',?,0,?
         FROM DUAL
         WHERE NOT EXISTS (
             SELECT 1 FROM activos_servicios_renovaciones WHERE IdServicio=?
         )
         ON DUPLICATE KEY UPDATE IdRenovacion=IdRenovacion"
    );

    foreach ($services as $service) {
        $end = activos_fecha_normalizar($service['FechaFin']);
        if ($end === null || $end <= '2000-01-01') {
            continue;
        }
        $insert->execute([
            $service['IdServicio'],
            'Vigencia ' . substr($end, 0, 4),
            activos_fecha_normalizar($service['FechaInicio']),
            $end,
            activos_fecha_aviso_mes_anterior($end),
            $service['Proveedor'] ?? '',
            $service['IdServicio'],
            $service['IdServicio'],
        ]);
    }
}
