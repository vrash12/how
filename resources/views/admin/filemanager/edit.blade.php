<!-- filepath: resources/views/admin/hospital_services/edit.blade.php -->
@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1>Edit Hospital Service</h1>
  <a href="{{ route('admin.hospital_services.index') }}" class="btn btn-sm btn-secondary">
    ← Back to List
  </a>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header">
        <strong>Edit Service</strong>
      </div>
      <div class="card-body">
        <form action="{{ route('admin.hospital_services.update', $service) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="service_name" class="form-label">Service Name</label>
            <input type="text" name="service_name" id="service_name" class="form-control" value="{{ $service->service_name }}" required>
          </div>
          
          <div class="mb-3">
            <label for="service_type" class="form-label">Type</label>
            <input type="text" name="service_type" id="service_type" class="form-control" value="{{ $service->service_type }}" required>
          </div>
          
          <!-- Add prescription requirement field -->
          <div class="mb-3" id="prescription-field" style="{{ $service->service_type === 'medication' ? '' : 'display:none;' }}">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="needs_prescription" name="needs_prescription" value="1" {{ $service->needs_prescription ? 'checked' : '' }}>
                <label class="form-check-label" for="needs_prescription">
                    Requires Prescription
                </label>
                <div class="form-text">Check if this medication requires a prescription. Unchecked items can be sold over-the-counter.</div>
            </div>
          </div>

          <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" name="price" id="price" class="form-control" min="0" step="0.01" value="{{ $service->price }}" required>
          </div>

          <div class="mb-3">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" name="quantity" id="quantity" class="form-control" min="0" value="{{ $service->quantity }}" required>
          </div>

          <button type="submit" class="btn btn-primary">Update Service</button>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceType = document.getElementById('service_type');
    const prescriptionField = document.getElementById('prescription-field');
    
    // Show/hide prescription field based on service type
    serviceType.addEventListener('change', function() {
        if (this.value === 'medication') {
            prescriptionField.style.display = 'block';
        } else {
            prescriptionField.style.display = 'none';
            document.getElementById('needs_prescription').checked = false;
        }
    });
});
</script>
@endpush
@endsection