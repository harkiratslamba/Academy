<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('teacher', '../index.php');

$teacherId = intval($_SESSION['teacher_id']);
$days      = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

// Full weekly timetable
$myTimetable = $conn->query("
    SELECT t.*, c.class_name, s.section_name, sub.subject_name, r.room_number
    FROM timetable t
    JOIN classes c ON t.class_id = c.id
    LEFT JOIN sections s ON t.section_id = s.id
    JOIN subjects sub ON t.subject_id = sub.id
    LEFT JOIN rooms r ON t.room_id = r.id
    WHERE t.teacher_id = $teacherId AND t.is_active = 1
    ORDER BY FIELD(t.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.period_number
")->fetch_all(MYSQLI_ASSOC);

// Group by day
$byDay = [];
foreach ($myTimetable as $tt) {
    $byDay[$tt['day']][] = $tt;
}

// Max periods for grid
$maxPeriods = 0;
foreach ($myTimetable as $tt) {
    $maxPeriods = max($maxPeriods, $tt['period_number']);
}
$maxPeriods = max($maxPeriods, 8);

// Indexed for grid: [$day][$period]
$grid = [];
foreach ($myTimetable as $tt) {
    $grid[$tt['day']][$tt['period_number']] = $tt;
}

$pageTitle = 'My Timetable';
require_once '../includes/header.php';
require_once '../includes/teacher_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">My Timetable</h4>
        <div class="d-flex gap-2">
            <span class="badge bg-success p-2">Total Periods: <?= count($myTimetable) ?></span>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i>Print
            </button>
        </div>
    </div>

    <!-- Weekly Grid View -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0">
            <h6 class="fw-bold mb-0"><i class="fas fa-th me-2 text-primary"></i>Weekly Grid</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width:80px">Period</th>
                            <?php foreach ($days as $d): ?>
                            <th class="text-center <?= $d === getTodayDay() ? 'bg-primary' : '' ?>"><?= $d ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($p = 1; $p <= $maxPeriods; $p++): ?>
                        <tr>
                            <td class="text-center fw-bold bg-light">P<?= $p ?></td>
                            <?php foreach ($days as $d): ?>
                            <td class="<?= $d === getTodayDay() ? 'table-primary' : '' ?>">
                                <?php if (isset($grid[$d][$p])): ?>
                                <?php $tt = $grid[$d][$p]; ?>
                                <div class="p-1">
                                    <div class="fw-semibold small text-primary"><?= sanitize($tt['subject_name']) ?></div>
                                    <div class="text-muted" style="font-size:11px">
                                        <?= sanitize($tt['class_name']) ?> <?= sanitize($tt['section_name'] ?? '') ?>
                                    </div>
                                    <?php if ($tt['room_number']): ?>
                                    <div class="text-muted" style="font-size:11px">
                                        <i class="fas fa-door-open"></i> <?= sanitize($tt['room_number']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="text-muted" style="font-size:10px">
                                        <?= formatTime($tt['start_time']) ?> - <?= formatTime($tt['end_time']) ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="text-center text-muted" style="font-size:12px">—</div>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- List View -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0">
            <h6 class="fw-bold mb-0"><i class="fas fa-list me-2 text-success"></i>Detailed List</h6>
        </div>
        <div class="card-body p-0">
            <table id="ttList" class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Day</th><th>Period</th><th>Time</th>
                        <th>Class</th><th>Subject</th><th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myTimetable as $tt): ?>
                    <tr class="<?= $tt['day'] === getTodayDay() ? 'table-primary' : '' ?>">
                        <td><span class="badge bg-primary"><?= $tt['day'] ?></span></td>
                        <td class="fw-bold">P<?= $tt['period_number'] ?></td>
                        <td class="small"><?= formatTime($tt['start_time']) ?> - <?= formatTime($tt['end_time']) ?></td>
                        <td><?= sanitize($tt['class_name']) ?> <?= sanitize($tt['section_name'] ?? '') ?></td>
                        <td><?= sanitize($tt['subject_name']) ?></td>
                        <td><?= sanitize($tt['room_number'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
