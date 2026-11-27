<?php
/**
 * API: Historial de versiones DWG de un plano (FH ING)
 * GET: ?id_planos=N
 * Responde: { success, cod_plano, desc_plano, actual: {...}, historial: [...] }
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    $id_planos = isset($_GET['id_planos']) ? (int)$_GET['id_planos'] : 0;
    if ($id_planos <= 0) {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'id_planos inválido']);
        exit;
    }

    global $db;

    // ── Asegurar que la tabla exista ────────────────────────────────
    $db->query(
        "CREATE TABLE IF NOT EXISTS fh_ing_plano_dwg_history (
            id            INT          NOT NULL AUTO_INCREMENT,
            id_planos     INT          NOT NULL,
            arch_plano    VARCHAR(255) NOT NULL,
            arch_original VARCHAR(255) NOT NULL DEFAULT '',
            tamano        BIGINT       DEFAULT NULL,
            usuario       VARCHAR(120) NOT NULL DEFAULT '',
            created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_id_planos (id_planos)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    // ── Plano actual ────────────────────────────────────────────────
    $row = find_by_sql(
        "SELECT cod_plano, desc_plano, arch_plano, arch_original
         FROM pln_dtll_Ing WHERE id_planos = $id_planos LIMIT 1"
    );
    if (empty($row)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Plano no encontrado']);
        exit;
    }

    $actual = [
        'id_planos'    => $id_planos,
        'arch_plano'   => $row[0]['arch_plano']    ?? '',
        'arch_original'=> $row[0]['arch_original'] ?? '',
    ];

    // ── Historial de versiones (más reciente primero) ───────────────
    $historial = [];
    $hist_rows = find_by_sql(
        "SELECT id, arch_plano, arch_original, tamano, usuario,
                DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS fecha_str
         FROM fh_ing_plano_dwg_history
         WHERE id_planos = $id_planos
         ORDER BY created_at DESC"
    );
    foreach (is_array($hist_rows) ? $hist_rows : [] as $h) {
        $historial[] = [
            'id'           => (int)$h['id'],
            'arch_plano'   => $h['arch_plano'],
            'arch_original'=> $h['arch_original'] ?: $h['arch_plano'],
            'tamano'       => $h['tamano'] !== null ? (int)$h['tamano'] : null,
            'usuario'      => $h['usuario'],
            'fecha_str'    => $h['fecha_str'],
        ];
    }

    ob_end_clean();
    echo json_encode([
        'success'   => true,
        'cod_plano' => $row[0]['cod_plano']  ?? '',
        'desc_plano'=> $row[0]['desc_plano'] ?? '',
        'actual'    => $actual,
        'historial' => $historial,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno']);
}
