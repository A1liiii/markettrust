@extends('layouts.app')

@section('title', 'Pusat Notifikasi - MarketTrust')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Breadcrumb & Page Header -->
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-2" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Beranda</a>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-semibold">Pusat Notifikasi</span>
            </nav>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight" data-cy="notifications-title">
                        Pusat Notifikasi
                    </h1>
                    <p class="text-sm text-slate-600 mt-1">
                        Pantau pembaruan status transaksi, pembayaran escrow, peringatan batas waktu, dan resolusi sengketa Anda.
                    </p>
                </div>

                @if ($unreadCount > 0)
                    <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit" class="glass-btn-secondary text-xs sm:text-sm py-2 px-3.5 flex items-center gap-2" data-cy="btn-mark-all-read">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Tandai Semua Dibaca</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Filter Toolbar Tabs -->
        <div class="glass-panel p-2 sm:p-3 border border-white/80 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                <a
                    href="{{ route('notifications.index', ['filter' => 'all']) }}"
                    class="text-xs px-3.5 py-1.5 rounded-xl font-semibold transition flex items-center gap-1.5 {{ $currentFilter === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/80 text-slate-600 hover:bg-white' }}"
                    data-cy="filter-all"
                >
                    <span>Semua</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentFilter === 'all' ? 'bg-indigo-500 text-white' : 'bg-slate-200/80 text-slate-700' }}">{{ $totalCount }}</span>
                </a>
                <a
                    href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                    class="text-xs px-3.5 py-1.5 rounded-xl font-semibold transition flex items-center gap-1.5 {{ $currentFilter === 'unread' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/80 text-slate-600 hover:bg-white' }}"
                    data-cy="filter-unread"
                >
                    <span>Belum Dibaca</span>
                    @if ($unreadCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $currentFilter === 'unread' ? 'bg-white text-indigo-700' : 'bg-rose-500 text-white' }}">{{ $unreadCount }}</span>
                    @else
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentFilter === 'unread' ? 'bg-indigo-500 text-white' : 'bg-slate-200/80 text-slate-700' }}">0</span>
                    @endif
                </a>
                <a
                    href="{{ route('notifications.index', ['filter' => 'read']) }}"
                    class="text-xs px-3.5 py-1.5 rounded-xl font-semibold transition flex items-center gap-1.5 {{ $currentFilter === 'read' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/80 text-slate-600 hover:bg-white' }}"
                    data-cy="filter-read"
                >
                    <span>Sudah Dibaca</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentFilter === 'read' ? 'bg-indigo-500 text-white' : 'bg-slate-200/80 text-slate-700' }}">{{ $readCount }}</span>
                </a>
            </div>

            <div class="text-xs text-slate-500 px-2 font-medium">
                Total: <span class="font-bold text-slate-800">{{ $notifications->total() }}</span> pemberitahuan
            </div>
        </div>

        <!-- Notification List -->
        @if ($notifications->isEmpty())
            <div class="glass-panel p-8 sm:p-12 text-center border border-white/80 shadow-xs space-y-4" data-cy="empty-notifications">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-500 shadow-inner">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div class="max-w-md mx-auto">
                    <h2 class="text-base sm:text-lg font-bold text-slate-800">
                        @if ($currentFilter === 'unread')
                            Tidak Ada Notifikasi Baru
                        @elseif ($currentFilter === 'read')
                            Belum Ada Riwayat Notifikasi Dibaca
                        @else
                            Belum Ada Notifikasi
                        @endif
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        @if ($currentFilter === 'unread')
                            Semua pemberitahuan penting telah Anda baca. Kami akan memberi tahu Anda jika ada aktivitas transaksi terbaru.
                        @else
                            Aktivitas seperti kesepakatan baru, pembayaran Midtrans, peringatan batas waktu, dan sengketa akan muncul di sini.
                        @endif
                    </p>
                </div>
                <div class="pt-2">
                    <a href="{{ route('transactions.index') }}" class="glass-btn-primary text-xs sm:text-sm py-2 px-4 inline-flex items-center gap-1.5 shadow-xs">
                        Lihat Transaksi Saya
                    </a>
                </div>
            </div>
        @else
            <div class="space-y-3" data-cy="notification-list">
                @foreach ($notifications as $notification)
                    <div
                        class="glass-panel p-4 sm:p-5 border transition duration-200 relative {{ $notification->isRead() ? 'border-white/80 bg-white/60 hover:bg-white/80' : 'border-indigo-300/80 bg-white/90 shadow-xs hover:border-indigo-400' }}"
                        data-cy="notification-item-{{ $notification->notification_id }}"
                    >
                        <div class="flex items-start justify-between gap-3 sm:gap-4">
                            <!-- Icon & Content -->
                            <div class="flex items-start gap-3 sm:gap-4 flex-1 min-w-0">
                                <!-- Status Dot / Icon Indicator -->
                                <div class="shrink-0 mt-0.5">
                                    @if (! $notification->isRead())
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <!-- Text & Badges -->
                                <div class="flex-1 min-w-0 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $notification->badge_class }}">
                                            {{ $notification->event_label }}
                                        </span>

                                        @if ($notification->transaction_id)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                #TRX-{{ $notification->transaction_id }}
                                            </span>
                                        @endif

                                        @if (! $notification->isRead())
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100" data-cy="badge-unread">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
                                                Baru
                                            </span>
                                        @endif

                                        <span class="text-xs text-slate-400 ml-auto whitespace-nowrap" title="{{ $notification->created_at->format('d M Y, H:i:s') }}">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>
                                    </div>

                                    <p class="text-xs sm:text-sm text-slate-800 leading-relaxed font-medium break-words" data-cy="notification-message">
                                        {{ $notification->message }}
                                    </p>

                                    @if ($notification->isRead() && $notification->read_at)
                                        <p class="text-[11px] text-slate-400 pt-0.5">
                                            Dibaca pada {{ $notification->read_at->format('d M Y, H:i') }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="shrink-0 flex items-center gap-2 self-center sm:self-start pt-1">
                                @if ($notification->transaction_id)
                                    <a
                                        href="{{ route('notifications.read', $notification) }}"
                                        class="glass-btn-primary text-xs py-1.5 px-3 flex items-center gap-1 shadow-2xs whitespace-nowrap"
                                        title="Buka rujukan transaksi"
                                        data-cy="btn-open-{{ $notification->notification_id }}"
                                    >
                                        <span>Rincian</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
                                @elseif (! $notification->isRead())
                                    <form action="{{ route('notifications.read', $notification) }}" method="POST" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="glass-btn-secondary text-xs py-1.5 px-2.5 flex items-center gap-1 shadow-2xs whitespace-nowrap"
                                            title="Tandai sudah dibaca"
                                            data-cy="btn-read-{{ $notification->notification_id }}"
                                        >
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Dibaca</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="pt-4">
                {{ $notifications->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
