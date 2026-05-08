<?php

namespace Modules\Report\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Teacher\Models\Teacher;
use Modules\Attendance\Models\Attendance;
use Modules\Substitution\Models\Substitution;
use Modules\Leave\Models\Leave;
use Modules\Timetable\Models\Timetable;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,coordinator']);
    }

    public function index(Request $request)
    {
        $month = $request->get('month', today()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $startDate = "{$year}-{$mon}-01";
        $endDate   = date('Y-m-t', strtotime($startDate));
        $totalDays = (int) date('t', strtotime($startDate));

        $teachers = Teacher::where('status', 'active')->orderBy('name')->get();

        // ── Attendance Summary ────────────────────────────────────────
        $attendanceData = DB::table('attendances')
            ->selectRaw('teacher_id,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = "absent"  THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = "late"    THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = "on_leave" THEN 1 ELSE 0 END) as leave_days,
                COUNT(*) as marked_days')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('teacher_id')
            ->get()
            ->keyBy('teacher_id');

        $attendanceSummary = $teachers->map(function ($teacher) use ($attendanceData, $totalDays) {
            $row         = $attendanceData->get($teacher->id);
            $presentDays = $row ? (int) $row->present_days : 0;
            $absentDays  = $row ? (int) $row->absent_days  : 0;
            $lateDays    = $row ? (int) $row->late_days    : 0;
            $leaveDays   = $row ? (int) $row->leave_days   : 0;
            $markedDays  = $row ? (int) $row->marked_days  : 0;
            $attendancePct = $markedDays > 0 ? round(($presentDays / $markedDays) * 100) : 0;

            return (object) [
                'teacher'        => $teacher,
                'present_days'   => $presentDays,
                'absent_days'    => $absentDays,
                'late_days'      => $lateDays,
                'leave_days'     => $leaveDays,
                'marked_days'    => $markedDays,
                'attendance_pct' => $attendancePct,
            ];
        });

        // ── Substitution Summary ──────────────────────────────────────
        $subAsOriginal = DB::table('substitutions')
            ->selectRaw('original_teacher_id, COUNT(*) as times_absent')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('original_teacher_id')
            ->get()->keyBy('original_teacher_id');

        $subAsSubstitute = DB::table('substitutions')
            ->selectRaw('substitute_teacher_id, COUNT(*) as times_subbed')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('substitute_teacher_id')
            ->get()->keyBy('substitute_teacher_id');

        $substitutionSummary = $teachers->map(function ($teacher) use ($subAsOriginal, $subAsSubstitute) {
            return (object) [
                'teacher'      => $teacher,
                'times_absent' => $subAsOriginal->has($teacher->id)   ? (int)$subAsOriginal->get($teacher->id)->times_absent  : 0,
                'times_subbed' => $subAsSubstitute->has($teacher->id) ? (int)$subAsSubstitute->get($teacher->id)->times_subbed : 0,
            ];
        })->filter(fn($r) => $r->times_absent > 0 || $r->times_subbed > 0)->values();

        // ── Leave Summary ─────────────────────────────────────────────
        $leaveSummary = Leave::with('teacher')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('from_date', [$startDate, $endDate])
                  ->orWhereBetween('to_date', [$startDate, $endDate]);
            })
            ->whereIn('status', ['approved', 'pending'])
            ->get()
            ->groupBy('teacher_id')
            ->map(function ($leaves) {
                return (object) [
                    'teacher'        => $leaves->first()->teacher,
                    'approved_count' => $leaves->where('status', 'approved')->count(),
                    'pending_count'  => $leaves->where('status', 'pending')->count(),
                    'total_days'     => $leaves->where('status', 'approved')->sum('days'),
                ];
            })->values();

        // ── Timetable Coverage ────────────────────────────────────────
        // Per class: total periods in schedule vs substitutions assigned
        $timetableCoverage = DB::table('timetables as t')
            ->select('c.class_name',
                DB::raw('COUNT(DISTINCT CONCAT(t.day, "-", t.period_number)) as scheduled_periods'),
                DB::raw('(SELECT COUNT(*) FROM substitutions s2
                          JOIN timetables t2 ON s2.timetable_id = t2.id
                          WHERE t2.class_id = t.class_id
                            AND s2.date BETWEEN "' . $startDate . '" AND "' . $endDate . '"
                            AND s2.status != "cancelled") as subs_assigned')
            )
            ->join('classes as c', 't.class_id', '=', 'c.id')
            ->where('t.is_active', true)
            ->groupBy('t.class_id', 'c.class_name')
            ->orderBy('c.class_name')
            ->get();

        return view('report::admin.index', compact(
            'month', 'startDate', 'endDate', 'totalDays',
            'attendanceSummary', 'substitutionSummary', 'leaveSummary', 'timetableCoverage'
        ));
    }
}
