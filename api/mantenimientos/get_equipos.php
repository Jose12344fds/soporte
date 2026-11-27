<?php
/**
 * API: Equipos SAP para mantenimiento.
 * Lista equipos solo desde @TAMA_EQUIPOS_TM.
 */
ob_start();
header('Content-Type: application/json; charset=utf-8');

$debug      = !empty($_GET['debug']);
$sin_filtro = !empty($_GET['sin_filtro']);

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

require_once __DIR__ . '/conexion_hana.php';

function hana_col_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $pdo->prepare('SELECT "' . str_replace('"', '""', $column) . '" FROM "SBO_TAMAING_PRODUC"."' . $table . '" WHERE 1=0');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

try {
    $search   = isset($_GET['search'])   ? trim($_GET['search'])   : '';
    $itemcode = isset($_GET['itemcode']) ? trim($_GET['itemcode']) : '';
    $paraProgramacion = !empty($_GET['para_programacion']);
    $categoria = isset($_GET['categoria']) ? strtolower(trim($_GET['categoria'])) : 'mantenimiento';
    if (!in_array($categoria, ['mantenimiento', 'sistemas', 'todos'], true)) {
        $categoria = 'mantenimiento';
    }

    $pdo = getHanaPdo();
    $modo = strtolower(trim($_GET['modo'] ?? $_GET['mode'] ?? 'select'));
    $codRegistroColumn = hana_col_exists($pdo, '@TAMA_EQUIPOS_TM', 'U_CodRegistro')
        ? 'U_CodRegistro'
        : (hana_col_exists($pdo, '@TAMA_EQUIPOS_TM', 'U_CodRegisto') ? 'U_CodRegisto' : '');
    $hasCodRegistro = $codRegistroColumn !== '';
    $hasCodComtaSap = hana_col_exists($pdo, '@TAMA_EQUIPOS_TM', 'U_CodComtaSap');
    $hasObservacion = hana_col_exists($pdo, '@TAMA_EQUIPOS_TM', 'U_Observacion');
    $criticidadFields = ['U_F1_EVA', 'U_R_EVA', 'U_SHA_EVA', 'U_IC_EVA', 'U_IP_EVA', 'U_BM_EVA', 'U_CM_EVA', 'U_RIESGO_EVA'];
    $criticidadAvailable = [];
    foreach ($criticidadFields as $criticidadField) {
        $criticidadAvailable[$criticidadField] = hana_col_exists($pdo, '@TAMA_EQUIPOS_TM', $criticidadField);
    }
    $criticidadMissing = array_keys(array_filter($criticidadAvailable, static function ($exists) { return !$exists; }));

    if ($modo === 'next_code' || $modo === 'siguiente') {
        $row = $pdo->query(
            'SELECT "Code", "Name"'
            . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM"'
            . " WHERE \"Code\" IS NOT NULL AND LENGTH(\"Code\") > 0 AND \"Code\" LIKE_REGEXPR '^[0-9]+$'"
            . ' ORDER BY CAST("Code" AS INTEGER) DESC LIMIT 1'
        )->fetch();
        $lastCode = $row ? (int)($row['Code'] ?? array_values($row)[0] ?? 0) : 0;
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'lastCode' => $lastCode,
            'nextCode' => $lastCode + 1,
            'lastName' => $row['Name'] ?? '',
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    if ($modo === 'crud' || $modo === 'tabla' || $modo === 'list') {
        $conditions = [];
        if ($search !== '') {
            $esc = str_replace("'", "''", $search);
            $conditions[] = '('
                . 'T0."Code" LIKE ' . "'%{$esc}%'"
                . ' OR T0."Name" LIKE ' . "'%{$esc}%'"
                . ' OR T0."U_ItemNameEq" LIKE ' . "'%{$esc}%'"
                . ' OR T0."U_InventryNo" LIKE ' . "'%{$esc}%'"
                . ' OR T0."U_AssetSerNo" LIKE ' . "'%{$esc}%'"
                . ' OR T0."U_Marca" LIKE ' . "'%{$esc}%'"
                . ' OR T0."U_Modelo" LIKE ' . "'%{$esc}%'"
                . ($hasCodComtaSap ? ' OR T0."U_CodComtaSap" LIKE ' . "'%{$esc}%'" : '')
                . ' OR T0."U_UBICACION" LIKE ' . "'%{$esc}%'"
                . ')';
        }
        $estado = strtoupper(trim($_GET['estado'] ?? ''));
        if ($estado !== '') {
            $escEstado = str_replace("'", "''", $estado);
            $conditions[] = 'UPPER(COALESCE(T0."U_Estado", \'\')) = ' . "'{$escEstado}'";
        }
        $limit = (int)($_GET['limit'] ?? 5000);
        if ($limit < 1) $limit = 5000;
        if ($limit > 10000) $limit = 10000;
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = 'SELECT'
             . ' T0."Code",'
             . ' T0."Name",'
             . ' T0."U_ItemNameEq",'
             . ' T0."U_InventryNo",'
             . ' T0."U_AssetSerNo",'
             . ' T0."U_Marca",'
             . ' T0."U_Modelo",'
             . ' T0."U_TIP_EMH",'
             . ' T0."U_Cod_Tipo",'
             . ($hasCodComtaSap ? ' T0."U_CodComtaSap",' : ' NULL AS "U_CodComtaSap",')
             . ' T0."U_Fecha_Adq",'
             . ' T0."U_Criterio",'
             . ' T0."U_Nivel_Criterio",'
             . ' T0."U_UBICACION",'
             . ' T0."U_Estado",'
             . ' T0."U_FECHA_BAJA",'
             . ' T0."U_Vida_Util",'
             . ' T0."U_Id_Proceso",'
             . ($hasObservacion ? ' CAST(T0."U_Observacion" AS NVARCHAR(254)) AS "U_Observacion",' : ' NULL AS "U_Observacion",')
             . ($hasCodRegistro ? ' T0."' . $codRegistroColumn . '" AS "U_CodRegistro",' : ' NULL AS "U_CodRegistro",')
             . implode('', array_map(static function ($column) use ($criticidadAvailable) {
                 return ($criticidadAvailable[$column] ? ' T0."' . $column . '"' : ' NULL') . ' AS "' . $column . '",';
             }, $criticidadFields))
             . ' P."Name" AS "ProcesoName",'
             . ' R."Name" AS "RegistroName"'
             . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" T0 '
             . ' LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_PROCESO" P ON CAST(T0."U_Id_Proceso" AS NVARCHAR) = CAST(P."Code" AS NVARCHAR)'
             . ($hasCodRegistro ? ' LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_REGISTROS" R ON CAST(T0."' . $codRegistroColumn . '" AS NVARCHAR) = CAST(R."Code" AS NVARCHAR)' : ' LEFT JOIN "SBO_TAMAING_PRODUC"."@TAMA_REGISTROS" R ON 1 = 0')
             . $where
             . ' ORDER BY LPAD(COALESCE(T0."Code", \'\'), 12, \'0\') DESC, T0."Name"'
             . ' LIMIT ' . $limit;

        $rows = $pdo->query($sql)->fetchAll();
        $clean = function ($v) {
            return trim(str_replace("\0", '', (string)($v ?? '')));
        };
        $date10 = function ($v) use ($clean) {
            $s = $clean($v);
            return preg_match('/^\d{4}-\d{2}-\d{2}/', $s, $m) ? $m[0] : '';
        };
        $result = array_map(function ($r) use ($clean, $date10) {
            return [
                'Code' => $clean($r['Code'] ?? ''),
                'Name' => $clean($r['Name'] ?? ''),
                'ItemCode' => $clean($r['Name'] ?? ''),
                'ItemName' => $clean($r['U_ItemNameEq'] ?? ''),
                'InventryNo' => $clean($r['U_InventryNo'] ?? ''),
                'AssetSerNo' => $clean($r['U_AssetSerNo'] ?? ''),
                'Marca' => $clean($r['U_Marca'] ?? ''),
                'Modelo' => $clean($r['U_Modelo'] ?? ''),
                'TIP_EMH' => $clean($r['U_TIP_EMH'] ?? ''),
                'Cod_Tipo' => $clean($r['U_Cod_Tipo'] ?? ''),
                'CodComtaSap' => $clean($r['U_CodComtaSap'] ?? ''),
                'Fecha_Adq' => $date10($r['U_Fecha_Adq'] ?? ''),
                'Criterio' => $clean($r['U_Criterio'] ?? ''),
                'Nivel_Criterio' => $clean($r['U_Nivel_Criterio'] ?? ''),
                'Ubicacion' => $clean($r['U_UBICACION'] ?? ''),
                'Estado' => $clean($r['U_Estado'] ?? ''),
                'Fecha_Baja' => $date10($r['U_FECHA_BAJA'] ?? ''),
                'Vida_Util' => $clean($r['U_Vida_Util'] ?? ''),
                'Id_Proceso' => $clean($r['U_Id_Proceso'] ?? ''),
                'Observacion' => $clean($r['U_Observacion'] ?? ''),
                'CodRegistro' => $clean($r['U_CodRegistro'] ?? ''),
                'ProcesoName' => $clean($r['ProcesoName'] ?? ''),
                'RegistroName' => $clean($r['RegistroName'] ?? ''),
                'F1_EVA' => $clean($r['U_F1_EVA'] ?? ''),
                'R_EVA' => $clean($r['U_R_EVA'] ?? ''),
                'SHA_EVA' => $clean($r['U_SHA_EVA'] ?? ''),
                'IC_EVA' => $clean($r['U_IC_EVA'] ?? ''),
                'IP_EVA' => $clean($r['U_IP_EVA'] ?? ''),
                'BM_EVA' => $clean($r['U_BM_EVA'] ?? ''),
                'CM_EVA' => $clean($r['U_CM_EVA'] ?? ''),
                'Riesgo_EVA' => $clean($r['U_RIESGO_EVA'] ?? ''),
                'Fuente' => '@TAMA_EQUIPOS_TM',
            ];
        }, $rows);

        if ($debug) {
            ob_end_clean();
            echo json_encode([
                'debug' => true,
                'modo' => $modo,
                'sql' => $sql,
                'rows_returned' => count($rows),
                'sample' => array_slice($result, 0, 3),
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT);
            exit;
        }

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'data' => $result,
            'total' => count($result),
            'criticidad_configurada' => count($criticidadMissing) === 0,
            'campos_criticidad_faltantes' => $criticidadMissing,
            'observacion_configurada' => $hasObservacion,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    $conditionsTm = [];

    if ($paraProgramacion) {
        $conditionsTm[] = 'UPPER(TRIM(COALESCE(T0."U_Estado", \'\'))) <> \'DE BAJA\'';
    }

    // @TAMA_EQUIPOS_TM no tiene columna de categoria en SAP; el filtro visual no restringe esta consulta.

    if ($itemcode !== '') {
        $esc = str_replace("'", "''", $itemcode);
        $conditionsTm[]   = "T0.\"Name\" = '{$esc}'";
    } elseif ($search !== '') {
        $esc = str_replace("'", "''", $search);
        $conditionsTm[]   = "(T0.\"Name\" LIKE '%{$esc}%' OR T0.\"U_ItemNameEq\" LIKE '%{$esc}%' OR T0.\"U_InventryNo\" LIKE '%{$esc}%' OR T0.\"U_AssetSerNo\" LIKE '%{$esc}%')";
    }

    $whereTm = $conditionsTm ? 'WHERE ' . implode(' AND ', $conditionsTm) : '';

    $sql = 'SELECT'
         . ' T0."Name" AS "ItemCode",'
         . ' T0."U_ItemNameEq" AS "ItemName",'
         . ' T0."U_InventryNo" AS "InventryNo",'
         . ' T0."U_AssetSerNo" AS "AssetSerNo",'
         . ' T0."U_Marca" AS "Marca",'
         . ' T0."U_Modelo" AS "Modelo",'
         . ' T0."U_Estado" AS "Estado",'
         . " '@TAMA_EQUIPOS_TM' AS \"Fuente\""
         . ' FROM "SBO_TAMAING_PRODUC"."@TAMA_EQUIPOS_TM" T0'
         . ' ' . $whereTm
         . ' ORDER BY T0."Name"'
         . ' LIMIT 100';

    $rows = $pdo->query($sql)->fetchAll();

    if ($debug) {
        ob_end_clean();
        echo json_encode([
            'debug' => true,
            'sql' => $sql,
            'rows_returned' => count($rows),
            'sample' => array_slice($rows, 0, 3),
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT);
        exit;
    }

    $result = array_map(function ($r) {
        $code = trim((string)($r['ItemCode'] ?? ''));
        $name = trim((string)($r['ItemName'] ?? ''));
        return [
            'id'         => $code,
            'text'       => $code . ' - ' . $name,
            'ItemCode'   => $code,
            'ItemName'   => $name,
            'InventryNo' => $r['InventryNo'] ?? '',
            'AssetSerNo' => $r['AssetSerNo'] ?? '',
            'Marca'      => $r['Marca'] ?? '',
            'Modelo'     => $r['Modelo'] ?? '',
            'Estado'     => $r['Estado'] ?? '',
            'Fuente'     => $r['Fuente'] ?? '',
        ];
    }, $rows);

    ob_end_clean();
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
