-- ============================================================================
-- INSERTS - Poblando tabla module_pages con datos de ejemplo
-- ============================================================================
-- Este script inserta la estructura jerárquica de páginas para cada módulo
-- ============================================================================

-- Limpiar datos previos (opcional)
-- DELETE FROM module_pages WHERE modulo_slug IN ('activos', 'usuarios', 'reportes', 'celulares', 'licencias');

-- ============================================================================
-- MÓDULO: ACTIVOS (Estructura de 3 niveles)
-- ============================================================================

-- Raíz: Activos / Equipos
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES ('activos', NULL, 'Activos / Equipos', '/modules/activos/index.php', 'fas fa-laptop', 0, 1);

-- Obtener ID de raíz para los siguientes INSERT
SET @activos_root = (SELECT id FROM module_pages WHERE modulo_slug='activos' AND parent_id IS NULL LIMIT 1);

-- Nivel 1: Subcategorías de Activos
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('activos', @activos_root, 'Computadoras', '/modules/activos/computadoras.php', 'fas fa-desktop', 1, 1),
('activos', @activos_root, 'Mantenimientos', '/modules/activos/mantenimientos.php', 'fas fa-wrench', 2, 1),
('activos', @activos_root, 'Servicios Técnicos', '/modules/activos/servicios.php', 'fas fa-tools', 3, 1);

-- Obtener IDs de nivel 1
SET @computadoras_id = (SELECT id FROM module_pages WHERE modulo_slug='activos' AND title='Computadoras' LIMIT 1);
SET @mantenimientos_id = (SELECT id FROM module_pages WHERE modulo_slug='activos' AND title='Mantenimientos' LIMIT 1);

-- Nivel 2: Subpáginas bajo Computadoras
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('activos', @computadoras_id, 'Agregar Computadora', '/modules/activos/agregar.php', 'fas fa-plus-circle', 1, 1),
('activos', @computadoras_id, 'Listar Computadoras', '/modules/activos/index.php', 'fas fa-list', 2, 1),
('activos', @computadoras_id, 'Editar', '/modules/activos/editar.php', 'fas fa-edit', 3, 1),
('activos', @computadoras_id, 'Reportes', '/modules/reportes/activos.php', 'fas fa-chart-bar', 4, 1);

-- Nivel 2: Subpáginas bajo Mantenimientos
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('activos', @mantenimientos_id, 'Registrar Mantenimiento', '/uploads/agregar_Mantenimientos.php', 'fas fa-plus-circle', 1, 1),
('activos', @mantenimientos_id, 'Ver Historial', '/modules/activos/historial.php', 'fas fa-history', 2, 1);

-- ============================================================================
-- MÓDULO: USUARIOS
-- ============================================================================

-- Raíz: Gestión de Usuarios
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES ('usuarios', NULL, 'Gestión de Usuarios', '/modules/usuarios/index.php', 'fas fa-users', 0, 1);

SET @usuarios_root = (SELECT id FROM module_pages WHERE modulo_slug='usuarios' AND parent_id IS NULL LIMIT 1);

-- Nivel 1: Opciones de Usuarios
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('usuarios', @usuarios_root, 'Agregar Usuario', '/modules/usuarios/agregar.php', 'fas fa-user-plus', 1, 1),
('usuarios', @usuarios_root, 'Listar Usuarios', '/modules/usuarios/index.php', 'fas fa-list', 2, 1),
('usuarios', @usuarios_root, 'Editar Usuario', '/modules/usuarios/editar.php', 'fas fa-edit', 3, 1),
('usuarios', @usuarios_root, 'Eliminar Usuario', '/modules/usuarios/eliminar.php', 'fas fa-trash', 4, 1),
('usuarios', @usuarios_root, 'Grupos', '/modules/usuarios/grupos.php', 'fas fa-user-shield', 5, 1);

-- ============================================================================
-- MÓDULO: REPORTES
-- ============================================================================

