<?php
/**
 * Diagnóstico de ver_plano.php — BORRAR DESPUÉS DE USAR
 * Acceder: http://192.168.4.8:8080/Soporte/api/fh_ing/diag_ver_plano.php
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once dirname(dirname(__DIR__)) . '/config/app.php';

echo "=== DIAGNÓSTICO ver_plano ===\n\n";

// --- BASE_PATH / open_basedir ---
echo "BASE_PATH       : " . BASE_PATH . "\n";
echo "open_basedir    : " . (ini_get('open_basedir') ?: '(no set)') . "\n";
echo "DIRECTORY_SEP   : " . DIRECTORY_SEPARATOR . "\n\n";

// --- Uploads ---
$uploadsDir = rtrim(BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'FH_ING' . DIRECTORY_SEPARATOR . 'planos', '/\\');
echo "uploads_dir     : $uploadsDir\n";
echo "is_dir          : " . (is_dir($uploadsDir) ? 'SÍ' : 'NO') . "\n\n";

echo "Archivos en uploads:\n";
foreach ((@scandir($uploadsDir) ?: []) as $f) {
    if ($f === '.' || $f === '..') continue;
    $p = $uploadsDir . DIRECTORY_SEPARATOR . $f;
    echo "  $f (" . filesize($p) . " bytes)\n";
}

// --- Python via exec() ---
echo "\n--- Python via exec() ---\n";
echo "getcwd()        : " . getcwd() . "\n";

// cmd.exe falla si el CWD es una ruta UNC → cambiar a C:\ antes de exec()
$prevDir = getcwd();
@chdir('C:\\');
echo "getcwd() post-chdir: " . getcwd() . "\n";

$knownPaths = ['C:\\Python313\\python.exe', 'C:\\python313\\python.exe',
               'C:\\Python312\\python.exe', 'C:\\python312\\python.exe'];
$pythonExe = null;
foreach ($knownPaths as $p) {
    $out = []; $exit = 1;
    // SIN comillas externas: "C:\Python313\python.exe" → cmd.exe doble-quoting bug
    exec($p . ' --version 2>&1', $out, $exit);
    $result = implode(' ', $out);
    echo "  exec($p --version) → exit=$exit  output=$result\n";
    if ($exit === 0 && $pythonExe === null) $pythonExe = $p;
}
@chdir($prevDir);
echo "Python seleccionado: " . ($pythonExe ?? 'NINGUNO') . "\n";

// --- Test ezdxf ---
echo "\n--- Test ezdxf via exec() ---\n";
if ($pythonExe) {
    $ezdxfPath = 'C:\\Users\\Jose_Sovero\\AppData\\Roaming\\Python\\Python313\\site-packages';
    putenv('PYTHONPATH=' . $ezdxfPath);
    $cmd = '"' . $pythonExe . '" -c "import ezdxf; print(\'OK\', ezdxf.__version__, ezdxf.__file__)" 2>&1';
    $out2 = []; $exit2 = 1;
    exec($cmd, $out2, $exit2);
    echo "  exit=$exit2\n";
    echo "  output=" . implode("\n", $out2) . "\n";

    // Si falló, probar con sys.path en el código
    if ($exit2 !== 0) {
        $cmd3 = '"' . $pythonExe . '" -c "import sys; sys.path.insert(0,\'' . addslashes($ezdxfPath) . '\'); import ezdxf; print(\'OK\', ezdxf.__version__)" 2>&1';
        $out3 = []; exec($cmd3, $out3);
        echo "  con sys.path: " . implode(' ', $out3) . "\n";
    }
} else {
    echo "  [Python no encontrado, skip]\n";
}

// --- BD ---
echo "\n--- BD: pln_dtll_Ing (últimos 10) ---\n";
$rows = find_by_sql("SELECT id_planos, arch_plano, arch_original FROM pln_dtll_Ing ORDER BY id_planos DESC LIMIT 10");
foreach ($rows as $r) {
    $saved = basename($r['arch_plano'] ?? '');
    $ext   = strtolower(pathinfo($saved, PATHINFO_EXTENSION));
    $dxfSibling = pathinfo($saved, PATHINFO_FILENAME) . '.dxf';
    $dxfPath    = $uploadsDir . DIRECTORY_SEPARATOR . $dxfSibling;
    $hasDxf = is_file($dxfPath) ? 'DXF OK ('.filesize($dxfPath).'b)' : 'SIN DXF';
    echo "  id={$r['id_planos']} ext=$ext saved=$saved → $hasDxf\n";
}

// --- Render de prueba ---
echo "\n--- Render de prueba (primer DXF disponible) ---\n";
if ($pythonExe) {
    $dxfTest = null;
    foreach ((@scandir($uploadsDir) ?: []) as $f) {
        if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'dxf' && $f !== 'test.dxf') {
            $dxfTest = $uploadsDir . DIRECTORY_SEPARATOR . $f;
            break;
        }
    }
    if ($dxfTest) {
        echo "  Archivo: $dxfTest\n";
        $script = BASE_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'dwg_to_svg.py';
        $ezdxfPath = 'C:\\Users\\Jose_Sovero\\AppData\\Roaming\\Python\\Python313\\site-packages';
        putenv('PYTHONPATH=' . $ezdxfPath);
        // Sin comillas externas al exe + chdir a C:\ para evitar doble-quoting de cmd.exe
        $prevDir2 = getcwd();
        @chdir('C:\\');
        $cmd4 = $pythonExe . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($dxfTest) . ' 2>&1';
        echo "  CMD: $cmd4\n";
        echo "  [iniciando conversión DXF→SVG, puede tardar 1-2 min...]\n";
        flush();
        $out4 = []; $exit4 = 1;
        exec($cmd4, $out4, $exit4);
        @chdir($prevDir2);
        $firstLine = trim($out4[0] ?? '');
        echo "  exit=$exit4\n";
        echo "  primera línea: " . substr($firstLine, 0, 120) . "\n";
        echo "  total líneas: " . count($out4) . "\n";
        if (strpos($firstLine, '<svg') !== false || strpos($firstLine, "<?xml") !== false) {
            echo "  → SVG GENERADO CORRECTAMENTE ✓\n";
        } else {
            echo "  → ERROR — detalles:\n";
            foreach (array_slice($out4, 0, 5) as $l) echo "    $l\n";
        }
    } else {
        echo "  No hay DXF disponible\n";
    }
}

echo "\n=== FIN DIAGNÓSTICO ===\n";


require_once dirname(dirname(__DIR__)) . '/includes/load.php';
require_once dirname(dirname(__DIR__)) . '/config/app.php';

echo "=== DIAGNÓSTICO ver_plano ===\n\n";

// --- BASE_PATH ---
echo "BASE_PATH     : " . BASE_PATH . "\n";
echo "DIRECTORY_SEP : " . DIRECTORY_SEPARATOR . "\n\n";

// --- Directorio de uploads ---
$uploadsDir = rtrim(
    BASE_PATH . DIRECTORY_SEPARATOR . 'uploads'
              . DIRECTORY_SEPARATOR . 'FH_ING'
              . DIRECTORY_SEPARATOR . 'planos',
    '/\\'
);
echo "uploads_dir   : $uploadsDir\n";
echo "is_dir        : " . (is_dir($uploadsDir) ? 'SÍ' : 'NO') . "\n\n";

// --- Listar archivos en uploads ---
echo "Archivos en uploads:\n";
if (is_dir($uploadsDir)) {
    $files = @scandir($uploadsDir) ?: [];
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = $uploadsDir . DIRECTORY_SEPARATOR . $f;
        echo "  " . $f . " (" . filesize($path) . " bytes)\n";
    }
} else {
    echo "  [NO SE PUEDE LEER EL DIRECTORIO]\n";
}

// --- Python ---
echo "\n--- Python ---\n";
$knownPaths = [
    'C:\\python313\\python.exe',
    'C:\\python312\\python.exe',
    'C:\\python311\\python.exe',
];
$pythonExe = null;
foreach ($knownPaths as $p) {
    if (is_file($p)) {
        $pythonExe = $p;
        echo "Python encontrado: $p\n";
        break;
    }
}
if ($pythonExe === null) {
    $out = []; exec('python --version 2>&1', $out);
    echo "python en PATH: " . implode(' ', $out) . "\n";
    exec('where python 2>&1', $out2);
    echo "where python: " . implode(' ', $out2) . "\n";
} else {
    $out = []; exec('"' . $pythonExe . '" --version 2>&1', $out);
    echo "Versión: " . implode(' ', $out) . "\n";
}

// --- Test ezdxf desde PHP exec ---
echo "\n--- Test ezdxf via exec ---\n";
if ($pythonExe) {
    $cmd = '"' . $pythonExe . '" -c "import sys; sys.path.insert(0,r\'C:\\\\Users\\\\Jose_Sovero\\\\AppData\\\\Roaming\\\\Python\\\\Python313\\\\site-packages\'); import ezdxf; print(\'ezdxf OK:\', ezdxf.__version__, ezdxf.__file__)" 2>&1';
    echo "CMD: $cmd\n";
    $out2 = []; $exit = 0;
    exec($cmd, $out2, $exit);
    echo "Exit: $exit\n";
    echo "Output: " . implode("\n", $out2) . "\n";
}

// --- dwg_to_svg.py ---
echo "\n--- Script dwg_to_svg.py ---\n";
$script = BASE_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'dwg_to_svg.py';
echo "Script: $script\n";
echo "is_file: " . (is_file($script) ? 'SÍ' : 'NO') . "\n";

// --- BD: registros pln_dtll_Ing ---
echo "\n--- BD: pln_dtll_Ing (últimos 10) ---\n";
$rows = find_by_sql("SELECT id_planos, arch_plano, arch_original FROM pln_dtll_Ing ORDER BY id_planos DESC LIMIT 10");
foreach ($rows as $r) {
    $saved = basename($r['arch_plano'] ?? '');
    $ext   = strtolower(pathinfo($saved, PATHINFO_EXTENSION));
    $dxfSibling = pathinfo($saved, PATHINFO_FILENAME) . '.dxf';
    $dxfPath    = $uploadsDir . DIRECTORY_SEPARATOR . $dxfSibling;
    $hasDxf = is_file($dxfPath) ? 'DXF OK ('.filesize($dxfPath).'b)' : 'SIN DXF';
    echo "  id={$r['id_planos']} ext=$ext saved=$saved → $hasDxf\n";
}

// --- Test exec: sys.path de Apache ---
echo "\n--- sys.path de Apache Python ---\n";
if ($pythonExe) {
    $cmd3 = '"' . $pythonExe . '" -c "import sys; print(chr(10).join(sys.path))" 2>&1';
    $out3 = []; exec($cmd3, $out3);
    foreach ($out3 as $p) echo "  $p\n";
}

echo "\n=== FIN DIAGNÓSTICO ===\n";
