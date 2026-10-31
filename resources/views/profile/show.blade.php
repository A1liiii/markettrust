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

                @if ($isOwnProfile)
                    <p class="text-sm text-slate-600 mb-4 flex items-center justify-center sm:justify-start gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ $user->email }}</span>
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-xs text-slate-500">ID: #MT-{{ str_pad($user->user_id, 4, '0', STR_PAD_LEFT) }}</span>
                    </p>
                @else
                    <p class="text-sm text-slate-600 mb-4 flex items-center justify-center sm:justify-start gap-2">
                        <span class="text-xs text-slate-500">ID Pengguna: #MT-{{ str_pad($user->user_id, 4, '0', STR_PAD_LEFT) }}</span>
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-xs text-slate-500">Bergabung {{ $user->created_at ? $user->created_at->format('M Y') : '-' }}</span>
                    </p>
                @endif

                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                    @if ($isOwnProfile)
                        <a href="{{ route('profile.edit') }}" class="glass-btn-primary text-xs sm:text-sm py-2 px-4 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            <span>Ubah Foto & Password</span>
                        </a>
                        <a href="{{ route('seller.payout-accounts.index') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-4 shadow-sm" data-cy="btn-profile-payout-accounts">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <span>Rekening Payout</span>
                        </a>
                    @else
                        @if ($existingConversation)
                            <a href="{{ route('conversations.show', $existingConversation) }}" class="glass-btn-primary text-xs sm:text-sm py-2 px-4 shadow-sm" data-cy="btn-profile-dm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span>Kirim Pesan / DM</span>
                            </a>
                        @else
                            <a href="{{ route('conversations.index') }}" class="glass-btn-primary text-xs sm:text-sm py-2 px-4 shadow-sm" data-cy="btn-profile-dm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span>Mulai Chat / Diskusi</span>
                            </a>
                        @endif

                        @if ($user->hasListings())
                            <a href="{{ route('marketplace.index') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-4 shadow-sm" data-cy="btn-profile-listings">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span>Lihat Etalase Produk</span>
                            </a>
                        @endif

                        @auth
                            <button
                                type="button"
                                onclick="openAccountReportModal({{ $user->user_id }}, '{{ addslashes($user->username) }}')"
                                class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs sm:text-sm font-bold flex items-center gap-1.5 transition active:scale-95 shadow-xs"
                                data-cy="btn-report-account"
                            >
                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Laporkan Akun</span>
                            </button>
                        @endauth
                    @endif
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

        <!-- Section Riwayat Peristiwa Reputasi (Reputation Ledger) -->
        <div class="pt-8 relative z-10" id="riwayat-reputasi">
            <div class="glass-card p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-slate-200/60 mb-6">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Riwayat Peristiwa Reputasi & Legitimasi</h2>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500">
                            Pencatatan transparan seluruh peristiwa yang menambah atau mengurangi poin legitimasi akun ini.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-500">Total Akumulasi:</span>
                        <span class="text-sm font-extrabold text-slate-900 px-3 py-1 rounded-xl bg-slate-100 border border-slate-200">
                            {{ $user->reputation_points > 0 ? '+' : '' }}{{ $user->reputation_points }} Poin
                        </span>
                    </div>
                </div>

                @if ($reputationEvents->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs sm:text-sm" data-cy="table-reputation-events">
                            <thead>
                                <tr class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-200/70">
                                    <th class="py-3 px-3">Waktu</th>
                                    <th class="py-3 px-3">Jenis Peristiwa</th>
                                    <th class="py-3 px-3">Poin</th>
                                    <th class="py-3 px-3">Status Baru</th>
                                    <th class="py-3 px-3">Keterangan Objektif</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-normal text-slate-700">
                                @foreach ($reputationEvents as $event)
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="py-3.5 px-3 whitespace-nowrap text-xs text-slate-500">
                                            {{ $event->created_at ? $event->created_at->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td class="py-3.5 px-3 whitespace-nowrap">
                                            <span class="font-medium text-slate-800 block">
                                                {{ $event->eventTypeLabel() }}
                                            </span>
                                            @if ($event->transaction_id)
                                                <span class="text-[10px] text-indigo-600 block">
                                                    Ref: Transaksi #{{ $event->transaction_id }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $event->pointsDeltaBadgeClass() }}">
                                                {{ $event->points_delta > 0 ? '+' : '' }}{{ $event->points_delta }} Poin
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-3 whitespace-nowrap">
                                            @if ($event->new_status)
                                                <span class="glass-badge {{ \App\Models\ReputationEvent::statusBadgeClass($event->new_status) }} text-[10px] py-0.5 px-2">
                                                    {{ $event->new_status }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-3 text-xs text-slate-600 max-w-xs sm:max-w-md">
                                            {{ $event->reason ?? 'Pencatatan otomatis kepatuhan sistem' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($reputationEvents->hasPages())
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            {{ $reputationEvents->links() }}
                        </div>
                    @endif
                @else
                    <!-- Empty State Ramah & Edukatif -->
                    <div class="text-center py-10 px-4 rounded-2xl bg-slate-50/50 border border-dashed border-slate-200" data-cy="empty-reputation-events">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 mb-1">Belum Ada Riwayat Peristiwa Reputasi</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                            Akun ini belum memiliki catatan evaluasi transaksi atau tindakan kepatuhan yang mengubah skor reputasi. Poin akan tercatat otomatis saat transaksi escrow selesai atau jika ada keputusan resmi moderator.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Section Panduan Indikator Reputasi & Legitimasi -->
        <div class="pt-8 relative z-10">
            <div class="glass-card p-6 sm:p-8">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Panduan Indikator Reputasi & Legitimasi</h3>
                        <p class="text-xs text-slate-500">Pahami arti setiap badge dan poin sebelum Anda menyetujui transaksi.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 rounded-2xl bg-white/60 border border-white/80 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="glass-badge glass-badge-success text-[10px]">LEGIT</span>
                                <span class="text-xs font-bold text-slate-800">Terverifikasi</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Diberikan kepada pengguna yang memiliki riwayat transaksi escrow lancar, tepat waktu, dan tingkat kepatuhan tinggi terhadap aturan platform.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/60 border border-white/80 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="glass-badge glass-badge-danger text-[10px]">PROBLEMATIC</span>
                                <span class="text-xs font-bold text-slate-800">Perlu Perhatian</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Diberikan kepada akun yang memiliki riwayat pembatalan sepihak pasca-bayar, sanksi moderasi, atau pelanggaran kesepakatan transaksi yang telah terbukti sah.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/60 border border-white/80 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="glass-badge glass-badge-neutral text-[10px]">UNREVIEWED</span>
                                <span class="text-xs font-bold text-slate-800">Belum Ditinjau</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Status bawaan untuk akun baru atau pengguna yang belum memiliki catatan transaksi atau evaluasi resmi dari moderator MarketTrust.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/60 border border-white/80 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="glass-badge glass-badge-primary text-[10px]">+ / - Poin</span>
                                <span class="text-xs font-bold text-slate-800">Poin Dinamis</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Skor reputasi dihitung secara dinamis dari akumulasi seluruh peristiwa transaksi berhasil (+poin) dan pengurangan akibat sanksi atau komplain (-poin).
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Catatan Kepatuhan Transaksi & KEEP (Hanya Ringkasan Sah Sesuai Kewenangan) -->
        <div class="pt-8 relative z-10">
            <div class="glass-card p-6 sm:p-8">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Catatan Kepatuhan Transaksi & KEEP</h3>
                        <p class="text-xs text-slate-500">Hasil tinjauan resmi pembatalan pasca-bayar dan pemenuhan kewajiban biaya platform.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kepatuhan Pembatalan Pasca-Bayar -->
                    <div class="p-4 rounded-2xl bg-white/60 border border-white/80">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Tinjauan Pembatalan Pasca-Bayar</h4>
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600">Kelalaian Pihak Penjual:</span>
                                @if ($sellerCancellationsCount > 0)
                                    <span class="font-bold text-rose-600">{{ $sellerCancellationsCount }}x Keputusan Admin</span>
                                @else
                                    <span class="font-semibold text-emerald-600">0 (Nir Kelalaian)</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600">Kelalaian Pihak Pembeli:</span>
                                @if ($buyerCancellationsCount > 0)
                                    <span class="font-bold text-rose-600">{{ $buyerCancellationsCount }}x Keputusan Admin</span>
                                @else
                                    <span class="font-semibold text-emerald-600">0 (Nir Kelalaian)</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-slate-400 pt-1 border-t border-slate-100">
                                Hanya menampilkan pembatalan pasca-bayar yang telah disetujui admin dan dibebankan pihak terkait.
                            </p>
                        </div>
                    </div>

                    <!-- Kepatuhan Kewajiban KEEP -->
                    <div class="p-4 rounded-2xl bg-white/60 border border-white/80">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Kewajiban Penggantian Fee (KEEP)</h4>
                        <div class="space-y-2 text-xs">
                            @if ($openKeepCount > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600">Status Kewajiban:</span>
                                    <span class="font-bold text-amber-600">{{ $openKeepCount }} Tagihan Terbuka</span>
                                </div>
                                @if ($isOwnProfile)
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-600">Sisa Tagihan Anda:</span>
                                        <span class="font-bold text-rose-600">Rp {{ number_format($openKeepAmount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="pt-2">
                                        <a href="{{ route('sellers.keep-ledger', $user->user_id) }}" class="text-xs font-bold text-indigo-600 hover:underline">
                                            Buka Ledger KEEP Seller &rarr;
                                        </a>
                                    </div>
                                @else
                                    <p class="text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                                        Penjual memiliki kewajiban kompensasi gateway fee yang sedang diselesaikan melalui sistem pemotongan otomatis.
                                    </p>
                                @endif
                            @else
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600">Status Kewajiban:</span>
                                    <span class="font-semibold text-emerald-600">Lunas / Tidak Ada Tunggakan</span>
                                </div>
                                <p class="text-[11px] text-slate-400 pt-1 border-t border-slate-100">
                                    Tidak ada catatan tunggakan kompensasi fee platform.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($isOwnProfile)
            <!-- Detail Keamanan & Ketentuan Transaksi (Hanya untuk Pemilik Akun Pribadi) -->
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

                <!-- Kolom 2: Informasi Transaksi & Rekening Payout -->
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
                        <div class="p-3 rounded-xl bg-white/60 border border-white/80 flex items-center justify-between">
                            <div>
                                <strong class="text-slate-800 block mb-0.5">Tujuan Payout Seller</strong>
                                @if ($user->activePayoutAccount)
                                    <span class="text-xs text-emerald-700 font-medium">
                                        {{ $user->activePayoutAccount->destination_provider ?? $user->activePayoutAccount->destination_type->value }}: {{ $user->activePayoutAccount->masked_destination_number }}
                                    </span>
                                @else
                                    <span class="text-xs text-amber-700 font-medium">Belum diatur (Payout tertahan)</span>
                                @endif
                            </div>
                            <a href="{{ route('seller.payout-accounts.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">
                                Kelola &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@include('account-reports.partials.report-modal')
@endsection
