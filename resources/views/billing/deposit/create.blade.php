@extends('layouts.billing')

@section('content')
<div class="container-fluid p-4">
  <h4 class="mb-4">Post Deposit</h4>

  <form id="depositForm" method="POST" action="{{ route('billing.deposits.store') }}">
    @csrf

    <div class="mb-3">
      <label for="patient_id" class="form-label">Patient ID</label>
      <input
        type="number"
        id="patient_id"
        name="patient_id"
        class="form-control @error('patient_id') is-invalid @enderror"
        value="{{ old('patient_id') }}"
        required
      >
      @error('patient_id')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <label for="amount" class="form-label">Amount (₱)</label>
        <input
          type="number"
          id="amount"
          name="amount"
          step="0.01"
          class="form-control @error('amount') is-invalid @enderror"
          value="{{ old('amount') }}"
          min="0"
          required
        >
        @error('amount')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
      <div class="col-md-6">
        <label for="deposited_at" class="form-label">Deposit Date</label>
        <input
          type="date"
          id="deposited_at"
          name="deposited_at"
          class="form-control @error('deposited_at') is-invalid @enderror"
          value="{{ old('deposited_at', now()->toDateString()) }}"
          required
        >
        @error('deposited_at')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>

    <div class="mt-4">
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-money-bill-wave me-1"></i> Record Deposit
      </button>
      <a href="{{ route('billing.dashboard') }}" class="btn btn-secondary ms-2">
        Cancel
      </a>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('depositForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData,
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'An error occurred');
            });
        }
        return response.json();
    })
    .then(data => {
        Swal.fire({
            title: 'Success',
            text: data.message,
            icon: 'success',
            confirmButtonText: 'OK',
        }).then(() => {
            window.location.href = "{{ route('billing.dashboard') }}";
        });
    })
    .catch(error => {
        Swal.fire({
            title: 'Error',
            text: error.message,
            icon: 'error',
            confirmButtonText: 'OK',
        });
    });
});
</script>
@endpush
