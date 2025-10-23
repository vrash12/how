@extends('layouts.nurse')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold hdng mb-1">📜 Request History </h3>
        <p class="text-muted">History of medication requests</p>
    </div>

    {{-- Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Pending Requests</h6>
                        <h4 class="fw-bold mb-0">{{ $pendingCount }}</h4>
                    </div>
                    <div class="ms-3 text-warning">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Approved Requests</h6>
                        <h4 class="fw-bold mb-0">{{ $approvedCount }}</h4>
                    </div>
                    <div class="ms-3 text-success">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Rejected Requests</h6>
                        <h4 class="fw-bold mb-0">{{ $rejectedCount }}</h4>
                    </div>
                    <div class="ms-3 text-danger">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Requests Table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Request ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Medications</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($requests as $request)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark">{{ $request->id }}</span>
                        </td>
                        <td>
                            <i class="fas fa-user text-primary me-1"></i>
                            {{ $request->patient->full_name 
                                ?? $request->patient->patient_first_name . ' ' . $request->patient->patient_last_name }}
                        </td>
                        <td>
                            <i class="fas fa-user-md text-secondary me-1"></i>
                            {{ $request->doctor->doctor_name ?? 'N/A' }}
                        </td>
                        <td class="text-muted">
                            {{ $request->created_at->format('M d, Y h:i A') }}
                        </td>
                        <td>
                            @php
                                $payload = json_decode($request->payload, true);
                                $services = $payload['services'] ?? [];
                            @endphp
                            
                            @foreach($services as $service)
                                <span class="badge rounded-pill bg-light text-dark border me-1 mb-1">
                                    @if(isset($service['service_name']))
                                        {{ $service['service_name'] }}
                                    @elseif(isset($service['name']))
                                        {{ $service['name'] }}
                                    @elseif(isset($service['id']))
                                        {{ \App\Models\HospitalService::find($service['id'])->service_name ?? 'Unknown' }}
                                    @else
                                        Unknown
                                    @endif
                                    @if(isset($service['quantity']))
                                        <span class="fw-semibold">x{{ $service['quantity'] }}</span>
                                    @endif
                                </span>
                            @endforeach
                        </td>
                        <td>
                            @if($request->status === 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($request->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($request->status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($request->status) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-1"></i> No medication requests yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
