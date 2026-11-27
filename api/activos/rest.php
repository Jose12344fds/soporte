<?php
/** Entrada HTTP por recurso; reutiliza los controladores y sus validaciones. */
function activos_rest(string $resource): void
{
    $methods = $resource === 'equipos'
        ? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']
        : (in_array($resource, ['inventario', 'renovaciones'], true) ? ['GET'] : ['GET', 'POST', 'PUT', 'PATCH']);
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, $methods, true)) {
        require_once dirname(__DIR__, 2) . '/includes/response.php';
        header('Allow: ' . implode(', ', $methods));
        api_json_response(['error' => 'Metodo no permitido.'], 405);
    }
    if ($method === 'GET') {
        $controller = $resource === 'equipos' && isset($_GET['id']) ? 'obtener' : 'listar';
    } elseif ($resource === 'equipos') {
        $controller = ['POST' => 'registrar', 'PUT' => 'actualizar', 'PATCH' => 'actualizar', 'DELETE' => 'eliminar'][$method];
    } else {
        $controller = 'guardar';
    }
    if ($method !== 'GET') {
        // Los controladores existentes comparten una ruta de escritura protegida
        // por sesion, permiso exacto y CSRF para todos los metodos de mutacion.
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
                parse_str(file_get_contents('php://input'), $input);
                $_POST = $input;
                if ($method === 'DELETE' && !isset($_POST['id']) && isset($_GET['id'])) $_POST['id'] = $_GET['id'];
            }
            $_SERVER['REQUEST_METHOD'] = 'POST';
        }
    }
    require __DIR__ . '/' . $resource . '/' . $controller . '.php';
}
