# Leave Module — MVC Flow Documentation

**Location:** `Modules/Leave/`  
**Purpose:** Teacher leave request system with admin approval/rejection workflow.

---

## Module Files

| Type | File |
|------|------|
| Admin Controller | Modules/Leave/app/Http/Controllers/LeaveController.php |
| Teacher Controller | Modules/Teacher/app/Http/Controllers/TeacherLeaveController.php |
| Routes | Modules/Leave/routes/web.php + Modules/Teacher/routes/web.php |
| Admin View | Modules/Leave/resources/views/admin/index.blade.php |
| Teacher View | Modules/Teacher/resources/views/portal/leaves.blade.php |
| Model | Modules/Leave/app/Models/Leave.php |
| DB Table | leaves |

---

## Teacher Side

### Flow T1: View Own Leave Requests

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/teacher/leaves` |
| Route Name | teacher.leaves.index |
| Controller@Method | TeacherLeaveController@index |
| Middleware | auth, role:teacher |

**Data:**
```
Leave::where('teacher_id', auth()->user()->teacher_id)
  ->orderByDesc('created_at')
  ->get()
```
Shows all leaves for the logged-in teacher with status badges.

---

### Flow T2: Apply for Leave

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/teacher/leaves` |
| Route Name | teacher.leaves.store |
| Controller@Method | TeacherLeaveController@store |

**Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| leave_type | Yes | in: sick,casual,earned,other | leaves | leave_type |
| from_date | Yes | required, date | leaves | from_date |
| to_date | Yes | required, date, after_or_equal:from_date | leaves | to_date |
| reason | Yes | required, string, max 1000 | leaves | reason |

**Business Logic:**
1. Get `$teacherId = auth()->user()->teacher_id`
2. `Leave::create([teacher_id, leave_type, from_date, to_date, reason, status='pending'])`
3. Redirect back with "Leave request submitted successfully"

**DB Table Written:** leaves  
**Columns Written:** teacher_id, leave_type, from_date, to_date, reason, status ('pending')

---

### Flow T3: Cancel Leave Request

| Property | Value |
|----------|-------|
| Method | DELETE |
| URL | `/teacher/leaves/{id}` |
| Route Name | teacher.leaves.cancel |
| Controller@Method | TeacherLeaveController@cancel |

**Business Logic:**
1. `Leave::findOrFail($id)` — verifies it exists
2. Verify `$leave->teacher_id == auth()->user()->teacher_id` — teacher can only cancel own leaves
3. Verify `$leave->status == 'pending'` — cannot cancel approved/rejected
4. `$leave->delete()` — hard delete

**DB Table Written:** leaves (deleted)

---

## Admin Side

### Flow A1: View All Leave Requests

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/admin/leaves?status=pending` |
| Route Name | admin.leaves.admin |
| Controller@Method | LeaveController@adminIndex |
| Middleware | auth, role:admin,coordinator |

**Query Parameter:** `status` — one of: pending, approved, rejected, all (default: pending)

**Data:**
```
$query = Leave::with(['teacher', 'approvedBy'])->orderByDesc('created_at');
if ($status !== 'all') {
    $query->where('status', $status);
}
$leaves = $query->get();

$counts = [
    'pending'  => Leave::where('status', 'pending')->count(),
    'approved' => Leave::where('status', 'approved')->count(),
    'rejected' => Leave::where('status', 'rejected')->count(),
    'all'      => Leave::count(),
];
```

**UI:**
- Tabs: Pending (n) / Approved (n) / Rejected (n) / All (n)
- DataTable with: Teacher, Type, From, To, Days, Reason, Status, Applied On, Actions
- Approve / Reject buttons (only for pending leaves) → open modal

---

### Flow A2: Approve Leave

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/leaves/{id}/approve` |
| Route Name | admin.leaves.approve |
| Controller@Method | LeaveController@approve |

**Form Field:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| admin_remarks | No | nullable, max 500 | leaves | admin_remarks |

