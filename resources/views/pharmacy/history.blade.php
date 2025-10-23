{{-- filepath: resources/views/pharmacy/dispensed.blade.php --}}
@extends('layouts.pharmacy')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold hdng mb-1">📜 Transaction Trail </h3>
        <p class="text-muted">History of dispensed prescriptions</p>
    </div>

    {{-- Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Completed Transactions</h6>
                        <h4 class="fw-bold mb-0">{{ $completedCharges->count() }}</h4>
                    </div>
                    <div class="ms-3 text-success">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Unified Table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>RX Number</th>
                        <th>Patient</th>
                        <th>Physician</th>
                        <th>Date</th>
                        <th>Medications</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                {{-- Fully Dispensed --}}
                @forelse($completedCharges as $charge)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark">{{ $charge->rx_number }}</span>
                        </td>
                        <td>
                            <i class="fas fa-user text-primary me-1"></i>
                            {{ $charge->patient->full_name 
                                ?? $charge->patient->patient_first_name . ' ' . $charge->patient->patient_last_name }}
                        </td>
                        <td>
                            <i class="fas fa-user-md text-secondary me-1"></i>
                            {{ $charge->prescribing_doctor }}
                        </td>
                        <td class="text-muted">
                            {{ $charge->dispensed_at?->format('M d, Y h:i A') ?? '-' }}
                        </td>
                        <td>
                            @foreach($charge->items as $item)
                                @if($item->status === 'cancelled')
                                    <span class="badge rounded-pill bg-danger text-white border me-1 mb-1">
                                        {{ $item->service->service_name ?? 'N/A' }} 
                                        <span class="fw-semibold">x{{ $item->quantity }}</span>
                                        <i class="fas fa-times ms-1"></i>
                                    </span>
                                @else
                                    <span class="badge rounded-pill bg-light text-dark border me-1 mb-1">
                                        {{ $item->service->service_name ?? 'N/A' }} 
                                        <span class="fw-semibold">x{{ $item->dispensed_quantity ?? $item->quantity }}</span>
                                        @if($item->status === 'dispensed')
                                            <i class="fas fa-check text-success ms-1"></i>
                                        @endif
                                    </span>
                                @endif
                            @endforeach
                        </td>
                        <td>
                            @php
                                $dispensedCount = $charge->items->where('status', 'dispensed')->count();
                                $cancelledCount = $charge->items->where('status', 'cancelled')->count();
                                $totalItems = $charge->items->count();
                            @endphp
                            
                            @if($dispensedCount === $totalItems)
                                <span class="badge bg-success">Fully Dispensed</span>
                            @elseif($cancelledCount === $totalItems)
                                <span class="badge bg-danger">Cancelled</span>
                            @elseif($dispensedCount > 0 && $cancelledCount > 0)
                                <span class="badge bg-warning text-dark">Partial</span>
                            @elseif($dispensedCount > 0)
                                <span class="badge bg-info">Partially Dispensed</span>
                            @else
                                <span class="badge bg-secondary">Completed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                @endforelse

                {{-- Partially Dispensed
                @foreach($partialCharges as $charge)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark">{{ $charge->rx_number }}</span>
                        </td>
                        <td>
                            <i class="fas fa-user text-primary me-1"></i>
                            {{ $charge->patient->full_name 
                                ?? $charge->patient->patient_first_name . ' ' . $charge->patient->patient_last_name }}
                        </td>
                        <td>
                            <i class="fas fa-user-md text-secondary me-1"></i>
                            {{ $charge->prescribing_doctor }}
                        </td>
                        <td class="text-muted">
                            {{ $charge->created_at->format('M d, Y h:i A') }}
                        </td>
                        <td>
                            @foreach($charge->items as $item)
                                <span class="badge rounded-pill bg-light text-dark border me-1 mb-1">
                                    {{ $item->service->service_name ?? 'N/A' }}
                                    <span class="fw-semibold">x{{ $item->quantity }}</span>
                                    @if($item->status === 'dispensed')
                                        <span class="badge bg-success ms-1">✓</span>
                                    @else
                                        <span class="badge bg-warning text-dark ms-1">Pending</span>
                                    @endif
                                </span>
                            @endforeach
                        </td>
                        <td><span class="badge bg-warning text-dark">Partial</span></td>
                    </tr>
                @endforeach --}}

                {{-- If none
                @if($completedCharges->isEmpty() && $partialCharges->isEmpty())
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-1"></i> No dispensed charges yet.
                        </td>
                    </tr>
                @endif --}}
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
