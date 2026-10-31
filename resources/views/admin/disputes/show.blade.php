@extends('layouts.admin')

@section('title', 'Investigasi Sengketa Transaksi #' . $complaint->transaction_id . ' - Panel Administrasi')

@section('content')
<div class="py-8 sm:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Breadcrumb & Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                    <a href="{{ route('admin.disputes.index') }}" class="hover:text-indigo-600 transition">Pusat Sengketa</a>
                    <span>&bull;</span>
                    <span class="text-indigo-600">Sengketa Transaksi #{{ $complaint->transaction_id }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Investigasi Sengketa Transaksi #{{ $complaint->transaction_id }}
                    </h1>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $complaint->status === 'OPEN' ? 'bg-amber-100 text-amber-800 border border-amber-200' : ($complaint->status === 'REVIEWING' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200') }}">
                        {{ $complaint->status }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <a
                    href="{{ route('transactions.show', $complaint->transaction_id) }}"
                    target="_blank"
                    class="glass-btn-secondary px-3.5 py-2 text-xs font-semibold flex items-center gap-1.5"
                    title="Buka halaman transaksi publik"
                >
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>Halaman Transaksi</span>
                </a>
                <a
                    href="{{ route('admin.disputes.index') }}"
                    class="glass-btn-secondary px-3.5 py-2 text-xs font-semibold flex items-center gap-1.5"
                >
                    &larr; Kembali
                </a>
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

        @php
            $tx = $complaint->transaction;
            $agreement = $tx?->agreement;
        @endphp

        <!-- Top Summary Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="glass-card p-4 rounded-xl border-slate-200 bg-white/70 space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Escrow Ditahan</span>
                <div class="text-xl font-extrabold text-indigo-700">
                    Rp {{ number_format($agreement?->buyer_total ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500">
                    Hak Bersih Penjual: <strong>Rp {{ number_format($agreement?->seller_net ?? 0, 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="glass-card p-4 rounded-xl border-slate-200 bg-white/70 space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pembeli (Pelapor Komplain)</span>
                <div class="font-bold text-slate-900 text-sm">
                    @<span>{{ $agreement?->buyer?->username }}</span> ({{ $agreement?->buyer?->name ?? 'Pembeli' }})
                </div>
                <div class="text-[11px] text-slate-500">{{ $agreement?->buyer?->email }}</div>
            </div>

            <div class="glass-card p-4 rounded-xl border-slate-200 bg-white/70 space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Penjual (Terlapor)</span>
                <div class="font-bold text-slate-900 text-sm">
                    @<span>{{ $agreement?->seller?->username }}</span> ({{ $agreement?->seller?->name ?? 'Penjual' }})
                </div>
                <div class="text-[11px] text-slate-500">{{ $agreement?->seller?->email }}</div>
            </div>
        </div>

        <!-- Investigation Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left 2 Cols: Comprehensive Case Facts & Evidence -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Section 1: Kesepakatan & Produk Digital -->
                <div class="glass-card p-5 sm:p-6 border-slate-200 bg-white/80 space-y-4 shadow-sm">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">1</span>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Snapshot Kesepakatan Digital</h2>
                    </div>

                    <div class="space-y-2">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Judul Item Produk:</span>
                        <h3 class="text-sm font-bold text-slate-800">{{ $agreement?->item_title }}</h3>
                        <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line bg-slate-50 p-3 rounded-lg border border-slate-100">
                            {{ $agreement?->item_description }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2 text-xs">
                        <div>
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Varian</span>
                            <span class="font-semibold text-slate-700">{{ $agreement?->variant?->variant_name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Metode Serah</span>
                            <span class="font-semibold text-slate-700">{{ $agreement?->deliveryMethodLabel() }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Komitmen SLA</span>
                            <span class="font-bold text-indigo-600">{{ $agreement?->delivery_within_minutes }} Menit</span>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Penyerahan Resmi oleh Penjual -->
                <div class="glass-card p-5 sm:p-6 border-slate-200 bg-white/80 space-y-4 shadow-sm">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">2</span>
                            <h2 class="text-sm sm:text-base font-bold text-slate-900">Hasil Penyerahan Resmi oleh Penjual</h2>
                        </div>
                        <span class="text-[11px] text-slate-500 font-medium">
                            Diserahkan: {{ $tx?->delivered_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                        </span>
                    </div>

                    <div class="space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Catatan Penyerahan:</span>
                        <p class="text-xs text-slate-800 bg-slate-50 p-3 rounded-lg border border-slate-100 leading-relaxed whitespace-pre-line font-medium">
                            {{ $tx?->delivery_note ?: 'Tidak ada catatan tertulis dari penjual.' }}
                        </p>
                    </div>

                    @if ($tx?->result_link)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-indigo-50/50 border border-indigo-100 text-xs">
                            <span class="font-semibold text-slate-700">Tautan Hasil Digital:</span>
                            <a href="{{ $tx->result_link }}" target="_blank" rel="noopener noreferrer" class="font-bold text-indigo-600 hover:underline">
                                Buka Tautan Eksternal &rarr;
                            </a>
                        </div>
                    @endif

                    <!-- Berkas Bukti Penyerahan Awal -->
                    @if ($tx?->evidenceFiles && $tx->evidenceFiles->isNotEmpty())
                        <div class="pt-2">
                            <span class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Berkas Bukti Pengiriman Awal:</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach ($tx->evidenceFiles as $delivEvidence)
                                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between gap-2 text-xs">
                                        <span class="truncate font-semibold text-slate-800" title="{{ $delivEvidence->file_name }}">
                                            {{ $delivEvidence->file_name }}
                                        </span>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <a href="{{ route('transactions.delivery-evidence', [$tx, $delivEvidence, 'inline' => 1]) }}" target="_blank" class="px-2 py-0.5 rounded bg-white hover:bg-slate-200 text-slate-700 text-[10px] font-semibold border">Lihat</a>
                                            <a href="{{ route('transactions.delivery-evidence', [$tx, $delivEvidence]) }}" class="px-2 py-0.5 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold">Unduh</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Section 3: Komplain Resmi Pembeli & Bukti Wajib -->
                <div class="glass-card p-5 sm:p-6 border-orange-200 bg-orange-50/20 space-y-4 shadow-sm" data-cy="admin-complaint-fact-card">
                    <div class="flex items-center justify-between pb-3 border-b border-orange-100">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-orange-600 text-white flex items-center justify-center font-bold text-xs">3</span>
                            <h2 class="text-sm sm:text-base font-bold text-slate-900">Gugatan Komplain Pembeli</h2>
                        </div>
                        <span class="text-[11px] text-orange-800 font-semibold">
                            Diajukan: {{ $complaint->created_at->translatedFormat('d M Y, H:i') }} WIB
                        </span>
                    </div>

                    <div class="space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Rincian Masalah / Alasan Komplain:</span>
                        <div class="text-xs sm:text-sm text-slate-900 bg-white p-3.5 rounded-lg border border-orange-200 leading-relaxed whitespace-pre-line font-medium" data-cy="admin-display-complaint-reason">
                            {{ $complaint->reason }}
                        </div>
                    </div>

                    <!-- Bukti Wajib Pembeli -->
                    <div class="pt-2">
                        <span class="block text-[10px] uppercase font-bold text-slate-400 mb-2">Berkas Bukti Penggugat:</span>
                        @if ($complaint->evidenceFiles->count() > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach ($complaint->evidenceFiles as $cEvidence)
                                    <div class="p-2.5 rounded-lg bg-white border border-orange-200 flex items-center justify-between gap-2 text-xs">
                                        <div class="truncate font-semibold text-slate-800" title="{{ $cEvidence->file_name }}">
                                            {{ $cEvidence->file_name }}
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <a href="{{ route('transactions.complaints.evidence', [$tx, $cEvidence, 'inline' => 1]) }}" target="_blank" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold">Lihat</a>
                                            <a href="{{ route('transactions.complaints.evidence', [$tx, $cEvidence]) }}" class="px-2 py-1 rounded bg-orange-600 hover:bg-orange-700 text-white text-[11px] font-bold">Unduh</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-rose-600 font-semibold">Tidak ada berkas bukti pembeli yang tercatat.</p>
                        @endif
                    </div>
                </div>

                <!-- Section 4: Tanggapan & Sanggahan Penjual -->
                <div class="glass-card p-5 sm:p-6 border-slate-200 bg-white/80 space-y-4 shadow-sm" data-cy="admin-seller-responses-card">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">4</span>
                            <h2 class="text-sm sm:text-base font-bold text-slate-900">Tanggapan & Sanggahan Penjual</h2>
                        </div>
                        <span class="text-xs font-bold text-slate-600">
                            {{ $complaint->responses->count() }} Tanggapan Tercatat
                        </span>
                    </div>

                    @forelse ($complaint->responses as $resp)
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5 text-xs">
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                                <strong class="text-slate-900">@<span>{{ $resp->responder?->username }}</span> (Penjual)</strong>
                                <span class="text-[10px] text-slate-500">{{ $resp->created_at?->translatedFormat('d M Y, H:i') }} WIB</span>
                            </div>
                            <p class="text-slate-700 leading-relaxed whitespace-pre-line text-xs sm:text-sm">
                                {{ $resp->response_text }}
                            </p>

                            @if ($resp->evidenceFiles && $resp->evidenceFiles->isNotEmpty())
                                <div class="pt-2 border-t border-slate-200">
                                    <span class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Bukti Sanggahan:</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach ($resp->evidenceFiles as $rEvidence)
                                            <div class="p-2 rounded bg-white border border-slate-200 flex items-center justify-between gap-2">
                                                <span class="truncate font-medium text-slate-700" title="{{ $rEvidence->file_name }}">{{ $rEvidence->file_name }}</span>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <a href="{{ route('transactions.complaints.evidence', [$tx, $rEvidence, 'inline' => 1]) }}" target="_blank" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-[10px] font-semibold">Lihat</a>
                                                    <a href="{{ route('transactions.complaints.evidence', [$tx, $rEvidence]) }}" class="px-2 py-0.5 rounded bg-indigo-600 text-white text-[10px] font-bold">Unduh</a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-200 text-amber-800 text-xs">
                            Penjual belum memberikan tanggapan resmi atas sengketa ini.
                            @if ($sellerDeadline && $sellerDeadline->new_deadline)
                                <div class="font-bold mt-1">Batas Waktu Seller: {{ $sellerDeadline->new_deadline->translatedFormat('d M Y, H:i') }} WIB</div>
                            @endif
                        </div>
                    @endforelse
                </div>

                <!-- Section 5: Riwayat SLA & Jejak Batas Waktu -->
                <div class="glass-card p-5 sm:p-6 border-slate-200 bg-white/80 space-y-3 shadow-sm">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                        <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">5</span>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Jejak Audit SLA & Batas Waktu Sengketa</h2>
                    </div>

                    <div class="space-y-2">
                        @foreach ($deadlineEvents as $dEvent)
                            <div class="flex items-start gap-2.5 text-xs text-slate-700 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                <span class="w-2 h-2 rounded-full {{ $dEvent->is_flagged_for_admin ? 'bg-rose-500' : 'bg-indigo-500' }} mt-1.5 shrink-0"></span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-slate-900 text-[11px]">{{ $dEvent->event_type }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $dEvent->created_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 mt-0.5">{{ $dEvent->reason }}</p>
                                    @if ($dEvent->new_deadline)
                                        <span class="inline-block mt-1 text-[10px] px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-700 font-semibold">
                                            Tenggat Baru: {{ $dEvent->new_deadline->translatedFormat('d M Y, H:i') }} WIB
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Right 1 Col: Decision & Action Console -->
            <div class="space-y-6">

                @if (! $complaint->isResolved())
                    <!-- Panel Permintaan Bukti Tambahan (REVIEWING) -->
                    <div class="glass-card p-5 border-purple-200 bg-purple-50/30 space-y-4 shadow-sm" data-cy="request-evidence-card">
                        <div class="flex items-center gap-2 pb-2 border-b border-purple-100">
                            <span class="text-base">🔍</span>
                            <h3 class="text-sm font-bold text-purple-950">Minta Bukti Tambahan</h3>
                        </div>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Jika bukti kedua pihak belum cukup kuat, ubah status ke <strong>REVIEWING</strong> dan berikan perpanjangan tenggat waktu investigasi.
                        </p>

                        <form action="{{ route('admin.disputes.request-evidence', $complaint) }}" method="POST" class="space-y-3" data-cy="form-request-evidence">
                            @csrf

                            <div>
                                <label for="req_reason" class="block text-[11px] font-bold text-slate-700 mb-1">
                                    Catatan Permintaan Bukti <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    name="reason"
                                    id="req_reason"
                                    rows="3"
                                    required
                                    class="w-full text-xs rounded-xl border border-slate-300 focus:border-purple-500 focus:ring-1 focus:ring-purple-200 p-2.5 bg-white transition"
                                    placeholder="Instruksikan berkas spesifik yang harus dilengkapi pembeli/penjual..."
                                >{{ old('reason') }}</textarea>
                                @error('reason')
                                    <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="extension_hours" class="block text-[11px] font-bold text-slate-700 mb-1">
                                    Perpanjangan Waktu Keputusan
                                </label>
                                <select
                                    name="extension_hours"
                                    id="extension_hours"
                                    class="w-full text-xs rounded-xl border border-slate-300 focus:border-purple-500 focus:ring-1 focus:ring-purple-200 p-2 bg-white transition"
                                >
                                    <option value="24" selected>+24 Jam (1 Hari)</option>
                                    <option value="48">+48 Jam (2 Hari)</option>
                                    <option value="72">+72 Jam (3 Hari)</option>
                                </select>
                            </div>

                            <button
                                type="submit"
                                class="w-full py-2.5 px-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs transition active:scale-98 shadow-sm flex items-center justify-center gap-1.5"
                                data-cy="btn-submit-request-evidence"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Kirim Permintaan Bukti</span>
                            </button>
                        </form>
                    </div>

                    <!-- Panel Penetapan Keputusan Final (UC-18 / KF-10) -->
                    <div class="glass-card p-5 sm:p-6 border-slate-300 bg-white/95 space-y-4 shadow-md" data-cy="decide-dispute-card">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-200">
                            <span class="text-base">⚖️</span>
                            <h3 class="text-sm sm:text-base font-black text-slate-900">Tetapkan Putusan Resmi</h3>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Keputusan admin bersifat final. Sistem akan menutup status sengketa menjadi <strong>RESOLVED</strong> dan menyiapkan tepat satu settlement berstatus <strong>PLANNED</strong>.
                        </p>

                        <form action="{{ route('admin.disputes.decide', $complaint) }}" method="POST" class="space-y-4" data-cy="form-decide-dispute">
                            @csrf

                            <!-- Pilihan Keputusan -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-800">
                                    Pilih Amar Putusan <span class="text-rose-500">*</span>
                                </label>

                                <div class="space-y-2">
                                    <label class="flex items-start gap-3 p-3 rounded-xl border border-emerald-200 bg-emerald-50/50 hover:bg-emerald-50 cursor-pointer transition">
                                        <input
                                            type="radio"
                                            name="decision"
                                            value="PAYOUT"
                                            required
                                            class="mt-1 text-emerald-600 focus:ring-emerald-500"
                                            data-cy="radio-decision-payout"
                                        >
                                        <div class="text-xs">
                                            <strong class="text-emerald-950 font-bold block">PAYOUT (Menangkan Penjual)</strong>
                                            <span class="text-slate-600">Alokasikan hak bersih penjual (Rp {{ number_format($agreement?->seller_net ?? 0, 0, ',', '.') }}) ke antrean pencairan payout.</span>
                                        </div>
                                    </label>

                                    <label class="flex items-start gap-3 p-3 rounded-xl border border-rose-200 bg-rose-50/50 hover:bg-rose-50 cursor-pointer transition">
                                        <input
                                            type="radio"
                                            name="decision"
                                            value="REFUND"
                                            required
                                            class="mt-1 text-rose-600 focus:ring-rose-500"
                                            data-cy="radio-decision-refund"
                                        >
                                        <div class="text-xs">
                                            <strong class="text-rose-950 font-bold block">REFUND (Menangkan Pembeli)</strong>
                                            <span class="text-slate-600">Alokasikan dana penuh pembeli (Rp {{ number_format($agreement?->buyer_total ?? 0, 0, ',', '.') }}) ke antrean refund escrow.</span>
                                        </div>
                                    </label>
                                </div>
                                @error('decision')
                                    <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Alasan Pertimbangan Putusan -->
                            <div class="space-y-1">
                                <label for="decision_reason" class="block text-xs font-bold text-slate-800">
                                    Alasan & Pertimbangan Putusan <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    name="decision_reason"
                                    id="decision_reason"
                                    rows="4"
                                    required
                                    class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 p-3 bg-white transition placeholder:text-slate-400"
                                    placeholder="Jelaskan dasar pertimbangan bukti yang menjadi alasan kemenangan salah satu pihak..."
                                    data-cy="input-decision-reason"
                                >{{ old('decision_reason') }}</textarea>
                                @error('decision_reason')
                                    <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="p-3 rounded-xl bg-slate-100 text-[11px] text-slate-600 leading-relaxed">
                                ℹ️ <em>Catatan Finansial:</em> Keputusan ini menyiapkan rekaman Settlement dalam status <strong>PLANNED</strong>. Transfer riil tidak dijalankan otomatis dan harus diverifikasi pada modul MT-A07.
                            </div>

                            <button
                                type="submit"
                                onclick="return confirm('Apakah Anda yakin ingin menetapkan putusan ini? Tindakan ini tidak dapat dibatalkan.')"
                                class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs sm:text-sm transition active:scale-98 shadow-md shadow-indigo-600/25 flex items-center justify-center gap-2"
                                data-cy="btn-submit-decision"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Tetapkan Putusan Resmi</span>
                            </button>
                        </form>
                    </div>

                @else
                    <!-- Putusan Telah Selesai (RESOLVED) -->
                    <div class="glass-card p-5 sm:p-6 border-emerald-300 bg-white/95 space-y-4 shadow-md" data-cy="admin-resolved-summary-card">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-200">
                            <span class="text-base">✅</span>
                            <h3 class="text-sm sm:text-base font-black text-slate-900">Sengketa Telah Diputuskan</h3>
                        </div>

                        <div class="p-4 rounded-xl {{ $complaint->decision === 'PAYOUT' ? 'bg-emerald-50 border border-emerald-200 text-emerald-950' : 'bg-rose-50 border border-rose-200 text-rose-950' }} space-y-1">
                            <span class="text-[10px] uppercase font-bold tracking-wider">Amar Putusan:</span>
                            <div class="text-base font-extrabold">
                                {{ $complaint->decision === 'PAYOUT' ? 'PAYOUT (Dimenangkan Penjual)' : 'REFUND (Dimenangkan Pembeli)' }}
                            </div>
                        </div>

                        <div class="space-y-1 text-xs text-slate-700">
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Dasar Alasan Putusan:</span>
                            <p class="leading-relaxed bg-slate-50 p-3 rounded-lg border border-slate-200 font-medium">
                                {{ $complaint->decision_reason }}
                            </p>
                        </div>

                        <div class="text-[11px] text-slate-500 pt-2 border-t border-slate-100 space-y-1">
                            <div>Diputuskan oleh: <strong>{{ $complaint->decidedBy?->name ?? ($complaint->decidedBy?->username ?? 'Administrator') }}</strong></div>
                            <div>Tanggal Keputusan: <strong>{{ $complaint->decided_at?->translatedFormat('d F Y, H:i') }} WIB</strong></div>
                        </div>

                        @if ($complaint->settlement)
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                                <span class="text-[10px] uppercase font-bold text-slate-500">Record Settlement Terkait (MT-A07):</span>
                                <div class="font-bold text-slate-800">
                                    Settlement #{{ $complaint->settlement->settlement_id }} &bull; Rp {{ number_format($complaint->settlement->amount, 0, ',', '.') }}
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    Status: <span class="px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800 font-bold uppercase">{{ $complaint->settlement->status }}</span>
                                    &bull; Tujuan: {{ $complaint->settlement->destination_holder }} ({{ $complaint->settlement->destination_provider ?? $complaint->settlement->destination_type }})
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

            </div>

        </div>

    </div>
</div>
@endsection

