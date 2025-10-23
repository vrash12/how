@php
  // ensure $patient is always defined
  $patient   = $patient ?? null;
  $admission = optional($patient)->admissionDetail;
  $medical   = optional($patient)->medicalDetail;
  $billing   = optional($patient)->billingInformation;
  
  // Import the BillingCalculationTrait (if it's not already imported in the controller)
  use App\Traits\BillingCalculationTrait;
@endphp

@csrf
@isset($patient)
  @method('PUT')
@endisset

<style>
  /* mark completed tabs with a green check */
  .nav-tabs .nav-link.completed {
    color: #28a745;
  }
  .nav-tabs .nav-link.completed::after {
    content: ' ✓';
    color: #28a745;
    font-weight: bold;
  }
</style>
<p style="margin: 0; padding: 0">Account Type</p>
<select style="width: 200px" id="AccTypeSelect" onchange="choose(this)" class="form-select" aria-label="Default select example">
  
  <option value="1">New Account</option>
  <option   value="2">Connect Existing</option>
 
</select>
<script>
  $(document).ready(function(){
      document.getElementById('AccTypeSelect').value="{{ old('typeAccount', $patient->typeAccount ?? '1') }}";

      choose(document.getElementById('AccTypeSelect'));
  });
</script>
<br>
<div id="chooseId" style="display: none">
Choose Account:
@php

           
use Illuminate\Http\Request;




use Illuminate\Support\Facades\Auth;
use App\Models\Bill;
use App\Models\BillingInformation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Dispute;
use App\Models\ServiceAssignment;
use App\Models\BillItem;
use App\Models\Deposit;
use App\Models\notifications_latest;
use App\Helpers\Audit;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Patient;
use App\Models\AdmissionDetail;
use App\Models\PharmacyCharge;
use App\Models\PharmacyChargeItem;
use App\Models\AuditLog;

          use App\Models\User;
          $count = 0;
          $emails = "";
          $passsf = "";
    

$patientDetails = [];

$patientList = User::where('role', 'patient')
    ->whereNull('partnered')
    ->get();

foreach ($patientList as $user) {
    $patient = Patient::where('patient_id', $user->patient_id)->first();

    if ($patient) {
        $patientDetails[$patient->sub_key] = [
            'firstname'  => $patient->patient_first_name,
            'patient_id'  => $patient->patient_id,
            'sub_key'  => $patient->sub_key,
            'lastname'   => $patient->patient_last_name,
            'birthday'   => $patient->patient_birthday,
            'sex'        => $patient->sex,
            'civil_status' => $patient->civil_status,
            'phone_number' => $patient->phone_number,
            'birthday'   => $patient->patient_birthday,
            'address'   => $patient->address,
            
        ];
    }
}
@endphp
   
<table id="myTable"  class="table table-striped table-bordered">
  <thead>
    <tr>
      <th></th>
      <th>MRN</th>
      <th>Fullname</th>
      <th>Birthday</th>
      <th>Gender</th>
      <th>Outstanding Balance</th>
      <th>Details</th>
    </tr>
  </thead>
  <tbody>
    @foreach($patientDetails as $patientList)

    @php
           $count++;
         $dataAdmssion = AdmissionDetail::where('patient_id', $patientList['patient_id'])->first();
         $admissionType = $dataAdmssion->admission_type;
         $admissionTime = $dataAdmssion->admission_date;
            $dataFound = User::where('patient_id', $patientList['patient_id'])->first();
             $emails =$dataFound->email;
          $passsf = $dataFound->password;

            $patientData = Patient::where('patient_id', $patientList['patient_id'])->first();
    $patients = [];

        $patient_id = $patientData->patient_id;
        $patient_first_name = $patientData->patient_first_name;
        $patient_last_name = $patientData->patient_last_name;

        // Use BillingCalculationTrait for accurate totals
        $calculationHelper = new class {
            use \App\Traits\BillingCalculationTrait;
            
            public function getBillingTotals($patient) {
                return $this->calculatePatientTotals($patient);
            }
        };
        
        // Get all billing totals using the trait
        $billingData = $calculationHelper->getBillingTotals($patientData);
        
        // Extract values for display
        $grandTotal = $billingData['grandTotal'];
        $balance = $billingData['balance'];
        
        $totals = [
            'total'    => $grandTotal,
            'balance'  => $balance,
            'discount' => 0,
        ];

        @endphp

      <tr onclick="
    const radio = document.getElementById('radio{{ $patientList['patient_id'] }}');
    radio.checked = true;
    document.getElementsByName('subkeY')[0].value = '{{ $patientList['sub_key'] }}';
    
    // Manually trigger the change event to fill the form fields
    const event = new Event('change');
    radio.dispatchEvent(event);
