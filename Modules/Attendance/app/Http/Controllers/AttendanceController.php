<?php

namespace Modules\Attendance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Attendance\Models\Attendance;
use Modules\Teacher\Models\Teacher;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,coordinator']);
    }

    public function adminIndex(Request $request)
    {
        $date     = $request->get('date', today()->toDateString());
        $teachers = Teacher::with(['attendances' => function ($q) use ($date) {
            $q->where('date', $date);
        }])
        ->where('status', 'active')
        ->orderBy('name')
        ->get();

        // Summary counts
        $summary = [
            'present'    => 0,
            'absent'     => 0,
            'late'       => 0,
            'on_leave'   => 0,
            'not_marked' => 0,
        ];

        foreach ($teachers as $teacher) {
            $att = $teacher->attendances->first();
            if (!$att) {
                $summary['not_marked']++;
            } else {
                $summary[$att->status] = ($summary[$att->status] ?? 0) + 1;
            }
        }

        return view('attendance::admin.index', compact('teachers', 'date', 'summary'));
    }

    public function markBulk(Request $request)
    {
        $data = $request->validate([
            'date'         => 'required|date',
            'attendance'   => 'required|array',
            'attendance.*' => 'required|in:present,absent,late,on_leave',
        ]);

        $date    = $data['date'];
        $markedBy = auth()->id();

        foreach ($data['attendance'] as $teacherId => $status) {
            Attendance::updateOrCreate(
                ['teacher_id' => (int) $teacherId, 'date' => $date],
                ['status' => $status, 'marked_by' => $markedBy]
            );
        }

        return redirect()->back()->with('success', 'Attendance marked successfully for ' . \Carbon\Carbon::parse($date)->format('d M Y') . '.');
    }
}
