<?php
// close_session.php
// Receives: session_id (POST or GET)
// Marks session status as 'closed' and sets closed_at timestamp

require_once __DIR__ . '/db_connect.php';
$pdo = get_db_connection();

header('Content-Type: application/json; charset=utf-8');

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : (isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0);

if ($sessionId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid session_id']);
    exit;
}

try {
    // Ensure table exists
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

    $stmt = $pdo->prepare('UPDATE sessions SET status = :status, closed_at = NOW() WHERE id = :id');
    $stmt->execute([':status' => 'closed', ':id' => $sessionId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Session not found or already closed']);
        exit;
    }

    echo json_encode(['status' => 'closed', 'session_id' => $sessionId]);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    @file_put_contents(__DIR__ . '/logs/db_errors.log', '['.date('c').'] close_session error: '.$e->getMessage()."\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['error' => 'Failed to close session']);
    exit;
}

?>
