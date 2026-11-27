<?php
/**
 * api/encuesta/get_mis_encuestas.php
 * Retorna encuestas activas con flag ya_respondio para el usuario actual
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/load.php';
require_once __DIR__ . '/ConsultasEncuesta.php';

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$idUsuario = (int)$user['id'];

try {
    $pdo = ConexionEncuesta::conectar();
    $sql = "SELECT e.id_encuesta, e.titulo, e.descripcion, e.fecha_cierre,
                   COUNT(DISTINCT p.id_pregunta) AS total_preguntas,
                   (SELECT COUNT(*) FROM respuestas r2
                    WHERE r2.id_encuesta = e.id_encuesta
                      AND r2.id_usuario  = :uid) AS ya_respondio
            FROM   encuestas e
            LEFT JOIN preguntas p ON p.id_encuesta = e.id_encuesta
            WHERE  e.activa = 1
              AND  (e.fecha_cierre IS NULL OR e.fecha_cierre >= CURDATE())
            GROUP BY e.id_encuesta
            ORDER BY e.fecha_creacion DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $idUsuario]);
    $data = $stmt->fetchAll();

    // Asegurar tipos correctos
    foreach ($data as &$row) {
        $row['ya_respondio']    = (bool)$row['ya_respondio'];
        $row['total_preguntas'] = (int)$row['total_preguntas'];
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
