<?php
/** Conservación de descripciones completas de HH META. */
function hhmeta_asegurar_nombre_completo(PDO $conexion): void {
    $col=$conexion->query("SHOW FULL COLUMNS FROM othija LIKE 'NPdre'")->fetch(PDO::FETCH_ASSOC);
    if(!$col)throw new RuntimeException('No se encontró la columna othija.NPdre.');
    $tipo=strtolower($col['Type']??'');$collation=(string)($col['Collation']??'');
    if(!in_array($tipo,['text','mediumtext','longtext'],true)||strpos($collation,'utf8mb4_')!==0){
        $tipo=in_array($tipo,['mediumtext','longtext'],true)?strtoupper($tipo):'TEXT';
        $null=($col['Null']??'NO')==='YES'?'NULL':'NOT NULL';
        $collation=(strpos($collation,'utf8mb4_')===0&&preg_match('/^[a-z0-9_]+$/',$collation))?$collation:'utf8mb4_unicode_ci';
        $conexion->exec("ALTER TABLE othija MODIFY COLUMN NPdre $tipo CHARACTER SET utf8mb4 COLLATE $collation $null");
    }
}
function hhmeta_nombre_recuperable(string $actual,string $completo): bool {
    $actual=trim($actual);$completo=trim($completo);
    if(!preg_match('/(?:\x{2026}|\.{3})$/u',$actual)||preg_match('/(?:\x{2026}|\.{3})$/u',$completo))return false;
    $prefijo=preg_replace('/(?:\x{2026}|\.{3})$/u','',$actual);
    return $prefijo!==''&&strlen($completo)>strlen($prefijo)&&strncmp($completo,$prefijo,strlen($prefijo))===0;
}