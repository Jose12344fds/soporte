<?php
/**
 * ConsultasValidar.php — Clase central de consultas del módulo Validar
 *
 * Métodos:
 *  - getOTsActivas()               → OTs con Estado_OT = 1
 *  - getItemsOT($idPadre)          → Items hijos de una OT padre
 *  - getItemsValidar($idPadre, $tipo) → Items con SUT según tipo de proceso
 *  - getProcesosItem($idPadre, $codPdre, $idHijOT) → 16 UNION desglose procesos
 *  - updateCelda($idHijOT, $campo, $valor)          → Edición inline othija
 *  - cambiarEstado($idPadre)        → Estado_OT = 2 (Validado)
 */

require_once __DIR__ . '/ConexionLocal.php';

class ConsultasValidar {

    /* ═══════════════════════════════════════════════════════
       1. OTs activas (Estado_OT = 1)
       ═══════════════════════════════════════════════════════ */
    public static function getOTsActivas(): array {
        $db   = ConexionLocal::conectar();
        $stmt = $db->query('SELECT * FROM otpadre WHERE Estado_OT = 1');
        return $stmt->fetchAll();
    }

    /* ═══════════════════════════════════════════════════════
       2. Items de una OT (para modal Exportar)
       ═══════════════════════════════════════════════════════ */
    public static function getItemsOT(int $idPadre): array {
        $db   = ConexionLocal::conectar();
        $sql  = "SELECT * FROM otpadre T0
                 LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
                 WHERE T0.IdPadre = :idPadre";
        $stmt = $db->prepare($sql);
        $stmt->execute([':idPadre' => $idPadre]);
        return $stmt->fetchAll();
    }

    /* ═══════════════════════════════════════════════════════
       3. Items con SUT según tipo de consulta (subconsulta principal)
          tipo 1 = Todos los procesos
          tipo 2 = Habilitado
          tipo 3 = Mecanizado
          tipo 4 = Calderería
          tipo 5 = Soldadura
          tipo 6 = Acabados
       ═══════════════════════════════════════════════════════ */
    public static function getItemsValidar(int $idPadre, int $tipo): array {
        $db = ConexionLocal::conectar();

        $base = "SELECT T0.*, T0.ProjectCode AS proyecto, T1.*,
                 IFNULL(T1.MO050,0) AS MO050, IFNULL(T1.MO060,0) AS MO060,
                 IFNULL(T1.MO070,0) AS MO070, IFNULL(T1.MO080,0) AS MO080,";

        $from = " FROM otpadre T0
                  LEFT JOIN othija T1 ON T0.IdPadre = T1.IdPadreOT
                  WHERE T0.IdPadre = :idPadre";

        switch ($tipo) {
            case 1: // Todos
                $sut = "(T1.MO001_1 + T1.MO001_2 + T1.MO002 + T1.MO004 + T1.MO014 +
                         T1.MO008_1 + T1.MO008_2 + T1.MO007_1 + T1.MO007_2 + T1.MO003 +
                         T1.MO015 + T1.MO005 + T1.MO009 + T1.MO004P + T1.MO006 +
                         T1.MO012 + T1.MO011 + T1.MO020 + T1.MO010 +
                         IFNULL(T1.MO050,0) + IFNULL(T1.MO060,0) + IFNULL(T1.MO070,0) + IFNULL(T1.MO080,0))";
                break;
            case 2: // Habilitado
                $sut = "(T1.MO001_1 + T1.MO001_2 + T1.MO002 + T1.MO004 + T1.MO014)";
                break;
            case 3: // Mecanizado
                $sut = "(T1.MO008_1 + T1.MO008_2 + T1.MO007_1 + T1.MO007_2)";
                break;
            case 4: // Calderería
                $sut = "(T1.MO003 + T1.MO005 + T1.MO009 + T1.MO004P + T1.MO006)";
                break;
            case 5: // Soldadura
                $sut = "(T1.MO015)";
                break;
            case 6: // Acabados
                $sut = "(T1.MO012 + T1.MO011 + T1.MO020 + T1.MO010)";
                break;
            default:
                return [];
        }

