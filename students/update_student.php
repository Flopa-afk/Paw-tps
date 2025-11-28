<?php
// update_student.php
require_once __DIR__ . '/db_connect.php';
$pdo = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if ($id <= 0) {
    die('Invalid student id.');
}

// load student
$student = null;
if ($pdo) {
    $stmt = $pdo->prepare('SELECT id, fullname, matricule, group_id FROM students WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    $student = $stmt->fetch();
}

if (!$student) {
    die('Student not found or DB connection failed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $matricule = trim($_POST['matricule'] ?? '');
    $group_id = trim($_POST['group_id'] ?? '');

    $errors = [];
    if ($fullname === '') $errors[] = 'Full name is required.';
    if ($matricule === '') $errors[] = 'Matricule is required.';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE students SET fullname = :fullname, matricule = :matricule, group_id = :group_id WHERE id = :id');
            $stmt->execute([':fullname'=>$fullname, ':matricule'=>$matricule, ':group_id'=>$group_id ?: null, ':id'=>$id]);
            header('Location: list_students.php');
            exit;
        } catch (PDOException $e) {
            $message = '<div style="color:red">Failed to update student: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
  <title>Edit Student</title>
</head>
<body>
  <h1>Edit Student</h1>
  <?php echo $message; ?>
  <form method="post">
    <label>Full name<br><input type="text" name="fullname" value="<?php echo e($student['fullname']); ?>"></label><br>
    <label>Matricule<br><input type="text" name="matricule" value="<?php echo e($student['matricule']); ?>"></label><br>
    <label>Group<br><input type="text" name="group_id" value="<?php echo e($student['group_id']); ?>"></label><br>
    <p><button type="submit">Save</button> <a href="list_students.php">Cancel</a></p>
  </form>
</body>
</html>
