<?php
include_once '../Conexion/ConexionHana.php';

// Valor de la variable

$periodo1 = $_POST['periodo1'];

// Crear una instancia de la clase de conexión y conectar
$objeto = new Conexion1();
$conexion = $objeto->Conectar1();


    // Consulta SQL con el valor de la variable directamente insertado
   $consulta = "  WITH UniqueData AS (
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
            T2.\"HHFab\",
            T2.\"PesPptoTotaKg\",
            T2.\"PeSoldaduraKg\",
            T2.\"RendihtoPpto\"
        FROM \"SBO_TAMAING_PRODUC\".\"TAMA_OFDetalleXItem_PCP\" T0
        LEFT JOIN \"SBO_TAMAING_PRODUC\".\"TAMA_CONTROL_PROYECTOS\" T1 ON T1.\"Cod Proyecto\" = T0.\"CodProy\"
        LEFT JOIN \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectosPCP\" T2 ON T2.\"Cdigproducto\" = T0.\"CodigoPadre\" AND  T2.\"OT\" =  T0.\"CodProy\"
        WHERE T0.\"CodigoPadre\" = '$periodo1' 
    )

    -- Consulta principal con agrupación
    SELECT 
        YEAR(UD.\"StartDate\") AS \"Year\",
        UD.\"CodProy\" AS \"ProjectCode\",
      
        UD.\"NomCliente\" AS \"ClientName\",
        UD.\"CantidadPadPlan\" AS \"PlannedParentQuantity\",
        UD.\"Hr PP\" AS \"PlannedHours\",
        UD.\"Hr. Meta\" AS \"ActualHours\",
        UD.\"CodigoPadre\" AS \"CodPdre\",
        UD.\"NombrePadre\" AS \"NPdre\",
        UD.\"HHFab\" AS \"HHFab\",
        UD.\"PesPptoTotaKg\" AS \"PesPptoTotaKg\",
        UD.\"PeSoldaduraKg\" AS \"PeSoldaduraKg\",
        UD.\"RendihtoPpto\" AS \"RendihtoPpto\",
         -- HABILITADO
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO001' AND UD.\"Etapa\"='HABILITADO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO001\",
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO002' AND UD.\"Etapa\"='HABILITADO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO002\",
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO004' AND UD.\"Etapa\"='HABILITADO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO004H\",  
        
          -- PLANTA
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO008' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO008\", 
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO007' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO007\", 
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO003' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO003\",
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO015' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO015\",
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO005' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO005\",
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO009' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO009\",
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO004' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO004P\",
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO018' AND UD.\"Etapa\"='PLANTA - FABRICACION' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO018P\",        

         -- GRANALLA
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO011' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO011\",
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO018' AND UD.\"Etapa\"='GRANALLA, PINTURA & PASIVADO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO018G\",        

         -- DESPACHO
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO010' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO010\",
         -- DISEÑO
        COALESCE(SUM(CASE WHEN UD.\"CodItemHijo\" = 'MO018' AND UD.\"Etapa\"='DISEÑO' THEN UD.\"CantPlanTot\" ELSE 0 END), 0) AS \"MO018D\"
        
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
        UD.\"HHFab\",
        UD.\"PesPptoTotaKg\",
        UD.\"PeSoldaduraKg\" ,
        UD.\"RendihtoPpto\"
    ORDER BY 
        YEAR(UD.\"StartDate\"),
        UD.\"CodProy\"";

  // Ejecutar la consulta
    $resultado = $conexion->query($consulta);

    // Obtener los datos
    $data = $resultado->fetchAll(PDO::FETCH_ASSOC);

  
// Enviar el array final en formato JSON a AJAX
print json_encode($data, JSON_UNESCAPED_UNICODE);//envio el array final el formato json a AJAX
  
    // Configurar el encabezado de la respuesta HTTP para UTF-8


    // Enviar el array final en formato JSON a AJAX
  
$conexion = null;
?>