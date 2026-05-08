# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**School Time Management & Teacher Substitution System** — A PHP/MySQL ERP for schools.
Manages teacher timetables, marks teacher attendance, automatically surfaces available substitutes when a teacher is absent, and handles leave requests.

**Stack:** Core PHP · MySQL · Bootstrap 5 · jQuery · AJAX · Apache (XAMPP)
**Target URL:** `http://localhost/school-management`

## Local Setup (XAMPP)

1. Place this folder at `C:\xampp\htdocs\school-management`
2. In phpMyAdmin, import `database/school_management.sql`
3. Start Apache + MySQL from XAMPP Control Panel
4. Visit `http://localhost/school-management`

**Demo credentials:**
- Admin: `admin` / `admin123`
- Teacher: `rajesh` / `teacher123`

## Architecture

```
school-management/
├── config/db.php              # DB connection + app constants (DB_HOST, APP_URL)
├── database/school_management.sql  # Full schema + sample data
├── includes/
│   ├── functions.php          # All shared helpers (sanitize, auth, notifications, etc.)
│   ├── header.php             # HTML <head> + Bootstrap navbar (uses APP_URL constant)
│   ├── footer.php             # Closing tags + Bootstrap/jQuery/DataTables JS
│   ├── admin_sidebar.php      # Admin nav (also runs live badge queries)
│   └── teacher_sidebar.php    # Teacher nav
├── index.php                  # Login (redirects to role-based dashboard on success)
├── logout.php
├── admin/                     # Role: admin, coordinator
│   ├── dashboard.php          # Stats, today's timetable, unassigned class alerts
│   ├── teachers.php           # CRUD for teachers + login credentials
│   ├── classes.php            # Classes and sections management
│   ├── subjects.php           # Subject CRUD
│   ├── timetable.php          # Timetable CRUD with clash detection
│   ├── substitution.php       # Core feature — mark absent, fetch free teachers, assign
│   ├── attendance.php         # Bulk daily attendance marking
│   ├── leaves.php             # Approve/reject leave requests
│   └── reports.php            # Monthly attendance, substitution, leave, coverage reports
├── teacher/                   # Role: teacher
│   ├── dashboard.php          # Today's schedule + extra duty (substitute) classes
│   ├── timetable.php          # Weekly grid + list view
│   ├── availability.php       # Mark period-specific or full-day availability
│   ├── leaves.php             # Apply for/cancel leaves; shows leave balance
│   └── attendance.php         # Personal monthly attendance record
└── ajax/
    ├── get_available_teachers.php  # Returns free teachers for a given period/date (JSON)
    ├── get_notifications.php       # Unread count + recent notifications (JSON)
    ├── mark_notification_read.php  # Marks 1 or all notifications read (POST)
    └── get_sections.php            # Returns sections for a given class_id (JSON)
```

## Key Patterns

### Authentication & Session
- `$_SESSION` keys: `user_id`, `username`, `role`, `teacher_id`
- `requireRole('admin')` or `requireAnyRole(['admin','coordinator'])` at top of every admin page
- `requireRole('teacher')` for teacher pages
- Session regenerated on login via `session_regenerate_id(true)`

### Page Template
Every page follows this structure:
```php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('admin', '../index.php');   // auth check

// ... query/form logic ...

$pageTitle = 'Page Name';
require_once '../includes/header.php';         // opens <body> and <div class="wrapper d-flex">
require_once '../includes/admin_sidebar.php';  // opens <div class="main-content">
?>
<!-- page HTML here -->
<?php require_once '../includes/footer.php';   // closes main-content, wrapper, adds scripts ?>
```

### Database Conventions
- All queries use **prepared statements** (`$conn->prepare()`)
- Output always wrapped in `sanitize()` (= `htmlspecialchars + strip_tags + trim`)
- Key DB constants: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` in `config/db.php`
- `APP_URL` constant used for all internal links (not relative paths)
- Passwords stored with `password_hash(PASSWORD_DEFAULT)`

### Substitution Logic (Core Feature)
Located in `admin/substitution.php` + `ajax/get_available_teachers.php`:
1. Admin marks a teacher absent via `attendance` table (status `absent`/`on_leave`)
2. `admin_sidebar.php` automatically shows a red badge counting unassigned classes
3. On the substitution page, all timetable entries for that day where the teacher is absent and no substitute is assigned are shown
4. Clicking "Assign" calls `ajax/get_available_teachers.php` which filters by:
   - Teacher is not absent that day
   - Teacher has no own timetable period at that slot
   - Teacher is not already assigned as substitute for that period
   - Sorts: same-subject teachers first, then by lowest workload
5. On form submit, a row is inserted into `substitutions` and a notification is sent to the substitute teacher's `user_id`

### AJAX Pattern
```javascript
fetch('<?= APP_URL ?>/ajax/get_available_teachers.php?timetable_id=X&date=Y')
  .then(r => r.json())
  .then(data => { /* data.teachers[] */ });
```
All AJAX endpoints check `isLoggedIn()` and return JSON with `error` key on failure.

### Notifications
Sent via `sendNotification($conn, $userId, $title, $message, $type)`.
Displayed in the navbar dropdown rendered by `includes/header.php`.
Marked read via `ajax/mark_notification_read.php`.

## Database Schema Summary

| Table | Purpose |
|-------|---------|
| `users` | Login credentials + role |
| `teachers` | Teacher profile, linked to `users.id` |
| `classes` | e.g. "Class 9" |
| `sections` | e.g. "A", "B" per class |
| `subjects` | Subject catalogue |
| `rooms` | Room/lab inventory |
| `timetable` | Weekly schedule; UNIQUE on `(teacher_id, day, period_number)` to enforce clash prevention |
| `teacher_availability` | Per-date/period override for a teacher |
| `substitutions` | Substitute assignments |
| `attendance` | Daily teacher attendance (UNIQUE on `teacher_id, date`) |
| `leaves` | Leave applications + approval workflow |
| `notifications` | In-app notifications |
| `activity_logs` | Audit trail |

## Development Notes

- `config/db.php` defaults to XAMPP defaults (`root`, no password). Change for any other environment.
- The `APP_URL` constant (`http://localhost/school-management`) is used throughout for links and AJAX URLs — update it when deploying.
- `admin_sidebar.php` runs live SQL queries (unassigned class count, pending leave count) on every page load; keep these queries indexed.
- Bootstrap 5, jQuery 3.7, Font Awesome 6, and DataTables 1.13 are loaded from CDN in `includes/header.php` and `includes/footer.php`.
- `assets/css/style.css` includes a dark mode stub (`[data-theme="dark"]`) that can be activated by setting `localStorage.darkMode = 'true'`.
