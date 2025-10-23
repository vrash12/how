{{-- resources/views/operatingroom/dashboard.blade.php --}}
@extends('layouts.operatingroom')

@section('content')
    {{-- Header --}}
    <div class="mb-4">
        <h4 class="fw-bold">🏥 Operating Room Services Management</h4>
        <p class="text-muted">Monitor ongoing and upcoming procedures, manage schedules, and oversee OR operations in real-time.</p>
    </div>

    {{-- URGENT ALERTS SECTION --}}
    @if($urgentProcedures->count() > 0 || $newProcedures->count() > 0)
    <div class="row g-3 mb-4">
        {{-- Urgent Procedures Alert --}}
        @if($urgentProcedures->count() > 0)
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1">⚠️ Urgent: {{ $urgentProcedures->count() }} Procedure(s) Need Immediate Attention!</h6>
                        <p class="mb-2">These procedures have been pending for more than 2 hours:</p>
                        <div class="row">
                            @foreach($urgentProcedures->take(3) as $urgent)
                            <div class="col-md-4">
                                <div class="card border-danger mb-2">
                                    <div class="card-body p-2">
                                        <small class="text-danger fw-bold">{{ $urgent->patient->patient_first_name }} {{ $urgent->patient->patient_last_name }}</small><br>
                                        <small>{{ $urgent->service->service_name }}</small><br>
                                        <small class="text-muted">{{ $urgent->created_at->diffForHumans() }}</small>
                                        <div class="mt-2">
                                            <form action="{{ route('operating.approve', $urgent) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-success btn-xs">Approve</button>
                                            </form>
                                            <button class="btn btn-danger btn-xs" onclick="cancelProcedure({{ $urgent->assignment_id }})">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <a href="{{ route('operating.queue') }}" class="btn btn-outline-danger">
                        <i class="fas fa-arrow-right me-1"></i> View All
                    </a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        @endif

        {{-- New Procedures Alert --}}
        @if($newProcedures->count() > 0)
        <div class="col-12">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-bell fa-2x me-3 text-info"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1">🔔 {{ $newProcedures->count() }} New Procedure(s) Require Review</h6>
                        <p class="mb-0">Recently assigned procedures awaiting your approval:</p>
                        @foreach($newProcedures->take(2) as $new)
                        <span class="badge bg-info me-2">{{ $new->patient->patient_first_name }} {{ $new->patient->patient_last_name }} - {{ $new->service->service_name }}</span>
                        @endforeach
                    </div>
                    <a href="{{ route('operating.queue') }}" class="btn btn-outline-info">
                        <i class="fas fa-eye me-1"></i> Review Now
                    </a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Stats / Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-procedures fa-2x text-primary me-4"></i>
                    <div>
                        <div class="text-muted small">Total Procedures Completed</div>
                        <h4 class="fw-semibold mb-0">{{ $completedCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-users fa-2x text-success me-4"></i>
                    <div>
                        <div class="text-muted small">Patients Served</div>
                        <h4 class="fw-semibold mb-0">{{ $patientsServed }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-hourglass-half fa-2x text-warning me-4"></i>
                    <div>
                        <div class="text-muted small">Pending Procedures</div>
                        <h4 class="fw-semibold mb-0">{{ $pendingCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-clock fa-2x text-danger me-4"></i>
                    <div>
                        <div class="text-muted small">Urgent (2+ Hours)</div>
                        <h4 class="fw-semibold mb-0 text-danger">{{ $urgentProcedures->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

     {{-- Quick Actions --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6 col-md-6">
            <a href="{{ route('operating.queue') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa-solid fa-list-check fa-2x text-info me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Procedure Queue</h6>
                            <small class="text-muted">See all pending procedures</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-6 col-md-6">
            <a href="{{ route('operating.history') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa-solid fa-history fa-2x text-success me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Procedure History</h6>
                            <small class="text-muted">View completed procedures</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Today's Procedures --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white border-0 d-flex align-items-center">
            <i class="fa-solid fa-calendar-day me-2 text-secondary"></i>
            <h6 class="mb-0">Today's Procedures</h6>
        </div>
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Procedure</th>
                        <th>Assigned By</th>
                        <th>OR No.</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($todayProcedures as $i => $proc)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $proc->patient->patient_first_name }} {{ $proc->patient->patient_last_name }}</strong><br>
                                <small class="text-muted">P-{{ $proc->patient->patient_id }}</small>
                            </td>
                            <td>{{ $proc->service->service_name }}</td>
                            <td><span class="badge bg-info text-white">{{ $proc->doctor->doctor_name }}</span></td>
                            <td>{{ $proc->assignment_id ? 'OR-' . str_pad($proc->assignment_id, 6, '0', STR_PAD_LEFT) : '–' }}</td>
                            <td class="text-end">₱{{ number_format($proc->amount, 2) }}</td>
                            <td class="text-center">
                                @if($proc->service_status === 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($proc->service_status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($proc->service_status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">
                                <i class="fa-solid fa-circle-info me-1"></i> No procedures today
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center text-muted mb-4">
        <small>Operating Room Dashboard &copy; {{ date('Y') }}. All rights reserved.</small>
    </div>
@endsection

@push('scripts')
<script>
// Auto-refresh every 30 seconds to check for new procedures
setInterval(function() {
    // Only refresh if no modals are open
    if (!document.querySelector('.modal.show')) {
        window.location.reload();
    }
}, 30000);

function cancelProcedure(assignmentId) {
    Swal.fire({
        title: 'Cancel Procedure',
        text: 'Please provide a reason for cancellation:',
        input: 'textarea',
        inputPlaceholder: 'Enter cancellation reason...',
        showCancelButton: true,
        confirmButtonText: 'Cancel Procedure',
        confirmButtonColor: '#dc3545',
        inputValidator: (value) => {
            if (!value) {
                return 'You need to provide a cancellation reason!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Create and submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/operating/${assignmentId}/cancel`;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            
            const reason = document.createElement('input');
            reason.type = 'hidden';
            reason.name = 'cancel_reason';
            reason.value = result.value;
            
            form.appendChild(csrf);
            form.appendChild(reason);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
