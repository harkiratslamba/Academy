@extends('layouts.app')

@section('title', 'Timetable')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="fa fa-calendar-alt me-2 text-primary"></i>Timetable</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
        <i class="fa fa-plus me-1"></i> Add Period
    </button>
</div>

{{-- ── Filter Bar ──────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.timetable.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small fw-semibold mb-1">Filter by Class</label>
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                        {{ $class->class_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4">
                <label class="form-label small fw-semibold mb-1">Filter by Day</label>
                <select name="day" class="form-select form-select-sm">
                    <option value="">All Days</option>
                    @foreach($days as $day)
                    <option value="{{ $day }}" {{ request('day') === $day ? 'selected' : '' }}>{{ $day }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter me-1"></i> Filter</button>
                <a href="{{ route('admin.timetable.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- ── Timetable Table ─────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable>
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Day</th>
                        <th>Period</th>
                        <th>Time</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timetables as $i => $tt)
                    <tr class="{{ !$tt->is_active ? 'table-secondary text-muted' : '' }}">
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $tt->day }}</td>
                        <td class="text-center"><span class="badge bg-primary">P{{ $tt->period_number }}</span></td>
                        <td class="text-nowrap small">{{ substr($tt->start_time, 0, 5) }} – {{ substr($tt->end_time, 0, 5) }}</td>
                        <td>{{ $tt->schoolClass->class_name ?? '—' }}</td>
                        <td>{{ $tt->section->section_name ?? '—' }}</td>
                        <td>{{ $tt->subject->subject_name ?? '—' }}</td>
                        <td>{{ $tt->teacher->name ?? '—' }}</td>
                        <td>{{ $tt->room->room_number ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $tt->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $tt->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <form method="POST" action="{{ route('admin.timetable.toggle', $tt->id) }}" style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-{{ $tt->is_active ? 'warning' : 'success' }}" title="{{ $tt->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="fa fa-{{ $tt->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.timetable.destroy', $tt->id) }}"
                                      onsubmit="return confirm('Delete this timetable entry?')" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">No timetable entries found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Add Period Modal ────────────────────────────────────────────── --}}
<div class="modal fade" id="addPeriodModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.timetable.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-calendar-plus me-2"></i>Add Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                        <select name="class_id" id="add_class_id" class="form-select" required onchange="loadSections(this.value)">
                            <option value="">— Select Class —</option>
                            @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->class_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Section</label>
                        <select name="section_id" id="add_section_id" class="form-select">
                            <option value="">— Select Section —</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">— Select Subject —</option>
                            @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->subject_name }} ({{ $subject->subject_code }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Teacher <span class="text-danger">*</span></label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">— Select Teacher —</option>
                            @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Day <span class="text-danger">*</span></label>
                        <select name="day" class="form-select" required>
                            <option value="">— Select Day —</option>
                            @foreach($days as $day)
                            <option value="{{ $day }}" {{ old('day') === $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Period Number <span class="text-danger">*</span></label>
                        <input type="number" name="period_number" class="form-control" value="{{ old('period_number') }}"
                               min="1" max="10" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Room</label>
                        <select name="room_id" class="form-select">
                            <option value="">— No Room —</option>
                            @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                {{ $room->room_number }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Save Period</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// All sections JSON for cascade
var allSections = @json($sections);

function loadSections(classId) {
    var sel = document.getElementById('add_section_id');
    sel.innerHTML = '<option value="">— Select Section —</option>';
    if (!classId) return;
    allSections.filter(function(s){ return String(s.class_id) === String(classId); })
        .forEach(function(s){
            var opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.section_name;
            sel.appendChild(opt);
        });
}
</script>
@endpush
