{{-- resources/views/admission/dashboard.blade.php --}}
@extends('layouts.admission')

@section('content')
    {{-- Header --}}
    <div class="mb-4">
        <h4 class="fw-bold">🏥 Patient Admission Management</h4>
        <p class="text-muted">Monitor patient admissions, manage bed availability, and oversee patient assignments in real-time.</p>
    </div>

    {{-- Stats / Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-users fa-2x text-primary me-4"></i>
                    <div>
                        <div class="text-muted small">Admitted Patients</div>
                        <h4 class="fw-semibold mb-0">{{ $totalPatients }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-user-plus fa-2x text-success me-4"></i>
                    <div>
                        <div class="text-muted small">New Admissions</div>
                        <h4 class="fw-semibold mb-0">{{ $newAdmissions }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-bed fa-2x text-warning me-4"></i>
                    <div>
                        <div class="text-muted small">Available Beds</div>
                        <h4 class="fw-semibold mb-0">{{ $availableBeds }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6 col-md-6">
            <a href="{{ route('admission.patients.create') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa-solid fa-user-plus fa-2x text-info me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Admit New Patient</h6>
                            <small class="text-muted">Register and admit a new patient.</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        {{-- <div class="col-lg-6 col-md-6">
            <a href="{{ route('admission.rooms.index') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa-solid fa-door-open fa-2x text-danger me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Room Management</h6>
                            <small class="text-muted">View and manage room allocations.</small>
                        </div>
                    </div>
                </div>
            </a>
        </div> --}}
    </div>

    {{-- Recent Admissions --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-calendar-day me-2 text-secondary"></i>
                <h6 class="mb-0">Recent Admissions</h6>
            </div>
            <a href="{{ route('admission.patients.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Admit New
            </a>
        </div>
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Patient ID</th>
                        <th>Patient</th>
                        <th>Room/Ward</th>
                        <th>Diagnosis</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAdmissions as $adm)
                        <tr>
                            <td>{{ $adm->admission_date->format('Y-m-d') }}</td>
                            <td><small class="text-muted">P-{{ optional($adm->patient)->patient_id ?? '–' }}</small></td>
                            <td>
                                <strong>{{ optional($adm->patient)->patient_first_name }} {{ optional($adm->patient)->patient_last_name }}</strong>
                            </td>
                            <td>{{ optional($adm->room)->room_number ?? '–' }}</td>
                            <td>{{ optional(optional($adm->patient)->medicalDetail)->primary_reason ?? '—' }}</td>
                            <td class="text-center">
                                @if($adm->patient)
                                    <a href="{{ route('admission.patients.show', $adm->patient) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file-alt me-1"></i> Details
                                    </a>
                                @else
                                    &mdash;
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">
                                <i class="fa-solid fa-circle-info me-1"></i> No recent admissions
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center text-muted mb-4">
        <small>Admission Dashboard &copy; {{ date('Y') }}. All rights reserved.</small>
    </div>
@endsection

@push('scripts')
<script>
// Auto-refresh every 30 seconds to check for new admissions
setInterval(function() {
    // Only refresh if no modals are open
    if (!document.querySelector('.modal.show')) {
        window.location.reload();
    }
}, 30000);
</script>
@endpush
