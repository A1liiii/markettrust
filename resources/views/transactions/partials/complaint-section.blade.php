@php
    $complaint = $transaction->complaint;
    $txStatus = $transaction->status instanceof \App\Enums\TransactionStatus ? $transaction->status->value : (string) $transaction->status;
    $isDelivered = $txStatus === 'DELIVERED';
    $isDisputed = $txStatus === 'DISPUTED' || ($complaint && in_array($complaint->status, ['OPEN', 'REVIEWING']));
    $isResolved = $complaint && $complaint->status === 'RESOLVED';

    // Periksa deadline sengketa
    $sellerDeadline = $transaction->deadlineEvents->where('event_type', 'SELLER_COMPLAINT_RESPONSE_DUE')->last();
    $adminDeadline = $transaction->deadlineEvents->where('event_type', 'ADMIN_DISPUTE_DECISION_DUE')->last();
@endphp

{{-- 1. Kartu Pengajuan Komplain bagi Buyer saat Status DELIVERED dan belum ada komplain --}}
@if ($isDelivered && ! $complaint)
    @if ($isBuyer)
        <div class="glass-card p-5 border-amber-200/80 bg-gradient-to-r from-amber-500/10 via-orange-50/40 to-white/90 shadow-sm" data-cy="buyer-complaint-cta-card">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-amber-900 uppercase tracking-wider">
                            Pesanan Bermasalah atau Tidak Sesuai Kesepakatan?
                        </div>
                        <div class="text-xs sm:text-sm text-slate-700 mt-0.5">
                            Jika hasil digital yang diserahkan rusak, salah, atau tidak valid, Anda dapat mengajukan komplain resmi berbukti sebelum konfirmasi berakhir.
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="openComplaintModal()"
                    class="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs sm:text-sm font-bold flex items-center justify-center gap-2 shadow-md shadow-amber-600/20 active:scale-95 transition shrink-0"
                    data-cy="btn-open-complaint-modal"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Ajukan Komplain</span>
                </button>
            </div>
        </div>

        {{-- Modal Pengajuan Komplain Berbukti --}}
        <div id="complaint-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeComplaintModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200 p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                                ⚠️
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Form Pengajuan Komplain Resmi</h3>
                                <p class="text-[11px] text-slate-500">Komplain akan menghentikan pelepasan dana otomatis (Escrow Hold)</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeComplaintModal()" class="text-slate-400 hover:text-slate-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form action="{{ route('transactions.complaints.store', $transaction) }}" method="POST" enctype="multipart/form-data" class="space-y-4" data-cy="form-file-complaint">
                        @csrf

                        <!-- Alasan Komplain -->
                        <div class="space-y-1">
                            <label for="complaint_reason" class="block text-xs font-bold text-slate-700">
                                Rincian Masalah / Alasan Komplain <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                name="reason"
                                id="complaint_reason"
                                rows="4"
                                required
                                class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 p-3 bg-white transition shadow-inner placeholder:text-slate-400"
                                placeholder="Jelaskan secara spesifik mengapa hasil penyerahan tidak sesuai kesepakatan (misal: kredensial salah, produk tidak dapat diakses, dsb)..."
                                data-cy="input-complaint-reason"
                            >{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Berkas Bukti Resmi Wajib -->
                        <div class="space-y-1">
                            <label for="complaint_evidence_file" class="block text-xs font-bold text-slate-700">
                                Berkas Bukti Pendukung <span class="text-rose-500">* (Wajib, Gambar / PDF, Maks. 5 MB)</span>
                            </label>
                            <div class="p-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 hover:bg-slate-100 transition flex flex-col gap-2">
                                <input
                                    type="file"
                                    name="evidence_file"
                                    id="complaint_evidence_file"
                                    required
                                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 transition cursor-pointer"
                                    data-cy="input-complaint-evidence"
                                >
                                <span class="text-[10px] text-slate-500">Unggah tangkapan layar eror, rekaman akses, atau dokumen bukti sah.</span>
                            </div>
                            @error('evidence_file')
                                <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-900 leading-relaxed">
                            <strong>Konsekuensi Pengajuan:</strong>
                            Transaksi akan berubah status menjadi <span class="font-bold uppercase text-amber-800">DISPUTED</span>. Dana escrow ditahan sampai sengketa diselesaikan. Penjual memiliki waktu 24 jam untuk menanggapi.
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-2">
                            <button
                                type="button"
                                onclick="closeComplaintModal()"
                                class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold flex items-center gap-2 shadow-md shadow-amber-600/20 active:scale-95 transition"
                                data-cy="btn-submit-complaint"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Kirim Komplain Resmi</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function openComplaintModal() {
                const modal = document.getElementById('complaint-modal');
                if (modal) {
                    if (modal.parentElement !== document.body) {
                        document.body.appendChild(modal);
                    }
                    modal.classList.remove('hidden');
                }
            }
            function closeComplaintModal() {
                const modal = document.getElementById('complaint-modal');
                if (modal) modal.classList.add('hidden');
            }
        </script>
    @endif
@endif

{{-- 2. Banner & Panel Sengketa Aktif (DISPUTED: OPEN / REVIEWING) --}}
@if ($complaint && ($isDisputed || $isResolved))
    <div class="glass-card p-5 sm:p-6 border-orange-200/90 bg-gradient-to-r from-orange-500/10 via-amber-50/40 to-white/95 shadow-md space-y-5" data-cy="dispute-workflow-card">
        {{-- Header Status Sengketa --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-orange-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $isResolved ? 'bg-indigo-600' : 'bg-orange-600' }} text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>Pusat Penyelesaian Sengketa Pesanan</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $complaint->status === 'OPEN' ? 'bg-amber-100 text-amber-800 border border-amber-200' : ($complaint->status === 'REVIEWING' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200') }}" data-cy="complaint-status-badge">
                            {{ $complaint->status === 'OPEN' ? 'Menunggu Tanggapan Penjual' : ($complaint->status === 'REVIEWING' ? 'Dalam Investigasi Admin (Bukti Tambahan)' : 'Sengketa Telah Diputuskan') }}
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        Diajukan oleh @<span>{{ $complaint->raisedBy?->username }}</span> pada {{ $complaint->created_at->translatedFormat('d F Y, H:i') }} WIB
                    </p>
                </div>
            </div>

            {{-- Deadline SLA Card --}}
            @if (! $isResolved)
                <div class="text-left sm:text-right shrink-0 p-3 rounded-xl bg-white/80 border border-orange-200">
                    @if ($complaint->status === 'OPEN' && $sellerDeadline && $sellerDeadline->new_deadline)
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-amber-700">
                            Batas Tanggapan Penjual
                        </span>
                        <span class="text-xs sm:text-sm font-extrabold text-amber-900" data-cy="seller-response-due">
                            {{ $sellerDeadline->new_deadline->translatedFormat('d M Y, H:i') }} WIB
                        </span>
                        <span class="block text-[10px] text-slate-500">(24 jam sejak komplain)</span>
                    @elseif ($adminDeadline && $adminDeadline->new_deadline)
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-indigo-700">
                            Tenggat Keputusan Admin
                        </span>
                        <span class="text-xs sm:text-sm font-extrabold text-indigo-900" data-cy="admin-decision-due">
                            {{ $adminDeadline->new_deadline->translatedFormat('d M Y, H:i') }} WIB
                        </span>
                        <span class="block text-[10px] text-slate-500">(SLA Investigasi Administrator)</span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Detail Komplain Pembeli & Bukti --}}
        <div class="bg-white/90 p-4 rounded-xl border border-orange-200/80 space-y-3">
            <span class="block text-[10px] uppercase font-bold text-slate-400">
                Alasan & Rincian Komplain Pembeli:
            </span>
            <div class="text-xs sm:text-sm text-slate-800 whitespace-pre-line leading-relaxed font-medium" data-cy="display-complaint-reason">
                {{ $complaint->reason }}
            </div>

            <!-- Berkas Bukti Pembeli -->
            @if ($complaint->evidenceFiles->count() > 0)
                <div class="pt-2 border-t border-slate-100">
                    <span class="block text-[10px] uppercase font-bold text-slate-400 mb-2">Berkas Bukti Resmi Pembeli:</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($complaint->evidenceFiles as $evidence)
                            <div class="p-2.5 rounded-lg bg-orange-50/60 border border-orange-200/70 flex items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2 truncate">
                                    <svg class="w-4 h-4 text-orange-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span class="truncate font-semibold text-slate-800" title="{{ $evidence->file_name }}">{{ $evidence->file_name }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a
                                        href="{{ route('transactions.complaints.evidence', [$transaction, $evidence, 'inline' => 1]) }}"
                                        target="_blank"
                                        class="px-2 py-1 rounded bg-white hover:bg-slate-100 text-slate-700 font-medium transition text-[11px] border border-slate-200"
                                        data-cy="btn-preview-complaint-evidence"
                                    >
                                        Lihat
                                    </a>
                                    <a
                                        href="{{ route('transactions.complaints.evidence', [$transaction, $evidence]) }}"
                                        class="px-2 py-1 rounded bg-orange-600 hover:bg-orange-700 text-white font-bold transition text-[11px]"
                                        data-cy="btn-download-complaint-evidence"
                                    >
                                        Unduh
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Tanggapan Penjual & Riwayat --}}
        @if ($complaint->responses->count() > 0)
            <div class="space-y-3">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Tanggapan & Klarifikasi Penjual
                </span>
                <div class="space-y-2.5">
                    @foreach ($complaint->responses as $response)
                        <div class="bg-white/95 p-4 rounded-xl border border-slate-200 space-y-2.5 shadow-2xs" data-cy="complaint-response-item">
                            <div class="flex items-center justify-between text-xs pb-1.5 border-b border-slate-100">
                                <div class="font-bold text-slate-900">
                                    @<span>{{ $response->responder?->username }}</span>
                                    <span class="ml-1 text-[10px] px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800 font-semibold">Penjual</span>
                                </div>
                                <span class="text-[10px] text-slate-400">
                                    {{ $response->created_at?->translatedFormat('d M Y, H:i') }} WIB
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line" data-cy="display-response-text">
                                {{ $response->response_text }}
                            </p>

                            @if ($response->evidenceFiles && $response->evidenceFiles->count() > 0)
                                <div class="pt-2 border-t border-slate-100">
                                    <span class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Lampiran Bukti Penjual:</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach ($response->evidenceFiles as $respEvidence)
                                            <div class="p-2 rounded bg-slate-50 border border-slate-200 flex items-center justify-between gap-2 text-xs">
                                                <span class="truncate font-medium text-slate-700 text-[11px]" title="{{ $respEvidence->file_name }}">
                                                    {{ $respEvidence->file_name }}
                                                </span>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <a
                                                        href="{{ route('transactions.complaints.evidence', [$transaction, $respEvidence, 'inline' => 1]) }}"
                                                        target="_blank"
                                                        class="px-2 py-0.5 rounded bg-white hover:bg-slate-200 text-slate-700 text-[10px] font-semibold border"
                                                    >
                                                        Lihat
                                                    </a>
                                                    <a
                                                        href="{{ route('transactions.complaints.evidence', [$transaction, $respEvidence]) }}"
                                                        class="px-2 py-0.5 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold"
                                                    >
                                                        Unduh
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Form Tanggapan Khusus Penjual (Jika Status Belum RESOLVED) --}}
        @if ($isSeller && ! $isResolved)
            <div class="p-4 sm:p-5 rounded-xl bg-white border border-indigo-200 shadow-sm space-y-3" data-cy="seller-response-form-card">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-indigo-950">Beri Tanggapan / Klarifikasi Penjual</span>
                    <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold">Khusus Penjual</span>
                </div>
                <p class="text-xs text-slate-500">
                    Sampaikan penjelasan atau sanggahan beserta berkas bukti bahwa item telah dikirimkan sesuai kesepakatan. Tanggapan sah Anda akan memulai evaluasi 24 jam oleh Administrator.
                </p>

                <form action="{{ route('transactions.complaints.respond', [$transaction, $complaint]) }}" method="POST" enctype="multipart/form-data" class="space-y-3" data-cy="form-seller-respond">
                    @csrf

                    <div>
                        <textarea
                            name="response_text"
                            rows="3"
                            required
                            class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 p-3 bg-white transition placeholder:text-slate-400"
                            placeholder="Tuliskan tanggapan atau penjelasan resmi Anda mengenai komplain ini..."
                            data-cy="input-response-text"
                        >{{ old('response_text') }}</textarea>
                        @error('response_text')
                            <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="p-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="text-[11px] text-slate-600">
                            Lampirkan bukti sanggahan (Opsional, Maks. 5 MB)
                        </div>
                        <input
                            type="file"
                            name="evidence_file"
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            class="text-xs text-slate-600 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer"
                            data-cy="input-response-evidence"
                        >
                    </div>

                    <div class="flex justify-end pt-1">
                        <button
                            type="submit"
                            class="glass-btn-primary px-5 py-2 text-xs sm:text-sm font-bold flex items-center gap-2 shadow-md shadow-indigo-500/20 active:scale-95 transition"
                            data-cy="btn-submit-response"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <span>Kirim Tanggapan Resmi</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- 3. Hasil Putusan Final Sengketa (RESOLVED) --}}
        @if ($isResolved)
            <div class="p-4 sm:p-5 rounded-xl border {{ $complaint->decision === 'PAYOUT' ? 'bg-emerald-50/90 border-emerald-300' : 'bg-rose-50/90 border-rose-300' }} space-y-3" data-cy="dispute-resolved-card">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b {{ $complaint->decision === 'PAYOUT' ? 'border-emerald-200' : 'border-rose-200' }}">
                    <div class="flex items-center gap-2">
                        <span class="text-base">{{ $complaint->decision === 'PAYOUT' ? '✅' : '🔄' }}</span>
                        <h3 class="text-sm sm:text-base font-black {{ $complaint->decision === 'PAYOUT' ? 'text-emerald-950' : 'text-rose-950' }}">
                            Keputusan Resmi Administrator: {{ $complaint->decision === 'PAYOUT' ? 'PAYOUT (Dimenangkan Penjual)' : 'REFUND (Dimenangkan Pembeli)' }}
                        </h3>
                    </div>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $complaint->decision === 'PAYOUT' ? 'bg-emerald-200 text-emerald-900' : 'bg-rose-200 text-rose-900' }}">
                        RESOLVED
                    </span>
                </div>

                <div class="text-xs sm:text-sm text-slate-800 space-y-1">
                    <span class="block text-[10px] uppercase font-bold text-slate-500">Alasan & Pertimbangan Keputusan Admin:</span>
                    <p class="leading-relaxed bg-white/80 p-3 rounded-lg border border-slate-200" data-cy="display-decision-reason">
                        {{ $complaint->decision_reason }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 text-[11px] text-slate-600">
                    <div>
                        Diputuskan oleh: <strong>{{ $complaint->decidedBy?->name ?? ($complaint->decidedBy?->username ?? 'Administrator') }}</strong>
                        &bull; {{ $complaint->decided_at?->translatedFormat('d F Y, H:i') }} WIB
                    </div>
                    <div class="font-bold {{ $complaint->decision === 'PAYOUT' ? 'text-emerald-700' : 'text-rose-700' }}">
                        Instruksi Settlement: <span class="uppercase">PLANNED (Antrean Pencairan Manual)</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif

