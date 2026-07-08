# User Manual — School Time Management System

**For:** School Administrators, Coordinators, and Teachers  
**Purpose:** Step-by-step guide to using the system. No technical knowledge required.

---

## What This System Does

The School Time Management & Teacher Substitution System helps your school:

- **Manage timetables** — who teaches which subject, when, and in which room
- **Track teacher attendance** — mark who is present, absent, or on leave every day
- **Find substitute teachers** — automatically identify available teachers when someone is absent
- **Handle leave requests** — teachers apply online; admins approve or reject
- **Generate monthly reports** — see attendance percentages, substitution patterns, and leave summaries

---

## Accessing the System

1. Open your web browser (Chrome, Firefox, Edge)
2. Type the school system URL in the address bar
3. You will see the Login page

---

## Logging In

1. On the login page, type your **Username** (not your email)
2. Type your **Password**
3. Optional: Check "Remember me" to stay logged in
4. Click **Sign In**

**If login fails:** Check that you typed the username and password correctly. Passwords are case-sensitive.

**If your account is disabled:** Contact your system administrator.

**Two types of accounts:**
- **Admin / Coordinator** — can access all features and manage everything
- **Teacher** — can only see their own information (timetable, attendance, leaves)

---

## FOR ADMINISTRATORS & COORDINATORS

After logging in, you see the **Admin Dashboard** with the main menu on the left side.

---

## Dashboard

The dashboard shows you the current state of your school at a glance:

| Card | What it shows |
|------|--------------|
| Active Teachers | Total number of currently active teachers |
| Total Classes | Number of classes in the school |
| Absent Today | Teachers absent or on leave today |
| Substitutions Today | How many substitute arrangements made today |
| Teachers Present Today | Active teachers without any absence record today |
| Pending Leave Requests | Leaves waiting for your approval |

**Red Alert Box:** If any classes are without a teacher today (absent teacher, no substitute assigned), a red box appears listing those classes. Click **Assign** to go to the Substitution page.

**Today's Timetable:** Shows all scheduled classes for today. Rows highlighted in red indicate absent teachers.

**Recent Substitutions:** Shows the 8 most recently assigned substitutions.

---

## Managing Teachers

Go to: **Admin Menu → Teachers**

### Viewing Teachers

You see a list of all teachers with their details — name, employee ID, specialization, qualification, and status.

### Adding a New Teacher

1. Click the **Add Teacher** button (green, top right)
2. Fill in the form:
   - **Name** (required) — teacher's full name
   - **Employee ID** (required, must be unique) — school-assigned ID like EMP006
   - **Username** (required) — login username for the teacher, must be unique
   - **Password** (required) — teacher's initial login password (minimum 6 characters)
   - **Email** (optional) — teacher's email address
   - **Phone** (optional) — contact number
   - **Subject Specialization** (optional but important!) — teacher's main subject, e.g. "Mathematics". This is used to suggest this teacher as a substitute for Math classes.
   - **Qualification** (optional) — academic qualification, e.g. "M.Sc Physics"
   - **Joining Date** (optional) — when the teacher joined
3. Click **Save**

The teacher's login account is created automatically at the same time.

### Editing Teacher Information

1. Find the teacher in the list
2. Click the **Edit** button (blue pencil icon)
3. Update the information
4. Click **Save**

Note: Username cannot be changed through the edit form. Contact your technical team if a username change is needed.

### Resetting a Teacher's Password

1. Find the teacher in the list
2. Click the **Reset Password** button (yellow key icon)
3. Type the new password (minimum 6 characters)
4. Type it again to confirm
5. Click **Reset**

The teacher can now log in with the new password.

### Deactivating a Teacher

1. Click **Edit** for the teacher
2. Change **Status** from "Active" to "Inactive"
3. Click **Save**

Inactive teachers do not appear in substitution suggestions and do not count in attendance.

### Deleting a Teacher

