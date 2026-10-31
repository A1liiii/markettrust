@extends('layouts.admin')

@section('title', 'Audit Log & Jejak Operasional Sistem - Panel Administrasi')

@section('content')
<div class="py-8 sm:py-12" data-cy="admin-audit-logs-page">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Header & Breadcrumb -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                    <a href="{{ route('admin.dashboard') }}" class="hover:underline">Panel Administrasi</a>
                    <span>&bull;</span>
                    <span>Keamanan &amp; Pengawasan</span>
                    <span>&bull;</span>
                    <span>Jejak Audit Operasional (UC-21 &bull; KF-10)</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight" data-cy="audit-logs-title">
                    Audit Log &amp; Jejak Aktivitas Sistem
                </h1>
                <p class="text-sm text-slate-600 mt-1 max-w-3xl">
                    Rekam jejak seluruh keputusan administratif, perubahan status finansial, peninjauan pembatalan transaksi, dan aktivitas otomatisasi sistem untuk keperluan audit dan investigasi sengketa.
                </p>
            </div>

            <div class="shrink-0 flex items-center gap-2.5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                    <span>Modul Tahap Lanjutan</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200/80 text-xs font-bold text-indigo-800 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                    <span>Audit Trail Aktif</span>
                </span>
            </div>
        </div>

        <!-- Quick Metrics / Stats Bar -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="glass-card p-4 rounded-2xl border border-white/80 shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/15 text-indigo-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Total Log Tercatat</span>
                    <span class="text-lg font-black text-slate-900" data-cy="stat-total-logs">{{ number_format($auditLogs->total(), 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="glass-card p-4 rounded-2xl border border-white/80 shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Aksi Finansial</span>
                    <span class="text-lg font-black text-slate-900">Settlement & KEEP</span>
                </div>
            </div>

            <div class="glass-card p-4 rounded-2xl border border-white/80 shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/15 text-rose-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Tinjauan Pembatalan</span>
                    <span class="text-lg font-black text-slate-900">Post-Payment</span>
                </div>
            </div>

            <div class="glass-card p-4 rounded-2xl border border-white/80 shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/15 text-purple-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Proses Sistem</span>
                    <span class="text-lg font-black text-slate-900">Auto-Confirm & SLA</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Card -->
        <div class="glass-panel p-5 rounded-2xl border border-white/90 shadow-sm">
            <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="space-y-4" data-cy="audit-filter-form">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Search Input -->
                    <div class="lg:col-span-2 space-y-1">
                        <label for="search" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Pencarian Kata Kunci / ID
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                name="search"
                                id="search"
                                value="{{ $currentSearch }}"
                                placeholder="Cari nama aksi, tipe entitas, ID, atau aktor..."
                                class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-white/90 border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition placeholder:text-slate-400"
                                data-cy="audit-search-input"
                            >
                        </div>
                    </div>

                    <!-- Filter Action Dropdown -->
                    <div class="space-y-1">
                        <label for="action" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Filter Jenis Aksi
                        </label>
                        <select
                            name="action"
                            id="action"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-white/90 border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition"
                            data-cy="audit-action-filter"
                        >
                            <option value="">Semua Aksi</option>
                            @foreach ($availableActions as $action)
                                <option value="{{ $action }}" {{ $currentAction === $action ? 'selected' : '' }}>
                                    {{ $action }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Entity Type Dropdown -->
                    <div class="space-y-1">
                        <label for="entity_type" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Tipe Entitas
                        </label>
                        <select
                            name="entity_type"
                            id="entity_type"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-white/90 border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition"
                            data-cy="audit-entity-filter"
                        >
                            <option value="">Semua Entitas</option>
                            @foreach ($availableEntityTypes as $entity)
                                <option value="{{ $entity }}" {{ $currentEntityType === $entity ? 'selected' : '' }}>
                                    {{ $entity }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date Range / Action Buttons -->
                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="glass-btn-primary w-full py-2 px-3 text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm shadow-indigo-500/20 active:scale-95 transition"
                            data-cy="btn-submit-filter"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span>Terapkan</span>
                        </button>

                        @if ($currentSearch || $currentAction || $currentEntityType || $currentDateFrom || $currentDateTo)
                            <a
                                href="{{ route('admin.audit-logs.index') }}"
                                class="glass-btn-secondary py-2 px-3 text-xs font-semibold shrink-0 text-slate-600 hover:text-slate-900 transition"
                                title="Reset Filter"
                                data-cy="btn-reset-filter"
                            >
                                Reset
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Date Range Filters (Accordion / Secondary Row) -->
                <div class="pt-2 border-t border-slate-200/60 flex flex-wrap items-center gap-4 text-xs text-slate-600">
                    <span class="font-bold text-[10px] uppercase tracking-wider text-slate-400">Rentang Tanggal:</span>
                    <div class="flex items-center gap-2">
                        <label for="date_from" class="text-[11px] text-slate-500">Dari:</label>
                        <input
                            type="date"
                            name="date_from"
                            id="date_from"
                            value="{{ $currentDateFrom }}"
                            class="px-2.5 py-1 text-xs rounded-lg bg-white/90 border border-slate-200 focus:border-indigo-400"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="date_to" class="text-[11px] text-slate-500">Sampai:</label>
                        <input
                            type="date"
                            name="date_to"
                            id="date_to"
                            value="{{ $currentDateTo }}"
                            class="px-2.5 py-1 text-xs rounded-lg bg-white/90 border border-slate-200 focus:border-indigo-400"
                        >
                    </div>
                </div>
            </form>
        </div>

        <!-- Audit Log Table Card -->
        <div class="glass-panel overflow-hidden rounded-2xl border border-white/90 shadow-md">
            <div class="p-5 border-b border-slate-200/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-base font-bold text-slate-900">
                        Catatan Jejak Aktivitas Terperinci
                    </h2>
                </div>
                <span class="text-xs text-slate-500 font-medium">
                    Menampilkan {{ $auditLogs->firstItem() ?? 0 }} - {{ $auditLogs->lastItem() ?? 0 }} dari {{ $auditLogs->total() }} entri
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                            <th class="py-3 px-4">Waktu (WIB)</th>
                            <th class="py-3 px-4">Pelaku / Aktor</th>
                            <th class="py-3 px-4">Aksi Audit</th>
                            <th class="py-3 px-4">Target Entitas</th>
                            <th class="py-3 px-4">Ringkasan Perubahan</th>
                            <th class="py-3 px-4 text-right">Data Payload</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" data-cy="audit-table-body">
                        @forelse ($auditLogs as $log)
                            @php
                                $action = (string) $log->action;
                                $badgeStyle = match(true) {
                                    str_contains($action, 'PAYOUT') => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    str_contains($action, 'REFUND') => 'bg-sky-50 text-sky-800 border-sky-200',
                                    str_contains($action, 'CANCEL') => 'bg-rose-50 text-rose-800 border-rose-200',
                                    str_contains($action, 'AUTO_CONFIRMED') || str_contains($action, 'SYSTEM') => 'bg-purple-50 text-purple-800 border-purple-200',
                                    str_contains($action, 'DEADLINE') => 'bg-amber-50 text-amber-800 border-amber-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <tr class="hover:bg-indigo-50/20 transition-colors" data-cy="audit-log-row-{{ $log->log_id }}">
                                <!-- Waktu -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">
                                        {{ $log->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $log->created_at?->diffForHumans() }}
                                    </div>
                                </td>

                                <!-- Aktor -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if ($log->actor)
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-indigo-500 to-indigo-600 text-white flex items-center justify-center font-black text-[10px] shrink-0">
                                                {{ strtoupper(substr($log->actor->username ?? 'A', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800">
                                                    {{ $log->actor->username }}
                                                </div>
                                                <span class="inline-block text-[9px] px-1 rounded bg-slate-100 text-slate-600 font-semibold">
                                                    {{ $log->actor->account_type ?? 'USER' }}
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1.5 text-purple-700 font-bold">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                            <span class="text-[11px]">Sistem Otomatis</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-1 rounded-lg text-[10px] font-black border uppercase tracking-wide {{ $badgeStyle }}" data-cy="badge-action">
                                        {{ $log->action }}
                                    </span>
                                </td>

                                <!-- Target Entitas -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-800">
                                        {{ ucfirst(str_replace('_', ' ', $log->entity_type)) }}
                                    </div>
                                    <div class="text-[10px] text-slate-500">
                                        ID #{{ $log->entity_id }}
                                    </div>
                                </td>

                                <!-- Ringkasan Perubahan -->
                                <td class="py-3.5 px-4 max-w-xs sm:max-w-md truncate">
                                    @php
                                        $old = is_array($log->old_value) ? $log->old_value : json_decode((string) $log->old_value, true);
                                        $new = is_array($log->new_value) ? $log->new_value : json_decode((string) $log->new_value, true);
                                    @endphp
                                    <div class="text-[11px] text-slate-600 space-y-0.5 truncate">
                                        @if ($old)
                                            <span class="text-rose-600 font-medium line-through">
                                                {{ Str::limit(json_encode($old, JSON_UNESCAPED_SLASHES), 40) }}
                                            </span>
                                            <span class="text-slate-400 mx-1">&rarr;</span>
                                        @endif
                                        @if ($new)
                                            <span class="text-emerald-700 font-bold">
                                                {{ Str::limit(json_encode($new, JSON_UNESCAPED_SLASHES), 50) }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Tombol Modal Detail JSON -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        onclick="inspectAuditLog({{ $log->log_id }})"
                                        class="glass-btn-secondary px-2.5 py-1 text-[11px] font-bold text-indigo-700 hover:text-indigo-900 transition flex items-center gap-1 ml-auto cursor-pointer"
                                        data-cy="btn-inspect-payload-{{ $log->log_id }}"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>Inspeksi</span>
                                    </button>

                                    <!-- Hidden Payload Template for Modal -->
                                    <div id="audit-payload-{{ $log->log_id }}" class="hidden">
                                        <div data-action="{{ $log->action }}"></div>
                                        <div data-entity="{{ $log->entity_type }} #{{ $log->entity_id }}"></div>
                                        <div data-actor="{{ $log->actor ? ($log->actor->username . ' (' . ($log->actor->account_type ?? 'USER') . ')') : 'Sistem Otomatis (MarketTrust Scheduler)' }}"></div>
                                        <div data-timestamp="{{ $log->created_at?->translatedFormat('d F Y, H:i:s') }} WIB"></div>
                                        <pre data-old>{{ json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'null' }}</pre>
                                        <pre data-new>{{ json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'null' }}</pre>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Tidak ada jejak audit yang ditemukan</p>
                                        <p class="text-xs text-slate-400 max-w-sm">
                                            Tidak ada riwayat aktivitas yang sesuai dengan kriteria filter atau pencarian Anda.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            @if ($auditLogs->hasPages())
                <div class="p-4 border-t border-slate-200/60 bg-white/50" data-cy="audit-pagination">
                    {{ $auditLogs->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Modal Dialog Inspeksi JSON Payload -->
<div
    id="audit-inspect-modal"
    class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-200"
    onclick="closeAuditInspectModal()"
    data-cy="audit-inspect-modal"
>
    <div
        class="p-6 max-w-2xl w-full bg-white shadow-2xl rounded-2xl border border-slate-100 relative text-left animate-in fade-in zoom-in-95 duration-150 space-y-4 max-h-[90vh] flex flex-col"
        onclick="event.stopPropagation()"
    >
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900" id="modal-audit-action">Detail Payload Log</h3>
                    <p class="text-xs text-slate-500" id="modal-audit-entity"></p>
                </div>
            </div>
            <button
                type="button"
                onclick="closeAuditInspectModal()"
                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Meta info -->
        <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 p-3 rounded-xl border border-slate-100">
            <div>
                <span class="block text-[10px] uppercase font-bold text-slate-400">Aktor Pelaksana</span>
                <span id="modal-audit-actor" class="font-bold text-slate-800"></span>
            </div>
            <div>
                <span class="block text-[10px] uppercase font-bold text-slate-400">Waktu Eksekusi</span>
                <span id="modal-audit-timestamp" class="font-semibold text-slate-700"></span>
            </div>
        </div>

        <!-- Scrollable JSON payload sections -->
        <div class="flex-1 overflow-y-auto space-y-3 pr-1 text-xs">
            <div>
                <span class="block text-[10px] uppercase font-bold text-rose-600 mb-1">Old Value (Sebelum Perubahan):</span>
                <pre id="modal-audit-old" class="p-3 rounded-xl bg-rose-50/60 border border-rose-100 text-slate-800 font-mono text-[11px] overflow-x-auto whitespace-pre-wrap leading-relaxed"></pre>
            </div>
            <div>
                <span class="block text-[10px] uppercase font-bold text-emerald-700 mb-1">New Value (Setelah Perubahan):</span>
                <pre id="modal-audit-new" class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-100 text-slate-800 font-mono text-[11px] overflow-x-auto whitespace-pre-wrap leading-relaxed"></pre>
            </div>
        </div>

        <!-- Footer -->
        <div class="pt-3 border-t border-slate-100 flex justify-end">
            <button
                type="button"
                onclick="closeAuditInspectModal()"
                class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
            >
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function inspectAuditLog(logId) {
        const container = document.getElementById('audit-payload-' + logId);
        if (!container) return;

        const action = container.querySelector('[data-action]')?.getAttribute('data-action') || '';
        const entity = container.querySelector('[data-entity]')?.getAttribute('data-entity') || '';
        const actor = container.querySelector('[data-actor]')?.getAttribute('data-actor') || '';
        const timestamp = container.querySelector('[data-timestamp]')?.getAttribute('data-timestamp') || '';
        const oldVal = container.querySelector('[data-old]')?.innerText || 'null';
        const newVal = container.querySelector('[data-new]')?.innerText || 'null';

        document.getElementById('modal-audit-action').textContent = action;
        document.getElementById('modal-audit-entity').textContent = entity;
        document.getElementById('modal-audit-actor').textContent = actor;
        document.getElementById('modal-audit-timestamp').textContent = timestamp;
        document.getElementById('modal-audit-old').textContent = oldVal;
        document.getElementById('modal-audit-new').textContent = newVal;

        const modal = document.getElementById('audit-inspect-modal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    }

    function closeAuditInspectModal() {
        const modal = document.getElementById('audit-inspect-modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAuditInspectModal();
        }
    });
</script>
@endsection
