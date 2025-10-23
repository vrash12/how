{{-- resources/views/patient/pdf/statement.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Receipt</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .totals { margin-top: 20px; width: 100%; }
        .totals td { padding: 4px; }
        .label { width: 80%; text-align: right; }
        .span
        {
           font-weight: bold;
           font-size: 14px;
        }
    </style>
</head>
<body>
    <div style="width: 100%;  text-align: center;">
        <img src="file://{{ public_path('images/pdf_header.png') }}" style="height: 120px; display: inline-block;">
    </div>
    
    


    <h2>Official Receipt</h2>

    <p>
        <strong>Patient:</strong>
        {{ $patient->patient_first_name }} {{ $patient->patient_last_name }}
    </p>
    <p>
        <strong>Admission Date:</strong>
        {{ optional($admission->admission_date)->format('Y-m-d') }}
    </p>
    <p>
        <strong>Generated:</strong>
        {{ \Carbon\Carbon::now()->format('Y-m-d') }}
    </p>

  
      <table>
        <tr>
            <th  colspan="2"> Details</th>
        </tr>
        @foreach([
          
          
          
          ['label'=>'Bed/Room Rate','value'=>$bedRate],
          ['label'=>'Doctor Fee','value'=>$doctorFee],
          ['label'=>'Laboratory Fee','value'=>$laboratoryFee],
          ['label'=>'Operating Room Service','value'=>$ORFee],
          ['label'=>'Pharmacy Charges','value'=>$pharmacyTotal],
        //   ['label'=>'Discount Applied','value'=>$totals['discount']],
        //   ['label'=>'Deposits Paid','value'=>$paymentsMade],  
          
       
        ] as $tile)
       
          <tr>
            <td style="text-align: left">{{ $tile['label'] }}</td>
            <td style="text-align: right">{{ number_format($tile['value'],2) }}</td>
          </tr>
        @endforeach
        
      </table>

      <table style="border: none; background-color: none;" >
        <tr style="border: none; background-color: none;">
            <td style="border: none; background-color: none;text-align: right;width: 90%">DISCOUNT APPLIED:</td>
            <td style="border: none; background-color: none;text-align: right;width: 10%; border-bottom: 1px solid black">{{$totals['discount']}}</td>
        </tr>
        <tr style="border: none; background-color: none;">
            <td style="border: none; background-color: none;text-align: right;width: 90%">DEPOSITS PAID:</td>
            <td style="border: none; background-color: none;text-align: right;width: 10%; border-bottom: 1px solid black">{{$paymentsMade}}</td>
        </tr>
        <tr style="border: none; background-color: none;">
            <td style="border: none; background-color: none;text-align: right;width: 90%">BALANCE:</td>
            <td style="border: none; background-color: none;text-align: right;width: 10%; border-bottom: 1px solid black">{{number_format($totals['balance'], 2)}}</td>
        </tr>
      </table>

<br>

      <table>
        <tr>
            <th  colspan="6"> More Details</th>
        </tr>
        <tr>
            
            <th>Date</th>
            <th>Ref no.</th>
            <th>Description</th>
            <th>Provider</th>
            <th class="text-end">Amount</th>
            <th>Status</th>
           
        </tr>
        @foreach($items as $row)
        @php
        $checkFirst = $row->provider_label ?? $row->provider;
        @endphp
        @if( ($checkFirst != "—") && ($row->status == 'completed' || $row->status == 'disputed' || $row->status == 'dispensed') )
        @php
          $itemId = data_get($row, 'billing_item_id', optional($row->children->first())->billing_item_id);
          $badge  = [
            'complete'=>'success','completed'=>'success',
            'pending'=>'warning','disputed'=>'danger','mixed'=>'secondary',
          ][$row->status] ?? 'secondary';
          if(isset($allDispute[$row->provider][$row->idAss]) && $allDispute[$row->provider][$row->idAss] == 'pending'){
      $row->status = "dispute pending";
    }
        @endphp
                <tr>
                  <td>{{ \Carbon\Carbon::parse($row->billing_date)->format('Y-m-d') }}</td>
                  <td>{{ $row->ref_no }}</td>
                  <td>{{ $row->description }}</td>
                  <td>{{ $row->provider_label ?? $row->provider }}</td>
                  <td class="text-end">{{ number_format($row->amount,2) }}</td>
                  <td><span class="badge bg-{{ $badge }} text-capitalize">{{ $row->status }}</span></td>
                 
                </tr>
           
            @endif
            @endforeach
        
      </table>


      <br><br><br> <br><br><br> <br><br><br>
      <div style="width: 200px;text-align: center">
        <div style="border-bottom: 1px solid black">
        
        </div>
       
        Name of Billing Officer
      </div>
      <br><br><br> <br><br><br>
      <div style="width: 200px;text-align: center">
        <div style="border-bottom: 1px solid black">
        
        </div>
       
        Signature of Billing Officer
      </div>
      
</body>
</html>
