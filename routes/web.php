<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\notifications_latest;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminUsersController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Billing\DepositController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Pharmacy\ChargeController;
use App\Http\Controllers\Billing\ChargeController   as BillingChargeController;
use App\Http\Controllers\OperatingRoomController;
use App\Http\Controllers\Billing\DischargeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PatientBillingController;
use App\Http\Controllers\PatientDisputeController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\PatientNotificationController;
use App\Http\Controllers\BillingDashboardController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\HospitalServiceController;
use App\Http\Controllers\Admin\UserAuditController;

// PHARMACY MODULE ---------------------------------------------------------------------------------------
Route::middleware(['auth'])
     ->prefix('pharmacy')->name('pharmacy.')
     ->group(function () {
         Route::get('dashboard', [PharmacyController::class, 'index'])
              ->name('dashboard');

         // ← dispense route must NOT repeat the 'pharmacy/' prefix
         Route::patch(
             'charges/{charge}/dispense',
             [App\Http\Controllers\PharmacyController::class,'dispense']
         )->name('charges.dispense');

         // Over-the-counter sales routes
         Route::get('otc', [PharmacyController::class, 'otcSales'])
              ->name('otc');
         Route::post('otc/charge', [PharmacyController::class, 'createOtcCharge'])
              ->name('otc.charge');

     //     Route::resource('charges', PharmacyChargeController::class);
         Route::get('queue', [PharmacyController::class, 'queue'])->name('queue');
         // Ensure consistent route naming - this was causing the error
         Route::get('charges/{charge}/select', [PharmacyController::class, 'selectItems'])->name('charges.select');
         Route::get('history', [PharmacyController::class, 'history'])->name('history');
     });

// OR MODULE ----------------------------------------------------------------------------------------------
Route::middleware(['auth','role:operating_room'])
     ->prefix('operating')
     ->name('operating.')
     ->group(function () {
         Route::get('dashboard',    [OperatingRoomController::class, 'dashboard'])->name('dashboard');
         Route::get('queue',        [OperatingRoomController::class, 'queue'])    ->name('queue');
         Route::get('create',       [OperatingRoomController::class, 'create'])   ->name('create');
         Route::post('store',       [OperatingRoomController::class, 'store'])    ->name('store');
         Route::get('history',      [OperatingRoomController::class, 'history'])  ->name('history');
         Route::get('{assignment}', [OperatingRoomController::class, 'show'])     ->name('details');
         Route::post('{assignment}/complete', [OperatingRoomController::class, 'markCompleted'])->name('complete');
         Route::get('/operating/history/{assignment}', [OperatingRoomController::class, 'show'])->name('history.show');
         Route::post('{assignment}/approve',  [OperatingRoomController::class, 'approveProcedure'])->name('approve');
         Route::post('{assignment}/cancel',   [OperatingRoomController::class, 'cancel'])->name('cancel');
 });

