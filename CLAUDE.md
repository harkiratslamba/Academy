# CLAUDE.md — School Time Management System

## ⚠️ MANDATORY: BEFORE IMPLEMENTING ANY CHANGE

Whenever a developer or user proposes a new feature, bugfix, or refactor:
1. Read this file fully
2. Read the relevant module doc in docs/modules/
3. Check docs/CHANGE-IMPACT-GUIDE.md for cross-module impacts
4. List ALL files that will be affected: Models, Views, Controllers, Routes, Migrations
5. State if DB migration is needed
6. State if composer.json autoload update is needed
7. Confirm scope with developer BEFORE coding

## Project Overview

School Time Management & Teacher Substitution System  
Stack: Laravel 11 · PHP 8.4 · MySQL · Bootstrap 5.3 · jQuery 3.7 · DataTables 1.13 · nwidart/laravel-modules v13  
Auth: Username-based (not email). Roles: admin, teacher, coordinator  
Target: http://localhost/school-management/public (XAMPP)

## Quick Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# Edit .env: DB_DATABASE=school_management, DB_USERNAME=root, DB_PASSWORD=
php artisan migrate --seed
```

Demo: admin/admin123 · rajesh/teacher123 (and priya, amit, sunita, vikram)

## Module Registry

| Module | Namespace | Controller(s) | Routes File | Views Path | Model(s) |
|--------|-----------|---------------|-------------|------------|----------|
| Auth | Modules\Auth | AuthController | Modules/Auth/routes/web.php | Modules/Auth/resources/views/ | App\Models\User |
| Dashboard | Modules\Dashboard | DashboardController | Modules/Dashboard/routes/web.php | Modules/Dashboard/resources/views/ | Teacher, Attendance, Substitution, Leave, Timetable |
| Teacher | Modules\Teacher | TeacherController, TeacherDashboardController, TeacherTimetableController, TeacherLeaveController, TeacherAttendanceController, TeacherAvailabilityController | Modules/Teacher/routes/web.php | Modules/Teacher/resources/views/admin/ + portal/ | Teacher, User |
| Classes | Modules\Classes | ClassesController | Modules/Classes/routes/web.php | Modules/Classes/resources/views/admin/ | SchoolClass, Section, Room |
| Subject | Modules\Subject | SubjectController | Modules/Subject/routes/web.php | Modules/Subject/resources/views/admin/ | Subject |
| Timetable | Modules\Timetable | TimetableController | Modules/Timetable/routes/web.php | Modules/Timetable/resources/views/admin/ | Timetable, TeacherAvailability |
| Substitution | Modules\Substitution | SubstitutionController | Modules/Substitution/routes/web.php | Modules/Substitution/resources/views/admin/ | Substitution |
| Attendance | Modules\Attendance | AttendanceController | Modules/Attendance/routes/web.php | Modules/Attendance/resources/views/admin/ | Attendance |
| Leave | Modules\Leave | LeaveController | Modules/Leave/routes/web.php | Modules/Leave/resources/views/admin/ | Leave |
| Report | Modules\Report | ReportController | Modules/Report/routes/web.php | Modules/Report/resources/views/admin/ | (uses DB facade + Teacher, Leave) |

## Complete Route Map

### Auth Routes (no prefix, guest middleware)
| Method | URL | Route Name | Controller@Method |
|--------|-----|------------|-------------------|
| GET | / | login | AuthController@showLogin |
| POST | /login | login.submit | AuthController@login |
| POST | /logout | logout | AuthController@logout |

### Admin Routes (prefix: admin/, middleware: auth + role:admin,coordinator)
| Method | URL | Route Name | Controller@Method |
|--------|-----|------------|-------------------|
| GET | admin/dashboard | admin.dashboard | DashboardController@index |
| GET | admin/teachers | admin.teachers.index | TeacherController@index |
| POST | admin/teachers | admin.teachers.store | TeacherController@store |
| PUT | admin/teachers/{teacher} | admin.teachers.update | TeacherController@update |
| DELETE | admin/teachers/{teacher} | admin.teachers.destroy | TeacherController@destroy |
| POST | admin/teachers/{teacher}/reset-password | admin.teachers.resetPassword | TeacherController@resetPassword |
| GET | admin/classes | admin.classes.index | ClassesController@index |
| POST | admin/classes | admin.classes.store | ClassesController@storeClass |
| DELETE | admin/classes/{id} | admin.classes.destroy | ClassesController@destroyClass |
| POST | admin/sections | admin.sections.store | ClassesController@storeSection |
| DELETE | admin/sections/{id} | admin.sections.destroy | ClassesController@destroySection |
| GET | admin/subjects | admin.subjects.index | SubjectController@index |
| POST | admin/subjects | admin.subjects.store | SubjectController@store |
| PUT | admin/subjects/{subject} | admin.subjects.update | SubjectController@update |
| DELETE | admin/subjects/{subject} | admin.subjects.destroy | SubjectController@destroy |
| GET | admin/timetable | admin.timetable.index | TimetableController@index |
| POST | admin/timetable | admin.timetable.store | TimetableController@store |
| DELETE | admin/timetable/{id} | admin.timetable.destroy | TimetableController@destroy |
| PATCH | admin/timetable/{id}/toggle | admin.timetable.toggle | TimetableController@toggle |
| GET | admin/substitution | admin.substitution.index | SubstitutionController@index |
| POST | admin/substitution | admin.substitution.store | SubstitutionController@store |
| DELETE | admin/substitution/{id} | admin.substitution.destroy | SubstitutionController@destroy |
| POST | admin/substitution/mark-absent | admin.substitution.markAbsent | SubstitutionController@markAbsent |
| GET | admin/substitution/available-teachers | admin.substitution.availableTeachers | SubstitutionController@availableTeachers |
| GET | admin/attendance | admin.attendance.index | AttendanceController@adminIndex |
| POST | admin/attendance/bulk | admin.attendance.bulk | AttendanceController@markBulk |
| GET | admin/leaves | admin.leaves.admin | LeaveController@adminIndex |
| POST | admin/leaves/{id}/approve | admin.leaves.approve | LeaveController@approve |
| POST | admin/leaves/{id}/reject | admin.leaves.reject | LeaveController@reject |
| GET | admin/reports | admin.reports | ReportController@index |

### Teacher Portal Routes (prefix: teacher/, middleware: auth + role:teacher)
| Method | URL | Route Name | Controller@Method |
|--------|-----|------------|-------------------|
| GET | teacher/dashboard | teacher.dashboard | TeacherDashboardController@index |
| GET | teacher/timetable | teacher.timetable | TeacherTimetableController@index |
| GET | teacher/availability | teacher.availability | TeacherAvailabilityController@index |
| POST | teacher/availability | teacher.availability.store | TeacherAvailabilityController@store |
| GET | teacher/leaves | teacher.leaves.index | TeacherLeaveController@index |
| POST | teacher/leaves | teacher.leaves.store | TeacherLeaveController@store |
| DELETE | teacher/leaves/{id} | teacher.leaves.cancel | TeacherLeaveController@cancel |
| GET | teacher/attendance | teacher.attendance | TeacherAttendanceController@index |

### API Routes (middleware: auth)
| Method | URL | Purpose |
|--------|-----|---------|
| GET | /api/sections/{classId} | Returns sections JSON for a class (used in timetable form) |
| GET | /api/available-teachers | Returns available substitute teachers JSON |
| GET | /notifications/poll | Returns unread count stub (future feature) |
| POST | /notifications/read | Mark notification read stub |

## Database Tables Quick Reference

| Table | Model | Key Columns | Unique Constraints |
|-------|-------|-------------|-------------------|
| users | App\Models\User | id, username, role enum(admin,teacher,coordinator), teacher_id, status | username, email |
| teachers | Teacher | id, user_id, name, employee_id, subject_specialization, status enum(active,inactive) | email, employee_id |
| classes | SchoolClass | id, class_name | class_name |
| sections | Section | id, class_id, section_name | - |
| rooms | Room | id, room_number, capacity | room_number |
| subjects | Subject | id, subject_name, subject_code, class_id | subject_code |
| timetables | Timetable | id, class_id, section_id, subject_id, teacher_id, room_id, day, period_number, start_time, end_time, is_active | (class_id,section_id,day,period_number), (teacher_id,day,period_number) |
| teacher_availabilities | TeacherAvailability | id, teacher_id, date, period_number, status, reason | (teacher_id,date,period_number) |
| attendances | Attendance | id, teacher_id, date, status enum(present,absent,late,on_leave), marked_by | (teacher_id,date) |
| substitutions | Substitution | id, original_teacher_id, substitute_teacher_id, timetable_id, date, status, assigned_by | - |
| leaves | Leave | id, teacher_id, leave_type, from_date, to_date, reason, status, approved_by | - |
| school_notifications | SchoolNotification | id, user_id, title, message, type, is_read | - |

## Key Architectural Patterns

### Authentication
- Field: `username` (not email) via Auth::attempt(['username' => ...])
- Role middleware alias: `role` → registered in bootstrap/app.php
- Usage: ->middleware(['auth', 'role:admin,coordinator']) or ->middleware(['auth', 'role:teacher'])
- File: Modules/Auth/app/Http/Middleware/RoleMiddleware.php

### Cross-Module Model References
Use full namespace: `Modules\Teacher\Models\Teacher::class`
All module controllers extend `Illuminate\Routing\Controller` (NOT App\Http\Controllers\Controller)

### Clash Detection (Timetable)
DB UNIQUE constraints prevent double-booking. Controllers catch `Illuminate\Database\QueryException` with SQLSTATE 23000 for duplicate key violations.

### Shared Layout
File: resources/views/layouts/app.blade.php
All views: `@extends('layouts.app')` + `@section('content')` + optionally `@push('scripts')` / `@push('styles')`
Flash: `session('success')` / `session('error')` → auto-displayed Bootstrap alerts

### UPSERT Pattern (Attendance)
`Attendance::updateOrCreate(['teacher_id' => $id, 'date' => $date], ['status' => $status, ...])`
One record per teacher per day (enforced by UNIQUE constraint).

### Substitution Core Algorithm (availableTeachers)
Excludes teachers who are: (1) absent that day, (2) have own timetable at that period, (3) already assigned as sub for that period
Sorts: same_subject match DESC, workload (monthly sub count) ASC

## New Module Checklist

When adding a new module:
1. `php artisan module:make ModuleName`
2. Add to `composer.json` autoload.psr-4: `"Modules\\ModuleName\\": "Modules/ModuleName/app/"`
3. Add seeder namespace if needed: `"Modules\\ModuleName\\Database\\Seeders\\": "Modules/ModuleName/database/seeders/"`
4. Run `composer dump-autoload`
5. Create migration: `php artisan make:migration name --path=Modules/ModuleName/database/migrations`
6. Add sidebar link in `resources/views/layouts/app.blade.php`
7. Add route to middleware group in `Modules/ModuleName/routes/web.php`
8. Update this CLAUDE.md Module Registry table
9. Create docs/modules/NEWMODULE.md

## Documentation Files

| File | Purpose |
|------|---------|
| CLAUDE.md | This file — AI master reference |
| docs/SYSTEM-OVERVIEW.md | Architecture diagrams, tech stack, request lifecycle |
| docs/DATABASE-SCHEMA.md | All DB tables with columns, ER diagram |
| docs/API-REFERENCE.md | API endpoints and background processes |
| docs/CHANGE-IMPACT-GUIDE.md | What gets affected when you change anything |
| docs/USER-MANUAL.md | Non-technical guide for admin and teacher users |
| docs/modules/AUTH.md | Auth module MVC flows |
| docs/modules/DASHBOARD.md | Dashboard module MVC flows |
| docs/modules/TEACHER.md | Teacher module MVC flows (admin + portal) |
| docs/modules/CLASSES.md | Classes & Sections MVC flows |
| docs/modules/SUBJECT.md | Subject module MVC flows |
| docs/modules/TIMETABLE.md | Timetable module MVC flows |
| docs/modules/SUBSTITUTION.md | Substitution module MVC flows (CORE FEATURE) |
| docs/modules/ATTENDANCE.md | Attendance module MVC flows |
| docs/modules/LEAVE.md | Leave module MVC flows |
| docs/modules/REPORT.md | Report module logic and queries |

## Common Commands

```bash
php artisan migrate --seed          # Fresh DB with demo data
php artisan migrate:fresh --seed    # Wipe and reseed
php artisan module:make ModuleName  # Create new module
php artisan module:list             # Show module status
php artisan route:list              # All registered routes
composer dump-autoload              # After adding module namespace
```

---
*Last updated: 2026-07. Architecture: Laravel 11 + nwidart/laravel-modules v13.*
