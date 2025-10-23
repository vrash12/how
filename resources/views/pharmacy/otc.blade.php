@extends('layouts.pharmacy')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0"><i class="fas fa-pills text-primary me-2"></i>Over-The-Counter Sales</h1>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 rounded-3 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="fas fa-cash-register text-success me-2"></i>Create OTC Sale</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('pharmacy.otc.charge') }}" method="POST" id="otcForm">
                @csrf
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="patient_id" class="form-label fw-bold">
                            <i class="fas fa-user-injured me-1"></i> Patient
                        </label>
                        <select name="patient_id" id="patient_id" class="form-select form-select-lg" required>
                            <option value="">-- Select Patient --</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->patient_id }}">
                                    {{ $patient->patient_last_name }}, {{ $patient->patient_first_name }} (PID-{{ str_pad($patient->patient_id, 6, '0', STR_PAD_LEFT) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="notes" class="form-label fw-bold">
                            <i class="fas fa-sticky-note me-1"></i> Notes (Optional)
                        </label>
                        <input type="text" name="notes" id="notes" class="form-control form-control-lg" placeholder="Any special instructions">
                    </div>
                </div>
                
                <h5 class="mb-3 border-bottom pb-2">
                    <i class="fas fa-pills text-primary me-2"></i>Medications (No Prescription Required)
                </h5>
                
                <div id="medications-container" class="mb-4">
                    <div class="medication-row row mb-3 align-items-center bg-light p-2 rounded">
                        <div class="col-md-6">
                            <label class="small mb-1">Medication</label>
                            <select name="medications[0][service_id]" class="form-select medication-select" required>
                                <option value="">-- Select Medication --</option>
                                @foreach($medications as $med)
                                    <option value="{{ $med->service_id }}" data-price="{{ $med->price }}">
                                        {{ $med->service_name }} - ₱{{ number_format($med->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small mb-1">Quantity</label>
                            <input type="number" name="medications[0][quantity]" class="form-control medication-qty" 
                                placeholder="Qty" min="1" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="small mb-1">Subtotal</label>
                            <span class="line-total form-control bg-white border-0 fw-bold">₱0.00</span>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <button type="button" id="add-medication" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i> Add Another Medication
                    </button>
                </div>
                
                <div class="d-flex justify-content-between border-top pt-3">
                    <h4 class="text-end mb-0">Total: <span id="grand-total" class="badge bg-primary fs-5">₱0.00</span></h4>
                    <button type="button" id="submit-otc" class="btn btn-success btn-lg">
                        <i class="fas fa-check-circle me-2"></i> Complete OTC Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let rowIndex = 0;
        
        // Handle form submission with SweetAlert confirmation
        document.getElementById('submit-otc').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validate form
            const form = document.getElementById('otcForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Get patient name for confirmation
            const patientSelect = document.getElementById('patient_id');
            const patientName = patientSelect.options[patientSelect.selectedIndex].text;
            
            // Calculate total for confirmation
            let total = parseFloat(document.getElementById('grand-total').textContent.replace('₱', '')) || 0;
            
            // Show SweetAlert confirmation
            Swal.fire({
                title: 'Confirm OTC Sale',
                html: `<p>Complete sale for patient:<br><strong>${patientName}</strong></p>
                       <p>Total amount: <strong>₱${total.toFixed(2)}</strong></p>
                       <p>This will be added to the patient's bill immediately.</p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Complete Sale',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show processing message
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Completing your OTC sale',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the form
                    form.submit();
                }
            });
        });
        
        // Add new medication row
        document.getElementById('add-medication').addEventListener('click', function() {
            rowIndex++;
            const newRow = document.createElement('div');
            newRow.className = 'medication-row row mb-3 align-items-center bg-light p-2 rounded';
            newRow.innerHTML = `
                <div class="col-md-6">
                    <label class="small mb-1">Medication</label>
                    <select name="medications[${rowIndex}][service_id]" class="form-select medication-select" required>
                        <option value="">-- Select Medication --</option>
                        @foreach($medications as $med)
                            <option value="{{ $med->service_id }}" data-price="{{ $med->price }}">
                                {{ $med->service_name }} - ₱{{ number_format($med->price, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small mb-1">Quantity</label>
                    <input type="number" name="medications[${rowIndex}][quantity]" class="form-control medication-qty" 
                        placeholder="Qty" min="1" value="1" required>
                </div>
                <div class="col-md-3">
                    <label class="small mb-1">Subtotal</label>
                    <div class="d-flex">
                        <span class="line-total form-control bg-white border-0 fw-bold flex-grow-1">₱0.00</span>
                        <button type="button" class="btn btn-outline-danger remove-row ms-2">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('medications-container').appendChild(newRow);
            
            // Add event listeners to the new row
            addRowListeners(newRow);
        });
        
        // Add listeners to existing rows
        document.querySelectorAll('.medication-row').forEach(row => {
            addRowListeners(row);
        });
        
        function addRowListeners(row) {
            const select = row.querySelector('.medication-select');
            const qtyInput = row.querySelector('.medication-qty');
            const totalSpan = row.querySelector('.line-total');
            const removeBtn = row.querySelector('.remove-row');
            
            if (select && qtyInput) {
                // Update line total when selection or quantity changes
                const updateLineTotal = () => {
                    const option = select.options[select.selectedIndex];
                    const price = option ? parseFloat(option.dataset.price || 0) : 0;
                    const qty = parseInt(qtyInput.value || 0);
                    const total = price * qty;
                    totalSpan.textContent = '₱' + total.toFixed(2);
                    updateGrandTotal();
                };
                
                select.addEventListener('change', updateLineTotal);
                qtyInput.addEventListener('input', updateLineTotal);
                
                // Initial calculation
                if (select.selectedIndex > 0) {
                    updateLineTotal();
                }
            }
            
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    // Show removal confirmation
                    Swal.fire({
                        title: 'Remove this item?',
                        text: 'This will remove the medication from your current sale.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, remove it',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#dc3545'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            row.remove();
                            updateGrandTotal();
                        }
                    });
                });
            }
        }
        
        function updateGrandTotal() {
            let total = 0;
            document.querySelectorAll('.line-total').forEach(span => {
                const value = parseFloat(span.textContent.replace('₱', '')) || 0;
                total += value;
            });
            document.getElementById('grand-total').textContent = '₱' + total.toFixed(2);
        }
        
        // Show toast notification for any success messages
        if (document.querySelector('.alert-success')) {
            const successMessage = document.querySelector('.alert-success').innerText;
            
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
            
            Toast.fire({
                icon: 'success',
                title: successMessage
            });
        }
        
        // Update patient select with search functionality
        $(document).ready(function() {
            $('#patient_id').select2({
                placeholder: "Search for a patient...",
                allowClear: true,
                theme: "bootstrap-5"
            });
        });
    });
</script>

<!-- Add Select2 for better dropdown experience (optional) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush
@endsection
