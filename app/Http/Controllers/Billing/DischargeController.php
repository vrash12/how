<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Traits\BillingCalculationTrait;
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
use App\Helpers\Audit;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Patient;
use App\Models\AdmissionDetail;
use App\Models\PharmacyCharge;
use App\Models\PharmacyChargeItem;
use App\Models\AuditLog;
use App\Models\notifications_latest;
use App\Models\UserAuditTrail;
use Illuminate\Support\Facades\Log;
use App\Models\Bed;

class DischargeController extends Controller
{
    use BillingCalculationTrait;
    
    public function __construct()
    {
        $this->middleware(['auth', 'role:billing']);
    }

    /**
     * Show a list of all active patients and their billing/discharge info.
     */
    public function index(Request $request)
    {
        $query = Patient::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['active', 'finished']);
        }

        // Search by name or PID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('patient_first_name', 'like', "%{$search}%")
                  ->orWhere('patient_last_name', 'like', "%{$search}%")
                  ->orWhere('patient_id', 'like', "%{$search}%");
            });
        }

        $activePatients = $query->get();
        $patients = [];
        foreach ($activePatients as $patientData) {
            $totals = $this->calculatePatientTotals($patientData);
            $patients[] = [
                'balance'      => $totals['balance'],
                'grandTotal'   => $totals['grandTotal'],
                'patient_id'   => $patientData->patient_id,
                'patient_first_name' => $patientData->patient_first_name,
                'patient_last_name'  => $patientData->patient_last_name,
                'depositBayad' => $totals['paymentsMade'],
                'depositArray' => $totals['depositArray'],
                'patient'      => $patientData,
                'admission'    => $totals['admission'],
                'admissions'   => $totals['admissions'],
                'admissionId'  => $totals['admissionId'],
                'totals'       => $totals,
                'bedRate'      => $totals['bedRate'],
                'doctorFee'    => $totals['doctorFee'],
                'pharmacyTotal'=> $totals['rxTotal'],
                'laboratoryFee'=> $totals['labFee'],
                'ORFee'        => $totals['orFee'],
                'pharmacyPendingTotal' => $totals['rxPendingTotal'],
                'paymentsMade' => $totals['paymentsMade'],
                'daysAdmitted' => $totals['daysAdmitted'],
            ];
        }
        return view('billing.records.index', compact('patients'));
    }

    /**
     * Settle the patient's balance and discharge them if fully paid.
     */
    public function settle(Request $request, Patient $patient)
    {
        DB::beginTransaction();

        try {
            // Calculate the outstanding balance
            $grandTotal = $this->calculateGrandTotal($patient);
            $totalPaid = Deposit::where('patient_id', $patient->patient_id)->sum('amount');
            $balance = $grandTotal - $totalPaid;

            // Log the calculation for debugging
            \Log::info('Settle Calculation', [
                'patient_id' => $patient->patient_id,
                'grand_total' => $grandTotal,
                'total_paid' => $totalPaid,
                'calculated_balance' => $balance,
            ]);

            if ($balance <= 0) {
                return back()->withErrors('Patient already has no outstanding balance.');
            }

            // Create a deposit for the full balance
            $deposit = Deposit::create([
                'patient_id' => $patient->patient_id,
                'amount' => $balance,
                'deposited_at' => now(),
            ]);

            // Mark the patient as discharged
            $patient->update([
                'status' => 'finished',
                'billing_status' => 'finished',
                'billing_closed_at' => now(),
            ]);

            // Release the patient's bed
                        // Release the bed using the Bed model
            $bed = Bed::where('patient_id', $patient->patient_id)->first();
            if ($bed) {
                $bed->releaseBed(); // Ensure this is called only once
            } else {
                \Log::warning('No bed found for patient', [
                    'patient_id' => $patient->patient_id,
                ]);
            }



            DB::commit();

            return back()->with('success', 'Patient balance settled and discharged successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error settling patient balance: ' . $e->getMessage());
            return back()->withErrors('Failed to settle balance: ' . $e->getMessage());
        }
    }

    /**
     * Calculate the grand total for a patient.
     */
    private function calculateGrandTotal(Patient $patient)
    {
        $totals = $this->calculatePatientTotals($patient);
        return $totals['grandTotal'];
    }

    // Show detailed billing/discharge info for a single patient
    public function show(Patient $patient)
    {
        $patient_id = $patient->patient_id;
        
        // Use the trait to calculate all billing totals
        $billingData = $this->calculatePatientTotals($patient);
        
        // Get the latest admission
        $admission = $billingData['admission'];
        $admissions = $billingData['admissions'];
        
        // Extract values from the billing data
        $deposits = $billingData['depositArray'];
        $daysAdmitted = $billingData['daysAdmitted'];
        $doctorFee = $billingData['doctorFee'];
        $doctorDailyRate = $billingData['doctorDailyRate'];
        $bedRate = $billingData['bedRate'];
        $bedDailyRate = $billingData['bedDailyRate'];
        $labFee = $billingData['labFee'];
        $orFee = $billingData['orFee'];
        $pharmacyFee = $billingData['rxTotal'];
        $grandTotal = $billingData['grandTotal'];
        $totalPaid = $billingData['paymentsMade'];
        $balance = $billingData['balance'];
        
        // // Get all bills and bill items
        // $bills = Bill::with('items.service.department')
        //     ->where('patient_id', $patient_id)
        //     ->get();
            
        // Get all pharmacy charges and items
        $pharmacyCharges = PharmacyCharge::with('items.service')
            ->where('patient_id', $patient_id)
            ->get();
        
        // Get all service assignments
        $serviceAssignments = ServiceAssignment::with('service.department')
            ->where('patient_id', $patient_id)
            ->get();
        
        // Room fee (for reference only)
        $roomFee = optional($admission?->room)->rate ?? 0;
        
        return view('billing.records.show', compact(
            'patient',
            'admission',
            'admissions',
            'deposits',
            'pharmacyCharges',
            'serviceAssignments',
            'doctorFee',
            'doctorDailyRate',
            'roomFee',
            'labFee',
            'orFee',
            'pharmacyFee',
            'grandTotal',
            'totalPaid',
            'balance',
            'bedRate',
            'bedDailyRate',
            'daysAdmitted'
        ));
    }

    /**
     * Update a billing item (service, pharmacy, doctor, or room)
     * Logs the update in the audit trail.
     */
    public function updateBillingItem(Request $request, $id)
    {
        // Log the incoming request for debugging
        \Log::info('UpdateBillingItem Request:', [
            'id' => $id,
            'data' => $request->all()
        ]);

        // Validate the request
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:service_assignment,pharmacy_item,doctor_fee,room_fee,bed_fee',
            'status' => 'nullable|string'
        ]);

        // Check if the patient is finished
        $patientId = $request->get('patient_id');
        $patient = Patient::find($patientId);

        if ($patient && $patient->medication_finished) {
            return response()->json([
                'success' => false,
                'message' => 'This patient has been marked as finished. Updates to charges are not allowed.'
            ], 403);
        }

        try {
            // Extract request data
            $type = $request->get('type');
            $amount = $request->get('amount');
            $status = $request->get('status');

            $oldData = null;
            $patientId = null;
            $affectedTable = null;
            $patientName = null;
            $medicineName = null;
            $amountInvolved = null;

            // Handle updates based on the type
            switch ($type) {
                case 'service_assignment':
                    $item = ServiceAssignment::find($id);
                    if (!$item) {
                        \Log::error("Service assignment not found", ['id' => $id]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Service assignment not found.'
                        ], 404);
                    }
                    $oldData = $item->toArray();
                    $patientId = $item->patient_id ?? null;
                    $affectedTable = 'service_assignments';
                    $amountInvolved = $amount;
                    if ($patientId) {
                        $patient = \App\Models\Patient::find($patientId);
                        $patientName = $patient ? trim($patient->patient_first_name . ' ' . $patient->patient_last_name) : null;
                    }
                    $item->update([
                        'amount' => $amount,
                        'service_status' => $status
                    ]);
                    $newData = $item->fresh()->toArray();
                    break;

                case 'pharmacy_item':
                    $item = PharmacyChargeItem::with('service')->find($id);
                    if (!$item) {
                        \Log::error("Pharmacy item not found", ['id' => $id]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Pharmacy item not found.'
                        ], 404);
                    }
                    $oldData = $item->toArray();
                    $patientId = $item->charge?->patient_id ?? null;
                    $affectedTable = 'pharmacy_charge_items';
                    $medicineName = $item->service?->service_name ?? null;
                    $amountInvolved = $amount;
                    if ($patientId) {
                        $patient = \App\Models\Patient::find($patientId);
                        $patientName = $patient ? trim($patient->patient_first_name . ' ' . $patient->patient_last_name) : null;
                    }
                    $item->update([
                        'total' => $amount,
                        'status' => $status
                    ]);
                    $newData = $item->fresh()->toArray();
                    break;

                case 'doctor_fee':
                    $admission = AdmissionDetail::where('doctor_id', $id)->first();
                    if (!$admission || !$admission->doctor) {
                        \Log::error("Doctor fee not found", ['doctor_id' => $id]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Doctor fee not found.'
                        ], 404);
                    }
                    $oldData = $admission?->doctor?->toArray();
                    $patientId = $admission?->patient_id ?? null;
                    $affectedTable = 'doctors';
                    $amountInvolved = $amount;
                    if ($patientId) {
                        $patient = \App\Models\Patient::find($patientId);
                        $patientName = $patient ? trim($patient->patient_first_name . ' ' . $patient->patient_last_name) : null;
                    }
                    if ($admission && $admission->doctor) {
                        DB::table('doctors')
                            ->where('doctor_id', $admission->doctor_id)
                            ->update(['rate' => $amount]);
                    }
                    $newData = ['rate' => $amount];
                    break;

                case 'bed_fee':
                    $bed = Bed::find($id);
                    if (!$bed) {
                        \Log::error("Bed not found", ['bed_id' => $id]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Bed not found.'
                        ], 404);
                    }

                    // Validate that the bed ID is not 0
                    if ($id == 0) {
                        \Log::error("Invalid Bed ID", ['bed_id' => $id]);
                        throw new \Exception("Invalid Bed ID: {$id}. Please ensure the correct bed is selected.");
                    }

                    // Ensure the bed ID is valid and exists
                    $bed = DB::table('beds')->where('bed_id', $id)->first();
                    if (!$bed) {
                        \Log::error("Bed not found", ['bed_id' => $id]);
                        throw new \Exception("Bed with ID {$id} not found. Please verify the bed ID.");
                    }

                    // Ensure the bed is occupied by a patient
                    if (is_null($bed->patient_id)) {
                        \Log::error("Bed is not occupied", ['bed_id' => $id]);
                        throw new \Exception("Bed with ID {$id} is not currently occupied by any patient.");
                    }

                    // Fetch the patient details
                    $patient = \App\Models\Patient::find($bed->patient_id);
                    $patientName = $patient ? trim($patient->patient_first_name . ' ' . $patient->patient_last_name) : null;
                    $patientId = $bed->patient_id;

                    $oldData = (array) $bed;
                    $affectedTable = 'beds';
                    $amountInvolved = $amount;

                    // Update the specific bed's rate
                    $updated = DB::table('beds')
                        ->where('bed_id', $id)
                        ->update(['rate' => $amount]);

                    if (!$updated) {
                        \Log::error("Failed to update bed fee", ['bed_id' => $id, 'amount' => $amount]);
                        throw new \Exception("Failed to update bed fee for Bed ID {$id}. Ensure the bed exists and the ID is correct.");
                    }

                    \Log::info('Bed Fee Updated Successfully', [
                        'bed_id' => $id,
                        'new_rate' => $amount,
                    ]);

                    $newData = ['rate' => $amount];
                    break;

                default:
                    \Log::error("Invalid item type", ['type' => $type]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid item type.'
                    ], 400);
            }

            // Audit trail
            UserAuditTrail::create([
                'user_id'           => auth()->id(),
                'username'          => auth()->user()->username ?? null,
                'user_role'         => auth()->user()->role ?? null,
                'action'            => 'update',
                'module'            => 'Billing',
                'affected_table'    => $affectedTable,
                'affected_record_id'=> $id,
                'patient_id'        => $patientId,
                'patient_name'      => $patientName,
                'description'       => "Updated billing item (type: $type)".($medicineName ? " - $medicineName" : ""),
                'old_data'          => $oldData,
                'new_data'          => $newData ?? [],
                'amount_involved'   => $amountInvolved,
                'ip_address'        => $request->ip(),
                'user_agent'        => $request->userAgent(),
            ]);

            // Send notification to patient
            if ($patientId) {
                notifications_latest::create([
                    'type' => 'Billing Update',
                    'sendTo_id' => $patientId,
                    'from_name' => 'Billing Department',
                    'read' => '0',
                    'message' => "Your billing item has been updated: " . ($medicineName ?? 'Item') . " - Amount: ₱" . number_format($amount, 2),
                    'sendTouser_type' => 'patient',
                ]);
            }
            
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Billing item updated successfully',
                'data' => [
                    'amount' => $amount,
                    'status' => $status,
                    'medicine_name' => $medicineName,
                    'type' => $type
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('UpdateBillingItem Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a billing item (service, pharmacy, or bill item)
     * Logs the delete in the audit trail.
     */
    public function deleteBillingItem(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:bill_item,service_assignment,pharmacy_item'
        ]);

        // Check if the patient is finished
        $type = $request->get('type');
        $patientId = null;

        switch ($type) {
            case 'bill_item':
                $item = DB::table('bill_items')->where('billing_item_id', $id)->first();
                $patientId = $item->patient_id ?? null;
                break;

            case 'service_assignment':
                $item = ServiceAssignment::where('assignment_id', $id)->first();
                $patientId = $item->patient_id ?? null;
                break;

            case 'pharmacy_item':
                $item = PharmacyChargeItem::find($id);
                $patientId = $item->charge?->patient_id ?? null;
                break;
        }

        $patient = Patient::find($patientId);

        if ($patient && $patient->medication_finished) {
            return response()->json([
                'success' => false,
                'message' => 'This patient has been marked as finished. Deletions of charges are not allowed.'
            ], 403);
        }

        try {
            $type = $request->get('type');
            $oldData = null;
            $patientId = null;
            $affectedTable = null;
            $patientName = null;
            $amountInvolved = null;
            $medicineName = null;

            switch ($type) {
                case 'bill_item':
                    $item = DB::table('bill_items')->where('billing_item_id', $id)->first();
                    $oldData = (array)$item;
                    $affectedTable = 'bill_items';
                    $patientId = $item->patient_id ?? null;
                    $amountInvolved = $item->amount ?? null;
                    // Try to get item name if available
                    $medicineName = $item->description ?? null;
                    if ($patientId) {
                        $patient = \App\Models\Patient::find($patientId);
                        $patientName = $patient ? trim($patient->patient_first_name . ' ' . $patient->patient_last_name) : null;
                    }
                    $deleted = DB::table('bill_items')->where('billing_item_id', $id)->delete();
                    if (!$deleted) throw new \Exception('Bill item not found');
                    break;

                case 'service_assignment':
                    $item = ServiceAssignment::where('assignment_id', $id)->first();
                    if (!$item) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Service assignment not found'
                        ], 404);
                    }
                    $oldData = $item->toArray();
                    $affectedTable = 'service_assignments';
                    $patientId = $item->patient_id ?? null;
                    $amountInvolved = $item->amount ?? null;
                    $medicineName = $item->service?->service_name ?? null;
                    if ($patientId) {
                        $patient = \App\Models\Patient::find($patientId);
                        $patientName = $patient ? trim($patient->patient_first_name . ' ' . $patient->patient_last_name) : null;
                    }
                    $item->delete();
                    break;
                case 'pharmacy_item':
                    $item = PharmacyChargeItem::with('service')->find($id);
                    if (!$item) throw new \Exception('Pharmacy item not found');
                    $oldData = $item->toArray();
                    $affectedTable = 'pharmacy_charge_items';
                    $patientId = $item->charge?->patient_id ?? null;
                    $amountInvolved = $item->total ?? null;
                    $medicineName = $item->service?->service_name ?? null;
                    if ($patientId) {
                        $patient = Patient::find($patientId);
                        $patientName = $patient ? trim($patient->patient_first_name . ' ' . $patient->patient_last_name) : null;
                    }
                    $item->delete();
                    break;

                default:
                    throw new \Exception('Cannot delete this item type');
            }

            // Audit trail
            UserAuditTrail::create([
                'user_id'           => auth()->id(),
                'username'          => auth()->user()->username ?? null,
                'user_role'         => auth()->user()->role ?? null,
                'action'            => 'delete',
                'module'            => 'Billing',
                'affected_table'    => $affectedTable,
                'affected_record_id'=> $id,
                'patient_id'        => $patientId,
                'patient_name'      => $patientName,
                'description'       => "Deleted billing item (type: $type)".($medicineName ? " - $medicineName" : ""),
                'old_data'          => $oldData,
                'new_data'          => null,
                'amount_involved'   => $amountInvolved,
                'ip_address'        => $request->ip(),
                'user_agent'        => $request->userAgent(),
            ]);

            // Send notification to patient
            if ($patientId) {
                notifications_latest::create([
                    'type' => 'Billing Delete',
                    'sendTo_id' => $patientId,
                    'from_name' => 'Billing Department',
                    'read' => '0',
                    'message' => "A billing item has been removed from your account: " . ($medicineName ?? 'Item') . " - Amount: ₱" . number_format($amountInvolved ?? 0, 2),
                    'sendTouser_type' => 'patient',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Billing item deleted successfully',
                'amount_involved' => $amountInvolved,
                'medicine_name' => $medicineName,
                'type' => $type
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate and print billing statement for a patient
     */
    public function printStatement(Patient $patient)
    {
        // Load related data for the patient
        $admission = AdmissionDetail::where('patient_id', $patient->patient_id)
            ->latest('admission_date')
            ->first();

        $deposits = Deposit::where('patient_id', $patient->patient_id)->get();

        $bills = Bill::with('items.service.department')
            ->where('patient_id', $patient->patient_id)
            ->get();

        $pharmacyCharges = PharmacyCharge::with('items.service')
            ->where('patient_id', $patient->patient_id)
            ->get();

        $serviceAssignments = ServiceAssignment::with('service.department')
            ->where('patient_id', $patient->patient_id)
            ->get();

        // Calculate all fees for this patient
        $labFee = $serviceAssignments
            ->filter(function($item) {
                return $item->mode === 'lab' && 
                    ($item->service_status === 'completed' || $item->service_status === 'disputed');
            })
            ->sum('amount');

        $orFee = $serviceAssignments
            ->filter(function($item) {
                return ($item->mode === 'or' || $item->mode === 'operating_room') && 
                    ($item->service_status === 'completed' || $item->service_status === 'disputed');
            })
            ->sum('amount');

        $pharmacyFee = 0;
        foreach($pharmacyCharges as $charge) {
            $pharmacyFee += $charge->items->where('status', 'dispensed')->sum('total');
        }

        $billItemsFee = $bills->sum(fn($bill) => $bill->items->sum('amount'));
        $doctorFee = optional($admission?->doctor)->rate ?? 0;
        $roomFee = optional(optional($admission)->room)->rate ?? 0;

        // Calculate grand total and balance
        $grandTotal = $billItemsFee + $labFee + $orFee + $pharmacyFee + $doctorFee + $roomFee;
        $totalPaid = $deposits->sum('amount');
        $balance = $grandTotal - $totalPaid;

        return view('billing.records.print', compact(
            'patient',
            'admission',
            'deposits',
            'bills',
            'pharmacyCharges',
            'serviceAssignments',
            'doctorFee',
            'roomFee',
            'labFee',
            'orFee',
            'pharmacyFee',
            'grandTotal',
            'totalPaid',
            'balance'
        ));
    }
}