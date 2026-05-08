@extends('layouts.app')

@section('title', 'My Availability')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="fa fa-clock me-2 text-primary"></i>My Availability</h4>
        <p class="text-muted mb-0">Week of {{ \Carbon\Carbon::parse($weekStart)->format('d M') }} &ndash; {{ \Carbon\Carbon::parse($weekEnd)->format('d M Y') }}</p>
    </div>
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

<div class="row g-3 mb-4">
    {{-- Full Day Availability Form --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="fa fa-calendar-day me-2 text-primary"></i>Set Full Day Availability
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('teacher.availability.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                               value="{{ old('date', now()->toDateString()) }}" required>
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="">-- Select Status --</option>
                            <option value="available" {{ old('status') === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="unavailable" {{ old('status') === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                            <option value="busy" {{ old('status') === 'busy' ? 'selected' : '' }}>Busy</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-muted">(optional)</span></label>
                        <textarea name="reason" rows="2" class="form-control @error('reason') is-invalid @enderror"
                                  placeholder="Enter reason...">{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    {{-- period_number = 0 for full day (omitted / empty) --}}
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-save me-1"></i> Save Full Day Availability
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Period-Specific Availability Form --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="fa fa-list-ol me-2 text-info"></i>Set Period-Specific Availability
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('teacher.availability.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Period Number</label>
                        <input type="number" name="period_number" class="form-control"
                               min="1" max="12" placeholder="e.g. 3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="">-- Select Status --</option>
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                            <option value="busy">Busy</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-muted">(optional)</span></label>
                        <textarea name="reason" rows="2" class="form-control" placeholder="Enter reason..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-info w-100 text-white">
                        <i class="fa fa-save me-1"></i> Save Period Availability
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- This Week's Availability Table --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa fa-table me-2 text-primary"></i>This Week's Availability</span>
        <span class="badge bg-primary">{{ $records->count() }} record(s)</span>
    </div>
    <div class="card-body p-0">
        @if($records->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa fa-calendar fa-2x mb-2 d-block opacity-25"></i>
                No availability records set for this week.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($record->date)->format('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($record->date)->format('l') }}</td>
                                <td>
                                    @if($record->period_number == 0)
                                        <span class="badge bg-secondary">Full Day</span>
                                    @else
                                        Period {{ $record->period_number }}
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'available'   => 'success',
                                            'unavailable' => 'danger',
                                            'busy'        => 'warning',
                                        ];
                                        $color = $statusColors[$record->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst($record->status) }}</span>
                                </td>
                                <td class="text-muted">{{ $record->reason ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
