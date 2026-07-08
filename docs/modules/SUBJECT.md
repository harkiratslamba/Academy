# Subject Module — MVC Flow Documentation

**Location:** `Modules/Subject/`  
**Purpose:** Manages academic subjects with optional class assignment and subject codes.

---

## Module Files

| Type | File |
|------|------|
| Controller | Modules/Subject/app/Http/Controllers/SubjectController.php |
| Routes | Modules/Subject/routes/web.php |
| View | Modules/Subject/resources/views/admin/index.blade.php |
| Model | Modules/Subject/app/Models/Subject.php |
| DB Table | subjects |

---

## Flow 1: View Subjects

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/admin/subjects` |
| Route Name | admin.subjects.index |
| Controller@Method | SubjectController@index |
| Middleware | auth, role:admin,coordinator |

**Data:**
- `$subjects = Subject::with('schoolClass')->orderBy('subject_name')->get()`
- `$classes = SchoolClass::orderBy('class_name')->get()` (for Add/Edit dropdowns)

**UI:** DataTable with Add and Edit/Delete modals

---

## Flow 2: Add Subject

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/subjects` |
| Route Name | admin.subjects.store |
| Controller@Method | SubjectController@store |

**Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| subject_name | Yes | required, string | subjects | subject_name |
| subject_code | No | nullable, unique:subjects | subjects | subject_code |
| class_id | No | nullable, exists:classes | subjects | class_id |

**Business Logic:** `Subject::create($validated)`

**DB Table Written:** subjects

**Note on class_id:**
- If `class_id` is set → subject is specific to that class only
- If `class_id` is NULL → subject applies to all classes (general subject)

---

## Flow 3: Edit Subject

| Property | Value |
|----------|-------|
| Method | PUT |
| URL | `/admin/subjects/{subject}` |
| Route Name | admin.subjects.update |
| Controller@Method | SubjectController@update |

**Form Fields:** Same as store, but subject_code validation: `Rule::unique('subjects','subject_code')->ignore($subject->id)`

**DB Table Written:** subjects

---

## Flow 4: Delete Subject

| Property | Value |
|----------|-------|
| Method | DELETE |
| URL | `/admin/subjects/{subject}` |
| Route Name | admin.subjects.destroy |
| Controller@Method | SubjectController@destroy |

**Business Logic:**
1. Check if `Timetable::where('subject_id', $id)->exists()` → block if true
2. `Subject::findOrFail($id)->delete()`

**DB Table Written:** subjects (deleted)  
**Why the check:** timetables.subject_id is a FK — MySQL would block deletion anyway, but we give a user-friendly error message instead.

---

## Model Relationships

```php
Subject
  ├── schoolClass() → belongsTo SchoolClass  (optional link to a class)
  └── (referenced by Timetable via subject_id)
```

---

## Subject in Substitution Logic

The `subject_name` field is used by `SubstitutionController@availableTeachers` to determine `same_subject`:

```php
$sameSubject = $teacher->subject_specialization &&
    str_contains(
        strtolower($teacher->subject_specialization),
        strtolower($timetable->subject->subject_name)
    );
```

Teachers whose `subject_specialization` contains the subject name are listed FIRST as substitute suggestions.

---

## Impact Summary

| Aspect | File/Location |
|--------|--------------|
| Controller | Modules/Subject/app/Http/Controllers/SubjectController.php |
| Routes | Modules/Subject/routes/web.php |
| View | Modules/Subject/resources/views/admin/index.blade.php |
| Model | Modules/Subject/app/Models/Subject.php |
| DB Table | subjects |

### If you change this, also update:
| Change | Also update |
|--------|-------------|
| Rename subject_name column | SubstitutionController@availableTeachers (same_subject logic uses subject_name) |
| Add subject fields | Migration, Subject model $fillable, SubjectController store+update, view modal |
| Change subject_code from nullable to required | SubjectController validation, existing subjects without codes will fail validation |
| Remove class_id | Migration, Subject model, view (hide class column), remove from store/update |
