<?php

namespace Modules\Substitution\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Substitution\Models\Substitution;
use Modules\Attendance\Models\Attendance;
use Modules\Teacher\Models\Teacher;
use Modules\Timetable\Models\Timetable;

class SubstitutionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,coordinator']);
    }

    public function index(Request $request)
    {
        $date    = $request->get('date', today()->toDateString());
        $dayName = \Carbon\Carbon::parse($date)->format('l'); // e.g. "Monday"

        // Teachers marked absent on $date
        $absentTeachers = Attendance::with('teacher')
            ->where('date', $date)
            ->whereIn('status', ['absent', 'on_leave'])
            ->get();

        $absentTeacherIds = $absentTeachers->pluck('teacher_id')->toArray();

        // Timetable entries on this day where teacher is absent and no sub assigned
        $unassignedClasses = DB::table('timetables as t')
            ->select(
                't.id as timetable_id',
                't.period_number',
                't.start_time',
                't.end_time',
                'c.class_name',
                'sec.section_name',
                'sub.subject_name',
                'sub.id as subject_id',
                'tea.name as teacher_name',
                'tea.id as teacher_id'
            )
            ->join('classes as c',   't.class_id',   '=', 'c.id')
            ->leftJoin('sections as sec', 't.section_id', '=', 'sec.id')
            ->join('subjects as sub', 't.subject_id', '=', 'sub.id')
            ->join('teachers as tea', 't.teacher_id', '=', 'tea.id')
            ->where('t.day', $dayName)
            ->where('t.is_active', true)
            ->whereIn('t.teacher_id', $absentTeacherIds)
            ->whereNotExists(function ($q) use ($date) {
                $q->from('substitutions as s')
                  ->whereColumn('s.timetable_id', 't.id')
                  ->where('s.date', $date)
                  ->whereNotIn('s.status', ['cancelled']);
            })
            ->orderBy('t.period_number')
            ->get();

        // Today's substitution records
        $todaySubs = Substitution::with([
            'originalTeacher',
            'substituteTeacher',
            'timetable.schoolClass',
            'timetable.section',
            'timetable.subject',
        ])
            ->where('date', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        // All active teachers (for mark-absent dropdown)
        $teachers = Teacher::where('status', 'active')->orderBy('name')->get();

        return view('substitution::admin.index', compact(
            'date', 'dayName', 'absentTeachers', 'unassignedClasses', 'todaySubs', 'teachers'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'timetable_id'          => 'required|exists:timetables,id',
            'substitute_teacher_id' => 'required|exists:teachers,id',
            'date'                  => 'required|date',
            'notes'                 => 'nullable|string|max:500',
        ]);

        $timetable = Timetable::findOrFail($data['timetable_id']);
        $date      = $data['date'];
        $subId     = $data['substitute_teacher_id'];
        $dayName   = \Carbon\Carbon::parse($date)->format('l');

        // Verify substitute is not absent
        $isAbsent = Attendance::where('teacher_id', $subId)
            ->where('date', $date)
            ->whereIn('status', ['absent', 'on_leave'])
            ->exists();

        if ($isAbsent) {
            return redirect()->back()->with('error', 'Selected teacher is also absent on this date.');
        }

        // Verify substitute has no own timetable period at this slot
        $isBusy = Timetable::where('teacher_id', $subId)
            ->where('day', $dayName)
            ->where('period_number', $timetable->period_number)
            ->where('is_active', true)
            ->exists();

        if ($isBusy) {
            return redirect()->back()->with('error', 'Selected teacher already has a class at this period.');
        }

        // Verify not already assigned as sub for this period
        $alreadySub = Substitution::where('substitute_teacher_id', $subId)
            ->where('date', $date)
            ->whereHas('timetable', function ($q) use ($timetable) {
                $q->where('period_number', $timetable->period_number)
                  ->where('day', $timetable->day);
            })
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($alreadySub) {
            return redirect()->back()->with('error', 'This teacher is already assigned as a substitute for this period.');
        }

        Substitution::create([
            'original_teacher_id'   => $timetable->teacher_id,
            'substitute_teacher_id' => $subId,
            'timetable_id'          => $timetable->id,
            'date'                  => $date,
            'status'                => 'assigned',
            'assigned_by'           => auth()->id(),
            'notes'                 => $data['notes'] ?? null,
        ]);

        // Flash session notification for the substitute teacher
        $subTeacher = Teacher::find($subId);
        session()->flash('success', "Substitution assigned to {$subTeacher->name} successfully.");

        return redirect()->route('admin.substitution.index', ['date' => $date]);
    }

    public function destroy($id)
    {
        $sub = Substitution::findOrFail($id);
        $sub->update(['status' => 'cancelled']);
        return redirect()->back()->with('success', 'Substitution cancelled.');
    }

    public function markAbsent(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'date'       => 'required|date',
            'status'     => 'required|in:absent,on_leave',
            'notes'      => 'nullable|string|max:500',
        ]);

        Attendance::updateOrCreate(
            ['teacher_id' => $data['teacher_id'], 'date' => $data['date']],
            [
                'status'    => $data['status'],
                'notes'     => $data['notes'] ?? null,
                'marked_by' => auth()->id(),
            ]
        );

        $teacher = Teacher::find($data['teacher_id']);
        return redirect()->back()->with('success', "{$teacher->name} marked as {$data['status']} on {$data['date']}.");
    }

    public function availableTeachers(Request $request)
    {
        $request->validate([
            'timetable_id' => 'required|exists:timetables,id',
            'date'         => 'required|date',
        ]);

        $timetable = Timetable::with('subject')->findOrFail($request->timetable_id);
        $date      = $request->date;
        $dayName   = \Carbon\Carbon::parse($date)->format('l');

        // Get absent teacher IDs on this date
        $absentIds = Attendance::where('date', $date)
            ->whereIn('status', ['absent', 'on_leave'])
            ->pluck('teacher_id')
            ->toArray();

        // Get already-busy teacher IDs (own timetable at this period/day)
        $busyIds = Timetable::where('day', $dayName)
            ->where('period_number', $timetable->period_number)
            ->where('is_active', true)
            ->pluck('teacher_id')
            ->toArray();

        // Get teacher IDs already assigned as sub for this period/date
        $alreadySubIds = Substitution::where('date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('timetable', function ($q) use ($timetable, $dayName) {
                $q->where('period_number', $timetable->period_number)
                  ->where('day', $dayName);
            })
            ->pluck('substitute_teacher_id')
            ->toArray();

        $excludeIds = array_unique(array_merge($absentIds, $busyIds, $alreadySubIds));

        // Count substitutions per teacher this month (workload)
        $workloadMap = Substitution::where('date', 'like', substr($date, 0, 7) . '%')
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('substitute_teacher_id, COUNT(*) as sub_count')
            ->groupBy('substitute_teacher_id')
            ->pluck('sub_count', 'substitute_teacher_id')
            ->toArray();

        $teachers = Teacher::where('status', 'active')
            ->whereNotIn('id', $excludeIds)
            ->orderBy('name')
            ->get()
            ->map(function ($t) use ($workloadMap, $timetable) {
                $sameSubject = $t->subject_specialization &&
                    str_contains(
                        strtolower($t->subject_specialization),
                        strtolower($timetable->subject->subject_name ?? '')
                    );
                return [
                    'id'           => $t->id,
                    'name'         => $t->name,
                    'employee_id'  => $t->employee_id,
                    'specialization' => $t->subject_specialization,
                    'same_subject' => $sameSubject,
                    'workload'     => $workloadMap[$t->id] ?? 0,
                ];
            })
            ->sortByDesc('same_subject')
            ->sortBy('workload')
            ->values();

        return response()->json(['teachers' => $teachers]);
    }
}
