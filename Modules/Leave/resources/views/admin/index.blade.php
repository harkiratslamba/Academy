@extends('layouts.app')

@section('title', 'Leave Requests')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="fa fa-calendar-times me-2 text-primary"></i>Leave Requests</h4>
</div>

{{-- ── Status Tabs ─────────────────────────────────────────────────── --}}
<ul class="nav nav-tabs mb-4">
    @foreach(['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'all' => 'secondary'] as $tab => $color)
    <li class="nav-item">
        <a href="{{ route('admin.leaves.admin', ['status' => $tab]) }}"
           class="nav-link {{ $status === $tab ? 'active' : '' }}">
            {{ ucfirst($tab) }}
            <span class="badge bg-{{ $color }} ms-1">{{ $counts[$tab] }}</span>
        </a>
    </li>
    @endforeach
</ul>

{{-- ── Leave Table ─────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable>
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Teacher</th>
                        <th>Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $i => $leave)
                    <tr class="
                        @if($leave->status === 'approved') table-success
                        @elseif($leave->status === 'rejected') table-danger
                        @elseif($leave->status === 'pending') table-warning
                        @endif
                    " style="--bs-table-bg-opacity:.4">
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $leave->teacher->name ?? '—' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst($leave->leave_type) }}</span></td>
                        <td>{{ \Carbon\Carbon::parse($leave->from_date)->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($leave->to_date)->format('d M Y') }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ $leave->days }}</span>
                        </td>
                        <td class="text-muted small" style="max-width:200px">
                            {{ Str::limit($leave->reason, 60) }}
                        </td>
                        <td>
                            <span class="badge
                                @if($leave->status === 'approved') bg-success
                                @elseif($leave->status === 'rejected') bg-danger
                                @else bg-warning text-dark @endif">
                                {{ ucfirst($leave->status) }}
                            </span>
                        </td>
                        <td class="text-muted small">
                            {{ $leave->created_at->format('d M Y') }}
                        </td>
                        <td>
                            @if($leave->status === 'pending')
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-success"
                                        onclick="openActionModal({{ $leave->id }}, 'approve', {{ json_encode($leave->teacher->name ?? 'Teacher') }})"
                                        title="Approve">
                                    <i class="fa fa-check"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                        onclick="openActionModal({{ $leave->id }}, 'reject', {{ json_encode($leave->teacher->name ?? 'Teacher') }})"
                                        title="Reject">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                            @elseif($leave->admin_remarks)
                            <span class="text-muted small" title="{{ $leave->admin_remarks }}">
                                <i class="fa fa-comment me-1"></i>{{ Str::limit($leave->admin_remarks, 30) }}
                            </span>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">
                        <i class="fa fa-inbox fa-2x d-block mb-2"></i>No leave requests found.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Approve / Reject Modal ──────────────────────────────────────── --}}
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="actionForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle">Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="actionModalMessage" class="mb-3"></p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Remarks (optional)</label>
                    <textarea name="admin_remarks" class="form-control" rows="3"
                              placeholder="Add any remarks for the teacher..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn" id="actionSubmitBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openActionModal(id, action, teacherName) {
    var isApprove = action === 'approve';
    var baseUrl = isApprove
        ? '{{ url("admin/leaves") }}/' + id + '/approve'
        : '{{ url("admin/leaves") }}/' + id + '/reject';

    document.getElementById('actionForm').action = baseUrl;
    document.getElementById('actionModalTitle').textContent = isApprove ? 'Approve Leave' : 'Reject Leave';
    document.getElementById('actionModalMessage').innerHTML =
        (isApprove
            ? '<i class="fa fa-check-circle text-success me-2"></i>'
            : '<i class="fa fa-times-circle text-danger me-2"></i>') +
        (isApprove ? 'Approve' : 'Reject') + ' leave request for <strong>' + teacherName + '</strong>?';

    var btn = document.getElementById('actionSubmitBtn');
    btn.className = 'btn btn-' + (isApprove ? 'success' : 'danger');
    btn.textContent = isApprove ? 'Approve' : 'Reject';

    new bootstrap.Modal(document.getElementById('actionModal')).show();
}
</script>
@endpush
