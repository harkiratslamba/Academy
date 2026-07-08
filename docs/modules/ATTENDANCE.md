# Attendance Module — MVC Flow Documentation

**Location:** `Modules/Attendance/`  
**Purpose:** Daily teacher attendance marking (bulk, admin side). Teachers view their own attendance history.

---

## Module Files

| Type | File |
|------|------|
| Controller | Modules/Attendance/app/Http/Controllers/AttendanceController.php |
| Routes | Modules/Attendance/routes/web.php |
| View (Admin) | Modules/Attendance/resources/views/admin/index.blade.php |
| View (Teacher) | Modules/Teacher/resources/views/portal/attendance.blade.php |
| Model | Modules/Attendance/app/Models/Attendance.php |
| DB Table | attendances |

---

## Flow 1: Admin Attendance Page

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/admin/attendance?date=YYYY-MM-DD` |
| Route Name | admin.attendance.index |
| Controller@Method | AttendanceController@adminIndex |
| Middleware | auth, role:admin,coordinator |

**Query Parameter:** `date` (default: today)

**Data:**
```
$teachers = Teacher::with(['attendances' => function($q) use ($date) {
    $q->where('date', $date);
}])->where('status', 'active')->orderBy('name')->get()
```
Eager loads each teacher's attendance record for the selected date (if any).

**Summary badges computed:**
```
$summary = [
  'present'    => count of teachers with status=present,
  'absent'     => count with status=absent,
  'late'       => count with status=late,
  'on_leave'   => count with status=on_leave,
  'not_marked' => count with no attendance record for this date
]
```

**UI features:**
- Date picker + Load button
- Summary badges (Present/Absent/Late/On Leave/Not Marked) at top
- Bulk marking table: one radio button row per teacher (Present | Absent | Late | On Leave)
- "Mark All Present" and "Mark All Absent" quick buttons (JavaScript)
- Color-coded rows: green=present, red=absent, yellow=late, blue=on_leave
- Save Attendance submit button at top and bottom

**DB Tables Read:** teachers, attendances

---

## Flow 2: Bulk Mark Attendance

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/attendance/bulk` |
| Route Name | admin.attendance.bulk |
| Controller@Method | AttendanceController@markBulk |

**Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| date | Yes | required, date | attendances | date |
| attendance (array) | Yes | required, array | - | - |
| attendance[{teacher_id}] | Yes (each) | in: present,absent,late,on_leave | attendances | status |

**Example POST body:**
```
date=2026-07-08
attendance[1]=present
attendance[2]=absent
attendance[3]=late
attendance[4]=on_leave
attendance[5]=present
```

**Business Logic:**
```php
foreach ($data['attendance'] as $teacherId => $status) {
    Attendance::updateOrCreate(
        ['teacher_id' => (int)$teacherId, 'date' => $date],
        ['status' => $status, 'marked_by' => auth()->id()]
    );
}
```
UPSERT: if attendance record exists for teacher+date → update status; if not → insert new record.

**DB Table Written:** attendances (multiple rows, one per teacher)  
**Columns Written:** teacher_id, date, status, marked_by  
**Models Used:** Attendance, Teacher

**After marking:** If any teachers are marked absent/on_leave, the Substitution page will show those teachers' classes in the unassigned alert.

---

## Flow 3: Teacher Personal Attendance View

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/teacher/attendance` |
| Route Name | teacher.attendance |
| Controller@Method | TeacherAttendanceController@index |
| View | Modules/Teacher/resources/views/portal/attendance.blade.php |
| Middleware | auth, role:teacher |

**Data:**
```
Attendance::where('teacher_id', auth()->user()->teacher_id)
  ->orderByDesc('date')
  ->get()