Route::post('notifications/mark-all-read', [PatientNotificationController::class, 'markAllRead'])
     ->name('notifications.markAllRead')
     ->middleware('auth');

     // BILLING MODULE ---------------------------------------------------------------------------------------
     Route::middleware(['auth','role:billing'])
     ->prefix('billing')
     ->name('billing.')
     ->group(function () {
    // 1) Dashboard ("Home" for billing users)
    Route::get('dashboard', [BillingDashboardController::class, 'index'])
         ->name('dashboard');
     //     Route::patch('patients/{patient}/toggle-lock', [BillingChargeController::class, 'toggleLock'])
     //     ->name('patients.toggleLock');
         Route::patch('dispute/{dispute}', [DisputeController::class, 'update'])->name('disputes.update');

    // 2) Patient Bills / Manual Charges list
    Route::get('main', [PatientBillingController::class, 'index'])
         ->name('main');
         Route::get(
          '/my-billing/charge/{billItem}/dispute',
          [PatientBillingController::class,'disputeRequest']
       )->name('patient.dispute.form');
       

    // 3) Notifications
    Route::get ('notifications',               [NotificationController::class, 'index'])
         ->name('notifications');
   Route::post('notifications/mark-all-read', [PatientNotificationController::class,'markAllRead'])
     ->name('notifications.markAllRead');


    // 4) Dispute queue & detail
    Route::get('dispute/queue',     [DisputeController::class, 'queue'])
         ->name('dispute.queue');
    Route::get('dispute/{dispute}', [DisputeController::class, 'show'])
         ->name('dispute.show');

//     Route::get   ('charges',             [BillingChargeController::class,'index'])   ->name('charges.index');
//     Route::get   ('charges/create',      [BillingChargeController::class,'create'])  ->name('charges.create');
//     Route::post  ('charges',             [BillingChargeController::class,'store'])   ->name('charges.store');
//     Route::get   ('charges/{item}',      [BillingChargeController::class,'show'])    ->name('charges.show');
//     Route::get   ('charges/{item}/edit', [BillingChargeController::class,'edit'])    ->name('charges.edit');
//     Route::put   ('charges/{item}',      [BillingChargeController::class,'update'])  ->name('charges.update');
//     Route::delete('charges/{item}',      [BillingChargeController::class,'destroy']) ->name('charges.destroy');
//     Route::get   ('charges/{item}/audit',[BillingChargeController::class,'audit'])   ->name('charges.audit');

    // 6) Deposits
    Route::get  ('deposits/create', [DepositController::class, 'create'])
         ->name('deposits.create');
    Route::post ('deposits',        [DepositController::class, 'store'])
         ->name('deposits.store');

    // 7) Print statement & lock bill
    Route::get  ('print/{patient}', [BillingDashboardController::class, 'print'])
         ->name('print');
    Route::post ('lock/{patient}',  [BillingDashboardController::class, 'lock'])
         ->name('lock');
         Route::get('patient/billing/charge-trace/{billItem}', 
    [PatientBillingController::class, 'chargeTrace'])
    ->name('patient.billing.chargeTrace');

         

          // Discharge dashboard
        Route::get('/records', [DischargeController::class,'index'])->name('billing.records.index');
        // Settle discharge for a patient
        Route::post('records/{patient}/settle', [DischargeController::class,'settle'])->name('records.settle');
        // Discharge index (duplicate route, consider removing one)
        Route::get('records', [DischargeController::class, 'index'])->name('records.index');
        // Finish discharge for a patient
        Route::post('records/{patient}/finish', [DischargeController::class, 'finish'])->name('records.finish');
        // Store discharge for a patient
         
        Route::post('discharge/{patient}', [DischargeController::class, 'store'])->name('discharge.store');
        // actually mark one patient finished

        Route::post('records/{patient}', [DischargeController::class, 'store'])->name('records.store');
        Route::get('records/{patient}', [DischargeController::class, 'show'])->name('records.show');
        Route::post('items/{id}', [DischargeController::class, 'updateBillingItem'])->name('items.update');
        Route::delete('items/{id}', [DischargeController::class, 'deleteBillingItem'])->name('items.delete');
        Route::get('records/{patient}/print', [DischargeController::class, 'printStatement'])->name('records.print');
        Route::delete('deposits/{deposit}', [DepositController::class, 'destroy'])->name('deposits.destroy');
         
});  

// PATIENT MODULE ---------------------------------------------------------------------------------------

Route::prefix('patient')
     ->name('patient.')
   ->middleware(['auth', 'role:patient'])  
     ->group(function(){
         Route::get('dashboard', [PatientController::class, 'dashboard'])
              ->name('dashboard');
          Route::get ('account',           [ProfileController::class,'edit'])           ->name('account');
          Route::patch('account',          [ProfileController::class,'update'])         ->name('account.update');
          Route::patch('account/password', [ProfileController::class,'updatePassword']) ->name('account.password');
    Route::get ('billing',          [PatientBillingController::class,'index'])->name('billing');
Route::get ('billing/{bill}',   [PatientBillingController::class,'show'])->name('billing.show');   // "Details"
    Route::get('billing/statement/pdf', [PatientBillingController::class,'downloadStatement'])
         ->name('billing.statement');
Route::get(
    'billing/charge-history/{billItem}',
    [PatientBillingController::class, 'chargeTrace']
)->name('billing.chargeTrace');

 // POST /patient/disputes
Route::post('disputes', [DisputeController::class,'store'])
    ->name('disputes.store');

// POST /patient/disputes/cancel
Route::post('disputes/cancel', [DisputeController::class,'cancel'])
    ->name('disputes.cancel');





// GET  /patient/disputes
Route::get('disputes',
   [DisputeController::class,'myDisputes']
)->name('disputes.mine');       // becomes patient.disputes.mine

  Route::get('notifications', [PatientNotificationController::class, 'index'])->name('notification');  
  Route::patch('notifications/{notification}', [PatientNotificationController::class, 'update'])
              ->name('notifications.update');

   Route::prefix('items')->name('items.')->group(function () {
        Route::post('/',            [HospitalServiceController::class, 'store' ])->name('store');
        Route::put('{service}',     [HospitalServiceController::class, 'update'])->name('update');
        Route::delete('{service}',  [HospitalServiceController::class, 'destroy'])->name('destroy');
    });
     });


// LABORATORY MODULE -----------------------------------

