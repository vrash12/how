{{-- resources/views/billing/dispute/queue.blade.php --}}
@extends('layouts.billing')

@section('content')
<div class="container-fluid min-vh-100 p-4 d-flex flex-column">

  <header class="mb-3">
    <h4 class="hdng"><i class="fa-solid fa-ticket me-2"></i>Dispute Tickets</h4>
    <p class="text-muted">Manage billing disputes that require attention and resolution.</p>
  </header>

  
 @if(session('success'))
      <div class="alert alert-success">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
      </div>
    @endif
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive rounded" style="padding: 20px;max-height: 900px;">
        <table id="myTable"  class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date Filed</th>
                            <th>Patient #</th>
                            <th>Patient</th>
                            <th>Item Disputed</th>
                            <th>Reason</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
    @forelse($disputes as $dispute)
        <tr>
            <td>{{ \Carbon\Carbon::parse($dispute['datetime'])->format('M d, Y') }}</td>
             <td>PID-{{ str_pad($dispute['patient_id'], 5, '0', STR_PAD_LEFT) }}</td>
            <td>{{ $dispute['fullname'] }}</td>
<td>{{ $dispute['desc'] ?? '' }}</td>
<td>{{ strlen($dispute['reason']) > 20 ? substr($dispute['reason'], 0, 20) . '...' : $dispute['reason'] }}</td>

@php
    $date = \Carbon\Carbon::parse($dispute['datetime']);
    $expired = $date->addMinutes(30)->isPast();
@endphp
<td class="text-center">
                @php
                    $badge = [
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    ][$dispute['status']] ?? 'secondary';
                @endphp
                <span class="badge bg-{{$badge}} text-capitalize">{{ $dispute['status'] }}</span>
            </td>

<td class="text-center">
   @php
      $viewButtons = true;
    @endphp
  @if($dispute['status'] != 'pending')
    @php
      $viewButtons = false;
    @endphp
  @endif
    <button onclick=""
            class="btn btn-success btn-sm view-details"

             data-date="{{ \Carbon\Carbon::parse($dispute['datetime'])->format('M d, Y h:i A') }}"
            data-patient="{{ $dispute['fullname'] }}"
            data-patientid="{{ str_pad($dispute['patient_id'], 6, '0', STR_PAD_LEFT) }}"
            data-reason="{{ $dispute['reason'] }}"
            data-status="{{ $dispute['status'] }}"
            data-desc="{{ $dispute['desc'] ?? '' }}"
            data-disputeID="{{ $dispute['id'] }}"
            data-disputableID="{{ $dispute['disputableID'] }}"
            data-additional="{{ $dispute['additional'] }}"
            data-file="{{ $dispute['file'] ?? '' }}"
            data-disputable_type="{{ $dispute['disputable_type']  }}"
            data-oldAmount="₱{{ number_format($dispute['oldAmount'],2) }} "
            data-oldAmountNotMoney="{{$dispute['oldAmount']}}"

            data-finalAmount="{{$dispute['finalAmount']}}"
            data-messageFromBiller="{{$dispute['messageFromBiller']}}"

            data-buttons="{{ $viewButtons  }}"
            >
      View Details
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
<form id="disputeForm"
      method="POST"
      action="{{ route('billing.disputes.change') }}"
      enctype="multipart/form-data">
        @csrf
<input type="hidden" id="disputableID" name="disputableID">
        <input type="hidden" id="disputeID" name="disputeID">
    <input type="hidden" id="disputable_type" name="disputable_type">
    <input type="hidden" id="what" name="what">
    <input type="hidden" id="approvedAmountInput" name="approvedAmountInput">
    <input type="hidden" id="approvedTextInput" name="approvedTextInput">
    <input type="hidden" id="oldAmount" name="oldAmount">
