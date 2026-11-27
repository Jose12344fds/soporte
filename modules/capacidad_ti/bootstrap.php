<?php
/** Base de datos y utilidades de Gestión de Capacidad TI. */

if (!function_exists('cap_h')) {
    function cap_h($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cap_user_id')) {
    function cap_user_id(): int {
        $user = function_exists('current_user') ? current_user() : null;
        return (int)($user['id'] ?? 0);
    }
}

if (!function_exists('capacidad_ti_schema')) {
    function capacidad_ti_schema(): void {
        global $db;
        static $ready = false;
        if ($ready) return;

        $queries = [
            "CREATE TABLE IF NOT EXISTS hipervisor (
                IdHipervisor INT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdMaquina INT NOT NULL,
                TipoHipervisor VARCHAR(50) NOT NULL,
                Fabricante VARCHAR(80) NULL,
                Version VARCHAR(60) NULL,
                Licencia VARCHAR(120) NULL,
                NucleosCPU SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                FrecuenciaCPUMHz DECIMAL(10,2) NOT NULL DEFAULT 0,
                Estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (IdHipervisor), UNIQUE KEY uq_hipervisor_maquina (IdMaquina)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS maquina_virtual (
                IdVM INT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdMaquina INT NOT NULL,
                IdHipervisor INT UNSIGNED NULL,
                NombreVM VARCHAR(100) NOT NULL,
                SistemaOperativo VARCHAR(120) NULL,
                CPUAsignada DECIMAL(10,2) NOT NULL DEFAULT 0,
                RAMAsignada DECIMAL(12,2) NOT NULL DEFAULT 0,
                DiscoAsignado DECIMAL(14,2) NOT NULL DEFAULT 0,
                IP_VM VARCHAR(45) NULL,
                EstadoVM VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',
                FechaCreacion DATE NULL,
                Observacion TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (IdVM), UNIQUE KEY uq_vm_nombre (NombreVM),
                KEY idx_vm_maquina (IdMaquina), KEY idx_vm_hipervisor (IdHipervisor)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS software (
                IdSoftware INT UNSIGNED NOT NULL AUTO_INCREMENT,
                NombreSoftware VARCHAR(140) NOT NULL,
                TipoSoftware VARCHAR(40) NOT NULL,
                Fabricante VARCHAR(100) NULL,
                Version VARCHAR(60) NULL,
                TipoLicencia VARCHAR(40) NULL,
                NumeroLicencia VARCHAR(180) NULL,
                FechaInicioLicencia DATE NULL,
                FechaFinLicencia DATE NULL,
                Criticidad VARCHAR(20) NOT NULL DEFAULT 'MEDIA',
                EstadoSoftware VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
                Observacion TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (IdSoftware), KEY idx_software_nombre (NombreSoftware)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS vm_software (
                IdVMSoftware INT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdVM INT UNSIGNED NOT NULL,
                IdSoftware INT UNSIGNED NOT NULL,
                FechaInstalacion DATE NULL,
                VersionInstalada VARCHAR(60) NULL,
                EstadoInstalacion VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
                Observacion TEXT NULL,
                PRIMARY KEY (IdVMSoftware), UNIQUE KEY uq_vm_software (IdVM,IdSoftware),
                CONSTRAINT fk_vms_vm FOREIGN KEY (IdVM) REFERENCES maquina_virtual (IdVM) ON DELETE CASCADE,
                CONSTRAINT fk_vms_software FOREIGN KEY (IdSoftware) REFERENCES software (IdSoftware) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS servicio_ti (
                IdServicio INT UNSIGNED NOT NULL AUTO_INCREMENT,
                NombreServicio VARCHAR(140) NOT NULL,
                Descripcion TEXT NULL,
                Categoria VARCHAR(60) NULL,
                Criticidad VARCHAR(20) NOT NULL DEFAULT 'MEDIA',
                Responsable VARCHAR(120) NULL,
                SLA VARCHAR(80) NULL,
                EstadoServicio VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (IdServicio), KEY idx_servicio_nombre (NombreServicio)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS servicio_vm (
                IdServicioVM INT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdServicio INT UNSIGNED NOT NULL,
                IdVM INT UNSIGNED NOT NULL,
                RolVM VARCHAR(80) NULL,
                PRIMARY KEY (IdServicioVM), UNIQUE KEY uq_servicio_vm (IdServicio,IdVM),
                CONSTRAINT fk_svm_servicio FOREIGN KEY (IdServicio) REFERENCES servicio_ti (IdServicio) ON DELETE CASCADE,
                CONSTRAINT fk_svm_vm FOREIGN KEY (IdVM) REFERENCES maquina_virtual (IdVM) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS servicio_software (
                IdServicioSoftware INT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdServicio INT UNSIGNED NOT NULL,
                IdSoftware INT UNSIGNED NOT NULL,
                RolSoftware VARCHAR(120) NULL,
                Observacion TEXT NULL,
                PRIMARY KEY (IdServicioSoftware), UNIQUE KEY uq_servicio_software (IdServicio,IdSoftware),
                CONSTRAINT fk_ss_servicio FOREIGN KEY (IdServicio) REFERENCES servicio_ti (IdServicio) ON DELETE CASCADE,
                CONSTRAINT fk_ss_software FOREIGN KEY (IdSoftware) REFERENCES software (IdSoftware) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS aplicacion (
                IdAplicacion INT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdSoftwareOrigen INT UNSIGNED NULL,
                NombreAplicacion VARCHAR(140) NOT NULL,
                TipoAplicacion VARCHAR(60) NOT NULL DEFAULT 'Aplicación',
                Version VARCHAR(60) NULL,
                Fabricante VARCHAR(100) NULL,
                Ambiente VARCHAR(30) NOT NULL DEFAULT 'PRODUCCION',
                Criticidad VARCHAR(20) NOT NULL DEFAULT 'MEDIA',
                Responsable VARCHAR(120) NULL,
                EstadoAplicacion VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',
                Descripcion TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (IdAplicacion),
                UNIQUE KEY uq_aplicacion_nombre (NombreAplicacion),
                UNIQUE KEY uq_aplicacion_origen (IdSoftwareOrigen)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS software_componente (
                IdComponente INT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdAplicacion INT UNSIGNED NOT NULL,
                IdSoftwareOrigen INT UNSIGNED NULL,
                NombreComponente VARCHAR(140) NOT NULL,
                TipoComponente VARCHAR(60) NOT NULL,
                Version VARCHAR(60) NULL,
                Fabricante VARCHAR(100) NULL,
                EstadoComponente VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
                Observacion TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (IdComponente),
                UNIQUE KEY uq_componente_aplicacion_nombre (IdAplicacion,NombreComponente),
                UNIQUE KEY uq_componente_aplicacion_origen (IdAplicacion,IdSoftwareOrigen),
                CONSTRAINT fk_componente_aplicacion FOREIGN KEY (IdAplicacion) REFERENCES aplicacion (IdAplicacion) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS aplicacion_vm (
                IdAplicacionVM INT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdAplicacion INT UNSIGNED NOT NULL,
                IdVM INT UNSIGNED NOT NULL,
                Ambiente VARCHAR(30) NOT NULL DEFAULT 'PRODUCCION',
                RolVM VARCHAR(100) NULL,
                FechaDespliegue DATE NULL,
                EstadoDespliegue VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
                Observacion TEXT NULL,
                PRIMARY KEY (IdAplicacionVM),
                UNIQUE KEY uq_aplicacion_vm (IdAplicacion,IdVM),
                CONSTRAINT fk_avm_aplicacion FOREIGN KEY (IdAplicacion) REFERENCES aplicacion (IdAplicacion) ON DELETE CASCADE,
                CONSTRAINT fk_avm_vm FOREIGN KEY (IdVM) REFERENCES maquina_virtual (IdVM) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS servicio_aplicacion (
                IdServicioAplicacion INT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdServicio INT UNSIGNED NOT NULL,
                IdAplicacion INT UNSIGNED NOT NULL,
                Modulo VARCHAR(140) NULL,
                Observacion TEXT NULL,
                PRIMARY KEY (IdServicioAplicacion),
                UNIQUE KEY uq_servicio_aplicacion (IdServicio,IdAplicacion),
                CONSTRAINT fk_sa_servicio FOREIGN KEY (IdServicio) REFERENCES servicio_ti (IdServicio) ON DELETE CASCADE,
                CONSTRAINT fk_sa_aplicacion FOREIGN KEY (IdAplicacion) REFERENCES aplicacion (IdAplicacion) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS configuracion_capacidad_vm (
                IdConfiguracion INT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdVM INT UNSIGNED NOT NULL,
                CPUAsignada DECIMAL(10,2) NOT NULL DEFAULT 0,
                RAMAsignada DECIMAL(12,2) NOT NULL DEFAULT 0,
                DiscoAsignado DECIMAL(14,2) NOT NULL DEFAULT 0,
                UmbralAdvertencia DECIMAL(5,2) NOT NULL DEFAULT 75,
                UmbralCritico DECIMAL(5,2) NOT NULL DEFAULT 90,
                FechaRegistro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                Responsable VARCHAR(120) NULL,
                Observacion TEXT NULL,
                Activa TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (IdConfiguracion), KEY idx_config_vm_fecha (IdVM,FechaRegistro),
                CONSTRAINT fk_config_vm FOREIGN KEY (IdVM) REFERENCES maquina_virtual (IdVM) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS historial_capacidad (
                IdHistorial BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdVM INT UNSIGNED NOT NULL,
                FechaEvaluacion DATETIME NOT NULL,
                Recurso VARCHAR(20) NOT NULL,
                CapacidadTotal DECIMAL(14,2) NOT NULL,
                ConsumoActual DECIMAL(14,2) NOT NULL,
                PorcentajeUso DECIMAL(6,2) NOT NULL,
                UmbralAlerta DECIMAL(5,2) NOT NULL DEFAULT 90,
                Estado VARCHAR(20) NOT NULL,
                RegistradoPor INT NOT NULL DEFAULT 0,
                Observacion TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (IdHistorial), KEY idx_historial_vm_fecha (IdVM,FechaEvaluacion),
                KEY idx_historial_estado (Estado),
                CONSTRAINT fk_historial_vm FOREIGN KEY (IdVM) REFERENCES maquina_virtual (IdVM) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS alerta_capacidad (
                IdAlerta BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                IdVM INT UNSIGNED NOT NULL,
                IdHistorial BIGINT UNSIGNED NULL,
                IdIncidencia BIGINT UNSIGNED NULL,
                FechaAlerta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                Recurso VARCHAR(20) NOT NULL,
                PorcentajeDetectado DECIMAL(6,2) NOT NULL,
                NivelAlerta VARCHAR(20) NOT NULL,
                Descripcion VARCHAR(500) NOT NULL,
                EstadoAlerta VARCHAR(20) NOT NULL DEFAULT 'ABIERTA',
                AtendidaPor INT NULL,
                FechaAtencion DATETIME NULL,
                ComentarioAtencion VARCHAR(500) NULL,
                PRIMARY KEY (IdAlerta), KEY idx_alerta_estado_fecha (EstadoAlerta,FechaAlerta),
                KEY idx_alerta_vm (IdVM),
                KEY idx_alerta_incidencia (IdIncidencia),
                CONSTRAINT fk_alerta_vm FOREIGN KEY (IdVM) REFERENCES maquina_virtual (IdVM) ON DELETE CASCADE,
                CONSTRAINT fk_alerta_historial FOREIGN KEY (IdHistorial) REFERENCES historial_capacidad (IdHistorial) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
        foreach ($queries as $query) $db->query($query);

        // Trazabilidad alerta -> ticket. Esta migracion es repetible y mantiene
        // compatibles las instalaciones que ya tienen alerta_capacidad.
        $alertIncidentColumn = $db->query('SHOW COLUMNS FROM alerta_capacidad LIKE \'IdIncidencia\'');
        if (!$db->num_rows($alertIncidentColumn)) {
            $db->query('ALTER TABLE alerta_capacidad ADD COLUMN IdIncidencia BIGINT UNSIGNED NULL AFTER IdHistorial');
        }
        $alertIncidentIndex = $db->query('SHOW INDEX FROM alerta_capacidad WHERE Key_name=\'idx_alerta_incidencia\'');
        if (!$db->num_rows($alertIncidentIndex)) {
            $db->query('ALTER TABLE alerta_capacidad ADD KEY idx_alerta_incidencia (IdIncidencia)');
        }
        // La Mesa de Ayuda puede instalarse despues de Capacidad TI. Por eso la
        // clave foranea se agrega solamente cuando inc_tickets ya esta disponible.
        $incidentTable = $db->query('SHOW TABLES LIKE \'inc_tickets\'');
        if ($db->num_rows($incidentTable)) {
            $db->query('UPDATE alerta_capacidad a
                LEFT JOIN inc_tickets t ON t.IdTicket=a.IdIncidencia
                SET a.IdIncidencia=NULL
                WHERE a.IdIncidencia IS NOT NULL AND t.IdTicket IS NULL');
            $alertIncidentFk = $db->query('SELECT CONSTRAINT_NAME
                FROM information_schema.REFERENTIAL_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA=DATABASE()
                  AND TABLE_NAME=\'alerta_capacidad\'
                  AND CONSTRAINT_NAME=\'fk_cap_alerta_incidencia\'');
            if (!$db->num_rows($alertIncidentFk)) {
                $db->query('ALTER TABLE alerta_capacidad
                    ADD CONSTRAINT fk_cap_alerta_incidencia
                    FOREIGN KEY (IdIncidencia) REFERENCES inc_tickets (IdTicket) ON DELETE SET NULL');
            }
        }

        $hypervisorColumns = [
            'NucleosCPU' => "ALTER TABLE hipervisor ADD COLUMN NucleosCPU SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER Licencia",
            'FrecuenciaCPUMHz' => "ALTER TABLE hipervisor ADD COLUMN FrecuenciaCPUMHz DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER NucleosCPU"
        ];
        foreach ($hypervisorColumns as $column => $alter) {
            $exists = $db->query("SHOW COLUMNS FROM hipervisor LIKE '{$column}'");
            if (!$db->num_rows($exists)) $db->query($alter);
        }

        // Migra el catálogo anterior al modelo Aplicación -> Componentes -> VM.
        // Las tablas antiguas se conservan para compatibilidad y trazabilidad.
        $db->query("INSERT INTO aplicacion
                (IdSoftwareOrigen,NombreAplicacion,TipoAplicacion,Version,Fabricante,Ambiente,Criticidad,Responsable,EstadoAplicacion,Descripcion)
            SELECT s.IdSoftware,
                   CASE WHEN UPPER(TRIM(s.NombreSoftware))='APP SOPORTE' THEN 'Sistema de Soporte TI' ELSE s.NombreSoftware END,
                   CASE WHEN UPPER(TRIM(s.NombreSoftware))='APP SOPORTE' THEN 'Aplicación Web' ELSE 'Aplicación Empresarial' END,
                   s.Version,s.Fabricante,'PRODUCCION',s.Criticidad,
                   CASE WHEN UPPER(TRIM(s.NombreSoftware))='APP SOPORTE' THEN 'Área de Sistemas' ELSE '' END,
                   CASE WHEN s.EstadoSoftware='ACTIVO' THEN 'ACTIVA' ELSE 'INACTIVA' END,
                   s.Observacion
            FROM software s
            WHERE UPPER(TRIM(s.TipoSoftware)) IN ('APLICACIÓN','APLICACION')
              AND UPPER(TRIM(s.NombreSoftware)) NOT IN ('XAMPP')
            ON DUPLICATE KEY UPDATE IdSoftwareOrigen=VALUES(IdSoftwareOrigen)");
        $db->query("INSERT IGNORE INTO aplicacion_vm
                (IdAplicacion,IdVM,Ambiente,RolVM,FechaDespliegue,EstadoDespliegue,Observacion)
            SELECT a.IdAplicacion,vs.IdVM,a.Ambiente,'Servidor de aplicación',vs.FechaInstalacion,
                   CASE WHEN vs.EstadoInstalacion='ACTIVO' THEN 'ACTIVO' ELSE 'INACTIVO' END,vs.Observacion
            FROM aplicacion a INNER JOIN vm_software vs ON vs.IdSoftware=a.IdSoftwareOrigen");
        $db->query("INSERT IGNORE INTO servicio_aplicacion
                (IdServicio,IdAplicacion,Modulo,Observacion)
            SELECT ss.IdServicio,a.IdAplicacion,ss.RolSoftware,ss.Observacion
            FROM servicio_software ss INNER JOIN aplicacion a ON a.IdSoftwareOrigen=ss.IdSoftware");
        $db->query("INSERT IGNORE INTO software_componente
                (IdAplicacion,IdSoftwareOrigen,NombreComponente,TipoComponente,Version,Fabricante,EstadoComponente,Observacion)
            SELECT DISTINCT a.IdAplicacion,s.IdSoftware,s.NombreSoftware,
                   CASE WHEN UPPER(TRIM(s.NombreSoftware))='XAMPP' THEN 'Plataforma' ELSE s.TipoSoftware END,
                   s.Version,s.Fabricante,
                   CASE WHEN s.EstadoSoftware='ACTIVO' THEN 'ACTIVO' ELSE 'INACTIVO' END,s.Observacion
            FROM aplicacion a
            INNER JOIN vm_software appvm ON appvm.IdSoftware=a.IdSoftwareOrigen
            INNER JOIN vm_software compvm ON compvm.IdVM=appvm.IdVM
            INNER JOIN software s ON s.IdSoftware=compvm.IdSoftware
            WHERE s.IdSoftware<>a.IdSoftwareOrigen
              AND (UPPER(TRIM(s.TipoSoftware)) NOT IN ('APLICACIÓN','APLICACION') OR UPPER(TRIM(s.NombreSoftware))='XAMPP')
              AND (UPPER(TRIM(s.NombreSoftware))<>'XAMPP' OR UPPER(TRIM(a.NombreAplicacion))='SISTEMA DE SOPORTE TI')");
        // Compartir una VM no demuestra que todas sus aplicaciones dependan de XAMPP.
        // Conservamos esa dependencia solo para el Sistema de Soporte confirmado.
        $db->query("DELETE sc FROM software_componente sc
            INNER JOIN aplicacion a ON a.IdAplicacion=sc.IdAplicacion
            INNER JOIN software s ON s.IdSoftware=sc.IdSoftwareOrigen
            WHERE UPPER(TRIM(s.NombreSoftware))='XAMPP'
              AND UPPER(TRIM(a.NombreAplicacion))<>'SISTEMA DE SOPORTE TI'");

        // Recupera la cantidad de CPU del inventario existente y configura el host
        // Xeon Silver 4110 informado para que las mediciones VMware usen MHz.
        $db->query("UPDATE hipervisor h INNER JOIN maquina m ON m.IdMaquina=h.IdMaquina
            SET h.NucleosCPU=CAST(SUBSTRING_INDEX(TRIM(m.Procesador),' ',1) AS UNSIGNED)
            WHERE h.NucleosCPU=0 AND TRIM(m.Procesador) REGEXP '^[0-9]+[[:space:]]+CPU'");
        $db->query("UPDATE hipervisor h INNER JOIN maquina m ON m.IdMaquina=h.IdMaquina
            SET h.FrecuenciaCPUMHz=2100
            WHERE h.FrecuenciaCPUMHz=0 AND m.Procesador LIKE '%Xeon(R) Silver 4110%'");

        // Elimina solo las alertas originadas por el cálculo antiguo MHz / vCPU.
        $db->query("DELETE a FROM alerta_capacidad a
            INNER JOIN historial_capacidad hc ON hc.IdHistorial=a.IdHistorial
            INNER JOIN maquina_virtual v ON v.IdVM=hc.IdVM
            INNER JOIN hipervisor h ON h.IdHipervisor=v.IdHipervisor
            LEFT JOIN configuracion_capacidad_vm c ON c.IdVM=v.IdVM AND c.Activa=1
            WHERE hc.Recurso='CPU' AND hc.CapacidadTotal<=128 AND h.FrecuenciaCPUMHz>0
              AND (hc.ConsumoActual/(COALESCE(NULLIF(c.CPUAsignada,0),v.CPUAsignada)*h.FrecuenciaCPUMHz)*100)
                  < COALESCE(c.UmbralAdvertencia,75)");
        $db->query("UPDATE historial_capacidad hc
            INNER JOIN maquina_virtual v ON v.IdVM=hc.IdVM
            INNER JOIN hipervisor h ON h.IdHipervisor=v.IdHipervisor
            LEFT JOIN configuracion_capacidad_vm c ON c.IdVM=v.IdVM AND c.Activa=1
            SET hc.CapacidadTotal=ROUND(COALESCE(NULLIF(c.CPUAsignada,0),v.CPUAsignada)*h.FrecuenciaCPUMHz,2),
                hc.PorcentajeUso=ROUND(hc.ConsumoActual/(COALESCE(NULLIF(c.CPUAsignada,0),v.CPUAsignada)*h.FrecuenciaCPUMHz)*100,2),
                hc.UmbralAlerta=CASE
                    WHEN (hc.ConsumoActual/(COALESCE(NULLIF(c.CPUAsignada,0),v.CPUAsignada)*h.FrecuenciaCPUMHz)*100)>=COALESCE(c.UmbralCritico,90)
                    THEN COALESCE(c.UmbralCritico,90) ELSE COALESCE(c.UmbralAdvertencia,75) END,
                hc.Estado=CASE
                    WHEN (hc.ConsumoActual/(COALESCE(NULLIF(c.CPUAsignada,0),v.CPUAsignada)*h.FrecuenciaCPUMHz)*100)>=COALESCE(c.UmbralCritico,90) THEN 'CRITICO'
                    WHEN (hc.ConsumoActual/(COALESCE(NULLIF(c.CPUAsignada,0),v.CPUAsignada)*h.FrecuenciaCPUMHz)*100)>=COALESCE(c.UmbralAdvertencia,75) THEN 'ADVERTENCIA'
                    ELSE 'NORMAL' END
            WHERE hc.Recurso='CPU' AND hc.CapacidadTotal<=128 AND h.FrecuenciaCPUMHz>0
              AND COALESCE(NULLIF(c.CPUAsignada,0),v.CPUAsignada)>0");

        $db->query("INSERT INTO modulos (slug,nombre,descripcion,icono,estado,orden)
            SELECT 'capacidad_ti','Gestión de Capacidad TI','Servidores, virtualización, servicios, mediciones y alertas de capacidad','fas fa-server',1,46
            FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM modulos WHERE slug='capacidad_ti')");

        $groups = $db->query("SELECT group_level FROM user_groups WHERE group_status=1");
        while ($group = $db->fetch_assoc($groups)) {
            $level = (int)$group['group_level'];
            $create = $level <= 2 ? 1 : 0;
            $edit = $level <= 2 ? 1 : 0;
            $delete = $level === 1 ? 1 : 0;
            $db->query("INSERT IGNORE INTO permisos_modulos (group_level,modulo,puede_ver,puede_crear,puede_editar,puede_eliminar)
                VALUES ({$level},'capacidad_ti',1,{$create},{$edit},{$delete})");
        }
        $ready = true;
    }
}

if (!function_exists('cap_redirect')) {
    function cap_redirect(string $message, string $type = 's', string $tab = ''): void {
        global $session;
        $session->msg($type, $message);
        $url = 'index.php'.($tab !== '' ? '#'.$tab : '');
        redirect($url, false);
    }
}
