# Change Impact Guide

**Purpose:** Before making ANY change to this system, use this guide to identify all files and components that will be affected. This prevents missed updates, broken views, and bugs.

**How to use:** Find the section matching your planned change → read the "Also update" list → check all listed files before and after implementing.

---

## Database Schema Changes

### If you ADD/REMOVE a column from `users` table

**Directly affected:**
- `app/Models/User.php` → update `$fillable` array
- `database/migrations/` → create new migration file

**Indirectly affected (depends on which column):**

If changing `role` column:
- `Modules/Auth/app/Http/Middleware/RoleMiddleware.php` — role check logic
- `Modules/Auth/app/Http/Controllers/AuthController.php` — `redirectByRole()` switch
- ALL 10 module `routes/web.php` files — `role:admin,coordinator` / `role:teacher` middleware
- `database/seeders/DatabaseSeeder.php` — role assigned to seeded users

If changing `teacher_id` column:
- `TeacherController@store` — sets `teacher_id` after creating teacher
- `TeacherLeaveController@store` — reads `auth()->user()->teacher_id`
- `TeacherAttendanceController@index` — filters by `auth()->user()->teacher_id`
- `TeacherTimetableController@index` — filters by `auth()->user()->teacher_id`

**Migration needed:** Yes  
**Test these pages:** Login, all admin pages, all teacher portal pages

---

### If you ADD/REMOVE a column from `teachers` table

**Directly affected:**
- `Modules/Teacher/app/Models/Teacher.php` → `$fillable`
- `Modules/Teacher/app/Http/Controllers/TeacherController.php` → `@store`, `@update` validation + create/update calls
- `Modules/Teacher/resources/views/admin/index.blade.php` → Add/Edit modal fields
- `database/seeders/DatabaseSeeder.php` → teacher creation data

**Indirectly affected:**
- `SubstitutionController@availableTeachers` — if adding to sort criteria (currently uses `subject_specialization`)
- `ReportController` — if adding to report output
- `DashboardController` — if teacher info shown on dashboard changes

**Migration needed:** Yes  
**Test these pages:** Admin → Teachers list, Add Teacher form, Substitution page (teacher names)

---

### If you ADD/REMOVE a column from `attendances` table

**Directly affected:**
- `Modules/Attendance/app/Models/Attendance.php` → `$fillable`
- `Modules/Attendance/app/Http/Controllers/AttendanceController.php` → `@markBulk`
- `Modules/Substitution/app/Http/Controllers/SubstitutionController.php` → `@markAbsent`

**Indirectly affected:**
- `DashboardController@index` — `whereIn('status', ...)` queries
- `SubstitutionController@index` and `@availableTeachers` — read status column
- `ReportController@index` — raw SQL SUM(CASE WHEN status = ...) in Section 1
- `Modules/Teacher/resources/views/portal/attendance.blade.php` — teacher view

**Migration needed:** Yes  
**Test these pages:** Attendance marking, Dashboard stats, Substitution page, Monthly report

---

### If you ADD/REMOVE a column from `timetables` table

**Directly affected:**
- `Modules/Timetable/app/Models/Timetable.php` → `$fillable`
- `Modules/Timetable/app/Http/Controllers/TimetableController.php` → `@store`
- `Modules/Timetable/resources/views/admin/index.blade.php` → Add Period modal

**Indirectly affected:**
- `DashboardController@index` — raw DB query joins timetables
- `SubstitutionController@index` — raw DB query joins timetables
- `SubstitutionController@availableTeachers` — reads period_number from timetable
- `TeacherTimetableController@index` — reads timetable for teacher portal
- `ReportController@index` — Section 4 subquery on timetables

**Migration needed:** Yes  
**Test these pages:** Timetable management, Dashboard today's timetable, Substitution, Teacher portal timetable, Monthly report

---

### If you change UNIQUE constraints on `timetables`

**Directly affected:**
- New migration to drop old constraint and add new
- `TimetableController@store` — error handling catches specific constraint names (`unique_class_period`, `unique_teacher_period`)

**Test:** Try adding duplicate period — must show correct error message

---

### If you ADD/REMOVE a column from `substitutions` table

**Directly affected:**
- `Modules/Substitution/app/Models/Substitution.php` → `$fillable`
- `SubstitutionController@store` — create() call

**Indirectly affected:**
- `DashboardController@index` — selects substitution data for display
- `ReportController@index` — Section 2 raw SQL on substitutions
- `Modules/Substitution/resources/views/admin/index.blade.php` — shows substitution details

**Migration needed:** Yes

---

### If you ADD/REMOVE a column from `leaves` table

