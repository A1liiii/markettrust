<?php

namespace App\Http\Controllers\Seller;

use App\Enums\PayoutDestinationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreSellerPayoutAccountRequest;
use App\Http\Requests\Seller\UpdateSellerPayoutAccountRequest;
use App\Models\SellerPayoutAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SellerPayoutAccountController extends Controller
{
    /**
     * Daftar bank rekanan umum di Indonesia.
     *
     * @var list<string>
     */
    protected array $supportedBanks = [
        'BCA',
        'Mandiri',
        'BNI',
        'BRI',
        'CIMB Niaga',
        'Permata Bank',
        'Bank Syariah Indonesia (BSI)',
        'Bank Danamon',
        'Bank Tabungan Negara (BTN)',
        'Bank Jago',
        'SeaBank',
    ];

    /**
     * Daftar e-wallet populer di Indonesia.
     *
     * @var list<string>
     */
    protected array $supportedEwallets = [
        'GoPay',
        'OVO',
        'DANA',
        'ShopeePay',
        'LinkAja',
    ];

    /**
     * Tampilkan daftar tujuan rekening payout seller.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SellerPayoutAccount::class);

        $user = $request->user();
        $accounts = $user->payoutAccounts()
            ->orderByDesc('is_active')
            ->latest('seller_payout_account_id')
            ->get();

        return view('seller.payout_accounts.index', [
            'user' => $user,
            'accounts' => $accounts,
            'activeAccount' => $accounts->firstWhere('is_active', true),
        ]);
    }

    /**
     * Tampilkan formulir penambahan rekening payout baru.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', SellerPayoutAccount::class);

        return view('seller.payout_accounts.create', [
            'supportedBanks' => $this->supportedBanks,
            'supportedEwallets' => $this->supportedEwallets,
            'destinationTypes' => PayoutDestinationType::cases(),
        ]);
    }

    /**
     * Simpan rekening payout baru secara atomik.
     */
    public function store(StoreSellerPayoutAccountRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Jika seller belum punya rekening, rekening pertama otomatis dijadikan aktif
        $hasExistingAccounts = $user->payoutAccounts()->exists();
        $makeActive = ! $hasExistingAccounts || $request->boolean('is_active');

        DB::transaction(function () use ($request, $user, $makeActive) {
            if ($makeActive) {
                // Nonaktifkan rekening aktif lain milik seller ini terlebih dahulu
                SellerPayoutAccount::where('seller_id', $user->user_id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $qrisUrl = null;
            if ($request->hasFile('qris_image')) {
                $path = $request->file('qris_image')->store('payout_qris', 'public');
                $qrisUrl = Storage::url($path);
            }

            $user->payoutAccounts()->create([
                'destination_type' => $request->input('destination_type'),
                'destination_provider' => $request->input('destination_provider'),
                'destination_number' => $request->input('destination_number'),
                'destination_holder' => $request->input('destination_holder'),
                'destination_qris_image_url' => $qrisUrl,
                'is_active' => $makeActive,
            ]);
        });

        return redirect()->route('seller.payout-accounts.index')
            ->with('success', 'Rekening payout berhasil ditambahkan!');
    }

    /**
     * Tampilkan formulir edit rekening payout.
     */
    public function edit(SellerPayoutAccount $payoutAccount): View
    {
        Gate::authorize('update', $payoutAccount);

        return view('seller.payout_accounts.edit', [
            'account' => $payoutAccount,
            'supportedBanks' => $this->supportedBanks,
            'supportedEwallets' => $this->supportedEwallets,
            'destinationTypes' => PayoutDestinationType::cases(),
        ]);
    }

    /**
     * Perbarui data rekening payout seller secara atomik.
     */
    public function update(UpdateSellerPayoutAccountRequest $request, SellerPayoutAccount $payoutAccount): RedirectResponse
    {
        Gate::authorize('update', $payoutAccount);

        $makeActive = $request->boolean('is_active') || $payoutAccount->is_active;

        DB::transaction(function () use ($request, $payoutAccount, $makeActive) {
            if ($makeActive && ! $payoutAccount->is_active) {
                SellerPayoutAccount::where('seller_id', $payoutAccount->seller_id)
                    ->where('seller_payout_account_id', '!=', $payoutAccount->seller_payout_account_id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $qrisUrl = $payoutAccount->destination_qris_image_url;

            if ($request->hasFile('qris_image')) {
                // Hapus gambar QRIS lama jika ada di storage lokal
                $this->deleteOldQrisFile($payoutAccount->destination_qris_image_url);

                $path = $request->file('qris_image')->store('payout_qris', 'public');
                $qrisUrl = Storage::url($path);
            } elseif ($request->input('destination_type') !== PayoutDestinationType::QRIS->value) {
                // Jika tipe diubah menjadi BANK atau EWALLET, bersihkan gambar QRIS lama
                $this->deleteOldQrisFile($payoutAccount->destination_qris_image_url);
                $qrisUrl = null;
            }

            $payoutAccount->update([
                'destination_type' => $request->input('destination_type'),
                'destination_provider' => $request->input('destination_provider'),
                'destination_number' => $request->input('destination_number'),
                'destination_holder' => $request->input('destination_holder'),
                'destination_qris_image_url' => $qrisUrl,
                'is_active' => $makeActive,
            ]);
        });

        return redirect()->route('seller.payout-accounts.index')
            ->with('success', 'Rekening payout berhasil diperbarui!');
    }

    /**
     * Jadikan rekening payout ini sebagai rekening utama yang aktif secara atomik.
     */
    public function activate(Request $request, SellerPayoutAccount $payoutAccount): RedirectResponse
    {
        Gate::authorize('activate', $payoutAccount);

        DB::transaction(function () use ($payoutAccount) {
            SellerPayoutAccount::where('seller_id', $payoutAccount->seller_id)
                ->where('seller_payout_account_id', '!=', $payoutAccount->seller_payout_account_id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $payoutAccount->update(['is_active' => true]);
        });

        return redirect()->route('seller.payout-accounts.index')
            ->with('success', 'Rekening payout berhasil diaktifkan sebagai tujuan utama.');
    }

    /**
     * Hapus rekening payout seller.
     */
    public function destroy(Request $request, SellerPayoutAccount $payoutAccount): RedirectResponse
    {
        Gate::authorize('delete', $payoutAccount);

        $wasActive = $payoutAccount->is_active;
        $sellerId = $payoutAccount->seller_id;

        DB::transaction(function () use ($payoutAccount, $wasActive, $sellerId) {
            $this->deleteOldQrisFile($payoutAccount->destination_qris_image_url);
            $payoutAccount->delete();

            // Jika yang dihapus rekening aktif, aktifkan rekening lain yang tersisa (jika ada)
            if ($wasActive) {
                $nextAccount = SellerPayoutAccount::where('seller_id', $sellerId)
                    ->latest('seller_payout_account_id')
                    ->first();

                if ($nextAccount) {
                    $nextAccount->update(['is_active' => true]);
                }
            }
        });

        return redirect()->route('seller.payout-accounts.index')
            ->with('success', 'Rekening payout berhasil dihapus.');
    }

    /**
     * Hapus file gambar QRIS lama dari storage publik.
     */
    protected function deleteOldQrisFile(?string $url): void
    {
        if (empty($url) || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return;
        }

        $relativePath = str_replace('/storage/', '', $url);
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
