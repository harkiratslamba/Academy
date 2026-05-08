# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**School Time Management & Teacher Substitution System** — A Laravel 11 + MySQL ERP for schools using modular architecture (`nwidart/laravel-modules`).

**Stack:** Laravel 11 · MySQL · Bootstrap 5 · jQuery · Blade · nwidart/laravel-modules  
**Target URL:** `http://localhost/school-management/public`

## Local Setup (XAMPP)

```bash
# 1. Place folder at C:\xampp\htdocs\school-management\
# 2. Create database in phpMyAdmin: school_management
# 3. Copy .env.example → .env, update DB_DATABASE=school_management, DB_USERNAME=root, DB_PASSWORD=
# 4. Run:
composer install
php artisan key:generate
php artisan migrate --seed
# 5. Visit http://localhost/school-management/public
```

**Demo credentials:**
- Admin: `admin` / `admin123`
- Teacher: `rajesh` / `teacher123`

## Common Commands

```bash
php artisan migrate --seed          # Fresh DB with demo data
php artisan migrate:fresh --seed    # Wipe and reseed
php artisan module:make ModuleName  # Create a new module
php artisan make:migration name --path=Modules/ModuleName/database/migrations
php artisan module:list             # Show all modules and status
```

## Module Structure

All features live in `Modules/`. Each module is self-contained:

```
Modules/
├── Auth/            # Login, logout, RoleMiddleware
├── Dashboard/       # Admin dashboard (stats, today's timetable, alerts)
├── Teacher/         # Admin CRUD + Teacher portal (dashboard, timetable, availability, leaves, attendance)
├── Classes/         # Class, Section, Room management
├── Subject/         # Subject CRUD
├── Timetable/       # Timetable CRUD with clash detection; TeacherAvailability model
├── Substitution/    # Core feature — absent marking, free-teacher lookup, assignment
├── Attendance/      # Bulk daily attendance marking (admin) + personal view (teacher)
├── Leave/           # Leave apply/approve/reject workflow
└── Report/          # Monthly reports (attendance %, substitutions, leaves, coverage)
```

Each module follows this internal layout:
```
Modules/ModuleName/
├── app/
│   ├── Http/Controllers/    # Module controllers
│   └── Models/              # Eloquent models
├── database/
│   └── migrations/          # Module-specific migrations
├── resources/views/
│   ├── admin/               # Admin-facing Blade views
│   └── portal/              # Teacher-facing Blade views (Teacher module)
├── routes/web.php           # Module routes
└── module.json
```

## Key Patterns

### Routing & Auth
- Route prefix `admin/` + middleware `['auth', 'role:admin,coordinator']` for all admin pages
- Route prefix `teacher/` + middleware `['auth', 'role:teacher']` for teacher portal
- Named routes: `admin.dashboard`, `admin.teachers.*`, `admin.timetable.*`, `teacher.dashboard`, etc.
- `RoleMiddleware` registered as `role` alias in `bootstrap/app.php`
- Login uses `username` field (not `email`); guard is default web

### Controllers
All module controllers extend `Illuminate\Routing\Controller` (not `App\Http\Controllers\Controller`).

### Models & Relationships
| Model | Module | Table |
|-------|--------|-------|
| `User` | App | `users` |
| `Teacher` | Teacher | `teachers` |
| `SchoolClass` | Classes | `classes` |
| `Section` | Classes | `sections` |
| `Room` | Classes | `rooms` |
| `Subject` | Subject | `subjects` |
| `Timetable` | Timetable | `timetables` |
| `TeacherAvailability` | Timetable | `teacher_availabilities` |
| `Substitution` | Substitution | `substitutions` |
| `Attendance` | Attendance | `attendances` |
| `Leave` | Leave | `leaves` |
| `SchoolNotification` | App | `school_notifications` |

`users.teacher_id` → links user to teacher profile.  
`teachers.user_id` → links teacher back to user login.

### Substitution Logic (Core Feature)
`Modules/Substitution/app/Http/Controllers/SubstitutionController.php`:
1. Admin marks a teacher absent → `attendances` table (status `absent`/`on_leave`)
2. Dashboard badge counts timetable entries for today where teacher is absent and no substitute assigned
3. `availableTeachers(Request)` returns JSON of teachers who: are not absent, have no own timetable period at that slot, are not already assigned as substitute — sorted same-subject first, then lowest workload
4. `store(Request)` inserts `substitutions` row and creates a `school_notifications` record for the substitute teacher

### Blade Views
All views extend `layouts.app` (`resources/views/layouts/app.blade.php`).  
Admin views in `Modules/ModuleName/resources/views/admin/`.  
Teacher portal views in `Modules/Teacher/resources/views/portal/`.

### Flash Messages
```php
redirect()->back()->with('success', 'Done!');
redirect()->back()->with('error', 'Failed!');
```
Displayed in `layouts.app` as auto-dismissing Bootstrap alerts.

### New Module Namespace Registration
After `php artisan module:make NewModule`, add to `composer.json` autoload:
```json
"Modules\\NewModule\\": "Modules/NewModule/app/",
"Modules\\NewModule\\Database\\Seeders\\": "Modules/NewModule/database/seeders/"
```
Then run `composer dump-autoload`.

## Development Notes

- `vendor/` is in `.gitignore` — run `composer install` after cloning
- `.env` is in `.gitignore` — copy `.env.example` and configure DB
- The `APP_URL` in `.env` must match XAMPP path for asset/route generation
- Bootstrap 5.3, Font Awesome 6, jQuery 3.7, DataTables 1.13 loaded from CDN in `layouts.app`
- Timetable clash detection uses UNIQUE constraint on `(teacher_id, day, period_number)` — catch `QueryException` on insert