**Directly affected:**
- `Modules/Leave/app/Models/Leave.php` → `$fillable`
- `LeaveController@approve`, `@reject`
- `TeacherLeaveController@store`

**Indirectly affected:**
- `DashboardController@index` — counts pending leaves
- `ReportController@index` — Section 3 leave queries
- `Leave::getDaysAttribute()` — if from_date/to_date columns renamed
- Both leave views (admin + teacher portal)

**Migration needed:** Yes

---

## Route Changes

### If you rename a route name (e.g., `admin.leaves.admin` → `admin.leaves.index`)

**Must update every `route()` helper call that uses the old name:**
- `resources/views/layouts/app.blade.php` — sidebar links
- All blade views with back-links and form actions
- Any controllers that `redirect()->route('old.name')`

**Find all uses:** Run `grep -r "admin.leaves.admin" /home/user/Academy/` before renaming

---

### If you add a new admin route

1. Add to `Modules/[Module]/routes/web.php` inside the admin middleware group
2. Create/update controller method
3. Create/update blade view
4. **Add sidebar link** in `resources/views/layouts/app.blade.php`
5. Update `CLAUDE.md` Route Map table
6. Update `docs/modules/[MODULE].md`

---

### If you add a new teacher portal route

1. Add to `Modules/Teacher/routes/web.php` inside the teacher middleware group
2. Create controller method in appropriate Teacher*Controller
3. Create blade view in `Modules/Teacher/resources/views/portal/`
4. **Add sidebar link** in `resources/views/layouts/app.blade.php` (teacher sidebar section)
5. Update `CLAUDE.md` and `docs/modules/TEACHER.md`

---

## Model Changes

### If you change/rename `Teacher::isAbsentOn()` method

**Used in:**
- `Modules/Dashboard/resources/views/admin.blade.php` — `$tt->teacher->isAbsentOn($today)` — shows "Absent" badge on today's timetable

---

### If you change/rename `Teacher::scopeActive()`

**Used in:**
- `SubstitutionController@availableTeachers` — `Teacher::where('status','active')`
- `AttendanceController@adminIndex` — `Teacher::where('status','active')`
- `DashboardController@index` — `Teacher::where('status','active')`

Note: Currently implemented as manual `where('status','active')` in most places, not always using the scope method.

---

### If you change `Leave::getDaysAttribute()`

**Used in:**
- `ReportController@index` — Section 3: `$leaves->sum('days')` — total leave days per teacher
- Leave admin view — "Days" column display

---

### If you change `Timetable` model relationships

**Used across:**
- `DashboardController@index` — `Timetable::with(['schoolClass','section','subject','teacher','room'])`
- `SubstitutionController` — accesses timetable relationships
- `TeacherTimetableController@index`
- `ReportController@index` — Section 4

---

### If you change/rename `SchoolClass` model (currently uses `$table = 'classes'`)

**Used in:**
- `DashboardController` — raw SQL joins `classes as c`
- `SubstitutionController` — raw SQL joins `classes as c`
- `ReportController` — raw SQL joins `classes as c`
- All module controllers that reference SchoolClass

---

## Controller Changes

### If you change `SubstitutionController@availableTeachers` response format

This is consumed by JavaScript. If you change the `teachers` array structure or key names:
- Update JavaScript in `Modules/Substitution/resources/views/admin/index.blade.php` — the code that processes the JSON response and builds the teacher selection UI
- Update `docs/API-REFERENCE.md`
- Update `docs/modules/SUBSTITUTION.md`

---

### If you change `DashboardController@index` unassigned classes query

Test that:
- The substitution page's unassigned classes list matches the dashboard alert
- Marking a substitute reduces both counts

---

## View Changes

### If you change `resources/views/layouts/app.blade.php`

**This is THE shared layout. Every single page is affected.**

Be careful with:
- CDN script/style links — removing any CDN breaks ALL pages using that library
- `@stack('scripts')` — removing this breaks any module that uses `@push('scripts')`
- `@stack('styles')` — same for styles
- `session('success')` and `session('error')` flash message handling — removing breaks ALL success/error feedback
- Sidebar links — check route names are correct
- CSRF token meta tag — removing breaks all AJAX calls
- Notification polling JavaScript — if removed, notification bell stops working

**Test after any layout change:** All major pages — dashboard, teachers, timetable, substitution, attendance, leave, reports, teacher portal

---

### If you rename a Bootstrap modal ID in any admin view

JavaScript functions reference modal IDs by string. Example:
```javascript
// In substitution view
openAssignModal(timetableId, className, ...) {
    document.getElementById('assignModal')  // ← this string must match
}
```
If you rename the modal's `id` attribute, update the matching JS reference in the same file.

---

## Authentication Changes

