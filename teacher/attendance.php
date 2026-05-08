<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('teacher', '../index.php');

$teacherId  = intval($_SESSION['teacher_id']);
$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');

$attendance = $conn->query("
    SELECT * FROM attendance
    WHERE teacher_id = $teacherId
    AND date BETWEEN '$monthStart' AND '$monthEnd'
    ORDER BY date DESC
")->fetch_all(MYSQLI_ASSOC);

$present   = count(array_filter($attendance, fn($a) => $a['status'] === 'present'));
$absent    = count(array_filter($attendance, fn($a) => $a['status'] === 'absent'));
$late      = count(array_filter($attendance, fn($a) => $a['status'] === 'late'));
$onLeave   = count(array_filter($attendance, fn($a) => $a['status'] === 'on_leave'));
$totalDays = count($attendance);
$pct       = $totalDays ? round(($present / $totalDays) * 100) : 0;

$pageTitle = 'My Attendance';
require_once '../includes/header.php';
require_once '../includes/teacher_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">My Attendance</h4>
            <small class="text-muted"><?= date('F Y') ?></small>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-success"><?= $present ?></div>
                    <div class="text-muted small">Present</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-danger"><?= $absent ?></div>
                    <div class="text-muted small">Absent</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-warning"><?= $late ?></div>
                    <div class="text-muted small">Late</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-primary"><?= $pct ?>%</div>
                    <div class="text-muted small">Attendance</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-1">
                <span class="small fw-semibold">Monthly Attendance</span>
                <span class="small text-<?= $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger') ?> fw-bold"><?= $pct ?>%</span>
            </div>
            <div class="progress" style="height:10px">
                <div class="progress-bar bg-<?= $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger') ?>"
                     style="width:<?= $pct ?>%"></div>
            </div>
            <?php if ($pct < 75): ?>
            <small class="text-danger mt-1 d-block"><i class="fas fa-exclamation-triangle me-1"></i>Attendance below 75%!</small>
            <?php endif; ?>
        </div>
    </div>

    <!-- Attendance Records -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table id="attTable" class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>Day</th><th>Status</th><th>Check In</th><th>Notes</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $a): ?>
                    <tr>
                        <td><?= formatDate($a['date']) ?></td>
                        <td><?= date('l', strtotime($a['date'])) ?></td>
                        <td><?= getStatusBadge($a['status']) ?></td>
                        <td><?= $a['check_in'] ? formatTime($a['check_in']) : '—' ?></td>
                        <td class="small text-muted"><?= sanitize($a['notes'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($attendance)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No attendance records for this month</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
