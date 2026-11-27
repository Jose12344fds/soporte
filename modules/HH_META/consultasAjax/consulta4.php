<?php
include_once './conexion.php';

// Obtener los datos JSON de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

// Crear una instancia de la clase de conexión y conectar
$objeto = new Conexion();
$conexion = $objeto->Conectar();

try {
    // Iniciar una transacción
    $conexion->beginTransaction();

    // Iterar sobre el array de datos
    foreach ($data as $index => $row) {
        // Consultar si OTPADRE existe en la tabla otpadre
        $sqlCheckOTPadre = "SELECT IdPadreOT FROM otpadre WHERE ProjectCode = :CodProyect";
        $stmtCheckOTPadre = $conexion->prepare($sqlCheckOTPadre);
        $stmtCheckOTPadre->bindParam(':CodProyect', $row['CodProyect']);
        $stmtCheckOTPadre->execute();

        // Capturamos el resultado
        $existingOTPadre = $stmtCheckOTPadre->fetch(PDO::FETCH_ASSOC);

        // Verificar si se encontró el IdPadreOT en la tabla otpadre
        if ($existingOTPadre) {
            // Actualizamos el valor de IdPadreOT en el array $row
            echo "OTPADRE encontrado para CodProyect " . $row['CodProyect'] . ": IdPadreOT asignado: " . $existingOTPadre['IdPadreOT'] . "<br>";
            $row['IdPadreOT'] = $existingOTPadre['IdPadreOT'];  // Asignamos el valor correcto
        } else {
            // Si no se encuentra en la base de datos, dejamos el valor como está (si lo tiene)
            echo "OTPADRE no encontrado para CodProyect " . $row['CodProyect'] . "<br>";
        }

        // Verificar el valor de IdPadreOT antes de la inserción
        if (!isset($row['IdPadreOT']) || empty($row['IdPadreOT'])) {
            echo "Error: IdPadreOT no se ha asignado correctamente para CodProyect " . $row['CodProyect'] . "<br>";
            continue; // Salir de este ciclo y no realizar la inserción
        } else {
            echo "IdPadreOT para CodProyect " . $row['CodProyect'] . " es: " . $row['IdPadreOT'] . "<br>";
        }

        // Verificar si ya existe un registro con el mismo OTPADRE y CodPdre en la tabla othija
      $sqlCheckExistence = "SELECT COUNT(*) FROM othija WHERE OTPADRE = :CodProyect AND CodPdre = :codPdre AND NumOF = :Numof";
        $stmtCheckExistence = $conexion->prepare($sqlCheckExistence);
        $stmtCheckExistence->bindParam(':CodProyect', $row['CodProyect']);
        $stmtCheckExistence->bindParam(':codPdre', $row['CodPdre']);
          $stmtCheckExistence->bindParam(':Numof', $row['Numof']);
        $stmtCheckExistence->execute();


        // Obtener el resultado del conteo
        $existingCount = $stmtCheckExistence->fetchColumn();

        if ($existingCount > 0) {
            // Si ya existe un registro con el mismo OTPADRE y CodPdre, no realizamos la inserción
            echo "Ya existe un registro con OTPADRE " . $row['CodProyect'] . " y CodPdre " . $row['CodPdre'] . "<br>";
            continue; // Saltamos a la siguiente iteración sin insertar
        } else {
            echo "No existe un registro, se procederá con la inserción para CodProyect " . $row['CodProyect'] . " y CodPdre " . $row['CodPdre'] . "<br>";
        }

        // Insertar las filas después de haber procesado todas las verificaciones
        $sql1 = "INSERT INTO othija (
            Tipo, Year, ProjectCode, CodPdre, NPdre, ClientName, 
            Cantidad, HHFab, PesPptoTotaKg, PeSoldaduraKg,
            MO001_1, MO001_2, MO002, MO004, MO014, MO008_1, MO008_2, MO007_1, MO007_2, 
            MO003, MO015, MO005, MO009, MO004P, MO006, 
            MO012, MO011, MO020, MO010, MO050, MO060, MO070, PesPptoUniKg, NumOF, SEM, CANTI, ITEM, FEAING, FEAREQU, OC, ESTADO, PESONETOTOTALKG, RENDIMEINTOHHTONPPTO, FECHATERMINADA, OTPADRE, Estado_OT, IdPadreOT
        ) VALUES (
            :tipo, :year, :projectCode, :codPdre, :npdre, :clientName, 
            :plannedParentQuantity, :HHFab, :PesPptoTotaKg, :PeSoldaduraKg,
            :mo001_1, :mo001_2, :mo002, :mo004, :mo014, :mo008_1, :mo008_2, :mo007_1, :mo007_2,
            :mo003, :mo015, :mo005, :mo009, :mo004P, :mo006, 
            :mo012, :mo011, :mo020, :mo010, 0, 0, 0, :PesPptoUniKg, :NumOF, :SEM, :CANTI, :ITEM, :FEAING, :FEAREQU, :OC, :ESTADO, :PESONETOTOTALKG, :RENDIMEINTOHHTONPPTO, :FECHATERMINADA, :CodProyect, 1, :IdPadreOT
        )";

        // Preparamos la consulta
        $stmt1 = $conexion->prepare($sql1);

        // Vinculamos los parámetros de la consulta con los valores de la fila
        $stmt1->bindValue(':tipo', $row['Tipo']);
        $stmt1->bindValue(':year', $row['Year']);
        $stmt1->bindValue(':projectCode', $row['ProjectCode']);
        $stmt1->bindValue(':codPdre', $row['CodPdre']);
        $stmt1->bindValue(':npdre', $row['NPdre']);
        $stmt1->bindValue(':clientName', $row['ClientName']);
        $stmt1->bindValue(':plannedParentQuantity', $row['PlannedParentQuantity']);
        $stmt1->bindValue(':HHFab', $row['HHFab']);
        $stmt1->bindValue(':PesPptoTotaKg', $row['PesPptoTotaKg']);
        $stmt1->bindValue(':PeSoldaduraKg', $row['PeSoldaduraKg']);
        $stmt1->bindValue(':mo001_1', $row['MO001_1']);
        $stmt1->bindValue(':mo001_2', $row['MO001_2']);
        $stmt1->bindValue(':mo002', $row['MO002']);
        $stmt1->bindValue(':mo004', $row['MO004']);
        $stmt1->bindValue(':mo014', $row['MO014']);
        $stmt1->bindValue(':mo008_1', $row['MO008_1']);
        $stmt1->bindValue(':mo008_2', $row['MO008_2']);
        $stmt1->bindValue(':mo007_1', $row['MO007_1']);
        $stmt1->bindValue(':mo007_2', $row['MO007_2']);
        $stmt1->bindValue(':mo003', $row['MO003']);
        $stmt1->bindValue(':mo015', $row['MO015']);
        $stmt1->bindValue(':mo005', $row['MO005']);
        $stmt1->bindValue(':mo009', $row['MO009']);
        $stmt1->bindValue(':mo004P', $row['MO004P']);
        $stmt1->bindValue(':mo006', $row['MO006']);
        $stmt1->bindValue(':mo012', $row['MO012']);
        $stmt1->bindValue(':mo011', $row['MO011']);
        $stmt1->bindValue(':mo020', $row['MO020']);
        $stmt1->bindValue(':mo010', $row['MO010']);
        $stmt1->bindValue(':PesPptoUniKg', $row['PesPptoUniKg']);
        $stmt1->bindValue(':NumOF', $row['Numof']);
        $stmt1->bindValue(':SEM', $row['SEM']);
        $stmt1->bindValue(':CANTI', $row['CANTI']);
        $stmt1->bindValue(':ITEM', $row['ITEM']);
        $stmt1->bindValue(':FEAING', $row['FEAING']);
        $stmt1->bindValue(':FEAREQU', $row['FEAREQU']);
        $stmt1->bindValue(':OC', $row['OC']);
        $stmt1->bindValue(':ESTADO', $row['ESTADO']);
        $stmt1->bindValue(':PESONETOTOTALKG', $row['PESONETOTOTALKG']);
        $stmt1->bindValue(':RENDIMEINTOHHTONPPTO', $row['RENDIMEINTOHHTONPPTO']);
        $stmt1->bindValue(':FECHATERMINADA', $row['FECHATERMINADA']);
        $stmt1->bindValue(':CodProyect', $row['CodProyect']);
        $stmt1->bindValue(':IdPadreOT', $row['IdPadreOT']); // Valor actualizado de IdPadreOT

        // Ejecutar la consulta de inserción
        $stmt1->execute();
    }

    // Confirmar la transacción
    $conexion->commit();
    echo json_encode(['message' => 'All data inserted successfully.']);

} catch (PDOException $e) {
    // Deshacer la transacción en caso de error
    $conexion->rollBack();
    echo json_encode(['error' => 'Error inserting data: ' . $e->getMessage()]);
}

$conexion = null;
