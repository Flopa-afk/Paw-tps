<?php
// test_db_connection.php
// Simple test script to verify the DB connection

require __DIR__ . '/db_connect.php';

$pdo = get_db_connection();

if ($pdo instanceof PDO) {
    echo "Connection successful";
} else {
    echo "Connection failed";
}

// Optional: check logs/log file for details if it failed
if (!is_null($pdo) && file_exists(__DIR__ . '/logs/db_errors.log')) {
    // nothing — success
} elseif (file_exists(__DIR__ . '/logs/db_errors.log')) {
    echo "\nCheck logs/db_errors.log for details.";
}
