<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAnyRole(['admin', 'coordinator'], '../index.php');

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $id      = intval($_POST['id']);
    $remarks = sanitize($_POST['admin_remarks'] ?? '');

    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt   = $conn->prepare(
            'UPDATE leaves SET status=?, approved_by=?, approved_on=NOW(), admin_remarks=? WHERE id=?'
        );
        $stmt->bind_param('sisi', $status, $_SESSION['user_id'], $remarks, $id);
        $stmt->execute();
        $stmt->close();

        // Notify teacher
        $leaveRow = $conn->query("
            SELECT l.*, t.user_id, t.name AS teacher_name
            FROM leaves l JOIN teachers t ON l.teacher_id = t.id
            WHERE l.id = $id
        ")->fetch_assoc();

        if ($leaveRow && $leaveRow['user_id']) {
            $statusLabel = ucfirst($status);
            sendNotification(
                $conn, $leaveRow['user_id'],
                "Leave Request $statusLabel",
                "Your leave request from " . formatDate($leaveRow['from_date']) . " to " . formatDate($leaveRow['to_date']) . " has been $status." . ($remarks ? " Remarks: $remarks" : ''),
                $status === 'approved' ? 'success' : 'danger'
            );
        }

        logActivity($conn, strtoupper($action) . '_LEAVE', "Leave #$id $status");
        $message = "Leave request $status!";
    }
}

// Fetch leaves
$statusFilter = $_GET['status'] ?? 'pending';
$where = $statusFilter !== 'all' ? "WHERE l.status = '$statusFilter'" : '';

$leaves = $conn->query("
    SELECT l.*, t.name AS teacher_name, t.subject_specialization
    FROM leaves l JOIN teachers t ON l.teacher_id = t.id
    $where
    ORDER BY l.applied_on DESC
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Leave Requests';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Leave Requests</h4>
    </div>

    <?php if ($message): ?><div class="alert alert-success alert-dismissible"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <!-- Filter tabs -->
    <ul class="nav nav-tabs mb-4">
        <?php foreach (['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'all' => 'secondary'] as $s => $c): ?>
        <li class="nav-item">
            <a class="nav-link <?= $statusFilter === $s ? 'active' : '' ?>"
               href="?status=<?= $s ?>">
                <?php
                $cnt = $conn->query("SELECT COUNT(*) FROM leaves" . ($s !== 'all' ? " WHERE status='$s'" : ''))->fetch_row()[0];
                ?>
                <?= ucfirst($s) ?>
                <span class="badge bg-<?= $c ?> ms-1"><?= $cnt ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table id="leaveTable" class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Teacher</th><th>Leave Type</th>
                        <th>From</th><th>To</th><th>Days</th>
                        <th>Reason</th><th>Applied On</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaves as $i => $l): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= sanitize($l['teacher_name']) ?></div>
                            <div class="text-muted small"><?= sanitize($l['subject_specialization'] ?? '') ?></div>
                        </td>
                        <td><span class="badge bg-info"><?= ucfirst($l['leave_type']) ?></span></td>
                        <td><?= formatDate($l['from_date']) ?></td>
                        <td><?= formatDate($l['to_date']) ?></td>
                        <td><?= (new DateTime($l['from_date']))->diff(new DateTime($l['to_date']))->days + 1 ?></td>
                        <td class="small" style="max-width:200px"><?= sanitize($l['reason']) ?></td>
                        <td class="small"><?= formatDate($l['applied_on']) ?></td>
                        <td><?= getStatusBadge($l['status']) ?></td>
                        <td>
                            <?php if ($l['status'] === 'pending'): ?>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-success"
                                        onclick="actionLeave(<?= $l['id'] ?>, 'approve', '<?= sanitize($l['teacher_name']) ?>')">
                                    <i class="fas fa-check"></i></button>
                                <button class="btn btn-sm btn-danger"
                                        onclick="actionLeave(<?= $l['id'] ?>, 'reject', '<?= sanitize($l['teacher_name']) ?>')">
                                    <i class="fas fa-times"></i></button>
                            </div>
                            <?php else: ?>
                            <span class="text-muted small"><?= sanitize($l['admin_remarks'] ?? '—') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Action Modal -->
<div class="modal fade" id="leaveActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="leaveActionHeader">
                <h5 class="modal-title" id="leaveActionTitle">Leave Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="leaveAction">
                <input type="hidden" name="id" id="leaveId">
                <div class="modal-body">
                    <p>Teacher: <strong id="leaveTeacherName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Admin Remarks (optional)</label>
                        <textarea name="admin_remarks" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="leaveSubmitBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function actionLeave(id, action, name) {
    document.getElementById('leaveAction').value      = action;
    document.getElementById('leaveId').value          = id;
    document.getElementById('leaveTeacherName').textContent = name;
    const isApprove = action === 'approve';
    document.getElementById('leaveActionHeader').className = 'modal-header bg-' + (isApprove ? 'success' : 'danger') + ' text-white';
    document.getElementById('leaveActionTitle').textContent = isApprove ? 'Approve Leave' : 'Reject Leave';
    document.getElementById('leaveSubmitBtn').className = 'btn btn-' + (isApprove ? 'success' : 'danger');
    document.getElementById('leaveSubmitBtn').textContent = isApprove ? 'Approve' : 'Reject';
    new bootstrap.Modal(document.getElementById('leaveActionModal')).show();
}
</script>
<?php require_once '../includes/footer.php'; ?>
