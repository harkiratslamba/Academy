<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('teacher', '../index.php');

$teacherId = intval($_SESSION['teacher_id']);
$message   = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'apply') {
        $type     = in_array($_POST['leave_type'] ?? '', ['sick','casual','earned','other']) ? $_POST['leave_type'] : 'casual';
        $fromDate = $_POST['from_date'] ?? '';
        $toDate   = $_POST['to_date'] ?? '';
        $reason   = sanitize($_POST['reason'] ?? '');

        if (!$fromDate || !$toDate || !$reason) {
            $error = 'All fields are required.';
        } elseif (strtotime($toDate) < strtotime($fromDate)) {
            $error = 'End date must be after start date.';
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO leaves (teacher_id, leave_type, from_date, to_date, reason) VALUES (?,?,?,?,?)'
            );
            $stmt->bind_param('issss', $teacherId, $type, $fromDate, $toDate, $reason);
            $stmt->execute();
            $stmt->close();

            // Notify admin
            $adminUsers = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch_all(MYSQLI_ASSOC);
            $tName      = $conn->query("SELECT name FROM teachers WHERE id = $teacherId")->fetch_assoc()['name'];
            foreach ($adminUsers as $admin) {
                sendNotification(
                    $conn, $admin['id'],
                    'New Leave Request',
                    "$tName has applied for $type leave from " . formatDate($fromDate) . " to " . formatDate($toDate),
                    'warning'
                );
            }

            logActivity($conn, 'APPLY_LEAVE', "Applied for $type leave from $fromDate to $toDate");
            $message = 'Leave application submitted successfully!';
        }
    } elseif ($action === 'cancel') {
        $id   = intval($_POST['id']);
        $conn->query("UPDATE leaves SET status='rejected' WHERE id=$id AND teacher_id=$teacherId AND status='pending'");
        $message = 'Leave application cancelled!';
    }
}

$myLeaves = $conn->query("
    SELECT l.*,
           u.username AS approved_by_name
    FROM leaves l
    LEFT JOIN users u ON l.approved_by = u.id
    WHERE l.teacher_id = $teacherId
    ORDER BY l.applied_on DESC
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Leave Requests';
require_once '../includes/header.php';
require_once '../includes/teacher_sidebar.php';
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Leave Requests</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
            <i class="fas fa-plus me-1"></i>Apply for Leave
        </button>
    </div>

    <?php if ($message): ?><div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-1"></i><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <!-- Leave Balance Cards -->
    <div class="row g-3 mb-4">
        <?php
        $leaveTypes = ['sick' => ['label' => 'Sick Leave', 'color' => 'danger', 'limit' => 10],
                       'casual' => ['label' => 'Casual Leave', 'color' => 'info', 'limit' => 12],
                       'earned' => ['label' => 'Earned Leave', 'color' => 'success', 'limit' => 15]];
        foreach ($leaveTypes as $type => $info):
            $used = $conn->query("SELECT COALESCE(SUM(DATEDIFF(to_date, from_date)+1), 0) FROM leaves WHERE teacher_id=$teacherId AND leave_type='$type' AND status='approved'")->fetch_row()[0];
            $remaining = max(0, $info['limit'] - $used);
        ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-<?= $info['color'] ?> border-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fw-semibold"><?= $info['label'] ?></div>
                            <div class="text-muted small">Used: <?= $used ?> / <?= $info['limit'] ?> days</div>
                        </div>
                        <div class="text-end">
                            <div class="fs-4 fw-bold text-<?= $info['color'] ?>"><?= $remaining ?></div>
                            <div class="text-muted" style="font-size:11px">remaining</div>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height:4px">
                        <div class="progress-bar bg-<?= $info['color'] ?>"
                             style="width:<?= min(100, ($used / $info['limit']) * 100) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Leave history -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table id="leaveHistTable" class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Type</th><th>From</th><th>To</th><th>Days</th>
                        <th>Reason</th><th>Applied On</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myLeaves as $i => $l): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><span class="badge bg-info"><?= ucfirst($l['leave_type']) ?></span></td>
                        <td><?= formatDate($l['from_date']) ?></td>
                        <td><?= formatDate($l['to_date']) ?></td>
                        <td><?= (new DateTime($l['from_date']))->diff(new DateTime($l['to_date']))->days + 1 ?></td>
                        <td class="small" style="max-width:200px"><?= sanitize($l['reason']) ?></td>
                        <td class="small"><?= formatDate($l['applied_on']) ?></td>
                        <td><?= getStatusBadge($l['status']) ?>
                            <?php if ($l['admin_remarks']): ?>
                            <div class="text-muted" style="font-size:11px"><?= sanitize($l['admin_remarks']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($l['status'] === 'pending'): ?>
                            <form method="POST" onsubmit="return confirm('Cancel this leave request?')">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0">Cancel</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Apply Leave Modal -->
<div class="modal fade" id="applyLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-times me-2"></i>Apply for Leave</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="apply">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Leave Type *</label>
                        <select name="leave_type" class="form-select" required>
                            <option value="casual">Casual Leave</option>
                            <option value="sick">Sick Leave</option>
                            <option value="earned">Earned Leave</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">From Date *</label>
                            <input type="date" name="from_date" class="form-control"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">To Date *</label>
                            <input type="date" name="to_date" class="form-control"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason *</label>
                        <textarea name="reason" class="form-control" rows="3"
                                  placeholder="Please describe the reason for leave..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
