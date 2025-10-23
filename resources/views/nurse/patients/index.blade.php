{{-- filepath: c:\Users\Sam\Desktop\PatientCare-Updated-main - Final\resources\views\nurse\index.blade.php --}}
@extends('layouts.nurse')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold hdng mb-1">👩‍⚕️ Patient Requests</h3>
        <p class="text-muted">Select a patient to send medication, lab, or operation requests to their doctor</p>
    </div>

    {{-- Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Active Patients</h6>
                        <h4 class="fw-bold mb-0">{{ $patients->count() }}</h4>
                    </div>
                    <div class="ms-3 text-primary">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">With Doctors</h6>
                        <h4 class="fw-bold mb-0">{{ $patients->filter(function($p) { return $p->admissionDetail && $p->admissionDetail->doctor_id; })->count() }}</h4>
                    </div>
                    <div class="ms-3 text-success">
                        <i class="fas fa-user-md fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Admitted Today</h6>
                        <h4 class="fw-bold mb-0">{{ $patients->filter(function($p) { return $p->admissionDetail && $p->admissionDetail->admission_date >= today(); })->count() }}</h4>
                    </div>
                    <div class="ms-3 text-info">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">In Rooms</h6>
                        <h4 class="fw-bold mb-0">{{ $patients->filter(function($p) { return $p->admissionDetail && $p->admissionDetail->room_id; })->count() }}</h4>
                    </div>
                    <div class="ms-3 text-warning">
                        <i class="fas fa-bed fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('nurse.patients.index') }}" class="row g-2 align-items-center">
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}" 
                               class="form-control" placeholder="Search by patient name, ID, or doctor...">
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                    @if(request('q'))
                        <a href="{{ route('nurse.patients.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Patients Table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><i class="fas fa-id-card me-1"></i> Patient ID</th>
                        <th><i class="fas fa-user me-1"></i> Patient Name</th>
                        <th><i class="fas fa-user-md me-1"></i> Attending Doctor</th>
                        <th><i class="fas fa-door-open me-1"></i> Room</th>
                        <th><i class="fas fa-calendar me-1"></i> Admitted</th>
                        <th class="text-center"><i class="fas fa-paper-plane me-1"></i> Actions</th>
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
                                        <div class="fw-semibold">{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</div>
                                        <small class="text-muted">
                                            @if($patient->sex)
                                                {{ $patient->sex }}
                                            @endif
                                            @if($patient->age)
                                                • {{ $patient->age }} years old
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($patient->admissionDetail?->doctor)
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" 
                                             style="width: 28px; height: 28px;">
                                            <i class="fas fa-user-md text-success" style="font-size: 12px;"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $patient->admissionDetail->doctor->doctor_name }}</div>
                                            <small class="text-muted">Attending Physician</small>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge bg-light text-muted">
                                        <i class="fas fa-minus me-1"></i> No Doctor Assigned
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($patient->admissionDetail?->room?->room_number)
                                    <span class="badge bg-success text-white">
                                        <i class="fas fa-bed me-1"></i> {{ $patient->admissionDetail->room->room_number }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted">
                                        <i class="fas fa-minus me-1"></i> No Room
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($patient->admissionDetail?->admission_date)
                                    <div class="fw-semibold">{{ $patient->admissionDetail->admission_date->diffForHumans() }}</div>
                                    <small class="text-muted">{{ $patient->admissionDetail->admission_date->format('M j, Y g:i A') }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($patient->admissionDetail?->doctor)
                                    <a href="{{ route('nurse.request.create', $patient) }}" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-pills me-1"></i> Request Medication
                                    </a>
                                @else
                                    <span class="btn btn-outline-secondary btn-sm disabled">
                                        <i class="fas fa-ban me-1"></i> No Doctor
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-1"></i> 
                                @if(request('q'))
                                    No patients found matching "{{ request('q') }}".
                                @else
                                    No active patients found.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
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
</style>
@endpush