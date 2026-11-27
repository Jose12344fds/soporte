<?php
/**
 * Preparacion controlada del listado de Activos.
 * Revisar y respaldar las tablas antes de utilizar --apply.
 * Uso: php sql/14_activos_listado.php --check
 *      php sql/14_activos_listado.php --apply
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Disponible unicamente desde la consola.');
}
require_once dirname(__DIR__) . '/config/database.php';

$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--apply'], true)) {
    fwrite(STDERR, "Uso: php sql/14_activos_listado.php --check|--apply\n");
    exit(2);
}
$conexion = database_connection();
if ($mode === '--apply') {
    // Compatibilidad para instalaciones existentes: crear la categoría y
    // clasificar automáticamente todas las PC y laptops ya registradas.
    $conexion->exec("CREATE TABLE IF NOT EXISTS tipos_activo (
      IdTipoActivo INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
      Codigo VARCHAR(30) NOT NULL,
      Nombre VARCHAR(60) NOT NULL,
      CategoriaPredeterminada VARCHAR(30) NOT NULL,
      Activo TINYINT(1) NOT NULL DEFAULT 1,
      Orden INT NOT NULL DEFAULT 0,
      UNIQUE KEY uq_tipos_activo_codigo (Codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $conexion->exec("INSERT IGNORE INTO tipos_activo (Codigo,Nombre,CategoriaPredeterminada,Activo,Orden) VALUES
     ('PC','PC','COMPUTADORAS',1,10),('LAPTOP','Laptop','COMPUTADORAS',1,20),
     ('SERVIDOR','Servidor','INFRAESTRUCTURA',1,30),('IMPRESORA','Impresora','PERIFERICOS',1,40),
     ('SWITCH','Switch','INFRAESTRUCTURA',1,50),('FIREWALL','Firewall','INFRAESTRUCTURA',1,60),
     ('TABLET','Tablet','COMPUTADORAS',1,70),('OTRO','Otro','PERIFERICOS',1,80)");
    $tipoIdExiste = $conexion->query("SHOW COLUMNS FROM maquina LIKE 'IdTipoActivo'")->fetch(PDO::FETCH_ASSOC);
    if (!$tipoIdExiste) {
      $conexion->exec("ALTER TABLE maquina ADD COLUMN IdTipoActivo INT NULL AFTER Tipo_Activo");
      $conexion->exec("ALTER TABLE maquina ADD INDEX idx_maquina_tipo_activo (IdTipoActivo)");
    }
    $conexion->exec("UPDATE maquina m INNER JOIN tipos_activo t
     ON CONVERT(t.Codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
        = CONVERT(UPPER(TRIM(m.Tipo_Activo)) USING utf8mb4) COLLATE utf8mb4_unicode_ci
     SET m.IdTipoActivo=t.IdTipoActivo WHERE m.IdTipoActivo IS NULL OR m.IdTipoActivo=0");
    $categoriaExiste = $conexion->query("SHOW COLUMNS FROM maquina LIKE 'CategoriaEquipo'")->fetch(PDO::FETCH_ASSOC);
    if (!$categoriaExiste) {
      $conexion->exec("ALTER TABLE maquina ADD COLUMN CategoriaEquipo VARCHAR(30) NULL DEFAULT NULL AFTER Tipo_Activo");
    }
    $codigoContabilidadExiste = $conexion->query("SHOW COLUMNS FROM maquina LIKE 'CodigoContabilidad'")->fetch(PDO::FETCH_ASSOC);
    if (!$codigoContabilidadExiste) {
      $conexion->exec("ALTER TABLE maquina ADD COLUMN CodigoContabilidad VARCHAR(100) NULL DEFAULT NULL AFTER Host");
    }
    $conexion->exec("UPDATE maquina
                     SET CategoriaEquipo = 'COMPUTADORAS'
                     WHERE UPPER(TRIM(Tipo_Activo)) IN ('PC', 'LAPTOP')
                       AND (CategoriaEquipo IS NULL OR TRIM(CategoriaEquipo) = '')");
    $conexion->exec("UPDATE maquina m INNER JOIN tipos_activo t ON t.IdTipoActivo=m.IdTipoActivo
     SET m.CategoriaEquipo=t.CategoriaPredeterminada
     WHERE m.CategoriaEquipo IS NULL OR TRIM(m.CategoriaEquipo)=''");
    $conexion->exec("CREATE TABLE IF NOT EXISTS areas_activo (
     IdAreaActivo INT NOT NULL AUTO_INCREMENT PRIMARY KEY, Codigo VARCHAR(10) NOT NULL,
     Nombre VARCHAR(80) NOT NULL, Activo TINYINT(1) NOT NULL DEFAULT 1, Orden INT NOT NULL DEFAULT 0,
     UNIQUE KEY uq_areas_activo_codigo (Codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $conexion->exec("INSERT IGNORE INTO areas_activo (Codigo,Nombre,Activo,Orden) VALUES
     ('AD','Administración',1,10),('RAD','RAD',1,20),('TI','Tecnología de la Información',1,30),
     ('GG','Gerencia General',1,40),('GO','Gerencia de Operaciones',1,50),('GC','Gerencia Comercial',1,60)");
    $columnasSeguridad = [
     'IdAreaActivo'    => 'INT NULL AFTER CategoriaEquipo',
     'Confidencialidad'=> 'TINYINT UNSIGNED NULL AFTER IdAreaActivo',
     'Disponibilidad'  => 'TINYINT UNSIGNED NULL AFTER Confidencialidad',
     'Integridad'      => 'TINYINT UNSIGNED NULL AFTER Disponibilidad',
     'Criticidad'      => 'VARCHAR(15) NULL AFTER Integridad',
     'Clasificacion'   => 'VARCHAR(20) NULL AFTER Criticidad'
    ];
    foreach ($columnasSeguridad as $columna=>$definicion) {
      $existe=$conexion->query("SHOW COLUMNS FROM maquina LIKE ".$conexion->quote($columna))->fetch(PDO::FETCH_ASSOC);
      if (!$existe) $conexion->exec("ALTER TABLE maquina ADD COLUMN {$columna} {$definicion}");
    }
    $indiceArea=$conexion->query("SHOW INDEX FROM maquina WHERE Key_name='idx_maquina_area_activo'")->fetch(PDO::FETCH_ASSOC);
    if (!$indiceArea) $conexion->exec("ALTER TABLE maquina ADD INDEX idx_maquina_area_activo (IdAreaActivo)");
    $conexion->exec("UPDATE maquina m INNER JOIN areas_activo a
     ON CONVERT(UPPER(m.Host) USING utf8mb4) COLLATE utf8mb4_unicode_ci
        LIKE CONCAT('%-',CONVERT(a.Codigo USING utf8mb4),'-%') COLLATE utf8mb4_unicode_ci
     SET m.IdAreaActivo=a.IdAreaActivo WHERE m.IdAreaActivo IS NULL OR m.IdAreaActivo=0");
    $conexion->exec("UPDATE maquina m
     INNER JOIN (SELECT IdMaquina,MAX(IdAsMaq) IdAsMaq FROM asig_maquina GROUP BY IdMaquina) ult ON ult.IdMaquina=m.IdMaquina
     INNER JOIN asig_maquina am ON am.IdAsMaq=ult.IdAsMaq INNER JOIN users u ON u.id=am.id
     INNER JOIN areas_activo a
     ON CONVERT(a.Codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(CASE
      WHEN UPPER(TRIM(u.area)) IN ('ADMINISTRACION','ADMINISTRACIÓN') THEN 'AD'
      WHEN UPPER(TRIM(u.area))='RAD' THEN 'RAD'
      WHEN UPPER(TRIM(u.area)) IN ('TI','SISTEMAS','TECNOLOGIA DE LA INFORMACION','TECNOLOGÍA DE LA INFORMACIÓN') THEN 'TI'
      WHEN UPPER(TRIM(u.area)) IN ('GERENCIA_GENERAL','GERENCIA GENERAL','GG') THEN 'GG'
      WHEN UPPER(TRIM(u.area)) IN ('GERENCIA_OPERACIONES','GERENCIA DE OPERACIONES','GERENCIA OPERACIONES','GO') THEN 'GO'
      WHEN UPPER(TRIM(u.area)) IN ('GERENCIA_COMERCIAL','GERENCIA COMERCIAL','GC') THEN 'GC' ELSE '' END USING utf8mb4) COLLATE utf8mb4_unicode_ci
     SET m.IdAreaActivo=a.IdAreaActivo WHERE m.IdAreaActivo IS NULL OR m.IdAreaActivo=0");
    
    

    $usersType = $conexion->query("SHOW COLUMNS FROM users LIKE 'tipo_registro'")->fetch();
    if (!$usersType) {
        $conexion->exec("ALTER TABLE users ADD COLUMN tipo_registro VARCHAR(10) NOT NULL DEFAULT 'USUARIO' AFTER status");
    }
    $usersTi = $conexion->query("SHOW COLUMNS FROM users LIKE 'es_personal_ti'")->fetch();
    if (!$usersTi) {
        $conexion->exec("ALTER TABLE users ADD COLUMN es_personal_ti TINYINT(1) NOT NULL DEFAULT 0 AFTER tipo_registro");
    }
    $location = $conexion->query("SHOW COLUMNS FROM maquina LIKE 'Ubicacion'")->fetch();
    if (!$location) {
        $conexion->exec("ALTER TABLE maquina ADD COLUMN Ubicacion VARCHAR(20) NOT NULL DEFAULT 'EMPRESA' AFTER EstadoMaquina");
    }
}

$required = [
    'maquina' => ['IdMaquina','Host','CodigoContabilidad','Tipo_Activo','IdTipoActivo','CategoriaEquipo','IdAreaActivo','Confidencialidad','Disponibilidad','Integridad','Criticidad','Clasificacion','Fecha_Maqui','Procesador','Tipo_Disco','Tamano_Disco','Mem_Ram','EstadoMaquina','Ip','Ubicacion'],
    'asig_maquina' => ['IdAsMaq','IdMaquina','id','Fecha','Estado','Comentarios'],
    'users' => ['id','name','area','subarea','status','estado','tipo_registro'],
    'tipos_activo' => ['IdTipoActivo','Nombre'],
    'areas_activo' => ['IdAreaActivo','Codigo','Nombre','Activo','Orden'],
];
$missing = [];
$tableExists = $conexion->prepare(
    'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
);
foreach ($required as $table => $columns) {
    $tableExists->execute([$table]);
    if (!$tableExists->fetchColumn()) {
        $missing[] = $table . ' (tabla)';
        continue;
    }
    $present = array_column($conexion->query("SHOW COLUMNS FROM `$table`")->fetchAll(), 'Field');
    foreach (array_diff($columns, $present) as $column) {
        $missing[] = $table . '.' . $column;
    }
}
if ($missing) {
    fwrite(STDERR, 'Estructura pendiente: ' . implode(', ', $missing) . PHP_EOL);
    exit(1);
}
echo 'Estructura del listado de Activos verificada en ' . DB_NAME . PHP_EOL;
