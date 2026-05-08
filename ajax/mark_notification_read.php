<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id     = intval($_POST['id'] ?? 0);
$userId = intval($_SESSION['user_id']);

if ($id) {
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $stmt->close();
} else {
    // Mark all read
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $userId");
}

echo json_encode(['success' => true]);
