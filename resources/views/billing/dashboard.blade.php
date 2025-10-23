{{-- resources/views/billing/dashboard.blade.php --}}
@extends('layouts.billing')

@section('content')
<div class="container-fluid min-vh-100 p-4 d-flex flex-column" style="background-color: #fafafa;">

  <!-- Header -->
  <header class="mb-3 d-flex justify-content-between align-items-center">
    <div>
      <h4 class="hdng">Patient Billing Management</h4>
      <p class="text-muted mb-0">Manage patient billing records and disputes.</p>
    </div>
    <div>
      <a href="{{ route('billing.records.index') }}" class="btn btn-outline-primary">
        <i class="fas fa-list me-1"></i> All Records
      </a>
    </div>
  </header>

  <!-- Metrics -->
  <div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
      <div class="card border h-100">
        <div class="card-body d-flex align-items-center">
          <div class="me-3">
            <div class="bg-success-subtle p-3 rounded-circle text-center">
              <i class="fa-solid fa-chart-simple fa-2x text-success"></i>
            </div>
          </div>
          <div>
            <div class="text-muted small">Total Gross</div>
            <h5 class="mb-0">₱{{ number_format($totalRevenue, 2) }}</h5>
          </div>
        </div>
        {{-- <div class="card-footer bg-success-subtle p-2 text-center">
          <small class="text-success">All time revenue from all services</small>
        </div> --}}
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card border h-100">
        <div class="card-body d-flex align-items-center">
          <div class="me-3">
            <div class="bg-warning-subtle p-3 rounded-circle text-center">
              <i class="fa-solid fa-peso-sign fa-2x text-warning"></i>
            </div>
          </div>
          <div>
            <div class="text-muted small">Outstanding Balance</div>
            <h5 class="mb-0">₱{{ number_format($outstandingBalance, 2) }}</h5>
          </div>
        </div>
        {{-- <div class="card-footer bg-warning-subtle p-2 text-center">
          <small class="text-warning">Amount still due from patients</small>
        </div> --}}
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card border h-100">
        <div class="card-body d-flex align-items-center">
          <div class="me-3">
            <div class="bg-info-subtle p-3 rounded-circle text-center">
              <i class="fa-solid fa-bed fa-2x text-info"></i>
            </div>
          </div>
          <div>
            <div class="text-muted small">Active Patients</div>
            <h5 class="mb-0">{{ $activePatientCount }}</h5>
          </div>
        </div>
        {{-- <div class="card-footer bg-info-subtle p-2 text-center">
          <small class="text-info">Currently admitted patients</small>
        </div> --}}
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card border h-100">
        <div class="card-body d-flex align-items-center">
          <div class="me-3">
            <div class="bg-danger-subtle p-3 rounded-circle text-center">
              <i class="fa-solid fa-person-circle-question fa-2x text-danger"></i>
            </div>
          </div>
          <div>
            <div class="text-muted small">Pending Disputes</div>
            <h5 class="mb-0">{{ $pendingDisputes }}</h5>
          </div>
        </div>
        {{-- <div class="card-footer bg-danger-subtle p-2 text-center">
          <small class="text-danger">Billing disputes requiring attention</small>
        </div> --}}
      </div>
    </div>
  </div>

  <!-- Pharmacy Charges -->
  <div class="card mb-5">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <h5 class="fw-semibold mb-0">
        <i class="fa-solid fa-prescription-bottle-medical me-2 text-primary"></i>
        Completed Pharmacy Charges
      </h5>
      {{-- <a href="{{ route('billing.pharmacy.index') }}" class="btn btn-sm btn-outline-primary">View All</a> --}}
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Patient</th>
              <th>Medication</th>
              <th>Quantity</th>
              <th>Date</th>
              <th>Status</th>
              <th class="text-end">Amount</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pharmacyCharges ?? [] as $charge)
              <tr>
                <td>{{ $charge['patient_first_name'] ?? 'N/A' }} {{ $charge['patient_last_name'] ?? '' }}</td>
                <td>{{ $charge['medication_name'] ?? 'N/A' }}</td>
                <td>{{ $charge['dispensed_quantity'] ?? '0' }}</td>
                <td>{{ $charge['date'] ? $charge['date']->format('M d, Y') : 'N/A' }}</td>
                <td>
                  <span class="badge bg-success">
                    {{ ucfirst($charge['status'] ?? 'Completed') }}
                  </span>
                </td>
                <td class="text-end">₱{{ number_format($charge['total'] ?? 0, 2) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-3">
                  No completed pharmacy charges found.
                </td>
              </tr>
            @endforelse
          </tbody>
          {{-- <tfoot class="table-light fw-bold">
            <tr>
              <td colspan="5" class="text-end">Total Pharmacy Charges:</td>
              <td class="text-end">₱{{ number_format($pharmacyTotal, 2) }}</td>
            </tr>
          </tfoot> --}}
        </table>
      </div>
    </div>
  </div>

  <!-- Operating Room Charges -->
  <div class="card mb-5">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <h5 class="fw-semibold mb-0">
        <i class="fa-solid fa-hospital me-2 text-primary"></i>
        Operating Room Charges
      </h5>
      {{-- <a href="{{ route('billing.services', ['type' => 'or']) }}" class="btn btn-sm btn-outline-primary">View All</a> --}}
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Patient</th>
              <th>Procedure</th>
              <th>Doctor</th>
              <th>Date</th>
              <th>Status</th>
              <th class="text-end">Amount</th>
            </tr>
          </thead>
          <tbody>
            @forelse($orCharges ?? [] as $charge)
              <tr>
                <td>{{ $charge['patient_first_name'] ?? 'N/A' }} {{ $charge['patient_last_name'] ?? '' }}</td>
                <td>{{ $charge['procedure_name'] ?? 'N/A' }}</td>
                <td>{{ $charge['doctor_name'] ?? 'N/A' }}</td>
                <td>{{ $charge['date'] ? $charge['date']->format('M d, Y') : 'N/A' }}</td>
                <td>
                  <span class="badge bg-{{ $charge['status'] == 'completed' ? 'success' : 'warning' }}">
                    {{ ucfirst($charge['status'] ?? 'Pending') }}
                  </span>
                </td>
                <td class="text-end">₱{{ number_format($charge['amount'] ?? 0, 2) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-3">
                  No operating room charges found.
                </td>
              </tr>
            @endforelse
          </tbody>
          {{-- <tfoot class="table-light fw-bold">
            <tr>
              <td colspan="5" class="text-end">Total OR Charges:</td>
              <td class="text-end">₱{{ number_format($orTotal, 2) }}</td>
            </tr>
          </tfoot> --}}
        </table>
      </div>
    </div>
  </div>

  <!-- Laboratory Charges -->
  <div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <h5 class="fw-semibold mb-0">
        <i class="fa-solid fa-flask me-2 text-primary"></i>
        Laboratory Charges
      </h5>
      {{-- <a href="{{ route('billing.services', ['type' => 'lab']) }}" class="btn btn-sm btn-outline-primary">View All</a> --}}
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Patient</th>
              <th>Test</th>
              <th>Doctor</th>
              <th>Date</th>
              <th>Status</th>
              <th class="text-end">Amount</th>
            </tr>
          </thead>
          <tbody>
            @forelse($labCharges ?? [] as $charge)
              <tr>
                <td>{{ $charge['patient_first_name'] ?? 'N/A' }} {{ $charge['patient_last_name'] ?? '' }}</td>
                <td>{{ $charge['test_name'] ?? 'N/A' }}</td>
                <td>{{ $charge['doctor_name'] ?? 'N/A' }}</td>
                <td>{{ $charge['date'] ? $charge['date']->format('M d, Y') : 'N/A' }}</td>
                <td>
                  <span class="badge bg-{{ $charge['status'] == 'completed' ? 'success' : 'warning' }}">
                    {{ ucfirst($charge['status'] ?? 'Pending') }}
                  </span>
                </td>
                <td class="text-end">₱{{ number_format($charge['amount'] ?? 0, 2) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-3">
                  No laboratory charges found.
                </td>
              </tr>
            @endforelse
          </tbody>
          {{-- <tfoot class="table-light fw-bold">
            <tr>
              <td colspan="5" class="text-end">Total Laboratory Charges:</td>
              <td class="text-end">₱{{ number_format($labTotal, 2) }}</td>
            </tr>
          </tfoot> --}}
        </table>
      </div>
    </div>
  </div>

        <div class="text-center text-muted mt-4">
        <small>Billing Dashboard &copy; {{ date('Y') }}. All rights reserved.</small>
    </div>

</div>
@endsection
