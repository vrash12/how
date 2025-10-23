<?php

namespace App\Http\Controllers;

use App\Models\BillItem;
use App\Models\Patient;
use App\Models\Dispute;
use App\Models\ServiceAssignment;
use App\Models\AdmissionDetail;
use App\Traits\BillingCalculationTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\PharmacyCharge;
use App\Models\PharmacyChargeItem;
use App\Models\Doctor;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

class BillingDashboardController extends Controller
{
    use BillingCalculationTrait;

    public function __construct()
    {
        $this->middleware(['auth','role:billing']);
    }

    public function index()
    {
        // Initialize metric variables
        $totalRevenue = 0;
        $outstandingBalance = 0;

        // Get all patients
        $allPatients = Patient::all();
        $activePatients = Patient::where('status', '!=', 'finished')->get();
        $activePatientCount = $activePatients->count();

        // Calculate totals using BillingCalculationTrait for proper calculations
        foreach ($allPatients as $patient) {
            // Use the trait to calculate proper totals
            $billingData = $this->calculatePatientTotals($patient);
            
            // Add to total revenue
            $totalRevenue += $billingData['grandTotal'];
            
            // Only add to outstanding balance if patient is not discharged/finished
            if ($patient->status != 'finished' && $patient->status != 'discharged') {
                $outstandingBalance += $billingData['balance'];
            }
        }

        // Log the totals for debugging
        Log::info('Dashboard Billing Calculations', [
            'total_revenue' => $totalRevenue,
            'outstanding_balance' => $outstandingBalance,
            'active_patients' => $activePatientCount
        ]);

        $pendingDisputes = Dispute::where('status','pending')->count();

        // Pharmacy Charges - Latest 6 completed charges
        $pharmacyCharges = PharmacyCharge::with(['patient', 'items.service'])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->map(function($charge) {
                // Debug the charge and its items
                Log::info('Pharmacy Charge', [
                    'charge_id' => $charge->id,
                    'items_count' => $charge->items->count(),
                    'has_items' => $charge->items->isNotEmpty()
                ]);
                
                // Calculate the total correctly
                $total = 0;
                foreach ($charge->items as $item) {
                    // Debug each item
                    Log::info('Pharmacy Item', [
                        'item_id' => $item->id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total_field' => $item->total,
                        'calculated_total' => ($item->quantity * $item->price)
                    ]);
                    
                    // Use price * quantity if total field is empty or zero
                    $itemTotal = $item->total > 0 ? $item->total : ($item->quantity * $item->price);
                    $total += $itemTotal;
                }
                
                return [
                    'patient_first_name' => $charge->patient->patient_first_name ?? 'N/A',
                    'patient_last_name' => $charge->patient->patient_last_name ?? '',
                    'medication_name' => $charge->items->first()->service->service_name ?? 'Multiple medications',
                    'dispensed_quantity' => $charge->items->sum('quantity') ?? 0,
                    'date' => $charge->created_at,
                    'status' => $charge->status,
                    'total' => $total  // Use our manually calculated total
                ];
            });
    
        // Calculate total pharmacy charges (only dispensed/disputed)
        $pharmacyTotal = PharmacyChargeItem::whereIn('status', ['dispensed', 'disputed'])
            ->sum('total');
        
        // OR Charges - Latest 10 completed or disputed
        $orCharges = ServiceAssignment::where('mode', 'or')
            ->whereIn('service_status', ['completed', 'disputed'])
            ->with(['patient', 'doctor', 'service'])
            ->orderBy('assignment_id', 'desc')
            ->take(10)
            ->get()
            ->map(function($item) {
                return [
                    'patient_first_name' => $item->patient->patient_first_name ?? 'N/A',
                    'patient_last_name' => $item->patient->patient_last_name ?? '',
                    'procedure_name' => $item->service->service_name ?? 'Procedure',
                    'doctor_name' => $item->doctor->doctor_name ?? 'N/A',
                    'date' => $item->created_at ?? now(),
                    'status' => $item->service_status ?? 'pending',
                    'amount' => $item->amount ?? 0
                ];
            });
    
        // Calculate total OR charges (completed/disputed)
        $orTotal = ServiceAssignment::where('mode', 'or')
            ->whereIn('service_status', ['completed', 'disputed'])
            ->sum('amount');
        
        // Laboratory Charges - Latest 10 completed or disputed
        $labCharges = ServiceAssignment::where('mode', 'lab')
            ->whereIn('service_status', ['completed', 'disputed'])
            ->with(['patient', 'doctor', 'service'])
            ->orderBy('assignment_id', 'desc')
            ->take(10)
            ->get()
            ->map(function($item) {
                return [
                    'patient_first_name' => $item->patient->patient_first_name ?? 'N/A',
                    'patient_last_name' => $item->patient->patient_last_name ?? '',
                    'test_name' => $item->service->service_name ?? 'Test',
                    'doctor_name' => $item->doctor->doctor_name ?? 'N/A',
                    'date' => $item->created_at ?? now(),
                    'status' => $item->service_status ?? 'pending',
                    'amount' => $item->amount ?? 0
                ];
            });
    
        // Calculate total Lab charges (completed/disputed)
        $labTotal = ServiceAssignment::where('mode', 'lab')
            ->whereIn('service_status', ['completed', 'disputed'])
            ->sum('amount');

        return view('billing.dashboard', [
            'totalRevenue'             => $totalRevenue,
            'outstandingBalance'       => $outstandingBalance,
            'activePatientCount'       => $activePatientCount,
            'pendingDisputes'          => $pendingDisputes,
            'pharmacyCharges'          => $pharmacyCharges,
            'pharmacyTotal'            => $pharmacyTotal,
            'orCharges'                => $orCharges,
            'orTotal'                  => $orTotal,
            'labCharges'               => $labCharges,
            'labTotal'                 => $labTotal,
        ]);
    }

