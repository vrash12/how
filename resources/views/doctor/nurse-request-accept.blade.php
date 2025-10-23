@extends('layouts.doctor')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1">
                <i class="fas fa-user-check me-2"></i>Approve Nurse Request
            </h3>
            <p class="text-muted mb-0">
                Processing request for <strong>{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</strong>
                <span class="badge bg-light text-dark ms-2">PID-{{ str_pad($patient->patient_id, 4, '0', STR_PAD_LEFT) }}</span>
            </p>
        </div>
        <a href="{{ route('doctor.nurse-requests') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Requests
        </a>
    </div>

    <div class="row">
        {{-- Main Form --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        @if($request->type === 'medication')
                            <i class="fas fa-pills text-info me-2"></i>Medication Request
                        @elseif($request->type === 'lab')
                            <i class="fas fa-flask text-primary me-2"></i>Laboratory Request
                        @elseif($request->type === 'operation')
                            <i class="fas fa-user-md text-danger me-2"></i>Operation Request
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('doctor.nurse-request.accept', $request->id) }}">
                        @csrf

                        @if($request->type === 'medication')
                            @foreach($payload['services'] ?? [] as $i => $serviceId)
                                @php
                                    $service = \App\Models\HospitalService::find($serviceId);
                                @endphp
                                <div class="card mb-3 border-info">
                                    <div class="card-header bg-info bg-opacity-10 border-info">
                                        <h6 class="mb-0 text-info">
                                            <i class="fas fa-capsules me-2"></i>{{ $service->service_name ?? 'Unknown Medication' }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="medications[{{ $i }}][medication_id]" value="{{ $serviceId }}">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">
                                                    <i class="fas fa-sort-numeric-up me-1"></i>Quantity <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" name="medications[{{ $i }}][quantity]" 
                                                       class="form-control" min="1" required placeholder="Enter quantity">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">
                                                    <i class="fas fa-calendar-alt me-1"></i>Duration <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" name="medications[{{ $i }}][duration]" 
                                                           class="form-control" min="1" required placeholder="Enter duration">
                                                    <select name="medications[{{ $i }}][duration_unit]" class="form-select">
                                                        <option value="days">Days</option>
                                                        <option value="weeks">Weeks</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">
                                                    <i class="fas fa-sticky-note me-1"></i>Instructions
                                                </label>
                                                <input type="text" name="medications[{{ $i }}][instructions]" 
                                                       class="form-control" placeholder="e.g., Take with food">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                        @elseif($request->type === 'lab')
                            @foreach($payload['services'] ?? [] as $i => $serviceId)
                                @php
                                    $service = \App\Models\HospitalService::find($serviceId);
                                @endphp
                                <div class="card mb-3 border-primary">
                                    <div class="card-header bg-primary bg-opacity-10 border-primary">
                                        <h6 class="mb-0 text-primary">
                                            <i class="fas fa-vial me-2"></i>{{ $service->service_name ?? 'Unknown Lab Test' }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="labs[{{ $i }}][service_id]" value="{{ $serviceId }}">
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-info-circle me-1"></i>
                                            This lab test will be scheduled and the patient will be notified.
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                            
                            <div class="card border-secondary">
                                <div class="card-header bg-secondary bg-opacity-10">
                                    <h6 class="mb-0">
                                        <i class="fas fa-notes-medical me-2"></i>Diagnosis/Notes
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <textarea name="diagnosis" class="form-control" rows="3" 
                                              placeholder="Enter diagnosis or additional notes for the laboratory..."></textarea>
                                </div>
                            </div>

                        @elseif($request->type === 'operation')
                            @foreach($payload['services'] ?? [] as $i => $serviceId)
                                @php
                                    $service = \App\Models\HospitalService::find($serviceId);
                                @endphp
                                <div class="card mb-3 border-danger">
                                    <div class="card-header bg-danger bg-opacity-10 border-danger">
                                        <h6 class="mb-0 text-danger">
                                            <i class="fas fa-procedures me-2"></i>{{ $service->service_name ?? 'Unknown Operation' }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="operations[{{ $i }}][service_id]" value="{{ $serviceId }}">
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-info-circle me-1"></i>
                                            This operation will be scheduled for the operating room.
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                            
                            <div class="card border-success">
                                <div class="card-header bg-success bg-opacity-10">
                                    <h6 class="mb-0 text-success">
                                        <i class="fas fa-dollar-sign me-2"></i>Professional Fee
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" name="professional_fee" class="form-control" 
                                               min="0" step="0.01" placeholder="0.00">
                                        <span class="input-group-text">.00</span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Enter the professional fee to be added to the operation cost.
                                    </small>
                                </div>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('doctor.nurse-requests') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check me-1"></i> Approve & Process
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Request Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Patient:</strong><br>
                        <span class="text-muted">{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Patient ID:</strong><br>
                        <span class="badge bg-primary">PID-{{ str_pad($patient->patient_id, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Request Type:</strong><br>
                        @if($request->type === 'medication')
                            <span class="badge bg-info">
                                <i class="fas fa-pills me-1"></i>Medication
                            </span>
                        @elseif($request->type === 'lab')
                            <span class="badge bg-primary">
                                <i class="fas fa-flask me-1"></i>Laboratory
                            </span>
                        @elseif($request->type === 'operation')
                            <span class="badge bg-danger">
                                <i class="fas fa-user-md me-1"></i>Operation
                            </span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <strong>Requested:</strong><br>
                        <span class="text-muted">{{ $request->created_at->diffForHumans() }}</span><br>
                        <small class="text-muted">{{ $request->created_at->format('M j, Y g:i A') }}</small>
                    </div>
                    <div class="mb-0">
                        <strong>Status:</strong><br>
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-clock me-1"></i>Pending Approval
                        </span>
                    </div>
                </div>
            </div>

            {{-- Help Card --}}
            <div class="card shadow-sm border-0 rounded-3 mt-3">
                <div class="card-header bg-info bg-opacity-10">
                    <h6 class="mb-0 text-info">
                        <i class="fas fa-question-circle me-2"></i>Need Help?
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        <strong>Medication:</strong> Specify quantity, duration, and instructions.
                    </p>
                    <p class="small text-muted mb-2">
                        <strong>Lab Tests:</strong> Add diagnosis or notes for the laboratory team.
                    </p>
                    <p class="small text-muted mb-0">
                        <strong>Operations:</strong> Set professional fee if applicable.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
@if (session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '{{ session('success') }}',
        showConfirmButton: false,
        timer: 3000
    });
@endif

@if ($errors->any())
    Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: `{!! implode('<br>', $errors->all()) !!}`,
    });
@endif

@if (session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '{{ session('error') }}',
    });
@endif
</script>
@endpush