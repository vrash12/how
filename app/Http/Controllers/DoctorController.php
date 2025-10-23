<?php
// Controller for doctor-related actions

namespace App\Http\Controllers;

// Import necessary models and classes
use App\Models\Doctor;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Medication;      // Medication model
use App\Models\LabTest;         // LabTest model
use App\Models\ImagingStudy;    // ImagingStudy model
use App\Models\HospitalService; // HospitalService model
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\AdmissionDetail; // AdmissionDetail model
use App\Models\Bill;   
use App\Models\Bed;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceAssignment;
use Carbon\Carbon;  
use App\Models\notifications_latest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PharmacyCharge;
use App\Models\PharmacyChargeItem;
use Illuminate\Support\Str;
use App\Helpers\UserAuditHelper; // Import the UserAuditHelper
use App\Models\NurseRequest; 

class DoctorController extends Controller
{
    // Doctor dashboard: shows patients and recent admissions
    public function dashboard(Request $request)
    {
        $q = $request->input('q'); // Get search query (Search bar)
        $user = Auth::user(); // Get current logged-in user
        $doctor = $user->doctor; // Get doctor profile
        $doctorId = optional($doctor)->doctor_id; // Doctor ID or null
        Log::debug("[DoctorDashboard] user_id {$user->user_id} doctorId {$doctorId}");

        // Query patients assigned to this doctor (Find patients assigned to this doctor)
        // whereHas: Filter a model based on conditions on its relationship
        $patientsQuery = Patient::whereHas('admissionDetail', function($admissionQuery) use ($doctorId) {
            $admissionQuery->where('doctor_id', $doctorId);
        });

        // Run the query and counts how many patients are matched
        $initialCount = $patientsQuery->count();

        // Debug Log: storage/logs/laravel.log.
        Log::debug("[DoctorDashboard] patientsQuery count before search: {$initialCount}");

        // If searching, filter by patient ID or name
        if ($q) { // Runs only if user typed something
            $patientsQuery->where(function($w) use ($q) {
                // Check if the query starts with "PID-" and extract the numeric part
                if (str_starts_with($q, 'PID-')) {
                    $numericId = ltrim(substr($q, 4), '0'); // Remove "PID-" and leading zeros
                    $w->where('patient_id', $numericId); // Search by numeric patient_id
                } else {
                    $w->where('patient_id', 'like', "%{$q}%") // Search patient ID
                      ->orWhere('patient_first_name', 'like', "%{$q}%") // Search Firstname
                      ->orWhere('patient_last_name', 'like', "%{$q}%"); // Search Lastname
                }
            });

            // Debug Log: storage/logs/laravel.log.
            $countAfterSearch = $patientsQuery->count();
            Log::debug("[DoctorDashboard] patientsQuery count after search '{$q}': {$countAfterSearch}");
        }

        // Get paginated patients with room info
        $patients = $patientsQuery
            ->with('admissionDetail.room')
            ->orderBy('patient_last_name')
            ->paginate(10)
            ->withQueryString();
        Log::debug("[DoctorDashboard] paginated total {$patients->total()} current page count {$patients->count()}");

        // Get today's recent admissions for this doctor
        $recentAdmissions = AdmissionDetail::with('patient','room')
            ->where('doctor_id', $doctorId)
            ->whereDate('admission_date', Carbon::today())
            ->latest('admission_date')
            ->take(10)
            ->get();
        Log::debug("[DoctorDashboard] recentAdmissions count: " . count($recentAdmissions));

        // Return dashboard view with data
        return view('doctor.dashboard', [
            'patients' => $patients,
            'q' => $q,
            'recentAdmissions' => $recentAdmissions,
        ]);
    }

    // Show a single patient's details
    public function show(Patient $patient)
    {
        $patient->load('admissionDetail.room', 'medicalDetail'); // Fetch Room and Medical Detail
        return view('doctor.show', compact('patient')); // Reference in view as $patient
    }

