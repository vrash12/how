<?php
//app/Http/Controllers/PharmacyController.php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\HospitalService;
use App\Models\PharmacyCharge;
use App\Models\PharmacyChargeItem;
use App\Models\notifications_latest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\UserAuditHelper;

class PharmacyController extends Controller
{
    // Restrict access to pharmacy role
    public function __construct()
    {
        $this->middleware(['auth','role:pharmacy']);
    }

    /**
     * Show pharmacy dashboard with stats and recent charges.
     */
    public function index(Request $request)
    {
        // Fetch all charges created today
        $todayCharges = PharmacyCharge::with(['patient', 'items.service'])
            ->whereDate('created_at', now()->toDateString())
            ->orderByDesc('created_at')
            ->get();

        // Fetch all dispensed items for today (filter by charge date instead of item date)
        $dispensedToday = PharmacyChargeItem::with(['charge.patient', 'service'])
            ->where('status', 'dispensed')
            ->whereHas('charge', function($query) {
                $query->whereDate('created_at', now()->toDateString());
            })
            ->get();

        // Fetch pending charges for today (exclude finished patients)
        $pendingToday = PharmacyCharge::with(['patient', 'items.service'])
            ->where('status', 'pending')
            ->whereDate('created_at', now()->toDateString())
            ->whereHas('patient', function($query) {
                // Only include patients who are NOT marked as finished
                $query->where(function($q) {
                    $q->where('medication_finished', 0)
                      ->orWhereNull('medication_finished');
                });
            })
            ->orderByDesc('created_at')
            ->get();

        // Calculate stats for the dashboard
        $totalProceduresToday = $todayCharges->count();
        $patientsOperated = $todayCharges->where('status', 'completed')->count();
        $pendingProcedures = $pendingToday->count();

        return view('pharmacy.dashboard', compact(
            'todayCharges',
            'dispensedToday',
            'pendingToday',
            'totalProceduresToday',
            'patientsOperated',
            'pendingProcedures'
        ));
    }

    /**
     * Show the over-the-counter sales interface
     */
    public function otcSales()
    {
        $medications = HospitalService::medications()
            ->withoutPrescription()
            ->orderBy('service_name')
            ->get();
        
        $patients = Patient::where('status', 'active')
            ->orderBy('patient_last_name')
            ->get();
            
        return view('pharmacy.otc', compact('medications', 'patients'));
    }
    
    /**
     * Create a new OTC pharmacy charge without prescription and mark as completed immediately
     */
    public function createOtcCharge(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'medications' => 'required|array|min:1',
            'medications.*.service_id' => 'required|exists:hospital_services,service_id',
            'medications.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);
        
        // Check if all medications are actually OTC
        $medicationIds = array_column($validated['medications'], 'service_id');
        $otcCount = HospitalService::whereIn('service_id', $medicationIds)
            ->where('needs_prescription', false)
            ->count();
            
        if ($otcCount != count($medicationIds)) {
            return back()->with('error', 'Some selected items require prescription!');
        }
        
        try {
            DB::beginTransaction();
            
            // Create the main charge
            $charge = PharmacyCharge::create([
                'patient_id' => $validated['patient_id'],
                'prescribing_doctor' => 'OTC Sale',
                'rx_number' => 'OTC-' . time(),
                'notes' => $validated['notes'] ?? null,
                'total_amount' => 0,
                'status' => 'completed', // Set status as completed directly
                'dispensed_at' => now(), // Set dispensed time now
            ]);
            
            $totalAmount = 0;
            
            // Create charge items
            foreach ($validated['medications'] as $med) {
                $service = HospitalService::findOrFail($med['service_id']);
                $quantity = (int)$med['quantity'];
                $lineTotal = $service->price * $quantity;
                $totalAmount += $lineTotal;
                
                $item = PharmacyChargeItem::create([
                    'charge_id' => $charge->id,
                    'service_id' => $service->service_id,
                    'quantity' => $quantity,
                    'dispensed_quantity' => $quantity, // Set the dispensed quantity same as quantity
                    'unit_price' => $service->price,
                    'total' => $lineTotal,
                    'status' => 'dispensed' // Mark as dispensed immediately
                ]);
                
                // Add notification for patient
                notifications_latest::create([
                    'type' => 'Dispense',
                    'sendTo_id' => $validated['patient_id'],
                    'from_name' => 'Pharmacy',
                    'read' => '0',
                    'message' => $service->service_name . " purchased (OTC): $quantity",
                    'sendTouser_type' => 'patient',
                    'idReference' => $item->id,
                    'titleReference' => 'OTC Medication',
                    'category' => 'pharmacy',
                ]);
                
                // Audit log entry for completed service
                UserAuditHelper::logComplete(
                    $validated['patient_id'],
                    $service->service_name . " (OTC Sale, Qty: $quantity)",
                    'pharmacy',
                    $item->id
                );
                
                // Additional audit trail entry specifically for OTC sales
                UserAuditHelper::log(
                    'OTC SALE',
                    'pharmacy', 
                    "OTC Medication: " . $service->service_name . " (Qty: $quantity)",
                    [
                        'affected_table' => 'pharmacy_charge_items',
                        'affected_record_id' => $item->id,
                        'patient_id' => $validated['patient_id'],
                        'amount_involved' => $lineTotal,
                    ]
                );
            }
            
            // Update total on the main charge
            $charge->total_amount = $totalAmount;
            $charge->save();
            
            DB::commit();
            
            return redirect()->route('pharmacy.otc')
                ->with('success', 'OTC medications dispensed and billed successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating OTC charge: ' . $e->getMessage());
        }
    }

