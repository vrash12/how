<?php
// app/Http/Controllers/Billing/DepositController.php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\notifications_latest;
use App\Models\Patient;                      // ← make sure to import
use App\Notifications\DepositReceived;       // ← import your new notification
use App\Models\UserAuditTrail;
use App\Helpers\UserAuditHelper;             // ← Add this import for audit logging

class DepositController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','role:billing']);
    }

    public function create()
    {
        return view('billing.deposit.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'   => 'required|exists:patients,patient_id',
            'amount'       => 'required|numeric|min:0',
            'deposited_at' => 'required|date',
        ]);

        // 1) Create the deposit
        $deposit = Deposit::create([
            'patient_id'   => $request->patient_id,
            'amount'       => $request->amount,
            'deposited_at' => $request->deposited_at,
        ]);

        // 2) Notify the patient
        $patient = Patient::where('patient_id', $request->patient_id)->first();
        if ($patient) {
            $patient->notify(new DepositReceived($deposit));
        }

        $notification = notifications_latest::create([
            'type' => 'Deposit',              // example type
            'sendTo_id' => $request->patient_id,              // ID of the recipient
            'from_name' => 'Biller',        // sender name
            'read' => '0',                 // mark as unread
            'message' => 'Your deposit of ₱'.$request->amount.' has been posted.',
            'sendTouser_type' => 'patient',
        ]);

        // Log the deposit in audit trail
        $patientName = $patient ? trim($patient->patient_first_name . ' ' . $patient->patient_last_name) : 'Unknown';
        UserAuditHelper::log('create', 'Billing', "Added deposit of ₱" . number_format($request->amount, 2), [
            'affected_table' => 'deposits',
            'affected_record_id' => $deposit->id,
            'patient_id' => $request->patient_id,
            'patient_name' => $patientName,
            'amount_involved' => $request->amount,
            'new_data' => $deposit->toArray()
        ]);

        // 3) Redirect back
        return back()->with('success','Deposit has been recorded and patient notified.');
    }

    public function destroy(Request $request, $id)
    {
        try {
            $deposit = Deposit::findOrFail($id);
            $patientId = $deposit->patient_id;
            $amount = $deposit->amount;
            
            // Get patient info for audit trail
            $patient = Patient::find($patientId);
            $patientName = $patient ? trim($patient->patient_first_name . ' ' . $patient->patient_last_name) : null;
            
            // Store old data for audit
            $oldData = $deposit->toArray();
            
            // Delete the deposit
            $deposit->delete();
            
            // Notify the patient
            if ($patient) {
                notifications_latest::create([
                    'type' => 'Deposit Removed',
                    'sendTo_id' => $patientId,
                    'from_name' => 'Billing Department',
                    'read' => '0',
                    'message' => 'A deposit of ₱' . number_format($amount, 2) . ' has been removed from your account.',
                    'sendTouser_type' => 'patient',
                ]);
            }
            
            // Create audit trail
            UserAuditTrail::create([
                'user_id' => auth()->id(),
                'username' => auth()->user()->username ?? null,
                'user_role' => auth()->user()->role ?? null,
                'action' => 'delete',
                'module' => 'Billing',
                'affected_table' => 'deposits',
                'affected_record_id' => $id,
                'patient_id' => $patientId,
                'patient_name' => $patientName,
                'description' => "Deleted deposit of ₱" . number_format($amount, 2),
                'old_data' => $oldData,
                'new_data' => null,
                'amount_involved' => $amount,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return back()->with('success', 'Deposit deleted successfully and patient notified.');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete deposit: ' . $e->getMessage());
        }
    }
}
