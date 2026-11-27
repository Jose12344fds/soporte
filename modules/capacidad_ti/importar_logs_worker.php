<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__.'/importar_logs_lib.php';

$arguments = $argv ?? [];
if (($arguments[1] ?? '') === '--validate') {
    $path = (string)($arguments[2] ?? '');
    if ($path === '') { fwrite(STDERR, "Indique el archivo que desea validar.\n"); exit(2); }
    try {
        $parsed = cap_log_parse_file($path);
        $fileInfo = cap_log_filename_data(basename($path));
        echo json_encode([
            'archivo' => basename($path),
            'servidor' => $parsed['SERVIDOR'] ?? $parsed['VM'] ?? $parsed['NOMBRE_VM'] ?? $fileInfo['vm'],
            'ip' => $parsed['IP'] ?? $fileInfo['ip'] ?? '',
            'fecha' => cap_log_measurement_date($parsed, $fileInfo['date']),
            'campos' => $parsed,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
        exit(0);
    } catch (Throwable $error) {
        fwrite(STDERR, 'ERROR: '.$error->getMessage().PHP_EOL);
        exit(1);
    }
}

require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/bootstrap.php';
capacidad_ti_schema();
cap_log_schema();

$folder = __DIR__.DIRECTORY_SEPARATOR.'Log_Sistems';
if (!is_dir($folder) && !mkdir($folder, 0775, true) && !is_dir($folder)) {
    fwrite(STDERR, "No se pudo crear la carpeta Log_Sistems.\n");
    exit(1);
}

$lockPath = $folder.DIRECTORY_SEPARATOR.'.importador.lock';
$lock = fopen($lockPath, 'c+');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Ya existe otra importación en ejecución.\n");
    exit(3);
}

$files = [];
foreach (new DirectoryIterator($folder) as $entry) {
    if (!$entry->isFile() || strtolower($entry->getExtension()) !== 'txt') continue;
    if (preg_match('/-LEIDO\.TXT$/i', $entry->getFilename())) continue;
    $files[] = $entry->getPathname();
}
usort($files, static function (string $a, string $b): int {
    $aHasIp = preg_match('/_(?:\d{1,3}\.){3}\d{1,3}\.txt$/i', basename($a)) === 1;
    $bHasIp = preg_match('/_(?:\d{1,3}\.){3}\d{1,3}\.txt$/i', basename($b)) === 1;
    if ($aHasIp !== $bHasIp) return $aHasIp ? -1 : 1;
    return strnatcasecmp(basename($a), basename($b));
});

$errorStatePath = $folder.DIRECTORY_SEPARATOR.'.importador_errores_estado.json';
$errorState = [];
if (is_file($errorStatePath)) {
    $decodedState = json_decode((string)file_get_contents($errorStatePath), true);
    if (is_array($decodedState)) $errorState = $decodedState;
}

$processed = 0;
$failed = 0;
$measurements = 0;
foreach ($files as $path) {
    $filename = basename($path);
    try {
        $result = cap_log_import_file($path);
        $readPath = cap_log_read_name($path);
        if (file_exists($readPath)) throw new RuntimeException('Ya existe el archivo '.basename($readPath).'.');
        if (!rename($path, $readPath)) throw new RuntimeException('La base de datos se actualizó, pero no se pudo agregar -LEIDO al archivo.');
        unset($errorState[$filename]);
        $processed++;
        $measurements += (int)$result['measurements'];
        echo '[OK] '.$filename.' -> '.basename($readPath).' | '.$result['vm'].' | '.$result['measurements'].' mediciones'.($result['duplicate'] ? ' (ya importadas)' : '').PHP_EOL;
    } catch (Throwable $error) {
        $failed++;
        $errorText = $error->getMessage();
        $errorHash = hash('sha256', $errorText);
        $previous = $errorState[$filename] ?? null;
        $shouldLog = !is_array($previous) || ($previous['hash'] ?? '') !== $errorHash || (time() - (int)($previous['time'] ?? 0)) >= 86400;
        if ($shouldLog) {
            $message = '['.date('Y-m-d H:i:s').'] '.$filename.' | '.$errorText.PHP_EOL;
            file_put_contents($folder.DIRECTORY_SEPARATOR.'importador_errores.log', $message, FILE_APPEND | LOCK_EX);
        }
        $errorState[$filename] = ['hash' => $errorHash, 'time' => time()];
        fwrite(STDERR, '[ERROR] '.$filename.' | '.$errorText.PHP_EOL);
    }
}

file_put_contents($errorStatePath, json_encode($errorState, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
echo "Resumen: {$processed} archivo(s) leído(s), {$measurements} medición(es), {$failed} error(es).".PHP_EOL;
flock($lock, LOCK_UN);
fclose($lock);
exit($failed > 0 ? 1 : 0);
