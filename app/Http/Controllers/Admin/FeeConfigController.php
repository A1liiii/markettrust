<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFeeConfigRequest;
use App\Models\FeeConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FeeConfigController extends Controller
{
    /**
     * Tampilkan konfigurasi fee aktif dan daftar riwayat konfigurasi.
     */
    public function index(): View
    {
        $activeConfig = FeeConfig::query()->where('is_active', true)->first();
        $configs = FeeConfig::query()
            ->orderByDesc('effective_from')
            ->get();

        return view('admin.fee-configs.index', compact('activeConfig', 'configs'));
    }

    /**
     * Tetapkan konfigurasi fee baru tanpa menimpa konfigurasi lama.
     */
    public function store(StoreFeeConfigRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            // Arsipkan/nonaktifkan konfigurasi yang sebelumnya aktif
            FeeConfig::query()
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Buat baris baru sebagai konfigurasi aktif saat ini
            FeeConfig::create([
                'min_amount' => $request->validated('min_amount'),
                'max_amount' => $request->validated('max_amount'),
                'fee_percent' => $request->validated('fee_percent'),
                'flat_fee' => $request->validated('flat_fee'),
                'is_active' => true,
                'effective_from' => now(),
            ]);
        });

        return redirect()->route('admin.fee-configs.index')
            ->with('success', 'Konfigurasi fee baru berhasil ditetapkan dan diaktifkan!');
    }
}
