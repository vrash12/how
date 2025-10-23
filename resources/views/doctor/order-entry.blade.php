{{-- resources/views/doctor/order-entry.blade.php --}}
@extends('layouts.doctor')

@section('content')

@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: '{{ session('success') }}',
        timer: 2500,
        showConfirmButton: false
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '{{ session('error') }}'
    });
</script>
@endif

@if($errors->any())
<script>
    Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: `{!! implode('<br>', $errors->all()) !!}`
    });
</script>
@endif


<div class="container-fluid">

  <div id="connection-status" class="alert alert-info mb-3" style="display:none;">
    <span id="connection-message"></span>
  </div>

  <div class="mb-4">
    <h4 class="fw-bold">🏥 Order Entry</h4>
    <p class="text-muted">Create Prescriptions and Order services for patients.</p>
  </div>
  
  {{-- PATIENT CARD --}}
  <div class="card mb-4">
    <div class="card-body d-flex align-items-start">
      <div class="rounded-circle bg-light flex-shrink-0 me-3" style="width:48px;height:48px;"></div>
      <div class="flex-grow-1">
        <div class="d-flex align-items-center mb-1">
          <strong class="me-2">{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</strong>
          <span class="text-muted small">| P-{{ $patient->patient_id }}</span>
        </div>
        <div class="small text-muted">
          {{ ucfirst($patient->civil_status) }}, {{ $patient->patient_birthday?->age }} yrs •
          DOB: {{ $patient->patient_birthday?->format('m/d/Y') }}<br>
          MRN: <span class="fw-semibold">{{ $patient->mrn ?? 'N/A' }}</span><br>
          Allergies:
          @forelse($patient->medicalDetail?->allergies ?? [] as $allergy)
            <span class="badge bg-danger-subtle text-danger border border-danger me-1">{{ $allergy }}</span>
          @empty
            <span class="text-muted">None</span>
          @endforelse
        </div>
      </div>
      <a href="{{ route('doctor.dashboard') }}" class="btn btn-outline-secondary btn-sm ms-3">
        <i class="fa-solid fa-xmark me-1"></i> Change Patient
      </a>
    </div>
  </div>

  {{-- TAB LABEL STYLING --}}
  <style>
      /* Base tab style */
      .nav-tabs .nav-link {
        color: #00529A;
        font-weight: 600;
        border: none;
        border-radius: 0;
      }

      /* Hover */
      .nav-tabs .nav-link:hover {
        color: #003f7a;
        background-color: #f0f6fb;
        border: none;
      }

      /* Active tab */
      .nav-tabs .nav-link.active {
        background-color: #00529A !important;
        color: #fff !important;
        border: none;
        border-bottom: 3px solid #003f7a; /* subtle bottom highlight */
        border-radius: 0;
      }

      /* Remove weird border gap under tabs */
      .nav-tabs {
        border-bottom: 2px solid #00529A;
      }
  </style>


  {{-- NAV TABS --}}
  <ul class="nav nav-tabs nav-fill mb-3" id="orderTabs" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#medications" type="button">
        💊 Medications
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#laboratory" type="button">
        🧪 Lab & Imaging
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#services" type="button">
        🛠️ OR Surgery
      </button>
    </li>
  </ul>

  <div class="tab-content">

{{-- TAB 1 – MEDICATIONS (with per-row duration & instructions) --}}
<div class="tab-pane fade show active" id="medications">
  @if($patient->medication_finished)
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> This patient has been marked as completed. No further orders can be made.
    </div>
  @else
    <form method="POST" action="{{ route('doctor.orders.store', $patient) }}">
      @csrf
      <input type="hidden" name="type" value="medication">

      <div id="med-wrapper">
     
  <div class="med-row border rounded p-3 mb-3">
    {{-- Top row: Medication, Qty, Duration, Unit --}}
    <div class="row g-3 mb-2">
      <div class="col-md-6">
        <label class="form-label">Medication</label>
        <select class="form-select medication-select" name="medications[0][medication_id]" required>
          <option value="" disabled selected>Select medication</option>
          @foreach($medications as $med)
            <option value="{{ $med->service_id }}" data-price="{{ $med->price }}">{{ $med->service_name }}</option>
          @endforeach
        </select>
        <div class="form-text medication-price">₱0.00</div>
      </div>

      <div class="col-md-2">
        <label class="form-label">Qty</label>
        <input type="number" min="1" class="form-control"
               name="medications[0][quantity]" value="1" required>
      </div>

      <div class="col-md-2">
        <label class="form-label">Duration</label>
        <input type="number" min="1" class="form-control"
               name="medications[0][duration]" value="1" required>
      </div>

      <div class="col-md-2">
        <label class="form-label">Unit</label>
        <select class="form-select" name="medications[0][duration_unit]" required>
          <option value="days">Days</option>
          <option value="weeks">Weeks</option>
        </select>
      </div>
    </div>

    {{-- Bottom row: Instructions + Remove --}}
    <div class="row g-3">
      <div class="col-md-10">
        <label class="form-label">Special Instructions</label>
        <textarea class="form-control"
                  name="medications[0][instructions]"
                  rows="2"></textarea>
      </div>
      <div class="col-md-2 d-grid">
        <button type="button" class="btn btn-danger btn-remove-med mt-4">✕ Remove</button>
      </div>
    </div>
  </div>

      </div>

      <button type="button" id="btn-add-med" class="btn btn-outline-primary btn-sm mb-4">
        + Add Medication
      </button>

      <div class="d-flex justify-content-end">
        
        <a href="{{ route('doctor.dashboard') }}" class="btn btn-light me-2">Cancel</a>
        <!-- <a href="#" class="btn btn-outline-primary me-2">Forward to Patient</a> -->
        <button type="submit" class="btn btn-primary">Submit Medication Order</button>
      </div>
    </form>
  @endif
</div>

  {{-- TAB 2 – LAB & IMAGING --}}
<div class="tab-pane fade" id="laboratory">
  <form method="POST" action="{{ route('doctor.orders.store', $patient) }}" class="row gy-3">
    @csrf
    <input type="hidden" name="type" value="lab">
    <input type="hidden" name="modeService" value="lab">

    {{-- Select labs & imaging --}}
    <div class="col-12">
      <label class="form-label">Select Laboratory Tests & Imaging Studies</label>

      {{-- Search Bar --}}
      <div class="mb-3">
        <input type="text" id="labSearch" class="form-control" placeholder="Search labs or imaging...">
      </div>

      {{-- Scrollable Labs --}}
      <div class="row g-2" style="max-height: 300px; overflow-y: auto;" id="labList">
        @foreach($labTests as $lab)
          <div class="col-md-6 lab-item" data-name="{{ strtolower($lab->service_name) }}">
            <div class="card shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <div class="form-check me-3">
                  <input class="form-check-input lab-checkbox" type="checkbox" name="labs[]" value="{{ $lab->service_id }}" id="lab{{ $lab->service_id }}">
                  <label class="form-check-label" for="lab{{ $lab->service_id }}"></label>
                </div>
                <div>
                  <label class="form-check-label fw-bold" for="lab{{ $lab->service_id }}">
                    {{ $lab->service_name }}
                  </label>
                  {{-- <small class="text-muted d-block">Base Price: <span class="fw-bold text-primary">₱{{ number_format($lab->price, 2) }}</span></small> --}}
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Diagnosis --}}
    <div class="col-12">
      <label class="form-label">Diagnosis / Clinical Indication</label>
      <textarea class="form-control" name="diagnosis" rows="2"></textarea>
    </div>

    {{-- Buttons --}}
    <div class="col-12 d-flex justify-content-end">
      <a href="{{ route('doctor.dashboard') }}" class="btn btn-light me-2">Cancel</a>
      <button type="submit" class="btn btn-primary">Submit Lab & Imaging Order</button>
    </div>
  </form>
