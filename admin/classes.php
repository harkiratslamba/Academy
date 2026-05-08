<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAnyRole(['admin', 'coordinator'], '../index.php');

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_class') {
        $name = sanitize($_POST['class_name'] ?? '');
        if (!$name) { $error = 'Class name is required.'; }
        else {
            $stmt = $conn->prepare('INSERT INTO classes (class_name) VALUES (?)');
            $stmt->bind_param('s', $name);
            if ($stmt->execute()) { $message = "Class '$name' added!"; }
            else { $error = 'Class name already exists.'; }
            $stmt->close();
        }
    } elseif ($action === 'delete_class') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM classes WHERE id = $id");
        $message = 'Class deleted!';
    } elseif ($action === 'add_section') {
        $classId = intval($_POST['class_id']);
        $secName = sanitize($_POST['section_name'] ?? '');
        if (!$classId || !$secName) { $error = 'Class and section name required.'; }
        else {
            $stmt = $conn->prepare('INSERT INTO sections (class_id, section_name) VALUES (?,?)');
            $stmt->bind_param('is', $classId, $secName);
            $stmt->execute();
            $stmt->close();
            $message = 'Section added!';
        }
    } elseif ($action === 'delete_section') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM sections WHERE id = $id");
        $message = 'Section deleted!';
    }
}

$classes = $conn->query("
    SELECT c.*, COUNT(s.id) AS section_count
    FROM classes c
    LEFT JOIN sections s ON c.id = s.class_id
    GROUP BY c.id
    ORDER BY c.class_name
")->fetch_all(MYSQLI_ASSOC);

$allSections = $conn->query("
    SELECT s.*, c.class_name
    FROM sections s
    JOIN classes c ON s.class_id = c.id
    ORDER BY c.class_name, s.section_name
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Classes & Sections';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Classes &amp; Sections</h4>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                <i class="fas fa-plus me-1"></i>Add Class
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                <i class="fas fa-plus me-1"></i>Add Section
            </button>
        </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-1"></i><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible"><i class="fas fa-times-circle me-1"></i><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <div class="row g-4">
        <!-- Classes -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="fas fa-school me-2 text-primary"></i>Classes</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Class Name</th><th>Sections</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $i => $c): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= sanitize($c['class_name']) ?></td>
                                <td><span class="badge bg-info"><?= $c['section_count'] ?></span></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this class and all sections?')">
                                        <input type="hidden" name="action" value="delete_class">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sections -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="fas fa-layer-group me-2 text-success"></i>Sections</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Class</th><th>Section</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allSections as $i => $s): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= sanitize($s['class_name']) ?></td>
                                <td><span class="badge bg-success"><?= sanitize($s['section_name']) ?></span></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this section?')">
                                        <input type="hidden" name="action" value="delete_section">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_class">
                <div class="modal-body">
                    <label class="form-label fw-semibold">Class Name *</label>
                    <input type="text" name="class_name" class="form-control" placeholder="e.g. Class 9" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Add Section</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_section">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Class *</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= sanitize($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Section Name *</label>
                        <input type="text" name="section_name" class="form-control" placeholder="e.g. A" maxlength="5" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
