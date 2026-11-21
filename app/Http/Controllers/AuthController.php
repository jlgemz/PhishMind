<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PHPMailerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller 
{
    protected $mailer;

    public function __construct(PHPMailerService $mailer) 
    { 
        $this->mailer = $mailer; 
    }

    public function showLogin() 
    { 
        return view('auth.login'); 
    }

    public function showRegister() 
    { 
        return view('auth.register'); 
    }

    public function login(Request $request) 
    {
        $credentials = $request->validate([
            'email' => 'required|email', 
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate(); // Security best practice
            $user = Auth::user();
            
            // 1. Check if account is verified
            if (!$user->is_verified) {
                $this->sendOtp($user);
                return redirect()->route('verify.otp')->with('success', 'Verification code sent.');
            }

            // 2. Redirect based on role (Admin vs Student)
            if ($user->is_admin) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->intended('/dashboard');
        }
        
        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function register(Request $request) 
    {
        $validated = $request->validate([
            'name' => 'required', 
            'email' => 'required|email|unique:users', 
            'password' => 'required|min:8|confirmed'
        ]);

        $user = User::create([
            'name' => $validated['name'], 
            'email' => $validated['email'], 
            'password' => Hash::make($validated['password']), 
            'is_verified' => false
        ]);

        Auth::login($user);
        $this->sendOtp($user);
        
        return redirect()->route('verify.otp')->with('success', 'Registration successful. Check your email.');
    }

    public function showVerifyOtp() 
    { 
        return view('auth.verify-otp'); 
    }

    public function verifyOtp(Request $request) 
    {
        $request->validate(['otp' => 'required|numeric|digits:6']);
        
        $user = Auth::user();
        
        if ($user->otp_code === $request->otp && Carbon::now()->lessThanOrEqualTo($user->otp_expires_at)) {
            $user->update(['is_verified' => true, 'otp_code' => null, 'otp_expires_at' => null]);
            
            // Check role after OTP verification
            if ($user->is_admin) {
                return redirect()->route('admin.dashboard')->with('success', 'Admin account verified!');
            }
            
            return redirect()->route('dashboard')->with('success', 'Account verified!');
        }
        
        return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
    }

    public function logout(Request $request) 
    {
        Auth::logout(); 
        $request->session()->invalidate(); 
        $request->session()->regenerateToken(); 
        return redirect('/');
    }

    private function sendOtp(User $user) 
    {
        $otp = (string) rand(100000, 999999);
        $user->update(['otp_code' => $otp, 'otp_expires_at' => Carbon::now()->addMinutes(15)]);
        $this->mailer->sendOTP($user->email, $otp);
    }

    public function resendOtp()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $this->sendOtp($user);
        return back()->with('success', 'A new verification code has been sent to ' . $user->email);
    }

        // ============================================
    // FORGOT PASSWORD FLOW
    // ============================================

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        
        $user->update([
            'reset_otp' => $otp,
            'reset_otp_expires_at' => Carbon::now()->addMinutes(15)
        ]);

        // Send email
        $this->mailer->sendOTP($user->email, $otp);

        // Store email in session to verify against later
        session([
            'reset_email' => $user->email,
        ]);

        return redirect()->route('password.verify')->with('success', 'Password reset code sent to your email.');
    }

    public function showVerifyResetOtp()
    {
        if (!session('reset_email')) {
            return redirect()->route('password.request');
        }
        return view('auth.verify-reset-otp');
    }

    public function verifyResetOtp(Request $request)
    {
        $request->validate(['otp' => 'required|numeric|digits:6']);

        $email = session('reset_email');
        if (!$email) {
            return redirect()->route('password.request')->withErrors(['email' => 'Session expired. Please try again.']);
        }

        $user = User::where('email', $email)->first();

        if (!$user || $user->reset_otp !== $request->otp || Carbon::now()->greaterThan($user->reset_otp_expires_at)) {
            return back()->withErrors(['otp' => 'Invalid or expired reset code.']);
        }

        // OTP is valid. Store a flag in session to allow password reset.
        session(['reset_verified' => true]);

        return redirect()->route('password.reset');
    }

    public function showResetPassword()
    {
        if (!session('reset_verified') || !session('reset_email')) {
            return redirect()->route('password.request');
        }
        return view('auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed'
        ]);

        $email = session('reset_email');
        if (!$email || !session('reset_verified')) {
            return redirect()->route('password.request')->withErrors(['email' => 'Session expired.']);
        }

        $user = User::where('email', $email)->first();
        
        $user->update([
            'password' => Hash::make($request->password),
            'reset_otp' => null,
            'reset_otp_expires_at' => null,
            'is_verified' => true, // Auto-verify if they reset password via email
        ]);

        // Clear the reset session
        session()->forget(['reset_email', 'reset_verified']);

        return redirect()->route('login')->with('success', 'Password reset successfully. Please log in.');
    }
}