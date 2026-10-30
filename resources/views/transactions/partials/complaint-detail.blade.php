@if ($transaction->complaint)
    @php
        $complaint = $transaction->complaint;
        $statusUpper = strtoupper((string) $complaint->status);
        $statusBadge = match($statusUpper) {
            'OPEN' => 'bg-amber-100 text-amber-900 border-amber-300',
            'REVIEWING' => 'bg-blue-100 text-blue-900 border-blue-300',
            'RESOLVED' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            'REJECTED' => 'bg-rose-100 text-rose-900 border-rose-300',
            default => 'bg-slate-100 text-slate-800 border-slate-300',
        };
        $statusLabel = match($statusUpper) {
            'OPEN' => 'Menunggu Tanggapan Penjual',
            'REVIEWING' => 'Sedang Ditinjau Admin',
            'RESOLVED' => 'Selesai / Diputuskan',
            'REJECTED' => 'Komplain Ditolak',
            default => $statusUpper,
        };
    @endphp

    <div class="glass-card p-5 sm:p-6 border-rose-200/80 bg-gradient-to-r from-rose-500/5 via-amber-50/20 to-white/90 shadow-sm space-y-4" data-cy="complaint-detail-card">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-rose-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>Informasi Komplain &amp; Mediasi Sengketa</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border uppercase tracking-wide {{ $statusBadge }}" data-cy="complaint-status-badge">
                            {{ $statusLabel }}
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        Diajukan oleh @<span>{{ $complaint->raisedBy?->username ?? 'Pembeli' }}</span> pada {{ $complaint->created_at?->translatedFormat('d F Y, H:i') }} WIB
                    </p>
                </div>
            </div>

            @if ($statusUpper === 'RESOLVED' && $complaint->decision)
                <div class="text-left sm:text-right shrink-0 p-2.5 rounded-xl {{ strtoupper((string) $complaint->decision) === 'PAYOUT' ? 'bg-emerald-50 border border-emerald-200 text-emerald-900' : 'bg-sky-50 border border-sky-200 text-sky-900' }}">
                    <span class="block text-[10px] uppercase font-bold tracking-wider">Keputusan Resolusi Admin</span>
                    <span class="text-xs sm:text-sm font-black">
                        {{ strtoupper((string) $complaint->decision) === 'PAYOUT' ? 'Pelepasan Dana (PAYOUT)' : 'Pengembalian Dana (REFUND)' }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Alasan Komplain Pembeli -->
        <div class="bg-white/80 p-4 rounded-xl border border-slate-200/80 space-y-2">
            <span class="block text-[10px] uppercase font-bold text-slate-400">
                Alasan & Rincian Masalah dari Pembeli:
            </span>
            <div class="text-xs sm:text-sm text-slate-800 whitespace-pre-line leading-relaxed" data-cy="complaint-reason-text">
                {{ $complaint->reason }}
            </div>

            <!-- Berkas Bukti Komplain -->
            @if ($complaint->evidenceFiles && $complaint->evidenceFiles->isNotEmpty())
                <div class="pt-2 border-t border-slate-100">
                    <span class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Berkas Bukti Pembeli:</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($complaint->evidenceFiles as $evidence)
                            <a
                                href="{{ route('transactions.delivery-evidence', [$transaction, $evidence, 'inline' => 1]) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium text-xs transition"
                            >
                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span class="truncate max-w-[200px]">{{ $evidence->file_name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Tanggapan Penjual -->
        @if ($complaint->responses && $complaint->responses->isNotEmpty())
            <div class="space-y-3 pt-2">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Tanggapan & Bukti Klarifikasi Penjual
                </span>
                @foreach ($complaint->responses as $response)
                    <div class="bg-indigo-50/40 p-4 rounded-xl border border-indigo-100 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-indigo-950">
                                @<span>{{ $response->responder?->username ?? 'Penjual' }}</span>
                            </span>
                            <span class="text-[10px] text-slate-400">
                                {{ $response->created_at?->translatedFormat('d M Y, H:i') }} WIB
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-800 whitespace-pre-line leading-relaxed">
                            {{ $response->response_text }}
                        </p>

                        @if ($response->evidenceFiles && $response->evidenceFiles->isNotEmpty())
                            <div class="pt-2 border-t border-indigo-100/60 flex flex-wrap gap-2">
                                @foreach ($response->evidenceFiles as $respEv)
                                    <a
                                        href="{{ route('transactions.delivery-evidence', [$transaction, $respEv, 'inline' => 1]) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-indigo-200 text-indigo-700 font-medium text-xs hover:bg-indigo-50 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span class="truncate max-w-[200px]">{{ $respEv->file_name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Keputusan Resolusi Admin -->
        @if ($statusUpper === 'RESOLVED')
            <div class="p-4 rounded-xl bg-gradient-to-r from-emerald-500/10 via-emerald-50 to-white/90 border border-emerald-200 space-y-2" data-cy="complaint-resolution-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-950 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Putusan Mediasi Administrator: <strong>{{ strtoupper((string) $complaint->decision) }}</strong></span>
                    </span>
                    <span class="text-[10px] text-slate-500">
                        Diputuskan pada {{ $complaint->decided_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
                    </span>
                </div>
                <div class="text-xs text-slate-700 leading-relaxed">
                    <span class="font-semibold text-slate-900">Catatan Pertimbangan:</span>
                    {{ $complaint->decision_reason ?: 'Sengketa telah diselesaikan berdasarkan peninjauan bukti digital kedua belah pihak.' }}
                </div>
                <div class="text-[10px] text-slate-400">
                    Peninjau: <strong>@<span>{{ $complaint->decidedBy?->username ?? 'Administrator' }}</span></strong>
                </div>
            </div>
        @endif
    </div>
@endif
