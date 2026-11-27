<?php
declare(strict_types=1);
$page_title = 'Exportar inventario ISO 27001';
require_once dirname(__DIR__, 3) . '/includes/load.php';
require_once dirname(__DIR__, 3) . '/config/app.php';
require_once dirname(__DIR__, 3) . '/api/activos/_interno/compartido.php';
require_once dirname(__DIR__, 3) . '/api/activos/_interno/inventario/repositorio.php';
require_once dirname(__DIR__, 3) . '/Reportes/_xlsx_corporativo.php';
require_permiso('activos', 'ver');

try {
    $rows = iso_inventory_rows();
    foreach ($rows as &$row) {
        $row['UltimaRevision'] = $row['UltimaRevision'] ? substr($row['UltimaRevision'], 0, 10) : '';
        foreach (['Confidencialidad','Integridad','Disponibilidad'] as $field) if (!is_numeric($row[$field])) $row[$field] = '';
    }
    unset($row);
    $columns = [
        ['key'=>'IdActivo','label'=>'ID ACTIVO','width'=>17],['key'=>'Estado','label'=>'ESTADO','width'=>15],['key'=>'Proceso','label'=>'PROCESO','width'=>25],
        ['key'=>'Propietario','label'=>'PROPIETARIO DEL ACTIVO','width'=>25],['key'=>'NombreActivo','label'=>'NOMBRE DEL ACTIVO','width'=>34],['key'=>'Descripcion','label'=>'DESCRIPCIÓN','width'=>48],
        ['key'=>'TipoActivo','label'=>'TIPO DE ACTIVO','width'=>22],['key'=>'Ubicacion','label'=>'UBICACIÓN FÍSICA / LÓGICA','width'=>36],['key'=>'SistemaRepositorio','label'=>'SISTEMA / REPOSITORIO','width'=>42],
        ['key'=>'DatosPersonales','label'=>'DATOS PERSONALES / SENSIBLES','width'=>22],['key'=>'RequisitoLegal','label'=>'REQUISITO LEGAL / CONTRACTUAL','width'=>32],
        ['key'=>'Confidencialidad','label'=>'CONFIDENCIALIDAD (1-4)','type'=>'number','width'=>19],['key'=>'Integridad','label'=>'INTEGRIDAD (1-4)','type'=>'number','width'=>17],['key'=>'Disponibilidad','label'=>'DISPONIBILIDAD (1-4)','type'=>'number','width'=>19],
        ['key'=>'Criticidad','label'=>'CRITICIDAD','width'=>16],['key'=>'Clasificacion','label'=>'CLASIFICACIÓN','width'=>18],['key'=>'UltimaRevision','label'=>'ÚLTIMA REVISIÓN','type'=>'date','width'=>18],['key'=>'Observaciones','label'=>'OBSERVACIONES','width'=>48],
    ];
    tx_export([[
        'name'=>'Inventario de activos','title'=>'INVENTARIO DE INFORMACIÓN Y OTROS ACTIVOS ASOCIADOS',
        'code'=>'ISO 27001 · A.5.9','date'=>date('d/m/Y'),'version'=>'01','elaborado'=>'TI','revisado'=>'SGSI','aprobado'=>'GG',
        'columns'=>$columns,'rows'=>$rows,'rowHeight'=>42,
    ]], 'Inventario_Maestro_Activos_ISO27001_'.date('Y-m-d_His').'.xlsx', dirname(__DIR__, 3).'/assets/img/logo.png');
} catch (Throwable $error) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500); header('Content-Type: text/plain; charset=UTF-8');
    echo 'No se pudo generar el inventario ISO 27001: '.$error->getMessage();
}
