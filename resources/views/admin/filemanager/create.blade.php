<!-- filepath: resources/views/admin/filemanager/create.blade.php -->
@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1>Add Hospital Service</h1>
  <a href="{{ route('admin.hospital_services.index') }}" class="btn btn-sm btn-secondary">
    ← Back to List
  </a>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header">
        <strong>New Service</strong>
      </div>
      <div class="card-body">
        <form action="{{ route('admin.hospital_services.store') }}" method="POST">
          @csrf

          <div class="mb-3">
            <label for="service_name" class="form-label">Service Name</label>
            <input type="text" name="service_name" id="service_name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="service_type" class="form-label">Type</label>
            <select name="service_type" id="service_type" class="form-select" required onchange="toggleFields()">
              <option value="">Select type</option>
              <option value="medication">Medication</option>
              <option value="lab">Lab</option>
              <option value="operation">Operation</option>
            </select>
          </div>
          
          <!-- Add prescription requirement field -->
          <div class="mb-3" id="prescription-field" style="display:none;">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="needs_prescription" name="needs_prescription" value="1" checked>
                <label class="form-check-label" for="needs_prescription">
                    Requires Prescription
                </label>
                <div class="form-text">Check if this medication requires a prescription. Unchecked items can be sold over-the-counter.</div>
            </div>
          </div>

          <div class="mb-3" id="quantity-group">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" name="quantity" id="quantity" class="form-control" min="0">
          </div>

          <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" name="price" id="price" class="form-control" min="0" step="0.01" required>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control"></textarea>
          </div>

          <button type="submit" class="btn btn-primary">Add Service</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function toggleFields() {
  const type = document.getElementById('service_type').value;
  const quantityGroup = document.getElementById('quantity-group');
  const prescriptionField = document.getElementById('prescription-field');
  
  // Handle quantity field
  if (type === 'medication') {
    quantityGroup.style.display = '';
    document.getElementById('quantity').required = true;
    
    // Show prescription field for medications
    prescriptionField.style.display = '';
  } else {
    quantityGroup.style.display = 'none';
    document.getElementById('quantity').required = false;
    document.getElementById('quantity').value = '';
    
    // Hide prescription field for non-medications
    prescriptionField.style.display = 'none';
    document.getElementById('needs_prescription').checked = false;
  }
}

document.addEventListener('DOMContentLoaded', toggleFields);
document.getElementById('service_type').addEventListener('change', toggleFields);

@if(session('success'))
  Swal.fire({
    icon: 'success',
    title: 'Success!',
    text: '{{ session('success') }}',
    timer: 2000,
    showConfirmButton: false
  });
@endif

@if($errors->any())
  Swal.fire({
    icon: 'error',
    title: 'Error',
    html: `{!! implode('<br>', $errors->all()) !!}`
  });
@endif
</script>
@endpush