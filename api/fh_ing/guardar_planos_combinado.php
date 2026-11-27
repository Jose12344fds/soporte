<?php
/**
 * API: Guardar vista combinada — actividades + planos (FH_ING)
 *
 * POST JSON:
 * {
 *   ot:          "OTC-XXXX",
 *   proyecto:    "...",
 *   fecha_inicio:"2026-05-11",
 *   actividades: [
 *     {
 *       actividad_id: 1,
 *       planos: [
 *         { cod_plano, desc_plano, arch_plano, arch_original, dias }
 *       ]
 *     }
 *   ]
 * }
 *
 * Responde:
 * { success, message, actividades: [{ actividad_id, total_dias, fecha_inicio, fecha_fin, planos:[...] }] }
 *
 * Lógica de fechas:
 *   - Actividades y planos secuenciales dentro de jornadas L-V de 8 horas.
 *   - Las fracciones comparten jornada; se omiten sábados y domingos.
 *   - El calendario compartido conserva la duración sin redondear al día completo.
 *   - Los días decimales se guardan tal cual (DECIMAL) en ambas tablas.
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$registroLock=null;
try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_once __DIR__ . '/calendario.php';
    require_once dirname(dirname(__DIR__)) . '/config/app.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Método no permitido.');
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) throw new RuntimeException('JSON inválido.');

    $ot           = trim($body['ot']           ?? '');
    $proyecto     = trim($body['proyecto']     ?? '');
    $fecha_inicio = trim($body['fecha_inicio'] ?? '');
    $actividades  = $body['actividades']       ?? [];

    if ($ot === '') throw new RuntimeException('OT requerida.');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
        throw new RuntimeException('Fecha de inicio inválida.');
    }

    $fechaCheck = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha_inicio);
    if (!$fechaCheck || $fechaCheck->format('Y-m-d') !== $fecha_inicio) throw new RuntimeException('Fecha de inicio inválida.');
    // Orden del catálogo, independiente del orden enviado por el navegador.
    $catalogo = find_by_sql("SELECT id, orden FROM fh_ing_actividades WHERE activo=1 ORDER BY orden, id");
    $rangos=[]; foreach ($catalogo as $i=>$a) $rangos[(int)$a['id']]=$i;
    usort($actividades, function($a,$b) use ($rangos) { return ($rangos[(int)$a['actividad_id']] ?? PHP_INT_MAX) <=> ($rangos[(int)$b['actividad_id']] ?? PHP_INT_MAX); });
    foreach ($actividades as $aValidar) {
        $idValidar=(int)($aValidar['actividad_id'] ?? 0);
        if (!array_key_exists($idValidar,$rangos)) throw new RuntimeException('Actividad no válida.');
        foreach (($aValidar['planos'] ?? []) as $pValidar) {
            $valorDias=str_replace(',', '.', (string)($pValidar['dias'] ?? 0));
            if (!is_numeric($valorDias) || (float)$valorDias<0 || (float)$valorDias>10000) throw new RuntimeException('Duración de plano inválida.');
        }
    }
    $registroLock=fopen(dirname(dirname(__DIR__)).'/storage/fh_modulo1_'.sha1($ot).'.lock','c');
    if(!$registroLock)throw new RuntimeException('No se pudo preparar el guardado.');
    $limite=microtime(true)+10;
    while(!flock($registroLock,LOCK_EX|LOCK_NB)){if(microtime(true)>=$limite)throw new RuntimeException('Hay otro guardado en proceso.');usleep(100000);}
    $ot_esc   = $db->escape($ot);
    $proy_esc = $db->escape($proyecto);
    $fi_esc   = $db->escape($fecha_inicio);
    $user_id  = (int)($_SESSION['user_id'] ?? 0);

    // Validar identidad y códigos antes de modificar registros.
    $idsRecibidos=[];
    foreach($actividades as $aValidar){
        $codigos=[];
        foreach(($aValidar['planos']??[]) as $pValidar){
            $pid=(int)($pValidar['id_planos']??0);$codigo=strtoupper(trim($pValidar['cod_plano']??''));
            if($codigo!==''&&isset($codigos[$codigo]))throw new RuntimeException('Código de plano repetido en la misma actividad: '.$codigo);
            $codigos[$codigo]=true;
            if($pid>0){if(isset($idsRecibidos[$pid]))throw new RuntimeException('Un plano se recibió más de una vez.');$idsRecibidos[$pid]=true;
                $propio=find_by_sql("SELECT id_planos FROM pln_dtll_Ing WHERE id_planos=$pid AND ot='$ot_esc'");
                if(!$propio)throw new RuntimeException('El plano no pertenece a esta OT.');
            }
        }
    }
    // Asegurar que ambas tablas conservan días decimales en instalaciones antiguas.
    foreach (['pln_dtll_Ing','fh_ing_planos_det'] as $tablaDias) {
        $colDias=$db->query("SHOW COLUMNS FROM $tablaDias LIKE 'dias'");
        $tipoDias=$db->fetch_assoc($colDias);
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)/i', $tipoDias['Type'] ?? '')) {
            $db->query("ALTER TABLE $tablaDias MODIFY COLUMN dias DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }
    }
    // ── Upsert cabecera ──────────────────────────────────────────────
    $exist = find_by_sql("SELECT id, estado FROM fh_ing_planos_cab WHERE ot = '$ot_esc' LIMIT 1");
    if ($exist) {
        if((int)$exist[0]['estado']>=2)throw new RuntimeException('La OT ya salió del Módulo 1. Actualiza el listado.');
        $cab_id = (int)$exist[0]['id'];
        $db->query("UPDATE fh_ing_planos_cab
                    SET proyecto='$proy_esc', fecha_inicio='$fi_esc', updated_at=NOW()
                    WHERE id=$cab_id");
    } else {
        $db->query("INSERT INTO fh_ing_planos_cab (ot, proyecto, fecha_inicio, estado, created_by)
                    VALUES ('$ot_esc', '$proy_esc', '$fi_esc', 0, $user_id)");
        $cab_id = $db->insert_id();
    }

    // ── Usuario actual (para historial) ─────────────────────────────
    // Asegurar que la tabla de historial exista (auto-migración)
    $db->query(
        "CREATE TABLE IF NOT EXISTS fh_ing_plano_dwg_history (
            id            INT          NOT NULL AUTO_INCREMENT,
            id_planos     INT          NOT NULL,
            arch_plano    VARCHAR(255) NOT NULL,
            arch_original VARCHAR(255) NOT NULL DEFAULT '',
            tamano        BIGINT       DEFAULT NULL,
            usuario       VARCHAR(120) NOT NULL DEFAULT '',
            created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_id_planos (id_planos)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $cu        = current_user();
    $user_name = $cu ? ($cu['name'] ?? ($cu['username'] ?? 'Sistema')) : 'Sistema';
    $user_name_esc = $db->escape($user_name);

    // ── Directorio de archivos ───────────────────────────────────────
    $uploadDir = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
               . DIRECTORY_SEPARATOR . 'FH_ING'
               . DIRECTORY_SEPARATOR . 'planos' . DIRECTORY_SEPARATOR;

    // ── Procesar actividades secuencialmente (UPSERT) ────────────────
    $cursor = 0;
    $all_kept_ids    = [];   // Todos los id_planos que sobreviven el guardado
    $result_acts     = [];

    foreach ((array)$actividades as $act_data) {
        $act_id = (int)($act_data['actividad_id'] ?? 0);
        $planos = is_array($act_data['planos'] ?? null) ? $act_data['planos'] : [];

        $planos_validos = [];
        foreach ($planos as $p) {
            $valor = str_replace(',', '.', (string)($p['dias'] ?? 0));
            if (!is_numeric($valor) || (float)$valor < 0 || (float)$valor > 10000) throw new RuntimeException('Duración de plano inválida.');
            $dias = round((float)$valor, 2);
            $planos_validos[] = array_merge($p, ['dias' => $dias]);
        }

        if (empty($planos_validos)) continue;

        $act_fi     = null;
        $act_ff     = null;
        $total_dias = 0.0;
        $orden      = 1;
        $res_planos = [];

        foreach ($planos_validos as $p) {
            $submitted_id = (int)($p['id_planos'] ?? 0);
            $cod   = $db->escape(trim($p['cod_plano']     ?? ''));
            $desc  = $db->escape(trim($p['desc_plano']    ?? ''));
            $arch  = $db->escape(trim($p['arch_plano']    ?? ''));
            $orig  = $db->escape(trim($p['arch_original'] ?? ''));
            $dias  = (float)$p['dias'];
            // Recuperar la identidad si una petición de alta se repite sin respuesta.
            if(!$submitted_id && $cod!==''){
                $previo=find_by_sql("SELECT id_planos FROM pln_dtll_Ing WHERE ot='$ot_esc' AND actividad_id=$act_id AND cod_plano='$cod' ORDER BY id_planos DESC LIMIT 1");
                if($previo)$submitted_id=(int)$previo[0]['id_planos'];
            }

            $tramo = fh_schedule_slot($fecha_inicio, $cursor, $dias);
            $fi_str = $tramo['fecha_inicio']; $ff_str = $tramo['fecha_fin'];
            if ($fi_str) { if ($act_fi === null) $act_fi=$fi_str; $act_ff=$ff_str; $total_dias+=$dias; }

            $fi_sql_p = $fi_str ? "'$fi_str'" : 'NULL';
            $ff_sql_p = $ff_str ? "'$ff_str'" : 'NULL';

            if ($submitted_id > 0) {
                // ── Detectar cambio de archivo → guardar versión anterior en historial ──
                $old_row = find_by_sql(
                    "SELECT arch_plano, arch_original FROM pln_dtll_Ing
                     WHERE id_planos = $submitted_id LIMIT 1"
                );
                if ($old_row) {
                    $old_arch = $old_row[0]['arch_plano']   ?? '';
                    $old_orig = $old_row[0]['arch_original'] ?? $old_arch;
                    $new_arch = trim($p['arch_plano'] ?? '');
                    if ($old_arch !== '' && $new_arch !== '' && $old_arch !== $new_arch) {
                        $oa_esc  = $db->escape($old_arch);
                        $oo_esc  = $db->escape($old_orig);
                        $file_sz = 'NULL';
                        $fp = $uploadDir . basename($old_arch);
                        if (is_file($fp)) $file_sz = (int)filesize($fp);
                        $db->query(
                            "INSERT INTO fh_ing_plano_dwg_history
                                (id_planos, arch_plano, arch_original, tamano, usuario, created_at)
                             VALUES
                                ($submitted_id, '$oa_esc', '$oo_esc', $file_sz, '$user_name_esc', NOW())"
                        );
                    }
                }
                // ── UPDATE fila existente ──────────────────────────────────────────────
                $db->query(
                    "UPDATE pln_dtll_Ing
                     SET cod_plano='$cod', desc_plano='$desc',
                         arch_plano='$arch', arch_original='$orig',
                         dias=$dias, fecha_inicio=$fi_sql_p, fecha_fin=$ff_sql_p,
                         orden=$orden, actividad_id=$act_id
                     WHERE id_planos = $submitted_id"
                );
                $new_id = $submitted_id;
            } else {
                // ── INSERT nueva fila ──────────────────────────────────────────────────
                $db->query(
                    "INSERT INTO pln_dtll_Ing
                        (ot, actividad_id, cod_plano, desc_plano, arch_plano, arch_original,
                         dias, fecha_inicio, fecha_fin, orden, created_by)
                     VALUES
                        ('$ot_esc', $act_id, '$cod', '$desc', '$arch', '$orig',
                         $dias, $fi_sql_p, $ff_sql_p, $orden, $user_id)"
                );
                $new_id = $db->insert_id();
            }

            $all_kept_ids[] = $new_id;
            $res_planos[] = [
                'id_planos'    => $new_id,
                'cod_plano'    => $p['cod_plano']    ?? '',
                'desc_plano'   => $p['desc_plano']   ?? '',
                'arch_plano'   => $p['arch_plano']   ?? '',
                'arch_original'=> $p['arch_original'] ?? '',
                'dias'         => $dias,
                'fecha_inicio' => $fi_str,
                'fecha_fin'    => $ff_str,
                'horas' => $tramo['horas'], 'inicio_jornada'=>$tramo['inicio_jornada'], 'fin_jornada'=>$tramo['fin_jornada'], 'segmentos'=>$tramo['segmentos'],
            ];
            $orden++;
        }

        // ── Guardar resumen de actividad ─────────────────────────────
        $fi_sql = $act_fi ? "'$act_fi'" : 'NULL';
        $ff_sql = $act_ff ? "'$act_ff'" : 'NULL';
        $act_det = find_by_sql(
            "SELECT id FROM fh_ing_planos_det WHERE cab_id=$cab_id AND actividad_id=$act_id LIMIT 1"
        );
        if ($act_det) {
            $det_id = (int)$act_det[0]['id'];
            $db->query(
                "UPDATE fh_ing_planos_det
                 SET dias=$total_dias, fecha_inicio=$fi_sql, fecha_fin=$ff_sql
                 WHERE id=$det_id"
            );
        } else {
            $db->query(
                "INSERT INTO fh_ing_planos_det (cab_id, actividad_id, dias, fecha_inicio, fecha_fin)
                 VALUES ($cab_id, $act_id, $total_dias, $fi_sql, $ff_sql)"
            );
        }

        $result_acts[] = [
            'actividad_id'  => $act_id,
            'total_dias'    => $total_dias,
            'total_horas' => $total_dias * 8,
            'fecha_inicio'  => $act_fi,
            'fecha_fin'     => $act_ff,
            'planos'        => $res_planos,
        ];
    }

    // ── Eliminar filas que ya no existen en el cronograma ────────────
    if (!empty($all_kept_ids)) {
        $ids_list = implode(',', array_map('intval', $all_kept_ids));
        // Antes de borrar, recoge los archivos de esas filas para limpieza
        $to_del_rows = find_by_sql(
            "SELECT arch_plano FROM pln_dtll_Ing
             WHERE ot='$ot_esc' AND arch_plano != '' AND id_planos NOT IN ($ids_list)"
        );
        $db->query("DELETE FROM pln_dtll_Ing WHERE ot='$ot_esc' AND id_planos NOT IN ($ids_list)");
    } else {
        $to_del_rows = find_by_sql(
            "SELECT arch_plano FROM pln_dtll_Ing WHERE ot='$ot_esc' AND arch_plano != ''"
        );
        $db->query("DELETE FROM pln_dtll_Ing WHERE ot='$ot_esc'");
    }

    // ── Limpiar archivos huérfanos (no en historial) ─────────────────
    $orphan_files = array_column(is_array($to_del_rows) ? $to_del_rows : [], 'arch_plano');
    if (!empty($orphan_files)) {
        $esc_list = implode("','", array_map([$db, 'escape'], $orphan_files));
        $in_hist  = find_by_sql(
            "SELECT DISTINCT arch_plano FROM fh_ing_plano_dwg_history WHERE arch_plano IN ('$esc_list')"
        );
        $in_hist_set = array_column(is_array($in_hist) ? $in_hist : [], 'arch_plano');
        $actual_orphans = array_diff($orphan_files, $in_hist_set);
        foreach ($actual_orphans as $fname) {
            $base = basename($fname);
            $stem = pathinfo($base, PATHINFO_FILENAME);
            foreach (['', '.dxf', '.svg', '.png'] as $suf) {
                $f = $uploadDir . $stem . $suf;
                if (is_file($f)) @unlink($f);
            }
            if (is_file($uploadDir . $base)) @unlink($uploadDir . $base);
        }
    }

    flock($registroLock,LOCK_UN);fclose($registroLock);$registroLock=null;
    ob_end_clean();
    echo json_encode([
        'success'     => true,
        'message'     => 'Cronograma guardado correctamente.',
        'actividades' => $result_acts,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if(is_resource($registroLock)){flock($registroLock,LOCK_UN);fclose($registroLock);}
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
