<?php

ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {

    // ─────────────────────────────────────────────
    // PARAMETRO OT
    // ─────────────────────────────────────────────

    $otRaw = isset($_GET['ot'])
        ? trim($_GET['ot'])
        : '';

    if ($otRaw === '') {

        ob_end_clean();

        http_response_code(400);

        echo json_encode([
            'error' => 'Parametro ot requerido'
        ]);

        exit;
    }

    // ─────────────────────────────────────────────
    // NORMALIZAR OT
    // ─────────────────────────────────────────────

    $otNormalized = strtoupper(
        preg_replace('/\s+/', '', $otRaw)
    );

    // Acepta:
    // 4682
    // OTC-004682
    // OTC004682

    if (ctype_digit($otNormalized)) {

        $otCod = 'OTC-' . str_pad(
            $otNormalized,
            6,
            '0',
            STR_PAD_LEFT
        );

    } elseif (preg_match('/^OTC-?(\d+)$/', $otNormalized, $matches)) {

        $otCod = 'OTC-' . str_pad(
            $matches[1],
            6,
            '0',
            STR_PAD_LEFT
        );

    } else {

        $otCod = $otNormalized;
    }

    // ─────────────────────────────────────────────
    // CONEXION SAP HANA
    // ─────────────────────────────────────────────

    $dsn = 'odbc:Driver={HDBODBC};'
         . 'ServerNode=192.168.4.33:30015;'
         . 'Database=SBO_TAMAING_PRODUC;'
         . 'CHAR_AS_UTF8=1';

    $pdo = new PDO(
        $dsn,
        'B1ADMIN',
        'Au$tr4l14',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ]
    );

    // ─────────────────────────────────────────────
    // ESCAPAR OT
    // ─────────────────────────────────────────────

    $otEsc = str_replace("'", "''", $otCod);

    // En la vista de solicitudes PROYECTO puede venir con formatos distintos.
    $otDigits = preg_replace('/\D+/', '', $otCod);
    $otShort = $otDigits !== '' ? (string)((int)$otDigits) : '';
    $projectCandidates = array_values(array_unique(array_filter([
        $otCod,
        str_replace('-', '', $otCod),
        $otDigits,
        $otShort,
        $otRaw,
    ], static fn($value) => trim((string)$value) !== '')));
    $projectCandidatesSql = implode(', ', array_map(
        static fn($value) => "'" . str_replace("'", "''", strtoupper(trim((string)$value))) . "'",
        $projectCandidates
    ));

    // ─────────────────────────────────────────────
    // QUERY UNIFICADO
    // ─────────────────────────────────────────────

    $sql = '
    WITH HIST AS (
        SELECT
            T0."OT" AS "OT",
            TRIM(T0."CodItem") AS "CodItem",
            MAX(T0."Anio") AS "Anio",
            MAX(T0."DescItemOC") AS "DescItemOC",
            MAX(T0."EstadoL") AS "EstadoL",
            SUM(T0."CantComp") AS "CantComp"
        FROM "SBO_TAMAING_PRODUC"."TAMA_REPHISTCOMP_WEB" T0
        WHERE T0."OT" = ' . "'$otEsc'" . '
        GROUP BY T0."OT", TRIM(T0."CodItem")
    ),
    OFITEM AS (
        SELECT
            T0."CodProy" AS "OT",
            TRIM(T0."CodItemHijo") AS "CodItem",
            MAX(T0."NomHijo") AS "NomHijo",
            SUM(T0."CantPlanTot") AS "CantPlanTot",
            SUM(T0."CantPendTot") AS "CantPendTot"
        FROM "SBO_TAMAING_PRODUC"."TAMA_OFDetalleXItem" T0
        WHERE T0."CodProy" = ' . "'$otEsc'" . '
        GROUP BY T0."CodProy", TRIM(T0."CodItemHijo")
    ),
    SOLICITUD AS (
        SELECT
            ' . "'$otEsc'" . ' AS "OT",
            TRIM(T0."NUM ARTICULO") AS "CodItem",
            MAX(T0."DESC ARTICULO") AS "DescArticulo",
            MAX(T0."UNI") AS "Unidad",
            SUM(T0."CANTIDAD") AS "CantSolicitudAbierta"
        FROM "SBO_TAMAING_PRODUC"."TAMA_COMPRA_SOLICITU_ABIER" T0
        WHERE UPPER(TRIM(T0."PROYECTO")) IN (' . $projectCandidatesSql . ')
        GROUP BY TRIM(T0."NUM ARTICULO")
    ),
    CONSUMO AS (
        SELECT
            ' . "'$otEsc'" . ' AS "OT",
            TRIM(T0."Cod. Elemento") AS "CodItem",
            MAX(T0."Desc. Elemento") AS "DescElemento",
            SUM(T0."Cantidad") AS "CantConsumida"
        FROM "SBO_TAMAING_PRODUC"."TAMA_CONSUMOS" T0
        WHERE UPPER(TRIM(T0."CodOT")) IN (' . $projectCandidatesSql . ')
        GROUP BY TRIM(T0."Cod. Elemento")
    ),
    TIPOS_ITEM AS (
        SELECT
            UPPER(TRIM(T0."ItemCode")) AS "CodItemOITM",
            MAX(TRIM(T0."U_MSSL_NFT")) AS "TipoItem"
        FROM "SBO_TAMAING_PRODUC"."OITM" T0
        GROUP BY UPPER(TRIM(T0."ItemCode"))
    ),
    CLAVES AS (
        SELECT "OT", "CodItem" FROM HIST
        UNION
        SELECT "OT", "CodItem" FROM OFITEM
        UNION
        SELECT "OT", "CodItem" FROM SOLICITUD
        UNION
        SELECT "OT", "CodItem" FROM CONSUMO
    )
    SELECT
        K."OT" AS "OT",
        K."CodItem" AS "CodItem",
        H."Anio" AS "Anio",
        COALESCE(H."DescItemOC", O."NomHijo", S."DescArticulo", C."DescElemento") AS "DescItemOC",
        H."EstadoL" AS "EstadoL",
        COALESCE(H."CantComp", 0) AS "CantComp",
        COALESCE(O."CantPlanTot", 0) AS "CantPlanTot",
        COALESCE(O."CantPendTot", 0) AS "CantPendTot",
        COALESCE(S."Unidad", \'\') AS "Unidad",
        COALESCE(S."CantSolicitudAbierta", 0) AS "CantSolicitudAbierta",
        COALESCE(C."CantConsumida", 0) AS "CantConsumida",
        CASE
            WHEN UPPER(TRIM(K."CodItem")) LIKE \'MO0%\' THEN \'PROCESOS\'
            ELSE COALESCE(NULLIF(T."TipoItem", \'\'), \'SIN TIPO\')
        END AS "TipoItem"
    FROM CLAVES K
    LEFT JOIN HIST H ON H."OT" = K."OT" AND H."CodItem" = K."CodItem"
    LEFT JOIN OFITEM O ON O."OT" = K."OT" AND O."CodItem" = K."CodItem"
    LEFT JOIN SOLICITUD S ON S."OT" = K."OT" AND S."CodItem" = K."CodItem"
    LEFT JOIN CONSUMO C ON C."OT" = K."OT" AND C."CodItem" = K."CodItem"
    LEFT JOIN TIPOS_ITEM T ON T."CodItemOITM" = CASE
        WHEN UPPER(TRIM(K."CodItem")) LIKE \'SRV-%\' THEN SUBSTRING(UPPER(TRIM(K."CodItem")), 5)
        ELSE UPPER(TRIM(K."CodItem"))
    END
    ORDER BY K."CodItem"
    ';

    // ─────────────────────────────────────────────
    // EJECUTAR QUERY
    // ─────────────────────────────────────────────

    $stmt = $pdo->query($sql);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ─────────────────────────────────────────────
    // DEBUG
    // ─────────────────────────────────────────────

    if (isset($_GET['debug'])) {

        ob_end_clean();

        echo json_encode([

            'ot'     => $otCod,

            'sql'    => $sql,

            'total'  => count($rows),

            'keys'   => !empty($rows)
                ? array_keys($rows[0])
                : [],

            'sample' => !empty($rows)
                ? $rows[0]
                : null,

        ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        exit;
    }

    // ─────────────────────────────────────────────
    // RESPUESTA OK
    // ─────────────────────────────────────────────

    ob_end_clean();

    echo json_encode([

        'ot'    => $otCod,

        'total' => count($rows),

        'data'  => $rows,

    ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Throwable $e) {

    ob_end_clean();

    http_response_code(500);

    echo json_encode([

        'error' => $e->getMessage(),

        'file'  => basename($e->getFile()),

        'line'  => $e->getLine(),

    ]);
}
?>
