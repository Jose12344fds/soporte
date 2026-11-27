<?php
include_once '../consultasAjax/conexion.php';

// Debugging - log de parámetros recibidos
error_log('=== CONSULTA2.PHP DEBUG ===');
error_log('POST recibido: ' . print_r($_POST, true));

$periodo1 = $_POST['idPadreOT'] ?? null;
$periodo2 = $_POST['codPdre'] ?? null;
$periodo3 = $_POST['proceso'] ?? null;

// Validar que se recibieron todos los parámetros
if (empty($periodo1) || empty($periodo2) || empty($periodo3)) {
    error_log('ERROR: Parámetros faltantes - idPadreOT: ' . $periodo1 . ', codPdre: ' . $periodo2 . ', proceso: ' . $periodo3);
    echo json_encode(['error' => 'Parámetros faltantes', 'received' => $_POST]);
    exit;
}

error_log('Parámetros válidos - idPadreOT: ' . $periodo1 . ', codPdre: ' . $periodo2 . ', proceso: ' . $periodo3);

try {
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

$consulta = "SELECT T0.IdPrograma, T0.*, T1.* FROM `programadorsem` T0
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

    error_log('Ejecutando consulta SQL');
    $resultado = $conexion->prepare($consulta);
    $resultado->execute();
    $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Consulta exitosa. Registros encontrados: ' . count($data));
    
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('ERROR en consulta2.php: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error en la consulta: ' . $e->getMessage()]);
} finally {
    $conexion = null;
}
?>