@extends('layouts.billing')

@section('content')
<div class="container-fluid p-4">
  <h4 class="mb-3">Settle / Discharge Patients</h4>

  {{-- ── Search & Sort ───────────────────────────────────────────────── --}}
  {{-- <form method="GET" action="{{ route('billing.discharge.index') }}"
        class="row g-2 mb-4 align-items-end">
    <div class="col-auto">
      <label class="form-label small mb-1">Search</label>
      <input type="text" name="search"
             value="{{ request('search') }}"
             class="form-control form-control-sm"
             placeholder="MRN or Name">
    </div>
    <div class="col-auto">
      <label class="form-label small mb-1">Sort by</label>
      <select name="sort_by" class="form-select form-select-sm">
        <option value="patient_id"        @selected(request('sort_by')=='patient_id')       >MRN</option>
        <option value="patient_last_name" @selected(request('sort_by')=='patient_last_name')>Name</option>
        <option value="balance"           @selected(request('sort_by')=='balance')          >Outstanding</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label small mb-1">Direction</label>
      <select name="sort_dir" class="form-select form-select-sm">
        <option value="asc"  @selected(request('sort_dir')=='asc') >Asc</option>
        <option value="desc" @selected(request('sort_dir')=='desc')>Desc</option>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-primary btn-sm">Apply</button>
    </div>
  </form> --}}

  {{-- ── Patients Table ─────────────────────────────────────────────── --}}
  <div class="">
    <table id="myTable" class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>MRN</th>
          <th>Name</th>
          <th >Total Bills ₱</th>
          <th >Outstanding ₱</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
      @forelse($patients as $p)
        @php $modalId = 'depositModal-'.$p['patient_id']; $ttalBills = $p['balance']; $p['balance'] = $p['balance'] - $p['depositBayad'];
        
        if($p['balance'] < 0){
          $p['balance'] = 0;
        }
        @endphp

        <tr>
          <td>PID-{{ str_pad($p['patient_id'],5,'0',STR_PAD_LEFT) }}</td>
          <td>{{ $p['patient_first_name'] }} {{ $p['patient_last_name'] }}</td>
          <td >{{ number_format($ttalBills,2) }}</td>
          <td >{{ number_format($p['balance'],2) }}</td>
          <td class="text-center">
            {{-- History button --}}
            <button class="btn btn-outline-secondary btn-sm me-1"
                    data-bs-toggle="modal" onclick="hideTheDiv('{{ $p['patient_id'] }}' ,'history')" data-bs-target="#{{ $modalId }}">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> History
            </button>

            @if($p['balance'] == 0)
              {{-- Settle --}}
            <form method="POST" action="{{ route('patient.statement', $p['patient_id']) }}"
                    class="d-inline">
                @csrf
                <button class="btn btn-success btn-sm">
                  <i class="fa-solid fa-download"></i></i> Receipt
                </button>
              </form>
            @else
              {{-- Deposit --}}
              <button onclick="hideTheDiv('{{ $p['patient_id'] }}' ,'deposit')" class="btn btn-primary btn-sm"
                      data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                <i class="fa-solid fa-money-bill-wave me-1"></i> Deposit
              </button>
            @endif

            
          </td>
        </tr>

        {{-- Push each modal to a stack so it's OUTSIDE the table --}}
        @push('modals')
          <div class="modal fade" id="{{ $modalId }}" tabindex="-1"
               aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content">
                <form method="POST" action="{{ route('billing.deposits.store') }}">
                  @csrf
                  <input type="hidden" name="patient_id" value="{{ $p['patient_id'] }}">

                  <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">
                      Deposits – {{ $p['patient_first_name'] }} {{ $p['patient_last_name'] }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>

                  <div class="modal-body">
                    {{-- New Deposit --}}
                    <div class="row g-3 mb-4"  id="depo{{ $p['patient_id'] }}">
                      <div class="col-md-6">
                        <label class="form-label">Amount (₱)</label>
                        <input type="number" name="amount" step="0.01" min="0"
                               class="form-control" required autofocus>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Deposit Date</label>
                        <input type="date" name="deposited_at"
                               class="form-control"
                               value="{{ now()->toDateString() }}" required>
                      </div>
                    </div>
                    
                    {{-- History --}}
                    <div id="history{{ $p['patient_id'] }}">

                    
                      <h6 class="fw-semibold mb-2">History</h6>
                      <div class="">
                        
                        <table id="mytableHistory{{ $p['patient_id'] }}" class="table table-sm table-bordered mb-0">
                          <thead class="table-light">
                            <tr><th>Date</th><th >Amount ₱</th></tr>
                          </thead>
                          <tbody>
                            @foreach($p['depositArray'] as $d)
                              <tr>
                                <td>{{ $d->deposited_at->format('Y-m-d') }}</td>
                                <td >{{ number_format($d->amount,2) }}</td>
                              </tr>
                              
                            
                            @endforeach
                          </tbody>
                        </table>
                        <script>
                          $('#mytableHistory{{ $p['patient_id'] }}').DataTable({
                                dom: '<"top d-flex justify-content-between align-items-center mb-3"fB>rt' +
                                      '<"bottom d-flex justify-content-between align-items-center mt-3"i p>',
                                buttons: [
                                    {
                                        extend: 'csv',
                                        text: '<i class="fa-solid fa-file-csv"></i> CSV'
                                    },
                                    {
                                        extend: 'excel',
                                        text: '<i class="fa-solid fa-file-excel"></i> Excel'
                                    },
                                    
                                    {
                                        extend: 'pdf',
                                        text: '<i class="fa-solid fa-file-pdf"></i> PDF',
                                      
                                        
                                    }
                                ]
                            });
                        </script>
                      </div>
                    </div>
                  </div>

                  <div class="modal-footer">
                    <button id="save{{ $p['patient_id'] }}" class="btn btn-primary">Save Deposit</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                      Close
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @endpush
      @empty
        <tr>
          <td colspan="5" class="text-center py-3 text-muted">No active patients.</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <script>
    $('#myTable').DataTable({
        dom: '<"top d-flex justify-content-between align-items-center mb-3"fB>rt' +
              '<"bottom d-flex justify-content-between align-items-center mt-3"i p>',
        buttons: [
            {
                extend: 'csv',
                text: '<i class="fa-solid fa-file-csv"></i> CSV'
            },
            {
                extend: 'excel',
                text: '<i class="fa-solid fa-file-excel"></i> Excel'
            },
            
            {
                extend: 'pdf',
                text: '<i class="fa-solid fa-file-pdf"></i> PDF',
              
                
            }
        ]
    });

    function hideTheDiv(IDdiv, mode){
   
      if(mode == "history"){
        document.getElementById("depo"+IDdiv).style = "display: none";
        document.getElementById("history"+IDdiv).style = "display: ";
        document.getElementById("save"+IDdiv).style = "display: none";
      }
      else{
        document.getElementById("history"+IDdiv).style = "display: none";
        document.getElementById("depo"+IDdiv).style = "display: ";
        document.getElementById("save"+IDdiv).style = "display: ";
      }
    }
 
 
   </script>

  {{-- Pagination --}}
  {{-- <div class="mt-3">
    {{ $patients->links() }}
  </div> --}}
</div>

{{-- Render all the modals collected above --}}
@stack('modals')
@endsection