    // Show order entry form for a patient
    public function orderEntry(Patient $patient)
    {
        // Prevent access if the patient is marked as completed
        if ($patient->medication_finished) {
            return back()->with('error', 'This patient has been marked as completed. No further orders can be made.');
        }

        $services = HospitalService::all(); // Get all hospital services MEDICATION/LAB/OR

        return view('doctor.order-entry', [
            // Removed Imaging and Added OR (Operating Room)
            'patient'        => $patient->load('medicalDetail','admissionDetail.room'),
            'medications'    => $services->where('service_type','medication'),
            'labTests'       => $services->where('service_type','lab'),
            'otherServices'  => $services->where('service_type','operation'),
        ]);
    }

    // Store an order (medication, laboratory, or OR)
    public function storeOrder(Request $request, Patient $patient)
    {
        // Prevent submission if the patient is marked as completed
        if ($patient->medication_finished) {
            return back()->with('error', 'This patient has been marked as completed. No further orders can be made.');
        }

        // Prevent changes if billing is closed
        if ($patient->medication_finished) {
            return back()->with('error', 'Action failed: The patient\'s bill is locked.');
        }
        $modeService       = $request->input('modeService');
        $rawPayload = $request->all(); // Get all input data from the request (for logging/debugging)
        $type       = $request->input('type'); // Get the type of order being submitted (medication, lab, service)
        $doctorId   = optional(Auth::user()->doctor)->doctor_id // Get the current user's doctor_id, or fallback to the first doctor in DB if not set
                    ?? Doctor::first()?->doctor_id; // Fallback if no doctor profile

        Log::debug('[OrderEntry] incoming request', [
            'user_id'  => Auth::id(), // Log the current user ID
            'patient'  => $patient->patient_id, // Log the patient ID
            'type'     => $type, // Log the order type
            'payload'  => $rawPayload, // Log the full request payload
        ]);

        if (! $doctorId) { // If no doctor ID could be resolved
            Log::warning('[OrderEntry] NO DOCTOR ID RESOLVED!');
            return back()->withErrors('No doctor profile found.'); // Return with error
        }

        // Handle medication orders
        if ($type === 'medication') {
            $data = $request->validate([ // Validate medication order input
                'medications'                 => 'required|array|min:1', // Must have at least one medication
                'medications.*.medication_id' => 'required|exists:hospital_services,service_id', // Each medication must exist
                'medications.*.quantity'      => 'required|integer|min:1', // Quantity must be at least 1
                'medications.*.duration'      => 'required|integer|min:1', // Duration must be at least 1
                'medications.*.duration_unit' => 'required|in:days,weeks', // Duration unit must be days or weeks
                'medications.*.instructions'  => 'nullable|string', // Instructions are optional
                'refills' => 'nullable|integer|min:0', // Refills are optional
                'daw'     => 'nullable|boolean', // DAW (dispense as written) is optional
            ]);

            $refills = $data['refills'] ?? 0; // Default refills to 0 if not set
            $daw     = $data['daw'] ?? false; // Default DAW to false if not set

            DB::beginTransaction(); // Start DB transaction
            try {
                // Create or get today's bill for this patient
                $bill = Bill::firstOrCreate(
                    [
                        'patient_id'   => $patient->patient_id, // Bill for this patient
                        'admission_id' => optional($patient->admissionDetail)->admission_id, // For this admission
                        'billing_date' => today(), // For today
                    ],
                    ['payment_status' => 'pending'] // Default status
                );

                // Create prescription header
                $prescription = Prescription::create([
                    'patient_id' => $patient->patient_id, // Link to patient
                    'doctor_id'  => $doctorId, // Link to doctor
                    'refills'    => $refills, // Number of refills
                    'daw'        => $daw, // Dispense as written
                ]);

                // Create pharmacy charge header
                $rxNumber = 'RX' . now()->format('YmdHis') . Str::upper(Str::random(3)); // Generate RX number
                $pharmCharge = PharmacyCharge::create([
                    'patient_id'         => $patient->patient_id, // Link to patient
                    'prescribing_doctor' => Doctor::find($doctorId)->doctor_name ?? '-', // Doctor name
                    'rx_number'          => $rxNumber, // RX number
                    'notes'              => $data['medications'][0]['instructions'] ?? null, // Notes from first medication
                    'total_amount'       => 0, // Will be updated later
                    'status'             => 'pending', // Initial status
                ]);

                $grandTotal = 0; // Track total charge

                // Loop through each medication row
                foreach ($data['medications'] as $row) {
                    $svc   = HospitalService::findOrFail($row['medication_id']); // Get service (medication) details
                    $line  = $svc->price * $row['quantity']; // Calculate line total
                    $grandTotal += $line; // Add to grand total

                    // Add prescription item
                    $prescription->items()->create([
                        'service_id'     => $svc->service_id, // Medication ID
                        'name'           => $svc->service_name, // Medication name
                        'datetime'       => now(), // Current time
                        'quantity_asked' => $row['quantity'], // Quantity requested
                        'quantity_given' => 0, // Not yet dispensed
                        'duration'       => $row['duration'], // Duration
                        'duration_unit'  => $row['duration_unit'], // Duration unit
                        'instructions'   => $row['instructions'] ?? '', // Instructions
                        'status'         => 'pending', // Initial status
                    ]);


                    // Add pharmacy charge item
                    PharmacyChargeItem::create([
                        'charge_id'  => $pharmCharge->id, // Link to pharmacy charge
                        'service_id' => $svc->service_id, // Medication ID
                        'quantity'   => $row['quantity'], // Quantity
                        'unit_price' => $svc->price, // Price per unit
                        'total'      => $line, // Line total
                        'status' => 'pending',
                    ]);
                    
                    $notification = notifications_latest::create([
                        'type' => 'Action',              // example type
                        'sendTo_id' => $patient->patient_id,              // ID of the recipient
                        'from_name' => 'Doctor',        // sender name
                        'read' => '0',                 // mark as unread
                        'message' => 'Your doctor has sent a request to the pharmacy for ' . $svc->service_name,
                        'sendTouser_type' => 'patient',
                        'titleReference' => 'Charge created',
                        'category' => 'pharmacy',
                        'idReference' => $prescription->id, // Add this line
                        
                    ]);

                    // ADD THIS LOG:
                    UserAuditHelper::logCharge(
                        $patient->patient_id,
                        $line,
                        $svc->service_name . " (Qty: {$row['quantity']})",
                        'doctor_medication'
                    );
                }

                // Update pharmacy charge total
                $pharmCharge->update(['total_amount' => $grandTotal]);

                DB::commit(); // Commit transaction

                Log::debug('[OrderEntry] MED + PHARM OK', [
                    'bill_id'      => $bill->billing_id, // Bill ID
                    'rx'           => $pharmCharge->rx_number, // RX number
                    'presc_id'     => $prescription->id, // Prescription ID
                ]);

                return back()->with('success', 'Medication orders submitted, Sent to the Pharmacy.');

            } catch (\Throwable $e) {
                DB::rollBack(); // Rollback on error
                Log::error('[OrderEntry] MED FAIL', [
                    'error' => $e->getMessage(), // Log error message
                    'trace' => $e->getTraceAsString(), // Log stack trace
                ]);
                return back()->withErrors('Unable to submit medication orders.');
            }  

            
        }

        // Handle lab and imaging orders
        if ($type === 'lab') {
            $data = $request->validate([
                'labs' => 'required|array|min:1',
                'diagnosis' => 'nullable|string',
            ]);

            // Fix the extraction of service IDs
            $serviceIDs = collect($request->input('labs', []))
                ->filter()
                ->values();

            if ($serviceIDs->isEmpty()) {
                Log::info('[OrderEntry] LAB form submitted with no items');
                return back()->withErrors('Select at least one Lab / Imaging study.');
            }

            DB::beginTransaction();
            try {
                foreach ($serviceIDs as $service_id) {
                    $service = HospitalService::findOrFail($service_id);

                    // Create service assignment first
                    $serviceAssignment = ServiceAssignment::create([
                        'patient_id'     => $patient->patient_id,
                        'doctor_id'      => $doctorId,
                        'service_id'     => $service->service_id,
                        'amount'         => $service->price,
                        'service_status' => 'pending',
                        'mode'           => 'lab',
                    ]);

                    Log::debug('[OrderEntry] LAB/IMG assignment created', [
                        'service' => $service->service_name,
                    ]);

                    // Now we can use the service assignment ID for the reference
                    $notification = notifications_latest::create([
                        'type' => 'Action',
                        'sendTo_id' => $patient->patient_id,
                        'from_name' => 'Doctor',
                        'read' => '0',
                        'message' => 'Your doctor has sent a request to the laboratory for ' . $service->service_name,
                        'sendTouser_type' => 'patient',
                        'titleReference' => 'Charge created',
                        'category' => 'laboratory',
                        'idReference' => $serviceAssignment->id, // Add this line
                    ]);

                    UserAuditHelper::logCharge(
                        $patient->patient_id,
                        $service->price,
                        $service->service_name,
                        'doctor_lab'
                    );
                }

                // // ✅ ADD THIS:
                // $request->status = 'approved';
                // $request->save();

                DB::commit();
                return redirect()->route('doctor.nurse-requests')->with('success', 'Lab / Imaging order approved.');
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('[OrderEntry] LAB /Imaging services FAILED', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]);
                return back()->withErrors('Unable to submit laboratory request: ' . $e->getMessage());
            }
        } elseif ($type === 'operation') {
            $data = $request->validate([
                'services' => 'required|array|min:1',
                'professional_fee' => 'nullable|numeric|min:0',
                'diagnosis' => 'nullable|string',
            ]);

            $serviceIDs = collect($request->input('services', []))
                ->filter()
                ->values();

            if ($serviceIDs->isEmpty()) {
                Log::info('[OrderEntry] Operation form submitted with no items');
                return back()->withErrors('Select at least one operation service.');
            }

            DB::beginTransaction();
            try {
                foreach ($serviceIDs as $service_id) {
                    $service = HospitalService::findOrFail($service_id);
                    
                    $totalCost = $service->price + ($request->input('professional_fee', 0));

                    // Create service assignment first
                    $serviceAssignment = ServiceAssignment::create([
                        'patient_id'     => $patient->patient_id,
                        'doctor_id'      => $doctorId,
                        'service_id'     => $service->service_id,
                        'amount'         => $totalCost,
                        'service_status' => 'pending',
                        'mode'           => 'or',
                        'notes'          => 'Includes professional fee: ₱' . number_format($request->input('professional_fee', 0), 2),
                    ]);

                    // Add notification for patient with the correct reference
                    notifications_latest::create([
                        'type' => 'Action',
                        'sendTo_id' => $patient->patient_id,
                        'from_name' => 'Doctor',
                        'read' => '0',
                        'message' => 'Your doctor has ordered a procedure: ' . $service->service_name,
                        'sendTouser_type' => 'patient',
                        'titleReference' => 'Charge created',
                        'category' => 'operating',
                        'idReference' => $serviceAssignment->id, // Add this line
                    ]);

                    // Log charge
                    UserAuditHelper::logCharge(
                        $patient->patient_id,
                        $totalCost,
                        $service->service_name,
                        'doctor_operation'
                    );
                }

                DB::commit();
                return back()->with('success', 'Operating room services submitted successfully.');
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('[OrderEntry] Operation services FAILED', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return back()->withErrors('Unable to submit operation request: ' . $e->getMessage());
            }
        }

        // Unknown order type
        Log::warning('[OrderEntry] Unknown type supplied', ['type' => $type]); // Log warning
        abort(400, 'Unknown order type'); // Abort with error

        // (Unreachable, but fallback)
        return redirect()
           ->route('doctor.orders.index') // Redirect to orders index
           ->with('success', 'Order saved.')
           ->with('show_patient', $patient->patient_id); // Pass patient ID to view
    }

