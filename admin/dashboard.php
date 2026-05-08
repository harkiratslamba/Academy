<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAnyRole(['admin', 'coordinator'], '../index.php');

$today    = getTodayDate();
$todayDay = getTodayDay();

// Dashboard stats
$totalTeachers     = $conn->query("SELECT COUNT(*) FROM teachers WHERE status = 'active'")->fetch_row()[0];
$totalClasses      = $conn->query("SELECT COUNT(*) FROM classes")->fetch_row()[0];
$absentToday       = $conn->query("SELECT COUNT(*) FROM attendance WHERE date = '$today' AND status IN ('absent','on_leave')")->fetch_row()[0];
$pendingLeaves     = $conn->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetch_row()[0];

// Free teachers today = active - absent today
$busyToday  = $conn->query("SELECT COUNT(DISTINCT teacher_id) FROM timetable WHERE day = '$todayDay' AND is_active = 1")->fetch_row()[0];
$freeToday  = $totalTeachers - $absentToday;

// Today substitutions
$todaySubs  = $conn->query("SELECT COUNT(*) FROM substitutions WHERE date = '$today' AND status = 'assigned'")->fetch_row()[0];

// Unassigned classes today (absent teacher, no substitute)
$unassigned = $conn->query("
    SELECT t.*, c.class_name, s.section_name, sub.subject_name, te.name AS teacher_name
    FROM timetable t
    JOIN classes c ON t.class_id = c.id
    LEFT JOIN sections s ON t.section_id = s.id
    JOIN subjects sub ON t.subject_id = sub.id
    JOIN teachers te ON t.teacher_id = te.id
    WHERE t.day = '$todayDay' AND t.is_active = 1
    AND t.teacher_id IN (
        SELECT teacher_id FROM attendance WHERE date = '$today' AND status IN ('absent','on_leave')
    )
    AND t.id NOT IN (
        SELECT timetable_id FROM substitutions WHERE date = '$today' AND status != 'cancelled'
    )
    ORDER BY t.period_number
")->fetch_all(MYSQLI_ASSOC);

// Today timetable overview
$todayTimetable = $conn->query("
    SELECT t.period_number, t.start_time, t.end_time,
           c.class_name, s.section_name, sub.subject_name,
           te.name AS teacher_name, r.room_number,
           (SELECT COUNT(*) FROM attendance WHERE teacher_id = t.teacher_id AND date = '$today' AND status IN ('absent','on_leave')) AS is_absent,
           (SELECT COUNT(*) FROM substitutions WHERE timetable_id = t.id AND date = '$today' AND status != 'cancelled') AS has_sub
    FROM timetable t
    JOIN classes c ON t.class_id = c.id
    LEFT JOIN sections s ON t.section_id = s.id
    JOIN subjects sub ON t.subject_id = sub.id
    JOIN teachers te ON t.teacher_id = te.id
    LEFT JOIN rooms r ON t.room_id = r.id
    WHERE t.day = '$todayDay' AND t.is_active = 1
    ORDER BY t.period_number, c.class_name
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

// Recent substitutions
$recentSubs = $conn->query("
    SELECT s.*, ot.name AS original_teacher, st.name AS sub_teacher,
           t.period_number, t.start_time, c.class_name, sec.section_name, sub.subject_name
    FROM substitutions s
    JOIN teachers ot ON s.original_teacher_id = ot.id
    JOIN teachers st ON s.substitute_teacher_id = st.id
    JOIN timetable t ON s.timetable_id = t.id
    JOIN classes c ON t.class_id = c.id
    LEFT JOIN sections sec ON t.section_id = sec.id
    JOIN subjects sub ON t.subject_id = sub.id
    ORDER BY s.created_at DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Dashboard';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<div class="p-4">
    <!-- Page title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Admin Dashboard</h4>
            <small class="text-muted"><i class="fas fa-calendar me-1"></i><?= date('l, d F Y') ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="substitution.php" class="btn btn-primary btn-sm">
                <i class="fas fa-exchange-alt me-1"></i>Manage Substitutions
            </a>
            <a href="attendance.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-user-check me-1"></i>Mark Attendance
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Total Teachers</div>
                            <div class="fs-3 fw-bold text-primary"><?= $totalTeachers ?></div>
                        </div>
                        <div class="stat-icon bg-primary-subtle">
                            <i class="fas fa-chalkboard-teacher text-primary fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Total Classes</div>
                            <div class="fs-3 fw-bold text-success"><?= $totalClasses ?></div>
                        </div>
                        <div class="stat-icon bg-success-subtle">
                            <i class="fas fa-school text-success fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Absent Today</div>
                            <div class="fs-3 fw-bold text-danger"><?= $absentToday ?></div>
                        </div>
                        <div class="stat-icon bg-danger-subtle">
                            <i class="fas fa-user-times text-danger fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Today Substitutions</div>
                            <div class="fs-3 fw-bold text-warning"><?= $todaySubs ?></div>
                        </div>
                        <div class="stat-icon bg-warning-subtle">
                            <i class="fas fa-exchange-alt text-warning fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert: Unassigned classes -->
    <?php if (!empty($unassigned)): ?>
    <div class="alert alert-danger alert-dismissible mb-4">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-exclamation-triangle fa-lg"></i>
            <div>
                <strong><?= count($unassigned) ?> class(es) have no teacher today!</strong>
                <a href="substitution.php" class="ms-2 btn btn-danger btn-sm py-0">Assign Substitutes</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Today's Timetable -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="fas fa-calendar-day me-2 text-primary"></i>Today's Timetable</h6>
                    <a href="timetable.php" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Period</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($todayTimetable)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No classes scheduled for today</td></tr>
                                <?php else: ?>
                                    <?php foreach ($todayTimetable as $tt): ?>
                                    <tr class="<?= $tt['is_absent'] && !$tt['has_sub'] ? 'table-danger' : ($tt['is_absent'] && $tt['has_sub'] ? 'table-warning' : '') ?>">
                                        <td>
                                            <strong>P<?= $tt['period_number'] ?></strong>
                                            <div class="text-muted" style="font-size:11px"><?= formatTime($tt['start_time']) ?></div>
                                        </td>
                                        <td><?= sanitize($tt['class_name']) ?> <?= sanitize($tt['section_name'] ?? '') ?></td>
                                        <td><?= sanitize($tt['subject_name']) ?></td>
                                        <td><?= sanitize($tt['teacher_name']) ?></td>
                                        <td>
                                            <?php if ($tt['is_absent'] && $tt['has_sub']): ?>
                                                <span class="badge bg-warning text-dark">Substituted</span>
                                            <?php elseif ($tt['is_absent']): ?>
                                                <span class="badge bg-danger">No Teacher</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Substitutions -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="fas fa-exchange-alt me-2 text-warning"></i>Recent Substitutions</h6>
                    <a href="substitution.php" class="btn btn-sm btn-outline-warning">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($recentSubs)): ?>
                        <div class="text-center text-muted py-4 small">No substitutions yet</div>
                        <?php else: ?>
                            <?php foreach ($recentSubs as $sub): ?>
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between">
                                    <div class="small">
                                        <span class="fw-semibold text-danger"><?= sanitize($sub['original_teacher']) ?></span>
                                        <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                        <span class="fw-semibold text-success"><?= sanitize($sub['sub_teacher']) ?></span>
                                    </div>
                                    <?= getStatusBadge($sub['status']) ?>
                                </div>
                                <div class="text-muted" style="font-size:11px">
                                    <?= sanitize($sub['class_name']) ?> <?= sanitize($sub['section_name'] ?? '') ?> |
                                    <?= sanitize($sub['subject_name']) ?> | P<?= $sub['period_number'] ?> |
                                    <?= formatDate($sub['date']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-info mb-2"></i>
                    <div class="fs-4 fw-bold"><?= $freeToday ?></div>
                    <div class="text-muted small">Available Teachers</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-times fa-2x text-warning mb-2"></i>
                    <div class="fs-4 fw-bold"><?= $pendingLeaves ?></div>
                    <div class="text-muted small">Pending Leave Requests</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                    <div class="fs-4 fw-bold"><?= count($unassigned) ?></div>
                    <div class="text-muted small">Unassigned Classes</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
