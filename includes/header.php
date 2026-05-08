<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?><?= APP_NAME ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
    <div class="container-fluid">
        <button class="btn btn-link text-white me-2 d-lg-none" id="sidebarToggleMobile">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        <a class="navbar-brand fw-bold" href="#">
            <i class="fas fa-school me-2"></i><?= APP_NAME ?>
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">
            <!-- Notifications -->
            <?php
            $notifCount   = getUnreadNotificationCount($conn, $_SESSION['user_id']);
            $notifications = getRecentNotifications($conn, $_SESSION['user_id'], 5);
            ?>
            <div class="dropdown">
                <button class="btn btn-link text-white position-relative" data-bs-toggle="dropdown">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if ($notifCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $notifCount ?>
                    </span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="width:320px;max-height:400px;overflow-y:auto">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <?php if (empty($notifications)): ?>
                    <li><span class="dropdown-item text-muted small">No new notifications</span></li>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                        <li>
                            <a class="dropdown-item <?= $n['is_read'] ? '' : 'fw-semibold bg-light' ?> py-2 notif-item"
                               href="#" data-id="<?= $n['id'] ?>">
                                <div class="small text-<?= $n['type'] ?>"><?= sanitize($n['title']) ?></div>
                                <div class="small text-muted"><?= sanitize($n['message']) ?></div>
                                <div class="text-muted" style="font-size:11px"><?= formatDate($n['created_at']) ?></div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center small" href="#">View all</a></li>
                </ul>
            </div>

            <!-- User menu -->
            <div class="dropdown">
                <button class="btn btn-link text-white d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle fa-lg"></i>
                    <span class="d-none d-md-inline"><?= sanitize($_SESSION['username']) ?></span>
                    <span class="badge bg-light text-primary"><?= ucfirst($_SESSION['role']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><h6 class="dropdown-header"><?= sanitize($_SESSION['username']) ?></h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="wrapper d-flex">
