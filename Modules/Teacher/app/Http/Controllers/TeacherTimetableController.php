<?php

namespace Modules\Teacher\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Teacher\Models\Teacher;
use Modules\Timetable\Models\Timetable;

class TeacherTimetableController extends Controller
{
    public function index()
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // All active timetable entries for this teacher
        $entries = Timetable::with(['subject', 'schoolClass', 'section', 'room'])
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->orderBy('period_number')
            ->get();

        // Group by day for grid view
        $grouped = [];
        foreach ($days as $day) {
            $grouped[$day] = $entries->where('day', $day)->sortBy('period_number')->values();
        }

        // Determine max periods for grid rows
        $maxPeriods = $entries->max('period_number') ?: 8;

        $today = now()->format('l');

        return view('teacher::portal.timetable', compact(
            'teacher',
            'grouped',
            'days',
            'maxPeriods',
            'entries',
            'today'
        ));
    }
}
