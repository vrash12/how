<?php
// app/Http/Controllers/PatientNotificationController.php
namespace App\Http\Controllers;
use App\Models\notifications_latest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class PatientNotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index(Request $request)
    {
        $patient = Auth::user()->patient ?? abort(404, 'No patient attached.');
        $patient_id = $patient->patient_id;
        $filter  = $request->input('filter', 'all');
    
        
    
        if ($filter === 'read') {
    $notifications = notifications_latest::where('sendTouser_type', 'patient')
        ->where('sendTo_id', $patient_id)
        ->where('read', '1')
        ->orderBy('created_at', 'desc')
        ->get();
} elseif ($filter === 'unread') {
    $notifications = notifications_latest::where('sendTouser_type', 'patient')
        ->where('sendTo_id', $patient_id)
        ->where('read', '0')
        ->orderBy('created_at', 'desc')
        ->get();
} else {
    $notifications = notifications_latest::where('sendTouser_type', 'patient')
        ->where('sendTo_id', $patient_id)
        ->orderBy('created_at', 'desc')
        ->get();
}

       
    
        return view('patient.notifications', compact('notifications','filter'));
    }
    

    public function update(Request $request, DatabaseNotification $notification)
    {
        $patient = Auth::user()->patient ?? abort(403);
        if ($notification->notifiable_id !== $patient->patient_id) {
            abort(403);
        }
        return back();
    }

    public function markAllRead()
    {
       $patient = Auth::user()->patient ?? abort(404, 'No patient attached.');
        $patient_id = $patient->patient_id;
        
        $notifications = notifications_latest::where('sendTouser_type', 'patient')
        ->where('sendTo_id', $patient_id)
        ->update([
            'read' => '1', 
            
        ]);


        return back();
    }
}