</div>


{{-- TAB 3 – OTHER SERVICES --}}
<div class="tab-pane fade" id="services">
  <form method="POST" action="{{ route('doctor.orders.store', $patient) }}" class="row gy-3">
    @csrf
    <input type="hidden" name="type" value="operation">
    <input type="hidden" name="modeService" value="or">
    
    {{-- Select services --}}
    <div class="col-12">
      <label class="form-label">Select Services</label>

      {{-- Search Bar --}}
      <div class="mb-3">
        <input type="text" id="serviceSearch" class="form-control" placeholder="Search services...">
      </div>

      {{-- Scrollable Services --}}
      <div class="row g-2" style="max-height: 300px; overflow-y: auto;" id="serviceList">
        @foreach($otherServices as $svc)
          <div class="col-md-6 service-item" data-name="{{ strtolower($svc->service_name) }}">
            <div class="card shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <div class="form-check me-3">
                  <input class="form-check-input service-checkbox" type="checkbox" name="services[]" value="{{ $svc->service_id }}" id="service{{ $svc->service_id }}" data-price="{{ $svc->price }}">
                  <label class="form-check-label" for="service{{ $svc->service_id }}"></label>
                </div>
                <div>
                  <label class="form-check-label fw-bold" for="service{{ $svc->service_id }}">
                    {{ $svc->service_name }}
                  </label>
                  <small class="text-muted d-block">Base Price: <span class="fw-bold text-primary">₱{{ number_format($svc->price, 2) }}</span></small>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Professional Fee --}}
    <div class="col-12">
      <label class="form-label">Doctor Surgery Fee (₱)</label>
      <input type="number" 
             min="0" 
             step="0.01" 
             class="form-control" 
             name="professional_fee" 
             placeholder="Enter professional fee amount (optional)">
      <div class="form-text">This will be added to the professional services</div>
    </div>

    {{-- Diagnosis --}}
    <div class="col-12">
      <label class="form-label">Diagnosis / Clinical Indication</label>
      <textarea class="form-control" name="diagnosis" rows="2"></textarea>
    </div>

    {{-- Scheduled date
    <div class="col-md-6">
      <label class="form-label">Scheduled Date</label>
      <input type="date"
             class="form-control"
             name="scheduled_date"
             value="{{ now()->toDateString() }}">
    </div>
=
    <div class="col-md-6">
      <label class="form-label d-block">Priority</label>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="priority" value="routine" checked>
        <label class="form-check-label">Routine</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="priority" value="urgent">
        <label class="form-check-label">Urgent</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="priority" value="stat">
        <label class="form-check-label">STAT</label>
      </div>
    </div> --}}

    {{-- Buttons --}}
    <div class="col-12 d-flex justify-content-end">
      <a href="{{ route('doctor.dashboard') }}" class="btn btn-light me-2">Cancel</a>
      <button type="submit" class="btn btn-primary">Submit Service Order</button>
    </div>
  </form>
