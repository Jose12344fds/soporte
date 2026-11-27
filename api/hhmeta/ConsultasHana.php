<?php
require_once __DIR__ . '/ConexionHana.php';

class Consultas {
    private $conexion;

    public function __construct() {
        $this->conexion = getHanaConnection();
    }

    public function obtenerProyectos4() {
        $sql = 'SELECT T0."OT", T0."Proyecto1", 
                SUM(T0."PesPptoTotaKg") AS "TotalPesPptoTotaKg",
                SUM(T0."HHFab") AS "HHFab",
                SUM(T0."PeNetTotKg") AS "PeNetTotKg"
                FROM "SBO_TAMAING_PRODUC"."TAMA_VISTAPROYECTOS_PCP1" T0  
                GROUP BY T0."OT", T0."Proyecto1"
                ORDER BY T0."OT", T0."Proyecto1"';
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return is_array($data) ? $data : [];
    }

    public function obtenerItemsPorOT($ot) {
        // Escapar el valor para evitar inyección SQL
        $otSafe = str_replace("'", "''", $ot);
        
        $sql = "SELECT T0.\"OT\", T0.\"Cliente\", T0.\"Proyecto11\", T0.\"Cdigproducto\", 
                T0.\"Descripci\", T0.\"PlanodelclienteTAMA\", T0.\"Tipoproyecto\",
                T0.\"NumOF\", T0.\"ItemOT\", T0.\"Cantd\", T0.\"HHFab\", T0.\"PesPptoTotaKg\",
                T0.\"PeSoldaduraKg\", T0.\"PesPptoUniKg\", T0.\"NroSemIngresOT\",
                T0.\"FechaIngreOC\", T0.\"FecReqCli\", T0.\"NumerOCenviacliente\",
                T0.\"RendihtoPpto\", T0.\"Fecha_Terminada\", T0.\"PeNetTotKg\", T0.\"Proyecto1\"
                FROM \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectosPCP\" T0
                WHERE T0.\"OT\" = '$otSafe'
                ORDER BY T0.\"ItemOT\"";
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return is_array($data) ? $data : [];
    }

    /**
     * Obtener detalle de procesos por Código de Producto (replica consulta3.php)
     */
    public function obtenerDetalleProducto($codProducto) {
        $safe = str_replace("'", "''", $codProducto);

        $sql = "WITH UniqueData AS (
            SELECT DISTINCT
                T0.\"StartDate\",
                T0.\"CodProy\",
                T0.\"NumSapOF\",
                T0.\"NomCliente\",
                T0.\"CantidadPadPlan\",
                T1.\"Hr PP\",
                T1.\"Hr. Meta\",
                T0.\"CodItemHijo\",
                T0.\"CantPlanTot\",
                T0.\"Etapa\",
                T0.\"CodigoPadre\",
                T0.\"NombrePadre\",
                T2.\"PesPptoUniKg\",
                T2.\"HHFab\",
                T2.\"PesPptoTotaKg\",
                T2.\"PeSoldaduraKg\",
                T2.\"RendihtoPpto\"
            FROM \"SBO_TAMAING_PRODUC\".\"TAMA_OFDetalleXItem_PCP\" T0
            LEFT JOIN \"SBO_TAMAING_PRODUC\".\"TAMA_CONTROL_PROYECTOS\" T1 ON T1.\"Cod Proyecto\" = T0.\"CodProy\"
            LEFT JOIN \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectosPCP\" T2 ON T2.\"Cdigproducto\" = T0.\"CodigoPadre\" AND T2.\"OT\" = T0.\"CodProy\"
            WHERE T0.\"CodigoPadre\" = '$safe'
        )
        SELECT
            YEAR(UD.\"StartDate\") AS \"Year\",
            UD.\"CodProy\" AS \"ProjectCode\",
            UD.\"NomCliente\" AS \"ClientName\",
            UD.\"CantidadPadPlan\" AS \"PlannedParentQuantity\",
            UD.\"Hr PP\" AS \"PlannedHours\",
            UD.\"Hr. Meta\" AS \"ActualHours\",
            UD.\"CodigoPadre\" AS \"CodPdre\",
            UD.\"NombrePadre\" AS \"NPdre\",
            UD.\"PesPptoUniKg\" AS \"PesPptoUniKg\",
            UD.\"HHFab\" AS \"HHFab\",
            UD.\"PesPptoTotaKg\" AS \"PesPptoTotaKg\",
            UD.\"PeSoldaduraKg\" AS \"PeSoldaduraKg\",
            UD.\"RendihtoPpto\" AS \"RendihtoPpto\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO001' AND UD.\"Etapa\"='HABILITADO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO001\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO002' AND UD.\"Etapa\"='HABILITADO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO002\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO004' AND UD.\"Etapa\"='HABILITADO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO004H\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO014' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO014\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO008' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO008\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO007' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO007\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO003' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO003\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO015' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO015\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO005' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO005\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO009' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO009\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO004' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO004P\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO006' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO006\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO012' AND UD.\"Etapa\"='GRANALLA, PINTURA & PASIVADO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO012\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO011' AND UD.\"Etapa\"='GRANALLA, PINTURA & PASIVADO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO011\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO020' AND UD.\"Etapa\"='GRANALLA, PINTURA & PASIVADO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO020\",
            COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO010' AND UD.\"Etapa\"='DESPACHO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO010\"
        FROM UniqueData UD
        GROUP BY
            YEAR(UD.\"StartDate\"),
            UD.\"CodProy\",
            UD.\"NomCliente\",
            UD.\"CantidadPadPlan\",
            UD.\"Hr PP\",
            UD.\"Hr. Meta\",
            UD.\"CodigoPadre\",
            UD.\"NombrePadre\",
            UD.\"PesPptoUniKg\",
            UD.\"HHFab\",
            UD.\"PesPptoTotaKg\",
            UD.\"PeSoldaduraKg\",
            UD.\"RendihtoPpto\"
        ORDER BY
            YEAR(UD.\"StartDate\"),
            UD.\"CodProy\"";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($data) ? $data : [];
    }

    /**
     * Obtener procesos SAP por ItemCode.
     * Prioriza la estructura BOM del producto y devuelve solo los procesos MO.
     */
    public function obtenerRecursosSAP($itemCode) {
        $itemSafe = str_replace("'", "''", $itemCode);

        $sql = "SELECT
                T2.\"ChildNum\",
                T2.\"StageId\",
                T3.\"ResName\",
                T2.\"Code\",
                ROUND(T2.\"Quantity\", 2) AS \"Quantity\"
            FROM \"SBO_TAMAING_PRODUC\".\"OITT\" T1
            JOIN \"SBO_TAMAING_PRODUC\".\"ITT1\" T2
                ON T1.\"Code\" = T2.\"Father\"
            JOIN \"SBO_TAMAING_PRODUC\".\"ORSC\" T3
                ON T2.\"Code\" = T3.\"ResCode\"
            WHERE T1.\"Code\" = '$itemSafe'
              AND T2.\"Code\" IN ('MO001','MO002','MO003','MO004','MO005','MO006','MO007','MO008','MO009','MO010','MO011','MO012','MO014','MO015','MO020')
            ORDER BY T2.\"StageId\" ASC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($data) ? $data : [];
    }
}
?>
