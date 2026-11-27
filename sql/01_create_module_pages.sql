-- ============================================================================
-- Crear tabla MODULE_PAGES - Subpáginas dinámicas de módulos
-- Base de datos: tama
-- Fecha: 03-03-2026
-- Descripción: Tabla para crear jerarquía N-niveles dentro de cada módulo
-- ============================================================================

CREATE TABLE IF NOT EXISTS `module_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `modulo_slug` varchar(100) NOT NULL COMMENT 'FK a modulos.slug',
  `parent_id` int(11) DEFAULT NULL COMMENT 'NULL=raíz, >0=subpágina',
  `title` varchar(200) NOT NULL COMMENT 'Nombre visible en el menú',
  `url` varchar(255) NOT NULL COMMENT 'Ruta de la página',
  `icon` varchar(100) DEFAULT 'fas fa-file' COMMENT 'Icono FontAwesome',
  `orden` int(11) DEFAULT 0 COMMENT 'Orden de visualización',
  `estado` tinyint(1) DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `created_at` timestamp DEFAULT current_timestamp() COMMENT 'Fecha creación',
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Fecha última actualización',
  
  -- Claves foráneas
  FOREIGN KEY (`modulo_slug`) REFERENCES `modulos`(`slug`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `module_pages`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  
  -- Índices para rendimiento
  INDEX `idx_modulo_parent` (`modulo_slug`, `parent_id`),
  INDEX `idx_parent` (`parent_id`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Subpáginas jerárquicas de módulos';

-- ============================================================================
-- Insertar datos de ejemplo para módulo ACTIVOS
-- ============================================================================

-- Raíz: Activos / Equipos
INSERT INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES ('activos', NULL, 'Activos / Equipos', '/modules/activos/index.php', 'fas fa-laptop', 0, 1)
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Nivel 1: Subcategorías
INSERT INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('activos', (SELECT MAX(id) FROM module_pages WHERE modulo_slug='activos' AND parent_id IS NULL), 'Computadoras', '/modules/activos/computadoras.php', 'fas fa-desktop', 1, 1),
('activos', (SELECT MAX(id) FROM module_pages WHERE modulo_slug='activos' AND parent_id IS NULL), 'Celulares', '/modules/activos/celulares.php', 'fas fa-mobile-alt', 2, 1),
('activos', (SELECT MAX(id) FROM module_pages WHERE modulo_slug='activos' AND parent_id IS NULL), 'Licencias', '/modules/activos/licencias.php', 'fas fa-key', 3, 1)
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- ============================================================================
-- Insertar datos de ejemplo para módulo USUARIOS
-- ============================================================================

INSERT INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES ('usuarios', NULL, 'Gestión de Usuarios', '/modules/usuarios/index.php', 'fas fa-users', 0, 1)
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Nivel 1: Opciones bajo Usuarios
INSERT INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('usuarios', (SELECT MAX(id) FROM module_pages WHERE modulo_slug='usuarios' AND parent_id IS NULL), 'Agregar Usuario', '/modules/usuarios/agregar.php', 'fas fa-user-plus', 1, 1),
('usuarios', (SELECT MAX(id) FROM module_pages WHERE modulo_slug='usuarios' AND parent_id IS NULL), 'Listar Usuarios', '/modules/usuarios/index.php', 'fas fa-list', 2, 1),
('usuarios', (SELECT MAX(id) FROM module_pages WHERE modulo_slug='usuarios' AND parent_id IS NULL), 'Permisos', '/modules/usuarios/permisos.php', 'fas fa-lock', 3, 1)
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- ============================================================================
-- Insertar datos de ejemplo para módulo REPORTES
-- ============================================================================

INSERT INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES ('reportes', NULL, 'Reportes', '/modules/reportes/index.php', 'fas fa-chart-bar', 0, 1)
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Nivel 1: Tipos de reportes
INSERT INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('reportes', (SELECT MAX(id) FROM module_pages WHERE modulo_slug='reportes' AND parent_id IS NULL), 'Activos', '/modules/reportes/activos.php', 'fas fa-hdd', 1, 1),
('reportes', (SELECT MAX(id) FROM module_pages WHERE modulo_slug='reportes' AND parent_id IS NULL), 'VPN', '/modules/reportes/vpn.php', 'fas fa-network-wired', 2, 1)
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- ============================================================================
-- MENSAJE DE CONFIRMACIÓN
-- ============================================================================
-- Tabla creada exitosamente: module_pages
-- Registros iniciales insertados: ~10 registros de ejemplo
-- Estado: LISTO PARA USAR
-- ============================================================================
