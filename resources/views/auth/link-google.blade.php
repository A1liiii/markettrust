@extends('layouts.app')

@section('title', 'Tautkan Akun Google - MarketTrust')

@section('content')
<div class="min-h-[calc(100vh-16rem)] flex items-center justify-center py-10 sm:py-16 px-4 sm:px-6 lg:px-8">
    <div class="glass-panel max-w-md w-full p-6 sm:p-8 relative">
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 mb-3 border border-amber-500/20 shadow-xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Verifikasi Kepemilikan Akun</h2>
            <p class="text-sm text-slate-600 mt-1.5 leading-relaxed">
                Email Google <span class="font-semibold text-slate-900">{{ $pending['email'] }}</span> cocok dengan akun lokal <span class="font-semibold text-indigo-600">@<span>{{ $pending['username'] }}</span></span>.
            </p>
        </div>

        <!-- Security Notice -->
        <div class="glass-alert glass-alert-warning mb-6">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="text-xs text-amber-900 leading-relaxed">
                <strong class="font-semibold block mb-0.5">Perlindungan Keamanan Akun:</strong>
                Untuk mencegah penggabungan akun tanpa izin, masukkan password akun lokal Anda untuk mengonfirmasi penautan akun Google ini.
            </div>
        </div>

        <!-- Verification Form -->
        <form action="{{ route('auth.google.link.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="password" class="glass-label">
                    Password Akun Lokal (@<span>{{ $pending['username'] }}</span>) <span class="text-rose-500">*</span>
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autofocus
                    placeholder="Masukkan password akun Anda"
                    class="glass-input w-full px-3.5 py-2.5 text-sm"
                >
            </div>

            <button type="submit" class="glass-btn-primary w-full py-2.5 sm:py-3 text-sm mt-3">
                <span>Verifikasi & Tautkan Akun Google</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </button>
        </form>

        <div class="text-center mt-6 pt-2 border-t border-slate-200/60">
            <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-slate-800 transition">
                &larr; Batal dan kembali ke halaman login
            </a>
        </div>
    </div>
</div>
@endsection
