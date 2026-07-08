# Classes Module — MVC Flow Documentation

**Location:** `Modules/Classes/`  
**Purpose:** Manages school classes (Class 6, 7...) and their sections (A, B...). Also manages rooms.

---

## Module Files

| Type | File |
|------|------|
| Controller | Modules/Classes/app/Http/Controllers/ClassesController.php |
| Routes | Modules/Classes/routes/web.php |
| View | Modules/Classes/resources/views/admin/index.blade.php |
| Models | SchoolClass (table: classes), Section, Room |

---

## Flow 1: View Classes & Sections

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/admin/classes` |
| Route Name | admin.classes.index |
| Controller@Method | ClassesController@index |
| Middleware | auth, role:admin,coordinator |

**Data:**
- `$classes = SchoolClass::withCount('sections')->orderBy('class_name')->get()`
- `$sections = Section::with('schoolClass')->orderBy('class_id')->get()`
- `$classes_for_dropdown = SchoolClass::orderBy('class_name')->get()` (for Add Section form)

**UI:** Two side-by-side tables + Add buttons opening modals

---

## Flow 2: Add Class

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/classes` |
| Route Name | admin.classes.store |
| Controller@Method | ClassesController@storeClass |

**Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| class_name | Yes | required, unique:classes | classes | class_name |

**Business Logic:**
1. Validate class_name is unique
2. `SchoolClass::create(['class_name' => $request->class_name])`
3. Redirect back with success

**DB Table Written:** classes

---

## Flow 3: Delete Class

| Property | Value |
|----------|-------|
| Method | DELETE |
| URL | `/admin/classes/{id}` |
| Route Name | admin.classes.destroy |

**Business Logic:**
1. Check if class has sections: `Section::where('class_id', $id)->exists()`
2. If yes → redirect with error "Delete all sections of this class first"
3. If no → `SchoolClass::findOrFail($id)->delete()`

**DB Table Written:** classes (deleted)  
**FK cascade:** subjects.class_id sets to NULL (nullOnDelete)

---

## Flow 4: Add Section

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/admin/sections` |
| Route Name | admin.sections.store |
| Controller@Method | ClassesController@storeSection |

**Form Fields:**
| Field | Required | Validation | DB Table | Column |
|-------|----------|-----------|----------|--------|
| class_id | Yes | required, exists:classes | sections | class_id |
| section_name | Yes | required, max 10 | sections | section_name |

**Business Logic:** `Section::create(['class_id' => $class_id, 'section_name' => $name])`

**DB Table Written:** sections

---

## Flow 5: Delete Section

| Property | Value |
|----------|-------|
| Method | DELETE |
| URL | `/admin/sections/{id}` |
| Route Name | admin.sections.destroy |

**Business Logic:**
1. Check if section has timetable entries: `Timetable::where('section_id', $id)->exists()`
2. If yes → error "Remove timetable entries for this section first"
3. If no → `Section::findOrFail($id)->delete()`

**FK cascade on delete:** timetables.section_id (this would cause issues if not checked first — hence the manual check)

---

## AJAX API: Get Sections for a Class

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/api/sections/{classId}` |
| Auth | Required |
| Returns | JSON array |

**Used by:** Timetable Add Period form. When admin selects a class from dropdown, JavaScript fetches sections.

```javascript
// In timetable view
fetch('/api/sections/' + classId)
  .then(r => r.json())
  .then(sections => { /* populate section dropdown */ })
```

**DB Query:** `Section::where('class_id', $classId)->get(['id', 'section_name'])`

---

## Class-Section Hierarchy

```
Class 6 ──► Section A
         ──► Section B

Class 7 ──► Section A
         ──► Section B

Class 8 ──► Section A
         ──► Section B
...

Timetable entry references:
  class_id (required) + section_id (optional)
  UNIQUE constraint: (class_id, section_id, day, period_number)
```

---

## Impact Summary

| Aspect | File/Location |
|--------|--------------|
| Controller | Modules/Classes/app/Http/Controllers/ClassesController.php |
| Routes | Modules/Classes/routes/web.php |
| View | Modules/Classes/resources/views/admin/index.blade.php |
| Models | SchoolClass (table: classes), Section, Room |
| DB Tables Written | classes, sections |
| API | routes/web.php line: GET /api/sections/{classId} |

### If you change this, also update:
| Change | Also update |
|--------|-------------|
| Add class fields (e.g., grade_level) | Migration, SchoolClass model $fillable, ClassesController, admin view modal |
| Delete class | Check subjects.class_id (currently set to NULL), check timetables.class_id (FK blocks delete) |
| Rename 'classes' table | Update $table = 'classes' in SchoolClass model, all raw SQL queries in DashboardController and SubstitutionController |
| Add room management UI | ClassesController (add storeRoom/destroyRoom methods), view, routes |
