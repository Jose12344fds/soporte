<?php
declare(strict_types=1);

ob_start();
error_reporting(0);
ini_set('display_errors', '0');
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/../../Reportes/_xlsx_corporativo.php';

evt_schema();
require_permiso('eventos_ti', 'ver');

function evt_export_text($value, int $limit = 32000): string
{
    $value = (string)($value ?? '');
    return mb_strlen($value, 'UTF-8') > $limit ? mb_substr($value, 0, $limit, 'UTF-8').'…' : $value;
}

$filters = [
    'severity' => strtoupper(trim((string)($_GET['severity'] ?? ''))),
    'source' => strtoupper(trim((string)($_GET['source'] ?? ''))),
    'state' => strtoupper(trim((string)($_GET['state'] ?? ''))),
    'vm' => (int)($_GET['vm'] ?? 0),
    'from' => trim((string)($_GET['from'] ?? '')),
    'to' => trim((string)($_GET['to'] ?? '')),
    'q' => trim((string)($_GET['q'] ?? '')),
];
$where = ['1=1'];
if (in_array($filters['severity'], ['INFORMATIVO','ADVERTENCIA','ERROR','CRITICO'], true)) $where[] = "e.Severidad='".$db->escape($filters['severity'])."'";
if ($filters['source'] !== '') $where[] = "e.Fuente='".$db->escape($filters['source'])."'";
if (in_array($filters['state'], ['NUEVO','EN_REVISION','REVISADO','DESCARTADO','VINCULADO'], true)) $where[] = "e.Estado='".$db->escape($filters['state'])."'";
if ($filters['vm'] > 0) $where[] = 'e.IdVM='.$filters['vm'];
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from'])) $where[] = "e.FechaEvento>='".$db->escape($filters['from'])." 00:00:00'";
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to'])) $where[] = "e.FechaEvento<='".$db->escape($filters['to'])." 23:59:59'";
if ($filters['q'] !== '') {
    $q = $db->escape($filters['q']);
    $where[] = "(e.Titulo LIKE '%{$q}%' OR e.Mensaje LIKE '%{$q}%' OR e.CodigoEvento LIKE '%{$q}%' OR e.HostOrigen LIKE '%{$q}%' OR e.IPOrigen LIKE '%{$q}%')";
}

