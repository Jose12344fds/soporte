<?php
/**
 * Autodesk APS (Platform Services) — credenciales para el Viewer de planos DWG.
 *
 * Registro en https://aps.autodesk.com → My Apps → Create Application
 * Scopes requeridos: data:read, data:write, data:create, bucket:create, bucket:read
 */

// ─── Credenciales de la aplicación ───────────────────────────────────────────
defined('APS_CLIENT_ID')     || define('APS_CLIENT_ID',     'sOVFQGil4SXyLDiQqTXG5Aa2aIzmBj4Hv5feS0T3CLYiaA4s');
defined('APS_CLIENT_SECRET') || define('APS_CLIENT_SECRET', '1vjg3kBGI4KGWFuZ2FNkwwsAPDr0W3xFVnOPMI8OYBC9SAXM4BAwFRYV1wQTnhmT');

// ─── Bucket de almacenamiento (globalmente único en APS) ─────────────────────
// Solo minúsculas, números y guiones. 3-128 caracteres.
defined('APS_BUCKET_KEY')    || define('APS_BUCKET_KEY',    'tama-soporte-planos');

// ─── Región del bucket (US o EMEA) ───────────────────────────────────────────
defined('APS_REGION')        || define('APS_REGION',        'US');
