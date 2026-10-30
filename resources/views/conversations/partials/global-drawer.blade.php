<!-- Global Floating Chat Widget Experience (MarketTrust Bubble Pop UI) -->
@auth
<!-- 1. Floating Action Bubble Launcher (Sudut Kanan Bawah) -->
<div id="global-dm-floating-launcher"
     class="fixed bottom-6 right-6 z-40 transition-all duration-400 ease-[cubic-bezier(0.16,1,0.3,1)]"
     title="Buka Obrolan Langsung">
    <button type="button"
            id="global-dm-launcher-btn"
            class="dm-floating-launcher relative w-14 h-14 rounded-full text-white flex items-center justify-center cursor-pointer shadow-xl focus:outline-none focus:ring-4 focus:ring-indigo-300/50"
            aria-label="Buka Obrolan">
        <!-- Chat Bubble Icon (saat widget tertutup) -->
        <svg id="global-dm-launcher-icon-chat" class="w-6 h-6 transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <!-- Close / Minimize Icon (saat widget terbuka) -->
        <svg id="global-dm-launcher-icon-close" class="w-6 h-6 hidden transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <!-- Floating unread pulse badge -->
        <span id="global-dm-launcher-badge"
              class="hidden absolute -top-1 -right-1 bg-rose-500 text-white font-bold text-[10px] min-w-5 h-5 px-1.5 rounded-full flex items-center justify-center border-2 border-white shadow-md animate-bounce">
            0
        </span>
    </button>
</div>

<!-- 2. Image Lightbox Modal for Full View Zoom (Hanya aktif saat preview foto diklik) -->
<div id="global-dm-lightbox-modal"
     class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md hidden items-center justify-center p-4 transition-all duration-400 ease-[cubic-bezier(0.16,1,0.3,1)] opacity-0"
     aria-hidden="true">
    <div class="relative max-w-3xl max-h-[90vh] flex flex-col items-center">
        <button type="button"
                id="global-dm-lightbox-close"
                class="absolute -top-10 right-0 p-1.5 rounded-full text-white/80 hover:text-white bg-white/10 hover:bg-white/20 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] cursor-pointer active:scale-95"
                title="Tutup Preview">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <img id="global-dm-lightbox-img" src="" alt="Preview Gambar" class="max-h-[80vh] max-w-full rounded-2xl shadow-2xl object-contain border border-white/20 transition-transform duration-400 ease-[cubic-bezier(0.16,1,0.3,1)]">
        <div class="mt-3 flex items-center justify-between w-full px-2 text-white text-xs">
            <span id="global-dm-lightbox-name" class="truncate font-medium text-slate-300 max-w-[70%]"></span>
            <a id="global-dm-lightbox-download" href="#" class="glass-btn-primary text-xs py-1.5 px-3 rounded-xl shrink-0" download>Unduh File</a>
        </div>
    </div>
</div>

