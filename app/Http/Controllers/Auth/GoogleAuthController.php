<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteGoogleRegisterRequest;
use App\Http\Requests\Auth\LinkGoogleAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google OAuth authentication page.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('login')->withErrors([
                'login' => 'Google Client ID dan Client Secret belum dikonfigurasi di environment (.env). Silakan atur GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET.',
            ]);
        }

        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle the callback returned from Google OAuth.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            Log::error('Gagal mengautentikasi akun Google: '.$e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            $message = $e->getMessage();
            if (empty(trim((string) $message))) {
                $message = $e instanceof InvalidStateException
                    ? 'Sesi OAuth tidak valid atau state kedaluwarsa. Silakan ulangi klik login Google.'
                    : 'Terjadi kesalahan pada respon Google OAuth ('.class_basename($e).'). Pastikan Google OAuth Consent Screen dan Test Users sudah dikonfigurasi.';
            }

            return redirect()->route('login')->withErrors([
                'login' => 'Gagal mengautentikasi akun Google: '.$message,
            ]);
        }

        $googleId = (string) $googleUser->getId();
        $email = (string) $googleUser->getEmail();
        $avatarUrl = $googleUser->getAvatar();
        $name = $googleUser->getName() ?? $googleUser->getNickname() ?? '';

        // Skenario 1: google_id sudah terhubung ke akun users
        $existingGoogleUser = User::where('google_id', $googleId)->first();
        if ($existingGoogleUser) {
            if ($existingGoogleUser->account_status === 'SUSPENDED') {
                return redirect()->route('login')->withErrors([
                    'login' => 'Akun Anda sedang ditangguhkan (SUSPENDED). Silakan hubungi admin.',
                ]);
            }

            Auth::login($existingGoogleUser);
            $request->session()->regenerate();

            if ($existingGoogleUser->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'))
                    ->with('success', 'Selamat datang di Panel Admin, @'.$existingGoogleUser->username.'!');
            }

            return redirect()->intended(route('home'))
                ->with('success', 'Berhasil masuk dengan akun Google @'.$existingGoogleUser->username.'!');
        }

        // Skenario 2: Email Google sudah terdaftar di akun lokal (Verifikasi kepemilikan)
        $existingEmailUser = User::where('email', $email)->first();
        if ($existingEmailUser) {
            session([
                'pending_google_link' => [
                    'google_id' => $googleId,
                    'email' => $email,
                    'avatar_url' => $avatarUrl,
                    'user_id' => $existingEmailUser->user_id,
                    'username' => $existingEmailUser->username,
                ],
            ]);

            return redirect()->route('auth.google.link')->with(
                'warning',
                'Email '.$email.' sudah terdaftar pada akun @'.$existingEmailUser->username.'. Masukkan password lokal untuk menautkan akun Google.'
            );
        }

        // Skenario 3: Pengguna baru Google (wajib memilih username unik)
        $cleanName = Str::lower(preg_replace('/[^a-z0-9_]/', '', Str::slug($name, '_')));
        $suggestedUsername = substr($cleanName ?: 'user', 0, 20);

        // Jika saran username sudah dipakai, tambahkan angka acak
        if (User::where('username', $suggestedUsername)->exists()) {
            $suggestedUsername = substr($suggestedUsername, 0, 15).'_'.Str::lower(Str::random(4));
        }

        session([
            'pending_google_registration' => [
                'google_id' => $googleId,
                'email' => $email,
                'avatar_url' => $avatarUrl,
                'name' => $name,
                'suggested_username' => $suggestedUsername,
            ],
        ]);

        return redirect()->route('auth.google.username');
    }

    /**
     * Show form to choose a unique username for first-time Google registration.
     */
    public function showChooseUsernameForm(): View|RedirectResponse
    {
        $pending = session('pending_google_registration');

        if (! $pending) {
            return redirect()->route('login');
        }

        return view('auth.google-choose-username', [
            'pending' => $pending,
        ]);
    }

    /**
     * Complete the Google registration after username selection.
     */
    public function completeGoogleRegistration(CompleteGoogleRegisterRequest $request): RedirectResponse
    {
        $pending = session('pending_google_registration');

        if (! $pending) {
            return redirect()->route('login')->withErrors([
                'login' => 'Sesi pendaftaran Google telah kedaluwarsa. Silakan coba lagi.',
            ]);
        }

        $user = User::create([
            'username' => $request->input('username'),
            'email' => $pending['email'],
            'google_id' => $pending['google_id'],
            'avatar_url' => $pending['avatar_url'] ?: null,
            'password_hash' => null,
            'email_verified_at' => now(),
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        session()->forget('pending_google_registration');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('success', 'Akun Google Anda berhasil didaftarkan! Selamat datang di MarketTrust, @'.$user->username.'.');
    }

    /**
     * Show form to verify password and link existing local account with Google.
     */
    public function showLinkForm(): View|RedirectResponse
    {
        $pending = session('pending_google_link');

        if (! $pending) {
            return redirect()->route('login');
        }

        return view('auth.link-google', [
            'pending' => $pending,
        ]);
    }

    /**
     * Link local account with Google account after verifying password.
     */
    public function linkAccount(LinkGoogleAccountRequest $request): RedirectResponse
    {
        $pending = session('pending_google_link');

        if (! $pending) {
            return redirect()->route('login')->withErrors([
                'login' => 'Sesi penautan akun Google telah kedaluwarsa. Silakan ulangi login Google.',
            ]);
        }

        $user = User::find($pending['user_id']);

        if (! $user) {
            session()->forget('pending_google_link');

            return redirect()->route('login')->withErrors([
                'login' => 'Akun pengguna tidak ditemukan.',
            ]);
        }

        if (! $user->password_hash || ! Hash::check($request->input('password'), $user->password_hash)) {
            return back()->withErrors([
                'password' => 'Password akun lokal salah. Gagal membuktikan kepemilikan akun.',
            ]);
        }

        if ($user->account_status === 'SUSPENDED') {
            session()->forget('pending_google_link');

            return redirect()->route('login')->withErrors([
                'login' => 'Akun lokal ini sedang ditangguhkan (SUSPENDED).',
            ]);
        }

        $user->google_id = $pending['google_id'];
        if (empty($user->avatar_url) && ! empty($pending['avatar_url'])) {
            $user->avatar_url = $pending['avatar_url'];
        }
        $user->save();

        session()->forget('pending_google_link');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('success', 'Akun Google berhasil ditautkan ke akun lokal @'.$user->username.'!');
    }
}
