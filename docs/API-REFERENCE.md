# API Reference — School Time Management System

## Overview

This system uses server-side rendering (Blade templates) for most pages. A small number of AJAX endpoints return JSON for dynamic UI features. All endpoints require authentication (auth middleware).

---

## AJAX JSON Endpoints

### 1. Get Sections for a Class

Used by the Timetable Add Period form to dynamically populate the section dropdown when a class is selected.

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/api/sections/{classId}` |
| Auth | Required (session cookie) |
| Route Name | (anonymous, in routes/web.php) |
| Controller | Inline closure in routes/web.php |

**Path Parameter:**
- `classId` — integer, the class ID

**Success Response (200):**
```json
[
  {"id": 1, "section_name": "A"},
  {"id": 2, "section_name": "B"}
]
```

**Empty Response (no sections):**
```json
[]
```

**DB Query:** `Section::where('class_id', $classId)->get(['id', 'section_name'])`

**Used in:** Timetable admin view (Modules/Timetable/resources/views/admin/index.blade.php) — JavaScript event listener on class dropdown change.

---

### 2. Get Available Substitute Teachers

The core AJAX call for the substitution assignment modal. Returns teachers eligible to cover a specific class period on a specific date, sorted by suitability.

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/api/available-teachers` OR `/admin/substitution/available-teachers` |
| Auth | Required |
| Route Name | admin.substitution.availableTeachers |
| Controller | SubstitutionController@availableTeachers |
| File | Modules/Substitution/app/Http/Controllers/SubstitutionController.php |

**Query Parameters:**
| Parameter | Required | Type | Description |
|-----------|----------|------|-------------|
| timetable_id | Yes | integer | ID of the timetable entry needing a sub |
| date | Yes | string | Date (YYYY-MM-DD) |

**Algorithm (in order):**
1. Load timetable entry with its subject
2. Get `$absentIds` — teachers absent or on_leave on the date (from `attendances`)
3. Get `$busyIds` — teachers who have their own timetable entry at the same period on the same day (from `timetables`)
4. Get `$alreadySubIds` — teachers already assigned as substitute for this period/date (from `substitutions`, non-cancelled)
5. `$excludeIds` = unique merge of all three exclusion lists
6. Get `$workloadMap` — count of this month's substitutions per teacher (from `substitutions`)
7. Return active teachers NOT in `$excludeIds` with computed fields
8. Sort: `same_subject=true` first, then `workload` ascending

**Success Response (200):**
```json
{
  "teachers": [
    {
      "id": 3,
      "name": "Priya Sharma",
      "employee_id": "EMP002",
      "specialization": "English",
      "same_subject": true,
      "workload": 2
    },
    {
      "id": 5,
      "name": "Vikram Patel",
      "employee_id": "EMP005",
      "specialization": "Social Science",
      "same_subject": false,
      "workload": 0
    }
  ]
}
```

**Field Descriptions:**
| Field | Description |
|-------|-------------|
| id | Teacher ID for form submission |
| name | Teacher display name |
| employee_id | School employee code |
| specialization | Teacher's subject specialization |
| same_subject | true if specialization matches the class subject |
| workload | Number of substitutions already done this month |

**DB Tables Read:** attendances, timetables, substitutions, teachers, subjects

**Used in:** Substitution admin view modal — when admin clicks "Assign" button, an AJAX call fetches this list and populates the teacher dropdown.

---

### 3. Notification Poll (Stub)

Stub endpoint for future real-time notifications. Currently returns empty data.

| Property | Value |
|----------|-------|
| Method | GET |
| URL | `/notifications/poll` |
| Auth | Required |

**Response:**
```json
{"unread_count": 0, "notifications": []}
```

**Future implementation:** Should return actual unread notifications for the logged-in user from `school_notifications` table.

---

### 4. Mark Notification Read (Stub)

Stub endpoint. Currently does nothing except return success.

| Property | Value |
|----------|-------|
| Method | POST |
| URL | `/notifications/read` |
| Auth | Required |

**Response:**
```json
{"ok": true}
```

---

## Cron Jobs

**Currently: NONE configured.**

The application has no scheduled tasks. All operations are request-driven.

### Recommended Future Cron Jobs

Add these to `app/Console/Kernel.php` or Laravel 11 `routes/console.php`:

| Job | Frequency | Purpose |
|-----|-----------|---------|
| Auto-mark attendance from approved leaves | Daily, 7am | For teachers with approved leaves, auto-create attendance record status='on_leave' |
| Complete pending substitutions | Daily, end of school day | Update substitution status: assigned → completed |
| Monthly report email | 1st of each month | Email monthly report to admin |
| Notify teachers of substitution | On assignment | Currently missing — notify substitute teacher via email/SMS |

---

## Background Processes

**Currently: NONE.** All operations are synchronous.

All form submissions complete within a single HTTP request-response cycle. No queues, jobs, or async processing.

### Recommended Future Background Jobs

- Email notification when substitute is assigned (queue job)
- SMS notification for urgent substitutions
- Report generation for large datasets (queue if slow)

---

## Database Seeder

Technically not an API, but a key process for initializing the system:

**Command:** `php artisan db:seed` (or `php artisan migrate --seed`)  
**File:** `database/seeders/DatabaseSeeder.php`

**What it creates:**

| Type | Count | Details |
|------|-------|---------|
| Admin user | 1 | username: admin, password: admin123 |
| Teacher users | 5 | rajesh, priya, amit, sunita, vikram (password: teacher123) |
| Teacher profiles | 5 | Linked to teacher users with specializations |
| Classes | 5 | Class 6 through Class 10 |
| Sections | 10 | 2 per class (A and B) |
| Rooms | 5 | 101, 102, 201, 202, Lab-1 |
| Subjects | 6 | Mathematics, English, Science, Hindi, Social Science, Computer Science |
| Timetable entries | 3 | Sample entries for Monday-Wednesday, Period 1 |

---

## Error Responses

All web routes return HTML (Blade views). Error handling:

| Scenario | Response |
|----------|----------|
| Unauthenticated | Redirect to /login |
| Unauthorized role | 403 Forbidden page |
| Model not found | 404 Not Found page |
| Validation failed | Redirect back with errors |
| DB constraint violation | Redirect back with error flash message |

For AJAX endpoints specifically (the JSON APIs above), validation failures return HTTP 422 with JSON error details (Laravel default behavior).

---

## Authentication

**Type:** Session-based (not API tokens)  
**Field:** `username` (not email) in login form  
**Session storage:** `sessions` table in database  
**Remember me:** 30-day cookie  
**No Bearer tokens, no JWT, no OAuth** — standard Laravel session auth only.

To add API token auth for mobile apps or external integrations, implement Laravel Sanctum.
