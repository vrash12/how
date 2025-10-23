<?php
//app/Http/Controllers/AdmissionController.php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\MedicalDetail;
use App\Models\AdmissionDetail;
use App\Models\InsuranceProvider;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;
use App\Models\Bed;
use App\Models\BillingInformation;

class AdmissionController extends Controller
{

    // Before accessing the controller, you must first log in
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard()
    {
        $totalPatients = Patient::where('status', 'active')->count();
        $availableBeds   = Bed::where('status', 'available')->count();
        $newAdmissions   = AdmissionDetail::whereDate('created_at', Carbon::today())->count();

        $recentAdmissions = AdmissionDetail::with([
            'patient', //Load patient details
            'doctor', //Load doctor details
            'room' //Load room details
        ])
        ->latest() // Most recent
        ->take(5)
        ->get();

        // Log for debugging
        Log::debug("Dashboard Data:", compact(
            'totalPatients','newAdmissions','availableBeds','recentAdmissions'
        ));

        return view('admission.dashboard', compact(
            'totalPatients','newAdmissions','availableBeds','recentAdmissions'
    ));
}
    public function login()
    {
        return view('auth.login');
    }


    public function patients()
    {
        $patients = Patient::with(['admissionDetails', 'medicalDetails', 'billingInformation'])
            ->latest()
            ->paginate(10);
        return view('admission.patients.index', compact('patients'));
    }

    public function getDepartments()
    {
        $departments = Department::all();
        return response()->json($departments);
    }

    public function getDoctorsByDepartment($departmentId)
    {
        $doctors = Doctor::where('department_id', $departmentId)->get();
        return response()->json($doctors);
    }

    public function getRoomsByDepartment($departmentId)
    {

        $rooms = Room::where('status', 'available')
            ->get();
        return response()->json($rooms);
    }

    public function getBedsByRoom($roomId)
    {
      
        $beds = Bed::where('room_id', $roomId)
            ->where('status', 'available')
            ->get();
        return response()->json($beds);
    }

    public function logout(Request $request)
    {
        Auth::guard('admission')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admission.login');
    }

    protected function generatePatientId()
    {
        $year = date('Y');
        $lastPatient = Patient::whereYear('created_at', $year)
            ->orderByDesc('patient_id')
            ->first();

        $sequence = $lastPatient ? intval(substr($lastPatient->patient_id, -4)) + 1 : 1;
        return sprintf('%d%04d', $year, $sequence);
    }

    protected function checkRoomAvailability($roomNumber, $admissionDate)
    {
        return !AdmissionDetail::where('room_number', $roomNumber)
            ->where('admission_date', $admissionDate)
            ->whereNull('discharge_date')
            ->exists();
    }

    protected function checkDoctorAvailability($doctorId, $admissionDate)
    {
      
        $maxPatientsPerDay = 10; 
        
        $currentPatients = AdmissionDetail::where('doctor_id', $doctorId)
            ->whereDate('admission_date', $admissionDate)
            ->count();

        return $currentPatients < $maxPatientsPerDay;
    }
}