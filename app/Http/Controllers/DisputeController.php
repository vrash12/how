<?php

namespace App\Http\Controllers;
use App\Models\Patient;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Models\notifications_latest;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceAssignment;
use App\Models\PharmacyChargeItem;
class DisputeController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:patient')->only(['store', 'myDisputes']);
        $this->middleware('role:billing')->only(['queue', 'show', 'update']); // 'index' was redundant
    }

    public function cancel(Request $request)
    {
        Dispute::where('dispute_id', $request->disputeID)->delete();
       return redirect()
            ->route('patient.disputes.mine')
            ->with('success', 'Cancelled Successfully!');
    }

        public function change(Request $request)
            {
                $disputeID       = $request->input('disputeID');
                $disputableType  = $request->input('disputable_type');
                $disputableID  = $request->input('disputableID');
                $what            = $request->input('what');

                $approveAmount            = $request->input('approvedAmountInput');

                $approvedTextInput            = $request->input('approvedTextInput');

                $oldAmount            = $request->input('oldAmount');
                $totalAmount = $oldAmount - $approveAmount;
                if($what == 'approved'){
                    if($disputableType == "Pharmacy"){
                        PharmacyChargeItem::where('id', $disputableID)
                        ->update(['status' => 'disputed', 'total' => $totalAmount ]);
                    }
                    else if($disputableType == "Operating room" || $disputableType == "Laboratory"){
                        ServiceAssignment::where('assignment_id', $disputableID)
                        ->update(['service_status' => 'disputed', 'amount' => $totalAmount ]);
                    }

                     Dispute::where('dispute_id', $disputeID)
                    ->update(['status' => $what, 'FinalAmount' => $approveAmount, 'messageFromBiller' => $approvedTextInput]);

                    $disputename =  Dispute::where('dispute_id', $disputeID)->first();
                    $notification = notifications_latest::create([
                        'type' => 'Dispute',              // example type
                        'sendTo_id' => $disputename->patient_id,              // ID of the recipient
                        'from_name' => 'Biller',        // sender name
                        'read' => '0',                 // mark as unread
                        'message' => 'Your dispute request for <b>'.$disputename->desc.'</b> has been approved.',
                        'sendTouser_type' => 'patient',
                    ]);
                }
                else{
                    Dispute::where('dispute_id', $disputeID)
                    ->update(['status' => $what, 'FinalAmount' => $approveAmount, 'messageFromBiller' => $approvedTextInput ]);
                    $disputename =  Dispute::where('dispute_id', $disputeID)->first();
                    $notification = notifications_latest::create([
                        'type' => 'Dispute',              // example type
                        'sendTo_id' => $disputename->patient_id,              // ID of the recipient
                        'from_name' => 'Biller',        // sender name
                        'read' => '0',                 // mark as unread
                        'message' => 'Your dispute request for <b>'.$disputename->desc.'</b> has been rejected.',
                        'sendTouser_type' => 'patient',
                    ]);
                }
                

                return back()
                    ->with('success', 'Updated Successfully!');
            }
    /**
     * For Billing Staff: Shows a queue of disputes.
     */
    public function queue(Request $request)
    {
        $disputess = Dispute::all();
        $disputes = [];

        foreach ($disputess as $dispute) {
            $patient = Patient::where('patient_id', $dispute->patient_id)->first();

            if ($patient) {
                $name = $patient->patient_first_name . " " . $patient->patient_last_name;

                // Add dispute data with fullname
                $disputes[] = [
                    'id'        => $dispute->dispute_id,
                    'patient_id'        => $dispute->patient_id,
                    'reason'    => $dispute->reason,
                    'status'    => $dispute->status,
                    'disputableID' => $dispute->disputable_id,
                    'datetime'  => $dispute->datetime,
                    'fullname'  => $name,
                    'disputable_type'  => $dispute->disputable_type,
                    'desc'      => $dispute->desc,
                    'file'      => $dispute->files,
                    'oldAmount'      => $dispute->oldAmount,

                    'messageFromBiller'      => $dispute->messageFromBiller,
                    'finalAmount'      => $dispute->FinalAmount,

                    'additional'      => $dispute->additional,
                ];
            }
        }


        return view('billing.dispute.queue', compact('disputes'));
    }

    /**
     * For Patients: Stores a new dispute request.
     */
    public function store(Request $request)
    {
       


            // Validate form input
        $validated = $request->validate([
            'idDispute'   => 'required|integer',
            'type'        => 'required|string',
            'reason'      => 'required|string|max:255',
            'details'     => 'nullable|string',
            'desc'     => 'nullable|string',
             'oldAmount'     => 'nullable|string',
        ]);

        // Handle file upload if present
        $filePath = '';
        if ($request->hasFile('document')) {
            $filePath = $request->file('document')->store('disputes', 'public');
        }

        $dispute = Dispute::create([
            'disputable_id'   => $validated['idDispute'],
             'desc'   => $validated['desc'],
            'disputable_type' => $validated['type'],
            
            'patient_id'      => Auth::user()->patient_id,
            'datetime'        => now(),
            'reason'          => $validated['reason'],
            'status'          => 'pending',
            'additional'      => $validated['details'] ?? '', 
            'files'           => $filePath,

            'oldAmount'           => $validated['oldAmount'],
            

            'approved_by'     => null,
        ]);


        return redirect()
            ->route('patient.disputes.mine')
            ->with('success', 'Your dispute has been submitted. You will be notified once reviewed. Cancellations are allowed within 30 minutes.');
    }

    public function myDisputes()
    {
        $disputes = Dispute::where('patient_id', auth()->user()->patient_id)->get();
    
        return view('patient.disputes.index', compact('disputes'));
    }

    public function show(Dispute $dispute)
    {
        // 1. Load main dispute data and patient info with their latest admission details
        $dispute->load(['disputable.service.department']);
        $patient = $dispute->patient()->with('admissionDetail')->first();
        $disputed_charge = $dispute->disputable;
    
        // 2. Fetch ALL charges for this patient to display in the transaction history
        // Note: This logic can be moved to a dedicated service class later for cleanliness
        $bill_items = \App\Models\BillItem::whereHas('bill', fn($q) => $q->where('patient_id', $patient->patient_id))
            ->with('service.department')->get();
        
        // In a real-world scenario, you would also merge ServiceAssignments and PharmacyCharges here
        // For now, we will display the bill_items as the main transaction history.
        $all_charges = $bill_items;
    
        // 3. Calculate totals from the fetched charges
        $totalCharges = $all_charges->sum(function($item) {
            return $item->amount - ($item->discount_amount ?? 0);
        });
        $totalDeposits = \App\Models\Deposit::where('patient_id', $patient->patient_id)->sum('amount');
        $balance = $totalCharges - $totalDeposits;
    
        // 4. Get services for the "Manual Charge" modal
        $services = \App\Models\HospitalService::orderBy('service_type')->get();
    
        // 5. Pass all data to the view
        return view('billing.show', compact(
            'dispute',
            'patient',
            'disputed_charge',
            'all_charges',
            'totalCharges',
            'totalDeposits',
            'balance',
            'services'
        ));
    }
    /**
     * For Billing Staff: Approves or rejects a dispute.
     */
    public function update(Request $request, Dispute $dispute)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes'  => 'nullable|string|max:2000',
        ]);
        
        // ✅ FIX: Use the polymorphic relationship to get the disputed item.
        $disputedItem = $dispute->disputable;

        // Ensure the item is a model that has 'amount' and 'discount_amount' fields before updating.
        if (!($disputedItem instanceof \App\Models\BillItem)) {
             // For now, we can only auto-adjust BillItems.
             // You could add logic here for other types if needed.
            return back()->with('error', 'Cannot automatically adjust this charge type.');
        }

        DB::transaction(function () use ($request, $dispute, $disputedItem) {
            $dispute->update([
                'status'      => $request->action === 'approve' ? 'approved' : 'rejected',
                'approved_by' => auth()->id(),
            ]);

            if ($request->action === 'approve') {
                $disputedItem->update([
                    'discount_amount' => $disputedItem->amount,
                    'notes' => $request->notes ?? $disputedItem->notes,
                ]);
            }
        });

        $dispute->patient->notify(new \App\Notifications\DisputeResolved($dispute));

        // Redirect to the main queue for billing staff
        return redirect()
            ->route('billing.dispute.queue')
            ->with('success', 'Dispute processed.');
    }
}