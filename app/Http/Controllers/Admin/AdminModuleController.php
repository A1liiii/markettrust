<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminModuleController extends Controller
{
    /**
     * Tampilkan halaman placeholder modul Transaksi & Escrow.
     */
    public function transactions(): View
    {
        return view('admin.placeholder', [
            'moduleKey' => 'transactions',
            'title' => 'Pengawasan Transaksi & Escrow',
            'description' => 'Monitoring status escrow, persetujuan pembatalan transaksi, dan pencatatan kewajiban KEEP seller.',
            'icon' => 'banknotes',
        ]);
    }

    /**
     * Tampilkan halaman placeholder modul Sengketa & Komplain.
     */
    public function disputes(): View
    {
        return view('admin.placeholder', [
            'moduleKey' => 'disputes',
            'title' => 'Penyelesaian Sengketa & Komplain',
            'description' => 'Investigasi laporan sengketa pembeli-penjual, peninjauan berkas bukti digital, dan keputusan resolusi komplain.',
            'icon' => 'scale',
        ]);
    }

    /**
     * Tampilkan halaman placeholder modul Pengguna & Akun.
     */
    public function users(): View
    {
        return view('admin.placeholder', [
            'moduleKey' => 'users',
            'title' => 'Manajemen Pengguna & Reputasi',
            'description' => 'Kelola direktori pengguna, tinjauan reputasi (LEGIT / CAUTION), dan penangguhan akun bermasalah.',
            'icon' => 'users',
        ]);
    }

    /**
     * Tampilkan halaman placeholder modul Audit Log.
     */
    public function auditLogs(): View
    {
        return view('admin.placeholder', [
            'moduleKey' => 'audit_logs',
            'title' => 'Audit Log & Jejak Aktivitas',
            'description' => 'Rekam jejak seluruh aktivitas administratif, perubahan status finansial, dan audit sistem transaksi.',
            'icon' => 'clipboard-document-list',
        ]);
    }
}
