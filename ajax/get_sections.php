<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$classId = intval($_GET['class_id'] ?? 0);

$sections = [];
if ($classId) {
    $sections = $conn->query("
        SELECT id, section_name FROM sections WHERE class_id = $classId ORDER BY section_name
    ")->fetch_all(MYSQLI_ASSOC);
}

echo json_encode(['sections' => $sections]);
