@extends('layouts.doctor')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold hdng mb-1">👥 All Patients</h3>
        <p class="text-muted">View and manage patient orders for medications, lab tests, and operations</p>
    </div>

    {{-- Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Total Patients</h6>
                        <h4 class="fw-bold mb-0">{{ $patients->total() }}</h4>
                    </div>
                    <div class="ms-3 text-primary">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Active Patients</h6>
                        <h4 class="fw-bold mb-0">
                            {{ $patients->where('medication_finished', 0)->count() }}
                        </h4>
                    </div>
                    <div class="ms-3 text-success">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">With Orders</h6>
                        <h4 class="fw-bold mb-0">
                            {{ $patients->filter(function($p) { return $p->service_assignments_count + $p->prescriptions_count > 0; })->count() }}
                        </h4>
                    </div>
                    <div class="ms-3 text-warning">
                        <i class="fas fa-file-medical fa-2x"></i>
                    </div>
                </div>
            </div>
        </div> --}}
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Total Orders</h6>
                        <h4 class="fw-bold mb-0">
                            {{ $patients->sum(function($p) { return $p->service_assignments_count + $p->prescriptions_count; }) }}
                        </h4>
                    </div>
                    <div class="ms-3 text-info">
                        <i class="fas fa-notes-medical fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and Filter --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('doctor.orders.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}" 
                               class="form-control" placeholder="Search by patient name or ID...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Patients ({{ $patients->total() }})</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                            Active Patients ({{ $totalActiveCount ?? $patients->where('medication_finished', 0)->count() }})
                        </option>
                        <option value="finished" {{ request('status') == 'finished' ? 'selected' : '' }}>
                            Finished Patients ({{ $totalFinishedCount ?? $patients->where('medication_finished', 1)->count() }})
                        </option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                    @if(request('q') || request('status'))
                        <a href="{{ route('doctor.orders.index') }}" class="btn btn-outline-secondary">
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
                        <th>
                            <i class="fas fa-id-card me-1"></i> Patient ID
                        </th>
                        <th>
                            <i class="fas fa-user me-1"></i> Patient Name
                        </th>
                        <th>
                            <i class="fas fa-venus-mars me-1"></i> Gender
                        </th>
                        <th>
                            <i class="fas fa-file-medical me-1"></i> Order Count
                        </th>
                        <th>
                            <i class="fas fa-heart-pulse me-1"></i> Status
                        </th>
                        <th class="text-center">
                            <i class="fas fa-cogs me-1"></i> Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patients as $p)
                        <tr class="{{ $p->medication_finished ? 'table-light' : '' }}">
                            <td>
                                <span class="fw-bold text-primary">
                                    PID-{{ str_pad($p->patient_id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 32px; height: 32px;">
                                        <i class="fas fa-user text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $p->patient_first_name }} {{ $p->patient_last_name }}</div>
                                        <small class="text-muted">Patient</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($p->sex)
                                    <span class="badge {{ $p->sex === 'Male' ? 'bg-primary' : 'bg-pink' }} text-white">
                                        <i class="fas {{ $p->sex === 'Male' ? 'fa-mars' : 'fa-venus' }} me-1"></i>
                                        {{ $p->sex }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $totalOrders = $p->service_assignments_count + $p->prescriptions_count;
                                @endphp
                                @if($totalOrders > 0)
                                    <span class="badge bg-success text-white">
                                        <i class="fas fa-notes-medical me-1"></i>
                                        {{ $totalOrders }} {{ Str::plural('Order', $totalOrders) }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted">
                                        <i class="fas fa-minus me-1"></i>
                                        No Orders
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($p->medication_finished)
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-check-circle me-1"></i> Finished
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="fas fa-user-clock me-1"></i> Active
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('doctor.orders.show', $p->patient_id) }}"
                                   class="btn btn-outline-primary btn-sm me-1">
                                    <i class="fas fa-eye me-1"></i> View Orders
                                </a>
                                
                                {{-- @if(!$p->medication_finished)
                                    <a href="{{ route('doctor.patient.finished', $p->patient_id) }}"
                                       class="btn btn-outline-secondary btn-sm"
                                       onclick="return confirm('Are you sure you want to mark this patient as finished? This cannot be undone.')">
                                        <i class="fas fa-check me-1"></i> Mark as Finished
                                    </a> 
                                @endif--}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-1"></i> 
                                @if(request('q'))
                                    No patients found matching "{{ request('q') }}".
                                @elseif(request('status'))
                                    No {{ request('status') }} patients found.
                                @else
                                    No patients found.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination Footer --}}
        @if($patients->hasPages())
            <div class="card-footer bg-light border-top-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $patients->firstItem() ?? 0 }} to {{ $patients->lastItem() ?? 0 }} 
                        of {{ $patients->total() }} results
                    </div>
                    <div>
                        {{ $patients->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-pink {
        background-color: #e91e63 !important;
    }
    
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


