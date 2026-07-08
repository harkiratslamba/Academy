# Timetable Module — MVC Flow Documentation

**Location:** `Modules/Timetable/`  
**Purpose:** Manages the school schedule — who teaches what subject to which class at which time on which day. Enforces no double-booking via DB UNIQUE constraints.

---

## Module Files

| Type | File |
|------|------|
| Controller | Modules/Timetable/app/Http/Controllers/TimetableController.php |
| Routes | Modules/Timetable/routes/web.php |
| View | Modules/Timetable/resources/views/admin/index.blade.php |
| Model 1 | Modules/Timetable/app/Models/Timetable.php |
| Model 2 | Modules/Timetable/app/Models/TeacherAvailability.php |
| DB Tables | timetables, teacher_availabilities |

---

## Flow 1: View Timetable

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/admin/timetable` |
| Route Name | admin.timetable.index |
| Controller@Method | TimetableController@index |
| Middleware | auth, role:admin,coordinator |

**Data:**
- `$timetables = Timetable::with(['schoolClass','section','subject','teacher','room'])->where('is_active', true)->orderBy('day')->orderBy('period_number')->get()`
- `$classes, $sections, $subjects, $teachers, $rooms` — for Add Period form dropdowns
- Optional filters by class or day from query params

**UI:**
- Filter bar (by class, day)
- DataTable with all active periods
- "Add Period" button opens modal
- Toggle active/inactive button per row

---

## Flow 2: Add Timetable Period

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/timetable` |
| Route Name | admin.timetable.store |
| Controller@Method | TimetableController@store |

**Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| class_id | Yes | required, exists:classes | timetables | class_id |
| section_id | No | nullable, exists:sections | timetables | section_id |
| subject_id | Yes | required, exists:subjects | timetables | subject_id |
| teacher_id | Yes | required, exists:teachers | timetables | teacher_id |
| room_id | No | nullable, exists:rooms | timetables | room_id |
| day | Yes | in: Monday-Saturday | timetables | day |
| period_number | Yes | required, integer, min 1 | timetables | period_number |
| start_time | Yes | required, date_format:H:i | timetables | start_time |
| end_time | Yes | required, after:start_time | timetables | end_time |

**Business Logic:**
1. Validate all fields
2. Attempt: `Timetable::create($data)`
3. If `QueryException` with SQLSTATE 23000 (duplicate key):
   - Message contains 'unique_class_period' → "This class/section already has period #{n} on {day}"
   - Message contains 'unique_teacher_period' → "Teacher {name} is already scheduled for period #{n} on {day}"
4. On success → redirect back with success message

**DB Table Written:** timetables  

**Clash Detection via UNIQUE Constraints:**
```
UNIQUE unique_class_period (class_id, section_id, day, period_number)
  → Same class cannot have two different subjects at the same period

UNIQUE unique_teacher_period (teacher_id, day, period_number)
  → Same teacher cannot teach two different classes at the same period
```
The database enforces this — not application-level logic — to prevent race conditions.

---

## Flow 3: Delete Timetable Entry

| Property | Value |
|----------|-------|
| Method | DELETE |
| URL | `/admin/timetable/{id}` |
| Route Name | admin.timetable.destroy |

**Business Logic:** `Timetable::findOrFail($id)->delete()`

**WARNING:** Deleting a timetable entry also cascades to related substitutions (timetable_id FK in substitutions table).

**DB Tables Written:** timetables (deleted), substitutions (cascade deleted)

---

## Flow 4: Toggle Active/Inactive

| Property | Value |
|----------|-------|
| Method | PATCH |
| URL | `/admin/timetable/{id}/toggle` |
| Route Name | admin.timetable.toggle |

**Business Logic:** `$timetable->update(['is_active' => !$timetable->is_active])`

**Use case:** Temporarily disable a period (e.g., exam week) without deleting it. Inactive entries don't show in dashboard or substitution alerts.

**DB Table Written:** timetables (is_active column only)

---

## AJAX: Class → Section Cascade Dropdown

When admin selects a class in the Add Period form:

```
User selects Class 7 from dropdown
        │
JavaScript: fetch('/api/sections/7')
        │
Returns: [{"id":3,"section_name":"A"}, {"id":4,"section_name":"B"}]
        │
JavaScript populates section dropdown
```

Route defined in `routes/web.php` (not module routes).

---

## Timetable Entry Logic

```
Adding Period 1 on Monday for Class 7A, Mathematics, Teacher Rajesh:

timetables table row:
  class_id = 2 (Class 7)
  section_id = 4 (Section A)
  subject_id = 1 (Mathematics)
  teacher_id = 1 (Rajesh)
  room_id = 1 (Room 101)
  day = 'Monday'
  period_number = 1
  start_time = '08:00'
  end_time = '08:45'
  is_active = 1
```

**Clash scenarios prevented:**
1. Class 7A cannot have Period 1 Monday assigned to BOTH Mathematics AND English
2. Rajesh cannot teach Period 1 Monday to BOTH Class 7A AND Class 8B

---

## Model: Timetable

```
Timetable
  ├── schoolClass()  → belongsTo SchoolClass (class_id)
  ├── section()      → belongsTo Section
  ├── subject()      → belongsTo Subject
  ├── teacher()      → belongsTo Teacher
  ├── room()         → belongsTo Room
  ├── scopeActive()  → where('is_active', true)
  └── scopeForDay($day) → where('day', $day)
```

Used by: DashboardController, SubstitutionController, TeacherTimetableController, ReportController

---

## Add Timetable Period Flow

```
Admin fills Add Period form
        │
POST /admin/timetable
        │
Validation (all required fields)
   FAIL ──► Back with validation errors
        │
   PASS ──► Timetable::create($data)
                    │
          MySQL tries INSERT INTO timetables...
                    │
     UNIQUE violated ──► QueryException caught
                              │
                  unique_class_period ──► "Class already has this period"
                              │
                  unique_teacher_period ──► "Teacher already has this period"
                              │
     INSERT success ──► Redirect with "Period added" success
```

---

## Impact Summary

| Aspect | File/Location |
|--------|--------------|
| Controller | Modules/Timetable/app/Http/Controllers/TimetableController.php |
| Routes | Modules/Timetable/routes/web.php |
| View | Modules/Timetable/resources/views/admin/index.blade.php |
| Model | Modules/Timetable/app/Models/Timetable.php |
| DB Table | timetables (UNIQUE constraints: unique_class_period, unique_teacher_period) |
| API | routes/web.php: GET /api/sections/{classId} |

### If you change this, also update:
| Change | Also update |
|--------|-------------|
| Add timetable field (e.g., room_type) | Migration, Timetable model $fillable, TimetableController store+update, view modal |
| Change UNIQUE constraint | Migration (drop old, add new), update error handling in TimetableController@store |
| Add Saturday/Sunday to days | Change ENUM in migration, update day validation in TimetableController |
| Change period_number max | Update validation, communicate to all stakeholders |
| Delete timetable entry | Check: substitutions cascade delete — inform user they're also removing substitution history |
