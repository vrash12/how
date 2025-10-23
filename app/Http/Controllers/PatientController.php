<?php namespace App\Http\Controllers;

use App\Models\{Patient, MedicalDetail, AdmissionDetail, BillingInformation, Bill, BillItem, Department, Doctor, Room, Bed};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Auth};
use Illuminate\Support\Str;
use App\Models\ServiceAssignment;
use App\Models\PharmacyCharge;
use App\Models\PharmacyChargeItem;
use App\Models\Dispute;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Http\Controllers\PatientNotificationController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\DepositController;
use App\Models\PrescriptionItem;
use App\Models\Service;
use App\Notifications\AdmissionCharged;
use App\Models\UserAuditTrail; 
use App\Models\Deposit;
use App\Traits\BillingCalculationTrait;

class PatientController extends Controller
{
    use BillingCalculationTrait;
    
    public function __construct()
    {
        // Login First before using methods
        $this->middleware('auth');
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user(); // Fetch the currently logged-in user
        $patientId = $user->patient_id; // Get the associated patient ID
        $patient = $user->patient ?? \App\Models\Patient::find($patientId); // Fetch the patient record

        // Use the trait to calculate all billing totals
        $billingData = $this->calculatePatientTotals($patient);
        
        // Extract data from the billing calculation
        $admission = $billingData['admission'];
        $admissions = $billingData['admissions'];
        $labTotal = $billingData['labFee'];
        $pharmacyTotal = $billingData['rxTotal'];
        $orTotal = $billingData['orFee'];
        $bedRate = $billingData['bedRate'];
        $doctorRate = $billingData['doctorDailyRate'];
        $totalDeposits = $billingData['paymentsMade'];
        $amountDue = $billingData['balance'];

        // Totals for the view
        $totals = [
            'balance' => $amountDue,
            'deposits' => $totalDeposits,
        ];

        // Get pharmacy charges for the view (not needed for calculations)
        $pharmacyCharges = PharmacyCharge::with('items.service')
            ->where('patient_id', $patientId)
            ->where('status', 'completed')
            ->get();

        // Assigned doctors
        $assignedDoctors = collect([$admission?->doctor])->filter()->unique('doctor_id');

        // Service assignments for the view
        $serviceAssignments = ServiceAssignment::with(['service.department', 'doctor'])
            ->where('patient_id', $patientId)
            ->orderByDesc('assignment_id') // Use ID for sorting instead of datetime
            ->get();

        return view('patient.dashboard', [
            'user' => $user,
            'patient' => $patient,
            'admission' => $admission,
            'totals' => $totals,
            'labTotal' => $labTotal,
            'pharmacyTotal' => $pharmacyTotal,
            'orTotal' => $orTotal,
            'assignedDoctors' => $assignedDoctors,
            'pharmacyCharges' => $pharmacyCharges,
            'serviceAssignments' => $serviceAssignments,
        ]);
    }

    public function create()
    {
        $departments = Department::all(); // Fetch all departments
        $doctors = Doctor::with('department')->get(); // Fetch all doctors with their departments
        $rooms = Room::where('status', 'available')->get(); // Fetch all available rooms
        $beds = Bed::where('status', 'available')->get(); // Fetch all available beds

        // When preparing data for the form, you can use the trait:
        $patients = Patient::all();
        foreach ($patients as $patient) {
            $billingData = $this->calculatePatientTotals($patient);
            $patient->billing_data = $billingData;
        }
        
        return view('patients.create', compact('departments', 'doctors', 'rooms', 'beds', 'patients'));
    }

