<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAnyRole(['admin', 'coordinator'], '../index.php');

$message = $error = '';
$today    = getTodayDate();
$todayDay = getTodayDay();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign') {
        $timetableId  = intval($_POST['timetable_id']);
        $subTeacherId = intval($_POST['substitute_teacher_id']);
        $date         = $_POST['date'] ?? $today;
        $notes        = sanitize($_POST['notes'] ?? '');

        // Get original teacher
        $ttRow = $conn->query("SELECT teacher_id FROM timetable WHERE id = $timetableId")->fetch_assoc();
        if (!$ttRow) { $error = 'Invalid timetable entry.'; }
        else {
            $origTeacherId = $ttRow['teacher_id'];

            // Check substitute is free
            $subDay = date('l', strtotime($date));
            $period = $conn->query("SELECT period_number FROM timetable WHERE id = $timetableId")->fetch_assoc()['period_number'];

            $alreadyBusy = $conn->query("
                SELECT COUNT(*) FROM substitutions s
                JOIN timetable t ON s.timetable_id = t.id
                WHERE s.substitute_teacher_id = $subTeacherId
                AND s.date = '$date'
                AND t.period_number = $period
                AND s.status != 'cancelled'
            ")->fetch_row()[0];

            $ownClass = $conn->query("
                SELECT COUNT(*) FROM timetable
                WHERE teacher_id = $subTeacherId AND day = '$subDay' AND period_number = $period AND is_active = 1
            ")->fetch_row()[0];

            if ($alreadyBusy || $ownClass) {
                $error = 'Selected substitute teacher is not free during this period!';
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO substitutions (original_teacher_id, substitute_teacher_id, timetable_id, date, status, assigned_by, notes)
                     VALUES (?,?,?,?,\'assigned\',?,?)
                     ON DUPLICATE KEY UPDATE substitute_teacher_id=VALUES(substitute_teacher_id), status=\'assigned\''
                );
                $stmt->bind_param('iiiiss', $origTeacherId, $subTeacherId, $timetableId, $date, $_SESSION['user_id'], $notes);
                $stmt->execute();
                $stmt->close();

                // Notify substitute teacher
                $subTeacher = $conn->query("SELECT user_id, name FROM teachers WHERE id = $subTeacherId")->fetch_assoc();
                $ttInfo     = $conn->query("
                    SELECT t.period_number, c.class_name, s.section_name, sub.subject_name
                    FROM timetable t JOIN classes c ON t.class_id=c.id
                    LEFT JOIN sections s ON t.section_id=s.id
                    JOIN subjects sub ON t.subject_id=sub.id
                    WHERE t.id = $timetableId
                ")->fetch_assoc();

                if ($subTeacher && $subTeacher['user_id']) {
                    sendNotification(
                        $conn, $subTeacher['user_id'],
                        'Substitute Class Assigned',
                        "You have been assigned to take Period {$ttInfo['period_number']} ({$ttInfo['subject_name']}) for {$ttInfo['class_name']} {$ttInfo['section_name']} on " . formatDate($date),
                        'warning'
                    );
                }

                logActivity($conn, 'ASSIGN_SUBSTITUTE', "Assigned substitute for timetable $timetableId on $date");
                $message = 'Substitute teacher assigned successfully!';
            }
        }
    } elseif ($action === 'cancel') {
        $id = intval($_POST['id']);
        $conn->query("UPDATE substitutions SET status = 'cancelled' WHERE id = $id");
        $message = 'Substitution cancelled!';
    } elseif ($action === 'mark_absent') {
        $teacherId = intval($_POST['teacher_id']);
        $date      = $_POST['date'] ?? $today;
        $stmt = $conn->prepare(
            'INSERT INTO attendance (teacher_id, date, status) VALUES (?,?,\'absent\')
             ON DUPLICATE KEY UPDATE status=\'absent\''
        );
        $stmt->bind_param('is', $teacherId, $date);
        $stmt->execute();
        $stmt->close();
        $message = 'Teacher marked as absent!';
    }
}

// Date filter
$filterDate    = $_GET['date'] ?? $today;
$filterDateDay = date('l', strtotime($filterDate));

// Classes needing substitutes on selected date
$needSubs = $conn->query("
    SELECT t.id AS timetable_id, t.period_number, t.start_time, t.end_time,
           c.class_name, s.section_name, sub.subject_name,
           te.id AS orig_teacher_id, te.name AS orig_teacher,
           (SELECT COUNT(*) FROM substitutions WHERE timetable_id = t.id AND date = '$filterDate' AND status != 'cancelled') AS has_sub,
           (SELECT name FROM teachers WHERE id = (
               SELECT substitute_teacher_id FROM substitutions WHERE timetable_id = t.id AND date = '$filterDate' AND status != 'cancelled' LIMIT 1
           )) AS sub_teacher_name
    FROM timetable t
    JOIN classes c ON t.class_id = c.id
    LEFT JOIN sections s ON t.section_id = s.id
    JOIN subjects sub ON t.subject_id = sub.id
    JOIN teachers te ON t.teacher_id = te.id
    WHERE t.day = '$filterDateDay' AND t.is_active = 1
    AND t.teacher_id IN (
        SELECT teacher_id FROM attendance WHERE date = '$filterDate' AND status IN ('absent','on_leave')
    )
    ORDER BY t.period_number
")->fetch_all(MYSQLI_ASSOC);

// All teachers absent on filter date
$absentTeachers = $conn->query("
    SELECT te.id, te.name, te.subject_specialization
    FROM attendance a JOIN teachers te ON a.teacher_id = te.id
    WHERE a.date = '$filterDate' AND a.status IN ('absent','on_leave')
")->fetch_all(MYSQLI_ASSOC);

// Active teachers list for substitution
$activeTeachers = $conn->query("SELECT id, name, subject_specialization FROM teachers WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

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
    WHERE s.date = '$filterDate'
    ORDER BY s.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Substitution Management';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Substitution Management</h4>
            <small class="text-muted">Assign substitute teachers for absent staff</small>
        </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-1"></i><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible"><i class="fas fa-times-circle me-1"></i><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <!-- Date Selector + Mark Absent -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Select Date</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="<?= $filterDate ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-4 text-muted small pt-3">
                    <?= date('l, d F Y', strtotime($filterDate)) ?>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger btn-sm float-end"
                            data-bs-toggle="modal" data-bs-target="#markAbsentModal">
                        <i class="fas fa-user-times me-1"></i>Mark Teacher Absent
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <!-- Classes needing substitutes -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                        Classes Without Teacher
                        <span class="badge bg-danger ms-2"><?= count(array_filter($needSubs, fn($n) => !$n['has_sub'])) ?></span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($needSubs)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="mb-0">All classes covered for this date!</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Period</th><th>Class</th><th>Subject</th>
                                    <th>Absent Teacher</th><th>Status</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($needSubs as $ns): ?>
                                <tr class="<?= !$ns['has_sub'] ? 'table-danger' : 'table-warning' ?>">
                                    <td><strong>P<?= $ns['period_number'] ?></strong><br>
                                        <small class="text-muted"><?= formatTime($ns['start_time']) ?></small></td>
                                    <td><?= sanitize($ns['class_name']) ?> <?= sanitize($ns['section_name'] ?? '') ?></td>
                                    <td><?= sanitize($ns['subject_name']) ?></td>
                                    <td class="text-danger"><?= sanitize($ns['orig_teacher']) ?></td>
                                    <td>
                                        <?php if ($ns['has_sub']): ?>
                                        <span class="badge bg-warning text-dark">
                                            <?= sanitize($ns['sub_teacher_name']) ?> (Sub)
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary"
                                                onclick="openAssignModal(<?= $ns['timetable_id'] ?>, '<?= sanitize($ns['orig_teacher']) ?>', <?= $ns['period_number'] ?>, '<?= $filterDate ?>', <?= $ns['orig_teacher_id'] ?>)">
                                            <i class="fas fa-user-plus me-1"></i>Assign
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Today's substitutions record -->
            <?php if (!empty($recentSubs)): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0">
                    <h6 class="fw-bold mb-0"><i class="fas fa-history text-primary me-2"></i>Substitutions on <?= date('d M Y', strtotime($filterDate)) ?></h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Period</th><th>Class</th><th>Subject</th><th>Original</th><th>Substitute</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSubs as $rs): ?>
                            <tr>
                                <td>P<?= $rs['period_number'] ?></td>
                                <td><?= sanitize($rs['class_name']) ?> <?= sanitize($rs['section_name'] ?? '') ?></td>
                                <td><?= sanitize($rs['subject_name']) ?></td>
                                <td class="text-danger small"><?= sanitize($rs['original_teacher']) ?></td>
                                <td class="text-success small"><?= sanitize($rs['sub_teacher']) ?></td>
                                <td><?= getStatusBadge($rs['status']) ?></td>
                                <td>
                                    <?php if ($rs['status'] !== 'cancelled'): ?>
                                    <form method="POST" onsubmit="return confirm('Cancel substitution?')" class="d-inline">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?= $rs['id'] ?>">
                                        <button type="submit" class="btn btn-xs btn-outline-danger btn-sm py-0 px-1">Cancel</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Absent teachers sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="fw-bold mb-0"><i class="fas fa-user-times text-danger me-2"></i>Absent Teachers</h6>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($absentTeachers)): ?>
                    <div class="list-group-item text-muted small text-center">No absent teachers</div>
                    <?php else: ?>
                        <?php foreach ($absentTeachers as $at): ?>
                        <div class="list-group-item">
                            <div class="fw-semibold"><?= sanitize($at['name']) ?></div>
                            <div class="text-muted small"><?= sanitize($at['subject_specialization'] ?? '') ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Substitute Modal -->
<div class="modal fade" id="assignSubModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Assign Substitute Teacher</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="assign">
                <input type="hidden" name="timetable_id" id="assignTtId">
                <input type="hidden" name="date" id="assignDate">
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        <strong>Original Teacher:</strong> <span id="assignOrigTeacher"></span> |
                        <strong>Period:</strong> <span id="assignPeriod"></span>
                    </div>
                    <div id="availableTeachersContainer">
                        <label class="form-label fw-semibold">Select Available Substitute Teacher *</label>
                        <div id="teacherList" class="mb-3">
                            <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading available teachers...</div>
                        </div>
                        <input type="hidden" name="substitute_teacher_id" id="selectedSubTeacher" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Any special notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mark Absent Modal -->
<div class="modal fade" id="markAbsentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-user-times me-2"></i>Mark Teacher Absent</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="mark_absent">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Teacher *</label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">-- Select Teacher --</option>
                            <?php foreach ($activeTeachers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= sanitize($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date *</label>
                        <input type="date" name="date" class="form-control" value="<?= $filterDate ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Mark Absent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAssignModal(ttId, origTeacher, period, date, origTeacherId) {
    document.getElementById('assignTtId').value       = ttId;
    document.getElementById('assignDate').value       = date;
    document.getElementById('assignOrigTeacher').textContent = origTeacher;
    document.getElementById('assignPeriod').textContent     = 'Period ' + period;
    document.getElementById('selectedSubTeacher').value = '';
    document.getElementById('teacherList').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

    new bootstrap.Modal(document.getElementById('assignSubModal')).show();

    // Fetch available teachers via AJAX
    fetch('<?= APP_URL ?>/ajax/get_available_teachers.php?timetable_id=' + ttId + '&date=' + date)
        .then(r => r.json())
        .then(data => {
            let html = '';
            if (!data.teachers || data.teachers.length === 0) {
                html = '<div class="alert alert-warning">No available teachers found for this period.</div>';
            } else {
                html = '<div class="list-group">';
                data.teachers.forEach(t => {
                    const badge = t.same_subject ? '<span class="badge bg-success ms-1">Same Subject</span>' : '';
                    html += `<label class="list-group-item list-group-item-action cursor-pointer">
                        <input type="radio" name="sub_radio" value="${t.id}" onchange="document.getElementById('selectedSubTeacher').value=this.value" class="me-2">
                        <strong>${t.name}</strong> ${badge}
                        <span class="text-muted small ms-2">${t.subject_specialization || ''}</span>
                        <span class="badge bg-secondary ms-2">${t.workload} periods today</span>
                    </label>`;
                });
                html += '</div>';
            }
            document.getElementById('teacherList').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('teacherList').innerHTML = '<div class="alert alert-danger">Error loading teachers.</div>';
        });
}
</script>
<?php require_once '../includes/footer.php'; ?>
