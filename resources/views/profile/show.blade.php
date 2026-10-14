@extends('layouts.app')

@section('title', 'Profil Pengguna - MarketTrust')

@section('content')
<div class="min-h-[calc(100vh-16rem)] py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
    <!-- Main Glass Panel -->
    <div class="glass-panel p-6 sm:p-10 relative overflow-hidden">
        <!-- Accent Ambient Glow inside card -->
        <div class="absolute -right-24 -top-24 w-72 h-72 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
        <div class="absolute -left-24 -bottom-24 w-72 h-72 bg-purple-500/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

        <!-- Header Profil -->
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 pb-8 border-b border-white/60 relative z-10">
            <!-- Avatar Display -->
            <div class="relative shrink-0">
                @if ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}" class="w-24 h-24 sm:w-28 sm:h-28 rounded-3xl object-cover ring-4 ring-white/80 shadow-lg shadow-indigo-500/10 hover:scale-105 transition-transform duration-200">
                @else
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-3xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-3xl font-bold shadow-lg shadow-indigo-500/20 ring-4 ring-white/80">
                        {{ strtoupper(substr($user->username, 0, 1)) }}
                    </div>
                @endif
                <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full border-2 border-white {{ $user->account_status === 'ACTIVE' ? 'bg-emerald-500' : 'bg-rose-500' }}" title="Status Akun: {{ $user->account_status }}"></span>
            </div>

            <!-- Detail Info Header -->
            <div class="text-center sm:text-left flex-1">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 mb-2">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        @<span>{{ $user->username }}</span>
                    </h1>
                    <span class="glass-badge {{ $user->account_status === 'ACTIVE' ? 'glass-badge-success' : 'glass-badge-danger' }} text-xs py-0.5 px-2.5">
                        {{ $user->account_status }}
                    </span>
                    <span class="glass-badge glass-badge-primary text-xs py-0.5 px-2.5">
                        {{ $user->account_type }}
                    </span>
                    @if ($user->email_verified_at)
                        <span class="glass-badge glass-badge-success text-xs py-0.5 px-2.5" title="Email terverifikasi pada {{ $user->email_verified_at->format('d M Y') }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Terverifikasi
                        </span>
                    @endif
                </div>

                <p class="text-sm text-slate-600 mb-4 flex items-center justify-center sm:justify-start gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ $user->email }}</span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-xs text-slate-500">ID: #MT-{{ str_pad($user->user_id, 4, '0', STR_PAD_LEFT) }}</span>
                </p>

                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                    <a href="{{ route('profile.edit') }}" class="glass-btn-primary text-xs sm:text-sm py-2 px-4 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        <span>Ubah Foto & Password</span>
                    </a>
                    <a href="{{ route('home') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-4">
                        <span>Kembali ke Beranda</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 Kartu Metrik & Statistik Representatif -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 pt-8 relative z-10">
            <!-- Metrik 1: Poin Reputasi & Legitimasi -->
            <div class="glass-card glass-card-interactive p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Reputasi & Poin</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ $user->reputation_points > 0 ? '+' : '' }}{{ $user->reputation_points }} <span class="text-xs font-semibold text-slate-500">Poin</span>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-200/60 mt-3">
                    @if ($user->reputation_status === 'LEGIT')
                        <span class="glass-badge glass-badge-success text-[10px]">
                            Terverifikasi (LEGIT)
                        </span>
                    @elseif ($user->reputation_status === 'PROBLEMATIC')
                        <span class="glass-badge glass-badge-danger text-[10px]">
                            Perlu Perhatian (PROBLEMATIC)
                        </span>
                    @else
                        <span class="glass-badge glass-badge-neutral text-[10px]" title="Belum ada riwayat evaluasi transaksi atau keputusan admin">
                            Belum Ditinjau
                        </span>
                    @endif
                </div>
            </div>

            <!-- Metrik 2: Transaksi Escrow Selesai -->
            <div class="glass-card glass-card-interactive p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Transaksi Escrow</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ $user->completed_transactions_count }} <span class="text-xs font-semibold text-slate-500">Selesai</span>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-200/60 mt-3 text-[11px] text-slate-600 flex items-center justify-between">
                    <span>{{ $user->seller_transactions_count }}x Seller</span>
                    <span>&bull;</span>
                    <span>{{ $user->buyer_transactions_count }}x Buyer</span>
                </div>
            </div>

            <!-- Metrik 3: Listing Marketplace Aktif -->
            <div class="glass-card glass-card-interactive p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Marketplace</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ $user->active_listings_count }} <span class="text-xs font-semibold text-slate-500">Listing</span>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-200/60 mt-3 text-[11px] text-slate-600">
                    <span>Produk digital aktif dijual</span>
                </div>
            </div>

            <!-- Metrik 4: Postingan Komunitas Forum -->
            <div class="glass-card glass-card-interactive p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Forum Komunitas</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ $user->forum_posts_count }} <span class="text-xs font-semibold text-slate-500">Postingan</span>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-200/60 mt-3 text-[11px] text-slate-600">
                    <span>Diskusi Umum, WTB & WTS</span>
                </div>
            </div>
        </div>

        <!-- Detail Keamanan & Ketentuan Transaksi Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-8 relative z-10">
            <!-- Kolom 1: Status Autentikasi & Keamanan Akun -->
            <div class="glass-card p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Keamanan Akun</span>
                        <h4 class="text-base font-bold text-slate-900">Metode Akses & Autentikasi</h4>
                    </div>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between py-2 border-b border-slate-200/60">
                        <div>
                            <span class="font-medium text-slate-800 block">Koneksi Akun Google</span>
                            <span class="text-xs text-slate-500">Masuk satu-klik dengan Google OAuth</span>
                        </div>
                        @if ($user->google_id)
                            <span class="glass-badge glass-badge-success text-xs">Terhubung</span>
                        @else
                            <span class="glass-badge glass-badge-neutral text-xs">Belum Ada</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between py-2 border-b border-slate-200/60">
                        <div>
                            <span class="font-medium text-slate-800 block">Kata Sandi (Password) Lokal</span>
                            <span class="text-xs text-slate-500">Login kredensial mandiri dengan email & password</span>
                        </div>
                        @if ($user->hasPassword())
                            <span class="glass-badge glass-badge-success text-xs">Aktif</span>
                        @else
                            <span class="glass-badge glass-badge-warning text-xs">Belum Dibuat</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <div>
                            <span class="font-medium text-slate-800 block">Tanggal Registrasi</span>
                            <span class="text-xs text-slate-500">Waktu pertama akun terdaftar di sistem</span>
                        </div>
                        <span class="font-semibold text-slate-800 text-xs">
                            {{ $user->created_at ? $user->created_at->format('d M Y, H:i') : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Kolom 2: Informasi Pengguna & Simulasi Akun -->
            <div class="glass-card p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Informasi Transaksi</span>
                        <h4 class="text-base font-bold text-slate-900">Ketentuan Transaksi dan Layanan</h4>
                    </div>
                </div>

                <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                    <div class="p-3 rounded-xl bg-white/60 border border-white/80">
                        <strong class="text-slate-800 block mb-0.5">Peran Akun Fleksibel</strong>
                        Akun Anda dapat bertindak sebagai Pembeli (Buyer) untuk produk marketplace/kesepakatan, maupun Penjual (Seller) tanpa perlu membuat akun ganda.
                    </div>
                    <div class="p-3 rounded-xl bg-white/60 border border-white/80">
                        <strong class="text-slate-800 block mb-0.5">Pembayaran Midtrans Sandbox</strong>
                        Seluruh alur pembayaran menggunakan Midtrans Sandbox (lingkungan uji coba tanpa uang riil). Tidak ada penagihan dana riil pada kartu/rekening Anda.
                    </div>
                    <div class="p-3 rounded-xl bg-white/60 border border-white/80">
                        <strong class="text-slate-800 block mb-0.5">Pencatatan Payout & Refund Simulatif</strong>
                        Pencairan dana ke seller (Payout) atau pengembalian dana ke buyer (Refund) dilakukan secara simulatif melalui pencatatan sistem setelah konfirmasi barang atau keputusan komplain.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
