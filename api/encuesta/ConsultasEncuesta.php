<?php
/**
 * ConsultasEncuesta.php — Todas las consultas del módulo Encuestas
 * Sistema de Soporte TI - TAMA
 */
require_once __DIR__ . '/ConexionEncuesta.php';

class ConsultasEncuesta {

    /* ═══════════════════════════════════════════════════════
       ENCUESTAS
       ═══════════════════════════════════════════════════════ */

    /** Lista todas las encuestas con su contador de respuestas */
    public static function getEncuestas(): array {
        $pdo = ConexionEncuesta::conectar();
        $sql = "SELECT e.*,
                       IFNULL(u.name, 'Sistema')                        AS creador,
                       COUNT(DISTINCT r.id_respuesta)                   AS total_respuestas,
                       COUNT(DISTINCT p.id_pregunta)                    AS total_preguntas
                FROM   encuestas e
                LEFT JOIN users   u ON u.id       = e.creado_por
                LEFT JOIN respuestas r ON r.id_encuesta = e.id_encuesta
                LEFT JOIN preguntas  p ON p.id_encuesta = e.id_encuesta
                GROUP BY e.id_encuesta
                ORDER BY e.fecha_creacion DESC";
        return $pdo->query($sql)->fetchAll();
    }

    /** Obtiene una encuesta por ID */
    public static function getEncuesta(int $id): ?array {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "SELECT e.*, IFNULL(u.name,'Sistema') AS creador
             FROM encuestas e
             LEFT JOIN users u ON u.id = e.creado_por
             WHERE e.id_encuesta = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Encuestas activas (para el portal de usuarios) */
    public static function getEncuestasActivas(): array {
        $pdo = ConexionEncuesta::conectar();
        $sql = "SELECT e.*,
                       COUNT(DISTINCT p.id_pregunta) AS total_preguntas
                FROM   encuestas e
                LEFT JOIN preguntas p ON p.id_encuesta = e.id_encuesta
                WHERE  e.activa = 1
                  AND  (e.fecha_cierre IS NULL OR e.fecha_cierre >= CURDATE())
                GROUP BY e.id_encuesta
                ORDER BY e.fecha_creacion DESC";
        return $pdo->query($sql)->fetchAll();
    }

    /** Inserta una nueva encuesta. Devuelve el ID generado */
    public static function insertEncuesta(string $titulo, string $desc, ?string $fechaCierre, int $activa, int $userId): int {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "INSERT INTO encuestas (titulo, descripcion, fecha_cierre, activa, creado_por)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$titulo, $desc, $fechaCierre ?: null, $activa, $userId]);
        return (int) $pdo->lastInsertId();
    }

    /** Actualiza una encuesta */
    public static function updateEncuesta(int $id, string $titulo, string $desc, ?string $fechaCierre, int $activa): bool {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "UPDATE encuestas
             SET titulo=?, descripcion=?, fecha_cierre=?, activa=?
             WHERE id_encuesta=?"
        );
        return $stmt->execute([$titulo, $desc, $fechaCierre ?: null, $activa, $id]);
    }

