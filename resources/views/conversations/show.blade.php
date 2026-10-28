@extends('layouts.app')

@section('title', 'Percakapan dengan @' . ($otherUser ? $otherUser->username : 'Pengguna'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Top Bar: Back & Opponent Info -->
    <div class="glass-panel p-4 mb-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('conversations.index') }}"
               class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-white/60 transition border border-transparent hover:border-white/60"
               title="Kembali ke Kotak Masuk">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>

            <!-- Other User Avatar & Info -->
            <div class="flex items-center gap-3">
                @if($otherUser && $otherUser->avatar_url)
                    <img src="{{ $otherUser->avatar_url }}" alt="{{ $otherUser->username }}" class="w-10 h-10 rounded-full object-cover border border-white/80 shadow-xs">
                @else
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                        {{ $otherUser ? strtoupper(substr($otherUser->username, 0, 1)) : '?' }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm sm:text-base font-bold text-slate-900 leading-tight">
                            @<span>{{ $otherUser ? $otherUser->username : 'Pengguna' }}</span>
                        </h2>
                        @if($otherUser)
                            <span class="text-[10px] uppercase font-bold px-1.5 py-0.2 rounded-full {{ $otherUser->account_status === 'ACTIVE' ? 'bg-emerald-500/15 text-emerald-700 border border-emerald-500/20' : 'bg-rose-500/15 text-rose-700 border border-rose-500/20' }}">
                                {{ $otherUser->account_status }}
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-500">Percakapan Pribadi Terverifikasi</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                    data-open-dm-drawer
                    data-conversation-id="{{ $conversation->conversation_id }}"
                    class="dm-floating-launcher text-white text-xs font-bold py-1.5 px-3 rounded-xl flex items-center gap-1.5 shadow-xs cursor-pointer hover:scale-105 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span>Buka di Side Panel</span>
            </button>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Terkoneksi
            </span>
        </div>
    </div>

    <!-- Context Attachment Banner (Listing / Post / Agreement) -->
    @if($conversation->listing)
        <div class="glass-card p-3 sm:p-4 mb-4 border-l-4 border-l-emerald-500 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wider font-bold text-emerald-700">Konteks: Produk Marketplace</div>
                    <h3 class="text-sm font-semibold text-slate-900">{{ $conversation->listing->title }}</h3>
                    <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                        <span class="font-bold text-slate-800">Rp {{ number_format($conversation->listing->price, 0, ',', '.') }}</span>
                        <span>&bull;</span>
                        <span>Metode: {{ $conversation->listing->delivery_method }}</span>
                    </div>
                </div>
            </div>
            <div class="text-xs text-slate-500 shrink-0">
                Estimasi: {{ $conversation->listing->estimated_delivery_value }} {{ strtolower($conversation->listing->estimated_delivery_unit) }}
            </div>
        </div>
    @elseif($conversation->post)
        <div class="glass-card p-3 sm:p-4 mb-4 border-l-4 border-l-purple-500 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/15 text-purple-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wider font-bold text-purple-700">Konteks: Thread Forum ({{ $conversation->post->category_code }})</div>
                    <h3 class="text-sm font-semibold text-slate-900">{{ $conversation->post->title }}</h3>
                    @if($conversation->post->price_offer)
                        <div class="text-xs text-slate-600 mt-0.5">
                            Penawaran: <span class="font-bold text-slate-800">Rp {{ number_format($conversation->post->price_offer, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @elseif($conversation->agreement)
        <div class="glass-card p-3 sm:p-4 mb-4 border-l-4 border-l-indigo-500 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/15 text-indigo-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wider font-bold text-indigo-700">Konteks: Sheet Kesepakatan Transaksi</div>
                    <h3 class="text-sm font-semibold text-slate-900">Agreement #{{ $conversation->agreement->agreement_id }} &bull; {{ $conversation->agreement->item_title }}</h3>
                </div>
            </div>
            <span class="glass-badge glass-badge-primary text-[11px]">{{ $conversation->agreement->status }}</span>
        </div>
    @endif

    <!-- Chat Stream Box -->
    <div class="glass-panel p-4 sm:p-6 mb-4 flex flex-col min-h-[420px] max-h-[600px] overflow-y-auto" id="messages-container">
        @if($conversation->messages->isEmpty())
            <div class="m-auto text-center py-12 text-slate-400" id="empty-state">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium">Belum ada pesan di percakapan ini.</p>
                <p class="text-xs text-slate-400 mt-1">Kirim pesan pertama atau gunakan template pesan singkat untuk memulai.</p>
            </div>
            <div class="space-y-4 hidden" id="message-list"></div>
        @else
            <div class="space-y-4" id="message-list">
                @foreach($conversation->messages as $msg)
                    @php
                        $isMe = $msg->sender_id === auth()->user()->user_id;
                    @endphp

                    <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $msg->message_id }}">
                        <div class="max-w-[85%] sm:max-w-[70%]">
                            <!-- Sender name label for other user -->
                            @if(! $isMe)
                                <div class="text-[11px] font-semibold text-slate-500 mb-1 ml-1">
                                    @<span>{{ $msg->sender ? $msg->sender->username : 'Peserta' }}</span>
                                </div>
                            @endif

                            <!-- Message Bubble -->
                            <div class="p-3.5 sm:p-4 rounded-2xl text-sm leading-relaxed shadow-xs {{ $isMe ? 'bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white rounded-tr-xs' : 'bg-white/85 backdrop-blur-md border border-slate-200/80 text-slate-800 rounded-tl-xs' }}">
                                @if($msg->content)
                                    <div class="whitespace-pre-wrap break-words">{{ $msg->content }}</div>
                                @endif

                                <!-- Attachment Card (If exists) -->
                                @if($msg->hasAttachment())
                                    <div class="mt-2.5 p-2.5 rounded-xl {{ $isMe ? 'bg-indigo-700/50 text-white' : 'bg-slate-50 border border-slate-200 text-slate-800' }} flex items-center justify-between gap-3 text-xs">
                                        <div class="flex items-center gap-2 overflow-hidden min-w-0">
                                            <div class="w-8 h-8 rounded-lg {{ $isMe ? 'bg-white/20 text-white' : 'bg-indigo-50 text-indigo-600' }} flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                </svg>
                                            </div>
                                            <div class="truncate">
                                                <div class="font-medium truncate text-xs">{{ $msg->attachment_name }}</div>
                                                <div class="text-[10px] opacity-75">{{ $msg->formattedAttachmentSize() }}</div>
                                            </div>
                                        </div>
                                        <a href="{{ route('conversations.messages.attachment', [$conversation->conversation_id, $msg->message_id]) }}"
                                           class="px-2.5 py-1 rounded-lg text-[11px] font-semibold shrink-0 {{ $isMe ? 'bg-white/20 hover:bg-white/30 text-white' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }} transition flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span>Unduh</span>
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <!-- Timestamp & Read Status (TC-DM-04) -->
                            <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-400 {{ $isMe ? 'justify-end mr-1' : 'justify-start ml-1' }}">
                                <span>{{ $msg->sent_at ? $msg->sent_at->format('H:i') : '' }}</span>
                                @if($isMe)
                                    <span>&bull;</span>
                                    <span id="msg-status-{{ $msg->message_id }}">
                                        @if($msg->read_at)
                                            <span class="text-indigo-600 font-semibold inline-flex items-center gap-1">
                                                <svg class="w-4 h-4 text-indigo-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 13l4.5 4.5L17 7"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 13l4.5 4.5L22 7"/>
                                                </svg>
                                                <span>Dibaca</span>
                                            </span>
                                        @else
                                            <span class="text-slate-400 inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>Terkirim</span>
                                            </span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Message Input Box with Template Dropdown & Attachment Picker -->
    <div class="glass-panel p-3 sm:p-4">
        <!-- Quick Message Templates (TC-DM-03) -->
        <div class="mb-2.5 flex items-center justify-between text-xs">
            <div class="relative inline-block text-left" id="template-dropdown-wrapper">
                <button type="button"
                        id="template-dropdown-btn"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-slate-600 hover:text-indigo-600 bg-slate-100 hover:bg-indigo-50 border border-slate-200/80 transition text-xs font-medium cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span>Pilihan Pesan Singkat</span>
                    <svg class="w-3 h-3 ml-0.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="template-menu" class="hidden absolute left-0 bottom-full mb-1.5 w-72 sm:w-80 rounded-xl bg-white/95 backdrop-blur-md shadow-xl border border-slate-200/90 p-1.5 z-30 space-y-1">
                    <div class="px-2 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih template kalimat</div>
                    <button type="button" class="quick-template-item w-full text-left px-3 py-2 text-xs rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition" data-text="Halo, apakah produk/postingan ini masih tersedia?">
                        "Halo, apakah masih tersedia?"
                    </button>
                    <button type="button" class="quick-template-item w-full text-left px-3 py-2 text-xs rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition" data-text="Halo, saya berminat dengan penawaran ini. Apakah harga masih bisa dinegosiasikan?">
                        "Apakah harga masih bisa dinegosiasikan?"
                    </button>
                    <button type="button" class="quick-template-item w-full text-left px-3 py-2 text-xs rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition" data-text="Halo, bagaimana detail spesifikasi dan tata cara penyerahannya?">
                        "Bagaimana detail spesifikasi & penyerahannya?"
                    </button>
                    <button type="button" class="quick-template-item w-full text-left px-3 py-2 text-xs rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition" data-text="Halo, saya ingin mengajukan lembar kesepakatan transaksi escrow untuk ini.">
                        "Saya ingin ajukan kesepakatan escrow."
                    </button>
                </div>
            </div>

            <span class="text-[11px] text-slate-400 hidden sm:inline">
                Tekan <kbd class="px-1 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[10px]">Enter</kbd> untuk kirim, <kbd class="px-1 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[10px]">Shift+Enter</kbd> baris baru
            </span>
        </div>

        <!-- Attachment Selected Preview Badge -->
        <div id="attachment-preview-badge" class="hidden mb-2 items-center justify-between px-3 py-1.5 bg-indigo-50 border border-indigo-200/80 rounded-xl text-xs text-indigo-700">
            <div class="flex items-center gap-2 truncate">
                <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                <span id="attachment-preview-name" class="font-medium truncate">nama_file.pdf</span>
                <span id="attachment-preview-size" class="text-slate-500 text-[11px] shrink-0"></span>
            </div>
            <button type="button" id="remove-attachment-btn" class="p-1 text-slate-400 hover:text-rose-600 transition ml-2" title="Hapus lampiran">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Message Form -->
        <form action="{{ route('conversations.messages.send', $conversation->conversation_id) }}"
              method="POST"
              enctype="multipart/form-data"
              id="message-form">
            @csrf

            <!-- Hidden File Input -->
            <input type="file"
                   name="attachment"
                   id="attachment-input"
                   class="hidden"
                   accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">

            <div class="flex items-end gap-2">
                <!-- Attach Document Button -->
                <button type="button"
                        id="attach-file-btn"
                        class="p-2.5 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 text-slate-500 hover:text-indigo-600 transition shrink-0 cursor-pointer"
                        title="Lampirkan Gambar atau PDF (Maks. 5 MB)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                </button>

                <!-- Textarea -->
                <div class="flex-1">
                    <textarea
                        name="content"
                        id="message-content"
                        rows="2"
                        placeholder="Tulis pesan Anda..."
                        class="glass-input resize-none w-full text-sm py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500"
                        autofocus></textarea>
                </div>

                <!-- Submit Button with TC-DM-06 Fixed Forward-Facing Centered Icon -->
                <button type="submit"
                        id="submit-message-btn"
                        class="glass-btn-primary py-2.5 px-4 shrink-0 inline-flex items-center justify-center gap-2 cursor-pointer transition">
                    <span id="btn-text">Kirim</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12L3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12zm0 0h7.5"/>
                    </svg>
                </button>
            </div>
            @error('content')
                <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
            @enderror
            @error('attachment')
                <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
            @enderror
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('messages-container');
    const form = document.getElementById('message-form');
    const textarea = document.getElementById('message-content');
    const submitBtn = document.getElementById('submit-message-btn');
    const fileInput = document.getElementById('attachment-input');
    const attachBtn = document.getElementById('attach-file-btn');
    const previewBadge = document.getElementById('attachment-preview-badge');
    const previewName = document.getElementById('attachment-preview-name');
    const previewSize = document.getElementById('attachment-preview-size');
    const removeAttachmentBtn = document.getElementById('remove-attachment-btn');
    const templateDropdownBtn = document.getElementById('template-dropdown-btn');
    const templateMenu = document.getElementById('template-menu');
    const syncUrl = @json(route('conversations.sync', $conversation->conversation_id));

    let isSubmitting = false;

    // Scroll to bottom helper
    function scrollToBottom() {
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    scrollToBottom();

    // 1. TC-DM-03: Quick Template Selection (Fill textarea without auto-submit)
    if (templateDropdownBtn && templateMenu) {
        templateDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            templateMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function(e) {
            if (!templateMenu.contains(e.target) && e.target !== templateDropdownBtn) {
                templateMenu.classList.add('hidden');
            }
        });

        document.querySelectorAll('.quick-template-item').forEach(btn => {
            btn.addEventListener('click', function() {
                const text = this.getAttribute('data-text');
                if (text && textarea) {
                    textarea.value = text;
                    textarea.focus();
                    templateMenu.classList.add('hidden');
                }
            });
        });
    }

    // 2. Attachment File Handling
    if (attachBtn && fileInput) {
        attachBtn.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file lampiran maksimal 5 MB.');
                    clearAttachment();
                    return;
                }
                previewName.textContent = file.name;
                const sizeKB = (file.size / 1024).toFixed(0);
                previewSize.textContent = sizeKB > 1024 ? `(${(sizeKB / 1024).toFixed(1)} MB)` : `(${sizeKB} KB)`;
                previewBadge.classList.remove('hidden');
                previewBadge.classList.add('flex');
            } else {
                clearAttachment();
            }
        });

        if (removeAttachmentBtn) {
            removeAttachmentBtn.addEventListener('click', function() {
                clearAttachment();
            });
        }
    }

    function clearAttachment() {
        if (fileInput) fileInput.value = '';
        if (previewBadge) {
            previewBadge.classList.add('hidden');
            previewBadge.classList.remove('flex');
        }
    }

    // 3. TC-DM-01: Enter to submit, Shift+Enter for newline, prevent empty & double submission
    function triggerFormSubmit() {
        if (isSubmitting) return;

        const contentVal = textarea.value.trim();
        const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

        // Prevent empty message without attachment
        if (contentVal.length === 0 && !hasFile) {
            textarea.focus();
            textarea.classList.add('ring-2', 'ring-rose-500');
            setTimeout(() => textarea.classList.remove('ring-2', 'ring-rose-500'), 1500);
            return;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        form.submit();
    }

    if (textarea) {
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                triggerFormSubmit();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }

            const contentVal = textarea.value.trim();
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

            if (contentVal.length === 0 && !hasFile) {
                e.preventDefault();
                textarea.focus();
                return;
            }

            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        });
    }

    // 4. TC-DM-04: Realtime Read Receipt Sync & Live Incoming Messages Polling
    let lastKnownMessageId = 0;
    const allMsgNodes = document.querySelectorAll('[data-message-id]');
    if (allMsgNodes.length > 0) {
        lastKnownMessageId = parseInt(allMsgNodes[allMsgNodes.length - 1].getAttribute('data-message-id'), 10) || 0;
    }

    async function pollSync() {
        if (document.hidden) return; // Save resource if tab is in background

        try {
            const res = await fetch(`${syncUrl}?after_id=${lastKnownMessageId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) return;

            const data = await res.json();

            // Update Read Status on My Messages
            if (data.my_read_message_ids && Array.isArray(data.my_read_message_ids)) {
                data.my_read_message_ids.forEach(id => {
                    const statusElem = document.getElementById(`msg-status-${id}`);
                    if (statusElem) {
                        statusElem.innerHTML = `
                            <span class="text-indigo-600 font-semibold inline-flex items-center gap-1">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 13l4.5 4.5L17 7"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 13l4.5 4.5L22 7"/>
                                </svg>
                                <span>Dibaca</span>
                            </span>
                        `;
                    }
                });
            }

            // Append New Messages if received
            if (data.new_messages && data.new_messages.length > 0) {
                const emptyState = document.getElementById('empty-state');
                if (emptyState) emptyState.remove();

                const msgList = document.getElementById('message-list');
                if (msgList) {
                    msgList.classList.remove('hidden');

                    data.new_messages.forEach(msg => {
                        lastKnownMessageId = Math.max(lastKnownMessageId, msg.message_id);

                        const msgDiv = document.createElement('div');
                        msgDiv.className = 'flex justify-start';
                        msgDiv.setAttribute('data-message-id', msg.message_id);

                        let attachmentHtml = '';
                        if (msg.has_attachment && msg.download_url) {
                            attachmentHtml = `
                                <div class="mt-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 flex items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-2 overflow-hidden min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                            </svg>
                                        </div>
                                        <div class="truncate">
                                            <div class="font-medium truncate text-xs">${msg.attachment_name}</div>
                                            <div class="text-[10px] opacity-75">${msg.attachment_size}</div>
                                        </div>
                                    </div>
                                    <a href="${msg.download_url}" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white transition flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <span>Unduh</span>
                                    </a>
                                </div>
                            `;
                        }

                        msgDiv.innerHTML = `
                            <div class="max-w-[85%] sm:max-w-[70%]">
                                <div class="text-[11px] font-semibold text-slate-500 mb-1 ml-1">@${msg.sender_name || 'Peserta'}</div>
                                <div class="p-3.5 sm:p-4 rounded-2xl text-sm leading-relaxed shadow-xs bg-white/85 backdrop-blur-md border border-slate-200/80 text-slate-800 rounded-tl-xs">
                                    ${msg.content ? `<div class="whitespace-pre-wrap break-words">${msg.content}</div>` : ''}
                                    ${attachmentHtml}
                                </div>
                                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-400 justify-start ml-1">
                                    <span>${msg.sent_at || ''}</span>
                                </div>
                            </div>
                        `;

                        msgList.appendChild(msgDiv);
                    });

                    scrollToBottom();
                }
            }
        } catch (err) {
            // Silently catch sync network error
        }
    }

    // Run poll every 3.5 seconds
    setInterval(pollSync, 3500);
});
</script>
@endsection