</div>


  </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const wrapper = document.getElementById('med-wrapper');
  const addBtn  = document.getElementById('btn-add-med');

  // Clone & index a new med-row
addBtn.addEventListener('click', () => {
  const idx  = wrapper.querySelectorAll('.med-row').length;
  let html    = wrapper.querySelector('.med-row').outerHTML;
  html        = html.replace(/\[\d+\]/g, `[${idx}]`);
  wrapper.insertAdjacentHTML('beforeend', html);
  bindRemove();
});

  // Bind Remove buttons
  function bindRemove() {
    wrapper.querySelectorAll('.btn-remove-med').forEach(btn => {
      btn.onclick = () => {
        const rows = wrapper.querySelectorAll('.med-row');
        if (rows.length > 1) {
          btn.closest('.med-row').remove();
        }
      };
    });
  }

  bindRemove(); // initial row
});
</script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Handle medication dropdown price display
  document.querySelectorAll('.medication-select').forEach(select => {
    select.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      const price = selectedOption.getAttribute('data-price') || 0;
      this.closest('.col-md-6').querySelector('.medication-price').textContent = `₱${parseFloat(price).toFixed(2)}`;
    });
  });

  // Handle service checkbox price display
  document.querySelectorAll('.service-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      const price = this.getAttribute('data-price') || 0;
      const priceDisplay = this.closest('.form-check').querySelector('.text-primary');
      if (this.checked) {
        priceDisplay.textContent = `₱${parseFloat(price).toFixed(2)}`;
      } else {
        priceDisplay.textContent = `₱0.00`;
      }
    });
  });
});
</script>
@endpush
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('serviceSearch');
    const serviceItems = document.querySelectorAll('.service-item');

    searchInput.addEventListener('input', function () {
      const query = this.value.toLowerCase();

      serviceItems.forEach(item => {
        const name = item.getAttribute('data-name');
        if (name.includes(query)) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    });
  });
</script>
@endpush
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('labSearch');
    const labItems = document.querySelectorAll('.lab-item');

    searchInput.addEventListener('input', function () {
      const query = this.value.toLowerCase();

      labItems.forEach(item => {
        const name = item.getAttribute('data-name');
        if (name.includes(query)) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    });
  });
</script>
@endpush
@push('styles')
<style>
  #serviceList {
    max-height: 300px; /* Adjust height as needed */
    overflow-y: auto;
    border: 1px solid #e3e3e3;
    border-radius: 8px;
    padding: 0.5rem;
  }

  .service-item {
    margin-bottom: 0.5rem;
  }

  .form-check-input {
    width: 1.2rem;
    height: 1.2rem;
    cursor: pointer;
  }

  .card {
    border: 1px solid #e3e3e3;
    border-radius: 6px;
    transition: box-shadow 0.2s ease-in-out;
  }

  .card:hover {
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  }
</style>
@endpush
@push('styles')
<style>
  #labList {
    max-height: 300px; /* Adjust height as needed */
    overflow-y: auto;
    border: 1px solid #e3e3e3;
    border-radius: 8px;
    padding: 0.5rem;
  }

  .lab-item {
    margin-bottom: 0.5rem;
  }

  .form-check-input {
    width: 1.2rem;
    height: 1.2rem;
    cursor: pointer;
  }

  .card {
    border: 1px solid #e3e3e3;
    border-radius: 6px;
    transition: box-shadow 0.2s ease-in-out;
  }

  .card:hover {
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  }
</style>
@endpush