Click the **Delete** button (red trash icon). Note: If the teacher has any classes in the timetable, deletion will be blocked. You must remove their timetable entries first.

---

## Managing Classes & Sections

Go to: **Admin Menu → Classes & Sections**

You see two tables side by side — Classes on the left, Sections on the right.

### What is a Class vs a Section?

- **Class** = a grade level, like "Class 6" or "Class 9"
- **Section** = a division within a class, like "A", "B", "C"
- Example: Class 7 can have Section A (Class 7A) and Section B (Class 7B)

### Adding a New Class

1. Click **Add Class** (blue button, top right)
2. Type the class name, e.g. "Class 11"
3. Click **Save**

### Adding a Section to a Class

1. Click **Add Section** (green button, top right)
2. Select the class from the dropdown
3. Type the section name (e.g. "C")
4. Click **Save**

### Deleting a Class

Click the delete button next to the class. Note: You must delete all sections of a class before you can delete the class itself.

### Deleting a Section

Click the delete button next to the section. Note: If the section has timetable entries, deletion will be blocked. Remove those entries first.

---

## Managing Subjects

Go to: **Admin Menu → Subjects**

### Adding a Subject

1. Click **Add Subject** (top right)
2. Fill in:
   - **Subject Name** (required) — e.g. "Computer Science"
   - **Subject Code** (optional) — short code like "CS" or "COMP"
   - **Class** (optional) — if this subject is specific to one class only. Leave blank for a subject taught across all classes.
3. Click **Save**

### Editing a Subject

Click the **Edit** button next to the subject, make changes, click **Save**.

### Deleting a Subject

Click **Delete**. Note: Cannot delete a subject that is part of the timetable. Remove timetable entries first.

---

## Managing the Timetable

Go to: **Admin Menu → Timetable**

The timetable shows all scheduled teaching periods — which teacher teaches which subject to which class at which time on which day.

### Adding a New Period

1. Click **Add Period** (top right)
2. Fill in all fields:
   - **Class** — select the class (e.g. Class 8)
   - **Section** — the section (auto-fills based on class selected; leave blank if no sections)
   - **Subject** — select the subject
   - **Teacher** — select the teacher
   - **Room** — optional, which room
   - **Day** — Monday through Saturday
   - **Period Number** — 1, 2, 3... (the period sequence for the day)
   - **Start Time** — e.g. 08:00
   - **End Time** — e.g. 08:45
3. Click **Save**

**If you see an error about a clash:** The system has detected a conflict:
- "Class already has a period at this slot" — that class/section already has a different subject scheduled for this period on this day
- "Teacher already scheduled for this period" — this teacher is already teaching another class at this period on this day

Fix the clash by changing the period number, day, teacher, or class.

### Making a Period Inactive

Click the **Toggle** button next to a period. Inactive periods are hidden from the substitution system and dashboard. Use this during exam weeks or holidays.

### Deleting a Period

Click **Delete** next to a period. Warning: This also removes any substitution records linked to this period.

---

## Daily Attendance Marking

Go to: **Admin Menu → Attendance**

This is where you mark whether each teacher is present, absent, late, or on leave every day.

### Marking Attendance

1. Select the **Date** (today is pre-selected)
2. Click **Load**
3. You see a table with all active teachers
4. For each teacher, click the appropriate radio button:
   - **Present** — teacher is in school
   - **Absent** — teacher is absent (will trigger substitution alerts)
   - **Late** — teacher arrived late
   - **On Leave** — teacher is on approved leave (also triggers substitution alerts)
5. Use **Mark All Present** or **Mark All Absent** buttons for quick bulk marking
6. Click **Save Attendance**

**Summary badges** at the top show totals at a glance.

**Important:** Marking a teacher as "Absent" or "On Leave" makes their classes appear in the Substitution page as needing a substitute.

### Re-marking Attendance

You can re-visit any date and change attendance. The system saves only one record per teacher per day, so re-marking simply updates the existing record.

---

