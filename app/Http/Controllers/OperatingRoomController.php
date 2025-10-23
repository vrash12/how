<?php
// app/Http/Controllers/OperatingRoomController.php

namespace App\Http\Controllers;

use App\Models\ServiceAssignment;
use App\Models\HospitalService;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Notifications\ORChargeCreated;
use App\Models\notifications_latest;
use App\Helpers\UserAuditHelper;


class OperatingRoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* --------------------------------------------------------
     *  DASHBOARD
     * ----------------------------------------------------- */
    public function dashboard()
    {
        $today = Carbon::today();

        // counts
        $completedCount = ServiceAssignment::whereHas('service',
                fn ($q) => $q->where('service_type', 'operation'))
            ->where('service_status', 'completed')
            ->count();

        $pendingCount = ServiceAssignment::whereHas('service',
                fn ($q) => $q->where('service_type', 'operation'))
            ->where('service_status', 'pending')
            ->whereHas('patient', function($query) {
                // Only include patients who are NOT marked as finished
                $query->where(function($q) {
                    $q->where('medication_finished', 0)
                      ->orWhereNull('medication_finished');
                });
            })
            ->count();

        $patientsServed = ServiceAssignment::whereHas('service',
                fn ($q) => $q->where('service_type', 'operation'))
            ->distinct('patient_id')
            ->count('patient_id'); // Fixed this line

        // ADD: Missing variables for alerts
        $urgentProcedures = ServiceAssignment::with(['patient', 'doctor', 'service'])
            ->whereHas('service', fn ($q) => $q->where('service_type', 'operation'))
            ->where('service_status', 'pending')
            ->where('created_at', '<', now()->subHours(2))
            ->whereHas('patient', function($query) {
                $query->where(function($q) {
                    $q->where('medication_finished', 0)
                      ->orWhereNull('medication_finished');
                });
            })
            ->orderBy('created_at')
            ->get();

        $newProcedures = ServiceAssignment::with(['patient', 'doctor', 'service'])
            ->whereHas('service', fn ($q) => $q->where('service_type', 'operation'))
            ->where('service_status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->whereHas('patient', function($query) {
                $query->where(function($q) {
                    $q->where('medication_finished', 0)
                      ->orWhereNull('medication_finished');
                });
            })
            ->orderByDesc('created_at')
            ->get();

        // today vs earlier
        $todayProcedures = ServiceAssignment::with(['patient', 'doctor', 'service'])
            ->whereHas('service', fn ($q) => $q->where('service_type', 'operation'))
            ->whereDate('created_at', $today)
            ->latest()
            ->get();

        $earlierProcedures = ServiceAssignment::with(['patient', 'doctor', 'service'])
            ->whereHas('service', fn ($q) => $q->where('service_type', 'operation'))
            ->whereDate('created_at', '<', $today)
            ->latest()
            ->take(10)
            ->get();

        $upcomingProcedures = ServiceAssignment::with(['patient','doctor','service'])
            ->whereHas('service', fn($q) => $q->where('service_type', 'operation'))
            ->where('service_status', 'pending')
            ->where('datetime', '>', now())
            ->whereHas('patient', function($query) {
                $query->where(function($q) {
                    $q->where('medication_finished', 0)
                      ->orWhereNull('medication_finished');
                });
            })
            ->orderBy('datetime')
            ->get();

        return view('operatingroom.dashboard', compact(
            'completedCount',
            'pendingCount',
            'patientsServed',
            'urgentProcedures',      // ADD
            'newProcedures',         // ADD
            'todayProcedures',
            'earlierProcedures',
            'upcomingProcedures'
        ));
    }


    /* --------------------------------------------------------
     *  QUEUE / LIST (Show pending OR requests from doctors)
     * ----------------------------------------------------- */
    public function queue(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        
        $query = ServiceAssignment::with(['patient','doctor','service'])
            ->whereHas('service', fn($q) => $q->where('service_type', 'operation'));

        if ($statusFilter !== 'all') {
            $query->where('service_status', $statusFilter);
        } else {
            // Show pending requests by default
            $query->where('service_status', 'pending');
        }

        // Filter out finished patients
        $query->whereHas('patient', function($q) {
            $q->where(function($q) {
                $q->where('medication_finished', 0)
                  ->orWhereNull('medication_finished');
            });
        });

        $orRequests = $query->orderBy('created_at', 'desc')
                           ->paginate(10)
                           ->withQueryString();

        return view('operatingroom.queue', compact('orRequests'));
    }


    // /* --------------------------------------------------------
    //  *  STORE  (save new charges)
    //  * ----------------------------------------------------- */
    // public function store(Request $request)
    // {
    //     $data = $request->validate([
    //         'patient_id'             => 'required|exists:patients,patient_id',
    //         'doctor_id'              => 'required|exists:doctors,doctor_id',
    //         'misc_item'              => 'required|array|min:1',
    //         'misc_item.*.service_id' => 'required|exists:hospital_services,service_id',
    //         'misc_item.*.orNumber'   => 'nullable|integer|min:1',
    //         'notes'                  => 'nullable|string',
    //     ]);
    
    //     $user     = Auth::user();
    //     $doctor   = Doctor::findOrFail($data['doctor_id']);
    //     $patient  = Patient::findOrFail($data['patient_id']);
    
    //     // 1) Find or create today's Bill
    //     $bill = \App\Models\Bill::firstOrCreate(
    //         ['patient_id'=>$patient->patient_id, 'billing_date'=>now()->toDateString()],
    //         ['payment_status'=>'pending']
    //     );
    
    //     foreach ($data['misc_item'] as $item) {
    //         $service  = HospitalService::findOrFail($item['service_id']);
    //         $amount   = $service->price;
    //         $orNumber = $item['orNumber'] ?? null;
    
    //         // 2) Create the ServiceAssignment first
    //         $assignment = ServiceAssignment::create([
    //             'patient_id'     => $patient->patient_id,
    //             'doctor_id'      => $doctor->doctor_id,
    //             'service_id'     => $service->service_id,
    //             'amount'         => $amount,
    //             'room'           => $orNumber,
    //             'service_status' => 'pending',
    //             'notes'          => $data['notes'] ?? null,
    //             'datetime'       => now(),
    //         ]);
    
    //         // 3) Then create the BillItem linking back to assignment_id
    //         $billItem = \App\Models\BillItem::create([
    //             'billing_id'    => $bill->billing_id,
    //             'service_id'    => $service->service_id,
    //             'assignment_id' => $assignment->assignment_id,
    //             'quantity'      => 1,
    //             'amount'        => $amount,
    //             'billing_date'  => $bill->billing_date,
    //         ]);
    
    //         // 4) Finally, log it
    //         \App\Models\AuditLog::create([
    //             'bill_item_id' => $billItem->billing_item_id,
    //             'action'       => 'create',
    //             'message'      => "OR charge {$service->service_name} (₱{$amount}) added by {$user->username} for Dr. {$doctor->doctor_name}",
    //             'actor'        => $user->username,
    //             'icon'         => 'fa-user-md',
    //         ]);
    //     }

    //     $patient->notify(new ORChargeCreated($assignment));
    
    //     return redirect()->route('operating.queue')
    //                      ->with('success','Operating-room charges recorded.');
    // }
    