">
  <td>
    <input 
     
      type="radio" 
      name="choosen" 
      value="{{ $patientList['sub_key'] }}"
      id="radio{{ $patientList['patient_id'] }}">
  </td>
  <td>{{ str_pad($patientList['patient_id'], 6, '0', STR_PAD_LEFT)  }}</td>
  <td>{{ $patientList['firstname'] }} {{ $patientList['lastname'] }}</td>
  <td>{{ \Carbon\Carbon::parse($patientList['birthday'])->format('F d, Y') }}</td>
  <td>{{ $patientList['sex'] }}</td>
  <td>₱{{number_format($totals['balance'], 2)}}</td>
  <td> <button type="button"
        class="btn btn-primary btn-sm btn-details"
        data-bs-toggle="modal"
        data-bs-target="#detailsModaloperating{{$patientList['patient_id']}}"
        
      >
  Details
</button></td>
</tr>




<div class="modal fade" id="detailsModaloperating{{$patientList['patient_id']}}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Patient Details: {{ $patientList['firstname'] }} {{ $patientList['lastname'] }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <h5 class="modal-title fw-bold">Admission Details</h5>
        <div style="background:#f8fbfe; border:1px solid #e5e7eb; border-radius:6px; padding:15px; font-family:Arial, sans-serif; font-size:14px; color:#374151; margin-bottom:20px;">
          <div style="display:flex; flex-wrap:wrap; gap:20px;">
            <div style="flex:1; min-width:150px;">
              <div><strong>Date:</strong></div>
              <div>{{ \Carbon\Carbon::parse($admissionTime)->format('Y-m-d') }}</div>
              <div style="margin-top:8px;"><strong>Time:</strong></div>
              <div>{{ \Carbon\Carbon::parse($admissionTime)->format('h:ia') }}</div>
            </div>
            <div style="flex:1; min-width:150px;">
              <div><strong>Amount:</strong></div>
              <div>₱{{number_format($totals['balance'], 2)}}</div>
              {{-- <div style="margin-top:8px;"><strong>Admission type:</strong></div>
              <div>{{ $admissionType }}</div> --}}
            </div>
          </div>
        </div>

        @php
          // Find connected accounts (users with the same sub_key)
          $connectedAccounts = User::where('partnered', $patientList['sub_key'])
                                  ->orWhere(function($query) use ($patientList) {
                                      $query->where('patient_id', '!=', $patientList['patient_id'])
                                            ->whereHas('patient', function($q) use ($patientList) {
                                                $q->where('sub_key', $patientList['sub_key']);
                                            });
                                  })
                                  ->get();
                                  
          $hasConnections = count($connectedAccounts) > 0;
        @endphp

        <h5 class="modal-title fw-bold mt-4">Connected Accounts</h5>
        
        @if($hasConnections)
          <div class="mt-3">
            <div class="row row-cols-1 row-cols-md-2 g-3">
              @foreach($connectedAccounts as $connectedUser)
                @php
                  $connectedPatient = Patient::find($connectedUser->patient_id);
                  if (!$connectedPatient) continue;
                  
                  // Calculate balance for this connected account
                  $calculationHelper = new class {
                      use \App\Traits\BillingCalculationTrait;
                      
                      public function getBillingTotals($patient) {
                          return $this->calculatePatientTotals($patient);
                      }
                  };
                  
                  // Get totals
                  $connectedBillingData = $calculationHelper->getBillingTotals($connectedPatient);
                  $connectedBalance = $connectedBillingData['balance'] > 0 ? $connectedBillingData['balance'] : 0;
                  
                  // Determine card color based on balance
                  $cardBorderClass = $connectedBalance > 0 ? 'border-warning' : 'border-success';
                  $cardHeaderClass = $connectedBalance > 0 ? 'bg-warning bg-opacity-25' : 'bg-success bg-opacity-25';
                  
                  // Get the admission date
                  $patientAdmission = AdmissionDetail::where('patient_id', $connectedPatient->patient_id)
                      ->latest('admission_date')
                      ->first();
                  $admissionDate = $patientAdmission ? $patientAdmission->admission_date : null;
                @endphp
                
                <div class="col">
                  <div class="card shadow-sm h-100 {{ $cardBorderClass }}">
                    <div class="card-header {{ $cardHeaderClass }}">
                      <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                          <i class="fas fa-user-circle me-1"></i>
                          {{ $connectedUser->partnered ? 'Secondary Account' : 'Primary Account' }}
                        </h6>
                        <span class="badge {{ $connectedBalance > 0 ? 'bg-warning text-dark' : 'bg-success' }}">
                          {{ $connectedBalance > 0 ? 'Outstanding' : 'Paid' }}
                        </span>
                      </div>
                    </div>
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                          <div class="avatar avatar-md bg-light rounded-circle">
                            <span class="fs-5 text-body">{{ substr($connectedPatient->patient_first_name, 0, 1) }}{{ substr($connectedPatient->patient_last_name, 0, 1) }}</span>
                          </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                          <h6 class="fw-bold mb-0">{{ $connectedPatient->patient_first_name }} {{ $connectedPatient->patient_last_name }}</h6>
                          <small class="text-muted">MRN: {{ str_pad($connectedPatient->patient_id, 6, '0', STR_PAD_LEFT) }}</small>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <div class="d-flex justify-content-between">
                          <small class="text-muted">Outstanding Balance:</small>
                          <span class="fw-bold {{ $connectedBalance > 0 ? 'text-warning' : 'text-success' }}">
                            ₱{{ number_format($connectedBalance, 2) }}
                          </span>
                        </div>
                        
                        @if($admissionDate)
                        <div class="d-flex justify-content-between mt-1">
                          <small class="text-muted">Last Admission:</small>
                          <span>{{ \Carbon\Carbon::parse($admissionDate)->format('M d, Y') }}</span>
                        </div>
                        @endif
                        
                        <div class="d-flex justify-content-between mt-1">
                          <small class="text-muted">Gender:</small>
                          <span>{{ $connectedPatient->sex }}</span>
                        </div>
                      </div>
                      
                      @if($connectedBalance > 0)
                      <div class="progress mb-2" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%"></div>
                      </div>
                      <small class="d-block text-center text-muted">Payment due</small>
                      @else
                      <div class="progress mb-2" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                      </div>
                      <small class="d-block text-center text-muted">Account clear</small>
                      @endif
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @else
          <div class="alert alert-info mt-3">
            <div class="d-flex align-items-center">
              <i class="fas fa-info-circle fs-4 me-3"></i>
              <div>
                <h6 class="fw-bold mb-1">No Connected Accounts</h6>
                <p class="mb-0">This patient doesn't have any connected accounts or family members registered in the system.</p>
              </div>
            </div>
          </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

    @endforeach
  </tbody>
