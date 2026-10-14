@extends('layouts.admin')

@section('title', 'Overview & Analitik Platform')

@section('content')
<div class="py-8 sm:py-10 px-4 sm:px-6 lg:px-8 space-y-8 max-w-7xl mx-auto">

    <!-- Dashboard Header & Interactive Period Filter -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-6 border-b border-white/60">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                <span>Panel Administrasi</span>
                <span>&bull;</span>
                <span>Pusat Kendali</span>
                <span>&bull;</span>
                <span class="text-slate-500">Overview Analitik</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Overview & Analitik Platform
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-1">
                Monitoring kesehatan transaksi rekber, antrean penanganan admin, volume dana escrow, dan ekosistem MarketTrust.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Period Selector Filter Buttons (7D, 30D, 90D, Semua) -->
            <div class="inline-flex p-1 rounded-2xl bg-white/70 border border-slate-200/80 shadow-2xs text-xs font-semibold text-slate-600">
                <button type="button" data-period="7d" class="period-btn px-3 py-1.5 rounded-xl transition {{ $currentPeriod === '7d' ? 'bg-indigo-600 text-white shadow-xs' : 'hover:text-slate-900 hover:bg-white' }}">
                    7 Hari
                </button>
                <button type="button" data-period="30d" class="period-btn px-3 py-1.5 rounded-xl transition {{ $currentPeriod === '30d' ? 'bg-indigo-600 text-white shadow-xs' : 'hover:text-slate-900 hover:bg-white' }}">
                    30 Hari
                </button>
                <button type="button" data-period="90d" class="period-btn px-3 py-1.5 rounded-xl transition {{ $currentPeriod === '90d' ? 'bg-indigo-600 text-white shadow-xs' : 'hover:text-slate-900 hover:bg-white' }}">
                    90 Hari
                </button>
                <button type="button" data-period="all" class="period-btn px-3 py-1.5 rounded-xl transition {{ $currentPeriod === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'hover:text-slate-900 hover:bg-white' }}">
                    Semua
                </button>
            </div>

            <!-- Quick Action Links -->
            <a href="{{ route('admin.catalog.index') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-3.5 flex items-center gap-1.5 shadow-2xs" title="Buka Pengelolaan Katalog Digital">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Kelola Katalog</span>
            </a>
            <a href="{{ route('admin.transactions.index') }}" class="glass-btn-primary text-xs sm:text-sm py-2 px-3.5 flex items-center gap-1.5 shadow-sm" title="Tinjau Seluruh Transaksi Escrow">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span>Tinjau Escrow</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- A. KARTU RINGKASAN (7 METRIK STATUS TRANSAKSI - CLICKABLE)               -->
    <!-- ========================================================================= -->
    <div>
        <div class="flex items-center justify-between mb-3.5">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Kartu Ringkasan Status Transaksi
                </h2>
                <p class="text-[11px] text-slate-400">Klik kartu untuk membuka daftar data terkait secara langsung</p>
            </div>
            <span class="glass-badge glass-badge-neutral text-xs">
                Total: {{ $totalTransactions }} Transaksi
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 sm:gap-4">
            <!-- 1. Transaksi Aktif -->
            <a href="{{ route('admin.transactions.index') }}?status=ACTIVE_ESCROW" class="glass-card p-4 border border-white/80 hover:border-indigo-300 hover:shadow-md transition group block" title="Lihat transaksi yang sedang berjalan">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <span class="text-[10px] text-indigo-600 font-bold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                </div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ $activeEscrowTransactions }}</div>
                <div class="text-[11px] font-semibold text-slate-600 mt-0.5 truncate">Transaksi Aktif</div>
                <div class="text-[10px] text-slate-400 mt-0.5 truncate">Proses rekber berjalan</div>
            </a>

            <!-- 2. Menunggu Pembayaran -->
            <a href="{{ route('admin.transactions.index') }}?status=WAITING_PAYMENT" class="glass-card p-4 border border-white/80 hover:border-slate-300 hover:shadow-md transition group block" title="Lihat transaksi menunggu pembayaran buyer">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-7 h-7 rounded-lg bg-slate-500/10 text-slate-600 flex items-center justify-center font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                </div>
                <div class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">{{ $waitingPaymentTransactions }}</div>
                <div class="text-[11px] font-semibold text-slate-600 mt-0.5 truncate">Menunggu Bayar</div>
                <div class="text-[10px] text-slate-400 mt-0.5 truncate">Menanti Midtrans</div>
            </a>

            <!-- 3. Menunggu Penyerahan Produk -->
            <a href="{{ route('admin.transactions.index') }}?status=PAID" class="glass-card p-4 border border-white/80 hover:border-amber-300 hover:shadow-md transition group block" title="Lihat transaksi menunggu seller menyerahkan produk">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <span class="text-[10px] text-amber-600 font-bold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                </div>
                <div class="text-xl sm:text-2xl font-black text-amber-600 tracking-tight">{{ $waitingDeliveryTransactions }}</div>
                <div class="text-[11px] font-semibold text-slate-600 mt-0.5 truncate">Menunggu Serah</div>
                <div class="text-[10px] text-slate-400 mt-0.5 truncate">Tenggat seller aktif</div>
            </a>

            <!-- 4. Menunggu Konfirmasi Buyer -->
            <a href="{{ route('admin.transactions.index') }}?status=DELIVERED" class="glass-card p-4 border border-white/80 hover:border-sky-300 hover:shadow-md transition group block" title="Lihat transaksi menunggu konfirmasi buyer">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-7 h-7 rounded-lg bg-sky-500/10 text-sky-600 flex items-center justify-center font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] text-sky-600 font-bold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                </div>
                <div class="text-xl sm:text-2xl font-black text-sky-600 tracking-tight">{{ $waitingConfirmationTransactions }}</div>
                <div class="text-[11px] font-semibold text-slate-600 mt-0.5 truncate">Menunggu Konfirmasi</div>
                <div class="text-[10px] text-slate-400 mt-0.5 truncate">Grace auto-payout 30m</div>
            </a>

            <!-- 5. Menunggu Payout Manual -->
            <a href="{{ route('admin.transactions.index') }}?status=CONFIRMED" class="glass-card p-4 border border-white/80 hover:border-emerald-300 hover:shadow-md transition group block" title="Lihat transaksi siap payout manual oleh admin">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <span class="text-[10px] text-emerald-600 font-bold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                </div>
                <div class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight">{{ $waitingPayoutTransactions }}</div>
                <div class="text-[11px] font-semibold text-slate-600 mt-0.5 truncate">Siap Payout</div>
                <div class="text-[10px] text-slate-400 mt-0.5 truncate">Perlu transfer admin</div>
            </a>

            <!-- 6. Komplain Aktif -->
            <a href="{{ route('admin.disputes.index') }}?status=OPEN" class="glass-card p-4 border border-white/80 hover:border-rose-300 hover:shadow-md transition group block" title="Lihat sengketa komplain yang perlu ditinjau">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-7 h-7 rounded-lg bg-rose-500/10 text-rose-600 flex items-center justify-center font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <span class="text-[10px] text-rose-600 font-bold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                </div>
                <div class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight">{{ $disputedTransactions }}</div>
                <div class="text-[11px] font-semibold text-slate-600 mt-0.5 truncate">Komplain Aktif</div>
                <div class="text-[10px] text-slate-400 mt-0.5 truncate">Investigasi bukti</div>
            </a>

            <!-- 7. Payout Gagal / Review -->
            <a href="{{ route('admin.transactions.index') }}?status=FAILED" class="glass-card p-4 border border-white/80 hover:border-purple-300 hover:shadow-md transition group block" title="Lihat payout atau transaksi bermasalah">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-7 h-7 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] text-purple-600 font-bold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                </div>
                <div class="text-xl sm:text-2xl font-black text-purple-700 tracking-tight">{{ $failedPayoutsTransactions }}</div>
                <div class="text-[11px] font-semibold text-slate-600 mt-0.5 truncate">Payout Gagal</div>
                <div class="text-[10px] text-slate-400 mt-0.5 truncate">Tinjauan rekening</div>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- B. ANTREAN TINDAKAN ADMIN (ACTION QUEUE - TENGGAT, PAYOUT, KOMPLAIN)      -->
    <!-- ========================================================================= -->
    <div class="glass-card p-6 border border-white/80 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-200/60">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">Antrean Tindakan Admin (Action Queue)</h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Daftar transaksi dan laporan mendesak yang memerlukan keputusan atau eksekusi admin</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="glass-badge {{ $totalActionQueueCount > 0 ? 'glass-badge-error' : 'glass-badge-success' }} text-xs">
                    {{ $totalActionQueueCount }} Tugas Mendesak
                </span>
            </div>
        </div>

        @if ($totalActionQueueCount === 0)
            <div class="p-8 text-center rounded-2xl bg-white/50 border border-dashed border-slate-200 space-y-2">
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="text-sm font-bold text-slate-900">Semua Antrean Bersih!</div>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Tidak ada komplain terbuka, tenggat waktu yang terancam, atau payout yang tertahan saat ini.</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                <!-- 1. Antrean Payout Siap Ditransfer -->
                <div class="p-4 rounded-2xl bg-white/60 border border-slate-200/70 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Payout Menunggu Transfer Admin ({{ $waitingPayoutActionList->count() }})
                        </span>
                        <a href="{{ route('admin.transactions.index') }}?status=CONFIRMED" class="text-[11px] text-emerald-700 font-semibold hover:underline">Kelola &rarr;</a>
                    </div>

                    @forelse ($waitingPayoutActionList as $tx)
                        <div class="p-3 rounded-xl bg-white/90 border border-slate-200/60 flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">#TRX-{{ $tx->transaction_id }}</span>
                                    <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-700">Dikonfirmasi</span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5 truncate">
                                    Seller: <strong class="text-slate-800">{{ '@' . ($tx->agreement?->seller?->username ?? 'seller') }}</strong> &bull; Net: <span class="font-bold text-slate-900">Rp {{ number_format($tx->agreement?->seller_net ?? 0, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <a href="{{ route('admin.transactions.index') }}?transaction_id={{ $tx->transaction_id }}" class="py-1 px-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition shrink-0 shadow-2xs">
                                Transfer &rarr;
                            </a>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-2">Tidak ada payout tertunda.</p>
                    @endforelse
                </div>

                <!-- 2. Antrean Komplain & Sengketa Terbuka -->
                <div class="p-4 rounded-2xl bg-white/60 border border-slate-200/70 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-700 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Sengketa Komplain Masuk ({{ $activeComplaints->count() }})
                        </span>
                        <a href="{{ route('admin.disputes.index') }}" class="text-[11px] text-rose-700 font-semibold hover:underline">Tinjau &rarr;</a>
                    </div>

                    @forelse ($activeComplaints as $c)
                        <div class="p-3 rounded-xl bg-white/90 border border-slate-200/60 flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">#CMP-{{ $c->complaint_id }}</span>
                                    <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full bg-rose-100 text-rose-700">{{ $c->status }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5 truncate">
                                    Pelapor: <strong class="text-slate-800">{{ '@' . ($c->raisedBy?->username ?? 'user') }}</strong> &bull; Alasan: "{{ Str::limit($c->reason, 30) }}"
                                </div>
                            </div>
                            <a href="{{ route('admin.disputes.index') }}?complaint_id={{ $c->complaint_id }}" class="py-1 px-2.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-[11px] transition shrink-0 shadow-2xs">
                                Investigasi &rarr;
                            </a>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-2">Tidak ada komplain aktif.</p>
                    @endforelse
                </div>

                <!-- 3. Transaksi Mendekati Tenggat Waktu (Delivery / Confirmation) -->
                @if ($urgentDeliveryDeadlines->isNotEmpty() || $urgentConfirmationDeadlines->isNotEmpty())
                    <div class="p-4 rounded-2xl bg-white/60 border border-slate-200/70 space-y-3 lg:col-span-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-700 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Peringatan Tenggat Waktu Berjalan (Countdown Timers)
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium">Batas Waktu Transaksi</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                            @foreach ($urgentDeliveryDeadlines as $tx)
                                @php
                                    $diffMinutes = now()->diffInMinutes($tx->delivery_due_at, false);
                                    $isExpired = $diffMinutes < 0;
                                @endphp
                                <div class="p-3 rounded-xl bg-white/90 border border-slate-200/60 flex items-center justify-between gap-3 text-xs">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900">#TRX-{{ $tx->transaction_id }}</span>
                                            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full {{ $isExpired ? 'bg-rose-100 text-rose-700' : ($diffMinutes < 60 ? 'bg-rose-50 text-rose-600' : 'bg-amber-100 text-amber-800') }}">
                                                {{ $isExpired ? 'Lewat Tenggat Penyerahan' : ($diffMinutes < 60 ? 'Sisa ' . $diffMinutes . ' Menit' : 'Sisa ' . round($diffMinutes/60, 1) . ' Jam') }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 truncate">
                                            Seller: <strong class="text-slate-800">{{ '@' . ($tx->agreement?->seller?->username ?? 'seller') }}</strong> wajib serahkan produk
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.transactions.index') }}?transaction_id={{ $tx->transaction_id }}" class="text-xs text-indigo-600 hover:underline font-semibold shrink-0">
                                        Lihat Detail &rarr;
                                    </a>
                                </div>
                            @endforeach

                            @foreach ($urgentConfirmationDeadlines as $tx)
                                @php
                                    $diffMinutes = now()->diffInMinutes($tx->confirmation_due_at, false);
                                    $isExpired = $diffMinutes < 0;
                                @endphp
                                <div class="p-3 rounded-xl bg-white/90 border border-slate-200/60 flex items-center justify-between gap-3 text-xs">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900">#TRX-{{ $tx->transaction_id }}</span>
                                            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full {{ $isExpired ? 'bg-purple-100 text-purple-700' : ($diffMinutes < 60 ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-800') }}">
                                                {{ $isExpired ? 'Siap Auto-Payout (Grace Period)' : 'Konfirmasi Sisa ' . ($diffMinutes < 60 ? $diffMinutes . ' Mnt' : round($diffMinutes/60, 1) . ' Jam') }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 truncate">
                                            Buyer: <strong class="text-slate-800">{{ '@' . ($tx->agreement?->buyer?->username ?? 'buyer') }}</strong> sedang meninjau pesanan
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.transactions.index') }}?transaction_id={{ $tx->transaction_id }}" class="text-xs text-indigo-600 hover:underline font-semibold shrink-0">
                                        Lihat Detail &rarr;
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- C. RINGKASAN FINANSIAL & PEMISAHAN DANA ESCROW VS FEE PLATFORM            -->
    <!-- ========================================================================= -->
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-4">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Kinerja Finansial & Pemisahan Dana
                </h2>
                <p class="text-[11px] text-slate-400">Pemisahan transparan antara dana titipan rekening bersama dan pendapatan operasional</p>
            </div>
            <span class="text-[11px] text-slate-500 font-medium bg-white/60 py-1 px-2.5 rounded-lg border border-slate-200/60 shadow-2xs">
                Audited Escrow Ledger
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <!-- 1. Gross Volume Escrow -->
            <div class="glass-card p-5 sm:p-6 border border-white/80 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Gross Escrow Volume</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        Rp {{ number_format($totalGrossVolume, 0, ',', '.') }}
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Total akumulasi seluruh nilai kesepakatan transaksi
                    </p>
                </div>
            </div>

            <!-- 2. Pendapatan Fee Platform -->
            <div class="glass-card p-5 sm:p-6 border border-white/80 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Pendapatan Fee Platform</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight">
                        Rp {{ number_format($totalFeeRevenue, 0, ',', '.') }}
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Hak pendapatan resmi MarketTrust dari transaksi selesai
                    </p>
                </div>
            </div>

            <!-- 3. Escrow Aktif (Berjalan) -->
            <div class="glass-card p-5 sm:p-6 border border-white/80 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Escrow Aktif (Berjalan)</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl sm:text-2xl font-black text-amber-600 tracking-tight">
                        Rp {{ number_format($activeEscrowVolume, 0, ',', '.') }}
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        {{ $inEscrowTransactions }} transaksi sedang tertahan aman
                    </p>
                </div>
            </div>

            <!-- 4. Payout Menunggu vs Payout Selesai -->
            <div class="glass-card p-5 sm:p-6 border border-white/80 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Payout Siap Transfer</span>
                    <div class="w-8 h-8 rounded-xl bg-sky-500/10 text-sky-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl sm:text-2xl font-black text-sky-600 tracking-tight">
                        Rp {{ number_format($waitingPayoutAmount, 0, ',', '.') }}
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Selesai ditransfer: Rp {{ number_format($completedPayoutAmount, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Banner Penjelasan Pemisahan Dana -->
        <div class="mt-3 p-3.5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-indigo-500/5 to-transparent border border-amber-200/60 text-xs text-amber-900 flex items-start gap-2.5">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="leading-relaxed">
                <strong>Prinsip Integritas Escrow MarketTrust:</strong> Dana yang berada di dalam status Escrow Aktif (Rp {{ number_format($activeEscrowVolume, 0, ',', '.') }}) adalah <em>dana amanah titipan pihak bertransaksi</em> yang tersimpan di rekening escrow penampung Midtrans dan secara sistematis dipisahkan dari Pendapatan Fee Platform (Rp {{ number_format($totalFeeRevenue, 0, ',', '.') }}). Dana baru dicairkan ke seller setelah buyer mengonfirmasi penerimaan produk atau melalui auto-payout resmi.
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- D. ANALITIK & 5 CHART INTERAKTIF DENGAN FILTER RENTANG WAKTU              -->
    <!-- ========================================================================= -->
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Analitik & Grafik Interaktif
                </h2>
                <p class="text-[11px] text-slate-400">Visualisasi data berkala dengan sinkronisasi filter waktu real-time</p>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Data Mode: <strong id="current-period-label" class="text-indigo-600 uppercase">{{ $currentPeriod }}</strong></span>
            </div>
        </div>

        <!-- Chart Grid 1: Tren Transaksi & GMV (Dual Axis Area/Line) -->
        <div class="glass-card p-6 border border-white/80 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-200/60">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight">1. Tren Transaksi & Gross Escrow Volume (GMV)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Frekuensi transaksi harian (axis kiri) dan total nominal perputaran Rupiah (axis kanan)</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1.5 text-indigo-600 font-semibold"><span class="w-3 h-1 bg-indigo-600 rounded-full"></span> Transaksi</span>
                    <span class="flex items-center gap-1.5 text-emerald-600 font-semibold"><span class="w-3 h-1 bg-emerald-500 rounded-full"></span> GMV (Rp)</span>
                </div>
            </div>
            <div class="h-64 sm:h-72 w-full relative">
                <canvas id="chart-transaction-trend"></canvas>
            </div>
        </div>

        <!-- Chart Grid 2: Donut Status & Bar Performa -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Chart 2: Komposisi Status Transaksi (Donut) -->
            <div class="lg:col-span-5 glass-card p-6 border border-white/80 shadow-sm space-y-4 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200/60">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 tracking-tight">2. Komposisi Status Transaksi</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Proporsi siklus transaksi dalam periode</p>
                        </div>
                    </div>
                    <div class="h-60 sm:h-64 w-full relative mt-2 flex items-center justify-center">
                        <canvas id="chart-status-donut"></canvas>
                    </div>
                </div>
                <div id="donut-legend-summary" class="text-center text-[11px] text-slate-500 pt-2 border-t border-slate-100">
                    Total Transaksi Periode Ini: <strong class="text-slate-900">{{ array_sum($analyticsData['statusComposition']['data']) }}</strong>
                </div>
            </div>

            <!-- Chart 3: Performa Transaksi (Bar) -->
            <div class="lg:col-span-7 glass-card p-6 border border-white/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200/60">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">3. Performa Transaksi (Outcome Ratios)</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Rasio kesuksesan transaksi selesai vs dibatalkan vs sengketa</p>
                    </div>
                    <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                        Success Rate: {{ $successRate }}%
                    </span>
                </div>
                <div class="h-60 sm:h-64 w-full relative">
                    <canvas id="chart-performance-bar"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart Grid 3: Tren Komplain & Status Payout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart 4: Tren Komplain (Bar / Line) -->
            <div class="glass-card p-6 border border-white/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200/60">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">4. Tren Komplain & Resolusi Sengketa</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Perbandingan komplain baru masuk vs komplain yang berhasil diputuskan</p>
                    </div>
                    <span class="text-xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                        {{ $openComplaints }} Sengketa Open
                    </span>
                </div>
                <div class="h-56 sm:h-60 w-full relative">
                    <canvas id="chart-complaints-trend"></canvas>
                </div>
            </div>

            <!-- Chart 5: Status Pencairan Dana / Payout (Bar) -->
            <div class="glass-card p-6 border border-white/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200/60">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">5. Distribusi Status Payout Seller</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Monitoring transfer manual payout yang berhasil, menunggu, atau gagal</p>
                    </div>
                    <span class="text-xs font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md border border-sky-200">
                        Siap: {{ $waitingPayoutTransactions }}
                    </span>
                </div>
                <div class="h-56 sm:h-60 w-full relative">
                    <canvas id="chart-payouts-status"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- E. AKTIVITAS TERBARU & AUDIT STREAM + TABEL TRANSAKSI TERAKHIR             -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-start">

        <!-- Left: Transaksi Terbaru (8 Cols) -->
        <div class="lg:col-span-8 glass-card p-6 border border-white/80 shadow-sm space-y-5">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200/60">
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Distribusi Status Escrow Transaksi</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pemantauan siklus hidup kesepakatan buyer-seller di sistem</p>
                </div>
                <span class="glass-badge glass-badge-neutral text-xs">
                    {{ $totalTransactions }} Transaksi
                </span>
            </div>

            <!-- Transaksi Terbaru Table -->
            <div class="pt-1">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600">Transaksi Terbaru</h4>
                    <a href="{{ route('admin.transactions.index') }}" class="text-xs text-indigo-600 hover:underline font-semibold">Lihat Semua &rarr;</a>
                </div>

                @if ($recentTransactions->isEmpty())
                    <div class="p-6 text-center rounded-xl bg-white/40 border border-dashed border-slate-200 text-xs text-slate-500">
                        Belum ada data transaksi tersimpan di database.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-slate-400 border-b border-slate-200/60 pb-2">
                                    <th class="py-2 font-medium">ID Transaksi</th>
                                    <th class="py-2 font-medium">Pihak Terlibat</th>
                                    <th class="py-2 font-medium">Item & Penawaran</th>
                                    <th class="py-2 font-medium">Nominal Total</th>
                                    <th class="py-2 font-medium">Status Escrow</th>
                                    <th class="py-2 font-medium text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($recentTransactions as $tx)
                                    <tr>
                                        <td class="py-2.5 font-bold text-slate-900">#TRX-{{ $tx->transaction_id }}</td>
                                        <td class="py-2.5 text-slate-600">
                                            <span class="block text-slate-900 font-semibold">{{ '@' . ($tx->agreement?->buyer?->username ?? 'buyer') }} (B)</span>
                                            <span class="block text-[10px] text-slate-400">{{ '@' . ($tx->agreement?->seller?->username ?? 'seller') }} (S)</span>
                                        </td>
                                        <td class="py-2.5 text-slate-700 max-w-[180px] truncate">
                                            {{ $tx->agreement?->item_title ?? $tx->listing?->title ?? 'Item Kesepakatan' }}
                                        </td>
                                        <td class="py-2.5 font-semibold text-slate-900">
                                            Rp {{ number_format($tx->agreement?->buyer_total ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-2.5">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold
                                                {{ $tx->status->value === 'COMPLETED' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                                {{ in_array($tx->status->value, ['PAID', 'DELIVERED', 'CONFIRMED']) ? 'bg-amber-100 text-amber-800' : '' }}
                                                {{ $tx->status->value === 'DISPUTED' ? 'bg-rose-100 text-rose-800' : '' }}
                                                {{ !in_array($tx->status->value, ['COMPLETED', 'PAID', 'DELIVERED', 'CONFIRMED', 'DISPUTED']) ? 'bg-slate-100 text-slate-700' : '' }}
                                            ">
                                                {{ $tx->status->value }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 text-right">
                                            <a href="{{ route('admin.transactions.index') }}?transaction_id={{ $tx->transaction_id }}" class="text-indigo-600 hover:underline font-semibold text-[11px]">
                                                Detail &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Stream Aktivitas Terkini & Listing Mini Stream (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Stream Aktivitas Terkini (Activity Feed) -->
            <div class="glass-card p-6 border border-white/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200/60">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                        <h3 class="text-sm font-bold text-slate-900">Aktivitas Terkini (Live Stream)</h3>
                    </div>
                    <span class="text-[10px] text-slate-400">Audit Log</span>
                </div>

                @if ($recentStatusHistories->isEmpty())
                    <p class="text-xs text-slate-500 text-center py-4">Belum ada riwayat aktivitas terbaru.</p>
                @else
                    <div class="space-y-3.5 relative before:absolute before:inset-y-0 before:left-3.5 before:w-0.5 before:bg-slate-200/60">
                        @foreach ($recentStatusHistories as $history)
                            <div class="relative flex items-start gap-3 text-xs pl-1">
                                <div class="w-5 h-5 rounded-full bg-white border-2 border-indigo-500 flex items-center justify-center shrink-0 z-10 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-1">
                                        <a href="{{ route('admin.transactions.index') }}?transaction_id={{ $history->transaction_id }}" class="font-bold text-slate-900 hover:text-indigo-600 transition truncate">
                                            #TRX-{{ $history->transaction_id }}
                                        </a>
                                        <span class="text-[10px] text-slate-400 shrink-0">
                                            {{ $history->changed_at ? $history->changed_at->diffForHumans() : 'baru saja' }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 mt-0.5 leading-snug">
                                        Status beralih: <strong class="text-indigo-700">{{ $history->to_status }}</strong>
                                    </p>
                                    @if ($history->note)
                                        <p class="text-[10px] text-slate-400 italic mt-0.5 truncate">{{ $history->note }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Listing Terbaru Mini Stream -->
            <div class="glass-card p-6 border border-white/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200/60">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Listing Terbaru</h3>
                    <a href="{{ route('admin.catalog.index') }}" class="text-[10px] text-indigo-600 hover:underline font-semibold">Semua &rarr;</a>
                </div>

                @if ($recentListings->isEmpty())
                    <p class="text-xs text-slate-500 text-center py-2">Belum ada penawaran listing aktif.</p>
                @else
                    <div class="space-y-2.5">
                        @foreach ($recentListings as $listing)
                            <div class="flex items-center justify-between text-xs">
                                <div class="min-w-0 flex-1 pr-2">
                                    <span class="block font-semibold text-slate-900 truncate">{{ $listing->title }}</span>
                                    <span class="text-[10px] text-slate-500">Seller: {{ '@' . ($listing->seller?->username ?? 'seller') }}</span>
                                </div>
                                <span class="font-bold text-slate-900 shrink-0">
                                    Rp {{ number_format($listing->price, 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>

<!-- Chart.js Official CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>

<!-- Script Inisialisasi & Filter Interaktif 5 Chart -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js library is not loaded.');
        return;
    }

    // Default chart global configuration
    Chart.defaults.font.family = "'Instrument Sans', system-ui, -apple-system, sans-serif";
    Chart.defaults.color = '#64748b';

    const rupiahFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    });

    let initialData = @json($analyticsData);
    let charts = {};

    // 1. Chart 1: Tren Transaksi & GMV (Dual Axis)
    function initTrendChart(trendData) {
        const ctx = document.getElementById('chart-transaction-trend')?.getContext('2d');
        if (!ctx) return;
        if (charts.trend) charts.trend.destroy();

        charts.trend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [
                    {
                        label: 'Jumlah Transaksi',
                        data: trendData.transactions,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.08)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Gross Escrow Volume (Rp)',
                        data: trendData.gmv,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.06)',
                        borderWidth: 2,
                        borderDash: [4, 4],
                        fill: false,
                        tension: 0.35,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Jumlah Transaksi', font: { size: 10, weight: 'bold' } },
                        ticks: { precision: 0, stepSize: 1 },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Nilai GMV', font: { size: 10, weight: 'bold' } },
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback: function(val) {
                                if (val >= 1000000) return 'Rp ' + (val/1000000).toFixed(1) + 'M';
                                if (val >= 1000) return 'Rp ' + (val/1000).toFixed(0) + 'k';
                                return 'Rp ' + val;
                            }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 1) {
                                    return 'GMV: ' + rupiahFormatter.format(context.raw);
                                }
                                return 'Transaksi: ' + context.raw + ' transaksi';
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Chart 2: Komposisi Status Transaksi (Donut)
    function initStatusDonut(statusData) {
        const ctx = document.getElementById('chart-status-donut')?.getContext('2d');
        if (!ctx) return;
        if (charts.statusDonut) charts.statusDonut.destroy();

        const colors = [
            '#94a3b8', // Menunggu Bayar
            '#f59e0b', // Penyerahan
            '#0ea5e9', // Konfirmasi
            '#10b981', // Siap Payout
            '#059669', // Selesai
            '#f43f5e', // Sengketa
            '#64748b'  // Dibatalkan
        ];

        charts.statusDonut = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: statusData.labels,
                datasets: [{
                    data: statusData.data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${context.raw} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Chart 3: Performa Transaksi (Bar)
    function initPerformanceBar(perfData) {
        const ctx = document.getElementById('chart-performance-bar')?.getContext('2d');
        if (!ctx) return;
        if (charts.performance) charts.performance.destroy();

        charts.performance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: perfData.labels,
                datasets: [{
                    data: perfData.data,
                    backgroundColor: ['#10b981', '#94a3b8', '#f43f5e'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 4. Chart 4: Tren Komplain
    function initComplaintsTrend(compData) {
        const ctx = document.getElementById('chart-complaints-trend')?.getContext('2d');
        if (!ctx) return;
        if (charts.complaints) charts.complaints.destroy();

        charts.complaints = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: compData.labels,
                datasets: [{
                    data: compData.data,
                    backgroundColor: ['#f43f5e', '#10b981'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 5. Chart 5: Status Payout
    function initPayoutsStatus(payoutData) {
        const ctx = document.getElementById('chart-payouts-status')?.getContext('2d');
        if (!ctx) return;
        if (charts.payouts) charts.payouts.destroy();

        charts.payouts = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: payoutData.labels,
                datasets: [{
                    data: payoutData.data,
                    backgroundColor: ['#10b981', '#0ea5e9', '#f43f5e'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Render initial charts
    function renderAllCharts(data) {
        initTrendChart(data.trend);
        initStatusDonut(data.statusComposition);
        initPerformanceBar(data.performance);
        initComplaintsTrend(data.complaints);
        initPayoutsStatus(data.payouts);

        const legendSummary = document.getElementById('donut-legend-summary');
        if (legendSummary && data.statusComposition) {
            legendSummary.innerHTML = `Total Transaksi Periode Ini: <strong class="text-slate-900">${data.statusComposition.total || 0}</strong>`;
        }
    }

    renderAllCharts(initialData);

    // Interactive Period Filter Switching (7d, 30d, 90d, all)
    const periodButtons = document.querySelectorAll('.period-btn');
    const periodLabel = document.getElementById('current-period-label');

    periodButtons.forEach(btn => {
        btn.addEventListener('click', async function () {
            const period = this.getAttribute('data-period');

            // Update active button state
            periodButtons.forEach(b => {
                b.classList.remove('bg-indigo-600', 'text-white', 'shadow-xs');
                b.classList.add('hover:text-slate-900', 'hover:bg-white');
            });
            this.classList.add('bg-indigo-600', 'text-white', 'shadow-xs');
            this.classList.remove('hover:text-slate-900', 'hover:bg-white');

            if (periodLabel) periodLabel.textContent = period;

            try {
                const response = await fetch(`{{ route('admin.dashboard.analytics') }}?period=${period}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const freshData = await response.json();
                    renderAllCharts(freshData);
                }
            } catch (err) {
                console.error('Gagal mengambil data analitik:', err);
            }
        });
    });
});
</script>
@endsection
