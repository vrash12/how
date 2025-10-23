{{-- filepath: c:\Users\Sam\Desktop\PatientCare-Updated-main - Final\resources\views\doctor\nurse-refills.blade.php --}}
{{-- Nurse Refills Blade --}}
@extends('layouts.doctor')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold hdng mb-1">🩺 Pending Nurse Requests</h3>
        <p class="text-muted">Review and approve nurse requests for medications, lab tests, and operations</p>
    </div>

    {{-- Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Total Pending</h6>
                        <h4 class="fw-bold mb-0">
                            {{ $requests->where('status', 'pending')->count() }}
                        </h4>
                    </div>
                    <div class="ms-3 text-warning">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Medication Requests</h6>
                        <h4 class="fw-bold mb-0">
                            {{ $requests->where('type', 'medication')->where('status', 'pending')->count() }}
                        </h4>
                    </div>
                    <div class="ms-3 text-info">
                        <i class="fas fa-pills fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Lab Requests</h6>
                        <h4 class="fw-bold mb-0">
                            {{ $requests->where('type', 'lab')->where('status', 'pending')->count() }}
                        </h4>
                    </div>
                    <div class="ms-3 text-primary">
                        <i class="fas fa-flask fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Operation Requests</h6>
                        <h4 class="fw-bold mb-0">
                            {{ $requests->where('type', 'operation')->where('status', 'pending')->count() }}
                        </h4>
                    </div>
                    <div class="ms-3 text-danger">
                        <i class="fas fa-user-md fa-2x"></i>
                    </div>
                </div>
            </div>
        </div> --}}
    </div>

    {{-- Requests Table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0" id="nurse-requests-table">
                <thead class="table-light">
                    <tr>
                        <th>Date Requested</th>
                        <th>Patient</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $request->created_at->diffForHumans() }}</div>
                                <small class="text-muted">{{ $request->created_at->format('M j, Y g:i A') }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">
                                    {{ $request->patient->patient_first_name }} {{ $request->patient->patient_last_name }}
                                </div>
                                <small class="text-muted">
                                    PID-{{ str_pad($request->patient->patient_id, 4, '0', STR_PAD_LEFT) }}
                                </small>
                            </td>
                            <td>
                                @if($request->type === 'medication')
                                    <span class="badge bg-info text-white">
                                        <i class="fas fa-pills me-1"></i> {{ ucfirst($request->type) }}
                                    </span>
                                @elseif($request->type === 'lab')
                                    <span class="badge bg-primary text-white">
                                        <i class="fas fa-flask me-1"></i> {{ ucfirst($request->type) }}
                                    </span>
                                @elseif($request->type === 'operation')
                                    <span class="badge bg-danger text-white">
                                        <i class="fas fa-user-md me-1"></i> {{ ucfirst($request->type) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $payload = json_decode($request->payload, true);
                                @endphp
                                @if(is_array($payload) && isset($payload['services']))
                                    @foreach($payload['services'] as $serviceId)
                                        <span class="badge rounded-pill bg-light text-dark border me-1 mb-1">
                                            {{ \App\Models\HospitalService::find($serviceId)->service_name ?? 'Unknown' }}
                                        </span>
                                    @endforeach
                                    @if(isset($payload['details']))
                                        <div class="mt-1">
                                            <strong>Details:</strong> {{ $payload['details'] }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">{{ $request->payload }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $request->status === 'pending' ? 'bg-warning text-dark' : ($request->status === 'rejected' ? 'bg-danger text-white' : 'bg-success text-white') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($request->status === 'pending')
                                    <a href="{{ route('doctor.nurse-request.accept.form', $request->id) }}" class="btn btn-sm btn-success">
                                        <i class="fa fa-check me-1"></i> Accept
                                    </a>
                                    <form action="{{ route('doctor.nurse-request.reject', $request->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger reject-btn">
                                            <i class="fa fa-times me-1"></i> Reject
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted">{{ ucfirst($request->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-1"></i> No pending nurse requests.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rejectButtons = document.querySelectorAll('.reject-btn');

    rejectButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = btn.closest('form');
            Swal.fire({
                title: "Are you sure?",
                text: "This will reject the nurse request.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#00529A",
                confirmButtonText: "Yes, reject it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});

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
        title: 'Oops...',
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