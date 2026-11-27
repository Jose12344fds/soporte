<?php
/**
 * api/encuesta/guardar_encuesta.php
 * POST — Crea o actualiza una encuesta junto con sus preguntas y opciones.
 * Recibe JSON: { titulo, descripcion, fecha_cierre, activa, userId, preguntas: [...] }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ConsultasEncuesta.php';

try {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) {
        http_response_code(400);
        echo json_encode(['error' => 'Cuerpo JSON inválido']);
        exit;
    }

    $titulo      = trim($body['titulo']       ?? '');
    $desc        = trim($body['descripcion']  ?? '');
    $fechaCierre = trim($body['fecha_cierre'] ?? '') ?: null;
    $activa      = isset($body['activa']) ? (int)$body['activa'] : 1;
    $userId      = (int)($body['userId']      ?? 0);
    $preguntas   = $body['preguntas']          ?? [];
    $idEncuesta  = (int)($body['id_encuesta'] ?? 0);

    if (!$titulo) {
        http_response_code(422);
        echo json_encode(['error' => 'El título es obligatorio']);
        exit;
    }

    $pdo = ConexionEncuesta::conectar();
    $pdo->beginTransaction();

    if ($idEncuesta > 0) {
        // — Actualizar encuesta existente —
        ConsultasEncuesta::updateEncuesta($idEncuesta, $titulo, $desc, $fechaCierre, $activa);

        // Upsert de preguntas: preservar IDs existentes para no destruir respuestas
        $keepPregIds = [];
        foreach ($preguntas as $orden => $pregData) {
            $texto      = trim($pregData['texto']    ?? '');
            $tipo       = $pregData['tipo']           ?? 'unica';
            $requerida  = isset($pregData['requerida']) ? (int)$pregData['requerida'] : 1;
            $idPregunta = (int)($pregData['id_pregunta'] ?? 0);
            if (!$texto) continue;

            if ($idPregunta > 0) {
                ConsultasEncuesta::updatePregunta($idPregunta, $texto, $tipo, $requerida, $orden + 1);
            } else {
                $idPregunta = ConsultasEncuesta::insertPregunta($idEncuesta, $texto, $tipo, $requerida, $orden + 1);
            }
            $keepPregIds[] = $idPregunta;

            // Upsert de opciones
            if (in_array($tipo, ['unica', 'multiple', 'escala'])) {
                $keepOpIds = [];
                foreach (($pregData['opciones'] ?? []) as $oOrd => $opData) {
                    $opTexto   = trim($opData['texto'] ?? '');
                    $opValor   = (int)($opData['valor'] ?? $oOrd + 1);
                    $idOpcion  = (int)($opData['id_opcion'] ?? 0);
                    if (!$opTexto) continue;

                    if ($idOpcion > 0) {
                        ConsultasEncuesta::updateOpcion($idOpcion, $opTexto, $opValor, $oOrd + 1);
                    } else {
                        $idOpcion = ConsultasEncuesta::insertOpcion($idPregunta, $opTexto, $opValor, $oOrd + 1);
                    }
                    $keepOpIds[] = $idOpcion;
                }
                // Eliminar opciones que ya no existen en el formulario
                ConsultasEncuesta::deleteOpcionesNotIn($idPregunta, $keepOpIds);
            }
        }
        // Eliminar preguntas que ya no existen en el formulario
        ConsultasEncuesta::deletePreguntasNotIn($idEncuesta, $keepPregIds);
    } else {
        // — Nueva encuesta —
        $idEncuesta = ConsultasEncuesta::insertEncuesta($titulo, $desc, $fechaCierre, $activa, $userId);

        // Insertar preguntas y opciones de la nueva encuesta
        foreach ($preguntas as $orden => $pregData) {
            $texto     = trim($pregData['texto']    ?? '');
            $tipo      = $pregData['tipo']           ?? 'unica';
            $requerida = isset($pregData['requerida']) ? (int)$pregData['requerida'] : 1;
            if (!$texto) continue;

            $idPregunta = ConsultasEncuesta::insertPregunta($idEncuesta, $texto, $tipo, $requerida, $orden + 1);

            if (in_array($tipo, ['unica', 'multiple', 'escala'])) {
                foreach (($pregData['opciones'] ?? []) as $oOrd => $opData) {
                    $opTexto = trim($opData['texto'] ?? '');
                    $opValor = (int)($opData['valor'] ?? $oOrd + 1);
                    if (!$opTexto) continue;
                    ConsultasEncuesta::insertOpcion($idPregunta, $opTexto, $opValor, $oOrd + 1);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'id_encuesta' => $idEncuesta]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