**Business Logic:**
1. Find leave: `Leave::findOrFail($id)`
2. Check `$leave->status === 'pending'` → if not, error "Already processed"
3. `$leave->update([status='approved', approved_by=auth()->id(), approved_on=now(), admin_remarks])`
4. Redirect back with "Leave approved for {teacher name}"

**DB Table Written:** leaves (status, approved_by, approved_on, admin_remarks columns)

---

### Flow A3: Reject Leave

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/leaves/{id}/reject` |
| Route Name | admin.leaves.reject |
| Controller@Method | LeaveController@reject |

**Form Field:** admin_remarks (optional)

**Business Logic:** Same as approve but `status='rejected'`

**DB Table Written:** leaves (status, approved_by, approved_on, admin_remarks)

---

## Leave Types

| Type | Description |
|------|-------------|
| sick | Medical / health reasons |
| casual | Personal / casual reasons |
| earned | Earned/privilege leave |
| other | Any other reason |

---

## Leave Status Lifecycle

```
Teacher applies → status = 'pending'
                         │
              Admin reviews (Pending tab)
                         │
                  ┌──────┴──────┐
                  │             │
              Approve        Reject
                  │             │
          status='approved'  status='rejected'
               (one-way — cannot be changed back)
```

---

## Leave Days Calculation

The `Leave` model has a computed attribute:
```php
public function getDaysAttribute(): int
{
    return \Carbon\Carbon::parse($this->from_date)
        ->diffInDays(\Carbon\Carbon::parse($this->to_date)) + 1;
}
```
Used in: Report module (total leave days per teacher), leave admin view (Days column).

---

## Integration Notes

**Leave → Substitution:** Approving a leave does NOT automatically mark attendance as 'on_leave'. Admin must MANUALLY go to Attendance page and mark the teacher as on_leave for the leave dates to trigger substitution workflow.

**Leave → Dashboard:** DashboardController counts `Leave::where('status','pending')->count()` for the "Pending Leaves" stat card.

**Leave → Report:** ReportController section 3 uses leaves approved in the month + getDaysAttribute.

---

## Leave Application Flow

```
Teacher logs in → /teacher/leaves
        │
Views existing leave requests with status badges
        │
Clicks "Apply for Leave"
        │
Fills: leave_type + from_date + to_date + reason
        │
POST /teacher/leaves
        │
Validation:
  to_date >= from_date? NO → error "to_date must be after from_date"
        │
  YES → Leave::create([teacher_id, leave_type, from_date, to_date, reason, status='pending'])
        │
Redirect: "Leave request submitted. Pending admin approval."
        │
Admin sees it in /admin/leaves (Pending tab)
        │
Admin opens Approve/Reject modal → adds optional remarks → submits
        │
Leave status updated → teacher sees updated status on their leaves page
```

---

## Impact Summary

| Aspect | File/Location |
|--------|--------------|
| Admin Controller | Modules/Leave/app/Http/Controllers/LeaveController.php |
| Teacher Controller | Modules/Teacher/app/Http/Controllers/TeacherLeaveController.php |
| Admin Routes | Modules/Leave/routes/web.php |
| Teacher Routes | Modules/Teacher/routes/web.php |
| Admin View | Modules/Leave/resources/views/admin/index.blade.php |
| Teacher View | Modules/Teacher/resources/views/portal/leaves.blade.php |
| Model | Modules/Leave/app/Models/Leave.php |
| DB Table | leaves |
| Computed Attribute | getDaysAttribute() — used by Report module |

### If you change this, also update:
| Change | Also update |
|--------|-------------|
| Add new leave_type (e.g., 'maternity') | Migration (ALTER ENUM), LeaveController validation, TeacherLeaveController validation, leave views |
| Auto-mark attendance on leave approval | LeaveController@approve — add Attendance::updateOrCreate for each date in range |
| Add leave balance/quota system | New leave_balances table + migration + model + controller logic in TeacherLeaveController@store |
| Change getDaysAttribute | ReportController section 3 (uses this attribute to sum leave days) |
| Add email notification on approval/rejection | LeaveController@approve + @reject |
