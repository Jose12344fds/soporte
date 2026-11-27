<?php
declare(strict_types=1);

$page_title = 'Reporte anual de accesos VPN';
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../Reportes/_xlsx_corporativo.php';
require_permiso('vpn', 'ver');

$currentYear = (int)date('Y');
$year = filter_input(INPUT_GET, 'anio', FILTER_VALIDATE_INT) ?: $currentYear;
if ($year < 2000 || $year > $currentYear + 1) $year = $currentYear;

function vpn_hours($value): float {
    $parts = array_map('intval', explode(':', (string)$value));
    return round(($parts[0] ?? 0) + (($parts[1] ?? 0) / 60) + (($parts[2] ?? 0) / 3600), 2);
}

function vpn_date_time(string $date, string $time): string {
    $timestamp = strtotime($date . ' ' . $time);
    return $timestamp === false ? trim($date . ' ' . $time) : date('d/m/Y H:i', $timestamp);
}

try {
    $rows = find_by_sql("SELECT Id_IngVpn,Solicitante,Empresa,Motivo,Fecha,Hora,Nivel_Usu,Tiempo,Culminado,Token,Permiso,Responsable FROM vpn WHERE YEAR(Fecha)={$year} ORDER BY Fecha,Hora,Id_IngVpn");
    $byMonth = array_fill(1, 12, []);
    foreach ($rows as $row) {
        $month = (int)date('n', strtotime((string)$row['Fecha']));
        $byMonth[$month][] = [
            'Numero' => count($byMonth[$month]) + 1,
            'Solicitante' => $row['Solicitante'],
            'Empresa' => $row['Empresa'],
            'Motivo' => $row['Motivo'],
            'FechaHora' => vpn_date_time((string)$row['Fecha'], (string)$row['Hora']),
            'Nivel' => strtoupper((string)$row['Nivel_Usu']),
            'TiempoHoras' => vpn_hours($row['Tiempo']),
            'Culminado' => strtoupper((string)$row['Culminado']),
            'Token' => (string)$row['Token'],
            'Permiso' => strtoupper((string)$row['Permiso']),
            'Responsable' => $row['Responsable'],
        ];
    }

    $columns = [
        ['key'=>'Numero','label'=>'N°','type'=>'number','width'=>7],
        ['key'=>'Solicitante','label'=>'SOLICITANTE DE ACCESO A LA VPN','width'=>30],
        ['key'=>'Empresa','label'=>'EMPRESA','width'=>18],
        ['key'=>'Motivo','label'=>'MOTIVO','width'=>42],
        ['key'=>'FechaHora','label'=>'FECHA / HORA DE SOLICITUD','width'=>22],
        ['key'=>'Nivel','label'=>'NIVEL DE TIEMPO DE USO','width'=>20],
        ['key'=>'TiempoHoras','label'=>'TIEMPO DE USO (HORAS)','type'=>'number','width'=>18],
        ['key'=>'Culminado','label'=>'CULMINADO','width'=>14],
        ['key'=>'Token','label'=>'TOKEN','width'=>15],
        ['key'=>'Permiso','label'=>'PERMISO','width'=>13],
        ['key'=>'Responsable','label'=>'RESPONSABLE','width'=>22],
    ];
    $monthNames = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Setiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $sheets = [];
    foreach ($monthNames as $month => $name) {
        $sheets[] = [
            'name'=>$name,
            'title'=>'REGISTRO DE ACCESO A LA VPN',
            'code'=>'T-TI-F-21','date'=>date('d/m/Y'),'version'=>'01','elaborado'=>'TI','revisado'=>'GG','aprobado'=>'GG',
            'groups'=>[['label'=>'AÑO '.$year.' · '.$name,'start'=>1,'end'=>11]],
            'layoutCount'=>12,'columns'=>$columns,'rows'=>$byMonth[$month],'rowHeight'=>31,
        ];
    }
    tx_export($sheets, 'Registro_Accesos_VPN_T-TI-F-21_'.$year.'_'.date('Y-m-d_His').'.xlsx', dirname(__DIR__, 2).'/assets/img/logo.png');
} catch (Throwable $error) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'No se pudo generar el reporte anual de VPN: '.$error->getMessage();
}
