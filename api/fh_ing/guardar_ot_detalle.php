<?php
/** Registro de OT: autor e inicio se fijan en el primer guardado. */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
$registroLock = null;
$etapa = 'carga';
try {
    require_once dirname(dirname(__DIR__)) . '/includes/load.php';
    require_permiso('ots_ingeniera', 'ver');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Método no permitido.');
    }
    $cu = current_user();
    $user_id = (int)($cu['id'] ?? 0);
    $nombre = trim($cu['name'] ?? $cu['username'] ?? '');
    if ($user_id < 1 || $nombre === '') {
        throw new RuntimeException('Debe iniciar sesión con un usuario válido.');
    }
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) throw new RuntimeException('Petición inválida.');
    $ot = strtoupper(trim((string)($body['ot'] ?? '')));
    if (ctype_digit($ot)) $ot = 'OTC-' . str_pad($ot, 6, '0', STR_PAD_LEFT);
    $proyecto = trim((string)($body['proyecto'] ?? ''));
    if ($ot === '' || strlen($ot) > 30) throw new RuntimeException('Seleccione una OT válida.');
    if ($proyecto === '') throw new RuntimeException('Seleccione el proyecto de la OT.');
    $hoy = (new DateTimeImmutable('now', new DateTimeZone('America/Bogota')))->format('Y-m-d');
    // Bloqueo de archivo: se libera automáticamente al terminar la petición.
    // No depende de bloqueos de sesión de MySQL ni de su configuración.
    $lockPath = dirname(dirname(__DIR__)) . '/storage/fh_ing_ot_registro.lock';
    $registroLock = fopen($lockPath, 'c');
    if (!$registroLock) throw new RuntimeException('No se pudo preparar el guardado del registro.');
    $limite = microtime(true) + 10;
    while (!flock($registroLock, LOCK_EX | LOCK_NB)) {
        if (microtime(true) >= $limite) throw new RuntimeException('Hay otro registro en proceso. Intente guardar nuevamente.');
        usleep(100000);
    }
    // Migración aditiva para instalaciones existentes. No altera fechas históricas.
    $etapa = 'verificar_columna';
    $column = $db->query("SHOW COLUMNS FROM fh_ing_ot_detalle LIKE 'fecha_inicio'");
    if ($db->num_rows($column) === 0) {
        $etapa = 'agregar_fecha_inicio';
        $db->query("ALTER TABLE fh_ing_ot_detalle ADD COLUMN fecha_inicio DATE NULL DEFAULT NULL AFTER actualizado_al");
    }
    $ot_esc = $db->escape($ot);
    $proyecto_esc = $db->escape($proyecto);
    $etapa = 'buscar_registro';
    $existing = find_by_sql("SELECT id, fecha_inicio, created_at FROM fh_ing_ot_detalle WHERE ot='$ot_esc' ORDER BY id DESC LIMIT 1");
    if ($existing) {
        $estadoOt=find_by_sql("SELECT estado FROM fh_ing_planos_cab WHERE ot='$ot_esc' LIMIT 1");
        if((int)($estadoOt[0]['estado']??0)===3)throw new RuntimeException('La OT está cerrada y se consulta en OTs cerradas.');
        $id = (int)$existing[0]['id'];
        $inicio = $existing[0]['fecha_inicio'] ?: substr($existing[0]['created_at'], 0, 10);
        // Nunca se toman autor, inicio, revisión ni aprobación del navegador al actualizar.
        $etapa = 'actualizar';
        $db->query("UPDATE fh_ing_ot_detalle SET proyecto='$proyecto_esc', actualizado_al='$hoy', updated_at=NOW() WHERE id=$id");
        $message = 'Registro actualizado. Se conservaron el elaborador y la fecha de inicio originales.';
    } else {
        $inicio = trim((string)($body['fecha_inicio'] ?? $hoy));
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $inicio);
        if (!$dt || $dt->format('Y-m-d') !== $inicio) throw new RuntimeException('Fecha de inicio inválida.');
        $autor = $db->escape($nombre);
        $fi = $db->escape($inicio);
        // Compatibilidad con instalaciones cuyo id no tiene AUTO_INCREMENT.
        // El bloqueo de registro mantiene esta asignación serializada.
        $ultimo = find_by_sql("SELECT COALESCE(MAX(id), 0) AS ultimo_id FROM fh_ing_ot_detalle");
        $id = (int)($ultimo[0]['ultimo_id'] ?? 0) + 1;
        $etapa = 'insertar';
        $db->query("INSERT INTO fh_ing_ot_detalle
            (id, ot, proyecto, elaborado_por, revisado_por, aprobado_por, fecha_inicio, actualizado_al, created_by, created_at, updated_at)
            VALUES ($id, '$ot_esc', '$proyecto_esc', '$autor', '', '', '$fi', '$hoy', $user_id, NOW(), NOW())");

        $message = 'Registro guardado. El elaborador y la fecha de inicio quedaron fijos.';
    }
    flock($registroLock, LOCK_UN);
    fclose($registroLock);
    $registroLock = null;
    ob_end_clean();
    echo json_encode(['success'=>true, 'message'=>$message, 'id'=>$id, 'fecha_inicio'=>$inicio, 'actualizado_al'=>$hoy], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $diagnostico = ['fecha'=>date('c'), 'etapa'=>$etapa, 'tipo'=>get_class($e), 'codigo'=>$e->getCode(), 'mensaje'=>$e->getMessage()];
    $logPath = dirname(dirname(__DIR__)) . '/storage/fh_ing_ot_guardado_diagnostico.php';
    if (!file_exists($logPath)) @file_put_contents($logPath, "<?php exit; ?>\n", LOCK_EX);
    @file_put_contents($logPath, json_encode($diagnostico, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    if (is_resource($registroLock)) {
        flock($registroLock, LOCK_UN);
        fclose($registroLock);
    }
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}