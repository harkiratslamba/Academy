@extends('layouts.app')

@section('title', 'My Attendance')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="fa fa-user-check me-2 text-primary"></i>My Attendance</h4>
        <p class="text-muted mb-0">{{ $monthName }} — {{ $teacher->name }}</p>
    </div>
</div>

{{-- Summary Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card bg-success">
            <i class="fa fa-check-circle stat-icon"></i>
            <div class="stat-value">{{ $summary['present'] }}</div>
            <div class="stat-label">Present</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-danger">
            <i class="fa fa-times-circle stat-icon"></i>
            <div class="stat-value">{{ $summary['absent'] }}</div>
            <div class="stat-label">Absent</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-warning">
            <i class="fa fa-clock stat-icon"></i>
            <div class="stat-value">{{ $summary['late'] }}</div>
            <div class="stat-label">Late</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-info">
            <i class="fa fa-calendar-minus stat-icon"></i>
            <div class="stat-value">{{ $summary['on_leave'] }}</div>
            <div class="stat-label">On Leave</div>
        </div>
    </div>
</div>

{{-- Attendance Percentage Progress Bar --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold">Attendance Rate</span>
            <span class="fw-bold text-{{ $summary['percentage'] >= 75 ? 'success' : ($summary['percentage'] >= 50 ? 'warning' : 'danger') }}">
                {{ $summary['percentage'] }}%
            </span>
        </div>
        <div class="progress mb-2" style="height: 12px;">
            @php
                $barColor = $summary['percentage'] >= 75 ? 'success' : ($summary['percentage'] >= 50 ? 'warning' : 'danger');
            @endphp
            <div class="progress-bar bg-{{ $barColor }}"
                 role="progressbar"
                 style="width: {{ $summary['percentage'] }}%"
                 aria-valuenow="{{ $summary['percentage'] }}"
                 aria-valuemin="0"
                 aria-valuemax="100">
            </div>
        </div>
        <div class="d-flex justify-content-between small text-muted">
            <span>Total working days recorded: <strong>{{ $summary['total'] }}</strong></span>
            <span>
                Attended (present + late): <strong>{{ $summary['present'] + $summary['late'] }}</strong>
            </span>
        </div>
        @if($summary['percentage'] < 75)
            <div class="alert alert-warning mt-2 py-1 px-2 mb-0 small">
                <i class="fa fa-exclamation-triangle me-1"></i>
                Attendance below 75%. Please ensure regular attendance.
            </div>
        @endif
    </div>
</div>

{{-- Monthly Records DataTable --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa fa-table me-2 text-primary"></i>Monthly Records — {{ $monthName }}</span>
        <span class="badge bg-secondary">{{ $summary['total'] }} day(s) recorded</span>
    </div>
    <div class="card-body p-0">
        @if($records->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa fa-calendar fa-2x mb-2 d-block opacity-25"></i>
                No attendance records found for {{ $monthName }}.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0" data-datatable>
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Status</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $i => $record)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($record->date)->format('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($record->date)->format('l') }}</td>
                                <td>
                                    @php
                                        $statusMap = [
                                            'present'  => ['color' => 'success',   'icon' => 'fa-check-circle'],
                                            'absent'   => ['color' => 'danger',    'icon' => 'fa-times-circle'],
                                            'late'     => ['color' => 'warning',   'icon' => 'fa-clock'],
                                            'on_leave' => ['color' => 'info',      'icon' => 'fa-calendar-minus'],
                                        ];
                                        $sm = $statusMap[$record->status] ?? ['color' => 'secondary', 'icon' => 'fa-circle'];
                                    @endphp
                                    <span class="badge bg-{{ $sm['color'] }}">
                                        <i class="fa {{ $sm['icon'] }} me-1"></i>
                                        {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                    </span>
                                </td>
                                <td class="text-muted">
                                    @if(!empty($record->check_in))
                                        {{ \Carbon\Carbon::parse($record->check_in)->format('h:i A') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted">
                                    @if(!empty($record->check_out))
                                        {{ \Carbon\Carbon::parse($record->check_out)->format('h:i A') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $record->remarks ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
