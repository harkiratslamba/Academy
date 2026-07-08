# Dashboard Module — MVC Flow Documentation

**Location:** `Modules/Dashboard/`  
**Purpose:** Central admin overview. Shows real-time stats, today's timetable, unassigned class alerts, and recent substitutions.

---

## Module Files

| Type | File |
|------|------|
| Controller | Modules/Dashboard/app/Http/Controllers/DashboardController.php |
| Routes | Modules/Dashboard/routes/web.php |
| View | Modules/Dashboard/resources/views/admin.blade.php |
| Models Used | Teacher, Attendance, Substitution, Leave, Timetable, SchoolClass |

---

## Flow 1: Admin Dashboard Page

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/admin/dashboard` |
| Route Name | admin.dashboard |
| Controller@Method | DashboardController@index |
| Middleware | auth, role:admin,coordinator |

**No form — this is a read-only display page.**

**Data computed and passed to view:**

### Stat Card 1: Total Active Teachers
```
Teacher::where('status', 'active')->count()
DB: SELECT COUNT(*) FROM teachers WHERE status = 'active'
```

### Stat Card 2: Total Classes
```
SchoolClass::count()
DB: SELECT COUNT(*) FROM classes
```

### Stat Card 3: Absent Today
```
Attendance::where('date', today)->whereIn('status', ['absent', 'on_leave'])->count()
DB: SELECT COUNT(*) FROM attendances WHERE date = TODAY AND status IN ('absent', 'on_leave')
```

### Stat Card 4: Substitutions Today
```
Substitution::where('date', today)->whereNotIn('status', ['cancelled'])->count()
DB: SELECT COUNT(*) FROM substitutions WHERE date = TODAY AND status != 'cancelled'
```

### Stat Card 5: Free Teachers Today
```
Teacher::where('status', 'active')
  ->whereDoesntHave('attendances', fn($q) =>
    $q->where('date', today)->whereIn('status', ['absent','on_leave'])
  )->count()
Logic: Active teachers who do NOT have an absent/on_leave record today
```

### Stat Card 6: Pending Leave Requests
```
Leave::where('status', 'pending')->count()
DB: SELECT COUNT(*) FROM leaves WHERE status = 'pending'
```

### Unassigned Classes (KEY ALERT — Core Feature)
Complex raw query joining 6 tables:
- timetables (entries for today's day-of-week)
- teachers (get teacher name)
- attendances (teacher is absent/on_leave today)
- classes + sections + subjects (get class details)
- NOT EXISTS: substitutions for this period/date (non-cancelled)

**Logic:**
```
Find all timetable entries where:
  day = 'Monday' (today's day name)
  AND is_active = true
  AND teacher is in attendances with status absent/on_leave for today
  AND no active substitution exists for this period today
```

**Output:** List of periods that need substitute coverage

### Today's Timetable
```
Timetable::with(['schoolClass', 'section', 'subject', 'teacher', 'room'])
  ->where('day', $todayDay)
  ->where('is_active', true)
  ->orderBy('period_number')
  ->get()
```
Each row shows: Period#, Time, Class-Section, Subject, Teacher (with "Absent" badge if teacher is absent)

### Recent Substitutions (last 8)
```
Substitution::with(['originalTeacher', 'substituteTeacher', 'timetable.schoolClass', 'timetable.subject'])
  ->orderByDesc('created_at')
  ->limit(8)
  ->get()
```

**DB Tables Read:** teachers, classes, attendances, substitutions, leaves, timetables, sections, subjects, rooms  
**DB Tables Written:** NONE — dashboard is read-only

---

## Dashboard Data Flow

```
DashboardController@index
        │
        ├─── Teacher::count(active) ──────────────────► Stat: Total Teachers
        │
        ├─── SchoolClass::count() ───────────────────► Stat: Total Classes
        │
        ├─── Attendance WHERE absent today ──────────► Stat: Absent Today
        │
        ├─── Substitution WHERE today ───────────────► Stat: Today's Subs
        │
        ├─── Teacher NOT absent today ───────────────► Stat: Free Teachers
        │
        ├─── Leave WHERE pending ────────────────────► Stat: Pending Leaves
        │
        ├─── JOIN: timetables + attendances + ───────► Alert: Unassigned Classes
        │    NOT EXISTS substitutions
        │
        ├─── Timetable WHERE day=today ──────────────► Table: Today's Schedule
        │
        └─── Substitution last 8 ────────────────────► List: Recent Subs
```

---

## Impact Summary

| Aspect | File/Location |
|--------|--------------|
| Controller | Modules/Dashboard/app/Http/Controllers/DashboardController.php |
| View | Modules/Dashboard/resources/views/admin.blade.php |
| Routes | Modules/Dashboard/routes/web.php |
| Models Used | Teacher, SchoolClass, Attendance, Substitution, Leave, Timetable |
| DB Tables Read | teachers, classes, attendances, substitutions, leaves, timetables, sections, subjects |

### If you change this, also update:
| Change | Also update |
|--------|-------------|
| Change attendance status values | DashboardController (whereIn status checks), SubstitutionController, AttendanceController |
| Change timetable schema | DashboardController unassigned classes query (raw DB join) |
| Add new stat card | DashboardController@index + admin.blade.php view |
| Change substitution status values | DashboardController whereNotIn status check |
