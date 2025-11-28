<?php
// db_connect.php
// Provides a helper function get_db_connection() which returns a PDO instance or null on failure.

$config = require __DIR__ . '/config.php';

function get_db_connection()
{
    global $config;

    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/db_errors.log';

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);

    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log error (do not expose details to users)
        $msg = sprintf("[%s] DB Connection failed: %s in %s:%d\n", date('c'), $e->getMessage(), $e->getFile(), $e->getLine());
        @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
        return null;
    }
}
