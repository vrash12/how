{{-- resources/views/pharmacy/dashboard.blade.php --}}
@extends('layouts.pharmacy')

@section('content')
    {{-- Header --}}
    <div class="mb-4">
        <h4 class="fw-bold">💊 Pharmacy Dashboard</h4>
        <p class="text-muted">Monitor medication dispensing, manage pharmacy charges, and oversee inventory in real-time.</p>
    </div>

    {{-- Stats / Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-pills fa-2x text-primary me-4"></i>
                    <div>
                        <div class="text-muted small">Total Medications Today</div>
                        <h4 class="fw-semibold mb-0">{{ $totalProceduresToday ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-users fa-2x text-success me-4"></i>
                    <div>
                        <div class="text-muted small">Patients Served</div>
                        <h4 class="fw-semibold mb-0">{{ $patientsOperated ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-hourglass-half fa-2x text-warning me-4"></i>
                    <div>
                        <div class="text-muted small">Pending Medications</div>
                        <h4 class="fw-semibold mb-0">{{ $pendingProcedures ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6 col-md-6">
            <a href="{{ route('pharmacy.queue') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa-solid fa-list-check fa-2x text-info me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Medication Queue</h6>
                            <small class="text-muted">View and manage pending medications.</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-6 col-md-6">
            <a href="{{ route('pharmacy.history') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa-solid fa-book-medical fa-2x text-danger me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Transaction History</h6>
                            <small class="text-muted">Review completed transactions and records.</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Today's Charges --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white border-0 d-flex align-items-center">
            <i class="fa-solid fa-calendar-day me-2 text-secondary"></i>
            <h6 class="mb-0">Today's Charges</h6>
        </div>
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Medications</th>
                        <th>Status</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($todayCharges as $i => $charge)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $charge->patient->patient_first_name }} {{ $charge->patient->patient_last_name }}</strong><br>
                                <small class="text-muted">P-{{ $charge->patient->patient_id }}</small>
                            </td>
                            <td>{{ $charge->items->pluck('service.service_name')->join(', ') }}</td>
                            <td>
                                <span class="badge bg-{{ $charge->status === 'completed' ? 'success' : 'warning' }}">
                                    {{ ucfirst($charge->status) }}
                                </span>
                            </td>
                            <td class="text-end">₱{{ number_format($charge->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                <i class="fa-solid fa-circle-info me-1"></i> No charges today.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Dispensed Medications Today --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white border-0 d-flex align-items-center">
            <i class="fa-solid fa-calendar-check text-success me-2"></i>
            <h6 class="mb-0">Dispensed Medications Today</h6>
        </div>
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Medication</th>
                        <th>Quantity</th>
                        <th>Time Dispensed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dispensedToday as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $item->charge->patient->patient_first_name }} {{ $item->charge->patient->patient_last_name }}</strong><br>
                                <small class="text-muted">P-{{ $item->charge->patient->patient_id }}</small>
                            </td>
                            <td>{{ $item->service->service_name ?? 'N/A' }}</td>
                            <td>{{ $item->dispensed_quantity ?? $item->quantity }}</td>
                            <td>{{ $item->charge->dispensed_at ? $item->charge->dispensed_at->format('h:i A') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                <i class="fa-solid fa-circle-info me-1"></i> No medications dispensed today.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pending Charges Today --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white border-0 d-flex align-items-center">
            <i class="fa-solid fa-clock text-warning me-2"></i>
            <h6 class="mb-0">Pending Charges Today</h6>
        </div>
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Medications</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingToday as $i => $charge)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $charge->patient->patient_first_name }} {{ $charge->patient->patient_last_name }}</strong><br>
                                <small class="text-muted">P-{{ $charge->patient->patient_id }}</small>
                            </td>
                            <td>{{ $charge->items->pluck('service.service_name')->join(', ') }}</td>
                            <td class="text-end">₱{{ number_format($charge->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                <i class="fa-solid fa-circle-info me-1"></i> No pending charges today.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center text-muted mb-4">
        <small>Pharmacy Dashboard &copy; {{ date('Y') }}. All rights reserved.</small>
    </div>
@endsection

@push('scripts')
<script>
    // Auto-refresh every 30 seconds
    setInterval(function() {
        // Only refresh if no modals are open
        if (!document.querySelector('.modal.show')) {
            window.location.reload();
        }
    }, 30000);
</script>
@endpush
