@extends('layouts.admin')

@section('title', $title . ' - Panel Administrasi')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb & Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                    <span>Panel Administrasi</span>
                    <span>&bull;</span>
                    <span>Modul Internal</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $title }}
                </h1>
                <p class="text-sm text-slate-600 mt-1 max-w-2xl">
                    {{ $description }}
                </p>
            </div>

            <div>
                <a href="{{ route('admin.catalog.index') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-3.5 flex items-center gap-1.5 shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 00-2-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>Buka Kelola Katalog</span>
                </a>
            </div>
        </div>

        <!-- Main Placeholder Surface Card -->
        <div class="glass-card p-8 sm:p-12 text-center max-w-3xl mx-auto border border-white/80 shadow-md">
            <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold mx-auto mb-5 border border-indigo-500/20 shadow-xs">
                @if ($icon === 'banknotes')
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                @elseif ($icon === 'scale')
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                @elseif ($icon === 'users')
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                @else
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                @endif
            </div>

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-semibold mb-3">
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                Modul Tahap Lanjutan
            </div>

            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight mb-2">
                {{ $title }}
            </h2>

            <p class="text-sm text-slate-600 leading-relaxed max-w-md mx-auto mb-6">
                Rute dan antarmuka modul ini telah siap pada panel navigasi admin. Fungsionalitas data operasional akan aktif pada rilis integrasi tahap berikutnya.
            </p>

            <div class="flex items-center justify-center gap-3">
                <a href="{{ route('admin.catalog.index') }}" class="glass-btn-primary text-xs sm:text-sm py-2 px-4 shadow-sm">
                    Kelola Katalog Digital
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