       // List all patients with orders for this doctor
    public function ordersIndex(Request $request)
    {
        $doctorId = optional(Auth::user()->doctor)->doctor_id; // Get current doctor ID
        $status = $request->input('status');
        $query = $request->input('q');

        // Debug the incoming request
        Log::debug('[DoctorController] ordersIndex status filter', [
            'status' => $status,
            'query' => $query
        ]);

        
        $patientsQuery = Patient::whereHas('admissionDetail', function($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId); // Only patients assigned to this doctor
            });
    
        // Filter by status if requested
        if ($status === 'active') {
            $patientsQuery->where(function($q) {
                $q->where('medication_finished', 0)
                  ->orWhereNull('medication_finished');
            });
        } elseif ($status === 'finished') {
            $patientsQuery->where('medication_finished', 1);
        }
        
        // Filter by search query if provided
        if ($query) {
            $patientsQuery->where(function($q) use ($query) {
                // Check if searching by PID format
                if (str_starts_with($query, 'PID-')) {
                    $numericId = ltrim(substr($query, 4), '0'); // Remove "PID-" and leading zeros
                    $q->where('patient_id', $numericId);
                } else {
                    $q->where('patient_id', 'like', "%{$query}%")
                      ->orWhere('patient_first_name', 'like', "%{$query}%")
                      ->orWhere('patient_last_name', 'like', "%{$query}%");
                }
            });
        }