### If you want to add email-based login (alongside username)

1. `Modules/Auth/app/Http/Controllers/AuthController.php` — modify `@login` to try email if username fails
2. `Modules/Auth/resources/views/login.blade.php` — add email field OR change label
3. No migration needed (email column already nullable in users)

---

### If you add a new role (e.g., 'principal')

1. New migration: `ALTER TABLE users MODIFY role ENUM('admin','teacher','coordinator','principal')`
2. `Modules/Auth/app/Http/Middleware/RoleMiddleware.php` — no change needed (generic role check)
3. `Modules/Auth/app/Http/Controllers/AuthController.php` — add `case 'principal'` in `redirectByRole()`
4. Decide which routes principal can access — add `role:admin,coordinator,principal` to relevant route middleware groups
5. Add principal to `database/seeders/DatabaseSeeder.php` if needed
6. Update `CLAUDE.md` → "Database Tables Quick Reference" and "Key Architectural Patterns" sections

---

## Adding a New Module

**Full checklist:**

1. `php artisan module:make ModuleName`
2. Add to `composer.json` autoload.psr-4:
   ```json
   "Modules\\ModuleName\\": "Modules/ModuleName/app/"
   ```
3. Add seeder namespace if needed:
   ```json
   "Modules\\ModuleName\\Database\\Seeders\\": "Modules/ModuleName/database/seeders/"
   ```
4. Run `composer dump-autoload`
5. Create migration in `Modules/ModuleName/database/migrations/`
6. Run `php artisan migrate`
7. Create Model in `Modules/ModuleName/app/Models/`
8. Create Controller in `Modules/ModuleName/app/Http/Controllers/`
9. Write routes in `Modules/ModuleName/routes/web.php` with appropriate middleware
10. Create blade views in `Modules/ModuleName/resources/views/`
11. **Add sidebar link** in `resources/views/layouts/app.blade.php`
12. Update `CLAUDE.md` → Module Registry table and Route Map
13. Create `docs/modules/NEWMODULE.md`
14. Verify with `php artisan module:list` and `php artisan route:list`

---

## Cross-Module Impact Matrix

Which modules are affected when you change another module:

| If you change → | Auth | Dashboard | Teacher | Classes | Subject | Timetable | Substitution | Attendance | Leave | Report |
|----------------|------|-----------|---------|---------|---------|-----------|--------------|------------|-------|--------|
| **users table** | ✓ Core | - | ✓ teacher_id | - | - | - | ✓ assigned_by | ✓ marked_by | ✓ approved_by | - |
| **teachers table** | - | ✓ stats | ✓ Core | - | - | ✓ FK | ✓ FK | ✓ FK | ✓ FK | ✓ names |
| **classes table** | - | ✓ query | - | ✓ Core | ✓ FK | ✓ FK | ✓ query | - | - | ✓ query |
| **sections table** | - | ✓ query | - | ✓ Core | - | ✓ FK | ✓ query | - | - | - |
| **subjects table** | - | ✓ query | - | - | ✓ Core | ✓ FK | ✓ same_subj | - | - | - |
| **timetables table** | - | ✓ Core | ✓ teacher | - | - | ✓ Core | ✓ Core | - | - | ✓ coverage |
| **attendances table** | - | ✓ absent count | - | - | - | - | ✓ Core reads | ✓ Core | - | ✓ Section 1 |
| **substitutions table** | - | ✓ Core | - | - | - | - | ✓ Core | - | - | ✓ Section 2 |
| **leaves table** | - | ✓ pending | - | - | - | - | - | - | ✓ Core | ✓ Section 3 |
| **layouts/app.blade.php** | ✓ login | ✓ ALL | ✓ ALL | ✓ ALL | ✓ ALL | ✓ ALL | ✓ ALL | ✓ ALL | ✓ ALL | ✓ ALL |

**Legend:** ✓ Core = primary owner, ✓ = affected, - = not affected

---

## Process: How to Propose a New Feature

When a developer or user proposes a new requirement, follow this process:

**Step 1:** Read `CLAUDE.md` to understand current architecture  
**Step 2:** Read relevant `docs/modules/[MODULE].md` files  
**Step 3:** Read this file (CHANGE-IMPACT-GUIDE.md) for impact analysis  
**Step 4:** Identify: Which DB tables need changes? New tables? Which controllers? Which views?  
**Step 5:** List all affected files before implementing  
**Step 6:** Create migration FIRST, then model, then controller, then view, then routes  
**Step 7:** Update `CLAUDE.md` and relevant module docs after implementation  
**Step 8:** Run `php artisan route:list` to verify new routes registered  
**Step 9:** Test end-to-end: form submission → DB write → view update
