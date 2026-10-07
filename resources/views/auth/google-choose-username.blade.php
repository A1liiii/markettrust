@extends('layouts.app')

@section('title', 'Lengkapi Username Google - MarketTrust')

@section('content')
<div class="min-h-[calc(100vh-16rem)] flex items-center justify-center py-10 sm:py-16 px-4 sm:px-6 lg:px-8">
    <div class="glass-panel max-w-md w-full p-6 sm:p-8 relative">
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 mb-3 border border-indigo-500/20 shadow-xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7 7z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Satu Langkah Lagi!</h2>
            <p class="text-sm text-slate-600 mt-1.5 leading-relaxed">
                Akun Google Anda berhasil diverifikasi. Tentukan username unik Anda untuk identitas publik di MarketTrust.
            </p>
        </div>

        <!-- Google Info Badge -->
        <div class="glass-card p-3.5 sm:p-4 rounded-xl flex items-center gap-3.5 mb-6 border border-white/80 shadow-xs">
            @if (!empty($pending['avatar_url']))
                <img src="{{ $pending['avatar_url'] }}" alt="Google Avatar" class="w-11 h-11 rounded-full object-cover shadow-xs ring-2 ring-white/80">
            @else
                <div class="w-11 h-11 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    G
                </div>
            @endif
            <div class="overflow-hidden">
                <span class="glass-badge glass-badge-primary text-[10px] py-0.5 px-2 mb-1">Akun Google Terhubung</span>
                <p class="text-sm font-semibold text-slate-800 truncate">{{ $pending['email'] }}</p>
            </div>
        </div>

        <!-- Username Form -->
        <form action="{{ route('auth.google.username.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="glass-label">
                    Pilih Username Anda <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm font-semibold">@</span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="{{ old('username', $pending['suggested_username'] ?? '') }}"
                        required
                        autofocus
                        placeholder="username_anda"
                        class="glass-input w-full pl-8 pr-3.5 py-2.5 text-sm"
                    >
                </div>
                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                    3–30 karakter huruf kecil, angka, atau underscore (_). Username ini akan terlihat di marketplace, forum, dan chat DM.
                </p>
            </div>

            <button type="submit" class="glass-btn-primary w-full py-2.5 sm:py-3 text-sm mt-3">
                <span>Selesaikan Pendaftaran</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
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
