@php
use App\Models\PharmacyChargeItem;
use App\Models\notifications_latest;
@endphp
@php
  $usersff    = Auth::user();
  $patientsfff = $usersff->patient;

  $patientIDD = $patientsfff->patient_id;
   $patientStatus = $patientsfff->medication_finished;
   $isFullyPaid = $totals['balance'] == 0;
@endphp

@extends('layouts.patients')

@section('content')
<div class="card-body p-4">
    {{-- Mobile Responsive Styles --}}
    <style>
      /* Responsive styling for billing page */
      @media (max-width: 767px) {
        .billing-cards {
          flex-direction: column;
        }
        .billing-card {
          margin-bottom: 15px;
          width: 100%;
        }
        .table-responsive {
          margin-bottom: 20px;
        }
        .dt-buttons {
          margin-bottom: 15px;
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
        }
        .dt-button {
          margin: 0 5px 5px 0;
          padding: 5px 10px;
        }
        .dataTables_filter {
          width: 100%;
          margin-top: 10px;
          text-align: center !important;
        }
        .dataTables_filter input {
          width: 100% !important;
          margin-left: 0 !important;
          max-width: 100%;
        }
        .dataTables_info, 
        .dataTables_paginate {
          text-align: center !important;
          float: none !important;
          display: block;
          margin-top: 15px;
          width: 100%;
        }
        /* Make sure the actions column doesn't overflow */
        .myTable td:last-child {
          min-width: 150px;
        }
      }
      
      /* Activity timeline styles */
      .activity-timeline {
        margin-bottom: 20px;
      }
      .activity-item {
        background: #fff;
        border-radius: 8px;
        padding: 15px 20px;
        margin-bottom: 10px;
        border-left: 5px solid #ccc;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      }
      .activity-item-blue {
        border-left-color: #3b82f6; /* blue */
      }
      .activity-item-green {
        border-left-color: #22c55e; /* green */
      }
      .activity-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
      }
      .activity-title {
        font-weight: bold;
        font-size: 16px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
      }
      .activity-date {
        font-size: 12px;
        color: #6b7280;
      }
      .activity-description {
        margin: 8px 0;
        color: #374151;
        word-break: break-word;
      }
      .activity-footer {
        font-size: 13px;
        color: #555;
        display: flex;
        align-items: center;
        gap: 6px;
      }
      .myTable {
        border: 1px solid #d1d1d1;
        border-radius: 5px;
        overflow: hidden;
      }
      .myTable th {
        background-color: #80b1e3;
        font-weight: bold;
        text-transform: uppercase;
      }
      .myTable td {
        vertical-align: middle;
      }
      .progress-container {
        width: 100%;
        box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
        border:1px solid #0000002d;
        border-radius: 10px;
        margin-top:20px;
        padding: 23px 30px;
        padding-bottom: 18px;
        font-family: Arial, sans-serif;
      }
      .progress-bar {
        background: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
        height: 11px;
        margin-bottom: 8px;
      }
      .progress-fill {
        height: 100%;
        background: #2e7d32; /* green */
        width: calc({{$paymentsMade}} / {{max(1, $totals['total'])}} * 100%); /* deposit / totalBill */
      }
      .progress-text {
        font-size: 14px;
        color: #333;
      }
      .spaceSearch {
        margin-bottom: 20px;
      }
    </style>

    {{-- Thank You Alert - Show when balance is 0 --}}
    @if($isFullyPaid)
    <div class="alert alert-success d-flex align-items-center mb-4">
      <i class="fas fa-check-circle me-3 fs-4"></i>
      <div>
        <h5 class="mb-1">Thank you for your payment!</h5>
        <p class="mb-0">Your account has been fully settled. We appreciate your prompt payment and hope you had a comfortable stay with us.</p>
        <div class="mt-2">
          <a href="{{ route('patient.billing.statement',['admission_id'=>$admissionId]) }}" class="btn btn-sm btn-success">
            <i class="fas fa-download me-1"></i> Download Receipt
          </a>
        </div>
      </div>
    </div>
    @endif

    {{-- Discharge Alert - Show when medication_finished = 1 --}}
    @if($patientStatus == 1 && !$isFullyPaid)
    <div class="alert alert-success d-flex align-items-center mb-4">
      <i class="fas fa-check-circle me-3 fs-4"></i>
      <div>
        <h5 class="mb-1">You are ready for discharge!</h5>
        <p class="mb-0">Your medication has been completed. Please proceed to the billing section to complete your discharge process and settle any remaining balance.</p>
      </div>
    </div>
    @endif

    {{-- Heading & Admission selector --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="fw-bold text-primary mb-1">Billing and Transactions</h4>
        <small class="text-muted">
          Welcome! Here you can monitor an itemized version of your bill.
        </small>
      </div>
    </div>
    
    @if($patientStatus == 0)
    {{-- Info banner --}}
    <div class="alert alert-warning d-flex align-items-center py-2 mb-4">
      <i class="fas fa-info-circle me-2"></i>
      <div>
        <strong>Important:</strong> Disputed items will appear with a red badge until resolved.
      </div>
    </div>
    @endif
    
    {{-- KPI Tiles - UPDATED FOR MOBILE --}}
    <div class="billing-cards d-flex flex-wrap gap-3">
      <div class="billing-card flex-grow-1 rounded shadow-sm border p-3">
        <h6 class="m-0">Balance</h6>
        <h1 style="color: #753c38">₱{{number_format($totals['balance'], 2)}}</h1>
      </div>
      
      <div class="billing-card flex-grow-1 rounded shadow-sm border p-3">
        <h6 class="m-0">Deposit</h6>
        <h1 style="color:#1f7046;">₱{{number_format($paymentsMade, 2)}}</h1>
      </div>
    </div>

    <div class="progress-container">
      <div class="progress-bar">
        <div class="progress-fill"></div>
      </div>
      <div class="progress-text">
        ₱{{number_format($paymentsMade, 2)}} deposit out of ₱{{number_format($totals['total'], 2)}} bill
      </div>
    </div>
    
{{-- Bill Items Summary --}}
<div class="card shadow-sm border mt-4 mb-5">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h5 class="m-0 fw-bold">Bill Summary</h5>
    {{-- Days Admitted Banner --}}
    <span class="badge bg-primary">
      <i class="fas fa-calendar-check me-1"></i> 
      {{ $daysAdmitted }} {{ Str::plural('day', $daysAdmitted) }} admitted
    </span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Service</th>
            <th class="text-end">Amount</th>
          </tr>
        </thead>
        <tbody>
          @php
            $services = [
              ['label' => "Bed/Room Rate (₱{$dailyBedRate}" . " per Day)", 'value' => $bedRate],
              ['label' => "Doctor Fee (₱{$dailyDoctorFee}" . " per Day)", 'value' => $doctorFee],
              ['label' => 'Pharmacy Charges', 'value' => $pharmacyTotal],
              ['label' => 'Laboratory Fee', 'value' => $laboratoryFee],
              ['label' => 'Operating Room Service', 'value' => $ORFee],
              ['label' => 'Discount Applied', 'value' => $totals['discount']]
            ];
          @endphp
          
          @foreach($services as $service)
            <tr>
              <td>{{ $service['label'] }}</td>
              <td class="text-end">₱{{ number_format($service['value'], 2) }}</td>
            </tr>
          @endforeach
          
          <tr class="table-light">
            <th>Total Bill</th>
            <th class="text-end">₱{{ number_format($totals['total'], 2) }}</th>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

    {{-- SURGERY TABLE - UPDATED FOR MOBILE --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h3 class="text-primary m-0">Surgery</h3>
      <a href="{{route('patient.disputes.mine')}}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-clipboard-list"></i> My Disputes
      </a>
    </div>
    
    <div class="table-responsive mb-5">
      <table class="table table-bordered table-hover align-middle myTable" id="surgeryTable" style="width:100%">
        <thead>
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th class="text-end">Amount</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $row)
            @php
              $checkFirst = $row->provider_label ?? $row->provider;
            @endphp
            @if($checkFirst == "Operating room" && $checkFirst != "—")
              @php
                $itemId = data_get($row, 'billing_item_id', optional($row->children->first())->billing_item_id);
               
                if(isset($allDispute[$row->provider][$row->idAss]) && $allDispute[$row->provider][$row->idAss] == 'pending'){
                  $row->status = "dispute pending";
                }
                $buttonDisabled = '';
                if(isset($allDispute[$row->provider][$row->idAss]) ){
                  $buttonDisabled = 'disabled';
                }
            
                if($row->status == 'pending'){
                  $buttonDisabled = 'disabled';
                }
            
                $badge  = [
                  'complete'=>'success','completed'=>'success', 'dispensed' => 'success',
                  'pending'=>'warning','disputed'=>'success','mixed'=>'secondary','dispute pending'=>'danger',
                ][$row->status] ?? 'secondary';
              @endphp
              
              <tr class="tr{{$row->idAss}}">
                <td>{{ \Carbon\Carbon::parse($row->billing_date)->format('Y-m-d') }}</td>
                <td>{{ $row->description }}</td>
                <td class="text-end">₱{{ number_format($row->amount,2) }}</td>
                <td><span class="badge bg-{{ $badge }} text-capitalize">{{ $row->status }}</span></td>
                <td class="text-center">
                  <button type="button"
                    class="btn btn-primary btn-sm mb-1"
                    data-bs-toggle="modal"
                    data-bs-target="#detailsModaloperating{{$row->idAss}}"
                    data-items='@json($row->children)'
                    data-provider="{{ $row->provider }}"
                    data-timeline='@json($row->children->flatMap(fn($c)=>$c->timeline)->sortBy("stamp")->values())'>
                    Details
                  </button>
                  
                  @if($itemId)
                    @php
                      $isBalanceZero = $totals['balance'] == 0;
                    @endphp
                    <button 
                      {{$buttonDisabled}} 
                      @if($isBalanceZero) disabled @endif
                      onclick="getId('{{$row->idAss}}', '{{$row->provider}}', '{{ $row->description }}', '{{ $row->amount }}')"
                      type="button"
                      class="btn btn-danger btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#disputeModal"
                      data-item-id="{{ $itemId }}"
                      data-date="{{ \Carbon\Carbon::parse($row->billing_date)->format('Y-m-d') }}"
                      data-time="{{ \Carbon\Carbon::parse($row->billing_date)->format('h:ia') }}"
                      data-ref="{{ $row->ref_no }}"
                      data-description="{{ $row->description }}"
                      data-provider="{{ $row->provider }}"
                      data-amount="₱{{ number_format($row->amount,2) }}">
                      Dispute
                    </button>
                  @else
                    <span class="text-muted">No details</span>
                  @endif
                </td>
              </tr>

              {{-- Surgery Modal - Unchanged --}}
              <div class="modal fade" id="detailsModaloperating{{$row->idAss}}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Surgery Details</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <h5 class="modal-title fw-bold">Complete Charge History</h5>
                      <div class="activity-timeline">
                        @php
                          // For Operating Room - Find both direct matches and related entries 
                          $alltransaction = notifications_latest::where('idReference', $row->idAss)
                            ->where('category', 'operating')
                            ->where('sendTo_id', $patientIDD)
                            ->get();
                          
                          // Also look for doctor's procedure order notification by service name/description
                          $doctorNotifications = notifications_latest::where('message', 'like', '%' . $row->description . '%')
                            ->where('category', 'operating')
                            ->where('sendTo_id', $patientIDD)
                            ->get();
                          
                          // Merge both collections
                          $alltransaction = $alltransaction->concat($doctorNotifications);
                          
                          // Sort by creation date (newest first)
                          $alltransaction = $alltransaction->sortByDesc('created_at');
                          
                          // Create a collection to track seen messages to prevent duplicates
                          $seenMessages = collect();
                          $find = false;
                        @endphp
                        
                        @foreach($alltransaction as $noti)
                          @php 
                            // Only process this notification if we haven't seen this message before
                            if (!$seenMessages->contains($noti->message)) {
                              $find = true;
                              $seenMessages->push($noti->message);
                          @endphp
                          <div class="activity-item {{ $noti->titleReference == 'Charge created' || $noti->type == 'Action' ? 'activity-item-blue' : 'activity-item-green' }}">
                            <div class="activity-header">
                              <div class="activity-title">
                                {{$noti->titleReference}} 
                                <div class="activity-date">{{$noti->created_at}}</div>
                              </div>
                            </div>
                            <div class="activity-description">{{$noti->message}}</div>
                            <div class="activity-footer">👤 {{strtoupper($noti->from_name)}}</div>
                          </div>
                          @php
                            } // End of if condition
                          @endphp
                        @endforeach
                        
                        @if(!$find)
                          <span class="text-muted">Nothing to show.</span>
                        @endif
                      </div>
                      
                      <h5 class="modal-title fw-bold mt-4">Charge Details</h5>
                      <div class="bg-light p-3 rounded mt-2">
                        <div class="row g-3">
                          <div class="col-sm-6">
                            <strong>Date:</strong> {{ \Carbon\Carbon::parse($row->billing_date)->format('Y-m-d') }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Time:</strong> {{ \Carbon\Carbon::parse($row->billing_date)->format('h:ia') }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Reference:</strong> {{ $row->ref_no }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Description:</strong> {{ $row->description }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Amount:</strong> ₱{{ number_format($row->amount,2) }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Provider:</strong> {{ $row->provider }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endif
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- LABORATORY TABLE - Follow same pattern as Surgery table --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h3 class="text-primary m-0">Laboratory</h3>
    </div>
    
    <div class="table-responsive mb-5">
      <table class="table table-bordered table-hover align-middle myTable" id="laboratoryTable" style="width:100%">
        <thead>
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th class="text-end">Amount</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $row)
            @php
              $checkFirst = $row->provider_label ?? $row->provider;
            @endphp
            @if($checkFirst == "Laboratory" && $checkFirst != "—")
              @php
                $itemId = data_get($row, 'billing_item_id', optional($row->children->first())->billing_item_id);
               
                if(isset($allDispute[$row->provider][$row->idAss]) && $allDispute[$row->provider][$row->idAss] == 'pending'){
                  $row->status = "dispute pending";
                }
                $buttonDisabled = '';
                if(isset($allDispute[$row->provider][$row->idAss]) ){
                  $buttonDisabled = 'disabled';
                }
            
                if($row->status == 'pending'){
                  $buttonDisabled = 'disabled';
                }
            
                $badge  = [
                  'complete'=>'success','completed'=>'success', 'dispensed' => 'success',
                  'pending'=>'warning','disputed'=>'success','mixed'=>'secondary','dispute pending'=>'danger',
                ][$row->status] ?? 'secondary';
              @endphp
              
              <tr class="tr{{$row->idAss}}">
                <td>{{ \Carbon\Carbon::parse($row->billing_date)->format('Y-m-d') }}</td>
                <td>{{ $row->description }}</td>
                <td class="text-end">₱{{ number_format($row->amount,2) }}</td>
                <td><span class="badge bg-{{ $badge }} text-capitalize">{{ $row->status }}</span></td>
                <td class="text-center">
                  <button type="button"
                    class="btn btn-primary btn-sm mb-1"
                    data-bs-toggle="modal"
                    data-bs-target="#detailsModallaboratory{{$row->idAss}}"
                    data-items='@json($row->children)'
                    data-provider="{{ $row->provider }}"
                    data-timeline='@json($row->children->flatMap(fn($c)=>$c->timeline)->sortBy("stamp")->values())'>
                    Details
                  </button>
                  
                  @if($itemId)
                    @php
                      $isBalanceZero = $totals['balance'] == 0;
                    @endphp
                    <button 
                      {{$buttonDisabled}} 
                      @if($isBalanceZero) disabled @endif
                      onclick="getId('{{$row->idAss}}', '{{$row->provider}}', '{{ $row->description }}', '{{ $row->amount }}')"
                      type="button"
                      class="btn btn-danger btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#disputeModal"
                      data-item-id="{{ $itemId }}"
                      data-date="{{ \Carbon\Carbon::parse($row->billing_date)->format('Y-m-d') }}"
                      data-time="{{ \Carbon\Carbon::parse($row->billing_date)->format('h:ia') }}"
                      data-ref="{{ $row->ref_no }}"
                      data-description="{{ $row->description }}"
                      data-provider="{{ $row->provider }}"
                      data-amount="₱{{ number_format($row->amount,2) }}">
                      Dispute
                    </button>
                  @else
                    <span class="text-muted">No details</span>
                  @endif
                </td>
              </tr>

              {{-- Laboratory Modal - Unchanged --}}
              <div class="modal fade" id="detailsModallaboratory{{$row->idAss}}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Laboratory Details</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <h5 class="modal-title fw-bold">Complete Charge History</h5>
                      <div class="activity-timeline">
                        @php
                          // For Laboratory - Find both direct matches and related entries
                          $alltransaction = notifications_latest::where('idReference', $row->idAss)
                            ->where('category', 'laboratory')
                            ->where('sendTo_id', $patientIDD)
                            ->get();
                          
                          // Also look for doctor's lab request notification by service name/description
                          $doctorNotifications = notifications_latest::where('message', 'like', '%' . $row->description . '%')
                            ->where('category', 'laboratory')
                            ->where('sendTo_id', $patientIDD)
                            ->get();
                          
                          // Merge both collections
                          $alltransaction = $alltransaction->concat($doctorNotifications);
                          
                          // Sort by creation date (newest first)
                          $alltransaction = $alltransaction->sortByDesc('created_at');
                          
                          // Create a collection to track seen messages to prevent duplicates
                          $seenMessages = collect();
                          $find = false;
                        @endphp
                        
                        @foreach($alltransaction as $noti)
                          @php 
                            // Only process this notification if we haven't seen this message before
                            if (!$seenMessages->contains($noti->message)) {
                              $find = true;
                              $seenMessages->push($noti->message);
                          @endphp
                          <div class="activity-item {{ $noti->titleReference == 'Charge created' || $noti->type == 'Action' ? 'activity-item-blue' : 'activity-item-green' }}">
                            <div class="activity-header">
                              <div class="activity-title">
                                {{$noti->titleReference}} 
                                <div class="activity-date">{{$noti->created_at}}</div>
                              </div>
                            </div>
                            <div class="activity-description">{{$noti->message}}</div>
                            <div class="activity-footer">👤 {{strtoupper($noti->from_name)}}</div>
                          </div>
                          @php
                            } // End of if condition
                          @endphp
                        @endforeach
                        
                        @if(!$find)
                          <span class="text-muted">Nothing to show.</span>
                        @endif
                      </div>
                      
                      <h5 class="modal-title fw-bold mt-4">Charge Details</h5>
                      <div class="bg-light p-3 rounded mt-2">
                        <div class="row g-3">
                          <div class="col-sm-6">
                            <strong>Date:</strong> {{ \Carbon\Carbon::parse($row->billing_date)->format('Y-m-d') }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Time:</strong> {{ \Carbon\Carbon::parse($row->billing_date)->format('h:ia') }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Reference:</strong> {{ $row->ref_no }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Description:</strong> {{ $row->description }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Amount:</strong> ₱{{ number_format($row->amount,2) }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Provider:</strong> {{ $row->provider }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endif
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- PHARMACY TABLE - Follow same pattern as Surgery table --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h3 class="text-primary m-0">Pharmacy</h3>
    </div>
    
    <div class="table-responsive mb-5">
      <table class="table table-bordered table-hover align-middle myTable" id="pharmacyTable" style="width:100%">
        <thead>
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Quantity</th>
            <th class="text-end">Amount</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $row)
            @php
              $checkFirst = $row->provider_label ?? $row->provider;
            @endphp
            @if($checkFirst == "Pharmacy" && $checkFirst != "—")
              @php
                $itemId = data_get($row, 'billing_item_id', optional($row->children->first())->billing_item_id);
               
                if(isset($allDispute[$row->provider][$row->idAss]) && $allDispute[$row->provider][$row->idAss] == 'pending'){
                  $row->status = "dispute pending";
                }
                $buttonDisabled = '';
                if(isset($allDispute[$row->provider][$row->idAss]) ){
                  $buttonDisabled = 'disabled';
                }
            
                if($row->status == 'pending'){
                  $buttonDisabled = 'disabled';
                }
            
                $badge  = [
                  'complete'=>'success','completed'=>'success', 'dispensed' => 'success',
                  'pending'=>'warning','disputed'=>'success','mixed'=>'secondary','dispute pending'=>'danger',
                ][$row->status] ?? 'secondary';
              @endphp
              
              <tr class="tr{{$row->idAss}}">
                <td>{{ \Carbon\Carbon::parse($row->billing_date)->format('Y-m-d') }}</td>
                <td>{{ $row->description }}</td>
                @php
                  $quantity = PharmacyChargeItem::where('id', $row->idAss)->first()->dispensed_quantity;
                @endphp
               <td>{{ $quantity }}</td>
                <td class="text-end">₱{{ number_format($row->amount,2) }}</td>
                <td><span class="badge bg-{{ $badge }} text-capitalize">{{ $row->status }}</span></td>
                <td class="text-center">
                  <button type="button"
                    class="btn btn-primary btn-sm mb-1"
                    data-bs-toggle="modal"
                    data-bs-target="#detailsModal{{$row->idAss}}"
                    data-items='@json($row->children)'
                    data-provider="{{ $row->provider }}"
                    data-timeline='@json($row->children->flatMap(fn($c)=>$c->timeline)->sortBy("stamp")->values())'>
                    Details
                  </button>
                  
                  @if($itemId)
                    @php
                      $isBalanceZero = $totals['balance'] == 0;
                    @endphp
                    <button 
                      {{$buttonDisabled}} 
                      @if($isBalanceZero) disabled @endif
                      onclick="getId('{{$row->idAss}}', '{{$row->provider}}', '{{ $row->description }}', '{{ $row->amount }}')"
                      type="button"
                      class="btn btn-danger btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#disputeModal"
                      data-item-id="{{ $itemId }}"
                      data-date="{{ \Carbon\Carbon::parse($row->billing_date)->format('Y-m-d') }}"
                      data-time="{{ \Carbon\Carbon::parse($row->billing_date)->format('h:ia') }}"
                      data-ref="{{ $row->ref_no }}"
                      data-description="{{ $row->description }}"
                      data-provider="{{ $row->provider }}"
                      data-amount="₱{{ number_format($row->amount,2) }}">
                      Dispute
                    </button>
                  @else
                    <span class="text-muted">No details</span>
                  @endif
                </td>
              </tr>

              {{-- Pharmacy Modal - Unchanged --}}
              <div class="modal fade" id="detailsModal{{$row->idAss}}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Pharmacy Details</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <h5 class="modal-title fw-bold">Complete Charge History</h5>
                      <div class="activity-timeline">
                        @php
                          // Start with direct matches to this item
                          $alltransaction = notifications_latest::where('idReference', $row->idAss)
                            ->where('category', 'pharmacy')
                            ->where('sendTo_id', $patientIDD)
                            ->get();
                          
                          // Also look for doctor's prescription notification by service name/description
                          $doctorNotifications = notifications_latest::where('message', 'like', '%' . $row->description . '%')
                            ->where('category', 'pharmacy')
                            ->where('sendTo_id', $patientIDD)
                            ->where('from_name', 'Doctor')
                            ->get();
                          
                          // Merge both collections
                          $alltransaction = $alltransaction->concat($doctorNotifications);
                          
                          // Sort by creation date (newest first)
                          $alltransaction = $alltransaction->sortByDesc('created_at');
                          
                          // Create a collection to track seen messages to prevent duplicates
                          $seenMessages = collect();
                          $find = false;
                        @endphp
                        
                        @foreach($alltransaction as $noti)
                          @php 
                            // Only process this notification if we haven't seen this message before
                            if (!$seenMessages->contains($noti->message)) {
                              $find = true;
                              $seenMessages->push($noti->message);
                          @endphp
                          <div class="activity-item {{ $noti->titleReference == 'Charge created' || $noti->type == 'Action' ? 'activity-item-blue' : 'activity-item-green' }}">
                            <div class="activity-header">
                              <div class="activity-title">
                                {{$noti->titleReference}} 
                                <div class="activity-date">{{$noti->created_at}}</div>
                              </div>
                            </div>
                            <div class="activity-description">{{$noti->message}}</div>
                            <div class="activity-footer">👤 {{strtoupper($noti->from_name)}}</div>
                          </div>
                          @php
                            } // End of if condition
                          @endphp
                        @endforeach
                        
                        @if(!$find)
                          <span class="text-muted">Nothing to show.</span>
                        @endif
                      </div>
                      
                      <h5 class="modal-title fw-bold mt-4">Charge Details</h5>
                      <div class="bg-light p-3 rounded mt-2">
                        <div class="row g-3">
                          <div class="col-sm-6">
                            <strong>Date:</strong> {{ \Carbon\Carbon::parse($row->billing_date)->format('Y-m-d') }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Time:</strong> {{ \Carbon\Carbon::parse($row->billing_date)->format('h:ia') }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Reference:</strong> {{ $row->ref_no }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Description:</strong> {{ $row->description }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Amount:</strong> ₱{{ number_format($row->amount,2) }}
                          </div>
                          <div class="col-sm-6">
                            <strong>Provider:</strong> {{ $row->provider }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endif
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-end mt-4">
      @if($patientStatus == 1)
        <a href="{{ route('patient.billing.statement',['admission_id'=>$admissionId]) }}" class="btn btn-primary">
          <i class="fa-solid fa-print me-2"></i> Download Statement
        </a>
      @else
        <button type="button" class="btn btn-secondary" disabled data-bs-toggle="tooltip" 
                data-bs-placement="top" title="Available after your doctor marks your treatment as complete">
          <i class="fa-solid fa-print me-2"></i> Download Statement
          <span class="small ms-1">(Pending treatment completion)</span>
        </button>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
              return new bootstrap.Tooltip(tooltipTriggerEl);
            });
          });
        </script>
      @endif
    </div>
  </div>

  {{-- DISPUTE MODAL --}}
  <div class="modal fade" id="disputeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-primary fw-bold">Request for Clarification</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="disputeForm"
              method="POST"
              action="{{ route('patient.disputes.store') }}"
              enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="bill_item_id" id="d-item-id">
          <div class="modal-body">
            <div class="bg-light rounded p-3 mb-4">
              <div class="row">
                <div class="col-4 small"><strong>Date:</strong> <span id="d-date"></span></div>
                <div class="col-4 small"><strong>Reference:</strong> <span id="d-ref"></span></div>
                <div class="col-4 small"><strong>Amount:</strong> <span id="d-amount"></span></div>
                <div class="col-4 small"><strong>Time:</strong> <span id="d-time"></span></div>
                <div class="col-4 small"><strong>Description:</strong> <span id="d-desc"></span></div>
                <div class="col-4 small"><strong>Provider:</strong> <span id="d-prov"></span></div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Reason (Dispute Type)</label>
              <input type="text" name="reason" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Additional Details</label>
              <textarea name="details" rows="3" class="form-control"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Supporting Document</label>
              <input type="file" name="document" class="form-control" accept="image/*">

            </div>
          </div>
          <input type="hidden" name="idDispute" id="idDispute">
          <input type="hidden" name="desc" id="desc">
          <input type="hidden" name="type" id="typeDispute">
           <input type="hidden" name="oldAmount" id="oldAmount">
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Initialize DataTables with mobile-friendly options
  // $('.myTable').DataTable({
  //   responsive: true,
  //   dom: '<"top d-flex flex-wrap justify-content-between align-items-center mb-3"fB>rt' +
  //        '<"bottom d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
  //   buttons: [
  //     {
  //       extend: 'csv',
  //       text: '<i class="fa-solid fa-file-csv"></i> CSV',
  //       className: 'btn btn-sm btn-outline-secondary'
  //     },
  //     {
  //       extend: 'excel',
  //       text: '<i class="fa-solid fa-file-excel"></i> Excel',
  //       className: 'btn btn-sm btn-outline-secondary'
  //     }
  //   ],
  //   language: {
  //     search: "",
  //     searchPlaceholder: "Search...",
  //     paginate: {
  //       previous: "<i class='fa fa-angle-left'></i>",
  //       next: "<i class='fa fa-angle-right'></i>"
  //     }
  //   },
  //   columnDefs: [
  //     { 
  //       responsivePriority: 1, 
  //       targets: [0, 1, 4] // Date, Description, Actions are most important
  //     },
  //     {
  //       responsivePriority: 2,
  //       targets: 3 // Status column is next priority
  //     }
  //   ]
  // });

  // Dispute modal population
  document.querySelectorAll('.btn-dispute').forEach(btn => {
    btn.addEventListener('click', e => {
      const d = e.currentTarget.dataset;
      document.getElementById('d-item-id').value     = d.itemId;
      document.getElementById('d-date').textContent   = d.date;
      document.getElementById('d-ref').textContent    = d.ref;
      document.getElementById('d-amount').textContent = d.amount;
      document.getElementById('d-time').textContent   = d.time;
      document.getElementById('d-desc').textContent   = d.description;
      document.getElementById('d-prov').textContent   = d.provider;
    });
  });

  function getId(id, type, desc, oldAmount){
    document.getElementById('typeDispute').value = type;
    document.getElementById('idDispute').value = id;
    document.getElementById('desc').value = desc;
    document.getElementById('oldAmount').value = oldAmount;
  }
  
  // Make getId global
  window.getId = getId;
});
</script>
@endpush