## Substitution Management

Go to: **Admin Menu → Substitutions** — or click **Manage Substitutions** from the Dashboard.

This is the most important feature. Use it when teachers are absent and their classes need coverage.

### Step-by-Step: Assign a Substitute

**Step 1:** Select the date (today is auto-selected)

**Step 2:** If teachers are already marked absent, you'll see a red alert box: "X Class(es) Need a Substitute"

**Step 3:** For each uncovered class, click the **Assign** button

**Step 4:** A popup appears showing available teachers with details:
- Green badge on same-subject teachers (prioritized)
- Workload number shows how many substitutions they've already done this month (lower = better)
- Available teachers have already been filtered — you won't see absent teachers or teachers who are busy at that period

**Step 5:** Select a teacher from the list

**Step 6:** Optionally add notes, then click **Assign Substitute**

**Step 7:** The class disappears from the "needs substitute" list and appears in the Today's Substitutions table

### Marking a Teacher Absent from the Substitution Page

You can also mark a teacher absent directly from the Substitution page without going to Attendance:

1. Click **Mark Teacher Absent** (red button, top right)
2. Select the teacher and date
3. Choose: Absent or On Leave
4. Add optional notes
5. Click **Mark Absent**

The teacher's classes immediately appear in the uncovered classes list.

### Cancelling a Substitution

In the Today's Substitutions table, click **Cancel** next to a substitution to cancel it. The class will reappear as uncovered.

### Viewing Past Substitutions

Change the date in the date picker to see substitution data for any past date.

---

## Managing Leave Requests

Go to: **Admin Menu → Leave Requests**

### Viewing Requests

Leave requests are organized in tabs:
- **Pending** — requests waiting for your decision (shown first by default)
- **Approved** — approved leave requests
- **Rejected** — rejected requests
- **All** — all requests combined

Each tab shows a count badge so you can see at a glance how many are in each category.

### Approving a Leave Request

1. Find the leave request in the **Pending** tab
2. Click the green **Approve** button (checkmark icon)
3. A popup appears showing: Teacher name, leave type, dates, reason
4. Optionally type **remarks** (e.g., "Approved — please arrange for students")
5. Click **Approve**

The leave status changes to "Approved" and moves to the Approved tab.

**Remember:** Approving a leave does NOT automatically mark attendance. You must still mark the teacher as "On Leave" in the Attendance page for the leave dates to trigger substitution alerts.

### Rejecting a Leave Request

1. Find the request in the **Pending** tab
2. Click the red **Reject** button (X icon)
3. Optionally type the **reason for rejection**
4. Click **Reject**

The teacher will see their leave status changed to "Rejected" in their portal.

---

## Monthly Reports

Go to: **Admin Menu → Reports**

### Generating a Report

1. Select the **Month** using the month picker (format: Month Year, e.g. July 2026)
2. Click **Load Report**

The page shows 4 report sections:

### Report Section 1: Attendance Summary
Shows for each teacher: how many days present, absent, late, on leave, and their overall attendance percentage (shown as a progress bar).

### Report Section 2: Substitution Summary
Shows which teachers were absent and needed substitutes, and which teachers stepped in as substitutes. The "Net Contribution" shows if a teacher helped more than they were absent.

### Report Section 3: Leave Summary
Shows approved and pending leave applications per teacher, and total number of leave days taken.

### Report Section 4: Timetable Coverage
Shows per class: how many periods are scheduled in the timetable, and how many substitutions were arranged for that class during the month.

### Printing the Report

Click the **Print** button at the top right. The browser's print dialog will open — you can print to paper or save as PDF.

---

## FOR TEACHERS

When a teacher logs in, they see the **Teacher Portal** with their personal information only.

---

## Teacher Dashboard

