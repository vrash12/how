<?php

namespace App\Http\Controllers;

use App\Models\HospitalService;
use Illuminate\Http\Request;

class HospitalServiceController extends Controller
{
    // Handles creation of a new hospital service
    public function store(Request $request)
    {
        // Validate incoming request data
        $data = $request->validate([
            'service_name'   => 'required|string|max:150', // Service name required, max 150 chars
            'service_type'   => 'required|in:medication,lab,operation', // Must be one of these types
            'price'          => 'required|numeric|min:0', // Price must be a positive number
            'description'    => 'nullable|string', // Description is optional
            'quantity'       => 'nullable|integer|min:0', // Quantity is optional, only for medication
            'needs_prescription' => 'sometimes|boolean' // Prescription flag, optional
        ]);

        // If service type is medication, quantity is required
        if ($data['service_type'] === 'medication' && empty($data['quantity'])) {
            return back()->withErrors(['quantity' => 'Quantity is required for medication.'])->withInput();
        }

        // Set default needs_prescription based on service type
        if ($data['service_type'] === 'medication') {
            $data['needs_prescription'] = $request->has('needs_prescription');
        } else {
            $data['needs_prescription'] = null; // Not applicable for non-medications
        }

        // Create the new service record in the database
        $service = HospitalService::create($data);

        // Redirect back with a success message
        return back()->with('success', 'Item “'.$service->service_name.'” added.');
    }

    // Handles updating an existing hospital service
    public function update(Request $request, HospitalService $service)
    {
        // Validate incoming request data
        $data = $request->validate([
            'service_name'  => 'required|string|max:150',
            'service_type'  => 'required|in:medication,lab,operation',
            'price'         => 'required|numeric|min:0',
            'description'   => 'nullable|string',
            'quantity'      => 'nullable|integer|min:0',
            'needs_prescription' => 'sometimes|boolean' // Prescription flag, optional
        ]);

        // If service type is medication, quantity is required
        if ($data['service_type'] === 'medication' && empty($data['quantity'])) {
            return back()->withErrors(['quantity' => 'Quantity is required for medication.'])->withInput();
        }

        // Set needs_prescription based on service type
        if ($data['service_type'] === 'medication') {
            $data['needs_prescription'] = $request->has('needs_prescription');
        } else {
            $data['needs_prescription'] = null; // Not applicable for non-medications
        }

        // Update the service record in the database
        $service->update($data);

        // Redirect back with a success message
        return back()->with('success', 'Item “'.$service->service_name.'” updated.');
    }

    // Handles deleting a hospital service
    public function destroy(HospitalService $service)
    {
        // Delete the service record from the database
        $service->delete();

        // Redirect back with a success message
        return back()->with('success', 'Item deleted.');
    }

    // Lists all services segmented by type, with optional search
    public function index(Request $request)
    {
        $query = $request->get('q');
        $prescriptionFilter = $request->get('prescription');
        
        $medications = HospitalService::where('service_type', 'medication');
        $labs = HospitalService::where('service_type', 'lab');
        $operations = HospitalService::where('service_type', 'operation');
        
        // Apply search filter if query exists
        if ($query) {
            $medications = $medications->where('service_name', 'like', "%{$query}%");
            $labs = $labs->where('service_name', 'like', "%{$query}%");
            $operations = $operations->where('service_name', 'like', "%{$query}%");
        }
        
        // Apply prescription filter for medications
        if ($prescriptionFilter === 'required') {
            $medications = $medications->where('needs_prescription', true);
        } elseif ($prescriptionFilter === 'otc') {
            $medications = $medications->where('needs_prescription', false);
        }
        
        $medications = $medications->orderBy('service_name')->get();
        $labs = $labs->orderBy('service_name')->get();
        $operations = $operations->orderBy('service_name')->get();
        
        // Pass all segmented data to the view for display
        return view('admin.filemanager.index', compact('medications', 'labs', 'operations', 'prescriptionFilter'));
    }

    // Shows the form to create a new hospital service
    public function create()
    {
        return view('admin.filemanager.create');
    }

    // Shows the form to edit an existing hospital service
    public function edit(HospitalService $service)
    {
        return view('admin.filemanager.edit', compact('service'));
    }
}
