@extends('layouts.app')

@section('title', 'My Leaves')

@push('styles')
<style>
    .leave-balance-card { border-left: 4px solid; }
    .leave-balance-card.sick    { border-color: #0dcaf0; }
    .leave-balance-card.casual  { border-color: #ffc107; }
    .leave-balance-card.earned  { border-color: #198754; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="fa fa-calendar-times me-2 text-primary"></i>My Leaves</h4>
        <p class="text-muted mb-0">Leave history and balance for {{ now()->year }}</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
        <i class="fa fa-plus me-1"></i> Apply for Leave
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show flash-alert" role="alert">
        <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show flash-alert" role="alert">
        <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Leave Balance Cards --}}
<div class="row g-3 mb-4">
    @foreach(['sick' => ['icon' => 'fa-plus-square', 'color' => 'info', 'label' => 'Sick Leave'],
               'casual' => ['icon' => 'fa-briefcase', 'color' => 'warning', 'label' => 'Casual Leave'],
               'earned' => ['icon' => 'fa-star', 'color' => 'success', 'label' => 'Earned Leave']] as $type => $meta)
    @php
        $total    = $limits[$type];
        $usedDays = $used[$type] ?? 0;
        $remaining = max(0, $total - $usedDays);
        $pct       = $total > 0 ? round(($usedDays / $total) * 100) : 0;
    @endphp
    <div class="col-md-4">
        <div class="card leave-balance-card {{ $type }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="mb-1 fw-bold text-{{ $meta['color'] }}">
                            <i class="fa {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                        </h6>
                        <p class="text-muted small mb-0">{{ now()->year }} entitlement</p>
                    </div>
                    <span class="badge bg-{{ $meta['color'] }} fs-5 px-3">{{ $remaining }}</span>
                </div>
                <div class="progress mb-2" style="height:8px">
                    <div class="progress-bar bg-{{ $meta['color'] }}"
                         role="progressbar"
                         style="width: {{ $pct }}%"
                         aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>Used: <strong>{{ $usedDays }}</strong></span>
                    <span>Total: <strong>{{ $total }}</strong></span>
                    <span>Left: <strong>{{ $remaining }}</strong></span>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@if($pending > 0)
    <div class="alert alert-info mb-4">
        <i class="fa fa-info-circle me-2"></i>
        You have <strong>{{ $pending }}</strong> pending leave request(s) awaiting approval.
    </div>
@endif

{{-- Leave History Table --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa fa-history me-2 text-primary"></i>Leave History</span>
        <span class="badge bg-secondary">{{ $leaves->count() }} total</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" data-datatable>
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $i => $leave)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                @php
                                    $typeColors = [
                                        'sick'    => 'info',
                                        'casual'  => 'warning',
                                        'earned'  => 'success',
                                        'other'   => 'secondary',
                                    ];
                                    $tc = $typeColors[$leave->leave_type] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $tc }}">{{ ucfirst($leave->leave_type) }}</span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($leave->from_date)->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($leave->to_date)->format('d M Y') }}</td>
                            <td>
                                @php
                                    $days = \Carbon\Carbon::parse($leave->from_date)->diffInDays(\Carbon\Carbon::parse($leave->to_date)) + 1;
                                @endphp
                                {{ $days }}
                            </td>
                            <td class="text-muted" style="max-width:200px">
                                <span title="{{ $leave->reason }}">
                                    {{ \Illuminate\Support\Str::limit($leave->reason, 50) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending'  => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                    ];
                                    $sc = $statusColors[$leave->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $sc }}">{{ ucfirst($leave->status) }}</span>
                            </td>
                            <td class="text-muted">{{ $leave->created_at->format('d M Y') }}</td>
                            <td>
                                @if($leave->status === 'pending')
                                    <form method="POST"
                                          action="{{ route('teacher.leaves.cancel', $leave->id) }}"
                                          onsubmit="return confirm('Cancel this leave request?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fa fa-times me-1"></i>Cancel
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fa fa-calendar-times fa-2x mb-2 d-block opacity-25"></i>
                                No leave records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Apply Leave Modal --}}
<div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-labelledby="applyLeaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('teacher.leaves.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="applyLeaveModalLabel">
                        <i class="fa fa-calendar-plus me-2 text-primary"></i>Apply for Leave
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type" class="form-select @error('leave_type') is-invalid @enderror" required>
                            <option value="">-- Select Type --</option>
                            <option value="sick"   {{ old('leave_type') === 'sick'   ? 'selected' : '' }}>Sick Leave ({{ $limits['sick'] }} days/yr)</option>
                            <option value="casual" {{ old('leave_type') === 'casual' ? 'selected' : '' }}>Casual Leave ({{ $limits['casual'] }} days/yr)</option>
                            <option value="earned" {{ old('leave_type') === 'earned' ? 'selected' : '' }}>Earned Leave ({{ $limits['earned'] }} days/yr)</option>
                            <option value="other"  {{ old('leave_type') === 'other'  ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('leave_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label fw-semibold">From Date <span class="text-danger">*</span></label>
                            <input type="date" name="from_date"
                                   class="form-control @error('from_date') is-invalid @enderror"
                                   value="{{ old('from_date', now()->toDateString()) }}"
                                   min="{{ now()->toDateString() }}" required>
                            @error('from_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold">To Date <span class="text-danger">*</span></label>
                            <input type="date" name="to_date"
                                   class="form-control @error('to_date') is-invalid @enderror"
                                   value="{{ old('to_date', now()->toDateString()) }}"
                                   min="{{ now()->toDateString() }}" required>
                            @error('to_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="3"
                                  class="form-control @error('reason') is-invalid @enderror"
                                  placeholder="Briefly describe the reason for your leave..."
                                  required>{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-paper-plane me-1"></i>Submit Request
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Re-open modal if there are validation errors
@if($errors->any())
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new bootstrap.Modal(document.getElementById('applyLeaveModal'));
        modal.show();
    });
@endif
</script>
@endpush
