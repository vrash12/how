<?php

namespace App\Http\Controllers;

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

class PatientBillingController extends Controller
{
    use BillingCalculationTrait;

    public function __construct()
    {
        $this->middleware(['auth']); // Webguard
    }

    public function index(Request $request)
    {
        // Find Logged Patient
        $patient = Auth::user()->patient ?? abort(404,'No patient profile.');
        
        // Get admissionId from request or first admission
        $admissionId = $request->input('admission_id') ?? 
            $patient->admissionDetail()->orderByDesc('admission_date')->first()?->admission_id;
        
        // Calculate patient billing totals using trait
        $billingData = $this->calculatePatientTotals($patient, $admissionId);

        $dailyBedRate = $billingData['daysAdmitted'] > 0 ? $billingData['bedRate'] / $billingData['daysAdmitted'] : 0;
        $dailyDoctorFee = $billingData['daysAdmitted'] > 0 ? $billingData['doctorFee'] / $billingData['daysAdmitted'] : 0;

        // Get itemized rows for display
        $billRows = $this->getBillRows($patient, $admissionId);
        $assignmentRows = $this->getAssignmentRows($patient);
        $rxRows = $this->getPharmacyRows($patient);
        
        // Merge and process rows
        $rows = collect()
            ->concat($billRows)
            ->concat($assignmentRows)
            ->concat($rxRows);

        // Group and collapse rows
        $groupedRows = $this->groupAndCollapseRows($rows);

        // Create paginator
        $perPage = 100000000000;
        $page = $request->input('page', 1);
        $paginator = new LengthAwarePaginator(
            $groupedRows->forPage($page, $perPage),
            $groupedRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Process disputes
        $disputeArray = Dispute::where('patient_id', $patient->patient_id)->get();
        $allDispute = [
            'Pharmacy'       => [],
            'Laboratory'     => [],
            'Operating room' => [],
        ];

        foreach ($disputeArray as $dis) {
            $allDispute[$dis->disputable_type][$dis->disputable_id] = $dis->status;
        }
        
        return view('patient.billing', [
            'allDispute'          => $allDispute,
            'admissions'          => $billingData['admissions'],
            'admissionId'         => $billingData['admissionId'],
            'items'               => $paginator,
            'totals'              => [
                'total'    => $billingData['grandTotal'],
                'balance'  => $billingData['balance'],
                'discount' => 0,
            ],
            'bedRate'             => $billingData['bedRate'],
            'doctorFee'           => $billingData['doctorFee'],
            'pharmacyTotal'       => $billingData['rxTotal'],
            'laboratoryFee'       => $billingData['labFee'],
            'ORFee'               => $billingData['orFee'],
            'pharmacyPendingTotal' => $billingData['rxPendingTotal'],
            'paymentsMade'        => $billingData['paymentsMade'],
            'daysAdmitted'        => $billingData['daysAdmitted'],
            'dailyBedRate'        => $dailyBedRate,
            'dailyDoctorFee'      => $dailyDoctorFee,
        ]);
    }

    // Helper method to get bill rows
    private function getBillRows($patient, $admissionId) {
        return Bill::with([
            'items.service.department',
            'items.logs',
        ])
        ->where('patient_id',$patient->patient_id)
        ->when($admissionId, fn($q) => $q->where('admission_id', $admissionId))
        ->get()
        ->flatMap(function ($bill) {
            return $bill->items->map(function ($it) use ($bill) {
                $timeline = $it->logs->map(fn($l) => (object)[
                    'stamp' => $l->created_at,
                    'actor' => $l->actor,
                    'dept'  => $it->service?->department?->department_name ?? '—',
                    'text'  => $l->message,
                ]);
                return (object)[
                    'billing_item_id' => $it->billing_item_id,
                    'billing_date'    => $bill->billing_date,
                    'ref_no'          => $bill->billing_id,
                    'description'     => $it->service?->service_name ?? '—',
                    'provider'        => $it->service?->department?->department_name ?? '—',
                    'amount'          => $it->amount,
                    'status'          => $it->dispute?->status ?? ($it->status ?: $bill->payment_status),
                    'timeline'        => $timeline,
                ];
            });
        });
    }

    // Helper method to get service assignment rows
    private function getAssignmentRows($patient) {
        return ServiceAssignment::with(['service.department','doctor'])
        ->where('patient_id',$patient->patient_id)
        ->get()
        ->map(function ($as) {
            $mode = ServiceAssignment::where('assignment_id', $as->assignment_id)->first();
            if($mode){
                if($mode->mode == "lab"){
                    $mode->mode = "Laboratory";
                }
                else if($mode->mode == "or"){
                    $mode->mode = "Operating room";
                }
                $idAss = $mode->assignment_id;
                $mode = $mode->mode;
            }
            $timeline = collect([
                (object)[
                    'stamp' => $as->created_at,
                    'actor' => optional($as->doctor)->doctor_name ?? '—',
                    'dept'  => optional($as->doctor->department)->department_name ?? '—',
                    'text'  => 'Ordered',
                ],
                $as->service_status === 'completed'
                    ? (object)[
                        'stamp' => $as->updated_at,
                        'actor' => optional($as->doctor)->doctor_name ?? '—',
                        'dept'  => optional($as->doctor->department)->department_name ?? '—',
                        'text'  => 'Marked completed',
                      ]
                    : null,
            ])->filter();

            return (object)[
                'idAss' => $idAss,
                'billing_item_id' => 'SA-'.$as->assignment_id,
                'billing_date'    => $as->datetime ?? $as->created_at,
                'ref_no'          => 'SA'.$as->assignment_id,
                'description'     => $as->service?->service_name ?? '—',
                'provider'        => $mode,
                'amount'          => $as->amount ?? 0,
                'status'          => $as->service_status,
                'timeline'        => $timeline,
                'type'            => 'service',
            ];
        });
    }

    // Helper method to get pharmacy rows
    private function getPharmacyRows($patient) {
        return PharmacyChargeItem::with(['service', 'charge'])
        ->whereHas('charge', function($q) use ($patient) {
            $q->where('patient_id', $patient->patient_id);
        })
        ->whereIn('status', ['dispensed','pending','disputed'])
        ->get()
        ->map(function ($it) {
            return (object)[
                'idAss' => $it->id,
                'type'  => 'lab',
                'billing_item_id' => 'RX-'.$it->id,
                'billing_date'    => $it->charge->created_at,
                'ref_no'          => $it->charge->rx_number,
                'description'     => $it->service?->service_name ?? '—',
                'provider'        => 'Pharmacy',
                'amount'          => $it->total,
                'status'          => $it->status,
                'is_rx'           => true,
                'timeline'        => collect([
                    (object)[
                        'stamp' => $it->updated_at,
                        'actor' => 'Pharmacy',
                        'dept'  => 'Pharmacy',
                        'text'  => $it->status === 'dispensed' ? 'Dispensed' : 'Pending',
                    ],
                ]),
            ];
        });
    }

    // Helper method to group and collapse rows
    private function groupAndCollapseRows($rows) {
        return $rows->groupBy(function($r) {
            if (!empty($r->is_rx)) {
                return $r->billing_item_id;
            }
            return $r->ref_no.'|'.$r->provider;
        })
        ->map(function($grp) {
            $first = $grp->first();
            $count = $grp->count();
            
            return (object)[
                'idAss' => $first->idAss,
                'type'  => $first->type ?? 'lab',
                'billing_date' => $grp->min('billing_date'),
                'ref_no'       => $first->ref_no,
                'description'  => $count === 1
                                ? $first->description
                                : "{$count} items",
                'provider'     => $first->provider,
                'amount'       => $grp->sum('amount'),
                'status'       => $grp->pluck('status')->unique()->count() === 1
                                ? $first->status
                                : 'mixed',
                'children'     => $grp->values(),
                'billing_item_id' => $first->billing_item_id,
                'is_rx'        => $first->is_rx ?? false,
            ];
        })
        ->values();
    }

    public function downloadStatement(Request $request)
    {
        $patient = Auth::user()->patient ?? abort(404,'No patient profile.');
        
        // Get admissionId from request or first admission
        $admissionId = $request->input('admission_id') ?? 
            $patient->admissionDetail()->orderByDesc('admission_date')->first()?->admission_id;
        
        // Calculate patient billing totals using trait
        $billingData = $this->calculatePatientTotals($patient, $admissionId);
        
        // Get itemized rows for display
        $billRows = $this->getBillRows($patient, $admissionId);
        $assignmentRows = $this->getAssignmentRows($patient);
        $rxRows = $this->getPharmacyRows($patient);
        
        // Merge and process rows
        $rows = collect()
            ->concat($billRows)
            ->concat($assignmentRows)
            ->concat($rxRows);

        // Group and collapse rows
        $groupedRows = $this->groupAndCollapseRows($rows);

        // Create paginator
        $perPage = 100000000000;
        $page = $request->input('page', 1);
        $paginator = new LengthAwarePaginator(
            $groupedRows->forPage($page, $perPage),
            $groupedRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Process disputes
        $disputeArray = Dispute::where('patient_id', $patient->patient_id)->get();
        $allDispute = [
            'Pharmacy'       => [],
            'Laboratory'     => [],
            'Operating room' => [],
        ];

        foreach ($disputeArray as $dis) {
            $allDispute[$dis->disputable_type][$dis->disputable_id] = $dis->status;
        }
        
        $pdf = Pdf::loadView('patient.pdf.statement', [
            'patient'             => $patient,
            'admission'           => $patient->admissionDetail()->find($admissionId),
            'allDispute'          => $allDispute,
            'admissions'          => $billingData['admissions'],
            'admissionId'         => $billingData['admissionId'],
            'items'               => $paginator,
            'totals'              => [
                'total'    => $billingData['grandTotal'],
                'balance'  => $billingData['balance'],
                'discount' => 0,
            ],
            'bedRate'             => $billingData['bedRate'],
            'doctorFee'           => $billingData['doctorFee'],
            'pharmacyTotal'       => $billingData['rxTotal'],
            'laboratoryFee'       => $billingData['labFee'],
            'ORFee'               => $billingData['orFee'],
            'pharmacyPendingTotal' => $billingData['rxPendingTotal'],
            'paymentsMade'        => $billingData['paymentsMade'],
            'daysAdmitted'        => $billingData['daysAdmitted'],
        ])->setPaper('a4', 'portrait');

        // Download with a filename
        $filename = 'statement_adm'.$admissionId.'_'.now()->format('Ymd').'.pdf';
        return $pdf->download($filename);
    }

    public function printReceipt(Request $request, $patient_id)
    {
        // Find patient by patient_id
        $patient = Patient::findOrFail($patient_id);
        
        // Get admissionId from request or first admission
        $admissionId = $request->input('admission_id') ?? 
            $patient->admissionDetail()->orderByDesc('admission_date')->first()?->admission_id;
        
        // Calculate patient billing totals using trait
        $billingData = $this->calculatePatientTotals($patient, $admissionId);
        
        // Get itemized rows for display
        $billRows = $this->getBillRows($patient, $admissionId);
        $assignmentRows = $this->getAssignmentRows($patient);
        $rxRows = $this->getPharmacyRows($patient);
        
        // Merge and process rows
        $rows = collect()
            ->concat($billRows)
            ->concat($assignmentRows)
            ->concat($rxRows);

        // Group and collapse rows
        $groupedRows = $this->groupAndCollapseRows($rows);

        // Create paginator
        $perPage = 100000000000;
        $page = $request->input('page', 1);
        $paginator = new LengthAwarePaginator(
            $groupedRows->forPage($page, $perPage),
            $groupedRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Process disputes
        $disputeArray = Dispute::where('patient_id', $patient->patient_id)->get();
        $allDispute = [
            'Pharmacy'       => [],
            'Laboratory'     => [],
            'Operating room' => [],
        ];

        foreach ($disputeArray as $dis) {
            $allDispute[$dis->disputable_type][$dis->disputable_id] = $dis->status;
        }
        
        $pdf = Pdf::loadView('patient.pdf.statementReceipt', [
            'patient'             => $patient,
            'admission'           => $patient->admissionDetail()->find($admissionId),
            'allDispute'          => $allDispute,
            'admissions'          => $billingData['admissions'],
            'admissionId'         => $billingData['admissionId'],
            'items'               => $paginator,
            'totals'              => [
                'total'    => $billingData['grandTotal'],
                'balance'  => $billingData['balance'],
                'discount' => 0,
            ],
            'bedRate'             => $billingData['bedRate'],
            'doctorFee'           => $billingData['doctorFee'],
            'pharmacyTotal'       => $billingData['rxTotal'],
            'laboratoryFee'       => $billingData['labFee'],
            'ORFee'               => $billingData['orFee'],
            'pharmacyPendingTotal' => $billingData['rxPendingTotal'],
            'paymentsMade'        => $billingData['paymentsMade'],
            'daysAdmitted'        => $billingData['daysAdmitted'],
        ])->setPaper('a4', 'portrait');

        // Download with a filename
        $filename = 'statement_adm'.$admissionId.'_'.now()->format('Ymd').'.pdf';
        return $pdf->download($filename);
    }

    public function disputeRequest($billItemId)
    {
        $charge = BillItem::with(['service.department','bill.doctor'])
                ->findOrFail($billItemId);

        // Ensure the logged-in patient owns it
        abort_unless(
            $charge->bill->patient_id === Auth::user()->patient_id,
            403
        );

        return view('patient.billing.disputeRequest', compact('charge'));
    }

    public function chargeTrace(string $key)
    {
        if (Str::startsWith($key, 'SA-')) {
            /* ---------- ServiceAssignment branch ---------- */
            $assignmentId = intval(Str::after($key, 'SA-'));

            $as = ServiceAssignment::with(['service.department','doctor'])
                    ->findOrFail($assignmentId);

            // synthesise a pseudo-charge object so the same Blade works
            $charge = (object) [
                'is_assignment'   => true,
                'billing_item_id' => 'SA-'.$as->assignment_id,
                'service'         => $as->service,
                'amount'          => $as->amount ?? 0,
                'status'          => $as->service_status,
                'billing_date'    => $as->datetime ?? $as->created_at,
                'logs'            => collect([
                    (object)[
                        'action'     => 'created',
                        'actor'      => optional($as->doctor)->doctor_name ?? '—',
                        'created_at' => $as->datetime ?? $as->created_at,
                    ],
                    $as->service_status === 'completed'
                        ? (object)[
                            'action'     => 'completed',
                            'actor'      => optional($as->doctor)->doctor_name ?? '—',
                            'created_at' => $as->updated_at ?? $as->datetime,
                        ]
                        : null,
                ])->filter(),
            ];
        } elseif (Str::startsWith($key, 'RX-')) {
            $itemId = intval(Str::after($key,'RX-'));

            $rxItem = PharmacyChargeItem::with(['service', 'charge'])
                    ->findOrFail($itemId);

            $charge = (object)[
                'is_rx'          => true,
                'billing_item_id'=> 'RX-'.$rxItem->id,
                'service'        => $rxItem->service,
                'amount'         => $rxItem->total,
                'status'         => 'completed',
                'billing_date'   => $rxItem->charge->created_at,
                'logs'           => collect([]),
            ];
        } else {
            /* ---------- BillItem branch ---------- */
            $charge = BillItem::with(['service.department','logs'])
                    ->findOrFail(intval($key));
        }

        return view('patient.billing.chargeTrace', compact('charge'));
    }

    public function show(Bill $bill)
    {
        $this->authorize('view', $bill); // optional policy

        $bill->load(['items.service.department']);
        return view('patient.bill-show', compact('bill'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bill_item_id' => 'required|exists:bill_items,id',
            'reason'       => 'required|string|max:255',
            'details'      => 'nullable|string',
            'documents.*'  => 'file|max:10240',
        ]);

        // save dispute
        $dispute = Dispute::create([
            'bill_item_id' => $data['bill_item_id'],
            'patient_id'   => Auth::user()->patient->patient_id,
            'reason'       => $data['reason'],
            'details'      => $data['details'] ?? '',
            'status'       => 'pending',
        ]);

        // store docs (optional)
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $file->storeAs('disputes/'.$dispute->id, $file->getClientOriginalName(), 'public');
            }
        }

        return back()->with('success','Your dispute request has been submitted.');
    }
}
