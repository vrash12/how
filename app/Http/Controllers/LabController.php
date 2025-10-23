<?php
//app/Http/Controllers/LabController.php
namespace App\Http\Controllers;

use App\Models\HospitalService;
use Illuminate\Http\Request;
use App\Models\ServiceAssignment;
use App\Models\notifications_latest;
use App\Models\HospitalService as Service;
use Illuminate\Support\Facades\Auth;
use App\Models\Doctor;
use App\Models\Patient;
use App\Notifications\LabChargeCreated;
use App\Notifications\LabChargeCompleted;
use Carbon\Carbon;
use App\Helpers\UserAuditHelper;

class LabController extends Controller
{
    public function __construct()
    {
        // Require authentication for all methods in this controller
        $this->middleware('auth');
    }

    // Dashboard: stats and recent admissions
    public function dashboard()
    {
        $today = Carbon::today();

        $completedCount = ServiceAssignment::whereHas('service', fn($q) => $q->where('service_type', 'lab'))
            ->where('service_status', 'completed')->count();

        $pendingCount = ServiceAssignment::whereHas('service', fn($q) => $q->where('service_type', 'lab'))
            ->where('service_status', 'pending')
            ->whereHas('patient', function($query) {
                // Only include patients who are NOT marked as finished
                $query->where(function($q) {
                    $q->where('medication_finished', 0)
                      ->orWhereNull('medication_finished');
                });
            })
            ->count();

        $patientsServed = ServiceAssignment::whereHas('service', fn($q) => $q->where('service_type', 'lab'))
            ->distinct('patient_id')->get();

        $todayAdmissions = ServiceAssignment::with('patient','doctor')
            ->whereHas('service', fn($q) => $q->where('service_type', 'lab'))
            ->whereDate('created_at', $today)
            ->latest()->get();

        $earlierAdmissions = ServiceAssignment::with('patient','doctor')
            ->whereHas('service', fn($q) => $q->where('service_type', 'lab'))
            ->whereDate('created_at', '<', $today)
            ->latest()->take(10)->get();

        // Optional: Upcoming dispense (scheduled for future, if you use scheduled_at)
        $upcomingDispense = ServiceAssignment::with(['patient','doctor','service'])
            ->whereHas('service', fn($q) => $q->where('service_type', 'lab'))
            ->where('service_status', 'pending')
            ->where('created_at', '>', now())
            ->whereHas('patient', function($query) {
                // Only include patients who are NOT marked as finished
                $query->where(function($q) {
                    $q->where('medication_finished', 0)
                      ->orWhereNull('medication_finished');
                });
            })
            ->orderBy('created_at')
            ->get();

        return view('laboratory.dashboard', compact(
            'completedCount',
            'pendingCount',
            'patientsServed',
            'todayAdmissions',
            'earlierAdmissions',
            'upcomingDispense'
        ));
    }

    // Show the lab queue (pending lab requests)
    public function queue(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $labRequests = ServiceAssignment::with('patient', 'doctor', 'service')
            ->whereHas('service', function ($query) {
                $query->where('service_type', 'lab');
            })
            ->where('service_status', 'pending')
            ->whereHas('patient', function($query) {
                // Only include patients who are NOT marked as finished
                $query->where(function($q) {
                    $q->where('medication_finished', 0)
                      ->orWhereNull('medication_finished');
                });
            })
            ->get();

        return view('laboratory.queue', compact('labRequests'));
    }

