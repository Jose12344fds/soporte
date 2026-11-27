<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ob_start();
defined('DS') || define('DS', DIRECTORY_SEPARATOR);
defined('LIB_PATH_INC') || define('LIB_PATH_INC', dirname(__DIR__) . '/includes/');
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../src/db/sql.php';
require_once __DIR__ . '/../api/activos/_interno/compartido.php';
require_once __DIR__ . '/../api/activos/_interno/ubicaciones/imagenes.php';
require_once __DIR__ . '/../api/activos/_interno/servicios/renovaciones.php';
require_once __DIR__ . '/../api/activos/_interno/aplicaciones/renovaciones.php';
if (!in_array('--apply', $argv, true)) {
 $missing = iso_missing_tables(['servicios_ti','activos_servicios_contratos','activos_aplicaciones','activos_aplicaciones_contratos','activos_aplicaciones_instalaciones','activos_servicios_renovaciones','activos_aplicaciones_renovaciones','activos_servicios_facturas','activos_aplicaciones_documentos','activos_roles_clave','ubicaciones_fisicas_imagenes']);
 echo $missing ? 'Faltan: ' . implode(', ', $missing) : 'Esquema de secciones disponible.';
 exit($missing ? 1 : 0);
}
$db->query("CREATE TABLE IF NOT EXISTS servicios_ti (
    IdServicio INT(11) NOT NULL AUTO_INCREMENT,
    CodigoServicio VARCHAR(50) NOT NULL,
    NombreServicio VARCHAR(100) NOT NULL,
    TipoServicio VARCHAR(50) NULL,
    CategoriaServicio VARCHAR(50) NULL,
    Descripcion TEXT NULL,
    Proveedor VARCHAR(100) NULL,
    Responsable VARCHAR(100) NULL,
    Confidencialidad TINYINT(3) UNSIGNED NULL,
    Integridad TINYINT(3) UNSIGNED NULL,
    Disponibilidad TINYINT(3) UNSIGNED NULL,
    Criticidad VARCHAR(15) NULL,
    Clasificacion VARCHAR(20) NULL,
    EstadoServicio VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    FechaInicio DATE NULL,
    FechaFin DATE NULL,
    SLA VARCHAR(100) NULL,
    Observaciones TEXT NULL,
    Ubicacion VARCHAR(100) NULL,
    FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IdServicio),
    UNIQUE KEY uq_servicios_ti_codigo (CodigoServicio),
    KEY idx_servicios_ti_estado (EstadoServicio),
    KEY idx_servicios_ti_categoria (CategoriaServicio),
    KEY idx_servicios_ti_criticidad (Criticidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$db->query("CREATE TABLE IF NOT EXISTS activos_servicios_contratos (
    IdContrato INT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdServicio INT(11) NOT NULL,
    CodigoContrato VARCHAR(60) NULL,
    TipoConcepto VARCHAR(30) NOT NULL,
    ModalidadPago VARCHAR(20) NOT NULL,
    Proveedor VARCHAR(100) NULL,
    Costo DECIMAL(14,2) NOT NULL DEFAULT 0,
    Moneda VARCHAR(10) NOT NULL DEFAULT 'PEN',
    CodigoSAP VARCHAR(50) NULL,
    OrdenCompra VARCHAR(60) NULL,
    FechaCompra DATE NULL,
    FechaInicio DATE NULL,
    FechaVencimiento DATE NULL,
    RenovacionAutomatica TINYINT(1) NOT NULL DEFAULT 0,
    EstadoContrato VARCHAR(20) NOT NULL DEFAULT 'VIGENTE',
    Observaciones TEXT NULL,
    FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IdContrato),
    KEY idx_serviciocontrato_servicio (IdServicio),
    KEY idx_serviciocontrato_vencimiento (FechaVencimiento),
    CONSTRAINT fk_serviciocontrato_servicio FOREIGN KEY (IdServicio) REFERENCES servicios_ti(IdServicio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");


$schema = [
"CREATE TABLE IF NOT EXISTS activos_aplicaciones (IdAplicacionActivo INT UNSIGNED NOT NULL AUTO_INCREMENT,IdAplicacionCapacidad INT UNSIGNED NULL,CodigoAplicacion VARCHAR(50) NOT NULL,NombreAplicacion VARCHAR(140) NOT NULL,TipoAplicacion VARCHAR(40) NOT NULL DEFAULT 'ESCRITORIO',Fabricante VARCHAR(100) NULL,Proveedor VARCHAR(100) NULL,Responsable VARCHAR(100) NULL,Descripcion TEXT NULL,EstadoAplicacion VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(IdAplicacionActivo),UNIQUE KEY uq_act_app_codigo(CodigoAplicacion),UNIQUE KEY uq_act_app_capacidad(IdAplicacionCapacidad),KEY idx_act_app_estado(EstadoAplicacion)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS activos_aplicaciones_contratos (IdContrato INT UNSIGNED NOT NULL AUTO_INCREMENT,IdAplicacionActivo INT UNSIGNED NOT NULL,CodigoContrato VARCHAR(60) NULL,TipoConcepto VARCHAR(30) NOT NULL,ModalidadPago VARCHAR(20) NOT NULL,Proveedor VARCHAR(100) NULL,CantidadLicencias INT UNSIGNED NOT NULL DEFAULT 1,Costo DECIMAL(14,2) NOT NULL DEFAULT 0,Moneda VARCHAR(10) NOT NULL DEFAULT 'PEN',CodigoSAP VARCHAR(50) NULL,OrdenCompra VARCHAR(60) NULL,FechaCompra DATE NULL,FechaInicio DATE NULL,FechaVencimiento DATE NULL,RenovacionAutomatica TINYINT(1) NOT NULL DEFAULT 0,EstadoContrato VARCHAR(20) NOT NULL DEFAULT 'VIGENTE',Observaciones TEXT NULL,FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(IdContrato),KEY idx_appcontrato_app(IdAplicacionActivo),KEY idx_appcontrato_vencimiento(FechaVencimiento),CONSTRAINT fk_appcontrato_app FOREIGN KEY(IdAplicacionActivo) REFERENCES activos_aplicaciones(IdAplicacionActivo) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS activos_aplicaciones_instalaciones (IdInstalacion INT UNSIGNED NOT NULL AUTO_INCREMENT,IdAplicacionActivo INT UNSIGNED NOT NULL,IdMaquina INT NOT NULL,VersionInstalada VARCHAR(60) NULL,CodigoLicencia VARCHAR(120) NULL,FechaInstalacion DATE NULL,FechaVencimiento DATE NULL,EstadoInstalacion VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',Observaciones TEXT NULL,FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(IdInstalacion),UNIQUE KEY uq_appinstalacion_equipo(IdAplicacionActivo,IdMaquina),KEY idx_appinstalacion_maquina(IdMaquina),CONSTRAINT fk_appinstalacion_app FOREIGN KEY(IdAplicacionActivo) REFERENCES activos_aplicaciones(IdAplicacionActivo) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];
foreach($schema as $sql) $db->query($sql);

require_once __DIR__ . '/../api/activos/_interno/servicios/facturas.php';
require_once __DIR__ . '/../api/activos/_interno/aplicaciones/documentos.php';
sr_schema(); ar_schema(); sf_schema(); af_schema(); ubi_img_ensure_schema(); iso_ensure_roles_schema();
sr_backfill(); ar_backfill();
echo "Esquema verificado y renovaciones inicializadas.\n";
