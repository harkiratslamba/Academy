@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="fa fa-user-check me-2 text-primary"></i>Teacher Attendance</h4>
</div>

{{-- ── Date Picker ─────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.attendance.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small fw-semibold mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}">
            </div>
            <div class="col-sm-4">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa fa-search me-1"></i> Load
                </button>
            </div>
            <div class="col-sm-4 text-end">
                <span class="badge bg-light text-dark border fs-6">
                    {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
                </span>
            </div>
        </form>
    </div>
</div>

{{-- ── Summary Badges ──────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-auto">
        <span class="badge bg-success fs-6 px-3 py-2">
            <i class="fa fa-check me-1"></i> Present: {{ $summary['present'] }}
        </span>
    </div>
    <div class="col-auto">
        <span class="badge bg-danger fs-6 px-3 py-2">
            <i class="fa fa-times me-1"></i> Absent: {{ $summary['absent'] }}
        </span>
    </div>
    <div class="col-auto">
        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
            <i class="fa fa-clock me-1"></i> Late: {{ $summary['late'] }}
        </span>
    </div>
    <div class="col-auto">
        <span class="badge bg-info text-dark fs-6 px-3 py-2">
            <i class="fa fa-calendar-times me-1"></i> On Leave: {{ $summary['on_leave'] }}
        </span>
    </div>
    <div class="col-auto">
        <span class="badge bg-secondary fs-6 px-3 py-2">
            <i class="fa fa-question me-1"></i> Not Marked: {{ $summary['not_marked'] }}
        </span>
    </div>
</div>

{{-- ── Marking Form ────────────────────────────────────────────────── --}}
<form method="POST" action="{{ route('admin.attendance.bulk') }}">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fa fa-list me-2"></i>Mark Attendance</span>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="markAll('present')">
                    <i class="fa fa-check me-1"></i> All Present
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="markAll('absent')">
                    <i class="fa fa-times me-1"></i> All Absent
                </button>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fa fa-save me-1"></i> Save Attendance
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Teacher Name</th>
                            <th>Employee ID</th>
                            <th>Specialization</th>
                            <th class="text-center">
                                <span class="text-success">Present</span>
                            </th>
                            <th class="text-center">
                                <span class="text-danger">Absent</span>
                            </th>
                            <th class="text-center">
                                <span class="text-warning">Late</span>
                            </th>
                            <th class="text-center">
                                <span class="text-info">On Leave</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $i => $teacher)
                        @php
                            $att = $teacher->attendances->first();
                            $currentStatus = $att ? $att->status : null;
                        @endphp
                        <tr class="
                            @if($currentStatus === 'present') table-success
                            @elseif($currentStatus === 'absent') table-danger
                            @elseif($currentStatus === 'late') table-warning
                            @elseif($currentStatus === 'on_leave') table-info
                            @endif
                        ">
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $teacher->name }}</td>
                            <td><code>{{ $teacher->employee_id }}</code></td>
                            <td class="text-muted small">{{ $teacher->subject_specialization ?? '—' }}</td>
                            @foreach(['present' => 'success', 'absent' => 'danger', 'late' => 'warning', 'on_leave' => 'info'] as $status => $color)
                            <td class="text-center">
                                <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input att-radio"
                                           type="radio"
                                           name="attendance[{{ $teacher->id }}]"
                                           value="{{ $status }}"
                                           data-teacher="{{ $teacher->id }}"
                                           {{ $currentStatus === $status ? 'checked' : '' }}
                                           onchange="updateRowColor(this, '{{ $color }}')">
                                </div>
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No active teachers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-1"></i> Save Attendance
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function markAll(status) {
    document.querySelectorAll('input.att-radio[value="' + status + '"]').forEach(function(radio) {
        radio.checked = true;
        var colorMap = { present: 'success', absent: 'danger', late: 'warning', on_leave: 'info' };
        updateRowColor(radio, colorMap[status] || '');
    });
}

function updateRowColor(radio, color) {
    var row = radio.closest('tr');
    row.className = row.className.replace(/table-(success|danger|warning|info|secondary)\b/g, '').trim();
    if (color) {
        row.classList.add('table-' + color);
    }
}
</script>
@endpush
