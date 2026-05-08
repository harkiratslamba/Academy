@extends('layouts.app')

@section('title', 'Substitutions')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="fa fa-exchange-alt me-2 text-primary"></i>Substitution Management</h4>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#markAbsentModal">
        <i class="fa fa-user-times me-1"></i> Mark Teacher Absent
    </button>
</div>

{{-- ── Date Selector ───────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.substitution.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small fw-semibold mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}">
            </div>
            <div class="col-sm-3">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search me-1"></i> View</button>
                <a href="{{ route('admin.substitution.index') }}" class="btn btn-secondary btn-sm ms-1">Today</a>
            </div>
            <div class="col-sm-5 text-end">
                <span class="badge bg-light text-dark border fs-6">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</span>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    {{-- ── Main Column ─────────────────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- Unassigned Classes Alert ──────────────────────────────── --}}
        @if($unassignedClasses->count() > 0)
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <h6 class="fw-bold"><i class="fa fa-exclamation-triangle me-2"></i>
                {{ $unassignedClasses->count() }} Class(es) Need a Substitute
            </h6>
            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered mb-0 bg-white">
                    <thead class="table-danger">
                        <tr>
                            <th>Period</th>
                            <th>Time</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Absent Teacher</th>
                            <th>Assign</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unassignedClasses as $uc)
                        <tr>
                            <td>P{{ $uc->period_number }}</td>
                            <td class="text-nowrap small">{{ substr($uc->start_time, 0, 5) }}–{{ substr($uc->end_time, 0, 5) }}</td>
                            <td>{{ $uc->class_name }}{{ $uc->section_name ? ' – '.$uc->section_name : '' }}</td>
                            <td>{{ $uc->subject_name }}</td>
                            <td><span class="badge bg-danger">{{ $uc->teacher_name }}</span></td>
                            <td>
                                <button class="btn btn-success btn-sm py-0"
                                        onclick="openAssignModal({{ $uc->timetable_id }}, {{ json_encode($uc->class_name . ($uc->section_name ? ' - '.$uc->section_name : '')) }}, {{ json_encode($uc->subject_name) }}, 'P{{ $uc->period_number }} ({{ substr($uc->start_time,0,5) }}-{{ substr($uc->end_time,0,5) }})', {{ json_encode($uc->teacher_name) }})">
                                    <i class="fa fa-user-plus"></i> Assign
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="fa fa-check-circle me-2"></i>
            All classes are covered for {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}.
        </div>
        @endif

        {{-- Today's Substitutions ────────────────────────────────── --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-list me-2 text-primary"></i>Substitution Records</span>
                <span class="badge bg-primary">{{ $todaySubs->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($todaySubs->isEmpty())
                <div class="text-center text-muted py-4">
                    <i class="fa fa-inbox fa-2x mb-2 d-block"></i>No substitutions recorded for this date.
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Original</th>
                                <th>Substitute</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todaySubs as $sub)
                            <tr class="{{ $sub->status === 'cancelled' ? 'table-secondary text-muted' : '' }}">
                                <td><span class="badge bg-primary">P{{ $sub->timetable->period_number ?? '?' }}</span></td>
                                <td>{{ $sub->timetable->schoolClass->class_name ?? '—' }}
                                    @if($sub->timetable->section)
                                    – {{ $sub->timetable->section->section_name }}
                                    @endif
                                </td>
                                <td>{{ $sub->timetable->subject->subject_name ?? '—' }}</td>
                                <td><span class="text-danger">{{ $sub->originalTeacher->name ?? '—' }}</span></td>
                                <td><span class="text-success fw-semibold">{{ $sub->substituteTeacher->name ?? '—' }}</span></td>
                                <td>
                                    <span class="badge
                                        @if($sub->status === 'assigned') bg-success
                                        @elseif($sub->status === 'cancelled') bg-secondary
                                        @elseif($sub->status === 'completed') bg-primary
                                        @else bg-warning text-dark @endif">
                                        {{ ucfirst($sub->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($sub->status !== 'cancelled')
                                    <form method="POST" action="{{ route('admin.substitution.destroy', $sub->id) }}"
                                          onsubmit="return confirm('Cancel this substitution?')" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fa fa-times"></i> Cancel
                                        </button>
                                    </form>
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

    {{-- ── Absent Teachers Sidebar ──────────────────────────────────── --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-user-times me-2 text-danger"></i>Absent Teachers
                <span class="badge bg-danger ms-1">{{ $absentTeachers->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($absentTeachers->isEmpty())
                <div class="text-center text-muted py-3 small">
                    <i class="fa fa-check-circle text-success d-block fa-2x mb-1"></i>
                    No teachers absent today.
                </div>
                @else
                <ul class="list-group list-group-flush">
                    @foreach($absentTeachers as $att)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                        <div>
                            <div class="fw-semibold small">{{ $att->teacher->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:0.75rem">{{ $att->teacher->employee_id ?? '' }}</div>
                        </div>
                        <span class="badge {{ $att->status === 'on_leave' ? 'bg-warning text-dark' : 'bg-danger' }}">
                            {{ $att->status === 'on_leave' ? 'On Leave' : 'Absent' }}
                        </span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Mark Absent Modal ───────────────────────────────────────────── --}}
<div class="modal fade" id="markAbsentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.substitution.markAbsent') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-user-times me-2 text-danger"></i>Mark Teacher Absent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Teacher <span class="text-danger">*</span></label>
                    <select name="teacher_id" class="form-select" required>
                        <option value="">— Select Teacher —</option>
                        @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }} ({{ $teacher->employee_id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="absent">Absent</option>
                        <option value="on_leave">On Leave</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fa fa-save me-1"></i> Mark Absent</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Assign Substitute Modal ─────────────────────────────────────── --}}
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.substitution.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="timetable_id" id="assign_timetable_id">
            <input type="hidden" name="date" value="{{ $date }}">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-user-plus me-2"></i>Assign Substitute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded small">
                            <strong>Class:</strong> <span id="assign_class"></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded small">
                            <strong>Subject:</strong> <span id="assign_subject"></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded small">
                            <strong>Period:</strong> <span id="assign_period"></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded small">
                            <strong>Absent Teacher:</strong> <span id="assign_teacher" class="text-danger"></span>
                        </div>
                    </div>
                </div>

                <label class="form-label fw-semibold">Available Teachers</label>
                <div id="teacher-list-loading" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary"></div> Loading...
                </div>
                <div id="teacher-list-container" style="display:none">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th>Employee ID</th>
                                    <th>Specialization</th>
                                    <th>This Month</th>
                                </tr>
                            </thead>
                            <tbody id="available-teacher-tbody"></tbody>
                        </table>
                    </div>
                    <div id="no-teachers-msg" class="text-center text-muted py-3 d-none">
                        <i class="fa fa-exclamation-circle text-warning me-1"></i> No available teachers found.
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label fw-semibold">Notes (optional)</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success" id="assignSubmitBtn" disabled>
                    <i class="fa fa-user-plus me-1"></i> Assign
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openAssignModal(timetableId, className, subjectName, period, absentTeacher) {
    document.getElementById('assign_timetable_id').value = timetableId;
    document.getElementById('assign_class').textContent   = className;
    document.getElementById('assign_subject').textContent = subjectName;
    document.getElementById('assign_period').textContent  = period;
    document.getElementById('assign_teacher').textContent = absentTeacher;

    // Reset UI
    document.getElementById('teacher-list-loading').style.display = 'block';
    document.getElementById('teacher-list-container').style.display = 'none';
    document.getElementById('assignSubmitBtn').disabled = true;
    document.getElementById('available-teacher-tbody').innerHTML = '';
    document.getElementById('no-teachers-msg').classList.add('d-none');

    new bootstrap.Modal(document.getElementById('assignModal')).show();

    // AJAX: load available teachers
    var date = '{{ $date }}';
    fetch('{{ route("admin.substitution.availableTeachers") }}?timetable_id=' + timetableId + '&date=' + date, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        document.getElementById('teacher-list-loading').style.display = 'none';
        document.getElementById('teacher-list-container').style.display = 'block';

        var tbody = document.getElementById('available-teacher-tbody');
        tbody.innerHTML = '';

        if (!data.teachers || data.teachers.length === 0) {
            document.getElementById('no-teachers-msg').classList.remove('d-none');
            return;
        }

        data.teachers.forEach(function(t) {
            var row = document.createElement('tr');
            row.innerHTML =
                '<td class="text-center">' +
                    '<input type="radio" name="substitute_teacher_id" value="' + t.id + '" ' +
                    'class="form-check-input sub-radio" required onchange="document.getElementById(\'assignSubmitBtn\').disabled=false">' +
                '</td>' +
                '<td class="fw-semibold">' + escHtml(t.name) +
                    (t.same_subject ? ' <span class="badge bg-success ms-1">Same Subject</span>' : '') +
                '</td>' +
                '<td><code>' + escHtml(t.employee_id || '') + '</code></td>' +
                '<td class="text-muted small">' + escHtml(t.specialization || '—') + '</td>' +
                '<td><span class="badge bg-light text-dark border">' + t.workload + ' sub(s)</span></td>';
            tbody.appendChild(row);
        });
    })
    .catch(function() {
        document.getElementById('teacher-list-loading').style.display = 'none';
        document.getElementById('teacher-list-container').style.display = 'block';
        document.getElementById('no-teachers-msg').textContent = 'Error loading teachers.';
        document.getElementById('no-teachers-msg').classList.remove('d-none');
    });
}

function escHtml(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}
</script>
@endpush
