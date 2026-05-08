@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="fa fa-tachometer-alt me-2 text-primary"></i>Dashboard</h4>
        <small class="text-muted">{{ now()->format('l, d F Y') }}</small>
    </div>
    <a href="{{ route('admin.substitution.index') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-exchange-alt me-1"></i> Manage Substitutions
    </a>
</div>

{{-- ── Stat Cards ─────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#0d6efd,#4fa3ff);">
            <div class="stat-value">{{ $totalTeachers }}</div>
            <div class="stat-label">Active Teachers</div>
            <i class="fa fa-chalkboard-teacher stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#198754,#48c774);">
            <div class="stat-value">{{ $totalClasses }}</div>
            <div class="stat-label">Total Classes</div>
            <i class="fa fa-school stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#dc3545,#f77185);">
            <div class="stat-value">{{ $absentToday }}</div>
            <div class="stat-label">Absent Today</div>
            <i class="fa fa-user-times stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#fd7e14,#ffb84d);">
            <div class="stat-value">{{ $todaySubs }}</div>
            <div class="stat-label">Substitutions Today</div>
            <i class="fa fa-exchange-alt stat-icon"></i>
        </div>
    </div>
</div>

{{-- ── Quick Stats Row ──────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card text-center py-3">
            <div class="fs-3 fw-bold text-success">{{ $freeToday }}</div>
            <div class="text-muted small">Teachers Present Today</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card text-center py-3">
            <div class="fs-3 fw-bold text-warning">{{ $pendingLeaves }}</div>
            <div class="text-muted small">Pending Leave Requests</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card text-center py-3">
            <div class="fs-3 fw-bold {{ $unassignedClasses->count() > 0 ? 'text-danger' : 'text-success' }}">
                {{ $unassignedClasses->count() }}
            </div>
            <div class="text-muted small">Unassigned Classes Today</div>
        </div>
    </div>
</div>

{{-- ── Unassigned Classes Alert ─────────────────────────────────────── --}}
@if($unassignedClasses->count() > 0)
<div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-center">
        <i class="fa fa-exclamation-triangle fa-2x me-3 text-danger"></i>
        <div class="flex-grow-1">
            <strong>{{ $unassignedClasses->count() }} class(es) need a substitute today!</strong>
            <div class="mt-2">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 bg-white">
                        <thead class="table-danger">
                            <tr>
                                <th>Period</th>
                                <th>Time</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Absent Teacher</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unassignedClasses as $uc)
                            <tr>
                                <td>P{{ $uc->period_number }}</td>
                                <td>{{ substr($uc->start_time, 0, 5) }} – {{ substr($uc->end_time, 0, 5) }}</td>
                                <td>{{ $uc->class_name }}{{ $uc->section_name ? ' – '.$uc->section_name : '' }}</td>
                                <td>{{ $uc->subject_name }}</td>
                                <td><span class="badge bg-danger">{{ $uc->teacher_name }}</span></td>
                                <td>
                                    <a href="{{ route('admin.substitution.index', ['date' => $today]) }}"
                                       class="btn btn-xs btn-warning btn-sm py-0 px-2">Assign</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-4">
    {{-- ── Today's Timetable ────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-calendar-day me-2 text-primary"></i>Today's Timetable
                    <span class="badge bg-secondary ms-1">{{ $todayDay }}</span>
                </span>
                <a href="{{ route('admin.timetable.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if($todayTimetable->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-calendar-times fa-2x mb-2 d-block"></i>No timetable entries for today.
                    </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>P#</th>
                                <th>Time</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todayTimetable as $tt)
                            <tr class="{{ $tt->teacher && $tt->teacher->isAbsentOn($today) ? 'table-danger' : '' }}">
                                <td><span class="badge bg-light text-dark border">P{{ $tt->period_number }}</span></td>
                                <td class="text-nowrap small">{{ substr($tt->start_time, 0, 5) }}–{{ substr($tt->end_time, 0, 5) }}</td>
                                <td>{{ $tt->schoolClass->class_name ?? '—' }}
                                    @if($tt->section)<small class="text-muted">–{{ $tt->section->section_name }}</small>@endif
                                </td>
                                <td>{{ $tt->subject->subject_name ?? '—' }}</td>
                                <td>
                                    {{ $tt->teacher->name ?? '—' }}
                                    @if($tt->teacher && $tt->teacher->isAbsentOn($today))
                                        <span class="badge bg-danger ms-1">Absent</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Recent Substitutions ─────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-exchange-alt me-2 text-warning"></i>Recent Substitutions</span>
                <a href="{{ route('admin.substitution.index') }}" class="btn btn-sm btn-outline-warning">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentSubs->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-inbox fa-2x mb-2 d-block"></i>No substitutions yet.
                    </div>
                @else
                <ul class="list-group list-group-flush">
                    @foreach($recentSubs as $sub)
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold small">
                                    {{ $sub->timetable->schoolClass->class_name ?? '—' }}
                                    — {{ $sub->timetable->subject->subject_name ?? '—' }}
                                </div>
                                <div class="text-muted" style="font-size:0.78rem">
                                    <i class="fa fa-arrow-right text-danger"></i>
                                    {{ $sub->originalTeacher->name ?? '—' }}
                                    <i class="fa fa-arrow-right text-success mx-1"></i>
                                    {{ $sub->substituteTeacher->name ?? '—' }}
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge
                                    @if($sub->status === 'assigned') bg-success
                                    @elseif($sub->status === 'cancelled') bg-secondary
                                    @elseif($sub->status === 'completed') bg-primary
                                    @else bg-warning text-dark @endif">
                                    {{ ucfirst($sub->status) }}
                                </span>
                                <div class="text-muted" style="font-size:0.72rem">{{ $sub->date }}</div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
