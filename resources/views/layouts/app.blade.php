<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Academy')) — {{ config('app.name', 'Academy') }}</title>

    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- DataTables 1.13 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    @stack('styles')

    <style>
        :root {
            --sidebar-width: 240px;
            --sidebar-bg: #1a1f2e;
            --sidebar-hover: #2d3446;
            --sidebar-active: #0d6efd;
            --navbar-height: 56px;
        }

        body {
            background-color: #f0f2f5;
            font-size: 0.9rem;
        }

        /* Navbar */
        .top-navbar {
            height: var(--navbar-height);
            background: var(--sidebar-bg) !important;
            z-index: 1040;
        }

        .top-navbar .navbar-brand {
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }

        /* Sidebar */
        #sidebar {
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            overflow-y: auto;
            z-index: 1030;
            transition: transform 0.25s ease;
        }

        #sidebar .nav-link {
            color: rgba(255, 255, 255, 0.72);
            padding: 0.6rem 1.25rem;
            border-radius: 6px;
            margin: 1px 8px;
            font-size: 0.85rem;
            transition: background 0.15s, color 0.15s;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        #sidebar .nav-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }

        #sidebar .nav-link.active {
            background: var(--sidebar-active);
            color: #fff;
        }

        #sidebar .nav-link i {
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
        }

        #sidebar .sidebar-section {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.35);
            padding: 1rem 1.4rem 0.25rem;
            font-weight: 600;
        }

        /* Main content */
        #main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--navbar-height);
            min-height: calc(100vh - var(--navbar-height));
            padding: 1.5rem;
            transition: margin-left 0.25s ease;
        }

        /* Sidebar collapsed state */
        body.sidebar-collapsed #sidebar {
            transform: translateX(calc(-1 * var(--sidebar-width)));
        }

        body.sidebar-collapsed #main-content {
            margin-left: 0;
        }

        /* Cards */
        .card {
            border: none;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
            border-radius: 10px;
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid rgba(0,0,0,.08);
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }

        /* Stat cards */
        .stat-card {
            border-radius: 12px;
            padding: 1.25rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .stat-card .stat-icon {
            font-size: 2.5rem;
            opacity: 0.25;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .stat-card .stat-label {
            font-size: 0.8rem;
            opacity: 0.85;
            margin-top: 0.25rem;
        }

        /* Notification dropdown */
        .notification-dropdown {
            width: 340px;
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            border-bottom: 1px solid rgba(0,0,0,.06);
            padding: 0.65rem 1rem;
            cursor: pointer;
            transition: background 0.15s;
        }

        .notification-item:hover { background: #f8f9fa; }
        .notification-item.unread { background: #eef4ff; }

        /* Flash alerts */
        .flash-alert {
            position: fixed;
            top: calc(var(--navbar-height) + 0.75rem);
            right: 1rem;
            z-index: 2000;
            min-width: 300px;
            max-width: 420px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* Badge */
        #notif-badge { font-size: 0.65rem; }

        /* Scrollbar sidebar */
        #sidebar::-webkit-scrollbar { width: 4px; }
        #sidebar::-webkit-scrollbar-track { background: transparent; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 4px; }

        @media (max-width: 767px) {
            #sidebar { transform: translateX(calc(-1 * var(--sidebar-width))); }
            #main-content { margin-left: 0; }
            body.sidebar-open #sidebar { transform: translateX(0); }
        }
    </style>
</head>
<body>

{{-- ═══════════════════════════ TOP NAVBAR ═══════════════════════════ --}}
<nav class="navbar top-navbar fixed-top navbar-dark px-3 py-0">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-secondary border-0 text-white" id="sidebarToggle" title="Toggle Sidebar">
            <i class="fa fa-bars"></i>
        </button>
        <a class="navbar-brand mb-0" href="{{ auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : route('teacher.dashboard') }}">
            <i class="fa fa-graduation-cap me-1 text-primary"></i>
            {{ config('app.name', 'Academy') }}
        </a>
    </div>

    <div class="d-flex align-items-center gap-2">
        {{-- Notification Bell --}}
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary border-0 text-white position-relative" id="notifBtn"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-bell"></i>
                <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle d-none"
                      id="notif-badge">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0 notification-dropdown shadow" aria-labelledby="notifBtn">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                    <strong class="small">Notifications</strong>
                    <a href="#" class="small text-muted" id="markAllReadBtn">Mark all read</a>
                </div>
                <div id="notif-list">
                    <div class="text-center text-muted py-3 small">No notifications</div>
                </div>
            </div>
        </div>

        {{-- User Menu --}}
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary border-0 text-white d-flex align-items-center gap-1"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-user-circle fa-lg"></i>
                <span class="d-none d-md-inline small">{{ auth()->user()->name ?? auth()->user()->username }}</span>
                <i class="fa fa-caret-down small ms-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><h6 class="dropdown-header">{{ auth()->user()->username }}</h6></li>
                <li><span class="dropdown-item-text small text-muted">
                    <i class="fa fa-shield-alt me-1"></i>{{ ucfirst(auth()->user()->role) }}
                </span></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fa fa-sign-out-alt me-1"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

{{-- ═══════════════════════════ SIDEBAR ═══════════════════════════ --}}
<nav id="sidebar">
    <ul class="nav flex-column pt-2 pb-4">
        @if(auth()->user()->isAdmin())
            {{-- Admin / Coordinator --}}
            <li class="sidebar-section">Main</li>
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}"
                   class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            <li class="sidebar-section">Management</li>
            <li class="nav-item">
                <a href="{{ route('admin.teachers.index') }}"
                   class="nav-link {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">
                    <i class="fa fa-chalkboard-teacher"></i> Teachers
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.classes.index') }}"
                   class="nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}">
                    <i class="fa fa-school"></i> Classes
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.subjects.index') }}"
                   class="nav-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}">
                    <i class="fa fa-book"></i> Subjects
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.timetable.index') }}"
                   class="nav-link {{ request()->routeIs('admin.timetable.*') ? 'active' : '' }}">
                    <i class="fa fa-calendar-alt"></i> Timetable
                </a>
            </li>

            <li class="sidebar-section">Daily Operations</li>
            <li class="nav-item">
                <a href="{{ route('admin.attendance.index') }}"
                   class="nav-link {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}">
                    <i class="fa fa-user-check"></i> Attendance
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.substitution.index') }}"
                   class="nav-link {{ request()->routeIs('admin.substitution.*') ? 'active' : '' }}">
                    <i class="fa fa-exchange-alt"></i> Substitutions
                    @php
                        try {
                            $unassignedCount = \Illuminate\Support\Facades\DB::table('timetables as t')
                                ->join('attendances as a', function($j){
                                    $j->on('a.teacher_id','=','t.teacher_id')
                                      ->where('a.date', today()->toDateString())
                                      ->whereIn('a.status', ['absent','on_leave']);
                                })
                                ->whereRaw("t.day = DAYNAME(CURDATE())")
                                ->where('t.is_active', true)
                                ->whereNotExists(function($q){
                                    $q->from('substitutions as s')
                                      ->whereColumn('s.timetable_id','t.id')
                                      ->where('s.date', today()->toDateString())
                                      ->whereNotIn('s.status',['cancelled']);
                                })
                                ->count();
                        } catch (\Exception $e) { $unassignedCount = 0; }
                    @endphp
                    @if($unassignedCount > 0)
                        <span class="badge bg-danger ms-auto">{{ $unassignedCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.leaves.admin') }}"
                   class="nav-link {{ request()->routeIs('admin.leaves.*') ? 'active' : '' }}">
                    <i class="fa fa-calendar-times"></i> Leave Requests
                    @php
                        try {
                            $pendingLeaves = \Modules\Leave\Models\Leave::where('status','pending')->count();
                        } catch(\Exception $e){ $pendingLeaves = 0; }
                    @endphp
                    @if($pendingLeaves > 0)
                        <span class="badge bg-warning text-dark ms-auto">{{ $pendingLeaves }}</span>
                    @endif
                </a>
            </li>

            <li class="sidebar-section">Analytics</li>
            <li class="nav-item">
                <a href="{{ route('admin.reports') }}"
                   class="nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                    <i class="fa fa-chart-bar"></i> Reports
                </a>
            </li>

        @elseif(auth()->user()->isTeacher())
            {{-- Teacher --}}
            <li class="sidebar-section">My Panel</li>
            <li class="nav-item">
                <a href="{{ route('teacher.dashboard') }}"
                   class="nav-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                    <i class="fa fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('teacher.timetable') }}"
                   class="nav-link {{ request()->routeIs('teacher.timetable') ? 'active' : '' }}">
                    <i class="fa fa-calendar-alt"></i> My Timetable
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('teacher.attendance') }}"
                   class="nav-link {{ request()->routeIs('teacher.attendance') ? 'active' : '' }}">
                    <i class="fa fa-user-check"></i> My Attendance
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('teacher.leaves.index') }}"
                   class="nav-link {{ request()->routeIs('teacher.leaves.*') ? 'active' : '' }}">
                    <i class="fa fa-calendar-times"></i> Leave Requests
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('teacher.availability') }}"
                   class="nav-link {{ request()->routeIs('teacher.availability') ? 'active' : '' }}">
                    <i class="fa fa-clock"></i> Availability
                </a>
            </li>
        @endif
    </ul>
