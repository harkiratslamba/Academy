<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAnyRole(['admin', 'coordinator'], '../index.php');

$today      = getTodayDate();
$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');

// Attendance report
$attendanceReport = $conn->query("
    SELECT t.name, t.subject_specialization,
           SUM(a.status = 'present') AS present_days,
           SUM(a.status = 'absent')  AS absent_days,
           SUM(a.status = 'late')    AS late_days,
           SUM(a.status = 'on_leave') AS leave_days,
           COUNT(a.id) AS total_marked
    FROM teachers t
    LEFT JOIN attendance a ON t.id = a.teacher_id
        AND a.date BETWEEN '$monthStart' AND '$monthEnd'
    WHERE t.status = 'active'
    GROUP BY t.id
    ORDER BY t.name
")->fetch_all(MYSQLI_ASSOC);

// Substitution report this month
$subReport = $conn->query("
    SELECT t.name AS teacher_name,
           COUNT(CASE WHEN s.original_teacher_id = t.id THEN 1 END) AS times_absent,
           COUNT(CASE WHEN s.substitute_teacher_id = t.id THEN 1 END) AS times_substituted
    FROM teachers t
    LEFT JOIN substitutions s ON (s.original_teacher_id = t.id OR s.substitute_teacher_id = t.id)
        AND s.date BETWEEN '$monthStart' AND '$monthEnd'
        AND s.status = 'assigned'
    WHERE t.status = 'active'
    GROUP BY t.id
    HAVING (times_absent > 0 OR times_substituted > 0)
    ORDER BY times_absent DESC
")->fetch_all(MYSQLI_ASSOC);

// Leave summary
$leaveReport = $conn->query("
    SELECT t.name,
           SUM(l.status='approved') AS approved,
           SUM(l.status='pending')  AS pending,
           SUM(l.status='rejected') AS rejected
    FROM teachers t
    LEFT JOIN leaves l ON t.id = l.teacher_id
    WHERE t.status='active'
    GROUP BY t.id
    ORDER BY approved DESC
")->fetch_all(MYSQLI_ASSOC);

// Timetable coverage
$coverageReport = $conn->query("
    SELECT c.class_name, s.section_name,
           COUNT(t.id) AS total_periods,
           SUM(t.is_active) AS active_periods
    FROM classes c
    LEFT JOIN sections s ON c.id = s.class_id
    LEFT JOIN timetable t ON c.id = t.class_id AND (s.id = t.section_id OR t.section_id IS NULL)
    GROUP BY c.id, s.id
    ORDER BY c.class_name, s.section_name
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Reports';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Reports</h4>
            <small class="text-muted">Month: <?= date('F Y') ?></small>
        </div>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="fas fa-print me-1"></i>Print Report
        </button>
    </div>

    <!-- Attendance Report -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0">
            <h6 class="fw-bold mb-0"><i class="fas fa-user-check text-primary me-2"></i>Monthly Attendance Summary</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="attReportTable" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Teacher</th><th>Specialization</th>
                            <th class="text-success">Present</th>
                            <th class="text-danger">Absent</th>
                            <th class="text-warning">Late</th>
                            <th class="text-info">Leave</th>
                            <th>Total Marked</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceReport as $ar): ?>
                        <?php
                        $total = $ar['total_marked'] ?: 1;
                        $pct   = round(($ar['present_days'] / $total) * 100);
                        $pctColor = $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger');
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= sanitize($ar['name']) ?></td>
                            <td class="small text-muted"><?= sanitize($ar['subject_specialization'] ?? '') ?></td>
                            <td class="text-success fw-bold"><?= $ar['present_days'] ?></td>
                            <td class="text-danger fw-bold"><?= $ar['absent_days'] ?></td>
                            <td class="text-warning fw-bold"><?= $ar['late_days'] ?></td>
                            <td class="text-info fw-bold"><?= $ar['leave_days'] ?></td>
                            <td><?= $ar['total_marked'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px">
                                        <div class="progress-bar bg-<?= $pctColor ?>" style="width:<?= $pct ?>%"></div>
                                    </div>
                                    <span class="small text-<?= $pctColor ?> fw-bold"><?= $pct ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Substitution Report -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="fw-bold mb-0"><i class="fas fa-exchange-alt text-warning me-2"></i>Substitution Summary</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Teacher</th><th>Times Absent</th><th>Times Subbed</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subReport as $sr): ?>
                            <tr>
                                <td><?= sanitize($sr['teacher_name']) ?></td>
                                <td><span class="badge bg-danger"><?= $sr['times_absent'] ?></span></td>
                                <td><span class="badge bg-success"><?= $sr['times_substituted'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($subReport)): ?>
                            <tr><td colspan="3" class="text-muted text-center">No substitutions this month</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Leave Report -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="fw-bold mb-0"><i class="fas fa-calendar-times text-info me-2"></i>Leave Summary</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Teacher</th><th>Approved</th><th>Pending</th><th>Rejected</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveReport as $lr): ?>
                            <?php if ($lr['approved'] || $lr['pending'] || $lr['rejected']): ?>
                            <tr>
                                <td><?= sanitize($lr['name']) ?></td>
                                <td><span class="badge bg-success"><?= $lr['approved'] ?></span></td>
                                <td><span class="badge bg-warning text-dark"><?= $lr['pending'] ?></span></td>
                                <td><span class="badge bg-danger"><?= $lr['rejected'] ?></span></td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Timetable Coverage -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-0">
            <h6 class="fw-bold mb-0"><i class="fas fa-calendar-alt text-success me-2"></i>Timetable Coverage</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Class</th><th>Section</th><th>Total Periods</th><th>Active</th><th>Coverage</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($coverageReport as $cr): ?>
                    <tr>
                        <td><?= sanitize($cr['class_name']) ?></td>
                        <td><?= sanitize($cr['section_name'] ?? '—') ?></td>
                        <td><?= $cr['total_periods'] ?></td>
                        <td><?= $cr['active_periods'] ?></td>
                        <td>
                            <?php $pct = $cr['total_periods'] ? round(($cr['active_periods'] / $cr['total_periods']) * 100) : 0; ?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px">
                                    <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                                </div>
                                <span class="small"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
