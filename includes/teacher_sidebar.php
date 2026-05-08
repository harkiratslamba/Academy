<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<!-- Sidebar -->
<nav class="sidebar bg-dark text-white" id="sidebar">
    <div class="sidebar-header p-3 border-bottom border-secondary">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center"
                 style="width:40px;height:40px">
                <i class="fas fa-chalkboard-teacher text-white"></i>
            </div>
            <div>
                <div class="fw-semibold small"><?= sanitize($_SESSION['username']) ?></div>
                <div class="text-muted" style="font-size:11px">Teacher</div>
            </div>
        </div>
    </div>

    <ul class="nav flex-column p-2 mt-1">
        <li class="nav-item">
            <a href="<?= APP_URL ?>/teacher/dashboard.php"
               class="nav-link text-white rounded <?= $currentPage === 'dashboard.php' ? 'active bg-success' : '' ?>">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/teacher/timetable.php"
               class="nav-link text-white rounded <?= $currentPage === 'timetable.php' ? 'active bg-success' : '' ?>">
                <i class="fas fa-calendar-alt me-2"></i>My Timetable
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/teacher/availability.php"
               class="nav-link text-white rounded <?= $currentPage === 'availability.php' ? 'active bg-success' : '' ?>">
                <i class="fas fa-clock me-2"></i>Availability
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/teacher/leaves.php"
               class="nav-link text-white rounded <?= $currentPage === 'leaves.php' ? 'active bg-success' : '' ?>">
                <i class="fas fa-calendar-times me-2"></i>Leave Requests
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="<?= APP_URL ?>/teacher/attendance.php"
               class="nav-link text-white rounded <?= $currentPage === 'attendance.php' ? 'active bg-success' : '' ?>">
                <i class="fas fa-user-check me-2"></i>My Attendance
            </a>
        </li>
    </ul>
</nav>

<!-- Main Content Wrapper -->
<div class="main-content flex-grow-1">
