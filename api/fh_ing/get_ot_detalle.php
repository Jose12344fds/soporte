<?php
/**
 * API: Obtener registros de OT Detalle (FH_ING)
 * GET: ?ot=OTC-XXXXXX  (opcional, sin parámetro devuelve todos)
 * GET: ?id=N            (opcional, devuelve uno)
 * GET: ?modulo=1        (OTs visibles en Módulo 1: sin cronograma aprobado)
 * GET: ?modulo=2        (OTs visibles en Módulo 2: cronograma aprobado, estado=1)
 * Sin modulo: devuelve OTs únicas (última fila por OT) con info de estado
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';

    $ot_filter = isset($_GET['ot'])     ? trim($_GET['ot'])    : '';
    $id_filter = isset($_GET['id'])     ? (int)$_GET['id']     : 0;
    $modulo    = isset($_GET['modulo']) ? (int)$_GET['modulo'] : 0;

    if ($id_filter > 0) {
        $rows = find_by_sql("SELECT * FROM fh_ing_ot_detalle WHERE id = $id_filter LIMIT 1");
        ob_end_clean();
        echo json_encode(is_array($rows) ? $rows : [], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }

    if ($ot_filter !== '') {
        $ot_esc = $db->escape($ot_filter);
        $rows = find_by_sql("SELECT * FROM fh_ing_ot_detalle WHERE ot = '$ot_esc' ORDER BY created_at DESC");
        ob_end_clean();
        echo json_encode(is_array($rows) ? $rows : [], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }

    // ── Verificar qué tablas/columnas existen (migraciones podrían no haberse ejecutado) ──
    $tblCheck = find_by_sql("
        SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fh_ing_planos_cab'
    ");
    $hasPlanosCab = (int)($tblCheck[0]['cnt'] ?? 0) > 0;

    $hasEstado = false;
    if ($hasPlanosCab) {
        $colCheck = find_by_sql("
            SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'fh_ing_planos_cab'
              AND COLUMN_NAME  = 'estado'
        ");
        $hasEstado = (int)($colCheck[0]['cnt'] ?? 0) > 0;
    }

    // Subquery reutilizable: última fila por OT
    $subLatest = "(SELECT ot, MAX(id) AS max_id FROM fh_ing_ot_detalle GROUP BY ot) latest";

    if ($modulo === 4) {
        // Módulo 4 (finalizados): solo OTs con estado = 3
        if (!$hasEstado) {
            $rows = [];
        } else {
            $rows = find_by_sql("
                SELECT d.*, c.estado AS estado_plano, 1 AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                INNER JOIN fh_ing_planos_cab c ON c.ot = d.ot
                WHERE c.estado = 3
                ORDER BY d.created_at DESC
            ");
        }
    } elseif ($modulo === 3) {
        // Módulo 3: solo OTs aprobadas desde Módulo 2 (estado = 2 exactamente, no finalizadas)
        if (!$hasEstado) {
            $rows = [];
        } else {
            $rows = find_by_sql("
                SELECT d.*, c.estado AS estado_plano, 1 AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                INNER JOIN fh_ing_planos_cab c ON c.ot = d.ot
                WHERE c.estado = 2
                ORDER BY d.created_at DESC
            ");
        }
    } elseif ($modulo === 2) {
        // Módulo 2: solo OTs con cronograma aprobado (estado = 1)
        // Si la columna/tabla no existe aún, no hay nada que mostrar
        if (!$hasEstado) {
            $rows = [];
        } else {
            $rows = find_by_sql("
                SELECT d.*, c.estado AS estado_plano, 1 AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                INNER JOIN fh_ing_planos_cab c ON c.ot = d.ot
                WHERE c.estado = 1
                ORDER BY d.created_at DESC
            ");
        }
    } elseif ($modulo === 12) {
        // Módulo 1+2 combinado: OTs en progreso (estado < 2, no en Módulo 3 ni finalizadas)
        if (!$hasPlanosCab) {
            $rows = find_by_sql("
                SELECT d.*, -1 AS estado_plano, 0 AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                ORDER BY d.created_at DESC
            ");
        } elseif ($hasEstado) {
            $rows = find_by_sql("
                SELECT d.*, COALESCE(c.estado, -1) AS estado_plano,
                       IF(c.id IS NOT NULL, 1, 0) AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                LEFT JOIN fh_ing_planos_cab c ON c.ot = d.ot
                WHERE COALESCE(c.estado, 0) < 2
                ORDER BY d.created_at DESC
            ");
        } else {
            $rows = find_by_sql("
                SELECT d.*, -1 AS estado_plano,
                       IF(c.id IS NOT NULL, 1, 0) AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                LEFT JOIN fh_ing_planos_cab c ON c.ot = d.ot
                ORDER BY d.created_at DESC
            ");
        }
    } elseif ($modulo === 1) {
        // Módulo 1: todas las OTs aún no aprobadas
        if (!$hasPlanosCab) {
            // Tabla de cronogramas no existe: mostrar todas las OTs sin JOIN
            $rows = find_by_sql("
                SELECT d.*, -1 AS estado_plano, 0 AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                ORDER BY d.created_at DESC
            ");
        } elseif ($hasEstado) {
            // Con columna estado: excluir las aprobadas (estado >= 1)
            $rows = find_by_sql("
                SELECT d.*, COALESCE(c.estado, -1) AS estado_plano,
                       IF(c.id IS NOT NULL, 1, 0) AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                LEFT JOIN fh_ing_planos_cab c ON c.ot = d.ot
                WHERE COALESCE(c.estado, 0) < 1
                ORDER BY d.created_at DESC
            ");
        } else {
            // Tabla existe pero sin columna estado: mostrar todas sin filtrar
            $rows = find_by_sql("
                SELECT d.*, -1 AS estado_plano,
                       IF(c.id IS NOT NULL, 1, 0) AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                LEFT JOIN fh_ing_planos_cab c ON c.ot = d.ot
                ORDER BY d.created_at DESC
            ");
        }
    } else {
        // Sin filtro: todas las OTs únicas
        if (!$hasPlanosCab) {
            $rows = find_by_sql("
                SELECT d.*, -1 AS estado_plano, 0 AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                ORDER BY d.created_at DESC
            ");
        } else {
            $estadoCol = $hasEstado ? "COALESCE(c.estado, -1)" : "-1";
            $rows = find_by_sql("
                SELECT d.*, $estadoCol AS estado_plano,
                       IF(c.id IS NOT NULL, 1, 0) AS _tiene_plano
                FROM fh_ing_ot_detalle d
                INNER JOIN $subLatest ON latest.ot = d.ot AND latest.max_id = d.id
                LEFT JOIN fh_ing_planos_cab c ON c.ot = d.ot
                ORDER BY d.created_at DESC
            ");
        }
    }

    if($modulo===5)$rows=array_values(array_filter(is_array($rows)?$rows:[],function($r){return (int)($r['estado_plano']??-1)!==3;}));
    ob_end_clean();
    echo json_encode(is_array($rows) ? $rows : [], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
