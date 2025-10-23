{{-- filepath: resources/views/operatingroom/history.blade.php --}}
@extends('layouts.operatingroom')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold hdng mb-1">🏥 Operating Room History</h3>
        <p class="text-muted">History of all finished operating room procedures (completed and cancelled)</p>
    </div>

    {{-- Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Total Finished Procedures</h6>
                        <h4 class="fw-bold mb-0">
                            {{ $finishedCount }}
                        </h4>
                    </div>
                    <div class="ms-3 text-primary">
                        <i class="fas fa-clipboard-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Unified Table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Procedure No</th>
                        <th>Patient</th>
                        <th>Surgeon</th>
                        <th>Last Updated</th>
                        <th>Procedure</th>
                        {{-- <th>OR Room</th> --}}
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($procedures as $procedure)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark">
                                OR-{{ str_pad($procedure->assignment_id, 6, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td>
                            <i class="fas fa-user text-primary me-1"></i>
                            {{ $procedure->patient->full_name 
                                ?? $procedure->patient->patient_first_name . ' ' . $procedure->patient->patient_last_name }}
                        </td>
                        <td>
                            <i class="fas fa-user-md text-secondary me-1"></i>
                            {{ $procedure->doctor->doctor_name ?? '—' }}
                        </td>
                        <td class="text-muted">
                            <span class="completed-at" style="cursor: pointer"
                                data-date="{{ $procedure->updated_at->format('M d, Y h:i A') }}">
                                {{ $procedure->updated_at->diffForHumans() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-light text-dark border me-1 mb-1">
                                {{ $procedure->service->service_name ?? 'N/A' }}
                            </span>
                        </td>
                        {{-- <td>
                            <span class="badge bg-info text-white">
                                {{ $procedure->room ? 'OR-' . $procedure->room : 'N/A' }}
                            </span>
                        </td> --}}
                        <td>
                            <span class="badge 
                                {{ $procedure->service_status === 'completed' ? 'bg-success text-white' : '' }}
                                {{ $procedure->service_status === 'cancelled' ? 'bg-danger text-white' : '' }}">
                                {{ ucfirst($procedure->service_status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-1"></i> No operating room procedures yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.completed-at').forEach(function(span) {
        span.addEventListener('click', function() {
            if (span.dataset.toggled === "1") {
                span.textContent = span.dataset.relative;
                span.dataset.toggled = "0";
            } else {
                // Save current (relative) text for toggling back
                span.dataset.relative = span.textContent;
                span.textContent = span.dataset.date;
                span.dataset.toggled = "1";
            }
        });
    });
});
</script>
@endpush

@endsection