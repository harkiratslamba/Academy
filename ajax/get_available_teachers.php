<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !in_array($_SESSION['role'], ['admin','coordinator'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$timetableId = intval($_GET['timetable_id'] ?? 0);
$date        = $_GET['date'] ?? date('Y-m-d');

if (!$timetableId) {
    echo json_encode(['error' => 'Invalid timetable ID']);
    exit;
}

// Get timetable details
$ttRow = $conn->query("
    SELECT t.period_number, t.teacher_id AS original_teacher_id,
           sub.subject_name, DAYNAME('$date') AS day_name
    FROM timetable t
    JOIN subjects sub ON t.subject_id = sub.id
    WHERE t.id = $timetableId
")->fetch_assoc();

if (!$ttRow) {
    echo json_encode(['error' => 'Timetable not found']);
    exit;
}

$period          = $ttRow['period_number'];
$dayName         = $ttRow['day_name'];
$originalTeacher = $ttRow['original_teacher_id'];

// Find available teachers:
// 1. Active
// 2. Not absent today
// 3. Not already teaching this period on this day
// 4. Not already assigned as substitute for this period on this date
$teachers = $conn->query("
    SELECT t.id, t.name, t.subject_specialization,
           (t.subject_specialization = (SELECT sub.subject_name FROM timetable tt JOIN subjects sub ON tt.subject_id = sub.id WHERE tt.id = $timetableId LIMIT 1)) AS same_subject,
           (SELECT COUNT(*) FROM timetable WHERE teacher_id = t.id AND is_active = 1) AS workload
    FROM teachers t
    WHERE t.status = 'active'
    AND t.id != $originalTeacher
    AND t.id NOT IN (
        SELECT teacher_id FROM attendance
        WHERE date = '$date' AND status IN ('absent','on_leave')
    )
    AND t.id NOT IN (
        SELECT teacher_id FROM timetable
        WHERE day = '$dayName' AND period_number = $period AND is_active = 1
    )
    AND t.id NOT IN (
        SELECT substitute_teacher_id FROM substitutions s
        JOIN timetable tt ON s.timetable_id = tt.id
        WHERE s.date = '$date' AND tt.period_number = $period AND s.status != 'cancelled'
    )
    ORDER BY same_subject DESC, workload ASC, t.name
")->fetch_all(MYSQLI_ASSOC);

echo json_encode(['teachers' => $teachers, 'period' => $period, 'day' => $dayName]);
