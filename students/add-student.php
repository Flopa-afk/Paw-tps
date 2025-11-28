<?php
// add-student.php (DB-backed)
require_once __DIR__ . '/db_connect.php';

$pdo = get_db_connection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $matricule = trim($_POST['matricule'] ?? '');
    $group_id = trim($_POST['group_id'] ?? '');

    $errors = [];
    if ($fullname === '') $errors[] = 'Full name is required.';
    if ($matricule === '') $errors[] = 'Matricule is required.';

    if (empty($errors)) {
        if (!$pdo) {
            $message = '<div style="color:red">Database connection not available.</div>';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO students (fullname, matricule, group_id) VALUES (:fullname, :matricule, :group_id)');
                $stmt->execute([':fullname' => $fullname, ':matricule' => $matricule, ':group_id' => $group_id ?: null]);
                header('Location: list_students.php');
                exit;
            } catch (PDOException $e) {
                // Unique matricule constraint or other DB error
                $message = '<div style="color:red">Failed to add student: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else {
        $message = '<div style="color:red">' . implode('<br>', array_map('htmlspecialchars', $errors)) . '</div>';
    }
}

function e($s){return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Add Student (DB)</title>
  <style>body{font-family:Arial;margin:18px}label{display:block;margin:8px 0}input{padding:8px;width:320px}</style>
</head>
<body>
  <h1>Add Student</h1>
  <?php echo $message; ?>
  <form method="post" action="<?php echo e($_SERVER['PHP_SELF']); ?>">
    <label>Full name<br><input type="text" name="fullname" value="<?php echo e($_POST['fullname'] ?? ''); ?>"></label>
    <label>Matricule<br><input type="text" name="matricule" value="<?php echo e($_POST['matricule'] ?? ''); ?>"></label>
    <label>Group ID<br><input type="text" name="group_id" value="<?php echo e($_POST['group_id'] ?? ''); ?>"></label>
    <p><button type="submit">Add student</button> <a href="list_students.php">Back to list</a></p>
  </form>
</body>
</html>
