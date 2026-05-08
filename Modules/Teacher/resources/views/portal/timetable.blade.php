@extends('layouts.app')

@section('title', 'My Timetable')

@push('styles')
<style>
    .timetable-grid th { font-size: 0.82rem; white-space: nowrap; }
    .timetable-grid td { vertical-align: top; font-size: 0.82rem; min-width: 100px; }
    .period-cell { min-height: 60px; }
    .today-col { background-color: #eef4ff; }
    @media print {
        #sidebar, .top-navbar, .btn, .flash-alert { display: none !important; }
        #main-content { margin: 0 !important; padding: 0 !important; }
    }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="fa fa-calendar-alt me-2 text-primary"></i>My Timetable</h4>
        <p class="text-muted mb-0">{{ $teacher->name }} &mdash; Weekly Schedule</p>
    </div>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
        <i class="fa fa-print me-1"></i> Print
    </button>
</div>

{{-- Weekly Grid --}}
<div class="card mb-4">
    <div class="card-header"><i class="fa fa-th me-2 text-primary"></i>Weekly Grid</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered timetable-grid mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" style="width:60px">Period</th>
                        @foreach($days as $day)
                            <th class="text-center {{ $day === $today ? 'bg-primary' : '' }}">
                                {{ $day }}
                                @if($day === $today)
                                    <span class="badge bg-light text-dark ms-1" style="font-size:0.65rem">Today</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for($p = 1; $p <= $maxPeriods; $p++)
                        <tr>
                            <td class="text-center fw-bold text-muted align-middle">{{ $p }}</td>
                            @foreach($days as $day)
                                @php
                                    $cell = $grouped[$day]->firstWhere('period_number', $p);
                                @endphp
                                <td class="{{ $day === $today ? 'today-col' : '' }}">
                                    @if($cell)
                                        <div class="period-cell">
                                            <div class="fw-semibold text-primary" style="font-size:0.82rem">
                                                {{ $cell->subject->name ?? '—' }}
                                            </div>
                                            <div class="text-muted" style="font-size:0.75rem">
                                                {{ $cell->schoolClass->class_name ?? '' }}
                                                {{ $cell->section->section_name ?? '' }}
                                            </div>
                                            @if($cell->room)
                                                <div class="text-muted" style="font-size:0.72rem">
                                                    <i class="fa fa-door-open"></i> {{ $cell->room->room_name }}
                                                </div>
                                            @endif
                                            @if($cell->start_time)
                                                <div class="text-muted" style="font-size:0.70rem">
                                                    {{ \Carbon\Carbon::parse($cell->start_time)->format('h:i A') }}
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="period-cell text-center text-muted" style="font-size:0.75rem; padding-top:18px;">
                                            &mdash;
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- List View --}}
<div class="card">
    <div class="card-header"><i class="fa fa-list me-2 text-primary"></i>List View</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" data-datatable>
                <thead class="table-light">
                    <tr>
                        <th>Day</th>
                        <th>Period</th>
                        <th>Time</th>
                        <th>Subject</th>
                        <th>Class / Section</th>
                        <th>Room</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr class="{{ $entry->day === $today ? 'table-primary' : '' }}">
                            <td>
                                {{ $entry->day }}
                                @if($entry->day === $today)
                                    <span class="badge bg-primary ms-1">Today</span>
                                @endif
                            </td>
                            <td>{{ $entry->period_number }}</td>
                            <td>
                                @if($entry->start_time && $entry->end_time)
                                    {{ \Carbon\Carbon::parse($entry->start_time)->format('h:i A') }}
                                    – {{ \Carbon\Carbon::parse($entry->end_time)->format('h:i A') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $entry->subject->name ?? '—' }}</td>
                            <td>
                                {{ $entry->schoolClass->class_name ?? '' }}
                                {{ $entry->section->section_name ?? '' }}
                            </td>
                            <td>{{ $entry->room->room_name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $entry->is_active ? 'success' : 'secondary' }}">
                                    {{ $entry->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No timetable entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
