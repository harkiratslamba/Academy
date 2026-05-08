<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<!-- Sidebar -->
<nav class="sidebar bg-dark text-white" id="sidebar">
    <div class="sidebar-header p-3 border-bottom border-secondary">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                 style="width:40px;height:40px">
                <i class="fas fa-user-shield text-white"></i>
            </div>
            <div>
                <div class="fw-semibold small"><?= sanitize($_SESSION['username']) ?></div>
                <div class="text-muted" style="font-size:11px">Administrator</div>
            </div>
        </div>
    </div>

    <ul class="nav flex-column p-2 mt-1">
        <li class="nav-item">
            <a href="<?= APP_URL ?>/admin/dashboard.php"
               class="nav-link text-white rounded <?= $currentPage === 'dashboard.php' ? 'active bg-primary' : '' ?>">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/admin/teachers.php"
               class="nav-link text-white rounded <?= $currentPage === 'teachers.php' ? 'active bg-primary' : '' ?>">
                <i class="fas fa-chalkboard-teacher me-2"></i>Teachers
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/admin/classes.php"
               class="nav-link text-white rounded <?= $currentPage === 'classes.php' ? 'active bg-primary' : '' ?>">
                <i class="fas fa-school me-2"></i>Classes &amp; Sections
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/admin/subjects.php"
               class="nav-link text-white rounded <?= $currentPage === 'subjects.php' ? 'active bg-primary' : '' ?>">
                <i class="fas fa-book me-2"></i>Subjects
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/admin/timetable.php"
               class="nav-link text-white rounded <?= $currentPage === 'timetable.php' ? 'active bg-primary' : '' ?>">
                <i class="fas fa-calendar-alt me-2"></i>Timetable
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/admin/substitution.php"
               class="nav-link text-white rounded <?= $currentPage === 'substitution.php' ? 'active bg-primary' : '' ?>">
                <i class="fas fa-exchange-alt me-2"></i>Substitution
                <?php
                // Show count of unassigned classes today
                $today = date('Y-m-d');
                $todayDay = date('l');
                $unassigned = $conn->query("
                    SELECT COUNT(*) as cnt FROM timetable t
                    WHERE t.day = '$todayDay' AND t.is_active = 1
                    AND t.teacher_id IN (
                        SELECT teacher_id FROM attendance WHERE date = '$today' AND status IN ('absent','on_leave')
                    )
                    AND t.id NOT IN (
                        SELECT timetable_id FROM substitutions WHERE date = '$today' AND status != 'cancelled'
                    )
                ")->fetch_assoc()['cnt'];
                if ($unassigned > 0):
                ?>
                <span class="badge bg-danger float-end"><?= $unassigned ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/admin/attendance.php"
               class="nav-link text-white rounded <?= $currentPage === 'attendance.php' ? 'active bg-primary' : '' ?>">
                <i class="fas fa-user-check me-2"></i>Attendance
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/admin/leaves.php"
               class="nav-link text-white rounded <?= $currentPage === 'leaves.php' ? 'active bg-primary' : '' ?>">
                <i class="fas fa-calendar-times me-2"></i>Leave Requests
                <?php
                $pendingLeaves = $conn->query("SELECT COUNT(*) as cnt FROM leaves WHERE status = 'pending'")->fetch_assoc()['cnt'];
                if ($pendingLeaves > 0):
                ?>
                <span class="badge bg-warning text-dark float-end"><?= $pendingLeaves ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/admin/reports.php"
               class="nav-link text-white rounded <?= $currentPage === 'reports.php' ? 'active bg-primary' : '' ?>">
                <i class="fas fa-chart-bar me-2"></i>Reports
            </a>
        </li>
    </ul>
</nav>

<!-- Main Content Wrapper -->
<div class="main-content flex-grow-1">
