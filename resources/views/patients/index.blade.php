{{-- resources/views/patients/index.blade.php --}}
@extends('layouts.admission')

@section('content')
    {{-- Header & action button --}}
    <div class="row align-items-center justify-content-between mb-3">
        <div class="col">
            <h4 class="fw-bold">🏥 Patient Admission Management</h4>
            <p class="text-muted">Welcome to Admission! Manage admissions, beds, and doctor assignments.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admission.patients.create') }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-user-plus me-2"></i>Admit New
            </a>
        </div>
    </div>

    {{-- Enhanced Search Bar
    <div class="card mb-4 shadow-sm border-0 rounded-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admission.patients.index') }}">
                @csrf
                <div class="search-wrapper">
                    <div class="input-group">
                        <span class="input-group-text border-end-0 bg-transparent">
                            <i class="fa-solid fa-magnifying-glass text-primary"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" 
                               placeholder="Search by MRN, name, room or status..." value="{{ request('q') }}">
                        <button class="btn btn-primary search-btn" type="submit">
                            Search
                        </button>
                        @if(request('q'))
                            <a href="{{ route('admission.patients.index') }}" class="btn btn-outline-secondary ms-2">
                                <i class="fa-solid fa-times me-1"></i> Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div> --}}

    {{-- Success message --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Patients table --}}
    <div class="table-responsive">
        <table id="myTable" class="table align-middle table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>MRN</th>
                    <th>Name</th>
                    <th>Room</th>
                    <th>Admission Date</th>
                    <th>Assigned Doctor</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patients as $patient)
                    <tr class="patient-row">
                        <td>PID-{{ str_pad($patient->patient_id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-2">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div>{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</div>
                            </div>
                        </td>
                        <td>
                            @if($patient->admissionDetail?->room_number)
                                <span class="badge bg-info text-white">
                                    <i class="fa-solid fa-door-open me-1"></i>
                                    {{ $patient->admissionDetail?->room_number }}
                                </span>
                            @else
                                <span class="badge bg-light text-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            @if($patient->admissionDetail?->admission_date)
                                <span class="admission-date" data-bs-toggle="tooltip" 
                                      title="{{ $patient->admissionDetail->admission_date->format('F j, Y g:i A') }}">
                                    {{ $patient->admissionDetail->admission_date->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $patient->admissionDetail?->doctor?->doctor_name ?? '—' }}</td>
                        <td>
                            @php
                                $badge = match(strtolower($patient->status)) {
                                    'active'    => 'bg-success',
                                    'finished' => 'bg-primary',
                                    'pending'   => 'bg-warning',
                                    default     => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge text-white {{ $badge }}">
                                {{ ucfirst($patient->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admission.patients.show', $patient->patient_id) }}" class="btn btn-sm btn-primary action-btn">
                                <i class="fa-solid fa-eye me-2"></i>View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No patients found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
   $('#myTable').DataTable({
    dom: '<"top d-flex justify-content-between align-items-center mb-3"fB>rt' +
         '<"bottom d-flex justify-content-between align-items-center mt-3"i p>',
    buttons: [
      
    ]
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
  </script>
    {{-- Pagination --}}
    <div class="d-flex justify-content-end">
        {{ $patients->withQueryString()->links('pagination::bootstrap-5') }}
    </div>

    <style>
        .avatar-circle {
            width: 32px;
            height: 32px;
            background-color: #e3f2fd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .avatar-circle i {
            color: #0d6efd;
        }
        
        .admission-date {
            color: #4d80c5; /* Slightly less bright blue */
            cursor: pointer;
            font-weight: normal;
            transition: color 0.2s ease;
        }
        
        .admission-date:hover {
            color: #0d6efd; /* Brighter blue on hover */
        }
        
        .patient-row:hover {
            background-color: rgba(13, 110, 253, 0.04);
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        
        .badge {
            padding: 0.5em 0.75em;
        }
        
        /* Enhanced Search Styling */
        .search-wrapper {
            position: relative;
        }
        
        .input-group {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .input-group .form-control {
            height: 50px;
            font-size: 1rem;
            border-color: #e9ecef;
        }
        
        .input-group .form-control:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }
        
        .input-group-text {
            border-color: #e9ecef;
        }
        
        .search-btn {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
            transition: all 0.2s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        }
        
        .form-control::placeholder {
            color: #adb5bd;
            font-style: italic;
        }
    </style>
@endsection
