<?php
/**
 * api/encuesta/exportar_csv.php — Exporta resultados completos a CSV
 * GET ?id=<id_encuesta>
 */
require_once __DIR__ . '/ConsultasEncuesta.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { http_response_code(400); exit('ID requerido'); }

$encuesta = ConsultasEncuesta::getEncuesta($id);
if (!$encuesta) { http_response_code(404); exit('Encuesta no encontrada'); }

$data    = ConsultasEncuesta::getExportData($id);
$titulo  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $encuesta['titulo']);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="encuesta_' . $titulo . '_' . date('Ymd') . '.csv"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

fputcsv($out, ['Usuario', 'Área', 'Pregunta', 'Tipo', 'Respuesta', 'Fecha'], ';');

foreach ($data as $row) {
    fputcsv($out, [
        $row['usuario'],
        $row['area']     ?? '',
        $row['pregunta'],
        $row['tipo'],
        $row['respuesta'],
        $row['fecha'],
    ], ';');
}

fclose($out);
