{{-- resources/views/nurse/dashboard.blade.php --}}
@extends('layouts.nurse')

@section('content')
<div class="container-fluid p-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold">🏥 Nursing Dashboard</h4>
            <p class="text-muted">Overview of patient care and medication requests</p>
        </div>
        <div>
            <a href="{{ route('nurse.patients.index') }}" class="btn btn-primary">
                <i class="fas fa-user-injured me-2"></i> View All Patients
            </a>
        </div>
    </div>
    
    {{-- Stats Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card ">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-0">Active Patients</h6>
                            <h2 class="fw-bold mb-0">{{ $activePatients }}</h2>
                        </div>
                        <div class="avatar-md rounded-circle bg-primary bg-opacity-10 p-3 flex-shrink-0">
                            <i class="fas fa-hospital-user fa-2x text-primary"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="d-flex">
                            {{-- <div class="me-2 text-muted">
                                @if(isset($patientsByGender['Male']))
                                    <span class="badge bg-info">{{ $patientsByGender['Male'] }} Male</span>
                                @endif
                                
                                @if(isset($patientsByGender['Female']))
                                    <span class="badge bg-danger">{{ $patientsByGender['Female'] }} Female</span>
                                @endif
                            </div> --}}
                            {{-- <a href="{{ route('nurse.patients.index') }}" class="text-decoration-none ms-auto">
                                Details <i class="fas fa-chevron-right fa-xs"></i>
                            </a> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-0">Today's Med Requests</h6>
                            <h2 class="fw-bold mb-0">{{ $todayMedicationRequests }}</h2>
                        </div>
                        <div class="avatar-md rounded-circle bg-success bg-opacity-10 p-3 flex-shrink-0">
                            <i class="fas fa-pills fa-2x text-success"></i>
                        </div>
                    </div>
                    {{-- <div class="mt-2">
                        <div class="progress" style="height:6px">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-0">Pending Requests</h6>
                            <h2 class="fw-bold mb-0">{{ $pendingRequests }}</h2>
                        </div>
                        <div class="avatar-md rounded-circle bg-warning bg-opacity-10 p-3 flex-shrink-0">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                    </div>
                    {{-- <div class="mt-2">
                        <div class="d-flex">
                            <div class="me-2">
                                <span class="text-success">{{ $approvedRequests }} <i class="fas fa-check-circle"></i></span>
                                <span class="text-danger ms-2">{{ $rejectedRequests }} <i class="fas fa-times-circle"></i></span>
                            </div>
                            <div class="ms-auto">
                                <span class="text-muted">Approved/Rejected</span>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
        
        {{-- <div class="col-xl-3 col-md-6">
            <div class="card border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-0">Weekly Requests</h6>
                            <h2 class="fw-bold mb-0">{{ array_sum($formattedRequestsByDay) }}</h2>
                        </div>
                        <div class="avatar-md rounded-circle bg-info bg-opacity-10 p-3 flex-shrink-0">
                            <i class="fas fa-chart-line fa-2x text-info"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between">
                            @foreach($formattedRequestsByDay as $day => $count)
                                <div class="text-center">
                                    <div class="text-muted small">{{ $day }}</div>
                                    <div class="fw-bold">{{ $count }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}
    </div>
    
    {{-- Recent Activity --}}
    <div class="row g-4">
        {{-- Recent Patients --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-transparent">
                    <h5 class="card-title">Recent Patients</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Patient</th>
                                    <th scope="col">Doctor</th>
                                    <th scope="col">Room</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPatients as $patient)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2 bg-soft-primary rounded text-center">
                                                <span class="avatar-title rounded text-primary">
                                                    {{ strtoupper(substr($patient->patient_first_name, 0, 1) . substr($patient->patient_last_name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 font-size-14">{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</h6>
                                                <small class="text-muted">ID: {{ $patient->patient_id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($patient->admissionDetail && $patient->admissionDetail->doctor)
                                        <span>Dr. {{ $patient->admissionDetail->doctor->doctor_name }}</span>
                                        @else
                                        <span class="text-danger">No Doctor</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($patient->admissionDetail && $patient->admissionDetail->room)
                                        <span class="badge bg-soft-info text-info">{{ $patient->admissionDetail->room->room_number }}</span>
                                        @else
                                        <span class="text-warning">Not Assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('nurse.request.create', $patient) }}" class="btn btn-sm btn-light">
                                            <i class="fas fa-file-medical"></i> Request
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">No recent patients found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-transparent text-center">
                    <a href="{{ route('nurse.patients.index') }}" class="btn btn-link">View All Patients</a>
                </div>
            </div>
        </div>
        
        {{-- Recent Medication Requests --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-transparent">
                    <h5 class="card-title">Recent Medication Requests</h5>
                </div>
                <div class="card-body">
                    @forelse($recentRequests as $request)
                    <div class="d-flex mb-3 p-2 rounded @if($request->status == 'pending') bg-warning bg-opacity-10 @elseif($request->status == 'approved') bg-success bg-opacity-10 @else bg-danger bg-opacity-10 @endif">
                        <div class="flex-shrink-0">
                            @if($request->status == 'pending')
                                <div class="avatar-sm bg-warning bg-opacity-10 text-center rounded p-2">
                                    <i class="fas fa-hourglass-half text-warning"></i>
                                </div>
                            @elseif($request->status == 'approved')
                                <div class="avatar-sm bg-success bg-opacity-10 text-center rounded p-2">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                            @else
                                <div class="avatar-sm bg-danger bg-opacity-10 text-center rounded p-2">
                                    <i class="fas fa-times-circle text-danger"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">
                                    @if($request->patient)
                                        {{ $request->patient->patient_first_name }} {{ $request->patient->patient_last_name }}
                                    @else
                                        Unknown Patient
                                    @endif
                                </h6>
                                <small class="text-muted">{{ $request->created_at->diffForHumans() }}</small>
                            </div>
                            <div class="text-muted small">
                                Request to Dr. {{ $request->doctor ? $request->doctor->doctor_name : 'Unknown' }}
                            </div>
                            <div>
                                <span class="badge @if($request->status == 'pending') bg-warning @elseif($request->status == 'approved') bg-success @else bg-danger @endif">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486754.png" alt="No Requests" style="width: 100px; opacity: 0.5">
                        <p class="mt-3 text-muted">No medication requests found</p>
                        <a href="{{ route('nurse.patients.index') }}" class="btn btn-sm btn-primary mt-2">Create Request</a>
                    </div>
                    @endforelse
                </div>
                <div class="card-footer bg-transparent text-center">
                    <a href="#" class="btn btn-link">View All Requests</a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add any dashboard-specific JavaScript here
    });
</script>
@endpush
@endsection
