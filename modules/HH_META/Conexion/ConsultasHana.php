<?php
include_once 'ConexionHana.php';
class Consultas {
    
    private $conexion;

    public function __construct() {
        // Crear una instancia de la clase de conexión y conectar
        $objeto = new Conexion1();
        $this->conexion = $objeto->Conectar1();
    }

    // Función para obtener proyectos
    public function obtenerProyectos4() {
        // Consulta SQL
        $consulta = '   SELECT T0."OT", T0."Proyecto1", SUM(T0."PesPptoTotaKg") AS "TotalPesPptoTotaKg", SUM(T0."HHFab") AS "HHFab", SUM(T0."PeNetTotKg") AS "PeNetTotKg"
                     FROM "SBO_TAMAING_PRODUC"."TAMA_VISTAPROYECTOS_PCP1" T0  
         GROUP BY T0."OT", T0."Proyecto1"
               ORDER BY T0."OT", T0."Proyecto1" ';
        // Preparar la consulta
        $resultado = $this->conexion->prepare($consulta);
        
        // Ejecutar la consulta
        $resultado->execute();
        
        // Obtener los datos
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
      
        // Verificar si hay datos
        if ($data) {
            // Convertir los datos a UTF-8
            $data = array_map(function($item) {
                return array_map('utf8_encode', $item);
            }, $data);
  
            // Devolver los resultados como un array
            return $data;
        } else {
            // Si no hay resultados, devolver un mensaje en formato de array
            return ["message" => "No se encontraron proyectos."];
        }
    }
      public function obtenerProyectosMayor() {
        // Consulta SQL
        $consulta = '
SELECT T0."OT", T0."Proyecto1",  SUM(T0."PesPptoTotaKg") AS "TotalPesPptoTotaKg", SUM(T0."HHFab") AS "HHFab",  SUM(T0."PeNetTotKg") AS "PeNetTotKg"
FROM "SBO_TAMAING_PRODUC"."TAMA_VISTAPROYECTOS_PCP1" T0  
GROUP BY T0."OT", T0."Proyecto1" HAVING SUM(T0."HHFab") > 300 ORDER BY T0."OT", T0."Proyecto1"';
       // Preparar la consulta
        $resultado = $this->conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
      
        // Verificar si hay datos
        if ($data) {
            // Convertir los datos a UTF-8
            $data = array_map(function($item) {
                return array_map('utf8_encode', $item);
            }, $data); // Devolver los resultados como un array
            return $data;
        } else {
            // Si no hay resultados, devolver un mensaje en formato de array
            return ["message" => "No se encontraron proyectos."];
        }
    }
      public function obtenerProyectosMenor() {
        // Consulta SQL
        $consulta = '
SELECT T0."OT", T0."Proyecto1",  SUM(T0."PesPptoTotaKg") AS "TotalPesPptoTotaKg", SUM(T0."HHFab") AS "HHFab",  SUM(T0."PeNetTotKg") AS "PeNetTotKg"
FROM "SBO_TAMAING_PRODUC"."TAMA_VISTAPROYECTOS_PCP1" T0  
GROUP BY T0."OT", T0."Proyecto1" HAVING SUM(T0."HHFab") < 300 ORDER BY T0."OT", T0."Proyecto1"';
       // Preparar la consulta
        $resultado = $this->conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
      
        // Verificar si hay datos
        if ($data) {
            // Convertir los datos a UTF-8
            $data = array_map(function($item) {
                return array_map('utf8_encode', $item);
            }, $data); // Devolver los resultados como un array
            return $data;
        } else {
            // Si no hay resultados, devolver un mensaje en formato de array
            return ["message" => "No se encontraron proyectos."];
        }
    }
     // Modificar la función para aceptar un parámetro dinámico
    public function obtenerProyectos($horaOption) {
        // Definir la condición de la consulta en función de la opción seleccionada
        if ($horaOption == 'mayor') {
            $condicion = 'HAVING SUM(T0."HHFab") > 300';
        } elseif ($horaOption == 'menor') {
            $condicion = 'HAVING SUM(T0."HHFab") < 300';
        } else {
            $condicion = '';  // Si la opción no es válida, no aplicar ninguna condición
        }

        // Consulta SQL con el filtro dinámico
        $consulta = '

       select * from   ( SELECT T0."OT", T0."Proyecto1",  
           SUM(T0."PesPptoTotaKg") AS "TotalPesPptoTotaKg", 
           SUM(T0."HHFab") AS "HHFab",  
           SUM(T0."PeNetTotKg") AS "PeNetTotKg",
            MAX(T0."Nivelproyecto") AS "Nivelproyecto"
            FROM "SBO_TAMAING_PRODUC"."TAMA_VISTAPROYECTOS_PCP1" T0
            GROUP BY T0."OT", T0."Proyecto1"
                 ' . $condicion . ' 
             ORDER BY T0."OT", T0."Proyecto1") T11
             
        INNER JOIN ( SELECT T0."Project"
FROM "SBO_TAMAING_PRODUC"."OWOR" T0
WHERE T0."Status"=\'P\' OR T0."Status"=\'R\' 
 GROUP BY T0."Project" 
 ORDER BY T0."Project") T12 ON T11."OT"=T12."Project"


 ORDER BY T11."OT"


        ';

        // Preparar la consulta
        $resultado = $this->conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);

        // Verificar si hay datos
        if ($data) {
            // Convertir los datos a UTF-8
            $data = array_map(function($item) {
                return array_map('utf8_encode', $item);
            }, $data); // Devolver los resultados como un array
            return $data;
        } else {
            // Si no hay resultados, devolver un mensaje en formato de array
            return ["message" => "No se encontraron proyectos."];
        }
    }
    
    // Obtener items de una OT específica
    public function obtenerItemsPorOT($ot) {
        $consulta = 'SELECT 
            T0."OT", T0."Cliente", T0."Proyecto11", T0."Cantd" AS "PlannedParentQuantity",
            T0."Cdigproducto", T0."Descripci", T0."PlanodelclienteTAMA", T0."Tipoproyecto",
            T0."NumOF", T0."ItemOT", T0."Cantd", T0."HHFab", T0."PesPptoTotaKg",
            T0."PeSoldaduraKg", T0."PesPptoUniKg", T0."NroSemIngresOT" AS "SEM",
            T0."FechaIngreOC", T0."FecReqCli", T0."NumerOCenviacliente",
            T0."RendihtoPpto", T0."Fecha_Terminada", T0."PeNetTotKg", T0."Proyecto1"
        FROM "SBO_TAMAING_PRODUC"."TAMA_VistaProyectosPCP" T0
        WHERE T0."OT" = ?
        ORDER BY T0."ItemOT"';
        
        $resultado = $this->conexion->prepare($consulta);
        $resultado->execute([$ot]);
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        
        if ($data) {
            $data = array_map(function($item) {
                return array_map('utf8_encode', $item);
            }, $data);
            return $data;
        } else {
            return [];
        }
    }
}
