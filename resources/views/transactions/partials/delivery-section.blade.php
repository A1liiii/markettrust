@php
    $txStatus = $transaction->status instanceof \App\Enums\TransactionStatus ? $transaction->status->value : (string) $transaction->status;
    $isDeliveredOrLater = in_array($txStatus, ['DELIVERED', 'CONFIRMED', 'DISPUTED', 'COMPLETED']);
    $isPaid = $txStatus === 'PAID';
    $isOverdue = $transaction->isDeliveryOverdue();
@endphp

@if ($isPaid)
    @if ($isSeller)
        <!-- Kartu Form Penyerahan Hasil & Bukti Pesanan (Khusus Seller) -->
        <div class="glass-card p-5 sm:p-6 border-indigo-200/80 bg-gradient-to-r from-indigo-500/5 via-sky-50/40 to-white/80 shadow-sm space-y-5" data-cy="seller-delivery-form-card">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-indigo-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>Form Penyerahan Hasil Resmi</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">Khusus Penjual</span>
                        </h2>
                        <p class="text-xs text-slate-500">
                            Pembayaran Buyer telah diamankan di Escrow. Silakan serahkan hasil pesanan beserta bukti penyerahan.
                        </p>
                    </div>
                </div>

                @if ($transaction->delivery_due_at)
                    <div class="text-left sm:text-right shrink-0">
                        <span class="block text-[10px] font-bold uppercase tracking-wider {{ $isOverdue ? 'text-rose-600' : 'text-slate-400' }}">
                            {{ $isOverdue ? 'Tenggat Waktu Lewat' : 'Batas Penyerahan' }}
                        </span>
                        <span class="text-xs sm:text-sm font-extrabold {{ $isOverdue ? 'text-rose-700 font-black' : 'text-slate-800' }}">
                            {{ $transaction->delivery_due_at->translatedFormat('d M Y, H:i') }} WIB
                        </span>
                        @if ($isOverdue)
                            <span class="block text-[10px] text-rose-500 font-semibold">Harap segera menyerahkan pesanan</span>
                        @endif
                    </div>
                @endif
            </div>

            <form action="{{ route('transactions.deliver', $transaction) }}" method="POST" enctype="multipart/form-data" class="space-y-4" data-cy="form-deliver-order">
                @csrf

                <!-- Catatan Penyerahan -->
                <div class="space-y-1">
                    <label for="delivery_note" class="block text-xs font-bold text-slate-700">
                        Catatan & Rincian Penyerahan <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        name="delivery_note"
                        id="delivery_note"
                        rows="4"
                        required
                        class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 p-3 bg-white/90 transition shadow-inner placeholder:text-slate-400"
                        placeholder="Tuliskan petunjuk, kredensial akun, kode lisensi, atau rincian instruksi akses produk digital untuk Pembeli..."
                        data-cy="input-delivery-note"
                    >{{ old('delivery_note') }}</textarea>
                    @error('delivery_note')
                        <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tautan Hasil (URL Eksternal) -->
                <div class="space-y-1">
                    <label for="result_link" class="block text-xs font-bold text-slate-700">
                        Tautan Hasil Digital <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        </div>
                        <input
                            type="url"
                            name="result_link"
                            id="result_link"
                            value="{{ old('result_link') }}"
                            class="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 bg-white/90 transition placeholder:text-slate-400"
                            placeholder="https://drive.google.com/... atau tautan repositori hasil"
                            data-cy="input-result-link"
                        >
                    </div>
                    @error('result_link')
                        <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unggah Berkas Bukti Resmi -->
                <div class="space-y-1">
                    <label for="evidence_file" class="block text-xs font-bold text-slate-700">
                        Unggah Bukti Resmi Penyerahan <span class="text-slate-400 font-normal">(Gambar / PDF, Maks. 5 MB)</span>
                    </label>
                    <div class="p-3 rounded-xl border border-dashed border-slate-300 bg-white/70 hover:bg-white transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2 text-xs text-slate-600">
                            <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            <span>Pilih berkas tangkapan layar, bukti kirim, atau dokumen hasil</span>
                        </div>
                        <input
                            type="file"
                            name="evidence_file"
                            id="evidence_file"
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer"
                            data-cy="input-evidence-file"
                        >
                    </div>
                    @error('evidence_file')
                        <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tombol Submit Penyerahan -->
                <div class="flex items-center justify-between pt-2">
                    <span class="text-[11px] text-slate-500">
                        Setelah diserahkan, Pembeli memiliki waktu 1 jam untuk memeriksa dan mengonfirmasi.
                    </span>
                    <button
                        type="submit"
                        class="glass-btn-primary px-5 py-2.5 text-xs sm:text-sm font-bold flex items-center gap-2 shadow-md shadow-indigo-500/20 active:scale-95 transition"
                        data-cy="btn-submit-delivery"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Serahkan Hasil Resmi</span>
                    </button>
                </div>
            </form>
        </div>
    @elseif ($isBuyer)
        <!-- Banner Menunggu Penyerahan bagi Buyer -->
        <div class="glass-card p-5 border-blue-200/80 bg-gradient-to-r from-blue-500/10 via-sky-50/50 to-white/70 shadow-sm" data-cy="buyer-waiting-delivery-card">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0 animate-pulse">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-blue-900 uppercase tracking-wider">
                            Pembayaran Berhasil — Menunggu Penjual Menyerahkan Item
                        </div>
                        <div class="text-xs sm:text-sm text-slate-700 mt-0.5">
                            Dana Anda aman tersimpan di sistem Escrow. Penjual sedang menyiapkan pesanan sesuai kesepakatan.
                        </div>
                    </div>
                </div>

                @if ($transaction->delivery_due_at)
                    <div class="text-left sm:text-right shrink-0">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Tenggat Penyerahan Penjual</span>
                        <span class="text-sm font-bold text-indigo-700">
                            {{ $transaction->delivery_due_at->translatedFormat('d M Y, H:i') }} WIB
                        </span>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endif

