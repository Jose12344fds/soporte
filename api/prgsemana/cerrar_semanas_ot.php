<?php
require_once __DIR__ . '/../hhmeta_guard.php';
/**
 * cerrar_semanas_ot.php
 * Cierra TODAS las semanas abiertas de una OT (otpadre).
 *
 * Lógica de Av.Físico (AvanceReal) por grupo (CodPdre + CodProceso):
 *  - Suma el AvanceReal ya registrado en filas con valor > 0.
 *  - El resto (100 - suma) se distribuye de forma equitativa entre las filas
 *    que tengan AvanceReal = 0.
 *  - Luego cierra (Estado=2) todas las semanas del grupo.
 *
 * POST params:
 *  idPadre  (int)  — IdPadre de la OT (= programadorsem.otpadre)
 *
 * Response JSON:
 *  { success, message, semanas, procesos: [{idPadreOT, codPdre, projectCode}] }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/Conexion.php';
require_once __DIR__.'/sincronizar_avance.php';

$idPadre = isset($_POST['idPadre']) ? (int)$_POST['idPadre'] : 0;
if ($idPadre <= 0) {
    echo json_encode(['success' => false, 'message' => 'idPadre inválido o no enviado']);
    exit;
}

try {
    $db   = new Conexion();
    $cn   = $db->Conectar();

    /* ── 1. Obtener todas las semanas ABIERTAS de la OT ── */
    $stmt = $cn->prepare(
        "SELECT IdPrograma, CodPdre, CodProceso, CodProyecto, AvanceReal
         FROM   programadorsem
         WHERE  otpadre = :ot AND Estado = 1
         ORDER  BY CodPdre, CodProceso"
    );
    $stmt->execute([':ot' => $idPadre]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo json_encode(['success' => false, 'message' => 'No hay semanas abiertas para esta OT']);
        exit;
    }

    /* ── 2. Agrupar por (CodPdre, CodProceso) ── */
    $groups      = [];   // key => [ row, ... ]
    $combosRecalc = [];  // key => { idPadreOT, codPdre, projectCode }

    foreach ($rows as $row) {
        $key = $row['CodPdre'] . '||' . $row['CodProceso'];
        if (!isset($groups[$key])) {
            $groups[$key]    = [];
            $combosRecalc[$key] = [
                'idPadreOT'   => $idPadre,
                'codPdre'     => $row['CodPdre'],
                'projectCode' => $row['CodProyecto']
            ];
        }
        $groups[$key][] = $row;
    }

    $cn->beginTransaction();

    $stmtUpAv = $cn->prepare("UPDATE programadorsem SET AvanceReal = :av WHERE IdPrograma = :id");

    foreach ($groups as $groupRows) {
        /* ── 3. Calcular distribución de AvanceReal ── */
        $sumaNoCero = 0.0;
        $idsCero    = [];

        foreach ($groupRows as $r) {
            $av = floatval($r['AvanceReal']);
            if ($av > 0.01) {
                $sumaNoCero += $av;
            } else {
                $idsCero[] = (int)$r['IdPrograma'];
            }
        }

        // Distribuir el porcentaje restante entre las filas con AvanceReal = 0
        if (!empty($idsCero)) {
            $restante = max(0.0, 100.0 - $sumaNoCero);
            $porFila  = round($restante / count($idsCero), 4);

            // La última fila absorbe el residuo de redondeo
            $suma = 0.0;
            $last = count($idsCero) - 1;
            foreach ($idsCero as $i => $id) {
                $valor = ($i === $last)
                    ? round(max(0.0, $restante - $suma), 4)
                    : $porFila;
                $stmtUpAv->execute([':av' => $valor, ':id' => $id]);
                $suma += $valor;
            }
        }

        /* ── 4. Cerrar todas las semanas del grupo (Estado=2) ── */
        $ids    = array_column($groupRows, 'IdPrograma');
        $ph     = implode(',', array_fill(0, count($ids), '?'));
        $stmtCl = $cn->prepare("UPDATE programadorsem SET Estado = 2 WHERE IdPrograma IN ($ph)");
        $stmtCl->execute($ids);
    }

    $cn->commit();
    $sapSync=psSincronizarItems($cn,array_values($combosRecalc));

    echo json_encode([
        'success'  => true,
        'message'  => count($rows) . ' semana(s) cerrada(s) en ' . count($groups) . ' proceso(s)',
        'semanas'  => count($rows),
        'sapSync'=>$sapSync,'warnings'=>$sapSync['warnings'],
        'procesos' => array_values($combosRecalc)
    ]);

} catch (Exception $e) {
    if (isset($cn) && $cn->inTransaction()) {
        try { $cn->rollBack(); } catch (Exception $__) {}
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $cn = null;
}
