<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('teacher', '../index.php');

$teacherId = intval($_SESSION['teacher_id']);
$message   = $error = '';
$today     = getTodayDate();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $date   = $_POST['date'] ?? $today;
        $status = in_array($_POST['status'] ?? '', ['available','unavailable','on_leave']) ? $_POST['status'] : 'available';
        $reason = sanitize($_POST['reason'] ?? '');
        $period = intval($_POST['period_number'] ?? 0) ?: null;

        $stmt = $conn->prepare(
            'INSERT INTO teacher_availability (teacher_id, date, period_number, status, reason)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE status=VALUES(status), reason=VALUES(reason)'
        );
        $stmt->bind_param('iiiss', $teacherId, $date, $period, $status, $reason);
        $stmt->execute();
        $stmt->close();

        logActivity($conn, 'UPDATE_AVAILABILITY', "Set status to $status for $date period $period");
        $message = 'Availability updated successfully!';
    } elseif ($action === 'day_unavailable') {
        $date   = $_POST['date'] ?? $today;
        $reason = sanitize($_POST['reason'] ?? '');
        $status = sanitize($_POST['status'] ?? 'unavailable');

        // Mark all periods unavailable for the day
        $stmt = $conn->prepare(
            'INSERT INTO teacher_availability (teacher_id, date, period_number, status, reason)
             VALUES (?,?,NULL,?,?)
             ON DUPLICATE KEY UPDATE status=VALUES(status), reason=VALUES(reason)'
        );
        $stmt->bind_param('iiss', $teacherId, $date, $status, $reason);
        $stmt->execute();
        $stmt->close();

        logActivity($conn, 'UPDATE_AVAILABILITY', "Marked full day $status for $date");
        $message = 'Full day availability updated!';
    }
}

// Fetch my timetable periods
$periods = $conn->query("
    SELECT DISTINCT period_number, start_time, end_time
    FROM timetable WHERE teacher_id = $teacherId AND is_active = 1
    ORDER BY period_number
")->fetch_all(MYSQLI_ASSOC);

// This week's availability
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd   = date('Y-m-d', strtotime('sunday this week'));

$myAvailability = $conn->query("
    SELECT * FROM teacher_availability
    WHERE teacher_id = $teacherId AND date BETWEEN '$weekStart' AND '$weekEnd'
    ORDER BY date, period_number
")->fetch_all(MYSQLI_ASSOC);

$avMap = [];
foreach ($myAvailability as $av) {
    $avMap[$av['date']][$av['period_number'] ?? 'day'] = $av;
}

$pageTitle = 'My Availability';
require_once '../includes/header.php';
require_once '../includes/teacher_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">My Availability</h4>
    </div>

    <?php if ($message): ?><div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-1"></i><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <div class="row g-4">
        <!-- Mark availability form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="fw-bold mb-0"><i class="fas fa-edit me-2 text-primary"></i>Update Availability</h6>
                </div>
                <div class="card-body">
                    <!-- Full day -->
                    <div class="mb-4 p-3 bg-light rounded">
                        <h6 class="fw-semibold mb-3">Full Day Status</h6>
                        <form method="POST">
                            <input type="hidden" name="action" value="day_unavailable">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Date *</label>
                                <input type="date" name="date" class="form-control form-control-sm"
                                       value="<?= $today ?>" min="<?= $today ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Status *</label>
                                <select name="status" class="form-select form-select-sm" required>
                                    <option value="unavailable">Unavailable</option>
                                    <option value="on_leave">On Leave</option>
                                    <option value="available">Available</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Reason</label>
                                <input type="text" name="reason" class="form-control form-control-sm"
                                       placeholder="Optional reason...">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-calendar-times me-1"></i>Mark Full Day
                            </button>
                        </form>
                    </div>

                    <!-- Period-specific -->
                    <h6 class="fw-semibold mb-3">Period-specific Availability</h6>
                    <form method="POST">
                        <input type="hidden" name="action" value="update">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Date *</label>
                            <input type="date" name="date" class="form-control form-control-sm"
                                   value="<?= $today ?>" min="<?= $today ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Period</label>
                            <select name="period_number" class="form-select form-select-sm">
                                <option value="">All periods</option>
                                <?php foreach ($periods as $p): ?>
                                <option value="<?= $p['period_number'] ?>">
                                    Period <?= $p['period_number'] ?> (<?= formatTime($p['start_time']) ?> - <?= formatTime($p['end_time']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Status *</label>
                            <select name="status" class="form-select form-select-sm" required>
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Reason</label>
                            <input type="text" name="reason" class="form-control form-control-sm"
                                   placeholder="Optional reason...">
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100">
                            <i class="fas fa-save me-1"></i>Save Period Availability
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- This week's availability -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-calendar-week me-2 text-success"></i>
                        This Week's Availability
                        <small class="text-muted">(<?= date('d M', strtotime($weekStart)) ?> - <?= date('d M', strtotime($weekEnd)) ?>)</small>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($myAvailability)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p>No unavailability marked – you're available all week!</p>
                    </div>
                    <?php else: ?>
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Period</th><th>Status</th><th>Reason</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myAvailability as $av): ?>
                            <tr>
                                <td><?= formatDate($av['date']) ?></td>
                                <td><?= $av['period_number'] ? 'P' . $av['period_number'] : 'Full Day' ?></td>
                                <td><?= getStatusBadge($av['status']) ?></td>
                                <td class="small text-muted"><?= sanitize($av['reason'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
