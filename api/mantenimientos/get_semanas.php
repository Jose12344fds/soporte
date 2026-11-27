<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(dirname(__DIR__)) . '/includes/load.php';

if (!$session->isUserLoggedIn(true)) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Sesion no valida.']);
    exit;
}

try {
    $rows = find_by_sql(
        "SELECT YEAR(fecha) AS anio, semana, MIN(fecha) AS fecha_inicio, MAX(fecha) AS fecha_fin
         FROM dias_del_anio
         WHERE YEAR(fecha) >= YEAR(CURDATE())
         GROUP BY YEAR(fecha), semana
         ORDER BY MIN(fecha)"
    );

    $currentIndex = -1;
    foreach ($rows as $index => $row) {
        if (date('Y-m-d') >= $row['fecha_inicio'] && date('Y-m-d') <= $row['fecha_fin']) {
            $currentIndex = $index;
            break;
        }
    }

    if (!$rows || $currentIndex < 0) {
        throw new RuntimeException('dias_del_anio no contiene la semana actual.');
    }

    while (ob_get_level()) ob_end_clean();
    echo json_encode(['weeks' => $rows, 'current_index' => $currentIndex], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
