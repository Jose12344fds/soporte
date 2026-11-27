<?php
require_once __DIR__ . '/ConexionLocal.php';

/** Adaptador temporal para los endpoints migrados desde modules/HH_META. */
class Conexion
{
    public static function Conectar()
    {
        return ConexionLocal::conectar();
    }
}
