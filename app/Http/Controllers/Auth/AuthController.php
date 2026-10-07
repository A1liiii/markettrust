<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle local login attempt with email or username.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $loginInput = trim($request->input('login'));
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        $user = User::query()
            ->when($isEmail, fn ($query) => $query->where('email', $loginInput))
            ->when(! $isEmail, fn ($query) => $query->where('username', Str::lower($loginInput)))
            ->first();

        if (! $user || ! $user->password_hash || ! Hash::check($request->input('password'), $user->password_hash)) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors([
                    'login' => 'Email/username atau password salah.',
                ]);
        }

        if ($user->account_status === 'SUSPENDED') {
            return back()
                ->withInput($request->only('login'))
                ->withErrors([
                    'login' => 'Akun Anda sedang ditangguhkan (SUSPENDED). Silakan hubungi admin.',
                ]);
        }

        Auth::login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('success', 'Selamat datang kembali, '.$user->username.'!');
    }

    /**
     * Show the registration form.
     */
    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle local user registration.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'password_hash' => Hash::make($request->input('password')),
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('success', 'Pendaftaran berhasil! Selamat datang di MarketTrust, @'.$user->username.'.');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('info', 'Anda telah berhasil keluar (logout).');
    }
}
