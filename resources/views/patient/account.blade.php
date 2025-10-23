@extends('layouts.patients')

@section('content')

 @php
    use App\Models\User;

    $checkPas = User::where('patient_id', $patient->patient_id)->first();

    $none = "";
    if($checkPas->partnered == 1){
       $none = "none";
    }
    @endphp
<div class="mx-auto" style="max-width:950px">

    <div class="alert alert-warning">
    <i class="fa-solid fa-circle-info me-2"></i>
    <strong>Important!</strong> Always Change Your Password for First Time Use.
  </div>

    {{-- —— My Profile ——————————————————————————————— --}}
    <div class="card shadow-sm">
        <div class="card-body px-5 py-4">

            <h4 class="mb-4 fw-bold text-primary">My Profile</h4>
   <div class="d-flex mb-4">
                <div>
                    @if($patient->profile_photo)
                        <img src="{{ asset('storage/patient/images/'.$patient->profile_photo) }}"
                             class="rounded-circle" width="100" height="100">
                    @else
                        <div class="rounded-circle bg-secondary" style="width:100px;height:100px;"></div>
                    @endif
                </div>
                <div class="ms-4 align-self-center">
                    <label class="form-label">Change Photo</label><br>
                    <input type="file" name="profile_photo" form="profile-form" class="form-control-file">
                </div>
            </div>
      <form id="profile-form" method="POST" action="{{ route('patient.account.update') }}" enctype="multipart/form-data">
  @csrf @method('PATCH')

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label">Last Name</label>
      <input type="text" name="patient_last_name"
             value="{{ old('patient_last_name', $patient->patient_last_name) }}"
             class="form-control" required>
    </div>

    <div class="col-md-4">
      <label class="form-label">First Name</label>
      <input type="text" name="patient_first_name"
             value="{{ old('patient_first_name', $patient->patient_first_name) }}"
             class="form-control" required>
    </div>

    <div class="col-md-4">
      <label class="form-label">Middle Initial</label>
      <input type="text" name="middle_initial"
             value="{{ old('middle_initial', $patient->middle_initial) }}"
             maxlength="1" class="form-control">
    </div>

    <div class="col-md-2">
      <label class="form-label">Sex</label>
      <select name="sex" class="form-select">
        <option value="">—</option>
        <option value="male"   @selected(old('sex', strtolower($patient->sex ?? ''))=='male')>Male</option>
        <option value="female" @selected(old('sex', strtolower($patient->sex ?? ''))=='female')>Female</option>
        <option value="other"  @selected(old('sex', strtolower($patient->sex ?? ''))=='other')>Other</option>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Birthday</label>
      <input type="date" name="patient_birthday"
             value="{{ old('patient_birthday', optional($patient->patient_birthday)->format('Y-m-d')) }}"
             class="form-control">
    </div>

    <div class="col-md-3">
      <label class="form-label">Civil Status</label>
      <select name="civil_status" class="form-select">
        <option value="">—</option>
        @foreach(['single','married','widowed','divorced'] as $status)
          <option value="{{ $status }}"
            @selected(old('civil_status', $patient->civil_status)==$status)>
            {{ ucfirst($status) }}
          </option>
        @endforeach
      </select>
    </div>

    {{-- <div class="col-md-4" style="display: {{$none}}">
      <label class="form-label">Email</label>
      <input style="opacity: 70%; cursor:not-allowed" readonly type="email" name="email"
             value="{{ old('email', $patient->email) }}"
             class="form-control" required>
    </div> --}}

    <div class="col-md-4">
      <label class="form-label">Contact #</label>
      <input type="text" name="phone_number"
             value="{{ old('phone_number', $patient->phone_number) }}"
             class="form-control">
    </div>
  </div>

  <button type="submit" class="btn btn-primary px-4">Save Changes</button>
</form>


        </div>
    </div>
   
    {{-- —— Change Password ——————————————————————————— --}}
    <div style="display: {{$none}}" class="card shadow-sm mt-5">
        <div class="card-body px-5 py-4">

            <h4 class="mb-4 fw-bold text-primary">Change Password</h4>

            <form method="POST" action="{{ route('patient.account.password') }}">
                @csrf @method('PATCH')

                <div class="row g-3 mb-3">
                    {{-- Current Password --}}
                    <div class="col-md-4">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    {{-- New Password --}}
                    <div class="col-md-4">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>

                    {{-- Confirm Password --}}
                    <div class="col-md-4">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4">Save New Password</button>
            </form>

        </div>
    </div>

</div>
@endsection
