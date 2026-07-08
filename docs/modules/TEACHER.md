# Teacher Module — MVC Flow Documentation

**Location:** `Modules/Teacher/`  
**Purpose:** Two sides — Admin manages teacher records and accounts. Teachers access their personal portal.

---

## Module Files

| Type | File |
|------|------|
| Admin Controller | Modules/Teacher/app/Http/Controllers/TeacherController.php |
| Teacher Dashboard | Modules/Teacher/app/Http/Controllers/TeacherDashboardController.php |
| Teacher Timetable | Modules/Teacher/app/Http/Controllers/TeacherTimetableController.php |
| Teacher Leaves | Modules/Teacher/app/Http/Controllers/TeacherLeaveController.php |
| Teacher Attendance | Modules/Teacher/app/Http/Controllers/TeacherAttendanceController.php |
| Teacher Availability | Modules/Teacher/app/Http/Controllers/TeacherAvailabilityController.php |
| Routes | Modules/Teacher/routes/web.php |
| Admin View | Modules/Teacher/resources/views/admin/index.blade.php |
| Portal Views | Modules/Teacher/resources/views/portal/ |
| Models | Modules/Teacher/app/Models/Teacher.php + App/Models/User |

---

## ADMIN SIDE

### Flow A1: Teacher List

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/admin/teachers` |
| Route Name | admin.teachers.index |
| Controller@Method | TeacherController@index |
| View | Modules/Teacher/resources/views/admin/index.blade.php |
| Middleware | auth, role:admin,coordinator |

**Data:** `Teacher::with('user')->orderBy('name')->get()`  
**UI:** DataTable with Add/Edit/Reset Password/Delete modal dialogs

---

### Flow A2: Add Teacher

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/teachers` |
| Route Name | admin.teachers.store |
| Controller@Method | TeacherController@store |

**Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| name | Yes | string, max 255 | teachers + users | name |
| employee_id | Yes | unique:teachers | teachers | employee_id |
| username | Yes | unique:users | users | username |
| password | Yes | min 6 | users | password (bcrypt) |
| email | No | email, unique:teachers | teachers.email + users.email | email |
| phone | No | string, max 20 | teachers | phone |
| subject_specialization | No | string | teachers | subject_specialization |
| qualification | No | string | teachers | qualification |
| joining_date | No | date | teachers | joining_date |

**Business Logic:**
1. `User::create([username, name, email, password=bcrypt, role='teacher'])`
2. `Teacher::create([user_id, name, email, phone, employee_id, specialization, qualification, joining_date, status='active'])`
3. `$user->update(['teacher_id' => $teacher->id])` — complete bidirectional link
4. Redirect back with success message

**DB Tables Written:** users (new row), teachers (new row)  
**Key:** Both records created atomically — if teacher create fails, user account exists but has no teacher_id

---

### Flow A3: Edit Teacher

| Property | Value |
|----------|-------|
| Method | PUT |
| URL | `/admin/teachers/{teacher}` |
| Route Name | admin.teachers.update |
| Controller@Method | TeacherController@update |

**Form Fields:** name, employee_id, email, phone, subject_specialization, qualification, joining_date, status (active/inactive)  
**Business Logic:**
1. Validate (employee_id unique excluding self, email unique excluding self)
2. `$teacher->update($data)`
3. Sync on linked user: `$teacher->user->update(['name' => $data['name'], 'email' => $data['email']])`

**DB Tables Written:** teachers, users (name + email synced)

---

### Flow A4: Delete Teacher

| Property | Value |
|----------|-------|
| Method | DELETE |
| URL | `/admin/teachers/{teacher}` |
| Route Name | admin.teachers.destroy |

**Business Logic:**
1. Check if `Timetable::where('teacher_id', $id)->exists()` → block with error if true
2. Delete linked user: `$teacher->user->delete()`
3. `$teacher->delete()` → cascades to attendances, leaves, teacher_availabilities (FK CASCADE DELETE)

**DB Tables Written:** teachers (deleted), users (deleted), + cascade: attendances, leaves, teacher_availabilities

---

### Flow A5: Reset Teacher Password

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/teachers/{teacher}/reset-password` |
| Route Name | admin.teachers.resetPassword |

**Form Fields:**
| Field | Required | Validation |
|-------|----------|-----------|
| new_password | Yes | required, min 6, confirmed |
| new_password_confirmation | Yes | must match new_password |

**Business Logic:** `$teacher->user->update(['password' => Hash::make($data['new_password'])])`  
**DB Table Written:** users (password column only)

---

## TEACHER PORTAL

### Flow T1: Teacher Dashboard

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/teacher/dashboard` |
| Route Name | teacher.dashboard |
| Controller@Method | TeacherDashboardController@index |
| Middleware | auth, role:teacher |

