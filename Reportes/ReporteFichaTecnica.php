<?php
require_once __DIR__ . '/../config/settings.php';
/** Reporte de Ficha Tecnica de Equipos T-TI-F-02. */
include_once '../public/ajax/conexion.php';
include_once '../includes/functions.php';

$objeto = new Conexion();
$conexion = $objeto->Conectar();
$sql = "SELECT c.IdMaquina,
 COALESCE(CONCAT(u.area,IF(u.subarea!='' AND u.subarea IS NOT NULL,CONCAT('/',u.subarea),'')),'-') area,
 c.Host,YEAR(c.Fecha_Maqui) fecha,
 CONCAT(IFNULL(c.Modelo_Placa,''),' / ',IFNULL(c.Procesador,''),' / RAM ',IFNULL(c.Mem_Ram,''),' Gb / ',
 CASE c.Tipo_Disco WHEN 'SOLIDO' THEN 'SSD' WHEN 'MECANICO' THEN 'HDD' WHEN 'NVME' THEN 'SSD M.2 NVMe' ELSE IFNULL(c.Tipo_Disco,'') END,
 ' ',IFNULL(c.Tamano_Disco,''),' GB') Caracteristicas,
 c.Ubicacion ubicacion,c.EstadoMaquina,COALESCE(u.name,'Sin asignar') usuario
 FROM maquina c LEFT JOIN asig_maquina g ON g.IdMaquina=c.IdMaquina LEFT JOIN users u ON g.id=u.id
 ORDER BY c.IdMaquina";
$stmt = $conexion->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$conexion = null;

header('Pragma: public');
header('Expires: 0');
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename=FichaTecnica_T-TI-F-02.xls');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
?>
<html><head><meta charset="UTF-8"></head><body>
<style>
table{font-family:Arial,sans-serif;border-collapse:collapse;width:100%;font-size:9pt}td,th{border:1px solid #002752;padding:4px 6px}.hdr-dark{background:#002752;color:#fff;text-align:center;font-weight:bold}.hdr-mid{background:#1F3864;color:#fff;text-align:center;font-weight:bold}.hdr-col{background:#0069D9;color:#fff;text-align:center;font-weight:bold}.center{text-align:center}.code-ref{background:#002752;color:#fff;text-align:center;font-weight:bold;font-size:10pt}
</style>
<table><thead>
<tr><td rowspan="5" colspan="2" class="center"><img src="<?php echo htmlspecialchars(REPORT_LOGO_URL, ENT_QUOTES, 'UTF-8'); ?>" width="120" height="50"></td><td colspan="3" rowspan="2" class="hdr-dark" style="font-size:12pt">SISTEMA INTEGRADO DE GESTI&Oacute;N</td><td class="code-ref" colspan="3">T-TI-F-02</td></tr>
<tr><td class="hdr-mid">FECHA</td><td class="center" colspan="2">2/03/2020</td></tr>
<tr><td colspan="3" rowspan="3" class="hdr-dark" style="font-size:11pt">REGISTRO DE FICHA T&Eacute;CNICA DE EQUIPOS</td><td class="hdr-mid">VERSI&Oacute;N</td><td class="center" colspan="2">1</td></tr>
<tr><td class="hdr-mid">Elaborado</td><td class="hdr-mid">Revisado</td><td class="hdr-mid">Aprobado</td></tr>
<tr><td class="center">TI</td><td class="center">GG</td><td class="center">GG</td></tr>
<tr><th class="hdr-col">N&deg;</th><th class="hdr-col">&Aacute;REA</th><th class="hdr-col">NOMBRE DE EQUIPO</th><th class="hdr-col">FECHA DE ADQUISICI&Oacute;N</th><th class="hdr-col">CARACTER&Iacute;STICAS</th><th class="hdr-col">UBICACI&Oacute;N</th><th class="hdr-col">ESTADO</th><th class="hdr-col">USUARIO</th></tr>
</thead><tbody>
<?php $n=1; foreach($data as $row): ?>
<tr><td class="center"><?= $n++ ?></td><td class="center"><?= htmlspecialchars($row['area'],ENT_QUOTES) ?></td><td class="center"><?= htmlspecialchars($row['Host'],ENT_QUOTES) ?></td><td class="center"><?= htmlspecialchars($row['fecha'],ENT_QUOTES) ?></td><td><?= htmlspecialchars($row['Caracteristicas'],ENT_QUOTES) ?></td><td class="center"><?= htmlspecialchars($row['ubicacion'],ENT_QUOTES) ?></td><td class="center"><?= htmlspecialchars($row['EstadoMaquina'],ENT_QUOTES) ?></td><td class="center"><?= htmlspecialchars($row['usuario'],ENT_QUOTES) ?></td></tr>
<?php endforeach; ?>
</tbody></table></body></html>
