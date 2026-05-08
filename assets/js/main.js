/* ============================================================
   School Time Management System - Main JavaScript
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Initialize DataTables ----
    const dtTargets = [
        '#teacherTable', '#subjectTable', '#ttTable', '#ttList',
        '#leaveTable', '#leaveHistTable', '#attReportTable', '#attTable'
    ];
    dtTargets.forEach(function (selector) {
        const el = document.querySelector(selector);
        if (el && typeof $.fn !== 'undefined' && $.fn.DataTable) {
            $(selector).DataTable({
                pageLength: 25,
                responsive: true,
                language: {
                    search: '<i class="fas fa-search"></i>',
                    searchPlaceholder: 'Search...',
                    emptyTable: 'No records found',
                    zeroRecords: 'No matching records'
                }
            });
        }
    });

    // ---- Mobile Sidebar Toggle ----
    const sidebarToggle = document.getElementById('sidebarToggleMobile');
    const sidebar       = document.getElementById('sidebar');

    let overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // ---- Notification auto-mark read ----
    document.querySelectorAll('.notif-item').forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            fetch('/school-management/ajax/mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            }).then(() => {
                this.classList.remove('fw-semibold', 'bg-light');
            });
        });
    });

    // ---- Auto-dismiss alerts after 4 seconds ----
    setTimeout(function () {
        document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            if (bsAlert) bsAlert.close();
        });
    }, 4000);

    // ---- Dark mode toggle ----
    const darkToggle = document.getElementById('darkModeToggle');
    if (darkToggle) {
        const isDark = localStorage.getItem('darkMode') === 'true';
        if (isDark) document.documentElement.setAttribute('data-theme', 'dark');

        darkToggle.addEventListener('click', function () {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            if (currentTheme === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('darkMode', 'false');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('darkMode', 'true');
            }
        });
    }

    // ---- Confirm delete forms ----
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(this.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ---- Date picker: ensure to_date >= from_date ----
    const fromDate = document.querySelector('input[name="from_date"]');
    const toDate   = document.querySelector('input[name="to_date"]');
    if (fromDate && toDate) {
        fromDate.addEventListener('change', function () {
            if (toDate.value && toDate.value < this.value) {
                toDate.value = this.value;
            }
            toDate.min = this.value;
        });
    }

    // ---- Periodic notification refresh (every 60 seconds) ----
    if (document.querySelector('.notif-badge-count')) {
        setInterval(refreshNotifications, 60000);
    }

    function refreshNotifications() {
        fetch('/school-management/ajax/get_notifications.php')
            .then(r => r.json())
            .then(data => {
                const badge = document.querySelector('.notif-badge-count');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(() => {});
    }

    // ---- Print button ----
    document.querySelectorAll('[data-print]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.print();
        });
    });

    // ---- Attendance: keyboard shortcut P/A/L ----
    const attTable = document.querySelector('#attendanceForm');
    if (attTable) {
        console.log('Attendance form ready. Use All Present button to mark all.');
    }

    // ---- Form validation feedback ----
    document.querySelectorAll('form[novalidate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ---- Tooltips ----
    document.querySelectorAll('[title]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

});
