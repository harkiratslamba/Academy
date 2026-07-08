# Database Schema — School Time Management System

## Overview

Database: `school_management` (MySQL)  
All tables use auto-increment `id` as primary key and `created_at`/`updated_at` timestamps unless noted.  
No soft deletes — hard deletes with FK protection.  
ENUM used for status fields (not lookup tables).

---

## Table: users

**Model:** `App\Models\User`  
**Purpose:** Authentication accounts for all users (admin, teachers, coordinators)

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| username | varchar(255) | NO | - | Unique login identifier |
| name | varchar(255) | YES | NULL | Display name |
| email | varchar(255) | YES | NULL | Email (optional, unique) |
| password | varchar(255) | NO | - | Bcrypt hashed |
| role | enum(admin,teacher,coordinator) | NO | teacher | User role for access control |
| teacher_id | bigint unsigned | YES | NULL | FK → teachers.id (for teacher accounts) |
| status | tinyint(1) | NO | 1 | 1=active, 0=disabled |
| last_login | timestamp | YES | NULL | Last successful login time |
| remember_token | varchar(100) | YES | NULL | Laravel remember-me token |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Unique Indexes:** username, email  
**Notes:** `teacher_id` and `teachers.user_id` form a bidirectional link between auth and teacher profile.

---

## Table: teachers

**Model:** `Modules\Teacher\Models\Teacher`  
**Purpose:** Teacher professional profiles (linked to users table)

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| user_id | bigint unsigned | YES | NULL | FK → users.id |
| name | varchar(255) | NO | - | Full name |
| email | varchar(255) | YES | NULL | Teacher email |
| phone | varchar(20) | YES | NULL | Contact number |
| employee_id | varchar(50) | YES | NULL | School employee ID (e.g. EMP001) |
| subject_specialization | varchar(255) | YES | NULL | Main subject taught |
| qualification | varchar(255) | YES | NULL | Academic qualification |
| joining_date | date | YES | NULL | Date joined school |
| status | enum(active,inactive) | NO | active | Employment status |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Unique Indexes:** email, employee_id  
**FK:** user_id → users(id) NULL ON DELETE

---

## Table: classes

**Model:** `Modules\Classes\Models\SchoolClass` (table name: `classes`)  
**Purpose:** School class definitions (Class 6, Class 7, etc.)

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| class_name | varchar(255) | NO | - | e.g. "Class 6" |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Unique Indexes:** class_name

---

## Table: sections

**Model:** `Modules\Classes\Models\Section`  
**Purpose:** Sections within a class (A, B, C...)

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| class_id | bigint unsigned | NO | - | FK → classes.id |
| section_name | varchar(10) | NO | - | e.g. "A", "B" |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**FK:** class_id → classes(id) CASCADE DELETE

---

## Table: rooms

**Model:** `Modules\Classes\Models\Room`  
**Purpose:** Physical classroom/lab rooms

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| room_number | varchar(20) | NO | - | Room identifier (101, Lab-1) |
| capacity | int | NO | 40 | Student capacity |
| floor | varchar(20) | YES | NULL | Floor location |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Unique Indexes:** room_number

---

## Table: subjects

**Model:** `Modules\Subject\Models\Subject`  
**Purpose:** Academic subjects offered

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| subject_name | varchar(255) | NO | - | e.g. "Mathematics" |
| subject_code | varchar(20) | YES | NULL | Short code (MATH, ENG) |
| class_id | bigint unsigned | YES | NULL | FK → classes.id (NULL = all classes) |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Unique Indexes:** subject_code  
**FK:** class_id → classes(id) NULL ON DELETE

---

## Table: timetables

**Model:** `Modules\Timetable\Models\Timetable`  
**Purpose:** Core schedule — which teacher teaches which subject to which class at which period on which day

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| class_id | bigint unsigned | NO | - | FK → classes.id |
| section_id | bigint unsigned | YES | NULL | FK → sections.id |
| subject_id | bigint unsigned | NO | - | FK → subjects.id |
| teacher_id | bigint unsigned | NO | - | FK → teachers.id |
| room_id | bigint unsigned | YES | NULL | FK → rooms.id |
| day | enum(Monday,Tuesday,Wednesday,Thursday,Friday,Saturday) | NO | - | Day of week |
| period_number | tinyint unsigned | NO | - | Period number (1-8 typically) |
| start_time | time | NO | - | Period start time |
| end_time | time | NO | - | Period end time |
| is_active | tinyint(1) | NO | 1 | Active/inactive toggle |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Unique Constraints:**
- `unique_class_period`: (class_id, section_id, day, period_number) — prevents two subjects for same class at same period
- `unique_teacher_period`: (teacher_id, day, period_number) — prevents teacher teaching two classes at same period

**This is the clash-detection mechanism — DB enforces it, not application code.**

---

## Table: teacher_availabilities

