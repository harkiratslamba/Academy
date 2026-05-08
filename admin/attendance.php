<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAnyRole(['admin', 'coordinator'], '../index.php');

$message = $error = '';
$today   = getTodayDate();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_bulk') {
        $date       = $_POST['date'] ?? $today;
        $statuses   = $_POST['status'] ?? [];
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                'INSERT INTO attendance (teacher_id, date, status, marked_by)
                 VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE status=VALUES(status), marked_by=VALUES(marked_by)'
            );
            foreach ($statuses as $teacherId => $status) {
                $tid = intval($teacherId);
                if (!in_array($status, ['present','absent','late','on_leave'])) continue;
                $stmt->bind_param('issi', $tid, $date, $status, $_SESSION['user_id']);
                $stmt->execute();
            }
            $stmt->close();
            $conn->commit();
            logActivity($conn, 'MARK_ATTENDANCE', "Marked attendance for $date");
            $message = "Attendance marked for " . date('d M Y', strtotime($date)) . "!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$filterDate = $_GET['date'] ?? $today;

$teachers = $conn->query("
    SELECT t.id, t.name, t.subject_specialization,
           a.status AS att_status, a.check_in
    FROM teachers t
    LEFT JOIN attendance a ON t.id = a.teacher_id AND a.date = '$filterDate'
    WHERE t.status = 'active'
    ORDER BY t.name
")->fetch_all(MYSQLI_ASSOC);

// Summary
$present   = count(array_filter($teachers, fn($t) => $t['att_status'] === 'present'));
$absent    = count(array_filter($teachers, fn($t) => in_array($t['att_status'], ['absent','on_leave'])));
$notMarked = count(array_filter($teachers, fn($t) => !$t['att_status']));

$pageTitle = 'Attendance';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Teacher Attendance</h4>
    </div>

    <?php if ($message): ?><div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-1"></i><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <!-- Date Selector -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label small mb-1">Select Date</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="<?= $filterDate ?>" onchange="this.form.submit()">
                </div>
                <div class="col-auto pt-3 text-muted small"><?= date('l, d F Y', strtotime($filterDate)) ?></div>
                <div class="col-auto ms-auto pt-3">
                    <span class="badge bg-success me-1">Present: <?= $present ?></span>
                    <span class="badge bg-danger me-1">Absent: <?= $absent ?></span>
                    <span class="badge bg-secondary">Not Marked: <?= $notMarked ?></span>
                </div>
            </form>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="mark_bulk">
        <input type="hidden" name="date" value="<?= $filterDate ?>">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Teacher Name</th>
                            <th>Specialization</th>
                            <th>
                                <div class="d-flex gap-2">
                                    <span>Attendance</span>
                                    <button type="button" class="btn btn-xs btn-outline-success btn-sm py-0 px-1"
                                            onclick="markAll('present')">All Present</button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $i => $t): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= sanitize($t['name']) ?></td>
                            <td class="text-muted small"><?= sanitize($t['subject_specialization'] ?? '') ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <?php
                                    $statuses = ['present' => 'success', 'absent' => 'danger', 'late' => 'warning', 'on_leave' => 'info'];
                                    foreach ($statuses as $s => $color):
                                        $checked = ($t['att_status'] === $s) ? 'checked' : '';
                                        if (!$t['att_status'] && $s === 'present') $checked = '';
                                    ?>
                                    <div class="form-check">
                                        <input class="form-check-input status-radio" type="radio"
                                               name="status[<?= $t['id'] ?>]"
                                               value="<?= $s ?>" <?= $checked ?>
                                               id="s_<?= $t['id'] ?>_<?= $s ?>">
                                        <label class="form-check-label text-<?= $color ?> small fw-semibold"
                                               for="s_<?= $t['id'] ?>_<?= $s ?>">
                                            <?= ucfirst(str_replace('_', ' ', $s)) ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Attendance
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
function markAll(status) {
    document.querySelectorAll(`input[type="radio"][value="${status}"]`).forEach(r => r.checked = true);
}
</script>
<?php require_once '../includes/footer.php'; ?>