    public function edit(Patient $patient)
    {
        $departments = Department::all(); // Fetch all departments
        $doctors = Doctor::with('department')->get(); // Fetch all doctors with their departments
        $rooms = Room::where('status', 'available')->get(); // Fetch all available rooms
        $beds = Bed::where('status', 'available')->get(); // Fetch all available beds

        return view('patients.edit', compact('patient', 'departments', 'doctors', 'rooms', 'beds'));
    }
  public function store(Request $request)
{

    $data = $request->validate([
        'subkeY' => [
            'sometimes',
            function ($attribute, $value, $fail) use ($request) {
                if ($request->typeAccount != 1 && empty($value)) {
                    $fail('The '.$attribute.' field is required.');
                }
            },
        ],
        
        'patient_first_name' => 'required|string|max:100',
        'patient_last_name' => 'required|string|max:100',
        'patient_birthday' => 'nullable|date',
        'civil_status' => 'nullable|string|max:50',
        'phone_number' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'sex' => 'required|in:Male,Female',

        'primary_reason' => 'nullable|string',
        'weight' => 'nullable|numeric',
        'height' => 'nullable|numeric',
        'temperature' => 'nullable|numeric',
        'blood_pressure' => 'nullable|string',
        'heart_rate' => 'nullable|integer',
        'history_others' => 'nullable|string',
        'allergy_others' => 'nullable|string',

        'admission_type' => 'nullable|string|max:50',
        'admission_source' => 'nullable|string|max:100',
        'department_id' => 'required|exists:departments,department_id',
        'doctor_id' => 'required|exists:doctors,doctor_id',
        'room_id' => 'required|exists:rooms,room_id',
        'bed_id' => 'nullable|exists:beds,bed_id',
        'admission_notes' => 'nullable|string',

        'guarantor_name' => 'required|string|max:100',
        'guarantor_relationship' => 'required|string|max:50',
    ]);

    $partnered = null;
    $subkeY = null;
    if ($request->typeAccount != 1) {
        $subkeY = $request->subkeY;
        $partnered = 1;
    } else {
        $latest = Patient::orderBy('sub_key', 'desc')->first();

        // If none exists, start at 1
        $subkeY = $latest ? ((int) $latest->sub_key + 1) : 1;
    }

    $pwd = $data['patient_birthday']; // plain default password

    $patient = DB::transaction(function () use ($data, $pwd, $subkeY, $partnered, $request) {
        /* 1️⃣  CREATE PATIENT FIRST */
        $p = Patient::create([
            'patient_first_name' => $data['patient_first_name'],
            'patient_last_name'  => $data['patient_last_name'],
            'sex'                => $data['sex'],
            'patient_birthday'   => $data['patient_birthday'],
            'civil_status'       => $data['civil_status'],
            'phone_number'       => $data['phone_number'],
            'address'            => $data['address'],
            'password'           => $pwd, // hashed by mutator
            'status'             => 'active',
            'sub_key'            => $subkeY,
        ]);

        /* 2️⃣  Generate unique email after we know the patient_id */
        $base  = strtolower(substr($p->patient_first_name, 0, 1) . substr($p->patient_last_name, 0, 1));
        $email = "{$base}." . str_pad($p->patient_id, 5, '0', STR_PAD_LEFT) . '@patientcare.com';

        $p->update(['email' => $email]);

        /* 3️⃣  MEDICAL DETAILS */
        $p->medicalDetail()->create([
            'primary_reason'   => $data['primary_reason'],
            'weight'           => $data['weight'],
            'height'           => $data['height'],
            'temperature'      => $data['temperature'],
            'blood_pressure'   => $data['blood_pressure'],
            'heart_rate'       => $data['heart_rate'],
            'medical_history'  => json_encode([
                'hypertension'   => (bool) $request->history_hypertension,
                'heart_disease'  => (bool) $request->history_heart_disease,
                'copd'           => (bool) $request->history_copd,
                'diabetes'       => (bool) $request->history_diabetes,
                'asthma'         => (bool) $request->history_asthma,
                'kidney_disease' => (bool) $request->history_kidney_disease,
                'others'         => $data['history_others'],
            ]),
            'allergies' => json_encode([
                'penicillin'    => (bool) $request->allergy_penicillin,
                'nsaids'        => (bool) $request->allergy_nsaids,
                'contrast_dye'  => (bool) $request->allergy_contrast_dye,
                'sulfa'         => (bool) $request->allergy_sulfa,
                'latex'         => (bool) $request->allergy_latex,
                'none'          => (bool) $request->allergy_none,
                'others'        => $data['allergy_others'],
            ]),
        ]);

        /* 4️⃣  ADMISSION */
        $room = Room::findOrFail($data['room_id']);
        $bed  = $data['bed_id'] ? Bed::findOrFail($data['bed_id']) : null;

        $admission = $p->admissionDetail()->create([
            'admission_date'   => now(),
            'department_id'    => $data['department_id'],
            'doctor_id'        => $data['doctor_id'],
            'room_number'      => $room->room_number,
            'bed_number'       => $bed ? $bed->bed_number : '',
            'admission_notes'  => $data['admission_notes'],
        ]);

        if ($bed) {
            $bed->update(['patient_id' => $p->patient_id, 'status' => 'occupied']);
        }

        /* 5️⃣  BILLING INFORMATION */
        $p->billingInformation()->create([
            'guarantor_name'        => $data['guarantor_name'],
            'guarantor_relationship'=> $data['guarantor_relationship'],
            'payment_status'        => 'pending',
        ]);

        /* 6️⃣  USER ACCOUNT */
        $p->user()->create([
            'username'      => Str::before($email, '@'),
            'email'         => $email,
            'password'      => $pwd,
            'role'          => 'patient',
            'department_id' => $data['department_id'],
            'room_id'       => $data['room_id'],
            'bed_id'        => $data['bed_id'] ?? null,
            'doctor_id'     => $data['doctor_id'],
            'partnered'     => $partnered,
        ]);

        // Log the admission action
        \App\Helpers\UserAuditHelper::log(
            'admit',
            'Admission',
            "Admitted patient {$p->patient_first_name} {$p->patient_last_name} to Room {$room->room_number}" . ($bed ? " and Bed {$bed->bed_number}" : ""),
            [
                'affected_table' => 'patients',
                'affected_record_id' => $p->patient_id,
                'patient_id' => $p->patient_id,
                'patient_name' => "{$p->patient_first_name} {$p->patient_last_name}",
            ]
        );

        return $p;
    });

    if ($partnered) {
        return redirect()
            ->route('admission.patients.show', $patient->patient_id)
            ->with([
                'success' => 'Account created, please use your old credentials to login.',
            ]);
    }

    return redirect()
        ->route('admission.patients.show', $patient->patient_id)
        ->with([
            'generatedEmail' => $patient->email,
            'plainPassword'  => $pwd,
            'success'        => 'Patient admitted successfully.',
        ]);
}

