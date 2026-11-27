<?php
// Compatibilidad para enlaces de exportacion ya publicados.
require_once __DIR__ . '/../config/app.php';
header('Location: ' . rtrim(BASE_URL, '/') . '/api/activos/aplicaciones/exportar.php', true, 302);
exit;
