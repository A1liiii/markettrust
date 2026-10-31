@extends('layouts.app')

@section('title', 'Pengaturan Rekening Payout - MarketTrust')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb & Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                    <span>Panel Seller</span>
                    <span>&bull;</span>
                    <span>Keuangan & Payout</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Pengaturan Rekening Payout
                </h1>
                <p class="text-sm text-slate-600 mt-1">
                    Kelola rekening bank, akun e-wallet, dan QRIS untuk pencairan dana hasil penjualan produk escrow digital.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('profile.show') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-3.5">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Profil
                </a>
                <a href="{{ route('seller.payout-accounts.create') }}" class="glass-btn-primary text-xs sm:text-sm py-2 px-4 shadow-md shadow-indigo-500/20" data-cy="btn-create-payout-account">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Rekening Baru
                </a>
            </div>
        </div>

        <!-- Flash Success Notification -->
        @if (session('success'))
            <div class="glass-alert glass-alert-success mb-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs sm:text-sm font-medium text-emerald-900">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- Banner Status Payout Aktif -->
        @if ($activeAccount)
            <div class="glass-panel p-6 mb-8 border-indigo-200/80 bg-gradient-to-r from-indigo-500/10 via-white/70 to-purple-500/10 relative overflow-hidden">
                <div class="absolute right-0 top-0 translate-x-10 -translate-y-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-md shadow-indigo-500/20">
                            @if ($activeAccount->destination_type->value === 'BANK')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            @elseif ($activeAccount->destination_type->value === 'EWALLET')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            @else
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                            @endif
                        </div>

                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="glass-badge glass-badge-success text-[10px] py-0.5 px-2 font-bold tracking-wide">
                                    TUJUAN UTAMA AKTIF
                                </span>
                                <span class="glass-badge glass-badge-primary text-[10px] py-0.5 px-2">
                                    {{ $activeAccount->destination_type->label() }}
                                </span>
                            </div>

                            <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">
                                {{ $activeAccount->destination_provider ?? $activeAccount->destination_type->value }}
                                <span class="text-slate-400 font-normal">&bull;</span>
                                <span class="font-mono text-slate-800">{{ $activeAccount->masked_destination_number }}</span>
                            </h3>

                            <p class="text-xs text-slate-600 mt-0.5">
                                Atas Nama: <strong class="text-slate-800">{{ $activeAccount->destination_holder }}</strong>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:self-center">
                        <span class="text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200/80 rounded-xl px-3 py-1.5 font-medium flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Siap Menerima Settlement
                        </span>
                    </div>
                </div>
            </div>
        @else
            <!-- Peringatan Belum Ada Rekening Aktif -->
            <div class="glass-alert glass-alert-warning mb-8 p-5 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-amber-900">Belum Ada Rekening Payout Aktif</h4>
                        <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                            Dana penjualan transaksi escrow Anda akan tertahan sementara hingga Anda mendaftarkan dan mengaktifkan setidaknya satu tujuan pencairan dana yang valid.
                        </p>
                    </div>
                </div>
                <a href="{{ route('seller.payout-accounts.create') }}" class="glass-btn-primary text-xs py-2 px-3.5 whitespace-nowrap shrink-0">
                    Atur Sekarang
                </a>
            </div>
        @endif

        <!-- Daftar Semua Rekening Payout -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Daftar Rekening & Tujuan Tersimpan
                    <span class="text-xs text-slate-500 font-normal">({{ $accounts->count() }} tersimpan)</span>
                </h3>
            </div>

            @if ($accounts->isEmpty())
                <div class="glass-panel p-10 text-center">
                    <div class="w-16 h-16 rounded-3xl bg-indigo-50 text-indigo-500 mx-auto flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-slate-900 mb-1">Belum Ada Rekening Payout</h4>
                    <p class="text-xs text-slate-600 max-w-md mx-auto mb-6">
                        Anda belum menambahkan tujuan pencairan dana. Tambahkan rekening bank, dompet digital, atau QRIS untuk kemudahan penerimaan dana.
                    </p>
                    <a href="{{ route('seller.payout-accounts.create') }}" class="glass-btn-primary text-xs sm:text-sm py-2 px-5 inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Tujuan Payout Pertama
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($accounts as $acc)
                        <div class="glass-card p-5 flex flex-col justify-between relative overflow-hidden transition-all duration-200 hover:shadow-lg {{ $acc->is_active ? 'ring-2 ring-indigo-500/40 bg-white/90' : 'bg-white/60' }}" data-cy="payout-account-card">
                            <!-- Card Header -->
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <span class="glass-badge {{ $acc->destination_type->value === 'BANK' ? 'glass-badge-primary' : ($acc->destination_type->value === 'EWALLET' ? 'glass-badge-success' : 'glass-badge-neutral') }} text-[10px] py-0.5 px-2">
                                        {{ $acc->destination_type->label() }}
                                    </span>

                                    @if ($acc->is_active)
                                        <span class="glass-badge glass-badge-success text-[10px] py-0.5 px-2 font-bold flex items-center gap-1" data-cy="badge-account-active">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400 font-medium">
                                            Nonaktif
                                        </span>
                                    @endif
                                </div>

                                <!-- Detail Info -->
                                <div class="space-y-1 mb-4">
                                    <div class="text-base font-bold text-slate-900 flex items-center gap-1.5">
                                        {{ $acc->destination_provider ?? $acc->destination_type->value }}
                                    </div>
                                    <div class="text-xs font-mono font-medium text-slate-700 tracking-wider">
                                        {{ $acc->masked_destination_number }}
                                    </div>
                                    <div class="text-xs text-slate-500 pt-1">
                                        a.n. <strong class="text-slate-800">{{ $acc->destination_holder }}</strong>
                                    </div>

                                    @if ($acc->destination_qris_image_url)
                                        <div class="pt-2">
                                            <a href="{{ $acc->qris_image_full_url }}" target="_blank" class="inline-flex items-center gap-1.5 text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Lihat Gambar Barcode QRIS
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Card Footer Actions -->
                            <div class="pt-4 border-t border-slate-200/60 flex items-center justify-between gap-2">
                                <div>
                                    @if (! $acc->is_active)
                                        <form action="{{ route('seller.payout-accounts.activate', $acc) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition hover:underline" data-cy="btn-activate-account">
                                                Jadikan Utama
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-slate-400 font-medium">Tujuan Utama</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('seller.payout-accounts.edit', $acc) }}" class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-slate-100 transition" title="Ubah rekening" data-cy="btn-edit-account">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </a>

                                    <button type="button"
                                            onclick="openDeletePayoutModal('{{ route('seller.payout-accounts.destroy', $acc) }}', '{{ addslashes($acc->destination_provider ?? $acc->destination_type->value) }}', '{{ addslashes($acc->masked_destination_number) }}', '{{ addslashes($acc->destination_holder) }}')"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                            title="Hapus rekening"
                                            data-cy="btn-delete-account">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Ketentuan & Keamanan Payout Info Panel -->
        <div class="glass-panel p-6 bg-slate-50/60 border border-slate-200/60 rounded-2xl">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Ketentuan & Kebijakan Settlement Payout MarketTrust
            </h4>
            <ul class="text-xs text-slate-600 space-y-1.5 list-disc list-inside leading-relaxed">
                <li>Satu akun seller hanya memiliki <strong>1 (satu) tujuan rekening aktif</strong> pada satu waktu untuk menghindari ambiguitas transfer.</li>
                <li>Pencairan dana (*settlement payout*) dijalankan setelah buyer melakukan konfirmasi penerimaan pesanan atau batas waktu auto-confirm berakhir tanpa sengketa aktif.</li>
                <li>Pastikan nama pemilik rekening sesuai identitas valid untuk memperlancar verifikasi transfer manual oleh administrator.</li>
                <li>Tujuan payout dengan data tidak lengkap akan secara otomatis <strong>menahan proses pencairan dana</strong> hingga diperbarui.</li>
            </ul>
        </div>
    </div>
