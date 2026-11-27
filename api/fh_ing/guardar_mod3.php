<?php
/**
 * API: Guardar datos del Módulo 3 (pln_mod3_Ing)
 * POST JSON: {
 *   ot,
 *   rows: [{ id_planos, desc_mod3, arch_plano, arch_original, dias }]
 * }
 * Actualiza la última versión de cada plano; sólo inserta si no existe.
 * Responde: { success, message, rows: [...] }
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$registroLock=null;
try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once __DIR__ . '/modulo2_helpers.php';
    require_permiso('ots_ingeniera','ver');
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Método no permitido.');
    }

    $body    = json_decode(file_get_contents('php://input'), true);
    $ot      = isset($body['ot'])   ? trim($body['ot'])   : '';
    $rows_in = isset($body['rows']) ? $body['rows']        : [];

    if ($ot === '') {
        throw new RuntimeException('El campo OT es requerido.');
    }
    if (!is_array($rows_in) || count($rows_in) === 0) {
        throw new RuntimeException('Debe incluir al menos una fila.');
    }

    if (($body['unidad'] ?? '') !== 'dias') throw new RuntimeException('Recarga la página para guardar la duración en días.');
    foreach($rows_in as $rValidar){$v=str_replace(',', '.',(string)($rValidar['dias']??0));if(!is_numeric($v)||(float)$v<0||(float)$v>9999)throw new RuntimeException('Duración inválida.');}
    // Un solo guardado simultáneo por OT, incluso si llegan dos peticiones.
    $registroLock=fopen(dirname(dirname(__DIR__)).'/storage/fh_modulo2_'.sha1($ot).'.lock','c');
    if(!$registroLock)throw new RuntimeException('No se pudo preparar el guardado.');
    $limite=microtime(true)+10;
    while(!flock($registroLock,LOCK_EX|LOCK_NB)){if(microtime(true)>=$limite)throw new RuntimeException('Otro guardado está en proceso.');usleep(100000);}
    // Un plano sólo puede aparecer una vez en la petición.
    $unicas=[];foreach($rows_in as $fila)$unicas[(int)($fila['id_planos']??0)]=$fila;
    $rows_in=array_values($unicas);
    $ot_esc  = $db->escape($ot);
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $saved   = [];
    $cab=find_by_sql("SELECT estado FROM fh_ing_planos_cab WHERE ot='$ot_esc' LIMIT 1");
    if ((int)($cab[0]['estado']??-1)!==2) throw new RuntimeException('La OT no está activa en el Módulo 2.');
    // Mantener las horas históricas; la interfaz y API trabajan en días.
    $col=$db->query("SHOW COLUMNS FROM pln_mod3_Ing LIKE 'dias'");$tipo=$db->fetch_assoc($col);
    if (strtolower($tipo['Type']??'')!=='decimal(12,4)') $db->query("ALTER TABLE pln_mod3_Ing MODIFY COLUMN dias DECIMAL(12,4) NOT NULL DEFAULT 0");
    foreach($rows_in as $rValidar){$pid=(int)($rValidar['id_planos']??0);$p=find_by_sql("SELECT id_planos FROM pln_dtll_Ing WHERE id_planos=$pid AND ot='$ot_esc'");if(!$p)throw new RuntimeException('El plano no pertenece a esta OT.');}


    // ── Asegurar tabla de historial y columna source ────────────────
    $db->query(
        "CREATE TABLE IF NOT EXISTS fh_ing_plano_dwg_history (
            id            INT          NOT NULL AUTO_INCREMENT,
            id_planos     INT          NOT NULL,
            arch_plano    VARCHAR(255) NOT NULL,
            arch_original VARCHAR(255) NOT NULL DEFAULT '',
            tamano        BIGINT       DEFAULT NULL,
            usuario       VARCHAR(120) NOT NULL DEFAULT '',
            source        TINYINT      NOT NULL DEFAULT 1 COMMENT '1=planos 3=mod3',
            created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_id_planos (id_planos)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    // La columna 'source' ya existe en la tabla, no ejecutar ALTER TABLE redundante

    $cu        = current_user();
    $user_name = $cu ? ($cu['name'] ?? ($cu['username'] ?? 'Sistema')) : 'Sistema';
    $user_esc  = $db->escape($user_name);

    $uploadDir = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
               . DIRECTORY_SEPARATOR . 'FH_ING'
               . DIRECTORY_SEPARATOR . 'planos_mod3' . DIRECTORY_SEPARATOR;

    foreach ($rows_in as $r) {
        $id_planos    = (int)($r['id_planos'] ?? 0);
        $desc_mod3    = $db->escape(trim($r['desc_mod3']    ?? ''));
        $arch_plano   = $db->escape(basename($r['arch_plano']   ?? ''));
        $arch_original = $db->escape(basename($r['arch_original'] ?? ''));
        $duracionDias = round((float)str_replace(',', '.', (string)($r['dias'] ?? 0)),4);
        $dias = round($duracionDias * 8,4); // almacenamiento histórico en horas

        if ($id_planos <= 0) continue;

        // ── Detectar cambio de archivo → guardar versión anterior en historial ──
        $old_row = find_by_sql(
            "SELECT id_mod3, arch_plano, arch_original FROM pln_mod3_Ing
             WHERE id_planos = $id_planos ORDER BY id_mod3 DESC LIMIT 1"
        );
        if ($old_row) {
            $old_arch = $old_row[0]['arch_plano']    ?? '';
            $old_orig = $old_row[0]['arch_original'] ?? $old_arch;
            $new_arch = trim($r['arch_plano'] ?? '');
            if ($old_arch !== '' && $new_arch !== '' && $old_arch !== $new_arch) {
                $oa_esc  = $db->escape($old_arch);
                $oo_esc  = $db->escape($old_orig);
                $file_sz = 'NULL';
                $fp      = $uploadDir . basename($old_arch);
                if (is_file($fp)) $file_sz = (int)filesize($fp);
                $db->query(
                    "INSERT INTO fh_ing_plano_dwg_history
                        (id_planos, arch_plano, arch_original, tamano, usuario, source, created_at)
                     VALUES
                        ($id_planos, '$oa_esc', '$oo_esc', $file_sz, '$user_esc', 3, NOW())"
                );
            }
        }

        if($old_row){
            $actual_id=(int)$old_row[0]['id_mod3'];
            $db->query("UPDATE pln_mod3_Ing SET desc_mod3='$desc_mod3',arch_plano='$arch_plano',arch_original='$arch_original',dias=$dias,updated_at=NOW() WHERE id_mod3=$actual_id");
        }else{
            $db->query("INSERT INTO pln_mod3_Ing (id_planos,ot,desc_mod3,arch_plano,arch_original,dias,created_by)
                VALUES ($id_planos,'$ot_esc','$desc_mod3','$arch_plano','$arch_original',$dias,$user_id)");
        }

        $saved[] = [
            'id_planos'    => $id_planos,
            'desc_mod3'    => $r['desc_mod3'] ?? '',
            'arch_plano'   => $r['arch_plano'] ?? '',
            'arch_original'=> $r['arch_original'] ?? '',
            'dias'         => $duracionDias,
        ];
    }

    flock($registroLock,LOCK_UN);fclose($registroLock);$registroLock=null;
    $inicio=fh_modulo2_inicio($ot);
    $saved=fh_modulo2_fechas($saved,$inicio);
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => count($saved) . ' fila(s) guardadas para OT ' . $ot,
        'unidad'=>'dias', 'fecha_inicio_mod3'=>$inicio,
        'rows'    => $saved,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if(is_resource($registroLock)){flock($registroLock,LOCK_UN);fclose($registroLock);}
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
