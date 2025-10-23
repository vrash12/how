{{-- filepath: c:\Users\Sam\Desktop\PatientCare-Updated-main - Edited\resources\views\billing\statements\print.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Statement - {{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .patient-info { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .text-right { text-align: right; }
        .summary { margin-top: 30px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Print Statement</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="header">
        <h1>Hospital Billing Statement</h1>
        <p>Generated on: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <div class="patient-info">
        <h3>Patient Information</h3>
        <p><strong>Name:</strong> {{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</p>
        <p><strong>MRN:</strong> {{ str_pad($patient->patient_id, 8, '0', STR_PAD_LEFT) }}</p>
        <p><strong>Admission ID:</strong> {{ optional($admission)->admission_id ?? 'N/A' }}</p>
        <p><strong>Room:</strong> {{ optional(optional($admission)->room)->room_name ?? 'N/A' }}</p>
        <p><strong>Doctor:</strong> {{ optional($admission?->doctor)->doctor_name ?? 'N/A' }}</p>
        <p><strong>Admission Date:</strong> {{ optional($admission)->admission_date ? $admission->admission_date->format('F d, Y') : 'N/A' }}</p>
    </div>

    <h3>Itemized Charges</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Type</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            {{-- Service Assignments --}}
            @foreach($serviceAssignments as $assignment)
                <tr>
                    <td>{{ $assignment->created_at->format('M d, Y') }}</td>
                    <td>{{ $assignment->service?->service_name ?? '—' }}</td>
                    <td>
                        @if($assignment->mode == 'lab')
                            Laboratory
                        @elseif($assignment->mode == 'or' || $assignment->mode == 'operating_room')
                            Operating Room
                        @else
                            {{ ucfirst($assignment->mode) }}
                        @endif
                    </td>
                    <td class="text-right">₱{{ number_format($assignment->amount ?? 0, 2) }}</td>
                </tr>
            @endforeach

            {{-- Pharmacy Items --}}
            @foreach($pharmacyCharges as $charge)
                @foreach($charge->items->where('status', 'dispensed') as $item)
                    <tr>
                        <td>{{ $charge->created_at->format('M d, Y') }}</td>
                        <td>{{ $item->service?->service_name ?? '—' }} (Qty: {{ $item->dispensed_quantity }})</td>
                        <td>Pharmacy</td>
                        <td class="text-right">₱{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            @endforeach

            {{-- Doctor Fee --}}
            @if($doctorFee > 0)
                <tr>
                    <td>{{ optional($admission)->admission_date ? $admission->admission_date->format('M d, Y') : '—' }}</td>
                    <td>Professional Fee - {{ optional($admission?->doctor)->doctor_name ?? '—' }}</td>
                    <td>Doctor Fee</td>
                    <td class="text-right">₱{{ number_format($doctorFee, 2) }}</td>
                </tr>
            @endif

            {{-- Room Fee --}}
            @if($roomFee > 0)
                <tr>
                    <td>{{ optional($admission)->admission_date ? $admission->admission_date->format('M d, Y') : '—' }}</td>
                    <td>Room Rate - {{ optional(optional($admission)->room)->room_name ?? '—' }}</td>
                    <td>Room Fee</td>
                    <td class="text-right">₱{{ number_format($roomFee, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <h3>Payment History</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deposits as $deposit)
                <tr>
                    <td>{{ $deposit->deposited_at->format('M d, Y') }}</td>
                    <td>Payment/Deposit</td>
                    <td class="text-right">₱{{ number_format($deposit->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">No payments recorded</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <table class="table">
            <tr class="total-row">
                <td><strong>Total Charges:</strong></td>
                <td class="text-right"><strong>₱{{ number_format($grandTotal, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Payments:</strong></td>
                <td class="text-right"><strong>₱{{ number_format($totalPaid, 2) }}</strong></td>
            </tr>
            <tr class="total-row">
                <td><strong>Outstanding Balance:</strong></td>
                <td class="text-right"><strong>₱{{ number_format($balance, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>