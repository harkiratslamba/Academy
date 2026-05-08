<?php

namespace Modules\Teacher\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Teacher\Models\Teacher;
use Modules\Attendance\Models\Attendance;

class TeacherAttendanceController extends Controller
{
    public function index()
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();

        $month = now()->month;
        $year  = now()->year;

        $records = Attendance::where('teacher_id', $teacher->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get();

        // Summary counts
        $summary = [
            'present'  => $records->where('status', 'present')->count(),
            'absent'   => $records->where('status', 'absent')->count(),
            'late'     => $records->where('status', 'late')->count(),
            'on_leave' => $records->where('status', 'on_leave')->count(),
            'total'    => $records->count(),
        ];

        // Attendance percentage (present + late counted as attended)
        $attended   = $summary['present'] + $summary['late'];
        $percentage = $summary['total'] > 0
            ? round(($attended / $summary['total']) * 100, 1)
            : 0;

        $summary['percentage'] = $percentage;

        $monthName = now()->format('F Y');

        return view('teacher::portal.attendance', compact(
            'teacher', 'records', 'summary', 'monthName'
        ));
    }
}
