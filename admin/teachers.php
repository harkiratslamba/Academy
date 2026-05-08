<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAnyRole(['admin', 'coordinator'], '../index.php');

$message = $error = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name    = sanitize($_POST['name'] ?? '');
        $email   = sanitize($_POST['email'] ?? '');
        $phone   = sanitize($_POST['phone'] ?? '');
        $empId   = sanitize($_POST['employee_id'] ?? '');
        $spec    = sanitize($_POST['subject_specialization'] ?? '');
        $qual    = sanitize($_POST['qualification'] ?? '');
        $joining = $_POST['joining_date'] ?? null;
        $uname   = sanitize($_POST['username'] ?? '');
        $pass    = $_POST['password'] ?? '';

        if (!$name || !$empId || !$uname || !$pass) {
            $error = 'Name, Employee ID, Username, and Password are required.';
        } else {
            // Check duplicate username/employee_id
            $check = $conn->prepare('SELECT id FROM users WHERE username = ?');
            $check->bind_param('s', $uname);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = 'Username already exists.';
            } else {
                $conn->begin_transaction();
                try {
                    $hashed = password_hash($pass, PASSWORD_DEFAULT);
                    $stmtU  = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?,?,\'teacher\')');
                    $stmtU->bind_param('ss', $uname, $hashed);
                    $stmtU->execute();
                    $userId = $conn->insert_id;
                    $stmtU->close();

                    $stmtT = $conn->prepare(
                        'INSERT INTO teachers (user_id, name, email, phone, employee_id, subject_specialization, qualification, joining_date) VALUES (?,?,?,?,?,?,?,?)'
                    );
                    $stmtT->bind_param('isssssss', $userId, $name, $email, $phone, $empId, $spec, $qual, $joining);
                    $stmtT->execute();
                    $teacherId = $conn->insert_id;
                    $stmtT->close();

                    // Link teacher_id in users table
                    $conn->query("UPDATE users SET teacher_id = $teacherId WHERE id = $userId");

                    $conn->commit();
                    logActivity($conn, 'ADD_TEACHER', "Added teacher: $name");
                    $message = "Teacher '$name' added successfully!";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = 'Error adding teacher: ' . $e->getMessage();
                }
            }
            $check->close();
        }
    } elseif ($action === 'edit') {
        $id   = intval($_POST['id']);
        $name = sanitize($_POST['name'] ?? '');
        $email= sanitize($_POST['email'] ?? '');
        $phone= sanitize($_POST['phone'] ?? '');
        $spec = sanitize($_POST['subject_specialization'] ?? '');
        $qual = sanitize($_POST['qualification'] ?? '');
        $stat = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';

        $stmt = $conn->prepare(
            'UPDATE teachers SET name=?, email=?, phone=?, subject_specialization=?, qualification=?, status=? WHERE id=?'
        );
        $stmt->bind_param('ssssssi', $name, $email, $phone, $spec, $qual, $stat, $id);
        $stmt->execute();
        $stmt->close();
        logActivity($conn, 'EDIT_TEACHER', "Edited teacher ID: $id");
        $message = 'Teacher updated successfully!';
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        // Check if teacher has timetable entries
        $chk = $conn->query("SELECT COUNT(*) FROM timetable WHERE teacher_id = $id")->fetch_row()[0];
        if ($chk > 0) {
            $error = 'Cannot delete teacher with existing timetable entries. Deactivate instead.';
        } else {
            $teacherRow = $conn->query("SELECT user_id FROM teachers WHERE id = $id")->fetch_assoc();
            $conn->query("DELETE FROM teachers WHERE id = $id");
            if ($teacherRow && $teacherRow['user_id']) {
                $conn->query("DELETE FROM users WHERE id = " . intval($teacherRow['user_id']));
            }
            logActivity($conn, 'DELETE_TEACHER', "Deleted teacher ID: $id");
            $message = 'Teacher deleted successfully!';
        }
    } elseif ($action === 'reset_password') {
        $id      = intval($_POST['id']);
        $newPass = $_POST['new_password'] ?? '';
        if (strlen($newPass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $teacher = $conn->query("SELECT user_id FROM teachers WHERE id = $id")->fetch_assoc();
            if ($teacher && $teacher['user_id']) {
                $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->bind_param('si', $hashed, $teacher['user_id']);
                $stmt->execute();
                $stmt->close();
                $message = 'Password reset successfully!';
            }
        }
    }
}

// Fetch all teachers
$teachers = $conn->query("
    SELECT t.*, u.username, u.last_login
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.name
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Teachers';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Manage Teachers</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
            <i class="fas fa-plus me-1"></i>Add Teacher
        </button>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-1"></i><?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible"><i class="fas fa-times-circle me-1"></i><?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table id="teacherTable" class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Employee ID</th>
                        <th>Specialization</th>
                        <th>Contact</th>
                        <th>Username</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $i => $t): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= sanitize($t['name']) ?></div>
                            <div class="text-muted small"><?= sanitize($t['email'] ?? '') ?></div>
                        </td>
                        <td><?= sanitize($t['employee_id'] ?? '') ?></td>
                        <td><?= sanitize($t['subject_specialization'] ?? '') ?></td>
                        <td><?= sanitize($t['phone'] ?? '') ?></td>
                        <td><code><?= sanitize($t['username'] ?? '') ?></code></td>
                        <td><?= $t['joining_date'] ? formatDate($t['joining_date']) : '—' ?></td>
                        <td><?= getStatusBadge($t['status']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary"
                                    onclick="editTeacher(<?= htmlspecialchars(json_encode($t)) ?>)"
                                    title="Edit"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-info"
                                    onclick="resetPassword(<?= $t['id'] ?>, '<?= sanitize($t['name']) ?>')"
                                    title="Reset Password"><i class="fas fa-key"></i></button>
                                <form method="POST" onsubmit="return confirm('Delete this teacher?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i></button>
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

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Teacher</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Employee ID *</label>
                            <input type="text" name="employee_id" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subject Specialization</label>
                            <input type="text" name="subject_specialization" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Qualification</label>
                            <input type="text" name="qualification" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Joining Date</label>
                            <input type="date" name="joining_date" class="form-control">
                        </div>
                        <div class="col-12"><hr><h6 class="fw-bold">Login Credentials</h6></div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Password *</label>
                            <input type="password" name="password" class="form-control" minlength="6" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name *</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" id="editPhone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subject Specialization</label>
                            <input type="text" name="subject_specialization" id="editSpec" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Qualification</label>
                            <input type="text" name="qualification" id="editQual" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="resetId">
                <div class="modal-body">
                    <p>Reset password for: <strong id="resetName"></strong></p>
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" minlength="6" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTeacher(t) {
    document.getElementById('editId').value    = t.id;
    document.getElementById('editName').value  = t.name;
    document.getElementById('editEmail').value = t.email || '';
    document.getElementById('editPhone').value = t.phone || '';
    document.getElementById('editSpec').value  = t.subject_specialization || '';
    document.getElementById('editQual').value  = t.qualification || '';
    document.getElementById('editStatus').value = t.status;
    new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
}
function resetPassword(id, name) {
    document.getElementById('resetId').value   = id;
    document.getElementById('resetName').textContent = name;
    new bootstrap.Modal(document.getElementById('resetPassModal')).show();
}
</script>
<?php require_once '../includes/footer.php'; ?>
