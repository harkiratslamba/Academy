@extends('layouts.app')

@section('title', 'Classes & Sections')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="fa fa-school me-2 text-primary"></i>Classes &amp; Sections</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addClassModal">
            <i class="fa fa-plus me-1"></i> Add Class
        </button>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="fa fa-plus me-1"></i> Add Section
        </button>
    </div>
</div>

<div class="row g-4">
    {{-- ── Classes Table ────────────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-building me-2 text-primary"></i>Classes
                <span class="badge bg-primary ms-1">{{ $classes->count() }}</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0" data-datatable='{"pageLength":15}'>
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Class Name</th>
                            <th>Sections</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $i => $class)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $class->class_name }}</td>
                            <td><span class="badge bg-info text-dark">{{ $class->sections_count }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('admin.classes.destroyClass', $class->id) }}"
                                      onsubmit="return confirm('Delete class {{ addslashes($class->class_name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No classes yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Sections Table ───────────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-list me-2 text-success"></i>Sections
                <span class="badge bg-success ms-1">{{ $sections->count() }}</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0" data-datatable='{"pageLength":15}'>
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $i => $section)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $section->schoolClass->class_name ?? '—' }}</td>
                            <td class="fw-semibold">{{ $section->section_name }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.classes.destroySection', $section->id) }}"
                                      onsubmit="return confirm('Delete section {{ addslashes($section->section_name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No sections yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Add Class Modal ─────────────────────────────────────────────── --}}
<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.classes.storeClass') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-building me-2"></i>Add Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">Class Name <span class="text-danger">*</span></label>
                <input type="text" name="class_name" class="form-control" value="{{ old('class_name') }}"
                       placeholder="e.g. Class 9" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Save</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Add Section Modal ───────────────────────────────────────────── --}}
<div class="modal fade" id="addSectionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.classes.storeSection') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-list me-2"></i>Add Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                    <select name="class_id" class="form-select" required>
                        <option value="">— Select Class —</option>
                        @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->class_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Section Name <span class="text-danger">*</span></label>
                    <input type="text" name="section_name" class="form-control" value="{{ old('section_name') }}"
                           placeholder="e.g. A" maxlength="10" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fa fa-save me-1"></i> Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
