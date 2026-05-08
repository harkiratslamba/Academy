@extends('layouts.app')

@section('title', 'Teachers')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="fa fa-chalkboard-teacher me-2 text-primary"></i>Teachers</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
        <i class="fa fa-plus me-1"></i> Add Teacher
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable>
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Employee ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Specialization</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $i => $teacher)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $teacher->name }}</td>
                        <td><code>{{ $teacher->employee_id }}</code></td>
                        <td>{{ $teacher->user->username ?? '—' }}</td>
                        <td>{{ $teacher->email ?? '—' }}</td>
                        <td>{{ $teacher->phone ?? '—' }}</td>
                        <td>{{ $teacher->subject_specialization ?? '—' }}</td>
                        <td>{{ $teacher->joining_date ? \Carbon\Carbon::parse($teacher->joining_date)->format('d M Y') : '—' }}</td>
                        <td>
                            <span class="badge {{ $teacher->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($teacher->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary"
                                        onclick="openEditModal({{ $teacher->id }}, {{ json_encode($teacher->name) }}, {{ json_encode($teacher->employee_id) }}, {{ json_encode($teacher->email) }}, {{ json_encode($teacher->phone) }}, {{ json_encode($teacher->subject_specialization) }}, {{ json_encode($teacher->qualification) }}, {{ json_encode($teacher->joining_date) }}, {{ json_encode($teacher->status) }})"
                                        title="Edit">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-warning"
                                        onclick="openResetModal({{ $teacher->id }}, {{ json_encode($teacher->name) }})"
                                        title="Reset Password">
                                    <i class="fa fa-key"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                        onclick="confirmDelete({{ $teacher->id }}, {{ json_encode($teacher->name) }})"
                                        title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No teachers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Add Teacher Modal ───────────────────────────────────────────── --}}
<div class="modal fade" id="addTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.teachers.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-plus me-2"></i>Add Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" value="{{ old('username') }}" required autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required autocomplete="new-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subject Specialization</label>
                        <input type="text" name="subject_specialization" class="form-control" value="{{ old('subject_specialization') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Qualification</label>
                        <input type="text" name="qualification" class="form-control" value="{{ old('qualification') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Joining Date</label>
                        <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date') }}">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Save Teacher</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit Teacher Modal ──────────────────────────────────────────── --}}
<div class="modal fade" id="editTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="editTeacherForm" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-edit me-2"></i>Edit Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" name="employee_id" id="edit_employee_id" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subject Specialization</label>
                        <input type="text" name="subject_specialization" id="edit_spec" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Qualification</label>
                        <input type="text" name="qualification" id="edit_qual" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Joining Date</label>
                        <input type="date" name="joining_date" id="edit_joining_date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Update Teacher</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Reset Password Modal ────────────────────────────────────────── --}}
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="resetPasswordForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-key me-2"></i>Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Reset password for: <strong id="reset_teacher_name"></strong></p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" minlength="6" required autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="new_password_confirmation" class="form-control" minlength="6" required autocomplete="new-password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fa fa-key me-1"></i> Reset Password</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Delete Form (hidden) ────────────────────────────────────────── --}}
<form method="POST" id="deleteTeacherForm" style="display:none">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
function openEditModal(id, name, empId, email, phone, spec, qual, joiningDate, status) {
    var base = '{{ url("admin/teachers") }}/' + id;
    document.getElementById('editTeacherForm').action = base;
    document.getElementById('edit_name').value         = name || '';
    document.getElementById('edit_employee_id').value  = empId || '';
    document.getElementById('edit_email').value        = email || '';
    document.getElementById('edit_phone').value        = phone || '';
    document.getElementById('edit_spec').value         = spec || '';
    document.getElementById('edit_qual').value         = qual || '';
    document.getElementById('edit_joining_date').value = joiningDate || '';
    document.getElementById('edit_status').value       = status || 'active';
    new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
}

function openResetModal(id, name) {
    var base = '{{ url("admin/teachers") }}/' + id + '/reset-password';
    document.getElementById('resetPasswordForm').action = base;
    document.getElementById('reset_teacher_name').textContent = name;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

function confirmDelete(id, name) {
    if (!confirm('Delete teacher "' + name + '"? This cannot be undone.')) return;
    var form = document.getElementById('deleteTeacherForm');
    form.action = '{{ url("admin/teachers") }}/' + id;
    form.submit();
}
</script>
@endpush
