<?php
// create_session.php
// Receives: course, group_id, professor_id (POST or GET)
// Creates sessions table if missing, inserts a new session and returns JSON { session_id: int }

require_once __DIR__ . '/db_connect.php';
$pdo = get_db_connection();

header('Content-Type: application/json; charset=utf-8');

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$course = trim($_POST['course'] ?? $_GET['course'] ?? '');
$group_id = trim($_POST['group_id'] ?? $_GET['group_id'] ?? '');
$professor_id = trim($_POST['professor_id'] ?? $_GET['professor_id'] ?? '');

if ($course === '' || $group_id === '' || $professor_id === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: course, group_id, professor_id']);
    exit;
}

// Create table if it doesn't exist
$createSql = "CREATE TABLE IF NOT EXISTS sessions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course VARCHAR(255) NOT NULL,
  group_id VARCHAR(100) NOT NULL,
  professor_id VARCHAR(100) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($createSql);
    $stmt = $pdo->prepare('INSERT INTO sessions (course, group_id, professor_id, status) VALUES (:course, :group_id, :professor_id, :status)');
    $stmt->execute([':course'=>$course, ':group_id'=>$group_id, ':professor_id'=>$professor_id, ':status'=>'open']);
    $sessionId = (int)$pdo->lastInsertId();
    echo json_encode(['session_id' => $sessionId]);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    @file_put_contents(__DIR__ . '/logs/db_errors.log', '['.date('c').'] create_session error: '.$e->getMessage()."\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['error' => 'Failed to create session']);
    exit;
}

?>