Route::prefix('laboratory')->name('laboratory.')
     ->middleware('auth')
     ->group(function () {
         Route::get('dashboard',   [LabController::class, 'dashboard'])->name('dashboard');
         Route::get('queue',       [LabController::class, 'queue'])->name('queue');
         Route::get('history',     [LabController::class, 'history'])->name('history');

         // handle the form POST
         Route::post('store', [LabController::class, 'store'])
              ->name('store');
         // Viewing & completing existing *requests*
         Route::get('details/{assignment}',       [LabController::class, 'show'])
              ->name('details');
         Route::post('details/{assignment}/complete',
              [LabController::class, 'markCompleted'])
              ->name('details.complete');
         Route::get('/laboratory/history/{assignment}', [LabController::class, 'show'])->name('history.show');
         Route::post('/laboratory/requests/{assignment}/cancel', [LabController::class, 'cancel'])->name('details.cancel');
      

     });

// DASHBOARD ROUTING ------------------------------------------------------------------------------
Route::middleware('auth')->get('/dashboard', function () {
    $role = Auth::user()->role;
    return match ($role) {
        'admin'     => redirect()->route('admin.dashboard'),
        'admission' => redirect()->route('admission.dashboard'),
        'pharmacy'  => redirect()->route('pharmacy.dashboard'),
        'laboratory' => redirect()->route('laboratory.dashboard'),
        default     => redirect()->route('home'),
    };
});

// HOME PAGE ---------------------------------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/login',   [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class,'login'])
     ->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('patient/login', [LoginController::class,'showPatientLoginForm'])
     ->name('patient.login');
// process patient login
Route::post('patient/login', [LoginController::class,'patientLogin'])
     ->name('patient.login.attempt');
// routes/web.php


// CHARGE TRACEBACK ----------------------------------------------------------------------------------
Route::middleware('auth')
     ->prefix('patient/billing')
     ->name('patient.billing.')
     ->group(function() {
         // … existing routes …
         Route::get('charge/{item}/trace', [PatientBillingController::class,'chargeTrace'])
              ->name('charge.trace');
     });


// ADMISSION MODULE ----------------------------------------------------------------------------------
Route::middleware(['auth'])
     ->prefix('admission')->name('admission.')
     ->group(function () {
         // Dashboard route
         Route::get('dashboard', [AdmissionController::class, 'dashboard'])->name('dashboard');
         
         // API routes for dynamic dropdowns
         Route::get('departments/{department}/doctors',
                    [PatientController::class,'getDoctorsByDepartment'])
              ->name('departments.doctors');
         Route::get('departments/{department}/rooms',
                    [PatientController::class,'getRoomsByDepartment'])
              ->name('departments.rooms');
         Route::get('rooms/{room}/beds',
                    [PatientController::class,'getBedsByRoom'])
              ->name('rooms.beds');
         
         // Patient CRUD routes
         Route::resource('patients', PatientController::class)
              ->only(['index', 'create', 'store', 'show']);
         
         // Doctor change route
         Route::patch('patients/{patient}/change-doctor', [PatientController::class, 'changeDoctor'])
              ->name('patients.change-doctor');
              
         // Room & bed change routes - consolidated here to avoid duplicates
         Route::get('patients/{patient}/change-room-bed', [PatientController::class, 'changeRoomBedForm'])
              ->name('patients.change-room-bed.form');
              
         Route::patch('patients/{patient}/change-room-bed', [PatientController::class, 'changeRoomAndBed'])
              ->name('patients.change-room-bed');
     });



     // ADMISSION MODULE END ----------------------------------------------------------------------------------

// ADMIN MODULE START -------------------------------------------------------------------------------------
Route::middleware(['auth'])
      ->prefix('admin')->name('admin.')
      ->group(function(){
          Route::get('dashboard',[AdminController::class,'dashboard'])->name('dashboard');
          Route::resource('users', AdminUsersController::class);
          Route::get('users/{user}/assign',
    [AdminUsersController::class,'showAssignment'])
    ->name('users.assign');


     Route::post('users/{user}/assign',
    [AdminUsersController::class,'updateAssignment'])
    ->name('users.assign.update');
   Route::get  ('resources',              [ResourceController::class,'index'])->name('resources.index');
    Route::get  ('resources/create',       [ResourceController::class,'create'])->name('resources.create');
    Route::post ('resources',              [ResourceController::class,'store'])->name('resources.store');

    // Rooms and Bed
    Route::get    ('resources/{type}/{id}/edit',   [ResourceController::class,'edit'])
           ->where('type','room|bed')->name('resources.edit');
    Route::put    ('resources/{type}/{id}',        [ResourceController::class,'update'])
           ->where('type','room|bed')->name('resources.update');
    Route::delete ('resources/{type}/{id}',        [ResourceController::class,'destroy'])
           ->where('type','room|bed')->name('resources.destroy');

    // User Audit Logs
    Route::get('/audit', [UserAuditController::class, 'index'])->name('audit.index');
    Route::get('/audit/{audit}', [UserAuditController::class, 'show'])->name('audit.show');

    // Hospital Services Management
    Route::get('hospital-services', [HospitalServiceController::class, 'index'])->name('hospital_services.index');
    Route::get('hospital-services/create', [HospitalServiceController::class, 'create'])->name('hospital_services.create');
    Route::post('hospital-services', [HospitalServiceController::class, 'store'])->name('hospital_services.store');
    Route::get('hospital-services/{service}/edit', [HospitalServiceController::class, 'edit'])->name('hospital_services.edit');
    Route::put('hospital-services/{service}', [HospitalServiceController::class, 'update'])->name('hospital_services.update');
    Route::delete('hospital-services/{service}', [HospitalServiceController::class, 'destroy'])->name('hospital_services.destroy');
    Route::get('hospital-services/{service}', function ($serviceId) {
        $service = \App\Models\HospitalService::findOrLog($serviceId);
        if (!$service) {
            abort(404, 'Hospital Service not found.');
        }
        return view('hospital_services.show', compact('service'));
    })->name('hospital_services.show');
});