    public function dispense(Request $request, PharmacyCharge $charge)
    {
        // Check if the patient is finished
        $patient = Patient::find($charge->patient_id);
        if ($patient && $patient->medication_finished) {
            return redirect()->back()->with('error', 'This patient has been marked as finished. Pending pharmacy orders cannot be processed.');
        }

        if ($charge->status === 'completed') {
            return back()->with('info', 'Already marked as dispensed.');
        }

        $itemsInput = $request->input('items', []); // ['item_id' => qty_to_dispense, ...]
        $cancelledItems = $request->input('cancel_items', []); // ['item_id', 'item_id', ...]

        foreach ($charge->items as $item) {
            // Check if item is marked for cancellation
            if (in_array($item->id, $cancelledItems)) {
                $item->status = 'cancelled';
                $item->dispensed_quantity = 0;
                $item->total = 0;
                $item->save();

                // Notification for cancelled item
                $service = HospitalService::where('service_id', $item->service_id)->first();
                notifications_latest::create([
                    'type' => 'Cancel',
                    'sendTo_id' => $charge->patient_id,
                    'from_name' => 'Pharmacy',
                    'read' => '0',
                    'message' => $service->service_name . " cancelled by pharmacy",
                    'sendTouser_type' => 'patient',
                    'idReference' => $item->id,  // ADD THIS
                    'titleReference' => 'Medication Cancelled',  // ADD THIS
                    'category' => 'pharmacy',  // ADD THIS
                ]);

                UserAuditHelper::logComplete(
                    $charge->patient_id,
                    $service->service_name . " (Cancelled)",
                    'pharmacy',
                    $item->id
                );
                continue;
            }

            // Process dispensing for non-cancelled items
            if (isset($itemsInput[$item->id])) {
                $qtyToDispense = (int)$itemsInput[$item->id];

                // Reject zero and negative quantities
                if ($qtyToDispense <= 0) {
                    return back()->with('error', 'Quantity must be greater than 0 for item: ' . $item->id);
                }

                $item->dispensed_quantity = $qtyToDispense;
                $item->status = 'dispensed';
                $item->total = ($item->unit_price ?? 0) * $qtyToDispense; // Calculate total based on dispensed quantity
                $item->save();

                // Notification and audit log
                $service = HospitalService::where('service_id', $item->service_id)->first();
                notifications_latest::create([
                    'type' => 'Dispense',
                    'sendTo_id' => $charge->patient_id,
                    'from_name' => 'Pharmacy',
                    'read' => '0',
                    'message' => $service->service_name . " dispensed: $qtyToDispense",
                    'sendTouser_type' => 'patient',
                    'idReference' => $item->id,  // ADD THIS
                    'titleReference' => 'Medication Dispensed',  // ADD THIS
                    'category' => 'pharmacy',  // ADD THIS
                ]);

                UserAuditHelper::logComplete(
                    $charge->patient_id,
                    $service->service_name . " (Qty: $qtyToDispense)",
                    'pharmacy',
                    $item->id
                );
            }
        }

        // Recalculate the total amount for the charge based on dispensed quantities only
        $charge->total_amount = $charge->items()->where('status', 'dispensed')->sum('total');
        $charge->status = 'completed';
        $charge->dispensed_at = now();
        $charge->save();

        return back()->with('success', 'Medication dispensed & flagged for billing.');
        
    }

