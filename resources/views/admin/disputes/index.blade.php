@extends('layouts.admin')

@section('title', 'Pusat Sengketa & Komplain - Panel Administrasi')

@section('content')
<div class="py-8 sm:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                    <span>Panel Administrasi</span>
                    <span>&bull;</span>
                    <span>Pengawasan Sengketa & Escrow</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Penyelesaian Sengketa & Komplain
                </h1>
                <p class="text-sm text-slate-600 mt-1 max-w-2xl">
                    Investigasi sengketa transaksi produk digital, peninjauan bukti dua pihak, dan penetapan keputusan payout atau refund (UC-18 / KF-09 / KF-10).
                </p>
            </div>

            <div class="shrink-0 flex items-center gap-2.5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                    <span>Modul Tahap Lanjutan</span>
                </span>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-xs">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Stats Metric Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="glass-card p-4 sm:p-5 border-slate-200/80 bg-white/70 space-y-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Sengketa</span>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ $stats['total'] }}</div>
                <span class="text-[11px] text-slate-400">Seluruh laporan komplain</span>
            </div>

            <div class="glass-card p-4 sm:p-5 border-amber-200/80 bg-amber-50/40 space-y-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Tenggat Penjual (OPEN)</span>
                <div class="text-2xl sm:text-3xl font-extrabold text-amber-800">{{ $stats['open'] }}</div>
                <span class="text-[11px] text-amber-600">Menunggu respons 24 jam</span>
            </div>

            <div class="glass-card p-4 sm:p-5 border-purple-200/80 bg-purple-50/40 space-y-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700">Investigasi (REVIEWING)</span>
                <div class="text-2xl sm:text-3xl font-extrabold text-purple-800">{{ $stats['reviewing'] }}</div>
                <span class="text-[11px] text-purple-600">Permintaan bukti tambahan</span>
            </div>

            <div class="glass-card p-4 sm:p-5 border-emerald-200/80 bg-emerald-50/40 space-y-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Telah Diputuskan</span>
                <div class="text-2xl sm:text-3xl font-extrabold text-emerald-800">{{ $stats['resolved'] }}</div>
                <span class="text-[11px] text-emerald-600">Settlement siap diproses</span>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="glass-card p-4 sm:p-5 border-white/80 bg-white/70 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <!-- Filter Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                <a
                    href="{{ route('admin.disputes.index', ['status' => 'ALL', 'search' => $search]) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition shrink-0 {{ $statusFilter === 'ALL' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
                >
                    Semua ({{ $stats['total'] }})
                </a>
                <a
                    href="{{ route('admin.disputes.index', ['status' => 'OPEN', 'search' => $search]) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition shrink-0 {{ $statusFilter === 'OPEN' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
                >
                    OPEN ({{ $stats['open'] }})
                </a>
                <a
                    href="{{ route('admin.disputes.index', ['status' => 'REVIEWING', 'search' => $search]) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition shrink-0 {{ $statusFilter === 'REVIEWING' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
                >
                    REVIEWING ({{ $stats['reviewing'] }})
                </a>
                <a
                    href="{{ route('admin.disputes.index', ['status' => 'RESOLVED', 'search' => $search]) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition shrink-0 {{ $statusFilter === 'RESOLVED' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
                >
                    RESOLVED ({{ $stats['resolved'] }})
                </a>
            </div>

            <!-- Search Form -->
            <form action="{{ route('admin.disputes.index') }}" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="relative w-full sm:w-64">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari ID, pelapor, item..."
                        class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 bg-white transition"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="glass-btn-secondary px-3 py-1.5 text-xs font-semibold">
                    Cari
                </button>
                @if ($search !== '')
                    <a href="{{ route('admin.disputes.index', ['status' => $statusFilter]) }}" class="text-xs text-rose-600 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <!-- Disputes Table -->
        <div class="glass-panel overflow-hidden border border-white/90 shadow-md">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-4 sm:px-6">Transaksi & Item</th>
                            <th class="py-3.5 px-4">Pihak Berperkara</th>
                            <th class="py-3.5 px-4">Status & Tahap</th>
                            <th class="py-3.5 px-4">Nilai Escrow</th>
                            <th class="py-3.5 px-4">Waktu Lapor</th>
                            <th class="py-3.5 px-4 sm:px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white/60">
                        @forelse ($complaints as $complaint)
                            @php
                                $tx = $complaint->transaction;
                                $agreement = $tx?->agreement;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition" data-cy="dispute-row-{{ $complaint->complaint_id }}">
                                <td class="py-4 px-4 sm:px-6">
                                    <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                        <span>Transaksi #{{ $complaint->transaction_id }}</span>
                                        <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-700 font-semibold">
                                            {{ $tx?->source_type->value ?? 'ESCROW' }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-600 mt-0.5 line-clamp-1 max-w-xs font-medium">
                                        {{ $agreement?->item_title ?? '-' }}
                                    </div>
                                </td>

                                <td class="py-4 px-4">
                                    <div class="space-y-0.5">
                                        <div class="text-xs">
                                            <span class="text-[10px] font-bold uppercase text-amber-700">Pelapor:</span>
                                            <strong class="text-slate-900">@<span>{{ $complaint->raisedBy?->username }}</span></strong>
                                        </div>
                                        <div class="text-xs">
                                            <span class="text-[10px] font-bold uppercase text-slate-400">Penjual:</span>
                                            <span class="text-slate-700">@<span>{{ $agreement?->seller?->username }}</span></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4">
                                    @if ($complaint->status === 'OPEN')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            OPEN (Tenggat Seller)
                                        </span>
                                    @elseif ($complaint->status === 'REVIEWING')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                            REVIEWING (Bukti Tambahan)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            RESOLVED ({{ $complaint->decision }})
                                        </span>
                                    @endif

                                    <div class="text-[10px] text-slate-500 mt-1">
                                        Tanggapan Seller: <strong>{{ $complaint->responses->count() }}</strong>
                                    </div>
                                </td>

                                <td class="py-4 px-4 font-bold text-slate-900">
                                    Rp {{ number_format($agreement?->buyer_total ?? 0, 0, ',', '.') }}
                                </td>

                                <td class="py-4 px-4 text-xs text-slate-500">
                                    {{ $complaint->created_at->diffForHumans() }}
                                    <span class="block text-[10px] text-slate-400">{{ $complaint->created_at->format('d/m/Y H:i') }}</span>
                                </td>

                                <td class="py-4 px-4 sm:px-6 text-right">
                                    <a
                                        href="{{ route('admin.disputes.show', $complaint) }}"
                                        class="glass-btn-primary px-3 py-1.5 text-xs font-bold inline-flex items-center gap-1 shadow-xs hover:scale-102 transition"
                                        data-cy="btn-investigate-dispute-{{ $complaint->complaint_id }}"
                                    >
                                        <span>Investigasi</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 px-4 text-center">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-xl font-bold">
                                            ⚖️
                                        </div>
                                        <p class="font-bold text-slate-800 text-sm">Tidak ada sengketa yang sesuai kriteria.</p>
                                        <p class="text-xs text-slate-500">Seluruh transaksi saat ini berjalan normal tanpa laporan komplain aktif.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($complaints->hasPages())
                <div class="p-4 border-t border-slate-200/80 bg-white/40">
                    {{ $complaints->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

