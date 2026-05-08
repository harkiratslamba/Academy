<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = intval($_SESSION['user_id']);
$count  = getUnreadNotificationCount($conn, $userId);
$items  = getRecentNotifications($conn, $userId, 5);

foreach ($items as &$n) {
    $n['time_ago'] = timeAgo($n['created_at']);
}

echo json_encode(['count' => $count, 'notifications' => $items]);

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
