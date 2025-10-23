{{-- resources/views/patient/disputes/index.blade.php --}}
@extends('layouts.patients')

@section('content')

<div class="container-fluid min-vh-100 d-flex flex-column">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="hdng mb-0">My Dispute Requests</h4>
            <p class="text-muted mb-0">Here is the history of your submitted billing disputes.</p>
        </div>
        <a href="{{ route('patient.billing') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Billing
        </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
      </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" style="margin: 20px">
                <table id="myTable"  class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date Filed</th>
                            <th>Item Disputed</th>
                            <th>Amount</th>
                            <th>Adjusted</th>
                            <th>Reason</th>
                            <th>Message from biller</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
    @forelse($disputes as $dispute)
        <tr>
            <td>{{ \Carbon\Carbon::parse($dispute->datetime)->format('M d, Y') }}</td>
            
            {{-- ✅ FIX: Use the new 'disputable' relationship --}}
            <td>{{$dispute->desc}}</td>
            <td>₱{{ number_format($dispute->oldAmount,2) }}</td>
            <td>₱{{ number_format($dispute->oldAmount-$dispute->FinalAmount,2) }}</td>
            <td>{{ strlen($dispute->reason) > 20 ? substr($dispute->reason, 0, 20) . '...' : $dispute->reason }}</td>
            <td><span style="cursor: pointer" onclick="messages('{{$dispute->messageFromBiller}}')" class="badge bg-primary text-capitalize">Show message</span></td>

            

            <td class="text-center">
                @php
                    $badge = [
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    ][$dispute->status] ?? 'secondary';
                @endphp
                <span class="badge bg-{{$badge}} text-capitalize">{{ $dispute->status }}</span>
            </td>
            @php
                $date = \Carbon\Carbon::parse($dispute->datetime);
                $expired = $date->addMinutes(30)->isPast();

                if($dispute->status != 'pending'){
                    $expired = true;
                }
            @endphp

            <td class="text-center">
                <button onclick="confirmDelete({{ $dispute->dispute_id }})"
                        style="padding-bottom: 5px;"
                        class="btn btn-danger btn-sm"
                        {{ $expired ? 'disabled' : '' }}>
                    Cancel
                </button>

            </td>

        </tr>
    @endforeach
</tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>
<script>
    var table =  $('#myTable').DataTable({
    dom: '<"top d-flex justify-content-between align-items-center mb-3"fB>rt' +
         '<"bottom d-flex justify-content-between align-items-center mt-3"i p>',
    buttons: [
        {
            className: 'd-none',
            extend: 'csv',
            text: '<i class="fa-solid fa-file-csv"></i> CSV',
            enabled: false // disables button on load
        },
        {
             className: 'd-none',
            extend: 'excel',
            text: '<i class="fa-solid fa-file-excel"></i> Excel',
            enabled: false // disables button on load   
        },
       
       
    ]
});
</script>
<!-- SweetAlert2 -->
<form id="disputeForm"
            method="POST"
            action="{{ route('patient.disputes.cancel') }}"
            enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="disputeID" name="disputeID">
    
</form>

<script>
function confirmDelete(disputeId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This dispute will be cancelled.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'No, keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            // Call your existing function
            deleteDispute(disputeId);
        }
    });
}
function messages(messages){
    if(messages == ""){
        messages = "No message.";
    }
    Swal.fire({
        title: 'Message from biller',
        text: messages,
        icon: '',
      
      
    });
}

// Example of deleteDispute function (adjust to your logic)
function deleteDispute(id) {
    document.getElementById('disputeID').value = id;
    document.getElementById('disputeForm').submit();
}
</script>

@endsection