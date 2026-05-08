<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Teacher\Models\Teacher;
use Modules\Classes\Models\SchoolClass;
use Modules\Substitution\Models\Substitution;
use Modules\Attendance\Models\Attendance;
use Modules\Leave\Models\Leave;
use Modules\Timetable\Models\Timetable;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,coordinator']);
    }

    public function index()
    {
        $today     = today()->toDateString();
        $todayDay  = today()->format('l'); // e.g. "Monday"

        // ── Stat counts ─────────────────────────────────────────────
        $totalTeachers = Teacher::where('status', 'active')->count();
        $totalClasses  = SchoolClass::count();

        $absentToday = Attendance::where('date', $today)
            ->whereIn('status', ['absent', 'on_leave'])
            ->count();

        $todaySubs = Substitution::where('date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        // Active teachers not absent today
        $freeToday = Teacher::where('status', 'active')
            ->whereDoesntHave('attendances', function ($q) use ($today) {
                $q->where('date', $today)
                  ->whereIn('status', ['absent', 'on_leave']);
            })
            ->count();

        $pendingLeaves = Leave::where('status', 'pending')->count();

        // ── Unassigned classes ───────────────────────────────────────
        // Timetable entries today where teacher is absent and no sub assigned
        $unassignedClasses = DB::table('timetables as t')
            ->select(
                't.id as timetable_id',
                't.period_number',
                't.start_time',
                't.end_time',
                't.day',
                'c.class_name',
                'sec.section_name',
                'sub.subject_name',
                'tea.name as teacher_name',
                'a.status as attendance_status'
            )
            ->join('classes as c',   't.class_id',   '=', 'c.id')
            ->leftJoin('sections as sec', 't.section_id', '=', 'sec.id')
            ->join('subjects as sub', 't.subject_id', '=', 'sub.id')
            ->join('teachers as tea', 't.teacher_id', '=', 'tea.id')
            ->join('attendances as a', function ($j) use ($today) {
                $j->on('a.teacher_id', '=', 't.teacher_id')
                  ->where('a.date', $today)
                  ->whereIn('a.status', ['absent', 'on_leave']);
            })
            ->where('t.day', $todayDay)
            ->where('t.is_active', true)
            ->whereNotExists(function ($q) use ($today) {
                $q->from('substitutions as s')
                  ->whereColumn('s.timetable_id', 't.id')
                  ->where('s.date', $today)
                  ->whereNotIn('s.status', ['cancelled']);
            })
            ->orderBy('t.period_number')
            ->get();

        // ── Today's full timetable ───────────────────────────────────
        $todayTimetable = Timetable::with(['schoolClass', 'section', 'subject', 'teacher', 'room'])
            ->where('day', $todayDay)
            ->where('is_active', true)
            ->orderBy('period_number')
            ->get();

        // ── Recent substitutions ─────────────────────────────────────
        $recentSubs = Substitution::with(['originalTeacher', 'substituteTeacher', 'timetable.schoolClass', 'timetable.subject'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('dashboard::admin', compact(
            'totalTeachers',
            'totalClasses',
            'absentToday',
            'todaySubs',
            'freeToday',
            'pendingLeaves',
            'unassignedClasses',
            'todayTimetable',
            'recentSubs',
            'today',
            'todayDay'
        ));
    }
}
