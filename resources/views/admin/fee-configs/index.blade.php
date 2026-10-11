@extends('layouts.app')

@section('title', 'Struktur Fee Platform & Simulator - MarketTrust Admin')

@section('content')
<div class="min-h-[calc(100vh-16rem)] py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto space-y-8">
    
    <!-- Header Section -->
    <div class="glass-panel p-6 sm:p-8 relative overflow-hidden">
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 relative z-10">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="glass-badge glass-badge-primary text-xs">Admin Control</span>
                    <span class="glass-badge glass-badge-success text-xs">PBL Escrow Engine</span>
                    <span class="glass-badge glass-badge-neutral text-xs">SKPL Revisi Okt 2026</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Struktur Tarif Layanan & Simulasi Transaksi
                </h1>
                <p class="text-sm text-slate-600 mt-1 max-w-3xl">
                    Sistem tarif berjenjang (degresif) resmi MarketTrust. Nilai biaya layanan dikalkulasi langsung oleh aturan aplikasi dan dikunci permanen pada snapshot kesepakatan transaksi (tabel <code class="text-xs bg-slate-100 text-slate-700 px-1 py-0.5 rounded">agreements</code>).
                </p>
            </div>
            <button 
                type="button" 
                onclick="document.getElementById('new-config-modal').classList.remove('hidden')"
                class="glass-btn-secondary text-xs sm:text-sm py-2.5 px-4 shadow-xs inline-flex items-center gap-2 shrink-0"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Catat Riwayat Legasi</span>
            </button>
        </div>
    </div>

    <!-- Alert / Notice Aturan Pembebanan Berdasarkan Jalur Transaksi -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Marketplace Rule -->
        <div class="glass-card p-5 relative overflow-hidden border-l-4 border-l-indigo-500">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                <h3 class="text-sm font-bold text-slate-900">Jalur Marketplace</h3>
                <span class="glass-badge glass-badge-primary text-[10px] ml-auto">Wajib EXCLUDE</span>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed mb-3">
                Fee layanan wajib dihitung dari harga listing dan <strong>ditambahkan pada pembayaran Buyer</strong>. Seller menerima harga listing penuh sebagai haknya. Buyer tidak memilih mode saat checkout.
            </p>
            <div class="p-2.5 rounded-xl bg-indigo-50/70 text-[11px] font-mono text-indigo-900 space-y-1">
                <div>• Total Buyer = Harga Listing + Fee Layanan</div>
                <div>• Hak Seller = Harga Listing (Penuh)</div>
            </div>
        </div>

        <!-- Forum / DM Rule -->
        <div class="glass-card p-5 relative overflow-hidden border-l-4 border-l-emerald-500">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <h3 class="text-sm font-bold text-slate-900">Jalur Forum / Direct Message</h3>
                <span class="glass-badge glass-badge-success text-[10px] ml-auto">EX / INC Disepakati</span>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed mb-3">
                Mode <span class="font-semibold text-slate-800">EXCLUDE</span> atau <span class="font-semibold text-slate-800">INCLUDE</span> ditentukan dalam usulan kesepakatan dan wajib <strong>disetujui kedua pihak</strong> sebelum kesepakatan dikunci.
            </p>
            <div class="p-2.5 rounded-xl bg-emerald-50/70 text-[11px] font-mono text-emerald-900 space-y-1">
                <div>• Mode EX: Buyer bayar (Harga + Fee), Seller terima Harga</div>
                <div>• Mode INC: Buyer bayar Harga, Seller terima (Harga - Fee)</div>
            </div>
        </div>
    </div>

    <!-- Tabel 7 Tier Rumus Resmi SKPL & Simulator Live -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Kolom Kiri: Tabel 7 Tier Degresif SKPL (6 Cols) -->
        <div class="lg:col-span-6 space-y-6">
            <div class="glass-card p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/60">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Rumus Standar</span>
                            <h3 class="text-base font-bold text-slate-900">Struktur Tarif 7 Tier (SKPL)</h3>
                        </div>
                    </div>
                    <span class="glass-badge glass-badge-neutral text-xs">Degresif</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200/80 text-slate-500 font-semibold uppercase tracking-wider">
                                <th class="py-2.5 px-2">Tier</th>
                                <th class="py-2.5 px-2">Rentang Transaksi</th>
                                <th class="py-2.5 px-2">Metode & Tarif</th>
                                <th class="py-2.5 px-2 text-right">Rentang Fee</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/50 text-slate-700">
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2.5 px-2 font-bold text-indigo-600">1</td>
                                <td class="py-2.5 px-2 font-medium">Rp5.000 – Rp10.000</td>
                                <td class="py-2.5 px-2"><span class="glass-badge glass-badge-primary text-[10px]">10%</span></td>
                                <td class="py-2.5 px-2 text-right font-mono font-semibold">Rp500 – Rp1.000</td>
                            </tr>
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2.5 px-2 font-bold text-indigo-600">2</td>
                                <td class="py-2.5 px-2 font-medium">&gt; Rp10.000 – Rp50.000</td>
                                <td class="py-2.5 px-2 text-slate-500">Interpolasi Linier</td>
                                <td class="py-2.5 px-2 text-right font-mono font-semibold">Rp1.000 – Rp2.500</td>
                            </tr>
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2.5 px-2 font-bold text-indigo-600">3</td>
                                <td class="py-2.5 px-2 font-medium">&gt; Rp50.000 – Rp100.000</td>
                                <td class="py-2.5 px-2"><span class="glass-badge glass-badge-primary text-[10px]">5%</span></td>
                                <td class="py-2.5 px-2 text-right font-mono font-semibold">Rp2.500 – Rp5.000</td>
                            </tr>
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2.5 px-2 font-bold text-indigo-600">4</td>
                                <td class="py-2.5 px-2 font-medium">&gt; Rp100.000 – Rp499.000</td>
                                <td class="py-2.5 px-2 text-slate-500">Interpolasi Linier</td>
                                <td class="py-2.5 px-2 text-right font-mono font-semibold">Rp5.000 – Rp7.000</td>
                            </tr>
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2.5 px-2 font-bold text-indigo-600">5</td>
                                <td class="py-2.5 px-2 font-medium">&gt; Rp499.000 – Rp1.000.000</td>
                                <td class="py-2.5 px-2 text-slate-500">Interpolasi Linier</td>
                                <td class="py-2.5 px-2 text-right font-mono font-semibold">Rp7.000 – Rp10.000</td>
                            </tr>
                            <tr class="hover:bg-slate-50/50 bg-indigo-50/20">
                                <td class="py-2.5 px-2 font-bold text-indigo-600">6</td>
                                <td class="py-2.5 px-2 font-medium">Rp1.000.000 – &lt; Rp10.000.000</td>
                                <td class="py-2.5 px-2"><span class="glass-badge glass-badge-success text-[10px]">Flat Fee</span></td>
                                <td class="py-2.5 px-2 text-right font-mono font-bold text-emerald-700">Rp10.000</td>
                            </tr>
                            <tr class="hover:bg-slate-50/50 bg-indigo-50/20">
                                <td class="py-2.5 px-2 font-bold text-indigo-600">7</td>
                                <td class="py-2.5 px-2 font-medium">&ge; Rp10.000.000</td>
                                <td class="py-2.5 px-2 text-slate-500">Rp20rb + Rp10rb/kelipatan 10jt</td>
                                <td class="py-2.5 px-2 text-right font-mono font-bold text-indigo-700">&ge; Rp20.000</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 p-3 rounded-xl bg-amber-50/80 border border-amber-200/70 text-xs text-amber-900 leading-relaxed flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <strong>Batas Minimum Platform:</strong> Transaksi di bawah <strong>Rp5.000</strong> ditolak oleh sistem. Fee selalu dibulatkan ke atas (<em>ceil</em>) ke rupiah penuh.
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Simulator Interaktif (6 Cols) -->
        <div class="lg:col-span-6 space-y-6">
            <div class="glass-card p-6">
                <div class="flex items-center gap-3 mb-5 pb-3 border-b border-slate-200/60">
                    <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Simulator Interaktif</span>
                        <h3 class="text-base font-bold text-slate-900">Uji Coba Kalkulator Realtime</h3>
                    </div>
                </div>

                <!-- Pemilihan Jalur Transaksi -->
                <div class="mb-4">
                    <label class="glass-label">Pilih Jalur Transaksi</label>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <button 
                            type="button" 
                            id="btn-channel-marketplace" 
                            onclick="setChannel('MARKETPLACE')"
                            class="py-2 px-3 rounded-xl text-xs font-bold transition border border-indigo-500 bg-indigo-600 text-white shadow-xs text-center"
                        >
                            Marketplace (Katalog)
                        </button>
                        <button 
                            type="button" 
                            id="btn-channel-forum" 
                            onclick="setChannel('FORUM')"
                            class="py-2 px-3 rounded-xl text-xs font-semibold transition border border-slate-200 bg-white/70 text-slate-700 hover:bg-white text-center"
                        >
                            Forum / Direct Message
                        </button>
                    </div>
                </div>

                <!-- Opsi Mode Fee EX/INC (Khusus Forum/DM) -->
                <div id="forum-mode-selector" class="mb-4 hidden p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <label class="glass-label text-xs">Kesepakatan Pembebanan Fee (Forum/DM)</label>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <button 
                            type="button" 
                            id="btn-mode-exclude" 
                            onclick="setSimulationMode('EXCLUDE')"
                            class="py-1.5 px-2.5 rounded-lg text-xs font-bold transition border border-indigo-500 bg-indigo-600 text-white text-center"
                        >
                            EXCLUDE (Buyer Bayar)
                        </button>
                        <button 
                            type="button" 
                            id="btn-mode-include" 
                            onclick="setSimulationMode('INCLUDE')"
                            class="py-1.5 px-2.5 rounded-lg text-xs font-semibold transition border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 text-center"
                        >
                            INCLUDE (Seller Bayar)
                        </button>
                    </div>
                </div>

                <!-- Input Harga Dasar -->
                <div class="mb-4">
                    <label for="sim_price" class="glass-label">Harga Dasar Transaksi (Rp)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-semibold text-xs">Rp</span>
                        <input 
                            type="number" 
                            id="sim_price" 
                            value="150000" 
                            min="5000" 
                            step="5000"
                            class="glass-input w-full pl-9 pr-3.5 py-2.5 text-sm font-bold text-slate-900"
                            oninput="runFeeSimulation()"
                        >
                    </div>

                    <!-- Presets Cepat Tiap Tier -->
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <span class="text-[10px] text-slate-400 self-center mr-1">Preset:</span>
                        <button type="button" onclick="setSimPrice(7500)" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium">7,5rb (T1)</button>
                        <button type="button" onclick="setSimPrice(25000)" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium">25rb (T2)</button>
                        <button type="button" onclick="setSimPrice(75000)" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium">75rb (T3)</button>
                        <button type="button" onclick="setSimPrice(250000)" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium">250rb (T4)</button>
                        <button type="button" onclick="setSimPrice(750000)" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium">750rb (T5)</button>
                        <button type="button" onclick="setSimPrice(2500000)" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium">2,5jt (T6)</button>
                        <button type="button" onclick="setSimPrice(15000000)" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium">15jt (T7)</button>
                    </div>
                </div>

                <!-- Error Warning Box (Jika < 5.000) -->
                <div id="sim-error-box" class="hidden mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Nominal transaksi di bawah batas minimum sistem (&lt; Rp5.000 ditolak).</span>
                </div>

                <!-- Hasil Perhitungan Live Box -->
                <div id="sim-result-box" class="p-5 rounded-2xl bg-gradient-to-br from-indigo-500/10 via-purple-500/5 to-white/60 border border-indigo-100 space-y-3">
                    <div class="flex items-center justify-between text-xs text-slate-600">
                        <div>
                            <span class="font-medium">Tier Tarif:</span>
                            <span id="sim-tier-name" class="font-bold text-indigo-700 block text-[11px]">-</span>
                        </div>
                        <div class="text-right">
                            <span class="text-slate-500 block text-[10px]">Biaya Layanan (Fee):</span>
                            <span id="sim-fee-amount" class="font-black text-indigo-700 text-base">Rp0</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-indigo-200/60 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-700 block">Total Tagihan Buyer:</span>
                            <span class="text-[10px] text-slate-500" id="buyer-calc-note">(Harga Dasar + Fee)</span>
                        </div>
                        <span id="sim-buyer-total" class="text-xl font-black text-indigo-600 tracking-tight">Rp0</span>
                    </div>

                    <div class="pt-2 border-t border-indigo-200/60 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-700 block">Penerimaan Bersih Seller:</span>
                            <span class="text-[10px] text-slate-500" id="seller-calc-note">(Harga Dasar Penuh)</span>
                        </div>
                        <span id="sim-seller-net" class="text-xl font-black text-emerald-600 tracking-tight">Rp0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat Konfigurasi Fee Legasi di Database -->
    <div class="glass-card p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-200/60">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Database Archive</span>
                    <span class="glass-badge glass-badge-neutral text-[10px]">Tabel `fee_configs`</span>
                </div>
                <h3 class="text-base font-bold text-slate-900">Riwayat Catatan Konfigurasi Database</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Data legasi pada tabel <code class="font-mono text-slate-700">fee_configs</code>. Kesepakatan transaksi aktif kini menggunakan aturan aplikasi 7 tier degresif di atas dan nilainya di-snapshot permanen pada <code class="font-mono text-slate-700">agreements</code>.
                </p>
            </div>
            <span class="text-xs text-slate-500 shrink-0">Total: {{ $configs->count() }} rekaman</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 text-slate-500 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-3">ID</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3">Rentang Berlaku</th>
                        <th class="py-3 px-3">Persentase</th>
                        <th class="py-3 px-3">Flat Fee</th>
                        <th class="py-3 px-3">Mulai Berlaku</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/50 text-slate-700">
                    @forelse ($configs as $cfg)
                        <tr class="hover:bg-white/40 transition {{ $cfg->is_active ? 'bg-indigo-50/30' : '' }}">
                            <td class="py-3 px-3 font-mono font-medium text-slate-500">
                                #CFG-{{ str_pad($cfg->fee_config_id, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="py-3 px-3">
                                @if ($cfg->is_active)
                                    <span class="glass-badge glass-badge-primary text-[10px]">Tercatat Aktif</span>
                                @else
                                    <span class="glass-badge glass-badge-neutral text-[10px]">Arsip</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-semibold text-slate-900">
                                Rp{{ number_format((float) $cfg->min_amount, 0, ',', '.') }} – Rp{{ number_format((float) $cfg->max_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 font-bold text-indigo-600">
                                {{ number_format((float) $cfg->fee_percent, 2, ',', '.') }}%
                            </td>
                            <td class="py-3 px-3">
                                Rp{{ number_format((float) $cfg->flat_fee, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-slate-500">
                                {{ $cfg->effective_from ? $cfg->effective_from->format('d M Y, H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">
                                Belum ada riwayat konfigurasi tersimpan pada database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Tetapkan Konfigurasi Fee Baru (Legasi) -->
<div id="new-config-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="glass-panel max-w-lg w-full p-6 sm:p-8 relative shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200/60 mb-5">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Database Record</span>
                <h3 class="text-lg font-bold text-slate-900">Catat Konfigurasi Database Baru</h3>
            </div>
            <button 
                type="button" 
                onclick="document.getElementById('new-config-modal').classList.add('hidden')"
                class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form action="{{ route('admin.fee-configs.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="min_amount" class="glass-label">Batas Minimal (Rp) <span class="text-rose-500">*</span></label>
                    <input 
                        type="number" 
                        id="min_amount" 
                        name="min_amount" 
                        value="{{ old('min_amount', $activeConfig ? (int) $activeConfig->min_amount : 5000) }}" 
                        required 
                        min="1000" 
                        step="1000"
                        class="glass-input w-full px-3 py-2 text-sm"
                    >
                </div>
                <div>
                    <label for="max_amount" class="glass-label">Batas Maksimal (Rp) <span class="text-rose-500">*</span></label>
                    <input 
                        type="number" 
                        id="max_amount" 
                        name="max_amount" 
                        value="{{ old('max_amount', $activeConfig ? (int) $activeConfig->max_amount : 100000000) }}" 
                        required 
                        min="1000" 
                        step="10000"
                        class="glass-input w-full px-3 py-2 text-sm"
                    >
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="fee_percent" class="glass-label">Persentase Fee (%) <span class="text-rose-500">*</span></label>
                    <input 
                        type="number" 
                        id="fee_percent" 
                        name="fee_percent" 
                        value="{{ old('fee_percent', $activeConfig ? (float) $activeConfig->fee_percent : 5.0) }}" 
                        required 
                        min="0" 
                        max="50" 
                        step="0.01"
                        class="glass-input w-full px-3 py-2 text-sm"
                    >
                </div>
                <div>
                    <label for="flat_fee" class="glass-label">Batas Bawah Flat (Rp) <span class="text-rose-500">*</span></label>
                    <input 
                        type="number" 
                        id="flat_fee" 
                        name="flat_fee" 
                        value="{{ old('flat_fee', $activeConfig ? (int) $activeConfig->flat_fee : 1000) }}" 
                        required 
                        min="0" 
                        step="500"
                        class="glass-input w-full px-3 py-2 text-sm"
                    >
                </div>
            </div>

            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
                Menyimpan data ini memperbarui catatan tabel legasi <code class="font-mono">fee_configs</code> di database.
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/60">
                <button 
                    type="button" 
                    onclick="document.getElementById('new-config-modal').classList.add('hidden')"
                    class="glass-btn-secondary text-xs py-2 px-4"
                >
                    Batal
                </button>
                <button 
                    type="submit" 
                    class="glass-btn-primary text-xs py-2 px-4 shadow-xs"
                >
                    Simpan Catatan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentChannel = 'MARKETPLACE';
    let currentSimulationMode = 'EXCLUDE';

    function setChannel(channel) {
        currentChannel = channel;
        const btnMkt = document.getElementById('btn-channel-marketplace');
        const btnFrm = document.getElementById('btn-channel-forum');
        const forumSelector = document.getElementById('forum-mode-selector');

        if (channel === 'MARKETPLACE') {
            btnMkt.className = 'py-2 px-3 rounded-xl text-xs font-bold transition border border-indigo-500 bg-indigo-600 text-white shadow-xs text-center';
            btnFrm.className = 'py-2 px-3 rounded-xl text-xs font-semibold transition border border-slate-200 bg-white/70 text-slate-700 hover:bg-white text-center';
            forumSelector.classList.add('hidden');
            currentSimulationMode = 'EXCLUDE';
            updateModeDisplay();
        } else {
            btnFrm.className = 'py-2 px-3 rounded-xl text-xs font-bold transition border border-indigo-500 bg-indigo-600 text-white shadow-xs text-center';
            btnMkt.className = 'py-2 px-3 rounded-xl text-xs font-semibold transition border border-slate-200 bg-white/70 text-slate-700 hover:bg-white text-center';
            forumSelector.classList.remove('hidden');
        }

        runFeeSimulation();
    }

    function setSimulationMode(mode) {
        currentSimulationMode = mode;
        updateModeDisplay();
        runFeeSimulation();
    }

    function updateModeDisplay() {
        const btnExclude = document.getElementById('btn-mode-exclude');
        const btnInclude = document.getElementById('btn-mode-include');
        const buyerNote = document.getElementById('buyer-calc-note');
        const sellerNote = document.getElementById('seller-calc-note');

        if (currentSimulationMode === 'EXCLUDE') {
            if (btnExclude) btnExclude.className = 'py-1.5 px-2.5 rounded-lg text-xs font-bold transition border border-indigo-500 bg-indigo-600 text-white text-center';
            if (btnInclude) btnInclude.className = 'py-1.5 px-2.5 rounded-lg text-xs font-semibold transition border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 text-center';
            buyerNote.innerText = '(Harga Dasar + Fee Layanan)';
            sellerNote.innerText = '(Harga Dasar Penuh)';
        } else {
            if (btnInclude) btnInclude.className = 'py-1.5 px-2.5 rounded-lg text-xs font-bold transition border border-indigo-500 bg-indigo-600 text-white text-center';
            if (btnExclude) btnExclude.className = 'py-1.5 px-2.5 rounded-lg text-xs font-semibold transition border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 text-center';
            buyerNote.innerText = '(Harga Dasar Penuh)';
            sellerNote.innerText = '(Harga Dasar - Fee Layanan)';
        }
    }

    function setSimPrice(val) {
        document.getElementById('sim_price').value = val;
        runFeeSimulation();
    }

    function calculate7TierFee(price) {
        if (price < 5000) {
            return { valid: false, tier: 'Di Bawah Minimum', fee: 0 };
        }

        let fee = 0;
        let tierName = '';

        if (price <= 10000) {
            tierName = 'Tier 1 (Rp5.000 – Rp10.000: 10%)';
            fee = price * 0.10;
        } else if (price <= 50000) {
            tierName = 'Tier 2 (> Rp10.000 – Rp50.000: Interpolasi Rp1.000 – Rp2.500)';
            fee = 1000 + ((price - 10000) / (50000 - 10000)) * (2500 - 1000);
        } else if (price <= 100000) {
            tierName = 'Tier 3 (> Rp50.000 – Rp100.000: 5%)';
            fee = price * 0.05;
        } else if (price <= 499000) {
            tierName = 'Tier 4 (> Rp100.000 – Rp499.000: Interpolasi Rp5.000 – Rp7.000)';
            fee = 5000 + ((price - 100000) / (499000 - 100000)) * (7000 - 5000);
        } else if (price <= 1000000) {
            tierName = 'Tier 5 (> Rp499.000 – Rp1.000.000: Interpolasi Rp7.000 – Rp10.000)';
            fee = 7000 + ((price - 499000) / (1000000 - 499000)) * (10000 - 7000);
        } else if (price < 10000000) {
            tierName = 'Tier 6 (Rp1.000.000 – < Rp10.000.000: Flat Rp10.000)';
            fee = 10000;
        } else {
            tierName = 'Tier 7 (≥ Rp10.000.000: Rp20.000 + Rp10.000/10jt)';
            const multiples = Math.floor(price / 10000000);
            fee = 20000 + (multiples - 1) * 10000;
        }

        return { valid: true, tier: tierName, fee: Math.ceil(fee) };
    }

    function runFeeSimulation() {
        const input = document.getElementById('sim_price');
        const price = parseFloat(input.value) || 0;
        const errBox = document.getElementById('sim-error-box');
        const resBox = document.getElementById('sim-result-box');

        const calc = calculate7TierFee(price);

        if (!calc.valid) {
            errBox.classList.remove('hidden');
            resBox.classList.add('opacity-40');
            document.getElementById('sim-tier-name').innerText = 'Tidak Sah (< Rp5.000)';
            document.getElementById('sim-fee-amount').innerText = 'Rp0';
            document.getElementById('sim-buyer-total').innerText = 'Rp0';
            document.getElementById('sim-seller-net').innerText = 'Rp0';
            return;
        }

        errBox.classList.add('hidden');
        resBox.classList.remove('opacity-40');

        const feeAmount = calc.fee;
        let buyerTotal = 0;
        let sellerNet = 0;

        if (currentSimulationMode === 'EXCLUDE') {
            buyerTotal = price + feeAmount;
            sellerNet = price;
        } else {
            buyerTotal = price;
            sellerNet = Math.max(0, price - feeAmount);
        }

        document.getElementById('sim-tier-name').innerText = calc.tier;
        document.getElementById('sim-fee-amount').innerText = 'Rp' + feeAmount.toLocaleString('id-ID');
        document.getElementById('sim-buyer-total').innerText = 'Rp' + buyerTotal.toLocaleString('id-ID');
        document.getElementById('sim-seller-net').innerText = 'Rp' + sellerNet.toLocaleString('id-ID');
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateModeDisplay();
        runFeeSimulation();
    });
</script>
@endsection
