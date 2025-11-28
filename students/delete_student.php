<?php
// delete_student.php
require_once __DIR__ . '/db_connect.php';
$pdo = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('Invalid id');
}

if (!$pdo) {
    die('DB connection not available.');
}

try {
    $stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
    $stmt->execute([':id' => $id]);
    header('Location: list_students.php');
    exit;
} catch (PDOException $e) {
    echo 'Failed to delete student: ' . htmlspecialchars($e->getMessage());
}