try {
    $events = find_by_sql("SELECT e.*,v.NombreVM,v.IP_VM,
            COALESCE(e.IdMaquina,v.IdMaquina) IdActivoRelacionado,
            m.Host EquipoHost,m.Tipo_Activo TipoEquipo,
            a.NombreAplicacion,a.Ambiente AmbienteAplicacion,
            t.Folio,t.Titulo TituloIncidencia,t.Estado EstadoIncidencia,t.Prioridad PrioridadIncidencia,
            ur.name RegistradoPorNombre,uv.name RevisadoPorNombre
        FROM evt_eventos e
        LEFT JOIN maquina_virtual v ON v.IdVM=e.IdVM
        LEFT JOIN maquina m ON m.IdMaquina=COALESCE(e.IdMaquina,v.IdMaquina)
        LEFT JOIN aplicacion a ON a.IdAplicacion=e.IdAplicacion
        LEFT JOIN inc_tickets t ON t.IdTicket=e.IdIncidencia
        LEFT JOIN users ur ON ur.id=e.RegistradoPor
        LEFT JOIN users uv ON uv.id=e.RevisadoPor
        WHERE ".implode(' AND ', $where)."
        ORDER BY e.FechaEvento DESC,e.IdEvento DESC");
    foreach ($events as &$event) {
        $event['Mensaje'] = evt_export_text($event['Mensaje']);
        $event['DatosJson'] = evt_export_text($event['DatosJson']);
        $event['ComentarioRevision'] = evt_export_text($event['ComentarioRevision']);
    }
    unset($event);
    $imports = find_by_sql("SELECT * FROM evt_importaciones ORDER BY FechaProceso DESC,IdImportacion DESC");
    $rules = find_by_sql("SELECT * FROM evt_reglas ORDER BY Activa DESC,Orden,Nombre");

    $critical = count(array_filter($events, static fn(array $r): bool => strtoupper((string)$r['Severidad']) === 'CRITICO'));
    $pendingTicket = count(array_filter($events, static fn(array $r): bool => (int)$r['RequiereIncidencia'] === 1 && empty($r['IdIncidencia'])));
    $linked = count(array_filter($events, static fn(array $r): bool => !empty($r['IdIncidencia'])));
    $unmapped = count(array_filter($events, static fn(array $r): bool => empty($r['IdVM']) && empty($r['IdActivoRelacionado'])));
    $scope = [];
    foreach (['severity'=>'severidad','source'=>'fuente','state'=>'estado','from'=>'desde','to'=>'hasta','q'=>'búsqueda'] as $key=>$label) {
        if ($filters[$key] !== '') $scope[] = $label.' '.$filters[$key];
    }
    if ($filters['vm'] > 0) $scope[] = 'VM #'.$filters['vm'];
    $summary = [
        ['Indicador'=>'Fecha de generación','Resultado'=>date('d/m/Y H:i:s'),'Interpretacion'=>'Corte del reporte.'],
        ['Indicador'=>'Alcance aplicado','Resultado'=>$scope ? implode(', ', $scope) : 'Todo el historial disponible','Interpretacion'=>'Filtros heredados de la bandeja.'],
        ['Indicador'=>'Eventos incluidos','Resultado'=>(string)count($events),'Interpretacion'=>'Hechos técnicos dentro del alcance.'],
        ['Indicador'=>'Eventos críticos','Resultado'=>(string)$critical,'Interpretacion'=>'Requieren revisión prioritaria.'],
        ['Indicador'=>'Pendientes de ticket','Resultado'=>(string)$pendingTicket,'Interpretacion'=>'Marcados para incidencia y aún sin folio.'],
        ['Indicador'=>'Eventos con incidencia','Resultado'=>(string)$linked,'Interpretacion'=>'Tienen trazabilidad con Mesa de Ayuda.'],
        ['Indicador'=>'Eventos sin equipo relacionado','Resultado'=>(string)$unmapped,'Interpretacion'=>'Revise la IP o el host del archivo fuente.'],
        ['Indicador'=>'Archivos procesados','Resultado'=>(string)count($imports),'Interpretacion'=>'Archivos TXT o JSON registrados.'],
        ['Indicador'=>'Flujo','Resultado'=>'Archivo → evento → evaluación → ticket → solución','Interpretacion'=>'El archivo y el ticket conservan la evidencia completa.'],
    ];
    $summaryColumns = [
        ['key'=>'Indicador','label'=>'INDICADOR','width'=>34],
        ['key'=>'Resultado','label'=>'RESULTADO','width'=>38],
        ['key'=>'Interpretacion','label'=>'INTERPRETACIÓN','width'=>52],
    ];
    $eventColumns = [
        ['key'=>'IdEvento','label'=>'ID EVENTO','type'=>'number','width'=>13],
        ['key'=>'FechaEvento','label'=>'FECHA Y HORA EVENTO','width'=>21],
        ['key'=>'Fuente','label'=>'FUENTE','width'=>15],
        ['key'=>'Canal','label'=>'CANAL','width'=>13],
        ['key'=>'Severidad','label'=>'SEVERIDAD','width'=>15],
        ['key'=>'Categoria','label'=>'CATEGORÍA','width'=>16],
        ['key'=>'CodigoEvento','label'=>'CÓDIGO EVENTO','width'=>17],
        ['key'=>'Proveedor','label'=>'PROVEEDOR / PROCESO','width'=>24],
        ['key'=>'Titulo','label'=>'TÍTULO','width'=>36],
        ['key'=>'Mensaje','label'=>'DETALLE TÉCNICO','width'=>48],
        ['key'=>'HostOrigen','label'=>'HOST DEL ARCHIVO','width'=>22],
        ['key'=>'IPOrigen','label'=>'IP ORIGEN','width'=>17],
        ['key'=>'IdVM','label'=>'ID VM','type'=>'number','width'=>10],
        ['key'=>'NombreVM','label'=>'MÁQUINA VIRTUAL','width'=>24],
        ['key'=>'IP_VM','label'=>'IP REGISTRADA VM','width'=>18],
        ['key'=>'IdActivoRelacionado','label'=>'ID ACTIVO','type'=>'number','width'=>12],
        ['key'=>'EquipoHost','label'=>'EQUIPO / HOST FÍSICO','width'=>24],
        ['key'=>'TipoEquipo','label'=>'TIPO ACTIVO','width'=>16],
        ['key'=>'IdAplicacion','label'=>'ID APLICACIÓN','type'=>'number','width'=>14],
        ['key'=>'NombreAplicacion','label'=>'APLICACIÓN','width'=>28],
        ['key'=>'AmbienteAplicacion','label'=>'AMBIENTE','width'=>14],
        ['key'=>'RequiereIncidencia','label'=>'REQUIERE TICKET','type'=>'boolean','width'=>16],
        ['key'=>'Estado','label'=>'ESTADO EVENTO','width'=>17],
        ['key'=>'IdIncidencia','label'=>'ID TICKET','type'=>'number','width'=>12],
        ['key'=>'Folio','label'=>'FOLIO TICKET','width'=>18],
        ['key'=>'PrioridadIncidencia','label'=>'PRIORIDAD TICKET','width'=>17],
        ['key'=>'EstadoIncidencia','label'=>'ESTADO TICKET','width'=>18],
        ['key'=>'TituloIncidencia','label'=>'ASUNTO DEL TICKET','width'=>34],
        ['key'=>'ArchivoOrigen','label'=>'ARCHIVO ORIGEN','width'=>40],
        ['key'=>'RegistradoPorNombre','label'=>'REGISTRADO POR','width'=>22],
        ['key'=>'FechaRegistro','label'=>'FECHA REGISTRO','width'=>21],
        ['key'=>'RevisadoPorNombre','label'=>'REVISADO POR','width'=>22],
        ['key'=>'FechaRevision','label'=>'FECHA REVISIÓN','width'=>21],
        ['key'=>'ComentarioRevision','label'=>'COMENTARIO DE REVISIÓN','width'=>46],
        ['key'=>'ClaveAgrupacion','label'=>'CLAVE DE AGRUPACIÓN','width'=>48],
        ['key'=>'HashEvento','label'=>'HASH DEL EVENTO','width'=>48],
        ['key'=>'DatosJson','label'=>'DATOS ORIGINALES','width'=>48],
    ];
    $importColumns = [
        ['key'=>'IdImportacion','label'=>'ID IMPORTACIÓN','type'=>'number','width'=>15],
        ['key'=>'FechaProceso','label'=>'FECHA PROCESO','width'=>21],
        ['key'=>'ArchivoOriginal','label'=>'ARCHIVO ORIGINAL','width'=>44],
        ['key'=>'HashArchivo','label'=>'HASH ARCHIVO','width'=>48],
        ['key'=>'Fuente','label'=>'FUENTE','width'=>16],
        ['key'=>'IPOrigen','label'=>'IP ORIGEN','width'=>17],
        ['key'=>'CantidadLeida','label'=>'REGISTROS LEÍDOS','type'=>'number','width'=>17],
        ['key'=>'CantidadInsertada','label'=>'NUEVOS','type'=>'number','width'=>12],
        ['key'=>'CantidadDuplicada','label'=>'DUPLICADOS','type'=>'number','width'=>14],
        ['key'=>'Estado','label'=>'ESTADO','width'=>16],
        ['key'=>'Mensaje','label'=>'RESULTADO / ERROR','width'=>48],
    ];
    $ruleColumns = [
        ['key'=>'IdRegla','label'=>'ID REGLA','type'=>'number','width'=>12],
        ['key'=>'Orden','label'=>'ORDEN','type'=>'number','width'=>10],
        ['key'=>'Nombre','label'=>'REGLA','width'=>34],
        ['key'=>'Fuente','label'=>'FUENTE','width'=>16],
        ['key'=>'CodigoEvento','label'=>'CÓDIGO EXACTO','width'=>17],
        ['key'=>'PatronTexto','label'=>'PATRÓN DE TEXTO','width'=>30],
        ['key'=>'Categoria','label'=>'CATEGORÍA','width'=>17],
        ['key'=>'Severidad','label'=>'SEVERIDAD','width'=>15],
        ['key'=>'RequiereIncidencia','label'=>'REQUIERE TICKET','type'=>'boolean','width'=>16],
        ['key'=>'Activa','label'=>'ACTIVA','type'=>'boolean','width'=>11],
        ['key'=>'Descripcion','label'=>'DESCRIPCIÓN','width'=>48],
        ['key'=>'created_at','label'=>'FECHA CREACIÓN','width'=>21],
        ['key'=>'updated_at','label'=>'ÚLTIMA ACTUALIZACIÓN','width'=>21],
    ];
    tx_export([
        ['name'=>'Resumen','title'=>'REPORTE DE GESTIÓN DE EVENTOS TI','code'=>'T-TI-F-02','columns'=>$summaryColumns,'rows'=>$summary],
        ['name'=>'Eventos y trazabilidad','title'=>'EVENTOS TÉCNICOS Y RELACIÓN CON INCIDENCIAS','code'=>'T-TI-F-02','columns'=>$eventColumns,'rows'=>$events],
        ['name'=>'Archivos procesados','title'=>'EVIDENCIA DE IMPORTACIÓN DE LOGS DE EVENTOS','code'=>'T-TI-F-02','columns'=>$importColumns,'rows'=>$imports],
        ['name'=>'Reglas','title'=>'REGLAS DE CLASIFICACIÓN Y ESCALAMIENTO','code'=>'T-TI-F-02','columns'=>$ruleColumns,'rows'=>$rules],
    ], 'Eventos_TI_Trazabilidad_'.date('Y-m-d_His').'.xlsx', dirname(__DIR__, 2).'/assets/img/logo.png');
} catch (Throwable $error) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'No se pudo generar el reporte Excel de eventos TI: '.$error->getMessage();
}
