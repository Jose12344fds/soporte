<?php
/**
 * Migración: tabla de historial de versiones DWG por plano (FH ING)
 * Visitar una vez en el navegador:
 *   http://<servidor>/soporte/sql/_run_fh_ing_dwg_history.php
 */
require_once dirname(__DIR__) . '/includes/config.php';
$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if (!$con) { die('Error de conexión: ' . mysqli_connect_error()); }
mysqli_set_charset($con, 'utf8mb4');

$sql = "CREATE TABLE IF NOT EXISTS fh_ing_plano_dwg_history (
    id            INT          NOT NULL AUTO_INCREMENT,
    id_planos     INT          NOT NULL  COMMENT 'FK a pln_dtll_Ing.id_planos',
    arch_plano    VARCHAR(255) NOT NULL  COMMENT 'Nombre guardado en disco',
    arch_original VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Nombre original del usuario',
    tamano        BIGINT       DEFAULT NULL COMMENT 'Tamaño en bytes',
    usuario       VARCHAR(120) NOT NULL DEFAULT '',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_id_planos (id_planos),
    INDEX idx_arch (arch_plano)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($con, $sql)) {
    echo '<p style="color:green;font-family:monospace">✓ Tabla <b>fh_ing_plano_dwg_history</b> creada o ya existe.</p>';
} else {
    echo '<p style="color:red;font-family:monospace">✗ Error: '.htmlspecialchars(mysqli_error($con)).'</p>';
}
mysqli_close($con);