@if ($isDeliveredOrLater && $transaction->delivered_at)
    <!-- Panel Rincian Hasil & Bukti Penyerahan (Untuk Buyer, Seller, dan Admin) -->
    <div class="glass-card p-5 sm:p-6 border-purple-200/80 bg-gradient-to-r from-purple-500/5 via-indigo-50/30 to-white/90 shadow-sm space-y-4" data-cy="delivered-summary-card">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-purple-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>Hasil Penyerahan Resmi</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                            Telah Diserahkan
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        Diserahkan pada {{ $transaction->delivered_at->translatedFormat('d F Y, H:i') }} WIB
                    </p>
                </div>
            </div>

            @if ($txStatus === 'DELIVERED' && $transaction->confirmation_due_at)
                <div class="text-left sm:text-right shrink-0 p-2.5 rounded-xl bg-purple-50 border border-purple-200/60">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-purple-700">
                        Batas Konfirmasi Penerimaan
                    </span>
                    <span class="text-xs sm:text-sm font-extrabold text-purple-900" data-cy="confirmation-due-text">
                        {{ $transaction->confirmation_due_at->translatedFormat('d F Y, H:i') }} WIB
                    </span>
                    <span class="block text-[10px] text-slate-500">(1 Jam sejak penyerahan)</span>
                </div>
            @endif
        </div>

        <!-- Catatan Hasil -->
        <div class="bg-white/80 p-4 rounded-xl border border-slate-200/80 space-y-2">
            <span class="block text-[10px] uppercase font-bold text-slate-400">
                Catatan dari Penjual:
            </span>
            <div class="text-xs sm:text-sm text-slate-800 whitespace-pre-line leading-relaxed" data-cy="display-delivery-note">
                {{ $transaction->delivery_note ?: 'Tidak ada catatan tambahan.' }}
            </div>
        </div>

        <!-- Tautan Hasil & Berkas Bukti -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            @if ($transaction->result_link)
                <div class="p-3 rounded-xl bg-white/70 border border-slate-200 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 truncate">
                        <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span class="font-semibold text-slate-700 truncate">Tautan Hasil Digital</span>
                    </div>
                    <a
                        href="{{ $transaction->result_link }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold shrink-0 transition"
                        data-cy="link-result-external"
                    >
                        Buka Tautan &rarr;
                    </a>
                </div>
            @endif

            @if ($transaction->evidenceFiles && $transaction->evidenceFiles->isNotEmpty())
                @foreach ($transaction->evidenceFiles as $evidence)
                    <div class="p-3 rounded-xl bg-white/70 border border-slate-200 flex items-center justify-between gap-2" data-cy="evidence-file-item">
                        <div class="flex items-center gap-2 truncate">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="font-medium text-slate-800 truncate" title="{{ $evidence->file_name }}">
                                {{ $evidence->file_name }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a
                                href="{{ route('transactions.delivery-evidence', [$transaction, $evidence, 'inline' => 1]) }}"
                                target="_blank"
                                class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition text-[11px]"
                                title="Lihat Bukti"
                                data-cy="btn-preview-evidence"
                            >
                                Lihat
                            </a>
                            <a
                                href="{{ route('transactions.delivery-evidence', [$transaction, $evidence]) }}"
                                class="px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold transition text-[11px]"
                                title="Unduh Berkas"
                                data-cy="btn-download-evidence"
                            >
                                Unduh
                            </a>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endif
