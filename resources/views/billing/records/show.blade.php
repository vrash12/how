{{-- filepath: c:\Users\Sam\Desktop\PatientCare-Updated-main - Final\resources\views\billing\records\show.blade.php --}}
@extends('layouts.billing')

@section('content')
<div class="container-fluid p-4">
  <h4 class="mb-3">Patient Details</h4>
  <div class="card mb-4 shadow-sm">
    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between">
      <div>
        <h5 class="card-title mb-1">{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</h5>
        <p class="mb-1"><strong>MRN:</strong> {{ str_pad($patient->patient_id,8,'0',STR_PAD_LEFT) }}</p>
        <p class="mb-1"><strong>Admission:</strong> {{ optional($admission)->admission_id ?? 'N/A' }}</p>
        <p class="mb-1"><strong>Bed:</strong> {{ $admission->bed?->bed_number ?? 'N/A' }}</p>
        <p class="mb-1"><strong>Status:</strong> {{ $patient->status }}</p>
        <p class="mb-1"><strong>Doctor Name:</strong> {{ optional($admission?->doctor)->doctor_name ?? 'N/A' }}</p>
        <p class="mb-1"><strong>Doctor Fee:</strong> <p>₱{{ number_format( $doctorDailyRate, 2) }} per day × {{ $daysAdmitted }} days = ₱{{ number_format($doctorFee, 2) }}</p></p>
        <p class="mb-1"><strong>Bed Fee:</strong> <p>₱{{ number_format($bedDailyRate, 2) }} per day × {{ $daysAdmitted }} days = ₱{{ number_format($bedRate, 2) }}</p></p>
      </div>
      <div class="mt-3 mt-md-0">
        @if($balance > 0)
        {{-- Add Deposit Button --}}
        <button class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm" 
                data-bs-toggle="modal" 
                data-bs-target="#depositModal{{ $patient->patient_id }}">
            <i class="fa fa-wallet"></i> Add Deposit
        </button>

        {{-- Settle Balance Button (disabled unless medication_finished = 1) --}}
        <form method="POST" action="{{ route('billing.records.settle', $patient->patient_id) }}" class="d-inline">
            @csrf
            <button type="submit" 
                    class="btn btn-success btn-sm rounded-pill px-4 shadow-sm"
                    @if(!$patient->medication_finished) disabled @endif>
                <i class="fa fa-check"></i> Settle Balance
            </button>
        </form>
        @endif

        {{-- Print Statement Button (disabled unless medication_finished = 1) --}}
        <a href="{{ route('patient.statement', $patient->patient_id) }}" 
           target="_blank"
           class="btn btn-outline-secondary btn-sm rounded-pill px-4 shadow-sm"
           @if(!$patient->medication_finished) disabled @endif>
          <i class="fa fa-print"></i> Print Statement
        </a>
      </div>
    </div>
  </div>

  {{-- Bill Summary --}}
  <div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Total Bill</h6>
                <div class="display-6 text-primary">
                    ₱{{ number_format($grandTotal, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Outstanding</h6>
                <div class="display-6 {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                    ₱{{ number_format($balance, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Total Paid</h6>
                <div class="display-6 text-success">
                    ₱{{ number_format($totalPaid, 2) }}
                </div>
            </div>
        </div>
    </div>
  </div>

  {{-- Transactions Table --}}
  <h5 class="mb-3">Transactions</h5>
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      @if($deposits && $deposits->count())
        <table class="table table-sm table-hover">
          <thead>
            <tr>
              <th>Date</th>
              <th>Amount (₱)</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($deposits as $deposit)
              <tr>
                <td>{{ $deposit->deposited_at->format('Y-m-d') }}</td>
                <td>₱{{ number_format($deposit->amount,2) }}</td>
                <td class="text-center">
                  <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 delete-deposit-btn"
                          data-deposit-id="{{ $deposit->id }}"
                          data-amount="{{ number_format($deposit->amount, 2) }}"
                          data-date="{{ $deposit->deposited_at->format('M d, Y') }}">
                    <i class="fa fa-trash"></i>
                  </button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <span class="text-muted">No transactions found.</span>
      @endif
    </div>
  </div>

  {{-- Charges Table --}}
  <h5 class="mb-3">All Charges</h5>
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
        <table id="chargesTable" class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Service</th>
                    <th>Type</th>
                    <th>Amount (₱)</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>

                {{-- Service Assignments (Lab & OR) --}}
                @foreach($serviceAssignments as $assignment)
                    @php
                        $isDisabled = $assignment->service_status === 'disputed' || $assignment->service_status === 'cancelled' || $patient->medication_finished;
                    @endphp
                    <tr id="service-assignment-{{ $assignment->id }}">
                        <td>{{ $assignment->created_at->format('Y-m-d') }}</td>
                        <td>{{ $assignment->service?->service_name ?? '—' }}</td>
                        <td>
                            @if($assignment->mode == 'lab')
                                <span class="badge bg-warning">Laboratory</span>
                            @elseif($assignment->mode == 'or' || $assignment->mode == 'operating_room')
                                <span class="badge bg-danger">Operating Room</span>
                            @else
                                <span class="badge bg-secondary">{{ $assignment->mode }}</span>
                            @endif
                        </td>
                        <td class="editable-amount" data-original="{{ $assignment->amount ?? 0 }}">
                            ₱{{ number_format($assignment->amount ?? 0, 2) }}
                        </td>
                        <td>
                            @if($assignment->service_status == 'completed')
                                <span class="badge bg-success">Completed</span>
                            @elseif($assignment->service_status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @else
                                <span class="badge bg-secondary">{{ $assignment->service_status }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-warning edit-item" 
                                    data-type="service_assignment" 
                                    data-id="{{ $assignment->assignment_id }}" {{-- Use assignment_id --}}
                                    data-amount="{{ $assignment->amount ?? 0 }}"
                                    data-status="{{ $assignment->service_status }}"
                                    @if($isDisabled) disabled @endif>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-item" 
                                    data-type="service_assignment" 
                                    data-id="{{ $assignment->assignment_id }}" {{-- Use assignment_id --}}
                                    data-service="{{ $assignment->service?->service_name ?? '—' }}"
                                    @if($isDisabled) disabled @endif>
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach

                {{-- Pharmacy Items --}}
                @foreach($pharmacyCharges as $charge)
                    @foreach($charge->items as $item)
                        @php
                            $isDisabled = $item->status === 'disputed' || $item->status === 'cancelled' || $patient->medication_finished;
                        @endphp
                        <tr id="pharmacy-item-{{ $item->id }}">
                            <td>{{ $charge->created_at->format('Y-m-d') }}</td>
                            <td>{{ $item->service?->service_name ?? '—' }}</td>
                            <td><span class="badge bg-success">Pharmacy</span></td>
                            <td class="editable-amount" data-original="{{ $item->total }}">
                                ₱{{ number_format($item->total, 2) }}
                            </td>
                            <td>
                                @if($item->status == 'dispensed')
                                    <span class="badge bg-success">Dispensed</span>
                                @elseif($item->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @else
                                    <span class="badge bg-secondary">{{ $item->status }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning edit-item" 
                                        data-type="pharmacy_item" 
                                        data-id="{{ $item->id }}"
                                        data-amount="{{ $item->total }}"
                                        data-service="{{ $item->service?->service_name ?? '—' }}"
                                        data-status="{{ $item->status }}"
                                        @if($isDisabled) disabled @endif>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-item" 
                                        data-type="pharmacy_item" 
                                        data-id="{{ $item->id }}"
                                        data-service="{{ $item->service?->service_name ?? '—' }}"
                                        @if($isDisabled) disabled @endif>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @endforeach

                {{-- Doctor Fee --}}
                @if($doctorFee > 0)
                    <tr id="doctor-fee">
                        <td>{{ $admission->admission_date ?? '—' }}</td>
                        <td>Professional Fee - {{ $admission->doctor?->doctor_name ?? '—' }}</td>
                        <td><span class="badge bg-primary">Doctor Fee</span></td>
                        <td class="editable-amount" data-original="{{ $doctorFee }}">
                            {{-- Added calculation breakdown --}}
                            
                            <p>
                                Daily Rate: ₱{{ number_format($doctorDailyRate, 2) }} × 
                                {{ $daysAdmitted }} days = 
                                <strong>₱{{ number_format($doctorFee, 2) }}</strong>
                            </p>
                        </td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td class="text-center">
                            {{-- Removed edit button, showing only lock icon --}}
                            <span class="text-muted">
                                <i class="fas fa-lock" title="Cannot modify doctor fee"></i>
                            </span>
                        </td>
                    </tr>
                @endif

                {{-- Bed Fee --}}
                @if($bedRate > 0)
                    <tr id="bed-fee">
                        <td>{{ $admission->admission_date ?? '—' }}</td>
                        <td>Bed Rate - {{ $admission->bed?->bed_number ?? '—' }}</td>
                        <td><span class="badge bg-secondary">Bed Fee</span></td>
                        <td class="editable-amount" data-original="{{ $bedRate }}">
                            
                            <p>
                                Daily: ₱{{ number_format($bedDailyRate, 2) }} × 
                                {{ $daysAdmitted }} days = 
                                <strong>₱{{ number_format($bedRate, 2) }}</strong>
                            </p>
                        </td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td class="text-center">
                            {{-- <button class="btn btn-sm btn-warning edit-item" 
                                    data-type="bed_fee" 
                                    data-id="{{ $admission->bed?->bed_id ?? 0 }}" 
                                    data-patient-id="{{ $patient->patient_id }}" 
                                    data-amount="{{ $bedRate }}"
                                    data-service="Bed Fee"
                                    @if($balance <= 0) disabled @endif>
                                <i class="fas fa-edit"></i>
                            </button> --}}
                            <span class="text-muted">
                                <i class="fas fa-lock" title="Cannot delete bed fee"></i>
                            </span>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
  </div>
</div>

{{-- Deposit Modal --}}
<div class="modal fade" id="depositModal{{ $patient->patient_id }}" tabindex="-1"
     aria-labelledby="depositModalLabel{{ $patient->patient_id }}" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <form method="POST" action="{{ route('billing.deposits.store') }}">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->patient_id }}">

        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="depositModalLabel{{ $patient->patient_id }}">
            <i class="fa fa-wallet me-2"></i>
            Add Deposit – {{ $patient->patient_first_name }} {{ $patient->patient_last_name }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Amount (₱)</label>
              <input type="number" name="amount" step="0.01" min="0"
                     class="form-control form-control-lg" required autofocus>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Deposit Date</label>
              <input type="date" name="deposited_at"
                     class="form-control form-control-lg"
                     value="{{ now()->toDateString() }}" required>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light">
          <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
            <i class="fa fa-save"></i> Save Deposit
          </button>
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
            Close
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Item Modal --}}
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Billing Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editItemForm">
                @csrf
                @method('POST')
                <div class="modal-body">
                    <input type="hidden" id="edit-item-type">
                    <input type="hidden" id="edit-item-id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Service</label>
                        <input type="text" id="edit-service-name" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-amount" class="form-label fw-bold">Amount (₱)</label>
                        <input type="number" step="0.01" min="0" id="edit-amount" 
                               name="amount" class="form-control" required>
                    </div>
                    
                    <div class="mb-3" id="edit-status-container" style="display: none;">
                        <label for="edit-status" class="form-label fw-bold">Status</label>
                        <select id="edit-status" name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="dispensed">Dispensed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Update Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-trash me-2"></i>Delete Billing Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning!</strong> This action cannot be undone.
                </div>
                <p>Are you sure you want to delete this billing item?</p>
                <div class="bg-light p-3 rounded">
                    <strong>Service:</strong> <span id="delete-service-name"></span><br>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirm-delete">
                    <i class="fas fa-trash me-1"></i>Delete Item
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '{{ session('success') }}',
            confirmButtonColor: '#28a745'
        });
    @elseif($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ $errors->first() }}',
            confirmButtonColor: '#dc3545'
        });
    @endif
});
$(document).ready(function() {
    // Initialize DataTable
    $('#chargesTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
    });

    let currentEditData = {};
    let currentDeleteData = {};

    // Edit Item Button Click
    $(document).on('click', '.edit-item', function() {
        const type = $(this).data('type');
        const id = $(this).data('id');
        const patientId = $(this).data('patient-id'); // Fetch the patient_id
        const amount = $(this).data('amount');
        const service = $(this).data('service');
        const status = $(this).data('status');

        currentEditData = { type, id, patientId }; // Include patient_id in the edit data

        // Populate modal
        $('#edit-item-type').val(type);
        $('#edit-item-id').val(id);
        $('#edit-service-name').val(service);
        $('#edit-amount').val(amount);

        // Show status field for certain types
        if (type === 'service_assignment' || type === 'pharmacy_item') {
            $('#edit-status-container').show();
            $('#edit-status').val(status);
        } else {
            $('#edit-status-container').hide();
        }

        $('#editItemModal').modal('show');
    });

    // Delete Item Button Click
    $(document).on('click', '.delete-item', function() {
        const type = $(this).data('type');
        const id = $(this).data('id');
        const service = $(this).data('service');
        const amount = $(this).data('amount') || 0;

        currentDeleteData = { type, id };

        $('#delete-service-name').text(service);
        $('#delete-amount').text('₱' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2}));

        $('#deleteItemModal').modal('show');
    });

    // Handle Edit Form Submit
    $('#editItemForm').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Updating...');

        const formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            _method: 'POST', // Use method spoofing for Laravel
            amount: $('#edit-amount').val(),
            status: $('#edit-status').val(),
            type: $('#edit-item-type').val(),
            patient_id: currentEditData.patientId // Include patient_id in the request
        };

        $.ajax({
            url: '{{ route("billing.items.update", ":id") }}'.replace(':id', $('#edit-item-id').val()),
            method: 'POST', // Send as POST
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#editItemModal').modal('hide');
                    location.reload(); // Reload the page to reflect changes
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: xhr.responseJSON?.message || 'An error occurred'
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Update Item');
            }
        });
    });

    // Handle Delete Confirmation
    $('#confirm-delete').on('click', function() {
        const deleteBtn = $(this);
        deleteBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Deleting...');

        $.ajax({
            url: '{{ route("billing.items.delete", ":id") }}'.replace(':id', currentDeleteData.id),
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                type: currentDeleteData.type,
                item_id: currentDeleteData.id
            },
            success: function(response) {
                if (response.success) {
                    // Remove the table row
                    removeTableRow(currentDeleteData.type, currentDeleteData.id);
                    
                    $('#deleteItemModal').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Refresh totals
                    if (response.totals) {
                        updateTotals(response.totals);
                    }
                } else {
                    throw new Error(response.message || 'Delete failed');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to delete item';
                if (xhr.responseJSON?.message) {
                    errorMsg = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Delete Failed',
                    text: errorMsg
                });
            },
            complete: function() {
                deleteBtn.prop('disabled', false).html('<i class="fas fa-trash me-1"></i>Delete Item');
            }
        });
    });

    // Handle Delete Deposit with SweetAlert
    $(document).on('click', '.delete-deposit-btn', function() {
        const depositId = $(this).data('deposit-id');
        const amount = $(this).data('amount');
        const date = $(this).data('date');

        Swal.fire({
            title: 'Delete Deposit?',
            html: `
                <div class="text-start">
                    <p><strong>Amount:</strong> ₱${amount}</p>
                    <p><strong>Date:</strong> ${date}</p>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> The patient will be notified of this deletion.
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-1"></i>Yes, Delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Create and submit a form for deletion
                const form = $('<form>', {
                    'method': 'POST',
                    'action': '{{ route("billing.deposits.destroy", ":id") }}'.replace(':id', depositId)
                });
                
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': $('meta[name="csrf-token"]').attr('content')
                }));
                
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_method',
                    'value': 'DELETE'
                }));
                
                $('body').append(form);
                form.submit();
            }
        });
    });

    // Helper function to update table row
    function updateTableRow(type, id, data) {
        const rowId = getRowId(type, id);
        const row = $(`#${rowId}`);
        
        if (row.length) {
            // Update amount
            row.find('.editable-amount').html('₱' + parseFloat(data.amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
            
            // Update status if applicable
            if (data.status) {
                const statusBadge = getStatusBadge(data.status);
                row.find('td:nth-child(5)').html(statusBadge);
            }
        }
    }

    // Helper function to remove table row
    function removeTableRow(type, id) {
        const rowId = getRowId(type, id);
        $(`#${rowId}`).fadeOut(300, function() {
            $(this).remove();
        });
    }

    // Helper function to get row ID
    function getRowId(type, id) {
        const typeMap = {
            'bill_item': 'bill-item',
            'service_assignment': 'service-assignment',
            'pharmacy_item': 'pharmacy-item',
            'doctor_fee': 'doctor-fee',
            'room_fee': 'room-fee',
            'bed_fee': 'bed-fee'
        };
        return `${typeMap[type]}-${id}`;
    }

    // Helper function to get status badge
    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge bg-warning">Pending</span>',
            'completed': '<span class="badge bg-success">Completed</span>',
            'dispensed': '<span class="badge bg-success">Dispensed</span>',
            'cancelled': '<span class="badge bg-danger">Cancelled</span>'
        };
        return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
    }

    // Helper function to update totals display
    function updateTotals(totals) {
        $('.display-6.text-primary').html('₱' + parseFloat(totals.total).toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('.display-6.text-danger, .display-6.text-success').html('₱' + parseFloat(totals.balance).toLocaleString('en-US', {minimumFractionDigits: 2}));
        
        // Update balance color
        const balanceEl = $('.display-6.text-danger, .display-6.text-success').last();
        if (totals.balance > 0) {
            balanceEl.removeClass('text-success').addClass('text-danger');
        } else {
            balanceEl.removeClass('text-danger').addClass('text-success');
        }
    }
});
</script>
@endpush

{{-- Debugging room_id --}}
@php
    \Log::info('Room ID in Blade Template', [
        'room_id' => optional($admission->room)->room_id ?? 'N/A',
        'room_name' => optional($admission->room)->room_name ?? 'N/A',
    ]);
@endphp