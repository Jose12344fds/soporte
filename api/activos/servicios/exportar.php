<?php
declare(strict_types=1);
ob_start(); error_reporting(0); ini_set('display_errors','0');
require_once dirname(__DIR__, 3) . '/includes/load.php';
require_once dirname(__DIR__, 3) . '/Reportes/_xlsx_corporativo.php';
if(!$session->isUserLoggedIn(true)){http_response_code(401);exit('Sesión no válida.');}
if(!tiene_permiso('activos','ver')){http_response_code(403);exit('Sin permiso para exportar servicios.');}
try{
    require_once dirname(__DIR__, 3) . '/api/activos/_interno/servicios/servicio.php';
    extract(activos_servicios_reporte(), EXTR_SKIP);
    $serviceCols=[
      ['key'=>'IdServicio','label'=>'ID SERVICIO','type'=>'number','width'=>13],['key'=>'CodigoServicio','label'=>'CÓDIGO SERVICIO','width'=>18],['key'=>'NombreServicio','label'=>'NOMBRE SERVICIO','width'=>30],['key'=>'TipoServicio','label'=>'TIPO SERVICIO','width'=>19],['key'=>'CategoriaServicio','label'=>'CATEGORÍA','width'=>20],['key'=>'Descripcion','label'=>'DESCRIPCIÓN','width'=>42],['key'=>'Proveedor','label'=>'PROVEEDOR','width'=>24],['key'=>'Responsable','label'=>'RESPONSABLE','width'=>24],['key'=>'Confidencialidad','label'=>'CONFIDENCIALIDAD (1-4)','type'=>'number','width'=>21],['key'=>'Integridad','label'=>'INTEGRIDAD (1-4)','type'=>'number','width'=>18],['key'=>'Disponibilidad','label'=>'DISPONIBILIDAD (1-4)','type'=>'number','width'=>21],['key'=>'Criticidad','label'=>'CRITICIDAD','width'=>15],['key'=>'Clasificacion','label'=>'CLASIFICACIÓN','width'=>18],['key'=>'EstadoServicio','label'=>'ESTADO SERVICIO','width'=>18],['key'=>'FechaInicio','label'=>'FECHA INICIO','type'=>'date','width'=>16],['key'=>'FechaFin','label'=>'FECHA FIN','type'=>'date','width'=>16],['key'=>'SLA','label'=>'SLA','width'=>22],['key'=>'Observaciones','label'=>'OBSERVACIONES','width'=>42],['key'=>'Ubicacion','label'=>'UBICACIÓN','width'=>25],['key'=>'TotalContratos','label'=>'TOTAL CONTRATOS','type'=>'number','width'=>16],['key'=>'ContratosVigentes','label'=>'CONTRATOS VIGENTES','type'=>'number','width'=>18],['key'=>'CostoMensualPEN','label'=>'COSTO MENSUAL PEN','type'=>'number','width'=>18],['key'=>'CostoMensualUSD','label'=>'COSTO MENSUAL USD','type'=>'number','width'=>18],['key'=>'FechaRegistro','label'=>'FECHA REGISTRO','width'=>21]
    ];
    $contractCols=[
      ['key'=>'IdContrato','label'=>'ID CONTRATO','type'=>'number','width'=>14],['key'=>'IdServicio','label'=>'ID SERVICIO','type'=>'number','width'=>13],['key'=>'CodigoServicio','label'=>'CÓDIGO SERVICIO','width'=>18],['key'=>'NombreServicio','label'=>'SERVICIO','width'=>30],['key'=>'CodigoContrato','label'=>'CÓDIGO CONTRATO','width'=>20],['key'=>'TipoConcepto','label'=>'CONCEPTO','width'=>18],['key'=>'ModalidadPago','label'=>'MODALIDAD','width'=>16],['key'=>'Proveedor','label'=>'PROVEEDOR','width'=>24],['key'=>'Costo','label'=>'COSTO','type'=>'number','width'=>14],['key'=>'Moneda','label'=>'MONEDA','width'=>10],['key'=>'CodigoSAP','label'=>'CÓDIGO SAP','width'=>18],['key'=>'OrdenCompra','label'=>'ORDEN DE COMPRA','width'=>20],['key'=>'FechaCompra','label'=>'FECHA COMPRA','type'=>'date','width'=>16],['key'=>'FechaInicio','label'=>'FECHA INICIO','type'=>'date','width'=>16],['key'=>'FechaVencimiento','label'=>'FECHA VENCIMIENTO','type'=>'date','width'=>19],['key'=>'RenovacionAutomatica','label'=>'RENOVACIÓN AUTOMÁTICA','type'=>'boolean','width'=>20],['key'=>'EstadoContrato','label'=>'ESTADO CONTRATO','width'=>18],['key'=>'Observaciones','label'=>'OBSERVACIONES','width'=>42],['key'=>'FechaRegistro','label'=>'FECHA REGISTRO','width'=>21]
    ];
    tx_export([
      ['name'=>'Servicios TI','title'=>'INVENTARIO COMPLETO DE SERVICIOS TI','code'=>'T-TI-F-02','columns'=>$serviceCols,'rows'=>$services],
      ['name'=>'Contratos de servicios','title'=>'CONTRATOS Y PAGOS DE SERVICIOS TI','code'=>'T-TI-F-02','columns'=>$contractCols,'rows'=>$contracts],
    ],'Servicios_TI_Data_Completa_'.date('Y-m-d_His').'.xlsx',dirname(__DIR__, 3).'/assets/img/logo.png');
}catch(Throwable $e){while(ob_get_level())ob_end_clean();http_response_code(500);header('Content-Type:text/plain;charset=utf-8');echo 'No se pudo generar el Excel de servicios: '.$e->getMessage();}
