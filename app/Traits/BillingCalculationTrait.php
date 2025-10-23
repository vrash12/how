<?php

namespace App\Traits;

use App\Models\AdmissionDetail;
use App\Models\Deposit;
use App\Models\ServiceAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait BillingCalculationTrait
{
    /**
     * Calculate all billing totals for a patient
     * 
     * @param \App\Models\Patient $patient
     * @return array Associative array of billing calculations
     */
    protected function calculatePatientTotals($patient)
    {
        $patient_id = $patient->patient_id;

        // Get the latest admission
        $admissionDetail = AdmissionDetail::where('patient_id', $patient_id)
            ->latest('admission_date')
            ->first();

        $admissionId = $admissionDetail?->admission_id;
        
        // Get admission date and calculate days - use billing_closed_at for finished patients
        $admissionDate = $admissionDetail?->admission_date ?? now();
        $dischargeDate = ($patient->status === 'discharged' || $patient->status === 'finished') 
            ? ($patient->billing_closed_at ?? now())
            : now();
            
        // Log the dates for debugging
        Log::info('Billing Date Calculation', [
            'patient_id' => $patient_id,
            'patient_status' => $patient->status,
            'admission_date' => $admissionDate,
            'discharge_date' => $dischargeDate,
            'billing_closed_at' => $patient->billing_closed_at,
        ]);
            
        $daysAdmitted = max(1, $admissionDate->diffInDays($dischargeDate) + 1); // +1 to include first day

        // Get all admissions
        $admissions = AdmissionDetail::where('patient_id', $patient_id)
            ->orderByDesc('admission_date')
            ->get();
            
        // Get admission with doctor relationship
        $admission = AdmissionDetail::with('doctor')->find($admissionId);

        // Calculate bill items total
        $billTotal = DB::table('bill_items as bi')
            ->join('bills as b', 'bi.billing_id', '=', 'b.billing_id')
            ->where('b.patient_id', $patient_id)
            ->when($admissionId, fn($q) => $q->where('b.admission_id', $admissionId))
            ->sum(DB::raw('bi.amount - COALESCE(bi.discount_amount, 0)'));

        // Pharmacy totals - only dispensed and disputed items
        $rxTotal = DB::table('pharmacy_charge_items as pci')
            ->join('pharmacy_charges as pc', 'pc.id', '=', 'pci.charge_id')
            ->where('pc.patient_id', $patient_id)
            ->where(function ($query) {
                $query->where('pci.status', 'dispensed')
                    ->orWhere('pci.status', 'disputed');
            })
            ->sum('pci.total');

        // Pending pharmacy items (for reference only, not included in total)
        $rxPendingTotal = DB::table('pharmacy_charge_items as pci')
            ->join('pharmacy_charges as pc', 'pc.id', '=', 'pci.charge_id')
            ->where('pc.patient_id', $patient_id)
            ->where('pci.status', 'pending')
            ->sum('pci.total');

        // Get bed daily rate - use the patient's assigned bed
        $bedDailyRate = DB::table('beds as b')
            ->join('rooms as r', 'r.room_id', '=', 'b.room_id')
            ->where('b.patient_id', $patient_id)
            ->where('b.status', 'occupied')
            ->orderByDesc('b.updated_at')
            ->select(DB::raw('COALESCE(NULLIF(b.rate, 0), r.rate, 0) as rate'))
            ->value('rate') ?? 0;

        // Get doctor's daily fee
        $doctorDailyRate = optional($admission?->doctor)->rate ?? 0;

        // Calculate total fees based on days admitted
        $doctorFee = $doctorDailyRate * $daysAdmitted;
        $bedRate = $bedDailyRate * $daysAdmitted;

        // Get all deposits
        $depositArray = Deposit::where('patient_id', $patient_id)->get();
        $paymentsMade = $depositArray->sum('amount');

        // Get all service assignments
        $labAssignments = ServiceAssignment::where('patient_id', $patient_id)->get();

        // Calculate lab fees (completed or disputed)
        $labFee = $labAssignments
            ->filter(function ($item) {
                return $item->mode === 'lab' &&
                    ($item->service_status === 'completed' || $item->service_status === 'disputed');
            })
            ->sum('amount');

        // Calculate OR fees (completed or disputed)
        $orFee = $labAssignments
            ->filter(function ($item) {
                return ($item->mode === 'or' || $item->mode === 'operating_room') &&
                    ($item->service_status === 'completed' || $item->service_status === 'disputed');
            })
            ->sum('amount');

        // Calculate grand total
        $grandTotal = $orFee + $billTotal + $rxTotal + $bedRate + $doctorFee + $labFee;
        $balance = $grandTotal - $paymentsMade;
        if ($balance < 0) {
            $balance = 0; // Prevent negative balance
        }

        // Log calculation breakdown for debugging
        Log::info('Billing Total Calculation', [
            'patient_id' => $patient->patient_id,
            'days_admitted' => $daysAdmitted,
            'bill_total' => $billTotal,
            'pharmacy_total' => $rxTotal,
            'bed_daily_rate' => $bedDailyRate,
            'bed_rate' => $bedRate,
            'doctor_daily_rate' => $doctorDailyRate,
            'doctor_fee' => $doctorFee,
            'lab_fee' => $labFee,
            'or_fee' => $orFee,
            'grand_total' => $grandTotal,
            'payments_made' => $paymentsMade,
            'balance' => $balance
        ]);

        return [
            'grandTotal' => $grandTotal,
            'balance' => $balance,
            'billTotal' => $billTotal,
            'rxTotal' => $rxTotal,
            'rxPendingTotal' => $rxPendingTotal,
            'bedRate' => $bedRate,
            'bedDailyRate' => $bedDailyRate,
            'doctorFee' => $doctorFee,
            'doctorDailyRate' => $doctorDailyRate,
            'labFee' => $labFee,
            'orFee' => $orFee,
            'paymentsMade' => $paymentsMade,
            'depositArray' => $depositArray,
            'admission' => $admission,
            'admissions' => $admissions,
            'admissionId' => $admissionId,
            'daysAdmitted' => $daysAdmitted,
        ];
    }
}