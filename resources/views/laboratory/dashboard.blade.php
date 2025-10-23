@extends('layouts.laboratory')

@section('content')
    {{-- Header --}}
    <div class="mb-4">
        <h4 class="fw-bold">🧪 Laboratory Services Management</h4>
        <p class="text-muted">Monitor laboratory services, manage lab charges, and oversee service completion in real-time.</p>
    </div>

    {{-- Stats / Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-vials fa-2x text-primary me-4"></i>
                    <div>
                        <div class="text-muted small">Total Services Completed</div>
                        <h4 class="fw-semibold mb-0">{{ $completedCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-users fa-2x text-success me-4"></i>
                    <div>
                        <div class="text-muted small">Patients Served</div>
                        <h4 class="fw-semibold mb-0">{{ $patientsServed->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-hourglass-half fa-2x text-warning me-4"></i>
                    <div>
                        <div class="text-muted small">Pending Orders</div>
                        <h4 class="fw-semibold mb-0">{{ $pendingCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6 col-md-6">
            <a href="{{ route('laboratory.queue') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa-solid fa-list-check fa-2x text-info me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Laboratory Queue</h6>
                            <small class="text-muted">View and manage pending lab tests.</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-6 col-md-6">
            <a href="{{ route('laboratory.history') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa-solid fa-book-medical fa-2x text-danger me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Service History</h6>
                            <small class="text-muted">Review completed tests and records.</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Today's Admissions --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white border-0 d-flex align-items-center">
            <i class="fa-solid fa-calendar-day me-2 text-secondary"></i>
            <h6 class="mb-0">Today's Admissions</h6>
        </div>
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Assigned By</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($todayAdmissions as $i => $assign)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $assign->patient->patient_first_name }} {{ $assign->patient->patient_last_name }}</strong><br>
                                <small class="text-muted">P-{{ $assign->patient->patient_id }}</small>
                            </td>
                            <td>{{ $assign->service->service_name }}</td>
                            <td><span class="badge bg-info text-white">{{ $assign->doctor->doctor_name }}</span></td>
                            <td class="text-end">₱{{ number_format($assign->amount, 2) }}</td>
                            <td class="text-center">
                                <a href="{{ route('laboratory.details', $assign) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">
                                <i class="fa-solid fa-circle-info me-1"></i> No admissions today
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Earlier Admissions --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white border-0 d-flex align-items-center">
            <i class="fa-solid fa-clock-rotate-left me-2 text-secondary"></i>
            <h6 class="mb-0">Earlier Admissions</h6>
        </div>
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Assigned By</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($earlierAdmissions as $i => $assign)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $assign->patient->patient_first_name }} {{ $assign->patient->patient_last_name }}</strong><br>
                                <small class="text-muted">P-{{ $assign->patient->patient_id }}</small>
                            </td>
                            <td>{{ $assign->service->service_name }}</td>
                            <td><span class="badge bg-info text-white">{{ $assign->doctor->doctor_name }}</span></td>
                            <td class="text-end">₱{{ number_format($assign->amount, 2) }}</td>
                            <td class="text-center">
                                <a href="{{ route('laboratory.details', $assign) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">
                                <i class="fa-solid fa-circle-info me-1"></i> No earlier admissions
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center text-muted mb-4">
        <small>Laboratory Dashboard &copy; {{ date('Y') }}. All rights reserved.</small>
    </div>
@endsection

@push('scripts')
<script>
// Auto-refresh every 30 seconds to check for new services
setInterval(function() {
    // Only refresh if no modals are open
    if (!document.querySelector('.modal.show')) {
        window.location.reload();
    }
}, 30000);
</script>
@endpush