    public function index(Request $request)
    {
        $query = Patient::query();

        // ← changed from input('search') to input('q')
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('patient_id', 'like', "%{$search}%")
                    ->orWhere('patient_first_name', 'like', "%{$search}%")
                    ->orWhere('patient_last_name', 'like', "%{$search}%");
            });
        }

        // status filter (if you ever add one)
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $patients = $query->with('admissionDetail.doctor')->orderBy('patient_last_name')->paginate(15)->withQueryString();

        return view('patients.index', compact('patients'));
    }

    public function getDoctorsByDepartment($departmentId)
    {
        $doctors = Doctor::all();
        return response()->json($doctors);
    }

    /**
     * GET /admission/departments/{department}/rooms
     */
    public function getRoomsByDepartment($departmentId)
    {
        // Fetch rooms with at least one available bed
        $rooms = Room::whereHas('beds', function ($query) {
            $query->where('status', 'available');
        })->get();

        return response()->json($rooms);
    }

    /**
     * GET /admission/rooms/{room}/beds
     */
    public function getBedsByRoom($roomId)
    {
        try {
            // Get the latest bed_id for each bed_number in this room
            $latestBedIds = DB::table('beds')
                ->select(DB::raw('MAX(bed_id) as bed_id'))
                ->where('room_id', $roomId)
                ->groupBy('bed_number')
                ->pluck('bed_id')
                ->toArray();
            
            // Get only available beds from the latest ones
            $beds = Bed::whereIn('bed_id', $latestBedIds)
                ->where('status', 'available')
                ->select('bed_id', 'bed_number', 'rate', 'status')
                ->orderBy('bed_number')
                ->get();

            return response()->json($beds);
        } catch (\Exception $e) {
            \Log::error('Error fetching beds', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($patient_id)
    {
        // Fetch patient using patient_id
        $patient = Patient::findOrFail($patient_id);
        $patient->load(['medicalDetail', 'admissionDetail', 'billingInformation', 'bills.items']);

        // Pass the patient data to the view
        return view('patients.show', compact('patient'));
    }

    public function changeDoctor(Request $request, $patientId)
    {
        $patient = Patient::findOrFail($patientId);
        
        // Make sure patient is active
        if ($patient->status !== 'active') {
            return redirect()->back()->with('error', 'Cannot change doctor for a discharged patient.');
        }
        
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'change_reason' => 'nullable|string|max:500',
        ]);
        
        // Get admission detail
        $admission = $patient->admissionDetail;
        if (!$admission) {
            return redirect()->back()->with('error', 'No admission details found for this patient.');
        }
        
        // Store old doctor for logging
        $oldDoctorId = $admission->doctor_id;
        $newDoctorId = $validated['doctor_id'];
        
        // Get doctor names for better logging
        $oldDoctor = Doctor::find($oldDoctorId);
        $newDoctor = Doctor::find($newDoctorId);
        $oldDoctorName = $oldDoctor ? $oldDoctor->doctor_name : 'Unknown';
        $newDoctorName = $newDoctor ? $newDoctor->doctor_name : 'Unknown';
        
        // Update doctor
        $admission->doctor_id = $newDoctorId;
        $admission->save();
        
        // Log the change using UserAuditHelper
        \App\Helpers\UserAuditHelper::log(
            'doctor_change',
            'Doctor Change',
            "Changed patient {$patient->patient_first_name} {$patient->patient_last_name}'s doctor from {$oldDoctorName} to {$newDoctorName}",
            [
                'affected_table' => 'admission_details',
                'affected_record_id' => $admission->admission_id,
                'patient_id' => $patient->patient_id,
                'patient_name' => "{$patient->patient_first_name} {$patient->patient_last_name}",
                'old_data' => ['doctor_id' => $oldDoctorId, 'doctor_name' => $oldDoctorName],
                'new_data' => ['doctor_id' => $newDoctorId, 'doctor_name' => $newDoctorName],
                'change_reason' => $validated['change_reason'] ?? null
            ]
        );
        
        return redirect()->back()->with('success', 'Doctor changed successfully.');
    }

    /**
     * Display the form to change a patient's room and bed
     */
    public function changeRoomBedForm($patient_id)
    {
        $patient = Patient::findOrFail($patient_id);
        $patient->load(['admissionDetail.department', 'admissionDetail.doctor']);
        
        // Check if patient is active
        if ($patient->status !== 'active') {
            return redirect()->route('admission.patients.show', $patient_id)
                ->with('error', 'Cannot change room/bed for a non-active patient.');
        }
        
        // Get all rooms with available beds - changed to a simpler query
        $availableRooms = Room::whereHas('beds', function($query) {
            $query->where('status', 'available');
        })
        ->with(['beds' => function($query) {
            $query->where('status', 'available')
                ->orderBy('bed_number');
        }])
        ->orderBy('room_number')
        ->get();
        
        // Log available rooms for debugging
        \Illuminate\Support\Facades\Log::info('Available Rooms Query Result:', [
            'patient_id' => $patient_id,
            'rooms_count' => $availableRooms->count(),
            'rooms' => $availableRooms->map(function($room) {
                return [
                    'room_id' => $room->room_id,
                    'room_number' => $room->room_number,
                    'available_beds_count' => $room->beds->count(),
                    'available_beds' => $room->beds->map(function($bed) {
                        return [
                            'bed_id' => $bed->bed_id,
                            'bed_number' => $bed->bed_number,
                            'status' => $bed->status
                        ];
                    })->toArray()
                ];
            })->toArray()
        ]);
        
        // Get current room and bed information for display
        $currentRoom = $patient->admissionDetail ? Room::where('room_number', $patient->admissionDetail->room_number)->first() : null;
        $currentBed = $patient->admissionDetail ? $patient->admissionDetail->bed_number : null;
        
        // Log current room/bed information
        \Illuminate\Support\Facades\Log::info('Current Patient Room/Bed:', [
            'patient_id' => $patient_id,
            'patient_name' => $patient->patient_first_name . ' ' . $patient->patient_last_name,
            'admission_detail_exists' => (bool)$patient->admissionDetail,
            'current_room' => $currentRoom ? [
                'room_id' => $currentRoom->room_id,
                'room_number' => $currentRoom->room_number
            ] : null,
            'current_bed_number' => $currentBed
        ]);
        
        return view('patients.change-room-bed', compact('patient', 'availableRooms', 'currentRoom', 'currentBed'));
    }

    /**
     * Process the room/bed change
     */
    public function changeRoomAndBed(Request $request, $patient_id)
    {
        $patient = Patient::findOrFail($patient_id);
        
        // Validate input
        $validated = $request->validate([
            'bed_id' => 'required|exists:beds,bed_id',
            'change_reason' => 'nullable|string|max:500',
        ]);
        
        // Check if patient is active
        if ($patient->status !== 'active') {
            return redirect()->route('admission.patients.show', $patient_id)
                ->with('error', 'Cannot change room/bed for a discharged patient.');
        }
        
        // Get the new bed
        $newBed = Bed::findOrFail($validated['bed_id']);
        
        // Get the new room
        $newRoom = $newBed->room;
        
        // Get current admission details
        $admission = $patient->admissionDetail;
        if (!$admission) {
            return redirect()->back()->with('error', 'No admission details found for this patient.');
        }
        
        // Store old room/bed for logging
        $oldRoomNumber = $admission->room_number;
        $oldBedNumber = $admission->bed_number;
        
        // Find the current bed if it exists and release it
        if ($oldBedNumber) {
            $currentBed = Bed::where('bed_number', $oldBedNumber)
                ->where('patient_id', $patient->patient_id)
                ->first();
                
            if ($currentBed) {
                $currentBed->releaseBed(); // This method handles creating a new bed record with status=available
            }
        }
        
        // Update patient's admission details
        $admission->room_number = $newRoom->room_number;
        $admission->bed_number = $newBed->bed_number;
        $admission->save();
        
        // Update the new bed status to occupied
        $newBed->patient_id = $patient->patient_id;
        $newBed->status = 'occupied';
        $newBed->save();
        
        // Update user record if it exists
        if ($patient->user) {
            $patient->user->update([
                'room_id' => $newRoom->room_id,
                'bed_id' => $newBed->bed_id
            ]);
        }
        
        // Log the room/bed change
        \App\Helpers\UserAuditHelper::log(
            'room_change',
            'Room Change',
            "Changed patient {$patient->patient_first_name} {$patient->patient_last_name}'s room/bed from Room {$oldRoomNumber} Bed {$oldBedNumber} to Room {$newRoom->room_number} Bed {$newBed->bed_number}",
            [
                'affected_table' => 'admission_details',
                'affected_record_id' => $admission->admission_id,
                'patient_id' => $patient->patient_id,
                'patient_name' => "{$patient->patient_first_name} {$patient->patient_last_name}",
                'change_reason' => $validated['change_reason'] ?? null
            ]
        );
        
        return redirect()->route('admission.patients.show', $patient->patient_id)
            ->with('success', "Room and bed changed successfully to Room {$newRoom->room_number}, Bed {$newBed->bed_number}");
    }
}
