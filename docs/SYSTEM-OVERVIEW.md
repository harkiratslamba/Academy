# System Overview — School Time Management & Teacher Substitution System

## Purpose

This system is a web-based ERP for schools that handles the daily operational challenge of managing teacher schedules and ensuring every class period is covered — even when teachers are absent.

Core functions:
- Manages school timetables (who teaches what, when, where)
- Tracks teacher attendance daily
- Detects when teachers are absent and which classes are uncovered
- Finds available substitute teachers (prioritized by subject match + workload)
- Manages leave requests with approval workflow
- Generates monthly reports

## Technology Stack

| Layer | Technology | Version | Notes |
|-------|------------|---------|-------|
| Backend Framework | Laravel | 11 | PHP 8.4 required |
| Module System | nwidart/laravel-modules | v13 | Modular architecture |
| Database | MySQL | 8.0+ | InnoDB, UNIQUE constraints for clash detection |
| Frontend CSS | Bootstrap | 5.3.2 | CDN loaded |
| Icons | Font Awesome | 6.5 | CDN loaded |
| JavaScript | jQuery | 3.7 | CDN loaded |
| Tables | DataTables | 1.13.8 | CDN, Bootstrap 5 integration |
| PHP | PHP | 8.4 | Required for Laravel 11 |
| Web Server | Apache (XAMPP) | - | Local development |

## System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Browser (User)                        │
└─────────────────────┬───────────────────────────────────┘
                      │ HTTP Request
                      ▼
┌─────────────────────────────────────────────────────────┐
│              Laravel Router (routes/web.php)             │
│              + Module Routes (Modules/*/routes/web.php)  │
└─────────────────────┬───────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│              Middleware Stack                            │
│   auth (check logged in) + role:X (check permission)   │
└─────────────────────┬───────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│           Module Controller                             │
│   Modules/[Module]/app/Http/Controllers/                │
│   1. Validate input                                     │
│   2. Call Eloquent Models                               │
│   3. Execute business logic                             │
│   4. Return Blade view OR redirect with flash           │
└──────────┬──────────────────────────┬───────────────────┘
           │                          │
           ▼                          ▼
┌──────────────────┐      ┌──────────────────────────────┐
│  Eloquent Models │      │    Blade View                │
│  (Read/Write DB) │      │  resources/views/layouts/app │
│                  │      │  + Modules/*/resources/views/ │
└──────────┬───────┘      └──────────────────────────────┘
           │
           ▼
┌──────────────────┐
│   MySQL Database │
│   school_mgmt    │
└──────────────────┘
```

## Module Map

```
Modules/
├── Auth/           ──→ Login, Logout, RoleMiddleware
├── Dashboard/      ──→ Admin stats & alerts (reads from 6 modules)
├── Teacher/        ──→ Admin: Teacher CRUD
│                       Teacher: Portal (dashboard, timetable, availability, leave, attendance)
├── Classes/        ──→ Class & Section management
├── Subject/        ──→ Subject CRUD
├── Timetable/      ──→ Schedule management + clash detection
├── Substitution/   ──→ ★ CORE FEATURE: absent detection + sub assignment
├── Attendance/     ──→ Daily attendance marking (bulk admin + personal teacher)
├── Leave/          ──→ Leave apply/approve/reject workflow
└── Report/         ──→ Monthly analytics (attendance, subs, leaves, coverage)
```

## Module Dependencies

Which modules depend on which (reads data from):

```
Auth ←── (no module deps, uses App\Models\User only)

Dashboard ←── Teacher, Attendance, Substitution, Timetable, Leave, Classes

Teacher ←── User (App model)

Timetable ←── Teacher, Classes, Subject

Substitution ←── Teacher, Timetable, Attendance (reads absent status)
             ──→ writes to Attendance (mark absent), Substitution (assign)

Attendance ←── Teacher

Leave ←── Teacher

Report ←── Teacher, Attendance, Substitution, Leave, Timetable (read-only)
```

## Authentication Flow

```
User visits any URL
       │
       ▼
  auth middleware
       │
       ├─── Not logged in ──→ Redirect to /login
       │
       └─── Logged in ──→ role middleware
                               │
                               ├─── Role matches ──→ Proceed to Controller
                               │
                               └─── Role mismatch ──→ 403 Forbidden
```

### Login Process:
1. GET / → shows login form (guest middleware, redirect if already logged in)
2. POST /login → AuthController@login
3. Auth::attempt(['username' => $username, 'password' => $password])
4. Check user.status (disabled account check)
5. Update users.last_login
6. Redirect: admin/coordinator → /admin/dashboard, teacher → /teacher/dashboard

## Request Lifecycle (Form Submission Example)

```
Teacher submits Leave Application form
       │
       ▼ POST /teacher/leaves
       │
       ▼ Route matches Modules/Teacher/routes/web.php
       │
       ▼ Middleware: auth (logged in?) + role:teacher
       │
       ▼ TeacherLeaveController@store
       │
       ▼ $request->validate([leave_type, from_date, to_date, reason])
       │
       │  Fails ──→ redirect back with validation errors
       │
       ▼ Leave::create([teacher_id, leave_type, from_date, to_date, reason, status='pending'])
       │
       ▼ INSERT INTO leaves (...)
       │
       ▼ redirect()->route('teacher.leaves.index')->with('success', 'Leave submitted')
       │
       ▼ layouts/app.blade.php renders success alert
```

## Substitution Core Workflow (Most Important Feature)

```
Step 1: Mark Teacher Absent
Admin marks teacher absent via:
  - Attendance page (POST /admin/attendance/bulk)
  - OR Substitution page mark-absent button (POST /admin/substitution/mark-absent)
       │
       ▼ attendances record: teacher_id + date + status='absent'

Step 2: Detect Unassigned Classes
Dashboard & Substitution page query:
  timetables WHERE day=today AND is_active=true
  JOIN attendances WHERE status IN (absent, on_leave)
  NOT EXISTS substitutions WHERE date=today AND NOT cancelled
       │
       ▼ Shows alert: "3 classes need a substitute"

Step 3: Find Available Teachers
POST /admin/substitution/available-teachers?timetable_id=X&date=Y
Algorithm:
  Exclude: absent teachers + teachers busy at that period + already assigned subs
  Sort: same subject specialization FIRST, then least workload (monthly sub count)
       │
       ▼ Returns JSON list to frontend

Step 4: Assign Substitute
Admin selects teacher, submits form
POST /admin/substitution
  3-layer validation → INSERT substitutions record
       │
       ▼ Unassigned class count decreases
```

## Deployment (XAMPP Local)

1. Place project at: C:\xampp\htdocs\school-management\
2. Start Apache and MySQL in XAMPP Control Panel
3. Create database `school_management` in phpMyAdmin
4. Copy .env.example → .env, configure DB settings
5. Run: `composer install && php artisan key:generate && php artisan migrate --seed`
6. Visit: http://localhost/school-management/public

---
*See docs/modules/ for per-module MVC flow documentation.*
*See docs/CHANGE-IMPACT-GUIDE.md before making any changes.*
