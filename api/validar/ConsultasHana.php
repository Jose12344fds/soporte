<?php
/**
 * ConsultasHana.php — Consultas SAP HANA para módulo Validar
 * Recursos de ProductTrees: OWOR → OITT → ITT1 → ORSC
 */

require_once __DIR__ . '/ConexionHana.php';

class ConsultasHana {

    /**
     * Obtiene los recursos SAP (MO codes) de un item dentro de un proyecto.
     *
     * @param string $itemCode  Código del producto (CodPdre)
     * @param string $proyecto  Código del proyecto (ProjectCode / OTPADRE)
     * @return array  [{ ChildNum, StageId, ResName, Code, Quantity }, ...]
     */
    public static function getRecursosSAP(string $itemCode, string $proyecto): array {
        $db = ConexionHana::conectar();

        // Sanitizar valores (mismo patrón que ConsultaSAP1.php original)
        $safeProy = str_replace("'", "''", $proyecto);
        $safeItem = str_replace("'", "''", $itemCode);

        $sql = "
            SELECT T2.\"ChildNum\", T2.\"StageId\",
                   T3.\"ResName\",
                   T2.\"Code\",
                   ROUND(T2.\"Quantity\" * T0.\"PlannedQty\", 2) AS \"Quantity\"
            FROM (
                SELECT TOP 1 *
                FROM \"SBO_TAMAING_PRODUC\".\"OWOR\"
                WHERE \"Project\" = '{$safeProy}'
                  AND \"ItemCode\" = '{$safeItem}'
                ORDER BY \"DocEntry\" DESC
            ) T0
            JOIN \"SBO_TAMAING_PRODUC\".\"OITT\" T1 ON T0.\"ItemCode\" = T1.\"Code\"
            JOIN \"SBO_TAMAING_PRODUC\".\"ITT1\" T2 ON T1.\"Code\" = T2.\"Father\"
            JOIN \"SBO_TAMAING_PRODUC\".\"ORSC\" T3 ON T2.\"Code\" = T3.\"ResCode\"
            WHERE T2.\"Code\" IN ('MO001','MO002','MO003','MO004','MO005',
                  'MO006','MO007','MO008','MO009','MO010',
                  'MO011','MO012','MO014','MO015','MO020')
            ORDER BY T2.\"StageId\" ASC";

        $resultado = $db->query($sql);
        $data = $resultado->fetchAll();

        // Asegurar UTF-8 (mismo patrón que ConsultaSAP1.php original)
        return array_map(function ($row) {
            return array_map(function ($v) {
                return is_string($v) ? utf8_encode($v) : $v;
            }, $row);
        }, $data);
    }
}
