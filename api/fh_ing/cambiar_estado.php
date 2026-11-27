<?php
/**
 * API: Cambiar estado de una OT (FH_ING)
 * POST JSON: { ot, estado }
 * Actualiza fh_ing_planos_cab.estado
 * Responde: { success, message }
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_permiso('ots_ingeniera','ver');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new RuntimeException('Método no permitido.');
    }

    $body  = json_decode(file_get_contents('php://input'), true);
    $ot    = isset($body['ot'])     ? trim($body['ot'])      : '';
    $estado = isset($body['estado']) ? (int)$body['estado']  : -1;

    if ($ot === '') {
        throw new RuntimeException('El campo OT es requerido.');
    }
    if ($estado < 0 || $estado > 10) {
        throw new RuntimeException('Estado inválido.');
    }

    $ot_esc = $db->escape($ot);

    // Verificar que existe un registro en planos_cab para esta OT
    $cab = find_by_sql("SELECT id, estado FROM fh_ing_planos_cab WHERE ot = '$ot_esc' LIMIT 1");
    if (empty($cab)) {
        throw new RuntimeException('No existe cronograma guardado para esta OT. Guarda el cronograma antes de aprobar.');
    }

    $actual=(int)$cab[0]['estado'];
    // 0/1=M1; 2=M2; 4=M2 terminado, pendiente de cierre; 3=proyecto cerrado.
    $permitidos=[0=>[1,2],1=>[0,2],2=>[0,1,4],4=>[3],3=>[]];
    if($estado!==$actual && !in_array($estado,$permitidos[$actual]??[],true))throw new RuntimeException('La transición no corresponde al estado actual del proyecto. Actualiza el listado.');
    if($estado===3 && $actual!==3 && ($body['accion']??'')!=='cerrar_proyecto')throw new RuntimeException('El proyecto se cierra desde el registro de OTs.');
    if($estado===4 && $actual!==4){$detalle=find_by_sql("SELECT COUNT(*) AS cnt FROM pln_mod3_Ing WHERE ot='$ot_esc' AND dias>0");if(!(int)($detalle[0]['cnt']??0))throw new RuntimeException('Guarda el detalle del Módulo 2 antes de terminarlo.');}
    if($estado!==$actual){
        $cierreSql='';
        if($estado===3){
            $col=$db->query("SHOW COLUMNS FROM fh_ing_planos_cab LIKE 'fecha_cierre'");
            if(!$db->num_rows($col))$db->query("ALTER TABLE fh_ing_planos_cab ADD COLUMN fecha_cierre DATETIME NULL DEFAULT NULL");
            $col=$db->query("SHOW COLUMNS FROM fh_ing_planos_cab LIKE 'cerrado_por'");
            if(!$db->num_rows($col))$db->query("ALTER TABLE fh_ing_planos_cab ADD COLUMN cerrado_por INT NULL DEFAULT NULL");
            $uid=(int)($_SESSION['user_id']??0);
            $cierreSql=", fecha_cierre=NOW(), cerrado_por=$uid";
        }
        $db->query("UPDATE fh_ing_planos_cab SET estado=$estado,updated_at=NOW() $cierreSql WHERE ot='$ot_esc' AND estado=$actual");
    }

    $labels = [
        0 => 'En proceso (Módulo 1)',
        1 => 'En proceso — Módulo 1',
        2 => 'Aprobado — pasó a Módulo 2',
        3 => 'Proyecto cerrado',
        4 => 'Módulo 2 terminado — pendiente de cierre en Registro de OT',
    ];
    $label = $labels[$estado] ?? "Estado $estado";

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => "OT $ot actualizada: $label",
        'ot'      => $ot,
        'estado'  => $estado,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
