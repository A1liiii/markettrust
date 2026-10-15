@extends('layouts.app')

@section('title', 'Masuk ke MarketTrust')

@section('content')
<div class="min-h-[calc(100vh-16rem)] flex items-center justify-center py-10 sm:py-16 px-4 sm:px-6 lg:px-8">
    <div class="glass-panel max-w-md w-full p-6 sm:p-8 relative">
        <!-- Header -->
        <div class="text-center mb-6 sm:mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 mb-3 border border-indigo-500/20 shadow-xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Masuk ke Akun Anda</h2>
            <p class="text-sm text-slate-600 mt-1.5">Akses marketplace, forum jual-beli, dan transaksi escrow Anda</p>
        </div>

        <!-- Google OAuth Button -->
        <div class="mb-6">
            <a href="{{ route('auth.google') }}" class="glass-btn-google w-full">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Masuk dengan Google</span>
            </a>
        </div>

        <div class="relative flex items-center justify-center my-6">
            <div class="glass-divider w-full"></div>
            <span class="glass-badge glass-badge-neutral px-3 uppercase text-[10px] tracking-wider absolute">Atau email / username</span>
        </div>

        <!-- Local Login Form -->
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="login" class="glass-label">Email atau Username</label>
                <div class="relative">
                    <input
                        type="text"
                        id="login"
                        name="login"
                        value="{{ old('login') }}"
                        required
                        autofocus
                        placeholder="nama@email.com atau username"
                        class="glass-input w-full px-3.5 py-2.5 text-sm"
                        data-cy="input-login"
                    >
                </div>
            </div>

            <div>
                <label for="password" class="glass-label">Password</label>
                <div class="relative">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="••••••••"
                        class="glass-input w-full px-3.5 py-2.5 text-sm"
                        data-cy="input-password"
                    >
                </div>
            </div>

            <div class="flex items-center justify-between text-sm pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30" data-cy="checkbox-remember">
                    <span class="text-slate-600 text-xs">Ingat saya di perangkat ini</span>
                </label>
            </div>

            <button type="submit" class="glass-btn-primary w-full py-2.5 sm:py-3 text-sm mt-2" data-cy="btn-login">
                <span>Masuk Sekarang</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>

        <div class="text-center mt-6 text-sm text-slate-600 pt-2 border-t border-slate-200/60">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-700 transition">
                Daftar Akun Baru
            </a>
        </div>
    </div>
</div>
@endsection
