<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAnyRole(['admin', 'coordinator'], '../index.php');

$message = $error = '';
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $classId   = intval($_POST['class_id']);
        $sectionId = intval($_POST['section_id'] ?? 0) ?: null;
        $subjectId = intval($_POST['subject_id']);
        $teacherId = intval($_POST['teacher_id']);
        $roomId    = intval($_POST['room_id'] ?? 0) ?: null;
        $day       = in_array($_POST['day'] ?? '', $days) ? $_POST['day'] : '';
        $period    = intval($_POST['period_number']);
        $startTime = $_POST['start_time'] ?? '';
        $endTime   = $_POST['end_time'] ?? '';

        if (!$classId || !$subjectId || !$teacherId || !$day || !$period || !$startTime || !$endTime) {
            $error = 'All required fields must be filled.';
        } else {
            // Check teacher clash
            $clash = $conn->prepare(
                'SELECT id FROM timetable WHERE teacher_id=? AND day=? AND period_number=? AND is_active=1'
            );
            $clash->bind_param('isi', $teacherId, $day, $period);
            $clash->execute();
            $clash->store_result();

            if ($clash->num_rows > 0) {
                $error = 'Teacher is already scheduled for this period!';
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO timetable (class_id, section_id, subject_id, teacher_id, room_id, day, period_number, start_time, end_time) VALUES (?,?,?,?,?,?,?,?,?)'
                );
                $stmt->bind_param('iiiiisisss', $classId, $sectionId, $subjectId, $teacherId, $roomId, $day, $period, $startTime, $endTime);
                if ($stmt->execute()) {
                    logActivity($conn, 'ADD_TIMETABLE', "Added period $period on $day");
                    $message = 'Period added to timetable!';
                } else {
                    $error = 'This period already exists for the selected class/section.';
                }
                $stmt->close();
            }
            $clash->close();
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM timetable WHERE id = $id");
        $message = 'Period removed!';
    } elseif ($action === 'toggle') {
        $id = intval($_POST['id']);
        $conn->query("UPDATE timetable SET is_active = NOT is_active WHERE id = $id");
        $message = 'Period status updated!';
    }
}

// Filters
$filterClass = intval($_GET['class_id'] ?? 0);
$filterDay   = in_array($_GET['day'] ?? '', array_merge($days, [''])) ? ($_GET['day'] ?? '') : '';

$where = 'WHERE 1=1';
if ($filterClass) $where .= " AND t.class_id = $filterClass";
if ($filterDay)   $where .= " AND t.day = '" . $conn->real_escape_string($filterDay) . "'";

$timetable = $conn->query("
    SELECT t.*, c.class_name, s.section_name, sub.subject_name,
           te.name AS teacher_name, r.room_number
    FROM timetable t
    JOIN classes c ON t.class_id = c.id
    LEFT JOIN sections s ON t.section_id = s.id
    JOIN subjects sub ON t.subject_id = sub.id
    JOIN teachers te ON t.teacher_id = te.id
    LEFT JOIN rooms r ON t.room_id = r.id
    $where
    ORDER BY FIELD(t.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.period_number
")->fetch_all(MYSQLI_ASSOC);

$classes  = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sections = $conn->query("SELECT s.id, s.section_name, s.class_id, c.class_name FROM sections s JOIN classes c ON s.class_id = c.id ORDER BY c.class_name, s.section_name")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
$teachers = $conn->query("SELECT id, name, subject_specialization FROM teachers WHERE status = 'active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$rooms    = $conn->query("SELECT id, room_number FROM rooms ORDER BY room_number")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Timetable Management';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Timetable Management</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
            <i class="fas fa-plus me-1"></i>Add Period
        </button>
    </div>

    <?php if ($message): ?><div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-1"></i><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible"><i class="fas fa-times-circle me-1"></i><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Filter by Class</label>
                    <select name="class_id" class="form-select form-select-sm">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>>
                            <?= sanitize($c['class_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Filter by Day</label>
                    <select name="day" class="form-select form-select-sm">
                        <option value="">All Days</option>
                        <?php foreach ($days as $d): ?>
                        <option value="<?= $d ?>" <?= $filterDay === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="timetable.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Timetable Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table id="ttTable" class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Day</th><th>Period</th><th>Time</th>
                        <th>Class</th><th>Subject</th><th>Teacher</th>
                        <th>Room</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timetable as $tt): ?>
                    <tr>
                        <td><span class="badge bg-primary"><?= $tt['day'] ?></span></td>
                        <td class="fw-bold">P<?= $tt['period_number'] ?></td>
                        <td class="small"><?= formatTime($tt['start_time']) ?> - <?= formatTime($tt['end_time']) ?></td>
                        <td><?= sanitize($tt['class_name']) ?> <?= sanitize($tt['section_name'] ?? '') ?></td>
                        <td><?= sanitize($tt['subject_name']) ?></td>
                        <td><?= sanitize($tt['teacher_name']) ?></td>
                        <td><?= sanitize($tt['room_number'] ?? '—') ?></td>
                        <td><?= getStatusBadge($tt['is_active'] ? 'active' : 'inactive') ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $tt['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $tt['is_active'] ? 'warning' : 'success' ?>"
                                            title="<?= $tt['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fas fa-<?= $tt['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this period?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $tt['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Period Modal -->
<div class="modal fade" id="addPeriodModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Timetable Period</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Class *</label>
                            <select name="class_id" id="ttClassId" class="form-select" required onchange="filterSections(this.value)">
                                <option value="">-- Select Class --</option>
                                <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= sanitize($c['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Section</label>
                            <select name="section_id" id="ttSectionId" class="form-select">
                                <option value="">-- Select Section --</option>
                                <?php foreach ($sections as $s): ?>
                                <option value="<?= $s['id'] ?>" data-class="<?= $s['class_id'] ?>">
                                    <?= sanitize($s['class_name']) ?> - <?= sanitize($s['section_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subject *</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= sanitize($s['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Teacher *</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">-- Select Teacher --</option>
                                <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= sanitize($t['name']) ?> (<?= sanitize($t['subject_specialization'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Day *</label>
                            <select name="day" class="form-select" required>
                                <option value="">-- Select Day --</option>
                                <?php foreach ($days as $d): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Period No. *</label>
                            <input type="number" name="period_number" class="form-control" min="1" max="10" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">End Time *</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Room</label>
                            <select name="room_id" class="form-select">
                                <option value="">-- Select Room --</option>
                                <?php foreach ($rooms as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= sanitize($r['room_number']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Period</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const allSections = <?= json_encode($sections) ?>;
function filterSections(classId) {
    const sel = document.getElementById('ttSectionId');
    sel.innerHTML = '<option value="">-- Select Section --</option>';
    allSections.filter(s => !classId || s.class_id == classId).forEach(s => {
        sel.innerHTML += `<option value="${s.id}">${s.class_name} - ${s.section_name}</option>`;
    });
}
</script>
<?php require_once '../includes/footer.php'; ?>