```

**Read-only** — teacher cannot modify their attendance. Shows history with date, status, check-in time if recorded.

---

## Attendance Status Values

| Status | Meaning | Triggers Substitution? |
|--------|---------|----------------------|
| present | Teacher attended normally | No |
| absent | Teacher did not attend | Yes |
| late | Teacher arrived late | No |
| on_leave | Teacher on approved leave | Yes |

---

## UPSERT Pattern Detail

The `updateOrCreate` pattern means:
1. If no attendance record for (teacher_id + date) → INSERT new row
2. If record exists → UPDATE the status and marked_by

**Result:** Exactly ONE row per teacher per day (enforced by UNIQUE constraint on `attendances(teacher_id, date)`).

**Re-marking is safe:** If admin marks all present in the morning, then later changes one to absent, the single record updates — no duplicates.

---

## Relationship with Other Modules

```
Attendance Module
       │
       ├── FEEDS → Substitution Module
       │   SubstitutionController reads attendances WHERE status IN (absent, on_leave)
       │   to find which teachers are absent and need substitutes
       │
       ├── FEEDS → Dashboard Module
       │   DashboardController counts absent teachers for "Absent Today" stat card
       │
       ├── FEEDS → Report Module
       │   ReportController reads all attendances for a month to compute attendance %
       │
       └── ALSO WRITTEN BY → Substitution Module
           SubstitutionController@markAbsent also writes to attendances table
           (both modules can write attendance records)
```

---

## Model: Attendance

```php
Modules\Attendance\Models\Attendance
  Table: attendances
  Fillable: teacher_id, date, status, check_in, notes, marked_by
  Relationships:
    teacher() → belongsTo Teacher
    markedBy() → belongsTo User (foreign key: marked_by)
  Scope:
    scopeAbsent() → whereIn('status', ['absent', 'on_leave'])
```

---

## Attendance Marking Workflow

```
Admin visits /admin/attendance?date=2026-07-08
        │
Loads all active teachers with today's attendance records
        │
Shows table with radio buttons:
  Rajesh Kumar:    (○) Present  (●) Absent   (○) Late  (○) On Leave  ← already marked absent
  Priya Sharma:    (○) Present  (○) Absent   (○) Late  (○) On Leave  ← not marked yet
  Amit Singh:      (●) Present  (○) Absent   (○) Late  (○) On Leave  ← marked present
        │
Admin changes Priya to "Present", submits form
        │
POST /admin/attendance/bulk
  attendance[2] = present  (Priya's teacher_id = 2)
        │
Attendance::updateOrCreate(['teacher_id'=>2, 'date'=>'2026-07-08'], ['status'=>'present'])
        │
  → INSERT if new (Priya had no record) → creates record
  → UPDATE if exists → changes status
        │
Redirect back with "Attendance marked for 08 Jul 2026"
```

---

## Impact Summary

| Aspect | File/Location |
|--------|--------------|
| Controller | Modules/Attendance/app/Http/Controllers/AttendanceController.php |
| Teacher Controller | Modules/Teacher/app/Http/Controllers/TeacherAttendanceController.php |
| Routes | Modules/Attendance/routes/web.php |
| Admin View | Modules/Attendance/resources/views/admin/index.blade.php |
| Teacher View | Modules/Teacher/resources/views/portal/attendance.blade.php |
| Model | Modules/Attendance/app/Models/Attendance.php |
| DB Table | attendances (UNIQUE: teacher_id + date) |

### If you change this, also update:
| Change | Also update |
|--------|-------------|
| Add new attendance status (e.g., 'half_day') | Migration (ALTER ENUM), Attendance model, AttendanceController validation, admin view radio options, SubstitutionController (check if 'half_day' should trigger substitution), ReportController (SUM CASE queries) |
| Add check_in time recording | AttendanceController@markBulk (add time input per teacher), admin view (add time field), Attendance model $fillable |
| Change UNIQUE constraint | Migration (drop + recreate), handle edge cases in updateOrCreate |
| Add leave integration (auto-mark on_leave for approved leaves) | New cron job or leave approval hook in LeaveController@approve |