        // Get counts before pagination for accurate metrics
        $totalActiveCount = (clone $patientsQuery)->where(function($q) {
            $q->where('medication_finished', 0)
              ->orWhereNull('medication_finished');
        })->count();
        $totalFinishedCount = (clone $patientsQuery)->where('medication_finished', 1)->count();

        $patients = $patientsQuery
            ->withCount(['serviceAssignments', 'prescriptions']) // Count assignments and prescriptions
            ->orderBy('medication_finished') // Show active patients first
            ->orderBy('patient_last_name') // Then order by last name
            ->paginate(12) // Paginate results
            ->withQueryString(); // Preserve URL query parameters

        Log::debug('[DoctorController] ordersIndex results', [
            'total' => $patients->total(),
            'activeCount' => $totalActiveCount,
            'finishedCount' => $totalFinishedCount,
            'current_page_count' => $patients->count()
        ]);

        return view('doctor.orders-index', compact('patients', 'totalActiveCount', 'totalFinishedCount')); 
    }


     // Show detailed orders view for a patient
    public function showOrders($patientId)
    {
        // Find the patient by ID (404 if not found)
        $patient = Patient::findOrFail($patientId);

        // Get all service assignments (lab, imaging, operation, etc.)
        $serviceOrders = ServiceAssignment::where('patient_id', $patient->patient_id)
            ->with('service') // Eager load service details
            ->latest() // Most recent first
            ->get();

        // Get all prescription items (medications)
        $medOrders = PrescriptionItem::whereHas('prescription', function($q) use ($patient) {
                $q->where('patient_id', $patient->patient_id);
            })
            ->with('service') // Eager load medication details
            ->orderByDesc('datetime')
            ->get();

        // Get pharmacy charges (approved vs. assigned)
         $pharmacyCharges = PharmacyCharge::with(['items.service'])
        ->where('patient_id', $patient->patient_id)
        ->get();

    // Pass both lists to the show view (use the existing orders-show blade)
    return view('doctor.orders-show', compact('patient', 'serviceOrders', 'medOrders', 'pharmacyCharges'));
    }


    public function patientFinished($patient_id)
    {
        // find patient
        $patient = Patient::where('patient_id', $patient_id)->first();

        // update patient status (adjust field name as needed, e.g. "status")
        $patient->medication_finished = 1;
        $patient->save();

        // reMOVED KASI NADOUBLE NA
        // // find the bed assigned to this patient
        // $bed = Bed::where('patient_id', $patient_id)->first();

        // if ($bed) {
        //     // duplicate the row
        //     $newBed = $bed->replicate();
        //     $newBed->patient_id = null;   // reset patient
        //     $newBed->status = 'available'; // set new status
        //     $newBed->save();

        //     // (optional) you can also update the old bed if needed
        //     // $bed->status = 'finished';
        //     // $bed->save();
        // }

        // redirect back with success message
        return redirect()->back()->with('success', 'Patient marked as finished.');
    }

    // View nurse requests assigned to this doctor

    public function nurseRequests()
    {
        $doctorId = auth()->user()->doctor->doctor_id;
        $requests = \App\Models\NurseRequest::where('doctor_id', $doctorId)
            ->where('status', 'pending')
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('doctor.nurse-refills', compact('requests'));
    }

   
    public function showAcceptForm(NurseRequest $request)
    {
        // dd($request->toArray());
        $payload = json_decode($request->payload, true);
        $patient = $request->patient; // This uses the relationship

        // Check for missing patient
        if (!$request->patient_id) {
            abort(500, 'NurseRequest is missing patient_id');
        }
        if (!$patient) {
            abort(500, 'Patient not found for this nurse request');
        }

        return view('doctor.nurse-request-accept', compact('request', 'payload', 'patient'));
    }

    public function acceptNurseRequest(Request $httpRequest, NurseRequest $request)
    {
        \Log::debug('NurseRequest Debug', [
            'id' => $request->id,
            'exists' => $request->exists,
            'attributes' => $request->getAttributes(),
            'patient_id' => $request->patient_id,
            'type' => $request->type,
            'status' => $request->status,
        ]);

        if (!$request->exists || !$request->patient_id) {
            abort(500, 'Invalid nurse request or missing patient_id');
        }

        $type = $request->type;
        $doctorId = auth()->user()->doctor->doctor_id;
        $patient = $request->patient;

        if ($type === 'medication') {
            $data = $httpRequest->validate([
                'medications'                 => 'required|array|min:1',
                'medications.*.medication_id' => 'required|exists:hospital_services,service_id',
                'medications.*.quantity'      => 'required|integer|min:1',
                'medications.*.duration'      => 'required|integer|min:1',
                'medications.*.duration_unit' => 'required|in:days,weeks',
                'medications.*.instructions'  => 'nullable|string',
            ]);

            DB::beginTransaction();
            try {
                // Create prescription header
                $prescription = Prescription::create([
                    'patient_id' => $patient->patient_id,
                    'doctor_id'  => $doctorId,
                    'refills'    => 0,
                    'daw'        => false,
                ]);

                // Create pharmacy charge header
                $rxNumber = 'RX' . now()->format('YmdHis') . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(3));
                $pharmCharge = PharmacyCharge::create([
                    'patient_id'         => $patient->patient_id,
                    'prescribing_doctor' => auth()->user()->doctor->doctor_name ?? '-',
                    'rx_number'          => $rxNumber,
                    'notes'              => $data['medications'][0]['instructions'] ?? null,
                    'total_amount'       => 0,
                    'status'             => 'pending',
                ]);

                $grandTotal = 0;

                foreach ($data['medications'] as $row) {
                    $svc = HospitalService::findOrFail($row['medication_id']);
                    $line = $svc->price * $row['quantity'];
                    $grandTotal += $line;

                    // Add prescription item
                    $prescription->items()->create([
                        'service_id'     => $svc->service_id,
                        'name'           => $svc->service_name,
                        'datetime'       => now(),
                        'quantity_asked' => $row['quantity'],
                        'quantity_given' => 0,
                        'duration'       => $row['duration'],
                        'duration_unit'  => $row['duration_unit'],
                        'instructions'   => $row['instructions'] ?? '',
                        'status'         => 'pending',
                    ]);

                    // Add pharmacy charge item
                    PharmacyChargeItem::create([
                        'charge_id'  => $pharmCharge->id,
                        'service_id' => $svc->service_id,
                        'quantity'   => $row['quantity'],
                        'unit_price' => $svc->price,
                        'total'      => $line,
                        'status'     => 'pending',
                    ]);
                }

                $pharmCharge->update(['total_amount' => $grandTotal]);
                $request->status = 'approved';
                $request->save();

                DB::commit();
                return redirect()->route('doctor.nurse-requests')->with('success', 'Medication request approved and sent to pharmacy.');
            } catch (\Throwable $e) {
                DB::rollBack();
                return back()->withErrors('Unable to process medication request: ' . $e->getMessage());
            }
        } elseif ($type === 'lab') {
            $data = $httpRequest->validate([
                'labs'                    => 'required|array|min:1',
                'labs.*.service_id'       => 'required|exists:hospital_services,service_id',
                'diagnosis'               => 'nullable|string',
            ]);

            $serviceIDs = collect($data['labs'] ?? [])
                ->pluck('service_id')
                ->unique()
                ->values();

            if ($serviceIDs->isEmpty()) {
                Log::info('[OrderEntry] LAB form submitted with no items');
                return back()->withErrors('Select at least one Lab / Imaging study.');
            }

            DB::beginTransaction();
            try {
                foreach ($serviceIDs as $service_id) {
                    $service = HospitalService::findOrFail($service_id);

                    // Create service assignment first
                    $serviceAssignment = ServiceAssignment::create([
                        'patient_id'     => $patient->patient_id,
                        'doctor_id'      => $doctorId,
                        'service_id'     => $service->service_id,
                        'amount'         => $service->price,
                        'service_status' => 'pending',
                        'mode'           => 'lab',
                    ]);

                    Log::debug('[OrderEntry] LAB/IMG assignment created', [
                        'service' => $service->service_name,
                    ]);

                    $notification = notifications_latest::create([
                        'type' => 'Action',
                        'sendTo_id' => $patient->patient_id,
                        'from_name' => 'Doctor',
                        'read' => '0',
                        'message' => 'Your doctor has sent a request to the laboratory for ' . $service->service_name,
                        'sendTouser_type' => 'patient',
                        'titleReference' => 'Charge created',
                        'category' => 'laboratory',
                        'idReference' => $serviceAssignment->id, // Add this line
                    ]);

                    UserAuditHelper::logCharge(
                        $patient->patient_id,
                        $service->price,
                        $service->service_name,
                        'doctor_lab'
                    );
                }

                // ✅ ADD THIS:
                $request->status = 'approved';
                $request->save();

                DB::commit();
                return redirect()->route('doctor.nurse-requests')->with('success', 'Lab / Imaging order approved.');
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('[OrderEntry] LAB /Imaging services FAILED', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]);
                return back()->withErrors('Unable to submit laboratory request: ' . $e->getMessage());
            }
        } elseif ($type === 'operation') {
            $data = $httpRequest->validate([
                'services' => 'required|array|min:1',
                'professional_fee' => 'nullable|numeric|min:0',
                'diagnosis' => 'nullable|string',
            ]);

            $serviceIDs = collect($data['services'] ?? [])
                ->pluck('service_id')
                ->unique()
                ->values();

            if ($serviceIDs->isEmpty()) {
                Log::info('[OrderEntry] Operation form submitted with no items');
                return back()->withErrors('Select at least one operation service.');
            }

            DB::beginTransaction();
            try {
                foreach ($serviceIDs as $service_id) {
                    $service = HospitalService::findOrFail($service_id);
                    
                    $totalCost = $service->price + ($data['professional_fee'] ?? 0);

                    // Create service assignment first
                    $serviceAssignment = ServiceAssignment::create([
                        'patient_id'     => $patient->patient_id,
                        'doctor_id'      => $doctorId,
                        'service_id'     => $service->service_id,
                        'amount'         => $totalCost,
                        'service_status' => 'pending',
                        'mode'           => 'or',
                        'notes'          => 'Includes professional fee: ₱' . number_format($data['professional_fee'] ?? 0, 2),
                    ]);

                    // Add notification for patient with the correct reference
                    notifications_latest::create([
                        'type' => 'Action',
                        'sendTo_id' => $patient->patient_id,
                        'from_name' => 'Doctor',
                        'read' => '0',
                        'message' => 'Your doctor has ordered a procedure: ' . $service->service_name,
                        'sendTouser_type' => 'patient',
                        'titleReference' => 'Charge created',
                        'category' => 'operating',
                        'idReference' => $serviceAssignment->id, // Add this line
                    ]);

                    // Log charge
                    UserAuditHelper::logCharge(
                        $patient->patient_id,
                        $totalCost,
                        $service->service_name,
                        'doctor_operation'
                    );
                }

                DB::commit();
                return redirect()->route('doctor.nurse-requests')->with('success', 'Operating room request approved.');
            } catch (\Throwable $e) {
                DB::rollBack();
                return back()->withErrors('Unable to submit operating room request: ' . $e->getMessage());
            }
        }

        $request->status = 'approved';
        $request->save();
        return redirect()->route('doctor.nurse-requests')->with('success', 'Request approved and processed.');
    }


    public function rejectNurseRequest($id)
    {
        $request = \App\Models\NurseRequest::findOrFail($id);
        $request->status = 'rejected';
        $request->save();
        return back()->with('success', 'Request rejected.');
    }
}

