@extends('layouts.admin')

@section('title', 'Pengawasan Transaksi, Escrow & Pembatalan - Panel Administrasi')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                    <span>Panel Administrasi</span>
                    <span>&bull;</span>
                    <span>Modul Tahap Lanjutan</span>
                    <span>&bull;</span>
                    <span>Transaksi & Escrow (UC-27 &bull; UC-28 &bull; UC-28B)</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Pengawasan Transaksi & Antrean Pembatalan
                </h1>
                <p class="text-sm text-slate-600 mt-1 max-w-2xl">
                    Tinjau pengajuan pembatalan pasca-bayar, putuskan pihak yang bertanggung jawab, serta pantau penetapan kewajiban KEEP seller.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-3.5">
                    &larr; Dashboard
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-xs">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- SECTION 1: ANTREAN PENINJAUAN PEMBATALAN (UC-28) -->
        <div class="glass-card p-6 border border-white/80 shadow-md space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold text-sm">
                        ⏳
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Antrean Pengajuan Pembatalan Pasca-Bayar
                        </h2>
                        <p class="text-xs text-slate-500">
                            Transaksi berstatus PAID yang diajukan pembatalan sebelum tahap penyerahan (DELIVERED).
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $pendingCancellations->count() > 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-600' }}">
                    {{ $pendingCancellations->count() }} Menunggu Tinjauan
                </span>
            </div>

            @if ($pendingCancellations->isEmpty())
                <div class="text-center py-10 text-slate-500 space-y-2">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <p class="text-xs font-semibold">Tidak ada pengajuan pembatalan yang sedang menunggu peninjauan.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-2.5 px-3">ID Pembatalan</th>
                                <th class="py-2.5 px-3">Transaksi</th>
                                <th class="py-2.5 px-3">Pemohon</th>
                                <th class="py-2.5 px-3">Alasan Pengajuan</th>
                                <th class="py-2.5 px-3">Diajukan Pada</th>
                                <th class="py-2.5 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pendingCancellations as $canc)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                        #CAN-{{ $canc->cancellation_id }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <a href="{{ route('transactions.show', $canc->transaction) }}" target="_blank" class="font-bold text-indigo-600 hover:underline">
                                            #TX-{{ $canc->transaction_id }}
                                        </a>
                                        <div class="text-[10px] text-slate-500">
                                            {{ $canc->transaction->agreement?->item_title ?? 'Produk Digital' }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-semibold text-slate-800">{{ $canc->requestedBy?->username }}</span>
                                        <span class="block text-[10px] text-slate-400">
                                            {{ $canc->requested_by === $canc->transaction->agreement?->buyer_id ? 'Pembeli' : 'Penjual' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 max-w-xs">
                                        <p class="line-clamp-2 text-slate-700 italic">
                                            "{{ $canc->reason }}"
                                        </p>
                                    </td>
                                    <td class="py-3 px-3 text-slate-500 whitespace-nowrap">
                                        {{ $canc->created_at->translatedFormat('d M Y, H:i') }} WIB
                                    </td>
                                    <td class="py-3 px-3 text-right whitespace-nowrap">
                                        <button
                                            type="button"
                                            onclick="openReviewModal({{ $canc->cancellation_id }}, {{ $canc->transaction_id }}, '{{ addslashes($canc->requestedBy?->username ?? '') }}', '{{ addslashes($canc->reason ?? '') }}')"
                                            class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition"
                                        >
                                            Tinjau Keputusan
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- SECTION 2: KEWAJIBAN BIAYA KEEP PENJUAL (UC-28B) -->
        <div class="glass-card p-6 border border-white/80 shadow-md space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-sm">
                        ⚖️
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Daftar Kewajiban KEEP Penjual (Escrow Retention)
                        </h2>
                        <p class="text-xs text-slate-500">
                            Biaya pemeliharaan rekening escrow yang dibebankan kepada penjual akibat pembatalan sepihak / kelalaian.
                        </p>
                    </div>
                </div>
            </div>

            @if ($keepObligations->isEmpty())
                <div class="text-center py-8 text-slate-500">
                    <p class="text-xs">Belum ada kewajiban KEEP yang tercatat pada sistem.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-2.5 px-3">ID KEEP</th>
                                <th class="py-2.5 px-3">Penjual (Seller)</th>
                                <th class="py-2.5 px-3">Transaksi Terkait</th>
                                <th class="py-2.5 px-3">Channel & Aturan Hitung</th>
                                <th class="py-2.5 px-3">Nominal Tagihan</th>
                                <th class="py-2.5 px-3">Sisa Tagihan</th>
                                <th class="py-2.5 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($keepObligations as $keep)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                        #KEEP-{{ $keep->keep_obligation_id }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-bold text-slate-900">{{ $keep->seller?->username }}</span>
                                        <span class="block text-[10px] text-slate-400">ID: {{ $keep->seller_id }}</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        @if ($keep->cancellation?->transaction)
                                            <a href="{{ route('transactions.show', $keep->cancellation->transaction) }}" target="_blank" class="font-bold text-indigo-600 hover:underline">
                                                #TX-{{ $keep->cancellation->transaction_id }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-semibold text-slate-800 uppercase">{{ $keep->payment_channel_snapshot }}</span>
                                        <span class="block text-[10px] text-slate-500">
                                            {{ $keep->calculation_rule === 'BANK_VA_FLAT' ? 'Flat Rp 4.000 (Bank/VA)' : '3x Fee Gateway (Rp ' . number_format($keep->fee_basis_amount, 0, ',', '.') . ')' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 font-bold text-slate-900">
                                        Rp {{ number_format($keep->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 font-extrabold {{ $keep->remaining_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        Rp {{ number_format($keep->remaining_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $keep->status === 'OPEN' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                            {{ $keep->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    {{ $keepObligations->links() }}
                </div>
            @endif
        </div>

        <!-- SECTION 3: REKAPITULASI TRANSAKSI TERBARU -->
        <div class="glass-card p-6 border border-white/80 shadow-md space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">
                    Seluruh Transaksi Sistem
                </h2>
                <span class="text-xs text-slate-500">
                    Total: {{ $transactions->total() }} Transaksi
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <th class="py-2.5 px-3">ID</th>
                            <th class="py-2.5 px-3">Item Kesepakatan</th>
                            <th class="py-2.5 px-3">Pembeli</th>
                            <th class="py-2.5 px-3">Penjual</th>
                            <th class="py-2.5 px-3">Nilai Transaksi</th>
                            <th class="py-2.5 px-3">Status Escrow</th>
                            <th class="py-2.5 px-3 text-right">Rincian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($transactions as $tx)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                    #TX-{{ $tx->transaction_id }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-800 line-clamp-1">
                                        {{ $tx->agreement?->item_title ?? 'Produk Digital' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $tx->source_type->label() }}
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-slate-700">
                                    {{ $tx->agreement?->buyer?->username ?? '-' }}
                                </td>
                                <td class="py-3 px-3 text-slate-700">
                                    {{ $tx->agreement?->seller?->username ?? '-' }}
                                </td>
                                <td class="py-3 px-3 font-bold text-slate-900">
                                    Rp {{ number_format($tx->agreement?->deal_price ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $tx->status->badgeClass() }}">
                                        {{ $tx->status->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <a href="{{ route('transactions.show', $tx) }}" target="_blank" class="glass-btn-secondary text-xs py-1 px-2.5">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-3 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        </div>

    </div>
</div>

<!-- MODAL REVIEW PEMBATALAN OLEH ADMIN -->
<div id="modal-review-cancellation" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-lg p-6 bg-white/95 border border-white/80 shadow-2xl rounded-2xl space-y-5 animate-in fade-in duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">
                    Keputusan Peninjauan Pembatalan
                </h3>
                <p id="modal-review-subtitle" class="text-xs text-slate-500"></p>
            </div>
            <button type="button" onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700">
            <span class="block text-[10px] uppercase font-bold text-slate-400">Alasan dari Pemohon (<span id="modal-review-requester"></span>):</span>
            <p id="modal-review-reason" class="mt-0.5 italic font-medium text-slate-800"></p>
        </div>

        <form id="form-review-cancellation" method="POST" action="" class="space-y-4">
            @csrf

            <!-- Keputusan -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Keputusan Admin</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 has-checked:border-emerald-500 has-checked:bg-emerald-50/50">
                        <input type="radio" name="decision" value="APPROVED" checked onchange="toggleResponsibleParty(true)" class="text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs font-bold text-slate-800">Setujui & Batalkan</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 has-checked:border-rose-500 has-checked:bg-rose-50/50">
                        <input type="radio" name="decision" value="REJECTED" onchange="toggleResponsibleParty(false)" class="text-rose-600 focus:ring-rose-500">
                        <span class="text-xs font-bold text-slate-800">Tolak Pembatalan</span>
                    </label>
                </div>
            </div>

            <!-- Pihak Bertanggung Jawab (Hanya jika APPROVED) -->
            <div id="section-responsible-party" class="space-y-1.5">
                <label for="responsible_party" class="block text-xs font-bold text-slate-700">
                    Pihak Bertanggung Jawab <span class="text-rose-600">*</span>
                </label>
                <select name="responsible_party" id="responsible_party" class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200">
                    <option value="SELLER">Penjual (Seller) - Berlakukan Kewajiban KEEP</option>
                    <option value="BUYER">Pembeli (Buyer) - Tanpa Kewajiban KEEP</option>
                    <option value="PLATFORM">Sistem / Platform</option>
                    <option value="UNDETERMINED">Tidak Dapat Ditentukan</option>
                </select>
                <p class="text-[11px] text-slate-500">
                    Jika Penjual bertanggung jawab, kewajiban biaya KEEP (Flat Rp 4.000 atau 3x gateway fee) akan otomatis diterbitkan.
                </p>
            </div>

            <!-- Catatan Keputusan -->
            <div class="space-y-1">
                <label for="decision_note" class="block text-xs font-bold text-slate-700">
                    Catatan Keputusan / Alasan Peninjauan
                </label>
                <textarea
                    name="decision_note"
                    id="decision_note"
                    rows="3"
                    class="glass-input text-xs w-full p-3 rounded-xl border border-slate-200"
                    placeholder="Tuliskan catatan investigasi atau alasan keputusan ini..."
                ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeReviewModal()" class="glass-btn-secondary text-xs py-2 px-4">
                    Tutup
                </button>
                <button type="submit" class="glass-btn-primary text-xs py-2 px-5 font-bold shadow-md shadow-indigo-500/20">
                    Simpan Keputusan Final
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReviewModal(cancId, txId, requester, reason) {
        const modal = document.getElementById('modal-review-cancellation');
        const form = document.getElementById('form-review-cancellation');
        const subtitle = document.getElementById('modal-review-subtitle');
        const reqEl = document.getElementById('modal-review-requester');
        const reasonEl = document.getElementById('modal-review-reason');

        if (!modal || !form) return;

        form.action = `/admin/cancellations/${cancId}/review`;
        subtitle.innerText = `Pembatalan #CAN-${cancId} untuk Transaksi #TX-${txId}`;
        reqEl.innerText = requester;
        reasonEl.innerText = `"${reason}"`;

        modal.classList.remove('hidden');
    }

    function closeReviewModal() {
        document.getElementById('modal-review-cancellation')?.classList.add('hidden');
    }

    function toggleResponsibleParty(isApproved) {
        const section = document.getElementById('section-responsible-party');
        const select = document.getElementById('responsible_party');
        if (!section || !select) return;

        if (isApproved) {
            section.classList.remove('hidden');
            select.required = true;
        } else {
            section.classList.add('hidden');
            select.required = false;
        }
    }
</script>
@endsection
