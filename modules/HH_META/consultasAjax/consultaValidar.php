<?php
include_once '../consultasAjax/conexion.php';

try {
    if (isset($_POST['idPadre']) && isset($_POST['tipoConsulta'])) {
        $idPadre = $_POST['idPadre'];
        $tipoConsulta = $_POST['tipoConsulta'];

        $objeto = new Conexion();
        $conexion = $objeto->Conectar();

        // Inicializa la consulta según el tipoConsulta
        switch ($tipoConsulta) {
            case '1': // Primera hoja
                $consulta = "SELECT T0.*, T0.ProjectCode AS proyecto, T1.*,
                            IFNULL(T1.MO050,0) AS MO050, IFNULL(T1.MO060,0) AS MO060, IFNULL(T1.MO070,0) AS MO070, IFNULL(T1.MO080,0) AS MO080,
                            (T1.MO001_1 + T1.MO001_2 + T1.MO002 + T1.MO004 + T1.MO014 +
                             T1.MO008_1 + T1.MO008_2 + T1.MO007_1 + T1.MO007_2 + T1.MO003 +
                             T1.MO015 + T1.MO005 + T1.MO009 + T1.MO004P + T1.MO006 +
                             T1.MO012 + T1.MO011 + T1.MO020 + T1.MO010 +
                             IFNULL(T1.MO050,0) + IFNULL(T1.MO060,0) + IFNULL(T1.MO070,0) + IFNULL(T1.MO080,0)) AS SUT
                            FROM otpadre T0
                            LEFT JOIN othija T1 ON T0.IdPadre = T1.IdPadreOT
                            WHERE T0.IdPadre = :idPadre";
                break;

            case '2': // Segunda hoja
                $consulta = "SELECT T0.*, T0.ProjectCode AS proyecto, T1.*,
                            IFNULL(T1.MO050,0) AS MO050, IFNULL(T1.MO060,0) AS MO060, IFNULL(T1.MO070,0) AS MO070, IFNULL(T1.MO080,0) AS MO080,
                            (T1.MO001_1 + T1.MO001_2 + T1.MO002 + T1.MO004 + T1.MO014 
                             ) AS SUT
                            FROM otpadre T0
                            LEFT JOIN othija T1 ON T0.IdPadre = T1.IdPadreOT
                            WHERE T0.IdPadre = :idPadre";
                break;

            case '3': // Tercera hoja
                $consulta = "SELECT T0.*, T0.ProjectCode AS proyecto, T1.*,
                            IFNULL(T1.MO050,0) AS MO050, IFNULL(T1.MO060,0) AS MO060, IFNULL(T1.MO070,0) AS MO070, IFNULL(T1.MO080,0) AS MO080,
                            (T1.MO008_1+T1.MO008_2+T1.MO007_1+T1.MO007_2) AS SUT
                            FROM otpadre T0
                            LEFT JOIN othija T1 ON T0.IdPadre = T1.IdPadreOT
                            WHERE T0.IdPadre = :idPadre";
                break;
            
               case '4': // Cuarta hoja
                $consulta = "SELECT T0.*, T0.ProjectCode AS proyecto, T1.*,
                            IFNULL(T1.MO050,0) AS MO050, IFNULL(T1.MO060,0) AS MO060, IFNULL(T1.MO070,0) AS MO070, IFNULL(T1.MO080,0) AS MO080,
                            (T1.MO003+T1.MO005+T1.MO009+T1.MO004P+T1.MO006) AS SUT
                            FROM otpadre T0
                            LEFT JOIN othija T1 ON T0.IdPadre = T1.IdPadreOT
                            WHERE T0.IdPadre = :idPadre";
                break;

            case '5': // Quinta hoja
                $consulta = "SELECT T0.*, T0.ProjectCode AS proyecto, T1.*,
                            IFNULL(T1.MO050,0) AS MO050, IFNULL(T1.MO060,0) AS MO060, IFNULL(T1.MO070,0) AS MO070, IFNULL(T1.MO080,0) AS MO080,
                            (T1.MO015) AS SUT
                            FROM otpadre T0
                            LEFT JOIN othija T1 ON T0.IdPadre = T1.IdPadreOT
                            WHERE T0.IdPadre = :idPadre";
                break;
            
             case '6': // Sexta hoja
                $consulta = "SELECT T0.*, T0.ProjectCode AS proyecto, T1.*,
                            IFNULL(T1.MO050,0) AS MO050, IFNULL(T1.MO060,0) AS MO060, IFNULL(T1.MO070,0) AS MO070, IFNULL(T1.MO080,0) AS MO080,
                            (T1.MO012+T1.MO011+T1.MO020+T1.MO010) AS SUT
                            FROM otpadre T0
                            LEFT JOIN othija T1 ON T0.IdPadre = T1.IdPadreOT
                            WHERE T0.IdPadre = :idPadre";
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Tipo de consulta inválido'], JSON_UNESCAPED_UNICODE);
                exit;
        }

        $stmt = $conexion->prepare($consulta);
        $stmt->bindParam(':idPadre', $idPadre);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data ?: [], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    $conexion = null;
}
?>
