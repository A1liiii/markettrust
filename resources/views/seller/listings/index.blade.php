@extends('layouts.app')

@section('title', 'Daftar Penawaran Listing - MarketTrust')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb & Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                    <span>Panel Seller</span>
                    <span>&bull;</span>
                    <span>Penawaran Digital</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Kelola Penawaran Listing
                </h1>
                <p class="text-sm text-slate-600 mt-1">
                    Atur stok akun game, item digital, dan lisensi Anda dengan sistem penyerahan escrow yang aman.
                </p>
            </div>

            <div>
                <a href="{{ route('seller.listings.create') }}" class="glass-btn-primary text-xs sm:text-sm py-2 px-4 shadow-md shadow-indigo-500/20">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Listing Baru
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="glass-card p-4 sm:p-5">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Penawaran</span>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $totalListings }}</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Semua item terdaftar</p>
            </div>

            <div class="glass-card p-4 sm:p-5">
                <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Aktif di Pasar</span>
                <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ $activeListings }}</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Dapat dibeli pembeli</p>
            </div>

            <div class="glass-card p-4 sm:p-5">
                <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Draf Tersimpan</span>
                <h3 class="text-2xl font-black text-amber-600 mt-1">{{ $draftListings }}</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Belum dipublikasikan</p>
            </div>

            <div class="glass-card p-4 sm:p-5">
                <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Ditutup / Nonaktif</span>
                <h3 class="text-2xl font-black text-rose-600 mt-1">{{ $closedListings }}</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Tidak tampil di pencarian</p>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="glass-panel overflow-hidden border border-white/80 mb-6">
            <div class="p-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white/40 border-b border-slate-200/70">
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('seller.listings.index') }}"
                       class="text-xs px-3 py-1.5 rounded-xl font-semibold transition {{ empty($statusFilter) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/80 text-slate-600 hover:bg-white' }}">
                        Semua ({{ $totalListings }})
                    </a>
                    <a href="{{ route('seller.listings.index', ['status' => 'ACTIVE']) }}"
                       class="text-xs px-3 py-1.5 rounded-xl font-semibold transition {{ $statusFilter === 'ACTIVE' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white/80 text-slate-600 hover:bg-white' }}">
                        Aktif ({{ $activeListings }})
                    </a>
                    <a href="{{ route('seller.listings.index', ['status' => 'DRAFT']) }}"
                       class="text-xs px-3 py-1.5 rounded-xl font-semibold transition {{ $statusFilter === 'DRAFT' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white/80 text-slate-600 hover:bg-white' }}">
                        Draf ({{ $draftListings }})
                    </a>
                    <a href="{{ route('seller.listings.index', ['status' => 'CLOSED']) }}"
                       class="text-xs px-3 py-1.5 rounded-xl font-semibold transition {{ $statusFilter === 'CLOSED' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white/80 text-slate-600 hover:bg-white' }}">
                        Ditutup ({{ $closedListings }})
                    </a>
                </div>

                <!-- Search Input -->
                <form action="{{ route('seller.listings.index') }}" method="GET" class="w-full sm:w-auto">
                    @if ($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="q" value="{{ $search }}" placeholder="Cari judul atau varian..."
                               class="glass-input text-xs py-1.5 pl-8 pr-3 w-full">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </form>
            </div>

            <!-- Listing Table / Cards -->
            @if ($listings->isEmpty())
                <div class="text-center py-16 px-4">
                    <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-3 border border-indigo-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Belum ada penawaran listing</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                        @if ($search || $statusFilter)
                            Tidak ada listing yang cocok dengan filter atau kata kunci pencarian Anda.
                        @else
                            Mulai jualan produk digital Anda dengan membuat listing penawaran pertama sekarang.
                        @endif
                    </p>
                    <div class="mt-6 mb-6">
                        <a href="{{ route('seller.listings.create') }}" class="glass-btn-primary text-xs sm:text-sm py-2.5 px-5 shadow-md shadow-indigo-500/20 inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Buat Listing Pertama
                        </a>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/60 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                                <th class="py-3 px-4">Judul Penawaran</th>
                                <th class="py-3 px-4">Varian & Produk</th>
                                <th class="py-3 px-4">Harga Bersih</th>
                                <th class="py-3 px-4">Estimasi Durasi</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($listings as $listing)
                                <tr class="hover:bg-white/60 transition">
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-slate-900 line-clamp-1">
                                            {{ $listing->title }}
                                        </div>
                                        <p class="text-xs text-slate-500 line-clamp-1 mt-0.5 max-w-sm">
                                            {{ $listing->description }}
                                        </p>
                                    </td>
                                    <td class="py-4 px-4 text-slate-700">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/70 mb-0.5">
                                            {{ $listing->variant->variant_name ?? 'Varian' }}
                                        </span>
                                        <div class="text-[11px] text-slate-500">
                                            {{ $listing->variant->product->product_name ?? '-' }} ({{ $listing->variant->product->category->category_name ?? '-' }})
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-extrabold text-slate-900 text-sm">
                                            Rp {{ number_format($listing->price, 0, ',', '.') }}
                                        </div>
                                        <span class="text-[10px] text-slate-400">Harga seller</span>
                                    </td>
                                    <td class="py-4 px-4 text-slate-600">
                                        <div class="font-medium text-slate-800">
                                            {{ $listing->estimated_delivery_formatted }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 truncate max-w-[150px]" title="{{ $listing->delivery_method }}">
                                            {{ $listing->delivery_method }}
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        @if ($listing->status === 'ACTIVE')
                                            <span class="glass-badge glass-badge-success text-[10px] py-0.5 px-2">
                                                AKTIF
                                            </span>
                                        @elseif ($listing->status === 'DRAFT')
                                            <span class="glass-badge glass-badge-warning text-[10px] py-0.5 px-2">
                                                DRAF
                                            </span>
                                        @else
                                            <span class="glass-badge glass-badge-danger text-[10px] py-0.5 px-2">
                                                DITUTUP
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-right space-x-1 whitespace-nowrap">
                                        <a href="{{ route('seller.listings.edit', $listing->listing_id) }}"
                                           class="glass-btn-secondary text-xs py-1 px-2.5">
                                            Edit
                                        </a>

                                        <form action="{{ route('seller.listings.toggle-status', $listing->listing_id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs py-1 px-2.5 rounded-lg border transition {{ $listing->status === 'ACTIVE' ? 'border-rose-300 text-rose-700 bg-rose-50/60 hover:bg-rose-100' : 'border-emerald-300 text-emerald-700 bg-emerald-50/60 hover:bg-emerald-100' }}">
                                                {{ $listing->status === 'ACTIVE' ? 'Tutup' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100">
                    {{ $listings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
