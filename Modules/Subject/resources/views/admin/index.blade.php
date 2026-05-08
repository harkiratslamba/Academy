@extends('layouts.app')

@section('title', 'Subjects')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="fa fa-book me-2 text-primary"></i>Subjects</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
        <i class="fa fa-plus me-1"></i> Add Subject
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable>
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Subject Name</th>
                        <th>Code</th>
                        <th>Class</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $i => $subject)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $subject->subject_name }}</td>
                        <td><code>{{ $subject->subject_code }}</code></td>
                        <td>{!! $subject->schoolClass ? $subject->schoolClass->class_name : '<span class="text-muted">All Classes</span>' !!}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary"
                                        onclick="openEditModal({{ $subject->id }}, {{ json_encode($subject->subject_name) }}, {{ json_encode($subject->subject_code) }}, {{ $subject->class_id ?? 'null' }})"
                                        title="Edit">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.subjects.destroy', $subject->id) }}"
                                      onsubmit="return confirm('Delete subject {{ addslashes($subject->subject_name) }}?')" style="display:inline">
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
                    <tr><td colspan="5" class="text-center text-muted py-4">No subjects found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Add Subject Modal ───────────────────────────────────────────── --}}
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.subjects.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-plus me-2"></i>Add Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Subject Name <span class="text-danger">*</span></label>
                    <input type="text" name="subject_name" class="form-control" value="{{ old('subject_name') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Subject Code <span class="text-danger">*</span></label>
                    <input type="text" name="subject_code" class="form-control" value="{{ old('subject_code') }}"
                           placeholder="e.g. MATH01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Class (optional)</label>
                    <select name="class_id" class="form-select">
                        <option value="">— All Classes —</option>
                        @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->class_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Save</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit Subject Modal ──────────────────────────────────────────── --}}
<div class="modal fade" id="editSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editSubjectForm" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-edit me-2"></i>Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Subject Name <span class="text-danger">*</span></label>
                    <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Subject Code <span class="text-danger">*</span></label>
                    <input type="text" name="subject_code" id="edit_subject_code" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Class (optional)</label>
                    <select name="class_id" id="edit_class_id" class="form-select">
                        <option value="">— All Classes —</option>
                        @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->class_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Update</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditModal(id, name, code, classId) {
    document.getElementById('editSubjectForm').action = '{{ url("admin/subjects") }}/' + id;
    document.getElementById('edit_subject_name').value = name || '';
    document.getElementById('edit_subject_code').value = code || '';
    var sel = document.getElementById('edit_class_id');
    sel.value = classId ? String(classId) : '';
    new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
}
</script>
@endpush
