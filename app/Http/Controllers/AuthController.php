<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserConsent;
use App\Services\CaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $captchaService;

    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
    }

    // --- Views ---

    public function showLoginForm()
    {
        $captchaQuestion = $this->captchaService->generate();
        return view('auth.login', compact('captchaQuestion'));
    }

    public function showRegisterForm()
    {
        $captchaQuestion = $this->captchaService->generate();
        return view('auth.register', compact('captchaQuestion'));
    }

    // --- Actions ---

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'captcha' => 'required|numeric',
        ]);

        if (!$this->captchaService->verify($request->captcha)) {
            return back()->withErrors(['captcha' => 'Jawaban Captcha salah.'])->withInput();
        }

        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $request->session()->regenerate();

            // Redirect based on role
            $user = Auth::user();
            if ($user->hasRole('siswa')) return redirect()->route('student.dashboard');
            if ($user->hasRole('tentor')) return redirect()->route('tutor.dashboard');
            if ($user->hasRole('admin')) return redirect()->route('admin.dashboard');
            if ($user->hasRole('owner')) return redirect()->route('owner.dashboard');
            if ($user->hasRole('superadmin')) return redirect()->route('superadmin.dashboard');

            return redirect('/');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput();
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:siswa,tentor',
            'captcha' => 'required|numeric',
            'terms' => 'required|accepted',
        ]);

        if (!$this->captchaService->verify($request->captcha)) {
            return back()->withErrors(['captcha' => 'Jawaban Captcha salah.'])->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        // Record Consent
        UserConsent::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tos_version' => 'v1.0',
            'agreed_at' => now(),
        ]);

        Auth::login($user);

        if ($request->role === 'siswa') return redirect()->route('student.dashboard');
        return redirect()->route('tutor.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
