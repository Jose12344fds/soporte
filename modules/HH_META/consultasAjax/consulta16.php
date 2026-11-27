<?php
include_once '../consultasAjax/conexion.php';
include_once '../Conexion/ConexionHana.php';

try {
    // Verifica si idPadre está definido
    if (isset($_POST['idPadre'])) {
        $idPadre = $_POST['idPadre'];
          $codPdre = $_POST['codPdre'];

        // Recoger optional IdHijOT para filtrar por item hijo específico
        $idHijOT = isset($_POST['idHijOT']) && $_POST['idHijOT'] !== '' ? $_POST['idHijOT'] : null;
        $filter = $idHijOT !== null ? ' AND T1.IdHijOT = :idHijOT ' : '';

        $objeto = new Conexion();
        $conexion = $objeto->Conectar();

        $consulta = "
SELECT '1' AS ordenes,'1' AS orden, 'HABILITADO' AS grupo, 'MO001' AS proceso,'Habilitado Planchas' AS Campo ,  (COALESCE(T1.MO001_1, 0) + COALESCE(T1.MO001_2, 0)) AS Valor, ((COALESCE(T1.MO001_1, 0) + COALESCE(T1.MO001_2, 0)))/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND (T1.MO001_1 IS NOT NULL OR T1.MO001_2 IS NOT NULL)

UNION 

SELECT '2' AS ordenes,'1' AS orden, 'HABILITADO' AS grupo, 'MO002' AS proceso,'Habilitado Perfiles' AS Campo , T1.MO002 AS Valor, T1.MO002/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO002 IS NOT NULL

UNION 

SELECT '3' AS ordenes,'1' AS orden, 'HABILITADO' AS grupo, 'MO004' AS proceso,'Limpieza Mecánica' AS Campo , T1.MO004 AS Valor, T1.MO004/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO004 IS NOT NULL

UNION 

SELECT '4' AS ordenes,'2' AS orden, 'PLANTA' AS grupo, 'MO014' AS proceso,'Rolado' AS Campo , T1.MO014 AS Valor, T1.MO014/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO014 IS NOT NULL

UNION 

SELECT '5' AS ordenes,'2' AS orden, 'PLANTA' AS grupo, 'MO008' AS proceso,'Torno' AS Campo ,(COALESCE(T1.MO008_1, 0) + COALESCE(T1.MO008_2, 0)) AS Valor, (COALESCE(T1.MO008_1, 0) + COALESCE(T1.MO008_2, 0))/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre  AND (T1.MO008_1 IS NOT NULL OR T1.MO008_2 IS NOT NULL)        

UNION 

SELECT '6' AS ordenes,'2' AS orden, 'PLANTA' AS grupo, 'MO007' AS proceso,'CNC' AS Campo ,(COALESCE(T1.MO007_1, 0) + COALESCE(T1.MO007_2, 0)) AS Valor, (COALESCE(T1.MO007_1, 0) + COALESCE(T1.MO007_2, 0))/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND (T1.MO007_1 IS NOT NULL OR T1.MO007_2 IS NOT NULL) 

UNION 

SELECT '7' AS ordenes,'2' AS orden, 'PLANTA' AS grupo, 'MO003'AS proceso,'Armado' AS Campo , T1.MO003 AS Valor, T1.MO003/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO003 IS NOT NULL

UNION 

SELECT '8' AS ordenes,'2' AS orden, 'PLANTA' AS grupo, 'MO015' AS proceso,'Soldeo' AS Campo , T1.MO015 AS Valor, T1.MO015/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO015 IS NOT NULL


UNION 

SELECT '9' AS ordenes,'2' AS orden, 'PLANTA' AS grupo, 'MO005' AS proceso,'Plantillado / Enderezado' AS Campo , T1.MO005 AS Valor, T1.MO005/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO005 IS NOT NULL

UNION 

SELECT '10' AS ordenes,'2' AS orden, 'PLANTA' AS grupo, 'MO009' AS proceso,'Taladrado' AS Campo , T1.MO009 AS Valor, T1.MO009/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO009 IS NOT NULL

UNION 

SELECT '11' AS ordenes,'2' AS orden, 'PLANTA' AS grupo, 'MO004' AS proceso,'Limpieza Mecánica' AS Campo , T1.MO004P AS Valor, T1.MO004P/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO004P IS NOT NULL

UNION 

SELECT '12' AS ordenes,'2' AS orden, 'PLANTA' AS grupo, 'MO006' AS proceso,'Presentación / Ensamble' AS Campo , T1.MO006 AS Valor, T1.MO006/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO006 IS NOT NULL

UNION 

SELECT '13' AS ordenes,'3' AS orden, 'Granallado' AS grupo, 'MO012' AS proceso,'Granallado' AS Campo , T1.MO012  AS Valor , T1.MO012/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO012 IS NOT NULL

UNION 

SELECT '14' AS ordenes,'3' AS orden, 'Granallado' AS grupo, 'MO011' AS proceso,'Pintura' AS Campo , T1.MO011  AS Valor, T1.MO011/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO011 IS NOT NULL

UNION 

SELECT '15' AS ordenes,'3' AS orden, 'Despacho' AS grupo, 'MO020' AS proceso,'Decapado o Pasivado' AS Campo , T1.MO020  AS Valor, T1.MO020/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO020 IS NOT NULL

UNION 

SELECT '16' AS ordenes,'4' AS orden, 'Diseño' AS grupo, 'MO010' AS proceso,'Embalaje' AS Campo , T1.MO010  AS Valor, T1.MO010/T1.CANTI AS Valor1
FROM otpadre T0
LEFT JOIN othija T1 ON T1.IdPadreOT = T0.IdPadre
WHERE T0.IdPadre =:idPadre and  T1.CodPdre =:codPdre AND MO010 IS NOT NULL


"; // Cambia la consulta según tus necesidades
        // Aplicar filtro opcional por IdHijOT en todas las cláusulas WHERE si corresponde
        $consulta = str_replace('T1.CodPdre =:codPdre', 'T1.CodPdre =:codPdre ' . $filter, $consulta);
        $stmt = $conexion->prepare($consulta);
        $stmt->bindParam(':idPadre', $idPadre);
        $stmt->bindParam(':codPdre', $codPdre);
        if ($idHijOT !== null) {
            $stmt->bindParam(':idHijOT', $idHijOT);
        }
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Devuelve los datos o un array vacío si no hay resultados
        echo json_encode($data ?: [], JSON_UNESCAPED_UNICODE);
    } else {
        // Si no se recibe idPadre, devuelve un array vacío
        echo json_encode([], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    $conexion = null; // Cierra la conexión
}
?>