        $sql  = $base . $sut . ' AS SUT' . $from;
        $stmt = $db->prepare($sql);
        $stmt->execute([':idPadre' => $idPadre]);
        return $stmt->fetchAll();
    }

    /* ═══════════════════════════════════════════════════════
       4. Desglose de procesos por item (16 UNION SELECTs)
          Devuelve: ordenes, orden, grupo, proceso, Campo, Valor, Valor1
       ═══════════════════════════════════════════════════════ */
    public static function getProcesosItem(int $idPadre, string $codPdre, ?int $idHijOT = null): array {
        $db = ConexionLocal::conectar();

        $filtroHijo = $idHijOT !== null ? ' AND T1.IdHijOT = :idHijOT ' : '';

        $sql = "
SELECT '1' AS ordenes,'1' AS orden, 'HABILITADO' AS grupo, 'MO001' AS proceso,'Habilitado Planchas' AS Campo,
  (COALESCE(T1.MO001_1,0)+COALESCE(T1.MO001_2,0)) AS Valor,
  ((COALESCE(T1.MO001_1,0)+COALESCE(T1.MO001_2,0)))/T1.CANTI AS Valor1
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND (T1.MO001_1 IS NOT NULL OR T1.MO001_2 IS NOT NULL)

UNION
SELECT '2','1','HABILITADO','MO002','Habilitado Perfiles',
  T1.MO002, T1.MO002/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO002 IS NOT NULL

UNION
SELECT '3','1','HABILITADO','MO004','Limpieza Mecánica',
  T1.MO004, T1.MO004/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO004 IS NOT NULL

UNION
SELECT '4','2','PLANTA','MO014','Rolado',
  T1.MO014, T1.MO014/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO014 IS NOT NULL

UNION
SELECT '5','2','PLANTA','MO008','Torno',
  (COALESCE(T1.MO008_1,0)+COALESCE(T1.MO008_2,0)),
  (COALESCE(T1.MO008_1,0)+COALESCE(T1.MO008_2,0))/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND (T1.MO008_1 IS NOT NULL OR T1.MO008_2 IS NOT NULL)

UNION
SELECT '6','2','PLANTA','MO007','CNC',
  (COALESCE(T1.MO007_1,0)+COALESCE(T1.MO007_2,0)),
  (COALESCE(T1.MO007_1,0)+COALESCE(T1.MO007_2,0))/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND (T1.MO007_1 IS NOT NULL OR T1.MO007_2 IS NOT NULL)

UNION
SELECT '7','2','PLANTA','MO003','Armado',
  T1.MO003, T1.MO003/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO003 IS NOT NULL

UNION
SELECT '8','2','PLANTA','MO015','Soldeo',
  T1.MO015, T1.MO015/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO015 IS NOT NULL

UNION
SELECT '9','2','PLANTA','MO005','Plantillado / Enderezado',
  T1.MO005, T1.MO005/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO005 IS NOT NULL

UNION
SELECT '10','2','PLANTA','MO009','Taladrado',
  T1.MO009, T1.MO009/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO009 IS NOT NULL

UNION
SELECT '11','2','PLANTA','MO004','Limpieza Mecánica',
  T1.MO004P, T1.MO004P/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO004P IS NOT NULL

UNION
SELECT '12','2','PLANTA','MO006','Presentación / Ensamble',
  T1.MO006, T1.MO006/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO006 IS NOT NULL

UNION
SELECT '13','3','Granallado','MO012','Granallado',
  T1.MO012, T1.MO012/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO012 IS NOT NULL

UNION
SELECT '14','3','Granallado','MO011','Pintura',
  T1.MO011, T1.MO011/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO011 IS NOT NULL

UNION
SELECT '15','3','Despacho','MO020','Decapado o Pasivado',
  T1.MO020, T1.MO020/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO020 IS NOT NULL

UNION
SELECT '16','4','Diseño','MO010','Embalaje',
  T1.MO010, T1.MO010/T1.CANTI
FROM otpadre T0 LEFT JOIN othija T1 ON T1.IdPadreOT=T0.IdPadre
WHERE T0.IdPadre=:idPadre AND T1.CodPdre=:codPdre {$filtroHijo} AND MO010 IS NOT NULL
";

        $stmt = $db->prepare($sql);
        $params = [':idPadre' => $idPadre, ':codPdre' => $codPdre];
        if ($idHijOT !== null) {
            $params[':idHijOT'] = $idHijOT;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /* ═══════════════════════════════════════════════════════
       5. Edición inline de celda (UPDATE othija)
       ═══════════════════════════════════════════════════════ */
    private static $camposPermitidos = [
        'MO001_1','MO001_2','MO002','MO004','MO014',
        'MO008_1','MO008_2','MO007_1','MO007_2','MO003',
        'MO015','MO005','MO009','MO004P','MO006',
        'MO012','MO011','MO020','MO010',
        'MO050','MO060','MO070','MO080'
    ];

    public static function updateCelda(int $idHijOT, string $campo, $valor): array {
        // Validación contra inyección SQL (whitelist de campos)
        if (!in_array($campo, self::$camposPermitidos, true)) {
            return ['success' => false, 'message' => 'Campo no permitido: ' . $campo];
        }

        $db   = ConexionLocal::conectar();
        $sql  = "UPDATE othija SET {$campo} = :valor WHERE IdHijOT = :idHijOT";
        $stmt = $db->prepare($sql);
        $stmt->execute([':valor' => $valor, ':idHijOT' => $idHijOT]);

        return $stmt->rowCount() >= 0
            ? ['success' => true,  'message' => 'Datos actualizados correctamente.']
            : ['success' => false, 'message' => 'No se actualizó ningún registro.'];
    }

    /* ═══════════════════════════════════════════════════════
       6. Cambiar estado OT → 2 (Validado)
       ═══════════════════════════════════════════════════════ */
    public static function cambiarEstado(int $idPadre): array {
        $db   = ConexionLocal::conectar();
        $sql  = "UPDATE otpadre SET Estado_OT = 2 WHERE IdPadre = :idPadre";
        $stmt = $db->prepare($sql);
        $stmt->execute([':idPadre' => $idPadre]);

        return $stmt->rowCount() > 0
            ? ['success' => true,  'message' => 'Se ha validado correctamente, ahora a Exportar a SAP.']
            : ['success' => false, 'message' => 'No se encontró la OT o ya estaba validada.'];
    }
}