</div>

<!-- In-Page Confirmation Modal: Hapus Rekening Payout -->
<div id="modal-delete-payout" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs transition-opacity" role="dialog" aria-modal="true" data-cy="modal-delete-payout" onclick="if(event.target === this) closeDeletePayoutModal()">
    <div class="glass-card w-full max-w-md p-6 bg-white/95 border border-white/80 shadow-2xl rounded-2xl space-y-5 animate-in fade-in duration-200">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Konfirmasi Hapus Rekening</h3>
                    <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan</p>
                </div>
            </div>
            <button type="button" onclick="closeDeletePayoutModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer" title="Tutup">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Isi Pesan Peringatan Web -->
        <div class="p-3.5 rounded-xl bg-rose-50/80 border border-rose-200/80 text-xs text-rose-900 space-y-1.5">
            <p class="font-medium">
                Apakah Anda yakin ingin menghapus rekening berikut dari daftar tujuan pencairan dana Anda?
            </p>
            <div class="p-2.5 rounded-lg bg-white/90 border border-rose-100 text-slate-800 space-y-0.5">
                <div class="font-bold text-slate-900 text-sm" id="delete-modal-provider">-</div>
                <div class="font-mono text-xs text-slate-600 tracking-wider" id="delete-modal-number">-</div>
                <div class="text-[11px] text-slate-500" id="delete-modal-holder">-</div>
            </div>
        </div>

        <!-- Form Aksi Submit -->
        <form id="form-delete-payout" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" onclick="closeDeletePayoutModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition cursor-pointer" data-cy="btn-cancel-delete">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-xs flex items-center gap-1.5 cursor-pointer" data-cy="btn-confirm-delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Hapus Rekening</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDeletePayoutModal(actionUrl, provider, number, holder) {
        const modal = document.getElementById('modal-delete-payout');
        const form = document.getElementById('form-delete-payout');
        document.getElementById('delete-modal-provider').textContent = provider;
        document.getElementById('delete-modal-number').textContent = number;
        document.getElementById('delete-modal-holder').textContent = 'a.n. ' + holder;
        form.action = actionUrl;
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    function closeDeletePayoutModal() {
        const modal = document.getElementById('modal-delete-payout');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeletePayoutModal();
        }
    });
</script>
@endsection
