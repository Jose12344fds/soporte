<?php
require_once __DIR__ . '/../hhmeta_guard.php';
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    require_once __DIR__ . '/Conexion.php';
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

    // Consulta principal para obtener procesos NO cerrados (Estado = 1)
    // Los códigos de proceso son MO001_1, MO001_2, MO002, etc.
    // CONSULTA CORREGIDA CON JOIN A TABLA PROCESOS
    $consulta = "
        SELECT DISTINCT
            ps.otpadre as proyecto,
            COALESCE(ot.ProjectCode, ps.otpadre) as projectCode,
            ps.CodPdre as ot,
            COALESCE(ot.NPdre, ps.otpadre) as descripcion,
            ps.CodPdre as item,
            COALESCE(oh.NPdre, 'Sin descripción item') as descripcion_item,
            ps.CodProceso as codigo_proceso,
            COALESCE(p.Nombre, ps.CodProceso) as proceso,
            ps.Aniio as anio,
            ps.Semana as semana,
            ROUND(ps.Avance, 2) as porcentaje,
            ROUND(COALESCE(ps.AvanceReal, 0), 2) as avance_fisico,
            ROUND(ps.TotHoras, 2) as horas,
            NOW() as fecha_calculo,
            COALESCE(ps.Estado, 0) as estado_actual,
            ps.IdPrograma as id_programa,
            COALESCE(p.Categoria, 
                CASE 
                    WHEN ps.CodProceso LIKE 'MO001%' THEN 'Habilitado'
                    WHEN ps.CodProceso LIKE 'MO002%' THEN 'Fabricación'
                    WHEN ps.CodProceso LIKE 'MO003%' THEN 'Acabados'
                    WHEN ps.CodProceso LIKE 'MO004%' THEN 'Soldadura'
                    WHEN ps.CodProceso LIKE 'MO005%' THEN 'Mecanizado'
                    WHEN ps.CodProceso LIKE 'MO006%' THEN 'Calderería'
                    WHEN ps.CodProceso LIKE 'MO007%' THEN 'Soldadura'
                    WHEN ps.CodProceso LIKE 'MO008%' THEN 'Soldadura'
                    WHEN ps.CodProceso LIKE 'MO009%' THEN 'Mecanizado'
                    WHEN ps.CodProceso LIKE 'MO010%' THEN 'Acabados'
                    WHEN ps.CodProceso LIKE 'MO011%' THEN 'Acabados'
                    WHEN ps.CodProceso LIKE 'MO012%' THEN 'Acabados'
                    WHEN ps.CodProceso LIKE 'MO014%' THEN 'Habilitado'
                    WHEN ps.CodProceso LIKE 'MO015%' THEN 'Mecanizado'
                    WHEN ps.CodProceso LIKE 'MO020%' THEN 'Acabados'
                    ELSE ps.CodProceso
                END
            ) as categoria_proceso, ps.CodProyecto AS codigo_proyecto
        FROM programadorsem ps
        LEFT JOIN otpadre ot ON ps.otpadre = ot.IdPadreOT
        LEFT JOIN othija oh ON ps.CodPdre = oh.CodPdre AND ps.otpadre = oh.IdPadreOT
        LEFT JOIN procesos p ON ps.CodProceso = p.Proceso
        WHERE ps.Estado = 1  -- Solo procesos ABIERTOS (no cerrados)
            AND ps.Aniio >= (YEAR(NOW()) - 2)  -- Solo últimos 2 años
        ORDER BY ps.Aniio DESC, ps.Semana DESC, ps.otpadre, oh.NPdre, ps.CodProceso
    ";

    $resultado = $conexion->prepare($consulta);
    $resultado->execute();
    // Leer por posición para que los IDs no dependan del case o alias devuelto por PDO.
    $columnas = ['proyecto', 'projectCode', 'ot', 'descripcion', 'item',
        'descripcion_item', 'codigo_proceso', 'proceso', 'anio', 'semana',
        'porcentaje', 'avance_fisico', 'horas', 'fecha_calculo', 'estado_actual', 'id_programa', 'categoria_proceso', 'codigo_proyecto'];
    $datos = [];
    foreach ($resultado->fetchAll(PDO::FETCH_NUM) as $valores) {
        $fila = array_combine($columnas, $valores);
        $fila['id_programa'] = $valores[15];
        // Identidad completa de la semana, independiente de IDs históricos incompletos.
        $fila['clave_seleccion'] = base64_encode(json_encode([
            (string)$fila['proyecto'], (string)$fila['codigo_proyecto'],
            (string)$fila['item'], (string)$fila['codigo_proceso'],
            (string)$fila['anio'], (string)$fila['semana']
        ], JSON_UNESCAPED_UNICODE));
        $datos[] = $fila;
    }
    // Calcular estadísticas
    $total_procesos = count($datos);
    $proyectos_unicos = array_unique(array_map(function($r) {
        return $r['projectCode'] ?? ($r['proyecto'] ?? '');
    }, $datos));
    $proyectos_unicos = array_filter($proyectos_unicos, function($v) {
        return $v !== null && $v !== '';
    });
    $total_proyectos = count($proyectos_unicos);
    
    // Calcular semanas únicas
    $semanas_unicas = array();
    foreach ($datos as $row) {
        $semana_key = $row['anio'] . '-' . $row['semana'];
        $semanas_unicas[$semana_key] = true;
    }
    $total_semanas = count($semanas_unicas);

    // Calcular promedio de días de retraso (simplificado ya que usamos NOW())
    $promedio_dias_retraso = 0; // Por ahora 0, ya que no tenemos fecha real de creación

    // Preparar respuesta con estadísticas
    $estadisticas = array(
        'total_procesos' => $total_procesos,
        'total_proyectos' => $total_proyectos,
        'total_semanas' => $total_semanas,
        'promedio_dias_retraso' => round($promedio_dias_retraso, 1)
    );

    // Respuesta exitosa
    echo json_encode(array(
        'success' => true,
        'datos' => $datos,
        'estadisticas' => $estadisticas
    ));

} catch (Exception $e) {
    // Manejo de errores
    echo json_encode(array(
        'success' => false,
        'error' => 'Error al consultar los datos: ' . $e->getMessage(),
        'datos' => array(),
        'estadisticas' => array(
            'total_procesos' => 0,
            'total_proyectos' => 0,
            'total_semanas' => 0,
            'promedio_dias_retraso' => 0
        )
    ));
}
?>