/* --------------------------------------------------------
     *  MARK COMPLETED - Like Lab module
     * ----------------------------------------------------- */
    public function markCompleted(ServiceAssignment $assignment)
    {
        // Check if the patient is finished
        $patient = Patient::find($assignment->patient_id);
        if ($patient && $patient->medication_finished) {
            return redirect()->back()->with('error', 'This patient has been marked as finished. Pending OR orders cannot be processed.');
        }

        $services = HospitalService::where('service_id', $assignment->service_id)->first();

        // Create notification for patient
        $notification = notifications_latest::create([
            'type' => 'Service',
            'sendTo_id' => $assignment->patient_id,
            'from_name' => 'Operating Room',
            'read' => '0',
            'message' => $services->service_name . ' procedure has been completed.',
            'sendTouser_type' => 'patient',
            'idReference' => $assignment->assignment_id,  // ADDED
            'titleReference' => 'Charge created',         // ADDED - match timeline display
            'category' => 'operating',                    // ADDED - match blade query
        ]);

        // Update status
        $assignment->service_status = 'completed';
        $assignment->save();

        // ADD THIS LOG:
        UserAuditHelper::logComplete(
            $assignment->patient_id,
            $services->service_name,
            'operating_room',
            $assignment->assignment_id
        );

        // Send notification (if you have ORChargeCompleted notification)
        // $assignment->patient->notify(new ORChargeCompleted($assignment));

        return redirect()
            ->route('operating.dashboard')
            ->with('success', 'Procedure marked as completed.');
    }


    /* --------------------------------------------------------
     *  HISTORY - Show completed procedures
     * ----------------------------------------------------- */
    public function history()
    {
        $procedures = ServiceAssignment::with(['patient', 'service', 'doctor'])
            ->whereHas('service', fn($q) => $q->where('service_type', 'operation'))
            ->whereIn('service_status', ['completed', 'cancelled']) // Include both statuses
            ->orderByDesc('updated_at')
            ->get();

        // Count all finished procedures
        $finishedCount = $procedures->count();

        return view('operatingroom.history', compact('procedures', 'finishedCount'));
    }

    // // ADD: Just the show method for the "View" button
    // public function show(ServiceAssignment $assignment)
    // {
    //     $assignment->load(['patient','doctor','service']);
    //     return view('operatingroom.details', compact('assignment'));
    // }

    /* --------------------------------------------------------
     *  CANCEL PROCEDURE - New method to cancel an OR procedure
     * ----------------------------------------------------- */
    public function cancel(ServiceAssignment $assignment)
    {
        // Update the status to 'cancelled'
        $assignment->service_status = 'cancelled';
        $assignment->save();

        // Check if bill_item_id exists before creating the audit log
        if ($assignment->bill_item_id) {
            \App\Models\AuditLog::create([
                'bill_item_id' => $assignment->bill_item_id,
                'action'       => 'cancel',
                'message'      => "OR procedure {$assignment->service->service_name} was cancelled.",
                'actor'        => Auth::user()->username,
                'icon'         => 'fa-times-circle',
            ]);
        } else {
            \Log::warning("ServiceAssignment {$assignment->id} does not have a bill_item_id. Audit log not created.");
        }

        $services = HospitalService::where('service_id', $assignment->service_id)->first();

        // Create notification for patient
        $notification = notifications_latest::create([
            'type' => 'Service',
            'sendTo_id' => $assignment->patient_id,
            'from_name' => 'Operating Room',
            'read' => '0',
            'message' => $services->service_name . ' procedure has been cancelled.',
            'sendTouser_type' => 'patient',
            'idReference' => $assignment->assignment_id,  // ADDED
            'titleReference' => 'Procedure cancelled',    // ADDED
            'category' => 'operating',                    // ADDED - match blade query
        ]);

        // Redirect back with a success message
        return redirect()
            ->route('operating.queue')
            ->with('success', 'Procedure has been cancelled.');
    }
}
