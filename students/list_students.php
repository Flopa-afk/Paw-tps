<?php
// list_students.php
require_once __DIR__ . '/db_connect.php';
$pdo = get_db_connection();

$students = [];
$queryError = '';
if ($pdo) {
  try {
    // detect whether a created_at column exists and build the SELECT accordingly
    $dbName = $pdo->query('select database()')->fetchColumn();
    $colStmt = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'students' AND COLUMN_NAME = 'created_at' LIMIT 1");
    $colStmt->execute([':db' => $dbName]);
    $hasCreated = (bool) $colStmt->fetchColumn();

    $select = 'SELECT id, fullname, matricule, group_id' . ($hasCreated ? ', created_at' : '') . ' FROM students ORDER BY id DESC';
    $stmt = $pdo->query($select);
    $students = $stmt->fetchAll();
  } catch (PDOException $e) {
    $queryError = 'Failed to read students from database. Check logs for details.';
    @file_put_contents(__DIR__ . '/logs/db_errors.log', '[' . date('c') . '] ' . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
  }
}

function e($s){return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Students</title>
  <style>body{font-family:Arial;margin:18px}table{border-collapse:collapse;width:100%;max-width:900px}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f4f4f4}</style>
</head>
<body>
  <h1>Students</h1>
  <p><a href="add_student.php">Add student</a> | <a href="take_attendance.php">Take attendance</a> | <a href="tp3.html">Attendance UI</a></p>

  <?php if (!$pdo): ?>
    <div style="color:red">Database connection failed. Check `test_db_connection.php` and `logs/db_errors.log`.</div>
  <?php else: ?>
    <?php if (empty($students)): ?>
      <p>No students found.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>ID</th><th>Full name</th><th>Matricule</th><th>Group</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($students as $s): ?>
            <tr>
              <td><?php echo e($s['id']); ?></td>
              <td><?php echo e($s['fullname']); ?></td>
              <td><?php echo e($s['matricule']); ?></td>
              <td><?php echo e($s['group_id']); ?></td>
              <td><?php echo e($s['created_at']); ?></td>
              <td>
                <a href="update_student.php?id=<?php echo e($s['id']); ?>">Edit</a> |
                <a href="delete_student.php?id=<?php echo e($s['id']); ?>" onclick="return confirm('Delete this student?');">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  <?php endif; ?>
</body>
</html>
