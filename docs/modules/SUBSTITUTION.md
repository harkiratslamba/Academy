# Substitution Module — MVC Flow Documentation

**Location:** `Modules/Substitution/`  
**Purpose:** CORE FEATURE of the system. When teachers are absent, this module detects uncovered classes and assigns available substitute teachers with intelligent matching.

---

## Module Files

| Type | File |
|------|------|
| Controller | Modules/Substitution/app/Http/Controllers/SubstitutionController.php |
| Routes | Modules/Substitution/routes/web.php |
| View | Modules/Substitution/resources/views/admin/index.blade.php |
| Model | Modules/Substitution/app/Models/Substitution.php |
| DB Table | substitutions |
| Also writes | attendances (via markAbsent) |

---

## Flow 1: View Substitution Management Page

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/admin/substitution?date=YYYY-MM-DD` |
| Route Name | admin.substitution.index |
| Controller@Method | SubstitutionController@index |
| Middleware | auth, role:admin,coordinator |

**Query Parameter:** `date` (defaults to today if not provided)

**Data computed:**

1. **$absentTeachers** — Teachers with attendance status 'absent' or 'on_leave' on the selected date
2. **$unassignedClasses** — Complex query:
   - Joins: timetables + classes + sections + subjects + teachers + attendances
   - Filter: day matches date's day-of-week, is_active=true, teacher is absent
   - Excludes: periods where a non-cancelled substitution already exists for that date
3. **$todaySubs** — All substitution records for the selected date with all relationships
4. **$teachers** — All active teachers (for Mark Absent dropdown)

**UI:**
- Date picker (shows any past or future date)
- Red alert box: unassigned classes needing substitute
- "Assign" button per unassigned class → opens AJAX modal
- "Mark Teacher Absent" button → opens modal
- Table of today's assigned substitutions

---

## Flow 2: Mark Teacher Absent

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/substitution/mark-absent` |
| Route Name | admin.substitution.markAbsent |
| Controller@Method | SubstitutionController@markAbsent |

**Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| teacher_id | Yes | required, exists:teachers | attendances | teacher_id |
| date | Yes | required, date | attendances | date |
| status | Yes | in: absent, on_leave | attendances | status |
| notes | No | nullable, max 500 | attendances | notes |

**Business Logic:**
```
Attendance::updateOrCreate(
  ['teacher_id' => $id, 'date' => $date],
  ['status' => $status, 'notes' => $notes, 'marked_by' => auth()->id()]
)
```
UPSERT: if attendance record exists for that teacher+date → update it; if not → create new.

**After this action:** Refreshing the substitution page will show the teacher's classes in the unassigned alert.

**DB Table Written:** attendances

---

## Flow 3: Get Available Substitute Teachers (AJAX JSON)

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/admin/substitution/available-teachers?timetable_id=X&date=YYYY-MM-DD` |
| Route Name | admin.substitution.availableTeachers |
| Controller@Method | SubstitutionController@availableTeachers |
| Returns | JSON |

**Called by:** JavaScript in the Assign modal when admin clicks "Assign" button

**Parameters:**
| Parameter | Required | Description |
|-----------|----------|-------------|
| timetable_id | Yes | Which timetable entry needs coverage |
| date | Yes | The date of absence |

**Algorithm (5 steps):**

**Step 1 — Get absent teacher IDs:**
```
SELECT teacher_id FROM attendances 
WHERE date = $date AND status IN ('absent', 'on_leave')
```

**Step 2 — Get busy teacher IDs (own class at this period):**
```
SELECT teacher_id FROM timetables 
WHERE day = $dayName AND period_number = $timetable->period_number AND is_active = true
```

**Step 3 — Get already-assigned substitute IDs for this period:**
```
SELECT substitute_teacher_id FROM substitutions s
JOIN timetables t ON s.timetable_id = t.id
WHERE s.date = $date AND t.period_number = $period AND t.day = $day
AND s.status != 'cancelled'
```

**Step 4 — Compute workload (monthly substitutions):**
```
SELECT substitute_teacher_id, COUNT(*) as sub_count FROM substitutions
WHERE date LIKE '$year-$month%' AND status != 'cancelled'
GROUP BY substitute_teacher_id
```

**Step 5 — Return available teachers:**
```
SELECT * FROM teachers 
WHERE status = 'active' AND id NOT IN ($excludeIds)
```
Add computed fields:
- `same_subject` = teacher's specialization contains the subject name (case-insensitive)
- `workload` = this month's substitution count (0 if none)

Sort: `same_subject DESC` then `workload ASC` (best match, least loaded, first)

**Response:**
```
{
  "teachers": [
    { "id": 3, "name": "Priya Sharma", "employee_id": "EMP002", 
      "specialization": "English", "same_subject": true, "workload": 2 },
    ...
  ]
}
```

**DB Tables Read:** attendances, timetables, substitutions, teachers, subjects (via timetable relationship)

---

## Flow 4: Assign Substitute Teacher

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/substitution` |
| Route Name | admin.substitution.store |
| Controller@Method | SubstitutionController@store |

**Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| timetable_id | Yes | required, exists:timetables | substitutions | timetable_id |
| substitute_teacher_id | Yes | required, exists:teachers | substitutions | substitute_teacher_id |
| date | Yes | required, date | substitutions | date |
| notes | No | nullable, max 500 | substitutions | notes |

**Business Logic — 3-layer validation:**

**Layer 1:** Is substitute absent on this date?
```
Attendance WHERE teacher_id=$subId AND date=$date AND status IN (absent,on_leave)
→ If exists: ERROR "Selected teacher is absent on this date"
```

**Layer 2:** Does substitute have own class at this period?
```
Timetable WHERE teacher_id=$subId AND day=$dayName AND period_number=$period AND is_active=true
→ If exists: ERROR "Selected teacher already has a class at this period"
```

**Layer 3:** Is substitute already assigned as sub for this period on this date?
```
Substitution WHERE substitute_teacher_id=$subId AND date=$date
  AND timetable has same period_number AND day AND status != cancelled
→ If exists: ERROR "Teacher already assigned as substitute for this period"
```

**If all 3 pass:**
```
Substitution::create([
  original_teacher_id = timetable->teacher_id,
  substitute_teacher_id = $subId,
  timetable_id = $timetableId,
  date = $date,
  status = 'assigned',
  assigned_by = auth()->id(),
  notes = $notes
])
```

**DB Table Written:** substitutions

---

## Flow 5: Cancel Substitution

| Property | Value |
|----------|-------|
| Method | DELETE |
| URL | `/admin/substitution/{id}` |
| Route Name | admin.substitution.destroy |

**Business Logic:** `$sub->update(['status' => 'cancelled'])`  
**NOT a hard delete** — record preserved for audit trail.

After cancellation, the class appears again in the unassigned classes alert.

---

## Complete Substitution Workflow

```
STEP 1: Teacher is absent
Admin clicks "Mark Teacher Absent"
  → POST /admin/substitution/mark-absent
  → Writes: attendances table (status = 'absent')

STEP 2: System detects uncovered classes
Substitution page loads / refreshes
  → Dashboard alert shows: "3 classes need a substitute today"
  → Unassigned classes table appears (red alert)
  → Data: timetables WHERE teacher is absent AND no active substitution

STEP 3: Admin finds available substitute
Admin clicks "Assign" button for a class
  → JavaScript calls GET /api/available-teachers?timetable_id=X&date=Y
  → Returns: ranked list of available teachers
  → Green badge on same-subject teachers
  → Workload count shown for fairness

STEP 4: Admin assigns substitute
Admin selects teacher from list, submits
  → POST /admin/substitution
  → 3-layer validation
  → Writes: substitutions table

STEP 5: Class is covered
Unassigned class count decreases
Substitution appears in today's assignments table
```

---

## Substitution Statuses

| Status | Meaning |
|--------|---------|
| pending | Assigned but not yet confirmed |
| assigned | Actively assigned for the period |
| completed | Period has occurred |
| cancelled | Cancelled by admin |

---

## Impact Summary

| Aspect | File/Location |
|--------|--------------|
| Controller | Modules/Substitution/app/Http/Controllers/SubstitutionController.php |
| Routes | Modules/Substitution/routes/web.php |
| View | Modules/Substitution/resources/views/admin/index.blade.php |
| Model | Modules/Substitution/app/Models/Substitution.php |
| DB Tables Written | substitutions, attendances (via markAbsent) |
| DB Tables Read | attendances, timetables, teachers, subjects, classes, sections, substitutions |
| JSON API | /api/available-teachers AND /admin/substitution/available-teachers |

### If you change this, also update:
| Change | Also update |
|--------|-------------|
| Change same_subject matching logic | SubstitutionController@availableTeachers — update str_contains logic |
| Change workload calculation period | SubstitutionController@availableTeachers — update the LIKE '%year-month%' query |
| Add new substitution status | Substitution model enum in migration (new migration), SubstitutionController (destroy → update status), DashboardController (whereNotIn status check), views (badge colors) |
| Change response format of availableTeachers | Update JavaScript in Substitution admin view that parses the JSON |
| Add notification when sub is assigned | SubstitutionController@store — add notification creation |
