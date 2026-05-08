<?php
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin($redirectTo = '../index.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

function requireRole($role, $redirectTo = '../index.php') {
    requireLogin($redirectTo);
    if ($_SESSION['role'] !== $role) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

function requireAnyRole(array $roles, $redirectTo = '../index.php') {
    requireLogin($redirectTo);
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

function logActivity($conn, $action, $details = '') {
    $userId = $_SESSION['user_id'] ?? null;
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt   = $conn->prepare(
        'INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?,?,?,?)'
    );
    $stmt->bind_param('isss', $userId, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

function sendNotification($conn, $userId, $title, $message, $type = 'info') {
    $stmt = $conn->prepare(
        'INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)'
    );
    $stmt->bind_param('isss', $userId, $title, $message, $type);
    $stmt->execute();
    $stmt->close();
}

function getUnreadNotificationCount($conn, $userId) {
    $stmt = $conn->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

function getRecentNotifications($conn, $userId, $limit = 5) {
    $stmt = $conn->prepare(
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
    );
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notifications;
}

function getTodayDay() {
    return date('l'); // Monday, Tuesday, etc.
}

function getTodayDate() {
    return date('Y-m-d');
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function getStatusBadge($status) {
    $map = [
        'active'      => 'success',
        'inactive'    => 'secondary',
        'present'     => 'success',
        'absent'      => 'danger',
        'late'        => 'warning',
        'on_leave'    => 'info',
        'pending'     => 'warning',
        'approved'    => 'success',
        'rejected'    => 'danger',
        'assigned'    => 'primary',
        'completed'   => 'success',
        'cancelled'   => 'secondary',
        'available'   => 'success',
        'unavailable' => 'danger',
    ];
    $color = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

function isTeacherAbsentToday($conn, $teacherId) {
    $today = getTodayDate();
    $stmt  = $conn->prepare(
        "SELECT id FROM attendance WHERE teacher_id = ? AND date = ? AND status IN ('absent','on_leave')"
    );
    $stmt->bind_param('is', $teacherId, $today);
    $stmt->execute();
    $stmt->store_result();
    $found = $stmt->num_rows > 0;
    $stmt->close();
    return $found;
}

function isTeacherBusyOnPeriod($conn, $teacherId, $day, $period) {
    $stmt = $conn->prepare(
        'SELECT id FROM timetable WHERE teacher_id = ? AND day = ? AND period_number = ? AND is_active = 1'
    );
    $stmt->bind_param('isi', $teacherId, $day, $period);
    $stmt->execute();
    $stmt->store_result();
    $busy = $stmt->num_rows > 0;
    $stmt->close();
    return $busy;
}

function hasSubstituteAssigned($conn, $timetableId, $date) {
    $stmt = $conn->prepare(
        "SELECT id FROM substitutions WHERE timetable_id = ? AND date = ? AND status NOT IN ('cancelled')"
    );
    $stmt->bind_param('is', $timetableId, $date);
    $stmt->execute();
    $stmt->store_result();
    $found = $stmt->num_rows > 0;
    $stmt->close();
    return $found;
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