<!-- 3. Floating Chat Widget Window (Ringkas, mengambang di pojok kanan bawah, tanpa backdrop pengganggu) -->
<div id="global-dm-drawer"
     class="dm-floating-widget is-closed fixed bottom-24 right-4 sm:right-6 w-[360px] sm:w-[390px] h-[520px] sm:h-[560px] max-h-[calc(100vh-7.5rem)] rounded-3xl shadow-2xl bg-white/98 backdrop-blur-2xl border border-slate-200/90 flex flex-col z-40 overflow-hidden"
     role="dialog"
     aria-modal="false">

    <!-- ========================================== -->
    <!-- VIEW 1: DAFTAR CHAT (Inbox List)           -->
    <!-- ========================================== -->
    <div id="global-dm-inbox-view" class="flex flex-col h-full">
        <!-- Inbox Header -->
        <div class="p-3.5 border-b border-slate-200/80 bg-white/90 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/20">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-slate-900 text-sm sm:text-base">Pesan</h3>
                        <span id="global-dm-total-unread-badge" class="hidden text-[10px] font-bold px-2 py-0.2 rounded-full bg-indigo-600 text-white shadow-xs">0</span>
                    </div>
                    <p class="text-[10px] text-slate-500 font-medium">Obrolan Aman &bull; MarketTrust</p>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <!-- Close Button -->
                <button type="button"
                        class="global-dm-close-btn p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                        title="Tutup Widget">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Inbox Conversation Items Container -->
        <div id="global-dm-inbox-container" class="dm-chat-scroll flex-1 overflow-y-auto p-3 space-y-2 bg-slate-50/50">
            <!-- Loading Spinner -->
            <div id="global-dm-inbox-loading" class="py-20 text-center text-slate-400">
                <div class="relative w-8 h-8 mx-auto mb-2.5">
                    <div class="w-8 h-8 rounded-full border-2 border-indigo-200 border-t-indigo-600 animate-spin"></div>
                </div>
                <p class="text-xs font-semibold text-slate-600">Memuat obrolan...</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Menyiapkan daftar percakapan</p>
            </div>

            <!-- Empty State -->
            <div id="global-dm-inbox-empty" class="hidden py-16 text-center text-slate-400">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center mx-auto mb-2.5 text-indigo-500 shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-xs font-bold text-slate-800">Belum Ada Percakapan</p>
                <p class="text-[11px] text-slate-500 mt-1 max-w-[220px] mx-auto leading-relaxed">Mulai obrolan dari katalog Marketplace atau thread Forum.</p>
            </div>

            <!-- List Elements Container (Pop Cards) -->
            <div id="global-dm-inbox-list" class="space-y-2"></div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- VIEW 2: RUANG OBROLAN (Active Chat Thread) -->
    <!-- ========================================== -->
    <div id="global-dm-thread-view" class="hidden flex flex-col h-full">
        <!-- Thread Header: Tombol Kembali, Info Lawan Bicara, Minimize & Close -->
        <div class="p-2.5 sm:p-3 border-b border-slate-200/80 bg-white/90 backdrop-blur-md flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <!-- Back to Inbox Button -->
                <button type="button"
                        id="global-dm-back-to-inbox-btn"
                        class="p-1.5 rounded-xl text-slate-500 hover:text-indigo-600 hover:bg-indigo-50/80 transition cursor-pointer shrink-0"
                        title="Kembali ke Daftar Chat">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <!-- Avatar & Opponent Info -->
                <div id="global-dm-thread-avatar" class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                    ?
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <h3 id="global-dm-thread-username" class="font-bold text-slate-900 text-xs sm:text-sm truncate">
                            @<span>Pengguna</span>
                        </h3>
                        <span id="global-dm-thread-status" class="inline-block w-2 h-2 rounded-full bg-emerald-500 shadow-xs shrink-0" title="Akun Terverifikasi"></span>
                    </div>
                    <p class="text-[10px] text-slate-400 truncate">Terverifikasi &bull; Escrow Trust</p>
                </div>
            </div>

            <div class="flex items-center gap-1 shrink-0">
                <!-- Close Button -->
                <button type="button"
                        class="global-dm-close-btn p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                        title="Tutup Widget">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Context Ribbon (Product / Forum Context Preview) -->
        <div id="global-dm-thread-context" class="hidden mx-2.5 mt-2 p-2 rounded-2xl bg-white/95 border border-slate-200/90 shadow-2xs flex items-center justify-between gap-2 shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <span id="global-dm-context-badge" class="font-bold text-[8px] uppercase px-1 py-0.2 rounded bg-indigo-50 text-indigo-700 border border-indigo-200/60 inline-block"></span>
                    <h4 id="global-dm-context-title" class="text-[11px] font-semibold text-slate-800 truncate"></h4>
                </div>
            </div>
            <div id="global-dm-context-price" class="text-[10px] font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded-lg shrink-0"></div>
        </div>

        <!-- Agreement Ribbon (Jika percakapan memiliki kesepakatan transaksi) -->
        <div id="global-dm-agreement-ribbon" class="hidden mx-2.5 mt-1.5 p-2 rounded-2xl bg-gradient-to-r from-indigo-50/90 via-purple-50/50 to-white border border-indigo-200/80 shadow-2xs items-center justify-between gap-2 shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span id="global-dm-agreement-status" class="font-bold text-[8px] uppercase px-1 py-0.2 rounded bg-indigo-100 text-indigo-800 inline-block">DRAFT</span>
                        <span id="global-dm-agreement-rev" class="font-mono text-[8px] text-slate-500">Rev #1</span>
                    </div>
                    <h4 id="global-dm-agreement-title" class="text-[10px] font-semibold text-slate-800 truncate"></h4>
                </div>
            </div>
            <a id="global-dm-agreement-action-link" href="#" class="glass-btn-primary text-[9px] py-1 px-2 rounded-lg font-bold shrink-0">
                Buka Kesepakatan
            </a>
        </div>

        <!-- Chat Stream (Message Bubbles) -->
        <div id="global-dm-thread-messages" class="dm-chat-scroll flex-1 p-3 overflow-y-auto space-y-2.5 min-h-0 bg-slate-50/40">
            <!-- Thread Loading -->
            <div id="global-dm-thread-loading" class="h-full flex items-center justify-center py-16 text-slate-400">
                <div class="text-center">
                    <div class="w-7 h-7 rounded-full border-2 border-indigo-200 border-t-indigo-600 animate-spin mx-auto mb-2"></div>
                    <p class="text-xs font-semibold text-slate-600">Memuat pesan...</p>
                </div>
            </div>

            <!-- Empty Chat State -->
            <div id="global-dm-thread-empty" class="hidden text-center py-12 text-slate-400">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center mx-auto mb-2 text-indigo-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-xs font-bold text-slate-700">Belum ada pesan.</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Kirim pesan pertama atau gunakan template.</p>
            </div>

            <!-- Message List Container -->
            <div id="global-dm-thread-message-list" class="space-y-2.5"></div>
        </div>

        <!-- Bottom Controls & Input Area -->
        <div class="p-2.5 border-t border-slate-200/80 bg-white/95 backdrop-blur-md shrink-0">
            <!-- Quick Message Templates -->
            <div class="mb-1.5 flex items-center justify-between text-xs">
                <div class="relative inline-block text-left" id="global-dm-template-wrapper">
                    <button type="button"
                            id="global-dm-template-btn"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-slate-600 hover:text-indigo-600 bg-slate-100 hover:bg-indigo-50 border border-slate-200/80 transition text-[10px] font-semibold cursor-pointer">
                        <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>Template Pesan</span>
                        <svg class="w-2.5 h-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="global-dm-template-menu" class="hidden absolute left-0 bottom-full mb-1.5 w-64 rounded-2xl bg-white shadow-xl border border-slate-200/90 p-1.5 z-50 space-y-0.5">
                        <div class="px-2 py-0.5 text-[9px] font-bold text-slate-400 uppercase tracking-wider">Pilih template pesan</div>
                        <button type="button" class="global-dm-template-item w-full text-left px-2 py-1 text-xs rounded-xl text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition font-medium" data-text="Halo, apakah produk/postingan ini masih tersedia?">
                            "Halo, apakah masih tersedia?"
                        </button>
                        <button type="button" class="global-dm-template-item w-full text-left px-2 py-1 text-xs rounded-xl text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition font-medium" data-text="Halo, saya berminat dengan penawaran ini. Apakah harga masih bisa dinegosiasikan?">
                            "Apakah harga masih bisa dinegosiasikan?"
                        </button>
                        <button type="button" class="global-dm-template-item w-full text-left px-2 py-1 text-xs rounded-xl text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition font-medium" data-text="Halo, bagaimana spesifikasi dan ketentuan penyerahannya?">
                            "Bagaimana ketentuan penyerahannya?"
                        </button>
                        <button type="button" class="global-dm-template-item w-full text-left px-2 py-1 text-xs rounded-xl text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition font-medium" data-text="Halo, saya ingin mengajukan lembar kesepakatan transaksi escrow untuk ini.">
                            "Saya ingin ajukan kesepakatan escrow."
                        </button>
                    </div>
                </div>

                <span class="text-[9px] text-slate-400">
                    <kbd class="px-1 py-0.2 rounded bg-slate-100 border border-slate-200 text-[9px] font-mono text-slate-500">Enter</kbd> kirim
                </span>
            </div>

            <!-- Error Alert Banner -->
            <div id="global-dm-error-alert" class="hidden mb-1.5 items-center justify-between px-2.5 py-1.5 bg-rose-50/95 border border-rose-200/90 rounded-xl text-xs text-rose-800 shadow-2xs">
                <div class="flex items-center gap-1.5 min-w-0">
                    <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span id="global-dm-error-message" class="font-medium text-[11px] leading-tight break-words"></span>
                </div>
                <button type="button" id="global-dm-dismiss-error-btn" class="p-0.5 text-rose-400 hover:text-rose-600 transition ml-1 rounded hover:bg-rose-100 shrink-0" title="Tutup peringatan">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Attachment Badge Pill -->
            <div id="global-dm-attachment-badge" class="hidden mb-1.5 items-center justify-between px-2.5 py-1 bg-indigo-50/90 border border-indigo-200/80 rounded-xl text-xs text-indigo-700 shadow-2xs">
                <div class="flex items-center gap-1.5 truncate">
                    <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    <span id="global-dm-attachment-name" class="font-medium truncate text-[11px]">dokumen.pdf</span>
                    <span id="global-dm-attachment-size" class="text-indigo-500 text-[9px] shrink-0"></span>
                </div>
                <button type="button" id="global-dm-remove-attachment-btn" class="p-0.5 text-slate-400 hover:text-rose-600 transition ml-1 rounded hover:bg-rose-50" title="Hapus lampiran">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Message Form -->
            <form id="global-dm-form" enctype="multipart/form-data">
                <input type="file"
                       id="global-dm-file-input"
                       class="hidden"
                       accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">

                <div class="flex items-end gap-1.5">
                    <button type="button"
                            id="global-dm-attach-btn"
                            class="p-2 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 text-slate-500 hover:text-indigo-600 transition shrink-0 cursor-pointer shadow-2xs"
                            title="Lampirkan Gambar atau PDF (Maks. 5 MB)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                    </button>

                    <div class="flex-1">
                        <textarea
                            id="global-dm-textarea"
                            rows="1"
                            placeholder="Tulis pesan..."
                            class="glass-input resize-none w-full text-xs py-2 px-3 rounded-xl focus:ring-2 focus:ring-indigo-500 leading-relaxed"></textarea>
                    </div>

                    <!-- Forward Send Button -->
                    <button type="submit"
                            id="global-dm-submit-btn"
                            class="dm-floating-launcher p-2 shrink-0 inline-flex items-center justify-center cursor-pointer text-white rounded-xl shadow-xs"
                            title="Kirim Pesan">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12L3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12zm0 0h7.5"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const drawer = document.getElementById('global-dm-drawer');
    const floatingLauncher = document.getElementById('global-dm-floating-launcher');
    const launcherBtn = document.getElementById('global-dm-launcher-btn');
    const launcherIconChat = document.getElementById('global-dm-launcher-icon-chat');
    const launcherIconClose = document.getElementById('global-dm-launcher-icon-close');
    const launcherBadge = document.getElementById('global-dm-launcher-badge');
    const closeBtns = document.querySelectorAll('.global-dm-close-btn');
    const minimizeBtns = document.querySelectorAll('.global-dm-minimize-btn');

    // Inbox View Elements
    const inboxView = document.getElementById('global-dm-inbox-view');
    const inboxList = document.getElementById('global-dm-inbox-list');
    const inboxLoading = document.getElementById('global-dm-inbox-loading');
    const inboxEmpty = document.getElementById('global-dm-inbox-empty');
    const totalUnreadBadge = document.getElementById('global-dm-total-unread-badge');

    // Thread View Elements
    const threadView = document.getElementById('global-dm-thread-view');
    const backToInboxBtn = document.getElementById('global-dm-back-to-inbox-btn');
    const threadAvatar = document.getElementById('global-dm-thread-avatar');
    const threadUsername = document.getElementById('global-dm-thread-username');
    const threadStatus = document.getElementById('global-dm-thread-status');
    const threadContext = document.getElementById('global-dm-thread-context');
    const contextBadge = document.getElementById('global-dm-context-badge');
    const contextTitle = document.getElementById('global-dm-context-title');
    const contextPrice = document.getElementById('global-dm-context-price');
    const messagesContainer = document.getElementById('global-dm-thread-messages');
    const messageList = document.getElementById('global-dm-thread-message-list');
    const threadLoading = document.getElementById('global-dm-thread-loading');
    const threadEmpty = document.getElementById('global-dm-thread-empty');
    const form = document.getElementById('global-dm-form');
    const textarea = document.getElementById('global-dm-textarea');
    const submitBtn = document.getElementById('global-dm-submit-btn');
    const fileInput = document.getElementById('global-dm-file-input');
    const attachBtn = document.getElementById('global-dm-attach-btn');
    const attachBadge = document.getElementById('global-dm-attachment-badge');
    const attachName = document.getElementById('global-dm-attachment-name');
    const attachSize = document.getElementById('global-dm-attachment-size');
    const removeAttachBtn = document.getElementById('global-dm-remove-attachment-btn');
    const drawerErrorAlert = document.getElementById('global-dm-error-alert');
    const drawerErrorMessage = document.getElementById('global-dm-error-message');
    const drawerDismissErrorBtn = document.getElementById('global-dm-dismiss-error-btn');
    const templateBtn = document.getElementById('global-dm-template-btn');
    const templateMenu = document.getElementById('global-dm-template-menu');

    const inboxApiUrl = @json(route('conversations.index'));
    const csrfToken = @json(csrf_token());

    let activeConversationId = null;
    let syncUrl = null;
    let sendUrl = null;
    let lastMessageId = 0;
    let syncTimer = null;
    let isSubmitting = false;

    // Helper functions for drawer in-page error alert
    function showDrawerError(msg) {
        if (drawerErrorMessage && drawerErrorAlert) {
            drawerErrorMessage.textContent = msg;
            drawerErrorAlert.classList.remove('hidden');
            drawerErrorAlert.classList.add('flex');
        }
    }

    function hideDrawerError() {
        if (drawerErrorAlert) {
            drawerErrorAlert.classList.add('hidden');
            drawerErrorAlert.classList.remove('flex');
        }
    }

    if (drawerDismissErrorBtn) {
        drawerDismissErrorBtn.addEventListener('click', hideDrawerError);
    }

    // Checkmark templates: Single tick (✓) for sent, Double tick (✓✓) for read
    const singleTickHtml = `
        <span class="text-slate-400 inline-flex items-center gap-0.5 text-[9px]">
            <svg class="w-3 h-3 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Terkirim</span>
        </span>
    `;

    const doubleTickHtml = `
        <span class="text-indigo-600 font-semibold inline-flex items-center gap-0.5 text-[9px]">
            <svg class="w-3 h-3 text-indigo-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 13l4.5 4.5L17 7"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 13l4.5 4.5L22 7"/>
            </svg>
            <span>Dibaca</span>
        </span>
    `;

    function scrollToBottom() {
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    function updateLauncherIcon(isOpen) {
        if (!launcherIconChat || !launcherIconClose) return;
        if (isOpen) {
            launcherIconChat.classList.add('hidden');
            launcherIconClose.classList.remove('hidden');
        } else {
            launcherIconChat.classList.remove('hidden');
            launcherIconClose.classList.add('hidden');
        }
    }

    // Open Floating Widget
    window.openGlobalDmDrawer = function(convId = null) {
        if (!drawer) return;
        drawer.classList.remove('is-closed');
        drawer.classList.add('is-open');
        updateLauncherIcon(true);

        if (convId) {
            openThread(convId);
        } else {
            showInboxView();
        }
    };

    // Close / Minimize Floating Widget
    window.closeGlobalDmDrawer = function() {
        if (!drawer) return;
        drawer.classList.remove('is-open');
        drawer.classList.add('is-closed');
        updateLauncherIcon(false);

        if (syncTimer) {
            clearInterval(syncTimer);
            syncTimer = null;
        }
    };

    window.minimizeGlobalDmDrawer = window.closeGlobalDmDrawer;

    // Toggle when clicking circular launcher button
    if (launcherBtn) {
        launcherBtn.addEventListener('click', function() {
            if (drawer && drawer.classList.contains('is-open')) {
                window.closeGlobalDmDrawer();
            } else {
                window.openGlobalDmDrawer(null);
            }
        });
    }

    closeBtns.forEach(btn => btn.addEventListener('click', window.closeGlobalDmDrawer));
    minimizeBtns.forEach(btn => btn.addEventListener('click', window.minimizeGlobalDmDrawer));

    if (backToInboxBtn) {
        backToInboxBtn.addEventListener('click', function() {
            showInboxView();
        });
    }

    // Show View 1: Inbox
    function showInboxView() {
        if (syncTimer) {
            clearInterval(syncTimer);
            syncTimer = null;
        }
        threadView.classList.add('hidden');
        inboxView.classList.remove('hidden');
        loadInboxList();
    }

    // Fetch and Render Inbox List as Bubble Pop Cards
    async function loadInboxList() {
        inboxLoading.classList.remove('hidden');
        inboxEmpty.classList.add('hidden');
        inboxList.innerHTML = '';

        try {
            const res = await fetch(inboxApiUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) return;

            const data = await res.json();
            inboxLoading.classList.add('hidden');

            // Update badge counters
            const unreadCount = data.total_unread || 0;
            if (unreadCount > 0) {
                totalUnreadBadge.textContent = unreadCount;
                totalUnreadBadge.classList.remove('hidden');
                if (launcherBadge) {
                    launcherBadge.textContent = unreadCount;
                    launcherBadge.classList.remove('hidden');
                }
                if (launcherBtn) launcherBtn.classList.add('has-unread');
            } else {
                totalUnreadBadge.classList.add('hidden');
                if (launcherBadge) launcherBadge.classList.add('hidden');
                if (launcherBtn) launcherBtn.classList.remove('has-unread');
            }

            if (!data.conversations || data.conversations.length === 0) {
                inboxEmpty.classList.remove('hidden');
                return;
            }

            data.conversations.forEach(conv => {
                const item = document.createElement('div');
                item.className = 'dm-bubble-pop-card p-2.5 flex items-center justify-between gap-2.5';
                item.setAttribute('data-conv-id', conv.conversation_id);

                const avatarLetter = (conv.other_user?.username || '?').substring(0, 1).toUpperCase();
                const unreadPill = conv.unread_count > 0 
                    ? `<span class="bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-[9px] font-bold px-2 py-0.2 rounded-full shrink-0 shadow-xs">${conv.unread_count}</span>` 
                    : '';

                const badgeBg = conv.context_type === 'MARKETPLACE' 
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' 
                    : (conv.context_type === 'FORUM' ? 'bg-purple-50 text-purple-700 border-purple-200/60' : 'bg-indigo-50 text-indigo-700 border-indigo-200/60');

                item.innerHTML = `
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                            ${avatarLetter}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="font-bold text-xs text-slate-900 truncate shrink-0">@${conv.other_user?.username || 'Pengguna'}</span>
                                <span class="text-[8px] uppercase font-bold px-1.5 py-0.2 rounded border ${badgeBg} shrink-0">
                                    ${conv.context_type}
                                </span>
                                ${conv.context_title && conv.context_title !== 'Percakapan Pribadi' ? `
                                    <span class="text-[9px] text-slate-500 font-medium truncate max-w-[105px] sm:max-w-[130px] inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200/80 border border-slate-200/60 px-1.5 py-0.2 rounded transition shrink-0" title="${conv.context_title}">
                                        <svg class="w-2.5 h-2.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        <span class="truncate">${conv.context_title}</span>
                                    </span>
                                ` : ''}
                            </div>
                            <p class="text-[11px] text-slate-500 truncate mt-0.5 font-normal">${conv.last_message ? conv.last_message.content : 'Belum ada pesan.'}</p>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-[9px] text-slate-400 block mb-0.5">${conv.updated_at || ''}</span>
                        ${unreadPill}
                    </div>
                `;

                item.addEventListener('click', function() {
                    openThread(conv.conversation_id);
                });

                inboxList.appendChild(item);
            });
        } catch (e) {
            inboxLoading.classList.add('hidden');
        }
    }

    // Open View 2: Thread View
    async function openThread(convId) {
        inboxView.classList.add('hidden');
        threadView.classList.remove('hidden');
        threadLoading.classList.remove('hidden');
        threadEmpty.classList.add('hidden');
        threadUsername.innerHTML = '@<span>Memuat...</span>';
        threadAvatar.textContent = '...';
        threadContext.classList.add('hidden');
        threadContext.classList.remove('flex');
        messageList.innerHTML = '';
        activeConversationId = convId;
        hideDrawerError();

        try {
            const res = await fetch(`/conversations/${convId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) {
                alert('Gagal memuat obrolan.');
                showInboxView();
                return;
            }

            const data = await res.json();
            threadLoading.classList.add('hidden');

            // Set Opponent Info
            const otherName = data.other_user?.username || 'Pengguna';
            threadUsername.innerHTML = `@<span>${otherName}</span>`;
            threadAvatar.textContent = otherName.substring(0, 1).toUpperCase();
            sendUrl = data.send_url;
            syncUrl = data.sync_url;

            // Context Ribbon
            if (data.context && data.context.title) {
                contextBadge.textContent = data.context.type;
                contextTitle.textContent = data.context.title;
                contextPrice.textContent = data.context.price || '';
                threadContext.classList.remove('hidden');
                threadContext.classList.add('flex');
            } else {
                threadContext.classList.add('hidden');
            }

            // Agreement Ribbon
            const agreementRibbon = document.getElementById('global-dm-agreement-ribbon');
            const agreementStatus = document.getElementById('global-dm-agreement-status');
            const agreementRev = document.getElementById('global-dm-agreement-rev');
            const agreementTitle = document.getElementById('global-dm-agreement-title');
            const agreementAction = document.getElementById('global-dm-agreement-action-link');

            if (agreementRibbon) {
                if (data.agreement) {
                    agreementStatus.textContent = data.agreement.status;
                    agreementRev.textContent = `Rev #${data.agreement.revision_no}`;
                    agreementTitle.textContent = data.agreement.item_title;
                    if (data.agreement.status === 'FINALIZED' && data.agreement.transaction_id) {
                        agreementAction.href = `/transactions/${data.agreement.transaction_id}`;
                        agreementAction.textContent = 'Buka Transaksi';
                    } else {
                        agreementAction.href = data.full_url;
                        agreementAction.textContent = 'Buka Kesepakatan';
                    }
                    agreementRibbon.classList.remove('hidden');
                    agreementRibbon.classList.add('flex');
                } else {
                    agreementRibbon.classList.add('hidden');
                    agreementRibbon.classList.remove('flex');
                }
            }

            // Render Messages
            lastMessageId = 0;
            if (!data.messages || data.messages.length === 0) {
                threadEmpty.classList.remove('hidden');
            } else {
                data.messages.forEach(msg => appendMessage(msg));
            }

            scrollToBottom();
            if (textarea) textarea.focus();

            // Start Sync Interval (3.5s)
            if (syncTimer) clearInterval(syncTimer);
            syncTimer = setInterval(pollThreadSync, 3500);

        } catch (e) {
            threadLoading.classList.add('hidden');
            alert('Gangguan jaringan saat memuat pesan.');
            showInboxView();
        }
    }

    function appendMessage(msg) {
        // Prevent duplicate appending
        if (document.querySelector(`[data-dm-msg-id="${msg.message_id}"]`)) {
            return;
        }

        lastMessageId = Math.max(lastMessageId, msg.message_id);
        threadEmpty.classList.add('hidden');

        const isMe = msg.is_me;
        const bubbleWrapper = document.createElement('div');
        bubbleWrapper.className = `flex ${isMe ? 'justify-end' : 'justify-start'} dm-message-bubble-pop`;
        bubbleWrapper.setAttribute('data-dm-msg-id', msg.message_id);

        let attachmentHtml = '';
        if (msg.has_attachment && msg.download_url) {
            const fileName = msg.attachment_name || 'lampiran';
            const lowerName = fileName.toLowerCase();
            const isImg = msg.is_image || lowerName.endsWith('.jpg') || lowerName.endsWith('.jpeg') || lowerName.endsWith('.png') || lowerName.endsWith('.webp') || lowerName.endsWith('.gif');
            const isVid = msg.is_video || lowerName.endsWith('.mp4') || lowerName.endsWith('.webm');
            const isPdf = msg.is_pdf || lowerName.endsWith('.pdf');
            const previewUrl = msg.preview_url || `${msg.download_url}?inline=1`;

            if (isImg) {
                attachmentHtml = `
                    <div class="mt-2 rounded-2xl overflow-hidden border border-slate-200/90 bg-slate-900/5 shadow-2xs">
                        <img src="${previewUrl}"
                             alt="${fileName}"
                             class="max-h-48 w-full object-cover rounded-t-2xl cursor-pointer hover:opacity-95 transition"
                             loading="lazy"
                             onclick="window.openDmImageLightbox('${previewUrl}', '${encodeURIComponent(fileName)}', '${msg.download_url}')">
                        <div class="p-1.5 bg-white/95 flex items-center justify-between gap-1.5 border-t border-slate-100 text-xs">
                            <span class="truncate text-[10px] font-medium text-slate-700">${fileName} (${msg.attachment_size})</span>
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" onclick="window.openDmImageLightbox('${previewUrl}', '${encodeURIComponent(fileName)}', '${msg.download_url}')" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">
                                    Zoom
                                </button>
                                <a href="${msg.download_url}" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition" download>
                                    Unduh
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            } else if (isVid) {
                attachmentHtml = `
                    <div class="mt-2 rounded-2xl overflow-hidden border border-slate-200/90 bg-black shadow-2xs">
                        <video controls preload="metadata" class="max-h-48 w-full rounded-t-2xl">
                            <source src="${previewUrl}" type="${msg.attachment_mime || 'video/mp4'}">
                            Browser tidak mendukung pemutar video.
                        </video>
                        <div class="p-1.5 bg-white/95 flex items-center justify-between gap-1.5 text-xs">
                            <span class="truncate text-[10px] font-medium text-slate-700">${fileName} (${msg.attachment_size})</span>
                            <a href="${msg.download_url}" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition shrink-0" download>
                                Unduh
                            </a>
                        </div>
                    </div>
                `;
            } else {
                let badgeColor = 'bg-slate-100 text-slate-700 border-slate-200';
                let typeLabel = 'FILE';
                if (isPdf) {
                    badgeColor = 'bg-rose-50 text-rose-700 border-rose-200';
                    typeLabel = 'PDF';
                } else if (lowerName.endsWith('.doc') || lowerName.endsWith('.docx')) {
                    badgeColor = 'bg-blue-50 text-blue-700 border-blue-200';
                    typeLabel = 'DOC';
                } else if (lowerName.endsWith('.xls') || lowerName.endsWith('.xlsx')) {
                    badgeColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    typeLabel = 'XLS';
                } else if (lowerName.endsWith('.zip')) {
                    badgeColor = 'bg-amber-50 text-amber-700 border-amber-200';
                    typeLabel = 'ZIP';
                }

                const previewBtn = isPdf ? `
                    <a href="${previewUrl}" target="_blank" rel="noopener noreferrer" class="px-2 py-0.5 rounded-lg text-[9px] font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition shrink-0">
                        Lihat
                    </a>
                ` : '';

                attachmentHtml = `
                    <div class="mt-2 p-2 rounded-xl ${isMe ? 'bg-indigo-700/60 text-white border border-indigo-400/30' : 'bg-slate-50 border border-slate-200 text-slate-800'} flex items-center justify-between gap-2 text-xs shadow-2xs">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="font-bold text-[8px] px-1 py-0.2 rounded border ${badgeColor} shrink-0">
                                ${typeLabel}
                            </span>
                            <div class="min-w-0">
                                <div class="truncate text-[10px] font-semibold">${fileName}</div>
                                <div class="text-[9px] opacity-75">${msg.attachment_size}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            ${previewBtn}
                            <a href="${msg.download_url}" class="px-2 py-0.5 rounded text-[9px] font-bold ${isMe ? 'bg-white/20 hover:bg-white/30 text-white' : 'bg-indigo-600 hover:bg-indigo-700 text-white'} transition shrink-0" download>
                                Unduh
                            </a>
                        </div>
                    </div>
                `;
            }
        }

        bubbleWrapper.innerHTML = `
            <div class="max-w-[85%]">
                ${!isMe ? `<div class="text-[9px] font-bold text-slate-400 mb-0.5 ml-1">@${msg.sender_name || 'Peserta'}</div>` : ''}
                <div class="px-3 py-2 rounded-2xl text-xs sm:text-sm leading-relaxed shadow-xs ${isMe ? 'bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white rounded-tr-xs' : 'bg-slate-100 text-slate-800 rounded-tl-xs'}">
                    ${msg.content ? `<div class="whitespace-pre-wrap break-words">${msg.content}</div>` : ''}
                    ${attachmentHtml}
                </div>
                <div class="flex items-center gap-1 mt-0.5 text-[9px] text-slate-400 ${isMe ? 'justify-end mr-1' : 'justify-start ml-1'}">
                    <span>${msg.sent_at || ''}</span>
                    ${isMe ? `
                        <span>&bull;</span>
                        <span id="global-dm-status-${msg.message_id}">
                            ${msg.is_read ? doubleTickHtml : singleTickHtml}
                        </span>
                    ` : ''}
                </div>
            </div>
        `;

        messageList.appendChild(bubbleWrapper);
    }

    // Polling Sync Thread
    async function pollThreadSync() {
        if (!activeConversationId || !syncUrl) return;

        try {
            const res = await fetch(`${syncUrl}?after_id=${lastMessageId}&last_id=${lastMessageId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) return;

            const data = await res.json();

            // Append new incoming messages
            if (data.new_messages && data.new_messages.length > 0) {
                data.new_messages.forEach(msg => appendMessage(msg));
                scrollToBottom();
            }

            // Update read status ticks
            const readIds = data.my_read_message_ids || data.read_ids || [];
            if (readIds.length > 0) {
                readIds.forEach(id => {
                    const statusEl = document.getElementById(`global-dm-status-${id}`);
                    if (statusEl) statusEl.innerHTML = doubleTickHtml;
                });
            }
        } catch (e) {
            // Silently continue polling
        }
    }

    // Message Form Submission
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (isSubmitting || !sendUrl) return;

            const content = textarea.value.trim();
            const hasFile = fileInput.files && fileInput.files.length > 0;

            if (!content && !hasFile) return;

            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-60', 'cursor-not-allowed');

            const formData = new FormData();
            formData.append('_token', csrfToken);
            if (content) formData.append('content', content);
            if (hasFile) formData.append('attachment', fileInput.files[0]);

            try {
                const res = await fetch(sendUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                if (!res.ok) {
                    const err = await res.json();
                    showDrawerError(err.message || 'Gagal mengirim pesan.');
                    return;
                }

                const data = await res.json();
                textarea.value = '';
                clearAttachment();
                hideDrawerError();

                if (data.message) {
                    appendMessage(data.message);
                    scrollToBottom();
                }

            } catch (e) {
                showDrawerError('Terjadi kesalahan koneksi saat mengirim pesan.');
            } finally {
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                if (textarea) textarea.focus();
            }
        });
    }

    if (textarea) {
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });
    }

    // Quick Templates
    if (templateBtn && templateMenu) {
        templateBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            templateMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function(e) {
            if (!templateMenu.contains(e.target) && e.target !== templateBtn) {
                templateMenu.classList.add('hidden');
            }
        });

        document.querySelectorAll('.global-dm-template-item').forEach(item => {
            item.addEventListener('click', function() {
                const text = this.getAttribute('data-text');
                if (textarea) {
                    textarea.value = text;
                    textarea.focus();
                }
                templateMenu.classList.add('hidden');
            });
        });
    }

    // File Attachment Picker
    if (attachBtn && fileInput) {
        attachBtn.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', function() {
            hideDrawerError();
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const extension = file.name.split('.').pop().toLowerCase();
                const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

                if (!allowedExtensions.includes(extension)) {
                    showDrawerError('Format file harus berupa gambar (JPG, JPEG, PNG, WEBP) atau PDF.');
                    clearAttachment();
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    showDrawerError('Ukuran file maksimal adalah 5 MB.');
                    clearAttachment();
                    return;
                }
                attachName.textContent = file.name;
                attachSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
                attachBadge.classList.remove('hidden');
                attachBadge.classList.add('flex');
            } else {
                clearAttachment();
            }
        });

        if (removeAttachBtn) {
            removeAttachBtn.addEventListener('click', function() {
                clearAttachment();
                hideDrawerError();
            });
        }
    }

    function clearAttachment() {
        if (fileInput) fileInput.value = '';
        if (attachBadge) {
            attachBadge.classList.add('hidden');
            attachBadge.classList.remove('flex');
        }
    }

    // Image Lightbox Helpers
    const lightboxModal = document.getElementById('global-dm-lightbox-modal');
    const lightboxClose = document.getElementById('global-dm-lightbox-close');
    const lightboxImg = document.getElementById('global-dm-lightbox-img');
    const lightboxName = document.getElementById('global-dm-lightbox-name');
    const lightboxDownload = document.getElementById('global-dm-lightbox-download');

    window.openDmImageLightbox = function(url, filename, downloadUrl) {
        if (!lightboxModal || !lightboxImg) return;
        lightboxImg.src = url;
        if (lightboxName) lightboxName.textContent = decodeURIComponent(filename);
        if (lightboxDownload) lightboxDownload.href = downloadUrl;

        lightboxModal.classList.remove('hidden');
        lightboxModal.classList.add('flex');
        setTimeout(() => lightboxModal.classList.remove('opacity-0'), 10);
    };

    window.closeDmImageLightbox = function() {
        if (!lightboxModal) return;
        lightboxModal.classList.add('opacity-0');
        setTimeout(() => {
            lightboxModal.classList.add('hidden');
            lightboxModal.classList.remove('flex');
            if (lightboxImg) lightboxImg.src = '';
        }, 300);
    };

    if (lightboxClose) lightboxClose.addEventListener('click', window.closeDmImageLightbox);
    if (lightboxModal) {
        lightboxModal.addEventListener('click', function(e) {
            if (e.target === lightboxModal) window.closeDmImageLightbox();
        });
    }

    // Global Click Listener for elements with [data-open-dm-drawer]
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('[data-open-dm-drawer]');
        if (trigger) {
            e.preventDefault();
            const convId = trigger.getAttribute('data-conversation-id');
            window.openGlobalDmDrawer(convId || null);
        }
    });

    // Check URL parameters for ?open_dm={id} on page load
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('open_dm')) {
        const targetConvId = urlParams.get('open_dm');
        setTimeout(() => {
            window.openGlobalDmDrawer(targetConvId || null);
        }, 200);
    } else {
        // Automatically fetch inbox unread count for launcher badge
        loadInboxList();
    }
});
</script>
@endauth