    /**
     * Show the queue of pending approvals (requests from doctors).
     */
    public function queue()
    {
        $pendingCharges = PharmacyCharge::where('status', 'pending')
            ->with(['patient', 'items.service'])
            ->whereHas('patient', function($query) {
                // Only include patients who are NOT marked as finished
                $query->where(function($q) {
                    $q->where('medication_finished', 0)
                      ->orWhereNull('medication_finished');
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return view('pharmacy.queue', compact('pendingCharges'));
    }

    /**
     * Show modal/page to select which items to dispense for a charge.
     * Used for partial dispensing.
     */
    public function selectItems(PharmacyCharge $charge)
    {
        $charge->load(['items.service', 'patient']);
        return view('pharmacy.select-items', compact('charge'));
    }

    public function history()
    {
        $completedCharges = PharmacyCharge::where('status', 'completed')
            ->with(['patient', 'items.service'])
            ->orderByDesc('dispensed_at')
            ->get();

        return view('pharmacy.history', compact('completedCharges'));
    }

    // /**
    //  * Mark a whole charge as dispensed (all items).
    //  * Used for full dispensing, not partial.
    //  */
    // public function dispense(PharmacyCharge $charge)
    // {
    //     if ($charge->status === 'completed') {
    //         return back()->with('info','Already marked as dispensed.');
    //     }

    //     // Mark all items as dispensed
    //     foreach ($charge->items as $item) {
    //         $item->status = 'dispensed';
    //         $item->save();
    //     }

    //     $charge->update([
    //         'status'       => 'completed',
    //         'dispensed_at' => now(),
    //     ]);

    //     // Optionally notify the patient
    //     // $charge->patient->notify(new PharmacyChargeDispensed($charge));

    //     return back()->with('success','Medication dispensed & flagged for billing.');
    // }

    // /**
    //  * Show form to create a new charge (manual/walk-in).
    //  */
    // public function create()
    // {
    //     $patients = Patient::where('status','active')
    //                 ->orderBy('patient_last_name')
    //                 ->get();

    //     $services = Service::with('department')->get();

    //     return view('pharmacy.create', compact('patients','services'));
    // }

    // /**
    //  * Store a new charge (manual/walk-in).
    //  */
    // public function store(Request $request)
    // {
    //     $patient = Patient::findOrFail($request->patient_id);
    //     if ($patient->billing_closed_at) {
    //         return back()->with('error', 'Action failed: The patient\'s bill is locked.');
    //     }

    //     $data = $request->validate([
    //         'patient_id'         => 'required|exists:patients,patient_id',
    //         'prescribing_doctor' => 'required|string|max:255',
    //         'rx_number'          => 'required|string|max:100',
    //         'notes'              => 'nullable|string',
    //         'medications'        => 'required|array|min:1',
    //         'medications.*.service_id' => 'required|exists:hospital_services,service_id',
    //         'medications.*.quantity'   => 'required|integer|min:1',
    //     ]);

    //     // Transaction: create charge and items
    //     DB::transaction(function() use($data, &$charge) {
    //         $charge = PharmacyCharge::create([
    //             'patient_id'         => $data['patient_id'],
    //             'prescribing_doctor' => $data['prescribing_doctor'],
    //             'rx_number'          => $data['rx_number'],
    //             'notes'              => $data['notes'] ?? null,
    //             'total_amount'       => 0,
    //             'status'             => 'pending', // Ensure status is set
    //         ]);

    //         $grandTotal = 0;
    //         foreach ($data['medications'] as $item) {
    //             $service   = Service::findOrFail($item['service_id']);
    //             $lineTotal = $service->price * $item['quantity'];
    //             $grandTotal += $lineTotal;

    //             PharmacyChargeItem::create([
    //                 'charge_id'   => $charge->id,
    //                 'service_id'  => $service->service_id,
    //                 'quantity'    => $item['quantity'],
    //                 'unit_price'  => $service->price,
    //                 'total'       => $lineTotal,
    //                 'status'      => 'pending', // Track per-item status
    //             ]);
    //         }

    //         $charge->update(['total_amount' => $grandTotal]);
    //     });

    //     // Optionally notify the patient
    //     // $charge->patient->notify(new PharmacyChargeCreated($charge));

    //     return redirect()
    //         ->route('pharmacy.index')
    //         ->with('success', 'Medication charge created successfully.');
    // }

    // /**
    //  * Show details for a single medication charge.
    //  */
    // public function show(PharmacyCharge $charge)
    // {
    //     $charge->load([
    //         'patient',
    //         'items.service.department'
    //     ]);

    //     return view('pharmacy.show', compact('charge'));
    // }

    // /**
    //  * Show the queue of pending approvals (requests from doctors).
    //  */
    // public function queue()
    // {
    //     $pendingCharges = PharmacyCharge::where('status', 'pending')
    //         ->with(['patient', 'items.service'])
    //         ->orderBy('created_at', 'asc')
    //         ->get();

    //     return view('pharmacy.queue', compact('pendingCharges'));
    // }

    // /**
    //  * Show modal/page to select which items to dispense for a charge.
    //  * Used for partial dispensing.
    //  */
    // public function selectItems(PharmacyCharge $charge)
    // {
    //     $charge->load(['items.service', 'patient']);
    //     return view('pharmacy.select-items', compact('charge'));
    // }

    // /**
    //  * Handle partial dispensing: only selected items are dispensed.
    //  * Updates per-item status and charge status if all are dispensed.
    //  */
    // public function partialDispense(Request $request, PharmacyCharge $charge)
    // {
    //     $selected = $request->input('items', []); // array of item IDs to dispense

    //     foreach ($charge->items as $item) {
    //         if (in_array($item->id, $selected) && $item->status !== 'dispensed') {
                

    //             $item->status = 'dispensed';
    //             $item->save();
                
    //             $find = PharmacyChargeItem::where('id', $item->id)->first();
    //             $findcharge = PharmacyCharge::where('id', $item->charge_id)->first();        
    //             $services = Service::where('service_id', $find->service_id)->first();
                

    //             // Creating a new notification
    //             $notification = notifications_latest::create([
    //                 'type' => 'Dispense',              // example type
    //                 'sendTo_id' => $findcharge->patient_id,              // ID of the recipient
    //                 'from_name' => 'Pharmacy',        // sender name
    //                 'read' => '0',                 // mark as unread
    //                 'message' => $services->service_name . ' has been dispensed.',
    //                 'sendTouser_type' => 'patient',
    //                 'idReference' => $item->id,
    //                 'titleReference' => 'Charge approved',
    //                 'category' => 'pharmacy',
    //             ]);
    //         }
    //         // Do NOT set others back to pending!
    //     }
      

    //     // If all items are dispensed, mark charge as completed
    //     if ($charge->items()->where('status', 'pending')->count() == 0) {
    //         $charge->status = 'completed';
    //         $charge->dispensed_at = now();
    //         $charge->save();
    //     } else {
    //         $charge->status = 'pending';
    //         $charge->save();
    //     }

    //     return redirect()->route('pharmacy.queue')->with('success', 'Selected items dispensed and billed.');
    // }

    // public function history()
    // {
    //     $completedCharges = PharmacyCharge::where('status', 'completed')
    //         ->with(['patient', 'items.service'])
    //         ->orderByDesc('dispensed_at')
    //         ->get();

    //     $partialCharges = PharmacyCharge::where('status', 'pending')
    //         ->whereHas('items', fn($q) => $q->where('status', 'dispensed'))
    //         ->with(['patient', 'items.service'])
    //         ->orderByDesc('updated_at')
    //         ->get();

    //     return view('pharmacy.history', compact('completedCharges', 'partialCharges'));
    // }

}