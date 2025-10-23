<?php
// app/Http/Controllers/LoginController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Patient;
class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        if( $request->mode == "patientSwitch"){
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
           
            $users = Patient::where('sub_key', $request->sub_key)->get();
            return view('patients.viewList', compact('users'));
        }

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $bypassPass = null;
        if($request->mode != "patient"){
            $user = User::where('email', $request->email)->whereNull('partnered')->first();
            if($user){
                $bypassPass = Hash::check($request->password, $user->password);

            }
            else{
                $bypassPass = false;
            }
        }
        else{
            $user = User::where('email', $request->email)->first();
            $bypassPass = $request->password == $user->password;
        }
        
        if ((! $user || ! $bypassPass)) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->withInput($request->only('email','remember'));
        }
  


        if($user->role == "patient" && $request->mode != "patient"){

            $users = Patient::where('patient_id', $user->patient_id)->first();
            $users = Patient::where('sub_key', $users->sub_key)->get();
            return view('patients.viewList', compact('users'));
        }else{
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            return $this->redirectByRole($user->role);
        }

     
    }

    private function redirectByRole(string $role)
    {
        return match (trim(strtolower($role))) {
            'admin'         => redirect()->route('admin.dashboard'),
            'admission'     => redirect()->route('admission.dashboard'),
            'pharmacy'      => redirect()->route('pharmacy.dashboard'),
            'doctor'        => redirect()->route('doctor.dashboard'),
            'patient'       => redirect()->route('patient.dashboard'),
            'laboratory'    => redirect()->route('laboratory.dashboard'),
            'operating_room'=> redirect()->route('operating.dashboard'),
            'billing'       => redirect()->route('billing.dashboard'),
            'nurse'         => redirect()->route('nurse.dashboard'),
            default         => redirect()->route('home'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