Shows:
- Today's teaching schedule
- Any substitution assignments (classes you're covering for a colleague)
- Your pending leave request status

---

## Your Timetable

Go to: **My Timetable**

Shows your complete weekly teaching schedule — what you teach, to which class, on which day and period. This is read-only; contact admin to make changes to the timetable.

---

## Marking Your Availability

Go to: **My Availability**

You can inform the system when you are or are not available for a specific period on a specific date. This information helps the admin when assigning substitutes.

1. Select **Date**
2. Select **Period** (or leave blank for the whole day)
3. Select your status: **Available**, **Unavailable**, or **On Leave**
4. Add an optional reason
5. Click **Save**

---

## Applying for Leave

Go to: **My Leaves**

### Applying

1. Click **Apply for Leave** (or the form at the top of the page)
2. Fill in:
   - **Leave Type:**
     - Sick — for medical reasons
     - Casual — for personal reasons
     - Earned — for earned/privilege leave
     - Other — any other reason
   - **From Date** — start date of leave
   - **To Date** — end date of leave (can be same as From Date for one-day leave)
   - **Reason** — brief explanation (required)
3. Click **Submit Leave Request**

Your request appears in the list with status "Pending". You will see it change to Approved or Rejected after the admin reviews it.

### Cancelling a Leave Request

You can only cancel leave requests that are still **Pending**.
1. Find the pending request
2. Click **Cancel** (delete button)
3. The request is removed

You cannot cancel an already approved or rejected request. Contact your admin if needed.

---

## Viewing Your Attendance

Go to: **My Attendance**

Shows your attendance history — each date with your marked status (Present, Absent, Late, On Leave). This is read-only. Contact admin if you see an error.

---

## Frequently Asked Questions

**Q: I forgot my password. What do I do?**  
A: Contact your school administrator. They can reset your password in the Teacher Management section.

**Q: My username doesn't work.**  
A: Your username is NOT your email address. It is usually a simple word like your first name (e.g., "rajesh"). Ask your admin what your username is.

**Q: Why can't the admin delete a teacher?**  
A: The teacher has class periods assigned in the timetable. The admin must remove those timetable entries first, then delete the teacher.

**Q: I marked a teacher absent but their classes don't show in the substitution alerts.**  
A: Make sure the attendance date matches today, and that there are timetable entries for that teacher for today's day of the week (e.g., if today is Monday, check if the teacher has Monday periods in the timetable).

**Q: Why don't I see some teachers in the substitute list?**  
A: The system automatically hides teachers who are: (1) absent on that date, (2) already teaching their own class at that time, or (3) already assigned as a substitute for that period. Only genuinely available teachers are shown.

**Q: A teacher's leave is approved but their classes still don't show as needing a substitute.**  
A: Approving a leave request does not automatically update attendance. The admin must also go to the Attendance page and mark the teacher as "On Leave" for the leave dates.

**Q: The monthly report shows 0 attendance days for a teacher.**  
A: Their attendance was not marked for that month. Go to Attendance, select the relevant dates, and mark their attendance status.

**Q: Can teachers edit their own timetable?**  
A: No. Only administrators and coordinators can change the timetable. Teachers can view their schedule but not modify it.

**Q: What is "subject specialization" used for?**  
A: When assigning a substitute, the system shows teachers with matching subject specialization first (highlighted in green). It helps admins quickly find the most suitable substitute.

---

## Glossary

| Term | Meaning |
|------|---------|
| Timetable Entry / Period | One scheduled class: teacher + subject + class + day + time |
| Substitution | A replacement teacher covering for an absent teacher's class |
| Workload | How many times a teacher has covered as a substitute this month |
| Period Number | The sequence of class periods in a school day (Period 1 = first class of the day) |
| Section | A division within a class (e.g., Class 7A, Class 7B) |
| Employee ID | The school-assigned code for a teacher (e.g., EMP001) |
| Attendance % | Present days ÷ Total marked days × 100 |
| Unassigned Class | A class that needs a substitute but none has been assigned yet |
| On Leave | A teacher who has an approved leave for that day |
| Pending | A leave request waiting for admin approval |