    public function print(Patient $patient)
    {
        // Get the patient's latest admission
        $admission = $patient->admissionDetail()->latest('admission_date')->first();
        $admissionId = $admission?->admission_id;
        
        // Use the trait to calculate all billing totals
        $billingData = $this->calculatePatientTotals($patient, $admissionId);
        
        // Get bill items
        $all_charges = BillItem::whereHas('bill', fn($q) => $q->where('patient_id', $patient->patient_id))
            ->with('service')
            ->get();
            
        // Get pharmacy charges
        $pharmacy_charges = PharmacyChargeItem::whereHas('charge', function($q) use ($patient) {
                $q->where('patient_id', $patient->patient_id);
            })
            ->where(function($query) {
                $query->where('status', 'dispensed')
                      ->orWhere('status', 'disputed');
            })
            ->with('service')
            ->get();
            
        // Get service assignments
        $service_assignments = ServiceAssignment::where('patient_id', $patient->patient_id)
            ->whereIn('service_status', ['completed', 'disputed'])
            ->with(['service', 'doctor'])
            ->get();
        
        $data = [
            'patient'         => $patient,
            'admission'       => $admission,
            'all_charges'     => $all_charges,
            'pharmacy_charges' => $pharmacy_charges,
            'service_assignments' => $service_assignments,
            'totals'          => [
                'grandTotal'  => $billingData['grandTotal'],
                'balance'     => $billingData['balance'],
                'paymentsMade' => $billingData['paymentsMade'],
                'bedRate'      => $billingData['bedRate'],
                'doctorFee'    => $billingData['doctorFee'],
                'labFee'       => $billingData['labFee'],
                'orFee'        => $billingData['orFee'],
                'pharmacyTotal' => $billingData['rxTotal'],
                'daysAdmitted' => $billingData['daysAdmitted'],
            ],
            'deposits'        => $billingData['depositArray'],
        ];

        // Load the view and generate the PDF
        $pdf = Pdf::loadView('billing.pdf.statement', $data);

        // Stream the PDF to the browser
        return $pdf->stream('SOA-' . $patient->patient_id . '-' . now()->format('Ymd') . '.pdf');
    }

    // Close out a patient's billing
    public function lock(Request $request, Patient $patient)
    {
        // Mark the billing as closed and record the time
        $patient->billing_locked = true;
        $patient->billing_closed_at = now();
        $patient->save();

        return back()->with('success','Billing locked and finalized.');
    }
    
    // Generate billing report for a specific date range
    public function report(Request $request)
    {
        $startDate = $request->input('start_date') ? 
            \Carbon\Carbon::parse($request->input('start_date')) : 
            now()->subDays(30);
            
        $endDate = $request->input('end_date') ? 
            \Carbon\Carbon::parse($request->input('end_date')) : 
            now();
            
        // Get all completed bills in the date range
        $completedBills = DB::table('bills')
            ->join('bill_items', 'bills.billing_id', '=', 'bill_items.billing_id')
            ->join('patients', 'bills.patient_id', '=', 'patients.patient_id')
            ->whereBetween('bills.billing_date', [$startDate, $endDate])
            ->where('bills.payment_status', 'paid')
            ->select(
                'bills.billing_id',
                'bills.billing_date',
                'patients.patient_id',
                'patients.patient_first_name',
                'patients.patient_last_name',
                DB::raw('SUM(bill_items.amount - COALESCE(bill_items.discount_amount, 0)) as total_amount')
            )
            ->groupBy('bills.billing_id', 'bills.billing_date', 'patients.patient_id', 'patients.patient_first_name', 'patients.patient_last_name')
            ->get();
            
        $totalBilled = $completedBills->sum('total_amount');
        
        return view('billing.reports.billing-report', [
            'completedBills' => $completedBills,
            'totalBilled' => $totalBilled,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
}
