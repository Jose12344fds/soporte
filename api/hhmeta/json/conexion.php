<?php
/**
 * Conexión PDO a MySQL - Base de datos tama (PCP)
 * Usado por los endpoints JSON de hhmeta_cons_api.php
 */

function getConexionLocal() {
    $host = 'localhost';
    $db   = 'tama';
    $user = 'root';
    $pass = '';

    // Intentar con password, si falla intentar sin password
    $opciones = [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
    ];

    try {
        return new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, $opciones);
    } catch (PDOException $e1) {
        // Si falla con password, intentar sin password (XAMPP default)
        try {
            return new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, '', $opciones);
        } catch (PDOException $e2) {
            // También intentar con 127.0.0.1
            try {
                return new PDO("mysql:host=127.0.0.1;dbname={$db};charset=utf8mb4", $user, '', $opciones);
            } catch (PDOException $e3) {
                throw new PDOException(
                    "No se pudo conectar a MySQL.\n" .
                    "Intento 1 (localhost+pass): " . $e1->getMessage() . "\n" .
                    "Intento 2 (localhost): " . $e2->getMessage() . "\n" .
                    "Intento 3 (127.0.0.1): " . $e3->getMessage()
                );
            }
        }
    }
}