</nav>

{{-- ═══════════════════════════ MAIN CONTENT ═══════════════════════════ --}}
<main id="main-content">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible flash-alert shadow" role="alert">
            <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible flash-alert shadow" role="alert">
            <i class="fa fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible flash-alert shadow" role="alert">
            <i class="fa fa-exclamation-triangle me-1"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible flash-alert shadow" role="alert">
            <i class="fa fa-exclamation-circle me-1"></i>
            <strong>Please fix the errors:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</main>

{{-- ═══════════════════════════ SCRIPTS ═══════════════════════════ --}}
<!-- jQuery 3.7 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables 1.13 -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function () {
    // ── Sidebar toggle ──────────────────────────────────────────────
    $('#sidebarToggle').on('click', function () {
        if (window.innerWidth < 768) {
            $('body').toggleClass('sidebar-open');
        } else {
            $('body').toggleClass('sidebar-collapsed');
        }
    });

    // Close sidebar on outside click (mobile)
    $(document).on('click', function (e) {
        if (window.innerWidth < 768 &&
            !$(e.target).closest('#sidebar, #sidebarToggle').length) {
            $('body').removeClass('sidebar-open');
        }
    });

    // ── DataTables auto-init ─────────────────────────────────────────
    $('[data-datatable]').each(function () {
        var opts = {
            pageLength: 25,
            language: { search: '', searchPlaceholder: 'Search…' },
            responsive: true,
        };
        var extra = $(this).data('datatable');
        if (typeof extra === 'object') $.extend(opts, extra);
        $(this).DataTable(opts);
    });

    // ── Auto-dismiss flash alerts after 5 s ─────────────────────────
    setTimeout(function () {
        $('.flash-alert').fadeOut(400, function () { $(this).remove(); });
    }, 5000);

    // ── Notification polling ─────────────────────────────────────────
    function loadNotifications() {
        $.getJSON('{{ url("/notifications/poll") }}', function (data) {
            if (!data || data.error) return;

            var badge = $('#notif-badge');
            var count = data.unread_count || 0;
            if (count > 0) {
                badge.text(count > 99 ? '99+' : count).removeClass('d-none');
            } else {
                badge.addClass('d-none');
            }

            var html = '';
            if (data.notifications && data.notifications.length) {
                $.each(data.notifications, function (i, n) {
                    html += '<div class="notification-item' + (n.is_read ? '' : ' unread') + '" ' +
                            'data-id="' + n.id + '">' +
                            '<div class="small fw-semibold">' + $('<div>').text(n.title).html() + '</div>' +
                            '<div class="text-muted" style="font-size:0.78rem">' + $('<div>').text(n.message).html() + '</div>' +
                            '<div class="text-muted" style="font-size:0.72rem">' + n.created_at + '</div>' +
                            '</div>';
                });
            } else {
                html = '<div class="text-center text-muted py-3 small">No notifications</div>';
            }
            $('#notif-list').html(html);
        }).fail(function () { /* ignore polling errors */ });
    }

    // Mark individual notification read on click
    $(document).on('click', '.notification-item', function () {
        var id = $(this).data('id');
        if (!id) return;
        $.post('{{ url("/notifications/read") }}', { id: id, _token: $('meta[name=csrf-token]').attr('content') });
        $(this).removeClass('unread');
    });

    // Mark all read
    $('#markAllReadBtn').on('click', function (e) {
        e.preventDefault();
        $.post('{{ url("/notifications/read") }}', { all: 1, _token: $('meta[name=csrf-token]').attr('content') }, function () {
            $('.notification-item').removeClass('unread');
            $('#notif-badge').addClass('d-none');
        });
    });

    loadNotifications();
    setInterval(loadNotifications, 60000);
});
</script>

@stack('scripts')
</body>
</html>
