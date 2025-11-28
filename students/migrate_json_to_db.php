<?php
// migrate_json_to_db.php
// Reads students.json and inserts into the `students` table if matricule not already present.

require_once __DIR__ . '/db_connect.php';
$pdo = get_db_connection();

if (!$pdo) {
    echo "Database connection failed. Check config and logs/db_errors.log\n";
    exit(1);
}

$jsonFile = __DIR__ . DIRECTORY_SEPARATOR . 'students.json';
if (!file_exists($jsonFile)) {
    echo "students.json not found\n";
    exit(1);
}

$json = @file_get_contents($jsonFile);
if ($json === false) {
    echo "Failed to read students.json\n";
    exit(1);
}

$data = json_decode($json, true);
if (!is_array($data)) {
    echo "students.json does not contain a valid array\n";
    exit(1);
}

$inserted = 0;
$skipped = 0;
$errors = 0;

try {
    $pdo->beginTransaction();

    // detect whether the students table has a created_at column (user may have created table manually)
    $hasCreatedAt = false;
    try {
        $dbName = $pdo->query('select database()')->fetchColumn();
        $colStmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'students' AND COLUMN_NAME = 'created_at'");
        $colStmt->execute([':db' => $dbName]);
        $hasCreatedAt = (bool) $colStmt->fetchColumn();
    } catch (Exception $e) {
        // If detection fails, fall back to safe behavior (do not use created_at)
        $hasCreatedAt = false;
    }

    $checkStmt = $pdo->prepare('SELECT id FROM students WHERE matricule = :matricule LIMIT 1');
    if ($hasCreatedAt) {
        $insertSql = 'INSERT INTO students (fullname, matricule, group_id, created_at) VALUES (:fullname, :matricule, :group_id, :created_at)';
    } else {
        $insertSql = 'INSERT INTO students (fullname, matricule, group_id) VALUES (:fullname, :matricule, :group_id)';
    }
    $insertStmt = $pdo->prepare($insertSql);

    foreach ($data as $entry) {
        $matricule = isset($entry['student_id']) ? (string)$entry['student_id'] : '';
        $fullname = isset($entry['name']) ? (string)$entry['name'] : '';
        $group = isset($entry['group']) ? (string)$entry['group'] : null;
        $created_at = isset($entry['created_at']) ? (string)$entry['created_at'] : null;

        if ($matricule === '' || $fullname === '') {
            $skipped++;
            continue;
        }

        // normalize matricule
        $matricule = trim($matricule);

        $checkStmt->execute([':matricule' => $matricule]);
        $found = $checkStmt->fetchColumn();
        if ($found) {
            $skipped++;
            continue;
        }

        // Use created_at if valid timestamp string and if the table supports it
        $createdParam = null;
        if ($created_at) {
            $dt = date_create($created_at);
            if ($dt) {
                $createdParam = $dt->format('Y-m-d H:i:s');
            }
        }

        try {
            if ($hasCreatedAt) {
                $insertStmt->execute([':fullname' => $fullname, ':matricule' => $matricule, ':group_id' => $group, ':created_at' => $createdParam]);
            } else {
                $insertStmt->execute([':fullname' => $fullname, ':matricule' => $matricule, ':group_id' => $group]);
            }
            $inserted++;
        } catch (PDOException $e) {
            // log and count
            @file_put_contents(__DIR__ . '/logs/migration_errors.log', '[' . date('c') . '] ' . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
            $errors++;
        }
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Migration complete. Inserted: $inserted, Skipped: $skipped, Errors: $errors\n";
exit(0);
