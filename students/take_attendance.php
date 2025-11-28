<?php
// take_attendance.php
// Load students and record attendance for today

$students = [];
$attendanceMessage = '';
$todayFile = __DIR__ . DIRECTORY_SEPARATOR . 'attendance_' . date('Y-m-d') . '.json';
$alreadyTaken = false;

// Load students from students.json
$studentsFile = __DIR__ . DIRECTORY_SEPARATOR . 'students.json';
if (file_exists($studentsFile)) {
    $json = @file_get_contents($studentsFile);
    if ($json !== false) {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $students = $decoded;
        }
    }
}

// Check if attendance already taken for today
if (file_exists($todayFile)) {
    $alreadyTaken = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyTaken) {
    $attendance = [];
    
    foreach ($students as $student) {
        $studentId = $student['student_id'] ?? '';
        $fieldName = 'status_' . htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8');
        $status = isset($_POST[$fieldName]) ? trim($_POST[$fieldName]) : 'absent';
        
        // Only accept 'present' or 'absent'
        if ($status !== 'present' && $status !== 'absent') {
            $status = 'absent';
        }
        
        $attendance[] = [
            'student_id' => $studentId,
            'status' => $status
        ];
    }
    
    // Save to file
    $written = @file_put_contents($todayFile, json_encode($attendance, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($written === false) {
        $attendanceMessage = '<div class="error">Failed to save attendance. Check file permissions.</div>';
    } else {
        $attendanceMessage = '<div class="success">Attendance recorded successfully for ' . date('Y-m-d') . '.</div>';
    }
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Take Attendance</title>
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      margin: 20px;
      background: #f6f8fb;
    }
    
    .container {
      max-width: 600px;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    h1 {
      color: #333;
      margin-bottom: 10px;
    }
    
    .date-info {
      color: #666;
      font-size: 0.95em;
      margin-bottom: 20px;
      font-style: italic;
    }
    
    .error {
      color: #a94442;
      background: #f2dede;
      border: 1px solid #ebccd1;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 15px;
    }
    
    .success {
      color: #155724;
      background: #d4edda;
      border: 1px solid #c3e6cb;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 15px;
    }
    
    .warning {
      color: #856404;
      background: #fff3cd;
      border: 1px solid #ffeaa7;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 15px;
    }
    
    .student-item {
      display: flex;
      align-items: center;
      padding: 12px;
      border-bottom: 1px solid #eee;
    }
    
    .student-item:last-child {
      border-bottom: none;
    }
    
    .student-name {
      flex: 1;
      font-weight: 600;
      color: #333;
    }
    
    .student-id {
      font-size: 0.85em;
      color: #999;
      margin-right: 15px;
    }
    
    .status-buttons {
      display: flex;
      gap: 8px;
    }
    
    .status-buttons label {
      display: flex;
      align-items: center;
      gap: 5px;
      font-weight: normal;
      cursor: pointer;
    }
    
    .status-buttons input[type="radio"] {
      cursor: pointer;
    }
    
    .btn-submit {
      background: #2563eb;
      color: #fff;
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      font-size: 1em;
      margin-top: 20px;
      width: 100%;
    }
    
    .btn-submit:hover {
      background: #1d4ed8;
    }
    
    .btn-submit:active {
      transform: translateY(1px);
    }
    
    .btn-submit:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
    
    .students-list {
      background: #f9f9f9;
      border-radius: 6px;
      margin-bottom: 15px;
      border: 1px solid #e9ecef;
    }
    
    .no-students {
      text-align: center;
      padding: 20px;
      color: #999;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Take Attendance</h1>
    <div class="date-info">Date: <?php echo e(date('Y-m-d (l)')); ?></div>
    
    <?php if ($alreadyTaken): ?>
      <div class="warning">
        Attendance for today has already been taken.
      </div>
      <p><a href="take_attendance.php" style="color: #2563eb; text-decoration: none;">Go back</a></p>
    <?php else: ?>
      <?php if (!empty($attendanceMessage)): ?>
        <?php echo $attendanceMessage; ?>
      <?php endif; ?>
      
      <?php if (empty($students)): ?>
        <div class="no-students">
          No students found. Please add students first.
        </div>
      <?php else: ?>
        <form method="post" action="<?php echo e($_SERVER['PHP_SELF']); ?>">
          <div class="students-list">
            <?php foreach ($students as $student): ?>
              <div class="student-item">
                <div class="student-id"><?php echo e($student['student_id']); ?></div>
                <div class="student-name"><?php echo e($student['name']); ?></div>
                <div class="status-buttons">
                  <?php $fieldName = 'status_' . $student['student_id']; ?>
                  <label>
                    <input type="radio" name="<?php echo e($fieldName); ?>" value="present" checked />
                    Present
                  </label>
                  <label>
                    <input type="radio" name="<?php echo e($fieldName); ?>" value="absent" />
                    Absent
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="submit" class="btn-submit">Submit Attendance</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
