@extends('layouts.app')

@section('title', 'Daftar Akun MarketTrust')
@section('hide_footer', true)

@section('content')
<div class="h-[calc(100vh-4rem)] flex items-center justify-center p-3 sm:p-4 overflow-hidden">
    <div class="glass-panel max-w-lg w-full p-5 sm:p-6 lg:py-4.5 lg:px-7 relative shadow-xl">
        <!-- Header -->
        <div class="text-center mb-3">
            <div class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-600 mb-1.5 border border-indigo-500/20 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <h2 class="text-xl lg:text-2xl font-bold tracking-tight text-slate-900">Buat Akun MarketTrust</h2>
            <p class="text-xs text-slate-600 mt-0.5">Daftar untuk mulai jual-beli item digital secara aman</p>
        </div>

        <!-- Google OAuth Button -->
        <div class="mb-4">
            <a href="{{ route('auth.google') }}" class="glass-btn-google w-full py-2 text-xs sm:text-sm">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Daftar dengan Google</span>
            </a>
        </div>

        <div class="relative flex items-center justify-center my-4">
            <div class="glass-divider w-full"></div>
            <span class="glass-badge glass-badge-neutral px-3 py-0.5 uppercase text-[9px] tracking-wider absolute">Atau form pendaftaran</span>
        </div>

        <!-- Registration Form -->
        <form action="{{ route('register') }}" method="POST" class="space-y-3 mt-4" data-cy="register-form">
            @csrf

            <!-- Grid Baris 1: Username & Email -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="username" class="glass-label text-xs">
                        Username <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs font-semibold">@</span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            placeholder="contoh_user"
                            class="glass-input w-full pl-7 pr-3 py-2 text-xs sm:text-sm"
                            data-cy="input-username"
                        >
                    </div>
                </div>

                <div>
                    <label for="email" class="glass-label text-xs">
                        Alamat Email <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        placeholder="nama@email.com"
                        class="glass-input w-full px-3 py-2 text-xs sm:text-sm"
                        data-cy="input-email"
                    >
                </div>
            </div>

            <!-- Grid Baris 2: Password & Konfirmasi -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="password" class="glass-label text-xs">
                        Password <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="Min. 8 karakter"
                        class="glass-input w-full px-3 py-2 text-xs sm:text-sm"
                        data-cy="input-password"
                    >
                </div>

                <div>
                    <label for="password_confirmation" class="glass-label text-xs">
                        Konfirmasi Password <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        placeholder="Ulangi password"
                        class="glass-input w-full px-3 py-2 text-xs sm:text-sm"
                        data-cy="input-password-confirmation"
                    >
                </div>
            </div>

            <button type="submit" class="glass-btn-primary w-full py-2 sm:py-2.5 text-xs sm:text-sm mt-1" data-cy="btn-register">
                <span>Daftar Akun Baru</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>

        <div class="text-center mt-3 text-xs sm:text-sm text-slate-600 pt-2 border-t border-slate-200/60">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700 transition">
                Masuk di sini
            </a>
        </div>
    </div>
</div>
@endsection
