<!-- Modal Laporkan Akun (UC-30) -->
<div id="account-report-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop Blur -->
    <div id="account-report-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeAccountReportModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white/95 backdrop-blur-xl border border-white/80 p-6 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg space-y-5">
            
            <!-- Modal Header -->
            <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-lg shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900" id="modal-title">
                            Laporkan Akun Pengguna
                        </h3>
                        <p class="text-xs text-slate-500">
                            Terlapor: <strong class="text-rose-600" id="report-modal-username">@pengguna</strong>
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeAccountReportModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg transition" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Notice Banner -->
            <div class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200/80 text-amber-900 text-xs flex items-start gap-2.5">
                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="leading-relaxed">
                    Pelaporan tidak otomatis mengurangi reputasi atau menyanksi pihak terlapor sebelum dilakukan peninjauan resmi oleh Admin. Laporan palsu atau tanpa dasar dapat berdampak pada evaluasi akun pelapor.
                </div>
            </div>

            <!-- Form -->
            <form action="{{ route('reports.accounts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="reported_user_id" id="report-input-user-id" value="">
                <input type="hidden" name="transaction_id" id="report-input-transaction-id" value="">

                <div>
                    <label for="report-reason" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Alasan Pelaporan <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        name="reason"
                        id="report-reason"
                        rows="4"
                        minlength="10"
                        maxlength="3000"
                        required
                        placeholder="Jelaskan secara spesifik indikasi penipuan, penyerahan tidak sesuai, atau pelanggaran ketentuan yang terjadi..."
                        class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition resize-none"
                    >{{ old('reason') }}</textarea>
                    <span class="block text-[11px] text-slate-400 mt-1">Minimal 10 karakter, maksimal 3000 karakter.</span>
                </div>

                <div>
                    <label for="report-evidence" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Berkas Bukti Pendukung (Opsional)
                    </label>
                    <input
                        type="file"
                        name="evidence"
                        id="report-evidence"
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100 transition cursor-pointer border border-slate-200 rounded-xl"
                    >
                    <span class="block text-[11px] text-slate-400 mt-1">Format: JPG, JPEG, PNG, WEBP, atau PDF (Maks. 5 MB).</span>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button
                        type="button"
                        onclick="closeAccountReportModal()"
                        class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-600 text-white text-xs sm:text-sm font-bold shadow-md shadow-rose-500/20 active:scale-95 transition flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span>Kirim Laporan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openAccountReportModal(userId, username, transactionId = null) {
        const modal = document.getElementById('account-report-modal');
        const inputUserId = document.getElementById('report-input-user-id');
        const inputTxId = document.getElementById('report-input-transaction-id');
        const textUsername = document.getElementById('report-modal-username');
        const textareaReason = document.getElementById('report-reason');
        const inputEvidence = document.getElementById('report-evidence');

        if (inputUserId) inputUserId.value = userId;
        if (inputTxId) inputTxId.value = transactionId || '';
        if (textUsername) textUsername.textContent = '@' + username;
        if (textareaReason) textareaReason.value = '';
        if (inputEvidence) inputEvidence.value = '';

        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeAccountReportModal() {
        const modal = document.getElementById('account-report-modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
</script>
