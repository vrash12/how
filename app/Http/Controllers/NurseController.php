<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Doctor;
use App\Models\HospitalService;
use App\Models\NurseRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NurseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard()
    {
        // Get active patients count
        $activePatients = Patient::where('status', 'active')->count();
        
        // // Get counts of patients by gender
        // $patientsByGender = Patient::where('status', 'active')
        //     ->select('gender', DB::raw('count(*) as count'))
        //     ->groupBy('gender')
        //     ->pluck('count', 'gender')
        //     ->toArray();
        
        // Get today's medication requests
        $todayMedicationRequests = NurseRequest::where('type', 'medication')
            ->whereDate('created_at', Carbon::today())
            ->count();
            
        // Get pending medication requests
        $pendingRequests = NurseRequest::where('status', 'pending')
            ->where('nurse_id', auth()->id())
            ->count();
            
        // Get approved medication requests
        $approvedRequests = NurseRequest::where('status', 'approved')
            ->where('nurse_id', auth()->id())
            ->count();
            
        // Get rejected medication requests
        $rejectedRequests = NurseRequest::where('status', 'rejected')
            ->where('nurse_id', auth()->id())
            ->count();
            
        // Get requests by day for the last 7 days
        $requestsByDay = NurseRequest::where('nurse_id', auth()->id())
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
            
        // Format the dates for display
        $formattedRequestsByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $formattedRequestsByDay[Carbon::now()->subDays($i)->format('D')] = $requestsByDay[$date] ?? 0;
        }
            
        // Get recently assigned patients (last 5)
        $recentPatients = Patient::where('status', 'active')
            ->with(['admissionDetail.doctor', 'admissionDetail.room'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // Get recently submitted requests (last 5)
        $recentRequests = NurseRequest::with(['patient', 'doctor'])
            ->where('nurse_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('nurse.dashboard', compact(
            'activePatients', 
            'todayMedicationRequests', 
            'pendingRequests',
            'approvedRequests',
            'rejectedRequests',
            'formattedRequestsByDay',
            'recentPatients',
            'recentRequests'
        ));
    }

    // Show all active patients
    public function patientsIndex(Request $request)
    {
        $query = Patient::with(['admissionDetail.doctor', 'admissionDetail.room'])
            ->where('status', 'active');

        // Search functionality
        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('patient_first_name', 'like', "%{$searchTerm}%")
                  ->orWhere('patient_last_name', 'like', "%{$searchTerm}%")
                  ->orWhere('patient_id', 'like', "%{$searchTerm}%")
                  ->orWhereHas('admissionDetail.doctor', function($doctorQuery) use ($searchTerm) {
                      $doctorQuery->where('doctor_name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        $patients = $query->get();

        return view('nurse.patients.index', compact('patients'));
    }

    // Show create request form for specific patient
    public function createRequest(Patient $patient)
    {
        // Load the patient with relationships
        $patient->load(['admissionDetail.doctor']);
        
        // Check if patient has assigned doctor
        if (!$patient->admissionDetail || !$patient->admissionDetail->doctor) {
            return redirect()->route('nurse.patients.index')
                ->withErrors('This patient does not have an assigned doctor.');
        }

        $doctor = $patient->admissionDetail->doctor;
        
        // Filter services to only show medications
        $services = HospitalService::where('service_type', 'medication')->get();

        return view('nurse.request_create', compact('patient', 'doctor', 'services'));
    }

    public function storeRequest(Request $request)
    {
        \Log::info('Nurse medication request submitted:', $request->all());

        $data = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'type' => 'required|in:medication', // Only allow medication type
            'payload' => 'required|array',
            'payload.services' => 'required|array|min:1',
            'payload.details' => 'required|string',
        ]);
        
        // Process services to ensure they include proper names
        if (isset($data['payload']['services']) && is_array($data['payload']['services'])) {
            foreach ($data['payload']['services'] as $key => $service) {
                if (isset($service['id'])) {
                    // Find the hospital service by ID
                    $hospitalService = HospitalService::find($service['id']);
                    if ($hospitalService) {
                        // Add service name to payload
                        $data['payload']['services'][$key]['service_name'] = $hospitalService->service_name;
                    }
                }
            }
        }
        
        \Log::info('Processed payload:', $data['payload']);

        NurseRequest::create([
            'patient_id' => $data['patient_id'],
            'nurse_id' => auth()->id(),
            'doctor_id' => $data['doctor_id'],
            'type' => $data['type'],
            'payload' => json_encode($data['payload']),
            'status' => 'pending',
        ]);

        return redirect()->route('nurse.patients.index')
            ->with('success', 'Medication request sent to doctor successfully.');
    }

    // Show request history for the nurse
    public function requestHistory()
    {
        // Get all requests made by the current nurse
        $requests = NurseRequest::where('nurse_id', auth()->id())
            ->with(['patient', 'doctor'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get counts by status
        $pendingCount = $requests->where('status', 'pending')->count();
        $approvedCount = $requests->where('status', 'approved')->count();
        $rejectedCount = $requests->where('status', 'rejected')->count();

        return view('nurse.requests_history', compact(
            'requests',
            'pendingCount',
            'approvedCount',
            'rejectedCount'
        ));
    }
}
