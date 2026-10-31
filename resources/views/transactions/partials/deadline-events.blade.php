@if ($transaction->deadlineEvents && $transaction->deadlineEvents->isNotEmpty())
    <div class="glass-card p-5 sm:p-6 border-amber-200/80 bg-gradient-to-r from-amber-500/5 via-orange-50/20 to-white/90 shadow-sm space-y-4" data-cy="deadline-events-card">
        <div class="flex items-center justify-between pb-3 border-b border-amber-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>Riwayat Penyesuaian Tenggat Waktu</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                            {{ $transaction->deadlineEvents->count() }} Peristiwa
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        Catatan perpanjangan waktu penyerahan penjual, masa tenggang pembeli, dan penyesuaian batas otomatisasi.
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-amber-50/60 border-b border-amber-100 text-[10px] uppercase font-bold tracking-wider text-amber-900">
                        <th class="py-2.5 px-3">Waktu Pencatatan</th>
                        <th class="py-2.5 px-3">Jenis Penyesuaian</th>
                        <th class="py-2.5 px-3">Tenggat Asal &rarr; Baru</th>
                        <th class="py-2.5 px-3">Tambahan</th>
                        <th class="py-2.5 px-3">Alasan & Pemberi Izin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" data-cy="deadline-events-table-body">
                    @foreach ($transaction->deadlineEvents->sortByDesc('deadline_event_id') as $event)
                        @php
                            $eventLabel = match($event->event_type) {
                                'DELIVERY_EXTENSION' => 'Perpanjangan Penyerahan',
                                'BUYER_GRACE_PERIOD' => 'Masa Tenggang Pembeli',
                                'AUTO_CONFIRMED' => 'Auto-Confirm Sistem',
                                default => str_replace('_', ' ', (string) $event->event_type),
                            };
                            $badgeColor = match($event->event_type) {
                                'DELIVERY_EXTENSION' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                'BUYER_GRACE_PERIOD' => 'bg-amber-50 text-amber-800 border-amber-200',
                                'AUTO_CONFIRMED' => 'bg-purple-50 text-purple-800 border-purple-200',
                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-amber-50/20 transition-colors">
                            <td class="py-2.5 px-3 whitespace-nowrap text-slate-700 font-medium">
                                <div>{{ $event->created_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</div>
                                <div class="text-[10px] text-slate-400">{{ $event->created_at?->diffForHumans() }}</div>
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase tracking-wider {{ $badgeColor }}">
                                    {{ $eventLabel }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap text-slate-800 font-semibold">
                                <span class="text-slate-500 font-normal">{{ $event->previous_deadline?->translatedFormat('d M, H:i') ?? '-' }}</span>
                                @if ($event->new_deadline)
                                    <span class="text-slate-400 mx-1">&rarr;</span>
                                    <span class="text-indigo-700 font-bold">{{ $event->new_deadline->translatedFormat('d M, H:i') }} WIB</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap font-bold text-amber-700">
                                @if ($event->extension_minutes)
                                    +{{ $event->extension_minutes }} Menit
                                @else
                                    <span class="text-slate-400 font-normal">-</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-slate-700 max-w-xs">
                                <p class="text-[11px] leading-relaxed">{{ $event->reason ?: 'Tidak ada alasan khusus.' }}</p>
                                <span class="text-[10px] text-slate-400 font-medium">
                                    Oleh: <strong>{{ $event->grantedBy?->username ?? 'Sistem Otomatis' }}</strong>
                                    @if ($event->is_flagged_for_admin)
                                        <span class="ml-1 text-rose-600 font-bold">(Ditandai untuk Tinjauan Admin)</span>
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
