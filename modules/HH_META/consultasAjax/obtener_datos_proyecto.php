<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../Conexion/ConexionHana.php';

    $proyecto = isset($_POST['proyecto']) ? $_POST['proyecto'] : '';
    if ($proyecto === '') {
        echo json_encode([]);
        exit;
    }

    $db = Conexion1::Conectar1();

    // Consultar datos del proyecto y mapear a la tabla del módulo DataTable
    $sql = "SELECT 
                T0.\"ItemOT\", 
                T0.\"Descripci\", 
                T0.\"Cantd\", 
                T0.\"HHFab\", 
                T0.\"Estado\" 
            FROM \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectosPCP\" T0 
            WHERE T0.\"OT\" = :ot";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ot', $proyecto, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = array_map(function ($r) {
        $item = isset($r['ItemOT']) ? $r['ItemOT'] : '';
        $desc = isset($r['Descripci']) ? $r['Descripci'] : '';
        $cant = isset($r['Cantd']) ? $r['Cantd'] : 0;
        $hh   = isset($r['HHFab']) ? $r['HHFab'] : 0;
        $est  = isset($r['Estado']) && $r['Estado'] !== '' ? $r['Estado'] : 'pendiente';

        // Forzar a UTF-8 por si viene ISO
        $item = is_string($item) ? utf8_encode($item) : $item;
        $desc = is_string($desc) ? utf8_encode($desc) : $desc;

        return [
            'ItemOT'      => $item,
            'Descripcion' => $desc,
            'Cantidad'    => (int)$cant,
            'HHEstimadas' => (float)$hh,
            'Estado'      => $est
        ];
    }, $rows ?: []);

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
