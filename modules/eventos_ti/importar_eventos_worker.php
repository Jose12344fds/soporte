<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/importar_eventos_lib.php';
evt_schema();

$folder = __DIR__.DIRECTORY_SEPARATOR.'Log_Eventos';
if (!is_dir($folder) && !mkdir($folder, 0775, true) && !is_dir($folder)) {
    fwrite(STDERR, "No se pudo crear Log_Eventos.\n"); exit(1);
}
$lock = fopen($folder.DIRECTORY_SEPARATOR.'.importador.lock', 'c+');
if ($lock === false || !flock($lock, LOCK_EX|LOCK_NB)) { fwrite(STDERR, "Otra importación está en ejecución.\n"); exit(3); }

$files = [];
foreach (new DirectoryIterator($folder) as $entry) {
    if (!$entry->isFile()) continue;
    $extension = strtolower($entry->getExtension());
    if (in_array($extension, ['json', 'txt'], true) && !preg_match('/-LEIDO\.(?:JSON|TXT)$/i', $entry->getFilename())) {
        $files[] = $entry->getPathname();
    }
}
natcasesort($files);
$errorStatePath = $folder.DIRECTORY_SEPARATOR.'.importador_errores_estado.json';
$errorState = [];
if (is_file($errorStatePath)) {
    $decoded = json_decode((string)file_get_contents($errorStatePath), true);
    if (is_array($decoded)) $errorState = $decoded;
}
$processed = 0; $inserted = 0; $duplicates = 0; $failed = 0;
foreach ($files as $path) {
    $filename = basename($path);
    try {
        $result = evt_log_import($path);
        $target = evt_log_read_name($path);
        if (file_exists($target)) throw new RuntimeException('Ya existe '.basename($target).'.');
        if (!rename($path, $target)) throw new RuntimeException('Se importó el archivo, pero no se pudo agregar -LEIDO.');
        unset($errorState[$filename]);
        $processed++; $inserted += $result['inserted']; $duplicates += $result['duplicates'];
        echo '[OK] '.basename($path).' | '.$result['inserted'].' nuevos | '.$result['duplicates'].' duplicados'.PHP_EOL;
    } catch (Throwable $error) {
        $failed++;
        $errorText = $error->getMessage();
        $errorHash = hash('sha256', $errorText);
        $previous = $errorState[$filename] ?? null;
        if (!is_array($previous) || ($previous['hash'] ?? '') !== $errorHash || time()-(int)($previous['time'] ?? 0) >= 86400) {
            $line = '['.date('Y-m-d H:i:s').'] '.$filename.' | '.$errorText.PHP_EOL;
            file_put_contents($folder.DIRECTORY_SEPARATOR.'importador_errores.log', $line, FILE_APPEND|LOCK_EX);
        }
        $errorState[$filename] = ['hash'=>$errorHash,'time'=>time()];
        fwrite(STDERR, '[ERROR] '.$filename.' | '.$errorText.PHP_EOL);
    }
}
file_put_contents($errorStatePath, json_encode($errorState, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);
echo "Resumen: {$processed} archivo(s), {$inserted} nuevo(s), {$duplicates} duplicado(s), {$failed} error(es).".PHP_EOL;
flock($lock, LOCK_UN); fclose($lock); exit($failed ? 1 : 0);