**Model:** `Modules\Timetable\Models\TeacherAvailability`  
**Purpose:** Teacher self-reported availability for specific dates/periods

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| teacher_id | bigint unsigned | NO | - | FK → teachers.id |
| date | date | NO | - | Specific date |
| period_number | tinyint unsigned | YES | NULL | Specific period (NULL = whole day) |
| status | enum(available,unavailable,on_leave) | NO | available | Availability status |
| reason | text | YES | NULL | Reason/notes |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Unique Constraint:** (teacher_id, date, period_number)  
**FK:** teacher_id → teachers(id) CASCADE DELETE

---

## Table: attendances

**Model:** `Modules\Attendance\Models\Attendance`  
**Purpose:** Daily teacher attendance record — ONE record per teacher per day

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| teacher_id | bigint unsigned | NO | - | FK → teachers.id |
| date | date | NO | - | Attendance date |
| status | enum(present,absent,late,on_leave) | NO | present | Attendance status |
| check_in | time | YES | NULL | Actual check-in time |
| notes | text | YES | NULL | Admin notes |
| marked_by | bigint unsigned | YES | NULL | FK → users.id (who marked it) |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Unique Constraint:** (teacher_id, date) — one record per teacher per day  
**FK:** teacher_id → teachers(id) CASCADE DELETE; marked_by → users(id) NULL ON DELETE  
**Key Note:** UPSERT pattern used (updateOrCreate) — re-marking updates existing record

**Triggers substitution workflow:** status = 'absent' or 'on_leave' → SubstitutionController shows unassigned classes

---

## Table: substitutions

**Model:** `Modules\Substitution\Models\Substitution`  
**Purpose:** Records substitute teacher assignments for absent teacher's classes

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| original_teacher_id | bigint unsigned | NO | - | FK → teachers.id (absent teacher) |
| substitute_teacher_id | bigint unsigned | NO | - | FK → teachers.id (covering teacher) |
| timetable_id | bigint unsigned | NO | - | FK → timetables.id (which class/period) |
| date | date | NO | - | Date of substitution |
| status | enum(pending,assigned,completed,cancelled) | NO | assigned | Current status |
| assigned_by | bigint unsigned | YES | NULL | FK → users.id (admin who assigned) |
| notes | text | YES | NULL | Optional notes |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**FK:** original_teacher_id, substitute_teacher_id → teachers(id); timetable_id → timetables(id); assigned_by → users(id) NULL ON DELETE  
**Note:** Cancelled via status update (not hard delete) to preserve audit trail

---

## Table: leaves

**Model:** `Modules\Leave\Models\Leave`  
**Purpose:** Teacher leave requests with approval workflow

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| teacher_id | bigint unsigned | NO | - | FK → teachers.id |
| leave_type | enum(sick,casual,earned,other) | NO | casual | Type of leave |
| from_date | date | NO | - | Leave start date |
| to_date | date | NO | - | Leave end date |
| reason | text | NO | - | Reason for leave |
| status | enum(pending,approved,rejected) | NO | pending | Approval status |
| approved_by | bigint unsigned | YES | NULL | FK → users.id (admin) |
| approved_on | timestamp | YES | NULL | When approved/rejected |
| admin_remarks | text | YES | NULL | Admin's remarks/reason |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**FK:** teacher_id → teachers(id) CASCADE DELETE; approved_by → users(id) NULL ON DELETE  
**Computed:** `getDaysAttribute()` returns (to_date - from_date) + 1 days count

---

## Table: school_notifications

**Model:** `App\Models\SchoolNotification`  
**Purpose:** In-app notifications (currently stub — notifications module pending)

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | Primary key |
| user_id | bigint unsigned | NO | - | FK → users.id |
| title | varchar(255) | NO | - | Notification title |
| message | text | NO | - | Notification body |
| type | varchar(255) | NO | - | Notification category |
| is_read | tinyint(1) | NO | 0 | Read/unread flag |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

---

## Entity Relationship Summary

```
users ──────────────────── teachers
  (users.teacher_id → teachers.id)
  (teachers.user_id → users.id)
  [Bidirectional link between auth and profile]

classes ─────────────────► sections
  (one class has many sections)
  (sections.class_id → classes.id)

classes ─────────────────► subjects (optional)
  (subject.class_id nullable → classes.id)

timetables connects:
  teachers ──┐
  classes  ──┤
  sections ──┤── timetables ──► substitutions
  subjects ──┤     │
  rooms    ──┘     └──► teacher_availabilities (same teacher/period)

attendances: teachers × date = one record
  (triggers substitution workflow when status = absent/on_leave)

leaves: teacher submits → admin approves/rejects
  (manual: admin must also mark attendance for leave dates)
```

---

## Key Database Design Decisions

| Decision | Reason |
|----------|--------|
| UNIQUE constraint for clash detection | DB enforces schedule conflicts, not just app code — prevents race conditions |
| ENUM for status fields | Simpler than lookup tables for small fixed sets |
| No soft deletes | Referential integrity managed via FK constraints |
| UPSERT for attendance | One record per teacher per day — re-marking updates, not duplicates |
| Bidirectional users↔teachers link | Allows querying from either direction efficiently |
| Substitution uses status='cancelled' | Preserves audit trail instead of hard delete |
