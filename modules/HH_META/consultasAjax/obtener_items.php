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

    // Reutilizamos la misma vista que consulta.php y mapeamos a la forma esperada por item-search.js
    $sql = "SELECT 
                T0.\"ItemOT\", 
                T0.\"Descripci\", 
                T0.\"Cantd\" 
            FROM \"SBO_TAMAING_PRODUC\".\"TAMA_VistaProyectosPCP\" T0 
            WHERE T0.\"OT\" = :ot";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ot', $proyecto, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Asegurar UTF-8 y mapear a { codigo, descripcion, cantidad }
    $items = array_map(function ($r) {
        $codigo = isset($r['ItemOT']) ? $r['ItemOT'] : '';
        $descripcion = isset($r['Descripci']) ? $r['Descripci'] : '';
        $cantidad = isset($r['Cantd']) ? $r['Cantd'] : 0;

        // Forzar a UTF-8 por si viene ISO
        $codigo = is_string($codigo) ? utf8_encode($codigo) : $codigo;
        $descripcion = is_string($descripcion) ? utf8_encode($descripcion) : $descripcion;

        return [
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'cantidad' => (int)$cantidad
        ];
    }, $rows ?: []);

    echo json_encode($items, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
