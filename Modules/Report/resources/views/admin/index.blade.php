@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="fa fa-chart-bar me-2 text-primary"></i>Monthly Reports</h4>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
        <i class="fa fa-print me-1"></i> Print
    </button>
</div>

{{-- ── Month Picker ────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.reports') }}" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small fw-semibold mb-1">Month</label>
                <input type="month" name="month" class="form-control form-control-sm" value="{{ $month }}">
            </div>
            <div class="col-sm-3">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa fa-search me-1"></i> Load Report
                </button>
            </div>
            <div class="col-sm-5 text-end">
                <span class="badge bg-light text-dark border fs-6">
                    {{ \Carbon\Carbon::parse($startDate)->format('F Y') }}
                    &nbsp;({{ $totalDays }} working days)
                </span>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- 1. Attendance Summary ─────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header">
        <i class="fa fa-user-check me-2 text-success"></i>Attendance Summary
        <span class="text-muted small ms-2">{{ \Carbon\Carbon::parse($startDate)->format('F Y') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable='{"pageLength":20}'>
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Teacher</th>
                        <th class="text-center text-success">Present</th>
                        <th class="text-center text-danger">Absent</th>
                        <th class="text-center text-warning">Late</th>
                        <th class="text-center text-info">On Leave</th>
                        <th class="text-center">Marked Days</th>
                        <th style="min-width:200px">Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendanceSummary as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $row->teacher->name }}</td>
                        <td class="text-center">
                            <span class="badge bg-success">{{ $row->present_days }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger">{{ $row->absent_days }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark">{{ $row->late_days }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info text-dark">{{ $row->leave_days }}</span>
                        </td>
                        <td class="text-center">{{ $row->marked_days }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-grow-1">
                                    <div class="progress" style="height:10px">
                                        <div class="progress-bar
                                            @if($row->attendance_pct >= 90) bg-success
                                            @elseif($row->attendance_pct >= 75) bg-warning
                                            @else bg-danger @endif"
                                            style="width:{{ $row->attendance_pct }}%">
                                        </div>
                                    </div>
                                </div>
                                <span class="small fw-semibold" style="width:40px">{{ $row->attendance_pct }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No attendance data for this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- 2. Substitution Summary ───────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header">
        <i class="fa fa-exchange-alt me-2 text-warning"></i>Substitution Summary
    </div>
    <div class="card-body p-0">
        @if($substitutionSummary->isEmpty())
        <div class="text-center text-muted py-4">No substitution activity this month.</div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable='{"pageLength":20}'>
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Teacher</th>
                        <th class="text-center">Times Absent (Covered)</th>
                        <th class="text-center">Times as Substitute</th>
                        <th>Net Contribution</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($substitutionSummary as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $row->teacher->name }}</td>
                        <td class="text-center">
                            <span class="badge bg-danger">{{ $row->times_absent }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success">{{ $row->times_subbed }}</span>
                        </td>
                        <td>
                            @php $net = $row->times_subbed - $row->times_absent; @endphp
                            <span class="badge {{ $net >= 0 ? 'bg-success' : 'bg-danger' }}">
                                {{ $net >= 0 ? '+' : '' }}{{ $net }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- 3. Leave Summary ─────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header">
        <i class="fa fa-calendar-times me-2 text-info"></i>Leave Summary
    </div>
    <div class="card-body p-0">
        @if($leaveSummary->isEmpty())
        <div class="text-center text-muted py-4">No leave applications this month.</div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable='{"pageLength":20}'>
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Teacher</th>
                        <th class="text-center">Approved Leaves</th>
                        <th class="text-center">Pending</th>
                        <th class="text-center">Total Days Off</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leaveSummary as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $row->teacher->name }}</td>
                        <td class="text-center">
                            <span class="badge bg-success">{{ $row->approved_count }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark">{{ $row->pending_count }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ $row->total_days }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- 4. Timetable Coverage ─────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header">
        <i class="fa fa-calendar-alt me-2 text-primary"></i>Timetable Coverage
    </div>
    <div class="card-body p-0">
        @if($timetableCoverage->isEmpty())
        <div class="text-center text-muted py-4">No timetable data found.</div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable='{"pageLength":20}'>
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Class</th>
                        <th class="text-center">Scheduled Periods/Day</th>
                        <th class="text-center">Substitutions Assigned This Month</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($timetableCoverage as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $row->class_name }}</td>
                        <td class="text-center">
                            <span class="badge bg-info text-dark">{{ $row->scheduled_periods }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $row->subs_assigned > 0 ? 'bg-warning text-dark' : 'bg-success' }}">
                                {{ $row->subs_assigned }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        #sidebar, .top-navbar, .card-header .btn, form, .btn { display: none !important; }
        #main-content { margin-left: 0 !important; margin-top: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    }
</style>
@endpush
