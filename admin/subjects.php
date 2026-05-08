<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAnyRole(['admin', 'coordinator'], '../index.php');

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name    = sanitize($_POST['subject_name'] ?? '');
        $code    = sanitize($_POST['subject_code'] ?? '');
        $classId = intval($_POST['class_id'] ?? 0) ?: null;
        if (!$name) { $error = 'Subject name is required.'; }
        else {
            $stmt = $conn->prepare('INSERT INTO subjects (subject_name, subject_code, class_id) VALUES (?,?,?)');
            $stmt->bind_param('ssi', $name, $code, $classId);
            if ($stmt->execute()) { $message = "Subject '$name' added!"; }
            else { $error = 'Subject code already exists.'; }
            $stmt->close();
        }
    } elseif ($action === 'edit') {
        $id      = intval($_POST['id']);
        $name    = sanitize($_POST['subject_name'] ?? '');
        $code    = sanitize($_POST['subject_code'] ?? '');
        $classId = intval($_POST['class_id'] ?? 0) ?: null;
        $stmt = $conn->prepare('UPDATE subjects SET subject_name=?, subject_code=?, class_id=? WHERE id=?');
        $stmt->bind_param('ssii', $name, $code, $classId, $id);
        $stmt->execute();
        $stmt->close();
        $message = 'Subject updated!';
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM subjects WHERE id = $id");
        $message = 'Subject deleted!';
    }
}

$subjects = $conn->query("
    SELECT s.*, c.class_name
    FROM subjects s
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY s.subject_name
")->fetch_all(MYSQLI_ASSOC);

$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Subjects';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Manage Subjects</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="fas fa-plus me-1"></i>Add Subject
        </button>
    </div>

    <?php if ($message): ?><div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-1"></i><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible"><i class="fas fa-times-circle me-1"></i><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table id="subjectTable" class="table table-hover">
                <thead class="table-light">
                    <tr><th>#</th><th>Subject Name</th><th>Code</th><th>Class</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $i => $s): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= sanitize($s['subject_name']) ?></td>
                        <td><span class="badge bg-secondary"><?= sanitize($s['subject_code'] ?? '') ?></span></td>
                        <td><?= $s['class_name'] ? sanitize($s['class_name']) : '<span class="text-muted">All Classes</span>' ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary"
                                    onclick="editSubject(<?= htmlspecialchars(json_encode($s)) ?>)">
                                    <i class="fas fa-edit"></i></button>
                                <form method="POST" onsubmit="return confirm('Delete subject?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
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

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject Name *</label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject Code</label>
                        <input type="text" name="subject_code" class="form-control" placeholder="e.g. MATH">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Class (optional)</label>
                        <select name="class_id" class="form-select">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= sanitize($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editSubId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject Name *</label>
                        <input type="text" name="subject_name" id="editSubName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject Code</label>
                        <input type="text" name="subject_code" id="editSubCode" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Class</label>
                        <select name="class_id" id="editSubClass" class="form-select">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= sanitize($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function editSubject(s) {
    document.getElementById('editSubId').value    = s.id;
    document.getElementById('editSubName').value  = s.subject_name;
    document.getElementById('editSubCode').value  = s.subject_code || '';
    document.getElementById('editSubClass').value = s.class_id || '';
    new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
}
</script>
<?php require_once '../includes/footer.php'; ?>
