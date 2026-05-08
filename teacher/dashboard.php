<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('teacher', '../index.php');

$teacherId = intval($_SESSION['teacher_id']);
$today     = getTodayDate();
$todayDay  = getTodayDay();

// Teacher info
$teacher = $conn->query("SELECT * FROM teachers WHERE id = $teacherId")->fetch_assoc();

// Today's timetable for this teacher
$myToday = $conn->query("
    SELECT t.period_number, t.start_time, t.end_time, t.id AS timetable_id,
           c.class_name, s.section_name, sub.subject_name, r.room_number,
           (SELECT COUNT(*) FROM substitutions
            WHERE original_teacher_id = $teacherId AND timetable_id = t.id
            AND date = '$today' AND status != 'cancelled') AS has_sub,
           (SELECT name FROM teachers WHERE id = (
               SELECT substitute_teacher_id FROM substitutions
               WHERE original_teacher_id = $teacherId AND timetable_id = t.id
               AND date = '$today' AND status != 'cancelled' LIMIT 1
           )) AS sub_name
    FROM timetable t
    JOIN classes c ON t.class_id = c.id
    LEFT JOIN sections s ON t.section_id = s.id
    JOIN subjects sub ON t.subject_id = sub.id
    LEFT JOIN rooms r ON t.room_id = r.id
    WHERE t.teacher_id = $teacherId AND t.day = '$todayDay' AND t.is_active = 1
    ORDER BY t.period_number
")->fetch_all(MYSQLI_ASSOC);

// Substitute classes assigned to me today
$mySubClasses = $conn->query("
    SELECT sub.period_number, sub.start_time, sub.end_time,
           c.class_name, s.section_name, su.subject_name, r.room_number,
           ot.name AS original_teacher
    FROM substitutions subs
    JOIN timetable sub ON subs.timetable_id = sub.id
    JOIN classes c ON sub.class_id = c.id
    LEFT JOIN sections s ON sub.section_id = s.id
    JOIN subjects su ON sub.subject_id = su.id
    LEFT JOIN rooms r ON sub.room_id = r.id
    JOIN teachers ot ON subs.original_teacher_id = ot.id
    WHERE subs.substitute_teacher_id = $teacherId
    AND subs.date = '$today' AND subs.status = 'assigned'
    ORDER BY sub.period_number
")->fetch_all(MYSQLI_ASSOC);

// Attendance status today
$myAttendance = $conn->query("
    SELECT status FROM attendance WHERE teacher_id = $teacherId AND date = '$today'
")->fetch_assoc();

// Pending leave requests
$pendingLeaves = $conn->query("
    SELECT COUNT(*) FROM leaves WHERE teacher_id = $teacherId AND status = 'pending'
")->fetch_row()[0];

// Total periods this week
$weekStart   = date('Y-m-d', strtotime('monday this week'));
$weekEnd     = date('Y-m-d', strtotime('sunday this week'));
$weeklyPeriods = $conn->query("
    SELECT COUNT(*) FROM timetable WHERE teacher_id = $teacherId AND is_active = 1
")->fetch_row()[0];

$pageTitle = 'My Dashboard';
require_once '../includes/header.php';
require_once '../includes/teacher_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Welcome, <?= sanitize($teacher['name'] ?? 'Teacher') ?>!</h4>
            <small class="text-muted"><?= date('l, d F Y') ?> | <?= sanitize($teacher['subject_specialization'] ?? '') ?></small>
        </div>
        <?php if ($myAttendance): ?>
        <span class="badge bg-<?= $myAttendance['status'] === 'present' ? 'success' : 'danger' ?> fs-6">
            Today: <?= ucfirst($myAttendance['status']) ?>
        </span>
        <?php else: ?>
        <span class="badge bg-secondary fs-6">Attendance not marked</span>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm stat-card text-center">
                <div class="card-body">
                    <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                    <div class="fs-3 fw-bold"><?= count($myToday) ?></div>
                    <div class="text-muted small">Periods Today</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm stat-card text-center">
                <div class="card-body">
                    <i class="fas fa-exchange-alt fa-2x text-warning mb-2"></i>
                    <div class="fs-3 fw-bold"><?= count($mySubClasses) ?></div>
                    <div class="text-muted small">Sub Classes Today</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm stat-card text-center">
                <div class="card-body">
                    <i class="fas fa-calendar-week fa-2x text-success mb-2"></i>
                    <div class="fs-3 fw-bold"><?= $weeklyPeriods ?></div>
                    <div class="text-muted small">Weekly Periods</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm stat-card text-center">
                <div class="card-body">
                    <i class="fas fa-calendar-times fa-2x text-info mb-2"></i>
                    <div class="fs-3 fw-bold"><?= $pendingLeaves ?></div>
                    <div class="text-muted small">Pending Leaves</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Today's Schedule -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="fas fa-calendar-day me-2 text-primary"></i>Today's Schedule</h6>
                    <a href="timetable.php" class="btn btn-sm btn-outline-primary">Full Timetable</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($myToday)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-coffee fa-2x mb-2"></i>
                        <p class="mb-0">No periods scheduled for <?= $todayDay ?>!</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($myToday as $period): ?>
                        <div class="list-group-item px-3 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-center bg-primary-subtle rounded p-2" style="min-width:50px">
                                    <div class="fw-bold text-primary">P<?= $period['period_number'] ?></div>
                                    <div class="text-muted" style="font-size:10px"><?= formatTime($period['start_time']) ?></div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold"><?= sanitize($period['subject_name']) ?></div>
                                    <div class="text-muted small">
                                        <?= sanitize($period['class_name']) ?> <?= sanitize($period['section_name'] ?? '') ?>
                                        <?= $period['room_number'] ? '| Room ' . sanitize($period['room_number']) : '' ?>
                                        | <?= formatTime($period['start_time']) ?> - <?= formatTime($period['end_time']) ?>
                                    </div>
                                </div>
                                <?php if ($period['has_sub']): ?>
                                <span class="badge bg-warning text-dark">Substituted by <?= sanitize($period['sub_name'] ?? '') ?></span>
                                <?php else: ?>
                                <span class="badge bg-success">Your Class</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Substitute classes -->
            <?php if (!empty($mySubClasses)): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-warning bg-opacity-25 border-0">
                    <h6 class="fw-bold mb-0"><i class="fas fa-exchange-alt text-warning me-2"></i>Extra Duties Today</h6>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($mySubClasses as $sc): ?>
                    <div class="list-group-item px-3 py-2">
                        <div class="d-flex gap-3">
                            <div class="text-center bg-warning-subtle rounded p-2" style="min-width:50px">
                                <div class="fw-bold text-warning">P<?= $sc['period_number'] ?></div>
                            </div>
                            <div>
                                <div class="fw-semibold"><?= sanitize($sc['subject_name']) ?></div>
                                <div class="text-muted small">
                                    <?= sanitize($sc['class_name']) ?> <?= sanitize($sc['section_name'] ?? '') ?> |
                                    <?= formatTime($sc['start_time']) ?> - <?= formatTime($sc['end_time']) ?>
                                </div>
                                <div class="text-danger small">Covering for: <?= sanitize($sc['original_teacher']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="fw-bold mb-0"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="availability.php" class="btn btn-outline-success">
                            <i class="fas fa-clock me-2"></i>Update My Availability
                        </a>
                        <a href="leaves.php" class="btn btn-outline-info">
                            <i class="fas fa-calendar-times me-2"></i>Apply for Leave
                        </a>
                        <a href="timetable.php" class="btn btn-outline-primary">
                            <i class="fas fa-calendar-alt me-2"></i>View Full Timetable
                        </a>
                        <a href="attendance.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user-check me-2"></i>My Attendance Record
                        </a>
                    </div>
                </div>
            </div>

            <!-- Teacher Info Card -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0">
                    <h6 class="fw-bold mb-0"><i class="fas fa-id-card me-2 text-primary"></i>My Profile</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted small">Employee ID</td>
                            <td class="fw-semibold"><?= sanitize($teacher['employee_id'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Specialization</td>
                            <td><?= sanitize($teacher['subject_specialization'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Qualification</td>
                            <td><?= sanitize($teacher['qualification'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Email</td>
                            <td class="small"><?= sanitize($teacher['email'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Phone</td>
                            <td><?= sanitize($teacher['phone'] ?? '—') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
