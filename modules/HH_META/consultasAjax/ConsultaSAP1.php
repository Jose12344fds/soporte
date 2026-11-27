<?php
include_once '../Conexion/ConexionHana.php';

$periodo2 = $_POST['ItemCod'];
$proy= $_POST['proy'];

// Crear una instancia de la clase de conexión y conectar
$objeto = new Conexion1();
$conexion = $objeto->Conectar1();

// Consulta SQL con el valor de la variable directamente insertado
$consulta = "
        SELECT  T2.\"ChildNum\",T2.\"StageId\", 
       T3.\"ResName\", 
       T2.\"Code\", 
       ROUND(T2.\"Quantity\" * T0.\"PlannedQty\", 2) as \"Quantity\"
FROM  (
    SELECT TOP 1 *
    FROM \"SBO_TAMAING_PRODUC\".\"OWOR\"
    WHERE \"Project\" = '$proy'  ) T0
join  \"SBO_TAMAING_PRODUC\".\"OITT\" T1 ON T0.\"ItemCode\"=T1.\"Code\"
JOIN  \"SBO_TAMAING_PRODUC\".\"ITT1\" T2 ON T1.\"Code\" = T2.\"Father\"
JOIN  \"SBO_TAMAING_PRODUC\".\"ORSC\" T3 ON T2.\"Code\" = T3.\"ResCode\"
WHERE T1.\"Code\" = '$periodo2' AND T2.\"Code\" IN ('MO001', 'MO002', 'MO003', 'MO004', 'MO005', 'MO006', 'MO007', 'MO008', 'MO009', 'MO010', 'MO011', 'MO012', 'MO014', 'MO015','MO020')
ORDER BY T2.\"StageId\" ASC;"
        
       ;

// Ejecutar la consulta
$resultado = $conexion->query($consulta);

    // Obtener los datos
    $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json; charset=utf-8');
    
    $data = array_map(function($item) {
    return array_map('utf8_encode', $item);
}, $data);


// Enviar el array final en formato JSON a AJAX
print json_encode($data, JSON_UNESCAPED_UNICODE);//envio el array final el formato json a AJAX
  
    // Configurar el encabezado de la respuesta HTTP para UTF-8


    // Enviar el array final en formato JSON a AJAX
  
$conexion = null;



//
//    $periodo2 = $_POST['ItemCod'];
//    $proy= $_POST['proy'];
//
//    // Crear una instancia de la clase de conexión y conectar
//    $objeto = new Conexion1();
//    $conexion = $objeto->Conectar1();
//
//    // Consulta SQL con el valor de la variable directamente insertado
//    $consulta = "SELECT  T2.\"ChildNum\", 
//           T3.\"ResName\", 
//           T2.\"Code\", 
//           ROUND(T2.\"Quantity\" * T0.\"PlannedQty\", 2) as \"Quantity\",
//            case 
//            when T3.\"ResName\"='Habilitado Planchas' and T2.\"Code\"='MO001'  then 1
//            when T3.\"ResName\"='Habilitado Perfiles' and T2.\"Code\"='MO002'  then 1
//            when T3.\"ResName\"='Limpieza Mecánica' and T2.\"Code\"='MO004'   and T2.\"U_MSS_COME\"='LIMPIEZA MECANICA (HABILITADO)' then 1
//            when T3.\"ResName\"='Rolado' and T2.\"Code\"='MO014'              and T2.\"U_MSS_COME\"='ROLADO DE PLANCHAS Y/O PERFILES' then 2
//            when T3.\"ResName\"='Torno' and T2.\"Code\"='MO008'               and T2.\"U_MSS_COME\"='MECANIZADO DE PIEZAS' then 2
//            when T3.\"ResName\"='CNC' and T2.\"Code\"='MO007'                 and T2.\"U_MSS_COME\"='MECANIZADO DE PIEZAS' then 2
//            when T3.\"ResName\"='Taladrado' and T2.\"Code\"='MO009'           and T2.\"U_MSS_COME\"='TALADRADO DE AGUJEROS'  then 2
//            when T3.\"ResName\"='Armado' and T2.\"Code\"='MO003'              and T2.\"U_MSS_COME\"='CALDERERIA O ARMADO DE PIEZAS'  then 2
//            when T3.\"ResName\"='Soldeo' and T2.\"Code\"='MO015'              and T2.\"U_MSS_COME\"='SOLDADURA DE PIEZAS'  then 2
//            when T3.\"ResName\"='Plantillado / Enderezado' and T2.\"Code\"='MO005'   and T2.\"U_MSS_COME\"='CONTROL DIMENSIONAL' then 2
//            when T3.\"ResName\"='Limpieza Mecánica' and T2.\"Code\"='MO004'   and T2.\"U_MSS_COME\"='LIMPIEZA MECANICA'  then 2
//            when T3.\"ResName\"='Presentación / Ensamble' and T2.\"Code\"='MO006'    and T2.\"U_MSS_COME\"='ENSAMBLE Y/O PRESENTACIÓN'  then 2
//            when T3.\"ResName\"='Pintura' and T2.\"Code\"='MO011'               then 3
//            when T3.\"ResName\"='Decapado o Pasivado' and T2.\"Code\"='MO020'   then 3
//            when T3.\"ResName\"='Granallado' and T2.\"Code\"='MO012'            then 3
//            when T3.\"ResName\"='Embalaje' and T2.\"Code\"='MO010'           then 4
//             ELSE '10' END AS \"StageId\",
//                  case 
//            when T3.\"ResName\"='Habilitado Planchas' and T2.\"Code\"='MO001'  then 1
//            when T3.\"ResName\"='Habilitado Perfiles' and T2.\"Code\"='MO002'  then 2
//            when T3.\"ResName\"='Limpieza Mecánica' and T2.\"Code\"='MO004'   and T2.\"U_MSS_COME\"='LIMPIEZA MECANICA (HABILITADO)' then 3
//            when T3.\"ResName\"='Rolado' and T2.\"Code\"='MO014'              and T2.\"U_MSS_COME\"='ROLADO DE PLANCHAS Y/O PERFILES' then 4
//            when T3.\"ResName\"='Torno' and T2.\"Code\"='MO008'               and T2.\"U_MSS_COME\"='MECANIZADO DE PIEZAS' then 5
//            when T3.\"ResName\"='CNC' and T2.\"Code\"='MO007'                 and T2.\"U_MSS_COME\"='MECANIZADO DE PIEZAS' then 6
//            when T3.\"ResName\"='Taladrado' and T2.\"Code\"='MO009'           and T2.\"U_MSS_COME\"='TALADRADO DE AGUJEROS'  then 7
//            when T3.\"ResName\"='Armado' and T2.\"Code\"='MO003'              and T2.\"U_MSS_COME\"='CALDERERIA O ARMADO DE PIEZAS'  then 8
//            when T3.\"ResName\"='Soldeo' and T2.\"Code\"='MO015'              and T2.\"U_MSS_COME\"='SOLDADURA DE PIEZAS'  then 9
//            when T3.\"ResName\"='Plantillado / Enderezado' and T2.\"Code\"='MO005'   and T2.\"U_MSS_COME\"='CONTROL DIMENSIONAL' then 10
//            when T3.\"ResName\"='Limpieza Mecánica' and T2.\"Code\"='MO004'   and T2.\"U_MSS_COME\"='LIMPIEZA MECANICA'  then 11
//            when T3.\"ResName\"='Presentación / Ensamble' and T2.\"Code\"='MO006'    and T2.\"U_MSS_COME\"='ENSAMBLE Y/O PRESENTACIÓN'  then 12
//            when T3.\"ResName\"='Granallado' and T2.\"Code\"='MO012'            then 13
//            when T3.\"ResName\"='Pintura' and T2.\"Code\"='MO011'               then 14
//            when T3.\"ResName\"='Decapado o Pasivado' and T2.\"Code\"='MO020'   then 15
//            when T3.\"ResName\"='Embalaje' and T2.\"Code\"='MO010'           then 16
//             ELSE '10' END AS \"orderere\"
//    FROM \"SBO_TAMAING_PRODUC\".\"OWOR\" T0
//    join  \"SBO_TAMAING_PRODUC\".\"OITT\" T1 ON T0.\"ItemCode\"=T1.\"Code\"
//    JOIN  \"SBO_TAMAING_PRODUC\".\"ITT1\" T2 ON T1.\"Code\" = T2.\"Father\"
//    JOIN  \"SBO_TAMAING_PRODUC\".\"ORSC\" T3 ON T2.\"Code\" = T3.\"ResCode\"
//    WHERE T0.\"Project\" = '$proy' AND T1.\"Code\" = '$periodo2' AND T2.\"Code\" IN ('MO001', 'MO002', 'MO003', 'MO004', 'MO005', 'MO006', 'MO007', 'MO008', 'MO009', 'MO010', 'MO011', 'MO012', 'MO014', 'MO015','MO020')
//    ORDER BY \"orderere\" ASC;";
//
//    // Ejecutar la consulta
//    $resultado = $conexion->query($consulta);
//
//        // Obtener los datos
//        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
//        header('Content-Type: application/json; charset=utf-8');
//
//        $data = array_map(function($item) {
//        return array_map('utf8_encode', $item);
//    }, $data);
//
//
//    // Enviar el array final en formato JSON a AJAX
//    print json_encode($data, JSON_UNESCAPED_UNICODE);//envio el array final el formato json a AJAX
//
//    $conexion = null;
//    
 //   SELECT  T2.\"ChildNum\",T2.\"StageId\",T3.\"ResName\", T3.\"ResName\",   ROUND(T2.\"Quantity\" * T0.\"PlannedQty\", 2) as \"Quantity\"
//  FROM \"SBO_TAMAING_PRODUC\".\"OWOR\" T0
//   join  \"SBO_TAMAING_PRODUC\".\"OITT\" T1 ON T0.\"ItemCode\"=T1.\"Code\"
//   JOIN  \"SBO_TAMAING_PRODUC\".\"ITT1\" T2 ON T1.\"Code\" = T2.\"Father\"
//  JOIN  \"SBO_TAMAING_PRODUC\".\"ORSC\" T3 ON T2.\"Code\" = T3.\"ResCode\"
//  WHERE T0.\"Project\" = '$proy' AND T1.\"Code\" = '$periodo2' AND T2.\"Code\" IN ('MO001', 'MO002', 'MO003', 'MO004', 'MO005', 'MO006', 'MO007', 'MO008', 'MO009', 'MO010', 'MO011', 'MO012', 'MO014', 'MO015','MO020')
//  ORDER BY T2.\"StageId\" ASC;"
?>




