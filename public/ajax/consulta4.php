<?php
/**
 * API para listar teléfonos con asignaciones
 */
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

// Consulta que une telefono con celulares (asignación) y users
$consulta = "SELECT 
    t.IdTelefo,
    t.Modelo,
    t.Imei,
    t.Fecha,
    t.Num_guia,
    t.Costo_Equipo,
    t.Contrato,
    c.IdCelu,
    c.Usuario as UserId,
    c.IdChip,
    c.FechaEntrega,
    c.Observaciones,
    c.EstUso,
    u.name as UsuarioAsignado,
    u.area
FROM telefono t
LEFT JOIN celulares c ON c.IdTelefo = t.IdTelefo AND c.IdCelu = (
    SELECT MAX(c2.IdCelu) FROM celulares c2 WHERE c2.IdTelefo = t.IdTelefo
)
LEFT JOIN users u ON u.id = c.Usuario
ORDER BY t.IdTelefo DESC";

$resultado = $conexion->prepare($consulta);
$resultado->execute();
$data = $resultado->fetchAll(PDO::FETCH_ASSOC);

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = null;
?>



