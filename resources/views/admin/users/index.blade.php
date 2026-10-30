@extends('layouts.admin')

@section('title', 'Manajemen Pengguna & Moderasi Akun - Panel Administrasi')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                    <span>Panel Administrasi</span>
                    <span>&bull;</span>
                    <span>Modul Tahap Lanjutan</span>
                    <span>&bull;</span>
                    <span>Pengawasan & Moderasi (UC-24 &bull; UC-30 &bull; KF-11)</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight" data-cy="admin-users-title">
                    Manajemen Pengguna & Moderasi Akun
                </h1>
                <p class="text-sm text-slate-600 mt-1 max-w-2xl">
                    Antrean pemeriksaan laporan akun pengguna, peninjauan berkas bukti digital, riwayat pembatalan/kewajiban KEEP, serta penetapan keputusan sanksi reputasi dan penangguhan akun.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-3.5">
                    &larr; Dashboard
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-xs">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1">
                @foreach ($errors->all() as $error)
                    <div>&bull; {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <!-- Statistik Metrik Cepat -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="glass-card p-4 sm:p-5 border border-white/80 shadow-xs flex items-center justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Pengguna</span>
                    <span class="text-2xl font-black text-slate-900 mt-0.5 block">{{ number_format($stats['total_users']) }}</span>
                    <span class="text-[10px] text-slate-500 font-medium">Pengguna terdaftar platform</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                    👥
                </div>
            </div>

            <div class="glass-card p-4 sm:p-5 border border-white/80 shadow-xs flex items-center justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Akun Aktif</span>
                    <span class="text-2xl font-black text-emerald-600 mt-0.5 block">{{ number_format($stats['active_users']) }}</span>
                    <span class="text-[10px] text-emerald-700 font-medium">Status operasional normal</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    ✅
                </div>
            </div>

            <div class="glass-card p-4 sm:p-5 border border-white/80 shadow-xs flex items-center justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Ditangguhkan</span>
                    <span class="text-2xl font-black text-rose-600 mt-0.5 block">{{ number_format($stats['suspended_users']) }}</span>
                    <span class="text-[10px] text-rose-700 font-medium">Akun berstatus SUSPENDED</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                    ⛔
                </div>
            </div>

            <div class="glass-card p-4 sm:p-5 border border-white/80 shadow-xs flex items-center justify-between">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Laporan Menunggu</span>
                    <span class="text-2xl font-black text-amber-600 mt-0.5 block">{{ number_format($stats['pending_reports']) }}</span>
                    <span class="text-[10px] text-amber-700 font-medium">Perlu tindakan moderasi</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                    ⚠️
                </div>
            </div>
        </div>

        <!-- TABEL ANTREAN LAPORAN AKUN -->
        <div class="glass-card p-6 border border-white/80 shadow-md space-y-6">
            <!-- Filter & Search Toolbar -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <!-- Filter Tabs -->
                <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-1.5 rounded-lg transition {{ empty($currentStatus) ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                        Semua Laporan
                    </a>
                    <a href="{{ route('admin.users.index', ['status' => 'OPEN']) }}" class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ $currentStatus === 'OPEN' ? 'bg-amber-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                        <span>Menunggu</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'OPEN' ? 'bg-amber-700 text-white' : 'bg-amber-100 text-amber-800' }}">{{ $stats['open_reports'] }}</span>
                    </a>
                    <a href="{{ route('admin.users.index', ['status' => 'REVIEWING']) }}" class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ $currentStatus === 'REVIEWING' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                        <span>Investigasi</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'REVIEWING' ? 'bg-blue-700 text-white' : 'bg-blue-100 text-blue-800' }}">{{ $stats['reviewing_reports'] }}</span>
                    </a>
                    <a href="{{ route('admin.users.index', ['status' => 'RESOLVED']) }}" class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ $currentStatus === 'RESOLVED' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                        <span>Diputuskan</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'RESOLVED' ? 'bg-emerald-700 text-white' : 'bg-emerald-100 text-emerald-800' }}">{{ $stats['resolved_reports'] }}</span>
                    </a>
                    <a href="{{ route('admin.users.index', ['status' => 'DISMISSED']) }}" class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ $currentStatus === 'DISMISSED' ? 'bg-slate-700 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                        <span>Ditolak</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'DISMISSED' ? 'bg-slate-800 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $stats['dismissed_reports'] }}</span>
                    </a>
                </div>

                <!-- Search Input Form -->
                <form action="{{ route('admin.users.index') }}" method="GET" class="flex items-center gap-2">
                    @if ($currentStatus)
                        <input type="hidden" name="status" value="{{ $currentStatus }}">
                    @endif
                    <div class="relative">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Cari pengguna / alasan..."
                            class="text-xs rounded-xl border border-slate-200 pl-8 pr-3 py-1.5 text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-56 sm:w-64"
                        >
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <button type="submit" class="glass-btn-secondary text-xs py-1.5 px-3">
                        Cari
                    </button>
                    @if ($search)
                        <a href="{{ route('admin.users.index', array_filter(['status' => $currentStatus])) }}" class="text-xs text-slate-400 hover:text-slate-600">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <!-- Tabel Data Laporan -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700" data-cy="table-account-reports">
                    <thead class="text-[10px] uppercase font-bold text-slate-400 bg-slate-50/80 border-y border-slate-100">
                        <tr>
                            <th class="py-3 px-3">ID Laporan</th>
                            <th class="py-3 px-3">Pelapor</th>
                            <th class="py-3 px-3">Terlapor & Status Akun</th>
                            <th class="py-3 px-3">Transaksi / Sumber</th>
                            <th class="py-3 px-3">Ringkasan Alasan</th>
                            <th class="py-3 px-3">Bukti</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Tanggal Masuk</th>
                            <th class="py-3 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($reports as $report)
                            <tr class="hover:bg-slate-50/60 transition" data-cy="report-row-{{ $report->report_id }}">
                                <td class="py-3 px-3 font-mono font-bold text-indigo-700">
                                    #MT-REP-{{ str_pad($report->report_id, 4, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="py-3 px-3">
                                    @if ($report->reporter)
                                        <div class="font-bold text-slate-900">{{ '@' . $report->reporter->username }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $report->reporter->email }}</div>
                                    @else
                                        <span class="text-slate-400 italic">Sistem Platform</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                        <span>{{ '@' . $report->reportedUser?->username }}</span>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-black {{ $report->reportedUser?->account_status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ $report->reportedUser?->account_status }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        Reputasi: <strong class="{{ $report->reportedUser?->reputation_status === 'LEGIT' ? 'text-emerald-700' : ($report->reportedUser?->reputation_status === 'PROBLEMATIC' ? 'text-rose-700' : 'text-slate-500') }}">{{ $report->reportedUser?->reputation_status }}</strong> ({{ $report->reportedUser?->reputation_points }} Poin)
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    @if ($report->transaction_id)
                                        <a href="{{ route('transactions.show', $report->transaction_id) }}" target="_blank" class="text-indigo-600 hover:underline font-semibold flex items-center gap-1">
                                            <span>#TX-{{ $report->transaction_id }}</span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                        <span class="block text-[10px] text-slate-400">Sumber: {{ $report->source }}</span>
                                    @else
                                        <span class="text-slate-600 font-medium">Profil Langsung</span>
                                        <span class="block text-[10px] text-slate-400">Sumber: {{ $report->source }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 max-w-xs">
                                    <p class="truncate text-slate-700" title="{{ $report->reason }}">
                                        {{ $report->reason }}
                                    </p>
                                    @if ($report->review_note)
                                        <p class="text-[10px] text-indigo-700 mt-0.5 italic truncate" title="Catatan Admin: {{ $report->review_note }}">
                                            Admin: {{ $report->review_note }}
                                        </p>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    @if ($report->evidenceFiles->isNotEmpty())
                                        <div class="flex items-center gap-1">
                                            @foreach ($report->evidenceFiles as $evidence)
                                                <a
                                                    href="{{ route('reports.accounts.evidence', ['report' => $report, 'evidence' => $evidence, 'inline' => true]) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded bg-slate-100 hover:bg-indigo-50 text-[10px] font-semibold text-indigo-700 transition"
                                                    title="{{ $evidence->file_name }}"
                                                >
                                                    📎 Bukti
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-[10px] italic">Tanpa berkas</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $report->statusBadgeClass() }}" data-cy="badge-report-status">
                                        {{ $report->statusLabel() }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ $report->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <button
                                        type="button"
                                        onclick="openAdminCaseModal({{ $report->report_id }})"
                                        class="glass-btn-primary text-xs py-1.5 px-3 font-bold"
                                        data-cy="btn-review-case-{{ $report->report_id }}"
                                    >
                                        Tinjau Kasus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl">
                                        🛡️
                                    </div>
                                    <div class="font-bold text-slate-700">Tidak ada laporan akun</div>
                                    <div class="text-xs text-slate-400 mt-0.5">Seluruh laporan pengguna telah selesai ditangani atau belum ada laporan baru.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($reports->hasPages())
                <div class="pt-4 border-t border-slate-100">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- CASE INVESTIGATION MODAL (UC-24) -->
<div id="admin-case-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="case-modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeAdminCaseModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white/95 backdrop-blur-xl border border-white/80 p-6 sm:p-8 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl space-y-6">

            <!-- Modal Header -->
            <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                <div>
                    <span class="block text-[10px] uppercase font-bold text-indigo-600 tracking-wider">Investigasi & Keputusan Moderasi</span>
                    <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 mt-0.5" id="case-modal-title">
                        Pemeriksaan Kasus Akun
                    </h3>
                </div>
                <button type="button" onclick="closeAdminCaseModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg transition" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Dynamic Content Area Filled By JS -->
            <div id="case-modal-content" class="space-y-6 text-xs text-slate-700">
                <div class="py-12 text-center text-slate-400 font-semibold animate-pulse">
                    Memuat berkas perkara dan rekam jejak pengguna...
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    async function openAdminCaseModal(reportId) {
        const modal = document.getElementById('admin-case-modal');
        const content = document.getElementById('case-modal-content');

        if (!modal || !content) return;

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        content.innerHTML = '<div class="py-12 text-center text-slate-400 font-semibold animate-pulse">Memuat berkas perkara dan rekam jejak pengguna...</div>';

        try {
            const response = await fetch(`/admin/users/reports/${reportId}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Gagal memuat rincian laporan.');
            }

            const data = await response.json();
            renderCaseModal(data.report, data.context);
        } catch (err) {
            content.innerHTML = `<div class="p-4 rounded-xl bg-rose-50 text-rose-800 border border-rose-200">Gagal mengambil data laporan: ${err.message}</div>`;
        }
    }

    function closeAdminCaseModal() {
        const modal = document.getElementById('admin-case-modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function renderCaseModal(report, context) {
        const content = document.getElementById('case-modal-content');
        if (!content) return;

        const csrfToken = '{{ csrf_token() }}';
        const user = context.user;
        const cancellations = context.cancellations || [];
        const keepObligations = context.keep_obligations || [];
        const reputationEvents = context.reputation_events || [];

        let evidenceHtml = '<span class="text-slate-400 italic">Tidak ada berkas bukti pendukung.</span>';
        if (report.evidence_files && report.evidence_files.length > 0) {
            evidenceHtml = report.evidence_files.map(ev => `
                <a href="/reports/accounts/${report.report_id}/evidence/${ev.evidence_id}?inline=true" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 text-xs font-bold transition">
                    📎 ${ev.file_name} (${(ev.mime_type || 'dokumen')})
                </a>
            `).join(' ');
        }

        let cancellationsHtml = '<div class="text-slate-400 text-xs italic">Tidak ada riwayat pembatalan transaksi.</div>';
        if (cancellations.length > 0) {
            cancellationsHtml = `
                <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden bg-white/60">
                    ${cancellations.map(c => `
                        <div class="p-2.5 flex items-center justify-between text-[11px]">
                            <div>
                                <span class="font-bold text-slate-800">#TX-${c.transaction_id}</span>
                                <span class="text-slate-400">&bull;</span>
                                <span class="text-slate-600">Tahap: ${c.stage}</span>
                                <span class="text-slate-400">&bull;</span>
                                <span class="text-slate-500">Pihak: <strong>${c.responsible_party || 'BELUM DITENTUKAN'}</strong></span>
                            </div>
                            <span class="px-2 py-0.5 rounded font-bold ${c.status === 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">
                                ${c.status}
                            </span>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        let keepHtml = '<div class="text-slate-400 text-xs italic">Tidak ada catatan kewajiban KEEP.</div>';
        if (keepObligations.length > 0) {
            keepHtml = `
                <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden bg-white/60">
                    ${keepObligations.map(k => `
                        <div class="p-2.5 flex items-center justify-between text-[11px]">
                            <div>
                                <span class="font-bold text-slate-800">KEEP #${k.keep_obligation_id}</span>
                                <span class="text-slate-400">&bull;</span>
                                <span class="text-slate-600">Total: Rp ${new Intl.NumberFormat('id-ID').format(k.amount)}</span>
                                <span class="text-slate-400">&bull;</span>
                                <span class="text-slate-500">Sisa: <strong class="text-rose-600">Rp ${new Intl.NumberFormat('id-ID').format(k.remaining_amount)}</strong></span>
                            </div>
                            <span class="px-2 py-0.5 rounded font-bold ${k.status === 'SETTLED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}">
                                ${k.status}
                            </span>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        let reputationHtml = '<div class="text-slate-400 text-xs italic">Belum ada peristiwa reputasi.</div>';
        if (reputationEvents.length > 0) {
            reputationHtml = `
                <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden bg-white/60">
                    ${reputationEvents.map(re => `
                        <div class="p-2.5 flex items-center justify-between text-[11px]">
                            <div>
                                <span class="font-bold text-slate-800">${re.event_type}</span>
                                <span class="text-slate-400">&bull;</span>
                                <span class="text-slate-600">${re.reason}</span>
                            </div>
                            <div class="font-bold ${re.points_delta > 0 ? 'text-emerald-600' : (re.points_delta < 0 ? 'text-rose-600' : 'text-slate-500')}">
                                ${re.points_delta > 0 ? '+' : ''}${re.points_delta} Poin
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        content.innerHTML = `
            <!-- Ringkasan Perkara -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Pihak Pelapor</span>
                    <div class="font-bold text-sm text-slate-900">${report.reporter ? '@' + report.reporter.username : 'Sistem Platform'}</div>
                    <div class="text-xs text-slate-500">${report.reporter ? report.reporter.email : '-'}</div>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Pihak Terlapor</span>
                    <div class="font-bold text-sm text-slate-900 flex items-center gap-1.5">
                        <span>@${user.username}</span>
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-black ${user.account_status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">${user.account_status}</span>
                    </div>
                    <div class="text-xs text-slate-500">${user.email} &bull; Reputasi: <strong>${user.reputation_status || 'UNREVIEWED'}</strong> (${user.reputation_points || 0} Poin)</div>
                </div>
            </div>

            <!-- Isi Laporan & Bukti -->
            <div class="p-4 rounded-xl bg-white border border-slate-200 space-y-2">
                <span class="block text-[10px] uppercase font-bold text-slate-400">Keterangan Alasan Pelaporan</span>
                <div class="text-xs sm:text-sm text-slate-800 whitespace-pre-line leading-relaxed">${report.reason}</div>
                <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-bold text-slate-500">Berkas Bukti:</span>
                    ${evidenceHtml}
                </div>
            </div>

            <!-- Rekam Jejak Terlapor (Cancellations, KEEP, Reputation) -->
            <div class="space-y-3">
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-900">Rekam Jejak Terlapor (KF-11 &bull; KF-10)</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Riwayat Pembatalan</span>
                        ${cancellationsHtml}
                    </div>
                    <div class="space-y-1">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Kewajiban KEEP</span>
                        ${keepHtml}
                    </div>
                </div>
                <div class="space-y-1 pt-2">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Riwayat Peristiwa Reputasi</span>
                    ${reputationHtml}
                </div>
            </div>

            <!-- Tindakan Keputusan Admin -->
            <div class="pt-4 border-t border-slate-200 space-y-4">
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-900">Ambil Keputusan Moderasi</span>

                <!-- Mode 1: Reviewing (Meminta Bukti Tambahan) -->
                <form action="/admin/users/reports/${report.report_id}/review" method="POST" class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-200/80 space-y-2">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <div class="flex items-center justify-between">
                        <strong class="text-xs text-blue-900">1. Minta Bukti Tambahan / Investigasi (REVIEWING)</strong>
                        <button type="submit" class="px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition" data-cy="btn-action-reviewing">
                            Set Reviewing
                        </button>
                    </div>
                    <input type="text" name="review_note" required placeholder="Catatan permintaan bukti tambahan atau aspek yang perlu diperiksa..." class="w-full text-xs rounded-lg border border-blue-200 px-3 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </form>

                <!-- Mode 2: Dismissed (Tidak Terbukti / Tanpa Sanksi) -->
                <form action="/admin/users/reports/${report.report_id}/dismiss" method="POST" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <div class="flex items-center justify-between">
                        <strong class="text-xs text-slate-800">2. Tolak Laporan (Tidak Terbukti / DISMISSED Tanpa Sanksi)</strong>
                        <button type="submit" class="px-3 py-1 rounded-lg bg-slate-700 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition" data-cy="btn-action-dismiss">
                            Tutup (Dismiss)
                        </button>
                    </div>
                    <input type="text" name="review_note" required placeholder="Alasan penolakan laporan (bukti tidak cukup / transaksi sah)..." class="w-full text-xs rounded-lg border border-slate-200 px-3 py-1.5 focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                </form>

                <!-- Mode 3: Resolved (Terbukti / Sanksi Reputasi / Penangguhan) -->
                <form action="/admin/users/reports/${report.report_id}/resolve" method="POST" class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-200/80 space-y-3">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <strong class="block text-xs text-emerald-950 font-bold">3. Putuskan Moderasi (RESOLVED dengan Sanksi Idempoten)</strong>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Status Akun</label>
                            <select name="account_status" class="w-full text-xs rounded-lg border border-emerald-200 px-2 py-1.5 bg-white text-slate-800">
                                <option value="ACTIVE" ${user.account_status === 'ACTIVE' ? 'selected' : ''}>Tetap ACTIVE</option>
                                <option value="SUSPENDED" ${user.account_status === 'SUSPENDED' ? 'selected' : ''}>SUSPEND Akun</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Status Reputasi</label>
                            <select name="new_reputation_status" class="w-full text-xs rounded-lg border border-emerald-200 px-2 py-1.5 bg-white text-slate-800">
                                <option value="">Tidak Mengubah Status</option>
                                <option value="PROBLEMATIC">Tandai PROBLEMATIC</option>
                                <option value="LEGIT">Tandai LEGIT</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Penalti / Delta Poin</label>
                            <input type="number" name="points_delta" value="0" step="1" class="w-full text-xs rounded-lg border border-emerald-200 px-2 py-1.5 bg-white text-slate-800" placeholder="Contoh: -10">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Catatan Keputusan Resmi <span class="text-rose-500">*</span></label>
                        <input type="text" name="review_note" required placeholder="Jelaskan dasar keputusan moderasi, pertimbangan bukti, dan sanksi yang ditetapkan..." class="w-full text-xs rounded-lg border border-emerald-200 px-3 py-1.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 bg-white">
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold text-xs shadow-md shadow-emerald-500/20 active:scale-95 transition" data-cy="btn-action-resolve">
                            Terapkan Keputusan Moderasi
                        </button>
                    </div>
                </form>
            </div>
        `;
    }
</script>
@endsection
