@extends('layouts.app')

@section('title', 'Ubah Rekening Payout - MarketTrust')

@section('content')
<div class="min-h-[calc(100vh-16rem)] py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
    <div class="glass-panel p-6 sm:p-10 relative overflow-hidden">
        <!-- Header -->
        <div class="mb-8 pb-6 border-b border-white/60">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                <a href="{{ route('seller.payout-accounts.index') }}" class="hover:underline">Rekening Payout</a>
                <span>&bull;</span>
                <span>Ubah</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Ubah Tujuan Payout
            </h1>
            <p class="text-sm text-slate-600 mt-1">
                Perbarui detail rekening bank, akun e-wallet, atau gambar barcode QRIS Anda.
            </p>
        </div>

        <form action="{{ route('seller.payout-accounts.update', $account) }}" method="POST" enctype="multipart/form-data" class="space-y-8" id="payoutForm">
            @csrf
            @method('PUT')

            <!-- 1. Pemilihan Jenis Tujuan -->
            <div class="space-y-3">
                <label class="glass-label">Pilih Jenis Tujuan Pencairan <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Bank Option -->
                    <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 bg-white/70 hover:bg-white destination-type-card" id="card-BANK">
                        <input type="radio" name="destination_type" value="BANK" class="sr-only" {{ old('destination_type', $account->destination_type->value) === 'BANK' ? 'checked' : '' }} onchange="switchDestinationType('BANK')">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <span class="w-4 h-4 rounded-full border-2 border-slate-300 check-indicator"></span>
                        </div>
                        <span class="text-sm font-bold text-slate-900">Rekening Bank</span>
                        <span class="text-[11px] text-slate-500 mt-0.5">BCA, Mandiri, BNI, BRI, dll.</span>
                    </label>

                    <!-- E-Wallet Option -->
                    <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 bg-white/70 hover:bg-white destination-type-card" id="card-EWALLET">
                        <input type="radio" name="destination_type" value="EWALLET" class="sr-only" {{ old('destination_type', $account->destination_type->value) === 'EWALLET' ? 'checked' : '' }} onchange="switchDestinationType('EWALLET')">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <span class="w-4 h-4 rounded-full border-2 border-slate-300 check-indicator"></span>
                        </div>
                        <span class="text-sm font-bold text-slate-900">E-Wallet</span>
                        <span class="text-[11px] text-slate-500 mt-0.5">GoPay, OVO, DANA, ShopeePay</span>
                    </label>

                    <!-- QRIS Option -->
                    <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 bg-white/70 hover:bg-white destination-type-card" id="card-QRIS">
                        <input type="radio" name="destination_type" value="QRIS" class="sr-only" {{ old('destination_type', $account->destination_type->value) === 'QRIS' ? 'checked' : '' }} onchange="switchDestinationType('QRIS')">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                            </div>
                            <span class="w-4 h-4 rounded-full border-2 border-slate-300 check-indicator"></span>
                        </div>
                        <span class="text-sm font-bold text-slate-900">QRIS Interaktif</span>
                        <span class="text-[11px] text-slate-500 mt-0.5">Upload Barcode QRIS Statis</span>
                    </label>
                </div>
                @error('destination_type')
                    <p class="text-[11px] text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- 2. Form Input Dinamis Berdasarkan Tipe -->
            <div class="space-y-5 pt-4 border-t border-slate-200/60">

                <!-- Bank Provider & E-wallet Provider Row -->
                <div id="section-provider">
                    <label for="destination_provider" class="glass-label" id="label-provider">
                        Nama Bank <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            id="destination_provider"
                            name="destination_provider"
                            list="provider-datalist"
                            value="{{ old('destination_provider', $account->destination_provider) }}"
                            placeholder="Pilih atau ketik nama bank (misal: BCA)"
                            class="glass-input w-full px-3.5 py-2.5 text-sm @error('destination_provider') border-rose-400 @enderror"
                            data-cy="input-payout-provider"
                        >
                        <datalist id="provider-datalist">
                            @foreach ($supportedBanks as $bank)
                                <option value="{{ $bank }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    @error('destination_provider')
                        <p class="text-[11px] text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Destination Number Row (Nomor Rekening / No HP) -->
                <div id="section-number">
                    <label for="destination_number" class="glass-label" id="label-number">
                        Nomor Rekening Bank <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="destination_number"
                        name="destination_number"
                        value="{{ old('destination_number', $account->destination_number) }}"
                        placeholder="Contoh: 1234567890"
                        class="glass-input w-full px-3.5 py-2.5 text-sm font-mono @error('destination_number') border-rose-400 @enderror"
                        data-cy="input-payout-number"
                    >
                    @error('destination_number')
                        <p class="text-[11px] text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @else
                        <p class="text-[10px] text-slate-500 mt-1" id="hint-number">Pastikan nomor rekening terdaftar dan aktif.</p>
                    @enderror
                </div>

                <!-- Destination Holder (Nama Pemilik / Atas Nama) -->
                <div>
                    <label for="destination_holder" class="glass-label">
                        Nama Pemilik / Atas Nama <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="destination_holder"
                        name="destination_holder"
                        value="{{ old('destination_holder', $account->destination_holder) }}"
                        required
                        placeholder="Nama lengkap sesuai buku rekening atau akun e-wallet"
                        class="glass-input w-full px-3.5 py-2.5 text-sm @error('destination_holder') border-rose-400 @enderror"
                        data-cy="input-payout-holder"
                    >
                    @error('destination_holder')
                        <p class="text-[11px] text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @else
                        <p class="text-[10px] text-slate-500 mt-1">Nama pemilik akun tujuan pencairan untuk validasi settlement.</p>
                    @enderror
                </div>

                <!-- QRIS Image Upload Row (Hanya jika tipe QRIS) -->
                <div id="section-qris" class="hidden space-y-3">
                    <label for="qris_image" class="glass-label">
                        Upload Gambar Barcode QRIS Baru <span class="text-slate-400 font-normal">(Kosongkan jika tidak ingin mengganti)</span>
                    </label>

                    <div class="flex flex-col sm:flex-row items-center gap-5">
                        <div class="w-32 h-32 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0" id="qris-preview-box">
                            @if ($account->destination_qris_image_url)
                                <img id="qris-preview-img" src="{{ $account->qris_image_full_url }}" class="w-full h-full object-contain" alt="QRIS Tersimpan">
                                <span class="text-xs text-slate-400 text-center px-2 hidden" id="qris-preview-text">Preview Gambar</span>
                            @else
                                <span class="text-xs text-slate-400 text-center px-2" id="qris-preview-text">Preview Gambar</span>
                                <img id="qris-preview-img" class="w-full h-full object-contain hidden" alt="Pratinjau QRIS">
                            @endif
                        </div>

                        <div class="flex-1 w-full">
                            <input
                                type="file"
                                id="qris_image"
                                name="qris_image"
                                accept="image/png,image/jpeg,image/webp"
                                onchange="previewQris(event)"
                                class="glass-input w-full px-3 py-2 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                data-cy="input-payout-qris-file"
                            >
                            <p class="text-[11px] text-slate-500 mt-1">Format: JPG, JPEG, PNG, atau WEBP. Maksimal ukuran 2MB.</p>
                        </div>
                    </div>

                    @error('qris_image')
                        <p class="text-[11px] text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jadikan Rekening Utama Checkbox -->
                <div class="pt-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', $account->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                            class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
                            data-cy="checkbox-payout-active"
                        >
                        <span class="text-xs sm:text-sm font-medium text-slate-700">
                            Jadikan tujuan payout ini sebagai <strong>rekening utama aktif</strong>
                        </span>
                    </label>
                    <p class="text-[10px] text-slate-500 ml-7 mt-0.5">
                        Jika dicentang, rekening aktif lainnya akan otomatis dinonaktifkan.
                    </p>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200/60">
                <a href="{{ route('seller.payout-accounts.index') }}" class="glass-btn-secondary py-2.5 px-5 text-sm">
                    Batal
                </a>
                <button type="submit" class="glass-btn-primary py-2.5 px-6 text-sm shadow-md shadow-indigo-500/20" data-cy="btn-submit-payout">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const supportedBanks = @json($supportedBanks);
    const supportedEwallets = @json($supportedEwallets);

    function switchDestinationType(type) {
        // Update Card Styles
        document.querySelectorAll('.destination-type-card').forEach(card => {
            card.classList.remove('border-indigo-600', 'bg-indigo-50/40');
            card.classList.add('border-slate-200');
            const ind = card.querySelector('.check-indicator');
            if (ind) {
                ind.classList.remove('border-indigo-600', 'bg-indigo-600');
                ind.classList.add('border-slate-300');
            }
        });

        const activeCard = document.getElementById('card-' + type);
        if (activeCard) {
            activeCard.classList.add('border-indigo-600', 'bg-indigo-50/40');
            activeCard.classList.remove('border-slate-200');
            const ind = activeCard.querySelector('.check-indicator');
            if (ind) {
                ind.classList.add('border-indigo-600', 'bg-indigo-600');
                ind.classList.remove('border-slate-300');
            }
        }

        const providerSec = document.getElementById('section-provider');
        const numberSec = document.getElementById('section-number');
        const qrisSec = document.getElementById('section-qris');

        const labelProvider = document.getElementById('label-provider');
        const inputProvider = document.getElementById('destination_provider');
        const labelNumber = document.getElementById('label-number');
        const inputNumber = document.getElementById('destination_number');
        const hintNumber = document.getElementById('hint-number');
        const datalist = document.getElementById('provider-datalist');

        if (type === 'BANK') {
            providerSec.classList.remove('hidden');
            numberSec.classList.remove('hidden');
            qrisSec.classList.add('hidden');

            labelProvider.innerHTML = 'Nama Bank <span class="text-rose-500">*</span>';
            inputProvider.placeholder = 'Pilih atau ketik nama bank (misal: BCA)';
            labelNumber.innerHTML = 'Nomor Rekening Bank <span class="text-rose-500">*</span>';
            inputNumber.placeholder = 'Contoh: 1234567890';
            hintNumber.innerText = 'Pastikan nomor rekening terdaftar dan aktif.';

            datalist.innerHTML = supportedBanks.map(b => `<option value="${b}"></option>`).join('');
        } else if (type === 'EWALLET') {
            providerSec.classList.remove('hidden');
            numberSec.classList.remove('hidden');
            qrisSec.classList.add('hidden');

            labelProvider.innerHTML = 'Penyedia Dompet Digital (E-Wallet) <span class="text-rose-500">*</span>';
            inputProvider.placeholder = 'Pilih penyedia e-wallet (misal: GoPay, DANA)';
            labelNumber.innerHTML = 'Nomor Ponsel Akun E-Wallet <span class="text-rose-500">*</span>';
            inputNumber.placeholder = 'Contoh: 081234567890';
            hintNumber.innerText = 'Nomor telepon seluler yang terhubung dengan akun dompet digital.';

            datalist.innerHTML = supportedEwallets.map(w => `<option value="${w}"></option>`).join('');
        } else if (type === 'QRIS') {
            providerSec.classList.remove('hidden');
            numberSec.classList.remove('hidden');
            qrisSec.classList.remove('hidden');

            labelProvider.innerHTML = 'Nama Penyedia QRIS / Bank Penerbit <span class="text-slate-400 font-normal">(Opsional)</span>';
            inputProvider.placeholder = 'Contoh: QRIS BCA, QRIS Mandiri, ShopeePay QRIS';
            labelNumber.innerHTML = 'NMID / Kode QRIS <span class="text-slate-400 font-normal">(Opsional)</span>';
            inputNumber.placeholder = 'Contoh: ID1020304050';
            hintNumber.innerText = 'Nomor National Merchant ID (NMID) jika tertera pada barcode.';

            datalist.innerHTML = '<option value="QRIS Nasional"></option><option value="QRIS BCA"></option><option value="QRIS Mandiri"></option>';
        }
    }

    function previewQris(event) {
        const file = event.target.files[0];
        const previewImg = document.getElementById('qris-preview-img');
        const previewText = document.getElementById('qris-preview-text');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
                if (previewText) previewText.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const checkedType = document.querySelector('input[name="destination_type"]:checked')?.value || '{{ $account->destination_type->value }}';
        switchDestinationType(checkedType);
    });
</script>
@endsection
