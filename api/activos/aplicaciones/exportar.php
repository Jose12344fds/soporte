<?php
declare(strict_types=1);
ob_start(); error_reporting(0); ini_set('display_errors','0');
require_once dirname(__DIR__, 3) . '/includes/load.php';
require_once dirname(__DIR__, 3) . '/Reportes/_xlsx_corporativo.php';
if(!$session->isUserLoggedIn(true)){http_response_code(401);exit('Sesión no válida.');}
if(!tiene_permiso('activos','ver')){http_response_code(403);exit('Sin permiso para exportar aplicaciones.');}
try{
    require_once dirname(__DIR__, 3) . '/api/activos/_interno/aplicaciones/servicio.php';
    extract(activos_aplicaciones_reporte(), EXTR_SKIP);
    $appCols=[
      ['key'=>'IdAplicacionActivo','label'=>'ID APLICACIÓN','type'=>'number','width'=>14],['key'=>'IdAplicacionCapacidad','label'=>'ID CAPACIDAD TI','type'=>'number','width'=>16],['key'=>'AplicacionCapacidad','label'=>'APLICACIÓN CAPACIDAD TI','width'=>28],
      ['key'=>'CodigoAplicacion','label'=>'CÓDIGO APLICACIÓN','width'=>18],['key'=>'NombreAplicacion','label'=>'NOMBRE APLICACIÓN','width'=>30],['key'=>'TipoAplicacion','label'=>'TIPO','width'=>16],['key'=>'Fabricante','label'=>'FABRICANTE','width'=>22],['key'=>'Proveedor','label'=>'PROVEEDOR','width'=>24],['key'=>'Responsable','label'=>'RESPONSABLE','width'=>24],['key'=>'Descripcion','label'=>'DESCRIPCIÓN','width'=>42],['key'=>'EstadoAplicacion','label'=>'ESTADO','width'=>14],
      ['key'=>'TotalContratos','label'=>'TOTAL CONTRATOS','type'=>'number','width'=>16],['key'=>'ContratosVigentes','label'=>'CONTRATOS VIGENTES','type'=>'number','width'=>18],['key'=>'TotalInstalaciones','label'=>'TOTAL INSTALACIONES','type'=>'number','width'=>18],['key'=>'InstalacionesActivas','label'=>'INSTALACIONES ACTIVAS','type'=>'number','width'=>19],['key'=>'CostoMensualPEN','label'=>'COSTO MENSUAL PEN','type'=>'number','width'=>18],['key'=>'CostoMensualUSD','label'=>'COSTO MENSUAL USD','type'=>'number','width'=>18],['key'=>'FechaRegistro','label'=>'FECHA REGISTRO','width'=>21]
    ];
    $contractCols=[
      ['key'=>'IdContrato','label'=>'ID CONTRATO','type'=>'number','width'=>14],['key'=>'IdAplicacionActivo','label'=>'ID APLICACIÓN','type'=>'number','width'=>14],['key'=>'CodigoAplicacion','label'=>'CÓDIGO APLICACIÓN','width'=>18],['key'=>'NombreAplicacion','label'=>'APLICACIÓN','width'=>30],['key'=>'CodigoContrato','label'=>'CÓDIGO CONTRATO','width'=>20],['key'=>'TipoConcepto','label'=>'CONCEPTO','width'=>17],['key'=>'ModalidadPago','label'=>'MODALIDAD','width'=>15],['key'=>'Proveedor','label'=>'PROVEEDOR','width'=>24],['key'=>'CantidadLicencias','label'=>'LICENCIAS','type'=>'number','width'=>12],['key'=>'Costo','label'=>'COSTO','type'=>'number','width'=>14],['key'=>'Moneda','label'=>'MONEDA','width'=>10],['key'=>'CodigoSAP','label'=>'CÓDIGO SAP','width'=>18],['key'=>'OrdenCompra','label'=>'ORDEN DE COMPRA','width'=>20],['key'=>'FechaCompra','label'=>'FECHA COMPRA','type'=>'date','width'=>16],['key'=>'FechaInicio','label'=>'FECHA INICIO','type'=>'date','width'=>16],['key'=>'FechaVencimiento','label'=>'FECHA VENCIMIENTO','type'=>'date','width'=>19],['key'=>'RenovacionAutomatica','label'=>'RENOVACIÓN AUTOMÁTICA','type'=>'boolean','width'=>20],['key'=>'EstadoContrato','label'=>'ESTADO CONTRATO','width'=>18],['key'=>'Observaciones','label'=>'OBSERVACIONES','width'=>42],['key'=>'FechaRegistro','label'=>'FECHA REGISTRO','width'=>21]
    ];
    $installCols=[
      ['key'=>'IdInstalacion','label'=>'ID INSTALACIÓN','type'=>'number','width'=>15],['key'=>'IdAplicacionActivo','label'=>'ID APLICACIÓN','type'=>'number','width'=>14],['key'=>'CodigoAplicacion','label'=>'CÓDIGO APLICACIÓN','width'=>18],['key'=>'NombreAplicacion','label'=>'APLICACIÓN','width'=>30],['key'=>'IdMaquina','label'=>'ID ACTIVO','type'=>'number','width'=>12],['key'=>'EquipoHost','label'=>'EQUIPO / HOST','width'=>20],['key'=>'VersionInstalada','label'=>'VERSIÓN INSTALADA','width'=>20],['key'=>'CodigoLicencia','label'=>'CÓDIGO LICENCIA','width'=>25],['key'=>'FechaInstalacion','label'=>'FECHA INSTALACIÓN','type'=>'date','width'=>18],['key'=>'FechaVencimiento','label'=>'FECHA VENCIMIENTO','type'=>'date','width'=>19],['key'=>'EstadoInstalacion','label'=>'ESTADO INSTALACIÓN','width'=>19],['key'=>'Observaciones','label'=>'OBSERVACIONES','width'=>42],['key'=>'FechaRegistro','label'=>'FECHA REGISTRO','width'=>21]
    ];
    tx_export([
      ['name'=>'Aplicaciones','title'=>'INVENTARIO COMPLETO DE APLICACIONES','code'=>'T-TI-F-02','columns'=>$appCols,'rows'=>$applications],
      ['name'=>'Contratos y licencias','title'=>'CONTRATOS, LICENCIAS Y SUSCRIPCIONES DE APLICACIONES','code'=>'T-TI-F-02','columns'=>$contractCols,'rows'=>$contracts],
      ['name'=>'Instalaciones','title'=>'APLICACIONES INSTALADAS POR EQUIPO','code'=>'T-TI-F-02','columns'=>$installCols,'rows'=>$installations],
    ],'Aplicaciones_Data_Completa_'.date('Y-m-d_His').'.xlsx',dirname(__DIR__, 3).'/assets/img/logo.png');
}catch(Throwable $e){while(ob_get_level())ob_end_clean();http_response_code(500);header('Content-Type:text/plain;charset=utf-8');echo 'No se pudo generar el Excel de aplicaciones: '.$e->getMessage();}
