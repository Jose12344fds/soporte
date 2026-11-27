<?php
include_once '../consultasAjax/conexion.php';
$periodo1 = $_POST['idPadreOT'];
$periodo2 = $_POST['codPdre'];
$periodo3 = $_POST['proceso'];

$objeto = new Conexion();
$conexion = $objeto->Conectar();

$consulta = "SELECT 
       CASE
           -- Verificamos si todos los valores de Estado son 2
           WHEN GROUP_CONCAT(DISTINCT T0.Estado) = '2' THEN '2'

           -- Verificamos si todos los valores de Estado son 1
           WHEN GROUP_CONCAT(DISTINCT T0.Estado) = '1' THEN '1'

           -- Verificamos si hay ambos valores 1 y 2 en el grupo
           WHEN GROUP_CONCAT(DISTINCT T0.Estado) = '1,2' THEN '1'

           -- En cualquier otro caso
           ELSE '1'
       END AS RESULT FROM `programadorsem` T0
     LEFT JOIN (SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO001_1' AS MO_Column, MO001_1 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO001_2' AS MO_Column, MO001_2 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO002' AS MO_Column, MO002 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO004' AS MO_Column, MO004 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO014' AS MO_Column, MO014 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO008_1' AS MO_Column, MO008_1 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO008_2' AS MO_Column, MO008_2 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO007_1' AS MO_Column, MO007_1 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO007_2' AS MO_Column, MO007_2 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO003' AS MO_Column, MO003 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO015' AS MO_Column, MO015 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO005' AS MO_Column, MO005 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO009' AS MO_Column, MO009 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO004P' AS MO_Column, MO004P AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO006' AS MO_Column, MO006 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO012' AS MO_Column, MO012 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO011' AS MO_Column, MO011 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO020' AS MO_Column, MO020 AS MO_Value FROM othija
  WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2'
UNION
SELECT 
  IdHijOT, Year, ProjectCode, CodPdre, Npdre, ClientName, Cantidad, HHFab, PesPptoTotaKg, 
  PeSoldaduraKg,IdPadreOT, 'MO010' AS MO_Column, MO010 AS MO_Value FROM othija
   WHERE `IdPadreOT`= $periodo1  and `CodPdre`='$periodo2') T1 ON T1.IdPadreOT=T0.`otpadre` AND T1.CodPdre=T0.CodPdre  AND T1.MO_Column=T0.CodProceso
WHERE T0.otpadre=$periodo1 AND T0.CodPdre='$periodo2' AND T0.CodProceso='$periodo3'";
$resultado = $conexion->prepare($consulta);
$resultado->execute();
$data=$resultado->fetchAll(PDO::FETCH_ASSOC);

// Verificamos si existe un valor 'total' y lo enviamos
if ($data && isset($data[0]['RESULT'])) {
    // Si el total está presente, lo tomamos y lo devolvemos en JSON
    $RESULT = $data[0]['RESULT']; 
    echo json_encode(array("RESULT" => $RESULT), JSON_UNESCAPED_UNICODE);
} else {
    // Si no hay resultados, devolvemos un total de 0
    echo json_encode(array("RESULT" => 0), JSON_UNESCAPED_UNICODE);
}

$conexion=null;
?>