-- Raíz: Reportes
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES ('reportes', NULL, 'Reportes', '/modules/reportes/index.php', 'fas fa-chart-bar', 0, 1);

SET @reportes_root = (SELECT id FROM module_pages WHERE modulo_slug='reportes' AND parent_id IS NULL LIMIT 1);

-- Nivel 1: Tipos de Reportes
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('reportes', @reportes_root, 'Reportes de Activos', '/modules/reportes/activos.php', 'fas fa-hdd', 1, 1),
('reportes', @reportes_root, 'Reportes de VPN', '/modules/reportes/vpn.php', 'fas fa-network-wired', 2, 1),
('reportes', @reportes_root, 'Reportes F08', '/Reportes/PadreTI-F-08.php', 'fas fa-file-alt', 3, 1);

-- ============================================================================
-- MÓDULO: CELULARES
-- ============================================================================

-- Raíz: Celulares
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES ('celulares', NULL, 'Celulares', '/modules/celulares/index.php', 'fas fa-mobile-alt', 0, 1);

SET @celulares_root = (SELECT id FROM module_pages WHERE modulo_slug='celulares' AND parent_id IS NULL LIMIT 1);

-- Nivel 1: Opciones de Celulares
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('celulares', @celulares_root, 'Agregar Celular', '/modules/celulares/agregar.php', 'fas fa-plus-circle', 1, 1),
('celulares', @celulares_root, 'Asignar Celular', '/modules/celulares/asignar.php', 'fas fa-exchange-alt', 2, 1),
('celulares', @celulares_root, 'Editar', '/modules/celulares/editar.php', 'fas fa-edit', 3, 1),
('celulares', @celulares_root, 'Listar', '/modules/celulares/index.php', 'fas fa-list', 4, 1);

-- ============================================================================
-- MÓDULO: LICENCIAS
-- ============================================================================

-- Raíz: Licencias
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES ('licencias', NULL, 'Licencias', '/modules/licencias/index.php', 'fas fa-key', 0, 1);

SET @licencias_root = (SELECT id FROM module_pages WHERE modulo_slug='licencias' AND parent_id IS NULL LIMIT 1);

-- Nivel 1: Tipos de Licencias
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('licencias', @licencias_root, 'Licencias Office', '/modules/licencias/LicenciasOffice.php', 'fas fa-file-word', 1, 1),
('licencias', @licencias_root, 'Licencias SAP', '/modules/licencias/LicenciasSap.php', 'fas fa-database', 2, 1),
('licencias', @licencias_root, 'Ver Todas', '/modules/licencias/index.php', 'fas fa-list', 3, 1);

-- ============================================================================
-- MÓDULO: VPN
-- ============================================================================

-- Raíz: Control VPN
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES ('vpn', NULL, 'Control VPN', '/modules/vpn/index.php', 'fas fa-network-wired', 0, 1);

SET @vpn_root = (SELECT id FROM module_pages WHERE modulo_slug='vpn' AND parent_id IS NULL LIMIT 1);

-- Nivel 1: Opciones de VPN
INSERT IGNORE INTO `module_pages` (`modulo_slug`, `parent_id`, `title`, `url`, `icon`, `orden`, `estado`) 
VALUES 
('vpn', @vpn_root, 'Ver Accesos', '/modules/vpn/index.php', 'fas fa-eye', 1, 1),
('vpn', @vpn_root, 'Registros', '/modules/vpn/registros.php', 'fas fa-list', 2, 1);

-- ============================================================================
-- VERIFICACIÓN: Contar registros insertados
-- ============================================================================
-- Ejecutar esta query para verificar:
-- SELECT modulo_slug, COUNT(*) as total FROM module_pages GROUP BY modulo_slug;
-- SELECT * FROM module_pages ORDER BY modulo_slug, orden, created_at;

-- ============================================================================
-- FIN DEL SCRIPT
-- ============================================================================
