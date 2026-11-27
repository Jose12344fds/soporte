<?php
ob_start();
require_once dirname(__DIR__, 2) . '/includes/response.php';

function activos_api(string|array $permission, string $method, callable $operation, bool $allowReports = false): void
{
    global $session, $db;
    try {
        $root = dirname(__DIR__, 2);
        require_once $root . '/includes/load.php';
        require_once $root . '/config/database.php';
        $db->query('SET NAMES utf8mb4');
        if (!$session->isUserLoggedIn()) {
            api_json_response(['error' => 'Sesion no valida.'], 401);
        }
        $allowed = array_filter((array)$permission, static fn($action) => tiene_permiso('activos', $action));
        if (!$allowed && !($allowReports && tiene_permiso('reportes', 'ver'))) {
            api_json_response(['error' => 'Sin permiso para realizar esta operacion.'], 403);
        }
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
            header('Allow: ' . $method);
            api_json_response(['error' => 'Metodo no permitido.'], 405);
        }
        $input = $_GET;
        if ($method === 'POST') {
            csrf_validate_or_fail();
            $input = $_POST;
            if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                $input = request_json_body();
                if (!is_array($input)) {
                    throw new InvalidArgumentException('El cuerpo JSON no es valido.');
                }
            }
        }
        api_json_response($operation(database_connection(), $input));
    } catch (InvalidArgumentException $error) {
        api_json_response(['error' => $error->getMessage()], 422);
    } catch (OutOfBoundsException $error) {
        api_json_response(['error' => $error->getMessage()], 404);
    } catch (DomainException $error) {
        api_json_response(['error' => $error->getMessage()], 409);
    } catch (PDOException $error) {
        error_log('Activos API: ' . $error->getMessage());
        if (str_starts_with($error->getCode(), '23')) {
            api_json_response(['error' => 'El registro tiene datos vinculados o entra en conflicto con otro registro.'], 409);
        }
        if ($error->getCode() === '40001') {
            api_json_response(['error' => 'Otro cambio se realizo al mismo tiempo. Vuelva a intentar la operacion.'], 409);
        }
        api_json_response(['error' => 'No se pudo completar la operacion de activos.'], 500);
    } catch (Throwable $error) {
        error_log('Activos API: ' . $error->getMessage());
        api_json_response(['error' => 'No se pudo completar la operacion de activos.'], 500);
    }
}