</table>
<!-- Test Autofill Button -->
    {{-- <button type="button" class="btn btn-secondary mt-3" id="testAutofill">Test Autofill</button> --}}
</div>
</div>

</div>


<ul class="nav nav-tabs mb-3" id="patientTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal"
            type="button" role="tab">Personal Information</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="medical-tab" data-bs-toggle="tab" data-bs-target="#medical"
            type="button" role="tab">Medical Details</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="admission-tab" data-bs-toggle="tab" data-bs-target="#admission"
            type="button" role="tab">Admission Details</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing"
            type="button" role="tab">Billing Details</button>
  </li>
</ul>

<div class="tab-content" id="patientTabsContent">
  {{-- PERSONAL --}}
  <div class="tab-pane fade show active" id="personal" role="tabpanel">
    <div class="card mb-4">
      <div class="card-header"><strong>Personal Information</strong></div>
      <div class="card-body">
        <div class="row g-3">
          {{-- First / Last / Birthday / Sex / Civil / Phone / Address --}}
          <div class="col-md-4">
            <label class="form-label">First Name <span class="text-danger">*</span></label>
            <input type="text" name="patient_first_name"
                   value="{{ old('patient_first_name', $patient->patient_first_name ?? '') }}"
                   class="form-control @error('patient_first_name') is-invalid @enderror" required>
            @error('patient_first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                   <input type="hidden" name="subkeY" id="subkeyField" value="{{ old('subkeY', $patient->subkeY ?? '') }}">
            
            <input type="hidden" name="typeAccount" value="{{ old('typeAccount', $patient->typeAccount ?? '') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Last Name <span class="text-danger">*</span></label>
            <input type="text" name="patient_last_name"
                   value="{{ old('patient_last_name', $patient->patient_last_name ?? '') }}"
                   class="form-control @error('patient_last_name') is-invalid @enderror" required>
            @error('patient_last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Birthday</label>
            <input type="date" name="patient_birthday"
                   value="{{ old('patient_birthday', optional($patient)->patient_birthday?->format('Y-m-d')) }}"
                   class="form-control @error('patient_birthday') is-invalid @enderror">
            @error('patient_birthday')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Sex <span class="text-danger">*</span></label>
            <select name="sex" class="form-select @error('sex') is-invalid @enderror" required>
              <option value="" id="sexButton">Choose…</option>
              @foreach(['Male','Female'] as $opt)
                <option value="{{ $opt }}"
                  {{ old('sex', $patient->sex ?? '') === $opt ? 'selected' : '' }}>
                  {{ $opt }}
                </option>
              @endforeach
            </select>
            @error('sex')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Civil Status <span class="text-danger">*</span></label>
            <select name="civil_status" class="form-select @error('civil_status') is-invalid @enderror" required>
              <option value="" id="civilButton">Choose…</option>
              @foreach(['Single','Married','Divorced','Widowed','Separated'] as $status)
                <option value="{{ $status }}"
                  {{ old('civil_status', $patient->civil_status ?? '') === $status ? 'selected' : '' }}>
                  {{ $status }}
                </option>
              @endforeach
            </select>
            @error('civil_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Phone Number</label>
            <div class="input-group">
              <span class="input-group-text">(+63)</span>
              <input type="text" name="phone_number"
                     value="{{ old('phone_number', $patient->phone_number ?? '') }}"
                     class="form-control @error('phone_number') is-invalid @enderror">
            </div>
            @error('phone_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea name="address" rows="2"
                      class="form-control @error('address') is-invalid @enderror"
                      placeholder="Street, Barangay, City, Zip Code">{{ old('address', $patient->address ?? '') }}</textarea>
            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
      <div class="card-footer text-end">
        <button type="button" class="btn btn-primary step-next"
                data-current="personal" data-next="medical">
          Next
        </button>
      </div>
    </div>
  </div>

  {{-- MEDICAL --}}
  <div class="tab-pane fade" id="medical" role="tabpanel">
    <div class="card mb-4">
      <div class="card-header"><strong>Medical Details</strong></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Primary Reason</label>
            <input type="text" name="primary_reason"
                   value="{{ old('primary_reason', $medical->primary_reason ?? '') }}"
                   class="form-control @error('primary_reason') is-invalid @enderror">
            @error('primary_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <label class="form-label">Weight (KG)</label>
            <input type="number" step="0.1" name="weight"
                   value="{{ old('weight', $medical->weight ?? '') }}"
                   class="form-control @error('weight') is-invalid @enderror">
            @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <label class="form-label">Height (cm)</label>
            <input type="number" step="0.1" name="height"
                   value="{{ old('height', $medical->height ?? '') }}"
                   class="form-control @error('height') is-invalid @enderror">
            @error('height')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <label class="form-label">Temperature (°F)</label>
            <input type="number" step="0.1" name="temperature"
                   value="{{ old('temperature', $medical->temperature ?? '') }}"
                   class="form-control @error('temperature') is-invalid @enderror">
            @error('temperature')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <label class="form-label">Blood Pressure</label>
            <input type="text" name="blood_pressure"
                   value="{{ old('blood_pressure', $medical->blood_pressure ?? '') }}"
                   class="form-control @error('blood_pressure') is-invalid @enderror"
                   placeholder="e.g. 120/80">
            @error('blood_pressure')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-3">
            <label class="form-label">Heart Rate (BPM)</label>
            <input type="number" name="heart_rate"
                   value="{{ old('heart_rate', $medical->heart_rate ?? '') }}"
                   class="form-control @error('heart_rate') is-invalid @enderror">
            @error('heart_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Medical History --}}
          <div class="col-12 mt-3">
            <label class="form-label">Medical History</label>
            <div class="row">
              <div class="col-md-3">
                @foreach(['hypertension','heart_disease','copd'] as $h)
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="history_{{ $h }}" id="history_{{ $h }}"
                           {{ old("history_$h", $medical->{'medical_history'}[$h] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="history_{{ $h }}">
                      {{ ucwords(str_replace('_',' ',$h)) }}
                    </label>
                  </div>
                @endforeach
              </div>
              <div class="col-md-3">
                @foreach(['diabetes','asthma','kidney_disease'] as $h)
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="history_{{ $h }}" id="history_{{ $h }}"
                           {{ old("history_$h", $medical->{'medical_history'}[$h] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="history_{{ $h }}">
                      {{ ucwords(str_replace('_',' ',$h)) }}
                    </label>
                  </div>
                @endforeach
              </div>
              <div class="col-md-6">
                <label class="form-label mt-2">Others</label>
                <input type="text" name="history_others"
                       value="{{ old('history_others', $medical->{'medical_history'}['others'] ?? '') }}"
                       class="form-control @error('history_others') is-invalid @enderror"
                       placeholder="Specify if not listed">
                @error('history_others')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          {{-- Allergies --}}
          <div class="col-12 mt-4">
            <label class="form-label">Allergies</label>
            <div class="row">
              <div class="col-md-3">
                @foreach(['penicillin','nsaids','contrast_dye'] as $a)
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="allergy_{{ $a }}" id="allergy_{{ $a }}"
                           {{ old("allergy_$a", $medical->{'allergies'}[$a] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="allergy_{{ $a }}">
                      {{ ucwords(str_replace('_',' ',$a)) }}
                    </label>
                  </div>
                @endforeach
              </div>
              <div class="col-md-3">
                @foreach(['sulfa','latex','none'] as $a)
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="allergy_{{ $a }}" id="allergy_{{ $a }}"
                           {{ old("allergy_$a", $medical->{'allergies'}[$a] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="allergy_{{ $a }}">  
                      {{ $a==='none' ? 'No Known Allergies' : ucwords($a) }}
                    </label>
                  </div>
                @endforeach
              </div>
              <div class="col-md-6">
                <label class="form-label mt-2">Others</label>
                <input type="text" name="allergy_others"
                       value="{{ old('allergy_others', $medical->{'allergies'}['others'] ?? '') }}"
                       class="form-control @error('allergy_others') is-invalid @enderror"
                       placeholder="Specify if not listed">
                @error('allergy_others')<div class="invalid-feedback">{{ $message }}</div>@enderror
               
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between">
        <button type="button" class="btn btn-secondary step-prev"
                data-current="medical" data-prev="personal">
          Previous
        </button>
        <button type="button" class="btn btn-primary step-next"
                data-current="medical" data-next="admission">
          Next
        </button>
      </div>
    </div>
  </div>

  {{-- ADMISSION --}}
  <div class="tab-pane fade" id="admission" role="tabpanel">
    <div class="card mb-4">
      <div class="card-header"><strong>Admission Details</strong></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Admission Date <span class="text-danger">*</span></label>
            <input type="datetime-local"
                   class="form-control"
                   name="admission_date"
                   value="{{ old('admission_date', now()->format('Y-m-d\TH:i')) }}">
            @error('admission_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Department <span class="text-danger">*</span></label>
            <select id="department" name="department_id"
                    class="form-select @error('department_id') is-invalid @enderror" required>
              <option value="">Choose…</option>
              @foreach($departments as $d)
                <option value="{{ $d->department_id }}"

                  {{ old('department_id', $admission->department_id ?? '') == $d->department_id ? 'selected':''}}>
                  {{ $d->department_name }}
                </option>
              @endforeach
            </select>
            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Attending Doctor <span class="text-danger">*</span></label>
            <select id="doctor" name="doctor_id"
                    class="form-select @error('doctor_id') is-invalid @enderror" required>
              <option value="">Choose a doctor...</option>
              @foreach($doctors as $doc)
                  <option value="{{ $doc->doctor_id }}" data-department="{{ $doc->department->department_name }}"
                      {{ old('doctor_id', $admission->doctor_id ?? '') == $doc->doctor_id ? 'selected' : '' }}>
                      {{ $doc->doctor_name }}
                  </option>
              @endforeach
            </select>
            @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Available Room <span class="text-danger">*</span></label>
            <select id="room" name="room_id"
                    class="form-select @error('room_id') is-invalid @enderror" required>
              <option value="">Select room with available beds...</option>
              @php
                // Get rooms with active available beds only
                $latestBedIds = DB::table('beds')
                    ->select(DB::raw('MAX(bed_id) as bed_id'))
                    ->groupBy('room_id', 'bed_number')
                    ->pluck('bed_id')
                    ->toArray();
                
                $availableRooms = \App\Models\Room::whereHas('beds', function($query) use ($latestBedIds) {
                    $query->whereIn('bed_id', $latestBedIds)
                          ->where('status', 'available');
                })
                ->with(['beds' => function($query) use ($latestBedIds) {
                    $query->whereIn('bed_id', $latestBedIds)
                          ->where('status', 'available');
                }])
                ->with('department')
                ->get();
              @endphp
              @forelse($availableRooms as $room)
                @php
                  $availableBedCount = $room->beds->count();
                @endphp
                <option value="{{ $room->room_id }}" 
                        data-available-beds="{{ $availableBedCount }}"
                        {{ old('room_id', $admission->room_id ?? '') == $room->room_id ? 'selected' : '' }}>
                  Room {{ $room->room_number }} - {{ $room->department->department_name ?? 'No Dept' }} ({{ $availableBedCount }} bed(s) available)
                </option>
              @empty
                <option value="" disabled>No rooms with available beds</option>
              @endforelse
            </select>
            <div class="form-text">Showing rooms with active available beds</div>
            @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Available Bed <span class="text-danger">*</span></label>
            <select id="bed" name="bed_id"
                    class="form-select @error('bed_id') is-invalid @enderror" required>
              <option value="">First select a room...</option>
            </select>
            <div class="form-text">Select an available bed from the chosen room</div>
            @error('bed_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label">Admission Notes</label>
            <textarea name="admission_notes" rows="3"
                      class="form-control @error('admission_notes') is-invalid @enderror"
                      placeholder="Any special instructions…">{{ old('admission_notes', $admission->admission_notes ?? '') }}</textarea>
            @error('admission_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between">
        <button type="button" class="btn btn-secondary step-prev"
                data-current="admission" data-prev="medical">
          Previous
        </button>
        <button type="button" class="btn btn-primary step-next"
                data-current="admission" data-next="billing">
          Next
        </button>
      </div>
    </div>
  </div>

  {{-- BILLING --}}
  <div class="tab-pane fade" id="billing" role="tabpanel">
    <div class="card mb-4">
      <div class="card-header"><strong>Billing Details</strong></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Guarantor Name <span class="text-danger">*</span></label>
            <input type="text" name="guarantor_name"
                   value="{{ old('guarantor_name', optional($billing)->guarantor_name ?? '') }}"
                   class="form-control @error('guarantor_name') is-invalid @enderror" required>
            @error('guarantor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Relationship to Guarantor <span class="text-danger">*</span></label>
            <input type="text" name="guarantor_relationship"
                   value="{{ old('guarantor_relationship', optional($billing)->guarantor_relationship ?? '') }}"
                   class="form-control @error('guarantor_relationship') is-invalid @enderror" required>
            @error('guarantor_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
            

          </div>
          <div class="col-md-4">
            
          </div>
          

        </div>
      </div>
      <div class="card-footer">
        <button type="button" class="btn btn-secondary step-prev"
                data-current="billing" data-prev="admission">
          Previous
        </button>
        {{-- Final submit button should be in your parent create/edit template --}}
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Get form elements
  const deptSelect = document.getElementById('department');
  const docSelect  = document.getElementById('doctor');
  const roomSelect = document.getElementById('room');
  const bedSelect  = document.getElementById('bed');

  // Department change - Only fetch doctors (rooms are already loaded)
  deptSelect?.addEventListener('change', async () => {
    const depId = deptSelect.value;
    
    // Reset doctor selection
    docSelect.innerHTML = `<option value="">Loading doctors...</option>`;
    
    if (!depId) {
      docSelect.innerHTML = `<option value="">Choose doctor…</option>`;
      return;
    }

    try {
      // Fetch doctors for this department
      const doctors = await fetch(`/admission/departments/${depId}/doctors`)
        .then(r => {
          if (!r.ok) throw new Error('Failed to fetch doctors');
          return r.json();
        });
      
      docSelect.innerHTML = `<option value="">Choose doctor…</option>`;
      doctors.forEach(d => {
        const option = document.createElement('option');
        option.value = d.doctor_id;
        option.textContent = d.doctor_name;
        option.selected = '{{ old("doctor_id", $admission->doctor_id ?? "") }}' == d.doctor_id;
        docSelect.appendChild(option);
      });
    } catch (error) {
      console.error('Error fetching doctors:', error);
      docSelect.innerHTML = `<option value="">Error loading doctors. Please try again.</option>`;
    }
  });

  // Room change - Fetch only available beds for selected room
  roomSelect?.addEventListener('change', async () => {
    const roomId = roomSelect.value;
    
    bedSelect.innerHTML = `<option value="">Loading available beds...</option>`;
    bedSelect.disabled = true;
    
    if (!roomId) {
      bedSelect.innerHTML = `<option value="">First select a room...</option>`;
      return;
    }

    try {
      const beds = await fetch(`/admission/rooms/${roomId}/beds`)
        .then(r => {
          if (!r.ok) throw new Error('Failed to fetch beds');
          return r.json();
        });
      
      bedSelect.innerHTML = `<option value="">Choose an available bed...</option>`;
      
      if (beds.length === 0) {
        bedSelect.innerHTML = `<option value="">No available beds in this room</option>`;
        bedSelect.disabled = true;
      } else {
        beds.forEach(b => {
          const option = document.createElement('option');
          option.value = b.bed_id;
          option.textContent = `Bed ${b.bed_number}` + 
            (b.rate ? ` - ₱${parseFloat(b.rate).toLocaleString('en-PH', {minimumFractionDigits: 2})} / day` : '');
          option.selected = '{{ old("bed_id", $admission->bed_id ?? "") }}' == b.bed_id;
          bedSelect.appendChild(option);
        });
        bedSelect.disabled = false;
      }
    } catch (error) {
      console.error('Error fetching beds:', error);
      bedSelect.innerHTML = `<option value="">Error loading beds. Please try again.</option>`;
      bedSelect.disabled = true;
    }
  });

  // Restore selections on page load (for edit mode or validation errors)
  if (deptSelect?.value) {
    deptSelect.dispatchEvent(new Event('change'));
  }
  
  // Wait a bit for rooms to load, then trigger bed loading if room is selected
  setTimeout(() => {
    if (roomSelect?.value) {
      roomSelect.dispatchEvent(new Event('change'));
    }
  }, 500);

  // Step navigation
  document.querySelectorAll('.step-next').forEach(btn => {
    btn.addEventListener('click', () => {
      const current = btn.dataset.current;
      const next    = btn.dataset.next;
      document.getElementById(`${current}-tab`).classList.add('completed');
      new bootstrap.Tab(document.getElementById(`${next}-tab`)).show();
    });
  });

  document.querySelectorAll('.step-prev').forEach(btn => {
    btn.addEventListener('click', () => {
      const current = btn.dataset.current;
      const prev    = btn.dataset.prev;
      document.getElementById(`${current}-tab`).classList.remove('completed');
      new bootstrap.Tab(document.getElementById(`${prev}-tab`)).show();
    });
  });
});
</script>
<script>
  function choose(element) {
    // Remove any existing event listeners first to prevent duplicates
    document.querySelectorAll('input[name="choosen"]').forEach(radio => {
      // Clone and replace to remove all event listeners
      const newRadio = radio.cloneNode(true);
      radio.parentNode.replaceChild(newRadio, radio);
    });
    
    // Clear form fields
    document.getElementsByName('patient_first_name')[0].value = "";
    document.getElementsByName('patient_last_name')[0].value = "";
    document.getElementsByName('patient_birthday')[0].value = "";
    document.getElementById('sexButton').value = "";
    document.getElementById('civilButton').value = "";
    document.getElementsByName('phone_number')[0].value = "";
    document.getElementsByName('subkeY')[0].value = "";
    document.getElementsByName('typeAccount')[0].value = element.value;

    if (element.value == '1') {
        // New account: Hide the connected account table
        document.getElementById('chooseId').style.display = "none";
    } else {
        // Connected account: Show the connected account table
        document.getElementById('chooseId').style.display = "block";

        // Add event listeners to all radio buttons
        document.querySelectorAll('input[name="choosen"]').forEach(radio => {
            radio.addEventListener('change', function() {
                console.log("Radio changed:", this.value);
                const selectedPatient = @json($patientDetails);
                const patient = selectedPatient[this.value];
                
                console.log("Selected patient:", patient);
                
                if (patient) {
                    document.getElementsByName('patient_first_name')[0].value = patient.firstname;
                    document.getElementsByName('patient_last_name')[0].value = patient.lastname;
                    
                    try {
                        // Manually format the birthday to YYYY-MM-DD
                        const birthday = new Date(patient.birthday);
                        const year = birthday.getFullYear();
                        const month = String(birthday.getMonth() + 1).padStart(2, '0');
                        const day = String(birthday.getDate()).padStart(2, '0');
                        const formattedBirthday = `${year}-${month}-${day}`;
                        document.getElementsByName('patient_birthday')[0].value = formattedBirthday;
                    } catch (e) {
                        console.error("Error formatting birthday:", e);
                    }

                    document.getElementById('sexButton').value = patient.sex;
                    document.getElementById('civilButton').value = patient.civil_status;
                    document.getElementsByName('phone_number')[0].value = patient.phone_number;
                    document.getElementsByName('subkeY')[0].value = patient.sub_key;
                    document.getElementsByName('address')[0].value = patient.address || '';
                }
            });
        });
    }
  }
</script>
<script>
  $('#myTable').DataTable({
    dom: '<"top d-flex justify-content-between align-items-center mb-3"fB>rt' +
         '<"bottom d-flex justify-content-between align-items-center mt-3"i p>',
    buttons: [
        
    ]
});
// Add event listener for the "Test Autofill" button
document.getElementById('testAutofill').addEventListener('click', function () {
    // Simulate selecting the first patient in the list
    const firstRadio = document.querySelector('input[name="choosen"]');
    if (firstRadio) {
        firstRadio.checked = true;
        firstRadio.dispatchEvent(new Event('change')); // Trigger the change event
    } else {
        alert('No patients available to test.');
    }
    console.log(patient);
});
</script>