    /** Cambia el estado activa/inactiva */
    public static function toggleActiva(int $id): bool {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "UPDATE encuestas SET activa = IF(activa=1,0,1) WHERE id_encuesta=?"
        );
        return $stmt->execute([$id]);
    }

    /** Elimina una encuesta (cascade borra preguntas, opciones, respuestas) */
    public static function deleteEncuesta(int $id): bool {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare("DELETE FROM encuestas WHERE id_encuesta=?");
        return $stmt->execute([$id]);
    }

    /* ═══════════════════════════════════════════════════════
       PREGUNTAS
       ═══════════════════════════════════════════════════════ */

    /** Obtiene todas las preguntas de una encuesta (con sus opciones) */
    public static function getPreguntasConOpciones(int $idEncuesta): array {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "SELECT p.*,
                    o.id_opcion, o.texto AS op_texto, o.valor AS op_valor, o.orden AS op_orden
             FROM preguntas p
             LEFT JOIN opciones o ON o.id_pregunta = p.id_pregunta
             WHERE p.id_encuesta = ?
             ORDER BY p.orden, p.id_pregunta, o.orden, o.id_opcion"
        );
        $stmt->execute([$idEncuesta]);
        $rows      = $stmt->fetchAll();
        $preguntas = [];
        foreach ($rows as $r) {
            $pid = $r['id_pregunta'];
            if (!isset($preguntas[$pid])) {
                $preguntas[$pid] = [
                    'id_pregunta' => $r['id_pregunta'],
                    'id_encuesta' => $r['id_encuesta'],
                    'texto'       => $r['texto'],
                    'tipo'        => $r['tipo'],
                    'requerida'   => $r['requerida'],
                    'orden'       => $r['orden'],
                    'opciones'    => [],
                ];
            }
            if ($r['id_opcion']) {
                $preguntas[$pid]['opciones'][] = [
                    'id_opcion' => $r['id_opcion'],
                    'texto'     => $r['op_texto'],
                    'valor'     => $r['op_valor'],
                    'orden'     => $r['op_orden'],
                ];
            }
        }
        return array_values($preguntas);
    }

    /** Inserta una pregunta, devuelve su ID */
    public static function insertPregunta(int $idEncuesta, string $texto, string $tipo, int $requerida, int $orden): int {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "INSERT INTO preguntas (id_encuesta, texto, tipo, requerida, orden)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$idEncuesta, $texto, $tipo, $requerida, $orden]);
        return (int) $pdo->lastInsertId();
    }

    /** Elimina todas las preguntas de una encuesta (para re-guardar) */
    public static function deletePreguntasByEncuesta(int $idEncuesta): void {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare("DELETE FROM preguntas WHERE id_encuesta=?");
        $stmt->execute([$idEncuesta]);
    }

    /** Actualiza una pregunta existente */
    public static function updatePregunta(int $id, string $texto, string $tipo, int $requerida, int $orden): void {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "UPDATE preguntas SET texto=?, tipo=?, requerida=?, orden=? WHERE id_pregunta=?"
        );
        $stmt->execute([$texto, $tipo, $requerida, $orden, $id]);
    }

    /**
     * Elimina las preguntas de una encuesta cuyos IDs NO estén en la lista dada.
     * Si $keepIds está vacío, elimina todas.
     */
    public static function deletePreguntasNotIn(int $idEncuesta, array $keepIds): void {
        $pdo = ConexionEncuesta::conectar();
        if (empty($keepIds)) {
            $stmt = $pdo->prepare("DELETE FROM preguntas WHERE id_encuesta=?");
            $stmt->execute([$idEncuesta]);
        } else {
            $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
            $params = array_merge([$idEncuesta], $keepIds);
            $stmt = $pdo->prepare(
                "DELETE FROM preguntas WHERE id_encuesta=? AND id_pregunta NOT IN ($placeholders)"
            );
            $stmt->execute($params);
        }
    }

    /* ═══════════════════════════════════════════════════════
       OPCIONES
       ═══════════════════════════════════════════════════════ */

    /** Inserta una opción para una pregunta */
    public static function insertOpcion(int $idPregunta, string $texto, int $valor, int $orden): int {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "INSERT INTO opciones (id_pregunta, texto, valor, orden)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$idPregunta, $texto, $valor, $orden]);
        return (int) $pdo->lastInsertId();
    }

    /** Actualiza una opción existente */
    public static function updateOpcion(int $id, string $texto, int $valor, int $orden): void {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "UPDATE opciones SET texto=?, valor=?, orden=? WHERE id_opcion=?"
        );
        $stmt->execute([$texto, $valor, $orden, $id]);
    }

    /**
     * Elimina las opciones de una pregunta cuyos IDs NO estén en la lista dada.
     * Si $keepIds está vacío, elimina todas.
     */
    public static function deleteOpcionesNotIn(int $idPregunta, array $keepIds): void {
        $pdo = ConexionEncuesta::conectar();
        if (empty($keepIds)) {
            $stmt = $pdo->prepare("DELETE FROM opciones WHERE id_pregunta=?");
            $stmt->execute([$idPregunta]);
        } else {
            $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
            $params = array_merge([$idPregunta], $keepIds);
            $stmt = $pdo->prepare(
                "DELETE FROM opciones WHERE id_pregunta=? AND id_opcion NOT IN ($placeholders)"
            );
            $stmt->execute($params);
        }
    }

    /* ═══════════════════════════════════════════════════════
       RESPUESTAS
       ═══════════════════════════════════════════════════════ */

    /** Verifica si un usuario ya respondió una encuesta */
    public static function yaRespondio(int $idEncuesta, int $idUsuario): bool {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "SELECT id_respuesta FROM respuestas
             WHERE id_encuesta=? AND id_usuario=? LIMIT 1"
        );
        $stmt->execute([$idEncuesta, $idUsuario]);
        return (bool) $stmt->fetch();
    }

    /** Guarda la cabecera de respuesta. Devuelve su ID */
    public static function insertRespuesta(int $idEncuesta, int $idUsuario): int {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "INSERT INTO respuestas (id_encuesta, id_usuario) VALUES (?, ?)"
        );
        $stmt->execute([$idEncuesta, $idUsuario]);
        return (int) $pdo->lastInsertId();
    }

    /** Elimina la respuesta de un usuario a una encuesta (permite re-responder) */
    public static function deleteRespuestaUsuario(int $idEncuesta, int $idUsuario): bool {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "DELETE FROM respuestas WHERE id_encuesta=? AND id_usuario=?"
        );
        return $stmt->execute([$idEncuesta, $idUsuario]);
    }

    /** Guarda un detalle de respuesta */
    public static function insertDetalle(int $idRespuesta, int $idPregunta, ?int $idOpcion, ?string $textoResp): void {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "INSERT INTO detalle_respuestas (id_respuesta, id_pregunta, id_opcion, texto_respuesta)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$idRespuesta, $idPregunta, $idOpcion, $textoResp]);
    }

    /* ═══════════════════════════════════════════════════════
       RESULTADOS / REPORTES
       ═══════════════════════════════════════════════════════ */

    /** Resumen por opción para cada pregunta de tipo cerrado */
    public static function getResultadosOpciones(int $idEncuesta): array {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "SELECT p.id_pregunta, p.texto AS pregunta, p.tipo,
                    o.id_opcion, o.texto AS opcion, o.valor,
                    COUNT(dr.id_detalle) AS votos
             FROM   preguntas p
             LEFT JOIN opciones       o  ON o.id_pregunta   = p.id_pregunta
             LEFT JOIN detalle_respuestas dr ON dr.id_opcion = o.id_opcion
             WHERE  p.id_encuesta = ?
               AND  p.tipo IN ('unica','multiple','escala')
             GROUP BY p.id_pregunta, o.id_opcion
             ORDER BY p.orden, o.orden"
        );
        $stmt->execute([$idEncuesta]);
        return $stmt->fetchAll();
    }

    /** Respuestas abiertas de una encuesta */
    public static function getResultadosAbiertos(int $idEncuesta): array {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "SELECT p.id_pregunta, p.texto AS pregunta,
                    dr.texto_respuesta,
                    u.name AS usuario, r.fecha
             FROM   preguntas p
             JOIN   detalle_respuestas dr ON dr.id_pregunta = p.id_pregunta
             JOIN   respuestas  r ON r.id_respuesta = dr.id_respuesta
             LEFT JOIN users    u ON u.id = r.id_usuario
             WHERE  p.id_encuesta = ?
               AND  p.tipo = 'abierta'
             ORDER BY p.orden, r.fecha"
        );
        $stmt->execute([$idEncuesta]);
        return $stmt->fetchAll();
    }

    /** Participantes de una encuesta */
    public static function getParticipantes(int $idEncuesta): array {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "SELECT u.name, u.correo AS email, u.area, r.fecha
             FROM respuestas r
             JOIN users u ON u.id = r.id_usuario
             WHERE r.id_encuesta = ?
             ORDER BY r.fecha DESC"
        );
        $stmt->execute([$idEncuesta]);
        return $stmt->fetchAll();
    }

    /** Detalle completo para exportar a CSV */
    public static function getExportData(int $idEncuesta): array {
        $pdo  = ConexionEncuesta::conectar();
        $stmt = $pdo->prepare(
            "SELECT u.name AS usuario, u.area,
                    p.texto AS pregunta, p.tipo,
                    IFNULL(o.texto, dr.texto_respuesta) AS respuesta,
                    r.fecha
             FROM   respuestas r
             JOIN   users   u  ON u.id = r.id_usuario
             JOIN   detalle_respuestas dr ON dr.id_respuesta = r.id_respuesta
             JOIN   preguntas p ON p.id_pregunta = dr.id_pregunta
             LEFT JOIN opciones o ON o.id_opcion = dr.id_opcion
             WHERE  r.id_encuesta = ?
             ORDER BY r.fecha, u.name, p.orden"
        );
        $stmt->execute([$idEncuesta]);
        return $stmt->fetchAll();
    }
}
