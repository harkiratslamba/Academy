# Report Module — MVC Flow Documentation

**Location:** `Modules/Report/`  
**Purpose:** Monthly analytics reports covering attendance, substitutions, leaves, and timetable coverage. Print-ready, read-only view.

---

## Module Files

| Type | File |
|------|------|
| Controller | Modules/Report/app/Http/Controllers/ReportController.php |
| Routes | Modules/Report/routes/web.php |
| View | Modules/Report/resources/views/admin/index.blade.php |
| Models Used | Teacher, Leave (via model), DB facade (raw SQL for others) |
| DB Tables Read | attendances, substitutions, teachers, leaves, timetables, classes |

---

## Flow: Monthly Report Page

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/admin/reports?month=YYYY-MM` |
| Route Name | admin.reports |
| Controller@Method | ReportController@index |
| Middleware | auth, role:admin,coordinator |

**Query Parameter:** `month` (format: YYYY-MM, default: current month)

**Data preparation:**
```php
[$year, $mon] = explode('-', $month);
$startDate = "{$year}-{$mon}-01";
$endDate   = date('Y-m-t', strtotime($startDate));  // last day of month
$totalDays = (int) date('t', strtotime($startDate)); // days in month
```

---

## Report Section 1: Attendance Summary

**Purpose:** Shows attendance percentage for each teacher for the selected month.

**Query:**
```sql
SELECT teacher_id,
  SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END)  AS present_days,
  SUM(CASE WHEN status = 'absent'  THEN 1 ELSE 0 END)  AS absent_days,
  SUM(CASE WHEN status = 'late'    THEN 1 ELSE 0 END)  AS late_days,
  SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) AS leave_days,
  COUNT(*) AS marked_days
FROM attendances
WHERE date BETWEEN '$startDate' AND '$endDate'
GROUP BY teacher_id
```

**Computed field:** `attendance_pct = (present_days / marked_days) x 100` (0 if no days marked)

**View output per teacher:**
| Teacher | Present | Absent | Late | On Leave | Marked | Attendance % |
|---------|---------|--------|------|----------|--------|--------------|
With a progress bar for the percentage.

**Models/Tables:** DB facade (raw), Teacher model (for names)

---

## Report Section 2: Substitution Summary

**Purpose:** Shows who was absent most and who helped cover the most.

**Query 1 — As Original (absent) Teacher:**
```sql
SELECT original_teacher_id, COUNT(*) as times_absent
FROM substitutions
WHERE date BETWEEN '$startDate' AND '$endDate'
  AND status NOT IN ('cancelled')
GROUP BY original_teacher_id
```

**Query 2 — As Substitute Teacher:**
```sql
SELECT substitute_teacher_id, COUNT(*) as times_subbed
FROM substitutions
WHERE date BETWEEN '$startDate' AND '$endDate'
  AND status NOT IN ('cancelled')
GROUP BY substitute_teacher_id
```

**Combined:** Map both to teachers. Only teachers with at least one entry shown.

**Computed:** Net contribution = `times_subbed - times_absent` (positive = more helped than absent)

**View output per teacher:**
| Teacher | Times Absent | Times as Substitute | Net Contribution |
|---------|-------------|---------------------|-----------------|

---

## Report Section 3: Leave Summary

**Purpose:** Approved and pending leaves per teacher for the month.

**Query:**
```php
Leave::with('teacher')
  ->where(function($q) use ($startDate, $endDate) {
    $q->whereBetween('from_date', [$startDate, $endDate])
      ->orWhereBetween('to_date', [$startDate, $endDate]);
  })
  ->whereIn('status', ['approved', 'pending'])
  ->get()
  ->groupBy('teacher_id')
```

**Computed per teacher:**
- `approved_count` — number of approved leave applications
- `pending_count` — number of pending leave applications
- `total_days` — sum of `$leave->days` (getDaysAttribute) for approved leaves only

**Models:** Leave model with getDaysAttribute computed property

---

## Report Section 4: Timetable Coverage

**Purpose:** Per class, how many unique periods are scheduled vs how many substitutions were assigned.

**Query:**
```sql
SELECT c.class_name,
  COUNT(DISTINCT CONCAT(t.day, '-', t.period_number)) AS scheduled_periods,
  (SELECT COUNT(*) FROM substitutions s2
   JOIN timetables t2 ON s2.timetable_id = t2.id
   WHERE t2.class_id = t.class_id
     AND s2.date BETWEEN '$startDate' AND '$endDate'
     AND s2.status != 'cancelled') AS subs_assigned
FROM timetables t
JOIN classes c ON t.class_id = c.id
WHERE t.is_active = true
GROUP BY t.class_id, c.class_name
ORDER BY c.class_name
```

**View output per class:**
| Class | Scheduled Periods | Substitutions Assigned |
|-------|------------------|----------------------|

---

## Variables Passed to View

| Variable | Type | Content |
|----------|------|---------|
| $month | string | Selected month (YYYY-MM) |
| $startDate | string | First day of month |
| $endDate | string | Last day of month |
| $totalDays | int | Number of days in month |
| $attendanceSummary | Collection | Per-teacher attendance data |
| $substitutionSummary | Collection | Per-teacher substitution data |
| $leaveSummary | Collection | Per-teacher leave data |
| $timetableCoverage | Collection | Per-class coverage data |

---

## Print Feature

View includes a Print button:
```html
<button onclick="window.print()">Print</button>
```
No server-side logic — browser's native print dialog handles page layout.

---

## Report Data Sources Diagram

```
attendances table ────────────────────────────────► Section 1: Attendance %
  (SUM by status, group by teacher, by month)         Per teacher: present%, absent, late, on_leave

substitutions table ──────────────────────────────► Section 2: Substitution Summary
  (2 queries: as original, as substitute)              Times absent vs times covered

leaves table ─────────────────────────────────────► Section 3: Leave Summary
  (filtered by date range, grouped by teacher)         Approved count + total days

timetables + substitutions tables ────────────────► Section 4: Timetable Coverage
  (subquery: scheduled periods vs subs assigned)       Coverage per class
```

---

## Report Module is READ-ONLY

No forms, no POST routes, no DB writes. The report page only reads data.

---

## Impact Summary

| Aspect | File/Location |
|--------|--------------|
| Controller | Modules/Report/app/Http/Controllers/ReportController.php |
| Routes | Modules/Report/routes/web.php |
| View | Modules/Report/resources/views/admin/index.blade.php |
| Models Used | DB facade (raw SQL), Teacher, Leave |
| DB Tables Read | attendances, substitutions, teachers, leaves, timetables, classes |

### If you change this, also update:
| Change | Also update |
|--------|-------------|
| Add new attendance status | ReportController Section 1 (add CASE WHEN for new status in SQL query) |
| Change substitution status names | ReportController Section 2 (NOT IN ('cancelled') filter) |
| Change leaves getDaysAttribute | Section 3 total_days calculation breaks |
| Add new report section | ReportController@index (add new query + variable), admin/index.blade.php (add new table), pass new variable via compact() |
| Change attendances date column name | All raw SQL queries in ReportController |
| Change timetables.is_active column | Section 4 raw SQL WHERE clause |