**Data:** Today's timetable for auth teacher, recent substitution assignments, pending leaves count

---

### Flow T2: View Own Timetable

| Property | Value |
|----------|-------|
| URL | `/teacher/timetable` |
| Route Name | teacher.timetable |
| Controller@Method | TeacherTimetableController@index |
| View | Modules/Teacher/resources/views/portal/timetable.blade.php |

**Data:** `Timetable::where('teacher_id', $teacherId)->orderBy('day')->orderBy('period_number')->get()`  
**Read-only:** No form submissions

---

### Flow T3: Mark Own Availability

| Property | Value |
|----------|-------|
| GET URL | `/teacher/availability` |
| POST URL | `/teacher/availability` |
| Route Name | teacher.availability / teacher.availability.store |
| Controller@Method | TeacherAvailabilityController@index / @store |

**POST Form Fields:**
| Field | Required | DB Table | Column |
|-------|----------|----------|--------|
| date | Yes | teacher_availabilities | date |
| period_number | No | teacher_availabilities | period_number |
| status | Yes (available/unavailable/on_leave) | teacher_availabilities | status |
| reason | No | teacher_availabilities | reason |

**Business Logic:** `TeacherAvailability::updateOrCreate(['teacher_id' => id, 'date' => date, 'period_number' => period], ['status' => status, 'reason' => reason])`

---

### Flow T4: Apply for Leave

| Property | Value |
|----------|-------|
| GET URL | `/teacher/leaves` |
| POST URL | `/teacher/leaves` |
| DELETE URL | `/teacher/leaves/{id}` |
| Controller@Method | TeacherLeaveController@index / @store / @cancel |

**POST Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| leave_type | Yes | in: sick,casual,earned,other | leaves | leave_type |
| from_date | Yes | date | leaves | from_date |
| to_date | Yes | date, after_or_equal:from_date | leaves | to_date |
| reason | Yes | required, string | leaves | reason |

**Business Logic:**
1. Get `teacher_id = auth()->user()->teacher_id`
2. `Leave::create([teacher_id, leave_type, from_date, to_date, reason, status='pending'])`
3. DELETE: Can only cancel pending leaves. `Leave::findOrFail($id)->delete()`

---

### Flow T5: View Own Attendance

| Property | Value |
|----------|-------|
| URL | `/teacher/attendance` |
| Route Name | teacher.attendance |
| Controller@Method | TeacherAttendanceController@index |

**Data:** `Attendance::where('teacher_id', auth()->user()->teacher_id)->orderByDesc('date')->get()`  
**Read-only**

---

## Add Teacher Flow Diagram

```
Admin clicks "Add Teacher" → Modal opens
        │
Admin fills: name, employee_id, username, password, email...
        │
POST /admin/teachers
        │
Validation passes?
   NO ──► Back with validation errors shown in form
        │
   YES ──► Step 1: User::create(username, name, email, password_hash, role='teacher')
        │                        ↓
        │             users table: new row inserted
        │
        ├──► Step 2: Teacher::create(user_id, name, email, phone, employee_id...)
        │                          ↓
        │               teachers table: new row inserted
        │
        └──► Step 3: User::update(teacher_id = teacher.id)
                                  ↓
                     Bidirectional link established
                                  ↓
                     Redirect back with "Teacher created" success
```

---

## Impact Summary

| Aspect | File/Location |
|--------|--------------|
| Admin Controller | Modules/Teacher/app/Http/Controllers/TeacherController.php |
| Portal Controllers | TeacherDashboardController, TeacherTimetableController, TeacherLeaveController, TeacherAttendanceController, TeacherAvailabilityController |
| Routes | Modules/Teacher/routes/web.php |
| Admin View | Modules/Teacher/resources/views/admin/index.blade.php |
| Portal Views | Modules/Teacher/resources/views/portal/*.blade.php |
| Models | Teacher (Modules/Teacher), User (App), Timetable, Leave, Attendance, TeacherAvailability |
| DB Tables Written (admin) | teachers, users |
| DB Tables Read (portal) | timetables, attendances, leaves, substitutions, teacher_availabilities |

### If you change this, also update:
| Change | Also update |
|--------|-------------|
| Add teacher field (e.g., department) | Migration, Teacher model $fillable, TeacherController@store+update, admin/index.blade.php modal |
| Change password hashing | TeacherController@store + @resetPassword |
| Change role from 'teacher' to something else | TeacherController@store, AuthController@login redirect, all route middleware |
| Change teacher.status values | TeacherController@update validation, AttendanceController (Teacher::where status active) |
