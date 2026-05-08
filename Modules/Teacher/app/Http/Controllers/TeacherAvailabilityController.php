<?php

namespace Modules\Teacher\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Teacher\Models\Teacher;
use Modules\Timetable\Models\TeacherAvailability;

class TeacherAvailabilityController extends Controller
{
    public function index()
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();

        // This week's availability records (Mon–Sun)
        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd   = now()->endOfWeek()->toDateString();

        $records = TeacherAvailability::where('teacher_id', $teacher->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->orderBy('date')
            ->orderBy('period_number')
            ->get();

        return view('teacher::portal.availability', compact('teacher', 'records', 'weekStart', 'weekEnd'));
    }

    public function store(Request $request)
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();

        $data = $request->validate([
            'date'          => ['required', 'date'],
            'period_number' => ['nullable', 'integer', 'min:1', 'max:12'],
            'status'        => ['required', 'in:available,unavailable,busy'],
            'reason'        => ['nullable', 'string', 'max:500'],
        ]);

        $data['teacher_id'] = $teacher->id;

        // If no period_number, it's a full-day record (period_number = 0)
        $period = $data['period_number'] ?? 0;

        TeacherAvailability::updateOrCreate(
            [
                'teacher_id'    => $teacher->id,
                'date'          => $data['date'],
                'period_number' => $period,
            ],
            [
                'status' => $data['status'],
                'reason' => $data['reason'] ?? null,
            ]
        );

        return redirect()->back()->with('success', 'Availability updated successfully.');
    }
}
