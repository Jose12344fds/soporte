<?php
require_once __DIR__ . '/settings.php';

/** Devuelve la ruta interna sin servidor ni carpeta de instalacion. */
function app_internal_route(string $url): ?string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if ($url[0] === '#' || $url[0] === '?') {
        return null;
    }
    $parts = parse_url($url);
    if ($parts === false || (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true))) {
        return null;
    }
    $path = $parts['path'] ?? '';
    $prefixes = array_unique([rtrim(BASE_URL, '/'), '/soporte']);
    usort($prefixes, static fn($left, $right) => strlen($right) <=> strlen($left));
    $matchedPrefix = false;
    foreach ($prefixes as $prefix) {
        if ($prefix !== '' && (strcasecmp($path, $prefix) === 0 || stripos($path, $prefix . '/') === 0)) {
            $path = substr($path, strlen($prefix));
            $matchedPrefix = true;
            break;
        }
    }
    if (!$matchedPrefix) {
        // Una URL externa conserva su destino. Las IP antiguas se reconocen por /soporte.
        if (isset($parts['host'])) {
            return null;
        }
        $internalPath = '~^/?(?:(?:modules|api|assets|public|Reportes|uploads|scripts)(?:/|$)|(?:home|index|login|logout|profile|media|users)\.php$)~i';
        if ($path !== '/' && !preg_match($internalPath, $path)) {
            return null;
        }
    }
    $route = ($path === '' || $path === '/') ? '' : '/' . ltrim($path, '/');
    if (isset($parts['query'])) {
        $route .= '?' . $parts['query'];
    }
    if (isset($parts['fragment'])) {
        $route .= '#' . $parts['fragment'];
    }
    return $route;
}

/** Resuelve rutas actuales y enlaces heredados usando la URL de settings.php. */
function app_url(string $url = ''): string
{
    $route = app_internal_route($url);
    return $route === null ? trim($url) : rtrim(APP_URL, '/') . $route;
}

/** Guarda las paginas internas como /modules/... para poder trasladar la base. */
function app_relative_url(string $url): string
{
    if (trim($url) === '') {
        return '';
    }
    $route = app_internal_route($url);
    return $route === null ? trim($url) : ($route === '' ? '/' : $route);
}
