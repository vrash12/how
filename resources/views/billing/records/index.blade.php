@extends('layouts.billing')

@section('content')
<div class="mb-4">
    <h3 class="fw-bold text-primary">💳 Billing Records</h3>
    <p class="text-muted">Manage patient billing, deposits, and outstanding balances.</p>
</div>

{{-- Search & Filter --}}
<form method="GET" action="{{ route('billing.records.index') }}" class="row g-2 mb-3 align-items-end">
    <div class="col-md-5">
        <div class="input-group">
            <span class="input-group-text bg-primary text-white"><i class="fas fa-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search by Name or PID..." value="{{ request('search') }}">
        </div>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="finished" {{ request('status') == 'finished' ? 'selected' : '' }}>Finished</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-filter"></i> Filter
        </button>
    </div>
    <div class="col-md-2">
        <a href="{{ route('billing.records.index') }}" class="btn btn-outline-secondary w-100">
            <i class="fas fa-times"></i> Reset
        </a>
    </div>
</form>

<div class="card shadow-sm border-0 rounded-3">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>PID</th>
                    <th>Patient Name</th>
                    <th>Total Bills (₱)</th>
                    <th>Outstanding (₱)</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($patients as $p)
                @php
                    $modalId = 'depositModal-'.$p['patient_id'];
                    // Fix: Use patient-specific grand total
                    $totalBills = $p['grandTotal'];
                    // Fix: Use patient-specific balance that's already calculated by the trait
                    $outstandingBalance = $p['balance'];
                @endphp
                <tr>
                    <td>
                        <span class="badge bg-info text-dark px-3 py-2 rounded-pill">
                            {{ "PID-" . str_pad($p['patient_id'], 6, '0', STR_PAD_LEFT) }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $p['patient_first_name'] }} {{ $p['patient_last_name'] }}</div>
                    </td>
                    <td>
                        <span class="text-success fw-bold">₱{{ number_format($totalBills, 2) }}</span>
                    </td>
                    <td>
                        <span class="text-danger fw-bold">₱{{ number_format($outstandingBalance, 2) }}</span>
                    </td>
                    <td class="text-center">
                        @if($outstandingBalance <= 0)
                            <form method="POST" action="{{ route('patient.statement', $p['patient_id']) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-success btn-sm rounded-pill px-4">
                                    <i class="fa fa-download"></i> Receipt
                                </button>
                            </form>
                        @else
                            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-4"
                                    data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                <i class="fa fa-wallet"></i> Deposit
                            </button>
                        @endif
                        <a href="{{ route('billing.records.show', $p['patient_id']) }}" class="btn btn-outline-primary btn-sm rounded-pill px-4 ms-2">
                            <i class="fa fa-eye"></i> Details
                        </a>
                    </td>
                </tr>

                @push('modals')
                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('billing.deposits.store') }}">
                                @csrf
                                <input type="hidden" name="patient_id" value="{{ $p['patient_id'] }}">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="{{ $modalId }}Label">
                                        <i class="fa fa-wallet me-2"></i> Deposit for {{ $p['patient_first_name'] }} {{ $p['patient_last_name'] }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Amount (₱)</label>
                                        <input type="number" name="amount" step="0.01" min="0" class="form-control" 
                                               value="{{ $outstandingBalance > 0 ? $outstandingBalance : '' }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Deposit Date</label>
                                        <input type="date" name="deposited_at" class="form-control" value="{{ now()->toDateString() }}" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success">Save Deposit</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endpush
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-info-circle me-1"></i> No active patients.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@stack('modals')
@endsection

@push('styles')
<style>
    body {
        background-color: #f9f9f9;
        font-family: 'Arial', sans-serif;
    }
    .table th, .table td {
        vertical-align: middle;
    }
    .table-hover tbody tr:hover {
        background-color: #f1f3f5;
    }
    .badge {
        font-size: 0.9rem;
    }
    .btn-outline-success, .btn-outline-primary {
        border-width: 2px;
    }
    .btn-outline-success:hover {
        background-color: #198754;
        color: #fff;
    }
    .btn-outline-primary:hover {
        background-color: #0d6efd;
        color: #fff;
    }
</style>
@endpush
