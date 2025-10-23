@extends('layouts.nurse')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1">
                <i class="fas fa-pills me-2"></i>Request Medication for Patient
            </h3>
            <p class="text-muted mb-0">
                Creating medication request for <strong>{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</strong>
                <span class="badge bg-light text-dark ms-2">PID-{{ str_pad($patient->patient_id, 4, '0', STR_PAD_LEFT) }}</span>
            </p>
        </div>
        <a href="{{ route('nurse.patients.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Patients
        </a>
    </div>

    <div class="row">
        {{-- Main Form --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Request Details
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('nurse.request.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="patient_id" value="{{ $patient->patient_id }}">
                        <input type="hidden" name="doctor_id" value="{{ $doctor->doctor_id }}">

                        {{-- Replace the request type dropdown with a hidden input for medication only --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-pills me-1"></i>Request Type
                            </label>
                            <div class="form-control disabled bg-light">Medication Request</div>
                            <input type="hidden" name="type" value="medication">
                        </div>

                        {{-- Filter services to show only medications --}}
                        <div class="mb-4" id="services-container">
                            <label for="services" class="form-label fw-semibold">
                                <i class="fas fa-pills me-1"></i>Select Medications <span class="text-danger">*</span>
                            </label>
                            <select name="payload[services][]" id="services" class="form-select" multiple required>
                                @foreach($services as $service)
                                    @if($service->service_type === 'medication')
                                        <option value="{{ $service->service_id }}">
                                            {{ $service->service_name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple medications</small>
                        </div>

                        <div class="mb-4">
                            <label for="details" class="form-label fw-semibold">
                                <i class="fas fa-comment-alt me-1"></i>Additional Details <span class="text-danger">*</span>
                            </label>
                            <textarea name="payload[details]" id="details" class="form-control" rows="4" required 
                                      placeholder="Enter specific details, instructions, or notes for this request..."></textarea>
                            <small class="text-muted">Provide any relevant information to help the doctor make an informed decision.</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('nurse.patients.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-1"></i> Send Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            {{-- Patient Info --}}
            <div class="card shadow-sm border-0 rounded-3 mb-3">
                <div class="card-header bg-primary bg-opacity-10">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-user me-2"></i>Patient Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Name:</strong><br>
                        <span class="text-muted">{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Patient ID:</strong><br>
                        <span class="badge bg-primary">PID-{{ str_pad($patient->patient_id, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    @if($patient->sex)
                    <div class="mb-3">
                        <strong>Gender:</strong><br>
                        <span class="text-muted">{{ $patient->sex }}</span>
                    </div>
                    @endif
                    @if($patient->age)
                    <div class="mb-3">
                        <strong>Age:</strong><br>
                        <span class="text-muted">{{ $patient->age }} years old</span>
                    </div>
                    @endif
                    @if($patient->admissionDetail?->room?->room_number)
                    <div class="mb-0">
                        <strong>Room:</strong><br>
                        <span class="badge bg-success">{{ $patient->admissionDetail->room->room_number }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Doctor Info --}}
            <div class="card shadow-sm border-0 rounded-3 mb-3">
                <div class="card-header bg-success bg-opacity-10">
                    <h6 class="mb-0 text-success">
                        <i class="fas fa-user-md me-2"></i>Attending Doctor
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Doctor:</strong><br>
                        <span class="text-muted">{{ $doctor->doctor_name }}</span>
                    </div>
                    <div class="mb-0">
                        <strong>Specialization:</strong><br>
                        <span class="text-muted">{{ $doctor->specialization ?? 'General Practice' }}</span>
                    </div>
                </div>
            </div>

            
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('#services').select2({
        placeholder: "Select medications",
        width: '100%',
        allowClear: true
    });

    // Remove the filter services by type code since we only show medications
});

// SweetAlert for success
@if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '{{ session('success') }}',
        showConfirmButton: false,
        timer: 3000
    });
@endif

// SweetAlert for errors
@if($errors->any())
    Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: `{!! implode('<br>', $errors->all()) !!}`,
    });
@endif
</script>
@endpush

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--multiple {
        min-height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }
</style>
@endpush