    // Store a new lab charge/request (if you allow creation from lab UI)
    public function store(Request $request)
    {
        $data = $request->validate([
            'search_patient'       => 'required|exists:patients,patient_id',
            'doctor_id'            => 'required|exists:doctors,doctor_id',
            'charges'              => 'required|array|min:1',
            'charges.*.service_id' => 'required|exists:hospital_services,service_id',
            'charges.*.amount'     => 'required|numeric|min:0',
            'notes'                => 'nullable|string',
        ]);

        $patient = Patient::findOrFail($data['search_patient']);
        $doctor  = Doctor::findOrFail($data['doctor_id']);
        $user    = Auth::user();

        $bill = \App\Models\Bill::firstOrCreate([
            'patient_id'   => $patient->patient_id,
            'billing_date' => now()->toDateString(),
        ], ['payment_status'=>'pending']);

        foreach ($data['charges'] as $row) {
            $service = HospitalService::findOrFail($row['service_id']);
            $amount  = $service->price; // Always use the price from the DB

            $billItem = \App\Models\BillItem::create([
                'billing_id'   => $bill->billing_id,
                'service_id'   => $service->service_id,
                'quantity'     => 1,
                'amount'       => $amount,
                'billing_date' => $bill->billing_date,
            ]);

            $assignment = ServiceAssignment::create([
                'patient_id'     => $patient->patient_id,
                'doctor_id'      => $doctor->doctor_id,
                'service_id'     => $service->service_id,
                'amount'         => $amount,
                'service_status' => 'pending',
                'notes'          => $data['notes'] ?? null,
                'bill_item_id'   => $billItem->billing_item_id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            \App\Models\AuditLog::create([
                'bill_item_id' => $billItem->billing_item_id,
                'action'       => 'create',
                'message'      => "Lab “{$service->service_name}” (₱{$amount}) added by {$user->username} for Dr. {$doctor->doctor_name}",
                'actor'        => $user->username,
                'icon'         => 'fa-vials',
            ]);

            $assignment->patient->notify(new LabChargeCreated($assignment));
        }

        return redirect()
            ->route('laboratory.queue')
            ->with('success','Lab charges have been successfully created.');
    }

    // Mark a lab request as completed (single action)
    public function markCompleted(ServiceAssignment $assignment)
    {
        // Check if the patient is finished
        $patient = Patient::find($assignment->patient_id);
        if ($patient && $patient->medication_finished) {
            return redirect()->back()->with('error', 'This patient has been marked as finished. Pending lab orders cannot be processed.');
        }

        $services = Service::where('service_id', $assignment->service_id)->first();

        // Creating a new notification
        $notification = notifications_latest::create([
            'type' => 'Service',              // example type
            'sendTo_id' => $assignment->patient_id,              // ID of the recipient
            'from_name' => 'Laboratory',        // sender name
            'read' => '0',                 // mark as unread
            'message' => $services->service_name . ' has been completed.',
            'sendTouser_type' => 'patient',
            'idReference' => $assignment->assignment_id ,
            'titleReference' => 'Laboratory Completed',
            'category' => 'laboratory',
        ]);
        
        $assignment->service_status = 'completed';
        $assignment->save();
        
        $assignment->patient->notify(new LabChargeCompleted($assignment));

        // ADD THIS LOG:
        UserAuditHelper::logComplete(
            $assignment->patient_id,
            $services->service_name,
            'laboratory',
            $assignment->assignment_id
        );

        return redirect()
            ->route('laboratory.queue', $assignment)
            ->with('success','Request marked as completed.');
    }

    // Show the laboratory history (completed and pending lab requests)
public function history()
    {
        // Fetch completed lab orders
        $completedOrders = ServiceAssignment::with(['patient', 'service', 'doctor'])
            ->whereHas('service', fn($q) => $q->where('service_type', 'lab'))
            ->where('service_status', 'completed')
            ->orderByDesc('updated_at')
            ->get();

        // Fetch cancelled lab orders
        $cancelledOrders = ServiceAssignment::with(['patient', 'service', 'doctor'])
            ->whereHas('service', fn($q) => $q->where('service_type', 'lab'))
            ->where('service_status', 'cancelled')
            ->orderByDesc('updated_at')
            ->get();

        return view('laboratory.history', compact('completedOrders', 'cancelledOrders'));
    }

    public function show(ServiceAssignment $assignment)
    {
        $assignment->load(['patient', 'doctor', 'service']);
        return view('laboratory.view', compact('assignment'));
    }

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
                'message'      => "Lab request {$assignment->service->service_name} was cancelled.",
                'actor'        => Auth::user()->username,
                'icon'         => 'fa-times-circle',
            ]);
        } else {
            \Log::warning("ServiceAssignment {$assignment->id} does not have a bill_item_id. Audit log not created.");
        }
$services = Service::where('service_id', $assignment->service_id)->first();


                $notification = notifications_latest::create([
            'type' => 'Service',              // example type
            'sendTo_id' => $assignment->patient_id,              // ID of the recipient
            'from_name' => 'Laboratory',        // sender name
            'read' => '0',                 // mark as unread
            'message' => $services->service_name . ' has been cancelled.',
            'sendTouser_type' => 'patient',
            'idReference' => $assignment->assignment_id ,
            'titleReference' => 'Laboratory Cancelled',
            'category' => 'laboratory',
        ]);

                // ADD THIS LOG:
        UserAuditHelper::logComplete(
            $assignment->patient_id,
            $services->service_name,
            'laboratory',
            $assignment->assignment_id
        );

        

        // Redirect back with a success message
        return redirect()
            ->route('laboratory.queue')
            ->with('success', 'Lab request has been cancelled.');
    }
}
