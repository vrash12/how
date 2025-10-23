{{-- resources/views/patient/dashboard.blade.php --}}
@extends('layouts.patients')

@section('content')
<div class="container-fluid py-4">

  {{-- Greeting --}}
  <div class="mb-4 text-center text-md-start">
    <h1 class="h4 fw-bold text-primary">Welcome back, {{ $patient->patient_first_name }} 👋</h1>
    <p class="text-muted mb-0">Your bills hub — quick access to records and bills!</p>
  </div>

  {{-- Quick Info Stats --}}
  <div class="row g-3 mb-4">
    <div class="col col-md-6 col-lg-3">
      <div class="card stat-card shadow-sm h-100 border-0">
        <div class="card-body" style="text-align: left">
          <small class="d-block">Patient ID</small>
          <div style="font-size: 25px;">
            <b>{{ 'PID-' . str_pad($user->patient_id, 5, '0', STR_PAD_LEFT) }}</b>
          </div>
        </div>
      </div>
    </div>

    <div class="col col-md-6 col-lg-3">
      <div class="card stat-card shadow-sm h-100 border-0">
        <div class="card-body" style="text-align: left">
          <small class="d-block">Assigned Room</small>
          <div style="font-size: 25px;">
            <b>{{ $admission->room_number ?? '—' }}</b>
          </div>
        </div>
      </div>
    </div>

    <!-- Replace the Admitted Date card with a Days Admitted card -->
    <div class="col col-md-6 col-lg-3">
      <div class="card stat-card shadow-sm h-100 border-0">
        <div class="card-body" style="text-align: left">
          <small class="d-block">Days Admitted</small>
          <div style="font-size: 25px;">
            <b>
              @if($admission?->admission_date)
                {{ now()->diffInDays($admission->admission_date) + 1 }} {{ Str::plural('day', now()->diffInDays($admission->admission_date) + 1) }}
              @else
                —
              @endif
            </b>
          </div>
          <small class="text-muted">
            @if($admission?->admission_date)
              Since {{ $admission->admission_date->format('M d, Y') }}
            @endif
          </small>
        </div>
      </div>
    </div>

    <div class="col col-md-6 col-lg-3">
      <div class="card stat-card shadow-sm h-100 border-0">
        <div class="card-body" style="text-align: left">
          <small class="d-block">Amount Due</small>
          <div style="font-size: 25px;">
            <b>{{ '₱' . number_format($totals['balance'], 2) }}</b>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Assigned Doctors --}}
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card shadow-sm h-100 border-0">
        <div class="card-header fw-semibold">Assigned Doctor</div>
        <div class="card-body scrollable">
          @forelse($assignedDoctors as $doc)
            <p class="mb-2" style="border-bottom: 1px solid lightgray; padding-bottom: 5px;">
              Dr. {{ $doc->doctor_name ?? 'No Doctor' }}
              <small class="text-muted">({{ $doc->department->department_name ?? '—' }})</small>
            </p>
          @empty
            <p class="text-muted text-center">No doctors assigned.</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Pharmacy Charges --}}
    <div class="col-md-6">
      <div class="card shadow-sm h-100 border-0">
        <div class="card-header fw-semibold">Pharmacy Charges</div>
        <div class="card-body scrollable">
          @forelse($pharmacyCharges as $charge)
            <div class="mb-3">
              <div class="fw-semibold">Rx #{{ $charge->rx_number }}</div>
              <ul class="list-unstyled mb-1">
                @foreach($charge->items as $item)
                  <li>
                    {{ $item->service->service_name ?? '—' }} 
                    (₱{{ number_format($item->total, 2) }})
                  </li>
                @endforeach
              </ul>
              <small class="text-muted">
                Total: ₱{{ number_format($charge->items->sum('total'), 2) }} ·
                {{ $charge->created_at?->format('M d, Y h:i A') ?? '—' }}
              </small>
            </div>
          @empty
            <p class="text-muted text-center">No pharmacy charges yet.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- Hospital Services --}}
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="card shadow-sm h-100 border-0">
        <div class="card-header fw-semibold">Hospital Services</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Service Name</th>
                  <th>Service Type</th>
                  <th>Doctor</th>
                  <th>Status</th>
                  <th>Created At</th>
                  <th class="text-end">Amount</th>
                </tr>
              </thead>
              <tbody>
                @forelse($serviceAssignments as $i => $service)
                  <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $service->service->service_name ?? '—' }}</td>
                    <td>{{ ucfirst($service->service->service_type ?? '—') }}</td>
                    <td>{{ $service->doctor->doctor_name ?? '—' }}</td>
                    <td>
                      <span class="badge 
                        {{ $service->service_status === 'completed' ? 'bg-success' : ($service->service_status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                        {{ ucfirst($service->service_status) }}
                      </span>
                    </td>
                    <td>{{ $service->created_at?->diffForHumans() ?? '—' }}</td>
                    <td class="text-end">₱{{ number_format($service->amount, 2) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted">No hospital services found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
      <div class="text-center text-muted mb-4">
        <small>Patient Dashboard &copy; {{ date('Y') }}. All rights reserved.</small>
    </div>
</div>
@endsection
