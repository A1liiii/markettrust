<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Tampilkan pusat notifikasi pengguna (semua, belum dibaca, atau sudah dibaca).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = (string) $request->query('filter', 'all');

        $query = $user->notifications()
            ->with(['transaction.agreement'])
            ->latest('notification_id');

        if ($filter === 'unread') {
            $query->unread();
        } elseif ($filter === 'read') {
            $query->read();
        }

        $notifications = $query->paginate(15)->withQueryString();

        $unreadCount = $user->notifications()->unread()->count();
        $totalCount = $user->notifications()->count();
        $readCount = max(0, $totalCount - $unreadCount);

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'totalCount' => $totalCount,
            'readCount' => $readCount,
            'currentFilter' => $filter,
        ]);
    }

    /**
     * Catat read_at saat notifikasi dipilih, verifikasi izin akses ke tautan rujukan,
     * dan tolak akses jika notifikasi atau rujukan milik pengguna lain.
     */
    public function read(Request $request, Notification $notification): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // 1. Otorisasi kepemilikan notifikasi: tolak jika bukan pemiliknya
        if ((int) $notification->user_id !== (int) $user->user_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka notifikasi ini.');
        }

        // 2. Catat read_at jika belum dibaca
        $notification->markAsRead();

        // Jika request menginginkan JSON (misal dari panggilan fetch/AJAX)
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'notification_id' => $notification->notification_id,
                'read_at' => $notification->read_at?->toIso8601String(),
            ]);
        }

        // 3. Verifikasi tautan rujukan (jika ada transaction_id)
        if ($notification->transaction_id) {
            $transaction = $notification->transaction;

            if (! $transaction) {
                return redirect()->route('notifications.index')
                    ->with('warning', 'Transaksi rujukan notifikasi ini sudah tidak tersedia.');
            }

            $agreement = $transaction->agreement;
            $isParty = $agreement && (
                (int) $user->user_id === (int) $agreement->buyer_id ||
                (int) $user->user_id === (int) $agreement->seller_id
            );
            $isAdmin = $user->isAdmin();

            // Tolak jika pengguna bukan buyer, seller, maupun admin pada transaksi tersebut
            if (! $isParty && ! $isAdmin) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses rincian transaksi rujukan ini.');
            }

            return redirect()->route('transactions.show', $transaction->transaction_id);
        }

        return redirect()->route('notifications.index')
            ->with('info', 'Notifikasi telah ditandai sebagai dibaca.');
    }

    /**
     * Tandai seluruh notifikasi belum dibaca milik pengguna aktif sebagai sudah dibaca.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        $updated = $user->notifications()->unread()->update(['read_at' => now()]);

        $message = $updated > 0
            ? "Sebanyak {$updated} notifikasi berhasil ditandai sebagai dibaca."
            : 'Tidak ada notifikasi baru yang perlu ditandai.';

        return redirect()->back()->with('success', $message);
    }
}
