<?php
// insert_sessions_test.php
// Simple script to insert 2-3 sessions directly using PDO and print resulting IDs.
require_once __DIR__ . '/db_connect.php';
$pdo = get_db_connection();

if (!$pdo) {
    echo "DB connection failed.\n";
    exit(1);
}

try {
    // ensure table exists (same schema as create_session)
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      course VARCHAR(255) NOT NULL,
      group_id VARCHAR(100) NOT NULL,
      professor_id VARCHAR(100) NOT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'open',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      closed_at TIMESTAMP NULL DEFAULT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $samples = [
        ['course' => 'Math 101', 'group_id' => 'G1', 'professor_id' => 'prof01'],
        ['course' => 'Physics 201', 'group_id' => 'G2', 'professor_id' => 'prof02'],
        ['course' => 'Chemistry 301', 'group_id' => 'G1', 'professor_id' => 'prof03'],
    ];

    $stmt = $pdo->prepare('INSERT INTO sessions (course, group_id, professor_id, status) VALUES (:course, :group_id, :professor_id, :status)');
    foreach ($samples as $s) {
        $stmt->execute([':course'=>$s['course'], ':group_id'=>$s['group_id'], ':professor_id'=>$s['professor_id'], ':status'=>'open']);
        $id = $pdo->lastInsertId();
        echo "Inserted session id: $id (course={$s['course']}, group={$s['group_id']}, prof={$s['professor_id']})\n";
    }
    echo "Done.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    @file_put_contents(__DIR__ . '/logs/db_errors.log', '['.date('c').'] insert_sessions_test error: '.$e->getMessage()."\n", FILE_APPEND | LOCK_EX);
}

?>
