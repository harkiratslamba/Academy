<?php

namespace Modules\Teacher\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Teacher\Models\Teacher;
use Modules\Timetable\Models\Timetable;
use Modules\Substitution\Models\Substitution;
use Modules\Attendance\Models\Attendance;
use Modules\Leave\Models\Leave;

class TeacherDashboardController extends Controller
{
    public function index()
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();
        $today   = now()->toDateString();
        $dayName = now()->format('l'); // e.g. "Monday"

        // Today's timetable entries
        $todaySchedule = Timetable::with(['subject', 'schoolClass', 'section', 'room'])
            ->where('teacher_id', $teacher->id)
            ->where('day', $dayName)
            ->where('is_active', true)
            ->orderBy('period_number')
            ->get();

        // Substitute classes assigned to this teacher today
        $subClasses = Substitution::with(['timetable.subject', 'timetable.schoolClass', 'timetable.section', 'originalTeacher'])
            ->where('substitute_teacher_id', $teacher->id)
            ->where('date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->get();

        // Today's attendance status
        $attendanceToday = Attendance::where('teacher_id', $teacher->id)
            ->where('date', $today)
            ->first();

        // Pending leave requests
        $pendingLeavesCount = Leave::where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->count();

        // Weekly periods count
        $weeklyPeriods = Timetable::where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->count();

        return view('teacher::portal.dashboard', compact(
            'teacher',
            'todaySchedule',
            'subClasses',
            'attendanceToday',
            'pendingLeavesCount',
            'weeklyPeriods'
        ));
    }
}
