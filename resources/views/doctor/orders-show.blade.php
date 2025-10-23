<!-- filepath: resources/views/doctor/orders-show.blade.php -->
@extends('layouts.doctor')

@section('content')
<div class="container-fluid">
    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        {{-- Back Button --}}
        <a href="{{ route('doctor.orders.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Patient List
        </a>

        
    </div>

    {{-- Patient Info Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-user-md fa-3x text-primary"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-primary mb-1">
                            Orders for {{ $patient->patient_first_name }} {{ $patient->patient_last_name }}
                        </h3>
                        <p class="text-muted mb-0 ps-3"><b>PID-{{ str_pad($patient->patient_id, 4, '0', STR_PAD_LEFT) }}</b></p>
                    </div>
                </div>
               {{-- filepath: c:\Users\Sam\Desktop\PatientCare-Updated-main - Final\resources\views\doctor\orders-show.blade.php --}}
                <div>
                    @if($patient->medication_finished == null)
                        <form id="completePatientForm" action="{{ route('doctor.patientFinished', $patient->patient_id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-outline-success ms-3" id="completePatientBtn">
                                <i class="fa-solid fa-check"></i> Complete
                            </button>
                        </form>
                    @else
                        <span class="btn btn-success ms-3">
                            <i class="fa-solid fa-check"></i> Completed
                        </span>
                    @endif
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Age:</strong> {{ \Carbon\Carbon::parse($patient->patient_birthday)->age }} years</p>
                    <p class="mb-1"><strong>Birthday:</strong> {{ $patient->patient_birthday->format('F d, Y') }}</p>
                    <p class="mb-1"><strong>Civil Status:</strong> {{ ucfirst($patient->civil_status) }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Contact:</strong> {{ $patient->phone_number ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Email:</strong> {{ $patient->email ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Address:</strong> {{ $patient->address ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Pharmacy Charges --}}
    <div class="card shadow-sm mb-4 col">
        <div class="card-header bg-light fw-bold d-flex align-items-center">
            <i class="fas fa-prescription-bottle-alt me-2 text-primary"></i>
            <span>Pharmacy Charges</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Rx Number</th>
                        <th>Medication</th>
                        <th>Status</th>
                        <th>Quantity Assigned</th>
                        <th>Quantity Dispensed</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pharmacyCharges as $charge)
                    @foreach($charge->items as $item)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $charge->rx_number }}</span>
                            </td>
                            <td>{{ $item->service->service_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $item->status === 'dispensed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->dispensed_quantity }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-1"></i> No pharmacy charges found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Service Orders --}}
    <div class="col mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-light fw-bold d-flex align-items-center">
                <i class="fas fa-flask me-2 text-primary"></i>
                <span>Service Orders</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Added at</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($serviceOrders as $order)
                        <tr>
                            <td>
                                @if($order->mode === 'lab')
                                    <span class="badge bg-primary">Laboratory</span>
                                @elseif($order->mode === 'imaging')
                                    <span class="badge bg-info">Imaging</span>
                                @elseif($order->mode === 'or')
                                    <span class="badge bg-danger">Operation</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($order->mode ?? 'N/A') }}</span>
                                @endif
                            </td>
                            <td>{{ $order->service->service_name ?? 'Service' }}</td>
                            <td>
                                <span class="badge 
                                    {{ $order->service_status === 'completed' ? 'bg-success' : 
                                    ($order->service_status === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                    {{ ucfirst($order->service_status) }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $order->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No service orders.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-4">
        {{-- Back Button --}}
        {{-- Add Order Button --}}
        @if(!$patient->medication_finished)
            <a href="{{ route('doctor.order', $patient->patient_id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Order
            </a>

        @else
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-ban"></i> Orders Disabled (Completed)
            </button>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-header {
        font-size: 1.1rem;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
    }
    .table th, .table td { vertical-align: middle; }
    .badge.bg-info, .badge.bg-success, .badge.bg-warning, .badge.bg-primary, .badge.bg-danger, .badge.bg-secondary {
        font-size: 0.9rem;
        padding: 0.4em 0.6em;
    }
    .btn i { margin-right: 4px; }
    .table thead th { background: #f8fafc; }
    .table-hover tbody tr:hover { background: #f1f3f5; }
    .text-muted { font-size: 0.85rem; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('completePatientBtn')?.addEventListener('click', function() {
        Swal.fire({
            title: 'Mark patient as completed?',
            text: "This action is not reversible! Once marked as completed, you won't be able to add new orders for this patient.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, mark as completed',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('completePatientForm').submit();
            }
        });
    });
    
    // Keep any existing script code below
    document.getElementById('markAsCompletedBtn')?.addEventListener('click', function () {
        const url = this.getAttribute('data-url');
        Swal.fire({
            title: 'Are you sure?',
            text: "This action is irreversible. You will no longer be able to order or charge this patient.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, mark as completed'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
</script>
@endpush