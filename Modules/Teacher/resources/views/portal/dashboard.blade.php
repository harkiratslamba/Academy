@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="fa fa-home me-2 text-primary"></i>My Dashboard</h4>
        <p class="text-muted mb-0">{{ now()->format('l, d F Y') }}</p>
    </div>
    <span class="badge bg-{{ $attendanceToday ? ($attendanceToday->status === 'present' ? 'success' : ($attendanceToday->status === 'absent' ? 'danger' : 'warning')) : 'secondary' }} fs-6 px-3 py-2">
        <i class="fa fa-circle me-1"></i>
        Today: {{ $attendanceToday ? ucfirst(str_replace('_', ' ', $attendanceToday->status)) : 'Not Marked' }}
    </span>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card bg-primary">
            <i class="fa fa-clock stat-icon"></i>
            <div class="stat-value">{{ $todaySchedule->count() }}</div>
            <div class="stat-label">Periods Today</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-warning">
            <i class="fa fa-exchange-alt stat-icon"></i>
            <div class="stat-value">{{ $subClasses->count() }}</div>
            <div class="stat-label">Sub Classes Today</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-success">
            <i class="fa fa-calendar-week stat-icon"></i>
            <div class="stat-value">{{ $weeklyPeriods }}</div>
            <div class="stat-label">Weekly Periods</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-danger">
            <i class="fa fa-calendar-times stat-icon"></i>
            <div class="stat-value">{{ $pendingLeavesCount }}</div>
            <div class="stat-label">Pending Leaves</div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Today's Schedule --}}
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-calendar-day me-2 text-primary"></i>Today's Schedule</span>
                <span class="badge bg-primary">{{ now()->format('l') }}</span>
            </div>
            <div class="card-body p-0">
                @forelse($todaySchedule as $entry)
                    <div class="d-flex align-items-center border-bottom px-3 py-2">
                        <div class="text-center me-3" style="min-width:48px">
                            <span class="badge bg-primary rounded-pill fs-6">{{ $entry->period_number }}</span>
                            <div class="text-muted" style="font-size:0.72rem">Period</div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $entry->subject->name ?? '—' }}</div>
                            <div class="text-muted small">
                                {{ $entry->schoolClass->class_name ?? '' }}
                                {{ $entry->section->section_name ?? '' }}
                                @if($entry->room)
                                    &mdash; <i class="fa fa-door-open me-1"></i>{{ $entry->room->room_name }}
                                @endif
                            </div>
                        </div>
                        <div class="text-end">
                            @if($entry->start_time && $entry->end_time)
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($entry->start_time)->format('h:i A') }}
                                    – {{ \Carbon\Carbon::parse($entry->end_time)->format('h:i A') }}
                                </small>
                            @endif
                            <span class="badge bg-success ms-2">Regular</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-calendar-check fa-2x mb-2 d-block opacity-25"></i>
                        No classes scheduled for today.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Extra Duties (Substitute Classes) --}}
        @if($subClasses->isNotEmpty())
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-exchange-alt me-2 text-warning"></i>Extra Duties Today</span>
                <span class="badge bg-warning text-dark">{{ $subClasses->count() }}</span>
            </div>
            <div class="card-body p-0">
                @foreach($subClasses as $sub)
                    <div class="d-flex align-items-center border-bottom px-3 py-2">
                        <div class="text-center me-3" style="min-width:48px">
                            <span class="badge bg-warning text-dark rounded-pill fs-6">
                                {{ $sub->timetable->period_number ?? '?' }}
                            </span>
                            <div class="text-muted" style="font-size:0.72rem">Period</div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">
                                {{ $sub->timetable->subject->name ?? '—' }}
                                <span class="badge bg-warning text-dark ms-1">Sub</span>
                            </div>
                            <div class="text-muted small">
                                {{ $sub->timetable->schoolClass->class_name ?? '' }}
                                {{ $sub->timetable->section->section_name ?? '' }}
                                &mdash; For: {{ $sub->originalTeacher->name ?? 'Unknown' }}
                            </div>
                        </div>
                        @if($sub->timetable && $sub->timetable->start_time)
                            <small class="text-muted">
                                {{ \Carbon\Carbon::parse($sub->timetable->start_time)->format('h:i A') }}
                                – {{ \Carbon\Carbon::parse($sub->timetable->end_time)->format('h:i A') }}
                            </small>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar: Profile + Quick Actions --}}
    <div class="col-lg-4">
        {{-- Profile Card --}}
        <div class="card mb-3">
            <div class="card-body text-center">
                <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:72px;height:72px;">
                    <i class="fa fa-user-tie fa-2x text-white"></i>
                </div>
                <h5 class="mb-1">{{ $teacher->name }}</h5>
                <p class="text-muted small mb-1">{{ $teacher->employee_id }}</p>
                <p class="text-muted small mb-2">{{ $teacher->subject_specialization }}</p>
                <span class="badge bg-{{ $teacher->status === 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($teacher->status) }}
                </span>
                @if($teacher->email)
                    <div class="mt-2 small text-muted"><i class="fa fa-envelope me-1"></i>{{ $teacher->email }}</div>
                @endif
                @if($teacher->phone)
                    <div class="small text-muted"><i class="fa fa-phone me-1"></i>{{ $teacher->phone }}</div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-header"><i class="fa fa-bolt me-2 text-warning"></i>Quick Actions</div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('teacher.timetable') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-calendar-alt me-1"></i> View Full Timetable
                </a>
                <a href="{{ route('teacher.leaves.index') }}" class="btn btn-outline-danger btn-sm">
                    <i class="fa fa-calendar-times me-1"></i> Apply for Leave
                </a>
                <a href="{{ route('teacher.availability') }}" class="btn btn-outline-info btn-sm">
                    <i class="fa fa-clock me-1"></i> Set Availability
                </a>
                <a href="{{ route('teacher.attendance') }}" class="btn btn-outline-success btn-sm">
                    <i class="fa fa-user-check me-1"></i> My Attendance
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
