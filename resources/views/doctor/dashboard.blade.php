{{-- resources/views/doctor/dashboard.blade.php --}}
@extends('layouts.doctor')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold hdng mb-1">🏥 Order Entry Dashboard</h3>
        <p class="text-muted">Create prescriptions and order services for patients efficiently</p>
    </div>

    {{-- Metrics / Quick Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Total Patients</h6>
                        <h4 class="fw-bold mb-0">{{ $patients->total() }}</h4>
                    </div>
                    <div class="ms-3 text-primary">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Recent Admissions</h6>
                        <h4 class="fw-bold mb-0">{{ $recentAdmissions->count() }}</h4>
                    </div>
                    <div class="ms-3 text-success">
                        <i class="fas fa-hospital-user fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Active Patients</h6>
                        <h4 class="fw-bold mb-0">{{ $patients->where('medication_finished', 0)->count() }}</h4>
                    </div>
                    <div class="ms-3 text-info">
                        <i class="fas fa-bed fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Current Page</h6>
                        <h4 class="fw-bold mb-0">{{ $patients->currentPage() }} / {{ $patients->lastPage() }}</h4>
                    </div>
                    <div class="ms-3 text-warning">
                        <i class="fas fa-list fa-2x"></i>
                    </div>
                </div>
            </div>
        </div> --}}
    </div>

    {{-- Recent Admissions --}}
    @if($recentAdmissions->count() > 0)
    <div class="card mb-4 shadow-sm border-0 rounded-3">
        <div class="card-header bg-gradient border-0">
            <div class="d-flex align-items-center">
                <i class="fas fa-clock me-2 text-primary"></i>
                <h6 class="mb-0 fw-semibold">Recently Admitted — {{ now()->format('M d, Y') }}</h6>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th><i class="fas fa-user me-1"></i> Patient Name</th>
                        <th><i class="fas fa-door-open me-1"></i> Room</th>
                        <th><i class="fas fa-clock me-1"></i> Admission Time</th>
                        <th class="text-center"><i class="fas fa-cogs me-1"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentAdmissions as $admit)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 32px; height: 32px;">
                                        <i class="fas fa-user text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $admit->patient->patient_first_name }} {{ $admit->patient->patient_last_name }}
                                        </div>
                                        <small class="text-muted">
                                            PID-{{ str_pad($admit->patient->patient_id, 4, '0', STR_PAD_LEFT) }}
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($admit->room?->room_number)
                                    <span class="badge bg-success text-white">
                                        <i class="fas fa-bed me-1"></i> {{ $admit->room->room_number }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted">
                                        <i class="fas fa-minus me-1"></i> No Room
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $admit->admission_date->diffForHumans() }}</div>
                                <small class="text-muted">{{ $admit->admission_date->format('h:i A') }}</small>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('doctor.orders.show', $admit->patient) }}" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Search --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" name="q" value="{{ $q }}" 
                               class="form-control" placeholder="Search by patient name or ID...">
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                    @if($q)
                        <a href="{{ route('doctor.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Patients List --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><i class="fas fa-id-card me-1"></i> Patient ID</th>
                        <th><i class="fas fa-user me-1"></i> Patient Name</th>
                        <th><i class="fas fa-venus-mars me-1"></i> Gender</th>
                        <th><i class="fas fa-door-open me-1"></i> Room</th>
                        <th><i class="fas fa-calendar me-1"></i> Date Admitted</th>
                        <th class="text-center"><i class="fas fa-cogs me-1"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                        <tr>
                            <td>
                                <span class="fw-bold text-primary">
                                    PID-{{ str_pad($patient->patient_id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 32px; height: 32px;">
                                        <i class="fas fa-user text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $patient->patient_first_name }} {{ $patient->patient_last_name }}
                                        </div>
                                        <small class="text-muted">Patient</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($patient->sex)
                                    <span class="badge {{ $patient->sex === 'Male' ? 'bg-primary' : 'bg-pink' }} text-white">
                                        <i class="fas {{ $patient->sex === 'Male' ? 'fa-mars' : 'fa-venus' }} me-1"></i>
                                        {{ $patient->sex }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($patient->admissionDetail?->room?->room_number)
                                    <span class="badge bg-success text-white">
                                        <i class="fas fa-bed me-1"></i> 
                                        {{ $patient->admissionDetail->room->room_number }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted">
                                        <i class="fas fa-minus me-1"></i> No Room
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($patient->admissionDetail?->admission_date)
                                    <div class="fw-semibold">
                                        {{ $patient->admissionDetail->admission_date->diffForHumans() }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $patient->admissionDetail->admission_date->format('M j, Y') }}
                                    </small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('doctor.orders.show', $patient) }}" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-file-medical me-1"></i> Order Entry
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-1"></i> 
                                @if($q)
                                    No patients found matching "{{ $q }}".
                                @else
                                    No patients found.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        @if($patients->hasPages())
            <div class="card-footer bg-light border-top-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $patients->firstItem() ?? 0 }} to {{ $patients->lastItem() ?? 0 }} 
                        of {{ $patients->total() }} results
                    </div>
                    <div>
                        {{ $patients->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-pink {
        background-color: #e91e63 !important;
    }
    
    .table th {
        background: #f8fafc;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .btn-outline-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .card {
        transition: all 0.2s ease;
    }
    
    .input-group-text {
        border-right: none;
        background: transparent;
    }
    
    .form-control:focus {
        border-left: none;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .bg-gradient {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
</style>
@endpush
