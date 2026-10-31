@if ($transaction->settlement)
    @php
        $settlement = $transaction->settlement;
        $isPayout = strtoupper((string) $settlement->settlement_type) === 'PAYOUT';
        $statusUpper = strtoupper((string) $settlement->status);

        $statusBadge = match($statusUpper) {
            'COMPLETED' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            'PROCESSING' => 'bg-blue-100 text-blue-900 border-blue-300',
            'READY' => 'bg-indigo-100 text-indigo-900 border-indigo-300',
            'PLANNED' => 'bg-amber-100 text-amber-900 border-amber-300',
            default => 'bg-slate-100 text-slate-800 border-slate-300',
        };

        $statusLabel = match($statusUpper) {
            'COMPLETED' => 'Selesai Ditransfer',
            'PROCESSING' => 'Sedang Diproses Bank/Gateway',
            'READY' => 'Siap Dieksekusi Admin',
            'PLANNED' => 'Menunggu Antrean Layak',
            default => $statusUpper,
        };

        // KNF-06: Otorisasi Rekening Privat:
        // Payout tujuan Seller -> tampil lengkap ke Seller & Admin; tersamar ke Buyer.
        // Refund tujuan Buyer -> tampil lengkap ke Buyer & Admin; tersamar ke Seller.
        $isAccountOwner = $isPayout ? ($isSeller ?? false) : ($isBuyer ?? false);
        $canViewFullAccount = ($isAdmin ?? false) || $isAccountOwner;

        $rawNumber = (string) ($settlement->destination_number ?? '');
        if ($canViewFullAccount) {
            $displayNumber = $rawNumber ?: '-';
        } else {
            $len = strlen($rawNumber);
            if ($len <= 4) {
                $displayNumber = '****';
            } elseif ($len <= 8) {
                $displayNumber = substr($rawNumber, 0, 2) . str_repeat('*', $len - 4) . substr($rawNumber, -2);
            } else {
                $displayNumber = substr($rawNumber, 0, 4) . str_repeat('*', $len - 7) . substr($rawNumber, -3);
            }
        }
    @endphp

    <div class="glass-card p-5 sm:p-6 border-indigo-200/80 bg-gradient-to-r from-indigo-500/5 via-sky-50/20 to-white/90 shadow-sm space-y-4" data-cy="settlement-detail-card">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-indigo-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $isPayout ? 'bg-indigo-600 text-white' : 'bg-sky-600 text-white' }} flex items-center justify-center font-bold shadow-xs shrink-0">
                    @if ($isPayout)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    @endif
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>{{ $isPayout ? 'Rincian Settlement Payout (Penjual)' : 'Rincian Pengembalian Dana (Refund Pembeli)' }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border uppercase tracking-wide {{ $statusBadge }}" data-cy="settlement-status-badge">
                            {{ $statusLabel }}
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        {{ $isPayout ? 'Penyaluran dana escrow hasil penjualan produk' : 'Pengembalian dana escrow kepada pembeli atas pembatalan / putusan sengketa' }}
                    </p>
                </div>
            </div>

            @if ($settlement->reference_no)
                <div class="text-left sm:text-right shrink-0 p-2.5 rounded-xl bg-white/80 border border-slate-200">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Nomor Referensi</span>
                    <span class="text-xs font-mono font-bold text-indigo-700" data-cy="settlement-reference-no">
                        {{ $settlement->reference_no }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Breakdown Nominal Finansial Settlement -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs bg-white/70 p-3.5 rounded-xl border border-slate-200/80">
            <div>
                <span class="block text-[10px] uppercase font-bold text-slate-400">Nominal Bruto Settlement</span>
                <span class="text-sm font-extrabold text-slate-800" data-cy="settlement-gross-amount">
                    Rp {{ number_format((float) $settlement->gross_amount, 0, ',', '.') }}
                </span>
            </div>

            <div>
                <span class="block text-[10px] uppercase font-bold text-slate-400">Potongan KEEP Penjual</span>
                <span class="text-sm font-extrabold {{ (float) $settlement->keep_deducted_amount > 0 ? 'text-rose-600' : 'text-slate-500' }}" data-cy="settlement-keep-deducted">
                    {{ (float) $settlement->keep_deducted_amount > 0 ? '- Rp ' . number_format((float) $settlement->keep_deducted_amount, 0, ',', '.') : 'Rp 0' }}
                </span>
            </div>

            <div>
                <span class="block text-[10px] uppercase font-bold text-slate-400">
                    {{ $isPayout ? 'Nominal Bersih Diterima Penjual' : 'Total Refund Diterima Pembeli' }}
                </span>
                <span class="text-sm font-black text-emerald-700" data-cy="settlement-net-amount">
                    Rp {{ number_format((float) $settlement->amount, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Informasi Rekening / Tujuan Pencairan (Dengan Masking KNF-06) -->
        <div class="bg-white/80 p-4 rounded-xl border border-slate-200/80 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Tujuan Pencairan Dana
                </span>
                @if (! $canViewFullAccount)
                    <span class="text-[10px] text-slate-400 italic">
                        (Nomor rekening disamarkan untuk privasi finansial)
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Metode / Provider</span>
                    <span class="font-bold text-slate-800">
                        {{ $settlement->destination_provider ?: $settlement->destination_type }} ({{ $settlement->destination_type }})
                    </span>
                </div>

                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Nomor Rekening / E-Wallet</span>
                    <span class="font-mono font-bold text-slate-800" data-cy="settlement-destination-number">
                        {{ $displayNumber }}
                    </span>
                </div>

                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Nama Pemilik Akun</span>
                    <span class="font-bold text-slate-800">
                        {{ $settlement->destination_holder ?: '-' }}
                    </span>
                </div>
            </div>

            <!-- Tampilan QRIS jika ada & berhak melihat -->
            @if ($settlement->destination_qris_image_url && $canViewFullAccount)
                <div class="pt-2 border-t border-slate-100 flex items-center gap-3">
                    <span class="text-[11px] font-medium text-slate-600">Dokumen QRIS:</span>
                    <a
                        href="{{ asset('storage/' . $settlement->destination_qris_image_url) }}"
                        target="_blank"
                        class="text-xs text-indigo-600 hover:underline font-bold"
                    >
                        Lihat Gambar QRIS &rarr;
                    </a>
                </div>
            @endif
        </div>

        <!-- Catatan & Bukti Transfer Settlement -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs bg-slate-50/80 p-3 rounded-xl border border-slate-200/60">
            <div>
                <span class="font-bold text-slate-700">Catatan Administratif:</span>
                <span class="text-slate-600">{{ $settlement->note ?: 'Settlement diproses secara tertib sesuai SOP Escrow MarketTrust.' }}</span>
                @if ($settlement->completed_at)
                    <div class="text-[10px] text-slate-400 mt-0.5">
                        Selesai pada {{ $settlement->completed_at->translatedFormat('d F Y, H:i') }} WIB &bull; Dicatat oleh Admin: <strong>@<span>{{ $settlement->recordedBy?->username ?? 'Admin' }}</span></strong>
                    </div>
                @endif
            </div>

            <!-- Bukti Berkas Settlement -->
            @if ($settlement->evidenceFiles && $settlement->evidenceFiles->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    @foreach ($settlement->evidenceFiles as $settlementEvidence)
                        <a
                            href="{{ route('transactions.delivery-evidence', [$transaction, $settlementEvidence, 'inline' => 1]) }}"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-indigo-700 font-bold hover:bg-indigo-50 transition shadow-2xs"
                            data-cy="btn-view-settlement-evidence"
                        >
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Bukti Transfer</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Riwayat Percobaan Gagal / Retry Settlement (Jika Ada) -->
        @if ($settlement->attempts && $settlement->attempts->isNotEmpty())
            <div class="space-y-2 pt-2 border-t border-indigo-100/60">
                <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                    Riwayat Percobaan Pencairan / Penanganan Kendala (UC-29)
                </span>
                <div class="space-y-1.5">
                    @foreach ($settlement->attempts as $attempt)
                        <div class="p-2.5 rounded-lg bg-white/70 border border-slate-200 text-xs flex items-center justify-between gap-2">
                            <div>
                                <span class="font-bold text-slate-800">Percobaan #{{ $attempt->attempt_no }}</span>
                                <span class="text-slate-400">&bull;</span>
                                <span class="text-slate-600">{{ $attempt->reason ?: 'Kendala transfer diproses ulang.' }}</span>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ $attempt->status === 'FAILED' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700' }}">
                                {{ $attempt->status }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