</form>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function () {
            let dateFiled   = this.getAttribute('data-date');
            let patient     = this.getAttribute('data-patient');
            let patientId   = this.getAttribute('data-patientid');
            let reason      = this.getAttribute('data-reason');
            let status      = this.getAttribute('data-status');

            let finalAmount      = this.getAttribute('data-finalAmount');
            let messageFromBiller      = this.getAttribute('data-messageFromBiller');


            let desc        = this.getAttribute('data-desc');
             var oldAmountNotMoney        = this.getAttribute('data-oldAmountNotMoney');
             var displayNone = "";
            
             if(status == 'approved' && finalAmount != ""){
              oldAmountNotMoney = finalAmount;
             }
             if(messageFromBiller == null){
              messageFromBiller = "";
             }
            let additional  = this.getAttribute('data-additional');
            if(additional == ""){
              additional = "No content.";
            }
            let file        = this.getAttribute('data-file');
            let id          = this.getAttribute('data-disputeID');
            let buttons      = this.getAttribute('data-buttons');
           let disputableID      = this.getAttribute('data-disputableID');
          let disputable_type          = this.getAttribute('data-disputable_type');

          let oldAmount          = this.getAttribute('data-oldAmount');


            let fileHtml = '';
            if (file) {
                fileHtml = `
                    <p><strong>Supporting Document:</strong></p>
                    <a href="/storage/${file}" target="_blank" ><img src="/storage/${file}" 
                         alt="Supporting Document" 
                         style="max-width:100%; max-height:300px; border:1px solid #ccc; border-radius:8px;"></a>
                `;
            }

            Swal.fire({
                title: '',
                html: `
                    <div class="text-start">
                        <h3>Dispute Details</h3>
                        <br>
                        <p><strong>Date Filed:</strong> ${dateFiled}</p>
                        <p><strong>Patient #:</strong> ${patientId}</p>
                        <p><strong>Patient:</strong> ${patient}</p>
                        <p><strong>Item Disputed:</strong> ${desc}</p>
                        <p><strong>Amount:</strong> ${oldAmount}</p>
                        <p><strong>Status:</strong> ${status}</p>
                        <p><strong>Reason:</strong> ${reason}</p>
                        <details>
                          <summary><b>Additional Details</b></summary>
                          <p>
                            ${additional}
                          </p>
                        </details>
                        
                        ${fileHtml}

                        <br><br>
                        <label for="approvedAmount"><strong>Amount to Subtract:</strong> <i>(if approved)</i></label><br>
                        ${oldAmount} - <input type="number" style="width: 150px; outline:none;padding: 3px 7px;border: 1px solid lightgray;border-radius: 3px" id="approvedAmount" class="" min="1" max="${oldAmountNotMoney}" value="${oldAmountNotMoney}" placeholder="Enter amount">
                    <br><br>
                    <label for="approvedAmount"><strong>Additional Message:</strong> <i>(optional)</i></label><br>
                        <input type="text" style="width: 150px; outline:none;padding: 3px 7px;border: 1px solid lightgray;border-radius: 3px" id="approvedText"  value="${messageFromBiller}" >
                    
                        </div>
                `,
                showCancelButton: true,
                showDenyButton: buttons,
                showConfirmButton: buttons,
                confirmButtonText: 'Approve',
                denyButtonText: 'Reject',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                customClass: {
                    actions: 'swal2-actions-left'
                },
                width: 600,
            }).then((result) => {
                if (result.isConfirmed) {
                      const approvedAmount = Number(document.getElementById('approvedAmount').value);
                      const approvedText = document.getElementById('approvedText').value;
                      if (approvedAmount < 1 || approvedAmount > oldAmountNotMoney) {
                          Swal.fire({
                              icon: 'warning',
                              title: 'Invalid amount',
                              text: `Amount must be between 1 and ${oldAmountNotMoney}.`
                          });
                          return; // stop submission
                      }

                      document.getElementById('disputeID').value = id;
                      document.getElementById('disputable_type').value = disputable_type;
                      document.getElementById('disputableID').value = disputableID;
                      document.getElementById('what').value = 'approved';
                        document.getElementById('oldAmount').value = oldAmountNotMoney;
                      document.getElementById('approvedAmountInput').value = approvedAmount; // hidden input in form
                      document.getElementById('approvedTextInput').value = approvedText; // hidden input in form
                     
                      document.getElementById('disputeForm').submit();
                  }

                 else if (result.isDenied) {
                   const approvedText = document.getElementById('approvedText').value;
                    document.getElementById('disputeID').value = id;
                    document.getElementById('disputable_type').value = disputable_type;
                    document.getElementById('what').value = 'rejected';
                      document.getElementById('approvedTextInput').value = approvedText; // hidden input in form

                    document.getElementById('disputableID').value = disputableID;
                    document.getElementById('disputeForm').submit();
                } 
            });

        });
    });
});


</script>

@endsection
