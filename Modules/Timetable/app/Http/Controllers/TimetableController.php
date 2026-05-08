<?php

namespace Modules\Timetable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Timetable\Models\Timetable;
use Modules\Classes\Models\SchoolClass;
use Modules\Classes\Models\Section;
use Modules\Classes\Models\Room;
use Modules\Subject\Models\Subject;
use Modules\Teacher\Models\Teacher;

class TimetableController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,coordinator']);
    }

    public function index(Request $request)
    {
        $query = Timetable::with(['schoolClass', 'section', 'subject', 'teacher', 'room'])
            ->orderBy('day')
            ->orderBy('period_number');

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('day')) {
            $query->where('day', $request->day);
        }

        $timetables = $query->get();
        $classes    = SchoolClass::orderBy('class_name')->get();
        $sections   = Section::with('schoolClass')->orderBy('class_id')->get();
        $subjects   = Subject::orderBy('subject_name')->get();
        $teachers   = Teacher::where('status', 'active')->orderBy('name')->get();
        $rooms      = Room::orderBy('room_number')->get();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return view('timetable::admin.index', compact(
            'timetables', 'classes', 'sections', 'subjects', 'teachers', 'rooms', 'days'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id'      => 'required|exists:classes,id',
            'section_id'    => 'nullable|exists:sections,id',
            'subject_id'    => 'required|exists:subjects,id',
            'teacher_id'    => 'required|exists:teachers,id',
            'room_id'       => 'nullable|exists:rooms,id',
            'day'           => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'period_number' => 'required|integer|min:1|max:10',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
        ]);

        // Check teacher clash
        $teacherClash = Timetable::where('teacher_id', $data['teacher_id'])
            ->where('day', $data['day'])
            ->where('period_number', $data['period_number'])
            ->exists();

        if ($teacherClash) {
            return redirect()->back()
                ->with('error', 'Teacher already has a class at this period/day.')
                ->withInput();
        }

        // Check class/section clash
        $classClash = Timetable::where('class_id', $data['class_id'])
            ->where('section_id', $data['section_id'])
            ->where('day', $data['day'])
            ->where('period_number', $data['period_number'])
            ->exists();

        if ($classClash) {
            return redirect()->back()
                ->with('error', 'This class/section already has a period at this slot.')
                ->withInput();
        }

        $data['is_active'] = true;
        Timetable::create($data);

        return redirect()->back()->with('success', 'Timetable entry added successfully.');
    }

    public function destroy($id)
    {
        $entry = Timetable::findOrFail($id);
        $entry->delete();
        return redirect()->back()->with('success', 'Timetable entry removed.');
    }

    public function toggle($id)
    {
        $entry = Timetable::findOrFail($id);
        $entry->update(['is_active' => !$entry->is_active]);
        $state = $entry->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Timetable entry {$state}.");
    }
}