// ADMISSION MODULE END  ----------------------------------------------------------------------------------

// DOCTOR MODULE -------------------------------------------------------------------------------------
Route::prefix('doctor')
    ->name('doctor.')
    ->middleware('auth')
    ->group(function(){

        Route::get('/dashboard', [DoctorController::class,'dashboard'])->name('dashboard');
        // Patient routes
        Route::get('/patients/{patient}', [DoctorController::class,'show'])->name('patient.show');

        Route::get('/order-entry/{patient}', [DoctorController::class,'orderEntry'])->name('order');
        Route::post('/orders/{patient}', [DoctorController::class,'storeOrder'])->name('orders.store');
        Route::get('/orders', [DoctorController::class,'ordersIndex'])->name('orders.index');
        Route::get('/orders/{patient}', [DoctorController::class, 'showOrders'])->name('orders.show');
        //Route::get('doctor/orders', [DoctorController::class, 'ordersIndex'])->name('orders.index');

        // Finish patient (mark as cleared)
        Route::post('/patients/{patient}/finished', [DoctorController::class, 'patientFinished'])->name('patientFinished');

        // Nurse Requests
        Route::get('nurse-requests', [DoctorController::class, 'nurseRequests'])->name('nurse-requests');

        Route::get('nurse-requests/{request}/accept', [DoctorController::class, 'showAcceptForm'])->name('nurse-request.accept.form'); // Show input form
        Route::post('nurse-requests/{request}/accept', [DoctorController::class, 'acceptNurseRequest'])->name('nurse-request.accept');
        Route::post('nurse-requests/{request}/reject', [DoctorController::class, 'rejectNurseRequest'])->name('nurse-request.reject');
    });



Route::post('disputes/change', [DisputeController::class, 'change'])
    ->name('billing.disputes.change');


Route::get('/notifications/latest', function () {
    // Lock for update to prevent race conditions
    $notification = notifications_latest::whereNull('popped_up')
                                        ->where('sendTo_id', Auth::user()->patient->patient_id)
                                        ->lockForUpdate()
                                        ->first();

    if ($notification) {
        // Mark as popped immediately
        $notification->popped_up = 1;
        $notification->save();

        return response()->json([$notification]);
    }

    return response()->json([]);
});

// Nurse Module -------------------------------------------------------------------------------------

Route::middleware(['auth', 'role:nurse'])->prefix('nurse')->name('nurse.')->group(function () {
    Route::get('/dashboard', [NurseController::class, 'dashboard'])->name('dashboard');
    
    // Patient management for requests
    Route::get('/patients', [NurseController::class, 'patientsIndex'])->name('patients.index');
    
    // Request management
    Route::get('/request/create/{patient}', [NurseController::class, 'createRequest'])->name('request.create');
    Route::post('/request/store', [NurseController::class, 'storeRequest'])->name('request.store');
    
    // Request history
    Route::get('/requests/history', [NurseController::class, 'requestHistory'])->name('requests.history');
});
   
Route::match(['get', 'post'], '/patient/billing/statement/{patient}', [PatientBillingController::class, 'printReceipt'])
    ->name('patient.statement');
;



// Remove these duplicate room/bed routes at the end of the file
// Route::get('/admission/patients/{patient}/change-room-bed', [App\Http\Controllers\PatientController::class, 'changeRoomBedForm'])
//    ->name('admission.patients.change-room-bed.form');
// Route::patch('/admission/patients/{patient}/change-room-bed', [App\Http\Controllers\PatientController::class, 'changeRoomAndBed'])
//    ->name('admission.patients.change-room-bed');

