<?php
require_once __DIR__ . '/../comun.php';
require_once __DIR__ . '/repositorio.php';
function activos_inventario_listar(): array
{
 return ['rows' => iso_inventory_rows()];
}
