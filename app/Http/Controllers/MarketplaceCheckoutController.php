<?php

namespace App\Http\Controllers;

use App\Contracts\Services\FeeCalculationServiceInterface;
use App\Contracts\Services\MarketplaceServiceInterface;
use App\Http\Requests\Marketplace\CheckoutConfirmRequest;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;

class MarketplaceCheckoutController extends Controller
{
    public function __construct(
        protected MarketplaceServiceInterface $marketplaceService,
        protected FeeCalculationServiceInterface $feeCalculationService,
    ) {}

    /**
     * Tampilkan halaman tinjauan rincian kesepakatan checkout Marketplace.
     */
    public function review(Request $request, Listing $listing): View|RedirectResponse
    {
        $user = $request->user();

        // 1. Larangan membeli listing sendiri
        if ($user->user_id === $listing->seller_id) {
            return redirect()->route('marketplace.show', $listing)
                ->with('warning', 'Anda tidak dapat membeli item pada listing milik Anda sendiri.');
        }

        // 2. Listing harus berstatus aktif
        if ($listing->status !== 'ACTIVE') {
            return redirect()->route('marketplace.index')
                ->with('warning', 'Listing ini sudah tidak aktif atau telah ditutup oleh penjual.');
        }

        $listing->load(['seller', 'variant.product.category']);

        // 3. Ambil data kalkulasi resmi Marketplace (selalu EXCLUDE, ditanggung Buyer)
        $feeCalculation = $this->feeCalculationService->calculateForMarketplace((float) $listing->price);

        // 4. Periksa ketersediaan rekening payout seller (TC-09-06)
        $hasSellerPayout = $this->marketplaceService->validateSellerPayoutAvailable($listing->seller);

        // 5. Token submission unik untuk idempotency
        $submissionToken = (string) Str::uuid();

        return view('marketplace.checkout', [
            'listing' => $listing,
            'feeCalculation' => $feeCalculation,
            'hasSellerPayout' => $hasSellerPayout,
            'submissionToken' => $submissionToken,
        ]);
    }

    /**
     * Konfirmasi kesepakatan final dan bentuk transaksi WAITING_PAYMENT.
     */
    public function confirm(CheckoutConfirmRequest $request, Listing $listing): RedirectResponse
    {
        $user = $request->user();
        $token = $request->input('submission_token');
        $expectedPrice = (float) $request->input('expected_price');

        try {
            $transaction = $this->marketplaceService->checkout(
                $user,
                $listing,
                $token,
                $expectedPrice
            );

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Kesepakatan final berhasil dibentuk! Transaksi menunggu pembayaran escrow.');
        } catch (InvalidArgumentException $e) {
            return redirect()->route('marketplace.show', $listing)
                ->with('warning', $e->getMessage());
        } catch (LogicException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}
